<?php

namespace Less\Node;

class Assigment {

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
        if ( ! is_string($this->value)) {
            $this->value = $this->value->compile($env);
        }
        return $this;
    }

}
