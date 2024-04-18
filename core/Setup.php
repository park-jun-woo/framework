<?php
namespace core;

use Doctrine\Inflector\InflectorFactory;
use util\Debug;
use util\File;
use util\Image;

class Setup{
    const PERMISSION = 0;
    const CLASSNAME = 1;
    const METHOD = 2;

    protected array $env,$source,$code,$controllers;

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
            "route"=>[[],[],[],[],[],[],[],[]],
            "message"=>[]
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
        $this->controllers = [];
        $this->messages = [];
        $this->log("앱 설정");
        $iu = 0;
        $methodMap = ["get"=>0,"post"=>1,"put"=>2,"delete"=>3];
        foreach($this->env as $appName=>&$appOptions){
            if(is_array($appOptions) && array_key_exists("DOMAIN",$appOptions)){
                $this->code["domain"][$appOptions["DOMAIN"]] = $appName;
                $this->code["app"][$appName] = ["key"=>$iu++];
                foreach($appOptions as $key=>&$value){
                    $this->code["app"][$appName][strtolower($key)] = $value;
                }
                $this->code["app"][$appName]["path"] = [
                    "source"=>$this->env["PATH_SOURCE"].DIRECTORY_SEPARATOR.$appName.(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
                    "view"=>$this->env["PATH_SOURCE"].DIRECTORY_SEPARATOR.$appName.DIRECTORY_SEPARATOR."views".(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
                    "controller"=>$this->env["PATH_SOURCE"].DIRECTORY_SEPARATOR.$appName.DIRECTORY_SEPARATOR."controllers".(DIRECTORY_SEPARATOR=="\\"?"\\\\":"/"),
                ];
                if(!is_dir($this->code["app"][$appName]["path"]["view"]))
                {mkdir($this->code["app"][$appName]["path"]["view"]);}
                if(!is_dir($this->code["app"][$appName]["path"]["controller"]))
                {mkdir($this->code["app"][$appName]["path"]["controller"]);}
            }
        }
        foreach($this->code["app"] as $appName=>&$app){
            $this->controllers[$appName] = ["HomeController"=>[]];
            foreach($this->source[$appName] as $routeUri=>&$route){
                foreach($route as $method=>$routeCode){
                    $entityName = "";
                    $appRoute = [0,"",""];
                    //권한 처리
                    foreach($routeCode["permission"] as $user){$appRoute[self::PERMISSION] |= $this->code["user"][$user];}
                    //메서드 이름
                    foreach($routeCode["code"] as &$sequence){
                        switch($sequence["method"]){
                            case "get":
                            case "post":
                            case "put":
                                $entityName = $this->getControllerName($sequence["entity"]);
                                $controllerClass = $inflector->classify($inflector->singularize($entityName))."Controller";
                                $appRoute[self::CLASSNAME] = $controllerClass;
                                if(!array_key_exists($appRoute[self::CLASSNAME],$this->controllers[$appName])){
                                    $this->controllers[$appName][$appRoute[self::CLASSNAME]] = ["list-of-entities"=>[]];
                                }
                                $this->controllers[$appName][$appRoute[self::CLASSNAME]]["list-of-entities"][$sequence["entity"]] = "";
                                break;
                            case "result":
                                if(array_key_exists("html",$sequence)){
                                    $appRoute[2] = $inflector->camelize($method."_".$sequence["html"]);
                                }
                                break;
                        }
                    }
                    if($appRoute[self::CLASSNAME]==""){
                        if(strlen($appRoute[self::METHOD])>6 && substr($appRoute[self::METHOD],0,6)=="getNew"){
                            $entityName = $inflector->pluralize(substr($appRoute[self::METHOD],6));
                            $entityName = $this->getControllerName($entityName);
                            $appRoute[self::CLASSNAME] = $inflector->classify($inflector->singularize($entityName)."_controller");
                            if(!array_key_exists($appRoute[self::CLASSNAME],$this->controllers[$appName])){
                                $this->controllers[$appName][$appRoute[self::CLASSNAME]] = ["list-of-entities"=>[]];
                            }
                            $entity = strtolower($inflector->pluralize(substr($appRoute[self::METHOD],6)));
                            $this->controllers[$appName][$appRoute[self::CLASSNAME]]["list-of-entities"][$entity] = "";
                        }else{
                            $appRoute[self::CLASSNAME] = "HomeController";
                        }
                    }
                    if($appRoute[self::METHOD]==""){
                        $appRoute[self::METHOD] = $inflector->camelize($method."_".$inflector->singularize($entityName));
                    }
                    $this->code["route"][$app["key"]<<2|$methodMap[$method]][$routeUri] = $appRoute;
                    
                    $this->controllers[$appName][$appRoute[self::CLASSNAME]][$appRoute[self::METHOD]] = [];
                    foreach($routeCode["code"] as &$sequence){
                        array_push($this->controllers[$appName][$appRoute[self::CLASSNAME]][$appRoute[self::METHOD]],$sequence);
                    }
                }
            }
        }
        //$this->code["controllers"] = $this->controllers;
    }
    /**
     * 폴더 및 파일 생성
     */
    protected function generate(){
        $inflector = InflectorFactory::create()->build();
        $path_template = $this->env["PATH_CORE"].DIRECTORY_SEPARATOR."template".DIRECTORY_SEPARATOR."sequence".DIRECTORY_SEPARATOR;
        //콘트롤러 생성
        foreach($this->controllers as $appName=>&$app){
            foreach($app as $controllerName=>&$controller){
                $controllerCode = "<?php
use core\Controller;

class {$controllerName} extends Controller{\n";
                if(array_key_exists("list-of-entities",$controller)){
                    foreach($controller["list-of-entities"] as $entityName=>$temp){
                        $singleName = $inflector->singularize($entityName);
                        $modelName = $inflector->classify("{$singleName}_model");
                        $controllerCode .= "    protected {$modelName} \${$singleName};\n";
                    }
                    $controllerCode .= "    /**
     * 컨트롤러 실행
     * @param Parkjunwoo \$man 프레임워크 객체
     */
    public function __construct(Parkjunwoo \$man){
        parent::__construct(\$man);
";
                    foreach($controller["list-of-entities"] as $entityName=>$temp){
                        $singleName = $inflector->singularize($entityName);
                        $modelName = $inflector->classify("{$singleName}_model");
                        $controllerCode .= "        \$this->{$singleName} = new {$modelName}();\n";
                    }
                    
                    $controllerCode .= "    }";
                }
                foreach($controller as $methodName=>&$method){
                    if($methodName=="list-of-entities"){continue;}
                    $controllerCode .= "
    /**
     * {$methodName}
     */
    public function {$methodName}(){
";
                    foreach($method as &$sequence){
                        $path_sequence = $path_template.$sequence["method"].".php";
                        if(file_exists($path_sequence)){
                            //$controllerCode .= "\n";
                            include $path_sequence;
                        }else{
                            echo "{$path_sequence} is not exixts.\n";
                        }
                    }
                    $controllerCode .= "
    }";
                }
                $controllerCode .= "
}
?>";
                $path_controller = $this->env["PATH_SOURCE"].DIRECTORY_SEPARATOR.$appName.DIRECTORY_SEPARATOR;
                $path_controller .= "controllers".DIRECTORY_SEPARATOR.$controllerName.".php";
                File::write($path_controller,$controllerCode);
            }

        }
        //app.php 파일 생성
        File::write($this->env["PATH_ROOT"].DIRECTORY_SEPARATOR."app.php", "<?PHP
require \"".$this->env["PATH_CORE"].DIRECTORY_SEPARATOR."Parkjunwoo.php\";
Parkjunwoo::walk(".Debug::print($this->code,"    ").");
?>");
    }
    
    protected function addMessage(array $message):int{
        $sameMessage = false;
        $messageId = "";
        foreach($this->code["message"] as $key=>&$value){
            if($message["ko"]==$value["ko"]){
                $sameMessage = true;
                $messageId = $key;
            }
        }
        if($sameMessage){
            return $messageId;
        }else{
            array_push($this->code["message"],$message);
            return count($this->code["message"])-1;
        }
    }
    protected function log(string $message){
        echo $message.PHP_EOL;
    }
    protected function print($array, string $indent="\t", string $eol=PHP_EOL, int $breakCols=140, int $icount=1):string{
        return Debug::print($array, $indent, $eol, $breakCols, $icount);
    }
    protected function getControllerName(string $entityName){
        $parentEntity = $entityName = strtolower($entityName);
        foreach($this->source["entities"][$entityName]["attributes"] as $attributeId=>&$attribute){
            if($attribute["define"]=="parent"){
                $parentEntity = $attribute["entity"];
                break;
            }
        }
        return $parentEntity;
    }
}

?>