<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Util\Image;
use Parkjunwoo\Util\Security;
use Parkjunwoo\Interface\FileModel;
use Parkjunwoo\Interface\ImageModel;

class Controller{
    protected Parkjunwoo $man;
    protected User $user;
    protected Request $request;
    protected FileModel $fileModel;
    protected ImageModel $imageModel;
    /**
     * 컨트롤러 실행
     * @param Parkjunwoo $man 프레임워크 객체
     */
    public function __construct(Parkjunwoo $man){
        $this->man = $man;
        $this->user = $this->man->user();
        $this->request = $man->request();
    }
    /**
     * 라우터가 존재하지 않거나 오류가 났을 때, 출력할 404
     */
    public function getNotFound():void {
        header("HTTP/1.1 404 Not Found");
        if($this->request->uri()!="/"){$this->redirect("/");}
    }
    /**
     * 뷰 출력
     * @param string $view 뷰 이름
     * @param mixed $result 뷰에 전달할 데이터
     */
    protected function view(string $view, array $result=[]):void {
        $this->user->save();
        $path = $this->man->path("view")."{$view}.php";
        if(file_exists($path)){
            extract($result);
            include $path;
        }
    }
    /**
     * 리데이렉트
     * @param string $uri 주소
     */
    protected function redirect(string $uri):void {
        $this->user->save();
        header("Location: $uri");
    }
    /**
     * 지정한 컨텐트 타입에 맞춰 메세지 출력
     * @param int $code
     * @param array $result 전달할 데이터
     */
    protected function message(int $code, array $result=[]):void {
        $this->user->save();
        $result["code"] = $code;
        $result["message"] = $this->man->message($code);
        switch($this->request->type()){
            case Parkjunwoo::JSON:echo json_encode($result);break;
            case Parkjunwoo::HTML:echo $result["message"];break;
        }
    }
    /**
     * XSS 공격 필터링 for articles
     * @param string $html 필터링할 입력 값
     * @return string 필터링한 HTML 문자열
     */
    protected function purify(string $html):string {
        return Security::purifyArticle($html);
    }
    /**
     * 파일 업로드
     * @param string $key 파일 업로드한 키
     * @return int|null 데이터베이스에 업로드한 파일 번호
     */
    protected function file(string $key):?int {
        if(!isset($this->fileModel)){return null;}
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {return null;}

        
    }
    /**
     * 이미지 업로드
     * @param string $key 파일 업로드한 키
     * @return int|null 데이터베이스에 업로드한 이미지 번호
     */
    protected function image(string $key):?int {
        if(!isset($this->imageModel)){return null;}
        $this->file($key);
        
        return null;
    }
}
?>