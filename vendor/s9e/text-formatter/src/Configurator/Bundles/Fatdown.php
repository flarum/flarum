<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;
class Fatdown extends Bundle
{
	public function configure(Configurator $configurator)
	{
		$configurator->urlConfig->allowScheme('ftp');
		$configurator->Litedown->decodeHtmlEntities = \true;
		$configurator->Autoemail;
		$configurator->Autolink;
		$configurator->Escaper;
		$configurator->FancyPants;
		$configurator->HTMLComments;
		$configurator->HTMLEntities;
		$configurator->PipeTables;
		$htmlAliases = array(
			'a'      => array('URL', 'href' => 'url'),
			'hr'     => 'HR',
			'em'     => 'EM',
			's'      => 'S',
			'strong' => 'STRONG',
			'sup'    => 'SUP'
		);
		foreach ($htmlAliases as $elName => $alias)
			if (\is_array($alias))
			{
				$configurator->HTMLElements->aliasElement($elName, $alias[0]);
				unset($alias[0]);
				foreach ($alias as $attrName => $alias)
					$configurator->HTMLElements->aliasAttribute($elName, $attrName, $alias);
			}
			else
				$configurator->HTMLElements->aliasElement($elName, $alias);
		$htmlElements = array(
			'abbr' => array('title'),
			'b',
			'br',
			'code',
			'dd',
			'del',
			'div' => array('class'),
			'dl',
			'dt',
			'i',
			'img' => array('alt', 'height', 'src', 'title', 'width'),
			'ins',
			'li',
			'ol',
			'pre',
			'rb',
			'rp',
			'rt',
			'rtc',
			'ruby',
			'span' => array('class'),
			'strong',
			'sub',
			'sup',
			'table',
			'tbody',
			'td' => array('colspan', 'rowspan'),
			'tfoot',
			'th' => array('colspan', 'rowspan', 'scope'),
			'thead',
			'tr',
			'u',
			'ul'
		);
		foreach ($htmlElements as $k => $v)
		{
			if (\is_numeric($k))
			{
				$elName    = $v;
				$attrNames = array();
			}
			else
			{
				$elName    = $k;
				$attrNames = $v;
			}
			$configurator->HTMLElements->allowElement($elName);
			foreach ($attrNames as $attrName)
				$configurator->HTMLElements->allowAttribute($elName, $attrName);
		}
		$configurator->tags['html:dd']->rules->createParagraphs(\false);
		$configurator->tags['html:dt']->rules->createParagraphs(\false);
		$configurator->tags['html:td']->rules->createParagraphs(\false);
		$configurator->tags['html:th']->rules->createParagraphs(\false);
		$configurator->plugins->load('MediaEmbed', array('createMediaBBCode' => \false));
		$sites = array(
			'bandcamp',
			'dailymotion',
			'facebook',
			'liveleak',
			'soundcloud',
			'spotify',
			'twitch',
			'vimeo',
			'vine',
			'youtube'
		);
		foreach ($sites as $site)
			$configurator->MediaEmbed->add($site);
	}
}