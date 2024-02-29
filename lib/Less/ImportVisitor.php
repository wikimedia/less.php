<?php

class Less_ImportVisitor extends Less_VisitorReplacing {

	public $env;
	public $variableImports = [];

	public $_currentDepth = 0;
	public $importItem;

	public function __construct( $env ) {
		parent::__construct();
		// NOTE: Upstream creates a new environment/context here. We re-use the main one instead.
		// This makes Less_Environment->addParsedFile() easier to support (which is custom to Less.php)
		$this->env = $env;
		// NOTE: Upstream `importCount` is not here, appears unused.
		// NOTE: Upstream `isFinished` is not here, we simply call tryRun() once at the end.
		// NOTE: Upstream `onceFileDetectionMap` is instead Less_Environment->isFileParsed.
		// NOTE: Upstream `ImportSequencer` logic is directly inside ImportVisitor for simplicity.
	}

	public function run( &$root ) {
		$root = $this->visitObj( $root );
		$this->tryRun();
	}

	public function visitImport( &$importNode, &$visitDeeper ) {
		$inlineCSS = $importNode->options['inline'];

		if ( !$importNode->css || $inlineCSS ) {

			$env = clone $this->env;
			$importParent = $env->frames[0];
			if ( $importNode->isVariableImport() ) {
				$this->addVariableImport( [
					'function' => 'processImportNode',
					'args' => [ $importNode, $env, $importParent ]
				] );
			} else {
				$this->processImportNode( $importNode, $env, $importParent );
			}
		}
		$visitDeeper = false;
		return $importNode;
	}

	public function processImportNode( &$importNode, $env, &$importParent ) {
		$evaldImportNode = $inlineCSS = $importNode->options['inline'];

		$evaldImportNode = $importNode->compileForImport( $env );
		// get path & uri
		$callback = Less_Parser::$options['import_callback'];
		$path_and_uri = is_callable( $callback ) ? $callback( $evaldImportNode ) : null;
		if ( !$path_and_uri ) {
			$path_and_uri = $evaldImportNode->PathAndUri();
		}
		if ( $path_and_uri ) {
			[ $full_path, $uri ] = $path_and_uri;
		} else {
			$full_path = $uri = $evaldImportNode->getPath();
		}
		'@phan-var string $full_path';

		if ( $evaldImportNode && ( !$evaldImportNode->css || $inlineCSS ) ) {

			if ( ( isset( $importNode->options['multiple'] ) && $importNode->options['multiple'] ) ) {
				$env->importMultiple = true;
			}

			for ( $i = 0; $i < count( $importParent->rules ); $i++ ) {
				if ( $importParent->rules[$i] === $importNode ) {
					$importParent->rules[$i] = $evaldImportNode;
					break;
				}
			}

			if ( $evaldImportNode->options['inline'] ) {
				$env->addParsedFile( $full_path );
				$contents = new Less_Tree_Anonymous( file_get_contents( $full_path ), 0, [], true, true );

				if ( $evaldImportNode->features ) {
					return new Less_Tree_Media( [ $contents ], $evaldImportNode->features->value );
				}

				return [ $contents ];
			}

			// optional (need to be before "CSS" to support optional CSS imports. CSS should be checked only if empty($this->currentFileInfo))
			if ( isset( $evaldImportNode->options['optional'] ) && $evaldImportNode->options['optional'] && !file_exists( $full_path )
			&& ( !$evaldImportNode->css || !empty( $evaldImportNode->currentFileInfo ) ) ) {
				return [];
			}

			// css ?
			if ( $evaldImportNode->css ) {
				$features = ( $evaldImportNode->features ? $evaldImportNode->features->compile( $env ) : null );
				return new Less_Tree_Import( $evaldImportNode->compilePath( $env ), $features, $evaldImportNode->options, $evaldImportNode->index );
			}

			$evaldImportNode->ParseImport( $full_path, $uri, $env );
			$importNode = $evaldImportNode;
			return $importNode;
		} else {
			$this->tryRun();
		}
	}

	public function addVariableImport( $callback ) {
		array_push( $this->variableImports, $callback );
	}

	public function tryRun() {
		while ( true ) {
			if ( count( $this->variableImports ) === 0 ) {
				break;
			}
			$variableImport = $this->variableImports[0];

			$this->variableImports = array_slice( $this->variableImports, 1 );
			$function = $variableImport['function'];

			$this->$function( ...$variableImport["args"] );
		}
	}

	public function visitRule( $ruleNode, &$visitDeeper ) {
		$visitDeeper = false;
		return $ruleNode;
	}

	public function visitDirective( $directiveNode, $visitArgs ) {
		array_unshift( $this->env->frames, $directiveNode );
		return $directiveNode;
	}

	public function visitDirectiveOut( $directiveNode ) {
		array_shift( $this->env->frames );
	}

	public function visitMixinDefinition( $mixinDefinitionNode, $visitArgs ) {
		array_unshift( $this->env->frames, $mixinDefinitionNode );
		return $mixinDefinitionNode;
	}

	public function visitMixinDefinitionOut( $mixinDefinitionNode ) {
		array_shift( $this->env->frames );
	}

	public function visitRuleset( $rulesetNode, $visitArgs ) {
		array_unshift( $this->env->frames, $rulesetNode );
		return $rulesetNode;
	}

	public function visitRulesetOut( $rulesetNode ) {
		array_shift( $this->env->frames );
	}

	public function visitMedia( &$mediaNode, $visitArgs ) {
		$mediaNode->allExtends = [];
		array_unshift( $this->env->frames, $mediaNode->allExtends );
		return $mediaNode;
	}

	public function visitMediaOut( &$mediaNode ) {
		array_shift( $this->env->frames );
	}

}
