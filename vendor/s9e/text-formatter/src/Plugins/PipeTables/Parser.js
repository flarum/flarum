var	pos, table = null, tableTag, tables, text;

if (config.overwriteMarkdown)
{
	overwriteMarkdown();
}
if (config.overwriteEscapes)
{
	overwriteEscapes();
}

captureTables();
processTables();

/**
* Add current line to a table
*
* @param {!string} line Line of text
*/
function addLine(line)
{
	var ignoreLen = 0;

	if (!table)
	{
		table = { rows: [] };

		// Make the table start at the first non-space character
		ignoreLen = /^ */.exec(line)[0].length;
		line      = line.substr(ignoreLen);
	}

	// Overwrite the outermost pipes
	line = line.replace(/^( *)\|/, '$1 ').replace(/\|( *)$/, ' $1');

	table.rows.push({ line: line, pos: pos + ignoreLen });
}

/**
* Process current table's body
*/
function addTableBody()
{
	var i   = 1,
		cnt = table.rows.length;
	while (++i < cnt)
	{
		addTableRow('TD', table.rows[i]);
	}

	createBodyTags(table.rows[2].pos, pos);
}

/**
* Add a cell's tags for current table at current position
*
* @param {!string} tagName Either TD or TH
* @param {!string} align   Either "left", "center", "right" or ""
*/
function addTableCell(tagName, align, text)
{
	var startPos  = pos,
		endPos    = startPos + text.length,
		ignoreLen;
	pos = endPos;

	var m = /^( *).*?( *)$/.exec(text);
	if (m[1])
	{
		ignoreLen = m[1].length;
		createIgnoreTag(startPos, ignoreLen);
		startPos += ignoreLen;
	}
	if (m[2])
	{
		ignoreLen = m[2].length;
		createIgnoreTag(endPos - ignoreLen, ignoreLen);
		endPos -= ignoreLen;
	}

	createCellTags(tagName, startPos, endPos, align);
}

/**
* Process current table's head
*/
function addTableHead()
{
	addTableRow('TH', table.rows[0]);
	createHeadTags(table.rows[0].pos, pos);
}

/**
* Process given table row
*
* @param {!string} tagName Either TD or TH
* @param {!Object} row
*/
function addTableRow(tagName, row)
{
	pos = row.pos;
	row.line.split('|').forEach(function(str, i)
	{
		if (i > 0)
		{
			createIgnoreTag(pos, 1);
			++pos;
		}

		var align = (!table.cols[i]) ? '' : table.cols[i];
		addTableCell(tagName, align, str);
	});

	createRowTags(row.pos, pos);
}

/**
* Capture all pipe tables in current text
*/
function captureTables()
{
	table  = null;
	tables = [];

	pos = 0;
	text.split("\n").forEach(function(line)
	{
		if (line.indexOf('|') < 0)
		{
			endTable();
		}
		else
		{
			addLine(line);
		}
		pos += 1 + line.length;
	});
	endTable();
}

/**
* Create a pair of TBODY tags for given text span
*
* @param {!number} startPos
* @param {!number} endPos
*/
function createBodyTags(startPos, endPos)
{
	addTagPair('TBODY', startPos, 0, endPos, 0, -103);
}

/**
* Create a pair of TD or TH tags for given text span
*
* @param {!string} tagName  Either TD or TH
* @param {!number} startPos
* @param {!number} endPos
* @param {!string} align    Either "left", "center", "right" or ""
*/
function createCellTags(tagName, startPos, endPos, align)
{
	var tag;
	if (startPos === endPos)
	{
		tag = addSelfClosingTag(tagName, startPos, 0, -101);
	}
	else
	{
		tag = addTagPair(tagName, startPos, 0, endPos, 0, -101);
	}
	if (align)
	{
		tag.setAttribute('align', align);
	}
}

