<?php

class MapTest extends LessTestCase {

	public function testMap() {
		$lessFile = self::$fixturesDir . '/bootstrap3-sourcemap/less/bootstrap.less';
		$expectedFile = self::$fixturesDir . '/bootstrap3-sourcemap/expected/bootstrap.map';
		$mapDestination = self::$cacheDir . '/bootstrap.map';

		$options['sourceMap'] = true;
		$options['sourceMapWriteTo'] = $mapDestination;
		$options['sourceMapURL'] = '/';
		$options['sourceMapBasepath'] = dirname( dirname( $lessFile ) );
		$options['math'] = "always";

		$parser = new Less_Parser( $options );
		$parser->parseFile( $lessFile );
		$css = $parser->getCss();

		$expected = file_get_contents( $expectedFile );
		$generated = file_get_contents( $mapDestination );
		$this->assertEquals( $expected, $generated );
	}

}
