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

				if( $extend->any || ($k == 0 && $i == 0) ){
					$potentialMatches[] = array('pathIndex'=> $k, 'index'=> $i, 'matched' => 0, 'initialCombinator'=> $selector->elements[$i]->combinator);
				}

				for( $l = 0; $l < count($potentialMatches); $l++ ){
					$potentialMatch = $potentialMatches[$l];
					$targetCombinator = $selector->elements[$i]->combinator->value;
					if( $targetCombinator == '' && $i === 0 ){
						$targetCombinator = ' ';
					}
					if( $extend->selector->elements[ $potentialMatch['matched'] ]->value !== $selector->elements[$i]->value ||
						($potentialMatch['matched'] > 0 && $extend->selector->elements[$potentialMatch['matched']]->combinator->value !== $targetCombinator)) {
						$potentialMatch = null;
					} else {
						$potentialMatch['matched']++;
					}

					if( $potentialMatch ){
						$potentialMatch['finished'] = ($potentialMatch['matched'] === count($extend->selector->elements));
						if( $potentialMatch['finished'] && (
							(!$extend->any && $i+1 < count($selector->elements) ) ||
							(!$extend->deep && $k+1 < count($selectorPath) ))) {
							$potentialMatch = null;
						}
					}
					if( $potentialMatch ){
						if( $potentialMatch['finished'] ){
							//$potentialMatch = array_slice($potentialMatch, 0, count($extend->selector->elements));
							$potentialMatch['length'] = count($extend->selector->elements);
							$potentialMatch['endPathIndex'] = $k;
							$potentialMatch['endPathElementIndex'] = $i+1; // index after end of match
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