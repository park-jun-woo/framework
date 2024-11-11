<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Config\Database;
use Parkjunwoo\Config\Path;
use Parkjunwoo\Config\Level;
use Parkjunwoo\Config\Route;
use Parkjunwoo\Util\File;
use Parkjunwoo\Util\Security;

class Config implements \Parkjunwoo\Interface\Config{
    protected Database $database;
    protected Path $path;
    protected int $id, $token_expire, $session_expire, $cache_expire;
    protected string $key, $private_key, $public_key;
    protected string $project_name, $app_name, $server_name, $default_language, $locale;
    protected array $levels, $permissions, $routes, $messages;
    /**
     * 설정 생성자
     * @param array $config 설정 배열
     */
    public function __construct(string $app_name){
        //폴더 구분자
        $DS = DIRECTORY_SEPARATOR;
        //설정 파일이 없다면
        if(!file_exists($config_path=realpath("..{$DS}..{$DS}config{$DS}{$app_name}.php"))){$this->error("{$config_path} 를 생성하세요.");}
        //설정 파일 불러오기
        $config = require $config_path;
        //프로젝트 이름
        if(isset($config['project'])){$this->project_name = $config['project'];}
        else{$this->error("프로젝트 이름을 설정하세요.");exit;}
        //프로젝트 아이디
        if(isset($config['id'])){$this->id = $config['id'];}
        else{$this->id = crc32($this->project_name);}
        //프로젝트 키값
        if(isset($config['key'])){$this->key = $config['key'];}
        else{$this->key = base_convert($this->id, 10, 36);}
        //앱 이름
        if(isset($config['app'])){$this->app_name = $config['app'];}
        else{$this->app_name = $app_name;}
        //서버 도메인
        if(isset($config['server-name'])){$this->server_name = $config['server-name'];}
        else{$this->error("서버 도메인을 설정하세요.");exit;}
        //토큰 유효기간
        if(isset($config['token-expire'])){$this->token_expire = $config['token-expire'];}
        else{$this->token_expire = 86400;}
        //세션 유효기간
        if(isset($config['session-expire'])){$this->session_expire = $config['session-expire'];}
        else{$this->session_expire = 15552000;}
        //캐시 유효기간
        if(isset($config['cache-expire'])){$this->cache_expire = $config['cache-expire'];}
        else{$this->cache_expire = 600;}

        //데이터베이스 배열
        if(!isset($config['database'])){$this->error("데이터베이스 접속 정보를 설정하세요.");exit;}
        //데이터베이스 종류
        if(isset($config['database']['connection'])){$connection = $config['database']['connection'];}
        else{$connection = "mysql";}
        //데이터베이스 호스트
        if(isset($config['database']['host'])){$host = $config['database']['host'];}
        else{$host = "127.0.0.1";}
        //데이터베이스 포트
        if(isset($config['database']['port'])){$port = $config['database']['port'];}
        else{$port = 3306;}
        //데이터베이스 이름
        if(isset($config['database']['database'])){$database = $config['database']['database'];}
        else{$this->error("데이터베이스 database를 입력하세요.");exit;}
        //데이터베이스 사용자 이름
        if(isset($config['database']['username'])){$username = $config['database']['username'];}
        else{$this->error("데이터베이스 username을 입력하세요.");exit;}
        //데이터베이스 비밀번호
        if(isset($config['database']['password'])){$password = $config['database']['password'];}
        else{$this->error("데이터베이스 password를 입력하세요.");exit;}
        //데이터베이스 객체
        $this->database = new Database($connection, $host, $port, $database, $username, $password);
        
        //경로 배열
        if(!isset($config['path'])){$config['path'] = [];}
        //루트 경로
        if(isset($config['path']['root'])){$root_path = $config['path']['root'];}
        else{$root_path = realpath("..{$DS}..");}
        //HTTP 경로
        if(isset($config['path']['http'])){$http_path = $config['path']['http'];}
        else{$http_path = realpath(".");}
        //캐시 경로
        if(isset($config['path']['cache'])){$cache_path = $config['path']['cache'];}
        else{$cache_path = "{$config['path']['root']}storage{$DS}cache{$DS}";}
        //데이터 경로
        if(isset($config['path']['data'])){$data_path = $config['path']['data'];}
        else{$data_path = "{$config['path']['root']}storage{$DS}data{$DS}";}
        //로그 경로
        if(isset($config['path']['log'])){$log_path = $config['path']['log'];}
        else{$log_path = "{$config['path']['root']}storage{$DS}log{$DS}";}
        //업로드 경로
        if(isset($config['path']['upload'])){$upload_path = $config['path']['upload'];}
        else{$upload_path = "{$config['path']['root']}storage{$DS}upload{$DS}";}
        //경로 객체
        $this->path = new Path($root_path, $http_path, $view_path, $cache_path, $data_path, $log_path, $upload_path);

        //사용자 정의 배열 초기화
        $this->levels = [];
        //방문자
        $this->levels["guest"] = new Level(0, "guest", "방문자");
        //회원
        $this->levels["member"] = new Level(1, "member", "회원");
        //사용자 정의 배열
        if(isset($config['users'])){
            foreach($config['users'] as $key=>$user){
                $this->levels[$key] = new Level($user['id'], $user['name'], $user['title']);
            }
        }
        //자기자신
        $this->levels["self"] = new Level(1152921504606846976, "self", "자신");
        //관리자
        $this->levels["admin"] = new Level(2305843009213693952, "admin", "관리자");
        //시스템
        $this->levels["system"] = new Level(1152921504606846976, "system", "시스템");

        //권한 배열 초기화
        $this->permissions = [];
        //사용자 배열로 권한 배열 생성
        foreach($this->levels as $level){$this->permissions[$level->id()] = $level->name();}
        
        //GET 라우터 배열
        if(!isset($config['get'])){$this->error("GET 라우터를 설정하세요.");exit;}
        //GET 라우터 배열 내용이 하나도 없으면
        if(count($config['get'])==0){$this->error("GET 라우터를 입력하세요.");exit;}
        //POST 라우터 배열
        if(!isset($config['post'])){$this->error("POST 라우터를 설정하세요.");exit;}
        //POST 라우터 배열 내용이 하나도 없으면
        if(count($config['post'])==0){$this->error("POST 라우터를 입력하세요.");exit;}
        //라우터 배열 초기화
        $this->routes = [];
        //GET 라우터 객체 생성
        foreach($config['get'] as $key=>$value){
            $this->routes["0{$key}"] = new Route(
                Request::GET,
                $key, 
                $value[Route::PERMISSION],
                $value[Route::CLASSNAME],
                $value[Route::METHODNAME],
                isset($value[Route::CACHEEXPIRE])?$value[Route::CACHEEXPIRE]:$this->cache_expire,
                isset($value[Route::OPTIONS])?$value[Route::OPTIONS]:[]
            );
        }
        //POST 라우터 객체 생성
        foreach($config['post'] as $key=>$value){
            $this->routes["1{$key}"] = new Route(
                Request::POST,
                $key, 
                $value[Route::PERMISSION],
                $value[Route::CLASSNAME],
                $value[Route::METHODNAME],
                isset($value[Route::CACHEEXPIRE])?$value[Route::CACHEEXPIRE]:$this->cache_expire,
                isset($value[Route::OPTIONS])?$value[Route::OPTIONS]:[]
            );
        }
        //메세지 배열
        $this->messages = [
            100=>["ko"=>"계속","en"=>"Continue"],
            101=>["ko"=>"프로토콜 전환","en"=>"Switching Protocols"],
            102=>["ko"=>"처리중","en"=>"Processing"],
            200=>["ko"=>"성공하였습니다.","en"=>"OK"],
            201=>["ko"=>"생성하였습니다.","en"=>"Created"],
            202=>["ko"=>"허용하였습니다.","en"=>"Accepted"],
            203=>["ko"=>"신뢰할 수 없습니다.","en"=>"Non-Authoritative Information"],
            204=>["ko"=>"콘텐츠가 없습니다.","en"=>"No Content"],
            205=>["ko"=>"콘텐츠를 재설정합니다.","en"=>"Reset Content"],
            206=>["ko"=>"콘텐츠 일부입니다.","en"=>"Partial Content"],
            207=>["ko"=>"다중 상태입니다.","en"=>"Multi-Status"],
            300=>["ko"=>"컨텐츠 유형이 여러 개 있습니다.","en"=>"Multiple Choices"],
            301=>["ko"=>"다른 URI로 바뀌었습니다.","en"=>"Moved Permanently"],
            302=>["ko"=>"다른 URI를 찾았습니다.","en"=>"Found"],
            303=>["ko"=>"다른 URI로 요청하세요.","en"=>"See Other"],
            304=>["ko"=>"갱신되지 않았습니다.","en"=>"Not Modified"],
            305=>["ko"=>"프록시를 사용해 접속하세요.","en"=>"Use Proxy"],
            307=>["ko"=>"임시 리다이렉션","en"=>"Temporary Redirect"],
            400=>["ko"=>"요청을 잘못 하였습니다.","en"=>"Bad Request"],
            401=>["ko"=>"권한이 없습니다.","en"=>"Unauthorized	"],
            402=>["ko"=>"결제가 필요합니다.","en"=>"Payment Required"],
            403=>["ko"=>"금지된 URI입니다.","en"=>"Forbidden"],
            404=>["ko"=>"찾을 수 없습니다.","en"=>"Not Found"],
            405=>["ko"=>"허용되지 않은 메소드입니다.","en"=>"Method Not Allowed"],
            406=>["ko"=>"수용할 수 없습니다.","en"=>"Not Acceptable"],
            407=>["ko"=>"프록시 인증 필요합니다.","en"=>"Proxy Authentication Required"],
            408=>["ko"=>"요청 시간초과하였습니다.","en"=>"Request Timeout"],
            409=>["ko"=>"충돌","en"=>"Conflict"],
            410=>["ko"=>"없어졌습니다.","en"=>"Gone"],
            411=>["ko"=>"길이를 지정하세요.","en"=>"Length Required"],
            412=>["ko"=>"사전 조건 실패하였습니다.","en"=>"Precondition Failed"],
            413=>["ko"=>"요청이 너무 큽니다.","en"=>"Request Entity Too Large"],
            414=>["ko"=>"요청 URI가 너무 깁니다.","en"=>"Request-URI Too Large"],
            415=>["ko"=>"지원하지 않는 미디어 유형입니다.","en"=>"Unsupported Media Type"],
            416=>["ko"=>"처리할 수 없는 요청 범위입니다.","en"=>"Range Not Satisfiable"],
            417=>["ko"=>"예상 실패하였습니다.","en"=>"Expectation Failed"],
            422=>["ko"=>"처리할 수 없습니다.","en"=>"Unprocessable Entity"],
            423=>["ko"=>"잠겼습니다.","en"=>"Locked"],
            424=>["ko"=>"의존 실패하였습니다.","en"=>"Failed Dependency"],
            426=>["ko"=>"업그레이드가 필요합니다.","en"=>"Upgraded Required"],
            428=>["ko"=>"사전 조건 필요합니다.","en"=>"Precondition Required"],
            429=>["ko"=>"너무 많은 요청입니다.","en"=>"Too Many Requests"],
            431=>["ko"=>"헤더가 너무 큽니다.","en"=>"Request Header Fields Too Large"],
            444=>["ko"=>"응답 없이 연결을 닫습니다.","en"=>"Connection Closed Without Response"],
            451=>["ko"=>"법적 사유로 불가합니다.","en"=>"Unavailable For Legal Reasons"],
            500=>["ko"=>"내부 서버 오류입니다.","en"=>"Internal Server Error"],
            501=>["ko"=>"구현되지 않았습니다.","en"=>"Not Implemented"],
            502=>["ko"=>"게이트웨이 오류가 있습니다.","en"=>"Bad Gateway"],
            503=>["ko"=>"서비스 제공이 불가능합니다.","en"=>"Service Unavailable"],
            504=>["ko"=>"게이트웨이 시간초과하였습니다.","en"=>"Gateway Timeout"],
            505=>["ko"=>"지원하지 않는 HTTP 버전입니다.","en"=>"HTTP Version Not Supported"],
            507=>["ko"=>"용량이 부족합니다.","en"=>"Insufficient Storage"]
        ] + (isset($config['message'])?$config['message']:[]);
        //데이터 경로
        $data_path = $this->path->data();
        //RSA 개인키 경로
        $private_path = "{$data_path}.private";
        //RSA 공개키 경로
        $public_path = "{$data_path}.public";
        //RSA키 파일이 있다면
        if(file_exists($private_path) && file_exists($public_path)){
            $this->private_key = File::read($private_path);
            $this->public_key = File::read($public_path);
        //RSA키 파일이 없다면
        }else{
            list($this->private_key, $this->public_key) = Security::generateRSA();
            File::write($private_path, $this->private_key);
            File::write($public_path, $this->public_key);
        }
    }
    /**
     * 데이터베이스 설정 객체
     * @return Database 경로 설정 객체
     */
    public function database():Database{return $this->database;}
    /**
     * 경로 설정 객체
     * @return Path 경로 설정 객체
     */
    public function path():Path{return $this->path;}
    /**
     * 프로젝트 아이디, 프로젝트 이름을 CRC32 변환한 값
     * @return int 아이디
     */
    public function id():int{return $this->id;}
    /**
     * 프로젝트 키값, 아이디를 36진수로 변환한 값
     * @return string 키값
     */
    public function key():string{return $this->key;}
    /**
     * 프로젝트 이름
     * @return string 이름
     */
    public function projectName():string{return $this->project_name;}
    /**
     * 앱 이름
     * @return string 이름
     */
    public function appName():string{return $this->app_name;}
    /**
     * 서버 이름
     * @return string 이름
     */
    public function serverName():string{return $this->server_name;}
    /**
     * 언어 기본값
     * @return string 언어
     */
    public function defaultLanguage():string{return $this->default_language;}
    /**
     * 토큰 유효기간
     * @return int 유효기간
     */
    public function tokenExpire():int{return $this->token_expire;}
    /**
     * 세션 유효기간
     * @return int 유효기간
     */
    public function sessionExpire():int{return $this->session_expire;}
    /**
     * 캐시 유효기간
     * @return int 유효기간
     */
    public function cacheExpire():int{return $this->cache_expire;}
    /**
     * 권한 목록
     * @return array 권한 목록 배열
     */
    public function permissions():array{return $this->permissions;}
    /**
     * 라우트 조회
     * @param int $http_method HTTP 메서드
     * @param string $key URI패턴 키
     * @return Route|null 라우트 객체
     */
    public function route(int $http_method, string $key):?Route{
        $route_key = "{$http_method}{$key}";
        return isset($this->routes[$route_key])?$this->routes[$route_key]:null;
    }
    /**
     * 메세지 얻기
     * @param int $code 메세지 코드
     * @param string $locale 언어 코드
     * @return string|null 메세지
     */
    public function message(int $code, string $locale=null):?string{
        //언어 코드를 입력 안했다면, 기본 언어 코드
        if($locale=null){$locale = $this->default_language;}
        //지정한 언어 코드 있다면, 지정 언어 코드의 메세지 반환
        if(isset($this->messages[$code][$locale])){return $this->messages[$code][$locale];}
        //지정한 언어 코드 없다면, 기본 언어 코드의 메세지 반환
        if(isset($this->messages[$code][$this->default_language])){return $this->messages[$code][$this->default_language];}
        //지정한 언어 코드, 기본 언어 코드 둘다 없다면 null 반환
        return null;
    }
    /**
     * RSA 개인키
     * @return string 개인키
     */
    public function privateKey():string{return $this->private_key;}
    /**
     * RSA 공개키
     * @return string 공개키
     */
    public function publicKey():string{return $this->public_key;}
    /**
     * 에러 메세지 출력 후 강제종료
     * @param string $message 에러 메세지
     */
    protected function error(string $message):void{
        echo $message;exit;
    }
}
?>