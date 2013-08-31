<?php

error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors',1);


//get parser
$dir = dirname(dirname(dirname(__DIR__)));
//require($dir.'/lib/Less/Parser.php');

ParserTest::IncludeScripts( $dir.'/lib/Less' );


//get diff
require( $dir. '/test/Less/Tests/php-diff/lib/Diff.php');
require( $dir. '/test/Less/Tests/php-diff/lib/Diff/Renderer/Html/SideBySide.php');
require( $dir. '/test/Less/Tests/php-diff/lib/Diff/Renderer/Html/Inline.php');



//

global $head;

class ParserTest{

	function __construct(){

		$dir = __DIR__ .'/Fixtures/less.js';
		$this->lessJsProvider($dir);
	}

    public function lessJsProvider($dir){

		if( isset($_GET['file']) ){
			$less = '/less/'.$_GET['file'].'.less';
			$css = '/css/'.$_GET['file'].'.css';
			$pairs = array( array($less,$css) );

		}else{

			$list = scandir($dir.'/less');
			foreach($list as $file){
				if( strpos($file,'.less') === false ){
					continue;
				}
				$pairs[] = array('/less/'.$file, '/css/'.str_replace('.less','.css',$file) );
			}

		}

		foreach($pairs as $files){
			$this->testLessJsCssGeneration( $dir, $files[0], $files[1] );
		}

		return;


		$dir = __DIR__ .'/Fixtures/less.js';
		if( isset($_GET['file']) ){
			$less = (array)($dir.'/less/'.$_GET['file'].'.less');
			$css = (array)($dir.'/css/'.$_GET['file'].'.css');
		}else{
			$less = glob($dir."/less/*.less");
			$css = glob($dir."/css/*.css");
		}


        return array_map(function($less, $css) { return array($less, $css); }, $less, $css);
    }

    public function testLessJsCssGeneration($dir, $less, $css){

		$basename = basename($less);
		$basename = substr($basename,0,-5); //remove .less extension
		echo '<br/><a href="?file='.$basename.'">'.$basename.'</a>';

		$less = $dir.$less;
		$css = $dir.$css;

		$compiled = '';
		try{
			$parser = new Less_Parser();
			$parser->parseFile($less);
			$compiled = $parser->getCss();

		}catch(\Exception $e){
			echo '<h1>Parser Error</h1>';
			echo '<p>'.$e->getMessage().'</p>';
		}

		$css = file_get_contents($css);

		if( empty($compiled) && empty($css) ){
			echo '<b>----empty----</b>';
			return;
		}
		if( $css === $compiled ){
			echo ' (equals) ';

			if( !isset($_GET['file']) ){
				return;
			}

		}else{

			$compiled = explode("\n", $compiled );
			$css = explode("\n", $css );


			$options = array();
			$diff = new Diff($compiled, $css, $options);
			$renderer = new Diff_Renderer_Html_SideBySide();
			//$renderer = new Diff_Renderer_Html_Inline();
			echo $diff->Render($renderer);


			if( isset($_GET['file']) ){
				echo '<table style="width:100%"><tr><td>';
				echo '<pre>';
				echo implode("\n",$compiled);
				echo '</pre>';
				echo '</td><td>';
				echo '<pre>';
				echo implode("\n",$css);
				echo '</pre>';
				echo '</td></tr></table>';
			}
		}


		$pos = strpos($less,'/less.php');

		global $head;
		if( isset($_GET['file']) ){
			$head .= '<link rel="stylesheet/less" type="text/css" href="'.substr($less,$pos).'" />';
		}
		//echo '<textarea>'.htmlspecialchars(file_get_contents($less)).'</textara>';

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



	/**
	 * Include the necessary php files
	 *
	 */
	static function IncludeScripts( $dir ){

		$files = scandir($dir);

		usort($files,function($a,$b){
			return strlen($a)-strlen($b);
		});


		$dirs = array();
		foreach($files as $file){
			if( $file == '.' || $file == '..' ){
				continue;
			}

			$full_path = $dir.'/'.$file;
			if( is_dir($full_path) ){
				$dirs[] = $full_path;
				continue;
			}

			if( strpos($file,'.php') !== (strlen($file) - 4) ){
				continue;
			}

			include_once($full_path);
		}

		foreach($dirs as $dir){
			self::IncludeScripts( $dir );
		}

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
	echo '<script src="/less.php/test/Less/Tests/less-1.4.2.js" ></script>';
}


	$max_used = memory_get_peak_usage();
	//$limit = @ini_get('memory_limit'); //need to convert to byte value
	//$percentage = round($max_used/$limit,2);
	echo '<div style="position:absolute;top:-1px;right:0;z-index:10000;padding:5px 10px;background:rgba(255,255,255,0.95);border:1px solid rgba(0,0,0,0.2);font-size:11px">';
	echo '<b>Performance</b>';
	echo '<table>';
	//.'<tr><td>Memory Usage:</td><td> '.number_format(memory_get_usage()).'</td></tr>';
	echo '<tr><td>Memory:</td><td> '.number_format($max_used).'</td></tr>';
	//.'<tr><td>% of Limit:</td><td> '.$percentage.'%</td></tr>';
	echo '<tr><td>Time (Request):</td><td> '.microtime_diff($_SERVER['REQUEST_TIME'],microtime()).'</td></tr>';
	echo '</table>';
	echo '</div>';

function microtime_diff($a, $b = false, $eff = 6) {
	if( !$b ) $b = microtime();
	$a = array_sum(explode(" ", $a));
	$b = array_sum(explode(" ", $b));
	return sprintf('%0.'.$eff.'f', $b-$a);
}



?>
</body></html>
