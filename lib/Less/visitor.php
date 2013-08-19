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

		if( !$node || !$node->type ){
			return $node;
		}

		$funcName = "visit" + $node->type,
		if( isset($this->_implementation[$funcName]) ){
			$func = $this->_implementation[$funcName];
			$visitArgs = array('visitDeeper'=> true);
			$node = call_user_func( $func, $node );
		}
		if( (!$visitArgs || $visitArgs['visitDeeper']) && method_exists($node,'accept') ){
			$node->accept($this);
		}
		return $node;
	}

	function visitArray( $nodes ){

		foreach($nodes as $evld){
			$evald = $this->visit( $nodes[$i] );
			if( is_array($evald) ){
				$newNodes = array_merge($newNodes,$evald);
			} else {
				$newNodes[] = $evald;
			}
		}
		return $newNodes;
	}

}

