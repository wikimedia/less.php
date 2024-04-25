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
			// We moved this to Less.php parens.less test case because
			// our current version of Less.php suports Less.js v3.x parens
			// behaviour of doing maths in parentheses by default
			'parens' => true,

			// Temporary disabled
			'css' => true, // T352911 & T352866
			'mixins-guards' => true, // T352867
			'urls' => true, // T353147
			'variables' => true, // T352830, T352866
		],
		'lessjs-2.5.3/include-path' => [
			'include-path' => true, // T353147, data-uri()
		],
		'lessjs-3.13.1' => [
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
			'import-remote' => true,
			'import' => true,
			'css-escapes' => true,
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
			$unsupported = $fixture['unsupported'] ?? [];
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
				if ( in_array( $name, $unsupported ) ) {
					continue;
				}
				$skipTestMessage = false;
				if ( self::KNOWN_FAILURE[ $group ][ $name ] ?? false ) {
					$skipTestMessage = 'Known failure, not yet supported.';
				}
				yield "Fixtures/$group $name" => [ $cssFile, $lessFile, $options, $skipTestMessage ];
			}
		}
	}

	/**
	 * @dataProvider provideFixtures
	 */
	public function testFixture( $cssFile, $lessFile, $options, $ifSetSkipTestMessage = false ) {
		if ( $ifSetSkipTestMessage !== false ) {
			$this->markTestSkipped( $ifSetSkipTestMessage );
		}

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
