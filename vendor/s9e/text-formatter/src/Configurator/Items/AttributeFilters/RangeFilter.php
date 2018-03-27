<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
class RangeFilter extends AttributeFilter
{
	public function __construct($min = \null, $max = \null)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterRange');
		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('min');
		$this->addParameterByName('max');
		$this->addParameterByName('logger');
		$this->setJS('BuiltInFilters.filterRange');
		$this->markAsSafeAsURL();
		$this->markAsSafeInCSS();
		$this->markAsSafeInJS();
		if (isset($min))
			$this->setRange($min, $max);
	}
	public function asConfig()
	{
		if (!isset($this->vars['min']))
			throw new RuntimeException("Range filter is missing a 'min' value");
		if (!isset($this->vars['max']))
			throw new RuntimeException("Range filter is missing a 'max' value");
		return parent::asConfig();
	}
	public function setRange($min, $max)
	{
		$min = \filter_var($min, \FILTER_VALIDATE_INT);
		$max = \filter_var($max, \FILTER_VALIDATE_INT);
		if ($min === \false)
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be an integer');
		if ($max === \false)
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be an integer');
		if ($min > $max)
			throw new InvalidArgumentException('Invalid range: min (' . $min . ') > max (' . $max . ')');
		$this->vars['min'] = $min;
		$this->vars['max'] = $max;
	}
}