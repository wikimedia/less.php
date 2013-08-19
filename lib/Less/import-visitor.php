<?php

namespace Less;

class importVisitor{

	public $_visitor;

	function __construct( $root ){
		$this->_visitor = new \Less\visitor($this);
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