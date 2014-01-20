<?php


class Less_Tree_Alpha extends Less_Tree{
	public $value;
	public $type = 'Alpha';

	public function __construct($val){
		$this->value = $val;
	}

	//function accept( $visitor ){
	//	$this->value = $visitor->visit( $this->value );
	//}

	public function compile($env){

		if( is_object($this->value) ){
			$this->value = $this->value->compile($env);
		}

		return $this;
	}

	public function genCSS( $env, $output ){

		$output->add( "alpha(opacity=" );

		if( is_string($this->value) ){
			$output->add( $this->value );
		}else{
			$this->value->genCSS($env, $output);
		}

		$output->add( ')' );
	}

	public function toCSS($env = null){
		return "alpha(opacity=" . (is_string($this->value) ? $this->value : $this->value->toCSS()) . ")";
	}


}