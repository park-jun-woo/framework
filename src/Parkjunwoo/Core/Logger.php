<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Util\File;

class Logger{
    protected Parkjunwoo $man;
    protected string $key_apcu;
    protected string $key_path;
    /**
     * Logger 생성자
     * DB 연결
     * @param Parkjunwoo $man 프레임워크 객체
     */
    public function __construct(Parkjunwoo $man){
        $this->man = $man;
        $this->key_apcu = $this->man->id();
        $this->key_path = $this->man->path("data")."url".DS;
    }
    /**
     * 기록하기
     * @param int $sessionId 세션아이디
     * @param string $referer 리퍼러URL
     * @param string $access 접속URL
     */
    public function record(int $sessionId, string $referer, string $access){
        //기록 시간
        $time = time();
        //날짜
        $date = date("Ymd", $time);
        //APCU 메모리에 포맷이 있으면
        if(apcu_exists($key_apcu = "{$this->key_apcu}@{$date}")){
            //APCU 메모리에서 포맷 조회
            $format = apcu_fetch($key_apcu);
        }else{
            //포맷 파일이 있으면
            if(file_exists($format_path = $this->man->path("log").$date.DS.".f")){
                //포맷 파일에서 포맷 가져오기
                $format = File::read($format_path);
            }else{
                //파일에서 URL 인덱스 크기 조회
                if(file_exists($key_path = "{$this->key_path}.id")){$length = unpack('P', File::read($key_path))[1];}else{$length = 0;}
                //URL 인덱스 크기가 2바이트 부호있는 정수보다 작으면 2바이트 부호없는 정수로 설정
                if($length < 32768){$pack = 'v';}
                //URL 인덱스 크기가 4바이트 부호있는 정수보다 작으면 4바이트 부호없는 정수로 설정
                else if($length < 2147483648){$pack = 'V';}
                //URL 인덱스 크기가 4바이트 부호있는 정수보다 크면 8바이트 부호없는 정수로 설정
                else{$pack = 'P';}
                //4바이트 정수는 2038년1월19일까지만 가능하므로 2038년1월1일부터는 8바이트 정수로 저장
                $time_pack = $time<2145916800?"V":"P";
                //포맷 설정
                $format = "{$time_pack}{$pack}{$pack}";
                //포맷 파일에 저장
                File::write($format_path, $format);
            }
            //APCU 메모리에 포맷 저장
            apcu_store($key_apcu, $format, 86400);
        }
        //리퍼러URL의 인덱스 조회
        $refererId = $this->getIndex($referer);
        //접속URL의 인덱스 조회
        $accessId = $this->getIndex($access);
        //로그 파일 경로 = 로그 폴더 + 날짜 + 세션아이디
        $log_path = $this->man->path("log").$date.DS.base_convert($sessionId, 10, 36);
        //로그에 기록할 데이터
        $stream = pack($format, $time, $refererId, $accessId);
        //기록하기
        File::append($log_path, $stream);
    }
    /**
     * URL의 인덱스 조회, 최초라면 등록한다.
     * @param string $key 데이터값
     * @return int 조회한 인덱스
     */
    public function getIndex(string $url):int{
        $key = str_replace('/', ':', $url);
        //APCU 메모리에 URL값이 있으면 바로 반환
        if(apcu_exists($key_apcu = $this->key_apcu.$key)){
            //APCU 메모리에서 데이터 불러오기
            $stream = apcu_fetch($key_apcu);
            //인덱스 데이터를 정수로 변환해서 반환
            return unpack('P', substr($stream, 0, 8))[1];
        }
        //파일에 URL값이 있으면 바로 반환
        if(file_exists($key_path = $this->key_path.$key)){
            //파일에서 데이터 불러오기
            $stream = File::read($key_path);
            //APCU 메모리에 데이터 저장
            apcu_store($key_apcu, $stream);
            //인덱스 데이터를 정수로 변환해서 반환
            return unpack('P', substr($stream, 0, 8))[1];
        }
        //마지막 세션 아이디 조회 및 부여
        $id = File::increase($this->key_path.".id");
        //인덱스 정수를 데이터로 변환
        $stream = pack('P', $id);
        //APCU 메모리에 데이터 저장
        apcu_store($key_apcu, $stream);
        //파일에 데이터 저장
        File::write($key_path, $stream);
        return $id;
    }
}