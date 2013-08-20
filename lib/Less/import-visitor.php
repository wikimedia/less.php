<?php

namespace Less;

class importVisitor{

	public $_visitor;
	public $_importer;

	function __construct( $root, $importer = null ){
		$this->_visitor = new \Less\visitor($this);
		$this->_importer = $importer;
		$this->_visitor->visit($root);
		$this->env = new \Less\Environment();
	}

	function visitImport($importNode, $visitArgs ){
		/*
		if (!importNode.css) {
			importNode = importNode.evalForImport(this.env);
			this._importer.push(importNode.getPath(), function (e, root, imported) {
				if (e) { e.index = importNode.index; }
				if (imported && importNode.once) { importNode.skip = imported; }
				importNode.root = root || new(tree.Ruleset)([], []);
			});
		}
		visitArgs.visitDeeper = false;
		*/

		return $importNode;
	}

	function visitRule( $ruleNode, $visitArgs ){
		$visitArgs['visitDeeper'] = false;
		return $ruleNode;
	}
}