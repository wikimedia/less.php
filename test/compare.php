<?php declare( strict_types = 1 );

require_once __DIR__ . '/../vendor/autoload.php';

const USAGE = <<<TEXT
Usage: php compare.php [--override] [<fixtureDir>]

Options:

    fixtureDir  Pass one of the /test/Fixtures/ directories.
                The compare tool will compile each file in the "less/"
                subdirectory, and compare it to an eponymous file in the
                "css/" subdirectory.

                - {{FIXTURE_DIR}}

                Default: test/Fixtures/lessjs-2.5.3/

    --override  By default, the compare tool validates the full upstream
                Less.js specification, as stored in the fixture's "css/"
                subdirectory. This way all differences are shown, including
                well-known or accepted differences.

                We sometimes create a copy of an upstream spec and alter it,
                to accomodate known differences, where it is worth keeping a
                variation of a test case enabled in PHPUnit, instead of
                disabling it entirely in phpunit/FixtureTest.php. This copy is
                stored in the "override/" subdirectory and used by PHPUnit.

                To run test/compare.php against the same expectation as PHPUnit,
                set the --override option. This shows unsolved differences that
                are disabled in CI via FixtureTest::KNOWN_FAILURE, whilst still
                accepting differences from any override files.

TEXT;

define( 'FIXTURES', require __DIR__ . '/fixtures.php' );

class LessFixtureDiff {
	private int $summaryOK = 0;
	private array $summaryFail = [];
	private array $summaryUnsupported = [];

	public function cli( $args ) {
		$useOverride = false;
		$fixtureDir = null;

		foreach ( $args as $arg ) {
			if ( $arg === '--override' ) {
				$useOverride = true;
			} elseif ( strpos( $arg, '--' ) === 0 ) {
				$this->error( "Invalid option $arg" );
			} elseif ( $fixtureDir === null ) {
				// First non-option argument
				$fixtureDir = $arg;
			} else {
				$this->error( "Unexpected argument $arg" );
			}
		}

		$this->compare(
			$fixtureDir ?? __DIR__ . '/Fixtures/lessjs-2.5.3/',
			$useOverride
		);
	}

	private function error( $message ) {
		print "Error: $message\n\n";
		print preg_replace_callback(
			'/^(.*){{FIXTURE_DIR}}$/m',
			static function ( $matches ) {
				$prefix = $matches[1];
				return $prefix . implode( "\n$prefix", array_keys( FIXTURES ) );
			},
			USAGE
		);
		exit( 1 );
	}

	/**
	 * @param string $fixtureDir Fixture group name as defined in test/fixtures.php,
	 *  or path to a fixture directory,
	 *  or path to a fixture css/less subdirectory.
	 * @return array|null
	 */
	private function getFixture( string $fixtureDir ): ?array {
		foreach ( FIXTURES as $group => $fixture ) {
			if ( $fixtureDir === $group
				|| realpath( $fixtureDir ) === realpath( $fixture['cssDir'] )
				|| realpath( $fixtureDir ) === realpath( $fixture['lessDir'] )
				|| realpath( $fixtureDir ) === realpath( $fixture['cssDir'] . "/.." )
			) {
				return $fixture;
			}
		}
		return null;
	}

	public function compare( string $fixtureDir, bool $useOverride ): void {
		$fixture = $this->getFixture( $fixtureDir );
		if ( !$fixture ) {
			$this->error( "Undefined fixture $fixtureDir" );
		}
		$cssDir = $fixture['cssDir'];
		$lessDir = $fixture['lessDir'];
		$overrideDir = $useOverride ? ( $fixture['overrideDir'] ?? null ) : null;
		$options = $fixture['options'] ?? [];
		$unsupported = $fixture['unsupported'] ?? [];
		foreach ( glob( "$cssDir/*.css" ) as $cssFile ) {
			$name = basename( $cssFile, '.css' );
			$lessFile = "$lessDir/$name.less";
			if ( in_array( $name, $unsupported ) ) {
				$this->summaryUnsupported[] = basename( $lessFile );
				continue;
			}
			$overrideFile = $overrideDir ? "$overrideDir/$name.css" : null;
			if ( $overrideFile && file_exists( $overrideFile ) ) {
				$cssFile = $overrideFile;
			}
			$this->handleFixture( $cssFile, $lessFile, $options );
		}

		// Create a simple to understand summary at the end,
		// separate from the potentially long diffs
		print "\nSummary:\n";
		print sprintf( "* OK: %d\n",
			$this->summaryOK
		);
		print sprintf( "* Fail: %d%s\n",
			count( $this->summaryFail ),
			$this->summaryFail
				? ' (' . implode( ', ', $this->summaryFail ) . ')'
				: ''
		);
		if ( $this->summaryUnsupported ) {
			print sprintf( "* Unsupported: %d (%s)\n",
				count( $this->summaryUnsupported ),
				implode( ', ', $this->summaryUnsupported )
			);
		}
	}

	private function addToSummary( string $line ) {
		$this->summary .= $line . "\n";
	}

	public function handleFixture( $cssFile, $lessFile, $options ) {
		$expectedCSS = trim( file_get_contents( $cssFile ) );

		// Check with standard parser
		$parser = new Less_Parser( $options );
		try {
			$parser->parseFile( $lessFile );
			$css = $parser->getCss();
		} catch ( Less_Exception_Parser $e ) {
			$css = $e->getMessage();
		} catch ( Throwable $e ) {
			$css = $e->__toString();
		}
		$css = trim( $css );

		if ( $css === $expectedCSS ) {
			print "... parse $lessFile OK\n";
			$this->summaryOK++;
		} else {
			print "... parse $lessFile Fail\n";
			$tmpActual = tempnam( sys_get_temp_dir(), 'lessphp_' );
			$tmpExpected = tempnam( sys_get_temp_dir(), 'lessphp_' );
			file_put_contents( $tmpActual, "$css\n" );
			file_put_contents( $tmpExpected, "$expectedCSS\n" );
			$output = null;
			exec(
				sprintf( 'diff -u --color=always --label=%s --label=%s %s %s',
					escapeshellarg( 'actual' ),
					escapeshellarg( $cssFile ),
					escapeshellarg( $tmpActual ),
					escapeshellarg( $tmpExpected )
				),
				$output
			);
			unlink( $tmpActual );
			unlink( $tmpExpected );
			print implode( "\n", $output ) . "\n";

			$this->summaryFail[] = basename( $lessFile );
		}
	}
}

( new LessFixtureDiff )->cli( array_slice( $argv, 1 ) );
