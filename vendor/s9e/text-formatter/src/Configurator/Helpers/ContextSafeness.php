<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
abstract class ContextSafeness
{
	public static function getDisallowedCharactersAsURL()
	{
		return array(':');
	}
	public static function getDisallowedCharactersInCSS()
	{
		return array('(', ')', ':', '\\', '"', "'", ';', '{', '}');
	}
	public static function getDisallowedCharactersInJS()
	{
		return array('(', ')', '"', "'", '\\', "\r", "\n", "\xE2\x80\xA8", "\xE2\x80\xA9", ':', '%', '=');
	}
}