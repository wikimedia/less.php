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

	public function testInvalidMinmax() {
		// The min/max functions only accept Less_Tree arguments that contain
		// a value property. Less_Tree_Color is one of the very few subclasses
		// that doesn't use a value property.
		//
		// This is garbage input. We mainly test it to ensure we handle it without
		// causing internal warnings/errors.
		$lessCode = '
		.foo {
			color: min(rgb(1,1,1), 2, rgb(3,3,3));
		}
		';

		$parser = new Less_Parser();
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );

		$expected = <<<CSS
.foo {
  color: 2;
}
CSS;
		$this->assertEquals( $expected, $css );
	}
}
