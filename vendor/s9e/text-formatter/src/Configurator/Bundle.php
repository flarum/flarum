<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator;
abstract class Bundle
{
	abstract public function configure(Configurator $configurator);
	public static function getConfigurator()
	{
		$configurator = new Configurator;
		$bundle  = new static;
		$bundle->configure($configurator);
		return $configurator;
	}
	public static function getOptions()
	{
		return array();
	}
}