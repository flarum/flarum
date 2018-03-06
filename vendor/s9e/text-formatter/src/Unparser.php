<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
use DOMDocument;
abstract class Unparser
{
	public static function unparse($xml)
	{
		return \html_entity_decode(\strip_tags($xml), \ENT_QUOTES, 'UTF-8');
	}
}