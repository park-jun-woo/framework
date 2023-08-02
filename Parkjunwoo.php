<?php
use utils\Security;

/**
 * Parkjunwoo Framework는 간결하고 강력한 구문을 가진 웹 어플리케이션 프레임워크입니다.
 * PHP Version 8.0
 * @name Parkjunwoo Framework Version 1.0 zeolite
 * @package Parkjunwoo
 * @see https://github.com/park-jun-woo/parkjunwoo/ The Parkjunwoo GitHub project
 * @author Park Jun woo <mail@parkjunwoo.com>
 * @copyright 2023 parkJunwoo.com
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class Parkjunwoo{
	protected static Parkjunwoo $man;
	/**
	 * Parkjunwoo Framework를 실행합니다.
	 * @param array $app 실행할 어플리케이션 코드 배열
	 */
	public static function walk(array $app){self::$man = new Parkjunwoo($app);}
	/**
	 * Parkjunwoo Framework 객체
	 * @return Parkjunwoo 프레임워크 객체
	 */
	public static function man(){return self::$man;}
	/**
	 * 앱 정보 조회
	 * @param string $key 키
	 * @return array|string 값 
	 */
	public static function app(string $key){return self::$man->app[$key];}
	/**
	 * 어플리케이션 루트 경로
	 * @return string 루트 경로
	 */
	public static function path(string $key="root"):string{
		if(array_key_exists($key, self::$man->server["path"])){return self::$man->server["path"][$key];}else{return "";}
	}
	/**
	 * 어플리케이션 권한 배열
	 * @return array 권한 배열
	 */
	public static function permissions():array{return self::$man->server["permissions"];}
	/**
	 * 개인키
	 * @return string 루트 경로
	 */
	public static function privateKey():string{return self::$man->server["privateKey"];}
	/**
	 * 개인키
	 * @return string 루트 경로
	 */
	public static function publicKey():string{return self::$man->server["publicKey"];}
	
	protected Controller $controller;
	protected string $path;
	protected array $app, $server;
	/**
     * Parkjunwoo Framework 생성자
	 * @param array $app 실행할 어플리케이션 코드 배열
	 */
	protected function __construct(array &$app){
		$this->app = $app;
		//클래스 자동 로더 등록
		spl_autoload_register([$this,"autoload"]);
		//APCU 사용 가능 여부 확인
		if(!function_exists("apcu_enabled")){
			if(!version_compare(PHP_VERSION, "8.0.0", ">=")){echo "Parkjunwoo 프레임워크는 PHP 8.0 이상에서 정상적으로 동작합니다.";exit;}
			echo "APCU 모듈을 설치해주세요.";exit;
		}
		//APCU 메모리에서 서버 배열을 불러올 수 없으면 리셋합니다.
		if(!apcu_exists($this->app["name"]."-server")){$this->reset();}else{$this->server = apcu_fetch($this->app["name"]."-server");}
		//매개 변수 값 남용 방지.
		Security::clearVars();
		//SQL인젝션 공격 필터링
		Security::sqlInjectionClean($_GET);
		Security::sqlInjectionClean($_POST);
		//요청 분석
		$request = new Request();
		//구문 분석된 주소에 대한 컨트롤러를 생성하고 리소스 메서드를 호출
		if(array_key_exists($request->uri(), $this->app["controllers"][$request->method().$request->type()])){
			$sequences = $this->app["controllers"][$request->method().$request->type()][$this->uri];
			$this->route = $this->uri;
		}else{
			foreach($this->app["controllers"][$this->method][$this->type] as $pattern=>$sequences){
				if(substr($pattern, -1)!=="/"){$pattern .= "/";}$matches = null;
				if(preg_match("/^".preg_replace("/\[([^\/]+)\]/i", "(?P<$1>[^\/]+)", str_replace("/", "\/", $pattern))."$/i",$this->uri,$matches)){
					foreach($matches as $key=>$value){if(is_string($key)){$_GET[$key] = $value;}}
					$this->route = $pattern;break;
				}
			}
			//라우터를 찾을 수 없다면
			if(!isset($this->route)){$this->route = "404";$sequences = [["method"=>"view","layout"=>"none","view"=>"404"]];}
		}
		//라우트 한 컨트롤러 실행
		$this->controller = new Controller($request, $sequences);
	}
	/**
	 * 시스템 리셋
	 */
	public function reset(){
		//App 유효한지 확인
		if(!array_key_exists("name", $this->app)){echo "name에 어플리케이션 이름을 영문으로 입력해 주세요.";exit;}
		if(!array_key_exists("domain", $this->app)){echo "domain에 쿠키 설정에 입력할 도메인을 입력해 주세요.";exit;}
		if(!array_key_exists("path", $this->app)){echo "path 배열을 입력해 주세요.";exit;}
		if(!array_key_exists("servers", $this->app)){echo "servers 배열을 입력해 주세요.";exit;}
		if(!array_key_exists("permissions", $this->app)){echo "permissions 배열을 입력해 주세요.";exit;}
		if(!array_key_exists("config", $this->app)){echo "config 배열을 입력해 주세요.";exit;}
		if(!array_key_exists("messages", $this->app)){echo "messages 배열을 입력해 주세요.";exit;}
		if(!array_key_exists("apps", $this->app)){echo "apps 배열을 입력해 주세요.";exit;}
		if(!is_array($this->app["path"])){echo "path는 배열이어야 합니다.";exit;}
		if(!is_array($this->app["servers"])){echo "servers는 배열이어야 합니다.";exit;}
		if(!is_array($this->app["permissions"])){echo "permissions는 배열이어야 합니다.";exit;}
		if(!is_array($this->app["config"])){echo "config는 배열이어야 합니다.";exit;}
		if(!is_array($this->app["messages"])){echo "messages는 배열이어야 합니다.";exit;}
		if(!is_array($this->app["apps"])){echo "apps는 배열이어야 합니다.";exit;}
		if(!array_key_exists("root", $this->app["path"])){echo "path[\"root\"]를 입력해 주세요.";exit;}
		if(!array_key_exists("cache", $this->app["path"])){echo "path[\"cache\"]를 입력해 주세요.";exit;}
		if(!array_key_exists("log", $this->app["path"])){echo "path[\"log\"]를 입력해 주세요.";exit;}
		if(!array_key_exists("session", $this->app["path"])){echo "path[\"session\"]를 입력해 주세요.";exit;}
		if(!array_key_exists("blacklist", $this->app["path"])){echo "path[\"blacklist\"]를 입력해 주세요.";exit;}
		if(!array_key_exists("token-expire", $this->app["config"])){echo "config[\"token-expire\"]를 정수로 입력해 주세요.";exit;}
		if(!is_int($this->app["config"]["token-expire"])){echo "config[\"token-expire\"]는 정수여야 합니다.";exit;}
		if(!array_key_exists("session-expire", $this->app["config"])){echo "config[\"session-expire\"]를 입력해 주세요.";exit;}
		if(!is_int($this->app["config"]["session-expire"])){echo "config[\"session-expire\"]는 정수여야 합니다.";exit;}
		//경로 설정
		$root = realpath(str_replace(basename($_SERVER["SCRIPT_FILENAME"]),"",realpath($_SERVER["SCRIPT_FILENAME"]))."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR);
		$this->server["path"] = ["root"=>(substr($this->app["path"]["root"],0,1)===DIRECTORY_SEPARATOR)?$this->app["path"]["root"]:$root.$this->app["path"]["root"]];
		foreach($this->app["path"] as $key=>$value){
			switch($key){
				case "root":break;
				default:
					$this->server["path"][$key] = $this->server["path"]["root"].$value;
					//끝 문자가 DIRECTORY_SEPARATOR가 아니라면 에러
					if(substr($this->server["path"][$key], -1)!=DIRECTORY_SEPARATOR){echo "PATH[\"$key\"]는 '".DIRECTORY_SEPARATOR."'로 끝나야 합니다.";exit;}
					//경로가 없거나 읽거나 쓸수 없다면 안내문 출력 후 종료
					if(!file_exists($this->server["path"][$key]) || !is_readable($this->server["path"][$key]) || !is_writable($this->server["path"][$key])){
						echo "{$this->server["path"][$key]} 폴더를 생성하고 읽고 쓰기가 가능한 user로 소유권을 변경하세요.<br>mkdir {$this->server["path"][$key]}<br>";
						if(strpos($_SERVER['SERVER_SOFTWARE'], 'Apache')!==false){
							echo "chown apache:apache {$this->server["path"][$key]}";
						}elseif(strpos($_SERVER['SERVER_SOFTWARE'], 'nginx')!==false){
							echo "chown nginx:nginx {$this->server["path"][$key]}";
						}
						exit;
					}break;
			}
		}
		
		//RSA 키 쌍 생성
		list($this->server["privateKey"], $this->server["publicKey"]) = Security::generateRSA();
		//분산서버 목록 확인 및 초기 통신
		$this->server["servers"] = [];
		foreach($this->app["servers"] as $key=>$value){
			
		}
		//권한 배열에 사용자 정의 권한 레벨 $this->app["permissions"]를 합치기
		$this->server["permissions"] = [User::GUEST=>"guest", User::MEMBER=>"member", User::STAFF=>"staff", User::ADMIN=>"admin", User::SYSTEM=>"system"];
		foreach($this->app["permissions"] as $key=>$value){
			switch($key){
				case User::GUEST:case User::MEMBER:case User::STAFF:case User::ADMIN:case User::SYSTEM:
					echo "permissions[\"{$key}\"]는 사용할 수 없는 권한키입니다.";exit;
				default:
					if(($key&($key-1))==0&&$key>0){echo "permissions[\"{$key}\"]는 2의 승수여야 합니다.";exit;}
					if($key>User::STAFF){echo "permissions[\"{$key}\"]는 ".number_format(User::STAFF)."보다 작아야합니다.";exit;}
					break;
			}
			$this->server["permissions"][$key] = $value;
		}
		apcu_store($this->app["name"]."-server", $this->server);
	}
	/**
	 * 클래스 파일 자동 로더
	 * @param string $className 클래스명
	 */
	public function autoload(string $className){
		$className = ltrim($className, "\\");
		$fileName = "";
		$namespace = "";
		if($lastNsPos = strpos($className, "\\")) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = str_replace("\\", DIRECTORY_SEPARATOR, substr($className, $lastNsPos+1));
			$fileName = str_replace("\\", DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace("_",DIRECTORY_SEPARATOR,$className).".php";
		require str_replace(basename(__FILE__),"",realpath(__FILE__))."..".DIRECTORY_SEPARATOR.$fileName;
	}
}