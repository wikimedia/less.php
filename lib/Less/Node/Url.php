<?php

namespace Less\Node;

class Url{
	public $attrs;
	public $value;
	public $rootpath;

	public function __construct($value, $rootpath = null){
		$this->value = $value;
		$this->rootpath = $rootpath;
	}
	public function toCSS(){
		return "url(" . $this->value->toCSS() . ")";
	}

	public function compile($ctx){
		$val = $this->value->compile($ctx);

		// Add the base path if the URL is relative
		if( $this->rootpath && is_string($val->value) && !preg_match('/^(?:[a-z-]+:|\/)/',$val->value) ){
			$rootpath = $this->rootpath;
			if ( !$val->quote ){
				$rootpath = preg_replace('/[\(\)\'"\s]/', '\\$1', $rootpath );
			}
			$val->value = $rootpath . $val->value;
		}


		return new \Less\Node\URL($val, null);
	}

}
