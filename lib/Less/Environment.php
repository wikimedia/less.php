<?php

//less.js : lib/less/functions.js


class Less_Environment{

	public $paths = array();				// option - unmodified - paths to search for imports on
	static $files = array();				// list of files that have been imported, used for import-once
	public $relativeUrls = true;			// option - whether to adjust URL's to be relative
	public $rootpath;						// option - rootpath to append to URL's
	public $strictImports = null;			// option -
	public $insecure;						// option - whether to allow imports from insecure ssl hosts
	public static $compress = false;		// option - whether to compress
	public $processImports;					// option - whether to process imports. if false then imports will not be imported
	public $javascriptEnabled;				// option - whether JavaScript is enabled. if undefined, defaults to true
	public $useFileCache;					// browser only - whether to use the per file session cache
	public $currentFileInfo;				// information about the current file - for error reporting and importing and making urls relative etc.

	public static $strictMath = false;		// whether math has to be within parenthesis
	public static $strictUnits = false;		// whether units need to evaluate correctly
	public $sourceMap = false;				// whether to output a source map
	public $importMultiple = false; 		// whether we are currently importing multiple copies


	/**
	 * @var array
	 */
	public $frames = array();


	/**
	 * @var bool
	 */
	public $debug = false;

	/**
	 * @var array
	 */
	public $mediaBlocks = array();

	/**
	 * @var array
	 */
	public $mediaPath = array();

	public $charset;

	public $parensStack = 0;

	public static $tabLevel = 0;

	public static $lastRule = false;



	/**
	 * Filename to contents of all parsed the files
	 *
	 * @var array
	 */
	public static $contentsMap = array();



	public static $comma_space;
	public static $colon_space;
	public static $firstSelector;


	/**
	 * @param array|null $options
	 */
	public function __construct( $options = null ){
		$this->frames = array();


		if( isset($options['compress']) ){
			self::$compress = (bool)$options['compress'];
		}
		if( isset($options['strictUnits']) ){
			self::$strictUnits = (bool)$options['strictUnits'];
		}
		if( isset($options['sourceMap']) ){
			$this->sourceMap = (bool)$options['sourceMap'];
		}
		if( isset($options['relativeUrls']) ){
			$this->relativeUrls = (bool)$options['relativeUrls'];
		}

		if( self::$compress ){
			self::$comma_space = ',';
			self::$colon_space = ':';
		}else{
			self::$comma_space = ', ';
			self::$colon_space = ': ';
		}
	}


	public function copyEvalEnv($frames = array() ){
		$new_env = new Less_Environment();
		$new_env->frames = $frames;
		return $new_env;
	}

	public function inParenthesis(){
		$this->parensStack++;
	}

	public function outOfParenthesis() {
		$this->parensStack--;
	}

	public function isMathOn(){
		return !Less_Environment::$strictMath || $this->parensStack;
	}

	public static function isPathRelative($path){
		return !preg_match('/^(?:[a-z-]+:|\/)/',$path);
	}


	/**
	 * Canonicalize a path by resolving references to '/./', '/../'
	 * Does not remove leading "../"
	 * @param string path or url
	 * @return string Canonicalized path
	 *
	 */
	static function normalizePath($path){

		$segments = explode('/',$path);
		$segments = array_reverse($segments);

		$path = array();
		$path_len = 0;

		while( $segments ){
			$segment = array_pop($segments);
			switch( $segment ) {

				case '.':
				break;

				case '..':
					if( !$path_len || ( $path[$path_len-1] === '..') ){
						$path[] = $segment;
						$path_len++;
					}else{
						array_pop($path);
						$path_len--;
					}
				break;

				default:
					$path[] = $segment;
					$path_len++;
				break;
			}
		}

		return implode('/',$path);
	}


	/**
	 * @return bool
	 */
	public function getDebug(){
		return $this->debug;
	}

	/**
	 * @param $debug
	 * @return void
	 */
	public function setDebug($debug){
		$this->debug = $debug;
	}

	public function unshiftFrame($frame){
		array_unshift($this->frames, $frame);
	}

	public function shiftFrame(){
		return array_shift($this->frames);
	}

	public function addFrame($frame){
		$this->frames[] = $frame;
	}

	public function addFrames(array $frames){
		$this->frames = array_merge($this->frames, $frames);
	}


	/**
	 * Returns the contents map
	 *
	 * @return array
	 */
	public function getContentsMap(){
		return self::$contentsMap;
	}

	/**
	 * Sets file contents to the map
	 *
	 * @param string $filePath
	 * @return Less_Environment
	 */
	public function setFileContent($filePath){
		if( $this->sourceMap && $filePath ){
			self::$contentsMap[$filePath] = file_get_contents($filePath);
		}
	}
}
