<?php

//less.js : lib/less/tree/selector.js

namespace Less\Node;

class Selector {

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
		if ($len < $olen) {
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
		foreach($this->extendList as $extend){
			$extendList[] = $extend->compile($extend);
		}

		$elements = array();
		foreach($this->elements as $e){
			$elements[] = $e->compile($env);
		}

		return new \Less\Node\Selector($elements, $extendList);
	}

	public function toCSS ($env){

		//$debug = debug_backtrace();
		//echo \Less\Pre($debug);

		//static $z = 0;
		//$z++;
		//echo '<h3>selector '.$z.'</h3>';
		//echo \Less\Pre($this->elements);


		if ($this->_css) {
			return $this->_css;
		}

		if (is_array($this->elements) && isset($this->elements[0]) &&
			$this->elements[0]->combinator instanceof \Less\Node\Combinator &&
			$this->elements[0]->combinator->value === '') {
				$this->_css = ' ';
		}else{
			$this->_css = '';
		}

		$temp = array_map(function ($e) use ($env) {
			if (is_string($e)) {
				return ' ' . trim($e);
			} else {
				return $e->toCSS($env);
			}
		}, $this->elements);
		$this->_css .= implode('', $temp);

		return $this->_css;
	}

}
