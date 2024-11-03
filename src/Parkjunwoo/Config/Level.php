<?php
namespace Parkjunwoo\Config;
/**
 * 사용자 종류 클래스
 *
 * 이 클래스는 사용자 종류에 대한 설정을 관리합니다.
 * 사용자 ID, 이름, 타이틀을 생성자에서 초기화하며,
 * 각 속성에 접근할 수 있는 게터(getter) 메서드를 제공합니다.
 */
class Level {
    // 사용자 종류의 ID, 이름, 타이틀 정보
    protected int $id;
    protected string $name, $title;
    /**
     * 사용자 종류 설정 생성자
     *
     * 각 사용자 종류의 ID, 이름, 타이틀을 설정합니다.
     *
     * @param int $id 사용자 종류의 고유 ID
     * @param string $name 사용자 종류의 이름 (예: admin, member 등)
     * @param string $title 사용자 종류의 타이틀 (예: 관리자, 회원 등)
     */
    public function __construct(int $id, string $name, string $title) {
        $this->id = $id;
        $this->name = $name;
        $this->title = $title;
    }
    /**
     * 사용자 종류의 ID 반환
     *
     * @return int 사용자 종류의 고유 ID
     */
    public function id(): int {return $this->id;}
    /**
     * 사용자 종류의 이름 반환
     *
     * @return string 사용자 종류의 이름 (예: admin, member 등)
     */
    public function name(): string {return $this->name;}
    /**
     * 사용자 종류의 타이틀 반환
     *
     * @return string 사용자 종류의 타이틀 (예: 관리자, 회원 등)
     */
    public function title(): string {return $this->title;}
}
