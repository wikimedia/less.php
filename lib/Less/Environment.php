<?php

//less.js : lib/less/functions.js


class Less_Environment{

	public $paths = array();			// option - unmodified - paths to search for imports on
	static $files = array();			// list of files that have been imported, used for import-once
	public $relativeUrls;				// option - whether to adjust URL's to be relative
	public $rootpath;					// option - rootpath to append to URL's
	public $strictImports = false;		// option -
	public $insecure;					// option - whether to allow imports from insecure ssl hosts
	public $compress = false;			// option - whether to compress
	public $processImports;				// option - whether to process imports. if false then imports will not be imported
	public $javascriptEnabled;			// option - whether JavaScript is enabled. if undefined, defaults to true
	public $useFileCache;				// browser only - whether to use the per file session cache
	public $currentFileInfo;			// information about the current file - for error reporting and importing and making urls relative etc.

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

	public $selectors = array();

	public $charset;

	public $parensStack = array();

	public $strictMath = false;

	public $strictUnits = false;

	public $tabLevel = 0;

	public function __construct( $options = null ){
		$this->frames = array();


		if( isset($options['compress']) ){
			$this->compress = (bool)$options['compress'];
		}
		if( isset($options['strictUnits']) ){
			$this->strictUnits = (bool)$options['strictUnits'];
		}

	}


	//may want to just use the __clone()?
	public function copyEvalEnv($frames = array() ){
		$new_env = clone $this;
		$new_env->frames = $frames;
		return $new_env;
	}

	public function inParenthesis(){
		$this->parensStack[] = true;
	}

	public function outOfParenthesis() {
		array_pop($this->parensStack);
	}

	public function isMathOn() {
		return $this->strictMath ? ($this->parensStack && count($this->parensStack)) : true;
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
	static function NormPath($path){

		$temp = explode('/',$path);
		$result = array();
		foreach($temp as $i => $p){
			if( $p == '.' ){
				continue;
			}
			if( $p == '..' ){
				for($j=$i-1;$j>0;$j--){
					if( isset($result[$j]) ){
						unset($result[$j]);
						continue 2;
					}
				}
			}
			$result[$i] = $p;
		}

		return implode('/',$result);
	}

	/**
	 * @return bool
	 */
	public function getCompress(){
		return $this->compress;
	}

	/**
	 * @param bool $compress
	 * @return void
	 */
	public function setCompress($compress){
		$this->compress = $compress;
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
}
