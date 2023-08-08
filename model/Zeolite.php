<?php
namespace model;

use Parkjunwoo;
use core\User;
use core\Model;

class Zeolite extends Model{
	protected Parkjunwoo $man;
	protected User $user;
	
	public function __construct(Parkjunwoo $man){
		$this->man = $man;
		$this->user = $this->man->user();
	}
	
	public function get(array $query){
		
	}
	
	public function post(){
		
	}
	
	public function put(){
		
	}
	
	public function delete(){
		
	}
	
	protected function error(string $path, string $message){
		$path = $this->man->path("log")."error";
		if($path==""){$path = str_replace(basename(__FILE__),"",realpath(__FILE__))."log".DIRECTORY_SEPARATOR."error";}
		self::append($path, date("Y-m-d H:i:s")."\t".$message, 1);
	}
}