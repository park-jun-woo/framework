<?php
namespace Parkjunwoo\Model;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Interface\Singleton;
use Parkjunwoo\Util\File;

class Logger implements Singleton{
    protected static Logger $instance;
    public static function getInstance(...$params):self{
        if(!isset(self::$instance)){self::$instance = new self(...$params);}
        return self::$instance;
    }
    protected Parkjunwoo $man;
    /**
     * Logger 생성자
     * DB 연결
     * @param Parkjunwoo $man 프레임워크 객체
     */
    public function __construct(Parkjunwoo $man){
        $this->man = $man;
    }
}