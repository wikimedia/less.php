<?php


class Less_Tree_Attribute extends Less_Tree{

	public $key;
	public $op;
	public $value;
	public $type = 'Attribute';

	function __construct($key, $op, $value){
		$this->key = $key;
		$this->op = $op;
		$this->value = $value;
	}

	function compile($env){
		return new Less_Tree_Attribute( ( (Less_Parser::is_method($this->key,'compile')) ? $this->key->compile($env) : $this->key),
			$this->op, ( Less_Parser::is_method($this->value,'compile')) ? $this->value->compile($env) : $this->value);
	}

	function genCSS( $env, &$strs ){
		self::OutputAdd( $strs, $this->toCSS($env) );
	}

	function toCSS($env = null){
		$value = $this->key;

		if( $this->op ){
			$value .= $this->op;
			$value .= ( Less_Parser::is_method($this->value,'toCSS') ? $this->value->toCSS($env) : $this->value);
		}

		return '[' . $value . ']';
	}
}