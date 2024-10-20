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
     * @param int $width 이미지 가로 크기
     * @param int $height 이미지 세로 크기
     * @param string $format 이미지 포맷
     */
    public static function resize(string $sourcePath, string $resizePath, int $width, int $height=null, string $format=""){
        try {
            // 높이가 null인 경우, width와 동일하게 설정 (정사각형)
            if ($height === null) {$height = $width;}
            // 저장할 이미지 포맷 설정
            if (empty($format)) {
                $resizeFormat = pathinfo($resizePath, PATHINFO_EXTENSION);
                $format = $resizeFormat ?: "png";  // 기본값 png
            }
            // Imagick 객체 생성
            $image = new Imagick($sourcePath);
            // 원본 이미지의 너비와 높이 가져오기
            $originalWidth = $image->getImageWidth();
            $originalHeight = $image->getImageHeight();
            // 비율에 맞춰 리사이즈할 크기 계산
            $scale = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = (int)($originalWidth * $scale);
            $newHeight = (int)($originalHeight * $scale);
            // 이미지 리사이즈
            $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            $image->setImageFormat($format);
            // 이미지 저장
            $image->writeImage($resizePath);
            // 메모리 해제
            $image->clear();
        } catch (ImagickException $e) {
            Debug::error($e->getMessage());
        }
    }
}
?>