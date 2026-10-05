<?php
declare( strict_types = 1 );

/**
 * @private
 */
class Less_Tree_Keyword extends Less_Tree implements Less_Tree_HasValueProperty {

	/** @var string */
	public $value;

	/**
	 * @param string $value
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	public function genCSS( ?Less_Environment $context, $output ) {
		if ( $this->value === '%' ) {
			throw new Less_Exception_Compiler( "Invalid % without number" );
		}

		$output->add( $this->value );
	}
}
