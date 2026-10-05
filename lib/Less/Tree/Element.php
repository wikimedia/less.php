<?php
declare( strict_types = 1 );

/**
 * @private
 */
class Less_Tree_Element extends Less_Tree implements Less_Tree_HasValueProperty {
	/** @var array<string,true> */
	private const noSpaceCombinators = [
		'' => true,
		' ' => true,
		'|' => true
	];

	/** @var string */
	public $combinatorVal;
	/** @var bool Whether combinator is null (represented by empty string) or child (single space) */
	public $combinatorIsEmptyOrWhitespace;
	/** @var string|Less_Tree */
	public $value;
	/** @var int|null */
	public $index;
	/** @var array|null */
	public $currentFileInfo;

	/**
	 * @param null|string $combinator
	 * @param string|Less_Tree $value
	 * @param int|null $index
	 * @param array|null $currentFileInfo
	 */
	public function __construct( $combinator, $value, $index = null, $currentFileInfo = null ) {
		// see less-3.13.1.js#Combinator.prototype
		if ( $combinator === ' ' ) {
			$this->combinatorVal = $combinator;
			$this->combinatorIsEmptyOrWhitespace = true;
		} else {
			$this->combinatorVal = trim( $combinator ?? '' );
			$this->combinatorIsEmptyOrWhitespace = $this->combinatorVal === '';
		}

		$this->value = is_string( $value ) ? trim( $value ) : $value;
		$this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
	}

	public function accept( $visitor ) {
		if ( $this->value instanceof Less_Tree ) {
			$this->value = $visitor->visitObj( $this->value );
		}
	}

	public function compile( $env ) {
		return new self(
			$this->combinatorVal,
			( $this->value instanceof Less_Tree ? $this->value->compile( $env ) : $this->value ),
			$this->index,
			$this->currentFileInfo
		);
	}

	public function genCSS( ?Less_Environment $context, $output ) {
		$output->add( $this->toCSS( $context ), $this->currentFileInfo, $this->index );
	}

	public function toCSS( ?Less_Environment $context = null ) {
		$context ??= new Less_Environment();
		$value = $this->value;
		$firstSelector = $context->firstSelector;
		if ( $value instanceof Less_Tree_Paren ) {
			// selector in parens should not be affected by outer selector flags
			// breaks only interpolated selectors - see https://github.com/less/less.js/issues/1973
			$context->firstSelector = true;
		}
		$value = ( $value instanceof Less_Tree ) ? $value->toCSS( $context ) : $value;
		$context->firstSelector = $firstSelector;

		if ( $value === '' && ( $this->combinatorVal[0] ?? '' ) === '&' ) {
			return '';
		} else {
			// see less-3.13.1.js#Combinator.prototype.genCSS
			$spaceOrEmpty = ( $context->compress || ( self::noSpaceCombinators[$this->combinatorVal] ?? false ) )
				? ''
				: ' ';
			return $spaceOrEmpty . $this->combinatorVal . $spaceOrEmpty . $value;
		}
	}
}
