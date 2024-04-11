<?php
namespace core;

use Parkjunwoo;
use util\Debug;

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
            route{display: block;}
            route::before{content: \"route: \";}
            method{display:block;}
            method::before{content: \"method: \";}
            type{display: block;}
            type::before{content: \"type: \";}
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
            <route>{$this->request->route()}</route>
            <method>{$this->request->method()}</method>
            <type>{$this->request->type()}</type>
            <locale>{$this->request->locale()}</locale>
        </section>
        <section>
            <h2>Route</h2>
            <p>".Debug::print($this->request->sequences())."</p>
        </section>
    </body>
</html>";
    }
}
?>