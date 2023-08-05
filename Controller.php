<?php
use utils\Debug;

class Controller{
	protected Parkjunwoo $man;
	protected Request $request;
	protected User $user;
	/**
	 * 컨트롤러 실행
	 * @param Request $request 분석한 요청 객체
	 */
	public function __construct(Request $request){
		$this->request = $request;
		$this->user = $this->request->user();
		$this->man = $this->request->man();
		
		$this->info();
		
	}
	
	protected function info(){
		echo "<article>";
		echo "	<h1>Request Information</h1>";
		echo "	<dl>";
		echo "		<dt>URI:</dt><dd>".$this->request->uri()."</dd>";
		echo "		<dt>route:</dt><dd>".$this->request->route()."</dd>";
		echo "		<dt>method:</dt><dd>".$this->request->method()."</dd>";
		echo "		<dt>type:</dt><dd>".$this->request->type()."</dd>";
		echo "		<dt>locale:</dt><dd>".$this->request->locale()."</dd>";
		echo "	</dl>";
		echo "</article>";
		
		echo "<article>";
		echo "	<h1>User Information</h1>";
		echo "	<dl>";
		echo "		<dt>permissions:</dt><dd>".$this->user->permissions()."</dd>";
		echo "	</dl>";
		echo "</article>";
		
		echo "<article>";
		echo "	<h1>Route Sequences</h1>";
		echo "	<p>";
		echo Debug::print($this->request->sequences(),"\t","<br>");
		echo "	</p>";
		echo "</article>";
	}
	
	/**
	 * 지정한 컨텐트 타입에 맞춰 뷰 출력
	 * @param string $layout 레이아웃 이름
	 * @param string $view 뷰 이름
	 * @param mixed $data 뷰에 전달할 데이터
	 */
	protected static function view(string $layout,string $view,$data=null){
		
		
	}
	
	/**
	 * 지정한 컨텐트 타입에 맞춰 메세지 출력
	 * @param string $message
	 */
	protected static function message(string $message){
		
	}
}
?>