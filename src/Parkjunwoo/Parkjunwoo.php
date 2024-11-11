<?php
namespace Parkjunwoo;

use Parkjunwoo\Core\Config;
use Parkjunwoo\Core\Log;
use Parkjunwoo\Core\User;
use Parkjunwoo\Core\Request;
use Parkjunwoo\Core\Controller;
use Parkjunwoo\Connector\Mysql;
use Parkjunwoo\Interface\Singleton;

/**
 * The Parkjunwoo framework is a web application framework with a concise and powerful syntax.
 * PHP Version 8.0
 * @name Parkjunwoo Parkjunwoo Version 1.0 zeolite
 * @package Parkjunwoo
 * @see https://github.com/park-jun-woo/parkjunwoo The Parkjunwoo GitHub project
 * @author Park Jun woo <mail@parkjunwoo.com>
 * @copyright 2023 parkJunwoo.com
 * @license https://opensource.org/license/bsd-2-clause/ The BSD 2-Clause License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class Parkjunwoo implements Singleton{
    /** @var self 싱글톤 객체 */
    protected static self $instance;
    /**
     * 싱글톤 객체 얻기
     * @param self 싱글톤 객체 
     */
    public static function getInstance(...$params):self{
        //처음이라면, 싱글톤 객체 초기화
        if(!isset(self::$instance)){self::$instance = new self(...$params);}
        //싱글톤 객체 반환
        return self::$instance;
    }
    /**
     * Parkjunwoo 생성자
     * @param string $key 프로젝트 키값
     * @param string $app_name 앱 이름
     */
    protected function __construct(string $key, string $app_name){
        //블랙에 등록한 IP라면, 접속 차단
        if(apcu_exists("{$key}@b{$_SERVER['REMOTE_ADDR']}")){http_response_code(404);exit;}
        //APCU 메모리에 설정값이 있다면, 설정값 가져오기
        if(apcu_exists($config_apcu="{$key}@{$app_name}")){
            /** @var Config $config 설정 객체 */
            $config = apcu_fetch($config_apcu);
        //없다면, 설정 객체 생성하여 APCU 메모리에 설정 저장
        }else{apcu_store($config_apcu, $config = new Config($app_name));}
        //사용자 객체
        $user = new User($config);
        //요청 객체
        $request = new Request($config, $user);
        //로그 객체
        $log = new Log($config, $user, $request);
        //라우트 객체 가져오기
        $route = $request->route();
        //캐시 키 가져오기
        $cache_key = $request->cacheKey();
        //권한 확인
        if(!$user->permission($route->permission())){http_response_code(404);exit;}
        $http_method = $route->httpMethod();
        //GET 라우트면서 캐시가 있다면
        if($http_method==Request::GET && apcu_exists($apcu_cache_key = "{$key}@{$cache_key}")){
            $response = apcu_fetch($apcu_cache_key);
        }else{
            //클래스 존재 확인
            if(!class_exists($class_name = $route->class())){http_response_code(404);exit;}
            /** @var Controller 클래스 인스턴스 */
            $controller = new $class_name($this);
            //메서드 존재 확인
            if(!method_exists($controller,$method_name = $route->method())){http_response_code(404);exit;}
            //데이터베이스 커넥터 초기화
            switch($config->database()->connection()){
                //MySQL 초기화
                case "mysql":Mysql::getInstance($config->database());break;
            }
            //클래스 메서드 실행
            $controller->{$method_name}($request);
            //반환 문자열
            $response = $controller->response();
            //GET 메서드인 경우
            if($http_method==Request::GET){
                apcu_store($apcu_cache_key, $response, $config->cacheExpire());
            }
        }
        //반환 문자열 출력
        echo $response;
    }
}