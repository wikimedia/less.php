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
			for($i = 0; $i < count($rulesetNode->paths); $i++ ){
				$selectorPath = $rulesetNode->paths[$i];
				$matches = $this->findMatch( $allExtends[$k], $selectorPath);
				if( count($matches) ){
					foreach( $allExtends[$k]->selfSelectors as $selfSelector ){
						$currentSelectorPathIndex = 0;
						$currentSelectorPathElementIndex = 0;
						$path = [];
						for($j = 0; $j < count($matches); $j++ ){
							$match = $matches[$j];
							$selector = $selectorPath[ $match['pathIndex'] ];

							$firstElement = new \Less\Node\Element(
								$match['initialCombinator'],
								$selfSelector->elements[0]->value,
								$selfSelector->elements[0]->index
							);

							if( $match['pathIndex'] > $currentSelectorPathIndex && $currentSelectorPathElementIndex > 0 ){
								$path[ count($path)-1]->elements = array_merge($path[ count($path)-1]->elements, array_slice($selectorPath[$currentSelectorPathIndex]->elements,$currentSelectorPathElementIndex));

								$currentSelectorPathIndex++;
							}

							$path = array_merge($path, array_slice($selectorPath,$currentSelectorPathIndex, $match['pathIndex']));

							$new_elements = array_slice($selector->elements,$currentSelectorPathElementIndex,$match['index']);
							$new_elements = array_merge($new_elements, array($firstElement) );
							$new_elements = array_merge($new_elements, array_slice($selfSelector->elements,1) );
							$path[] = new \Less\Node\Selector( $new_elements );


							$currentSelectorPathIndex = $match['endPathIndex'];
							$currentSelectorPathElementIndex = $match['endPathElementIndex'];
							if( $currentSelectorPathElementIndex >= count($selector->elements) ){
								$currentSelectorPathElementIndex = 0;
								$currentSelectorPathIndex++;
							}
						}

						if( $currentSelectorPathIndex < count($selectorPath) && $currentSelectorPathElementIndex > 0 ){
							$path[ count($path)-1]->elements = array_merge($path[ count($path)-1]->elements, array_slice($selectorPath[$currentSelectorPathIndex]->elements,$currentSelectorPathElementIndex));
							$currentSelectorPathElementIndex = 0;
							$currentSelectorPathIndex++;
						}

						$path = array_merge($path,array_slice($selectorPath,$currentSelectorPathIndex, count($selectorPath)));

						$selectorsToAdd[] = $path;
					}
				}
			}
		}

		$rulesetNode->paths = array_merge($rulesetNode->paths, $selectorsToAdd);
	}

	function findMatch( $extend, $selectorPath ){
		$hasMatch = false;
		$potentialMatches = array();
		$potentialMatch = null;
		$matches = array();

		for( $k = 0; $k < count($selectorPath); $k++ ){
			$selector = $selectorPath[$k];
			for( $i = 0; $i < count($selector->elements); $i++ ){
				$potentialMatches[] = array('pathIndex'=> $k, 'index'=> $i, 'matched' => 0);

				for( $l = 0; $l < count($potentialMatches); $l++ ){
					$potentialMatch = $potentialMatches[$l];
					$targetElementIndex = $i;
					for($j = $potentialMatch['matched']; $j < count($extend->selector->elements) && $targetElementIndex < count($selector->elements); $j++, $targetElementIndex++ ){
						$potentialMatch['matched'] = $j + 1;
						if( $extend->selector->elements[$j]->value !== $selector->elements[$targetElementIndex]->value ||
							($j > 0 && $extend->selector->elements[$j]->combinator->value !== $selector->elements[$targetElementIndex]->combinator->value) ){
							$potentialMatch = null;
							break;
						}
					}
					if( $potentialMatch ){
						if( $potentialMatch['matched'] === count($extend->selector->elements) ){
							$potentialMatch['initialCombinator'] = $selector->elements[$i]->combinator;
							$potentialMatch['length'] = count($extend->selector->elements);
							$potentialMatch['endPathIndex'] = $k;
							$potentialMatch['endPathElementIndex'] = $targetElementIndex; // index after end of match
							$potentialMatches = array();
							$matches[] = $potentialMatch;
							break;
						}
					} else {
						array_splice($potentialMatches,$l, 1);
						$l--;
					}
				}
			}
		}
		return $matches;
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