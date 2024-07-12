<?php
/**
 * @private
 */
class Less_Tree_VariableCall extends Less_Tree {

	public $variable;
	public $type = "VariableCall";

	/**
	 * @param string $variable
	 */
	public function __construct( $variable ) {
		$this->variable = $variable;
	}

	public function accept( $visitor ) {
	}

	public function compile( $env ) {
		$variable = new Less_Tree_Variable( $this->variable );
		$detachedRuleset = $variable->compile( $env );
		'@phan-var Less_Tree_DetachedRuleset $detachedRuleset';
		return $detachedRuleset->callEval( $env );
	}
}
