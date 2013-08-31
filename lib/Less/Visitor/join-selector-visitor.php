<?php

class Less_joinSelectorVisitor{

	public $contexts = array( array() );
	public $_visitor;

	const visitRuleDeeper = false;
	const visitMixinDefinition = false;

	function __construct(){
		$this->_visitor = new Less_visitor($this);
	}

	function run( $root ){
		return $this->_visitor->visit($root);
	}

	function visitRuleset($rulesetNode) {
		$context = $this->contexts[ count($this->contexts) - 1];
		$paths = array();
		//$this->contexts[] = $paths;
		if( !$rulesetNode->root ){
			$rulesetNode->joinSelectors($paths, $context, $rulesetNode->selectors);
			$rulesetNode->paths = $paths;
		}

		//array_pop($this->contexts);
		$this->contexts[] = $paths;

	}

	function visitRulesetOut( $rulesetNode ){
		array_pop($this->contexts);
	}

	function visitMedia(&$mediaNode) {
		$context = $this->contexts[ count($this->contexts) - 1];
		$mediaNode->ruleset->root = ( count($context) === 0 || @$context[0]->multiMedia);
	}

}

