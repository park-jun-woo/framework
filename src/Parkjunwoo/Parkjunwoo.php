<?php
namespace Parkjunwoo;

use Parkjunwoo\Core\Model;
use Parkjunwoo\Core\User;
use Parkjunwoo\Core\Request;
use Parkjunwoo\Core\Controller;
use Parkjunwoo\Model\Database;
use Parkjunwoo\Util\File;
use Parkjunwoo\Util\Security;

/**
 * The Parkjunwoo framework is a web application framework with a concise and powerful syntax.
 * PHP Version 8.0
 * @name Parkjunwoo Parkjunwoo Version 1.0 zeolite
 * @package Parkjunwoo
 * @see https://github.com/park-jun-woo/parkjunwoo The Parkjunwoo GitHub project
 * @author Park Jun woo <mail@parkjunwoo.com>
 * @copyright 2023 parkJunwoo.com
 * @license https://opensource.org/license/bsd-2-clause/ The BSD 2-Clause License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class Parkjunwoo{
    public const HTML = 0;
    public const JSON = 1;
    
    public const GET = 0;
    public const POST = 1;
    public const PUT = 2;
    public const DELETE = 3;

    public const PERMISSION = 0;
    public const CLASSNAME = 1;
    public const METHODNAME = 2;

    protected static Parkjunwoo $man;
    protected User $user;
    protected Request $request;
    protected Controller $controller;
    protected string $path;
    protected array $code, $server, $thisApp;
    /**
     * Parkjunwoo Parkjunwoo 생성자
     * @param array $app 실행할 어플리케이션 코드 배열
     */
    protected function __construct(array &$code){
        define("DS",DIRECTORY_SEPARATOR);
        self::$man = $this;
        $this->code = $code;
        //클래스 자동 로더 등록
        spl_autoload_register([$this,"autoload"]);
        //APCU 메모리에서 서버 배열을 불러올 수 없으면 리셋합니다.
        if(!apcu_exists($this->code["name"]."-server")){$this->reset();}
        else{$this->server = apcu_fetch($this->code["name"]."-server");}
        //블랙리스트 접속차단
        if(apcu_exists($this->code["name"]."-blacklist-".$_SERVER["REMOTE_ADDR"])){
            File::append($this->path("blacklist").$_SERVER["REMOTE_ADDR"], date("Y-m-d H:i:s")."\t접속차단\n");
            http_response_code(404);
            exit;
        }
        //현재 접속한 앱
        $this->thisApp = $this->code["app"][$this->code["domain"][$_SERVER["SERVER_NAME"]]];
        //캐시형 데이터베이스
        Database::init($this);
        //사용자 세션
        $this->user = new User($this);
        //요청 분석
        $this->request = new Request($this);
        //요청 실행
        $route = $this->request->route();
        //$route[self::PERMISSION];
        print_r($route);
        if(class_exists($route[self::CLASSNAME])){
            echo $route[self::CLASSNAME]." is exists.";
            $controller = new $route[self::CLASSNAME]($this);
            if(method_exists($controller,$route[self::METHODNAME])){
                $controller->{$route[self::METHODNAME]}();
            }else{echo $route[self::METHODNAME]." is not found.";}
        }else{
            echo $route[self::CLASSNAME]." is not found.";
        }
    }
    /**
     * Parkjunwoo Parkjunwoo를 실행합니다.
     * @param array $env 실행할 어플리케이션 설정
     */
    public static function walk(array $env){
        if(!isset(self::$man)){new Parkjunwoo($env);}
    }
    /**
     * Parkjunwoo 객체
     * @return Parkjunwoo 맨 객체
     */
    public static function man():Parkjunwoo{return self::$man;}
    /**
     * 사용자 세션 정보
     * @return User 사용자 객체
     */
    public function model():Model{return $this->model;}
    /**
     * 사용자 세션 정보
     * @return User 사용자 객체
     */
    public function user():User{return $this->user;}
    /**
     * 요청 분석 정보
     * @return Request 요청 객체
     */
    public function request():Request{return $this->request;}
    /**
     * 요청 실행 컨트롤러
     * @return Controller 콘트롤러 객체
     */
    public function controller():Controller{return $this->controller;}
    /**
     * 접속한 도메인의 앱 코드에 라우터가 있는지 확인
     * @param int $type 컨텐트 타입
     * @param int $method 메서드
     * @return bool 존재여부
     */
    public function isRouter(int $type,int $method):bool{
        return array_key_exists(($this->thisApp["key"]<<2)|$method, $this->code["route"]);
    }
    /**
     * 접속한 도메인의 앱 코드에서 라우터 배열 조회
     * @param int $type 컨텐트 타입
     * @param int $method 메서드
     * @return array 라우터 배열
     */
    public function router(int $type,int $method):array{
        return $this->code["route"][($this->thisApp["key"]<<2)|$method];
    }
    /**
     * 앱 코드 조회
     * @param string $name 키 또는 도메인
     * @return array|bool 앱 코드 배열
     */
    public function app(string $name=""){
        if($name==""){return $this->thisApp;}
        if(array_key_exists($name,$this->code["app"])){return $this->code["app"][$name];}
        return false;
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
    public function servername():string{
        $app = $this->app();
        if(array_key_exists("session-name",$app) && $app["session-name"]!=""){
            return $app["session-name"];
        }else{
            return $_SERVER["REMOTE_ADDR"];
        }
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
        if(array_key_exists($key, $this->code["path"])){return $this->code["path"][$key];}else{return "";}
    }
    /**
     * 어플리케이션 권한 배열
     * @return array 권한 배열
     */
    public function permissions():array{
        return $this->code["permission"];
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
        //RSA 키 쌍 생성
        list($this->server["privateKey"], $this->server["publicKey"]) = Security::generateRSA();
        //분산서버 목록 확인 및 초기 통신
        /*
        $this->server["servers"] = [];
        foreach($this->code["servers"] as $key=>$value){
            
        }
        apcu_store($this->code["name"]."-server", $this->server);
        */
    }
    /**
     * 에러 메세지 출력 후 강제종료
     * @param string $message 에러 메세지
     */
    protected static function installError(string $message){
        echo $message;exit;
    }
    /**
     * 에러 메세지 출력 후 강제종료
     * @param string $message 에러 메세지
     */
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