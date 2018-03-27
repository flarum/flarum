<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
class ConfigValue
{
	protected $isDeduplicated = \false;
	protected $name;
	protected $useCount = 0;
	protected $value;
	protected $varName;
	public function __construct($value, $varName)
	{
		$this->value   = $value;
		$this->varName = $varName;
	}
	public function deduplicate()
	{
		if ($this->useCount > 1)
		{
			$this->isDeduplicated = \true;
			$this->decrementUseCount($this->useCount - 1);
		}
	}
	public function getUseCount()
	{
		return $this->useCount;
	}
	public function getValue()
	{
		return $this->value;
	}
	public function getVarName()
	{
		return $this->varName;
	}
	public function incrementUseCount()
	{
		++$this->useCount;
	}
	public function isDeduplicated()
	{
		return $this->isDeduplicated;
	}
	protected function decrementUseCount($step = 1)
	{
		$this->useCount -= $step;
		foreach ($this->value as $value)
			if ($value instanceof ConfigValue)
				$value->decrementUseCount($step);
	}
}