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
		if( !$extendFinder->foundExtends) { return $root; }
		$root->allExtends = array_merge($root->allExtends, $this->doExtendChaining( $root->allExtends, $root->allExtends));
		$this->allExtendsStack = array( $root->allExtends );
		return $this->_visitor->visit( $root );
	}

	function doExtendChaining( $extendsList, $extendsListTarget, $iterationCount = 0){
		$extendsToAdd = array();
		$extendVisitor = $this;

		for( $extendIndex = 0; $extendIndex < count($extendsList); $extendIndex++ ){
			for( $targetExtendIndex = 0; $targetExtendIndex < count($extendsListTarget); $targetExtendIndex++ ){

				$extend = $extendsList[$extendIndex];
				$targetExtend = $extendsListTarget[$targetExtendIndex];
				if( $this->inInheritanceChain( $targetExtend, $extend)) { continue; }

				$selectorPath = array( $targetExtend->selfSelectors[0] );
				$matches = $extendVisitor->findMatch( $extend, $selectorPath);

				if( count($matches) ){

					foreach($extend->selfSelectors as $selfSelector ){
						$newSelector = $extendVisitor->extendSelector( $matches, $selectorPath, $selfSelector);
						$newExtend = new \Less\Node\Extend( $targetExtend->selector, $targetExtend->option, 0);
						$newExtend->selfSelectors = $newSelector;
						$newSelector[ count($newSelector)-1]->extendList = array($newExtend);
						$extendsToAdd[] = $newExtend;
						$newExtend->ruleset = $targetExtend->ruleset;
						$newExtend->parents = array($targetExtend, $extend);
						$targetExtend->ruleset->paths[] = $newSelector;
					}
				}
			}
		}

		if( count($extendsToAdd) ){
			$this->extendChainCount++;
			if( $iterationCount > 100) {
				$selectorOne = "{unable to calculate}";
				$selectorTwo = "{unable to calculate}";
				try{
					$selectorOne = $extendsToAdd[0]->selfSelectors[0]->toCSS();
					$selectorTwo = $extendsToAdd[0]->selector->toCSS();
				}catch(\Exception $e){}
				throw new \Less\Exception\ParserException("extend circular reference detected. One of the circular extends is currently:"+$selectorOne+":extend(" + $selectorTwo+")");
			}
			return array_merge($extendsToAdd, $extendVisitor->doExtendChaining( $extendsToAdd, $extendsListTarget, $iterationCount+1));
		} else {
			return $extendsToAdd;
		}
	}

	function inInheritanceChain( $possibleParent, $possibleChild ){
		if( $possibleParent === $possibleChild) {
			return true;
		}

		if( $possibleChild->parents ){
			if( $this->inInheritanceChain( $possibleParent, $possibleChild->parents[0]) ){
				return true;
			}
			if( $this->inInheritanceChain($possibleParent, $possibleChild->parents[1]) ){
				return true;
			}
		}
		return false;
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

	function visitRuleset($rulesetNode, $visitArgs) {
		if( $rulesetNode->root ){
			return;
		}

		$allExtends = $this->allExtendsStack[ count($this->allExtendsStack)-1];
		$selectorsToAdd = array();
		$extendVisitor = $this;

		// look at each selector path in the ruleset, find any extend matches and then copy, find and replace

		for( $extendIndex = 0; $extendIndex < count($allExtends); $extendIndex++ ){
			for($pathIndex = 0; $pathIndex < count($rulesetNode->paths); $pathIndex++ ){

				$selectorPath = $rulesetNode->paths[$pathIndex];

				// extending extends happens initially, before the main pass
				if( count( $selectorPath[ count($selectorPath)-1]->extendList) ) { continue; }

				$matches = $this->findMatch($allExtends[$extendIndex], $selectorPath);

				if( count($matches) ){
					foreach($allExtends[$extendIndex]->selfSelectors as $selfSelector ){
						$selectorsToAdd[] = $extendVisitor->extendSelector($matches, $selectorPath, $selfSelector);
					}
				}
			}
		}
		$rulesetNode->paths = array_merge($rulesetNode->paths,$selectorsToAdd);
	}

	function findMatch($extend, $haystackSelectorPath ){
		//
		// look through the haystack selector path to try and find the needle - extend.selector
		// returns an array of selector matches that can then be replaced
		//
		$needleElements = $extend->selector->elements;
		$potentialMatches = array();
		$potentialMatch = null;
		$matches = array();

		// loop through the haystack elements
		for($haystackSelectorIndex = 0; $haystackSelectorIndex < count($haystackSelectorPath); $haystackSelectorIndex++ ){
			$hackstackSelector = $haystackSelectorPath[$haystackSelectorIndex];

			for($hackstackElementIndex = 0; $hackstackElementIndex < count($hackstackSelector->elements); $hackstackElementIndex++ ){

				$haystackElement = $hackstackSelector->elements[$hackstackElementIndex];

				// if we allow elements before our match we can add a potential match every time. otherwise only at the first element.
				if( $extend->allowBefore || ($haystackSelectorIndex == 0 && $hackstackElementIndex == 0) ){
					$potentialMatches[] = array('pathIndex'=> $haystackSelectorIndex, 'index'=> $hackstackElementIndex, 'matched'=> 0, 'initialCombinator'=> $haystackElement->combinator);
				}

				for($i = 0; $i < count($potentialMatches); $i++ ){
					$potentialMatch = $potentialMatches[$i];

					// selectors add " " onto the first element. When we use & it joins the selectors together, but if we don't
					// then each selector in haystackSelectorPath has a space before it added in the toCSS phase. so we need to work out
					// what the resulting combinator will be
					$targetCombinator = $haystackElement->combinator->value;
					if( $targetCombinator == '' && $hackstackElementIndex === 0 ){
						$targetCombinator = ' ';
					}

					// if we don't match, null our match to indicate failure
					if( $needleElements[ $potentialMatch['matched'] ]->value !== $haystackElement->value ||
						($potentialMatch['matched'] > 0 && $needleElements[ $potentialMatch['matched'] ]->combinator->value !== $targetCombinator) ){
						$potentialMatch = null;
					} else {
						$potentialMatch['matched']++;
					}

					// if we are still valid and have finished, test whether we have elements after and whether these are allowed
					if( $potentialMatch ){
						$potentialMatch['finished'] = ($potentialMatch['matched'] === count($needleElements) );
						if( $potentialMatch['finished'] &&
							(!$extend->allowAfter && ($hackstackElementIndex+1 < count($hackstackSelector->elements) || $haystackSelectorIndex+1 < count($haystackSelectorPath))) ){
							$potentialMatch = null;
						}
					}
					// if null we remove, if not, we are still valid, so either push as a valid match or continue
					if( $potentialMatch ){
						if( $potentialMatch['finished'] ){
							$potentialMatch['length'] = count($needleElements);
							$potentialMatch['endPathIndex'] = $haystackSelectorIndex;
							$potentialMatch['endPathElementIndex'] = $hackstackElementIndex + 1; // index after end of match
							$potentialMatches = array(); // we don't allow matches to overlap, so start matching again
							$matches[] = $potentialMatch;
						}
					} else {
						array_splice($potentialMatches, $i, 1);
						$i--;
					}
				}
			}
		}
		return $matches;
	}

	function extendSelector($matches, $selectorPath, $replacementSelector){

		//for a set of matches, replace each match with the replacement selector

		$currentSelectorPathIndex = 0;
		$currentSelectorPathElementIndex = 0;
		$path = array();

		for($matchIndex = 0; $matchIndex < count($matches); $matchIndex++ ){
			$match = $matches[$matchIndex];
			$selector = $selectorPath[ $match['pathIndex'] ];
			$firstElement = new \Less\Node\Element(
				$match['initialCombinator'],
				$replacementSelector->elements[0]->value,
				$replacementSelector->elements[0]->index
			);

			if( $match['pathIndex'] > $currentSelectorPathIndex && $currentSelectorPathElementIndex > 0 ){
				$path[ count($path)-1]->elements = array_merge( $path[ count($path) - 1]->elements, array_slice( $selectorPath[$currentSelectorPathIndex]->elements, $currentSelectorPathElementIndex));
				$currentSelectorPathElementIndex = 0;
				$currentSelectorPathIndex++;
			}

			$path = array_merge( $path, array_slice($selectorPath,$currentSelectorPathIndex, $match['pathIndex']));

			$new_elements = array_slice($selector->elements,$currentSelectorPathElementIndex, $match['index']);
			$new_elements = array_merge($new_elements, array($firstElement) );
			$new_elements = array_merge($new_elements, array_slice($replacementSelector->elements,1) );
			$path[] = new \Less\Node\Selector( $new_elements );

			$currentSelectorPathIndex = $match['endPathIndex'];
			$currentSelectorPathElementIndex = $match['endPathElementIndex'];
			if( $currentSelectorPathElementIndex >= count($selector->elements) ){
				$currentSelectorPathElementIndex = 0;
				$currentSelectorPathIndex++;
			}
		}

		if( $currentSelectorPathIndex < count($selectorPath) && $currentSelectorPathElementIndex > 0 ){
			$path[ count($path) - 1]->elements = array_merge( $path[ count($path) - 1]->elements, array_slice($selectorPath[$currentSelectorPathIndex]->elements, $currentSelectorPathElementIndex));
			$currentSelectorPathElementIndex = 0;
			$currentSelectorPathIndex++;
		}

		$path = array_merge($path, array_slice($selectorPath,$currentSelectorPathIndex, count($selectorPath)));

		return $path;
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