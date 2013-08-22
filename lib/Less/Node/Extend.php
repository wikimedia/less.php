<?php

namespace Less\Node;

class Extend{

	public $type = 'Extend';
	public $selector;
	public $option;
	public $index;
	public $selfSelectors = array();
	public $deep;
	public $any;


	function __construct($selector, $option, $index){
		$this->selector = $selector;
		$this->option = $option;
		$this->index = $index;

		switch($option){
			case "all":
				$this->deep = true;
				$this->any = true;
			break;
			case "deep":
				$this->deep = true;
				$this->any = false;
			break;
			case "any":
				$this->deep = false;
				$this->any = true;
			break;
			default:
				$this->deep = false;
				$this->any = false;
			break;
		}
	}

	function accept( $visitor ){
		$this->selector = $visitor->visit( $this->selector );
	}

	function compile( $env ){
		return new \Less\Node\Extend( $this->selector->compile($env), $this->option, $this->index);
	}


	function findSelfSelectors( $selectors){
		$selfElements = array();

		for($i = 0; $i < count($selectors); $i++ ){
			$selfElements = array_merge($selfElements, $selectors[$i]->elements);
		}

		$this->selfSelectors = array(new \Less\Node\Selector($selfElements));
	}
}