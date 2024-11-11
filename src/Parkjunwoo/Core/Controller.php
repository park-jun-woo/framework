<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Util\Image;
use Parkjunwoo\Util\Security;
use Parkjunwoo\Interface\Config;
use Parkjunwoo\Interface\FileModel;
use Parkjunwoo\Interface\ImageModel;

class Controller{
    protected Config $config;
    protected User $user;
    protected Request $request;
    protected FileModel $fileModel;
    protected ImageModel $imageModel;
    protected string $key, $view_path, $upload_path, $response;
    /**
     * 컨트롤러 생성자
     * @param Parkjunwoo $man 프레임워크 객체
     */
    protected function __construct(Config $config, User $user, Request $request){
        $this->config = $config;
        $this->user = $user;
        $this->request = $request;
        $this->key = $config->key();
        $this->view_path = $config->path()->view();
        $this->upload_path = $config->path()->upload();
    }
    /**
     * 결과 스트림
     * @return string 결과 스트림
     */
    public function response():string{return $this->response;}
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
        $path = "{$this->view_path}{$view}.php";
        if(file_exists($path)){
            ob_start();
            extract($result);
            include $path;
            $this->response = ob_get_clean();
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
        $result["message"] = $this->config->message($code, $this->request->locale());
        switch($this->request->contentType()){
            case Request::JSON:$this->response = json_encode($result);break;
            case Request::HTML:$this->response = $result["message"];break;
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
     * @return array|null 데이터베이스에 업로드한 파일 정보 배열
     */
    protected function file(string $key):?array {
        //사용자 정보에 회원 인덱스가 없으면 실패
        if(!$this->user->member()==0){return ["error"=>"User 'member' is not set."];}
        //파일 모델이 설정되어 있지 않으면 실패
        if(!isset($this->fileModel)){return ["error"=>"\$this->fileModel is not set."];}
        //파일이 업로드 되어 있지 않다면 실패
        if(!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {return ["error"=>"\$_FILES['{$key}'] is not uploaded."];}
        $datetime = date("YmdHis");
        $file = [];
        //작성자
        $file['writer'] = $this->user->member();
        //파일 이름만
        $file['name'] = pathinfo($_FILES[$key]['name'], PATHINFO_FILENAME);
        //파일 확장자
        $file['ext'] = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
        //서버에 업로드할 파일명
        $file['path'] = md5("{$this->key}{$datetime}{$file['name']}");
        //지정한 폴더에 파일 업로드
        if(!move_uploaded_file($_FILES[$key]['tmp_name'], $file_path = "{$this->upload_path}{$file['path']}"))
        {return ["error"=>"move_uploaded_file fail. name:{$file_path}"];}
        //모델을 통해 데이터베이스에 정보 등록
        $file['no'] = $this->fileModel->postFile($file['writer'], $file['name'], $file['ext'], $file['path']);
        //결과 반환
        return $file;
    }
    /**
     * 이미지 업로드
     * @param string $key 이미지 파일 업로드한 키
     * @param string $caption 이미지 설명
     * @param string $thumbnail_crop 썸네일 자르기 정보
     * @param string $small_crop 작은 이미지 자르기 정보
     * @param string $large_crop 큰 이미지 자르기 정보
     * @return array|null 데이터베이스에 업로드한 이미지 정보 배열
     */
    protected function image(string $key, string $caption="", string $thumbnail_crop="", string $small_crop="", string $large_crop=""):?array {
        //사용자 정보에 회원 인덱스가 없으면 실패
        if(!$this->user->member()==0){return ["error"=>"User 'member' is not set."];}
        //이미지 모델이 설정되어 있지 않으면 실패
        if(!isset($this->imageModel)){return ["error"=>"\$this->imageModel is not set."];}
        //업로드 시도
        $source = $this->file($key);
        //파일이 업로드 되어 있지 않다면 실패
        if(array_key_exists("error",$source)){return $source;}
        //썸네일 이미지 생성
        if($thumbnail_crop==""){Image::resize("{$this->upload_path}{$source['path']}", "{$this->upload_path}{$source['path']}2", 256);}
        //작은 이미지 생성
        if($small_crop==""){Image::resize("{$this->upload_path}{$source['path']}", "{$this->upload_path}{$source['path']}5", 512);}
        //큰 이미지 생성
        if($large_crop==""){Image::resize("{$this->upload_path}{$source['path']}", "{$this->upload_path}{$source['path']}A", 1024);}
        $image = [];
        //작성자
        $image["writer"] = $this->user->member();
        //파일 원본
        $image["source"] = $source["no"];
        //이미지 설명
        $image["caption"] = $caption;
        //썸네일 이미지 경로
        $image["thumbnail"] = "{$source['path']}2";
        //작은 이미지 경로
        $image["small"] = "{$source['path']}5";
        //큰 이미지 경로
        $image["large"] = "{$source['path']}A";
        //모델을 통해 데이터베이스에 정보 등록
        $image["no"] = $this->imageModel->postImage(
            $image["writer"], $image["source"], $image["caption"],
            $image["thumbnail"], $image["small"], $image["large"],
            $thumbnail_crop, $small_crop, $large_crop,
        );
        //결과 반환
        return $image;
    }
}
?>