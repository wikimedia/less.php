<?php
declare( strict_types = 1 );

/**
 * @private
 * @see less-3.13.1.js#AtRule.prototype
 */
class Less_Tree_AtRule extends Less_Tree implements Less_Tree_HasValueProperty {
	/** @var string */
	public $name;
	/** @var Less_Tree|null */
	public $value;
	/** @var Less_Tree_Ruleset[]|null */
	public $rules;
	/** @var int|null */
	public $index;
	/** @var bool */
	public $isReferenced;
	/** @var bool */
	public $isRooted;
	/** @var array|null */
	public $currentFileInfo;
	/** @var mixed|null */
	public $debugInfo;

	public function __construct(
		$name,
		$value = null,
		$rules = null,
		$index = null,
		$isRooted = false,
		$currentFileInfo = null,
		$debugInfo = null,
		$isReferenced = false
	) {
		$this->name = $name;
		// TODO: Less.js 3.13 handles `$value instanceof Less_Tree` and creates Anonymous here.
		$this->value = $value;

		if ( $rules !== null ) {
			if ( is_array( $rules ) ) {
				$this->rules = $rules;
			} else {
				$this->rules = [ $rules ];
				$this->rules[0]->selectors = $this->emptySelectors();
			}

			foreach ( $this->rules as $rule ) {
				$rule->allowImports = true;
			}
			// TODO: Less.js 3.13 handles setParent() here
		}

		$this->index = $index;
		$this->isRooted = $isRooted;
		$this->currentFileInfo = $currentFileInfo;
		$this->debugInfo = $debugInfo;
		$this->allowRoot = true;
		$this->isReferenced = $isReferenced;
	}

	public function accept( $visitor ) {
		if ( $this->rules ) {
			$this->rules = $visitor->visitArray( $this->rules );
		}
		if ( $this->value ) {
			$this->value = $visitor->visitObj( $this->value );
		}
	}

	public function isRulesetLike() {
		return $this->rules || !$this->isCharset();
	}

	public function isCharset() {
		return $this->name === "@charset";
	}

	public function genCSS( ?Less_Environment $context, $output ) {
		$output->add( $this->name, $this->currentFileInfo, $this->index );
		if ( $this->value ) {
			$output->add( ' ' );
			$this->value->genCSS( $context, $output );
		}
		if ( $this->rules !== null ) {
			'@phan-var Less_Environment $context';
			self::outputRuleset( $context, $output, $this->rules );
		} else {
			$output->add( ';' );
		}
	}

	public function compile( $env ) {
		$value = $this->value;
		$rules = $this->rules;

		// Media stored inside other directive should not bubble over it
		// backup media bubbling information
		$mediaPathBackup = $env->mediaPath;
		$mediaPBlocksBackup = $env->mediaBlocks;
		// Deleted media bubbling information
		$env->mediaPath = [];
		$env->mediaBlocks = [];

		if ( $value ) {
			$value = $value->compile( $env );
		}

		if ( $rules ) {
			// Assuming that there is only one rule at this point - that is how parser constructs the rule
			$rules = $rules[0]->compile( $env );
			$rules->root = true;
		}

		// Restore media bubbling information
		$env->mediaPath = $mediaPathBackup;
		$env->mediaBlocks = $mediaPBlocksBackup;

		return new self( $this->name, $value, $rules, $this->index, $this->isRooted, $this->currentFileInfo, $this->debugInfo, $this->isReferenced );
	}

	public function variable( $name ) {
		if ( $this->rules ) {
			return $this->rules[0]->variable( $name );
		}
	}

	public function find( $selector ) {
		// TODO: Less.js 3.13.1 adds multiple variadic arguments here
		if ( $this->rules ) {
			return $this->rules[0]->find( $selector, $this );
		}
	}

	// TODO: Implement less-3.13.1.js#AtRule.prototype.rulesets
	// Unused?

	/**
	 * @see less-3.13.1.js#AtRule.prototype.outputRuleset
	 * @param Less_Environment $context
	 * @param Less_Output $output
	 * @param Less_Tree_Ruleset[] $rules
	 */
	public static function outputRuleset( Less_Environment $context, $output, $rules ) {
		$ruleCnt = count( $rules );
		Less_Environment::$tabLevel++;

		// Compressed
		if ( $context->compress ) {
			$output->add( '{' );
			for ( $i = 0; $i < $ruleCnt; $i++ ) {
				$rules[$i]->genCSS( $context, $output );
			}

			$output->add( '}' );
			Less_Environment::$tabLevel--;
			return;
		}

		// Non-compressed
		$tabSetStr = "\n" . str_repeat( Less_Parser::$options['indentation'], Less_Environment::$tabLevel - 1 );
		$tabRuleStr = $tabSetStr . Less_Parser::$options['indentation'];
		if ( !$ruleCnt ) {
			$output->add( " {" . $tabSetStr . '}' );
		} else {
			$output->add( " {" . $tabRuleStr );
			$rules[0]->genCSS( $context, $output );
			for ( $i = 1; $i < $ruleCnt; $i++ ) {
				$output->add( $tabRuleStr );
				$rules[$i]->genCSS( $context, $output );
			}
			$output->add( $tabSetStr . '}' );
		}
		Less_Environment::$tabLevel--;
	}

	public function markReferenced() {
		$this->isReferenced = true;
		if ( $this->rules ) {
			Less_Tree::ReferencedArray( $this->rules );
		}
	}

	public function getIsReferenced() {
		return !isset( $this->currentFileInfo['reference'] ) || !$this->currentFileInfo['reference'] || $this->isReferenced;
	}

	public function emptySelectors() {
		$el = new Less_Tree_Element( '', '&', $this->index, $this->currentFileInfo );
		$sels = [ new Less_Tree_Selector( [ $el ], [], null, $this->index, $this->currentFileInfo ) ];
		$sels[0]->mediaEmpty = true;
		return $sels;
	}

}
