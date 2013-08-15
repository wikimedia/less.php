<?php

namespace Less\Node;

class Paren {

	public $value;

	public function __construct($value) {
		$this->value = $value;
	}

	public function toCSS($env) {
		return '(' . trim($this->value->toCSS($env)) . ')';
	}

	public function compile($env) {
		return new Paren($this->value->compile($env));
	}

}
