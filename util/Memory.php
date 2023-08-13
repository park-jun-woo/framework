<?php
namespace util;

class Memory{
	public static function get(string $key){
		return apcu_fetch($key);
	}
	
	public static function set(string $key, $value):int{
		while(!apcu_add("l*".$key,1)){
			
		}
		
		apcu_store($key, $value);
	}
	
	public static function delete(string $key):bool{
		return apcu_delete($key);
	}
	
	protected static function lock(string $key, int $operation, int &$wouldblock=null):bool{
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
}