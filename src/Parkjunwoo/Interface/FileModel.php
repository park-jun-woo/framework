<?php
namespace Parkjunwoo\Interface;

use Parkjunwoo\Parkjunwoo;

interface FileModel {
    /**
     * 모델 생성자
     * @param Parkjunwoo $man 박준우 프레임워크 메인 객체
     */
    public function __construct(Parkjunwoo $man);
    /**
     * 업로드한 파일 데이터베이스에 등록
     * @param int $writer 작성자 인덱스
     * @param string $name 파일명
     * @param string $ext 파일 확장자
     * @param string $path 파일 저장한 경로
     * @return int|null 등록한 인덱스
     */
    public function postFile(int $writer, string $name, string $ext, string $path):?int;
    /**
     * 업로드한 파일 정보 수정
     * @param int $file 파일 인덱스
     * @param int $writer 작성자 인덱스
     * @param string $name 파일명
     * @param string $ext 파일 확장자
     * @param string $path 파일 저장한 경로
     * @return bool|null 수정 여부
     */
    public function putFile(int $file, int $writer, string $name, string $ext, string $path):?bool;
}
?>