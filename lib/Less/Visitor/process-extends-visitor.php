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

		if( !count($allExtends) ){
			return;
		}

		for( $i = 0; $i < count($rulesetNode->selectors); $i++ ){
			$selector = $rulesetNode->selectors[$i];
			for( $j = 0; $j < count($selector->elements); $j++ ){
				$element = $selector->elements[$j];
				for( $k = 0; $k < count($allExtends); $k++ ){
					if( $allExtends[$k]->selector->elements[0]->value === $element->value ){
						foreach($allExtends[$k]->selfSelectors as $selfSelector){
							$selfSelector->elements[0] = new \Less\Node\Element(
								$element->combinator,
								$selfSelector->elements[0]->value,
								$selfSelector->elements[0]->index
							);

							$new_elements = array_slice($selector->elements,0,$j);
							$new_elements = array_merge($new_elements, $selfSelector->elements);
							$new_elements = array_merge($new_elements, array_slice($selector->elements,$j+1) );
							$rule->selectors[] = new \Less\Node\Selector( $new_elements );
						}
					}
				}
			}
		}
		$rulesetNode->selectors = array_merge($rulesetNode->selectors,$selectorsToAdd);
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