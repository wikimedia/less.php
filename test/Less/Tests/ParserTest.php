<?php

error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors',1);


//get parser
$dir = dirname(dirname(dirname(__DIR__)));
require($dir.'/lib/Less/Parser.php');

//get diff
require( $dir. '/test/Less/Tests/php-diff/lib/Diff.php');
require( $dir. '/test/Less/Tests/php-diff/lib/Diff/Renderer/Html/SideBySide.php');
require( $dir. '/test/Less/Tests/php-diff/lib/Diff/Renderer/Html/Inline.php');


global $head;

class ParserTest{

	function __construct(){

		$pairs = $this->lessJsProvider();
		foreach($pairs as $files){
			$basename = basename($files[0]);
			$basename = substr($basename,0,-5); //remove .less extension
			echo '<h3><a href="?file='.$basename.'">'.$basename.'</a></h3>';
			$this->testLessJsCssGeneration($files[0], $files[1]);
		}

	}

    /**
     * @dataProvider lessJsProvider
     */
    public function testLessJsCssGeneration($less, $css){
		$parser = new \Less\Parser();

		$compiled = $parser->parseFile($less)->getCss();
		$css = file_get_contents($css);

		if( empty($compiled) ){
			echo '<b>----empty----</b>';
			return;
		}
		if( $css === $compiled ){
			echo 'equal';
			return;
		}

		$compiled = explode("\n", $compiled );
		$css = explode("\n", $css );


		$options = array();
		$diff = new Diff($compiled, $css, $options);
		$renderer = new Diff_Renderer_Html_SideBySide();
		//$renderer = new Diff_Renderer_Html_Inline();
		echo $diff->Render($renderer);

		$pos = strpos($less,'/less.php');

		global $head;
		if( isset($_GET['file']) ){
			$head .= '<link rel="stylesheet/less" type="text/css" href="'.substr($less,$pos).'" />';
		}
		//echo '<textarea>'.htmlspecialchars(file_get_contents($less)).'</textara>';

    }

    public function lessJsProvider(){

		$dir = __DIR__.'/Fixtures/less.js';
		if( isset($_GET['file']) ){
			$less = (array)($dir.'/less/'.$_GET['file'].'.less');
			$css = (array)($dir.'/css/'.$_GET['file'].'.css');
		}else{
			$less = glob($dir."/less/*.less");
			$css = glob($dir."/css/*.css");
		}

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

    public function lessPhpProvider(){
		$dir = __DIR__.'/Fixtures/less.php/less/';
		$less = glob($dir.'/*.less');
		$css = glob($dir.'/*.css');

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

ob_start();
new ParserTest();
$content = ob_get_clean();

?>
<!DOCTYPE html>
<html><head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Parser Tests</title>
<link rel="stylesheet" href="/less.php/test/Less/Tests/php-diff/styles.css" type="text/css" />
<?php echo $head ?>
</head>
<body>
<?php

echo $content;

if( isset($_GET['file']) ){
	echo '<script src="/less.php/test/Less/Tests/less-1.4.0.js" ></script>';
}
?>
</body></html>
