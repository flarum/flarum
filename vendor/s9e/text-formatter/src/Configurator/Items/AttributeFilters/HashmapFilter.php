<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
class HashmapFilter extends AttributeFilter
{
	public function __construct(array $map = \null, $strict = \false)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterHashmap');
		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('map');
		$this->addParameterByName('strict');
		$this->setJS('BuiltInFilters.filterHashmap');
		if (isset($map))
			$this->setMap($map, $strict);
	}
	public function asConfig()
	{
		if (!isset($this->vars['map']))
			throw new RuntimeException("Hashmap filter is missing a 'map' value");
		return parent::asConfig();
	}
	public function setMap(array $map, $strict = \false)
	{
		if (!\is_bool($strict))
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be a boolean');
		if (!$strict)
			$map = $this->optimizeLooseMap($map);
		\ksort($map);
		$this->vars['map']    = $map;
		$this->vars['strict'] = $strict;
		$this->resetSafeness();
		if (!empty($this->vars['strict']))
		{
			$this->evaluateSafenessInCSS();
			$this->evaluateSafenessInJS();
		}
	}
	protected function evaluateSafenessInCSS()
	{
		$disallowedChars = ContextSafeness::getDisallowedCharactersInCSS();
		foreach ($this->vars['map'] as $value)
			foreach ($disallowedChars as $char)
				if (\strpos($value, $char) !== \false)
					return;
		$this->markAsSafeInCSS();
	}
	protected function evaluateSafenessInJS()
	{
		$disallowedChars = ContextSafeness::getDisallowedCharactersInJS();
		foreach ($this->vars['map'] as $value)
			foreach ($disallowedChars as $char)
				if (\strpos($value, $char) !== \false)
					return;
		$this->markAsSafeInJS();
	}
	protected function optimizeLooseMap(array $map)
	{
		foreach ($map as $k => $v)
			if ($k === $v)
				unset($map[$k]);
		return $map;
	}
}