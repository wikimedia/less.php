<?php

namespace Less;

class visitor{

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
			$node = call_user_func( $func, $node, $visitArgs );
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

		$newNodes = array();
		foreach($nodes as $node){
			$evald = $this->visit($node);
			if( is_array($evald) ){
				$newNodes = array_merge($newNodes,$evald);
			} else {
				$newNodes[] = $evald;
			}
		}
		return $newNodes;
	}

}

