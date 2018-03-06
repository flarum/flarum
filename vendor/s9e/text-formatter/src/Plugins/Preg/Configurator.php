<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Preg;
use DOMAttr;
use DOMText;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	public $captures;
	protected $collection = array();
	protected $delimiter;
	protected $modifiers;
	protected $references;
	protected $referencesRegexp = '((?<!\\\\)(?:\\\\\\\\)*\\K(?:[$\\\\]\\d+|\\$\\{\\d+\\}))S';
	public function asConfig()
	{
		if (!\count($this->collection))
			return;
		$pregs = array();
		foreach ($this->collection as $_ca164be8)
		{
			list($tagName, $regexp, $passthroughIdx) = $_ca164be8;
			$captures = RegexpParser::getCaptureNames($regexp);
			$pregs[]  = array($tagName, new Regexp($regexp, \true), $passthroughIdx, $captures);
		}
		return array('generics' => $pregs);
	}
	public function getJSHints()
	{
		$hasPassthrough = \false;
		foreach ($this->collection as $_ca164be8)
		{
			list($tagName, $regexp, $passthroughIdx) = $_ca164be8;
			if ($passthroughIdx)
			{
				$hasPassthrough = \true;
				break;
			}
		}
		return array('PREG_HAS_PASSTHROUGH' => $hasPassthrough);
	}
	public function match($regexp, $tagName)
	{
		$passthrough = 0;
		$this->parseRegexp($regexp);
		foreach ($this->captures as $i => $capture)
		{
			if (!$this->isCatchAll($capture['expr']))
				continue;
			$passthrough = $i;
		}
		$this->collection[] = array($tagName, $regexp, $passthrough);
	}
	public function replace($regexp, $template, $tagName = \null)
	{
		if (!isset($tagName))
			$tagName = 'PREG_' . \strtoupper(\dechex(\crc32($regexp)));
		$this->parseRegexp($regexp);
		$this->parseTemplate($template);
		$passthrough = $this->getPassthroughCapture();
		if ($passthrough)
			$this->captures[$passthrough]['passthrough'] = \true;
		$regexp   = $this->fixUnnamedCaptures($regexp);
		$template = $this->convertTemplate($template, $passthrough);
		$this->collection[] = array($tagName, $regexp, $passthrough);
		return $this->createTag($tagName, $template);
	}
	protected function addAttribute(Tag $tag, $attrName)
	{
		$isUrl = \false;
		$exprs = array();
		foreach ($this->captures as $key => $capture)
		{
			if ($capture['name'] !== $attrName)
				continue;
			$exprs[] = $capture['expr'];
			if (isset($this->references['asUrl'][$key]))
				$isUrl = \true;
		}
		$exprs = \array_unique($exprs);
		$regexp = $this->delimiter . '^';
		$regexp .= (\count($exprs) === 1) ? $exprs[0] : '(?:' . \implode('|', $exprs) . ')';
		$regexp .= '$' . $this->delimiter . 'D' . $this->modifiers;
		$attribute = $tag->attributes->add($attrName);
		$filter = $this->configurator->attributeFilters['#regexp'];
		$filter->setRegexp($regexp);
		$attribute->filterChain[] = $filter;
		if ($isUrl)
		{
			$filter = $this->configurator->attributeFilters['#url'];
			$attribute->filterChain[] = $filter;
		}
	}
	protected function convertTemplate($template, $passthrough)
	{
		$_this = $this;
		$template = TemplateHelper::replaceTokens(
			$template,
			$this->referencesRegexp,
			function ($m, $node) use ($passthrough, $_this)
			{
				$key = (int) \trim($m[0], '\\${}');
				if ($key === 0)
					return array('expression', '.');
				if ($key === $passthrough && $node instanceof DOMText)
					return array('passthrough');
				if (isset($_this->captures[$key]['name']))
					return array('expression', '@' . $_this->captures[$key]['name']);
				return array('literal', '');
			}
		);
		$template = TemplateHelper::replaceTokens(
			$template,
			'(\\\\+[0-9${\\\\])',
			function ($m)
			{
				return array('literal', \stripslashes($m[0]));
			}
		);
		return $template;
	}
	protected function createTag($tagName, $template)
	{
		$tag = new Tag;
		foreach ($this->captures as $key => $capture)
		{
			if (!isset($capture['name']))
				continue;
			$attrName = $capture['name'];
			if (isset($tag->attributes[$attrName]))
				continue;
			$this->addAttribute($tag, $attrName);
		}
		$tag->template = $template;
		$this->configurator->templateNormalizer->normalizeTag($tag);
		$this->configurator->templateChecker->checkTag($tag);
		return $this->configurator->tags->add($tagName, $tag);
	}
	protected function fixUnnamedCaptures($regexp)
	{
		$keys = array();
		foreach ($this->references['anywhere'] as $key)
		{
			$capture = $this->captures[$key];
			if (!$key || isset($capture['name']))
				continue;
			if (isset($this->references['asUrl'][$key]) || !isset($capture['passthrough']))
				$keys[] = $key;
		}
		\rsort($keys);
		foreach ($keys as $key)
		{
			$name   = '_' . $key;
			$pos    = $this->captures[$key]['pos'];
			$regexp = \substr_replace($regexp, "?'" . $name . "'", 2 + $pos, 0);
			$this->captures[$key]['name'] = $name;
		}
		return $regexp;
	}
	protected function getPassthroughCapture()
	{
		$passthrough = 0;
		foreach ($this->references['inText'] as $key)
		{
			if (!$this->isCatchAll($this->captures[$key]['expr']))
				continue;
			if ($passthrough)
			{
				$passthrough = 0;
				break;
			}
			$passthrough = (int) $key;
		}
		return $passthrough;
	}
	protected function getRegexpInfo($regexp)
	{
		$valid = \false;
		try
		{
			$valid = @\preg_match_all($regexp, '', $m);
		}
		catch (Exception $e)
		{
			}
		if ($valid === \false)
			throw new InvalidArgumentException('Invalid regexp');
		return RegexpParser::parse($regexp);
	}
	protected function isCatchAll($expr)
	{
		return (bool) \preg_match('(^\\.[*+]\\??$)D', $expr);
	}
	protected function parseRegexp($regexp)
	{
		$this->captures = array(array('name' => \null, 'expr' => \null));
		$regexpInfo = $this->getRegexpInfo($regexp);
		$this->delimiter = $regexpInfo['delimiter'];
		$this->modifiers = \str_replace('D', '', $regexpInfo['modifiers']);
		foreach ($regexpInfo['tokens'] as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart')
				continue;
			$this->captures[] = array(
				'pos'    => $token['pos'],
				'name'   => (isset($token['name'])) ? $token['name'] : \null,
				'expr'   => $token['content']
			);
		}
	}
	protected function parseTemplate($template)
	{
		$this->references = array(
			'anywhere' => array(),
			'asUrl'    => array(),
			'inText'   => array()
		);
		\preg_match_all($this->referencesRegexp, $template, $matches);
		foreach ($matches[0] as $match)
		{
			$key = \trim($match, '\\${}');
			$this->references['anywhere'][$key] = $key;
		}
		$dom   = TemplateHelper::loadTemplate($template);
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//text()') as $node)
		{
			\preg_match_all($this->referencesRegexp, $node->textContent, $matches);
			foreach ($matches[0] as $match)
			{
				$key = \trim($match, '\\${}');
				$this->references['inText'][$key] = $key;
			}
		}
		foreach (TemplateHelper::getURLNodes($dom) as $node)
			if ($node instanceof DOMAttr
			 && \preg_match('(^(?:[$\\\\]\\d+|\\$\\{\\d+\\}))', \trim($node->value), $m))
			{
				$key = \trim($m[0], '\\${}');
				$this->references['asUrl'][$key] = $key;
			}
		$this->removeUnknownReferences();
	}
	protected function removeUnknownReferences()
	{
		foreach ($this->references as &$references)
			$references = \array_intersect_key($references, $this->captures);
	}
}