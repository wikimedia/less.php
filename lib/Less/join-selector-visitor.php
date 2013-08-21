<?php

namespace Less;

class joinSelectorVisitor{

	public $contexts = array( array() );
	public $_visitor;

	function __construct(){
		$this->_visitor = new \Less\visitor($this);
	}

	function run( $root ){
		return $this->_visitor->visit($root);
	}

	function visitRule($ruleNode, $visitArgs) {
		$visitArgs['visitDeeper'] = false;
		return $ruleNode;
	}

	function visitMixinDefinition($mixinDefinitionNode, $visitArgs) {
		$visitArgs['visitDeeper'] = false;
		return $mixinDefinitionNode;
	}

	function visitRuleset($rulesetNode, $visitArgs) {
		$context = $this->contexts[ count($this->contexts) - 1];
		$paths = array();
		//$this->contexts[] = $paths;
		if( !$rulesetNode->root ){
			$rulesetNode->joinSelectors($paths, $context, $rulesetNode->selectors);
			$rulesetNode->paths = $paths;
		}

		//array_pop($this->contexts);
		$this->contexts[] = $paths;

		return $rulesetNode;
	}

	function visitRulesetOut( $rulesetNode ){
		array_pop($this->contexts);
	}

	function visitMedia($mediaNode, $visitArgs) {
		$context = $this->contexts[ count($this->contexts) - 1];
		$mediaNode->ruleset->root = ( count($context) === 0 || $context[0]->multiMedia);
		return $mediaNode;
	}

}

