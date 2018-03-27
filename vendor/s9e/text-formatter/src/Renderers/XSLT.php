<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;
use XSLTProcessor;
use s9e\TextFormatter\Renderer;
class XSLT extends Renderer
{
	protected $proc;
	protected $reloadParams = \false;
	protected $stylesheet;
	public function __construct($stylesheet)
	{
		$this->stylesheet = $stylesheet;
		\preg_match_all('#<xsl:param name="([^"]+)"(?>/>|>([^<]+))#', $stylesheet, $matches);
		foreach ($matches[1] as $k => $paramName)
			$this->params[$paramName] = (isset($matches[2][$k]))
			                          ? \htmlspecialchars_decode($matches[2][$k])
			                          : '';
	}
	public function __sleep()
	{
		$props = \get_object_vars($this);
		unset($props['proc']);
		if (empty($props['reloadParams']))
			unset($props['reloadParams']);
		return \array_keys($props);
	}
	public function __wakeup()
	{
		if (!empty($this->reloadParams))
		{
			$this->setParameters($this->params);
			$this->reloadParams = \false;
		}
	}
	public function setParameter($paramName, $paramValue)
	{
		if (\strpos($paramValue, '"') !== \false && \strpos($paramValue, "'") !== \false)
			$paramValue = \str_replace('"', "\xEF\xBC\x82", $paramValue);
		else
			$paramValue = (string) $paramValue;
		if (!isset($this->params[$paramName]) || $this->params[$paramName] !== $paramValue)
		{
			$this->load();
			$this->proc->setParameter('', $paramName, $paramValue);
			$this->params[$paramName] = $paramValue;
			$this->reloadParams = \true;
		}
	}
	protected function renderRichText($xml)
	{
		$dom = $this->loadXML($xml);
		$this->load();
		$output = (string) $this->proc->transformToXml($dom);
		$output = \str_replace('</embed>', '', $output);
		if (\substr($output, -1) === "\n")
			$output = \substr($output, 0, -1);
		if (\strpos($output, "='") !== \false)
			$output = $this->normalizeAttributes($output);
		return $output;
	}
	protected function load()
	{
		if (!isset($this->proc))
		{
			$xsl = $this->loadXML($this->stylesheet);
			$this->proc = new XSLTProcessor;
			$this->proc->importStylesheet($xsl);
		}
	}
	protected function normalizeAttribute(array $m)
	{
		if ($m[0][0] === '"')
			return $m[0];
		return '"' . \str_replace('"', '&quot;', \substr($m[0], 1, -1)) . '"';
	}
	protected function normalizeAttributes($html)
	{
		return \preg_replace_callback('(<\\S++ [^>]++>)', array($this, 'normalizeElement'), $html);
	}
	protected function normalizeElement(array $m)
	{
		if (\strpos($m[0], "='") === \false)
			return $m[0];
		return \preg_replace_callback('((?:"[^"]*"|\'[^\']*\'))S', array($this, 'normalizeAttribute'), $m[0]);
	}
}