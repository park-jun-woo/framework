<?php
namespace Parkjunwoo\Util;

class File{
    protected const MAP = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_";
    /**
     * 지정한 경로에 텍스트를 덮어쓰기로 저장해준다.
     * 덮어 쓰기이므로 멀티쓰레드 기능은 제공하지 않는다.
     * @param string $path 경로
     * @param string $content 저장할 텍스트 내용
     */
    public static function write(string $path, string $content){
        if(!is_dir($folder = dirname($path))) {mkdir($folder, 0777, true);}
        $handle = fopen($path, "wb+");
        if(flock($handle, LOCK_EX)){fwrite($handle, $content);}
        flock($handle, LOCK_UN);
        fclose($handle);
    }
    /**
     * 지정한 경로에 텍스트를 추가로 붙여넣는다.
     * 멀티쓰레드를 고려해 파일이 락이 걸려있는 경우 다른 여러 개의 파일에 기록할 수 있도록 한다.
     * 첫 파일은 지정한 경로의 파일명 그대로며, 2번째부터 숫자를 붙인다.
     * 파일명 길이를 최소화하기위해 1에서 9까지 증가한 후 다음은 a에서 z까지 증가한다.
     * @param string $path 경로
     * @param string $content 덧붙일 내용
     * @param int $max 파일을 저장할 파일의 최대 개수, 기본값은 1
     */
    public static function append(string $path, string $content, int $max=1){
        if(!is_dir($folder = dirname($path))) {mkdir($folder, 0777, true);}
        if($max<=1){
            $handle = fopen($path, "a");
            if(flock($handle, LOCK_EX)){fwrite($handle, $content);}
        }else{
            $process = 0;
            while(true){
                $handle = fopen($path.($process==0?"":".".File::MAP[$process++]), "a");
                if(flock($handle, LOCK_EX|LOCK_NB)){break;}
                fclose($handle);
                if($process>=$max){$process = 0;}
            }
            fwrite($handle,$content);
        }
        flock($handle,LOCK_UN);
        fclose($handle);
    }
    /**
     * 지정한 경로의 파일을 읽는다.
     * @param string $path 경로
     * @return string|null 읽은 텍스트, 파일이 없는 경우 null 반환
     */
    public static function read(string $path):?string{
        if(!file_exists($path)){return null;}
        $handle = fopen($path, "r");
        if(flock($handle, LOCK_SH)){
            $result = fread($handle, filesize($path));
            flock($handle, LOCK_UN);
        }
        fclose($handle);
        return $result;
    }
    /**
     * 지정한 경로 파일을 불러와 저장한 숫자를 조회하고 지정한만큼 증가한다.
     * 지정한 경로에 파일이 없을 경우 0을 반환하고 파일을 새로 만들어 $amount만큼 증가시켜서 저장한다.
     * @param string $path 경로
     * @param int $amount 증가할 숫자, 기본값은 1
     * @return int 조회한 숫자
     */
    public static function increase(string $path, int $amount=1):int{
        if(!is_dir($folder = dirname($path))) {mkdir($folder, 0777, true);}
        //파일을 읽는다.
        if(file_exists($path)){
            if(flock($handle = fopen($path, "r"), LOCK_EX)){$key = unpack("P", fread($handle, 8))[1];}
            flock($handle,LOCK_UN);fclose($handle);
        }else{$key = $amount;}
        //값을 올리고 저장한다.
        if(flock($handle = fopen($path,"wb+"), LOCK_EX)){fwrite($handle, pack("P", $key+$amount));}
        flock($handle, LOCK_UN);fclose($handle);
        return $key;
    }
    /**
     * 폴더 목록 조회
     *
     * @param string $path 조회할 경로
     * @return array
     */
    public static function getDirectories(string $path):array{
        $contents = scandir($path);
        $dirs = [];
        foreach($contents as $item) {
            if ($item != '.' && $item != '..' && is_dir($path.DS.$item)) {
                $dirs[] = $item;
            }
        }
        return $dirs;
    }
    /**
     * 파일 목록 조회
     *
     * @param string $path 조회할 경로
     * @return array
     */
    public static function getFiles(string $path):array{
        $contents = scandir($path);
        $files = [];
        foreach($contents as $item) {
            if ($item!='.' && $item!='..' && !is_dir($path.DS.$item)) {
                $files[] = $item;
            }
        }
        return $files;
    }
}