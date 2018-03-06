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
class OptimizeNestedConditionals extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:choose/xsl:otherwise[count(node()) = 1]/xsl:choose';
		foreach ($xpath->query($query) as $innerChoose)
		{
			$otherwise   = $innerChoose->parentNode;
			$outerChoose = $otherwise->parentNode;
			while ($innerChoose->firstChild)
				$outerChoose->appendChild($innerChoose->removeChild($innerChoose->firstChild));
			$outerChoose->removeChild($otherwise);
		}
	}
}