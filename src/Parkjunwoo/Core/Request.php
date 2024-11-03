<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Config\Route;

class Request{
    public const HTML = 0;
    public const JSON = 1;

    public const GET = 0;
    public const POST = 1;

    protected User $user;
    protected string $referer, $uri, $route_key, $locale;
    protected int $content_type, $http_method;
    protected Route $route;
    protected array $parameters;
    /**
     * 요청 생성자
     */
    protected function __construct(Config $config, User $user){
        //사용자 세션
        $this->user = $user;
        //파라미터 배열 초기화
        $this->parameters = [];
        // Content-Type 분석
        if(isset($_SERVER["CONTENT_TYPE"])){
            $contentType = $_SERVER["CONTENT_TYPE"];
            if(strpos($contentType, "multipart/form-data") !== false){$this->content_type = self::JSON;}
            elseif($contentType === "application/json" || $contentType === "json"){$this->content_type = self::JSON;}
            else{$this->content_type = self::HTML;}
        } else {
            $this->content_type = self::HTML;
        }
        //Method 분석
        switch($_SERVER["REQUEST_METHOD"]){
            case "GET":$this->http_method = self::GET;break;
            case "POST":$this->http_method = self::POST;break;
        }
        //리퍼러
        $this->referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"";
        //URI 분해
        $this->uri = explode("?",$_SERVER["REQUEST_URI"])[0];
        //사용자 환경 언어 처리
        if(!array_key_exists("HTTP_ACCEPT_LANGUAGE",$_SERVER)){$_SERVER["HTTP_ACCEPT_LANGUAGE"] = "";}
        else{$languageList = explode("-",preg_split("[;,]",$_SERVER["HTTP_ACCEPT_LANGUAGE"])[0]);}
        if($_SERVER["HTTP_ACCEPT_LANGUAGE"]=="" || $languageList[0]==""){$languageList = array($config->defaultLanguage());}
        //사용자 사용언어 지정
        if(array_key_exists("language",$_GET) && $_GET["language"]!=""){
            if(preg_match('/^[a-z]{2}$/i', $_GET["language"]) === 1){$language = $_GET["language"];}
            else{$language = $config->defaultLanguage();}
            $this->user->language($language);
        }else if($this->user->language()!=""){
            $language = $this->user->language();
        }else if($languageList[0]!=""){$language = $languageList[0];}
        else{$language = $config->defaultLanguage();}
        $this->locale = strtolower($language);
        //URI 분석 후 라우트
        $route = "";
        $exploded = explode("/",$this->uri);
        $len = count($exploded);
        for($iu=1;$iu<$len;$iu++){
            if($iu%2==1){$route .= "/".$exploded[$iu];}
            else if(is_numeric($exploded[$iu])){
                $route .= "/[{$exploded[$iu-1]}]";
                $this->parameters[$exploded[$iu-1]] = intval($exploded[$iu]);
            }else{$route .= "/".$exploded[$iu];}
        }
        if(($this->route = $config->route($this->http_method, $route))!=null){
            $this->route_key = $route;
        }else{
            $this->route = new Route(self::GET, $route, 0, "Parkjunwoo\\Core\\Controller", "getNotFound");
        }
    }
    /**
     * 컨텐트 타입
     * @return int 컨텐트 타입
     */
    public function contentType():int{return $this->content_type;}
    /**
     * HTTP 메서드
     * @return int 메서드
     */
    public function httpMethod():int{return $this->http_method;}
    /**
     * 사용언어 코드
     * @return string 코드
     */
    public function locale():string{return $this->locale;}
    /**
     * 리퍼러
     * @return string 리퍼러
     */
    public function referer():string{return $this->referer;}
    /**
     * URI
     * @return string URI
     */
    public function uri():string{return $this->uri;}
    /**
     * 라우트 패턴
     * @return string 패턴
     */
    public function routeKey():string{return $this->route_key;}
    /**
     * 시퀀스 배열
     * @return array 시퀀스 배열
     */
    public function route():Route{return $this->route;}
    /**
     * 파라미터값 조회
     * @param string $key 조회할 값의 키
     * @param string $default 키값이 없으면 출력할 기본값
     * @return string 파라미터값
     */
    public function parameter(string $key, string $default=null):?string{
        if(isset($this->parameters[$key])){return $this->parameters[$key];}
        else{
            switch($this->http_method){
                case self::GET:return isset($_GET[$key])?$_GET[$key]:null;
                case self::POST:return isset($_POST[$key])?$_POST[$key]:null;
            }
        }
        return $default;
    }
}
?>