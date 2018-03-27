<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;
abstract class ConfiguratorBase implements ConfigProvider
{
	protected $configurator;
	protected $quickMatch = \false;
	protected $regexpLimit = 10000;
	final public function __construct(Configurator $configurator, array $overrideProps = array())
	{
		$this->configurator = $configurator;
		foreach ($overrideProps as $k => $v)
		{
			$methodName = 'set' . \ucfirst($k);
			if (\method_exists($this, $methodName))
				$this->$methodName($v);
			elseif (\property_exists($this, $k))
				$this->$k = $v;
			else
				throw new RuntimeException("Unknown property '" . $k . "'");
		}
		$this->setUp();
	}
	protected function setUp()
	{
	}
	public function finalize()
	{
	}
	public function asConfig()
	{
		$properties = \get_object_vars($this);
		unset($properties['configurator']);
		return ConfigHelper::toArray($properties);
	}
	final public function getBaseProperties()
	{
		$config = array(
			'className'   => \preg_replace('/Configurator$/', 'Parser', \get_class($this)),
			'quickMatch'  => $this->quickMatch,
			'regexpLimit' => $this->regexpLimit
		);
		$js = $this->getJSParser();
		if (isset($js))
			$config['js'] = new Code($js);
		return $config;
	}
	public function getJSHints()
	{
		return array();
	}
	public function getJSParser()
	{
		$className = \get_class($this);
		if (\strpos($className, 's9e\\TextFormatter\\Plugins\\') === 0)
		{
			$p = \explode('\\', $className);
			$pluginName = $p[3];
			$filepath = __DIR__ . '/' . $pluginName . '/Parser.js';
			if (\file_exists($filepath))
				return \file_get_contents($filepath);
		}
		return \null;
	}
	public function getTag()
	{
		if (!isset($this->tagName))
			throw new RuntimeException('No tag associated with this plugin');
		return $this->configurator->tags[$this->tagName];
	}
	public function disableQuickMatch()
	{
		$this->quickMatch = \false;
	}
	protected function setAttrName($attrName)
	{
		if (!\property_exists($this, 'attrName'))
			throw new RuntimeException("Unknown property 'attrName'");
		$this->attrName = AttributeName::normalize($attrName);
	}
	public function setQuickMatch($quickMatch)
	{
		if (!\is_string($quickMatch))
			throw new InvalidArgumentException('quickMatch must be a string');
		$this->quickMatch = $quickMatch;
	}
	public function setRegexpLimit($limit)
	{
		$limit = (int) $limit;
		if ($limit < 1)
			throw new InvalidArgumentException('regexpLimit must be a number greater than 0');
		$this->regexpLimit = $limit;
	}
	protected function setTagName($tagName)
	{
		if (!\property_exists($this, 'tagName'))
			throw new RuntimeException("Unknown property 'tagName'");
		$this->tagName = TagName::normalize($tagName);
	}
}