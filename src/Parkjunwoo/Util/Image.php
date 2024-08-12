<?php
namespace Parkjunwoo\Util;

use Parkjunwoo\Util\Debug;
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
            //리사이즈 이미지의 확장자를 입력한 경우
            if($format==""){
                $resizeFormat = explode(".", $resizePath);
                if(count($resizeFormat)>1){$format = $resizeFormat[1];}
                else{$format = "png";}
            }
            //Imagick 객체 생성
            $image = new Imagick($sourcePath);
            //투명 배경 설정
            $image->setImageBackgroundColor(new ImagickPixel("transparent"));
            //알파 채널 활성화
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            if($size==$height){
                //이미지 리사이즈
                $image->resizeImage($size, $height, Imagick::FILTER_LANCZOS, 1);
                //이미지 포맷 설정
                $image->setImageFormat($format);
                //이미지를 파일에 쓰기
                $image->writeImage($resizePath);
            }else{
                //원본 이미지의 가로 및 세로 길이 가져오기
                $imageWidth = $image->getImageWidth();
                $imageHeight = $image->getImageHeight();
                //원본 이미지를 타겟 크기 내에서 비율 유지하면서 축소
                $image->thumbnailImage($size, $height, true, true);
                //새로 축소된 이미지의 크기 가져오기
                $newWidth = $image->getImageWidth();
                $newHeight = $image->getImageHeight();
                //지정한 크기의 새 이미지 생성, 투명 배경 설정
                $canvas = new Imagick();
                $canvas->newImage($size, $height, new ImagickPixel('transparent'));
                $canvas->setImageFormat($format);
                //축소된 이미지를 중앙에 배치
                $canvas->compositeImage($image, Imagick::COMPOSITE_OVER, ($size-$newWidth)/2, ($height-$newHeight)/2);
                //이미지를 파일에 쓰기
                $canvas->writeImage($resizePath);
            }
        }catch(ImagickException $e) {
            Debug::error($e->getMessage());
        }
    }
}
?>