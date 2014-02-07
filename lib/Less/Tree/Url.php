<?php

/**
 * Url
 *
 * @package Less
 * @subpackage tree
 */
class Less_Tree_Url extends Less_Tree{

	public $attrs;
	public $value;
	public $currentFileInfo;
	public $isEvald;
	public $type = 'Url';

	public function __construct($value, $currentFileInfo = null, $isEvald = null){
		$this->value = $value;
		$this->currentFileInfo = $currentFileInfo;
		$this->isEvald = $isEvald;
	}

	function accept( $visitor ){
		$this->value = $visitor->visitObj($this->value);
	}

    /**
     * @see Less_Tree::genCSS
     */
	function genCSS( $output ){
		$output->add( 'url(' );
		$this->value->genCSS( $output );
		$output->add( ')' );
	}

	/**
	 * @param Less_Functions $ctx
	 */
	public function compile($ctx){
		$val = $this->value->compile($ctx);

		if( !$this->isEvald ){
			// Add the base path if the URL is relative
			if( Less_Parser::$options['relativeUrls']
				&& $this->currentFileInfo
				&& is_string($val->value)
				&& Less_Environment::isPathRelative($val->value)
			){
				$rootpath = $this->currentFileInfo['uri_root'];
				if ( !$val->quote ){
					$rootpath = preg_replace('/[\(\)\'"\s]/', '\\$1', $rootpath );
				}
				$val->value = $rootpath . $val->value;
			}

			$val->value = Less_Environment::normalizePath( $val->value);
		}

		return new Less_Tree_URL($val, $this->currentFileInfo, true);
	}

}
