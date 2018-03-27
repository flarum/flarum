<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use ArrayAccess;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\MinifierList;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

class FirstAvailable extends Minifier implements ArrayAccess
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
	protected $collection;
	public function __construct()
	{
		$this->collection = new MinifierList;
		foreach (\func_get_args() as $minifier)
			$this->collection->add($minifier);
	}
	public function minify($src)
	{
		foreach ($this->collection as $minifier)
			try
			{
				return $minifier->minify($src);
			}
			catch (Exception $e)
			{
				}
		throw new RuntimeException('No minifier available');
	}
}