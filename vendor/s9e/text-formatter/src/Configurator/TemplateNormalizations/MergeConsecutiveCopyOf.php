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
class MergeConsecutiveCopyOf extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('//xsl:copy-of') as $node)
			$this->mergeCopyOfSiblings($node);
	}
	protected function mergeCopyOfSiblings(DOMElement $node)
	{
		while ($this->nextSiblingIsCopyOf($node))
		{
			$node->setAttribute('select', $node->getAttribute('select') . '|' . $node->nextSibling->getAttribute('select'));
			$node->parentNode->removeChild($node->nextSibling);
		}
	}
	protected function nextSiblingIsCopyOf(DOMElement $node)
	{
		return ($node->nextSibling && $node->nextSibling->localName === 'copy-of' && $node->nextSibling->namespaceURI === self::XMLNS_XSL);
	}
}