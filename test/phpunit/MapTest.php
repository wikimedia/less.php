<?php

class phpunit_MapTest extends phpunit_bootstrap {

	public function testMap() {
		$lessFile = $this->fixtures_dir . '/bootstrap3-sourcemap/less/bootstrap.less';
		$expectedFile = $this->fixtures_dir . '/bootstrap3-sourcemap/expected/bootstrap.map';
		$mapDestination = $this->cache_dir . '/bootstrap.map';

		$options['sourceMap'] = true;
		$options['sourceMapWriteTo'] = $mapDestination;
		$options['sourceMapURL'] = '/';
		$options['sourceMapBasepath'] = dirname( dirname( $lessFile ) );

		$parser = new Less_Parser( $options );
		$parser->parseFile( $lessFile );
		$css = $parser->getCss();

		$expected = file_get_contents( $expectedFile );
		$generated = file_get_contents( $mapDestination );
		$this->assertEquals( $expected, $generated );
	}

}
