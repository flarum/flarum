<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use ArrayObject;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
class Dictionary extends ArrayObject implements FilterableConfigValue
{
	public function filterConfig($target)
	{
		$value = $this->getArrayCopy();
		if ($target === 'JS')
			$value = new Dictionary(ConfigHelper::filterConfig($value, $target));
		return $value;
	}
}