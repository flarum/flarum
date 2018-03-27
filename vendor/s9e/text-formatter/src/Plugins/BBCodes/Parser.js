/**
* @type {!Object} Attributes of the BBCode being parsed
*/
var attributes;

/**
* @type {!Object} Configuration for the BBCode being parsed
*/
var bbcodeConfig;

/**
* @type {!string} Name of the BBCode being parsed
*/
var bbcodeName;

/**
* @type {!string} Suffix of the BBCode being parsed, including its colon
*/
var bbcodeSuffix;

/**
* @type {!number} Position of the cursor in the original text
*/
var pos;

/**
* @type {!number} Position of the start of the BBCode being parsed
*/
var startPos;

/**
* @type {!number} Length of the text being parsed
*/
var textLen = text.length;

/**
* @type {!string} Text being parsed, normalized to uppercase
*/
var uppercaseText = '';

matches.forEach(function(m)
{
	bbcodeName = m[1][0].toUpperCase();
	if (!(bbcodeName in config.bbcodes))
	{
		return;
	}
	bbcodeConfig = config.bbcodes[bbcodeName];
	startPos     = m[0][1];
	pos          = startPos + m[0][0].length;

	try
	{
		parseBBCode();
	}
	catch (e)
	{
		// Do nothing
	}
});

/**
* Add the end tag that matches current BBCode
*
* @return {!Tag}
*/
function addBBCodeEndTag()
{
	return addEndTag(getTagName(), startPos, pos - startPos);
}

/**
* Add the self-closing tag that matches current BBCode
*
* @return {!Tag}
*/
function addBBCodeSelfClosingTag()
{
	var tag = addSelfClosingTag(getTagName(), startPos, pos - startPos);
	tag.setAttributes(attributes);

	return tag;
}

/**
* Add the start tag that matches current BBCode
*
* @return {!Tag}
*/
function addBBCodeStartTag()
{
	var tag = addStartTag(getTagName(), startPos, pos - startPos);
	tag.setAttributes(attributes);

	return tag;
}

/**
* Parse the end tag that matches given BBCode name and suffix starting at current position
*
* @return {Tag}
*/
function captureEndTag()
{
	if (!uppercaseText)
	{
		uppercaseText = text.toUpperCase();
	}
	var match     = '[/' + bbcodeName + bbcodeSuffix + ']',
		endTagPos = uppercaseText.indexOf(match, pos);
	if (endTagPos < 0)
	{
		return null;
	}

	return addEndTag(getTagName(), endTagPos, match.length);
}

/**
* Get the tag name for current BBCode
*
* @return string
*/
function getTagName()
{
	// Use the configured tagName if available, or reuse the BBCode's name otherwise
	return bbcodeConfig.tagName || bbcodeName;
}

/**
* Parse attributes starting at current position
*
* @return array Associative array of [name => value]
*/
function parseAttributes()
{
	var firstPos = pos, attrName;
	attributes = {};
	while (pos < textLen)
	{
		var c = text.charAt(pos);
		if (" \n\t".indexOf(c) > -1)
		{
			++pos;
			continue;
		}
		if ('/]'.indexOf(c) > -1)
		{
			return;
		}

		// Capture the attribute name
		var spn = /^[-\w]*/.exec(text.substr(pos, 100))[0].length;
		if (spn)
		{
			attrName = text.substr(pos, spn).toLowerCase();
			pos += spn;
			if (pos >= textLen)
			{
				// The attribute name extends to the end of the text
				throw '';
			}
			if (text.charAt(pos) !== '=')
			{
				// It's an attribute name not followed by an equal sign, ignore it
				continue;
			}
		}
		else if (c === '=' && pos === firstPos)
		{
			// This is the default param, e.g. [quote=foo]
			attrName = bbcodeConfig.defaultAttribute || bbcodeName.toLowerCase();
		}
		else
		{
			throw '';
		}

		// Move past the = and make sure we're not at the end of the text
		if (++pos >= textLen)
		{
			throw '';
		}

		attributes[attrName] = parseAttributeValue();
	}
}

