<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Preg;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		foreach ($this->config['generics'] as $_8d36e519)
		{
			list($tagName, $regexp, $passthroughIdx, $map) = $_8d36e519;
			\preg_match_all($regexp, $text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);
			foreach ($matches as $m)
			{
				$startTagPos = $m[0][1];
				$matchLen    = \strlen($m[0][0]);
				if ($passthroughIdx && isset($m[$passthroughIdx]) && $m[$passthroughIdx][0] !== '')
				{
					$contentPos  = $m[$passthroughIdx][1];
					$contentLen  = \strlen($m[$passthroughIdx][0]);
					$startTagLen = $contentPos - $startTagPos;
					$endTagPos   = $contentPos + $contentLen;
					$endTagLen   = $matchLen - ($startTagLen + $contentLen);
					$tag = $this->parser->addTagPair($tagName, $startTagPos, $startTagLen, $endTagPos, $endTagLen, -100);
				}
				else
					$tag = $this->parser->addSelfClosingTag($tagName, $startTagPos, $matchLen, -100);
				foreach ($map as $i => $attrName)
					if ($attrName && isset($m[$i]) && $m[$i][0] !== '')
						$tag->setAttribute($attrName, $m[$i][0]);
			}
		}
	}
}