<?php

class Less_visitor{

	var $isReplacing = false;

	var $methods = array();
	var $_visitFnCache = array();

	function __construct(){
		$this->_visitFnCache = get_class_methods(get_class($this));
		$this->_visitFnCache = array_flip($this->_visitFnCache);
	}

	function visitObj( $node ){

		$funcName = 'visit'.$node->type;
		if( isset($this->_visitFnCache[$funcName]) ){

			$visitDeeper = true;
			$newNode = $this->$funcName( $node, $visitDeeper );
			if( $this->isReplacing ){
				$node = $newNode;
			}

			if( $visitDeeper && (!$this->isReplacing || is_object($node)) ){
				$node->accept($this);
			}

			$funcName = $funcName . "Out";
			if( isset($this->_visitFnCache[$funcName]) ){
				$this->$funcName( $node );
			}

		}else{
			$node->accept($this);
		}

		return $node;
	}

	function visitArray( $nodes ){

		if( !$this->isReplacing ){
			array_map( array($this,'visitObj'), $nodes);
			return $nodes;
		}


		$newNodes = array();
		foreach($nodes as $node){
			$evald = $this->visitObj($node);
			if( is_array($evald) ){
				self::flatten($evald,$newNodes);
			}else{
				$newNodes[] = $evald;
			}
		}
		return $newNodes;
	}


	function flatten( $arr, &$out ){

		foreach($arr as $item){
			if( !is_array($item) ){
				$out[] = $item;
				continue;
			}

			foreach($item as $nestedItem){
				if( is_array($nestedItem) ){
					self::flatten( $nestedItem, $out);
				}else{
					$out[] = $nestedItem;
				}
			}
		}

		return $out;
	}

}

