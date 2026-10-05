<?php
declare( strict_types = 1 );

/**
 * @private
 */
class Less_Tree_UnicodeDescriptor extends Less_Tree implements Less_Tree_HasValueProperty {

	/** @var string */
	public $value;

	public function __construct( $value ) {
		$this->value = $value;
	}

	public function genCSS( ?Less_Environment $context, $output ) {
		$output->add( $this->value );
	}
}
