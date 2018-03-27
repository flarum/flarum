<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\PipeTables;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	protected $pos;
	protected $table;
	protected $tableTag;
	protected $tables;
	protected $text;
	public function parse($text, array $matches)
	{
		$this->text = $text;
		if ($this->config['overwriteMarkdown'])
			$this->overwriteMarkdown();
		if ($this->config['overwriteEscapes'])
			$this->overwriteEscapes();
		$this->captureTables();
		$this->processTables();
		unset($this->tables);
		unset($this->text);
	}
	protected function addLine($line)
	{
		$ignoreLen = 0;
		if (!isset($this->table))
		{
			$this->table = array();
			\preg_match('/^ */', $line, $m);
			$ignoreLen = \strlen($m[0]);
			$line      = \substr($line, $ignoreLen);
		}
		$line = \preg_replace('/^( *)\\|/', '$1 ', $line);
		$line = \preg_replace('/\\|( *)$/', ' $1', $line);
		$this->table['rows'][] = array('line' => $line, 'pos' => $this->pos + $ignoreLen);
	}
	protected function addTableBody()
	{
		$i   = 1;
		$cnt = \count($this->table['rows']);
		while (++$i < $cnt)
			$this->addTableRow('TD', $this->table['rows'][$i]);
		$this->createBodyTags($this->table['rows'][2]['pos'], $this->pos);
	}
	protected function addTableCell($tagName, $align, $text)
	{
		$startPos  = $this->pos;
		$endPos    = $startPos + \strlen($text);
		$this->pos = $endPos;
		\preg_match('/^( *).*?( *)$/', $text, $m);
		if ($m[1])
		{
			$ignoreLen = \strlen($m[1]);
			$this->createIgnoreTag($startPos, $ignoreLen);
			$startPos += $ignoreLen;
		}
		if ($m[2])
		{
			$ignoreLen = \strlen($m[2]);
			$this->createIgnoreTag($endPos - $ignoreLen, $ignoreLen);
			$endPos -= $ignoreLen;
		}
		$this->createCellTags($tagName, $startPos, $endPos, $align);
	}
	protected function addTableHead()
	{
		$this->addTableRow('TH', $this->table['rows'][0]);
		$this->createHeadTags($this->table['rows'][0]['pos'], $this->pos);
	}
	protected function addTableRow($tagName, $row)
	{
		$this->pos = $row['pos'];
		foreach (\explode('|', $row['line']) as $i => $str)
		{
			if ($i > 0)
			{
				$this->createIgnoreTag($this->pos, 1);
				++$this->pos;
			}
			$align = (empty($this->table['cols'][$i])) ? '' : $this->table['cols'][$i];
			$this->addTableCell($tagName, $align, $str);
		}
		$this->createRowTags($row['pos'], $this->pos);
	}
	protected function captureTables()
	{
		unset($this->table);
		$this->tables = array();
		$this->pos = 0;
		foreach (\explode("\n", $this->text) as $line)
		{
			if (\strpos($line, '|') === \false)
				$this->endTable();
			else
				$this->addLine($line);
			$this->pos += 1 + \strlen($line);
		}
		$this->endTable();
	}
	protected function createBodyTags($startPos, $endPos)
	{
		$this->parser->addTagPair('TBODY', $startPos, 0, $endPos, 0, -103);
	}
	protected function createCellTags($tagName, $startPos, $endPos, $align)
	{
		if ($startPos === $endPos)
			$tag = $this->parser->addSelfClosingTag($tagName, $startPos, 0, -101);
		else
			$tag = $this->parser->addTagPair($tagName, $startPos, 0, $endPos, 0, -101);
		if ($align)
			$tag->setAttribute('align', $align);
	}
	protected function createHeadTags($startPos, $endPos)
	{
		$this->parser->addTagPair('THEAD', $startPos, 0, $endPos, 0, -103);
	}
	protected function createIgnoreTag($pos, $len)
	{
		$this->tableTag->cascadeInvalidationTo($this->parser->addIgnoreTag($pos, $len, 1000));
	}
	protected function createRowTags($startPos, $endPos)
	{
		$this->parser->addTagPair('TR', $startPos, 0, $endPos, 0, -102);
	}
	protected function createSeparatorTag(array $row)
	{
		$this->createIgnoreTag($row['pos'] - 1, 1 + \strlen($row['line']));
	}
	protected function createTableTags($startPos, $endPos)
	{
		$this->tableTag = $this->parser->addTagPair('TABLE', $startPos, 0, $endPos, 0, -104);
	}
	protected function endTable()
	{
		if ($this->hasValidTable())
		{
			$this->table['cols'] = $this->parseColumnAlignments($this->table['rows'][1]['line']);
			$this->tables[]      = $this->table;
		}
		unset($this->table);
	}
	protected function hasValidTable()
	{
		return (isset($this->table) && \count($this->table['rows']) > 2 && $this->isValidSeparator($this->table['rows'][1]['line']));
	}
	protected function isValidSeparator($line)
	{
		return (bool) \preg_match('/^ *:?-+:?(?:(?:\\+| *\\| *):?-+:?)+ *$/', $line);
	}
	protected function overwriteBlockquoteCallback(array $m)
	{
		return \strtr($m[0], '>', ' ');
	}
	protected function overwriteEscapes()
	{
		if (\strpos($this->text, '\\|') !== \false)
			$this->text = \preg_replace('/\\\\[\\\\|]/', '..', $this->text);
	}
	protected function overwriteInlineCodeCallback(array $m)
	{
		return \strtr($m[0], '|', '.');
	}
	protected function overwriteMarkdown()
	{
		if (\strpos($this->text, '`') !== \false)
			$this->text = \preg_replace_callback('/`[^`]*`/', array($this, 'overwriteInlineCodeCallback'), $this->text);
		if (\strpos($this->text, '>') !== \false)
			$this->text = \preg_replace_callback('/^(?:> ?)+/m', array($this, 'overwriteBlockquoteCallback'), $this->text);
	}
	protected function parseColumnAlignments($line)
	{
		$align = array(
			0 => '',
			1 => 'right',
			2 => 'left',
			3 => 'center'
		);
		$cols = array();
		\preg_match_all('/(:?)-+(:?)/', $line, $matches, \PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			$key = (!empty($m[1]) ? 2 : 0) + (!empty($m[2]) ? 1 : 0);
			$cols[] = $align[$key];
		}
		return $cols;
	}
	protected function processCurrentTable()
	{
		$firstRow = $this->table['rows'][0];
		$lastRow  = \end($this->table['rows']);
		$this->createTableTags($firstRow['pos'], $lastRow['pos'] + \strlen($lastRow['line']));
		$this->addTableHead();
		$this->createSeparatorTag($this->table['rows'][1]);
		$this->addTableBody();
	}
	protected function processTables()
	{
		foreach ($this->tables as $table)
		{
			$this->table = $table;
			$this->processCurrentTable();
		}
	}
}