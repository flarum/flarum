<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLComments;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];
		foreach ($matches as $m)
		{
			$content = \html_entity_decode(\substr($m[0][0], 4, -3), \ENT_QUOTES, 'UTF-8');
			$content = \str_replace(array('<', '>'), '', $content);
			$content = \str_replace('--', '', $content);
			$this->parser->addSelfClosingTag($tagName, $m[0][1], \strlen($m[0][0]))->setAttribute($attrName, $content);
		}
	}
}