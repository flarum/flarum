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
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\Regexp;
class MapFilter extends AttributeFilter
{
	public function __construct(array $map = \null, $caseSensitive = \false, $strict = \false)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterMap');
		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('map');
		$this->setJS('BuiltInFilters.filterMap');
		if (isset($map))
			$this->setMap($map, $caseSensitive, $strict);
	}
	public function asConfig()
	{
		if (!isset($this->vars['map']))
			throw new RuntimeException("Map filter is missing a 'map' value");
		return parent::asConfig();
	}
	public function setMap(array $map, $caseSensitive = \false, $strict = \false)
	{
		if (!\is_bool($caseSensitive))
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be a boolean');
		if (!\is_bool($strict))
			throw new InvalidArgumentException('Argument 3 passed to ' . __METHOD__ . ' must be a boolean');
		$this->resetSafeness();
		if ($strict)
			$this->assessSafeness($map);
		$valueKeys = array();
		foreach ($map as $key => $value)
			$valueKeys[$value][] = $key;
		$map = array();
		foreach ($valueKeys as $value => $keys)
		{
			$regexp = RegexpBuilder::fromList(
				$keys,
				array(
					'delimiter'       => '/',
					'caseInsensitive' => !$caseSensitive
				)
			);
			$regexp = '/^' . $regexp . '$/D';
			if (!$caseSensitive)
				$regexp .= 'i';
			if (!\preg_match('#^[[:ascii:]]*$#D', $regexp))
				$regexp .= 'u';
			$map[] = array(new Regexp($regexp), $value);
		}
		if ($strict)
			$map[] = array('//', \false);
		$this->vars['map'] = $map;
	}
	protected function assessSafeness(array $map)
	{
		$values = \implode('', $map);
		$isSafeInCSS = \true;
		foreach (ContextSafeness::getDisallowedCharactersInCSS() as $char)
			if (\strpos($values, $char) !== \false)
			{
				$isSafeInCSS = \false;
				break;
			}
		if ($isSafeInCSS)
			$this->markAsSafeInCSS();
		$isSafeInJS = \true;
		foreach (ContextSafeness::getDisallowedCharactersInJS() as $char)
			if (\strpos($values, $char) !== \false)
			{
				$isSafeInJS = \false;
				break;
			}
		if ($isSafeInJS)
			$this->markAsSafeInJS();
	}
}