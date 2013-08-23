<?php

namespace Less\Node;

class Extend{

	public $type = 'Extend';
	public $selector;
	public $option;
	public $index;
	public $selfSelectors = array();
	public $allowBefore;
	public $allowAfter;
	public $parents = array();
	public $firstExtendOnThisSelectorPath;


	function __construct($selector, $option, $index){
		$this->selector = $selector;
		$this->option = $option;
		$this->index = $index;

		switch($option){
	        case "all":
	            $this->allowBefore = true;
	            $this->allowAfter = true;
	        break;
	        default:
	            $this->allowBefore = false;
	            $this->allowAfter = false;
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