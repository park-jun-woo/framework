<?php
namespace Parkjunwoo;

use Parkjunwoo\Core\User;
use Parkjunwoo\Core\Request;
use Parkjunwoo\Core\Controller;
use Parkjunwoo\Model\Database;
use Parkjunwoo\Util\File;
use Parkjunwoo\Util\Security;
use Parkjunwoo\Interface\Singleton;
use Parkjunwoo\Interface\Model;

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
class Parkjunwoo implements Singleton{
    public const HTML = 0;
    public const JSON = 1;
    
    public const GET = 0;
    public const POST = 1;
    public const PUT = 2;
    public const DELETE = 3;

    public const PERMISSION = 0;
    public const CLASSNAME = 1;
    public const METHODNAME = 2;

    protected static Parkjunwoo $instance;
    public static function getInstance(...$params):self{
        if(!isset(self::$instance)){self::$instance = new self(...$params);}
        return self::$instance;
    }

    protected User $user;
    protected Request $request;
    protected Controller $controller;
    protected string $path;
    protected array $code, $server;
    /**
     * Parkjunwoo 생성자
     * @param array $app 실행할 어플리케이션 코드 배열
     */
    protected function __construct(array &$code){
        define("DS",DIRECTORY_SEPARATOR);
        self::$instance = $this;
        $this->code = $code;
        //APCU 메모리에서 서버 배열을 불러올 수 없으면 리셋합니다.
        if(!apcu_exists($this->code["name"]."-server")){$this->reset();}
        else{$this->server = apcu_fetch($this->code["name"]."-server");}
        //블랙리스트 접속차단
        if(apcu_exists($this->code["name"]."-blacklist-".$_SERVER["REMOTE_ADDR"])){
            File::append($this->path("blacklist").$_SERVER["REMOTE_ADDR"], date("Y-m-d H:i:s")."\t접속차단\n");
            http_response_code(404);
            exit;
        }
        //사용자 세션
        $this->user = new User($this);
        //요청 분석
        $this->request = new Request($this);
        $route = $this->request->route();
        //권한 확인
        if(!$this->user->permission($route[self::PERMISSION]))
        {$route = [0,"Parkjunwoo\\Core\\Controller","getNotFound"];}
        //클래스 존재 확인
        if(!class_exists($route[self::CLASSNAME]))
        {$route = [0,"Parkjunwoo\\Core\\Controller","getNotFound"];}
        //클래스 인스턴스 생성
        $controller = new $route[self::CLASSNAME]($this);
        //메서드 존재 확인
        if(!method_exists($controller,$route[self::METHODNAME])){
            $route = [0,"Parkjunwoo\\Core\\Controller","getNotFound"];
            $controller = new $route[self::CLASSNAME]($this);
        }
        $controller->{$route[self::METHODNAME]}($this->request);
    }
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
        return array_key_exists($method, $this->code["route"]);
    }
    /**
     * 접속한 도메인의 앱 코드에서 라우터 배열 조회
     * @param int $type 컨텐트 타입
     * @param int $method 메서드
     * @return array 라우터 배열
     */
    public function router(int $type,int $method):array{
        return $this->code["route"][$method];
    }
    /**
     * 어플리케이션 이름
     * @return string 어플리케이션 이름
     */
    public function name():string{
        return $this->code["name"];
    }
    /**
     * 어플리케이션 타이틀
     * @return string 어플리케이션 타이틀
     */
    public function title():string{
        return $this->code["title"];
    }
    /**
     * 어플리케이션 설명
     * @return string 어플리케이션 설명
     */
    public function description():string{
        return $this->code["description"];
    }
    /**
     * 세션 도메인
     * @return string 도메인
     */
    public function servername():string{
        return $this->code["name"];
    }
    /**
     * 어플리케이션 설정값
     * @return string 키값
     */
    public function config(string $key){
        if(!array_key_exists($key, $this->code)){
            $this->error("[\"{$key}\"]이 입력되어 있지 않습니다.");
        }
        return $this->code[$key];
    }
    /**
     * 토큰 유효기간
     * @return integer 유효기간
     */
    public function tokenExpire():int{
        if(!array_key_exists("token-expire", $this->code)){
            $this->error("token-expire가 입력되어 있지 않습니다.");
        }
        return $this->code["token-expire"];
    }
    /**
     * 세션 유효기간
     * @return integer 유효기간
     */
    public function sessionExpire():int{
        if(!array_key_exists("session-expire", $this->code)){
            $this->error("session-expire가 입력되어 있지 않습니다.");
        }
        return $this->code["session-expire"];
    }
    /**
     * 어플리케이션 경로
     * @return string 키값
     */
    public function path(string $key="root"):string{
        if(array_key_exists($key, $this->code["path"])){return $this->code["path"][$key];}else{return "";}
    }
    /**
     * 어플리케이션 데이터베이스 설정값
     * @return string 키값
     */
    public function database(string $key){
        if(!array_key_exists($key, $this->code["database"])){$this->error("database[\"{$key}\"]이 입력되어 있지 않습니다.");}
        return $this->code["database"][$key];
    }
    /**
     * 어플리케이션 권한 배열
     * @param string $user 사용자
     * @return integer|null 권한 아이디
     */
    public function permission(string $user):?int{
        if(array_key_exists($user, $this->code["users"])){
            return $this->code["users"][$user]["id"];
        }
    }
    /**
     * 어플리케이션 권한 배열
     * @return array 권한 배열
     */
    public function permissions():array{
        return $this->code["permission"];
    }
    /**
     * 메세지 얻기
     * @param int $code 메세지 코드
     * @return string 메세지
     */
    public function message(int $code):?string{
        $message = null;
        $locale = $this->request->locale();
        if($code<1000){
            $default = [
                "100"=> ["ko"=>"계속","en"=>"Continue"],
                "101"=> ["ko"=>"프로토콜 전환","en"=>"Switching Protocols"],
                "102"=> ["ko"=>"처리중","en"=>"Processing"],
                "200"=> ["ko"=>"성공하였습니다.","en"=>"OK"],
                "201"=> ["ko"=>"생성하였습니다.","en"=>"Created"],
                "202"=> ["ko"=>"허용하였습니다.","en"=>"Accepted"],
                "203"=> ["ko"=>"신뢰할 수 없습니다.","en"=>"Non-Authoritative Information"],
                "204"=> ["ko"=>"콘텐츠가 없습니다.","en"=>"No Content"],
                "205"=> ["ko"=>"콘텐츠를 재설정합니다.","en"=>"Reset Content"],
                "206"=> ["ko"=>"콘텐츠 일부입니다.","en"=>"Partial Content"],
                "207"=> ["ko"=>"다중 상태입니다.","en"=>"Multi-Status"],
                "300"=> ["ko"=>"컨텐츠 유형이 여러 개 있습니다.","en"=>"Multiple Choices"],
                "301"=> ["ko"=>"다른 URI로 바뀌었습니다.","en"=>"Moved Permanently"],
                "302"=> ["ko"=>"다른 URI를 찾았습니다.","en"=>"Found"],
                "303"=> ["ko"=>"다른 URI로 요청하세요.","en"=>"See Other"],
                "304"=> ["ko"=>"갱신되지 않았습니다.","en"=>"Not Modified"],
                "305"=> ["ko"=>"프록시를 사용해 접속하세요.","en"=>"Use Proxy"],
                "307"=> ["ko"=>"임시 리다이렉션","en"=>"Temporary Redirect"],
                "400"=> ["ko"=>"요청을 잘못 하였습니다.","en"=>"Bad Request"],
                "401"=> ["ko"=>"권한이 없습니다.","en"=>"Unauthorized	"],
                "402"=> ["ko"=>"결제가 필요합니다.","en"=>"Payment Required"],
                "403"=> ["ko"=>"금지된 URI입니다.","en"=>"Forbidden"],
                "404"=> ["ko"=>"찾을 수 없습니다.","en"=>"Not Found"],
                "405"=> ["ko"=>"허용되지 않은 메소드입니다.","en"=>"Method Not Allowed"],
                "406"=> ["ko"=>"수용할 수 없습니다.","en"=>"Not Acceptable"],
                "407"=> ["ko"=>"프록시 인증 필요합니다.","en"=>"Proxy Authentication Required"],
                "408"=> ["ko"=>"요청 시간초과하였습니다.","en"=>"Request Timeout"],
                "409"=> ["ko"=>"충돌","en"=>"Conflict"],
                "410"=> ["ko"=>"없어졌습니다.","en"=>"Gone"],
                "411"=> ["ko"=>"길이를 지정하세요.","en"=>"Length Required"],
                "412"=> ["ko"=>"사전 조건 실패하였습니다.","en"=>"Precondition Failed"],
                "413"=> ["ko"=>"요청이 너무 큽니다.","en"=>"Request Entity Too Large"],
                "414"=> ["ko"=>"요청 URI가 너무 깁니다.","en"=>"Request-URI Too Large"],
                "415"=> ["ko"=>"지원하지 않는 미디어 유형입니다.","en"=>"Unsupported Media Type"],
                "416"=> ["ko"=>"처리할 수 없는 요청 범위입니다.","en"=>"Range Not Satisfiable"],
                "417"=> ["ko"=>"예상 실패하였습니다.","en"=>"Expectation Failed"],
                "422"=> ["ko"=>"처리할 수 없습니다.","en"=>"Unprocessable Entity"],
                "423"=> ["ko"=>"잠겼습니다.","en"=>"Locked"],
                "424"=> ["ko"=>"의존 실패하였습니다.","en"=>"Failed Dependency"],
                "426"=> ["ko"=>"업그레이드가 필요합니다.","en"=>"Upgraded Required"],
                "428"=> ["ko"=>"사전 조건 필요합니다.","en"=>"Precondition Required"],
                "429"=> ["ko"=>"너무 많은 요청입니다.","en"=>"Too Many Requests"],
                "431"=> ["ko"=>"헤더가 너무 큽니다.","en"=>"Request Header Fields Too Large"],
                "444"=> ["ko"=>"응답 없이 연결을 닫습니다.","en"=>"Connection Closed Without Response"],
                "451"=> ["ko"=>"법적 사유로 불가합니다.","en"=>"Unavailable For Legal Reasons"],
                "500"=> ["ko"=>"내부 서버 오류입니다.","en"=>"Internal Server Error"],
                "501"=> ["ko"=>"구현되지 않았습니다.","en"=>"Not Implemented	"],
                "502"=> ["ko"=>"게이트웨이 오류가 있습니다.","en"=>"Bad Gateway"],
                "503"=> ["ko"=>"서비스 제공이 불가능합니다.","en"=>"Service Unavailable	"],
                "504"=> ["ko"=>"게이트웨이 시간초과하였습니다.","en"=>"Gateway Timeout"],
                "505"=> ["ko"=>"지원하지 않는 HTTP 버전입니다.","en"=>"HTTP Version Not Supported"],
                "507"=> ["ko"=>"용량이 부족합니다.","en"=>"Insufficient Storage"],
            ];
            if(array_key_exists($code,$default)){
                $message = $default[$code];
            }
        }else if(array_key_exists($code,$this->code["message"])){
            $message = $this->code["message"][$code];
        }
        if($message!=null){
            if(array_key_exists($locale, $message)){return $message[$locale];}
            else{return $message[array_keys($message)[0]];}
        }
        return null;
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
}