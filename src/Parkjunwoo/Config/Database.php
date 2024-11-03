<?php
namespace Parkjunwoo\Config;
/**
 * Database 클래스
 * 
 * 이 클래스는 데이터베이스 설정을 관리합니다.
 * 데이터베이스 연결에 필요한 설정 정보를 생성자 매개변수로 받아 저장하며,
 * 설정된 각 속성에 접근할 수 있는 게터(getter) 메서드를 제공합니다.
 */
class Database{
    /** @var string 데이터베이스 연결 방식 */
    protected string $connection;
    /** @var string 데이터베이스 호스트 주소 */
    protected string $host;
    /** @var int 데이터베이스 접속 포트 번호 */
    protected int $port;
    /** @var string 데이터베이스 이름 */
    protected string $database;
    /** @var string 데이터베이스 사용자 이름 */
    protected string $username;
    /** @var string 데이터베이스 비밀번호 */
    protected string $password;
    /**
     * 데이터베이스 설정 생성자
     * 
     * 데이터베이스 연결에 필요한 설정 정보를 초기화합니다.
     *
     * @param string $connection 데이터베이스 연결 방식 (예: mysql, pgsql 등)
     * @param string $host 데이터베이스 호스트 주소 (예: localhost)
     * @param int $port 데이터베이스 접속 포트 (예: 3306)
     * @param string $database 데이터베이스 이름
     * @param string $username 데이터베이스 사용자 이름
     * @param string $password 데이터베이스 비밀번호
     */
    public function __construct(string $connection, string $host, int $port, string $database, string $username, string $password){
        $this->connection = $connection;
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }
    /**
     * 데이터베이스 연결 방식을 반환합니다.
     *
     * @return string 데이터베이스 연결 방식 (예: mysql, pgsql 등)
     */
    public function connection(): string {return $this->connection;}
    /**
     * 데이터베이스 호스트 주소를 반환합니다.
     *
     * @return string 호스트 주소 (예: localhost)
     */
    public function host(): string {return $this->host;}
    /**
     * 데이터베이스 접속 포트 번호를 반환합니다.
     *
     * @return int 포트 번호 (예: 3306)
     */
    public function port(): int {return $this->port;}
    /**
     * 데이터베이스 이름을 반환합니다.
     *
     * @return string 데이터베이스 이름
     */
    public function database(): string {return $this->database;}
    /**
     * 데이터베이스 사용자 이름을 반환합니다.
     *
     * @return string 사용자 이름
     */
    public function username(): string {return $this->username;}
    /**
     * 데이터베이스 비밀번호를 반환합니다.
     *
     * @return string 비밀번호
     */
    public function password(): string {return $this->password;}
}

?>