/**
* Parse the attribute value starting at current position
*
* @return string
*/
function parseAttributeValue()
{
	// Test whether the value is in quotes
	if (text.charAt(pos) === '"' || text.charAt(pos) === "'")
	{
		return parseQuotedAttributeValue();
	}

	// Capture everything up to whichever comes first:
	//  - an endline
	//  - whitespace followed by a slash and a closing bracket
	//  - a closing bracket, optionally preceded by whitespace
	//  - whitespace followed by another attribute (name followed by equal sign)
	//
	// NOTE: this is for compatibility with some forums (such as vBulletin it seems)
	//       that do not put attribute values in quotes, e.g.
	//       [quote=John Smith;123456] (quoting "John Smith" from post #123456)
	var match = /[^\]\n]*?(?=\s*(?:\s\/)?\]|\s+[-\w]+=)/.exec(text.substr(pos));
	if (!match)
	{
		throw '';
	}

	var attrValue = match[0];
	pos += attrValue.length;

	return attrValue;
}

/**
* Parse current BBCode
*
* @return void
*/
function parseBBCode()
{
	parseBBCodeSuffix();

	// Test whether this is an end tag
	if (text.charAt(startPos + 1) === '/')
	{
		// Test whether the tag is properly closed and whether this tag has an identifier.
		// We skip end tags that carry an identifier because they're automatically added
		// when their start tag is processed
		if (text.charAt(pos) === ']' && bbcodeSuffix === '')
		{
			++pos;
			addBBCodeEndTag();
		}

		return;
	}

	// Parse attributes and fill in the blanks with predefined attributes
	parseAttributes();
	if (bbcodeConfig.predefinedAttributes)
	{
		for (var attrName in bbcodeConfig.predefinedAttributes)
		{
			if (!(attrName in attributes))
			{
				attributes[attrName] = bbcodeConfig.predefinedAttributes[attrName];
			}
		}
	}

	// Test whether the tag is properly closed
	if (text.charAt(pos) === ']')
	{
		++pos;
	}
	else
	{
		// Test whether this is a self-closing tag
		if (text.substr(pos, 2) === '/]')
		{
			pos += 2;
			addBBCodeSelfClosingTag();
		}

		return;
	}

	// Record the names of attributes that need the content of this tag
	var contentAttributes = [];
	if (bbcodeConfig.contentAttributes)
	{
		bbcodeConfig.contentAttributes.forEach(function(attrName)
		{
			if (!(attrName in attributes))
			{
				contentAttributes.push(attrName);
			}
		});
	}

	// Look ahead and parse the end tag that matches this tag, if applicable
	var requireEndTag = (bbcodeSuffix || bbcodeConfig.forceLookahead),
		endTag = (requireEndTag || contentAttributes.length) ? captureEndTag() : null;
	if (endTag)
	{
		contentAttributes.forEach(function(attrName)
		{
			attributes[attrName] = text.substr(pos, endTag.getPos() - pos);
		});
	}
	else if (requireEndTag)
	{
		return;
	}

	// Create this start tag
	var tag = addBBCodeStartTag();

	// If an end tag was created, pair it with this start tag
	if (endTag)
	{
		tag.pairWith(endTag);
	}
}

/**
* Parse the BBCode suffix starting at current position
*
* Used to explicitly pair specific tags together, e.g.
*   [code:123][code]type your code here[/code][/code:123]
*
* @return void
*/
function parseBBCodeSuffix()
{
	bbcodeSuffix = '';
	if (text[pos] === ':')
	{
		// Capture the colon and the (0 or more) digits following it
		bbcodeSuffix = /^:\d*/.exec(text.substr(pos))[0];

		// Move past the suffix
		pos += bbcodeSuffix.length;
	}
}

/**
* Parse a quoted attribute value that starts at current offset
*
* @return {!string}
*/
function parseQuotedAttributeValue()
{
	var quote    = text.charAt(pos),
		valuePos = pos + 1;
	while (1)
	{
		// Look for the next quote
		pos = text.indexOf(quote, pos + 1);
		if (pos < 0)
		{
			// No matching quote. Apparently that string never ends...
			throw '';
		}

		// Test for an odd number of backslashes before this character
		var n = 0;
		do
		{
			++n;
		}
		while (text.charAt(pos - n) === '\\');

		if (n % 2)
		{
			// If n is odd, it means there's an even number of backslashes. We can exit this loop
			break;
		}
	}

	// Unescape special characters ' " and \
	var attrValue = text.substr(valuePos, pos - valuePos).replace(/\\([\\'"])/g, '$1');

	// Skip past the closing quote
	++pos;

	return attrValue;
}