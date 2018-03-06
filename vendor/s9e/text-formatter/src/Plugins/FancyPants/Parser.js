var attrName       = config.attrName,
	hasSingleQuote = (text.indexOf("'") >= 0),
	hasDoubleQuote = (text.indexOf('"') >= 0),
	tagName        = config.tagName;

if (!config.disableQuotes)
{
	parseSingleQuotes();
	parseSingleQuotePairs();
	parseDoubleQuotePairs();
}
if (!config.disableGuillemets)
{
	parseGuillemets();
}
if (!config.disableMathSymbols)
{
	parseNotEqualSign();
	parseSymbolsAfterDigits();
	parseFractions();
}
if (!config.disablePunctuation)
{
	parseDashesAndEllipses();
}
if (!config.disableSymbols)
{
	parseSymbolsInParentheses();
}

/**
* Add a fancy replacement tag
*
* @param  {!number} tagPos Position of the tag in the text
* @param  {!number} tagLen Length of text consumed by the tag
* @param  {!string} chr    Replacement character
* @param  {number}  prio   Tag's priority
* @return {!Tag}
*/
function addTag(tagPos, tagLen, chr, prio)
{
	var tag = addSelfClosingTag(tagName, tagPos, tagLen, prio || 0);
	tag.setAttribute(attrName, chr);

	return tag;
}

/**
* Parse dashes and ellipses
*
* Does en dash –, em dash — and ellipsis …
*/
function parseDashesAndEllipses()
{
	if (text.indexOf('...') < 0 && text.indexOf('--') < 0)
	{
		return;
	}

	var chrs = {
			'--'  : "\u2013",
			'---' : "\u2014",
			'...' : "\u2026"
		},
		regexp = /---?|\.\.\./g,
		m;
	while (m = regexp.exec(text))
	{
		addTag(+m['index'], m[0].length, chrs[m[0]]);
	}
}

/**
* Parse pairs of double quotes
*
* Does quote pairs “” -- must be done separately to handle nesting
*/
function parseDoubleQuotePairs()
{
	if (hasDoubleQuote)
	{
		parseQuotePairs('"', /(?:^|\W)".+?"(?!\w)/g, "\u201c", "\u201d");
	}
}

/**
* Parse vulgar fractions
*/
function parseFractions()
{
	if (text.indexOf('/') < 0)
	{
		return;
	}

	/** @const */
	var map = {
		'0/3'  : "\u2189",
		'1/10' : "\u2152",
		'1/2'  : "\u00BD",
		'1/3'  : "\u2153",
		'1/4'  : "\u00BC",
		'1/5'  : "\u2155",
		'1/6'  : "\u2159",
		'1/7'  : "\u2150",
		'1/8'  : "\u215B",
		'1/9'  : "\u2151",
		'2/3'  : "\u2154",
		'2/5'  : "\u2156",
		'3/4'  : "\u00BE",
		'3/5'  : "\u2157",
		'3/8'  : "\u215C",
		'4/5'  : "\u2158",
		'5/6'  : "\u215A",
		'5/8'  : "\u215D",
		'7/8'  : "\u215E"
	};

	var m, regexp = /\b(?:0\/3|1\/(?:[2-9]|10)|2\/[35]|3\/[458]|4\/5|5\/[68]|7\/8)\b/g;
	while (m = regexp.exec(text))
	{
		addTag(+m['index'], m[0].length, map[m[0]]);
	}
}

/**
* Parse guillemets-style quotation marks
*/
function parseGuillemets()
{
	if (text.indexOf('<<') < 0)
	{
		return;
	}

	var m, regexp = /<<( ?)(?! )[^\n<>]*?[^\n <>]\1>>(?!>)/g;
	while (m = regexp.exec(text))
	{
		var left  = addTag(+m['index'],                   2, "\u00AB"),
			right = addTag(+m['index'] + m[0].length - 2, 2, "\u00BB");

		left.cascadeInvalidationTo(right);
	}
}

