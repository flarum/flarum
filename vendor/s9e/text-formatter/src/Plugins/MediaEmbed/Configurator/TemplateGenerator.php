<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
abstract class TemplateGenerator
{
	protected $attributes;
	protected $defaultAttributes = array(
		'height'         => 360,
		'padding-height' => 0,
		'style'          => array(),
		'width'          => 640
	);
	abstract protected function getContentTemplate();
	public function getTemplate(array $attributes)
	{
		$this->attributes = $attributes + $this->defaultAttributes;
		$prepend = $append = '';
		if ($this->needsWrapper())
		{
			$this->attributes['style']['width']    = '100%';
			$this->attributes['style']['height']   = '100%';
			$this->attributes['style']['position'] = 'absolute';
			$this->attributes['style']['left'] = '0';
			$outerStyle = 'display:inline-block;width:100%;max-width:' . $this->attributes['width'] . 'px';
			$innerStyle = 'overflow:hidden;position:relative;' . $this->getResponsivePadding();
			$prepend .= '<div>' . $this->generateAttributes(array('style' => $outerStyle));
			$prepend .= '<div>' . $this->generateAttributes(array('style' => $innerStyle));
			$append  .= '</div></div>';
		}
		else
		{
			$this->attributes['style']['width']  = '100%';
			$this->attributes['style']['height'] = $this->attributes['height'] . 'px';
			if (isset($this->attributes['max-width']))
				$this->attributes['style']['max-width'] = $this->attributes['max-width'] . 'px';
			elseif ($this->attributes['width'] !== '100%')
			{
				$property = ($this->hasDynamicWidth()) ? 'width' : 'max-width';
				$this->attributes['style'][$property] = $this->attributes['width'] . 'px';
			}
		}
		return $prepend . $this->getContentTemplate() . $append;
	}
	protected function expr($expr)
	{
		$expr = \trim($expr, '{}');
		return (\preg_match('(^[@$]?[-\\w]+$)D', $expr)) ? $expr : "($expr)";
	}
	protected function getResponsivePadding()
	{
		$height        = $this->expr($this->attributes['height']);
		$paddingHeight = $this->expr($this->attributes['padding-height']);
		$width         = $this->expr($this->attributes['width']);
		$css = 'padding-bottom:<xsl:value-of select="100*(' . $height . '+' . $paddingHeight . ')div' . $width . '"/>%';
		
		if (!empty($this->attributes['padding-height']))
			$css .= ';padding-bottom:calc(<xsl:value-of select="100*' . $height . ' div' . $width . '"/>% + ' . $paddingHeight . 'px)';
		if (\strpos($width, '@') !== \false)
			$css = '<xsl:if test="@width&gt;0">' . $css . '</xsl:if>';
		return $css;
	}
	protected function generateAttributes(array $attributes)
	{
		if (isset($attributes['style']) && \is_array($attributes['style']))
			$attributes['style'] = $this->generateStyle($attributes['style']);
		\ksort($attributes);
		$xsl = '';
		foreach ($attributes as $attrName => $attrValue)
		{
			$innerXML = (\strpos($attrValue, '<xsl:') !== \false) ? $attrValue : AVTHelper::toXSL($attrValue);
			$xsl .= '<xsl:attribute name="' . \htmlspecialchars($attrName, \ENT_QUOTES, 'UTF-8') . '">' . $innerXML . '</xsl:attribute>';
		}
		return $xsl;
	}
	protected function generateStyle(array $properties)
	{
		\ksort($properties);
		$style = '';
		foreach ($properties as $name => $value)
			$style .= $name . ':' . $value . ';';
		return \trim($style, ';');
	}
	protected function hasDynamicHeight()
	{
		return (isset($this->attributes['onload']) && \strpos($this->attributes['onload'], '.height') !== \false);
	}
	protected function hasDynamicWidth()
	{
		return (isset($this->attributes['onload']) && \strpos($this->attributes['onload'], '.width') !== \false);
	}
	protected function mergeAttributes(array $defaultAttributes, array $newAttributes)
	{
		$attributes = \array_merge($defaultAttributes, $newAttributes);
		if (isset($defaultAttributes['style'], $newAttributes['style']))
			$attributes['style'] += $defaultAttributes['style'];
		return $attributes;
	}
	protected function needsWrapper()
	{
		return ($this->attributes['width'] !== '100%' && !$this->hasDynamicHeight());
	}
}