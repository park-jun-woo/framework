<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Util\File;
class User{
    const GUEST = 0;
    
    const PERMISSION = 0;
    const MEMBER = 1;
    const IP = 2;
    const SESSION = 3;
    const TOKENTIME = 4;
    const LANGUAGE = 5;
    const AGENT = 6;

    protected Parkjunwoo $man;
    protected string $token;
    protected array $permissions, $session, $data;
    protected bool $change = false;
    /**
     * 사용자 생성자
     * @param Parkjunwoo $man 프레임워크 객체
     */
    public function __construct(Parkjunwoo $man){
        $this->man = $man;
        $this->permissions = $this->man->permissions();
        //쿠키에 세션이 없다면 신규 생성
        if(!array_key_exists("s", $_COOKIE)){
            $this->guest();
            return;
        }
        //쿠키에 토큰은 없는데 세션은 있다면 만료된 것으로 보고 세션 로드 후 토큰 재발행
        else if(!array_key_exists("t", $_COOKIE) && array_key_exists("s", $_COOKIE)){
            $this->load();
            return;
        }
        //APCU 메모리에 토큰이 등록되어 있지 않으면 만료된 것으로 보고 세션 로드 후 토큰 재발행
        if(!apcu_exists($this->man->id()."@t".$_COOKIE["t"])){
            $this->load();
            return;
        }
        //APCU 메모리 토큰으로 조회
        $data = apcu_fetch($this->man->id()."@t".$_COOKIE["t"]);
        //agent 값 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($_SERVER["HTTP_USER_AGENT"]!=$data[self::AGENT]){
            $this->black(1, "토큰 HTTP_USER_AGENT 불일치");
        }
        //IP가 달라졌다면 세션 로드 후 토큰 재발행
        if(ip2long($_SERVER["REMOTE_ADDR"])!=$data[self::IP]){
            $this->load();
            return;
        }
        $this->token = $_COOKIE["t"];
        $this->data = $data;
    }
    /**
     * 회원 인덱스 조회
     * @param int $member 지정할 회원 인덱스
     * @return int 값
     */
    public function member(int $member=null):int{
        if($member!=null){
            if(!isset($this->session)){$this->load(false);}
            $this->data[self::MEMBER] = $member;
            $this->change = true;
        }
        return $this->data[self::MEMBER];
    }
    /**
     * 언어 조회
     * @return string 값
     */
    public function language(string $language=null):string{
        if($language!=null){
            if(!isset($this->session)){$this->load(false);}
            $this->data[self::LANGUAGE] = $language;
            $this->change = true;
        }
        return $this->data[self::LANGUAGE];
    }
    /**
     * 사용자 세션 아이디
     * @return int 세션 아이디;
     */
    public function session():int{
        return $this->data[self::SESSION];
    }
    /**
     * 사용자 세션 파일명
     * @return string 세션 파일명;
     */
    public function sessionName():string{
        return base_convert($this->data[self::SESSION], 10, 36);
    }
    /**
     * 사용자 접속한 IP
     * @return string IP;
     */
    public function ip():string{
        return $_SERVER["REMOTE_ADDR"];
    }
    /**
     * HTTP_ACCEPT_AGENT 접속기기정보 조회
     * @return string 값
     */
    public function agent():string{
        return $this->data[self::AGENT];
    }
    /**
     * 세션 데이터 키값 조회
     * @param int $key 키
     * @return string 값
     */
    public function get(int $key):string{
        return $this->data[$key];
    }
    /**
     * 세션 데이터에 키값 입력
     * @param int $key 키
     * @param string $value 값
     * @return bool 성공 여부
     */
    public function set(int $key, string $value):bool{
        switch($key){
            case self::MEMBER:
            case self::IP:
            case self::TOKENTIME:
            case self::LANGUAGE:
                if(!isset($this->session)){$this->load(false);}
                $this->data[$key] = $value;
                $this->change = true;
                return true;
            case self::PERMISSION:
            case self::AGENT:
            default:
                return false;
        }
    }
    /**
     * 권한 확인
     * @param int $permission 권한
     * @return bool 권한 일치하는지 여부
     */
    public function permission(int $permission):bool{
        if($permission==0){return true;}
        return ($this->data[self::PERMISSION] & $permission) !== 0;
    }
    /**
     * 권한 추가
     * @param int $permission 권한
     */
    public function add(int $permission){
        if(!isset($this->session)){$this->load(false);}
        $this->data[self::PERMISSION] |= $permission;
        $this->change = true;
    }
    /**
     * 권한 제거
     * @param int $permission 권한
     */
    public function remove(int $permission){
        if(!isset($this->session)){$this->load(false);}
        $this->data[self::PERMISSION] &= ~$permission;
        $this->change = true;
    }
    /**
     * 접속자 보유 권한 조회
     * @return string 권한 이름 나열 ,로 묶음
     */
    public function permissionNames():string{
        $names = [];
        foreach($this->man->permissions() as $key=>$value){
            if(($this->data[self::PERMISSION] & $key)===$key){$names[] = $value;}
        }
        return implode(",", $names);
    }
    /**
     * 불량 접속자 블랙리스트 처리
     * @param int $level 블랙 수준, 높을 수록 심각
     * @param string $log 로그 
     */
    public function black(float $level,string $log){
        apcu_store($this->man->id()."@b".$this->ip(), "", $level*3600);
        File::append($this->man->path("blacklist").$this->ip(), date("Y-m-d H:i:s")."\t{$log}\n");
        exit;
    }
    /**
     * 방문자 기본 세션 설정
     */
    protected function guest(){
        //마지막 세션 아이디 조회 및 부여
        $sessionId = File::increase($this->man->path("session").".id");
        $sessionTime = time();
        //세션 객체 초기화
        $this->session = [
            "permission"=>self::GUEST,
            "session"=>$sessionId,
            "session-time"=>$sessionTime,
            "server"=>ip2long($_SERVER["SERVER_ADDR"]),
            "app"=>$this->man->id()
        ];
        //세션정보 객체 초기화
        $this->data = [
            self::GUEST,
            0,
            ip2long($_SERVER["REMOTE_ADDR"]),
            $sessionId,
            $sessionTime,
            "",
            isset($_SERVER["HTTP_USER_AGENT"])?$_SERVER["HTTP_USER_AGENT"]:"",
        ];
        //토큰 생성
        $this->token = hash("sha256",$this->data[self::TOKENTIME].$sessionId);
        $this->change = true;
    }
    /**
     * 세션 복호화한 후 불러오기
     */
    protected function load(bool $newToken=true){
        //쿠키에 저장한 세션 복호화
        if(!array_key_exists("s", $_COOKIE)){
            $this->guest();
            return;
        }
        $decrypted = "";
        if(openssl_private_decrypt(base64_decode(urldecode($_COOKIE["s"])), $decrypted, $this->man->privateKey())===false){
            $this->guest();
            return;
        }
        //사용자 인증 배열에 복호화한 데이터 입력
        $session = [
            "permission"=>unpack("P", substr($decrypted, 8, 8))[1],
            "session"=>unpack("P", substr($decrypted, 16, 8))[1],
            "session-time"=>unpack("P", substr($decrypted, 24, 8))[1],
            "server"=>unpack("N", substr($decrypted, 32, 4))[1],
            "app"=>substr($decrypted, 36)
        ];
        //세션 파일이 존재하지 않는다면 RSA 키 탈취 가능성 있으므로 리셋.
        if(!file_exists($sessionPath = $this->man->path("session").base_convert($session["session"], 10, 36))){
            $this->man->reset();
            $this->black(24, "세션 파일 존재하지 않아 RSA 키 탈취 가능성");
        }
        //세션 로드
        $data = explode("\n", File::read($sessionPath));
        //세션 도메인 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($this->man->id()!=$session["app"]){
            $this->black(1, "세션 도메인 불일치");
        }
        //agent 값 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($_SERVER["HTTP_USER_AGENT"]!=$data[self::AGENT]){
            $this->black(1, "세션 HTTP_USER_AGENT 불일치");
        }
        $this->session = $session;
        //토큰 신규 생성
        if($newToken){
            $this->data = $data;
            $this->data[self::TOKENTIME] = time();
            $this->token = hash("sha256",$this->data[self::TOKENTIME].$this->session["session"]);
            $this->change = true;
        }
    }
    /**
     * 세션 파일에 저장
     */
    public function save(){
        if($this->change){
            //쿠키에 세션 등록
            $data = pack("P", time());
            $data .= pack("P", $this->session["permission"]);
            $data .= pack("P", $this->session["session"]);
            $data .= pack("P", $this->session["session-time"]);
            $data .= pack("N", $this->session["server"]);
            $data .= $this->session["app"];
            $crypted = "";
            openssl_public_encrypt($data, $crypted, $this->man->publicKey());
            $cookieData = urlencode(base64_encode($crypted));
            setcookie("s", $cookieData, time()+$this->man->sessionExpire(), "/", $this->man->servername(), true, true);
            //쿠키에 토큰 등록
            setcookie("t", $this->token, time()+$this->man->tokenExpire(), "/", $this->man->servername(), true, true);
            //APCU 메모리에 토큰으로 세션 데이터 저장
            apcu_store($this->man->id()."@t".$this->token, $this->data, $this->man->tokenExpire());
            //세션 파일에 정보 저장
            File::write($this->man->path("session").$this->sessionName(),implode(PHP_EOL, $this->data));
            $this->change = false;

        }
    }
}