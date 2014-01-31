<?php


class FixturesTest extends PHPUnit_Framework_TestCase{

	public $fixtures_dir;
	public $cache_dir;

	function setUp(){
		print_r("\nSet-Up");
		require_once( dirname(__FILE__) . '/../lib/Less/Autoloader.php' );
		Less_Autoloader::register();

		$this->fixtures_dir = dirname(__FILE__).'/Fixtures';
		print_r("\n  fixtures_dir: ".$this->fixtures_dir);

		Less_Cache::$cache_dir = $this->CacheDirectory();
		print_r("\n  cache_dir:    ".Less_Cache::$cache_dir);

		print_r("\n\n");
	}


	/**
	 * Test the contents of the files in /test/Fixtures/lessjs/expected
	 *
	 */
	function testLessJs(){

		print_r("\nBegin Tests");

		$css_dir = $this->fixtures_dir.'/lessjs/expected';
		$files = scandir($css_dir);

		foreach($files as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}

			$expected_file = $css_dir.'/'.$file;

			if( is_dir($expected_file) ){
				continue;
			}

			$this->CompareFile( $expected_file );
		}

		print_r("\n\nTests Complete!!");
	}


	/**
	 * Return the path of the cache directory if it's writable
	 *
	 */
	function CacheDirectory(){
		$cache_dir = dirname(__FILE__).'/_cache';

		if( !file_exists($cache_dir) && !mkdir($cache_dir) ){
			return false;
		}

		if( !is_writable($cache_dir) ){
			return false;
		}

		return $cache_dir;
	}


	/**
	 * Change a css file name to a less file name
	 *
	 * eg: /Fixtures/lessjs/css/filename.css -> /Fixtures/lessjs/less/filename.less
	 *
	 */
	function TranslateFile( $file_css, $dir = 'less', $type = 'less' ){

		$filename = basename($file_css);
		$filename = substr($filename,0,-4);

		return dirname( dirname($file_css) ).'/'.$dir.'/'.$filename.'.'.$type;
	}


	/**
	 * Compare the parser results with the expected css
	 *
	 */
	function CompareFile( $expected_file ){

		$less_file = $this->TranslateFile( $expected_file );
		$expected_css = trim(file_get_contents($expected_file));


		// Check with standard parser
		print_r("\n  ".basename($expected_file));
		print_r("\n    - Standard Compiler");

		$parser = new Less_Parser();
		$parser->parseFile($less_file);
		$css = $parser->getCss();
		$css = trim($css);
		$this->assertEquals( $expected_css, $css );


		// Check with cache
		if( Less_Cache::$cache_dir ){
			print_r("\n    - Regenerating Cache");
			$files = array( $less_file => '' );
			$css_file_name = Less_Cache::Regen( $files );
			$css = file_get_contents(Less_Cache::$cache_dir.'/'.$css_file_name);
			$css = trim($css);
			$this->assertEquals( $expected_css, $css );



			// Check using the cached data
			print_r("\n    - Using Cache");
			$css_file_name = Less_Cache::Get( $files );
			$css = file_get_contents(Less_Cache::$cache_dir.'/'.$css_file_name);
			$css = trim($css);
			$this->assertEquals( $expected_css, $css );

		}


	}


}