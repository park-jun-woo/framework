<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Util\File;

class User{
    const GUEST = 0;
    protected Parkjunwoo $man;
    protected string $token;
    protected array $permissions, $session = [], $data = [];
    protected bool $change = false;
    /**
     * 사용자 객체 생성
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
        if(!apcu_exists($this->man->name()."s".$_COOKIE["t"])){
            $this->load();
            return;
        }
        //APCU 메모리 토큰으로 조회
        $data = apcu_fetch($this->man->name()."s".$_COOKIE["t"]);
        //agent 값 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($_SERVER["HTTP_USER_AGENT"]!=$data["user-agent"]){
            $this->black(1, "토큰 HTTP_USER_AGENT 불일치");
        }
        //언어값 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($_SERVER["HTTP_ACCEPT_LANGUAGE"]!=$data["user-language"]){
            $this->black(1, "토큰 HTTP_ACCEPT_LANGUAGE 불일치");
        }
        //IP가 달라졌다면 세션 로드 후 토큰 재발행.
        if(ip2long($_SERVER["REMOTE_ADDR"])!=$data["ip"]){
            $this->load();
            return;
        }
        $this->token = $_COOKIE["t"];
        $this->data = $data;
    }
    /**
     * 사용자 접속한 IP
     * @return string IP;
     */
    public function ip():string{
        return $_SERVER["REMOTE_ADDR"];
    }
    /**
     * 세션 데이터에 키값이 존재하는지 여부 확인
     * @param string $key 키
     * @return bool 존재 여부
     */
    public function is(string $key):bool{
        return array_key_exists($key, $this->data);
    }
    /**
     * 세션 데이터 키값 조회
     * @param string $key 키
     * @return string 값
     */
    public function get(string $key):string{
        return $this->data[$key];
    }
    /**
     * 세션 데이터에 키값 입력
     * @param string $key 키
     * @param string $value 값
     * @return bool 성공 여부
     */
    public function set(string $key, string $value):bool{
        if(in_array($key, ["permission","ip","first-referer","user-agent","user-language","token-time"])){return false;}
        $this->data[$key] = $value;
        $this->change = true;
        return true;
    }
    /**
     * 권한 확인
     * @param int $permission 권한
     * @return bool 권한 일치하는지 여부
     */
    public function permission(int $permission):bool{
        if($permission==0){return true;}
        return ($this->data["permission"] & $permission) !== 0;
    }
    /**
     * 권한 추가
     * @param int $permission 권한
     */
    public function add(int $permission){
        $this->session["permission"] |= $permission;
        $this->data["permission"] = $this->session["permission"];
        $this->change = true;
    }
    /**
     * 권한 제거
     * @param int $permission 권한
     */
    public function remove(int $permission){
        $this->session["permission"] &= ~$permission;
        $this->data["permission"] = $this->session["permission"];
        $this->change = true;
    }
    /**
     * 접속자 보유 권한 조회
     * @return string 권한 이름 나열 ,로 묶음
     */
    public function permissionNames():string{
        $names = [];
        foreach($this->man->permissions() as $key=>$value){
            if(($this->data["permission"] & $key)===$key){$names[] = $value;}
        }
        return implode(",", $names);
    }
    /**
     * 불량 접속자 블랙리스트 처리
     * @param int $level 블랙 수준, 높을 수록 심각
     * @param string $log 로그 
     */
    public function black(float $level,string $log){
        apcu_store($this->man->name()."b".$this->ip(), "", $level*3600);
        File::append($this->man->path("blacklist").$this->ip(), date("Y-m-d H:i:s")."\t{$log}\n");
        exit;
    }
    /**
     * 방문자 기본 세션 설정
     */
    protected function guest(){
        //마지막 세션 아이디 조회 및 부여
        $sessionId = File::increase($this->man->path("session")."id");
        $sessionTime = time();
        //토큰 생성
        $this->token = hash("sha256",$sessionTime.$sessionId);
        //세션 객체 초기화
        $this->session = [
            "permission"=>self::GUEST,
            "session"=>$sessionId,
            "session-time"=>$sessionTime,
            "server"=>ip2long($_SERVER["SERVER_ADDR"]),
            "app"=>$this->man->name(),
        ];
        //세션정보 객체 초기화
        $this->data = [
            "permission"=>self::GUEST,
            "ip"=>ip2long($_SERVER["REMOTE_ADDR"]),
            "first-referer"=>isset($_SERVER["HTTP_REFERER"])?$_SERVER["HTTP_REFERER"]:"",
            "user-agent"=>isset($_SERVER["HTTP_USER_AGENT"])?$_SERVER["HTTP_USER_AGENT"]:"",
            "user-language"=>isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])?$_SERVER["HTTP_ACCEPT_LANGUAGE"]:"",
            "token-time"=>$sessionTime,
        ];
        $this->change = true;
    }
    /**
     * 세션 복호화한 후 불러오기
     */
    protected function load(){
        //쿠키에 저장한 세션 복호화
        if(!array_key_exists("s", $_COOKIE)){
            $this->guest();
            return;
        }
        $decrypted = "";
        if(openssl_private_decrypt(base64_decode($_COOKIE["s"]), $decrypted, $this->man->privateKey())===false){
            $this->guest();
            return;
        }
        //사용자 인증 배열에 복호화한 데이터 입력
        $session = [
            "permission"=>unpack("J", substr($decrypted, 8, 8))[1],
            "session"=>unpack("J", substr($decrypted, 16, 8))[1],
            "session-time"=>unpack("J", substr($decrypted, 24, 8))[1],
            "server"=>unpack("N", substr($decrypted, 32, 4))[1],
            "app"=>unpack("C", substr($decrypted, 36))[1],
        ];
        //세션 파일이 존재하지 않는다면 RSA 키 탈취 가능성 있으므로 리셋.
        if(!file_exists($sessionPath = $this->man->path("session").base64_encode(pack("J", $session["session"])))){
            $this->man->reset();
            $this->black(24, "세션 파일 존재하지 않아 RSA 키 탈취 가능성");
        }
        //세션 로드
        foreach(explode("\n", File::read($sessionPath)) as $keyValue){
            if($keyValue==""){continue;}
            list($key,$value) = explode("\t", $keyValue);
            $data[$key] = $value;
        }
        //세션 도메인 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($this->man->name()!=$session["app"]){
            $this->black(1, "세션 도메인 불일치");
        }
        //agent 값 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($_SERVER["HTTP_USER_AGENT"]!=$data["ra"]){
            $this->black(1, "세션 HTTP_USER_AGENT 불일치");
        }
        //언어값 일치 여부 확인, 일치하지 않으면 블랙 처리
        if($_SERVER["HTTP_ACCEPT_LANGUAGE"]!=$data["rl"]){
            $this->black(1, "세션 HTTP_ACCEPT_LANGUAGE 불일치");
        }
        //토큰이 제시되었다면 생성시간과 세션 아이디를 sha256으로 인코딩하여 일치여부 확인
        if(array_key_exists("t", $_COOKIE) && $_COOKIE["t"]!=hash("sha256",$data["token-time"].$session["session"])){
            $this->black(1, "토큰 불일치. 변조 가능성");
        }
        $this->session = $session;
        $this->data = $data;
        //토큰 신규 생성
        $this->token = hash("sha256",($tokenTime = time()).$this->session["session"]);
        $this->data["token-time"] = $tokenTime;
        $this->change = true;
    }
    /**
     * 세션 파일에 저장
     */
    public function save(){
        if($this->change){
            //APCU 메모리에 토큰으로 세션 데이터 저장
            apcu_store($this->man->name()."s".$this->token, $this->data, $this->man->tokenExpire());
            //쿠키에 토큰 등록
            setcookie("t", $this->token, time()+$this->man->tokenExpire(), "/", $this->man->servername(), true, true);
            //쿠키에 세션 등록
            $data = pack("J", time());
            $data .= pack("J", $this->session["permission"]);
            $data .= pack("J", $this->session["session"]);
            $data .= pack("J", $this->session["session-time"]);
            $data .= pack("N", $this->session["server"]);
            $data .= pack("C", $this->session["app"]);
            $crypted = "";
            openssl_public_encrypt($data, $crypted, $this->man->publicKey());
            setcookie("s", base64_encode($crypted), $this->session["session-time"]+$this->man->sessionExpire(), "/", $this->man->servername(), true, true);
            //세션 파일에 정보 저장
            $data = "";
            foreach($this->data as $key=>$value){$data .= "{$key}\t{$value}\n";}
            File::write($this->man->path("session").base64_encode(pack("J", $this->session["session"])),$data);
            $this->change = false;
        }
    }
}