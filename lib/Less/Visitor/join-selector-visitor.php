<?php

class Less_joinSelectorVisitor extends Less_visitor{

	public $contexts = array( array() );

	public $visitRuleDeeper = false;
	public $visitMixinDefinition = false;


	function run( $root ){
		$this->visit($root);
	}

	function visitRuleset($rulesetNode) {

		$context = end($this->contexts); //$context = $this->contexts[ count($this->contexts) - 1];
		$paths = array();
		$this->contexts[] = $paths;

		if( !$rulesetNode->root ){

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
			$rulesetNode->joinSelectors( $paths, $context, $rulesetNode->selectors );
			$rulesetNode->paths = $paths;
		}
	}

	function visitRulesetOut( $rulesetNode ){
		array_pop($this->contexts);
	}

	function visitMedia($mediaNode) {
		$context = end($this->contexts); //$context = $this->contexts[ count($this->contexts) - 1];
		$mediaNode->rules[0]->root = ( count($context) === 0 || @$context[0]->multiMedia);
	}

}

