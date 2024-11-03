<?php
namespace Parkjunwoo\Config;

/**
 * Route 클래스
 *
 * 이 클래스는 애플리케이션의 라우트 정보를 관리합니다.
 * 각 라우트에는 URI, 접근 권한, 클래스, 메서드 정보가 포함됩니다.
 */
class Route {
    public const PERMISSION = 0;
    public const CLASSNAME = 1;
    public const METHODNAME = 2;
    
    /** @var int HTTP 메서드 */
    protected int $http_method;
    /** @var string 라우트의 URI 경로 */
    protected string $uri;
    /** @var int 라우트 접근 권한 */
    protected int $permission;
    /** @var string 실행할 클래스 이름 */
    protected string $class;
    /** @var string 클래스 내에서 실행할 메서드 이름 */
    protected string $method;
    /**
     * Route 생성자
     *
     * 라우트 정보(URI, 권한, 클래스, 메서드)를 초기화합니다.
     *
     * @param int $http_method HTTP 메서드 (0=GET, 1=POST)
     * @param string $uri 라우트 URI 경로
     * @param int $permission 해당 라우트의 접근 권한 (예: 0=guest, 1=member 등)
     * @param string $class 라우트를 처리할 클래스 이름
     * @param string $method 클래스 내에서 실행할 메서드 이름
     */
    public function __construct(int $http_method, string $uri, int $permission, string $class, string $method) {
        $this->http_method = $http_method;
        $this->uri = $uri;
        $this->permission = $permission;
        $this->class = $class;
        $this->method = $method;
    }
    /**
     * HTTP 메서드를 반환합니다.
     *
     * @return int HTTP 메서드 (0=GET, 1=POST)
     */
    public function httpMethod():int {return $this->http_method;}
    /**
     * 라우트의 URI 경로를 반환합니다.
     *
     * @return string 라우트의 URI 경로
     */
    public function uri():string {return $this->uri;}
    /**
     * 접근 권한을 반환합니다.
     *
     * @return int 접근 권한 (예: 0=guest, 1=member 등)
     */
    public function permission():int {return $this->permission;}
    /**
     * 라우트를 처리할 클래스 이름을 반환합니다.
     *
     * @return string 처리할 클래스 이름
     */
    public function class():string {return $this->class;}
    /**
     * 클래스 내에서 실행할 메서드 이름을 반환합니다.
     *
     * @return string 실행할 메서드 이름
     */
    public function method():string {return $this->method;}
}
