<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;
use InvalidArgumentException;
abstract class TemplateParameterName
{
	public static function isValid($name)
	{
		return (bool) \preg_match('#^[a-z_][-a-z_0-9]*$#Di', $name);
	}
	public static function normalize($name)
	{
		$name = (string) $name;
		if (!static::isValid($name))
			throw new InvalidArgumentException("Invalid parameter name '" . $name . "'");
		return $name;
	}
}