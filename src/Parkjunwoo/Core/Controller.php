<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Util\Debug;
use Parkjunwoo\Util\Image;

class Controller{
    protected Parkjunwoo $man;
    protected User $user;
    protected Request $request;
    /**
     * 컨트롤러 실행
     * @param Parkjunwoo $man 프레임워크 객체
     */
    public function __construct(Parkjunwoo $man){
        $this->man = $man;
        $this->user = $this->man->user();
        $this->request = $man->request();
    }

    public function getNotFound(){
        header("HTTP/1.1 404 Not Found");
        if($this->request->uri()!="/"){$this->redirect("/");}
    }
    /**
     * 지정한 컨텐트 타입에 맞춰 뷰 출력
     * @param string $layout 레이아웃 이름
     * @param string $view 뷰 이름
     * @param mixed $data 뷰에 전달할 데이터
     */
    protected function view(string $view, array $result=[]){
        $path = $this->man->app()["view"]."{$view}.html";
        if(file_exists($path)){
            include $path;
        }
    }
    /**
     * 리데이렉트
     * @param string $uri 주소
     */
    protected function redirect(string $uri){
        header("Location: ".$uri);
    }
    /**
     * 지정한 컨텐트 타입에 맞춰 메세지 출력
     * @param string $message
     */
    protected function message(string $message){
        
    }
    /**
     * 페이지 정보 출력
     */
    protected function info(){
        echo "<!DOCTYPE html>
<html>
<head>
<title>{$this->man->app()["title"]}</title>
<meta charset=\"utf-8\"/>
<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"/>
<meta name=\"description\" content=\"{$this->man->app()["description"]}\"/>
<style>
::before{font-weight: bold;}
permissions{display: block;}
permissions::before{content: \"permissions: \";}
ip{display: block;}
ip::before{content: \"ip: \";}
referer{display: block;}
referer::before{content: \"referer: \";}
agent{display: block;}
agent::before{content: \"agent: \";}
uri{display: block;}
uri::before{content: \"uri: \";}
routeKey{display: block;}
routeKey::before{content: \"route-key: \";}
method{display:block;}
method::before{content: \"method: \";}
contentType{display: block;}
contentType::before{content: \"content-type: \";}
locale{display: block;}
locale::before{content: \"locale: \";}
</style>
</head>
<body>
<h1>{$this->man->app()["title"]} Informations</h1>
<section>
<h2>User</h2>
<permissions>{$this->user->permissionNames()}</permissions>
<ip>{$this->user->ip()}</ip>
<referer>{$this->user->get("first-referer")}</referer>
<agent>{$this->user->get("user-agent")}</agent>
</section>
<section>
<h2>Request</h2>
<uri>{$this->request->uri()}</uri>
<routeKey>{$this->request->routeKey()}</routeKey>
<contentType>".["HTML","JSON"][$this->request->type()]."</contentType>
<method>".["GET","POST","PUT","\DELETE"][$this->request->method()]."</method>
<locale>{$this->request->locale()}</locale>
</section>
<section>
<h2>Route</h2>
<p>".Debug::print($this->request->route())."</p>
</section>
</body>
</html>";
    }
}
?>