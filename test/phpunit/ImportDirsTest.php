<?php

class phpunit_ImportDirsTest extends phpunit_bootstrap {

	public static function provideConsumeSomevars() {
		yield [
			'from-importdir.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
			],
			'div{font-family:monospace}'
		];
		yield [
			'from-importdir-file.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
			],
			'div{font-family:fantasy}'
		];
		yield [
			'from-importdir-filenosuffix.less',
			[
				__DIR__ . '/data/importdir-somevars/' => '',
			],
			'div{font-family:fantasy}'
		];
		yield [
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
		yield [
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
	 * @dataProvider provideConsumeSomevars
	 */
	public function testConsumeSomevars( string $input, array $importDirs, string $expect ) {
		$file = __DIR__ . '/data/consume-somevars/' . $input;

		$parser = new Less_Parser( [
			'compress' => true,
		] );
		$parser->SetImportDirs( $importDirs );
		$parser->parseFile( $file );

		$this->assertEquals( $expect, $parser->getCss() );
	}
}
