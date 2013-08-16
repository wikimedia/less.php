<?php

namespace Less\Node;

class Extend{

	var $selector;
	var $index;

	function __construct($elements, $index){
		$this->selector = new \Less\Node\Selector($elements);
		$this->index = $index;
	}

	function compile( $env, $selectors = array() ){

		$selfSelectors = self::findSelfSelectors( (count($selectors) ? $selectors : $env->selectors) );
		$targetValue = $this->selector->elements[0]->value;

		foreach($env->frames as $frame){
			foreach($frame->rulesets() as $rule){
				foreach($rule->selectors as $selector){
					foreach($selector->elements as $idx => $element){

						if( $element->value = $targetValue ){
							foreach($selfSelectors as $_selector){

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

	static function findSelfSelectors( $selectors, $elem = array(), $i = 0){
		$ret = array();
		if( isset($selectors[$i]) && count($selectors[$i]) ){
			foreach($selectors as $s){
				$ret = array_merge( $ret, self::findSelfSelectors($selectors, array_merge($s->elements,$elem), $i+1) );
			}
		}else{
			$ret[] = new \Less\Node\Selector($elem);
		}

		return $ret;
	}

}