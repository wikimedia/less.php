<?php
/**
 * @private
 */
class Less_Tree_Comment extends Less_Tree implements Less_Tree_HasValueProperty {

	public $value;
	public $isLineComment;
	public $isReferenced;
	public $currentFileInfo;

	public function __construct( $value, $isLineComment, $index = null, $currentFileInfo = null ) {
		$this->value = $value;
		$this->isLineComment = (bool)$isLineComment;
		$this->currentFileInfo = $currentFileInfo;
	}

	/**
	 * @see Less_Tree::genCSS
	 */
	public function genCSS( $output ) {
		// if( $this->debugInfo ){
			//$output->add( tree.debugInfo($env, $this), $this->currentFileInfo, $this->index);
		//}
		$output->add( $this->value );
	}

	public function toCSS() {
		return Less_Parser::$options['compress'] ? '' : $this->value;
	}

	public function isSilent() {
		$isReference = ( $this->currentFileInfo && isset( $this->currentFileInfo['reference'] ) && ( !isset( $this->isReferenced ) || !$this->isReferenced ) );
		$isCompressed = Less_Parser::$options['compress'] && ( $this->value[2] ?? '' ) !== "!";
		return $this->isLineComment || $isReference || $isCompressed;
	}

	public function markReferenced() {
		$this->isReferenced = true;
	}

}
