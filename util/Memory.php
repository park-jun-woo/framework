<?php
namespace util;

class Memory{
	public static function get(int $key){
		return apcu_fetch($key);
	}
	
	public static function set(int $key, $value):int{
		
		apcu_store($key, $value);
	}
	
	public static function delete(int $key):bool{
		return apcu_delete($key);
	}
	
	public static function lock(int $key, int $operation, int &$wouldblock=null):bool{
		switch($operation){
			case LOCK_SH:
				break;
			case LOCK_EX:
				break;
			case LOCK_UN:
				break;
			case LOCK_SH|LOCK_NB:
				break;
			case LOCK_EX|LOCK_NB:
				break;
		}
	}
	
	public static function keyToInt(string $key, int $system=0):int{
		if(($length = strlen($key))>12){return -1;}
		if($system<1 || $system>15){return -1;}
		$int = 0;
		for($iu=0;$iu<$length;$iu++){
			$char = ord($key[$iu]);
			if($char>41 && $char<48){$char -= 16;}
			else if($char>96 && $char<123){$char -= 97;}
			else{return -1;}
			$int <<= 5;
			$int |= $char;
		}
		$int |= $system<<60;
		return $int;
	}
}