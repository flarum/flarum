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
	 * Set options
	 *
	 * If $options is an object it will be converted into an array by called
	 * it's toArray method.
	 *
	 * @throws Exception
	 * @param array|object $options
	 *
	 */
	public function setOptions($options){
		$options = array_intersect_key($options,$this->defaultOptions);
		$this->options = array_merge($this->defaultOptions, $this->options, $options);
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