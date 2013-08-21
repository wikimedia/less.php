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

	function a(){

		self::findSelfSelectors( (count($selectors) ? $selectors : $env->selectors) );
		$targetValue = $this->selector->elements[0]->value;

		foreach($env->frames as &$frame){
			foreach($frame->rules as &$rule){

				if( !($rule instanceof \Less\Node\Ruleset) && !($rule instanceof \Less\Node\Mixin\Definition) ){
					continue;
				}

				$changed = false;
				$before = $rule->selectors;

				foreach($rule->selectors as $selector){
					foreach($selector->elements as $idx => $element){

						if( $element->value == $targetValue ){

							foreach(self::$selfSelectors as $_selector){

								$_selector->elements[0] = new \Less\Node\Element(
									$element->combinator,
									$_selector->elements[0]->value,
									$_selector->elements[0]->index
								);

								$new_elements = array_slice($selector->elements,0,$idx);
								$new_elements = array_merge($new_elements, $_selector->elements);
								$new_elements = array_merge($new_elements, array_slice($selector->elements,$idx+1) );
								$rule->selectors[] = new \Less\Node\Selector( $new_elements );
							}
						}
					}
				}
			}
		}

		return $this;
	}

}