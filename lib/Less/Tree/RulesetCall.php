<?php

/**
 * RulesetCall
 *
 * @package Less
 * @subpackage tree
 */
class Less_Tree_RulesetCall extends Less_Tree{

	public $variable;
	public $type = "RulesetCall";

	function __construct($variable){
		$this->variable = $variable;
	}

	function accept($visitor) {}

	function compile( $env ){
		$detachedRuleset = new Less_Tree_Variable($this->variable)->compile($env);
		return $detachedRuleset->callEval($env);
	}
}

