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

		if( !is_object($node) || !property_exists($node,'type') || !$node->type ){
			return $node;
		}

		$visitArgs = null;
		$funcName = "visit" . $node->type;
		if( method_exists($this->_implementation,$funcName) ){
			$func = array($this->_implementation,$funcName);
			$visitArgs = array('visitDeeper'=> true);
			$newNode = $func($node, $visitArgs);
			if( $this->_implementation->isReplacing ){
				$node = $newNode;
			}
		}

		if( (!$visitArgs || $visitArgs['visitDeeper']) && $node && method_exists($node,'accept') ){
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
		foreach($nodes as $key => $node){
			//not the same as less.js
			$newNodes[$key] = $this->visit($node);
		}
		if( $this->_implementation->isReplacing ){
			return $newNodes;
		}
		return $nodes;
	}

}

