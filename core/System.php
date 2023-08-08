<?php
namespace core;

use Parkjunwoo;

class System{
	protected Parkjunwoo $man;
	protected array $entities = [
		"users"=>[
			"key"=>["define"=>"key"],
			"permission"=>["define"=>"permission"],
		],
		"tokens"=>[
			"permission"=>["define"=>"permission"],
			"ip"=>["define"=>"ip"],
			"referer"=>["define"=>"text","length"=>"TEXT"],
			"agent"=>["define"=>"text","length"=>"TINYTEXT"],
			"language"=>["define"=>"text","length"=>"TINYTEXT"],
			"time"=>["define"=>"datetime","datetype"=>"solar","timezone"=>"Asia/Seoul"],
		],
		"sessions"=>[
			"permission"=>["define"=>"permission"],
			"id"=>["define"=>"key"],
			"time"=>["define"=>"datetime","datetype"=>"solar","timezone"=>"Asia/Seoul"],
			"server"=>["define"=>"ip"],
			"app"=>["define"=>"text","length"=>32],
		],
		"requests"=>[
			"user"=>["define"=>"parent","target"=>"users"],
			"datetime"=>["define"=>"datetime","datetype"=>"solar","timezone"=>"Asia/Seoul"],
			"uri"=>["define"=>"uri"],
		],
	];
	
	public function __construct(Parkjunwoo $man){
		$this->man = $man;
	}
}
?>