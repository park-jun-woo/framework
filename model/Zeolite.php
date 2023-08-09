<?php
namespace model;

use Parkjunwoo;
use core\User;
use core\Model;

/**
 * Zeolite는 웹 어플리케이션에서 별도의 데이터베이스 없이 파일 기반으로 직접 데이터베이스를 구축합니다.
 * PHP Version 8.0
 * @name Zeolite Version 1.0
 * @package Parkjunwoo
 * @see https://github.com/park-jun-woo/framework The Parkjunwoo GitHub project
 * @author Park Jun woo <mail@parkjunwoo.com>
 * @copyright 2023 parkJunwoo.com
 * @license https://opensource.org/license/bsd-2-clause/ The BSD 2-Clause License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class Zeolite extends Model{
	protected Parkjunwoo $man;
	protected User $user;
	
	public function __construct(Parkjunwoo $man){
		$this->man = $man;
		$this->user = $this->man->user();
	}
	
	public function options():array{return ["get","post","put","delete"];}
	
	public function get(array $entity, array $where){
		
	}
	
	public function post(array $entity, array $data){
		
	}
	
	public function put(array $entity, array $data, array $where){
		
	}
	
	public function delete(array $entity, array $where){
		
	}
	
	protected static function pack(array &$attirute, $value) {
		switch($attirute["define"]){
			case "key":
			case "permission":
				return pack("J", $value);
			case "datetime":
				if(is_numeric($value)){return pack("J", $value);}
				else if(is_string($value)){return pack("J", strtotime($value));}
				return;
			case "ip":
				return pack("J", ip2long($value));
			case "text":
		}
		
	}
	
	protected function error(string $path, string $message){
		$path = $this->man->path("log")."error";
		if($path==""){$path = str_replace(basename(__FILE__),"",realpath(__FILE__))."log".DIRECTORY_SEPARATOR."error";}
		self::append($path, date("Y-m-d H:i:s")."\t".$message, 1);
	}
}