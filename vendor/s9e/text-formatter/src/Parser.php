<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;
class Parser
{
	const RULE_AUTO_CLOSE        = 1;
	const RULE_AUTO_REOPEN       = 2;
	const RULE_BREAK_PARAGRAPH   = 4;
	const RULE_CREATE_PARAGRAPHS = 8;
	const RULE_DISABLE_AUTO_BR   = 16;
	const RULE_ENABLE_AUTO_BR    = 32;
	const RULE_IGNORE_TAGS       = 64;
	const RULE_IGNORE_TEXT       = 128;
	const RULE_IGNORE_WHITESPACE = 256;
	const RULE_IS_TRANSPARENT    = 512;
	const RULE_PREVENT_BR        = 1024;
	const RULE_SUSPEND_AUTO_BR   = 2048;
	const RULE_TRIM_FIRST_LINE   = 4096;
	const RULES_AUTO_LINEBREAKS = 2096;
	const RULES_INHERITANCE = 32;
	const WHITESPACE = ' 
	';
	protected $cntOpen;
	protected $cntTotal;
	protected $context;
	protected $currentFixingCost;
	protected $currentTag;
	protected $isRich;
	protected $logger;
	public $maxFixingCost = 1000;
	protected $namespaces;
	protected $openTags;
	protected $output;
	protected $pos;
	protected $pluginParsers = array();
	protected $pluginsConfig;
	public $registeredVars = array();
	protected $rootContext;
	protected $tagsConfig;
	protected $tagStack;
	protected $tagStackIsSorted;
	protected $text;
	protected $textLen;
	protected $uid = 0;
	protected $wsPos;
	public function __construct(array $config)
	{
		$this->pluginsConfig  = $config['plugins'];
		$this->registeredVars = $config['registeredVars'];
		$this->rootContext    = $config['rootContext'];
		$this->tagsConfig     = $config['tags'];
		$this->__wakeup();
	}
	public function __sleep()
	{
		return array('pluginsConfig', 'registeredVars', 'rootContext', 'tagsConfig');
	}
	public function __wakeup()
	{
		$this->logger = new Logger;
	}
	protected function reset($text)
	{
		$text = \preg_replace('/\\r\\n?/', "\n", $text);
		$text = \preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]+/S', '', $text);
		$this->logger->clear();
		$this->cntOpen           = array();
		$this->cntTotal          = array();
		$this->currentFixingCost = 0;
		$this->currentTag        = \null;
		$this->isRich            = \false;
		$this->namespaces        = array();
		$this->openTags          = array();
		$this->output            = '';
		$this->pos               = 0;
		$this->tagStack          = array();
		$this->tagStackIsSorted  = \false;
		$this->text              = $text;
		$this->textLen           = \strlen($text);
		$this->wsPos             = 0;
		$this->context = $this->rootContext;
		$this->context['inParagraph'] = \false;
		++$this->uid;
	}
	protected function setTagOption($tagName, $optionName, $optionValue)
	{
		if (isset($this->tagsConfig[$tagName]))
		{
			$tagConfig = $this->tagsConfig[$tagName];
			unset($this->tagsConfig[$tagName]);
			$tagConfig[$optionName]     = $optionValue;
			$this->tagsConfig[$tagName] = $tagConfig;
		}
	}
	public function disableTag($tagName)
	{
		$this->setTagOption($tagName, 'isDisabled', \true);
	}
	public function enableTag($tagName)
	{
		if (isset($this->tagsConfig[$tagName]))
			unset($this->tagsConfig[$tagName]['isDisabled']);
	}
	public function getLogger()
	{
		return $this->logger;
	}
	public function getText()
	{
		return $this->text;
	}
	public function parse($text)
	{
		$this->reset($text);
		$uid = $this->uid;
		$this->executePluginParsers();
		$this->processTags();
		$this->finalizeOutput();
		if ($this->uid !== $uid)
			throw new RuntimeException('The parser has been reset during execution');
		if ($this->currentFixingCost > $this->maxFixingCost)
			$this->logger->warn('Fixing cost limit exceeded');
		return $this->output;
	}
	public function setTagLimit($tagName, $tagLimit)
	{
		$this->setTagOption($tagName, 'tagLimit', $tagLimit);
	}
	public function setNestingLimit($tagName, $nestingLimit)
	{
		$this->setTagOption($tagName, 'nestingLimit', $nestingLimit);
	}
	public static function executeAttributePreprocessors(Tag $tag, array $tagConfig)
	{
		if (!empty($tagConfig['attributePreprocessors']))
			foreach ($tagConfig['attributePreprocessors'] as $_5f417eec)
			{
				list($attrName, $regexp, $map) = $_5f417eec;
				if (!$tag->hasAttribute($attrName))
					continue;
				self::executeAttributePreprocessor($tag, $attrName, $regexp, $map);
			}
		return \true;
	}
	protected static function executeAttributePreprocessor(Tag $tag, $attrName, $regexp, $map)
	{
		$attrValue = $tag->getAttribute($attrName);
		$captures  = self::getNamedCaptures($attrValue, $regexp, $map);
		foreach ($captures as $k => $v)
			if ($k === $attrName || !$tag->hasAttribute($k))
				$tag->setAttribute($k, $v);
	}
	protected static function getNamedCaptures($attrValue, $regexp, $map)
	{
		if (!\preg_match($regexp, $attrValue, $m))
			return array();
		$values = array();
		foreach ($map as $i => $k)
			if (isset($m[$i]) && $m[$i] !== '')
				$values[$k] = $m[$i];
		return $values;
	}
	protected static function executeFilter(array $filter, array $vars)
	{
		$callback = $filter['callback'];
		$params   = (isset($filter['params'])) ? $filter['params'] : array();
		$args = array();
		foreach ($params as $k => $v)
			if (\is_numeric($k))
				$args[] = $v;
			elseif (isset($vars[$k]))
				$args[] = $vars[$k];
			elseif (isset($vars['registeredVars'][$k]))
				$args[] = $vars['registeredVars'][$k];
			else
				$args[] = \null;
		return \call_user_func_array($callback, $args);
	}
	public static function filterAttributes(Tag $tag, array $tagConfig, array $registeredVars, Logger $logger)
	{
		if (empty($tagConfig['attributes']))
		{
			$tag->setAttributes(array());
			return \true;
		}
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
			if (isset($attrConfig['generator']))
				$tag->setAttribute(
					$attrName,
					self::executeFilter(
						$attrConfig['generator'],
						array(
							'attrName'       => $attrName,
							'logger'         => $logger,
							'registeredVars' => $registeredVars
						)
					)
				);
		foreach ($tag->getAttributes() as $attrName => $attrValue)
		{
			if (!isset($tagConfig['attributes'][$attrName]))
			{
				$tag->removeAttribute($attrName);
				continue;
			}
			$attrConfig = $tagConfig['attributes'][$attrName];
			if (!isset($attrConfig['filterChain']))
				continue;
			$logger->setAttribute($attrName);
			foreach ($attrConfig['filterChain'] as $filter)
			{
				$attrValue = self::executeFilter(
					$filter,
					array(
						'attrName'       => $attrName,
						'attrValue'      => $attrValue,
						'logger'         => $logger,
						'registeredVars' => $registeredVars
					)
				);
				if ($attrValue === \false)
				{
					$tag->removeAttribute($attrName);
					break;
				}
			}
			if ($attrValue !== \false)
				$tag->setAttribute($attrName, $attrValue);
			$logger->unsetAttribute();
		}
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
			if (!$tag->hasAttribute($attrName))
				if (isset($attrConfig['defaultValue']))
					$tag->setAttribute($attrName, $attrConfig['defaultValue']);
				elseif (!empty($attrConfig['required']))
					return \false;
		return \true;
	}
	protected function filterTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];
		$isValid   = \true;
		if (!empty($tagConfig['filterChain']))
		{
			$this->logger->setTag($tag);
			$vars = array(
				'logger'         => $this->logger,
				'openTags'       => $this->openTags,
				'parser'         => $this,
				'registeredVars' => $this->registeredVars,
				'tag'            => $tag,
				'tagConfig'      => $tagConfig,
				'text'           => $this->text
			);
			foreach ($tagConfig['filterChain'] as $filter)
				if (!self::executeFilter($filter, $vars))
				{
					$isValid = \false;
					break;
				}
			$this->logger->unsetTag();
		}
		return $isValid;
	}
	protected function finalizeOutput()
	{
		$this->outputText($this->textLen, 0, \true);
		do
		{
			$this->output = \preg_replace('(<([^ />]+)></\\1>)', '', $this->output, -1, $cnt);
		}
		while ($cnt > 0);
		if (\strpos($this->output, '</i><i>') !== \false)
			$this->output = \str_replace('</i><i>', '', $this->output);
		$this->output = Utils::encodeUnicodeSupplementaryCharacters($this->output);
		$tagName = ($this->isRich) ? 'r' : 't';
		$tmp = '<' . $tagName;
		foreach (\array_keys($this->namespaces) as $prefix)
			$tmp .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		$this->output = $tmp . '>' . $this->output . '</' . $tagName . '>';
	}
	protected function outputTag(Tag $tag)
	{
		$this->isRich = \true;
		$tagName  = $tag->getName();
		$tagPos   = $tag->getPos();
		$tagLen   = $tag->getLen();
		$tagFlags = $tag->getFlags();
		if ($tagFlags & self::RULE_IGNORE_WHITESPACE)
		{
			$skipBefore = 1;
			$skipAfter  = ($tag->isEndTag()) ? 2 : 1;
		}
		else
			$skipBefore = $skipAfter = 0;
		$closeParagraph = \false;
		if ($tag->isStartTag())
		{
			if ($tagFlags & self::RULE_BREAK_PARAGRAPH)
				$closeParagraph = \true;
		}
		else
			$closeParagraph = \true;
		$this->outputText($tagPos, $skipBefore, $closeParagraph);
		$tagText = ($tagLen)
		         ? \htmlspecialchars(\substr($this->text, $tagPos, $tagLen), \ENT_NOQUOTES, 'UTF-8')
		         : '';
		if ($tag->isStartTag())
		{
			if (!($tagFlags & self::RULE_BREAK_PARAGRAPH))
				$this->outputParagraphStart($tagPos);
			$colonPos = \strpos($tagName, ':');
			if ($colonPos)
				$this->namespaces[\substr($tagName, 0, $colonPos)] = 0;
			$this->output .= '<' . $tagName;
			$attributes = $tag->getAttributes();
			\ksort($attributes);
			foreach ($attributes as $attrName => $attrValue)
				$this->output .= ' ' . $attrName . '="' . \str_replace("\n", '&#10;', \htmlspecialchars($attrValue, \ENT_COMPAT, 'UTF-8')) . '"';
			if ($tag->isSelfClosingTag())
				if ($tagLen)
					$this->output .= '>' . $tagText . '</' . $tagName . '>';
				else
					$this->output .= '/>';
			elseif ($tagLen)
				$this->output .= '><s>' . $tagText . '</s>';
			else
				$this->output .= '>';
		}
		else
		{
			if ($tagLen)
				$this->output .= '<e>' . $tagText . '</e>';
			$this->output .= '</' . $tagName . '>';
		}
		$this->pos = $tagPos + $tagLen;
		$this->wsPos = $this->pos;
		while ($skipAfter && $this->wsPos < $this->textLen && $this->text[$this->wsPos] === "\n")
		{
			--$skipAfter;
			++$this->wsPos;
		}
	}
	protected function outputText($catchupPos, $maxLines, $closeParagraph)
	{
		if ($closeParagraph)
			if (!($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS))
				$closeParagraph = \false;
			else
				$maxLines = -1;
		if ($this->pos >= $catchupPos)
		{
			if ($closeParagraph)
				$this->outputParagraphEnd();
			return;
		}
		if ($this->wsPos > $this->pos)
		{
			$skipPos       = \min($catchupPos, $this->wsPos);
			$this->output .= \substr($this->text, $this->pos, $skipPos - $this->pos);
			$this->pos     = $skipPos;
			if ($this->pos >= $catchupPos)
			{
				if ($closeParagraph)
					$this->outputParagraphEnd();
				return;
			}
		}
		if ($this->context['flags'] & self::RULE_IGNORE_TEXT)
		{
			$catchupLen  = $catchupPos - $this->pos;
			$catchupText = \substr($this->text, $this->pos, $catchupLen);
			if (\strspn($catchupText, " \n\t") < $catchupLen)
				$catchupText = '<i>' . $catchupText . '</i>';
			$this->output .= $catchupText;
			$this->pos = $catchupPos;
			if ($closeParagraph)
				$this->outputParagraphEnd();
			return;
		}
		$ignorePos = $catchupPos;
		$ignoreLen = 0;
		while ($maxLines && --$ignorePos >= $this->pos)
		{
			$c = $this->text[$ignorePos];
			if (\strpos(self::WHITESPACE, $c) === \false)
				break;
			if ($c === "\n")
				--$maxLines;
			++$ignoreLen;
		}
		$catchupPos -= $ignoreLen;
		if ($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS)
		{
			if (!$this->context['inParagraph'])
			{
				$this->outputWhitespace($catchupPos);
				if ($catchupPos > $this->pos)
					$this->outputParagraphStart($catchupPos);
			}
			$pbPos = \strpos($this->text, "\n\n", $this->pos);
			while ($pbPos !== \false && $pbPos < $catchupPos)
			{
				$this->outputText($pbPos, 0, \true);
				$this->outputParagraphStart($catchupPos);
				$pbPos = \strpos($this->text, "\n\n", $this->pos);
			}
		}
		if ($catchupPos > $this->pos)
		{
			$catchupText = \htmlspecialchars(
				\substr($this->text, $this->pos, $catchupPos - $this->pos),
				\ENT_NOQUOTES,
				'UTF-8'
			);
			if (($this->context['flags'] & self::RULES_AUTO_LINEBREAKS) === self::RULE_ENABLE_AUTO_BR)
				$catchupText = \str_replace("\n", "<br/>\n", $catchupText);
			$this->output .= $catchupText;
		}
		if ($closeParagraph)
			$this->outputParagraphEnd();
		if ($ignoreLen)
			$this->output .= \substr($this->text, $catchupPos, $ignoreLen);
		$this->pos = $catchupPos + $ignoreLen;
	}
	protected function outputBrTag(Tag $tag)
	{
		$this->outputText($tag->getPos(), 0, \false);
		$this->output .= '<br/>';
	}
	protected function outputIgnoreTag(Tag $tag)
	{
		$tagPos = $tag->getPos();
		$tagLen = $tag->getLen();
		$ignoreText = \substr($this->text, $tagPos, $tagLen);
		$this->outputText($tagPos, 0, \false);
		$this->output .= '<i>' . \htmlspecialchars($ignoreText, \ENT_NOQUOTES, 'UTF-8') . '</i>';
		$this->isRich = \true;
		$this->pos = $tagPos + $tagLen;
	}
	protected function outputParagraphStart($maxPos)
	{
		if ($this->context['inParagraph']
		 || !($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS))
			return;
		$this->outputWhitespace($maxPos);
		if ($this->pos < $this->textLen)
		{
			$this->output .= '<p>';
			$this->context['inParagraph'] = \true;
		}
	}
	protected function outputParagraphEnd()
	{
		if (!$this->context['inParagraph'])
			return;
		$this->output .= '</p>';
		$this->context['inParagraph'] = \false;
	}
	protected function outputVerbatim(Tag $tag)
	{
		$flags = $this->context['flags'];
		$this->context['flags'] = $tag->getFlags();
		$this->outputText($this->currentTag->getPos() + $this->currentTag->getLen(), 0, \false);
		$this->context['flags'] = $flags;
	}
	protected function outputWhitespace($maxPos)
	{
		if ($maxPos > $this->pos)
		{
			$spn = \strspn($this->text, self::WHITESPACE, $this->pos, $maxPos - $this->pos);
			if ($spn)
			{
				$this->output .= \substr($this->text, $this->pos, $spn);
				$this->pos += $spn;
			}
		}
	}
	public function disablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
		{
			$pluginConfig = $this->pluginsConfig[$pluginName];
			unset($this->pluginsConfig[$pluginName]);
			$pluginConfig['isDisabled'] = \true;
			$this->pluginsConfig[$pluginName] = $pluginConfig;
		}
	}
	public function enablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
			$this->pluginsConfig[$pluginName]['isDisabled'] = \false;
	}
	protected function executePluginParser($pluginName)
	{
		$pluginConfig = $this->pluginsConfig[$pluginName];
		if (isset($pluginConfig['quickMatch']) && \strpos($this->text, $pluginConfig['quickMatch']) === \false)
			return;
		$matches = array();
		if (isset($pluginConfig['regexp']))
		{
			$matches = $this->getMatches($pluginConfig['regexp'], $pluginConfig['regexpLimit']);
			if (empty($matches))
				return;
		}
		\call_user_func($this->getPluginParser($pluginName), $this->text, $matches);
	}
	protected function executePluginParsers()
	{
		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
			if (empty($pluginConfig['isDisabled']))
				$this->executePluginParser($pluginName);
	}
	protected function getMatches($regexp, $limit)
	{
		$cnt = \preg_match_all($regexp, $this->text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);
		if ($cnt > $limit)
			$matches = \array_slice($matches, 0, $limit);
		return $matches;
	}
	protected function getPluginParser($pluginName)
	{
		if (!isset($this->pluginParsers[$pluginName]))
		{
			$pluginConfig = $this->pluginsConfig[$pluginName];
			$className = (isset($pluginConfig['className']))
			           ? $pluginConfig['className']
			           : 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';
			$this->pluginParsers[$pluginName] = array(new $className($this, $pluginConfig), 'parse');
		}
		return $this->pluginParsers[$pluginName];
	}
	public function registerParser($pluginName, $parser, $regexp = \null, $limit = \PHP_INT_MAX)
	{
		if (!\is_callable($parser))
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');
		if (!isset($this->pluginsConfig[$pluginName]))
			$this->pluginsConfig[$pluginName] = array();
		if (isset($regexp))
		{
			$this->pluginsConfig[$pluginName]['regexp']      = $regexp;
			$this->pluginsConfig[$pluginName]['regexpLimit'] = $limit;
		}
		$this->pluginParsers[$pluginName] = $parser;
	}
	protected function closeAncestor(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];
			if (!empty($tagConfig['rules']['closeAncestor']))
			{
				$i = \count($this->openTags);
				while (--$i >= 0)
				{
					$ancestor     = $this->openTags[$i];
					$ancestorName = $ancestor->getName();
					if (isset($tagConfig['rules']['closeAncestor'][$ancestorName]))
					{
						$this->tagStack[] = $tag;
						$this->addMagicEndTag($ancestor, $tag->getPos());
						return \true;
					}
				}
			}
		}
		return \false;
	}
	protected function closeParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];
			if (!empty($tagConfig['rules']['closeParent']))
			{
				$parent     = \end($this->openTags);
				$parentName = $parent->getName();
				if (isset($tagConfig['rules']['closeParent'][$parentName]))
				{
					$this->tagStack[] = $tag;
					$this->addMagicEndTag($parent, $tag->getPos());
					return \true;
				}
			}
		}
		return \false;
	}
	protected function createChild(Tag $tag)
	{
		$tagConfig = $this->tagsConfig[$tag->getName()];
		if (isset($tagConfig['rules']['createChild']))
		{
			$priority = -1000;
			$tagPos   = $this->pos + \strspn($this->text, " \n\r\t", $this->pos);
			foreach ($tagConfig['rules']['createChild'] as $tagName)
				$this->addStartTag($tagName, $tagPos, 0, ++$priority);
		}
	}
	protected function fosterParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];
			if (!empty($tagConfig['rules']['fosterParent']))
			{
				$parent     = \end($this->openTags);
				$parentName = $parent->getName();
				if (isset($tagConfig['rules']['fosterParent'][$parentName]))
				{
					if ($parentName !== $tagName && $this->currentFixingCost < $this->maxFixingCost)
					{
						$child = $this->addCopyTag($parent, $tag->getPos() + $tag->getLen(), 0, $tag->getSortPriority() + 1);
						$tag->cascadeInvalidationTo($child);
					}
					$this->tagStack[] = $tag;
					$this->addMagicEndTag($parent, $tag->getPos(), $tag->getSortPriority() - 1);
					$this->currentFixingCost += 4;
					return \true;
				}
			}
		}
		return \false;
	}
	protected function requireAncestor(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];
		if (isset($tagConfig['rules']['requireAncestor']))
		{
			foreach ($tagConfig['rules']['requireAncestor'] as $ancestorName)
				if (!empty($this->cntOpen[$ancestorName]))
					return \false;
			$this->logger->err('Tag requires an ancestor', array(
				'requireAncestor' => \implode(',', $tagConfig['rules']['requireAncestor']),
				'tag'             => $tag
			));
			return \true;
		}
		return \false;
	}
	protected function addMagicEndTag(Tag $startTag, $tagPos, $prio = 0)
	{
		$tagName = $startTag->getName();
		if ($startTag->getFlags() & self::RULE_IGNORE_WHITESPACE)
			$tagPos = $this->getMagicPos($tagPos);
		$endTag = $this->addEndTag($tagName, $tagPos, 0, $prio);
		$endTag->pairWith($startTag);
		return $endTag;
	}
	protected function getMagicPos($tagPos)
	{
		while ($tagPos > $this->pos && \strpos(self::WHITESPACE, $this->text[$tagPos - 1]) !== \false)
			--$tagPos;
		return $tagPos;
	}
	protected function isFollowedByClosingTag(Tag $tag)
	{
		return (empty($this->tagStack)) ? \false : \end($this->tagStack)->canClose($tag);
	}
	protected function processTags()
	{
		if (empty($this->tagStack))
			return;
		foreach (\array_keys($this->tagsConfig) as $tagName)
		{
			$this->cntOpen[$tagName]  = 0;
			$this->cntTotal[$tagName] = 0;
		}
		do
		{
			while (!empty($this->tagStack))
			{
				if (!$this->tagStackIsSorted)
					$this->sortTags();
				$this->currentTag = \array_pop($this->tagStack);
				$this->processCurrentTag();
			}
			foreach ($this->openTags as $startTag)
				$this->addMagicEndTag($startTag, $this->textLen);
		}
		while (!empty($this->tagStack));
	}
	protected function processCurrentTag()
	{
		if (($this->context['flags'] & self::RULE_IGNORE_TAGS)
		 && !$this->currentTag->canClose(\end($this->openTags))
		 && !$this->currentTag->isSystemTag())
			$this->currentTag->invalidate();
		$tagPos = $this->currentTag->getPos();
		$tagLen = $this->currentTag->getLen();
		if ($this->pos > $tagPos && !$this->currentTag->isInvalid())
		{
			$startTag = $this->currentTag->getStartTag();
			if ($startTag && \in_array($startTag, $this->openTags, \true))
			{
				$this->addEndTag(
					$startTag->getName(),
					$this->pos,
					\max(0, $tagPos + $tagLen - $this->pos)
				)->pairWith($startTag);
				return;
			}
			if ($this->currentTag->isIgnoreTag())
			{
				$ignoreLen = $tagPos + $tagLen - $this->pos;
				if ($ignoreLen > 0)
				{
					$this->addIgnoreTag($this->pos, $ignoreLen);
					return;
				}
			}
			$this->currentTag->invalidate();
		}
		if ($this->currentTag->isInvalid())
			return;
		if ($this->currentTag->isIgnoreTag())
			$this->outputIgnoreTag($this->currentTag);
		elseif ($this->currentTag->isBrTag())
		{
			if (!($this->context['flags'] & self::RULE_PREVENT_BR))
				$this->outputBrTag($this->currentTag);
		}
		elseif ($this->currentTag->isParagraphBreak())
			$this->outputText($this->currentTag->getPos(), 0, \true);
		elseif ($this->currentTag->isVerbatim())
			$this->outputVerbatim($this->currentTag);
		elseif ($this->currentTag->isStartTag())
			$this->processStartTag($this->currentTag);
		else
			$this->processEndTag($this->currentTag);
	}
	protected function processStartTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];
		if ($this->cntTotal[$tagName] >= $tagConfig['tagLimit'])
		{
			$this->logger->err(
				'Tag limit exceeded',
				array(
					'tag'      => $tag,
					'tagName'  => $tagName,
					'tagLimit' => $tagConfig['tagLimit']
				)
			);
			$tag->invalidate();
			return;
		}
		if (!$this->filterTag($tag))
		{
			$tag->invalidate();
			return;
		}
		if ($this->fosterParent($tag) || $this->closeParent($tag) || $this->closeAncestor($tag))
			return;
		if ($this->cntOpen[$tagName] >= $tagConfig['nestingLimit'])
		{
			$this->logger->err(
				'Nesting limit exceeded',
				array(
					'tag'          => $tag,
					'tagName'      => $tagName,
					'nestingLimit' => $tagConfig['nestingLimit']
				)
			);
			$tag->invalidate();
			return;
		}
		if (!$this->tagIsAllowed($tagName))
		{
			$msg     = 'Tag is not allowed in this context';
			$context = array('tag' => $tag, 'tagName' => $tagName);
			if ($tag->getLen() > 0)
				$this->logger->warn($msg, $context);
			else
				$this->logger->debug($msg, $context);
			$tag->invalidate();
			return;
		}
		if ($this->requireAncestor($tag))
		{
			$tag->invalidate();
			return;
		}
		if ($tag->getFlags() & self::RULE_AUTO_CLOSE
		 && !$tag->getEndTag()
		 && !$this->isFollowedByClosingTag($tag))
		{
			$newTag = new Tag(Tag::SELF_CLOSING_TAG, $tagName, $tag->getPos(), $tag->getLen());
			$newTag->setAttributes($tag->getAttributes());
			$newTag->setFlags($tag->getFlags());
			$tag = $newTag;
		}
		if ($tag->getFlags() & self::RULE_TRIM_FIRST_LINE
		 && !$tag->getEndTag()
		 && \substr($this->text, $tag->getPos() + $tag->getLen(), 1) === "\n")
			$this->addIgnoreTag($tag->getPos() + $tag->getLen(), 1);
		$this->outputTag($tag);
		$this->pushContext($tag);
		$this->createChild($tag);
	}
	protected function processEndTag(Tag $tag)
	{
		$tagName = $tag->getName();
		if (empty($this->cntOpen[$tagName]))
			return;
		$closeTags = array();
		$i = \count($this->openTags);
		while (--$i >= 0)
		{
			$openTag = $this->openTags[$i];
			if ($tag->canClose($openTag))
				break;
			$closeTags[] = $openTag;
			++$this->currentFixingCost;
		}
		if ($i < 0)
		{
			$this->logger->debug('Skipping end tag with no start tag', array('tag' => $tag));
			return;
		}
		$keepReopening = (bool) ($this->currentFixingCost < $this->maxFixingCost);
		$reopenTags = array();
		foreach ($closeTags as $openTag)
		{
			$openTagName = $openTag->getName();
			if ($keepReopening)
				if ($openTag->getFlags() & self::RULE_AUTO_REOPEN)
					$reopenTags[] = $openTag;
				else
					$keepReopening = \false;
			$tagPos = $tag->getPos();
			if ($openTag->getFlags() & self::RULE_IGNORE_WHITESPACE)
				$tagPos = $this->getMagicPos($tagPos);
			$endTag = new Tag(Tag::END_TAG, $openTagName, $tagPos, 0);
			$endTag->setFlags($openTag->getFlags());
			$this->outputTag($endTag);
			$this->popContext();
		}
		$this->outputTag($tag);
		$this->popContext();
		if (!empty($closeTags) && $this->currentFixingCost < $this->maxFixingCost)
		{
			$ignorePos = $this->pos;
			$i = \count($this->tagStack);
			while (--$i >= 0 && ++$this->currentFixingCost < $this->maxFixingCost)
			{
				$upcomingTag = $this->tagStack[$i];
				if ($upcomingTag->getPos() > $ignorePos
				 || $upcomingTag->isStartTag())
					break;
				$j = \count($closeTags);
				while (--$j >= 0 && ++$this->currentFixingCost < $this->maxFixingCost)
					if ($upcomingTag->canClose($closeTags[$j]))
					{
						\array_splice($closeTags, $j, 1);
						if (isset($reopenTags[$j]))
							\array_splice($reopenTags, $j, 1);
						$ignorePos = \max(
							$ignorePos,
							$upcomingTag->getPos() + $upcomingTag->getLen()
						);
						break;
					}
			}
			if ($ignorePos > $this->pos)
				$this->outputIgnoreTag(new Tag(Tag::SELF_CLOSING_TAG, 'i', $this->pos, $ignorePos - $this->pos));
		}
		foreach ($reopenTags as $startTag)
		{
			$newTag = $this->addCopyTag($startTag, $this->pos, 0);
			$endTag = $startTag->getEndTag();
			if ($endTag)
				$newTag->pairWith($endTag);
		}
	}
	protected function popContext()
	{
		$tag = \array_pop($this->openTags);
		--$this->cntOpen[$tag->getName()];
		$this->context = $this->context['parentContext'];
	}
	protected function pushContext(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagFlags  = $tag->getFlags();
		$tagConfig = $this->tagsConfig[$tagName];
		++$this->cntTotal[$tagName];
		if ($tag->isSelfClosingTag())
			return;
		$allowed = array();
		if ($tagFlags & self::RULE_IS_TRANSPARENT)
			foreach ($this->context['allowed'] as $k => $v)
				$allowed[] = $tagConfig['allowed'][$k] & $v;
		else
			foreach ($this->context['allowed'] as $k => $v)
				$allowed[] = $tagConfig['allowed'][$k] & (($v & 0xFF00) | ($v >> 8));
		$flags = $tagFlags | ($this->context['flags'] & self::RULES_INHERITANCE);
		if ($flags & self::RULE_DISABLE_AUTO_BR)
			$flags &= ~self::RULE_ENABLE_AUTO_BR;
		++$this->cntOpen[$tagName];
		$this->openTags[] = $tag;
		$this->context = array(
			'allowed'       => $allowed,
			'flags'         => $flags,
			'inParagraph'   => \false,
			'parentContext' => $this->context
		);
	}
	protected function tagIsAllowed($tagName)
	{
		$n = $this->tagsConfig[$tagName]['bitNumber'];
		return (bool) ($this->context['allowed'][$n >> 3] & (1 << ($n & 7)));
	}
	public function addStartTag($name, $pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::START_TAG, $name, $pos, $len, $prio);
	}
	public function addEndTag($name, $pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::END_TAG, $name, $pos, $len, $prio);
	}
	public function addSelfClosingTag($name, $pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, $name, $pos, $len, $prio);
	}
	public function addBrTag($pos, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'br', $pos, 0, $prio);
	}
	public function addIgnoreTag($pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'i', $pos, \min($len, $this->textLen - $pos), $prio);
	}
	public function addParagraphBreak($pos, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'pb', $pos, 0, $prio);
	}
	public function addCopyTag(Tag $tag, $pos, $len, $prio = \null)
	{
		if (!isset($prio))
			$prio = $tag->getSortPriority();
		$copy = $this->addTag($tag->getType(), $tag->getName(), $pos, $len, $prio);
		$copy->setAttributes($tag->getAttributes());
		return $copy;
	}
	protected function addTag($type, $name, $pos, $len, $prio)
	{
		$tag = new Tag($type, $name, $pos, $len, $prio);
		if (isset($this->tagsConfig[$name]))
			$tag->setFlags($this->tagsConfig[$name]['rules']['flags']);
		if (!isset($this->tagsConfig[$name]) && !$tag->isSystemTag())
			$tag->invalidate();
		elseif (!empty($this->tagsConfig[$name]['isDisabled']))
		{
			$this->logger->warn(
				'Tag is disabled',
				array(
					'tag'     => $tag,
					'tagName' => $name
				)
			);
			$tag->invalidate();
		}
		elseif ($len < 0 || $pos < 0 || $pos + $len > $this->textLen)
			$tag->invalidate();
		else
			$this->insertTag($tag);
		return $tag;
	}
	protected function insertTag(Tag $tag)
	{
		if (!$this->tagStackIsSorted)
			$this->tagStack[] = $tag;
		else
		{
			$i = \count($this->tagStack);
			while ($i > 0 && self::compareTags($this->tagStack[$i - 1], $tag) > 0)
			{
				$this->tagStack[$i] = $this->tagStack[$i - 1];
				--$i;
			}
			$this->tagStack[$i] = $tag;
		}
	}
	public function addTagPair($name, $startPos, $startLen, $endPos, $endLen, $prio = 0)
	{
		$endTag   = $this->addEndTag($name, $endPos, $endLen, -$prio);
		$startTag = $this->addStartTag($name, $startPos, $startLen, $prio);
		$startTag->pairWith($endTag);
		return $startTag;
	}
	public function addVerbatim($pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'v', $pos, $len, $prio);
	}
	protected function sortTags()
	{
		\usort($this->tagStack, __CLASS__ . '::compareTags');
		$this->tagStackIsSorted = \true;
	}
	protected static function compareTags(Tag $a, Tag $b)
	{
		$aPos = $a->getPos();
		$bPos = $b->getPos();
		if ($aPos !== $bPos)
			return $bPos - $aPos;
		if ($a->getSortPriority() !== $b->getSortPriority())
			return $b->getSortPriority() - $a->getSortPriority();
		$aLen = $a->getLen();
		$bLen = $b->getLen();
		if (!$aLen || !$bLen)
		{
			if (!$aLen && !$bLen)
			{
				$order = array(
					Tag::END_TAG          => 0,
					Tag::SELF_CLOSING_TAG => 1,
					Tag::START_TAG        => 2
				);
				return $order[$b->getType()] - $order[$a->getType()];
			}
			return ($aLen) ? -1 : 1;
		}
		return $aLen - $bLen;
	}
}