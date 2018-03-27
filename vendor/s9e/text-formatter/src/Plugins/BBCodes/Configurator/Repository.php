<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
class Repository
{
	protected $bbcodeMonkey;
	protected $dom;
	public function __construct($value, BBCodeMonkey $bbcodeMonkey)
	{
		if (!($value instanceof DOMDocument))
		{
			if (!\file_exists($value))
				throw new InvalidArgumentException('Not a DOMDocument or the path to a repository file');
			$dom = new DOMDocument;
			$dom->preserveWhiteSpace = \false;
			$useErrors = \libxml_use_internal_errors(\true);
			$success = $dom->load($value);
			\libxml_use_internal_errors($useErrors);
			if (!$success)
				throw new InvalidArgumentException('Invalid repository file');
			$value = $dom;
		}
		$this->bbcodeMonkey = $bbcodeMonkey;
		$this->dom = $value;
	}
	public function get($name, array $vars = array())
	{
		$name = \preg_replace_callback(
			'/^[^#]+/',
			function ($m)
			{
				return BBCode::normalizeName($m[0]);
			},
			$name
		);
		$xpath = new DOMXPath($this->dom);
		$node  = $xpath->query('//bbcode[@name="' . \htmlspecialchars($name) . '"]')->item(0);
		if (!($node instanceof DOMElement))
			throw new RuntimeException("Could not find '" . $name . "' in repository");
		$clonedNode = $node->cloneNode(\true);
		foreach ($xpath->query('.//var', $clonedNode) as $varNode)
		{
			$varName = $varNode->getAttribute('name');
			if (isset($vars[$varName]))
				$varNode->parentNode->replaceChild(
					$this->dom->createTextNode($vars[$varName]),
					$varNode
				);
		}
		$usage      = $xpath->evaluate('string(usage)', $clonedNode);
		$template   = $xpath->evaluate('string(template)', $clonedNode);
		$config     = $this->bbcodeMonkey->create($usage, $template);
		$bbcode     = $config['bbcode'];
		$bbcodeName = $config['bbcodeName'];
		$tag        = $config['tag'];
		if ($node->hasAttribute('tagName'))
			$bbcode->tagName = $node->getAttribute('tagName');
		foreach ($xpath->query('rules/*', $node) as $ruleNode)
		{
			$methodName = $ruleNode->nodeName;
			$args       = array();
			if ($ruleNode->textContent)
				$args[] = $ruleNode->textContent;
			\call_user_func_array(array($tag->rules, $methodName), $args);
		}
		foreach ($node->getElementsByTagName('predefinedAttributes') as $predefinedAttributes)
			foreach ($predefinedAttributes->attributes as $attribute)
				$bbcode->predefinedAttributes->set($attribute->name, $attribute->value);
		return array(
			'bbcode'     => $bbcode,
			'bbcodeName' => $bbcodeName,
			'tag'        => $tag
		);
	}
}