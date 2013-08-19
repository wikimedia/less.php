<?php

namespace Less\Node;

class Paren {

	public $type = 'Paren';
	public $value;

	public function __construct($value) {
		$this->value = $value;
	}

	function accept($visitor){
		$this->value = $visitor->visit($this->value);
	}

	public function toCSS($env) {
		return '(' . trim($this->value->toCSS($env)) . ')';
	}

	public function compile($env) {
		return new Paren($this->value->compile($env));
	}

}
