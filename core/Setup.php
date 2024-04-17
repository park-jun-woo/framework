<?php
namespace core;

use util\Debug;
use util\File;
use util\Image;

class Setup{
    protected array $env,$source,$code;

    public function __construct(array $env){
        $this->env = $env;
        //필수 설치 확인
        //$this->required();
        //소스 불러오기
        $this->load($env["PATH_SOURCE"].DIRECTORY_SEPARATOR."code.php");
        //코드 골격
        $this->code();
        //경로 설정
        $this->path($source);
        //권한 설정
        $this->permission();
        //설정값 설정
        $this->config();
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
        include $source;
        $this->source = $code;
    }
    /**
     * 코드 골격 생성
     */
    protected function code(){
        //코드 기본 골격
        $this->code = [
            "user"=>$this->source["user"],
            "permission"=>[]
        ];
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
    
    protected function log(string $message){
        echo $message.PHP_EOL;
    }
}

?>