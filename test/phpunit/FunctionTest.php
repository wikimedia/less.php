<?php

class phpunit_FunctionTest extends phpunit_bootstrap {

	public function testFunction() {
		$lessFile = $this->fixtures_dir . '/functions/less/f1.less';
		$expected = file_get_contents( $this->fixtures_dir . '/functions/css/f1.css' );

		$parser = new Less_Parser();
		$parser->registerFunction( 'myfunc-reverse', [ __CLASS__, 'reverse' ] );
		$parser->parseFile( $lessFile );
		$generated = $parser->getCss();

		$this->assertEquals( $expected, $generated );
	}

	public static function reverse( $arg ) {
		if ( is_a( $arg, 'Less_Tree_Quoted' ) ) {
			$arg->value = strrev( $arg->value );
			return $arg;
		}
	}

	public function testException() {
		$lessCode = '
		.foo {
			content: number("x");
		}
		';

		$parser = new Less_Parser();
		$parser->parse( $lessCode );

		try {
			$parser->getCss();
			$this->fail();
		} catch ( Exception $e ) {
			$this->assertInstanceOf( Less_Exception_Parser::class, $e );
			$this->assertStringContainsString(
				'error evaluating function',
				$e->getMessage()
			);

			// Bypass PHPUnit's excectException() to assert presence and specifics
			// of the previous exception as well.
			$prev = $e->getPrevious();
			$this->assertInstanceOf( Less_Exception_Parser::class, $e );
			$this->assertStringContainsString(
				'color functions take numbers as parameters',
				$e->getMessage()
			);
		}
	}
}
