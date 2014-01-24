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

		/*
		if( is_object($this->key) ){
			$this->key = $this->key->compile($env);
		}

		if( is_object($this->value) ){
			$this->value = $this->value->compile($env);
		}
		return $this;
		*/

		return new Less_Tree_Attribute(
			is_object($this->key) ? $this->key->compile($env) : $this->key ,
			$this->op,
			is_object($this->value) ? $this->value->compile($env) : $this->value);
	}

    /**
     * @see Less_Tree::genCSS
     */
	function genCSS( $output ){
		$output->add( $this->toCSS() );
	}

	function toCSS(){
		$value = $this->key;

		if( $this->op ){
			$value .= $this->op;
			$value .= (is_object($this->value) ? $this->value->toCSS() : $this->value);
		}

		return '[' . $value . ']';
	}
}