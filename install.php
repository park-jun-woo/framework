<?php
require "/home/framework/utils/Debug.php";
require "/home/framework/utils/File.php";
require "/home/framework/utils/Image.php";
use utils\Debug;
use utils\File;
use utils\Image;

switch(count($argv)){
	default:Debug::error("사용법: php install.php /home/sample/sample.bml");
	case 2:$sourcePath = $argv[1];break;
}
//PHP 버전 확인
if(!version_compare(PHP_VERSION, "8.0.0", ">=")){Debug::error("Parkjunwoo 프레임워크는 PHP 8.0 이상에서 정상적으로 동작합니다.");}
//APCU 사용 가능 여부 확인
if(!function_exists("apcu_enabled")){Debug::error("APCU 모듈을 설치해주세요.");}
//Imagick 설치 여부 확인
if(!extension_loaded("imagick")){Debug::error("Imagick을 설치해주세요.");}
//소스 파일 존재 여부 확인
if(!file_exists($sourcePath)){Debug::error("{$sourcePath} 파일이 없습니다. BML 파일 경로를 정확히 입력해주세요.");}
//소스코드 불러오기
$handle = fopen($sourcePath,"r");$result = fread($handle,filesize($sourcePath));fclose($handle);
$bml = simplexml_load_string($result,"SimpleXMLElement",LIBXML_NOCDATA | LIBXML_NOBLANKS);
//소스코드 검증
if($bml===false){Debug::error("{$sourcePath}는 BML이 아닙니다. 올바른 BML 파일 경로를 입력해주세요.");}
if($bml->getName()!="project"){Debug::error("{$sourcePath}는 BML이 아닙니다. 올바른 BML 파일 경로를 입력해주세요.");}
if(!isset($bml->id) && !isset($bml->attributes()->id)){Debug::error("<project> 태그에 프로젝트 아이디를 id 속성이나 <id>태그로 입력하세요.");}
if(!isset($bml->name)){Debug::error("<name> 태그에 프로젝트 이름를 입력해 주세요.");}
if(!isset($bml->domain)){Debug::error("<domain>.domain.com</domain>에 쿠키 설정에 입력할 도메인을 입력해 주세요.");}
if(!isset($bml->path)){Debug::error("<path> 태그를 작성해 주세요.");}
if(count($bml->path)>1){Debug::error("<path> 태그를 하나만 작성해 주세요.");}
if(!isset($bml->config)){Debug::error("<config> 태그를 작성해 주세요.");}
if(count($bml->config)>1){Debug::error("<config> 태그를 하나만 작성해 주세요.");}
if(!isset($bml->app)){Debug::error("<app> 태그를 적어도 하나 작성해 주세요.");}
//루트 경로
$root = realpath(str_replace(basename($sourcePath),"",realpath($sourcePath))).DIRECTORY_SEPARATOR;
$rootPath = isset($bml->path->root)?(string)$bml->path->root:"";
$rootPath = (substr($rootPath,0,1)===DIRECTORY_SEPARATOR)?$rootPath:$root.$rootPath;
//코드 기본 골격
$code = [
	"name"=>(string)$bml->name,
	"description"=>isset($bml->description)?(string)$bml->description:(isset($bml->attributes()->description)?(string)$bml->attributes()->description:""),
	"domain"=>(string)$bml->domain,
	"path"=>[
		"root"=>$rootPath,
		"upload"=>$rootPath.(isset($bml->path->upload)?(string)$bml->path->upload:"upload".DIRECTORY_SEPARATOR),
		"cache"=>$rootPath.(isset($bml->path->cache)?(string)$bml->path->cache:"cache".DIRECTORY_SEPARATOR),
		"log"=>$rootPath.(isset($bml->path->log)?(string)$bml->path->log:"log".DIRECTORY_SEPARATOR),
		"request"=>$rootPath.(isset($bml->path->request)?(string)$bml->path->request:"log".DIRECTORY_SEPARATOR."request".DIRECTORY_SEPARATOR),
		"session"=>$rootPath.(isset($bml->path->session)?(string)$bml->path->session:"log".DIRECTORY_SEPARATOR."session".DIRECTORY_SEPARATOR),
		"blacklist"=>$rootPath.(isset($bml->path->blacklist)?(string)$bml->path->blacklist:"log".DIRECTORY_SEPARATOR."blacklist".DIRECTORY_SEPARATOR),
	],
	"config"=>[
		"token-expire"=>isset($bml->config->{"token-expire"})?(int)$bml->config->{"token-expire"}:3600,
		"session-expire"=>isset($bml->config->{"session-expire"})?(int)$bml->config->{"session-expire"}:15552000,
	],
	"permission"=>[0=>"guest", 1=>"member", 536870912=>"staff", 1073741824=>"admin", 2147483648=>"system"],
	"user"=>[],
	"message"=>[],
	"app"=>[],
	"domain-app"=>[],
];
//사용자 권한 검증 및 등록
if(isset($bml->permission)){
	foreach($bml->permission as $permission){
		if((string)$permission==""){Debug::error("<permission> 태그 안에 권한 이름을 입력해 주세요. 예: <permission id=\"2\">sample</permission>");}
		if(isset($permission->attributes()->id) && (string)$permission->attributes()->id!=""){
			$permissionId = (int)$permission->attributes()->id;
			if(array_key_exists($permissionId, $code["permission"])){Debug::error("<permission> 태그의 아이디는 2부터 가능하며 536870912보다 작은 2의 승수입니다.");}
			if(($permissionId&($permissionId-1))==0&&$permissionId>0){Debug::error("<permission> 태그의 아이디는 2부터 가능하며 536870912보다 작은 2의 승수입니다.");}
		}else{
			$permissionId = 2;
			while(array_key_exists($permissionId, $code["permission"]) && $permissionId<536870912){$permissionId *= 2;}
			if($permissionId>536870912){Debug::error("<permission> 태그가 너무 많습니다.");}
		}
		$code["permission"][$permissionId] = (string)$permission;
	}
}
foreach($code["permission"] as $key=>$value){$code["user"][$value] = $key;}
//코드 배열에 메세지 검증 및 등록
foreach($bml->message as $message){
	if(!isset($message->attributes()->id) || (string)$message->attributes()->id==""){Debug::error("<message> 태그에 id 속성을 입력해 주세요.");}
	if(!isset($message->ko) || (string)$message->ko==""){Debug::error("<message> 태그에 <ko> 태그를 입력해 주세요.");}
	$code["message"][$messageId = (string)$message->attributes()->id] = ["ko"=>(string)$message->ko];
	if(isset($message->en) && (string)$message->en!=""){$code["message"][$messageId]["en"] = (string)$message->en;}
}
//코드 배열에 앱 검증 및 등록
foreach($bml->app as $app){
	if(!isset($app->attributes()->id) || (string)$app->attributes()->id==""){Debug::error("<app> 태그에 id 속성을 입력해 주세요.");}
	if(!isset($app->attributes()->type) || (string)$app->attributes()->type==""){$app->addAttribute("type", "parkjunwoo");}
	$appId = (string)$app->attributes()->id;
	$appType = (string)$app->attributes()->type;
	switch($appType){
		case "parkjunwoo":
			//앱 코드 기본 골격
			$code["app"][$appId] = [
				"id"=>$appId,
				"type"=>$appType,
				"name"=>isset($app->name)?(string)$app->name:(isset($app->attributes()->name)?(string)$app->attributes()->name:$code["name"]),
				"description"=>isset($app->description)?(string)$app->description:(isset($app->attributes()->description)?(string)$app->attributes()->description:$code["description"]),
				"domain"=>[],
				"icon"=>isset($app->icon)?(string)$app->icon:"icon.png",
			];
			//라우터 배열 생성
			foreach(["get","post","put","delete"] as $method){
				foreach(["html","json"] as $type){
					$code["app"][$appId]["{$method}-{$type}"] = [];
				}
			}
			//도메인 입력
			if(isset($app->domain)){
				foreach($app->domain as $domain){$code["app"][$appId]["domain"][] = (string)$domain;}
			}else{
				if($appId=="www"){$code["app"][$appId]["domain"][] = substr($code["domain"], 0, 1)=="."?substr($code["domain"], 1):$code["domain"];}
				$code["app"][$appId]["domain"][] = substr($code["domain"], 0, 1)=="."?"{$appId}{$code["domain"]}":"{$appId}.{$code["domain"]}";
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
					if(!isset($route->attributes()->type) || (string)$route->attributes()->type==""){$type = "html";}
					else{$type = (string)$route->attributes()->type;}
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
							$permission |= $code["user"][(string)$user];
						}
					}else if(isset($route->attributes()->user) && (string)$route->attributes()->user!=""){
						$users = explode("|",(string)$route->attributes()->user);
						foreach($users as $user){$permission |= $code["user"][$user];}
					}
					$code["app"][$appId]["{$method}-{$type}"][$routeId][$permission] = $routeCode;
				}
			}
			//Route "404" 없으면 추가
			if(!array_key_exists("404", $code["app"][$appId]["get-html"])){
				$code["app"][$appId]["get-html"]["404"] = [
					0=>[["method"=>"view", "layout"=>"none", "view"=>"404"]]
				];
			}
			//Route "/" 없으면 추가
			if(!array_key_exists("/", $code["app"][$appId]["get-html"])){
				$code["app"][$appId]["get-html"]["/"] = [
					0=>[["method"=>"view", "layout"=>"none", "view"=>"index"]]
				];
			}
			//도메인-앱 매칭맵 구성
			foreach($code["app"][$appId]["domain"] as $domain){
				$code["domain-app"][$domain] = $appId;
			}
			break;
		case "mysql":
			//앱 코드 기본 골격
			$code["app"][$appId] = [
				"id"=>$appId,
				"type"=>$appType,
				"name"=>isset($app->name)?(string)$app->name:(isset($app->attributes()->name)?(string)$app->attributes()->name:""),
				"charset"=>"utf8",
			];
			//서버 IP
			if(isset($app->attributes()->servername) && (string)$app->attributes()->servername!="")
			{$code["app"][$appId]["servername"] = (string)$app->attributes()->servername;}
			else if(isset($app->servername) && (string)$app->servername!="")
			{$code["app"][$appId]["servername"] = (string)$app->servername;}
			else{$code["app"][$appId]["servername"] = "localhost";}
			//아이디가 없다면
			if(isset($app->attributes()->username) && (string)$app->attributes()->username!="")
			{$code["app"][$appId]["username"] = (string)$app->attributes()->username;}
			else if(isset($app->username) && (string)$app->username!="")
			{$code["app"][$appId]["username"] = (string)$app->username;}
			else{$code["app"][$appId]["username"] = strtolower($code["name"]);}
			//비밀번호가 없다면
			if(isset($app->attributes()->password) && (string)$app->attributes()->password!="")
			{$code["app"][$appId]["password"] = (string)$app->attributes()->password;}
			else if(isset($app->password) && (string)$app->password!="")
			{$code["app"][$appId]["password"] = (string)$app->password;}
			else{$code["app"][$appId]["password"] = base64_encode(hash("sha256",time().random_bytes(32)));}
			//데이터베이스 명이 없다면
			if(isset($app->attributes()->databasename) && (string)$app->attributes()->databasename!="")
			{$code["app"][$appId]["databasename"] = (string)$app->attributes()->databasename;}
			else if(isset($app->databasename) && (string)$app->databasename!="")
			{$code["app"][$appId]["databasename"] = (string)$app->databasename;}
			else{$code["app"][$appId]["databasename"] = strtolower($code["name"]);}
			//테이블 엔진명이 없다면
			if(isset($app->attributes()->engine) && (string)$app->attributes()->engine!="")
			{$code["app"][$appId]["engine"] = (string)$app->attributes()->engine;}
			else if(isset($app->engine) && (string)$app->engine!="")
			{$code["app"][$appId]["engine"] = (string)$app->engine;}
			else{$code["app"][$appId]["engine"] = "InnoDB";}
			break;
	}
}
//경로 폴더 생성 및 소유권 변경
foreach($code["path"] as $path){
	if(!file_exists($path)){mkdir($path, 0755);chown($path, "apache");}
}
//source 폴더 생성
if(!file_exists("{$rootPath}source")){mkdir("{$rootPath}source", 0755);}
//어플리케이션 리소스 폴더 생성
if(!file_exists($path = "{$rootPath}public")){mkdir($path, 0755);}

