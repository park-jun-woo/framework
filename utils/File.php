<?php
namespace utils;

class File{
	/**
	 * 지정한 경로에 텍스트를 저장해준다.
	 * @param string $path 저장할 경로
	 * @param string $content 저장할 텍스트 내용
	 */
	public static function write(string $path, string $content){
		$handle = fopen($path, "wb+");
		if(flock($handle, LOCK_EX)){
			fwrite($handle, $content);
			flock($handle, LOCK_UN);
		}
		fclose($handle);
	}
	public static function append(string $path, string $content){
		$handle = fopen($path, "a");
		if(flock($handle, LOCK_EX)){
			fwrite($handle, $content);
			flock($handle, LOCK_UN);
		}
		fclose($handle);
	}
	public static function read(string $path){
		if(!file_exists($path)){return false;}
		$handle = fopen($path, "r");
		if(flock($handle, LOCK_SH)){
			$result = fread($handle, filesize($path));
			flock($handle, LOCK_UN);
		}
		fclose($handle);
		return $result;
	}
	public static function increase(string $path){
		//파일을 읽는다.
		if(file_exists($path)){
			if(flock($handle = fopen($path, "r"), LOCK_EX)){$key = unpack("J", fread($handle, 8))[1];}
			flock($handle,LOCK_UN);fclose($handle);
		}else{$key = 1;}
		//값을 올리고 저장한다.
		if(flock($handle = fopen($path,"wb+"), LOCK_EX)){fwrite($handle, pack("J", $key+1));}
		flock($handle, LOCK_UN);fclose($handle);
		return $key;
	}
}