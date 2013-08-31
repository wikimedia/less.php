<?php

class Less_visitor{

	private $_implementation;

	function __construct( $implementation ){
		$this->_implementation = $implementation;
	}

	function visit($node){

		if( is_array($node) ){
			return $this->visitArray($node);
		}

		if( !@property_exists($node,'type') || !$node->type ){
			return $node;
		}

		$visitArgs = array('visitDeeper'=> true);
		$funcName = "visit" . $node->type;
		if( method_exists($this->_implementation,$funcName) ){
			$func = array($this->_implementation,$funcName);
			$newNode = $func($node, $visitArgs);
			if( $this->_implementation->isReplacing ){
				$node = $newNode;
			}
		}

		if( $visitArgs['visitDeeper'] && $node && method_exists($node,'accept') ){
			$node->accept($this);
		}

		$funcName = $funcName . "Out";
		if( method_exists($this->_implementation, $funcName) ){
			$func = array($this->_implementation,$funcName);
			call_user_func( $func, $node );
		}

		return $node;
	}

	function visitArray( $nodes ){

		//check for associative arrays
		if( $nodes !== array_values($nodes) ){
			return $nodes;
		}


		$newNodes = array();
		for($i = 0, $len = count($nodes); $i < $len; $i++ ){
			$newNodes[] = $this->visit($nodes[$i]);
		}

		if( $this->_implementation->isReplacing ){
			return $newNodes;
		}
		return $nodes;
	}

}

