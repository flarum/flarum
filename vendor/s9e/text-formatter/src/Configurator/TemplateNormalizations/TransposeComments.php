<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class TransposeComments extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//comment()') as $comment)
			$comment->parentNode->replaceChild(
				$dom->createElementNS(
					self::XMLNS_XSL,
					'xsl:comment',
					\htmlspecialchars($comment->nodeValue)
				),
				$comment
			);
	}
}