<?php
declare( strict_types = 1 );

/**
 * @private
 */
class Less_Tree_Paren extends Less_Tree implements Less_Tree_HasValueProperty {

	/** @var Less_Tree */
	public $value;

	/**
	 * @param Less_Tree $value
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	public function accept( $visitor ) {
		$this->value = $visitor->visitObj( $this->value );
	}

	public function genCSS( ?Less_Environment $context, $output ) {
		$output->add( '(' );
		$this->value->genCSS( $context, $output );
		$output->add( ')' );
	}

	public function compile( $env ) {
		return new self( $this->value->compile( $env ) );
	}

}
