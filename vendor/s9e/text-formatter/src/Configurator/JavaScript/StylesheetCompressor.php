<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\JavaScript\Code;
class StylesheetCompressor
{
	protected $deduplicateTargets = array(
		'<xsl:template match="',
		'</xsl:template>',
		'<xsl:apply-templates/>',
		'<param name="allowfullscreen" value="true"/>',
		'<xsl:value-of select="',
		'<xsl:copy-of select="@',
		'<iframe allowfullscreen="" scrolling="no"',
		'overflow:hidden;position:relative;padding-bottom:',
		'display:inline-block;width:100%;max-width:',
		' [-:\\w]++="',
		'\\{[^}]++\\}',
		'@[-\\w]{4,}+',
		'(?<=<)[-:\\w]{4,}+',
		'(?<==")[^"]{4,}+"'
	);
	protected $dictionary;
	protected $keyPrefix = '$';
	public $minSaving = 10;
	protected $savings;
	protected $xsl;
	public function encode($xsl)
	{
		$this->xsl = $xsl;
		$this->estimateSavings();
		$this->filterSavings();
		$this->buildDictionary();
		$js = \json_encode($this->getCompressedStylesheet());
		if (!empty($this->dictionary))
			$js .= '.replace(' . $this->getReplacementRegexp() . ',function(k){return' . \json_encode($this->dictionary) . '[k]})';
		return $js;
	}
	protected function buildDictionary()
	{
		$keys = $this->getAvailableKeys();
		\rsort($keys);
		$this->dictionary = array();
		\arsort($this->savings);
		foreach (\array_keys($this->savings) as $str)
		{
			$key = \array_pop($keys);
			if (!$key)
				break;
			$this->dictionary[$key] = $str;
		}
	}
	protected function estimateSavings()
	{
		$this->savings = array();
		foreach ($this->getStringsFrequency() as $str => $cnt)
		{
			$len             = \strlen($str);
			$originalCost    = $cnt * $len;
			$replacementCost = $cnt * 2;
			$overhead        = $len + 6;
			$this->savings[$str] = $originalCost - ($replacementCost + $overhead);
		}
	}
	protected function filterSavings()
	{
		$_this = $this;
		$this->savings = \array_filter(
			$this->savings,
			function ($saving) use ($_this)
			{
				return ($saving >= $_this->minSaving);
			}
		);
	}
	protected function getAvailableKeys()
	{
		return \array_diff($this->getPossibleKeys(), $this->getUnavailableKeys());
	}
	protected function getCompressedStylesheet()
	{
		return \strtr($this->xsl, \array_flip($this->dictionary));
	}
	protected function getPossibleKeys()
	{
		$keys = array();
		foreach (\range('a', 'z') as $char)
			$keys[] = $this->keyPrefix . $char;
		return $keys;
	}
	protected function getReplacementRegexp()
	{
		return '/' . RegexpBuilder::fromList(\array_keys($this->dictionary)) . '/g';
	}
	protected function getStringsFrequency()
	{
		$regexp = '(' . \implode('|', $this->deduplicateTargets) . ')S';
		\preg_match_all($regexp, $this->xsl, $matches);
		return \array_count_values($matches[0]);
	}
	protected function getUnavailableKeys()
	{
		\preg_match_all('(' . \preg_quote($this->keyPrefix) . '.)', $this->xsl, $matches);
		return \array_unique($matches[0]);
	}
}