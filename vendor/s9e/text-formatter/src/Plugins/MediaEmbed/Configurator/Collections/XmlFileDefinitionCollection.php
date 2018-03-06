<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
class XmlFileDefinitionCollection extends SiteDefinitionCollection
{
	public function __construct($path)
	{
		if (!\file_exists($path) || !\is_dir($path))
			throw new InvalidArgumentException('Invalid site directory');
		foreach (\glob($path . '/*.xml') as $filepath)
		{
			$siteId = \basename($filepath, '.xml');
			$this->items[$siteId] = $this->getConfigFromXmlFile($filepath);
		}
	}
	protected function flattenConfig(array $config)
	{
		foreach ($config as $k => $v)
			if (\is_array($v) && \count($v) === 1)
				$config[$k] = \end($v);
		return $config;
	}
	protected function getConfigFromXmlFile($filepath)
	{
		$dom = new DOMDocument;
		$dom->load($filepath);
		return $this->getElementConfig($dom->documentElement);
	}
	protected function getElementConfig(DOMElement $element)
	{
		$config = array();
		foreach ($element->attributes as $attribute)
			$config[$attribute->name][] = $attribute->value;
		foreach ($element->childNodes as $childNode)
			if ($childNode instanceof DOMElement)
				$config[$childNode->nodeName][] = $this->getValueFromElement($childNode);
		return $this->flattenConfig($config);
	}
	protected function getValueFromElement(DOMElement $element)
	{
		return (!$element->attributes->length && $element->childNodes->length === 1)
		     ? $element->nodeValue
		     : $this->getElementConfig($element);
	}
}