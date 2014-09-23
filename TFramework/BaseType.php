<?php
namespace TFramework;

class BaseType{
	
    protected function __construct(){}
	
	public function __call($method, $args)
    {
        if (isset($this->$method) === true) {
            $func = $this->$method;
            return $func();
        }
    }
} 
?>