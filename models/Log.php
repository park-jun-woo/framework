<?php
namespace models;

use Parkjunwoo;

class Log extends Model{
	public function __construct(){
		
	}
	
	public function get(){
		
	}
	
	public function post(){
		
	}
	
	public function put(){
		
	}
	
	public function delete(){
		
	}
	
	public static function error(string $path, string $message){
		$path = Parkjunwoo::man()->path("log")."error";
		if($path==""){$path = str_replace(basename(__FILE__),"",realpath(__FILE__))."log".DIRECTORY_SEPARATOR."error";}
		self::append($path, date("Y-m-d H:i:s")."\t".$message, 1);
	}
}