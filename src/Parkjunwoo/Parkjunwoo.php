<?php
namespace Parkjunwoo;

use Parkjunwoo\Core\Config;
use Parkjunwoo\Core\Log;
use Parkjunwoo\Core\User;
use Parkjunwoo\Core\Request;
use Parkjunwoo\Config\Route;
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
    protected static self $instance;
    /**
     * 싱글톤 객체 얻기
     */
    public static function getInstance(...$params):self{
        if(!isset(self::$instance)){self::$instance = new self(...$params);}
        return self::$instance;
    }
    /**
     * Parkjunwoo 생성자
     * @param string $key 프로젝트 키값
     * @param string $app_name 앱 이름
     */
    protected function __construct(string $key, string $app_name){
        //설정 객체
        $config = Config::load($key, $app_name);
        //사용자 객체
        $user = new User($config);
        //요청 객체
        $request = new Request($config, $user);
        //로그 객체
        $log = new Log($config->path()->data(), $config->path()->log());
        //로그 쓰기
        $log->write($user->session(), $user->ip(), $request->referer(), $_SERVER["REQUEST_URI"]);
        //요청 분석
        $route = $request->route();
        //권한 확인
        if(!$user->permission($route[Route::PERMISSION])){http_response_code(404);exit;}
        //클래스 존재 확인
        if(!class_exists($route[Route::CLASSNAME])){http_response_code(404);exit;}
        //클래스 인스턴스 생성
        $controller = new $route[Route::CLASSNAME]($this);
        //메서드 존재 확인
        if(!method_exists($controller,$route[Route::METHODNAME])){http_response_code(404);exit;}
        //데이터베이스 커넥터 초기화
        switch($config->database()->connection()){
            case "mysql":Mysql::getInstance($config->database());break;
        }
        //클래스 메서드 실행
        $controller->{$route[Route::METHODNAME]}($request);
    }
}