<?php

namespace Less;

class processExtendsVisitor{

	public $_visitor;
	public $allExtendsStack;
	public $isReplacing = false;

	function __construct(){
		$this->_visitor = new \Less\visitor($this);
	}

	function run( $root ){
		$extendFinder = new \Less\extendFinderVisitor();
		$extendFinder->run( $root );
		$this->allExtendsStack = array( $root->allExtends );
		return $this->_visitor->visit( $root );
	}

	function visitRule( $ruleNode, &$visitArgs ){
		$visitArgs['visitDeeper'] = false;
	}

	function visitMixinDefinition( $mixinDefinitionNode, &$visitArgs ){
		$visitArgs['visitDeeper'] = false;
	}

	function visitSelector($selectorNode, $visitArgs) {
		$visitArgs['visitDeeper'] = false;
	}

	function visitRuleset($rulesetNode, $visitArgs ){

		if( $rulesetNode->root ){
			return;
		}

		$allExtends = $this->allExtendsStack[ count($this->allExtendsStack)-1];
		$selectorsToAdd = array();



		for( $k = 0; $k < count($allExtends); $k++ ){
			for( $i = 0; $i < count($rulesetNode->selectors); $i++ ){
				$selector = $rulesetNode->selectors[$i];
				$match = $this->findMatch($allExtends[$k], $selector);
				if( $match ){
					foreach($allExtends[$k]->selfSelectors as $selfSelector ){
						$firstElement = new \Less\Node\Element(
							$match['initialCombinator'],
							$selfSelector->elements[0]->value,
							$selfSelector->elements[0]->index
						);

						$new_elements = array_slice($selector->elements,0,$match['index']);
						$new_elements = array_merge($new_elements, array($firstElement) );
						$new_elements = array_merge($new_elements, array_slice($selfSelector->elements,1) );
						$new_elements = array_merge($new_elements, array_slice($selector->elements,$match['index']+1) );
						$selectorsToAdd[] = new \Less\Node\Selector( $new_elements );
					}
				}
			}
		}

		$rulesetNode->selectors = array_merge($rulesetNode->selectors,$selectorsToAdd);
	}

	function findMatch( $extend, $selector ){
		for( $j = 0; $j < count($selector->elements); $j++ ){
			$element = $selector->elements[$j];
			if( $extend->selector->elements[0]->value === $element->value ){
				return array('index' => $j, 'initialCombinator' => $element->combinator );
			}
		}
		return null;
	}

	function visitRulesetOut( $rulesetNode ){
	}

	function visitMedia( $mediaNode, $visitArgs ){
		$temp = $this->allExtendsStack[ count($this->allExtendsStack)-1 ];
		$this->allExtendsStack[] = array_merge( $mediaNode->allExtends, $temp );
	}

	function visitMediaOut( $mediaNode ){
		array_pop( $this->allExtendsStack );
	}

	function visitDirective( $directiveNode, $visitArgs ){
		$temp = $this->allExtendsStack[ count($this->allExtendsStack)-1];
		$this->allExtendsStack[] = array_merge( $directiveNode->allExtends, $temp );
	}

	function visitDirectiveOut( $directiveNode ){
		array_pop($this->allExtendsStack);
	}

}