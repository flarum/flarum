<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;
class Flash extends TemplateGenerator
{
	protected function getContentTemplate()
	{
		$attributes = array(
			'data'          => $this->attributes['src'],
			'style'         => $this->attributes['style'],
			'type'          => 'application/x-shockwave-flash',
			'typemustmatch' => ''
		);
		$flashVarsParam = '';
		if (isset($this->attributes['flashvars']))
			$flashVarsParam = $this->generateParamElement('flashvars', $this->attributes['flashvars']);
		$template = '<object>'
		          . $this->generateAttributes($attributes)
		          . $this->generateParamElement('allowfullscreen', 'true')
		          . $flashVarsParam
		          . '</object>';
		return $template;
	}
	protected function generateParamElement($paramName, $paramValue)
	{
		return '<param name="' . \htmlspecialchars($paramName) . '">' . $this->generateAttributes(array('value' => $paramValue)) . '</param>';
	}
}