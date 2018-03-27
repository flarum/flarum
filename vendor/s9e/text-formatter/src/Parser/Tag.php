<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;
class Tag
{
	const START_TAG = 1;
	const END_TAG = 2;
	const SELF_CLOSING_TAG = 3;
	protected $attributes = array();
	protected $cascade = array();
	protected $endTag = \null;
	protected $flags = 0;
	protected $invalid = \false;
	protected $len;
	protected $name;
	protected $pos;
	protected $sortPriority;
	protected $startTag = \null;
	protected $type;
	public function __construct($type, $name, $pos, $len, $priority = 0)
	{
		$this->type = (int) $type;
		$this->name = $name;
		$this->pos  = (int) $pos;
		$this->len  = (int) $len;
		$this->sortPriority = (int) $priority;
	}
	public function addFlags($flags)
	{
		$this->flags |= $flags;
	}
	public function cascadeInvalidationTo(Tag $tag)
	{
		$this->cascade[] = $tag;
		if ($this->invalid)
			$tag->invalidate();
	}
	public function invalidate()
	{
		if ($this->invalid)
			return;
		$this->invalid = \true;
		foreach ($this->cascade as $tag)
			$tag->invalidate();
	}
	public function pairWith(Tag $tag)
	{
		if ($this->name === $tag->name)
			if ($this->type === self::START_TAG
			 && $tag->type  === self::END_TAG
			 && $tag->pos   >=  $this->pos)
			{
				$this->endTag  = $tag;
				$tag->startTag = $this;
				$this->cascadeInvalidationTo($tag);
			}
			elseif ($this->type === self::END_TAG
			     && $tag->type  === self::START_TAG
			     && $tag->pos   <=  $this->pos)
			{
				$this->startTag = $tag;
				$tag->endTag    = $this;
			}
	}
	public function removeFlags($flags)
	{
		$this->flags &= ~$flags;
	}
	public function setFlags($flags)
	{
		$this->flags = $flags;
	}
	public function setSortPriority($sortPriority)
	{
		$this->sortPriority = $sortPriority;
		\trigger_error('setSortPriority() is deprecated. Set the priority when calling adding the tag instead. See http://s9etextformatter.readthedocs.io/Internals/API_changes/#070', \E_USER_DEPRECATED);
	}
	public function getAttributes()
	{
		return $this->attributes;
	}
	public function getEndTag()
	{
		return $this->endTag;
	}
	public function getFlags()
	{
		return $this->flags;
	}
	public function getLen()
	{
		return $this->len;
	}
	public function getName()
	{
		return $this->name;
	}
	public function getPos()
	{
		return $this->pos;
	}
	public function getSortPriority()
	{
		return $this->sortPriority;
	}
	public function getStartTag()
	{
		return $this->startTag;
	}
	public function getType()
	{
		return $this->type;
	}
	public function canClose(Tag $startTag)
	{
		if ($this->invalid
		 || $this->name !== $startTag->name
		 || $startTag->type !== self::START_TAG
		 || $this->type !== self::END_TAG
		 || $this->pos < $startTag->pos
		 || ($this->startTag && $this->startTag !== $startTag)
		 || ($startTag->endTag && $startTag->endTag !== $this))
			return \false;
		return \true;
	}
	public function isBrTag()
	{
		return ($this->name === 'br');
	}
	public function isEndTag()
	{
		return (bool) ($this->type & self::END_TAG);
	}
	public function isIgnoreTag()
	{
		return ($this->name === 'i');
	}
	public function isInvalid()
	{
		return $this->invalid;
	}
	public function isParagraphBreak()
	{
		return ($this->name === 'pb');
	}
	public function isSelfClosingTag()
	{
		return ($this->type === self::SELF_CLOSING_TAG);
	}
	public function isSystemTag()
	{
		return (\strpos('br i pb v', $this->name) !== \false);
	}
	public function isStartTag()
	{
		return (bool) ($this->type & self::START_TAG);
	}
	public function isVerbatim()
	{
		return ($this->name === 'v');
	}
	public function getAttribute($attrName)
	{
		return $this->attributes[$attrName];
	}
	public function hasAttribute($attrName)
	{
		return isset($this->attributes[$attrName]);
	}
	public function removeAttribute($attrName)
	{
		unset($this->attributes[$attrName]);
	}
	public function setAttribute($attrName, $attrValue)
	{
		$this->attributes[$attrName] = $attrValue;
	}
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}
}