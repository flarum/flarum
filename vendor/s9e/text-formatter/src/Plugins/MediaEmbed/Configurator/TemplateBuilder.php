<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Choose;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Flash;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe;
class TemplateBuilder
{
	protected $templateGenerators = array();
	public function __construct()
	{
		$this->templateGenerators['choose'] = new Choose($this);
		$this->templateGenerators['flash']  = new Flash;
		$this->templateGenerators['iframe'] = new Iframe;
	}
	public function build($siteId, array $siteConfig)
	{
		return $this->addSiteId($siteId, $this->getTemplate($siteConfig));
	}
	public function getTemplate(array $config)
	{
		foreach ($this->templateGenerators as $type => $generator)
			if (isset($config[$type]))
				return $generator->getTemplate($config[$type]);
		return '';
	}
	protected function addSiteId($siteId, $template)
	{
		$dom   = TemplateHelper::loadTemplate($template);
		$xpath = new DOMXPath($dom);
		$query = '//*[namespace-uri() != "' . TemplateHelper::XMLNS_XSL . '"][not(ancestor::*[namespace-uri() != "' . TemplateHelper::XMLNS_XSL . '"])]';
		foreach ($xpath->query($query) as $element)
			$element->setAttribute('data-s9e-mediaembed', $siteId);
		return TemplateHelper::saveTemplate($dom);
	}
}