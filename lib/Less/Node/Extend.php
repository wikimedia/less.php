<?php

namespace Less\Node;

class Extend{

	public $type = 'Extend';
	var $selector;
	var $option;
	var $index;
	var $selfSelectors = array();

	function __construct($selector, $option, $index){
		$this->selector = $selector;
		$this->option = $option;
		$this->index = $index;
	}

	function accept( $visitor ){
		$this->selector = $visitor->visit( $this->selector );
	}

	function compile( $env ){
		return new \Less\Node\Extend( $this->selector->compile($env), $this->option, $this->index);
	}


	function findSelfSelectors( $selectors, $elem = array(), $i = 0){
		$selfElements = array();

		for($i = 0; $i < count($selectors); $i++ ){

			if( !is_object($selectors[$i]) ){
				$debug = debug_backtrace();
				echo \Less\Pre($debug);
				//echo \Less\Pre($selectors[$i]);
				die();
			}


			$selfElements = array_merge($selfElements, $selectors[$i]->elements);
		}

		$this->selfSelectors = array(new \Less\Node\Selector($elem));
	}
}