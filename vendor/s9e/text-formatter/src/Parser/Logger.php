<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;
use InvalidArgumentException;
use s9e\TextFormatter\Parser;
class Logger
{
	protected $attrName;
	protected $logs = array();
	protected $tag;
	protected function add($type, $msg, array $context)
	{
		if (!isset($context['attrName']) && isset($this->attrName))
			$context['attrName'] = $this->attrName;
		if (!isset($context['tag']) && isset($this->tag))
			$context['tag'] = $this->tag;
		$this->logs[] = array($type, $msg, $context);
	}
	public function clear()
	{
		$this->logs = array();
		$this->unsetAttribute();
		$this->unsetTag();
	}
	public function get()
	{
		return $this->logs;
	}
	public function setAttribute($attrName)
	{
		$this->attrName = $attrName;
	}
	public function setTag(Tag $tag)
	{
		$this->tag = $tag;
	}
	public function unsetAttribute()
	{
		unset($this->attrName);
	}
	public function unsetTag()
	{
		unset($this->tag);
	}
	public function debug($msg, array $context = array())
	{
		$this->add('debug', $msg, $context);
	}
	public function err($msg, array $context = array())
	{
		$this->add('err', $msg, $context);
	}
	public function info($msg, array $context = array())
	{
		$this->add('info', $msg, $context);
	}
	public function warn($msg, array $context = array())
	{
		$this->add('warn', $msg, $context);
	}
}