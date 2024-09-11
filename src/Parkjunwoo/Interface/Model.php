<?php
namespace Parkjunwoo\Interface;

use Parkjunwoo\Parkjunwoo;

interface Model{
    /**
     * Model 생성자
     * DB 연결
     * @param array $app 실행할 어플리케이션 코드 배열
     */
    public function __construct(Parkjunwoo $man);
    /**
     * Model 파괴자
     * DB 연결 해제
     */
    public function __destruct();
    /**
     * 조회 쿼리 실행
     *
     * @param string $query 쿼리문
     * @return mixed 결과
     */
    public function query(string $query);
    /**
     * 입력 결과 키값
     *
     * @param string $query 쿼리문
     * @return int 결과 키값
     */
    public function insertId():int;
    /**
     * 쿼리 결과 배열로 반환
     *
     * @param mixed $result 쿼리결과
     * @return boolean array|null 결과 배열
     */
    public function fetch($result):?array;
    /**
     * 잠금을 실행합니다.
     * 
     * @return mixed 잠금 실행 성공 여부를 반환합니다.
     */
    public function lock(string $tableName):bool;
    /**
     * 잠금을 해제합니다.
     * 
     * @return mixed 잠금 해제 성공 여부를 반환합니다.
     */
    public function unlock():bool;
    /**
     * 트랜잭션을 실행합니다.
     * 
     * @return mixed 트랜잭션 실행 성공 여부를 반환합니다.
     */
    public function beginTransaction():bool;
    /**
     * 트랜잭션을 커밋합니다.
     * 
     * @return bool 트랜잭션 커밋 성공 여부를 반환합니다.
     */
    public function commit():bool;
    /**
     * 트랜잭션을 롤백합니다.
     * 
     * @return bool 트랜잭션 롤백 성공 여부를 반환합니다.
     */
    public function rollback():bool;
}