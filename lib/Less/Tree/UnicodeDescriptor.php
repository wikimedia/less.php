<?php
/**
 * @private
 */
class Less_Tree_UnicodeDescriptor extends Less_Tree {

	public $value;

	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * @see Less_Tree::genCSS
	 */
	public function genCSS( $output ) {
		$output->add( $this->value );
	}
}
