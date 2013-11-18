<?php


class Less_Tree_Alpha extends Less_Tree{
	private $value;

	public function __construct($val){
		$this->value = $val;
	}

	function accept( $visitor ){
		$visitor->visit( $this->value );
	}

	public function compile($env){

		if( !is_string($this->value) ){ return new Less_Tree_Alpha( $this->value->compile($env) ); }

		return $this;
	}

	public function genCSS( $env, &$strs ){

		$this->toCSS_Add( $strs, "alpha(opacity=" );

		if( is_string($this->value) ){
			$this->toCSS_Add( $strs, $this->value );
		}else{
			$this->value->genCSS($env, $strs);
		}

		$this->toCSS_Add( $strs, ')' );
	}

	public function toCss($env){
		return "alpha(opacity=" . (is_string($this->value) ? $this->value : $this->value->toCSS()) . ")";
	}


}