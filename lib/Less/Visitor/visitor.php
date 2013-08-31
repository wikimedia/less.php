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
		if( method_exists($this,$funcName) ){
			$this->$funcName( $node );
		}

		$deeper_property = $funcName.'Deeper';
		if( !property_exists($this,$deeper_property) && method_exists($node,'accept') ){
			$node->accept($this);
		}

		$funcName = $funcName . "Out";
		if( method_exists($this,$funcName) ){
			$this->$funcName( $node );
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

