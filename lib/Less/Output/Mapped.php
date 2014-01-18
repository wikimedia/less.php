<?php

/**
 * Parser output with source map
 *
 * @package Less
 * @subpackage Output
 */
//class Less_Output_Mapped extends Less_Output {
class Less_Output_Mapped{

	/**
	 * The source map generator
	 *
	 * @var Less_SourceMap_Generator
	 */
	protected $generator;

	/**
	 * Current line
	 *
	 * @var integer
	 */
	protected $lineNumber = 0;

	/**
	 * Current column
	 *
	 * @var integer
	 */
	protected $column = 0;

	/**
	 * Array of contents map (file and its content)
	 *
	 * @var array
	 */
	protected $contentsMap = array();

	/**
	 * Constructor
	 *
	 * @param array $contentsMap Array of filename to contents map
	 * @param Less_SourceMap_Generator $generator
	 */
	public function __construct(array $contentsMap, Less_SourceMap_Generator $generator){
		$this->contentsMap = $contentsMap;
		$this->generator = $generator;
	}

	/**
	 * Adds a chunk to the stack
	 *
	 * @param string $chunk
	 * @param string $fileInfo
	 * @param integer $index
	 * @param mixed $mapLines
	 * @return Less_Output
	 */
	public function add($chunk, Less_FileInfo $fileInfo = null, $index = 0, $mapLines = null){
		// nothing to do
		if(!$chunk){
			return $this;
		}

		$sourceLines = array();
		$sourceColumns = ' ';


		if($fileInfo/* && isset($this->contentsMap[$fileInfo->filename])*/){
			$inputSource = substr($this->contentsMap[$fileInfo->importedFile->getPath()], 0, $index);
			$sourceLines = explode("\n", $inputSource);
			$sourceColumns = end($sourceLines);
		}

		$lines = explode("\n", $chunk);
		$columns = end($lines);

		if($fileInfo){
			if(!$mapLines){
				$this->generator->addMapping(
						$this->lineNumber + 1, $this->column, // generated
						count($sourceLines), strlen($sourceColumns), // original
						$fileInfo->filename
				);
			}else{
				for($i = 0, $count = count($lines); $i < $count; $i++){
					$this->generator->addMapping(
						$this->lineNumber + $i + 1, $i === 0 ? $this->column : 0, // generated
						count($sourceLines) + $i, $i === 0 ? strlen($sourceColumns) : 0, // original
						$fileInfo->filename
					);
				}
			}
		}

		if(count($lines) === 1){
			$this->column += strlen($columns);
		}else{
			$this->lineNumber += count($lines) - 1;
			$this->column = strlen($columns);
		}

		// add only chunk
		return parent::add($chunk);
	}

	/**
	 * Returns the generator
	 *
	 * @return Less_SourceMap_Generator
	 */
	public function getGenerator(){
		return $this->generator;
	}

	/**
	 * Sets the generator
	 *
	 * @param Less_SourceMap_Generator $generator
	 * @return Less_Output_Mapped
	 */
	public function setGenerator(Less_SourceMap_Generator $generator){
		$this->generator = $generator;
		return $this;
	}

}