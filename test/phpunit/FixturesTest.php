<?php

class phpunit_FixturesTest extends phpunit_bootstrap {
	private const KNOWN_FAILURE = [
		'bug-reports' => [
			// <lessjs>   background: transparent url("../images/logo.png")
			// <less.php> background: transparent url("images/logo.png")
			'110' => true,

			// Invalid input, which lessjs autocorrects.
			//
			// <lessjs>   @charset 'UTF-8';
			// <lessjs>   .test {}
			// <less.php> .test {}
			// <less.php> @charset 'UTF-8';
			'280' => true,
		],

		'lessjs-2.5.3' => [
			// Permanently disabled
			'plugin' => true, // Not supported.
			'javascript' => true, // Not supported.
			// We moved this to Less.php parens.less test case because
			// our current version of Less.php suports Less.js v3.x parens
			// behaviour of doing maths in parentheses by default
			'parens' => true,

			// Temporary disabled
			'comments' => true, // T353131 & T353132
			'comments2' => true, // T353131 & T353132
			'css' => true, // T352911 & T352866
			'css-guards' => true, // T353144
			'import' => true, // T353146
			'import-reference' => true, // T352862
			'mixin-args' => true, // T352897
			'mixins-args' => true, // T352897
			'mixins-guards' => true, // T352867
			'urls' => true, // T353147
			'variables' => true, // T352830, T352866
		],
		'lessjs-2.5.3/include-path' => [
			'include-path' => true, // T353147, data-uri()
		],
		'lessjs-3.13.1' => [
			// Permanently disabled
			'plugin' => true, // Not supported
			'plugin-preeval' => true, // Not Supported
			'plugin-module' => true, // Not Supported
			'javascript' => true, // Not supported.

			'calc' => true, // New Feature

			'variables' => true,
			'functions' => true,
			'functions-each' => true,
			'import-reference-issues' => true,
			'detached-rulesets' => true,
			'import-reference' => true,
			'import-module' => true,
			'extend-selector' => true,
			'mixins-guards' => true,
			'merge' => true,
			'css-3' => true,
			'colors' => true,
			'css-grid' => true,
			'urls' => true,
			'operations' => true,
			'comments2' => true,
			'comments' => true,
			'import-remote' => true,
			'import' => true,
			'css-escapes' => true,
			'css-guards' => true,
			'parse-interpolation' => true,
			'selectors' => true,
			'property-accessors' => true,
			'property-name-interp' => true,
			'permissive-parse' => true,
		],
		'lessjs-3.13.1/include-path' => [
			'include-path' => true,
		],
		'lessjs-3.13.1/compression' => [
			'compression' => true,
		],
	];

	public static function provideFixtures() {
		foreach ( (
			require __DIR__ . '/../fixtures.php'
		) as $group => $fixture ) {
			$cssDir = $fixture['cssDir'];
			$lessDir = $fixture['lessDir'];
			$overrideDir = $fixture['overrideDir'] ?? null;
			$options = $fixture['options'] ?? [];
			if ( !is_dir( $cssDir ) ) {
				// Check because glob() tolerances non-existence
				throw new RuntimeException( "Directory missing: $cssDir" );
			}
			foreach ( glob( "$cssDir/*.css" ) as $cssFile ) {
				$name = basename( $cssFile, '.css' );
				$lessFile = "$lessDir/$name.less";
				$overrideFile = $overrideDir ? "$overrideDir/$name.css" : null;
				if ( $overrideFile && file_exists( $overrideFile ) ) {
					if ( file_get_contents( $overrideFile ) === file_get_contents( $cssFile ) ) {
						print "WARNING: Redundant override for $overrideFile\n";
					}
					$cssFile = $overrideFile;
				}
				if ( self::KNOWN_FAILURE[ $group ][ $name ] ?? false ) {
					continue;
				}
				yield "Fixtures/$group $name" => [ $cssFile, $lessFile, $options ];
			}
		}
	}

	/**
	 * @dataProvider provideFixtures
	 */
	public function testFixture( $cssFile, $lessFile, $options ) {
		$expectedCSS = trim( file_get_contents( $cssFile ) );

		// Check with standard parser
		$parser = new Less_Parser( $options );
		try {
			$parser->registerFunction( '_color', [ __CLASS__, 'FnColor' ] );
			$parser->registerFunction( 'add', [ __CLASS__, 'FnAdd' ] );
			$parser->registerFunction( 'increment', [ __CLASS__, 'FnIncrement' ] );
			$parser->parseFile( $lessFile );
			$css = $parser->getCss();
		} catch ( Less_Exception_Parser $e ) {
			$css = $e->getMessage();
		}
		$css = trim( $css );
		$this->assertSame( $expectedCSS, $css, "Standard compiler" );

		// Check with cache
		$optionsWithCache = $options + [
			'cache_dir' => $this->cache_dir,
			'functions' => [
				'_color' => [ __CLASS__, 'FnColor' ],
				'add' => [ __CLASS__, 'FnAdd' ],
				'increment' => [ __CLASS__, 'FnIncrement' ],
			],
		];
		$files = [ $lessFile => '' ];
		try {
			$cacheFile = Less_Cache::Regen( $files, $optionsWithCache );
			$css = file_get_contents( $this->cache_dir . '/' . $cacheFile );
		} catch ( Less_Exception_Parser $e ) {
			$css = $e->getMessage();
		}
		$css = trim( $css );
		$this->assertEquals( $expectedCSS, $css, "Regenerating cache" );

		// Check using the cached data
		try {
			$cacheFile = Less_Cache::Get( $files, $optionsWithCache );
			$css = file_get_contents( $this->cache_dir . '/' . $cacheFile );
		} catch ( Less_Exception_Parser $e ) {
			$css = $e->getMessage();
		}
		$css = trim( $css );
		$this->assertEquals( $expectedCSS, $css, "Using cache" );
	}

	public static function FnColor( $str ) {
		if ( $str->value === "evil red" ) {
			return new Less_Tree_Color( "600" );
		}
	}

	public static function FnAdd( $a, $b ) {
		return new Less_Tree_Dimension( $a->value + $b->value );
	}

	public static function FnIncrement( $a ) {
		return new Less_Tree_Dimension( $a->value + 1 );
	}

}
