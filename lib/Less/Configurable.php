<?php

/**
 * Configurable
 *
 * @package Less
 * @subpackage Core
 */
abstract class Less_Configurable {

	/**
	 * Array of options
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Array of default options
	 *
	 * @var array
	 */
	protected $defaultOptions = array();

	/**
	 * Constructor
	 *
	 * @param array $options
	 * @return void
	 */
	public function __construct($options = array()){
		$options += $this->defaultOptions;
		$this->setOptions($options);
	}

	/**
	 * Set options
	 *
	 * If $options is an object it will be converted into an array by called
	 * it's toArray method.
	 *
	 * @throws InvalidArgumentException
	 * @param array|object $options
	 *
	 */
	public function setOptions($options){
		// first convert to array if needed
		if(!is_array($options)){
			if(is_object($options) && is_callable(array($options, 'toArray'))){
				$options = $options->toArray();
			}else{
				throw new Exception(sprintf('Options for "%s" must be an array or a object with ->toArray() method'));
			}
		}

		// combine the passed options with the defaults

		$this->options = array_merge($this->defaultOptions, $options);

	}


	/**
	 * Get an option value by name
	 *
	 * If the option is empty or not set a NULL value will be returned.
	 *
	 * @param string $name
	 * @param mixed $default Default value if confiuration of $name is not present
	 * @return mixed
	 */
	public function getOption($name, $default = null){
		if(isset($this->options[$name])){
			return $this->options[$name];
		}
		return $default;
	}


	/**
	 * Set an option
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setOption($name, $value){
		$this->options[$name] = $value;
	}

}