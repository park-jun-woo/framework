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
		$this->man = $this->request->man();
		$this->user = $this->man->user();
		
		$this->info();
	}
	
	protected function info(){
		$html = new DOMDocument();
		$html->loadHTML(<<<HTML
<!DOCTYPE html>
<html>
	<head>
		<title>Parkjunwoo Framework Informations</title>
	</head>
	<body>
		<h1>Parkjunwoo Framework Informations</h1>
		<article>
			<h2>Request Information</h2>
			<dl>
				<dt>URI</dt><dd>{$this->request->uri()}</dd>
				<dt>route</dt><dd>{$this->request->route()}</dd>
				<dt>method</dt><dd>{$this->request->method()}</dd>
				<dt>type</dt><dd>{$this->request->type()}</dd>
				<dt>locale</dt><dd>{$this->request->locale()}</dd>
			</dl>
		</article>
		
		<article>
			<h2>User Information</h2>
			<dl>
				<dt>permissions</dt><dd>{$this->user->permissionNames()}</dd>
			</dl>
		</article>
		
		<article>
			<h2>Route Sequences</h2>
			<p>
				{Debug::print($this->request->sequences(),"\t","<br>")}
			</p>
		</article>
	</body>
</html>
HTML);
		echo $html->saveHTML();
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
}
?>