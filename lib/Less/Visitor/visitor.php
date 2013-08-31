<?php

class Less_visitor{

	function visit($node){

		if( is_array($node) ){
			$this->visitArray($node);
			return;
		}

		if( !@property_exists($node,'type') || !$node->type ){
			return;
		}

		$funcName = "visit" . $node->type;
		$this->Call( $funcName, $node);

		$deeper_property = $funcName.'Deeper';
		if( !property_exists($this,$deeper_property) && method_exists($node,'accept') ){
			$node->accept($this);
		}

		$funcName = $funcName . "Out";
		$this->Call( $funcName, $node);
	}

	function Call( $funcName, $node ){

		if( method_exists($this,$funcName) ){
			$func = array($this,$funcName);
			//$func($node);
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

