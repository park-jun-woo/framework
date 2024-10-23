<?php
namespace Parkjunwoo\Lab;
use Parkjunwoo\Util\File;
use lastguest\Murmur;
class HashMap{
    protected string $id;

    protected string $key_apcu;
    protected string $hash_apcu;
    protected string $index_apcu;

    protected string $hash_path;
    protected string $index_path;
    protected string $data_path;

    protected array $hash;
    protected array $index;
    protected int $length;

    protected int $hash_bit;
    protected int $hash_size;
    protected int $hash_mask;
    protected string $slot_pack;
    protected int $slot_size;
    protected int $slot_empty;
    protected string $index_pack;
    protected int $index_size;
    /**
     * 생성자
     * @param string $id 해시맵 아이디
     * @param string $path 캐시 파일 저장할 경로
     * @param int $hash_bit 해시 크기 비트
     * @param string $slot_pack 슬롯 타입
     * @param int $slot_size 슬롯 크기
     * @param string $index_pack 인덱스 타입
     * @param int $index_size 인덱스 크기
     */
    public function __construct(string $id, string $path, int $hash_bit=10, string $slot_pack='P', int $slot_size=8, string $index_pack='P', int $index_size=8){
        $this->id = $id;
        $this->key_apcu = "#".$this->id.">";
        //해시테이블 APCU 메모리 주소
        $this->hash_apcu = "#".$this->id.".m";
        //인덱스 APCU 메모리 주소
        $this->index_apcu = "#".$this->id.".i";
        //해시테이블 파일 경로
        $this->hash_path = $path.$this->id.".m";
        //인덱스 파일 경로
        $this->index_path = $path.$this->id.".i";
        //데이터 파일 경로
        $this->data_path = $path.$this->id.".d";
        //해시 크기 초기화
        $this->hash_bit = $hash_bit;
        //해시 크기 계산
        $this->hash_size = 1<<$this->hash_bit;
        //해시 마스크 계산
        $this->hash_mask = $this->hash_size-1;
        //슬롯 타입 초기화
        $this->slot_pack = $slot_pack;
        //슬롯 사이즈 초기화
        $this->slot_size = $slot_size;
        //빈 슬롯값 - 슬롯 크기로 담을 수 있는 정수의 최대값 계산
        $this->slot_empty = (1 << ($this->slot_size * 8)) - 1;
        //인덱스 타입 초기화
        $this->index_pack = $index_pack;
        //인덱스 사이즈 초기화
        $this->index_size = $index_size;
    }
    /**
     * 인덱스로 데이터값 조회
     * @param int $index 인덱스
     * @return string|null 조회한 데이터
     */
    public function getValue(int $index):?string{
        //인덱스 로드
        $this->loadIndex();
        if($index<$this->length){
            $data_position = $this->index[$index];
        }else{
            return null;
        }
    }
    /**
     * 데이터의 인덱스 조회, 최초라면 등록한다.
     * @param string $key 데이터값
     * @return int 조회한 인덱스
     */
    public function getIndex(string $key):int{
        //APCU 메모리에 키값이 있으면 바로 반환
        if(apcu_exists($key_apcu = $this->key_apcu.$key)){return apcu_fetch($key_apcu);}
        //해시테이블 로드
        $this->loadHash();
        //머머해시 함수값을 구해 상위 hash_bit 비트값을 가져온다.
        $slotKey = (Murmur::hash3_int($key) >> (32 - $this->hash_bit)) & $this->hash_mask;
        //해시테이블을 상위 hash_bit 비트값으로 조회
        $slotValue = $this->hash[$slotKey];
        //조회한 슬롯 값이 빈 슬롯이라면
        if($slotValue===$this->slot_empty){
            $this->regist($slotKey, $key);
        }
        //해시테이블에 
        //if($this->length>0 && $handle===0){}
        return 0;
    }
    /**
     * 현재 등록한 데이터의 총 개수
     * @return int 총 개수
     */
    public function length():int{
        return $this->length;
    }
    /**
     * 해시테이블 로드
     */
    protected function regist(int $slotKey, string $key){
        //데이터 팩킹
        $data = pack('v P P', strlen($key), $this->length(), $this->slot_empty).$key;
        //데이터 파일을 열고
        $file_handle = fopen($this->data_path, "rb+");
        //데이터 파일에 락을 걸고
        if(flock($file_handle, LOCK_EX)){
            //인덱스 로드
            $this->loadIndex();
            //데이터 파일의 마지막 위치
            $file_size = filesize($this->data_path);

            //해시테이블에 현재 데이터 파일의 마지막 위치 저장
            $this->hash[$slotKey] = $file_size;
            //해시테이블 직렬화
            $hash_data = pack($this->slot_pack.'*', ...$this->hash);
            //파일에 해시테이블 저장
            File::write($this->hash_path, $hash_data);
            //APCU 메모리에 해시테이블 저장
            apcu_store($this->hash_apcu, $hash_data);

            //인덱스에 현재 데이터 파일의 마지막 위치 저장
            $this->index[] = $file_size;
            //인덱스 직렬화
            $index_data = pack($this->index_pack.'*', ...$this->index);
            //파일에 인덱스 저장
            File::write($this->index_path, $index_data);
            //APCU 메모리에 인덱스 저장
            apcu_store($this->index_apcu, $index_data);

            //APCU 메모리에 키값 저장
            apcu_store($this->key_apcu.$key, $this->length);

            //파일 핸들을 데이터 파일의 끝으로 이동
            fseek($file_handle, $file_size);
            //데이터 파일 끝에 데이터 추가하기
            fwrite($file_handle, $data);

            $this->length = count($this->index);
        }
        //데이터 파일에 락을 해제
        flock($file_handle, LOCK_UN);
    }
    /**
     * 해시테이블 로드
     */
    protected function loadHash(){
        if(!isset($this->hash)){
            //APCU 메모리에 해시테이블이 없다면
            if(!apcu_exists($this->hash_apcu)){
                //해시테이블 파일이 없다면
                if(!file_exists($this->hash_path)){
                    //해시테이블 초기화
                    $this->hash = array_fill(0, $this->hash_size, $this->slot_empty);
                    //해시테이블 직렬화
                    $hash_data = pack($this->slot_pack.'*', ...$this->hash);
                    //파일에 해시테이블 저장
                    File::write($this->hash_path, $hash_data);
                }else{
                    //파일에서 해시테이블 조회
                    $hash_data = File::read($this->hash_path);
                    //해시테이블 직렬화
                    $this->hash = unpack($this->slot_pack.'*', $hash_data);
                }
                //APCU 메모리에 해시테이블 저장
                apcu_store($this->hash_apcu, $hash_data);
            }else{
                //APCU 메모리에서 해시테이블 조회
                $this->hash = unpack($this->slot_pack.'*', apcu_fetch($this->hash_apcu));
            }
        }
    }
    /**
     * 인덱스 로드
     */
    protected function loadIndex(){
        if(!isset($this->index)){
            //APCU 메모리에 인덱스이 없다면
            if(!apcu_exists($this->index_apcu)){
                //인덱스 파일이 없다면
                if(!file_exists($this->index_path)){
                    //인덱스 초기화
                    $this->index = [];
                    $index_data = "";
                    //파일에 인덱스 저장
                    File::write($this->index_path, $index_data);
                }else{
                    //파일에서 인덱스 조회
                    $index_data = File::read($this->index_path);
                    //해시테이블 직렬화
                    $this->index = pack($this->index_pack.'*', $index_data);
                }
                //APCU 메모리에 인덱스 저장
                apcu_store($this->index_apcu, $index_data);
            }else{
                //APCU 메모리에서 인덱스 조회
                $this->index = unpack($this->index_pack.'*', apcu_fetch($this->index_apcu));
            }
            //인덱스의 총 개수 구하기
            $this->length = count($this->index);
        }
    }
}