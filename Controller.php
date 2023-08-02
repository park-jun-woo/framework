<?php
class Controller{
	protected Request $request;
	protected User $user;
	/**
	 * 컨트롤러 실행
	 * @param array $controller 실행할 컨트롤러
	 */
	public function __construct(Request $request, array $controller){
		$this->request = $request;
		$this->user = $this->request->user();
		
		echo "URI: ".$this->request->uri()."<br>";
		echo "route: ".$this->request->route()."<br>";
		echo "method: ".$this->request->method()."<br>";
		echo "type: ".$this->request->type()."<br>";
		echo "locale: ".$this->request->locale()."<br><br>";
		
		echo "permissions: ".$this->user->permissions()."<br>";
		
	}
	
	/**
	 * 지정한 사용자만 허용
	 * @param string $allows 허용할 유저 목록
	 * @param string $message 오류시 메세지
	 */
	protected static function user(string $allows,string $message){
		
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