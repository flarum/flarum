<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT\Optimizer;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;
class XSLT implements RendererGenerator
{
	public $optimizer;
	public function __construct()
	{
		$this->optimizer = new Optimizer;
	}
	public function getRenderer(Rendering $rendering)
	{
		return new XSLTRenderer($this->getXSL($rendering));
	}
	public function getXSL(Rendering $rendering)
	{
		$groupedTemplates = array();
		$prefixes         = array();
		$templates        = $rendering->getTemplates();
		TemplateHelper::replaceHomogeneousTemplates($templates, 3);
		foreach ($templates as $tagName => $template)
		{
			$template = $this->optimizer->optimizeTemplate($template);
			$groupedTemplates[$template][] = $tagName;
			$pos = \strpos($tagName, ':');
			if ($pos !== \false)
				$prefixes[\substr($tagName, 0, $pos)] = 1;
		}
		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';
		$prefixes = \array_keys($prefixes);
		\sort($prefixes);
		foreach ($prefixes as $prefix)
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		if (!empty($prefixes))
			$xsl .= ' exclude-result-prefixes="' . \implode(' ', $prefixes) . '"';
		$xsl .= '><xsl:output method="html" encoding="utf-8" indent="no"';
		$xsl .= '/>';
		foreach ($rendering->getAllParameters() as $paramName => $paramValue)
		{
			$xsl .= '<xsl:param name="' . \htmlspecialchars($paramName) . '"';
			if ($paramValue === '')
				$xsl .= '/>';
			else
				$xsl .= '>' . \htmlspecialchars($paramValue) . '</xsl:param>';
		}
		foreach ($groupedTemplates as $template => $tagNames)
		{
			$xsl .= '<xsl:template match="' . \implode('|', $tagNames) . '"';
			if ($template === '')
				$xsl .= '/>';
			else
				$xsl .= '>' . $template . '</xsl:template>';
		}
		$xsl .= '</xsl:stylesheet>';
		return $xsl;
	}
}