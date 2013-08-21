<?php

namespace Less;


class extendFinderVisitor{

	public $contexts = array();
	public $_visitor;
	public $allExtendsStack;
	public $isReplacing = false;

	function __construct(){
		$this->_visitor = new \Less\visitor($this);
		$this->contexts = array();
		$this->allExtendsStack = array(array());
	}

	function run($root) {
		$root = $this->_visitor->visit($root);
		$root->allExtends = $this->allExtendsStack[0];
		return $root;
	}

	function visitRule($ruleNode, &$visitArgs) {
		$visitArgs['visitDeeper'] = false;
	}

	function visitMixinDefinition($mixinDefinitionNode, &$visitArgs) {
		$visitArgs['visitDeeper'] = false;
	}

	function visitRuleset($rulesetNode, $visitArgs) {

		if( $rulesetNode->root ){
			return;
		}

		$allSelectorsExtendList = array();

		// get &:extend(.a); rules which apply to all selectors in this ruleset
		for( $i = 0; $i < count($rulesetNode->rules); $i++ ){
			if( $rulesetNode->rules[$i] instanceof \Less\Extend ){
				$allSelectorsExtendList[] = $rulesetNode->rules[$i];
			}
		}

		// now find every selector and apply the extends that apply to all extends
		// and the ones which apply to an individual extend
		for($i = 0; $i < count($rulesetNode->selectors); $i++) {
			$selector = $rulesetNode->selectors[$i];
			if( $selector instanceof \Less\Node\Selector ){

			}else{
				echo \Less\Pre($selector);
			}

			$cloneList = array();
			foreach($allSelectorsExtendList as $allSelectorsExtend){
				$cloneList = clone $allSelectorsExtend;
			}

			$extendList = array_slice($selector->extendList,0);
			$extendList = array_merge($extendList, $cloneList);

			for( $j = 0; $j < count($extendList); $j++ ){
				$extend = $extendList[$j];
				$find = array(array($selector));
				$find = array_merge($find,$this->contexts);
				$extend->findSelfSelectors( $find );
				$this->allExtendsStack[ count($this->allExtendsStack)-1][] = $extend;
			}
		}


		$this->contexts[] = $rulesetNode->selectors;
	}

	function visitRulesetOut( $rulesetNode ){
		if( !$rulesetNode->root) {
			array_pop($this->contexts);
		}
	}

	function visitMedia( $mediaNode, $visitArgs ){
		$mediaNode->allExtends = array();
		$this->allExtendsStack[] = $mediaNode->allExtends;
	}

	function visitMediaOut( $mediaNode ){
		array_pop($this->allExtendsStack);
	}

	function visitDirective( $directiveNode, $visitArgs ){
		$directiveNode->allExtends = array();
		$this->allExtendsStack[] = $directiveNode->allExtends;
	}

	function visitDirectiveOut( $directiveNode ){
		array_pop($this->allExtendsStack);
	}
}


