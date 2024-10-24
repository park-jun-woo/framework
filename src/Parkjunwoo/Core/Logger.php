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
     * @param int $ip 접속 IP
     * @param string $referer 리퍼러URL
     * @param string $access 접속URL
     */
    public function record(int $sessionId, int $ip, string $referer, string $access){
        //기록 시간
        $time = time();
        //날짜
        $date = date("Ymd", $time);
        //APCU 메모리에 포맷이 있으면
        if(apcu_exists($key_apcu = "{$this->key_apcu}@{$date}")){
            //APCU 메모리에서 포맷 조회
            $format = apcu_fetch($key_apcu);
            //포맷 파일이 없으면
            if(!file_exists($format_path = $this->man->path("log").$date.DS.".f")){
                //포맷 파일에 저장
                File::write($format_path, $format);
            }
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
                $format = "{$time_pack}V{$pack}{$pack}";
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
        $stream = pack($format, $time, $ip, $refererId, $accessId);
        //기록하기
        File::append($log_path, $stream);
    }
    /**
     * URL의 인덱스 조회, 최초라면 등록한다.
     * @param string $key 데이터값
     * @return int 조회한 인덱스
     */
    public function getIndex(string $url):int{
        //URL이 빈문자열이면 0 반환
        if($url==""){return 0;}
        //URL 변환
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
        //폴더가 없으면 생성한다.
        if(!is_dir($this->key_path)) {mkdir($this->key_path, 0777, true);}
        //인덱스 부여 파일 경로
        $id_path = $this->key_path.".id";
        //인덱스 부여 파일 열기
        $id_handle = fopen($id_path, file_exists($id_path)?"rb+":"wb+");
        // 배타락
        if(flock($id_handle, LOCK_EX)){
            //인덱스 목록 파일 열기
            $list_handle = fopen($this->key_path.".list","a");
            //데이터 파일 열기
            $file_handle = fopen($key_path,"rb+");
            //인덱스 부여 파일 크기를 확인
            $id_stat = fstat($id_handle);$id_file_size = $id_stat['size'];
            //인덱스 부여 파일에 내용이 없으면 초기화, 있으면 인덱스 값 조회
            if($id_file_size===0){$id = 1;}else{$id = unpack('P', fread($id_handle, 8))[1];}
            //인덱스 증가하여 덮어쓰기
            fseek($id_handle, 0);fwrite($id_handle, pack('P', $id+1));
            //인덱스로 URL 조회를 위해 기록
            fwrite($list_handle, "$id $url\n");
            //파일에 데이터 저장
            fwrite($file_handle, $stream = pack('P', $id));
            //인덱스 부여 파일 강제적용
            fflush($id_handle);fsync($id_handle);
            //인덱스 목록 파일 강제적용
            fflush($list_handle);fsync($list_handle);
            //데이터 파일 강제적용
            fflush($file_handle);fsync($file_handle);
            //APCU 메모리에 데이터 저장
            apcu_store($key_apcu, $stream);
            //인덱스 부여 파일 언락
            flock($id_handle, LOCK_UN);
        }
        //인덱스 부여 파일 닫기
        fclose($id_handle);
        //인덱스 목록 파일 닫기
        fclose($list_handle);
        //데이터 파일 닫기
        fclose($file_handle);
        //인덱스 반환
        return $id;
    }
}