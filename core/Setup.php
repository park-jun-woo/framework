<?php
namespace core;

use Doctrine\Inflector\InflectorFactory;
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
        $this->load();
        //코드 골격
        $this->code();
        //설정값 설정
        $this->config();
        //앱 설정
        $this->app();

        //폴더 및 파일 생성
        $this->generate();
        //완료
        $this->log("Setup Complete!");
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
    protected function load(){
        $path_source = $this->env["PATH_SOURCE"].DIRECTORY_SEPARATOR."code.php";
        $this->log("소스 불러오기: {$path_source}");
        include $path_source;
        $this->source = $code;
    }
    /**
     * 코드 골격 생성
     */
    protected function code(){
        //코드 기본 골격
        $this->code = [
            "id"=>crc32($this->env["PROJECT_NAME"]),
            "name"=>$this->env["PROJECT_NAME"],
            "user"=>$this->source["user"],
            "permission"=>[],
            "path"=>[],
            "domain"=>[],
            "app"=>[],
            "route"=>[],
            "controllers"=>[]
        ];
    }
    /**
     * 설정값 설정
     */
    protected function config(){
        $this->log("권한 설정");
        foreach($this->source["user"] as $user=>$permission){
            $this->code["permission"][$permission] = $user;
        }

        $this->log("경로 설정");
        $this->code["path"] = [
            "root"=>$this->env["PATH_ROOT"].(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
            "source"=>$this->env["PATH_SOURCE"].(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
            "data"=>$this->env["PATH_DATA"].(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
            "blacklist"=>$this->env["PATH_DATA"].DIRECTORY_SEPARATOR."blacklist".(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
            "cache"=>$this->env["PATH_DATA"].DIRECTORY_SEPARATOR."cache".(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
            "session"=>$this->env["PATH_DATA"].DIRECTORY_SEPARATOR."session".(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
            "upload"=>$this->env["PATH_DATA"].DIRECTORY_SEPARATOR."upload".(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
        ];

        //$this->log("설정값 설정");
    }
    /**
     * 라우트 설정
     */
    protected function app(){
        $inflector = InflectorFactory::create()->build();
        $controllers = [
            "HomeController"=>[]
        ];
        $this->log("앱 설정");
        $methodMap = ["get"=>0,"post"=>1,"put"=>2,"delete"=>3];
        foreach($this->env as $appName=>&$appOptions){
            if(is_array($appOptions) && array_key_exists("DOMAIN",$appOptions)){
                $this->code["domain"][$appOptions["DOMAIN"]] = $appName;
                $this->code["app"][$appName] = [];
                foreach($appOptions as $key=>&$value){
                    $this->code["app"][$appName][strtolower($key)] = $value;
                }
                $this->code["app"][$appName]["route"] = [[],[],[],[]];
            }
        }
        foreach($this->code["app"] as $appName=>&$app){
            foreach($this->source[$appName] as $routeUri=>&$route){
                foreach($route as $method=>$routeCode){
                    $entityName = "";
                    $appRoute = ["permission"=>0,"class"=>"","method"=>""];
                    //권한 처리
                    foreach($routeCode["permission"] as $user){$appRoute["permission"] |= $this->code["user"][$user];}
                    //메서드 이름
                    foreach($routeCode["code"] as &$sequence){
                        switch($sequence["method"]){
                            case "get":
                            case "post":
                            case "put":
                                $entityName = $sequence["entity"];
                                $controllerClass = $inflector->classify($inflector->singularize($entityName))."Controller";
                                $appRoute["class"] = $controllerClass;
                                break;
                            case "result":
                                if(array_key_exists("html",$sequence)){
                                    $appRoute["method"] = $inflector->camelize($method."_".$sequence["html"]);
                                }
                                break;
                        }
                    }
                    if($appRoute["class"]==""){
                        if(strlen($appRoute["method"])>6 && substr($appRoute["method"],0,6)=="getNew"){
                            $appRoute["class"] = $inflector->classify(substr($appRoute["method"],6)."_controller");
                        }else{
                            $appRoute["class"] = "HomeController";
                        }
                    }
                    if($appRoute["method"]==""){$appRoute["method"] = $inflector->camelize($method."_".$inflector->singularize($entityName));}

                    $app["route"][$methodMap[$method]][$routeUri] = $appRoute;

                    if(!array_key_exists($appRoute["class"],$controllers)){$controllers[$appRoute["class"]] = [];}
                    $controllers[$appRoute["class"]][$appRoute["method"]] = [];
                    foreach($routeCode["code"] as &$sequence){
                        array_push($controllers[$appRoute["class"]][$appRoute["method"]],$sequence);
                    }
                }
            }
        }
        $this->code["controllers"] = $controllers;
    }
    /**
     * 폴더 및 파일 생성
     */
    protected function generate(){
        
        //app.php 파일 생성
        File::write($this->env["PATH_ROOT"].DIRECTORY_SEPARATOR."app.php", "<?PHP
require \"".$this->env["PATH_CORE"].DIRECTORY_SEPARATOR."Parkjunwoo.php\";
Parkjunwoo::walk(".Debug::print($this->code,"    ").");
?>");
    }
    
    protected function log(string $message){
        echo $message.PHP_EOL;
    }
}

?>