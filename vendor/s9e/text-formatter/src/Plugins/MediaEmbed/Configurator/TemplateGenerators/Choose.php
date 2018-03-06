<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;
class Choose extends TemplateGenerator
{
	protected $templateBuilder;
	public function __construct(TemplateBuilder $templateBuilder)
	{
		$this->templateBuilder = $templateBuilder;
	}
	protected function needsWrapper()
	{
		return \false;
	}
	protected function getContentTemplate()
	{
		$branches = (isset($this->attributes['when'][0])) ? $this->attributes['when'] : array($this->attributes['when']);
		$template = '<xsl:choose>';
		foreach ($branches as $when)
			$template .= '<xsl:when test="' . \htmlspecialchars($when['test'], \ENT_COMPAT, 'UTF-8') . '">' . $this->templateBuilder->getTemplate($when) . '</xsl:when>';
		$template .= '<xsl:otherwise>' . $this->templateBuilder->getTemplate($this->attributes['otherwise']) . '</xsl:otherwise></xsl:choose>';
		return $template;
	}
}