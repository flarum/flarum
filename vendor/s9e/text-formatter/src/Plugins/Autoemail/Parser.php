<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoemail;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];
		foreach ($matches as $m)
		{
			$startTag = $this->parser->addStartTag($tagName, $m[0][1], 0);
			$startTag->setAttribute($attrName, $m[0][0]);
			$endTag = $this->parser->addEndTag($tagName, $m[0][1] + \strlen($m[0][0]), 0);
			$startTag->pairWith($endTag);
		}
	}
}