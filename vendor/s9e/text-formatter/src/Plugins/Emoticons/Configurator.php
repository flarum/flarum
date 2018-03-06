<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons;
use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\Emoticons\Configurator\EmoticonCollection;
class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->collection, $methodName), $args);
	}
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}
	public function count()
	{
		return \count($this->collection);
	}
	public function current()
	{
		return $this->collection->current();
	}
	public function key()
	{
		return $this->collection->key();
	}
	public function next()
	{
		return $this->collection->next();
	}
	public function rewind()
	{
		$this->collection->rewind();
	}
	public function valid()
	{
		return $this->collection->valid();
	}
	protected $collection;
	public $notAfter = '';
	public $notBefore = '';
	public $notIfCondition;
	protected $onDuplicateAction = 'replace';
	protected $tagName = 'E';
	protected function setUp()
	{
		$this->collection = new EmoticonCollection;
		if (!$this->configurator->tags->exists($this->tagName))
			$this->configurator->tags->add($this->tagName);
	}
	public function finalize()
	{
		$tag = $this->getTag();
		if (!isset($tag->template))
			$tag->template = $this->getTemplate();
	}
	public function asConfig()
	{
		if (!\count($this->collection))
			return;
		$codes = \array_keys(\iterator_to_array($this->collection));
		$regexp = '/';
		if ($this->notAfter !== '')
			$regexp .= '(?<!' . $this->notAfter . ')';
		$regexp .= RegexpBuilder::fromList($codes);
		if ($this->notBefore !== '')
			$regexp .= '(?!' . $this->notBefore . ')';
		$regexp .= '/S';
		if (\preg_match('/\\\\[pP](?>\\{\\^?\\w+\\}|\\w\\w?)/', $regexp))
			$regexp .= 'u';
		$regexp = \preg_replace('/(?<!\\\\)((?>\\\\\\\\)*)\\(\\?:/', '$1(?>', $regexp);
		$config = array(
			'quickMatch' => $this->quickMatch,
			'regexp'     => $regexp,
			'tagName'    => $this->tagName
		);
		if ($this->notAfter !== '')
		{
			$lpos = 6 + \strlen($this->notAfter);
			$rpos = \strrpos($regexp, '/');
			$jsRegexp = RegexpConvertor::toJS('/' . \substr($regexp, $lpos, $rpos - $lpos) . '/', \true);
			$config['regexp'] = new Regexp($regexp);
			$config['regexp']->setJS($jsRegexp);
			$config['notAfter'] = new Regexp('/' . $this->notAfter . '/');
		}
		if ($this->quickMatch === \false)
			$config['quickMatch'] = ConfigHelper::generateQuickMatchFromList($codes);
		return $config;
	}
	public function getJSHints()
	{
		return array('EMOTICONS_NOT_AFTER' => (int) !empty($this->notAfter));
	}
	public function getTemplate()
	{
		$xsl = '<xsl:choose>';
		if (!empty($this->notIfCondition))
			$xsl .= '<xsl:when test="' . \htmlspecialchars($this->notIfCondition) . '"><xsl:value-of select="."/></xsl:when><xsl:otherwise><xsl:choose>';
		foreach ($this->collection as $code => $template)
			$xsl .= '<xsl:when test=".=' . \htmlspecialchars(XPathHelper::export($code)) . '">'
			      . $template
			      . '</xsl:when>';
		$xsl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>';
		$xsl .= '</xsl:choose>';
		if (!empty($this->notIfCondition))
			$xsl .= '</xsl:otherwise></xsl:choose>';
		return $xsl;
	}
}