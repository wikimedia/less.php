<?php
/**
 * CSS `@import` node
 *
 * The general strategy here is that we don't want to wait
 * for the parsing to be completed, before we start importing
 * the file. That's because in the context of a browser,
 * most of the time will be spent waiting for the server to respond.
 *
 * On creation, we push the import path to our import queue, though
 * `import,push`, we also pass it a callback, which it'll call once
 * the file has been fetched, and parsed.
 *
 * @private
 * @see less-2.5.3.js#Import.prototype
 */
class Less_Tree_Import extends Less_Tree {

	public $options;
	public $index;
	public $path;
	public $features;
	public $currentFileInfo;
	public $css;
	/** @var bool|null This is populated by Less_ImportVisitor */
	public $doSkip = false;
	/** @var string|null This is populated by Less_ImportVisitor */
	public $importedFilename;
	/**
	 * This is populated by Less_ImportVisitor.
	 *
	 * For imports that use "inline", this holds a raw string.
	 *
	 * @var string|Less_Tree_Ruleset|null
	 */
	public $root;

	public function __construct( $path, $features, array $options, $index, $currentFileInfo = null ) {
		$this->options = $options + [ 'inline' => false, 'optional' => false, 'multiple' => false ];
		$this->index = $index;
		$this->path = $path;
		$this->features = $features;
		$this->currentFileInfo = $currentFileInfo;

		if ( isset( $this->options['less'] ) || $this->options['inline'] ) {
			$this->css = !isset( $this->options['less'] ) || !$this->options['less'] || $this->options['inline'];
		} else {
			$pathValue = $this->getPath();
			// Leave any ".css" file imports as literals for the browser.
			// Also leave any remote HTTP resources as literals regardless of whether
			// they contain ".css" in their filename.
			if ( $pathValue && (
				preg_match( '/[#\.\&\?\/]css([\?;].*)?$/', $pathValue )
				|| preg_match( '/^(https?:)?\/\//i', $pathValue )
			) ) {
				$this->css = true;
			}
		}
	}

//
// The actual import node doesn't return anything, when converted to CSS.
// The reason is that it's used at the evaluation stage, so that the rules
// it imports can be treated like any other rules.
//
// In `eval`, we make sure all Import nodes get evaluated, recursively, so
// we end up with a flat structure, which can easily be imported in the parent
// ruleset.
//

	public function accept( $visitor ) {
		if ( $this->features ) {
			$this->features = $visitor->visitObj( $this->features );
		}
		$this->path = $visitor->visitObj( $this->path );

		if ( !$this->options['inline'] && $this->root ) {
			$this->root = $visitor->visit( $this->root );
		}
	}

	public function genCSS( $output ) {
		// TODO: this.path.currentFileInfo.reference === undefined
		// Related: T352862 (test/less-2.5.3/less/import-reference.less)
		if ( $this->css ) {
			$output->add( '@import ', $this->currentFileInfo, $this->index );
			$this->path->genCSS( $output );
			if ( $this->features ) {
				$output->add( ' ' );
				$this->features->genCSS( $output );
			}
			$output->add( ';' );
		}
	}

	/**
	 * @return string|null
	 */
	public function getPath() {
		if ( $this->path instanceof Less_Tree_Quoted ) {
			$path = $this->path->value;
			// TODO: This should be moved to ImportVisitor using $tryAppendLessExtension
			// to match upstream. However, to remove this from here we have to first
			// fix differences with how/when 'import_callback' is executed.
			$path = ( isset( $this->css ) || preg_match( '/(\.[a-z]*$)|([\?;].*)$/', $path ) ) ? $path : $path . '.less';

		// During the first pass, Less_Tree_Url may contain a Less_Tree_Variable (not yet expanded),
		// and thus has no value property defined yet. Return null until we reach the next phase.
		// https://github.com/wikimedia/less.php/issues/29
		// TODO: Do we still need this now that we have ImportVisitor?
		} elseif ( $this->path instanceof Less_Tree_Url && !( $this->path->value instanceof Less_Tree_Variable ) ) {
			$path = $this->path->value->value;
		} else {
			return null;
		}

		// remove query string and fragment
		return preg_replace( '/[\?#][^\?]*$/', '', $path );
	}

	public function isVariableImport() {
		$path = $this->path;
		if ( $path instanceof Less_Tree_Url ) {
			$path = $path->value;
		}
		if ( $path instanceof Less_Tree_Quoted ) {
			return $path->containsVariables();
		}
		return true;
	}

	public function compileForImport( $env ) {
		// TODO: We might need upstream `if (path instanceof URL) { path = path.value; }`
		return new self( $this->path->compile( $env ), $this->features, $this->options, $this->index, $this->currentFileInfo );
	}

	public function compilePath( $env ) {
		$path = $this->path->compile( $env );
		$rootpath = $this->currentFileInfo['rootpath'] ?? null;

		if ( !( $path instanceof Less_Tree_Url ) ) {
			if ( $rootpath ) {
				$pathValue = $path->value;
				// Add the base path if the import is relative
				if ( $pathValue && Less_Environment::isPathRelative( $pathValue ) ) {
					$path->value = $this->currentFileInfo['uri_root'] . $pathValue;
				}
			}
			$path->value = Less_Environment::normalizePath( $path->value );
		}

		return $path;
	}

