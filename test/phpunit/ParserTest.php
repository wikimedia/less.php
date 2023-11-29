<?php

class phpunit_ParserTest extends phpunit_bootstrap {
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
		$css = $parser->getCss();

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

	public function testAllParsedFiles() {
		$parser = new Less_Parser();
		$baseDir = Less_Parser::WinPath( realpath( $this->fixtures_dir . '/less.php/less' ) );
		$parser->parseFile( $baseDir . '/imports.less' );
		$parser->getCss();

		$files = $parser->AllParsedFiles();
		$files = array_map( fn ( $file ) => str_replace( $baseDir, '', $file ), $files );

		$this->assertEqualsCanonicalizing(
			[
				'/imports.less',
				'/imports/b.less',
				'/imports/a.less'
			],
			$files
		);
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
}
