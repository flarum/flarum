<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
use DOMDocument;
use InvalidArgumentException;
abstract class Renderer
{
	public $metaElementsRegexp = '(<[eis]>[^<]*</[eis]>)';
	protected $params = array();
	protected function loadXML($xml)
	{
		$this->checkUnsupported($xml);
		$flags = (\LIBXML_VERSION >= 20700) ? \LIBXML_COMPACT | \LIBXML_PARSEHUGE : 0;
		$dom = new DOMDocument;
		$dom->loadXML($xml, $flags);
		return $dom;
	}
	public function render($xml)
	{
		if (\substr($xml, 0, 3) === '<t>')
			return $this->renderPlainText($xml);
		else
			return $this->renderRichText(\preg_replace($this->metaElementsRegexp, '', $xml));
	}
	protected function renderPlainText($xml)
	{
		$html = \substr($xml, 3, -4);
		$html = \str_replace('<br/>', '<br>', $html);
		$html = $this->decodeSMP($html);
		return $html;
	}
	abstract protected function renderRichText($xml);
	public function getParameter($paramName)
	{
		return (isset($this->params[$paramName])) ? $this->params[$paramName] : '';
	}
	public function getParameters()
	{
		return $this->params;
	}
	public function setParameter($paramName, $paramValue)
	{
		$this->params[$paramName] = (string) $paramValue;
	}
	public function setParameters(array $params)
	{
		foreach ($params as $paramName => $paramValue)
			$this->setParameter($paramName, $paramValue);
	}
	protected function checkUnsupported($xml)
	{
		if (\strpos($xml, '<!') !== \false)
			throw new InvalidArgumentException('DTDs, CDATA nodes and comments are not allowed');
		if (\strpos($xml, '<?') !== \false)
			throw new InvalidArgumentException('Processing instructions are not allowed');
	}
	protected function decodeSMP($str)
	{
		if (\strpos($str, '&#') === \false)
			return $str;
		return \preg_replace_callback('(&#(?:x[0-9A-Fa-f]+|[0-9]+);)', __CLASS__ . '::decodeEntity', $str);
	}
	protected static function decodeEntity(array $m)
	{
		return \htmlspecialchars(\html_entity_decode($m[0], \ENT_QUOTES, 'UTF-8'), \ENT_COMPAT);
	}
}