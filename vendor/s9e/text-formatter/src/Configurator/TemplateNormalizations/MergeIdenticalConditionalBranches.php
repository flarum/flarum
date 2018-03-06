<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class MergeIdenticalConditionalBranches extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'choose') as $choose)
		{
			self::mergeCompatibleBranches($choose);
			self::mergeConsecutiveBranches($choose);
		}
	}
	protected static function mergeCompatibleBranches(DOMElement $choose)
	{
		$node = $choose->firstChild;
		while ($node)
		{
			$nodes = self::collectCompatibleBranches($node);
			if (\count($nodes) > 1)
			{
				$node = \end($nodes)->nextSibling;
				self::mergeBranches($nodes);
			}
			else
				$node = $node->nextSibling;
		}
	}
	protected static function mergeConsecutiveBranches(DOMElement $choose)
	{
		$nodes = array();
		foreach ($choose->childNodes as $node)
			if (self::isXslWhen($node))
				$nodes[] = $node;
		$i = \count($nodes);
		while (--$i > 0)
			self::mergeBranches(array($nodes[$i - 1], $nodes[$i]));
	}
	protected static function collectCompatibleBranches(DOMNode $node)
	{
		$nodes  = array();
		$key    = \null;
		$values = array();
		while ($node && self::isXslWhen($node))
		{
			$branch = TemplateParser::parseEqualityExpr($node->getAttribute('test'));
			if ($branch === \false || \count($branch) !== 1)
				break;
			if (isset($key) && \key($branch) !== $key)
				break;
			if (\array_intersect($values, \end($branch)))
				break;
			$key    = \key($branch);
			$values = \array_merge($values, \end($branch));
			$nodes[] = $node;
			$node    = $node->nextSibling;
		}
		return $nodes;
	}
	protected static function mergeBranches(array $nodes)
	{
		$sortedNodes = array();
		foreach ($nodes as $node)
		{
			$outerXML = $node->ownerDocument->saveXML($node);
			$innerXML = \preg_replace('([^>]+>(.*)<[^<]+)s', '$1', $outerXML);
			$sortedNodes[$innerXML][] = $node;
		}
		foreach ($sortedNodes as $identicalNodes)
		{
			if (\count($identicalNodes) < 2)
				continue;
			$expr = array();
			foreach ($identicalNodes as $i => $node)
			{
				$expr[] = $node->getAttribute('test');
				if ($i > 0)
					$node->parentNode->removeChild($node);
			}
			$identicalNodes[0]->setAttribute('test', \implode(' or ', $expr));
		}
	}
	protected static function isXslWhen(DOMNode $node)
	{
		return ($node->namespaceURI === self::XMLNS_XSL && $node->localName === 'when');
	}
}