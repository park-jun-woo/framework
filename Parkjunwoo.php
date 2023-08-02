<?php
use utils\Security;

/**
 * Parkjunwoo Framework는 간결하고 강력한 구문을 가진 웹 어플리케이션 프레임워크입니다.
 * PHP Version 8.0
 * @name Parkjunwoo Framework Version 1.0 zeolite
 * @package Parkjunwoo
 * @see https://github.com/park-jun-woo/framework The Parkjunwoo GitHub project
 * @author Park Jun woo <mail@parkjunwoo.com>
 * @copyright 2023 parkJunwoo.com
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class Parkjunwoo{
	protected static Parkjunwoo $man;
	protected Controller $controller;
	protected string $path;
	protected array $code, $server, $thisApp;
	/**
     * Parkjunwoo Framework 생성자
	 * @param array $app 실행할 어플리케이션 코드 배열
	 */
	protected function __construct(array &$code){
		self::$man = $this;
		$this->code = $code;
		//클래스 자동 로더 등록
		spl_autoload_register([$this,"autoload"]);
		//APCU 사용 가능 여부 확인
		if(!function_exists("apcu_enabled")){
			if(!version_compare(PHP_VERSION, "8.0.0", ">=")){$this->error("Parkjunwoo 프레임워크는 PHP 8.0 이상에서 정상적으로 동작합니다.");}
			$this->error("APCU 모듈을 설치해주세요.");
		}
		//APCU 메모리에서 서버 배열을 불러올 수 없으면 리셋합니다.
		if(!apcu_exists($this->code["name"]."-server")){$this->reset();}else{$this->server = apcu_fetch($this->code["name"]."-server");}
		//매개 변수 값 남용 방지.
		Security::clearVars();
		//SQL인젝션 공격 필터링
		Security::sqlInjectionClean($_GET);
		Security::sqlInjectionClean($_POST);
		//접속한 도메인으로 앱 조회
		if(!array_key_exists($domain = strtolower($_SERVER["SERVER_NAME"]),$this->server["domain-app"])){
			apcu_delete($this->code["name"]."-server");
			$this->error("앱에 도메인(\"{$domain}\")이 입력되어 있지 않습니다.도메인을 입력해주세요.");
		}
		$this->thisApp = $this->code["apps"][$this->server["domain-app"][$domain]];
		//요청 분석
		$request = new Request($this);
		//라우트 한 컨트롤러 실행
		$this->controller = new Controller($request);
	}
	/**
	 * Parkjunwoo Framework를 실행합니다.
	 * @param array $app 실행할 어플리케이션 코드 배열
	 */
	public static function walk(array $code){
		if(!isset(self::$man)){new Parkjunwoo($code);}
	}
	/**
	 * 접속한 도메인의 앱 코드에 라우터가 있는지 확인
	 * @param string $method 메서드
	 * @param string $type 컨텐트 타입
	 * @return bool 존재여부
	 */
	public function isRouter(string $method, string $type):bool{
		return array_key_exists($method.$type, $this->thisApp);
	}
	/**
	 * 접속한 도메인의 앱 코드에서 라우터 배열 조회
	 * @param string $method 메서드
	 * @param string $type 컨텐트 타입
	 * @return array 라우터 배열
	 */
	public function router(string $method, string $type):array{
		return $this->thisApp[$method.$type];
	}
	/**
	 * 앱 코드 조회
	 * @param string $key 키 또는 도메인
	 * @return array 앱 코드 배열
	 */
	public function app(string $key):array{
		if(array_key_exists($key,$this->code["apps"])){return $this->code["apps"][$key];}
	}
	/**
	 * 어플리케이션 이름
	 * @return string 어플리케이션 이름
	 */
	public function name():string{
		return $this->code["name"];
	}
	/**
	 * 세션 도메인
	 * @return string 도메인
	 */
	public function domain():string{
		return $this->code["domain"];
	}
	/**
	 * 어플리케이션 설정값
	 * @return string 루트 경로
	 */
	public function config(string $key){
		if(!array_key_exists($key, $this->code["config"])){$this->error("config[\"{$key}\"]이 입력되어 있지 않습니다.");}
		return $this->code["config"][$key];
	}
	/**
	 * 어플리케이션 루트 경로
	 * @return string 루트 경로
	 */
	public function path(string $key="root"):string{
		if(array_key_exists($key, $this->server["path"])){return $this->server["path"][$key];}else{return "";}
	}
	/**
	 * 어플리케이션 권한 배열
	 * @return array 권한 배열
	 */
	public function permissions():array{
		return $this->server["permissions"];
	}
	/**
	 * 개인키
	 * @return string 루트 경로
	 */
	public function privateKey():string{
		return $this->server["privateKey"];
	}
	/**
	 * 개인키
	 * @return string 루트 경로
	 */
	public function publicKey():string{
		return $this->server["publicKey"];
	}
	/**
	 * 시스템 리셋
	 */
	public function reset(){
		//App 유효한지 확인
		if(!array_key_exists("name", $this->code)){$this->error("name에 어플리케이션 이름을 영문으로 입력해 주세요.");}
		if(!array_key_exists("domain", $this->code)){$this->error("domain에 쿠키 설정에 입력할 도메인을 입력해 주세요.");}
		if(!array_key_exists("path", $this->code)){$this->error("path 배열을 입력해 주세요.");}
		if(!array_key_exists("servers", $this->code)){$this->error("servers 배열을 입력해 주세요.");}
		if(!array_key_exists("permissions", $this->code)){$this->error("permissions 배열을 입력해 주세요.");}
		if(!array_key_exists("config", $this->code)){$this->error("config 배열을 입력해 주세요.");}
		if(!array_key_exists("messages", $this->code)){$this->error("messages 배열을 입력해 주세요.");}
		if(!array_key_exists("apps", $this->code)){$this->error("apps 배열을 입력해 주세요.");}
		if(!is_array($this->code["path"])){$this->error("path는 배열이어야 합니다.");}
		if(!is_array($this->code["servers"])){$this->error("servers는 배열이어야 합니다.");}
		if(!is_array($this->code["permissions"])){$this->error("permissions는 배열이어야 합니다.");}
		if(!is_array($this->code["config"])){$this->error("config는 배열이어야 합니다.");}
		if(!is_array($this->code["messages"])){$this->error("messages는 배열이어야 합니다.");}
		if(!is_array($this->code["apps"])){$this->error("apps는 배열이어야 합니다.");}
		if(!array_key_exists("root", $this->code["path"])){$this->error("path[\"root\"]를 입력해 주세요.");}
		if(!array_key_exists("cache", $this->code["path"])){$this->error("path[\"cache\"]를 입력해 주세요.");}
		if(!array_key_exists("log", $this->code["path"])){$this->error("path[\"log\"]를 입력해 주세요.");}
		if(!array_key_exists("session", $this->code["path"])){$this->error("path[\"session\"]를 입력해 주세요.");}
		if(!array_key_exists("blacklist", $this->code["path"])){$this->error("path[\"blacklist\"]를 입력해 주세요.");}
		if(!array_key_exists("token-expire", $this->code["config"])){$this->error("config[\"token-expire\"]를 정수로 입력해 주세요.");}
		if(!is_int($this->code["config"]["token-expire"])){$this->error("config[\"token-expire\"]는 정수여야 합니다.");}
		if(!array_key_exists("session-expire", $this->code["config"])){$this->error("config[\"session-expire\"]를 입력해 주세요.");}
		if(!is_int($this->code["config"]["session-expire"])){$this->error("config[\"session-expire\"]는 정수여야 합니다.");}
		//경로 설정
		$root = realpath(str_replace(basename($_SERVER["SCRIPT_FILENAME"]),"",realpath($_SERVER["SCRIPT_FILENAME"]))."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$this->server["path"] = ["root"=>(substr($this->code["path"]["root"],0,1)===DIRECTORY_SEPARATOR)?$this->code["path"]["root"]:$root.$this->code["path"]["root"]];
		foreach($this->code["path"] as $key=>$value){
			switch($key){
				case "root":break;
				default:
					$this->server["path"][$key] = $this->server["path"]["root"].$value;
					//끝 문자가 DIRECTORY_SEPARATOR가 아니라면 에러
					if(substr($this->server["path"][$key], -1)!=DIRECTORY_SEPARATOR){$this->error("PATH[\"$key\"]는 '".DIRECTORY_SEPARATOR."'로 끝나야 합니다.");}
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
		foreach($this->code["servers"] as $key=>$value){
			
		}
		//권한 배열에 사용자 정의 권한 레벨 $this->code["permissions"]를 합치기
		$this->server["permissions"] = [User::GUEST=>"guest", User::MEMBER=>"member", User::STAFF=>"staff", User::ADMIN=>"admin", User::SYSTEM=>"system"];
		foreach($this->code["permissions"] as $key=>$value){
			switch($key){
				case User::GUEST:case User::MEMBER:case User::STAFF:case User::ADMIN:case User::SYSTEM:
					$this->error("permissions[\"{$key}\"]는 사용할 수 없는 권한키입니다.");
				default:
					if(($key&($key-1))==0&&$key>0){$this->error("permissions[\"{$key}\"]는 2의 승수여야 합니다.");}
					if($key>User::STAFF){$this->error("permissions[\"{$key}\"]는 ".number_format(User::STAFF)."보다 작아야합니다.");}
					break;
			}
			$this->server["permissions"][$key] = $value;
		}
		//도메인 앱아이디 연결맵 구성
		$this->server["domain-app"] = [];
		foreach($this->code["apps"] as $appId=>$app){
			if(!array_key_exists("type",$app)){$this->error("apps[\"$appId\"]의 type을 입력하세요.");}
			switch($app["type"]){
				default:$this->error("apps[\"$appId\"]에 입력한 '{$app["type"]}'은 지원하지 않는 앱 type입니다.<br>지원타입: mysql, papago, chatgpt, parkjunwoo");
				case "mysql":break;
				case "papago":break;
				case "chatgpt":break;
				case "parkjunwoo":
					foreach($app["domain"] as $domain){$this->server["domain-app"][$domain] = $appId;}
					break;
			}
		}
		apcu_store($this->code["name"]."-server", $this->server);
	}
	protected function error(string $message){
		echo $message;exit;
	}
	/**
	 * 클래스 파일 자동 로더
	 * @param string $className 클래스명
	 */
	protected function autoload(string $className){
		$className = ltrim($className, "\\");
		$fileName = "";
		$namespace = "";
		if($lastNsPos = strpos($className, "\\")) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = str_replace("\\", DIRECTORY_SEPARATOR, substr($className, $lastNsPos+1));
			$fileName = str_replace("\\", DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace("_",DIRECTORY_SEPARATOR,$className).".php";
		require str_replace(basename(__FILE__),"",realpath(__FILE__)).$fileName;
	}
}