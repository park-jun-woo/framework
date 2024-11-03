<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Util\File;

class User{
    const GUEST = 0;
    const PROJECT = 1;
    const SERVERIP = 2;
    const SESSION = 3;
    const TOKENTIME = 4;
    const IP = 5;
    const PERMISSION = 6;
    const MEMBER = 7;
    const LANGUAGE = 8;

    protected string $token;
    protected array $session;
    protected bool $change = false;

    protected int $id, $token_expire, $session_expire;
    protected string $key, $data_path, $private_key, $public_key, $server_name;
    protected array $permissions;
    /**
     * 사용자 생성자
     */
    protected function __construct(Config $config){
        //프로젝트 아이디 가져오기
        $this->id = $config->id();
        //프로젝트 키값 가져오기
        $this->key = $config->key();
        //서버 이름 가져오기
        $this->server_name = $config->serverName();
        //토큰 유효기간 가져오기
        $this->token_expire = $config->tokenExpire();
        //세션 유효기간 가져오기
        $this->session_expire = $config->sessionExpire();
        //RSA 비대칭 개인키 가져오기
        $this->private_key = $config->privateKey();
        //RSA 비대칭 공개키 가져오기
        $this->public_key = $config->publicKey();
        //권한 목록 가져오기
        $this->permissions = $config->permissions();
        //데이터 경로 가져오기
        $this->data_path = $config->path()->data();
        //쿠키에 세션이 없다면 신규 생성
        if(!array_key_exists("s", $_COOKIE)){$this->guest();return;}
        //쿠키에 토큰은 없는데 세션은 있다면 만료된 것으로 보고 세션 로드 후 토큰 재발행
        else if(!array_key_exists("t", $_COOKIE) && array_key_exists("s", $_COOKIE)){$this->load();return;}
        //APCU 메모리에 토큰이 등록되어 있지 않으면 만료된 것으로 보고 세션 로드 후 토큰 재발행
        if(!apcu_exists("{$this->key}@t{$_COOKIE['t']}")){$this->load();return;}
        //APCU 메모리에서 토큰으로 세션 데이터 조회
        $stream = apcu_fetch("{$this->key}@t{$_COOKIE['t']}");
        //세션 데이터 언팩킹
        $session = unpack('V/V/P/P/V/P/P/a2/v/a2', $stream);
        //IP가 달라졌다면 세션 로드 후 토큰 재발행
        if(ip2long($_SERVER["REMOTE_ADDR"])!=$session[self::IP]){$this->load();return;}
        //토큰 쿠키 있으면 토큰 멤버변수에 할당
        $this->token = $_COOKIE["t"];
        //세션 멤버변수에 할당
        $this->session = $session;
    }
    /**
     * 회원 인덱스 조회
     * @param int $member 지정할 회원 인덱스
     * @return int 인덱스 값
     */
    public function member(int $member=null):int{
        if($member!=null){
            if(!isset($this->session)){$this->load(false);}
            $this->session[self::MEMBER] = $member;
            $this->change = true;
        }
        return $this->session[self::MEMBER];
    }
    /**
     * 언어 조회
     * @param string $language 지정할 언어 코드 (영문자 2글자)
     * @return string 언어 코드값
     */
    public function language(string $language=null):string{
        if($language!=null){
            if(!isset($this->session)){$this->load(false);}
            $this->session[self::LANGUAGE] = $language;
            $this->change = true;
        }
        return $this->session[self::LANGUAGE];
    }
    /**
     * 사용자 세션 아이디
     * @return int 세션 아이디;
     */
    public function session():int{
        return $this->session[self::SESSION];
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
        return $_SERVER["HTTP_USER_AGENT"];
    }
    /**
     * 세션 데이터 키값 조회
     * @param int $key 키
     * @return string 값
     */
    public function get(int $key):string{
        return $this->session[$key];
    }
    /**
     * 세션 데이터에 키값 입력
     * @param int $key 키
     * @param string $value 값
     * @return bool 성공 여부
     */
    public function set(int $key, string $value):bool{
        switch($key){
            case self::TOKENTIME:
            case self::IP:
            case self::MEMBER:
            case self::LANGUAGE:
                if(!isset($this->session)){$this->load(false);}
                $this->session[$key] = $value;
                $this->change = true;
                return true;
            case self::PERMISSION:
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
        return ($this->session[self::PERMISSION] & $permission) !== 0;
    }
    /**
     * 권한 추가
     * @param int $permission 권한
     */
    public function add(int $permission){
        if(!isset($this->session)){$this->load(false);}
        $this->session[self::PERMISSION] |= $permission;
        $this->change = true;
    }
    /**
     * 권한 제거
     * @param int $permission 권한
     */
    public function remove(int $permission){
        if(!isset($this->session)){$this->load(false);}
        $this->session[self::PERMISSION] &= ~$permission;
        $this->change = true;
    }
    /**
     * 접속자 보유 권한 조회
     * @return string 권한 이름 나열 ,로 묶음
     */
    public function permissionNames():string{
        $names = [];
        foreach($this->permissions as $key=>$value){
            if(($this->session[self::PERMISSION] & $key)===$key){$names[] = $value;}
        }
        return implode(",", $names);
    }
    /**
     * 불량 접속자 블랙리스트 처리
     * @param int $level 블랙 수준, 높을 수록 심각
     * @param string $log 로그 
     */
    public function black(float $level,string $log){
        apcu_store("{$this->key}@b{$_SERVER['REMOTE_ADDR']}", "", 86400);
        $date = date("Y-m-d H:i:s");
        File::append("{$this->data_path}blacklist", "{$_SERVER['REMOTE_ADDR']}\t{$date}\t{$level}\t{$log}\n");
        exit;
    }
    /**
     * 방문자 기본 세션 설정
     */
    protected function guest(){
        //마지막 세션 아이디 조회 및 부여
        $session_id = File::increase("{$this->data_path}session");
        //토큰 생성 시간
        $token_time = time();
        //세션 객체 초기화
        $this->session = [
            null,
            $this->id,                          /*PROJECT ID*/
            ip2long($_SERVER["SERVER_ADDR"]),   /*SERVER IP*/
            $session_id,                        /*SESSION*/
            $token_time,                        /*TOKENTIME*/
            ip2long($_SERVER["REMOTE_ADDR"]),   /*IP*/
            self::GUEST,                        /*PERMISSION*/
            0,                                  /*MEMBER*/
            ""                                  /*LANGUAGE*/
        ];
        //토큰 생성
        $this->token = hash("sha256",$session_id.$token_time);
        //변경 여부 예
        $this->change = true;
    }
    /**
     * 세션 복호화한 후 불러오기
     */
    protected function load(bool $newToken=true){
        //세션 쿠키 없으면 신규 세션 생성
        if(!array_key_exists("s", $_COOKIE)){$this->guest();return;}
        //세션 쿠키 비대칭 복호화
        if(openssl_private_decrypt(base64_decode(urldecode($_COOKIE["s"])), $decrypted, $this->private_key)===false){
            //실패하면 신규 세션 생성
            $this->guest();return;
        }
        //PJWF 언팩
        $PJWF = unpack("a4", substr($decrypted, 2, 4))[1];
        //사용자 인증 배열에 복호화한 데이터 입력
        $session = unpack('V/V/P/P/V/P/P/a2/v/a2', $decrypted);
        //PJWF 문자열이 아니라면 세션 임의 변조한 것이므로, 블랙 처리
        if($session[10]!="PF"){$this->black(1, "PJWF 불일치, 세션 쿠키 임의 변조");}
        //앱 아이디 불일치, 블랙 처리
        if($this->id!=$session[self::PROJECT]){$this->black(1, "앱 아이디 불일치");}
        //세션 멤버변수에 할당
        $this->session = $session;
        //토큰 신규 생성
        if($newToken){
            //토큰 생성 시간 갱신
            $this->session[self::TOKENTIME] = time();
            //토큰값 재설정
            $this->token = hash("sha256", $this->session[self::SESSION].$this->session[self::TOKENTIME]);
            //변경 여부 예
            $this->change = true;
        }
    }
    /**
     * 세션 파일에 저장
     */
    public function save(){
        //변경 여부 '예'라면
        if($this->change){
            //세션 쿠키 팩킹
            $stream = pack(
                'VVPPVPPa2va2',
                $this->session[self::PROJECT],
                $this->session[self::SERVERIP],
                $this->session[self::SESSION],
                $this->session[self::TOKENTIME],
                $this->session[self::IP],
                $this->session[self::PERMISSION],
                $this->session[self::MEMBER],
                $this->session[self::LANGUAGE],
                time(),
                "PF"
            );
            //세션 쿠키 비대칭 암호화
            openssl_public_encrypt($stream, $crypted, $this->public_key);
            //세션 쿠키 BASE64 및 URL 인코드
            $cookieData = urlencode(base64_encode($crypted));
            //쿠키에 세션 등록 
            setcookie("s", $cookieData, time()+$this->session_expire, "/", $this->server_name, true, true);
            //쿠키에 토큰 등록
            setcookie("t", $this->token, time()+$this->token_expire, "/", $this->server_name, true, true);
            //APCU 메모리에 토큰으로 세션 데이터 저장
            apcu_store("{$this->key}@t{$this->token}", $stream, $this->token_expire);
            //변경 여부 아니오
            $this->change = false;
        }
    }
}