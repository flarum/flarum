<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\AttributeList;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;
class BBCode implements ConfigProvider
{
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		return $this->$propName;
	}
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);
			return;
		}
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;
			return;
		}
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!\is_array($propValue)
			 && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			$this->$propName->clear();
			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);
			return;
		}
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);
			if ($oldType === 'boolean')
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = \false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = \true;
				}
			if ($oldType !== $newType)
			{
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);
				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				\settype($propValue, $oldType);
			}
		}
		$this->$propName = $propValue;
	}
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		return isset($this->$propName);
	}
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName();
			return;
		}
		if (!isset($this->$propName))
			return;
		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();
			return;
		}
		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}
	protected $contentAttributes;
	protected $defaultAttribute;
	protected $forceLookahead = \false;
	protected $predefinedAttributes;
	protected $tagName;
	public function __construct(array $options = \null)
	{
		$this->contentAttributes    = new AttributeList;
		$this->predefinedAttributes = new AttributeValueCollection;
		if (isset($options))
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
	}
	public function asConfig()
	{
		$config = ConfigHelper::toArray(\get_object_vars($this));
		if (!$this->forceLookahead)
			unset($config['forceLookahead']);
		if (isset($config['predefinedAttributes']))
			$config['predefinedAttributes'] = new Dictionary($config['predefinedAttributes']);
		return $config;
	}
	public static function normalizeName($bbcodeName)
	{
		if ($bbcodeName === '*')
			return '*';
		if (!TagName::isValid($bbcodeName))
			throw new InvalidArgumentException("Invalid BBCode name '" . $bbcodeName . "'");
		return TagName::normalize($bbcodeName);
	}
	public function setDefaultAttribute($attrName)
	{
		$this->defaultAttribute = AttributeName::normalize($attrName);
	}
	public function setTagName($tagName)
	{
		$this->tagName = TagName::normalize($tagName);
	}
}