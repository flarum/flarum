<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;
use RuntimeException;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	protected $attributes;
	protected $bbcodeConfig;
	protected $bbcodeName;
	protected $bbcodeSuffix;
	protected $pos;
	protected $startPos;
	protected $text;
	protected $textLen;
	protected $uppercaseText;
	public function parse($text, array $matches)
	{
		$this->text          = $text;
		$this->textLen       = \strlen($text);
		$this->uppercaseText = '';
		foreach ($matches as $m)
		{
			$this->bbcodeName = \strtoupper($m[1][0]);
			if (!isset($this->config['bbcodes'][$this->bbcodeName]))
				continue;
			$this->bbcodeConfig = $this->config['bbcodes'][$this->bbcodeName];
			$this->startPos     = $m[0][1];
			$this->pos          = $this->startPos + \strlen($m[0][0]);
			try
			{
				$this->parseBBCode();
			}
			catch (RuntimeException $e)
			{
				}
		}
	}
	protected function addBBCodeEndTag()
	{
		return $this->parser->addEndTag($this->getTagName(), $this->startPos, $this->pos - $this->startPos);
	}
	protected function addBBCodeSelfClosingTag()
	{
		$tag = $this->parser->addSelfClosingTag($this->getTagName(), $this->startPos, $this->pos - $this->startPos);
		$tag->setAttributes($this->attributes);
		return $tag;
	}
	protected function addBBCodeStartTag()
	{
		$tag = $this->parser->addStartTag($this->getTagName(), $this->startPos, $this->pos - $this->startPos);
		$tag->setAttributes($this->attributes);
		return $tag;
	}
	protected function captureEndTag()
	{
		if (empty($this->uppercaseText))
			$this->uppercaseText = \strtoupper($this->text);
		$match     = '[/' . $this->bbcodeName . $this->bbcodeSuffix . ']';
		$endTagPos = \strpos($this->uppercaseText, $match, $this->pos);
		if ($endTagPos === \false)
			return;
		return $this->parser->addEndTag($this->getTagName(), $endTagPos, \strlen($match));
	}
	protected function getTagName()
	{
		return (isset($this->bbcodeConfig['tagName']))
		     ? $this->bbcodeConfig['tagName']
		     : $this->bbcodeName;
	}
	protected function parseAttributes()
	{
		$firstPos = $this->pos;
		$this->attributes = array();
		while ($this->pos < $this->textLen)
		{
			$c = $this->text[$this->pos];
			if (\strpos(" \n\t", $c) !== \false)
			{
				++$this->pos;
				continue;
			}
			if (\strpos('/]', $c) !== \false)
				return;
			$spn = \strspn($this->text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-', $this->pos);
			if ($spn)
			{
				$attrName = \strtolower(\substr($this->text, $this->pos, $spn));
				$this->pos += $spn;
				if ($this->pos >= $this->textLen)
					throw new RuntimeException;
				if ($this->text[$this->pos] !== '=')
					continue;
			}
			elseif ($c === '=' && $this->pos === $firstPos)
				$attrName = (isset($this->bbcodeConfig['defaultAttribute']))
				          ? $this->bbcodeConfig['defaultAttribute']
				          : \strtolower($this->bbcodeName);
			else
				throw new RuntimeException;
			if (++$this->pos >= $this->textLen)
				throw new RuntimeException;
			$this->attributes[$attrName] = $this->parseAttributeValue();
		}
	}
	protected function parseAttributeValue()
	{
		if ($this->text[$this->pos] === '"' || $this->text[$this->pos] === "'")
			return $this->parseQuotedAttributeValue();
		if (!\preg_match('#[^\\]\\n]*?(?=\\s*(?:\\s/)?\\]|\\s+[-\\w]+=)#', $this->text, $m, \null, $this->pos))
			throw new RuntimeException;
		$attrValue  = $m[0];
		$this->pos += \strlen($attrValue);
		return $attrValue;
	}
	protected function parseBBCode()
	{
		$this->parseBBCodeSuffix();
		if ($this->text[$this->startPos + 1] === '/')
		{
			if (\substr($this->text, $this->pos, 1) === ']' && $this->bbcodeSuffix === '')
			{
				++$this->pos;
				$this->addBBCodeEndTag();
			}
			return;
		}
		$this->parseAttributes();
		if (isset($this->bbcodeConfig['predefinedAttributes']))
			$this->attributes += $this->bbcodeConfig['predefinedAttributes'];
		if (\substr($this->text, $this->pos, 1) === ']')
			++$this->pos;
		else
		{
			if (\substr($this->text, $this->pos, 2) === '/]')
			{
				$this->pos += 2;
				$this->addBBCodeSelfClosingTag();
			}
			return;
		}
		$contentAttributes = array();
		if (isset($this->bbcodeConfig['contentAttributes']))
			foreach ($this->bbcodeConfig['contentAttributes'] as $attrName)
				if (!isset($this->attributes[$attrName]))
					$contentAttributes[] = $attrName;
		$requireEndTag = ($this->bbcodeSuffix || !empty($this->bbcodeConfig['forceLookahead']));
		$endTag = ($requireEndTag || !empty($contentAttributes)) ? $this->captureEndTag() : \null;
		if (isset($endTag))
			foreach ($contentAttributes as $attrName)
				$this->attributes[$attrName] = \substr($this->text, $this->pos, $endTag->getPos() - $this->pos);
		elseif ($requireEndTag)
			return;
		$tag = $this->addBBCodeStartTag();
		if (isset($endTag))
			$tag->pairWith($endTag);
	}
	protected function parseBBCodeSuffix()
	{
		$this->bbcodeSuffix = '';
		if ($this->text[$this->pos] === ':')
		{
			$spn = 1 + \strspn($this->text, '0123456789', 1 + $this->pos);
			$this->bbcodeSuffix = \substr($this->text, $this->pos, $spn);
			$this->pos += $spn;
		}
	}
	protected function parseQuotedAttributeValue()
	{
		$quote    = $this->text[$this->pos];
		$valuePos = $this->pos + 1;
		while (1)
		{
			$this->pos = \strpos($this->text, $quote, $this->pos + 1);
			if ($this->pos === \false)
				throw new RuntimeException;
			$n = 0;
			do
			{
				++$n;
			}
			while ($this->text[$this->pos - $n] === '\\');
			if ($n % 2)
				break;
		}
		$attrValue = \preg_replace(
			'#\\\\([\\\\\'"])#',
			'$1',
			\substr($this->text, $valuePos, $this->pos - $valuePos)
		);
		++$this->pos;
		return $attrValue;
	}
}