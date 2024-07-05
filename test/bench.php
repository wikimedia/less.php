<?php

require_once __DIR__ . '/../vendor/autoload.php';

class LessPhpBenchmark {
	private const CASES = [
		'strings' => [
			'count' => 500,
			'files' => [
				__DIR__ . '/Fixtures/bench-strings/*.less',
			],
			'options' => [],
		],
		'bootstrap-3.0.3' => [
			'count' => 50,
			'files' => [
				__DIR__ . '/Fixtures/bootstrap-3.0.3/less/bootstrap.less',
			],
			// Same as in /test/fixtures.php
			'options' => [
				'math' => 'always',
			]
		],
	];

	public function run() {
		echo sprintf( "\nwikimedia/less.php %s Benchmark on (PHP %s)\n\n",
			Less_Version::version,
			PHP_VERSION
		);
		foreach ( self::CASES as $name => $info ) {
			$files = [];
			foreach ( $info['files'] as $pattern ) {
				$files = array_merge(
					$files,
					glob( $pattern )
				);
			}
			$this->bench( $name, $info['count'], $info['options'], $files );
		}
	}

	public function bench( $name, $iterations, $options, $files ) {
		$name = sprintf( $name, count( $files ) );
		$total = 0;
		$max = -INF;
		for ( $i = 1; $i <= $iterations; $i++ ) {
			$start = microtime( true );
			foreach ( $files as $lessFile ) {
				$parser = new Less_Parser( $options );
				$parser->parseFile( $lessFile );
				try {
					$css = $parser->getCss();
				} catch ( Less_Exception_Parser $e ) {
					echo "$lessFile\n";
					throw $e;
				}
			}
			$took = ( microtime( true ) - $start ) * 1000;
			$max = max( $max, $took );
			$total += $took;
		}
		$this->outputStat( $name, $iterations, $total, $max );
	}

	protected function outputStat( $name, $iterations, $total, $max ) {
		$mean = $total / $iterations; // in milliseconds
		$ratePerSecond = 1.0 / ( $mean / 1000.0 );

		echo sprintf(
			"* %-25s iterations=%d max=%.2fms mean=%.2fms rate=%.0f/s\n",
			$name,
			$iterations,
			$max,
			$mean,
			$ratePerSecond
		);
	}
}

( new LessPhpBenchmark )->run();
