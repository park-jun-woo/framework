<?php
//PHP 버전 확인
if(!version_compare(PHP_VERSION, "8.0.0", ">=")){error("Parkjunwoo 프레임워크는 PHP 8.0 이상에서 정상적으로 동작합니다.");}
//APCU 사용 가능 여부 확인
if(!function_exists("apcu_enabled")){error("APCU 모듈을 설치해주세요.");}
//루트 경로
$root = realpath(str_replace(basename($_SERVER["SCRIPT_FILENAME"]),"",realpath($_SERVER["SCRIPT_FILENAME"]))).DIRECTORY_SEPARATOR;
$path = $root."source".DIRECTORY_SEPARATOR."main.bml";
if(!file_exists($path)){error("{$path} 파일을 작성해주세요.");}
//소스코드 불러오기
$handle = fopen($path,"r");$result = fread($handle,filesize($path));fclose($handle);
$bml = simplexml_load_string($result,"SimpleXMLElement",LIBXML_NOCDATA | LIBXML_NOBLANKS);
echo $bml->getName();

/*
public static function install(string $bml){
	//PHP 버전 확인
	if(!version_compare(PHP_VERSION, "8.0.0", ">=")){self::installError("Parkjunwoo 프레임워크는 PHP 8.0 이상에서 정상적으로 동작합니다.");}
	//APCU 사용 가능 여부 확인
	if(!function_exists("apcu_enabled")){self::installError("APCU 모듈을 설치해주세요.");}
	
	
	//앱 코드가 유효한지 확인
	if(!array_key_exists("name", $code)){self::installError("name에 어플리케이션 이름을 영문으로 입력해 주세요.");}
	if(!array_key_exists("domain", $code)){self::installError("domain에 쿠키 설정에 입력할 도메인을 입력해 주세요.");}
	if(!array_key_exists("path", $code)){self::installError("path 배열을 입력해 주세요.");}
	if(!array_key_exists("servers", $code)){self::installError("servers 배열을 입력해 주세요.");}
	if(!array_key_exists("permissions", $code)){self::installError("permissions 배열을 입력해 주세요.");}
	if(!array_key_exists("config", $code)){self::installError("config 배열을 입력해 주세요.");}
	if(!array_key_exists("messages", $code)){self::installError("messages 배열을 입력해 주세요.");}
	if(!array_key_exists("apps", $code)){self::installError("apps 배열을 입력해 주세요.");}
	if(!is_array($code["path"])){self::installError("path는 배열이어야 합니다.");}
	if(!is_array($code["servers"])){self::installError("servers는 배열이어야 합니다.");}
	if(!is_array($code["permissions"])){self::installError("permissions는 배열이어야 합니다.");}
	if(!is_array($code["config"])){self::installError("config는 배열이어야 합니다.");}
	if(!is_array($code["messages"])){self::installError("messages는 배열이어야 합니다.");}
	if(!is_array($code["apps"])){self::installError("apps는 배열이어야 합니다.");}
	if(!array_key_exists("root", $code["path"])){self::installError("path[\"root\"]를 입력해 주세요.");}
	if(!array_key_exists("cache", $code["path"])){self::installError("path[\"cache\"]를 입력해 주세요.");}
	if(!array_key_exists("log", $code["path"])){self::installError("path[\"log\"]를 입력해 주세요.");}
	if(!array_key_exists("request", $code["path"])){self::installError("path[\"request\"]를 입력해 주세요.");}
	if(!array_key_exists("session", $code["path"])){self::installError("path[\"session\"]를 입력해 주세요.");}
	if(!array_key_exists("blacklist", $code["path"])){self::installError("path[\"blacklist\"]를 입력해 주세요.");}
	if(!array_key_exists("token-expire", $code["config"])){self::installError("config[\"token-expire\"]를 정수로 입력해 주세요.");}
	if(!is_int($code["config"]["token-expire"])){self::installError("config[\"token-expire\"]는 정수여야 합니다.");}
	if(!array_key_exists("session-expire", $code["config"])){self::installError("config[\"session-expire\"]를 입력해 주세요.");}
	if(!is_int($code["config"]["session-expire"])){self::installError("config[\"session-expire\"]는 정수여야 합니다.");}
//}

/**
 * 에러 메세지 출력 후 종료
 * @param string $message 에러 메세지
 */
function error($message){
	echo $message;
	exit;
}
?>