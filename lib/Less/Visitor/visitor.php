<?php

class Less_visitor{

	var $isReplacing = false;

	var $methods = array();
	var $_visitFnCache = array();

	function __construct(){
		$this->_visitFnCache = get_class_methods(get_class($this));
	}


	function visit($node){

		$type = getType($node);

		if( $type === 'array' ){
			return $this->visitArray($node);
		}

		if( $type !== 'object' ){
			return $node;
		}

		$funcName = 'visit'.$node->type;
		if( in_array($funcName,$this->_visitFnCache) ){

			$visitDeeper = true;
			$newNode = $this->$funcName( $node, $visitDeeper );
			if( $this->isReplacing ){
				$node = $newNode;
			}

			if( $visitDeeper && Less_Parser::is_method($node,'accept') ){
				$node->accept($this);
			}

			$funcName = $funcName . "Out";
			if( in_array($funcName,$this->_visitFnCache) ){
				$this->$funcName( $node );
			}

		}elseif( method_exists($node,'accept') ){
			$node->accept($this);
		}


		return $node;
	}

	function visitArray( $nodes ){

		if( !$this->isReplacing ){
			array_map( array($this,'visit'), $nodes);
			return $nodes;
		}


		$newNodes = array();
		foreach($nodes as $node){
			$evald = $this->visit($node);
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

