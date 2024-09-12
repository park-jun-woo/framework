<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Util\Debug;
use Parkjunwoo\Util\Image;
use Parkjunwoo\Util\Security;

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
     * 뷰 출력
     * @param string $view 뷰 이름
     * @param mixed $result 뷰에 전달할 데이터
     */
    protected function view(string $view, array $result=[]){
        $path = $this->man->path("view")."{$view}.php";
        if(file_exists($path)){
            include $path;
        }
    }
    /**
     * 리데이렉트
     * @param string $uri 주소
     */
    protected function redirect(string $uri){
        header("Location: $uri");
    }
    /**
     * 지정한 컨텐트 타입에 맞춰 메세지 출력
     * @param int $code
     * @param array $result 전달할 데이터
     */
    protected function message(int $code, array $result=[]){
        $result["code"] = $code;
        $result["message"] = $this->man->message($code);
        switch($this->request->type()){
            case Parkjunwoo::JSON:echo json_encode($result);break;
            case Parkjunwoo::HTML:
                
                break;
        }
    }
}
?>