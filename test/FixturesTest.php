<?php


class FixturesTest extends PHPUnit_Framework_TestCase{

	public $dir_fixtures;

	function setUp(){
		require_once( dirname(__FILE__) . '/../lib/Less/Autoloader.php' );
		Less_Autoloader::register();

		$this->dir_fixtures = dirname(__FILE__).'/Fixtures';
	}


	/**
	 * Test the contents of the files in /test/Fixtures/less.js/expected
	 *
	 */
	function testLessJs(){

		$css_dir = $this->dir_fixtures.'/less.js/expected';
		$files = scandir($css_dir);

		foreach($files as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}

			$file_css = $css_dir.'/'.$file;

			if( is_dir($file_css) ){
				continue;
			}

			$this->CompareFile( $file_css );
		}

	}


	/**
	 * Change a css file name to a less file name
	 *
	 * eg: /Fixtures/less.js/css/filename.css -> /Fixtures/less.js/less/filename.less
	 *
	 */
	function TranslateFile( $file_css, $dir = 'less', $type = 'less' ){

		$filename = basename($file_css);
		$filename = substr($filename,0,-4);

		return dirname( dirname($file_css) ).'/'.$dir.'/'.$filename.'.'.$type;
	}

	function CompareFile( $file_css ){

		$file_less = $this->TranslateFile( $file_css );

		$parser = new Less_Parser();
		$parser->parseFile($file_less);

        $css = $parser->getCss();
        $css = trim($css);

        $less = trim(file_get_contents($file_css));

        $this->assertEquals( $less, $css );
	}


}