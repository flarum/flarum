<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\PipeTables;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\ChoiceFilter;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $quickMatch = '|';
	protected function setUp()
	{
		$tags = array(
			'TABLE' => array('template' => '<table><xsl:apply-templates/></table>'),
			'TBODY' => array('template' => '<tbody><xsl:apply-templates/></tbody>'),
			'TD'    => $this->generateCellTagConfig('td'),
			'TH'    => $this->generateCellTagConfig('th'),
			'THEAD' => array('template' => '<thead><xsl:apply-templates/></thead>'),
			'TR'    => array('template' => '<tr><xsl:apply-templates/></tr>')
		);
		foreach ($tags as $tagName => $tagConfig)
			if (!isset($this->configurator->tags[$tagName]))
				$this->configurator->tags->add($tagName, $tagConfig);
	}
	protected function generateCellTagConfig($elName)
	{
		$alignFilter = new ChoiceFilter(array('left', 'center', 'right', 'justify'), \true);
		return	array(
			'attributes' => array(
				'align' => array(
					'filterChain' => array('strtolower', $alignFilter),
					'required' => \false
				)
			),
			'rules' => array('createParagraphs' => \false),
			'template' =>
				'<' . $elName . '>
					<xsl:if test="@align">
						<xsl:attribute name="style">text-align:<xsl:value-of select="@align"/></xsl:attribute>
					</xsl:if>
					<xsl:apply-templates/>
				</' . $elName . '>'
		);
	}
	public function asConfig()
	{
		return array(
			'overwriteEscapes'  => isset($this->configurator->Escaper),
			'overwriteMarkdown' => isset($this->configurator->Litedown)
		);
	}
}