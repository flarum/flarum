<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use Exception;
abstract class Minifier
{
	public $cacheDir;
	public $keepGoing = \false;
	abstract public function minify($src);
	public function get($src)
	{
		try
		{
			return (isset($this->cacheDir)) ? $this->getFromCache($src) : $this->minify($src);
		}
		catch (Exception $e)
		{
			if (!$this->keepGoing)
				throw $e;
		}
		return $src;
	}
	public function getCacheDifferentiator()
	{
		return '';
	}
	protected function getFromCache($src)
	{
		$differentiator = $this->getCacheDifferentiator();
		$key            = \sha1(\serialize(array(\get_class($this), $differentiator, $src)));
		$cacheFile      = $this->cacheDir . '/minifier.' . $key . '.js';
		if (!\file_exists($cacheFile))
			\file_put_contents($cacheFile, $this->minify($src));
		return \file_get_contents($cacheFile);
	}
}