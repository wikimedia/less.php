<?php

/**
 * Utility for handling the generation and caching of css files
 */
class Less_Cache {

	/** @var string|false Directory less.php can use for storing data */
	public static $cache_dir = false;

	/** @var string Prefix for the storing data */
	public static $prefix = 'lessphp_';

	/** @var string Prefix for the storing vars */
	public static $prefix_vars = 'lessphpvars_';

	/**
	 * @var int Specifies the number of seconds after which data created by less.php will be seen
	 *  as 'garbage' and potentially cleaned up
	 */
	public static $gc_lifetime = 604800;

	/** @var bool */
	private static $gc_done = false;

	/**
	 * Save and reuse the results of compiled less files.
	 * The first call to Get() will generate css and save it.
	 * Subsequent calls to Get() with the same arguments will return the same css filename
	 *
	 * @param array $less_files Array of .less files to compile
	 * @param array $parser_options Array of compiler options
	 * @param array $modify_vars Array of variables
	 * @return string|false Name of the css file
	 */
	public static function Get( $less_files, $parser_options = [], $modify_vars = [] ) {
		// check $cache_dir
		if ( isset( $parser_options['cache_dir'] ) ) {
			self::$cache_dir = $parser_options['cache_dir'];
		}

		if ( empty( self::$cache_dir ) ) {
			throw new Exception( 'cache_dir not set' );
		}

		if ( isset( $parser_options['prefix'] ) ) {
			self::$prefix = $parser_options['prefix'];
		}

		if ( empty( self::$prefix ) ) {
			throw new Exception( 'prefix not set' );
		}

		if ( isset( $parser_options['prefix_vars'] ) ) {
			self::$prefix_vars = $parser_options['prefix_vars'];
		}

		if ( empty( self::$prefix_vars ) ) {
			throw new Exception( 'prefix_vars not set' );
		}

		self::$cache_dir = self::CheckCacheDir();
		$less_files = (array)$less_files;

		// create a file for variables
		if ( !empty( $modify_vars ) ) {
			$lessvars = Less_Parser::serializeVars( $modify_vars );
			$vars_file = self::$cache_dir . self::$prefix_vars . sha1( $lessvars ) . '.less';

			if ( !file_exists( $vars_file ) ) {
				file_put_contents( $vars_file, $lessvars );
			}

			$less_files += [ $vars_file => '/' ];
		}

		// generate name for compiled css file
		$hash = md5( json_encode( $less_files ) );
		$list_file = self::$cache_dir . self::$prefix . $hash . '.list';

		// check cached content
		if ( !isset( $parser_options['use_cache'] ) || $parser_options['use_cache'] === true ) {
			if ( file_exists( $list_file ) ) {

				self::ListFiles( $list_file, $list, $cached_name );
				$compiled_name = self::CompiledName( $list, $hash );

				// if $cached_name is the same as the $compiled name, don't regenerate
				if ( !$cached_name || $cached_name === $compiled_name ) {

					$output_file = self::OutputFile( $compiled_name, $parser_options );

					if ( $output_file && file_exists( $output_file ) ) {
						@touch( $list_file );
						return basename( $output_file ); // for backwards compatibility, we just return the name of the file
					}
				}
			}
		}

		$compiled = self::Cache( $less_files, $parser_options );
		if ( !$compiled ) {
			return false;
		}

		$compiled_name = self::CompiledName( $less_files, $hash );
		$output_file = self::OutputFile( $compiled_name, $parser_options );

		// save the file list
		$list = $less_files;
		$list[] = $compiled_name;
		$cache = implode( "\n", $list );
		file_put_contents( $list_file, $cache );

		// save the css
		file_put_contents( $output_file, $compiled );

		// clean up
		// Garbage collection can be slow, so run it only on cache misses,
		// and at most once per process.
		if ( !self::$gc_done ) {
			self::$gc_done = true;
			self::CleanCache();
		}

		return basename( $output_file );
	}

	/**
	 * Force the compiler to regenerate the cached css file
	 *
	 * @param array $less_files Array of .less files to compile
	 * @param array $parser_options Array of compiler options
	 * @param array $modify_vars Array of variables
	 * @return string Name of the css file
	 */
	public static function Regen( $less_files, $parser_options = [], $modify_vars = [] ) {
		$parser_options['use_cache'] = false;
		return self::Get( $less_files, $parser_options, $modify_vars );
	}