	/**
	 * @param Less_Environment $env
	 * @see less-2.5.3.js#Import.prototype.eval
	 */
	public function compile( $env ) {
		$features = ( $this->features ? $this->features->compile( $env ) : null );

		// TODO: Upstream doesn't do path resolution here. The reason we need it here is
		// because skip() takes a $path_and_uri argument. Once the TODO in ImportVisitor
		// about Less_Tree_Import::PathAndUri() is fixed, this can be removed by letting
		// skip() call $this->PathAndUri() on its own.
		// get path & uri
		$path_and_uri = $env->callImportCallback( $this );
		if ( !$path_and_uri ) {
			$path_and_uri = $this->PathAndUri();
		}
		if ( $path_and_uri ) {
			[ $full_path, $uri ] = $path_and_uri;
		} else {
			$full_path = $uri = $this->getPath();
		}
		'@phan-var string $full_path';

		// import once
		if ( $this->skip( $full_path, $env ) ) {
			return [];
		}

		if ( $this->options['inline'] ) {
			$contents = new Less_Tree_Anonymous( $this->root, 0,
				[
					'filename' => $this->importedFilename,
					'reference' => $this->currentFileInfo['reference'] ?? null,
				],
				true,
				true
				// TODO: We might need upstream's bool $referenced param to Anonymous
			);
			return $this->features
				? new Less_Tree_Media( [ $contents ], $this->features->value )
				: [ $contents ];
		} elseif ( $this->css ) {
			$newImport = new self( $this->compilePath( $env ), $features, $this->options, $this->index );
			// TODO: We might need upstream's `if (!newImport.css && this.error) { throw this.error;`
			return $newImport;
		} else {
			$ruleset = new Less_Tree_Ruleset( null, $this->root->rules );

			$ruleset->evalImports( $env );

			return $this->features
				? new Less_Tree_Media( $ruleset->rules, $this->features->value )
				: $ruleset->rules;

		}
	}

	/**
	 * Using the import directories, get the full absolute path and uri of the import
	 *
	 * @see less-node/FileManager.getPath https://github.com/less/less.js/blob/v2.5.3/lib/less-node/file-manager.js#L70
	 */
	public function PathAndUri() {
		$evald_path = $this->getPath();
		// TODO: Move callImportCallback() and getPath() fallback logic from callers
		//       to here so that PathAndUri() is equivalent to upstream fileManager.getPath()

		if ( $evald_path ) {

			$import_dirs = [];

			if ( Less_Environment::isPathRelative( $evald_path ) ) {
				// if the path is relative, the file should be in the current directory
				if ( $this->currentFileInfo ) {
					$import_dirs[ $this->currentFileInfo['currentDirectory'] ] = $this->currentFileInfo['uri_root'];
				}

			} else {
				// otherwise, the file should be relative to the server root
				if ( $this->currentFileInfo ) {
					$import_dirs[ $this->currentFileInfo['entryPath'] ] = $this->currentFileInfo['entryUri'];
				}
				// if the user supplied entryPath isn't the actual root
				$import_dirs[ $_SERVER['DOCUMENT_ROOT'] ] = '';

			}

			// always look in user supplied import directories
			$import_dirs = array_merge( $import_dirs, Less_Parser::$options['import_dirs'] );

			foreach ( $import_dirs as $rootpath => $rooturi ) {
				if ( is_callable( $rooturi ) ) {
					$res = $rooturi( $evald_path );
					if ( $res && is_string( $res[0] ) ) {
						return [
							Less_Environment::normalizePath( $res[0] ),
							Less_Environment::normalizePath( $res[1] ?? dirname( $evald_path ) )
						];
					}
				} elseif ( !empty( $rootpath ) ) {
					$path = rtrim( $rootpath, '/\\' ) . '/' . ltrim( $evald_path, '/\\' );
					if ( file_exists( $path ) ) {
						return [
							Less_Environment::normalizePath( $path ),
							Less_Environment::normalizePath( dirname( $rooturi . $evald_path ) )
						];
					}
					if ( file_exists( $path . '.less' ) ) {
						return [
							Less_Environment::normalizePath( $path . '.less' ),
							Less_Environment::normalizePath( dirname( $rooturi . $evald_path . '.less' ) )
						];
					}
				}
			}
		}
	}

	/**
	 * Should the import be skipped?
	 *
	 * @param string|null $path
	 * @param Less_Environment $env
	 * @return bool|null
	 */
	public function skip( $path, $env ) {
		if ( $this->doSkip !== null ) {
			return $this->doSkip;
		}

		// @see less-2.5.3.js#ImportVisitor.prototype.onImported
		if ( isset( $env->importVisitorOnceMap[$path] ) ) {
			return true;
		}

		$env->importVisitorOnceMap[$path] = true;
		return false;
	}
}
