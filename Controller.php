<?php
class Controller{
	protected Parkjunwoo $man;
	protected User $user;
	/**
	 * 컨트롤러 실행
	 * @param array $controller 실행할 컨트롤러
	 */
	public function __construct(Parkjunwoo $man, array $controller){
		$this->man = $man;
		$this->user = $this->man->user();
		
		echo "URI: ".$this->man->uri()."<br>";
		echo "route: ".$this->man->route()."<br>";
		echo "method: ".$this->man->method()."<br>";
		echo "type: ".$this->man->type()."<br>";
		echo "locale: ".$this->man->locale()."<br><br>";
		
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