<?php
namespace Parkjunwoo\Data;

use Parkjunwoo\Util\File;

class Index{
    protected string $key_apcu;
    protected string $key_path;
    protected int $length;
    protected array $list;
    /**
     * Index 생성자
     * @param string $id 아이디
     * @param string $data_path 데이터 폴더 경로
     */
    public function __construct(string $id, string $data_path){
        //폴더 구분자
        $DS = DIRECTORY_SEPARATOR;
        $this->key_apcu = $id;
        $this->key_path = "{$data_path}{$id}{$DS}";
    }
    /**
     * 등록한 값의 총 개수
     * @return int 총 개수
     */
    public function length():int{
        if(!isset($this->length)){
            if(file_exists($this->key_path.".id")){
                $this->length = File::increase($this->key_path.".id", 0);
            }
        }
        return $this->length;
    }
    /**
     * 인덱스로 키값 조회
     * @param int $index 인덱스
     * @return string 조회한 값
     */
    public function getValue(int $index):string{
        return "";
    }
    /**
     * 키값의 인덱스 조회, 최초라면 등록한다.
     * @param string $key 데이터값
     * @return int 조회한 인덱스
     */
    public function getIndex(string $value):int{
        //키값이 빈문자열이면 0 반환
        if($value==""){return 0;}
        //키값 255글자 제한, 길면 자른다.
        if(strlen($value)>0xFF){$value = substr($value, 0, 0xFF);}
        //APCU 메모리에 키값이 있으면
        if(apcu_exists($key_apcu = $this->key_apcu.$value)){
            //APCU 메모리에서 데이터 불러오기
            $stream = apcu_fetch($key_apcu);
            //인덱스 데이터를 정수로 변환해서 반환
            return unpack('P', substr($stream, 0, 8))[1];
        }
        //파일에 키값이 있으면
        if(file_exists($key_path = $this->key_path.urlencode($value))){
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
        //인덱스 부여 파일 배타락
        if(flock($id_handle, LOCK_EX)){
            //인덱스 목록 파일 열기
            $list_handle = fopen($this->key_path.".list","a");
            //데이터 파일 열기
            $file_handle = fopen($key_path, file_exists($key_path)?"rb+":"wb+");
            //인덱스 부여 파일 크기를 확인
            $id_stat = fstat($id_handle);$id_file_size = $id_stat['size'];
            //인덱스 부여 파일에 내용이 없으면 초기화
            if($id_file_size===0){$id = 1;fwrite($list_handle, "\n");}
            //인덱스 부여 파일에 내용이 있으면 인덱스 값 조회
            else{$id = unpack('P', fread($id_handle, 8))[1];}
            //인덱스 증가하여 덮어쓰기
            fseek($id_handle, 0);fwrite($id_handle, pack('P', $id+1));
            //인덱스로 값 조회를 위해 기록
            fwrite($list_handle, "$value\n");
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
?>