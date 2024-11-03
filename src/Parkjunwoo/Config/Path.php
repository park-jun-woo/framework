<?php
namespace Parkjunwoo\Config;
/**
 * Path 클래스
 *
 * 이 클래스는 애플리케이션에서 사용되는 여러 경로를 관리합니다.
 * 각 경로는 생성자 매개변수로 전달되며, 설정된 경로에 접근할 수 있는 
 * 게터(getter) 메서드를 제공합니다.
 */
class Path{
    // 경로 정보들: 루트, HTTP, 뷰, 캐시, 데이터, 로그, 업로드
    protected string $root, $http, $view, $cache, $data, $log, $upload;
    /**
     * 경로 설정 생성자
     * @param array $config 설정 배열
     */
    public function __construct(string $root, string $http, string $view, string $cache, string $data, string $log, string $upload){
        $this->root = $root;
        $this->http = $http;
        $this->view = $view;
        $this->cache = $cache;
        $this->data = $data;
        $this->log = $log;
        $this->upload = $upload;
    }
    /**
     * 루트 경로 반환
     *
     * @return string 루트 경로
     */
    public function root(): string {return $this->root;}
    /**
     * HTTP 경로 반환
     *
     * @return string HTTP 경로
     */
    public function http(): string {return $this->http;}
    /**
     * 뷰 경로 반환
     *
     * @return string 뷰 경로
     */
    public function view(): string {return $this->view;}
    /**
     * 캐시 파일 경로 반환
     *
     * @return string 캐시 파일 경로
     */
    public function cache(): string {return $this->cache;}
    /**
     * 데이터 파일 경로 반환
     *
     * @return string 데이터 파일 경로
     */
    public function data(): string {return $this->data;}
    /**
     * 로그 파일 경로 반환
     *
     * @return string 로그 파일 경로
     */
    public function log(): string {return $this->log;}
    /**
     * 파일 업로드 경로 반환
     *
     * @return string 파일 업로드 경로
     */
    public function upload(): string {return $this->upload;}
}

?>