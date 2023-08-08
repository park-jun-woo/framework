<?php
namespace core;

use Parkjunwoo;

class System{
	protected Parkjunwoo $man;
	protected array $entities = [
		"users"=>[
			"permission"=>["defien"=>"key"],
		],
		"requests"=>[
			"user"=>["define"=>"parent","target"=>"users"],
			"datetime"=>["define"=>"datetime","datetype"=>"solar","timezone"=>"Asia/Seoul","format"=>"Y-m-d H:i:s"],
			"uri"=>["define"=>"uri"],
		],
	];
	
	public function __construct(Parkjunwoo $man){
		$this->man = $man;
	}
}
?>