/**
* Create a pair of THEAD tags for given text span
*
* @param {!number} startPos
* @param {!number} endPos
*/
function createHeadTags(startPos, endPos)
{
	addTagPair('THEAD', startPos, 0, endPos, 0, -103);
}

/**
* Create an ignore tag for given text span
*
* @param {!number} pos
* @param {!number} len
*/
function createIgnoreTag(pos, len)
{
	tableTag.cascadeInvalidationTo(addIgnoreTag(pos, len, 1000));
}

/**
* Create a pair of TR tags for given text span
*
* @param {!number} startPos
* @param {!number} endPos
*/
function createRowTags(startPos, endPos)
{
	addTagPair('TR', startPos, 0, endPos, 0, -102);
}

/**
* Create an ignore tag for given separator row
*
* @param {!Object} row
*/
function createSeparatorTag(row)
{
	createIgnoreTag(row.pos - 1, 1 + row.line.length);
}

/**
* Create a pair of TABLE tags for given text span
*
* @param {!number} startPos
* @param {!number} endPos
*/
function createTableTags(startPos, endPos)
{
	tableTag = addTagPair('TABLE', startPos, 0, endPos, 0, -104);
}

/**
* End current buffered table
*/
function endTable()
{
	if (hasValidTable())
	{
		table.cols = parseColumnAlignments(table.rows[1].line);
		tables.push(table);
	}
	table = null;
}

/**
* Test whether a valid table is currently buffered
*
* @return {!boolean}
*/
function hasValidTable()
{
	return (table && table.rows.length > 2 && isValidSeparator(table.rows[1].line));
}

/**
* Test whether given line is a valid separator
*
* @param  {!string}  line
* @return {!boolean}
*/
function isValidSeparator(line)
{
	return /^ *:?-+:?(?:(?:\+| *\| *):?-+:?)+ */.test(line);
}

/**
* Overwrite right angle brackets in given match
*
* @param  {!string} str
* @return {!string}
*/
function overwriteBlockquoteCallback(str)
{
	return str.replace(/>/g, ' ');
}

/**
* Overwrite escape sequences in current text
*/
function overwriteEscapes()
{
	if (text.indexOf('\\|') > -1)
	{
		text = text.replace(/\\[\\|]/g, '..');
	}
}

/**
* Overwrite backticks in given match
*
* @param  {!string} str
* @return string
*/
function overwriteInlineCodeCallback(str)
{
	return str.replace(/\|/g, '.');
}

/**
* Overwrite Markdown-style markup in current text
*/
function overwriteMarkdown()
{
	// Overwrite inline code spans
	if (text.indexOf('`') > -1)
	{
		text = text.replace(/`[^`]*`/g, overwriteInlineCodeCallback);
	}

	// Overwrite blockquotes
	if (text.indexOf('>') > -1)
	{
		text = text.replace(/^(?:> ?)+/gm, overwriteBlockquoteCallback);
	}
}

/**
* Parse and return column alignments in given separator line
*
* @param  {!string} line
* @return {!Array<!string>}
*/
function parseColumnAlignments(line)
{
	// Use a bitfield to represent the colons' presence and map it to the CSS value
	var align = [
			'',
			'right',
			'left',
			'center'
		],
		cols = [],
		regexp = /(:?)-+(:?)/g,
		m;

	while (m = regexp.exec(line))
	{
		var key = (m[1] ? 2 : 0) + (m[2] ? 1 : 0);
		cols.push(align[key]);
	}

	return cols;
}

/**
* Process current table declaration
*/
function processCurrentTable()
{
	var firstRow = table.rows[0],
		lastRow  = table.rows[table.rows.length - 1];
	createTableTags(firstRow.pos, lastRow.pos + lastRow.line.length);

	addTableHead();
	createSeparatorTag(table.rows[1]);
	addTableBody();
}

/**
* Process all the captured tables
*/
function processTables()
{
	var i = -1, cnt = tables.length;
	while (++i < cnt)
	{
		table = tables[i];
		processCurrentTable();
	}
}