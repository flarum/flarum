<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;
class Forum extends Bundle
{
	public function configure(Configurator $configurator)
	{
		$configurator->rootRules->enableAutoLineBreaks();
		$configurator->BBCodes->addFromRepository('B');
		$configurator->BBCodes->addFromRepository('CENTER');
		$configurator->BBCodes->addFromRepository('CODE');
		$configurator->BBCodes->addFromRepository('COLOR');
		$configurator->BBCodes->addFromRepository('EMAIL');
		$configurator->BBCodes->addFromRepository('FONT');
		$configurator->BBCodes->addFromRepository('I');
		$configurator->BBCodes->addFromRepository('IMG');
		$configurator->BBCodes->addFromRepository('LIST');
		$configurator->BBCodes->addFromRepository('*');
		$configurator->BBCodes->add('LI');
		$configurator->BBCodes->addFromRepository('OL');
		$configurator->BBCodes->addFromRepository('QUOTE', 'default', array(
			'authorStr' => '<xsl:value-of select="@author"/> <xsl:value-of select="$L_WROTE"/>'
		));
		$configurator->BBCodes->addFromRepository('S');
		$configurator->BBCodes->addFromRepository('SIZE');
		$configurator->BBCodes->addFromRepository('SPOILER', 'default', array(
			'hideStr'    => '{L_HIDE}',
			'showStr'    => '{L_SHOW}',
			'spoilerStr' => '{L_SPOILER}',
		));
		$configurator->BBCodes->addFromRepository('TABLE');
		$configurator->BBCodes->addFromRepository('TD');
		$configurator->BBCodes->addFromRepository('TH');
		$configurator->BBCodes->addFromRepository('TR');
		$configurator->BBCodes->addFromRepository('U');
		$configurator->BBCodes->addFromRepository('UL');
		$configurator->BBCodes->addFromRepository('URL');
		$configurator->rendering->parameters = array(
			'L_WROTE'   => 'wrote:',
			'L_HIDE'    => 'Hide',
			'L_SHOW'    => 'Show',
			'L_SPOILER' => 'Spoiler'
		);
		$emoticons = array(
			':)'  => '1F642',
			':-)' => '1F642',
			';)'  => '1F609',
			';-)' => '1F609',
			':D'  => '1F600',
			':-D' => '1F600',
			':('  => '2639',
			':-(' => '2639',
			':-*' => '1F618',
			':P'  => '1F61B',
			':-P' => '1F61B',
			':p'  => '1F61B',
			':-p' => '1F61B',
			';P'  => '1F61C',
			';-P' => '1F61C',
			';p'  => '1F61C',
			';-p' => '1F61C',
			':?'  => '1F615',
			':-?' => '1F615',
			':|'  => '1F610',
			':-|' => '1F610',
			':o'  => '1F62E',
			':lol:' => '1F602'
		);
		foreach ($emoticons as $code => $hex)
			$configurator->Emoji->addAlias($code, \html_entity_decode('&#x' . $hex . ';'));
		$configurator->MediaEmbed->createIndividualBBCodes = \true;
		$configurator->MediaEmbed->add('bandcamp');
		$configurator->MediaEmbed->add('dailymotion');
		$configurator->MediaEmbed->add('facebook');
		$configurator->MediaEmbed->add('indiegogo');
		$configurator->MediaEmbed->add('instagram');
		$configurator->MediaEmbed->add('kickstarter');
		$configurator->MediaEmbed->add('liveleak');
		$configurator->MediaEmbed->add('soundcloud');
		$configurator->MediaEmbed->add('twitch');
		$configurator->MediaEmbed->add('twitter');
		$configurator->MediaEmbed->add('vimeo');
		$configurator->MediaEmbed->add('vine');
		$configurator->MediaEmbed->add('wshh');
		$configurator->MediaEmbed->add('youtube');
		$configurator->Autoemail;
		$configurator->Autolink;
	}
}