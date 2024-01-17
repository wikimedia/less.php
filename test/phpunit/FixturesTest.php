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

			// Temporary disabled; Bug logged here T353289
			'functions' => true,

			// Temporary disabled; Bug logged here T352859
			'selectors' => true,

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

			// Temporary disabled; Bug logged T353133
			'strings' => true,
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

			// Temporary disabled; Bug logged T353141
			'mixins-important' => true,
			// Temporary disabled; Bug logged T353142
			'mixins-interpolated' => true,
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
			$parser->parseFile( $lessFile );
			$css = $parser->getCss();
		} catch ( Less_Exception_Parser $e ) {
			$css = $e->getMessage();
		}
		$css = trim( $css );
		$this->assertSame( $expectedCSS, $css, "Standard compiler" );

		// Check with cache
		$options = [ 'cache_dir' => $this->cache_dir ];
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

	public function testOptionRootpath() {
		// When CSS refers to a URL that is only a hash fragment, it is a
		// dynamic reference to something in the current DOM, thus it must
		// not be remapped. https://phabricator.wikimedia.org/T331649
		$lessCode = '
			div {
				--a10: url("./images/icon.svg");
				--a11: url("./images/icon.svg#myid");
				--a20: url(icon.svg);
				--a21: url(icon.svg#myid);
				--a30: url(#myid);
			}
		';

		$parser = new Less_Parser();
		$parser->parse( $lessCode, '/x/fake.css' );
		$css = trim( $parser->getCss() );

		$expected = <<<CSS
div {
  --a10: url("/x/images/icon.svg");
  --a11: url("/x/images/icon.svg#myid");
  --a20: url(/x/icon.svg);
  --a21: url(/x/icon.svg#myid);
  --a30: url(#myid);
}
CSS;
		$this->assertEquals( $expected, $css );
	}

	public function testOptionFunctions() {
		// Add options
		$lessCode = '
		#test{
			border-width: add(7, 6);
		  }
		';

		$options = [ 'functions' => [ 'add' => [ __CLASS__, 'fooBar2' ] ] ];
		$parser = new Less_Parser( $options );
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );
		$expected = <<<CSS
#test {
  border-width: 13;
}
CSS;
		$this->assertSame( $expected, $css );

		// test with directly with registerFunction()
		$parser = new Less_Parser();
		$parser->registerFunction( 'add', [ __CLASS__, 'fooBar2' ] );
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );
		$this->assertSame( $expected, $css );

		// test with both passing options and using registerFunction()
		$lessCode = '
		#test{
			border-width: add(2, 3);
			border-color: _color("evil red");
			width: increment(15);
		  }
		';

		$options = [ 'functions' => [ '_color' => [ __CLASS__, 'fooBar1' ], 'add' => [ __CLASS__, 'fooBar2' ] ] ];
		$parser = new Less_Parser( $options );
		$parser->registerFunction( 'increment', [ __CLASS__, 'fooBar3' ] );
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );
		$expected = <<<CSS
#test {
  border-width: 5;
  border-color: #660000;
  width: 16;
}
CSS;
		$this->assertSame( $expected, $css );
	}

	public static function fooBar1( $str ) {
		if ( $str->value === "evil red" ) {
			return new Less_Tree_Color( "600" );
		}
	}

	public static function fooBar2( $a, $b ) {
		return new Less_Tree_Dimension( $a->value + $b->value );
	}

	public static function fooBar3( $a ) {
		return new Less_Tree_Dimension( $a->value + 1 );
	}
}