$indexPHP = str_replace("{DIRECTORY_SEPARATOR}", DIRECTORY_SEPARATOR, <<<PHPCODE
<?PHP
	\$start_time = microtime();
	require "..{DIRECTORY_SEPARATOR}..{DIRECTORY_SEPARATOR}app.php";
	\$end_time = microtime();
	echo "<br>실행시간: ".\$end_time-\$start_time."s";
?>
PHPCODE);
foreach($code["app"] as $id=>$app){
	if($app["type"]!="parkjunwoo"){continue;}
	$publicPath = "{$rootPath}public".DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR;
	if(!file_exists($publicPath)){mkdir($publicPath, 0755);}
	if(!file_exists($publicPath."assets")){mkdir($publicPath."assets", 0755);}
	if(!file_exists($publicPath."images")){mkdir($publicPath."images", 0755);}
	if(!file_exists($publicPath."images".DIRECTORY_SEPARATOR."icon")){mkdir($publicPath."images".DIRECTORY_SEPARATOR."icon", 0755);}
	if(!file_exists($publicPath."scripts")){mkdir($publicPath."scripts", 0755);}
	if(!file_exists($publicPath."styles")){mkdir($publicPath."styles", 0755);}
	File::write("{$publicPath}index.php", $indexPHP);
	if(isset($app["icon"]) && file_exists($iconPath = $rootPath.$app["icon"])){
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
$appPHP = str_replace(["{DIRECTORY_SEPARATOR}","{code}"], [DIRECTORY_SEPARATOR,Debug::print($code)], <<<PHPCODE
<?PHP
	require "{DIRECTORY_SEPARATOR}home{DIRECTORY_SEPARATOR}framework{DIRECTORY_SEPARATOR}Parkjunwoo.php";
	Parkjunwoo::walk({code});
?>
PHPCODE);
File::write("{$rootPath}app.php", $appPHP);
//rename($sourcePath, "{$rootPath}source".DIRECTORY_SEPARATOR.$sourceFile);
echo "Install Complete!".PHP_EOL;
?>