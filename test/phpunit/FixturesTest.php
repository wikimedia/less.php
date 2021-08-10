<?php

class phpunit_FixturesTest extends phpunit_bootstrap {

	public static function provideFixtures() {
		foreach ( glob( self::getFixtureDir() . '/lessjs/expected/*.css' ) as $file ) {
			$name = basename( $file, '.css' );
			yield "Fixtures/lessjs $name" => [ $file ];
		}
		foreach ( glob( self::getFixtureDir() . '/less.php/css/*.css' ) as $file ) {
			$name = basename( $file, '.css' );
			yield "Fixtures/less.php $name" => [ $file ];
		}
	}

	/**
	 * @dataProvider provideFixtures
	 */
	public function testFixture( $cssFile ) {
		// Translate /Fixtures/lessjs/css/name.css to /Fixtures/lessjs/less/name.less
		$name = basename( $cssFile, '.css' );
		$lessFile = dirname( dirname( $cssFile ) ) . '/less/' . $name . '.less';
		$expected_css = trim( file_get_contents( $cssFile ) );

		$fixtureGroup = basename( dirname( dirname( $cssFile ) ) );

		// Check with standard parser
		$parser = new Less_Parser();
		$parser->parseFile( $lessFile );
		$css = $parser->getCss();
		$css = trim( $css );
		$this->assertEquals( $expected_css, $css, "Standard compiler" );

		// Check with cache
		$options = array( 'cache_dir' => $this->cache_dir );
		$files = array( $lessFile => '' );

		$css_file_name = Less_Cache::Regen( $files, $options );
		$css = file_get_contents( $this->cache_dir.'/'.$css_file_name );
		$css = trim( $css );
		$this->assertEquals( $expected_css, $css, "Regenerating cache" );

		// Check using the cached data
		$css_file_name = Less_Cache::Get( $files, $options );
		$css = file_get_contents( $this->cache_dir.'/'.$css_file_name );
		$css = trim( $css );
		$this->assertEquals( $expected_css, $css, "Using cache" );
	}
}
