<?php
namespace utils;

use Parkjunwoo;

class Log{
	public static function append(string $path, string $content, int $max=8){
		if($max<=1){
			$handle = fopen($path, "a");
			if(flock($handle, LOCK_EX)){fwrite($handle, $content);}
		}else{
			$process = 0;
			while(true){
				$handle = fopen($path.($process++), "a");
				if(flock($handle, LOCK_EX|LOCK_NB)){break;}
				fclose($handle);
				if($process>=$max){$process = 0;}
			}
			fwrite($handle,$content);
		}
		flock($handle,LOCK_UN);
		fclose($handle);
	}
	
	public static function error(string $path, string $message){
		$path = Parkjunwoo::man()->path("log")."error";
		if($path==""){$path = str_replace(basename(__FILE__),"",realpath(__FILE__))."log".DIRECTORY_SEPARATOR."error";}
		self::append($path, date("Y-m-d H:i:s")."\t".$message, 1);
	}
}