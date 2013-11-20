<?php

class Less_Tree_Paren extends Less_Tree{

	public $value;

	public function __construct($value) {
		$this->value = $value;
	}

	function accept($visitor){
		$visitor->visit($this->value);
	}

	function genCSS( $env, &$strs ){
		self::toCSS_Add( $strs, '(' );
		$this->value->genCSS( $env, $strs );
		self::toCSS_Add( $strs, ')' );
	}

	public function compile($env) {
		return new Less_Tree_Paren($this->value->compile($env));
	}

}
