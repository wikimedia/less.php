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
			// Permanently disabled: not supported.
			'plugin' => true,
			'javascript' => true,

			// Temporary disabled; Bug logged here T352830
			// If T352866 is fixed, this is should also be resolved
			'variables' => true,

			// TODO: This needs a task
			'css-escapes' => true,

			// This should also be fixed or might need further investigation
			'import-inline' => true,

			// Temporary disabled; Bug logged here T352867
			'mixins-guards' => true,

			// Temporary disabled; Bug logged here T352897
			'mixin-args' => true,

			// Temporary disabled; Bug logged here T352862
			'css-3' => true,
			'import-reference' => true,

			// TODO; Create Task for import-interpolation
			'import-interpolation' => true,

			// Temporary disabled; Bug logged here T352897
			'mixins-args' => true,

			// Temporary disabled: Bug logged here T352911
			'whitespace' => true,

			// Temporary disabled: After fixing T352911 & T352866
			// This might be resolved
			'css' => true,

			 // Temporary disabled: Bug logged here T353146
			'import' => true,

			// Temporary disabled:Bug logged here T353147
			'urls' => true,

			// Temporary disabled; Bug logged T353131 & T353132
			'comments' => true,
			'comments2' => true,

			// Temporary disabled; Bug logged T353144
			'css-guards' => true,

			// We moved this to Less.php parens.less test case because
			// our current version of Less.php suports Less.js v3.x parens
			// behaviour of doing maths in parentheses by default
			'parens' => true,

			// Temporary disabled; Bug logged T353143
			'detached-rulesets' => true,
		]
	];

	public static function provideFixtures() {
		foreach ( [
			// 'lessjs' => 'expected',
			'less.php' => 'css',
			'bug-reports' => 'css',
			'lessjs-2.5.3' => 'expected'
		] as $group => $expectedSubdir ) {
			$expectedDir = self::getFixtureDir() . "/$group/$expectedSubdir";
			if ( !is_dir( $expectedDir ) ) {
				// Check because glob() tolerances non-existence
				throw new RuntimeException( "Directory missing: $expectedDir" );
			}
			foreach ( glob( "$expectedDir/*.css" ) as $cssFile ) {
				// From /Fixtures/lessjs/css/something.css
				// into /Fixtures/lessjs/less/name.less
				$name = basename( $cssFile, '.css' );
				$lessFile = dirname( dirname( $cssFile ) ) . '/less/' . $name . '.less';
				if ( self::KNOWN_FAILURE[ $group ][ $name ] ?? false ) {
					continue;
				}
				yield "Fixtures/$group $name" => [ $cssFile, $lessFile ];
			}
		}
	}

	/**
	 * @dataProvider provideFixtures
	 */
	public function testFixture( $cssFile, $lessFile ) {
		$expectedCSS = trim( file_get_contents( $cssFile ) );

		// Check with standard parser
		$parser = new Less_Parser();
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
		$options = [ 'cache_dir' => $this->cache_dir,
					 'functions' => [ '_color' => [ __CLASS__, 'FnColor' ],
									  'add' => [ __CLASS__, 'FnAdd' ],
									  'increment' => [ __CLASS__, 'FnIncrement' ] ] ];
		$files = [ $lessFile => '' ];
		try {
			$cacheFile = Less_Cache::Regen( $files, $options );
			$css = file_get_contents( $this->cache_dir . '/' . $cacheFile );
		} catch ( Less_Exception_Parser $e ) {
			$css = $e->getMessage();
		}
		$css = trim( $css );
		$this->assertEquals( $expectedCSS, $css, "Regenerating cache" );

		// Check using the cached data
		try {
			$cacheFile = Less_Cache::Get( $files, $options );
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
