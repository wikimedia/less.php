<?php
/**
 * @private
 * @see less-2.5.3.js#Anonymous.prototype
 */
class Less_Tree_Anonymous extends Less_Tree implements Less_Tree_HasValueProperty {
	public $value;
	public $quote;
	public $index;
	public $mapLines;
	public $currentFileInfo;
	/** @var bool */
	public $rulesetLike;

	/**
	 * @param string $value
	 * @param int|null $index
	 * @param array|null $currentFileInfo
	 * @param bool|null $mapLines
	 * @param bool $rulesetLike
	 */
	public function __construct( $value, $index = null, $currentFileInfo = null, $mapLines = null, $rulesetLike = false ) {
		$this->value = $value;
		$this->index = $index;
		$this->mapLines = $mapLines;
		$this->currentFileInfo = $currentFileInfo;
		$this->rulesetLike = $rulesetLike;
	}

	public function compile( $env ) {
		return new self( $this->value, $this->index, $this->currentFileInfo, $this->mapLines );
	}

	public function compare( $x ) {
		if ( !is_object( $x ) ) {
			return -1;
		}

		$left = $this->toCSS();
		$right = $x->toCSS();

		if ( $left === $right ) {
			return 0;
		}

		return $left < $right ? -1 : 1;
	}

	public function isRulesetLike() {
		return $this->rulesetLike;
	}

	/**
	 * @see Less_Tree::genCSS
	 */
	public function genCSS( $output ) {
		$output->add( $this->value, $this->currentFileInfo, $this->index, $this->mapLines );
	}

	public function toCSS() {
		return $this->value;
	}

}
