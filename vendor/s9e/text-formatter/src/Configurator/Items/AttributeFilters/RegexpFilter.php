<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\Regexp;
class RegexpFilter extends AttributeFilter
{
	public function __construct($regexp = \null)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterRegexp');
		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('regexp');
		$this->setJS('BuiltInFilters.filterRegexp');
		if (isset($regexp))
			$this->setRegexp($regexp);
	}
	public function asConfig()
	{
		if (!isset($this->vars['regexp']))
			throw new RuntimeException("Regexp filter is missing a 'regexp' value");
		return parent::asConfig();
	}
	public function getRegexp()
	{
		return (string) $this->vars['regexp'];
	}
	public function setRegexp($regexp)
	{
		if (\is_string($regexp))
			$regexp = new Regexp($regexp);
		$this->vars['regexp'] = $regexp;
		$this->resetSafeness();
		$this->evaluateSafeness();
	}
	protected function evaluateSafeness()
	{
		try
		{
			$this->evaluateSafenessAsURL();
			$this->evaluateSafenessInCSS();
			$this->evaluateSafenessInJS();
		}
		catch (Exception $e)
		{
			}
	}
	protected function evaluateSafenessAsURL()
	{
		$regexpInfo = RegexpParser::parse($this->vars['regexp']);
		$captureStart = '(?>\\((?:\\?:)?)*';
		$regexp = '#^\\^' . $captureStart . '(?!data|\\w*script)[a-z0-9]+\\??:#i';
		if (\preg_match($regexp, $regexpInfo['regexp'])
		 && \strpos($regexpInfo['modifiers'], 'm') === \false)
		{
			$this->markAsSafeAsURL();
			return;
		}
		$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);
		foreach (ContextSafeness::getDisallowedCharactersAsURL() as $char)
			if (\preg_match($regexp, $char))
				return;
		$this->markAsSafeAsURL();
	}
	protected function evaluateSafenessInCSS()
	{
		$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);
		foreach (ContextSafeness::getDisallowedCharactersInCSS() as $char)
			if (\preg_match($regexp, $char))
				return;
		$this->markAsSafeInCSS();
	}
	protected function evaluateSafenessInJS()
	{
		$safeExpressions = array(
			'\\d+',
			'[0-9]+'
		);
		$regexp = '(^(?<delim>.)\\^(?:(?<expr>' . \implode('|', \array_map('preg_quote', $safeExpressions)) . ')|\\((?:\\?[:>])?(?&expr)\\))\\$(?&delim)(?=.*D)[Dis]*$)D';
		if (\preg_match($regexp, $this->vars['regexp']))
			$this->markAsSafeInJS();
	}
}