/**
* Parse the not equal sign
*
* Supports != and =/=
*/
function parseNotEqualSign()
{
	if (text.indexOf('!=') < 0 && text.indexOf('=/=') < 0)
	{
		return;
	}

	var m, regexp = /\b (?:!|=\/)=(?= \b)/g;
	while (m = regexp.exec(text))
	{
		addTag(+m['index'] + 1, m[0].length - 1, "\u2260");
	}
}

/**
* Parse pairs of quotes
*
* @param {!string} q          ASCII quote character 
* @param {!RegExp} regexp     Regexp used to identify quote pairs
* @param {!string} leftQuote  Fancy replacement for left quote
* @param {!string} rightQuote Fancy replacement for right quote
*/
function parseQuotePairs(q, regexp, leftQuote, rightQuote)
{
	var m;
	while (m = regexp.exec(text))
	{
		var left  = addTag(+m['index'] + m[0].indexOf(q), 1, leftQuote),
			right = addTag(+m['index'] + m[0].length - 1, 1, rightQuote);

		// Cascade left tag's invalidation to the right so that if we skip the left quote,
		// the right quote remains untouched
		left.cascadeInvalidationTo(right);
	}
}

/**
* Parse pairs of single quotes
*
* Does quote pairs ‘’ must be done separately to handle nesting
*/
function parseSingleQuotePairs()
{
	if (hasSingleQuote)
	{
		parseQuotePairs("'", /(?:^|\W)'.+?'(?!\w)/g, "\u2018", "\u2019");
	}
}

/**
* Parse single quotes in general
*
* Does apostrophes ’ after a letter or at the beginning of a word or a couple of digits
*/
function parseSingleQuotes()
{
	if (!hasSingleQuote)
	{
		return;
	}

	var m, regexp = /[a-z]'|(?:^|\s)'(?=[a-z]|[0-9]{2})/gi;
	while (m = regexp.exec(text))
	{
		// Give this tag a worse priority than default so that quote pairs take precedence
		addTag(+m['index'] + m[0].indexOf("'"), 1, "\u2019", 10);
	}
}

/**
* Parse symbols found after digits
*
* Does symbols found after a digit:
*  - apostrophe ’ if it's followed by an "s" as in 80's
*  - prime ′ and double prime ″
*  - multiply sign × if it's followed by an optional space and another digit
*/
function parseSymbolsAfterDigits()
{
	if (!hasSingleQuote && !hasDoubleQuote && text.indexOf('x') < 0)
	{
		return;
	}

	/** @const */
	var map = {
		// 80's -- use an apostrophe
		"'s" : "\u2019",
		// 12' or 12" -- use a prime
		"'"  : "\u2032",
		"' " : "\u2032",
		"'x" : "\u2032",
		'"'  : "\u2033",
		'" ' : "\u2033",
		'"x' : "\u2033"
	};

	var m, regexp = /[0-9](?:'s|["']? ?x(?= ?[0-9])|["'])/g;
	while (m = regexp.exec(text))
	{
		// Test for a multiply sign at the end
		if (m[0].charAt(m[0].length - 1) === 'x')
		{
			addTag(+m['index'] + m[0].length - 1, 1, "\u00d7");
		}

		// Test for an apostrophe/prime right after the digit
		var str = m[0].substr(1, 2);
		if (map[str])
		{
			addTag(+m['index'] + 1, 1, map[str]);
		}
	}
}

/**
* Parse symbols found in parentheses such as (c)
*
* Does symbols ©, ® and ™
*/
function parseSymbolsInParentheses()
{
	if (text.indexOf('(') < 0)
	{
		return;
	}

	var chrs = {
			'(c)'  : "\u00A9",
			'(r)'  : "\u00AE",
			'(tm)' : "\u2122"
		},
		regexp = /\((?:c|r|tm)\)/gi,
		m;
	while (m = regexp.exec(text))
	{
		addTag(+m['index'], m[0].length, chrs[m[0].toLowerCase()]);
	}
}