<?php
namespace Parkjunwoo\Util;

use Parkjunwoo\Data\Index;

class Log{
    protected Index $index;
    protected string $key_apcu;
    protected string $key_path;
    protected string $log_path;
    /**
     * Log 생성자
     * @param string $data_path 데이터 폴더 경로
     * @param string $log_path 로그 폴더 경로
     */
    protected function __construct(){
        
    }
    /**
     * 기록하기
     * @param int $sessionId 세션아이디
     * @param string $ip 접속 IP
     * @param string $referer 리퍼러URL
     * @param string $access 접속URL
     */
    public function write(int $sessionId, string $ip, string $referer, string $access){
        //폴더 구분자
        $DS = DIRECTORY_SEPARATOR;
        //기록 시간
        $time = time();
        //날짜
        $date = date("Ymd", $time);
        //APCU 메모리에 포맷이 있으면
        if(apcu_exists($key_apcu = "{$this->key_apcu}@{$date}")){
            //APCU 메모리에서 포맷 조회
            $format = apcu_fetch($key_apcu);
            //포맷 파일이 없으면
            if(!file_exists($format_path = $this->log_path.$date.$DS.".f")){
                //포맷 파일에 저장
                File::write($format_path, $format);
            }
        }else{
            //포맷 파일이 있으면
            if(file_exists($format_path = $this->log_path.$date.$DS.".f")){
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
                $format = "{$time_pack}V{$pack}{$pack}";
                //포맷 파일에 저장
                File::write($format_path, $format);
            }
            //APCU 메모리에 포맷 저장
            apcu_store($key_apcu, $format, 86400);
        }
        //리퍼러URL의 인덱스 조회
        $refererId = $this->index->getIndex($referer);
        //접속URL의 인덱스 조회
        $accessId = $this->index->getIndex($access);
        //로그 파일 경로 = 로그 폴더 + 날짜 + 세션아이디
        $log_path = $this->log_path.$date.$DS.base_convert($sessionId, 10, 36);
        //로그에 기록할 데이터
        $stream = pack($format, $time, ip2long($ip), $refererId, $accessId);
        //기록하기
        File::append($log_path, $stream);
    }
}