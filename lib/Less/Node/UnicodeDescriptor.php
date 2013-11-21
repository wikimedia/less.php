<?php


class Less_Tree_UnicodeDescriptor extends Less_Tree{

	public function __construct($value){
		$this->value = $value;
	}

	public function genCSS( $env, &$strs ){
		self::toCSS_Add( $strs, $this->value );
	}

	public function compile($env){
		return $this;
	}
}

