<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autolink;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
			$this->linkifyUrl($m[0][1], $this->trimUrl($m[0][0]));
	}
	protected function linkifyUrl($tagPos, $url)
	{
		if (!\preg_match('/^[^:]+:|^www\\./i', $url))
			return;
		$endTag = $this->parser->addEndTag($this->config['tagName'], $tagPos + \strlen($url), 0);
		if ($url[3] === '.')
			$url = 'http://' . $url;
		$startTag = $this->parser->addStartTag($this->config['tagName'], $tagPos, 0, 1);
		$startTag->setAttribute($this->config['attrName'], $url);
		$startTag->pairWith($endTag);
	}
	protected function trimUrl($url)
	{
		while (1)
		{
			$url = \preg_replace('#(?![-=/)])[\\s!-.:-@[-`{-~\\pP]+$#Du', '', $url);
			if (\substr($url, -1) === ')' && \substr_count($url, '(') < \substr_count($url, ')'))
			{
				$url = \substr($url, 0, -1);
				continue;
			}
			break;
		}
		return $url;
	}
}