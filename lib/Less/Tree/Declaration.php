<?php
declare( strict_types = 1 );

/**
 * @private
 * @see less-3.13.1.js#Declaration.prototype
 */
class Less_Tree_Declaration extends Less_Tree implements Less_Tree_HasValueProperty {

	/** @var string|array<Less_Tree_Keyword|Less_Tree_Variable> */
	public $name;
	/** @var Less_Tree */
	public $value;
	/** @var string */
	public $important;
	/** @var null|false|string */
	public $merge;
	/** @var int|null */
	public $index;
	/** @var bool */
	public $inline;
	/** @var bool */
	public $variable;
	/** @var array|null */
	public $currentFileInfo;

	/**
	 * In the upstream `parsed` is stored in `Node`, but Less_Tree_Declaration is the only place
	 * that make use of it.
	 * @see less-3.13.1.js#Node.parsed
	 * @var bool
	 */
	public $parsed = false;

	/**
	 * @param string|array<Less_Tree_Keyword|Less_Tree_Variable> $name
	 * @param Less_Tree|string|null $value
	 * @param null|false|string $important
	 * @param null|false|string $merge
	 * @param int|null $index
	 * @param array|null $currentFileInfo
	 * @param bool $inline
	 * @param bool|null $variable
	 */
	public function __construct(
		$name,
		$value = null,
		$important = null,
		$merge = null,
		$index = null,
		$currentFileInfo = null,
		$inline = false,
		$variable = null
	) {
		$this->name = $name;
		$this->value = ( $value instanceof Less_Tree )
			? $value
			: new Less_Tree_Value( [ $value ? new Less_Tree_Anonymous( $value ) : null ] );
		$this->important = $important ? ' ' . trim( $important ) : '';
		$this->merge = $merge;
		$this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
		$this->inline = $inline;
		$this->variable = $variable ?? ( is_string( $name ) && $name[0] === '@' );
		$this->allowRoot = true;
		// TODO: Implement Less_Tree#setParent() and call here
	}

	public function accept( $visitor ) {
		$this->value = $visitor->visitObj( $this->value );
	}

	/**
	 * @see less-3.13.1.js#Declaration.prototype.genCSS
	 */
	public function genCSS( ?Less_Environment $context, $output ) {
		'@phan-var Less_Environment $context';
		$output->add( $this->name . ( $context->compress ? ':' : ': ' ), $this->currentFileInfo, $this->index );
		try {
			$this->value->genCSS( $context, $output );

		} catch ( Less_Exception_Parser $e ) {
			$e->index = $this->index;
			$e->currentFile = $this->currentFileInfo;
			throw $e;
		}
		$output->add(
			$this->important . ( ( $this->inline || ( Less_Environment::$lastRule && $context->compress ) ) ? "" : ";" ),
			$this->currentFileInfo,
			$this->index
		);
	}

	/**
	 * @see less-3.13.1.js#Declaration.prototype.eval
	 * @param Less_Environment $env
	 * @return self
	 */
	public function compile( $env ) {
		$mathBypass = false;
		$name = $this->name;
		$variable = $this->variable;
		if ( is_array( $name ) ) {
			// expand 'primitive' name directly to get
			// things faster (~10% for benchmark.less):
			if ( count( $name ) === 1 && $name[0] instanceof Less_Tree_Keyword ) {
				$name = $name[0]->value;
			} else {
				$name = $this->evalName( $env, $name );
			}
			$variable = false; // never treat expanded interpolation as new variable name
		}

		if ( $name === "font" && $env->math === Less_Environment::MATH_ALWAYS ) {
			$mathBypass = true;
			$prevMath = $env->math;
			$env->math = Less_Environment::MATH_PARENS_DIVISION;
		}

		try {
			$env->importantScope[] = [];
			$evaldValue = $this->value->compile( $env );

			if ( !$this->variable && $evaldValue instanceof Less_Tree_DetachedRuleset ) {
				throw new Less_Exception_Compiler( "Rulesets cannot be evaluated on a property.", null, $this->index, $this->currentFileInfo );
			}

			$important = $this->important;
			$importantResult = array_pop( $env->importantScope );

			if ( !$important && isset( $importantResult['important'] ) && $importantResult['important'] ) {
				$important = $importantResult['important'];
			}

			// TODO: Once we have getIndex(), fileInfo(), and setParent() in Less_Tree, use it here
			return new Less_Tree_Declaration(
				$name,
				$evaldValue,
				$important,
				$this->merge,
				$this->index,
				$this->currentFileInfo,
				$this->inline,
				$variable,
			);

		} catch ( Less_Exception_Parser $e ) {
			if ( !is_numeric( $e->index ) ) {
				$e->index = $this->index;
				$e->currentFile = $this->currentFileInfo;
				$e->genMessage();
			}
			throw $e;
		} finally {
			if ( $mathBypass ) {
				// @phan-suppress-next-line PhanPossiblyUndeclaredVariable, PhanPossiblyNullTypeMismatchProperty
				$env->math = $prevMath;
			}
		}
	}

	private function evalName( $env, $name ) {
		$output = new Less_Output();
		foreach ( $name as $n ) {
			$n->compile( $env )->genCSS( $env, $output );
		}
		return $output->toString();
	}

	public function makeImportant() {
		// TODO: Once we have getIndex(), fileInfo(), and setParent() in Less_Tree, use it here
		return new self( $this->name, $this->value, '!important', $this->merge, $this->index, $this->currentFileInfo, $this->inline );
	}

	/**
	 * @see less-2.5.3.js#Declaration.prototype.markReferenced
	 * TODO: remove markReferenced and implement $visibilityInfo
	 */
	public function mark( $value ) {
		if ( !is_array( $this->value ) ) {

			if ( method_exists( $value, 'markReferenced' ) ) {
				// @phan-suppress-next-line PhanUndeclaredMethod
				$value->markReferenced();
			}
		} else {
			foreach ( $this->value as $v ) {
				$this->mark( $v );
			}
		}
	}

	public function markReferenced() {
		if ( $this->value ) {
			$this->mark( $this->value );
		}
	}

}
