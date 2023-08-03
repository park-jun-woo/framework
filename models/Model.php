<?php
namespace models;

abstract class Model{
	protected array $enableMethods;
	abstract public function get(array $query);
	abstract public function post();
	abstract public function put();
	abstract public function delete();
}