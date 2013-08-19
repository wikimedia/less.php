<?php

namespace Less;

class importVisitor{

	public $_visitor;
	public $_importer;

	function __construct( $root, $importer = null ){
		$this->_visitor = new \Less\visitor($this);
		$this->_importer = $importer;
		$this->_visitor->visit($root);
	}

	function visitImport($importNode, $visitArgs ){
		return $importNode;
	}

	function visitRule( $ruleNode, $visitArgs ){
		$visitArgs['visitDeeper'] = false;
		return $ruleNode;
	}
}