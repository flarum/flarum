<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Template;
class BBCodeMonkey
{
	const REGEXP = '(.).*?(?<!\\\\)(?>\\\\\\\\)*+\\g{-1}[DSUisu]*';
	public $allowedFilters = array(
		'addslashes',
		'dechex',
		'intval',
		'json_encode',
		'ltrim',
		'mb_strtolower',
		'mb_strtoupper',
		'rawurlencode',
		'rtrim',
		'str_rot13',
		'stripslashes',
		'strrev',
		'strtolower',
		'strtotime',
		'strtoupper',
		'trim',
		'ucfirst',
		'ucwords',
		'urlencode'
	);
	protected $configurator;
	public $tokenRegexp = array(
		'COLOR'      => '[a-zA-Z]+|#[0-9a-fA-F]+',
		'EMAIL'      => '[^@]+@.+?',
		'FLOAT'      => '(?>0|-?[1-9]\\d*)(?>\\.\\d+)?(?>e[1-9]\\d*)?',
		'ID'         => '[-a-zA-Z0-9_]+',
		'IDENTIFIER' => '[-a-zA-Z0-9_]+',
		'INT'        => '0|-?[1-9]\\d*',
		'INTEGER'    => '0|-?[1-9]\\d*',
		'NUMBER'     => '\\d+',
		'RANGE'      => '\\d+',
		'SIMPLETEXT' => '[-a-zA-Z0-9+.,_ ]+',
		'UINT'       => '0|[1-9]\\d*'
	);
	public $unfilteredTokens = array(
		'ANYTHING',
		'TEXT'
	);
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}
	public function create($usage, $template)
	{
		$_this = $this;
		$config = $this->parse($usage);
		if (!($template instanceof Template))
			$template = new Template($template);
		$template->replaceTokens(
			'#\\{(?:[A-Z]+[A-Z_0-9]*|@[-\\w]+)\\}#',
			function ($m) use ($config, $_this)
			{
				$tokenId = \substr($m[0], 1, -1);
				if ($tokenId[0] === '@')
					return array('expression', $tokenId);
				if (isset($config['tokens'][$tokenId]))
					return array('expression', '@' . $config['tokens'][$tokenId]);
				if ($tokenId === $config['passthroughToken'])
					return array('passthrough');
				if ($_this->isFilter($tokenId))
					throw new RuntimeException('Token {' . $tokenId . '} is ambiguous or undefined');
				return array('expression', '$' . $tokenId);
			}
		);
		$return = array(
			'bbcode'     => $config['bbcode'],
			'bbcodeName' => $config['bbcodeName'],
			'tag'        => $config['tag']
		);
		$return['tag']->template = $template;
		return $return;
	}
	protected function parse($usage)
	{
		$tag    = new Tag;
		$bbcode = new BBCode;
		$config = array(
			'tag'              => $tag,
			'bbcode'           => $bbcode,
			'passthroughToken' => \null
		);
		$usage = \preg_replace_callback(
			'#(\\{(?>HASH)?MAP=)([^:]+:[^,;}]+(?>,[^:]+:[^,;}]+)*)(?=[;}])#',
			function ($m)
			{
				return $m[1] . \base64_encode($m[2]);
			},
			$usage
		);
		$usage = \preg_replace_callback(
			'#(\\{(?:PARSE|REGEXP)=)(' . self::REGEXP . '(?:,' . self::REGEXP . ')*)#',
			function ($m)
			{
				return $m[1] . \base64_encode($m[2]);
			},
			$usage
		);
		$regexp = '(^'
		        . '\\[(?<bbcodeName>\\S+?)'
		        . '(?<defaultAttribute>=\\S+?)?'
		        . '(?<attributes>(?:\\s+[^=]+=\\S+?)*?)?'
		        . '\\s*(?:/?\\]|\\]\\s*(?<content>.*?)\\s*(?<endTag>\\[/\\1]))$)i';
		if (!\preg_match($regexp, \trim($usage), $m))
			throw new InvalidArgumentException('Cannot interpret the BBCode definition');
		$config['bbcodeName'] = BBCode::normalizeName($m['bbcodeName']);
		$definitions = \preg_split('#\\s+#', \trim($m['attributes']), -1, \PREG_SPLIT_NO_EMPTY);
		if (!empty($m['defaultAttribute']))
			\array_unshift($definitions, $m['bbcodeName'] . $m['defaultAttribute']);
		if (!empty($m['content']))
		{
			$regexp = '#^\\{' . RegexpBuilder::fromList($this->unfilteredTokens) . '[0-9]*\\}$#D';
			if (\preg_match($regexp, $m['content']))
				$config['passthroughToken'] = \substr($m['content'], 1, -1);
			else
			{
				$definitions[] = 'content=' . $m['content'];
				$bbcode->contentAttributes[] = 'content';
			}
		}
		$attributeDefinitions = array();
		foreach ($definitions as $definition)
		{
			$pos   = \strpos($definition, '=');
			$name  = \substr($definition, 0, $pos);
			$value = \preg_replace('(^"(.*?)")s', '$1', \substr($definition, 1 + $pos));
			$value = \preg_replace_callback(
				'#(\\{(?>HASHMAP|MAP|PARSE|REGEXP)=)([A-Za-z0-9+/]+=*)#',
				function ($m)
				{
					return $m[1] . \base64_decode($m[2]);
				},
				$value
			);
			if ($name[0] === '$')
			{
				$optionName = \substr($name, 1);
				$bbcode->$optionName = $this->convertValue($value);
			}
			elseif ($name[0] === '#')
			{
				$ruleName = \substr($name, 1);
				foreach (\explode(',', $value) as $value)
					$tag->rules->$ruleName($this->convertValue($value));
			}
			else
			{
				$attrName = \strtolower(\trim($name));
				$attributeDefinitions[] = array($attrName, $value);
			}
		}
		$tokens = $this->addAttributes($attributeDefinitions, $bbcode, $tag);
		if (isset($tokens[$config['passthroughToken']]))
			$config['passthroughToken'] = \null;
		$config['tokens'] = \array_filter($tokens);
		return $config;
	}
	protected function addAttributes(array $definitions, BBCode $bbcode, Tag $tag)
	{
		$composites = array();
		$table = array();
		foreach ($definitions as $_e874cdc7)
		{
			list($attrName, $definition) = $_e874cdc7;
			if (!isset($bbcode->defaultAttribute))
				$bbcode->defaultAttribute = $attrName;
			$tokens = self::parseTokens($definition);
			if (empty($tokens))
				throw new RuntimeException('No valid tokens found in ' . $attrName . "'s definition " . $definition);
			if ($tokens[0]['content'] === $definition)
			{
				$token = $tokens[0];
				if ($token['type'] === 'PARSE')
					foreach ($token['regexps'] as $regexp)
						$tag->attributePreprocessors->add($attrName, $regexp);
				elseif (isset($tag->attributes[$attrName]))
					throw new RuntimeException("Attribute '" . $attrName . "' is declared twice");
				else
				{
					if (!empty($token['options']['useContent']))
						$bbcode->contentAttributes[] = $attrName;
					unset($token['options']['useContent']);
					$tag->attributes[$attrName] = $this->generateAttribute($token);
					$tokenId = $token['id'];
					$table[$tokenId] = (isset($table[$tokenId]))
					                 ? \false
					                 : $attrName;
				}
			}
			else
				$composites[] = array($attrName, $definition, $tokens);
		}
		foreach ($composites as $_2d84f0a0)
		{
			list($attrName, $definition, $tokens) = $_2d84f0a0;
			$regexp  = '/^';
			$lastPos = 0;
			$usedTokens = array();
			foreach ($tokens as $token)
			{
				$tokenId   = $token['id'];
				$tokenType = $token['type'];
				if ($tokenType === 'PARSE')
					throw new RuntimeException('{PARSE} tokens can only be used has the sole content of an attribute');
				if (isset($usedTokens[$tokenId]))
					throw new RuntimeException('Token {' . $tokenId . '} used multiple times in attribute ' . $attrName . "'s definition");
				$usedTokens[$tokenId] = 1;
				if (isset($table[$tokenId]))
				{
					$matchName = $table[$tokenId];
					if ($matchName === \false)
						throw new RuntimeException('Token {' . $tokenId . "} used in attribute '" . $attrName . "' is ambiguous");
				}
				else
				{
					$i = 0;
					do
					{
						$matchName = $attrName . $i;
						++$i;
					}
					while (isset($tag->attributes[$matchName]));
					$attribute = $tag->attributes->add($matchName);
					if (!\in_array($tokenType, $this->unfilteredTokens, \true))
					{
						$filter = $this->configurator->attributeFilters->get('#' . \strtolower($tokenType));
						$attribute->filterChain->append($filter);
					}
					$table[$tokenId] = $matchName;
				}
				$regexp .= \preg_quote(\substr($definition, $lastPos, $token['pos'] - $lastPos), '/');
				$expr = (isset($this->tokenRegexp[$tokenType]))
				      ? $this->tokenRegexp[$tokenType]
				      : '.+?';
				$regexp .= '(?<' . $matchName . '>' . $expr . ')';
				$lastPos = $token['pos'] + \strlen($token['content']);
			}
			$regexp .= \preg_quote(\substr($definition, $lastPos), '/') . '$/D';
			$tag->attributePreprocessors->add($attrName, $regexp);
		}
		$newAttributes = array();
		foreach ($tag->attributePreprocessors as $attributePreprocessor)
			foreach ($attributePreprocessor->getAttributes() as $attrName => $regexp)
			{
				if (isset($tag->attributes[$attrName]))
					continue;
				if (isset($newAttributes[$attrName])
				 && $newAttributes[$attrName] !== $regexp)
					throw new RuntimeException("Ambiguous attribute '" . $attrName . "' created using different regexps needs to be explicitly defined");
				$newAttributes[$attrName] = $regexp;
			}
		foreach ($newAttributes as $attrName => $regexp)
		{
			$filter = $this->configurator->attributeFilters->get('#regexp');
			$tag->attributes->add($attrName)->filterChain->append($filter)->setRegexp($regexp);
		}
		return $table;
	}
	protected function convertValue($value)
	{
		if ($value === 'true')
			return \true;
		if ($value === 'false')
			return \false;
		return $value;
	}
	protected static function parseTokens($definition)
	{
		$tokenTypes = array(
			'choice' => 'CHOICE[0-9]*=(?<choices>.+?)',
			'map'    => '(?:HASH)?MAP[0-9]*=(?<map>.+?)',
			'parse'  => 'PARSE=(?<regexps>' . self::REGEXP . '(?:,' . self::REGEXP . ')*)',
			'range'  => 'RAN(?:DOM|GE)[0-9]*=(?<min>-?[0-9]+),(?<max>-?[0-9]+)',
			'regexp' => 'REGEXP[0-9]*=(?<regexp>' . self::REGEXP . ')',
			'other'  => '(?<other>[A-Z_]+[0-9]*)'
		);
		\preg_match_all(
			'#\\{(' . \implode('|', $tokenTypes) . ')(?<options>(?:;[^;]*)*)\\}#',
			$definition,
			$matches,
			\PREG_SET_ORDER | \PREG_OFFSET_CAPTURE
		);
		$tokens = array();
		foreach ($matches as $m)
		{
			if (isset($m['other'][0])
			 && \preg_match('#^(?:CHOICE|HASHMAP|MAP|REGEXP|PARSE|RANDOM|RANGE)#', $m['other'][0]))
				throw new RuntimeException("Malformed token '" . $m['other'][0] . "'");
			$token = array(
				'pos'     => $m[0][1],
				'content' => $m[0][0],
				'options' => array()
			);
			$head = $m[1][0];
			$pos  = \strpos($head, '=');
			if ($pos === \false)
				$token['id'] = $head;
			else
			{
				$token['id'] = \substr($head, 0, $pos);
				foreach ($m as $k => $v)
					if (!\is_numeric($k) && $k !== 'options' && $v[1] !== -1)
						$token[$k] = $v[0];
			}
			$token['type'] = \rtrim($token['id'], '0123456789');
			$options = (isset($m['options'][0])) ? $m['options'][0] : '';
			foreach (\preg_split('#;+#', $options, -1, \PREG_SPLIT_NO_EMPTY) as $pair)
			{
				$pos = \strpos($pair, '=');
				if ($pos === \false)
				{
					$k = $pair;
					$v = \true;
				}
				else
				{
					$k = \substr($pair, 0, $pos);
					$v = \substr($pair, 1 + $pos);
				}
				$token['options'][$k] = $v;
			}
			if ($token['type'] === 'PARSE')
			{
				\preg_match_all('#' . self::REGEXP . '(?:,|$)#', $token['regexps'], $m);
				$regexps = array();
				foreach ($m[0] as $regexp)
					$regexps[] = \rtrim($regexp, ',');
				$token['regexps'] = $regexps;
			}
			$tokens[] = $token;
		}
		return $tokens;
	}
	protected function generateAttribute(array $token)
	{
		$attribute = new Attribute;
		if (isset($token['options']['preFilter']))
		{
			$this->appendFilters($attribute, $token['options']['preFilter']);
			unset($token['options']['preFilter']);
		}
		if ($token['type'] === 'REGEXP')
		{
			$filter = $this->configurator->attributeFilters->get('#regexp');
			$attribute->filterChain->append($filter)->setRegexp($token['regexp']);
		}
		elseif ($token['type'] === 'RANGE')
		{
			$filter = $this->configurator->attributeFilters->get('#range');
			$attribute->filterChain->append($filter)->setRange($token['min'], $token['max']);
		}
		elseif ($token['type'] === 'RANDOM')
		{
			$attribute->generator = new ProgrammableCallback('mt_rand');
			$attribute->generator->addParameterByValue((int) $token['min']);
			$attribute->generator->addParameterByValue((int) $token['max']);
		}
		elseif ($token['type'] === 'CHOICE')
		{
			$filter = $this->configurator->attributeFilters->get('#choice');
			$attribute->filterChain->append($filter)->setValues(
				\explode(',', $token['choices']),
				!empty($token['options']['caseSensitive'])
			);
			unset($token['options']['caseSensitive']);
		}
		elseif ($token['type'] === 'HASHMAP' || $token['type'] === 'MAP')
		{
			$map = array();
			foreach (\explode(',', $token['map']) as $pair)
			{
				$pos = \strpos($pair, ':');
				if ($pos === \false)
					throw new RuntimeException("Invalid map assignment '" . $pair . "'");
				$map[\substr($pair, 0, $pos)] = \substr($pair, 1 + $pos);
			}
			if ($token['type'] === 'HASHMAP')
			{
				$filter = $this->configurator->attributeFilters->get('#hashmap');
				$attribute->filterChain->append($filter)->setMap(
					$map,
					!empty($token['options']['strict'])
				);
			}
			else
			{
				$filter = $this->configurator->attributeFilters->get('#map');
				$attribute->filterChain->append($filter)->setMap(
					$map,
					!empty($token['options']['caseSensitive']),
					!empty($token['options']['strict'])
				);
			}
			unset($token['options']['caseSensitive']);
			unset($token['options']['strict']);
		}
		elseif (!\in_array($token['type'], $this->unfilteredTokens, \true))
		{
			$filter = $this->configurator->attributeFilters->get('#' . $token['type']);
			$attribute->filterChain->append($filter);
		}
		if (isset($token['options']['postFilter']))
		{
			$this->appendFilters($attribute, $token['options']['postFilter']);
			unset($token['options']['postFilter']);
		}
		if (isset($token['options']['required']))
			$token['options']['required'] = (bool) $token['options']['required'];
		elseif (isset($token['options']['optional']))
			$token['options']['required'] = !$token['options']['optional'];
		unset($token['options']['optional']);
		foreach ($token['options'] as $k => $v)
			$attribute->$k = $v;
		return $attribute;
	}
	protected function appendFilters(Attribute $attribute, $filters)
	{
		foreach (\preg_split('#\\s*,\\s*#', $filters) as $filterName)
		{
			if (\substr($filterName, 0, 1) !== '#'
			 && !\in_array($filterName, $this->allowedFilters, \true))
				throw new RuntimeException("Filter '" . $filterName . "' is not allowed");
			$filter = $this->configurator->attributeFilters->get($filterName);
			$attribute->filterChain->append($filter);
		}
	}
	public function isFilter($tokenId)
	{
		$filterName = \rtrim($tokenId, '0123456789');
		if (\in_array($filterName, $this->unfilteredTokens, \true))
			return \true;
		try
		{
			if ($this->configurator->attributeFilters->get('#' . $filterName))
				return \true;
		}
		catch (Exception $e)
		{
			}
		return \false;
	}
}