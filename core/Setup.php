<?php
namespace core;

use SimpleXMLElement;
use util\Debug;
use util\File;
use util\Image;

class Setup{
	protected string $root;
	protected SimpleXMLElement $bml;
	protected array $code;
	
	public function __construct(string $source){
		//필수 설치 확인
		$this->required();
		//소스 불러오기
		$this->load($source);
		//코드 기본 골격
		$this->code = [
			"name"=>(string)$this->bml->name,
			"description"=>isset($this->bml->description)?(string)$this->bml->description:(isset($this->bml->attributes()->description)?(string)$this->bml->attributes()->description:""),
			"domain"=>(string)$this->bml->domain,
			"path"=>[],
			"config"=>[],
			"permission"=>[0=>"member", 61=>"writer", 62=>"admin", 63=>"system"],
			"user"=>["guest"=>0],
			"message"=>[],
			"app"=>[],
			"domain-app"=>[],
		];
		//경로 설정
		$this->path($source);
		//권한 설정
		$this->permission();
		//설정값 설정
		$this->config();
		//언어팩 설정
		$this->language();
		//코드 배열에 앱 검증 및 등록
		foreach($this->bml->app as $app){
			if(!isset($app->attributes()->id) || (string)$app->attributes()->id==""){Debug::error("<app> 태그에 id 속성을 입력해 주세요.");}
			if(!isset($app->attributes()->type) || (string)$app->attributes()->type==""){$app->addAttribute("type", "parkjunwoo");}
			switch((string)$app->attributes()->type){
				case "parkjunwoo":$this->parkjunwoo($app);break;
				case "mysql":$this->mysql($app);break;
			}
		}
		//폴더 및 파일 생성
		$this->generate();
		//완료
		$this->log("Install Complete!");
		
	}
	/**
	 * 필수 설치 확인
	 */
	protected function required(){
		$this->log("필수 설치 확인");
		//PHP 버전 확인
		if(!version_compare(PHP_VERSION, "8.0.0", ">=")){Debug::error("Parkjunwoo 프레임워크는 PHP 8.0 이상에서 정상적으로 동작합니다.");}
		//APCU 사용 가능 여부 확인
		if(!extension_loaded("apcu")){Debug::error("APCU 모듈을 설치해주세요.");}
		//세마포어 설치 여부 확인
		if(!extension_loaded("sysvsem")){Debug::error("세마포어 모듈을 설치해주세요.");}
		//Imagick 설치 여부 확인
		if(!extension_loaded("imagick")){Debug::error("Imagick 모듈을 설치해주세요.");}
	}
	/**
	 * 소스파일 불러오기
	 * @param string $source 소스 경로
	 */
	protected function load(string $source){
		$this->log("소스 불러오기: {$source}");
		//소스 파일 존재 여부 확인
		if(!file_exists($source)){Debug::error("{$source} 파일이 없습니다. BML 파일 경로를 정확히 입력해주세요.");}
		//소스코드 불러오기
		$handle = fopen($source,"r");$result = fread($handle,filesize($source));fclose($handle);
		$this->bml = simplexml_load_string($result,"SimpleXMLElement",LIBXML_NOCDATA | LIBXML_NOBLANKS);
		//소스코드 간단한 검증
		if($this->bml===false){Debug::error("{$source}는 BML이 아닙니다. 올바른 BML 파일 경로를 입력해주세요.");}
		if($this->bml->getName()!="project"){Debug::error("{$source}는 BML이 아닙니다. 올바른 BML 파일 경로를 입력해주세요.");}
		if(!isset($this->bml->id) && !isset($this->bml->attributes()->id)){Debug::error("<project> 태그에 프로젝트 아이디를 id 속성이나 <id>태그로 입력하세요.");}
		if(!isset($this->bml->name)){Debug::error("<name> 태그에 프로젝트 이름를 입력해 주세요.");}
		if(!isset($this->bml->domain)){Debug::error("<domain>.domain.com</domain>에 쿠키 설정에 입력할 도메인을 입력해 주세요.");}
		if(!isset($this->bml->path)){Debug::error("<path> 태그를 작성해 주세요.");}
		if(count($this->bml->path)>1){Debug::error("<path> 태그를 하나만 작성해 주세요.");}
		if(!isset($this->bml->config)){Debug::error("<config> 태그를 작성해 주세요.");}
		if(count($this->bml->config)>1){Debug::error("<config> 태그를 하나만 작성해 주세요.");}
		if(!isset($this->bml->app)){Debug::error("<app> 태그를 적어도 하나 작성해 주세요.");}
	}
	/**
	 * 경로 설정
	 */
	protected function path(string $source){
		$this->log("경로 설정");
		//루트 경로
		$path = $this->bml->path->attributes();
		$root = realpath(str_replace(basename($source),"",realpath($source))).DIRECTORY_SEPARATOR;
		$this->root = isset($path->root)?(string)$path->root:"";
		$this->root = realpath((substr($this->root,0,1)===DIRECTORY_SEPARATOR)?$this->root:$root.$this->root).DIRECTORY_SEPARATOR;
		$this->log("Root: {$this->root}");
		$this->code["path"]["root"] = $this->root;
		$this->code["path"]["upload"] = $this->root.(isset($path->upload)?(string)$path->upload:"upload".DIRECTORY_SEPARATOR);
		$this->code["path"]["data"] = $this->root.(isset($path->data)?(string)$path->data:"data".DIRECTORY_SEPARATOR);
	}
	/**
	 * 권한 설정
	 */
	protected function permission(){
		$this->log("권한 설정");
		//권한-권한명 검증 및 등록
		if(isset($this->bml->permission)){
			foreach($this->bml->permission as $permission){
				if((string)$permission==""){Debug::error("<permission> 태그 안에 권한 이름을 입력해 주세요. 예: <permission id=\"2\">sample</permission>");}
				if(isset($permission->attributes()->id) && (string)$permission->attributes()->id!=""){
					$permissionId = (int)$permission->attributes()->id;
					if($permissionId<1 || $permissionId>60){Debug::error("<permission> 태그의 아이디는 1부터 60까지만 가능합니다.");}
				}else{
					$permissionId = 1;
					while(array_key_exists($permissionId, $this->code["permission"]) && $permissionId<=60){$permissionId++;}
					if($permissionId>60){Debug::error("<permission> 태그가 너무 많습니다.");}
				}
				$this->code["permission"][$permissionId] = (string)$permission;
			}
		}
		//권한명-권한 배열
		$permission = [];
		foreach($this->code["permission"] as $key=>$value){
			$this->code["user"][$value] = 1<<$key;
			$permission[1<<$key] = $value;
		}
		$this->code["permission"] = $permission;
	}
	/**
	 * 설정값 설정
	 */
	protected function config(){
		$this->log("설정값 설정");
		$config = $this->bml->config->attributes();
		$this->code["config"]["token-expire"] = isset($config->{"token-expire"})?(int)$config->{"token-expire"}:3600;
		$this->code["config"]["session-expire"] = isset($config->{"session-expire"})?(int)$config->{"session-expire"}:15552000;
	}
	/**
	 * 언어팩 설정
	 */
	protected function language(){
		$this->log("언어팩 설정");
		//언어팩 검증 및 등록
		foreach($this->bml->message as $message){
			if(!isset($message->attributes()->id) || (string)$message->attributes()->id==""){Debug::error("<message> 태그에 id 속성을 입력해 주세요.");}
			if(!isset($message->ko) || (string)$message->ko==""){Debug::error("<message> 태그에 <ko> 태그를 입력해 주세요.");}
			$this->code["message"][$messageId = (string)$message->attributes()->id] = ["ko"=>(string)$message->ko];
			if(isset($message->en) && (string)$message->en!=""){$this->code["message"][$messageId]["en"] = (string)$message->en;}
		}
	}
	/**
	 * parkjunwoo 앱 설정
	 */
	protected function parkjunwoo(SimpleXMLElement $app){
		$id = (string)$app->attributes()->id;
		$type = (string)$app->attributes()->type;
		$this->log("parkjunwoo 앱(id:{$id}) 추가");
		//앱 코드 기본 골격
		$code = [
			"id"=>$id,
			"type"=>$type,
			"name"=>isset($app->name)?(string)$app->name:(isset($app->attributes()->name)?(string)$app->attributes()->name:$this->code["name"]),
			"description"=>isset($app->description)?(string)$app->description:(isset($app->attributes()->description)?(string)$app->attributes()->description:$this->code["description"]),
			"domain"=>[],
			"icon"=>isset($app->icon)?(string)$app->icon:"icon.png",
		];
		//라우터 배열 생성
		foreach(["get","post","put","delete"] as $method){
			foreach(["html","json"] as $contentType){
				$code["{$method}-{$contentType}"] = [];
			}
		}
		//도메인 입력
		if(isset($app->domain)){
			foreach($app->domain as $domain){$code["domain"][] = (string)$domain;}
		}else{
			if($id=="www"){$code["domain"][] = substr($this->code["domain"], 0, 1)=="."?substr($this->code["domain"], 1):$this->code["domain"];}
			$code["domain"][] = substr($this->code["domain"], 0, 1)=="."?"{$id}{$this->code["domain"]}":"{$id}.{$this->code["domain"]}";
		}
		//라우터 입력
		if(isset($app->route)){
			foreach($app->route as $route){
				//id를 입력 안했다면 오류 처리
				if(!isset($route->attributes()->id) || (string)$route->attributes()->id==""){Debug::error("<route> 태그에 id 속성을 입력해 주세요.");}
				//메소드, 기본값은 get
				if(!isset($route->attributes()->method) || (string)$route->attributes()->method==""){$method = "get";}
				else{$method = (string)$route->attributes()->method;}
				//컨텐트 타입, 기본값은 html
				if(!isset($route->attributes()->type) || (string)$route->attributes()->type==""){$contentType = "html";}
				else{$contentType = (string)$route->attributes()->type;}
				$routeId = (string)$route->attributes()->id;
				//라우트 코드 기본 골격
				$routeCode = [];
				//라우트 별로 시퀀스 추가
				if(isset($route->sequence)){
					foreach($route->sequence as $sequence){
						if(!isset($sequence->attributes()->method) || (string)$sequence->attributes()->method==""){Debug::error("<sequence> 태그에 method 속성을 입력해 주세요.");}
						$smethod = (string)$sequence->attributes()->method;
						switch($smethod){
							case "view":
								if(!isset($sequence->attributes()->layout) || (string)$sequence->attributes()->layout=="")
								{Debug::error("<sequence method=\"view\"> 태그에 layout 속성을 입력해 주세요.");}
								if(!isset($sequence->attributes()->view) || (string)$sequence->attributes()->view=="")
								{Debug::error("<sequence method=\"view\"> 태그에 view 속성을 입력해 주세요.");}
								$routeCode[] = [
									"method"=>$smethod,
									"layout"=>(string)$sequence->attributes()->layout,
									"view"=>(string)$sequence->attributes()->view
								];
								break;
						}
					}
				}
				//사용자 권한
				$permission = 0;
				if(isset($route->user)){
					foreach($route->user as $user){
						$permission |= $this->code["user"][(string)$user];
					}
				}else if(isset($route->attributes()->user) && (string)$route->attributes()->user!=""){
					$users = explode("|",(string)$route->attributes()->user);
					foreach($users as $user){$permission |= $this->code["user"][$user];}
				}
				$code["{$method}-{$contentType}"][$routeId][$permission] = $routeCode;
			}
		}
		//Route "404" 없으면 추가
		if(!array_key_exists("404", $code["get-html"])){
			$code["get-html"]["404"] = [
				0=>[["method"=>"view", "layout"=>"none", "view"=>"404"]]
			];
		}
		//Route "/" 없으면 추가
		if(!array_key_exists("/", $code["get-html"])){
			$code["get-html"]["/"] = [
				0=>[["method"=>"view", "layout"=>"none", "view"=>"index"]]
			];
		}
		//도메인-앱 매칭맵 구성
		foreach($code["domain"] as $domain){$this->code["domain-app"][$domain] = $id;}
		//코드에 앱 코드 등록
		$this->code["app"][$id] = $code;
	}
	/**
	 * mysql 앱 설정
	 */
	protected function mysql(SimpleXMLElement $app){
		$id = (string)$app->attributes()->id;
		$type = (string)$app->attributes()->type;
		$this->log("mysql 앱(id:{$id}) 추가");
		//앱 코드 기본 골격
		$code = [
			"id"=>$id,
			"type"=>$type,
			"name"=>isset($app->name)?(string)$app->name:(isset($app->attributes()->name)?(string)$app->attributes()->name:""),
			"charset"=>"utf8",
		];
		//서버 IP
		if(isset($app->attributes()->servername) && (string)$app->attributes()->servername!="")
		{$code["servername"] = (string)$app->attributes()->servername;}
		else if(isset($app->servername) && (string)$app->servername!="")
		{$code["servername"] = (string)$app->servername;}
		else{$code["servername"] = "localhost";}
		//아이디가 없다면
		if(isset($app->attributes()->username) && (string)$app->attributes()->username!="")
		{$code["username"] = (string)$app->attributes()->username;}
		else if(isset($app->username) && (string)$app->username!="")
		{$code["username"] = (string)$app->username;}
		else{$code["username"] = strtolower($this->code["name"]);}
		//비밀번호가 없다면
		if(isset($app->attributes()->password) && (string)$app->attributes()->password!="")
		{$code["password"] = (string)$app->attributes()->password;}
		else if(isset($app->password) && (string)$app->password!="")
		{$code["password"] = (string)$app->password;}
		else{$code["password"] = base64_encode(hash("sha256",time().random_bytes(32)));}
		//데이터베이스 명이 없다면
		if(isset($app->attributes()->databasename) && (string)$app->attributes()->databasename!="")
		{$code["databasename"] = (string)$app->attributes()->databasename;}
		else if(isset($app->databasename) && (string)$app->databasename!="")
		{$code["databasename"] = (string)$app->databasename;}
		else{$code["databasename"] = strtolower($this->code["name"]);}
		//테이블 엔진명이 없다면
		if(isset($app->attributes()->engine) && (string)$app->attributes()->engine!="")
		{$code["engine"] = (string)$app->attributes()->engine;}
		else if(isset($app->engine) && (string)$app->engine!="")
		{$code["engine"] = (string)$app->engine;}
		else{$code["engine"] = "InnoDB";}
		//코드에 앱 코드 등록
		$this->code["app"][$id] = $code;
	}
	/**
	 * 폴더 및 파일 생성
	 */
	protected function generate(){
		$this->log("폴더 및 파일 생성");
		//경로 폴더 생성 및 소유권 변경
		foreach($this->code["path"] as $path){
			if(!file_exists($path)){mkdir($path, 0755);chown($path, "apache");}
		}
		//어플리케이션 리소스 폴더 생성
		if(!file_exists($path = "{$this->root}public")){mkdir($path, 0755);}
		//index 파일
		$indexPHP = "<?PHP
	\$start_time = microtime();
	require \"..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."app.php\";
	\$end_time = microtime();
	echo \"<p>실행시간: \".\$end_time-\$start_time.\"s</p>\";
?>";
		foreach($this->code["app"] as $id=>$app){
			if($app["type"]!="parkjunwoo"){continue;}
			$publicPath = "{$this->root}public".DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR;
			echo "create folder(path: {$publicPath})".PHP_EOL;
			if(!file_exists($publicPath)){mkdir($publicPath, 0755);}
			if(!file_exists($publicPath."assets")){mkdir($publicPath."assets", 0755);}
			if(!file_exists($publicPath."images")){mkdir($publicPath."images", 0755);}
			if(!file_exists($publicPath."images".DIRECTORY_SEPARATOR."icon")){mkdir($publicPath."images".DIRECTORY_SEPARATOR."icon", 0755);}
			if(!file_exists($publicPath."scripts")){mkdir($publicPath."scripts", 0755);}
			if(!file_exists($publicPath."styles")){mkdir($publicPath."styles", 0755);}
			File::write("{$publicPath}index.php", $indexPHP);
			echo "create file(path: {$publicPath}index.php)".PHP_EOL;
			if(isset($app["icon"]) && file_exists($iconPath = $this->root.$app["icon"])){
				echo "create image(from: {$iconPath})".PHP_EOL;
				Image::resize($iconPath,$publicPath."favicon.ico",72);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."72x72.png",72);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."96x96.png",96);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."144x144.png",144);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."192x192.png",192);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."120x120.png",120);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."180x180.png",180);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."76x76.png",76);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."152x152.png",152);
				Image::resize($iconPath,$publicPath."images".DIRECTORY_SEPARATOR."icon".DIRECTORY_SEPARATOR."167x167.png",167);
			}
		}
		//app.php 파일 생성
		File::write("{$this->root}app.php", "<?PHP
	require \"".DIRECTORY_SEPARATOR."home".DIRECTORY_SEPARATOR."framework".DIRECTORY_SEPARATOR."Parkjunwoo.php\";
	Parkjunwoo::walk(".Debug::print($this->code).");
?>");
	}
	protected function log(string $message){
		echo $message.PHP_EOL;
	}
}
?>