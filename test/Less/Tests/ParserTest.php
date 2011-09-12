<?php

namespace Less\Tests;

use Less\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider lessJsProvider
     */
    public function testLessJsCssGeneration($less, $css)
    {
        $parser = new Parser();

        $less = $parser->parseFile($less)->getCss();
        $css = file_get_contents($css);

        $this->assertEquals($css, $less);
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
}
