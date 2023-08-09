<?php
namespace util;

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
	/**
	 * 에러 메세지 출력 후 종료
	 * @param string $message 에러 메세지
	 */
	public static function error($message){echo $message.PHP_EOL;exit;}
	/**
	 * 배열을 출력합니다.
	 * @param array $array 배열
	 * @param string $indent 띄어쓰기
	 * @param int $icount 띄어쓰기 카운트
	 * @return string 결과 문자열
	 */
	public static function print($array, string $indent="\t", string $eol=PHP_EOL, int $icount=1):string{
		$result = "";$isSubArray = false;$isStringKey = false;$isOrderedKey = true;
		if($icount==1){$result .= (is_array($array)?"":get_class($array))."[";}
		$sortedArray = array();
		$arrayCount = count($array);
		foreach($array as $key=>$value){
			if(!is_array($value)){$sortedArray[$key] = $value;}
			if(is_string($key)){$isStringKey = true;$isOrderedKey = false;}
		}
		$iu = 0;
		foreach($array as $key=>$value){
			if(is_array($value)){$isSubArray = true;$sortedArray[$key] = $value;}
			if($isOrderedKey && (int)$key!=$iu){$isOrderedKey = false;}
			$iu++;
		}
		$iu = 0;
		foreach($sortedArray as $key=>$value){
			if($iu>0){$result .= ",";}
			if(is_array($value) || is_object($value)){
				if($isSubArray){$result .= $eol.str_repeat($indent,$icount);}
				$result .= $isStringKey?"\"{$key}\"=>":($isOrderedKey?"":"{$key}=>");
				$result .= is_array($value)?"":get_class($value);
				$result .= "[";
				$result .= self::print($value, $indent, $eol, $icount+1);
				$result .= "]";
			}else{
				if(($isSubArray && $iu==0) || $arrayCount>4){$result .= $eol.str_repeat($indent,$icount);}
				$result .= $isStringKey?"\"{$key}\"=>":($isOrderedKey?"":"{$key}=>");
				$result .= is_numeric($value)?$value:"\"{$value}\"";
			}
			$iu++;
		}
		if($isSubArray || $arrayCount>4){$result .= $eol.str_repeat($indent,$icount-1);}
		if($icount==1){$result .= "]";return $result;}else{return $result;}
	}
}