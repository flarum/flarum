<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class SortAttributesByName extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		foreach ($template->getElementsByTagName('*') as $element)
		{
			if (!$element->attributes->length)
				continue;
			$attributes = array();
			foreach ($element->attributes as $name => $attribute)
				$attributes[$name] = $element->removeAttributeNode($attribute);
			\ksort($attributes);
			foreach ($attributes as $attribute)
				$element->setAttributeNode($attribute);
		}
	}
}