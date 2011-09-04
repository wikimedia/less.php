<?php

namespace Less\Tests;

use Less\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     */
    public function testCssGeneration($less, $css)
    {
        $parser = new Parser();

        $less = $parser->parse(file_get_contents($less));
        $css = file_get_contents($css);

        $this->assertEquals($css, $less);
    }

    public function provider()
    {
        $less = glob(__DIR__."/Fixtures/less/*.less");
        $css = glob(__DIR__."/Fixtures/css/*.css");

        return array_map(function($less, $css) { return array($less, $css); }, $less, $css);
    }
}
