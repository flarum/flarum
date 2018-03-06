<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
class ChoiceFilter extends RegexpFilter
{
	public function __construct(array $values = \null, $caseSensitive = \false)
	{
		parent::__construct();
		if (isset($values))
			$this->setValues($values, $caseSensitive);
	}
	public function setValues(array $values, $caseSensitive = \false)
	{
		if (!\is_bool($caseSensitive))
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be a boolean');
		$regexp = RegexpBuilder::fromList($values, array('delimiter' => '/'));
		$regexp = '/^' . $regexp . '$/D';
		if (!$caseSensitive)
			$regexp .= 'i';
		if (!\preg_match('#^[[:ascii:]]*$#D', $regexp))
			$regexp .= 'u';
		$this->setRegexp($regexp);
	}
}