<?php
namespace utils;

class Debug{
	public static bool $debug = true;
	protected static array $category = array();
	/**
	 * Enable debug output for the specified category.
	 * @param string $category category
	 */
	public static function active(string $category){self::$category[$category] = true;}
	/**
	 * Disable debug output for the specified category.
	 * @param string $category category
	 */
	public static function disable(string $category){self::$category[$category] = false;}
	
	/**
	 * Print debug messages for the specified category.
	 * @param string $message Debugging message
	 * @param string $category category
	 * @param bool $trace tracing
	 * @param string $path Log file path
	 */
	public static function trace(string $message,string $category="",bool $trace=false,string $path=""){
		if(!self::$debug){return false;}
		if($category!=""){if(array_key_exists($category,self::$category)){if(self::$category[$category]==false){return false;}}}
		if($trace){
			$traceList = debug_backtrace();
			foreach($traceList as &$traceItem){
				if($traceItem["function"]=="trace"){$message .= ",{$traceItem["function"]}() on {$traceItem["line"]}";}
				else{$message .= ",{$traceItem["function"]}() on {$traceItem["line"]} {$traceItem["file"]}";}
			}
		}
		$message .= PHP_EOL;echo $message;
		if($path!=""){$fp = fopen($path,"a");if(flock($fp,LOCK_EX)){fwrite($fp,$message);flock($fp,LOCK_UN);}fclose($fp);}
	}
	
	public static function print($array,int $intent=1){
		$iu = 0;
		$isSubArray = false;
		$result = "";
		if($intent==1){$result .= (is_array($array)?"Array":get_class($array))."[";}
		$noArray = array();
		$yesArray = array();
		foreach($array as $key=>$value){
			if(is_array($value)){$isSubArray = true;$yesArray[$key] = $value;}
			else{$noArray[$key] = $value;}
		}
		$array = array_merge($noArray,$yesArray);
		foreach($array as $key=>$value){
			if($iu>0){$result .= ",";}
			if(is_array($value) || is_object($value)){
				if($isSubArray){$result .= PHP_EOL.str_repeat("    ",$intent);}
				$result .= is_numeric($key)?"":"\"{$key}\":";
				$result .= (is_array($value)?"":get_class($value))."[";
				$result .= Debug::print($value,$intent+1);
				$result .= "]";
			}else{
				if($isSubArray && $iu==0){$result .= PHP_EOL.str_repeat("    ",$intent);}
				$result .= is_numeric($key)?"":"\"{$key}\":";
				$result .= "\"{$value}\"";
			}
			$iu++;
		}
		if($isSubArray){$result .= PHP_EOL.str_repeat("    ",$intent-1);}
		if($intent==1){$result .= "]".PHP_EOL;echo $result;}else{return $result;}
	}
}