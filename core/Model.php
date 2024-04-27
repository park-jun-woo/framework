<?php
namespace Framework\Core;

abstract class Model{
    abstract public function options():array;
    abstract public function get(array $entity, array $where):array;
    abstract public function post(array $entity, array $data):int;
    abstract public function put(array $entity, array $data, array $where):bool;
    abstract public function delete(array $entity, array $where):bool;
}