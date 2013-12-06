<?php

class Less_visitor{

	var $isReplacing = false;

	function visit($node){

		if( is_array($node) ){
			return $this->visitArray($node);
		}

		if( !is_object($node) ){
			return $node;
		}

		$class = get_class($node);
		$funcName = 'visit'.substr($class,10); //remove 'Less_Tree_' from the class name

		if( method_exists($this,$funcName) ){
			$visitDeeper = true;
			$newNode = $this->$funcName( $node, $visitDeeper );
			if( $this->isReplacing ){
				$node = $newNode;
			}
		}

		if( ( !isset($visitDeeper) || $visitDeeper ) && Less_Parser::is_method($node,'accept') ){
			$node->accept($this);
		}

		$funcName = $funcName . "Out";
		if( method_exists($this,$funcName) ){
			$this->$funcName( $node );
		}
		return $node;
	}

	function visitArray( $nodes ){

		$node_len = count($nodes);

		if( !$this->isReplacing ){
			for( $i = 0; $i < $node_len; $i++ ){
				$this->visit($nodes[$i]);
			}
			return $nodes;
		}


		$newNodes = array();
		for($i = 0; $i < $node_len; $i++ ){
			$evald = $this->visit($nodes[$i]);
			if( is_array($evald) ){
				self::flatten($evald,$newNodes);
			}else{
				$newNodes[] = $evald;
			}
		}
		return $newNodes;
	}

	function flatten( $arr, &$out ){

		$cnt = count($arr);

		for( $i = 0; $i < $cnt; $i++ ){
			$item = $arr[$i];
			if( !is_array($item) ){
				$out[] = $item;
				continue;
			}

			$nestedCnt = count($item);
			for( $j = 0; $j < $nestedCnt; $j++ ){
				$nestedItem = $item[$j];
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