	public static function Cache( &$less_files, $parser_options = [] ) {
		$parser_options['cache_dir'] = self::$cache_dir;
		$parser = new Less_Parser( $parser_options );

		// combine files
		foreach ( $less_files as $file_path => $uri_or_less ) {

			// treat as less markup if there are newline characters
			if ( str_contains( $uri_or_less, "\n" ) ) {
				$parser->Parse( $uri_or_less );
				continue;
			}

			$parser->ParseFile( $file_path, $uri_or_less );
		}

		$compiled = $parser->getCss();

		$less_files = $parser->getParsedFiles();

		return $compiled;
	}

	private static function OutputFile( $compiled_name, $parser_options ) {
		// custom output file
		if ( !empty( $parser_options['output'] ) ) {

			// relative to cache directory?
			if ( preg_match( '#[\\\\/]#', $parser_options['output'] ) ) {
				return $parser_options['output'];
			}

			return self::$cache_dir . $parser_options['output'];
		}

		return self::$cache_dir . $compiled_name;
	}

	private static function CompiledName( $files, $extrahash ) {
		// save the file list
		$temp = [ Less_Version::cache_version ];
		foreach ( $files as $file ) {
			$temp[] = filemtime( $file ) . "\t" . filesize( $file ) . "\t" . $file;
		}

		return self::$prefix . sha1( json_encode( $temp ) . $extrahash ) . '.css';
	}

	public static function SetCacheDir( $dir ) {
		self::$cache_dir = self::CheckCacheDir( $dir );
	}

	/**
	 * @deprecated since 5.3.0 Internal for use by Less_Cache and Less_Parser only.
	 */
	public static function CheckCacheDir( $dir = null ) {
		$dir ??= self::$cache_dir;
		$dir = Less_Parser::WinPath( $dir );
		$dir = rtrim( $dir, '/' ) . '/';

		if ( !file_exists( $dir ) ) {
			if ( !mkdir( $dir ) ) {
				throw new Less_Exception_Parser( 'Less.php cache directory couldn\'t be created: ' . $dir );
			}

		} elseif ( !is_dir( $dir ) ) {
			throw new Less_Exception_Parser( 'Less.php cache directory doesn\'t exist: ' . $dir );

		} elseif ( !is_writable( $dir ) ) {
			throw new Less_Exception_Parser( 'Less.php cache directory isn\'t writable: ' . $dir );
		}

		return $dir;
	}

	/**
	 * @deprecated since 5.3.0 Called automatically. Internal for use by Less_Cache and Less_Parser only.
	 */
	public static function CleanCache( $dir = null ) {
		$dir ??= self::$cache_dir;
		if ( !$dir ) {
			return;
		}

		// only remove files with extensions created by less.php
		// css files removed based on the list files
		$remove_types = [ 'lesscache' => 1, 'list' => 1, 'less' => 1, 'map' => 1 ];

		$files = scandir( $dir );
		if ( !$files ) {
			return;
		}

		$check_time = time() - self::$gc_lifetime;
		foreach ( $files as $file ) {

			// don't delete if the file wasn't created with less.php
			if ( !str_starts_with( $file, self::$prefix ) ) {
				continue;
			}

			$parts = explode( '.', $file );
			$type = array_pop( $parts );

			if ( !isset( $remove_types[$type] ) ) {
				continue;
			}

			$fullPath = $dir . $file;
			$mtime = filemtime( $fullPath );

			// don't delete if it's a relatively new file
			if ( $mtime > $check_time ) {
				continue;
			}

			// delete the list file and associated css file
			if ( $type === 'list' ) {
				self::ListFiles( $fullPath, $list, $css_file_name );
				if ( $css_file_name ) {
					$css_file = $dir . $css_file_name;
					if ( file_exists( $css_file ) ) {
						unlink( $css_file );
					}
				}
			}

			unlink( $fullPath );
		}
	}

	/**
	 * Get the list of less files and generated css file from a list file
	 */
	public static function ListFiles( $list_file, &$list, &$css_file_name ) {
		$list = explode( "\n", file_get_contents( $list_file ) );

		// pop the cached name that should match $compiled_name
		$css_file_name = array_pop( $list );

		if ( !preg_match( '/^' . self::$prefix . '[a-f0-9]+\.css$/', $css_file_name ) ) {
			$list[] = $css_file_name;
			$css_file_name = false;
		}
	}

}
