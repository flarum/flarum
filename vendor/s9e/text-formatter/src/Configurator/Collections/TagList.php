<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\Validators\TagName;
class TagList extends NormalizedList
{
	public function normalizeValue($attrName)
	{
		return TagName::normalize($attrName);
	}
	public function asConfig()
	{
		$list = \array_unique($this->items);
		\sort($list);
		return $list;
	}
}