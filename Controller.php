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
	public function __construct(Parkjunwoo $man){
		$this->man = $man;
		$this->user = $this->man->user();
		$this->request = $man->request();
		
		$this->information();
	}
	
	/**
	 * 지정한 컨텐트 타입에 맞춰 뷰 출력
	 * @param string $layout 레이아웃 이름
	 * @param string $view 뷰 이름
	 * @param mixed $data 뷰에 전달할 데이터
	 */
	protected function view(string $layout,string $view,$data=null){
		$html = new DOMDocument();
		$html->loadHTML("<!DOCTYPE html><html><head></head><body></body></html>");
	}
	
	/**
	 * 지정한 컨텐트 타입에 맞춰 메세지 출력
	 * @param string $message
	 */
	protected function message(string $message){
		
	}
	
	protected function information(){
		echo "<!DOCTYPE html>
<html>
	<head>
		<title>{$this->man->name()} Informations</title>
	</head>
	<body>
		<h1>{$this->man->name()} Informations</h1>
		<section>
			<h2>User Information</h2>
			<dl>
				<dt>permissions</dt><dd>{$this->user->permissionNames()}</dd>
				<dt>IP</dt><dd>{$this->user->ip()}</dd>
			</dl>
		</section>
		<section>
			<h2>Request Information</h2>
			<dl>
				<dt>URI</dt><dd>{$this->request->uri()}</dd>
				<dt>route</dt><dd>{$this->request->route()}</dd>
				<dt>method</dt><dd>{$this->request->method()}</dd>
				<dt>type</dt><dd>{$this->request->type()}</dd>
				<dt>locale</dt><dd>{$this->request->locale()}</dd>
			</dl>
		</section>
		<section>
			<h2>Route Sequences</h2>
			<p>
				".Debug::print($this->request->sequences())."
			</p>
		</section>
	</body>
</html>";
	}
}
?>