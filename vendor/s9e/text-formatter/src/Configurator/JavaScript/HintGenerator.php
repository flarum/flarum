<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use ReflectionClass;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
class HintGenerator
{
	protected $config;
	protected $hints;
	protected $plugins;
	protected $xsl;
	public function getHints()
	{
		$this->hints = array();
		$this->setPluginsHints();
		$this->setRenderingHints();
		$this->setRulesHints();
		$this->setTagsHints();
		$js = "/** @const */ var HINT={};\n";
		\ksort($this->hints);
		foreach ($this->hints as $hintName => $hintValue)
			$js .= '/** @const */ HINT.' . $hintName . '=' . \json_encode($hintValue) . ";\n";
		return $js;
	}
	public function setConfig(array $config)
	{
		$this->config = $config;
	}
	public function setPlugins(PluginCollection $plugins)
	{
		$this->plugins = $plugins;
	}
	public function setXSL($xsl)
	{
		$this->xsl = $xsl;
	}
	protected function setPluginsHints()
	{
		foreach ($this->plugins as $plugins)
			$this->hints += $plugins->getJSHints();
	}
	protected function setRenderingHints()
	{
		$this->hints['postProcessing'] = (int) (\strpos($this->xsl, 'data-s9e-livepreview-postprocess') !== \false);
	}
	protected function setRulesHints()
	{
		$this->hints['closeAncestor']   = 0;
		$this->hints['closeParent']     = 0;
		$this->hints['createChild']     = 0;
		$this->hints['fosterParent']    = 0;
		$this->hints['requireAncestor'] = 0;
		$flags = 0;
		foreach ($this->config['tags'] as $tagConfig)
		{
			foreach (\array_intersect_key($tagConfig['rules'], $this->hints) as $k => $v)
				$this->hints[$k] = 1;
			$flags |= $tagConfig['rules']['flags'];
		}
		$flags |= $this->config['rootContext']['flags'];
		$parser = new ReflectionClass('s9e\\TextFormatter\\Parser');
		foreach ($parser->getConstants() as $constName => $constValue)
			if (\substr($constName, 0, 5) === 'RULE_')
				$this->hints[$constName] = ($flags & $constValue) ? 1 : 0;
	}
	protected function setTagAttributesHints(array $tagConfig)
	{
		if (empty($tagConfig['attributes']))
			return;
		foreach ($tagConfig['attributes'] as $attrConfig)
		{
			$this->hints['attributeGenerator']    |= isset($attrConfig['generator']);
			$this->hints['attributeDefaultValue'] |= isset($attrConfig['defaultValue']);
		}
	}
	protected function setTagsHints()
	{
		$this->hints['attributeGenerator']    = 0;
		$this->hints['attributeDefaultValue'] = 0;
		$this->hints['namespaces']            = 0;
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			$this->hints['namespaces'] |= (\strpos($tagName, ':') !== \false);
			$this->setTagAttributesHints($tagConfig);
		}
	}
}