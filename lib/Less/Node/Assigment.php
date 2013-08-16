<?php

namespace Less\Node;

class Assignment {

	private $key;
	private $value;

	function __construct($key, $val) {
		$this->key = $key;
		$this->value = $val;
	}

    public function toCss($env) {
        return $this->key . '=' . (is_string($this->value) ? $this->value : $this->value->toCSS());
    }

    public function compile($env) {
		if( is_object($this->value) && method_exists($this->value,'compile') ){
			return new \Less\Node\Assignment( $this->key, $this->value->compile($env));
        }
        return $this;
    }

}
