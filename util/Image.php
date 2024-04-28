<?php
namespace Parkjunwoo\Framework\Util;

use Parkjunwoo\Framework\Util\Debug;
use Imagick;
use ImagickPixel;
use ImagickException;

class Image{
    /**
     * 이미지를 리사이즈해서 지정한 포맷으로 저장합니다.
     * @param string $sourcePath 원본 이미지 경로
     * @param string $resizePath 수정한 이미지 경로
     * @param int $size 이미지 크기
     * @param string $format 이미지 포맷
     */
    public static function resize(string $sourcePath, string $resizePath, int $size, int $height=null, string $format=""){
        try {
            if($height==null){$height = $size;}
            //Imagick 객체 생성
            $image = new Imagick($sourcePath);
            //투명 배경 설정
            $image->setImageBackgroundColor(new ImagickPixel("transparent"));
            //알파 채널 활성화
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            //이미지 리사이즈
            $image->resizeImage($size, $height, Imagick::FILTER_LANCZOS, 1);
            //이미지 포맷 설정
            if($format!=""){$image->setImageFormat($format);}
            //리사이즈 이미지의 확장자를 입력한 경우
            $resizeFormat = explode(".", $resizePath);
            if(count($resizeFormat)>1){$image->setImageFormat($resizeFormat[1]);}
            //이미지를 파일에 쓰기
            $image->writeImage($resizePath);
        }catch(ImagickException $e) {
            Debug::error($e->getMessage());
        }
    }
}
?>