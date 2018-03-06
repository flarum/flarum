<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\FancyPants;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'char';
	protected $disabledPasses = array();
	protected $tagName = 'FP';
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName);
		$tag->template
			= '<xsl:value-of select="@' . \htmlspecialchars($this->attrName) . '"/>';
	}
	public function disablePass($passName)
	{
		$this->disabledPasses[] = $passName;
	}
	public function enablePass($passName)
	{
		foreach (\array_keys($this->disabledPasses, $passName, \true) as $k)
			unset($this->disabledPasses[$k]);
	}
	public function asConfig()
	{
		$config = array(
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		);
		foreach ($this->disabledPasses as $passName)
			$config['disable' . $passName] = \true;
		return $config;
	}
}