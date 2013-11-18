<?php


class Less_Tree_Assignment extends Less_Tree{

	private $key;
	private $value;

	function __construct($key, $val) {
		$this->key = $key;
		$this->value = $val;
	}

	function accept( $visitor ){
		$visitor->visit( $this->value );
	}


	public function compile($env) {
		if( Less_Parser::is_method($this->value,'compile') ){
			return new Less_Tree_Assignment( $this->key, $this->value->compile($env));
		}
		return $this;
	}

	public function genCSS( $env, &$strs ){
		$this->toCSS_Add( $strs, $this->key . '=' );
		if( is_string($this->value) ){
			$this->toCSS_Add( $strs, $this->value );
		}else{
			$this->value->genCSS( $env, $strs );
		}
	}

	public function toCss($env) {
		return $this->key . '=' . (is_string($this->value) ? $this->value : $this->value->toCSS());
	}
}
