<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'seq';
	protected $aliases = array();
	protected $forceImageSize = \true;
	protected $imageSet = 'emojione';
	protected $imageSize = 16;
	protected $imageType = 'png';
	protected $tagName = 'EMOJI';
	protected $twemojiAliases = array(
		'00a9'                    => 'a9',
		'00ae'                    => 'ae',
		'0023-20e3'               => '23-20e3',
		'002a-20e3'               => '2a-20e3',
		'0030-20e3'               => '30-20e3',
		'0031-20e3'               => '31-20e3',
		'0032-20e3'               => '32-20e3',
		'0033-20e3'               => '33-20e3',
		'0034-20e3'               => '34-20e3',
		'0035-20e3'               => '35-20e3',
		'0036-20e3'               => '36-20e3',
		'0037-20e3'               => '37-20e3',
		'0038-20e3'               => '38-20e3',
		'0039-20e3'               => '39-20e3',
		'1f3f3-1f308'             => '1f3f3-fe0f-200d-1f308',
		'1f3f4-2620'              => '1f3f4-200d-2620-fe0f',
		'1f441-1f5e8'             => '1f441-200d-1f5e8',
		'1f468-1f468-1f466-1f466' => '1f468-200d-1f468-200d-1f466-200d-1f466',
		'1f468-1f468-1f466'       => '1f468-200d-1f468-200d-1f466',
		'1f468-1f468-1f467-1f466' => '1f468-200d-1f468-200d-1f467-200d-1f466',
		'1f468-1f468-1f467-1f467' => '1f468-200d-1f468-200d-1f467-200d-1f467',
		'1f468-1f468-1f467'       => '1f468-200d-1f468-200d-1f467',
		'1f468-1f469-1f466-1f466' => '1f468-200d-1f469-200d-1f466-200d-1f466',
		'1f468-1f469-1f466'       => '1f468-200d-1f469-200d-1f466',
		'1f468-1f469-1f467-1f466' => '1f468-200d-1f469-200d-1f467-200d-1f466',
		'1f468-1f469-1f467-1f467' => '1f468-200d-1f469-200d-1f467-200d-1f467',
		'1f468-1f469-1f467'       => '1f468-200d-1f469-200d-1f467',
		'1f468-2764-1f468'        => '1f468-200d-2764-fe0f-200d-1f468',
		'1f468-2764-1f48b-1f468'  => '1f468-200d-2764-fe0f-200d-1f48b-200d-1f468',
		'1f469-1f469-1f466-1f466' => '1f469-200d-1f469-200d-1f466-200d-1f466',
		'1f469-1f469-1f466'       => '1f469-200d-1f469-200d-1f466',
		'1f469-1f469-1f467-1f466' => '1f469-200d-1f469-200d-1f467-200d-1f466',
		'1f469-1f469-1f467-1f467' => '1f469-200d-1f469-200d-1f467-200d-1f467',
		'1f469-1f469-1f467'       => '1f469-200d-1f469-200d-1f467',
		'1f469-2764-1f468'        => '1f469-200d-2764-fe0f-200d-1f468',
		'1f469-2764-1f469'        => '1f469-200d-2764-fe0f-200d-1f469',
		'1f469-2764-1f48b-1f468'  => '1f469-200d-2764-fe0f-200d-1f48b-200d-1f468',
		'1f469-2764-1f48b-1f469'  => '1f469-200d-2764-fe0f-200d-1f48b-200d-1f469'
	);
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$this->resetTemplate();
	}
	public function addAlias($alias, $emoji)
	{
		$this->aliases[$alias] = $emoji;
	}
	public function forceImageSize()
	{
		$this->forceImageSize = \true;
		$this->resetTemplate();
	}
	public function removeAlias($alias)
	{
		unset($this->aliases[$alias]);
	}
	public function omitImageSize()
	{
		$this->forceImageSize = \false;
		$this->resetTemplate();
	}
	public function getAliases()
	{
		return $this->aliases;
	}
	public function setImageSize($size)
	{
		$this->imageSize = (int) $size;
		$this->resetTemplate();
	}
	public function useEmojiOne()
	{
		$this->imageSet = 'emojione';
		$this->resetTemplate();
	}
	public function usePNG()
	{
		$this->imageType = 'png';
		$this->resetTemplate();
	}
	public function useSVG()
	{
		$this->imageType = 'svg';
		$this->resetTemplate();
	}
	public function useTwemoji()
	{
		$this->imageSet = 'twemoji';
		$this->resetTemplate();
	}
	public function asConfig()
	{
		$config = array(
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		);
		if (!empty($this->aliases))
		{
			$aliases = \array_keys($this->aliases);
			$regexp  = '/' . RegexpBuilder::fromList($aliases) . '/';
			$config['aliases']       = $this->aliases;
			$config['aliasesRegexp'] = new Regexp($regexp, \true);
			$quickMatch = ConfigHelper::generateQuickMatchFromList($aliases);
			if ($quickMatch !== \false)
				$config['aliasesQuickMatch'] = $quickMatch;
		}
		return $config;
	}
	public function getJSHints()
	{
		$quickMatch = ConfigHelper::generateQuickMatchFromList(\array_keys($this->aliases));
		return array(
			'EMOJI_HAS_ALIASES'          => !empty($this->aliases),
			'EMOJI_HAS_ALIAS_QUICKMATCH' => ($quickMatch !== \false)
		);
	}
	protected function getEmojiOneSrc()
	{
		$src  = '//cdn.jsdelivr.net/emojione/assets/' . $this->imageType . '/';
		$src .= '<xsl:value-of select="@seq"/>';
		$src .= '.' . $this->imageType;
		return $src;
	}
	protected function getTemplate()
	{
		$template = '<img alt="{.}" class="emoji" draggable="false"';
		if ($this->forceImageSize)
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		$template .= '><xsl:attribute name="src">';
		$template .= ($this->imageSet === 'emojione') ? $this->getEmojiOneSrc() :  $this->getTwemojiSrc();
		$template .= '</xsl:attribute></img>';
		return $template;
	}
	protected function getTwemojiSrc()
	{
		$src  = '//twemoji.maxcdn.com/2/';
		$src .= ($this->imageType === 'svg') ? 'svg' : '72x72';
		$src .= '/<xsl:choose>';
		foreach ($this->twemojiAliases as $seq => $filename)
			$src .= '<xsl:when test="@seq=\'' . $seq . '\'">' . $filename . '</xsl:when>';
		$src .= '<xsl:otherwise><xsl:value-of select="@seq"/></xsl:otherwise></xsl:choose>';
		$src .= '.' . $this->imageType;
		return $src;
	}
	protected function resetTemplate()
	{
		$this->getTag()->template = $this->getTemplate();
	}
}