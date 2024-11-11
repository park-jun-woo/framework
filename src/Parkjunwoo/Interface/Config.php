<?php
namespace Parkjunwoo\Interface;

use Parkjunwoo\Config\Database;
use Parkjunwoo\Config\Path;
use Parkjunwoo\Config\Route;

interface Config {
    /**
     * 데이터베이스 설정 객체
     * @return Database 경로 설정 객체
     */
    public function database():Database;
    /**
     * 경로 설정 객체
     * @return Path 경로 설정 객체
     */
    public function path():Path;
    /**
     * 프로젝트 아이디, 프로젝트 이름을 CRC32 변환한 값
     * @return int 아이디
     */
    public function id():int;
    /**
     * 프로젝트 키값, 아이디를 36진수로 변환한 값
     * @return string 키값
     */
    public function key():string;
    /**
     * 프로젝트 이름
     * @return string 이름
     */
    public function projectName():string;
    /**
     * 앱 이름
     * @return string 이름
     */
    public function appName():string;
    /**
     * 서버 이름
     * @return string 이름
     */
    public function serverName():string;
    /**
     * 언어 기본값
     * @return string 언어
     */
    public function defaultLanguage():string;
    /**
     * 토큰 유효기간
     * @return int 유효기간
     */
    public function tokenExpire():int;
    /**
     * 세션 유효기간
     * @return int 유효기간
     */
    public function sessionExpire():int;
    /**
     * 권한 목록
     * @return array 권한 목록 배열
     */
    public function permissions():array;
    /**
     * 라우트 조회
     * @param int $http_method HTTP 메서드
     * @param string $key URI패턴 키
     * @return Route|null 라우트 객체
     */
    public function route(int $http_method, string $key):?Route;
    /**
     * 메세지 얻기
     * @param int $code 메세지 코드
     * @param string $locale 언어 코드
     * @return string|null 메세지
     */
    public function message(int $code, string $locale=null):?string;
    /**
     * RSA 개인키
     * @return string 개인키
     */
    public function privateKey():string;
    /**
     * RSA 공개키
     * @return string 공개키
     */
    public function publicKey():string;
}
?>