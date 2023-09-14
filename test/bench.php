<?php

require_once __DIR__ . '/../vendor/autoload.php';

class LessPhpBenchmark {
	private const CASES = [
		'strings' => [
			'count' => 500,
			'files' => [
				__DIR__ . '/Fixtures/codex-icons/*.less',
				__DIR__ . '/Fixtures/lessjs/less/strings.less',
			],
		],
		'%s_fixtures' => [
			'count' => 50,
			'files' => [
				__DIR__ . '/Fixtures/bootstrap-3.0.3/less/bootstrap.less',
				__DIR__ . '/Fixtures/lessjs/less/*.less',
				__DIR__ . '/Fixtures/less.php/less/*.less',
				__DIR__ . '/Fixtures/bug-reports/less/*.less',
			],
		],
	];
	private const FIXTURES_FAIL = [
		__DIR__ . '/Fixtures/bug-reports/less/109.less',
		__DIR__ . '/Fixtures/bug-reports/less/129.less',
		__DIR__ . '/Fixtures/bug-reports/less/259.less',
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
					array_values(
						array_diff( glob( $pattern ), self::FIXTURES_FAIL )
					)
				);
			}
			$this->bench( $name, $info['count'], $files );
		}
	}

	public function bench( $name, $iterations, $files ) {
		$name = sprintf( $name, count( $files ) );
		$total = 0;
		$max = -INF;
		for ( $i = 1; $i <= $iterations; $i++ ) {
			$start = microtime( true );
			foreach ( $files as $lessFile ) {
				$parser = new Less_Parser();
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
