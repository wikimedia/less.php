<?php

//less.js : lib/less/tree/selector.js


class Less_Tree_Selector {

	public $type = 'Selector';
	public $elements;
	public $extendList = array();
	private $_css;

	public function __construct($elements, $extendList = array() ){
		$this->elements = $elements;
		$this->extendList = $extendList;
	}

	function accept($visitor) {
		$this->elements = $visitor->visit($this->elements);
		$this->extendList = $visitor->visit($this->extendList);
	}

	public function match($other) {
		$len   = count($this->elements);

		$oelements = array_slice( $other->elements, (count($other->elements) && $other->elements[0]->value === "&") ? 1 : 0);
		$olen = count($oelements);

		$max = min($len, $olen);

		if( $olen === 0 || $len < $olen ){
			return false;
		} else {
			for ($i = 0; $i < $max; $i ++) {
				if ($this->elements[$i]->value !== $oelements[$i]->value) {
					return false;
				}
			}
		}

		return true;
	}

	public function compile($env) {
		$extendList = array();

		for($i = 0, $len = count($this->extendList); $i < $len; $i++){
			$extendList[] = $this->extendList[$i]->compile($this->extendList[$i]);
		}

		$elements = array();
		for( $i = 0, $len = count($this->elements); $i < $len; $i++){
			$elements[] = $this->elements[$i]->compile($env);
		}

		return new Less_Tree_Selector($elements, $extendList);
	}

	public function toCSS ($env){

		if ($this->_css) {
			return $this->_css;
		}

		if (is_array($this->elements) && isset($this->elements[0]) &&
			$this->elements[0]->combinator instanceof Less_Tree_Combinator &&
			$this->elements[0]->combinator->value === '') {
				$this->_css = ' ';
		}else{
			$this->_css = '';
		}

		$temp = array();
		foreach($this->elements as $e){
			if( is_string($e) ){
				$temp[] = ' ' . trim($e);
			}else{
				$temp[] = $e->toCSS($env);
			}
		}
		$this->_css .= implode('', $temp);

		return $this->_css;
	}

}
