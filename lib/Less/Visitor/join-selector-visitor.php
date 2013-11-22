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

