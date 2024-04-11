<?php
namespace model;

use Parkjunwoo;
use core\User;
use core\Model;

/**
 * Database는 웹 어플리케이션에서 별도의 데이터베이스 없이 파일 기반으로 직접 데이터베이스를 구축합니다. 외부 데이터베이스와 연동하는 것도 가능합니다.
 * PHP Version 8.0
 * @name Database Version 1.0
 * @package Parkjunwoo
 * @see https://github.com/park-jun-woo/framework The Parkjunwoo GitHub project
 * @author Park Jun woo <mail@parkjunwoo.com>
 * @copyright 2023 parkJunwoo.com
 * @license https://opensource.org/license/bsd-2-clause/ The BSD 2-Clause License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class Database{
    protected static Parkjunwoo $man;
    
    public static function init(Parkjunwoo $man){
        self::$man = $man;
    }
    
    public static function table(string $entity):Database{
        
        return new Database($entity);
    }
    
    protected string $entity;
    protected int $key;
    protected array $where;
    protected array $data;
    protected array $paginate;
    
    public function __construct(string $entity){
        
    }
    
    public function where(array $where):Database{
        
        return $this;
    }
    
    public function data(array $data):Database{
        
        return $this;
    }
    
    public function paginate():Database{
        
        return $this;
    }
    
    public function get():array{
        
    }
    
    public function post():int{
        
    }
    
    public function put():bool{
        
    }
    
    public function delete():bool{
        
    }
    
    protected function pack(array &$attirute, $value) {
        switch($attirute["define"]){
            case "key":
            case "permission":
                return pack("J", $value);
            case "datetime":
                if(is_numeric($value)){return pack("J", $value);}
                else if(is_string($value)){return pack("J", strtotime($value));}
                return;
            case "ip":
                return pack("N", ip2long($value));
            case "text":
        }
        
    }
    
    protected function error(string $path, string $message){
        $path = $this->man->path("log")."error";
        if($path==""){$path = str_replace(basename(__FILE__),"",realpath(__FILE__))."log".DIRECTORY_SEPARATOR."error";}
        self::append($path, date("Y-m-d H:i:s")."\t".$message, 1);
    }
}