<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;
class Iframe extends TemplateGenerator
{
	protected $defaultIframeAttributes = array(
		'allowfullscreen' => '',
		'scrolling'       => 'no',
		'style'           => array('border' => '0')
	);
	protected $iframeAttributes = array('onload', 'scrolling', 'src', 'style');
	protected function getContentTemplate()
	{
		$attributes = $this->mergeAttributes($this->defaultIframeAttributes, $this->getFilteredAttributes());
		return '<iframe>' . $this->generateAttributes($attributes) . '</iframe>';
	}
	protected function getFilteredAttributes()
	{
		return \array_intersect_key($this->attributes, \array_flip($this->iframeAttributes));
	}
}