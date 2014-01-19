<?php

/**
 * Source map generator
 *
 * @package Less
 * @subpackage Output
 */
class Less_SourceMap_Generator extends Less_Configurable {

	/**
	 * What version of source map does the generator generate?
	 */
	const VERSION = 3;

	/**
	 * Array of default options
	 *
	 * @var array
	 */
	protected $defaultOptions = array(
			// an optional source root, useful for relocating source files
			// on a server or removing repeated values in the 'sources' entry.
			// This value is prepended to the individual entries in the 'source' field.
			'sourceRoot' => '',
			// an optional name of the generated code that this source map is associated with.
			'filename' => null,
			// url of the map
			'url' => null,
			// absolute path to a file to write the map to
			'write_to' => null,
			// output source contents?
			'source_contents' => false,
			// base path for filename normalization
			'base_path' => ''
	);

	/**
	 * The base64 VLQ encoder
	 *
	 * @var Less_SourceMap_Base64VLQ
	 */
	protected $encoder;

	/**
	 * Array of mappings
	 *
	 * @var array
	 */
	protected $mappings = array();

	/**
	 * The root node
	 *
	 * @var Less_Tree_Ruleset
	 */
	protected $root;

	/**
	 * Array of contents map
	 *
	 * @var array
	 */
	protected $contentsMap = array();

	/**
	 * File to content map
	 *
	 * @var array
	 */
	protected $sources = array();

	/**
	 * Constructor
	 *
	 * @param Less_Tree_Ruleset $root The root node
	 * @param array $options Array of options
	 * @param Less_SourceMap_Base64VLQ $encoder The encoder
	 */
	public function __construct(Less_Tree_Ruleset $root, array $contentsMap, $options = array(), Less_SourceMap_Base64VLQ $encoder = null){
		$this->root = $root;
		$this->contentsMap = $contentsMap;
		$this->encoder = $encoder ? $encoder : new Less_SourceMap_Base64VLQ();
		parent::__construct($options);
	}

	/**
	 * Setups the generator
	 *
	 */
	protected function setup(){
		// fix windows paths
		if($basePath = $this->getOption('base_path')){
			$basePath = str_replace('\\', '/', $basePath);
			$this->setOption('base_path', $basePath);
		}
	}

	/**
	 * Generates the CSS
	 *
	 * @param Less_Environment $env
	 * @return string
	 */
	public function generateCSS(Less_Environment $env){
		//$output = new Less_Output();
		$output = new Less_Output_Mapped($this->contentsMap, $this);

		// catch the output
		$this->root->genCSS($env, $output);


		// prepare sources
		/*
		foreach($this->contentsMap as $filename => $contents){
			// match md5 hash in square brackets _[#HASH#]_
			// see Less_Parser_Core::parseString()
			if(preg_match('/(\[__[0-9a-f]{32}__\])+$/', $filename)){
				$filename = substr($filename, 0, -38);
			}

			$this->sources[$this->normalizeFilename($filename)] = $contents;
		}
		*/

		$sourceMapUrl = null;
		if($url = $this->getOption('url')){
			$sourceMapUrl = $url;
		}elseif($path = $this->getOption('filename')){
			$sourceMapUrl = $this->normalizeFilename($path);
			// naming conventions, make it foobar.css.map
			if(!preg_match('/\.map$/', $sourceMapUrl)){
				$sourceMapUrl = sprintf('%s.map', $sourceMapUrl);
			}
		}

		$sourceMapContent = $this->generateJson();
		// write map to a file
		if($file = $this->getOption('write_to')){
			// FIXME: should this happen here?
			$this->saveMap($file, $sourceMapContent);

		// inline the map
		}else{
			$sourceMapUrl = sprintf('data:application/json,%s', Less_Util::encodeURIComponent($sourceMapContent));
		}

		if($sourceMapUrl){
			$output->add( sprintf('/*# sourceMappingURL=%s */', $sourceMapUrl) );
		}

		return $output->toString();
	}

	/**
	 * Saves the source map to a file
	 *
	 * @param string $file The absolute path to a file
	 * @param string $content The content to write
	 * @throws Exception If the file could not be saved
	 */
	protected function saveMap($file, $content){
		$dir = dirname($file);
		// directory does not exist
		if(!is_dir($dir)){
			// FIXME: create the dir automatically?
			throw new Exception(sprintf('The directory "%s" does not exist. Cannot save the source map.', $dir));
		}
		// FIXME: proper saving, with dir write check!
		if(file_put_contents($file, $content) === false){
			throw new Exception(sprintf('Cannot save the source map to "%s"', $file));
		}
		return true;
	}

	/**
	 * Normalizes the filename
	 *
	 * @param string $filename
	 * @return string
	 */
	protected function normalizeFilename($filename){
		$filename = str_replace('\\', '/', $filename);
		if(($basePath = $this->getOption('base_path'))
				&& ($pos = strpos($filename, $basePath)) !== false){
			$filename = substr($filename, $pos + strlen($basePath));
			if(strpos($filename, '\\') === 0 || strpos($filename, '/') === 0){
				$filename = substr($filename, 1);
			}
		}
		return sprintf('%s%s', $this->getOption('root_path'), $filename);
	}

