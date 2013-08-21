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

        // multiplies out the selectors, e.g.
        // [[.a],[.b,.c]] => [.a.b,.a.c]
        if( $i === 0 ){
			$this->selfSelectors = array();
		}

		if( isset($selectors[$i]) && is_array($selectors[$i]) && count($selectors[$i]) ){
			foreach($selectors[$i] as $s){
				$this->findSelfSelectors($selectors, array_merge($s->elements,$elem), $i+1 );
			}
		}else{
			$this->selfSelectors[] = new \Less\Node\Selector($elem);
		}
	}
}