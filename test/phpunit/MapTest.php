<?php

class MapTest extends LessTestCase {

	public function testMap() {
		$lessFile = self::$fixturesDir . '/bootstrap3-sourcemap/less/bootstrap.less';
		$expectedFile = self::$fixturesDir . '/bootstrap3-sourcemap/expected/bootstrap.map';
		$mapDestination = self::$cacheDir . '/bootstrap.map';

		$parser = new Less_Parser( [
			'sourceMap' => true,
			'sourceMapURL' => '/',
			'sourceMapBasepath' => dirname( dirname( $lessFile ) ),
			'sourceMapWriteTo' => $mapDestination,
			'math' => 'always',
		] );
		$parser->parseFile( $lessFile );
		$parser->getCss();

		$expected = file_get_contents( $expectedFile );
		$generated = file_get_contents( $mapDestination );
		$this->assertEquals( $expected, $generated );
	}

	public function testImportInline() {
		$lessFile = self::$fixturesDir . '/less.php/less/T380641-sourcemap-import-inline.less';
		$expectedFile = self::$fixturesDir . '/less.php/css/T380641-sourcemap-import-inline.map';
		$mapDestination = self::$cacheDir . '/import-inline.map';

		$parser = new Less_Parser( [
			'sourceMap' => true,
			'sourceMapURL' => '/',
			'sourceMapBasepath' => dirname( $lessFile ),
			'sourceMapWriteTo' => $mapDestination,
		] );
		$parser->parseFile( $lessFile );
		$parser->getCss();

		$expected = file_get_contents( $expectedFile );
		$generated = file_get_contents( $mapDestination );
		$this->assertEquals( $expected, $generated );
	}

}
