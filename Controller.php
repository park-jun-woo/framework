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
		$html->loadHTML(str_replace(
			["{name}","{uri}","{route}","{method}","{type}","{locale}","{permissionNames}","{sequences}"],
			[
				$this->man->name(),
				$this->request->uri(),
				$this->request->route(),
				$this->request->method(),
				$this->request->type(),
				$this->request->locale(),
				$this->user->permissionNames(),
				Debug::print($this->request->sequences(),"\t","<br>")
			], <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<title>{name} Informations</title>
	</head>
	<body>
		<h1>{name} Informations</h1>
		<section>
			<h2>Request Information</h2>
			<dl>
				<dt>URI</dt><dd>{uri}</dd>
				<dt>route</dt><dd>{route}</dd>
				<dt>method</dt><dd>{method}</dd>
				<dt>type</dt><dd>{type}</dd>
				<dt>locale</dt><dd>{locale}</dd>
			</dl>
		</section>
		
		<section>
			<h2>User Information</h2>
			<dl>
				<dt>permissions</dt><dd>{permissionNames}</dd>
			</dl>
		</section>
		
		<section>
			<h2>Route Sequences</h2>
			<p>
				{sequences}
			</p>
		</section>
	</body>
</html>
HTML));
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