<?php
declare( strict_types = 1 );

/**
 * @private
 */
class Less_Tree_Value extends Less_Tree implements Less_Tree_HasValueProperty {

	/** @var Less_Tree[] */
	public $value;
	/** @var int|null */
	public $index;
	/** @var array */
	public $currentFileInfo;

	/**
	 * @param array<Less_Tree> $value
	 */
	public function __construct( $value, $index = null ) {
		$this->value = $value;
		$this->index = $index;
	}

	public function accept( $visitor ) {
		$this->value = $visitor->visitArray( $this->value );
	}

	public function compile( $env ) {
		$ret = [];
		$i = 0;
		foreach ( $this->value as $i => $v ) {
			$ret[] = $v->compile( $env );
		}
		if ( $i > 0 ) {
			return new self( $ret );
		}
		return $ret[0];
	}

	/**
	 * @see less-3.13.1.js#Value.prototype.genCSS
	 */
	public function genCSS( ?Less_Environment $context, $output ) {
		$len = count( $this->value );
		for ( $i = 0; $i < $len; $i++ ) {
			$this->value[$i]->genCSS( $context, $output );
			if ( $i + 1 < $len ) {
				$output->add( ( $context && $context->compress ) ? ',' : ', ' );
			}
		}
	}

}
