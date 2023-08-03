<?php
namespace utils;

class File{
	protected const MAP = ["0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];
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
	/**
	 * 지정한 경로에 텍스트를 붙여넣는다.
	 * 멀티쓰레드를 고려해 파일이 락이 걸려있는 경우 다른 여러 개의 파일에 기록할 수 있도록 한다.
	 * @param string $path 기록할 경로
	 * @param string $content 기록할 내용
	 * @param int $max 파일을 저장할 파일의 최대 개수
	 */
	public static function append(string $path, string $content, int $max=1){
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