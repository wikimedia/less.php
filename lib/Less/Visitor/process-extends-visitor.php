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

	function visitRule( $ruleNode, $visitArgs ){
		$visitArgs['visitDeeper'] = false;
		return $ruleNode;
	}

	function visitMixinDefinition( $mixinDefinitionNode, $visitArgs ){
		$visitArgs['visitDeeper'] = false;
		return $mixinDefinitionNode;
	}

	function visitRuleset( $rulesetNode, $visitArgs ){
		if( $rulesetNode->root ){
			return;
		}
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