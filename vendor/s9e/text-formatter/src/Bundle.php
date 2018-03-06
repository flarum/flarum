<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
abstract class Bundle
{
	public static function getCachedParser()
	{
		if (!isset(static::$parser))
			static::$parser = static::getParser();
		return static::$parser;
	}
	public static function getCachedRenderer()
	{
		if (!isset(static::$renderer))
			static::$renderer = static::getRenderer();
		return static::$renderer;
	}
	public static function parse($text)
	{
		if (isset(static::$beforeParse))
			$text = \call_user_func(static::$beforeParse, $text);
		$xml = static::getCachedParser()->parse($text);
		if (isset(static::$afterParse))
			$xml = \call_user_func(static::$afterParse, $xml);
		return $xml;
	}
	public static function render($xml, array $params = array())
	{
		$renderer = static::getCachedRenderer();
		if (!empty($params))
			$renderer->setParameters($params);
		if (isset(static::$beforeRender))
			$xml = \call_user_func(static::$beforeRender, $xml);
		$output = $renderer->render($xml);
		if (isset(static::$afterRender))
			$output = \call_user_func(static::$afterRender, $output);
		return $output;
	}
	public static function reset()
	{
		static::$parser   = \null;
		static::$renderer = \null;
	}
	public static function unparse($xml)
	{
		if (isset(static::$beforeUnparse))
			$xml = \call_user_func(static::$beforeUnparse, $xml);
		$text = Unparser::unparse($xml);
		if (isset(static::$afterUnparse))
			$text = \call_user_func(static::$afterUnparse, $text);
		return $text;
	}
}