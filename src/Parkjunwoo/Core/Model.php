<?php
namespace Parkjunwoo\Core;

use Parkjunwoo\Parkjunwoo;

class Model{
    /**
     * Model 생성자
     * @param array $app 실행할 어플리케이션 코드 배열
     */
    public function __construct(Parkjunwoo $man, string $entityName){

    }
    public function get(array $entity, array $where):array{

    }
    public function post(array $entity, array $data):int{

    }
    public function put(array $entity, array $data, array $where):bool{

    }
    public function delete(array $entity, array $where):bool{

    }
}