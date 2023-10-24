<?php declare( strict_types = 1 );

require_once __DIR__ . '/../vendor/autoload.php';

const USAGE = <<<TEXT
Usage: php compare.php [--override] [<fixtureDir>]

Options:

    fixtureDir  Pass one of the /test/Fixtures/ directories.
                The compare tool will compile each file in the "less/"
                subdirectory, and compare it to an eponymous file in the
                "css/" subdirectory.

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

class LessFixtureDiff {
	private int $summaryOK = 0;
	private array $summaryFail = [];

	public function cli( $args ) {
		$useOverride = false;
		$fixtureDir = null;

		foreach ( $args as $arg ) {
			if ( $arg === '--override' ) {
				$useOverride = true;
			} elseif ( strpos( $arg, '--' ) === 0 ) {
				print "Error: Invalid option $arg\n\n";
				print USAGE;
				exit( 1 );
			} elseif ( $fixtureDir === null ) {
				// First non-option argument
				$fixtureDir = $arg;
			} else {
				print "Error: Unexpected argument $arg\n\n";
				print USAGE;
			}
		}

		$this->compare(
			$fixtureDir ?? __DIR__ . '/Fixtures/lessjs-2.5.3/',
			$useOverride
		);
	}

	public function compare( string $fixtureDir, bool $useOverride ): void {
		$fixtureDir = rtrim( $fixtureDir, '/' );
		$cssDir = "$fixtureDir/css";
		$overrideDir = "$fixtureDir/override";
		if ( !is_dir( $cssDir ) ) {
			// Check because glob() tolerances non-existence
			print "Error: Missing directory $cssDir\n\n";
			print USAGE;
			exit( 1 );
		}
		if ( $useOverride && !is_dir( $overrideDir ) ) {
			print "Error: Missing directory $overrideDir\n\n";
			print USAGE;
			exit( 1 );
		}
		$group = basename( $fixtureDir );
		foreach ( glob( "$cssDir/*.css" ) as $cssFile ) {
			// From /Fixtures/lessjs/css/something.css
			// into /Fixtures/lessjs/less/name.less
			$name = basename( $cssFile, '.css' );
			$lessFile = dirname( dirname( $cssFile ) ) . '/less/' . $name . '.less';
			$overrideFile = dirname( dirname( $cssFile ) ) . '/override/' . $name . '.css';
			if ( $useOverride && file_exists( $overrideFile ) ) {
				$cssFile = $overrideFile;
			}
			$this->handleFixture( $cssFile, $lessFile );
		}

		// Create a simple to understand summary at the end,
		// separate from the potentially long diffs
		print sprintf( "\nSummary:\n* OK: %d\n* Fail: %d%s\n",
			$this->summaryOK,
			count( $this->summaryFail ),
			$this->summaryFail
				? ' (' . implode( ', ', $this->summaryFail ) . ')'
				: ''
		);
	}

	private function addToSummary( string $line ) {
		$this->summary .= $line . "\n";
	}

	public function handleFixture( $cssFile, $lessFile ) {
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
