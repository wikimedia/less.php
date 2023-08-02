<?php

class Less_Tree_Selector extends Less_Tree {
	public $elements;
	public $condition;
	public $extendList = [];
	public $_css;
	public $index;
	public $evaldCondition = false;
	public $currentFileInfo = [];
	public $isReferenced;
	public $mediaEmpty;
	public $elements_len = 0;
	public $_oelements;
	public $_oelements_assoc;
	public $_oelements_len;
	public $cacheable = true;

	public function __construct( $elements, $extendList = [], $condition = null, $index = null, $currentFileInfo = null, bool $isReferenced = null ) {
	}

	public function accept( $visitor ) {
	}

	public function createDerived( $elements, $extendList = null, $evaldCondition = null ) {
	}

	public function match( $other ) {
	}

	public function CacheElements() {
	}

	public function isJustParentSelector() {
	}

	public function compile( $env ) {
	}

	public function genCSS( $output, $firstSelector = true ) {
	}

	public function markReferenced() {
	}

	public function getIsReferenced() {
	}

	public function getIsOutput() {
	}

}
