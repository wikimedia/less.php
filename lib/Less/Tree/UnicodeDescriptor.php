<?php


class Less_Tree_UnicodeDescriptor extends Less_Tree{

	public $type = 'UnicodeDescriptor';

	public function __construct($value){
		$this->value = $value;
	}

	public function genCSS( $env, $output ){
		$output->add( $this->value );
	}

	public function compile($env){
		return $this;
	}
}

