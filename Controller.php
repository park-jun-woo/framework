<?php
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
		
		echo "URI: ".$this->request->uri()."<br>";
		echo "route: ".$this->request->route()."<br>";
		echo "method: ".$this->request->method()."<br>";
		echo "type: ".$this->request->type()."<br>";
		echo "locale: ".$this->request->locale()."<br><br>";
		
		echo "permissions: ".$this->user->permissions()."<br>";
		
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