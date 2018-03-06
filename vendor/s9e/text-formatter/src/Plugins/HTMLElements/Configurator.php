<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLElements;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $aliases = array();
	protected $attributeFilters = array(
		'action'     => '#url',
		'cite'       => '#url',
		'data'       => '#url',
		'formaction' => '#url',
		'href'       => '#url',
		'icon'       => '#url',
		'longdesc'   => '#url',
		'poster'     => '#url',
		'src'        => '#url'
	);
	protected $elements = array();
	protected $prefix = 'html';
	protected $quickMatch = '<';
	protected $unsafeElements = array(
		'base',
		'embed',
		'frame',
		'iframe',
		'meta',
		'object',
		'script'
	);
	protected $unsafeAttributes = array(
		'style',
		'target'
	);
	public function aliasAttribute($elName, $attrName, $alias)
	{
		$elName   = $this->normalizeElementName($elName);
		$attrName = $this->normalizeAttributeName($attrName);
		$this->aliases[$elName][$attrName] = AttributeName::normalize($alias);
	}
	public function aliasElement($elName, $tagName)
	{
		$elName = $this->normalizeElementName($elName);
		$this->aliases[$elName][''] = TagName::normalize($tagName);
	}
	public function allowElement($elName)
	{
		return $this->allowElementWithSafety($elName, \false);
	}
	public function allowUnsafeElement($elName)
	{
		return $this->allowElementWithSafety($elName, \true);
	}
	protected function allowElementWithSafety($elName, $allowUnsafe)
	{
		$elName  = $this->normalizeElementName($elName);
		$tagName = $this->prefix . ':' . $elName;
		if (!$allowUnsafe && \in_array($elName, $this->unsafeElements))
			throw new RuntimeException("'" . $elName . "' elements are unsafe and are disabled by default. Please use " . __CLASS__ . '::allowUnsafeElement() to bypass this security measure');
		$tag = ($this->configurator->tags->exists($tagName))
		     ? $this->configurator->tags->get($tagName)
		     : $this->configurator->tags->add($tagName);
		$this->rebuildTemplate($tag, $elName, $allowUnsafe);
		$this->elements[$elName] = 1;
		return $tag;
	}
	public function allowAttribute($elName, $attrName)
	{
		return $this->allowAttributeWithSafety($elName, $attrName, \false);
	}
	public function allowUnsafeAttribute($elName, $attrName)
	{
		return $this->allowAttributeWithSafety($elName, $attrName, \true);
	}
	protected function allowAttributeWithSafety($elName, $attrName, $allowUnsafe)
	{
		$elName   = $this->normalizeElementName($elName);
		$attrName = $this->normalizeAttributeName($attrName);
		$tagName  = $this->prefix . ':' . $elName;
		if (!isset($this->elements[$elName]))
			throw new RuntimeException("Element '" . $elName . "' has not been allowed");
		if (!$allowUnsafe)
			if (\substr($attrName, 0, 2) === 'on'
			 || \in_array($attrName, $this->unsafeAttributes))
				throw new RuntimeException("'" . $attrName . "' attributes are unsafe and are disabled by default. Please use " . __CLASS__ . '::allowUnsafeAttribute() to bypass this security measure');
		$tag = $this->configurator->tags->get($tagName);
		if (!isset($tag->attributes[$attrName]))
		{
			$attribute = $tag->attributes->add($attrName);
			$attribute->required = \false;
			if (isset($this->attributeFilters[$attrName]))
			{
				$filterName = $this->attributeFilters[$attrName];
				$filter = $this->configurator->attributeFilters->get($filterName);
				$attribute->filterChain->append($filter);
			}
		}
		$this->rebuildTemplate($tag, $elName, $allowUnsafe);
		return $tag->attributes[$attrName];
	}
	protected function normalizeElementName($elName)
	{
		if (!\preg_match('#^[a-z][a-z0-9]*$#Di', $elName))
			throw new InvalidArgumentException ("Invalid element name '" . $elName . "'");
		return \strtolower($elName);
	}
	protected function normalizeAttributeName($attrName)
	{
		if (!\preg_match('#^[a-z][-\\w]*$#Di', $attrName))
			throw new InvalidArgumentException ("Invalid attribute name '" . $attrName . "'");
		return \strtolower($attrName);
	}
	protected function rebuildTemplate(Tag $tag, $elName, $allowUnsafe)
	{
		$template = '<' . $elName . '>';
		foreach ($tag->attributes as $attrName => $attribute)
			$template .= '<xsl:copy-of select="@' . $attrName . '"/>';
		$template .= '<xsl:apply-templates/></' . $elName . '>';
		if ($allowUnsafe)
			$template = new UnsafeTemplate($template);
		$tag->setTemplate($template);
	}
	public function asConfig()
	{
		if (empty($this->elements) && empty($this->aliases))
			return;
		$attrRegexp = '[a-z][-a-z0-9]*(?>\\s*=\\s*(?>"[^"]*"|\'[^\']*\'|[^\\s"\'=<>`]+))?';
		$tagRegexp  = RegexpBuilder::fromList(\array_merge(
			\array_keys($this->aliases),
			\array_keys($this->elements)
		));
		$endTagRegexp   = '/(' . $tagRegexp . ')';
		$startTagRegexp = '(' . $tagRegexp . ')((?>\\s+' . $attrRegexp . ')*+)\\s*/?';
		$regexp = '#<(?>' . $endTagRegexp . '|' . $startTagRegexp . ')\\s*>#i';
		$config = array(
			'quickMatch' => $this->quickMatch,
			'prefix'     => $this->prefix,
			'regexp'     => $regexp
		);
		if (!empty($this->aliases))
		{
			$config['aliases'] = new Dictionary;
			foreach ($this->aliases as $elName => $aliases)
				$config['aliases'][$elName] = new Dictionary($aliases);
		}
		return $config;
	}
	public function getJSHints()
	{
		return array('HTMLELEMENTS_HAS_ALIASES' => (int) !empty($this->aliases));
	}
}