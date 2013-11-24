<?php

class Less_joinSelectorVisitor extends Less_visitor{

	public $contexts = array( array() );

	function run( $root ){
		return $this->visit($root);
	}

	function visitRule( $ruleNode, &$visitDeeper ){
		$visitDeeper = false;
	}

	function visitMixinDefinition( $mixinDefinitionNode, &$visitDeeper ){
		$visitDeeper = false;
	}

	function visitRuleset($rulesetNode) {

		$paths = array();
		if( !$rulesetNode->root ){

			$context = end($this->contexts); //$context = $this->contexts[ count($this->contexts) - 1];
			$paths = array();

			$selectors = array();
			foreach($rulesetNode->selectors as $selector){
				if( $selector->getIsOutput() ){
					$selectors[] = $selector;
				}
			}

			$rulesetNode->selectors = $selectors;
			if( count($rulesetNode->selectors) === 0 ){
				$rulesetNode->rules = array();
			}
			$paths = $rulesetNode->joinSelectors($context, $rulesetNode->selectors );
			$rulesetNode->paths = $paths;
		}
		$this->contexts[] = $paths; //different from less.js. Placed after joinSelectors() so that $this->contexts will get correct $paths

	}

	function visitRulesetOut( $rulesetNode ){
		array_pop($this->contexts);
	}

	function visitMedia($mediaNode) {
		$context = end($this->contexts); //$context = $this->contexts[ count($this->contexts) - 1];

		if( !count($context) || (is_object($context[0]) && @$context[0]->multiMedia) ){
			$mediaNode->rules[0]->root = true;
		}
	}

}

