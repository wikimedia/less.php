<?php

// No @covers here, as this may cover all classes.
// phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers

class FixturesTest extends LessTestCase {
	private const KNOWN_FAILURE = [
		'lessjs-2.5.3' => [
			// We moved this to Less.php parens.less test case because
			// our current version of Less.php suports Less.js v3.x parens
			// behaviour of doing maths in parentheses by default
			'parens' => true,
		],
		'lessjs-3.13.1' => [
			'functions' => true,
			'functions-each' => true,
			'import-reference-issues' => true,
			'detached-rulesets' => true,
			'import-module' => true,
			'extend-selector' => true,
			'mixins-guards' => true,
			'merge' => true,
			'colors' => true,
			'urls' => true,
			'operations' => true,
			'import-remote' => true,
			'import' => true,
			'css-escapes' => true,
			'parse-interpolation' => true,
			'selectors' => true,
			'property-name-interp' => true,
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
				$overrideCssFile = $overrideDir ? "$overrideDir/$name.css" : null;
				if ( $overrideCssFile && file_exists( $overrideCssFile ) ) {
					if ( file_get_contents( $overrideCssFile ) === file_get_contents( $cssFile ) ) {
						print "WARNING: Redundant override for $overrideCssFile\n";
					}
					$cssFile = $overrideCssFile;
				}
				$overrideLessFile = $overrideDir ? "$overrideDir/$name.less" : null;
				if ( $overrideLessFile && file_exists( $overrideLessFile ) ) {
					if ( file_get_contents( $overrideLessFile ) === file_get_contents( $lessFile ) ) {
						print "WARNING: Redundant override for $overrideLessFile\n";
					}
					$lessFile = $overrideLessFile;
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
		$hasError = false;
		try {
			$parser->registerFunction( '_color', [ __CLASS__, 'FnColor' ] );
			$parser->registerFunction( 'add', [ __CLASS__, 'FnAdd' ] );
			$parser->registerFunction( 'increment', [ __CLASS__, 'FnIncrement' ] );
			$parser->parseFile( $lessFile );
			$css = $parser->getCss();
		} catch ( Less_Exception_Parser $e ) {
			$hasError = true;
			$css = $e->getMessage();
		}
		$css = trim( $css );

		if ( $hasError && $expectedCSS !== $css && strlen( $expectedCSS ) > 1024 ) {
			// If we have a parser exception, show the error as-is instead of a long diff
			// with all lines from $expectedCss as missing. We check the length so as to
			// still render a diff if this is a test case where we expected a (different) error.
			$this->fail( $css );
		} else {
			$this->assertSame( $expectedCSS, $css, "Standard compiler" );
		}

		// Check with cache
		$optionsWithCache = $options + [
			'cache_dir' => self::$cacheDir,
			'functions' => [
				'_color' => [ __CLASS__, 'FnColor' ],
				'add' => [ __CLASS__, 'FnAdd' ],
				'increment' => [ __CLASS__, 'FnIncrement' ],
			],
		];
		$files = [ $lessFile => '' ];
		try {
			$cacheFile = Less_Cache::Regen( $files, $optionsWithCache );
			$css = file_get_contents( self::$cacheDir . '/' . $cacheFile );
		} catch ( Less_Exception_Parser $e ) {
			$css = $e->getMessage();
		}
		$css = trim( $css );
		$this->assertEquals( $expectedCSS, $css, "Regenerating cache" );

		// Check using the cached data
		try {
			$cacheFile = Less_Cache::Get( $files, $optionsWithCache );
			$css = file_get_contents( self::$cacheDir . '/' . $cacheFile );
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
