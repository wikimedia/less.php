<?php

// No @covers here, as this may cover all classes.
// phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers

class FunctionTest extends LessTestCase {

	public function testFunction() {
		$lessFile = __DIR__ . '/data/f1.less';
		$expected = file_get_contents( __DIR__ . '/data/f1.css' );

		$parser = new Less_Parser();
		$parser->registerFunction( 'myfunc-reverse', [ __CLASS__, 'reverse' ] );
		$parser->parseFile( $lessFile );
		$generated = $parser->getCss();

		$this->assertEquals( $expected, $generated );
	}

	public static function reverse( $arg ) {
		if ( $arg instanceof Less_Tree_Quoted ) {
			$arg->value = strrev( $arg->value );
			return $arg;
		}
	}

	public function testException() {
		$lessFile = __DIR__ . '/data/exception/f1.less';

		$parser = new Less_Parser();
		$parser->parseFile( $lessFile );

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
				'rgb expects three parameters',
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

	public function testInvalidColor() {
		$lessCode = '
		@color-progressive: 42px;
		.foo {
			color: mix( #000, @color-progressive, 20% );
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
				'error evaluating function `mix` The second argument must be a color, Less_Tree_Dimension given',
				$e->getMessage()
			);
		}
	}

	/**
	 * Function registry must not clash with private helper functions.
	 * Regression test for T363728, by checking that a custom function
	 * that happens to share the same name, works fine.
	 */
	public function testNoconflictPrivateHelper() {
		$lessCode = '
		.foo {
			fround: fround();
			number-lo: _number();
			number: number();
			operate: operate();
			scaled-lo: _scaled();
			scaled: scaled();
		}
		';

		$fnLo = static function () {
			return new Less_Tree_Quoted( '', 'x' );
		};
		$fn = static function () {
			return new Less_Tree_Quoted( '', 'y' );
		};
		$parser = new Less_Parser( [
			'functions' => [
				'_number' => $fnLo,
				'_scaled' => $fnLo,
				'fround' => $fn,
				'number' => $fn,
				'operate' => $fn,
				'scaled' => $fn,
			]
		] );

		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );

		$expected = <<<CSS
.foo {
  fround: y;
  number-lo: x;
  number: y;
  operate: y;
  scaled-lo: x;
  scaled: y;
}
CSS;

		$this->assertEquals( $expected, $css );
	}
}
