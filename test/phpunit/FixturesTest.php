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
	];

	public static function provideFixtures() {
		foreach ( [
			'lessjs' => 'expected',
			'less.php' => 'css',
			'bug-reports' => 'css',
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
}