	/**
	 * Adds a mapping
	 *
	 * @param integer $generatedLine The line number in generated file
	 * @param integer $generatedColumn The column number in generated file
	 * @param integer $originalLine The line number in original file
	 * @param integer $originalColumn The column number in original file
	 * @param string $sourceFile The original source file
	 * @return Less_SourceMap_Generator
	 */
	public function addMapping($generatedLine, $generatedColumn, $originalLine, $originalColumn, $sourceFile){
		$this->mappings[] = array(
			'generated_line' => $generatedLine,
			'generated_column' => $generatedColumn,
			'original_line' => $originalLine,
			'original_column' => $originalColumn,
			'source_file' => $sourceFile
		);


		$norm_file = $this->normalizeFilename($sourceFile);

		$this->sources[$norm_file] = 1;
	}

	/**
	 * Clear the mappings
	 *
	 * @return Less_SourceMap_Generator
	 */
	public function clear(){
		$this->mappings = array();
		return $this;
	}

	/**
	 * Sets the encoder
	 *
	 * @param Less_SourceMap_Base64VLQ $encoder
	 * @return Less_SourceMap_Generator
	 */
	public function setEncoder(Less_SourceMap_Base64VLQ $encoder){
		$this->encoder = $encoder;
		return $this;
	}

	/**
	 * Returns the encoder
	 *
	 * @return Less_SourceMap_Base64VLQ
	 */
	public function getEncoder(){
		return $this->encoder;
	}

	/**
	 * Generates the JSON source map
	 *
	 * @return string
	 * @see https://docs.google.com/document/d/1U1RGAehQwRypUTovF1KRlpiOFze0b-_2gc6fAH0KY0k/edit#
	 */
	protected function generateJson(){

		$sourceMap = array();
		$mappings = $this->generateMappings();

		// File version (always the first entry in the object) and must be a positive integer.
		$sourceMap['version'] = self::VERSION;


		// An optional name of the generated code that this source map is associated with.
		$file = $this->getOption('filename');
		if( $file ){
			$sourceMap['file'] = $file;
		}


		// An optional source root, useful for relocating source files on a server or removing repeated values in the 'sources' entry.	This value is prepended to the individual entries in the 'source' field.
		$root = $this->getOption('sourceRoot');
		if( $root ){
			$sourceMap['sourceRoot'] = $root;
		}


		// A list of original sources used by the 'mappings' entry.
		$sourceMap['sources'] = array_keys($this->sources);



		// A list of symbol names used by the 'mappings' entry.
		$sourceMap['names'] = array();
		// A string with the encoded mapping data.
		$sourceMap['mappings'] = $mappings;

		if( $this->getOption('source_contents') ){
			// An optional list of source content, useful when the 'source' can't be hosted.
			// The contents are listed in the same order as the sources above.
			// 'null' may be used if some original sources should be retrieved by name.
			$sourceMap['sourcesContent'] = $this->getSourcesContent();
		}

		// less.js compat fixes
		if( count($sourceMap['sources']) && empty($sourceMap['sourceRoot']) ){
			unset($sourceMap['sourceRoot']);
		}

		return json_encode($sourceMap);
	}

	/**
	 * Returns the sources contents
	 *
	 * @return array|null
	 */
	protected function getSourcesContent(){
		if(empty($this->sources)){
			return;
		}
		// FIXME: we should output only those which were used
		return array_values($this->sources);
	}

	/**
	 * Generates the mappings string
	 *
	 * @return string
	 */
	public function generateMappings(){

		if( !count($this->mappings) ){
			return '';
		}

		// group mappings by generated line number.
		$groupedMap = $groupedMapEncoded = array();
		foreach($this->mappings as $m){
			$groupedMap[$m['generated_line']][] = $m;
		}
		ksort($groupedMap);

		$lastGeneratedLine = $lastOriginalIndex = $lastOriginalLine = $lastOriginalColumn = 0;

		foreach($groupedMap as $lineNumber => $line_map){
			while(++$lastGeneratedLine < $lineNumber){
				$groupedMapEncoded[] = ';';
			}

			$lineMapEncoded = array();
			$lastGeneratedColumn = 0;

			foreach($line_map as $m){
				$mapEncoded = $this->encoder->encode($m['generated_column'] - $lastGeneratedColumn);
				$lastGeneratedColumn = $m['generated_column'];

				// find the index
				if($m['source_file'] &&
						($index = $this->findFileIndex($this->normalizeFilename($m['source_file']))) !== false){
					$mapEncoded .= $this->encoder->encode($index - $lastOriginalIndex);
					$lastOriginalIndex = $index;

					// lines are stored 0-based in SourceMap spec version 3
					$mapEncoded .= $this->encoder->encode($m['original_line'] - 1 - $lastOriginalLine);
					$lastOriginalLine = $m['original_line'] - 1;

					$mapEncoded .= $this->encoder->encode($m['original_column'] - $lastOriginalColumn);
					$lastOriginalColumn = $m['original_column'];
				}

				$lineMapEncoded[] = $mapEncoded;
			}

			$groupedMapEncoded[] = implode(',', $lineMapEncoded) . ';';
		}

		return rtrim(implode($groupedMapEncoded), ';');
	}

	/**
	 * Finds the index for the filename
	 *
	 * @param string $filename
	 * @return integer|false
	 */
	protected function findFileIndex($filename){
		return array_search($filename, array_keys($this->sources));
	}

}