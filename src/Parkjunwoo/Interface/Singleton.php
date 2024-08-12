<?php
namespace Parkjunwoo\Interface;

interface Singleton {
    /**
     * 인스턴스를 반환합니다.
     *
     * @return self 인스턴스
     */
    public static function getInstance(...$params):self;
}
?>