<?php

class ParserTest extends LessTestCase {
	public function testGetVariablesUncompiled() {
		$lessCode = '
			// Rule > Quoted
			@some_string: "foo";

			// Rule > Dimension
			@some_number: 123;

			// Rule > Dimension
			@some_unit: 12px;

			// Rule > Color
			@some_color: #f9f9f9;

			// Rule > Url > Quoted
			@some_url: url("just/a/test.jpg");
		';
		$parser = new Less_Parser();
		$parser->parse( $lessCode );
		// Without getCss()
		$this->assertEquals(
			[
				'@some_string' => '"foo"',
				'@some_number' => 123.0,
				'@some_unit' => '12px',
				'@some_color' => '#f9f9f9',
				'@some_url' => 'url("just/a/test.jpg")',
			],
			$parser->getVariables()
		);
	}

	public function testGetVariablesUncompiledError() {
		$lessCode = '
			// Rule > Dimension + Operation
			@some_unit_op: 2px + 3px;
		';

		$parser = new Less_Parser();
		$parser->parse( $lessCode );
		// Without getCss()

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'getVariables() require Less to be compiled' );
		$parser->getVariables();
	}

	public function testNamespacedValuesWithProperties() {
		$lessCode = '
		   @defaultHeight: 50px;
		   .block {
		       color: #f9f9f9;
		       width: 10px;
		       height: @defaultHeight;
		       margin: $width;
		   };
		   @var: .block();
		   @width: @var[width];
		';
		$parser = new Less_Parser();
		$parser->parse( $lessCode );
		$parser->getCss();
		$this->assertEquals(
			[
				"@var" => [
					"color" => "#f9f9f9",
					"width" => "10px",
					"height" => "50px",
					"margin" => "10px",
				],
				"@width" => "10px",
				"@defaultHeight" => "50px",
			],
			$parser->getVariables()
		);
	}

	public function testGetVariables() {
		$lessCode = '
			// Rule > Quoted
			@some_string: "foo";

			// Rule > Dimension
			@some_number: 123;

			// Rule > Dimension
			@some_unit: 12px;

			// Rule > Dimension
			@some_unit_op: 2px + 3px;

			// Rule > Color
			@some_color: #f9f9f9;

			// Rule > Url > Quoted
			@some_url: url("just/a/test.jpg");
		';

		$parser = new Less_Parser();
		$parser->parse( $lessCode );
		$parser->getCss();

		$this->assertEquals(
			[
				'@some_string' => '"foo"',
				'@some_number' => 123.0,
				'@some_unit' => '12px',
				'@some_unit_op' => '5px',
				'@some_color' => '#f9f9f9',
				'@some_url' => 'url("just/a/test.jpg")',
			],
			$parser->getVariables()
		);
	}

	public function testGetParsedFiles() {
		$parser = new Less_Parser();
		$baseDir = Less_Parser::WinPath( realpath( self::$fixturesDir . '/less.php/less' ) );
		$parser->parseFile( $baseDir . '/imports.less' );
		$parser->getCss();

		$files = $parser->getParsedFiles();

		$normalFiles = array_map( fn ( $file ) => str_replace( $baseDir, '', $file ), $files );
		$this->assertEqualsCanonicalizing(
			[
				'/imports.less',
				'/imports/b.less',
				'/imports/a.less'
			],
			$normalFiles
		);
	}

	public static function provideSetImportDirs() {
		yield 'from-importdir' => [
			'from-importdir.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
			],
			'div{font-family:monospace}'
		];
		yield 'from-importdir-file' => [
			'from-importdir-file.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
			],
			'div{font-family:fantasy}'
		];
		yield 'from-importdir-filenosuffix' => [
			'from-importdir-filenosuffix.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
			],
			'div{font-family:fantasy}'
		];
		yield 'from-importcallback (backcompat)' => [
			'from-importcallback.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
				static function ( $path ) {
					// Backwards compatibility with how people used
					// less.php 4.0.0 and earlier.
					if ( $path === '@wikimedia/example.less' ) {
						return [
							Less_Environment::normalizePath( __DIR__ . '/data/importdir-somevars/callme.less' ),
							Less_Environment::normalizePath( dirname( $path ) )
						];
					}
					return [ null, null ];
				}
			],
			'div{font-family:Call Me Maybe}'
		];
		yield 'from-importcallback (new)' => [
			'from-importcallback.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
				static function ( $path ) {
					if ( $path === '@wikimedia/example.less' ) {
						return [
							__DIR__ . '/data/importdir-somevars/callme.less',
							null
						];
					}
				}
			],
			'div{font-family:Call Me Maybe}'
		];
	}

	/**
	 * @dataProvider provideSetImportDirs
	 */
	public function testSetImportDirs( string $input, array $importDirs, string $expect ) {
		$file = __DIR__ . '/data/consume-somevars/' . $input;

		$parser = new Less_Parser( [
			'compress' => true,
		] );
		$parser->SetImportDirs( $importDirs );
		$parser->parseFile( $file );

		$this->assertEquals( $expect, $parser->getCss() );
	}

	public function testOperationException() {
		$lessFile = __DIR__ . '/data/exception/f2.less';

		$parser = new Less_Parser();
		$parser->parseFile( $lessFile );

		try {
			$parser->getCss();
		} catch ( Exception $e ) {
			$this->assertInstanceOf( Less_Exception_Parser::class, $e );
			$this->assertEquals(
"Operation on an invalid type in f2.less on line 4, column 2
2| @bar: 'world';
3| div {
4|  content: (@foo) * @bar;
5| }",
								$e->getMessage()
							);
		}

		$lessFile = __DIR__ . '/data/exception/f3.less';

		$parser = new Less_Parser();
		$parser->parseFile( $lessFile );

		try {
			$parser->getCss();
		} catch ( Exception $e ) {
			$this->assertInstanceOf( Less_Exception_Parser::class, $e );
			$this->assertEquals(
"Operation on an invalid type in f3.less on line 5, column 2
3| 
4| div {
5|  content: @foo * @bar;
6| }",
				$e->getMessage()
			);
		}
	}

	public function testOptionRootpath() {
		// When CSS refers to a URL that is only a hash fragment, it is a
		// dynamic reference to something in the current DOM, thus it must
		// not be remapped. https://phabricator.wikimedia.org/T331649
		$lessCode = '
			div {
				--a10: url("./images/icon.svg");
				--a11: url("./images/icon.svg#myid");
				--a20: url(icon.svg);
				--a21: url(icon.svg#myid);
				--a30: url(#myid);
			}
		';

		$parser = new Less_Parser();
		$parser->parse( $lessCode, '/x/fake.css' );
		$css = trim( $parser->getCss() );

		$expected = <<<CSS
div {
  --a10: url("/x/images/icon.svg");
  --a11: url("/x/images/icon.svg#myid");
  --a20: url(/x/icon.svg);
  --a21: url(/x/icon.svg#myid);
  --a30: url(#myid);
}
CSS;
		$this->assertEquals( $expected, $css );
	}

	public function testOptionFunctions() {
		$lessCode = <<<CSS
			#test {
			  border-width: add(7, 6);
			}
		CSS;
		$expected = <<<CSS
			#test {
			  border-width: 13;
			}
			CSS;

		// test with static callback
		$options = [ 'functions' => [ 'add' => [ __CLASS__, 'fnAdd' ] ] ];
		$parser = new Less_Parser( $options );
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );
		$this->assertSame( $expected, $css, 'static callback' );

		// test with closure
		$parser = new Less_Parser( [
			'functions' => [
				'add' => static function ( $a, $b ) {
					return new Less_Tree_Dimension( $a->value + $b->value );
				}
			]
		] );
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );
		$this->assertSame( $expected, $css, 'closure' );

		// test directly with registerFunction()
		$parser = new Less_Parser();
		$parser->registerFunction( 'add', [ __CLASS__, 'fnAdd' ] );
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );
		$this->assertSame( $expected, $css, 'registerFunction' );

		// test with both passing options and using registerFunction()
		$lessCode = <<<CSS
			#test{
			  border-width: add(2, 3);
			  width: increment(15);
			}
			CSS;
		$expected = <<<CSS
			#test {
			  border-width: 5;
			  width: 16;
			}
			CSS;
		$options = [ 'functions' => [ 'add' => [ __CLASS__, 'fnAdd' ] ] ];
		$parser = new Less_Parser( $options );
		$parser->registerFunction( 'increment', [ __CLASS__, 'fnIncrement' ] );
		$parser->parse( $lessCode );
		$css = trim( $parser->getCss() );
		$this->assertSame( $expected, $css, 'both' );
	}

	public static function fnAdd( $a, $b ) {
		return new Less_Tree_Dimension( $a->value + $b->value );
	}

	public static function fnIncrement( $a ) {
		return new Less_Tree_Dimension( $a->value + 1 );
	}
}
