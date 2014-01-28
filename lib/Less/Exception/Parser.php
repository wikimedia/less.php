<?php


class Less_Exception_Parser extends Exception{

	/**
	 * The current file
	 *
	 * @var Less_ImportedFile
	 */
	private $currentFile;

	/**
	 * The current parser index
	 *
	 * @var integer
	 */
	private $index;

	private $content;

	private $details = array();


	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param Exception $previous Previous exception
	 * @param integer $index The current parser index
	 * @param Less_FileInfo|string $currentFile The file
	 * @param integer $code The exception code
	 */
	public function __construct($message = null, Exception $previous = null, $index = null, $currentFile = null, $code = 0){

		if (PHP_VERSION_ID < 50300) {
			$this->previous = $previous;
			parent::__construct($message, $code);
		} else {
			parent::__construct($message, $code, $previous);
		}

		$this->currentFile = $currentFile;
		$this->index = $index;

		$this->message = $this->genMessage();
	}



	/**
	 * Returns current line from the file
	 *
	 * @return integer|false If the index is not present
	 */
	private function getDetails(){

		if( $this->currentFile && $this->currentFile['filename'] ){
			$this->details['filename'] = $this->currentFile['filename'];
		}

		if( $this->index !== null ){

			if( $this->currentFile && $this->currentFile['filename'] ){

				$this->content = file_get_contents( $this->currentFile['filename'] );

				$this->details['line'] = self::getLineNumber();
				$this->details['column'] = self::getColumn();
				$this->details['index'] = $this->index;


				$this->details['snippet_start'] = max(0,($this->index - 10));

				$snippet = substr($this->content, $this->details['snippet_start'], 20);
				$this->details['snippet'] = preg_replace('/\s/',' ', $snippet);
			}else{
				$this->details['index'] = $this->index;
			}
		}

		$previous = null;
		// PHP 5.3
		if (method_exists($this, 'getPrevious')) {
			$previous = $this->getPrevious();
		} // PHP 5.2
		elseif (isset($this->previous)) {
			$previous = $this->previous;
		}

		if ($previous) {
			$this->details['previous'] = sprintf(", caused by %s, %s\n%s", get_class($previous), $previous->getMessage(), $previous->getTraceAsString());
		}
	}


	/**
	 * Converts the exception to string
	 *
	 * @return string
	 */
	public function genMessage(){
		$string = $this->message;

		$this->getDetails();

		//format details
		foreach($this->details as $k => $v){
			$string .= "\n    ".str_pad($k.':',15). $v;
		}

		return $string;
	}

	/**
	 * Returns the line number the error was encountered
	 *
	 * @return integer
	 */
	public function getLineNumber(){
		return substr_count($this->content, "\n", 0, $this->index) + 1;
	}


	/**
	 * Returns the column the error was encountered
	 *
	 * @return integer
	 */
	public function getColumn(){

		$part = substr($this->content, 0, $this->index);
		$pos = strrpos($part,"\n");
		return $this->index - $pos;
	}

}
