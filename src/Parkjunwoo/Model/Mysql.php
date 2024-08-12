<?php
namespace Parkjunwoo\Model;

use Parkjunwoo\Parkjunwoo;
use Parkjunwoo\Interface\Singleton;
use Parkjunwoo\Interface\Model;

/**
 * Mysql은 웹 어플리케이션에서 외부 Mysql 데이터베이스에 접근하는 클래스입니다.
 * PHP Version 8.0
 * @name Mysql Version 1.0
 * @package Parkjunwoo
 * @see https://github.com/park-jun-woo/parkjunwoo The Parkjunwoo GitHub project
 * @author Park Jun woo <mail@parkjunwoo.com>
 * @copyright 2023 parkJunwoo.com
 * @license https://opensource.org/license/bsd-2-clause/ The BSD 2-Clause License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class Mysql implements Singleton, Model{
    protected static Mysql $instance;
    public static function getInstance(...$params):self{
        if(!isset(self::$instance)){self::$instance = new self(...$params);}
        return self::$instance;
    }

    protected Parkjunwoo $man;
    protected mysqli $connection;
    /**
     * Mysql 생성자
     * DB 연결
     * @param array $app 실행할 어플리케이션 코드 배열
     */
    public function __construct(Parkjunwoo $man){
        $this->man = $man;
        $host = $this->man->database("host");
        $username = $this->man->database("username");
        $password = $this->man->database("password");
        $database = $this->man->database("database");
        $this->connection = new mysqli($host, $username, $password, $database);
        if($this->connection->connect_error){
            die('Connection failed: '.$this->connection->connect_error);
        }
    }
    /**
     * Mysql 파괴자
     * DB 연결 해제
     */
    public function __destruct(){
        if (isset($this->connection)){
            $this->connection->close();
            $this->connection = null;
        }
    }
    /**
     * 쿼리 실행
     *
     * @param string $query 쿼리문
     * @return mixed 결과
     */
    public function query(string $query){
        return $this->connection->query($query);
    }
    /**
     * 쿼리 결과 배열로 반환
     *
     * @param mixed $result 쿼리결과
     * @return array|null 결과 배열
     */
    public function fetch($result):?array{
        return $result->fetch_assoc();
    }
    /**
     * 잠금을 실행합니다.
     * 
     * @return mixed 잠금 실행 성공 여부를 반환합니다.
     */
    public function lock(string $tableName):bool{
        return $mysqli->query("LOCK TABLES {$tableName} WRITE");
    }
    /**
     * 잠금을 해제합니다.
     * 
     * @return mixed 잠금 해제 성공 여부를 반환합니다.
     */
    public function unlock():bool{
        return $mysqli->query("UNLOCK TABLES");
    }
    /**
     * 트랜잭션을 실행합니다.
     * 
     * @return boolean 트랜잭션 실행 성공 여부를 반환합니다.
     */
    public function beginTransaction():bool{
        return $this->connection->begin_transaction();
    }
    /**
     * 트랜잭션을 커밋합니다.
     * 
     * @return boolean 트랜잭션 커밋 성공 여부를 반환합니다.
     */
    public function commit():bool{
        return $this->connection->commit();
    }
    /**
     * 트랜잭션을 롤백합니다.
     * 
     * @return boolean 트랜잭션 롤백 성공 여부를 반환합니다.
     */
    public function rollback():bool{
        return $this->connection->commit();
    }
}