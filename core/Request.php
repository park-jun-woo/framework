<?php
namespace Parkjunwoo\Framework\Core;

use Parkjunwoo;

class Request{
    protected Parkjunwoo $man;
    protected User $user;
    protected string $uri, $routeKey, $locale;
    protected int $method, $type;
    protected array $route;
    /**
     * 요청 분석하는 생성자
     */
    public function __construct(Parkjunwoo $man){
        $this->man = $man;
        //세션 설정
        $this->user = $this->man->user();
        //URI 분석
        $this->uri = explode("?",$_SERVER["REQUEST_URI"])[0];
        //Method 분석
        switch($_SERVER["REQUEST_METHOD"]){
            case "GET":$this->method = Parkjunwoo::GET;break;
            case "POST":$this->method = Parkjunwoo::POST;break;
            case "PUT":$this->method = Parkjunwoo::PUT;break;
            case "\DELETE":$this->method = Parkjunwoo::DELETE;break;
        }
        //ContentType 분석
        if(array_key_exists("CONTENT_TYPE", $_SERVER)){
            switch($_SERVER["CONTENT_TYPE"]){
                default:$this->type = Parkjunwoo::HTML;break;
                case "json":case "application/json":$this->type = Parkjunwoo::JSON;break;
            }
        }else{$this->type = Parkjunwoo::HTML;}
        //사용자 환경 언어 처리
        if(!array_key_exists("HTTP_ACCEPT_LANGUAGE",$_SERVER)){$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "";}
        else{$languageList = explode("-",preg_split("[;,]",$_SERVER["HTTP_ACCEPT_LANGUAGE"])[0]);}
        if($_SERVER["HTTP_ACCEPT_LANGUAGE"]=="" || $languageList[0]==""){$languageList = array("ko");}
        //사용자 사용언어 지정
        if(array_key_exists("language",$_GET) && $_GET["language"]!=""){
            $language = $_GET["language"];
            $this->user->set("language",$language);
        }else if($this->user->is("language")){
            $language = $this->user->get("language");
        }else if($languageList[0]!=""){$language = $languageList[0];}
        else{$language = "ko";}
        $this->locale = strtolower($language);
        //URI 분석 후 라우트
        $route = "";
        $exploded = explode("/",$this->uri);
        $len = count($exploded);
        for($iu=1;$iu<$len;$iu++){
            if($iu%2==1){$route .= "/".$exploded[$iu];}
            else if(is_numeric($exploded[$iu])){$route .= "/[{$exploded[$iu-1]}]";}
            else{$route .= "/".$exploded[$iu];}
        }
        $router = $this->man->router($this->type, $this->method);
        if(array_key_exists($route, $router)){
            $this->routeKey = $route;
            $this->route = $router[$route];
        }
        if(!isset($this->routeKey)){
            $this->routeKey = $route;//"404";
            $this->route = [["method"=>"view","view"=>"404"]];
        }
    }
    /**
     * URI
     * @return string URI
     */
    public function uri():string{
        return $this->uri;
    }
    /**
     * 라우트 패턴
     * @return string 패턴
     */
    public function routeKey():string{
        return $this->routeKey;
    }
    /**
     * HTTP 메서드
     * @return string 메서드
     */
    public function method():string{
        return $this->method;
    }
    /**
     * 컨텐트 타입
     * @return string 컨텐트 타입
     */
    public function type():string{
        return $this->type;
    }
    /**
     * 사용언어 코드
     * @return string 코드
     */
    public function locale():string{
        return $this->locale;
    }
    /**
     * 시퀀스 배열
     * @return array 시퀀스 배열
     */
    public function route():array{
        return $this->route;
    }
}
?>