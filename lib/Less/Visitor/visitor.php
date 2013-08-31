<?php

class Less_visitor{

	function visit($nodes){

		if( is_array($nodes) ){

			//check for associative arrays
			if( $nodes !== array_values($nodes) ){
				return;
			}
		}else{
			$nodes = array($nodes);
		}

		foreach($nodes as $node){

			if( !@property_exists($node,'type') || !$node->type ){
				return;
			}

			$funcName = "visit" . $node->type;
			if( method_exists($this,$funcName) ){
				$this->$funcName( $node );
			}

			$deeper_property = $funcName.'Deeper';
			if( !isset($this->$deeper_property) && method_exists($node,'accept') ){
				$node->accept($this);
			}

			$funcName = $funcName . "Out";
			if( method_exists($this,$funcName) ){
				$this->$funcName( $node );
			}
		}
	}

}

