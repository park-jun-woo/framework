<?php
namespace Parkjunwoo\Interface;

use Parkjunwoo\Parkjunwoo;

interface ImageModel {
    /**
     * 모델 생성자
     * @param Parkjunwoo $man 박준우 프레임워크 메인 객체
     */
    public function __construct(Parkjunwoo $man);
    /**
     * 업로드한 이미지 데이터베이스에 등록
     * @param int $writer 작성자 인덱스
     * @param int $source 원본 파일 인덱스
     * @param string $thumbnail 썸네일 이미지 파일 경로
     * @param string $small 작은 이미지 파일 경로
     * @param string $large 큰 이미지 파일 경로
     * @param string $thumbnail_crop 썸네일 이미지 자르기 정보
     * @param string $small_crop 작은 이미지 자르기 정보
     * @param string $large_crop 큰 이미지 자르기 정보
     * @return int|null 등록한 인덱스
     */
    public function postImage(int $writer, int $source, string $caption,string $thumbnail, string $small, string $large,string $thumbnail_crop, string $small_crop, string $large_crop):?int;
    /**
     * 업로드한 이미지 데이터베이스에 등록
     * @param int $image 이미지 인덱스
     * @param int $writer 작성자 인덱스
     * @param int $source 원본 파일 인덱스
     * @param string $thumbnail 썸네일 이미지 파일 경로
     * @param string $small 작은 이미지 파일 경로
     * @param string $large 큰 이미지 파일 경로
     * @param string $thumbnail_crop 썸네일 이미지 자르기 정보
     * @param string $small_crop 작은 이미지 자르기 정보
     * @param string $large_crop 큰 이미지 자르기 정보
     * @return bool|null 수정 여부
     */
    public function putImage(int $image, int $writer, int $source, string $caption,string $thumbnail, string $small, string $large,string $thumbnail_crop, string $small_crop, string $large_crop):?bool;
}
?>