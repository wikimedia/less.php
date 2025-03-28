<?php
/**
 * @method int match($other)
 */
class Less_Tree_Selector extends Less_Tree {
	/** @var Less_Tree_Element[] */
	public $elements;
	/** @var Less_Tree_Condition|null */
	public $condition;
	/** @var Less_Tree_Extend[] */
	public $extendList = [];
	/** @var int|null */
	public $index;
	/** @var bool */
	public $evaldCondition = false;
	/** @var array|null */
	public $currentFileInfo = [];
	/** @var null|bool */
	public $isReferenced;
	/** @var null|bool */
	public $mediaEmpty;
	/** @var int */
	public $elements_len = 0;
	/** @var string[] */
	public $_oelements;
	/** @var array<string,true> */
	public $_oelements_assoc;
	/** @var int */
	public $_oelements_len;
	/** @var bool */
	public $cacheable = true;

	public function __construct( $elements, $extendList = [], $condition = null, $index = null, $currentFileInfo = null, ?bool $isReferenced = null ) {
	}

	public function accept( $visitor ) {
	}

	public function createDerived( $elements, $extendList = null, $evaldCondition = null ) {
	}

	// https://github.com/phan/phan/issues/4751
	// public function match( $other ) {
	// }

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
