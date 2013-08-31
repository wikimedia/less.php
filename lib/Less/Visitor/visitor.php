<?php

class Less_visitor{

	private $_implementation;

	function __construct( $implementation ){
		$this->_implementation = $implementation;
	}

	function visit($node){

		if( is_array($node) ){
			$this->visitArray($node);
			return;
		}

		if( !@property_exists($node,'type') || !$node->type ){
			return;
		}

		$funcName = "visit" . $node->type;
		if( method_exists($this->_implementation,$funcName) ){
			$func = array($this->_implementation,$funcName);
			$func($node);
		}


		$deeper_property = $funcName.'Deeper';
		if( !property_exists($this->_implementation,$deeper_property) && $node && method_exists($node,'accept') ){
			$node->accept($this);
		}

		$funcName = $funcName . "Out";
		if( method_exists($this->_implementation, $funcName) ){
			$func = array($this->_implementation,$funcName);
			call_user_func( $func, $node );
		}
	}

	function visitArray( $nodes ){

		//check for associative arrays
		if( $nodes !== array_values($nodes) ){
			return;
		}

		for($i = 0, $len = count($nodes); $i < $len; $i++ ){
			$this->visit($nodes[$i]);
		}
	}

}

