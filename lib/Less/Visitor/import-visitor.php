<?php

namespace Less;

class importVisitor{

	public $_visitor;
	public $_importer;
	public $isReplacing = true;

	function __construct( $importer = null ){
		$this->_visitor = new \Less\visitor($this);
		$this->_importer = $importer;
		$this->env = new \Less\Environment();
		$this->_visitor->visit($root);
	}

	function visitImport($importNode, $visitArgs ){
		/*
		if (!importNode.css) {
			importNode = importNode.evalForImport(this.env);
			this._importer.push(importNode.getPath(), function (e, root, imported) {
				if (e) { e.index = importNode.index; }
				if (imported && !importNode.options.multiple) { importNode.skip = imported; }
				importNode.root = root || new(tree.Ruleset)([], []);
			});
		}
		visitArgs.visitDeeper = false;
		*/

		return $importNode;
	}

	function run( $root ){
		// process the contents
		$this->_visitor->visit($root);

		$this->isFinished = true;

		if( $this->importCount === 0) {
			$this->_finish();
		}
	}

	function visitRule( $ruleNode, $visitArgs ){
		$visitArgs['visitDeeper'] = false;
		return $ruleNode;
	}

	function visitDirective($directiveNode, $visitArgs){
		array_unshift($this->env->frames,$directiveNode);
		return $directiveNode;
	}

	function visitDirectiveOut($directiveNode) {
		array_shift($this->env->frames);
	}

	function visitMixinDefinition($mixinDefinitionNode, $visitArgs) {
		array_unshift($this->env->frames,$mixinDefinitionNode);
		return $mixinDefinitionNode;
	}

	function visitMixinDefinitionOut($mixinDefinitionNode) {
		array_shift($this->env->frames);
	}

	function visitRuleset($rulesetNode, $visitArgs) {
		array_unshift($this->env->frames,$rulesetNode);
		return $rulesetNode;
	}

	function visitRulesetOut($rulesetNode) {
		array_shift($this->env->frames);
	}

	function visitMedia($mediaNode, $visitArgs) {
		array_unshift($this->env->frames, $mediaNode->ruleset);
		return $mediaNode;
	}

	function visitMediaOut($mediaNode) {
		array_shift($this->env->frames);
	}
}