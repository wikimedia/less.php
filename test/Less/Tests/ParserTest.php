<?php

error_reporting(E_ALL);
ini_set('display_errors',1);


$dir = dirname(dirname(dirname(__DIR__)));
require($dir.'/lib/Less/Parser.php');

class ParserTest{

	function __construct(){

		$pairs = $this->lessJsProvider();
		foreach($pairs as $files){
			echo '<h3>'.basename($files[0]).'</h3>';
			$this->testLessJsCssGeneration($files[0], $files[1]);
		}

	}

    /**
     * @dataProvider lessJsProvider
     */
    public function testLessJsCssGeneration($less, $css)
    {
        $parser = new \Less\Parser();

        $less = $parser->parseFile($less)->getCss();
        $css = file_get_contents($css);

		if( $css === $less ){
			echo 'equal';
			return;
		}
		echo '<b>not equal</b>';
        //$this->assertEquals($css, $less);
    }

    public function lessJsProvider()
    {
        $less = glob(__DIR__."/Fixtures/less.js/less/*.less");
        $css = glob(__DIR__."/Fixtures/less.js/css/*.css");

        return array_map(function($less, $css) { return array($less, $css); }, $less, $css);
    }

    /**
     * @dataProvider lessPhpProvider
     */
    public function testLessPhpCssGeneration($less, $css)
    {
        $parser = new Parser();

        $less = $parser->parseFile($less)->getCss();
        $css = file_get_contents($css);

        $this->assertEquals($css, $less);
    }

    public function lessPhpProvider()
    {
        $less = glob(__DIR__."/Fixtures/less.php/less/*.less");
        $css = glob(__DIR__."/Fixtures/less.php/css/*.css");

        return array_map(function($less, $css) { return array($less, $css); }, $less, $css);
    }

    /**
     * @dataProvider boostrap202Provider
     */
    public function testBoostrap202CssGeneration($less, $css)
    {
        $parser = new Parser();

        $less = $parser->parseFile($less)->getCss();
        $css = file_get_contents($css);

        $this->assertEquals($css, $less);
    }

    public function boostrap202Provider()
    {
		$dir = __DIR__ . "/Fixtures/bootstrap-2.0.2/";
        $less = array(
			$dir . 'less/bootstrap.less',
			$dir . 'less/responsive.less'
		);
        $css = array(
			$dir . 'css/bootstrap.css',
			$dir . 'css/bootstrap-responsive.css'
		);

        return array_map(function($less, $css) { return array($less, $css); }, $less, $css);
    }
}

new ParserTest();
