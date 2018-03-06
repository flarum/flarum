<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Keywords;
use s9e\TextFormatter\Configurator\Collections\NormalizedList;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->collection, $methodName), $args);
	}
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}
	public function count()
	{
		return \count($this->collection);
	}
	public function current()
	{
		return $this->collection->current();
	}
	public function key()
	{
		return $this->collection->key();
	}
	public function next()
	{
		return $this->collection->next();
	}
	public function rewind()
	{
		$this->collection->rewind();
	}
	public function valid()
	{
		return $this->collection->valid();
	}
	protected $attrName = 'value';
	public $caseSensitive = \true;
	protected $collection;
	public $onlyFirst = \false;
	protected $tagName = 'KEYWORD';
	protected function setUp()
	{
		$this->collection = new NormalizedList;
		$this->configurator->tags->add($this->tagName)->attributes->add($this->attrName);
	}
	public function asConfig()
	{
		if (!\count($this->collection))
			return;
		$config = array(
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		);
		if (!empty($this->onlyFirst))
			$config['onlyFirst'] = $this->onlyFirst;
		$keywords = \array_unique(\iterator_to_array($this->collection));
		\sort($keywords);
		$groups   = array();
		$groupKey = 0;
		$groupLen = 0;
		foreach ($keywords as $keyword)
		{
			$keywordLen  = 4 + \strlen($keyword);
			$groupLen   += $keywordLen;
			if ($groupLen > 30000)
			{
				$groupLen = $keywordLen;
				++$groupKey;
			}
			$groups[$groupKey][] = $keyword;
		}
		foreach ($groups as $keywords)
		{
			$regexp = RegexpBuilder::fromList(
				$keywords,
				array('caseInsensitive' => !$this->caseSensitive)
			);
			$regexp = '/\\b' . $regexp . '\\b/S';
			if (!$this->caseSensitive)
				$regexp .= 'i';
			if (\preg_match('/[^[:ascii:]]/', $regexp))
				$regexp .= 'u';
			$config['regexps'][] = new Regexp($regexp, \true);
		}
		return $config;
	}
}