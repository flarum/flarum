/**#@+
* Boolean rules bitfield
*/
/** @const */ var RULE_AUTO_CLOSE        = 1 << 0;
/** @const */ var RULE_AUTO_REOPEN       = 1 << 1;
/** @const */ var RULE_BREAK_PARAGRAPH   = 1 << 2;
/** @const */ var RULE_CREATE_PARAGRAPHS = 1 << 3;
/** @const */ var RULE_DISABLE_AUTO_BR   = 1 << 4;
/** @const */ var RULE_ENABLE_AUTO_BR    = 1 << 5;
/** @const */ var RULE_IGNORE_TAGS       = 1 << 6;
/** @const */ var RULE_IGNORE_TEXT       = 1 << 7;
/** @const */ var RULE_IGNORE_WHITESPACE = 1 << 8;
/** @const */ var RULE_IS_TRANSPARENT    = 1 << 9;
/** @const */ var RULE_PREVENT_BR        = 1 << 10;
/** @const */ var RULE_SUSPEND_AUTO_BR   = 1 << 11;
/** @const */ var RULE_TRIM_FIRST_LINE   = 1 << 12;
/**#@-*/

/**
* @const Bitwise disjunction of rules related to automatic line breaks
*/
var RULES_AUTO_LINEBREAKS = RULE_DISABLE_AUTO_BR | RULE_ENABLE_AUTO_BR | RULE_SUSPEND_AUTO_BR;

/**
* @const Bitwise disjunction of rules that are inherited by subcontexts
*/
var RULES_INHERITANCE = RULE_ENABLE_AUTO_BR;

/**
* @const All the characters that are considered whitespace
*/
var WHITESPACE = " \n\t";

/**
* @type {!Object.<string,!number>} Number of open tags for each tag name
*/
var cntOpen;

/**
* @type {!Object.<string,!number>} Number of times each tag has been used
*/
var cntTotal;

/**
* @type {!Object} Current context
*/
var context;

/**
* @type {!number} How hard the parser has worked on fixing bad markup so far
*/
var currentFixingCost;

/**
* @type {Tag} Current tag being processed
*/
var currentTag;

/**
* @type {!boolean} Whether the output contains "rich" tags, IOW any tag that is not <p> or <br/>
*/
var isRich;

/**
* @type {!Logger} This parser's logger
*/
var logger = new Logger;

/**
* @type {!number} How hard the parser should work on fixing bad markup
*/
var maxFixingCost = 1000;

/**
* @type {!Object} Associative array of namespace prefixes in use in document (prefixes used as key)
*/
var namespaces;

/**
* @type {!Array.<!Tag>} Stack of open tags (instances of Tag)
*/
var openTags;

/**
* @type {!string} This parser's output
*/
var output;

/**
* @type {!Object.<!Object>}
*/
var plugins;

/**
* @type {!number} Position of the cursor in the original text
*/
var pos;

/**
* @type {!Object} Variables registered for use in filters
*/
var registeredVars;

/**
* @type {!Object} Root context, used at the root of the document
*/
var rootContext;

/**
* @type {!Object} Tags' config
* @const
*/
var tagsConfig;

/**
* @type {!Array.<!Tag>} Tag storage
*/
var tagStack;

/**
* @type {!boolean} Whether the tags in the stack are sorted
*/
var tagStackIsSorted;

/**
* @type {!string} Text being parsed
*/
var text;

/**
* @type {!number} Length of the text being parsed
*/
var textLen;

/**
* @type {!number} Counter incremented everytime the parser is reset. Used to as a canary to detect
*                 whether the parser was reset during execution
*/
var uid = 0;

/**
* @type {!number} Position before which we output text verbatim, without paragraphs or linebreaks
*/
var wsPos;

//==========================================================================
// Public API
//==========================================================================

/**
* Disable a tag
*
* @param {!string} tagName Name of the tag
*/
function disableTag(tagName)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).isDisabled = true;
	}
}

/**
* Enable a tag
*
* @param {!string} tagName Name of the tag
*/
function enableTag(tagName)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).isDisabled = false;
	}
}

/**
* Get this parser's Logger instance
*
* @return {!Logger}
*/
function getLogger()
{
	return logger;
}

/**
* Parse a text
*
* @param  {!string} _text Text to parse
* @return {!string}       XML representation
*/
function parse(_text)
{
	// Reset the parser and save the uid
	reset(_text);
	var _uid = uid;

	// Do the heavy lifting
	executePluginParsers();
	processTags();

	// Finalize the document
	finalizeOutput();

	// Check the uid in case a plugin or a filter reset the parser mid-execution
	if (uid !== _uid)
	{
		throw 'The parser has been reset during execution';
	}

	// Log a warning if the fixing cost limit was exceeded
	if (currentFixingCost > maxFixingCost)
	{
		logger.warn('Fixing cost limit exceeded');
	}

	return output;
}

/**
* Reset the parser for a new parsing
*
* @param {!string} _text Text to be parsed
*/
function reset(_text)
{
	// Normalize CR/CRLF to LF, remove control characters that aren't allowed in XML
	_text = _text.replace(/\r\n?/g, "\n");
	_text = _text.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F]+/g, '');

	// Clear the logs
	logger.clear();

	// Initialize the rest
	cntOpen           = {};
	cntTotal          = {};
	currentFixingCost = 0;
	currentTag        = null;
	isRich            = false;
	namespaces        = {};
	openTags          = [];
	output            = '';
	pos               = 0;
	tagStack          = [];
	tagStackIsSorted  = false;
	text              = _text;
	textLen           = text.length;
	wsPos             = 0;

	// Initialize the root context
	context = rootContext;
	context.inParagraph = false;

	// Bump the UID
	++uid;
}

/**
* Change a tag's tagLimit
*
* NOTE: the default tagLimit should generally be set during configuration instead
*
* @param {!string} tagName  The tag's name, in UPPERCASE
* @param {!number} tagLimit
*/
function setTagLimit(tagName, tagLimit)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).tagLimit = tagLimit;
	}
}

/**
* Change a tag's nestingLimit
*
* NOTE: the default nestingLimit should generally be set during configuration instead
*
* @param {!string} tagName      The tag's name, in UPPERCASE
* @param {!number} nestingLimit
*/
function setNestingLimit(tagName, nestingLimit)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).nestingLimit = nestingLimit;
	}
}

/**
* Copy a tag's config
*
* This method ensures that the tag's config is its own object and not shared with another
* identical tag
*
* @param  {!string} tagName Tag's name
* @return {!Object}         Tag's config
*/
function copyTagConfig(tagName)
{
	var tagConfig = {}, k;
	for (k in tagsConfig[tagName])
	{
		tagConfig[k] = tagsConfig[tagName][k];
	}

	return tagsConfig[tagName] = tagConfig;
}

//==========================================================================
// Filter processing
//==========================================================================

/**
* Execute all the attribute preprocessors of given tag
*
* @private
*
* @param  {!Tag}     tag       Source tag
* @param  {!Object}  tagConfig Tag's config
* @return {!boolean}           Unconditionally TRUE
*/
function executeAttributePreprocessors(tag, tagConfig)
{
	if (tagConfig.attributePreprocessors)
	{
		tagConfig.attributePreprocessors.forEach(function(attributePreprocessor)
		{
			var attrName = attributePreprocessor[0],
				regexp   = attributePreprocessor[1],
				map      = attributePreprocessor[2];

			if (!tag.hasAttribute(attrName))
			{
				return;
			}

			executeAttributePreprocessor(tag, attrName, regexp, map);
		});
	}

	return true;
}

/**
* Execute an attribute preprocessor
*
* @param  {!Tag}            tag
* @param  {!string}         attrName
* @param  {!string}         regexp
* @param  {!Array<!string>} map
*/
function executeAttributePreprocessor(tag, attrName, regexp, map)
{
	var attrValue = tag.getAttribute(attrName),
		captures  = getNamedCaptures(attrValue, regexp, map),
		k;
	
	for (k in captures)
	{
		// Attribute preprocessors cannot overwrite other attributes but they can
		// overwrite themselves
		if (k === attrName || !tag.hasAttribute(k))
		{
			tag.setAttribute(k, captures[k]);
		}
	}
}

/**
* Execute a regexp and return the values of the mapped captures
*
* @param  {!string}                  attrValue
* @param  {!string}                  regexp
* @param  {!Array<!string>}          map
* @return {!Object<!string,!string>}
*/
function getNamedCaptures(attrValue, regexp, map)
{
	var m = regexp.exec(attrValue);
	if (!m)
	{
		return [];
	}

	var values = {};
	map.forEach(function(k, i)
	{
		if (typeof m[i] === 'string' && m[i] !== '')
		{
			values[k] = m[i];
		}
	});

	return values;
}

/**
* Filter the attributes of given tag
*
* @private
*
* @param  {!Tag}     tag            Tag being checked
* @param  {!Object}  tagConfig      Tag's config
* @param  {!Object}  registeredVars Vars registered for use in attribute filters
* @param  {!Logger}  logger         This parser's Logger instance
* @return {!boolean}           Whether the whole attribute set is valid
*/
function filterAttributes(tag, tagConfig, registeredVars, logger)
{
	if (!tagConfig.attributes)
	{
		tag.setAttributes({});

		return true;
	}

	var attrName, attrConfig;

	// Generate values for attributes with a generator set
	if (HINT.attributeGenerator)
	{
		for (attrName in tagConfig.attributes)
		{
			attrConfig = tagConfig.attributes[attrName];

			if (attrConfig.generator)
			{
				tag.setAttribute(attrName, attrConfig.generator(attrName));
			}
		}
	}

	// Filter and remove invalid attributes
	var attributes = tag.getAttributes();
	for (attrName in attributes)
	{
		var attrValue = attributes[attrName];

		// Test whether this attribute exists and remove it if it doesn't
		if (!tagConfig.attributes[attrName])
		{
			tag.removeAttribute(attrName);
			continue;
		}

		attrConfig = tagConfig.attributes[attrName];

		// Test whether this attribute has a filterChain
		if (!attrConfig.filterChain)
		{
			continue;
		}

		// Record the name of the attribute being filtered into the logger
		logger.setAttribute(attrName);

		for (var i = 0; i < attrConfig.filterChain.length; ++i)
		{
			// NOTE: attrValue is intentionally set as the first argument to facilitate inlining
			attrValue = attrConfig.filterChain[i](attrValue, attrName);

			if (attrValue === false)
			{
				tag.removeAttribute(attrName);
				break;
			}
		}

		// Update the attribute value if it's valid
		if (attrValue !== false)
		{
			tag.setAttribute(attrName, attrValue);
		}

		// Remove the attribute's name from the logger
		logger.unsetAttribute();
	}

	// Iterate over the attribute definitions to handle missing attributes
	for (attrName in tagConfig.attributes)
	{
		attrConfig = tagConfig.attributes[attrName];

		// Test whether this attribute is missing
		if (!tag.hasAttribute(attrName))
		{
			if (HINT.attributeDefaultValue && attrConfig.defaultValue !== undefined)
			{
				// Use the attribute's default value
				tag.setAttribute(attrName, attrConfig.defaultValue);
			}
			else if (attrConfig.required)
			{
				// This attribute is missing, has no default value and is required, which means
				// the attribute set is invalid
				return false;
			}
		}
	}

	return true;
}

/**
* Execute given tag's filterChain
*
* @param  {!Tag}     tag Tag to filter
* @return {!boolean}     Whether the tag is valid
*/
function filterTag(tag)
{
	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName],
		isValid   = true;

	if (tagConfig.filterChain)
	{
		// Record the tag being processed into the logger it can be added to the context of
		// messages logged during the execution
		logger.setTag(tag);

		for (var i = 0; i < tagConfig.filterChain.length; ++i)
		{
			if (!tagConfig.filterChain[i](tag, tagConfig))
			{
				isValid = false;
				break;
			}
		}

		// Remove the tag from the logger
		logger.unsetTag();
	}

	return isValid;
}

//==========================================================================
// Output handling
//==========================================================================

/**
* Replace Unicode characters outside the BMP with XML entities in the output
*/
function encodeUnicodeSupplementaryCharacters()
{
	output = output.replace(
		/[\uD800-\uDBFF][\uDC00-\uDFFF]/g,
		encodeUnicodeSupplementaryCharactersCallback
	);
}

/**
* Encode given surrogate pair into an XML entity
*
* @param  {!string} pair Surrogate pair
* @return {!string}      XML entity
*/
function encodeUnicodeSupplementaryCharactersCallback(pair)
{
	var cp = (pair.charCodeAt(0) << 10) + pair.charCodeAt(1) - 56613888;

	return '&#' + cp + ';';
}

/**
* Finalize the output by appending the rest of the unprocessed text and create the root node
*/
function finalizeOutput()
{
	var tmp;

	// Output the rest of the text and close the last paragraph
	outputText(textLen, 0, true);

	// Remove empty tag pairs, e.g. <I><U></U></I> as well as empty paragraphs
	do
	{
		tmp = output;
		output = output.replace(/<([^ />]+)><\/\1>/g, '');
	}
	while (output !== tmp);

	// Merge consecutive <i> tags
	output = output.replace(/<\/i><i>/g, '', output);

	// Encode Unicode characters that are outside of the BMP
	encodeUnicodeSupplementaryCharacters();

	// Use a <r> root if the text is rich, or <t> for plain text (including <p></p> and <br/>)
	var tagName = (isRich) ? 'r' : 't';

	// Prepare the root node with all the namespace declarations
	tmp = '<' + tagName;
	if (HINT.namespaces)
	{
		for (var prefix in namespaces)
		{
			tmp += ' xmlns:' + prefix + '="urn:s9e:TextFormatter:' + prefix + '"';
		}
	}

	output = tmp + '>' + output + '</' + tagName + '>';
}

/**
* Append a tag to the output
*
* @param {!Tag} tag Tag to append
*/
function outputTag(tag)
{
	isRich = true;

	var tagName    = tag.getName(),
		tagPos     = tag.getPos(),
		tagLen     = tag.getLen(),
		tagFlags   = tag.getFlags(),
		skipBefore = 0,
		skipAfter  = 0;

	if (HINT.RULE_IGNORE_WHITESPACE && (tagFlags & RULE_IGNORE_WHITESPACE))
	{
		skipBefore = 1;
		skipAfter  = (tag.isEndTag()) ? 2 : 1;
	}

	// Current paragraph must end before the tag if:
	//  - the tag is a start (or self-closing) tag and it breaks paragraphs, or
	//  - the tag is an end tag (but not self-closing)
	var closeParagraph = false;
	if (tag.isStartTag())
	{
		if (HINT.RULE_BREAK_PARAGRAPH && (tagFlags & RULE_BREAK_PARAGRAPH))
		{
			closeParagraph = true;
		}
	}
	else
	{
		closeParagraph = true;
	}

	// Let the cursor catch up with this tag's position
	outputText(tagPos, skipBefore, closeParagraph);

	// Capture the text consumed by the tag
	var tagText = (tagLen)
				? htmlspecialchars_noquotes(text.substr(tagPos, tagLen))
				: '';

	// Output current tag
	if (tag.isStartTag())
	{
		// Handle paragraphs before opening the tag
		if (!HINT.RULE_BREAK_PARAGRAPH || !(tagFlags & RULE_BREAK_PARAGRAPH))
		{
			outputParagraphStart(tagPos);
		}

		// Record this tag's namespace, if applicable
		if (HINT.namespaces)
		{
			var colonPos = tagName.indexOf(':');
			if (colonPos > 0)
			{
				namespaces[tagName.substr(0, colonPos)] = 0;
			}
		}

		// Open the start tag and add its attributes, but don't close the tag
		output += '<' + tagName;

		// We output the attributes in lexical order. Helps canonicalizing the output and could
		// prove useful someday
		var attributes = tag.getAttributes(),
			attributeNames = [];
		for (var attrName in attributes)
		{
			attributeNames.push(attrName);
		}
		attributeNames.sort(
			function(a, b)
			{
				return (a > b) ? 1 : -1;
			}
		);
		attributeNames.forEach(
			function(attrName)
			{
				output += ' ' + attrName + '="' + htmlspecialchars_compat(attributes[attrName].toString()).replace(/\n/g, '&#10;') + '"';
			}
		);

		if (tag.isSelfClosingTag())
		{
			if (tagLen)
			{
				output += '>' + tagText + '</' + tagName + '>';
			}
			else
			{
				output += '/>';
			}
		}
		else if (tagLen)
		{
			output += '><s>' + tagText + '</s>';
		}
		else
		{
			output += '>';
		}
	}
	else
	{
		if (tagLen)
		{
			output += '<e>' + tagText + '</e>';
		}

		output += '</' + tagName + '>';
	}

	// Move the cursor past the tag
	pos = tagPos + tagLen;

	// Skip newlines (no other whitespace) after this tag
	wsPos = pos;
	while (skipAfter && wsPos < textLen && text.charAt(wsPos) === "\n")
	{
		// Decrement the number of lines to skip
		--skipAfter;

		// Move the cursor past the newline
		++wsPos;
	}
}

/**
* Output the text between the cursor's position (included) and given position (not included)
*
* @param  {!number}  catchupPos     Position we're catching up to
* @param  {!number}  maxLines       Maximum number of lines to ignore at the end of the text
* @param  {!boolean} closeParagraph Whether to close the paragraph at the end, if applicable
*/
function outputText(catchupPos, maxLines, closeParagraph)
{
	if (closeParagraph)
	{
		if (!(context.flags & RULE_CREATE_PARAGRAPHS))
		{
			closeParagraph = false;
		}
		else
		{
			// Ignore any number of lines at the end if we're closing a paragraph
			maxLines = -1;
		}
	}

	if (pos >= catchupPos)
	{
		// We're already there, close the paragraph if applicable and return
		if (closeParagraph)
		{
			outputParagraphEnd();
		}
	}

	// Skip over previously identified whitespace if applicable
	if (wsPos > pos)
	{
		var skipPos = Math.min(catchupPos, wsPos);
		output += text.substr(pos, skipPos - pos);
		pos = skipPos;

		if (pos >= catchupPos)
		{
			// Skipped everything. Close the paragraph if applicable and return
			if (closeParagraph)
			{
				outputParagraphEnd();
			}
		}
	}

	var catchupLen, catchupText;

	// Test whether we're even supposed to output anything
	if (HINT.RULE_IGNORE_TEXT && context.flags & RULE_IGNORE_TEXT)
	{
		catchupLen  = catchupPos - pos,
		catchupText = text.substr(pos, catchupLen);

		// If the catchup text is not entirely composed of whitespace, we put it inside ignore tags
		if (!/^[ \n\t]*$/.test(catchupText))
		{
			catchupText = '<i>' + catchupText + '</i>';
		}

		output += catchupText;
		pos = catchupPos;

		if (closeParagraph)
		{
			outputParagraphEnd();
		}

		return;
	}

	// Compute the amount of text to ignore at the end of the output
	var ignorePos = catchupPos,
		ignoreLen = 0;

	// Ignore as many lines (including whitespace) as specified
	while (maxLines && --ignorePos >= pos)
	{
		var c = text.charAt(ignorePos);
		if (c !== ' ' && c !== "\n" && c !== "\t")
		{
			break;
		}

		if (c === "\n")
		{
			--maxLines;
		}

		++ignoreLen;
	}

	// Adjust catchupPos to ignore the text at the end
	catchupPos -= ignoreLen;

	// Break down the text in paragraphs if applicable
	if (HINT.RULE_CREATE_PARAGRAPHS && context.flags & RULE_CREATE_PARAGRAPHS)
	{
		if (!context.inParagraph)
		{
			outputWhitespace(catchupPos);

			if (catchupPos > pos)
			{
				outputParagraphStart(catchupPos);
			}
		}

		// Look for a paragraph break in this text
		var pbPos = text.indexOf("\n\n", pos);

		while (pbPos > -1 && pbPos < catchupPos)
		{
			outputText(pbPos, 0, true);
			outputParagraphStart(catchupPos);

			pbPos = text.indexOf("\n\n", pos);
		}
	}

	// Capture, escape and output the text
	if (catchupPos > pos)
	{
		catchupText = htmlspecialchars_noquotes(
			text.substr(pos, catchupPos - pos)
		);

		// Format line breaks if applicable
		if (HINT.RULE_ENABLE_AUTO_BR && (context.flags & RULES_AUTO_LINEBREAKS) === RULE_ENABLE_AUTO_BR)
		{
			catchupText = catchupText.replace(/\n/g, "<br/>\n");
		}

		output += catchupText;
	}

	// Close the paragraph if applicable
	if (closeParagraph)
	{
		outputParagraphEnd();
	}

	// Add the ignored text if applicable
	if (ignoreLen)
	{
		output += text.substr(catchupPos, ignoreLen);
	}

	// Move the cursor past the text
	pos = catchupPos + ignoreLen;
}

/**
* Output a linebreak tag
*
* @param  {!Tag} tag
* @return void
*/
function outputBrTag(tag)
{
	outputText(tag.getPos(), 0, false);
	output += '<br/>';
}

/**
* Output an ignore tag
*
* @param  {!Tag} tag
* @return void
*/
function outputIgnoreTag(tag)
{
	var tagPos = tag.getPos(),
		tagLen = tag.getLen();

	// Capture the text to ignore
	var ignoreText = text.substr(tagPos, tagLen);

	// Catch up with the tag's position then output the tag
	outputText(tagPos, 0, false);
	output += '<i>' + htmlspecialchars_noquotes(ignoreText) + '</i>';
	isRich = true;

	// Move the cursor past this tag
	pos = tagPos + tagLen;
}

/**
* Start a paragraph between current position and given position, if applicable
*
* @param  {!number} maxPos Rightmost position at which the paragraph can be opened
*/
function outputParagraphStart(maxPos)
{
	if (!HINT.RULE_CREATE_PARAGRAPHS)
	{
		return;
	}

	// Do nothing if we're already in a paragraph, or if we don't use paragraphs
	if (context.inParagraph
	 || !(context.flags & RULE_CREATE_PARAGRAPHS))
	{
		return;
	}

	// Output the whitespace between pos and maxPos if applicable
	outputWhitespace(maxPos);

	// Open the paragraph, but only if it's not at the very end of the text
	if (pos < textLen)
	{
		output += '<p>';
		context.inParagraph = true;
	}
}

/**
* Close current paragraph at current position if applicable
*/
function outputParagraphEnd()
{
	// Do nothing if we're not in a paragraph
	if (!context.inParagraph)
	{
		return;
	}

	output += '</p>';
	context.inParagraph = false;
}

/**
* Output the content of a verbatim tag
*
* @param {!Tag} tag
*/
function outputVerbatim(tag)
{
	var flags = context.flags;
	context.flags = tag.getFlags();
	outputText(currentTag.getPos() + currentTag.getLen(), 0, false);
	context.flags = flags;
}

/**
* Skip as much whitespace after current position as possible
*
* @param  {!number} maxPos Rightmost character to be skipped
*/
function outputWhitespace(maxPos)
{
	while (pos < maxPos && " \n\t".indexOf(text.charAt(pos)) > -1)
	{
		output += text.charAt(pos);
		++pos;
	}
}

//==========================================================================
// Plugins handling
//==========================================================================

/**
* Disable a plugin
*
* @param {!string} pluginName Name of the plugin
*/
function disablePlugin(pluginName)
{
	if (plugins[pluginName])
	{
		plugins[pluginName].isDisabled = true;
	}
}

/**
* Enable a plugin
*
* @param {!string} pluginName Name of the plugin
*/
function enablePlugin(pluginName)
{
	if (plugins[pluginName])
	{
		plugins[pluginName].isDisabled = false;
	}
}

/**
* Execute given plugin
*
* @param {!string} pluginName Plugin's name
*/
function executePluginParser(pluginName)
{
	var pluginConfig = plugins[pluginName];
	if (pluginConfig.quickMatch && text.indexOf(pluginConfig.quickMatch) < 0)
	{
		return;
	}

	var matches = [];
	if (pluginConfig.regexp)
	{
		matches = getMatches(pluginConfig.regexp, pluginConfig.regexpLimit);
		if (!matches.length)
		{
			return;
		}
	}

	// Execute the plugin's parser, which will add tags via addStartTag() and others
	getPluginParser(pluginName)(text, matches);
}

/**
* Execute all the plugins
*/
function executePluginParsers()
{
	for (var pluginName in plugins)
	{
		if (!plugins[pluginName].isDisabled)
		{
			executePluginParser(pluginName);
		}
	}
}

/**
* Get regexp matches in a manner similar to preg_match_all() with PREG_SET_ORDER | PREG_OFFSET_CAPTURE
*
* @param  {!RegExp} regexp
* @param  {!number} limit
* @return {!Array.<!Array>}
*/
function getMatches(regexp, limit)
{
	// Reset the regexp
	regexp.lastIndex = 0;
	var matches = [], cnt = 0, m;
	while (++cnt <= limit && (m = regexp.exec(text)))
	{
		// NOTE: coercing m.index to a number because Closure Compiler thinks pos is a string otherwise
		var pos   = +m['index'],
			match = [[m[0], pos]],
			i = 0;
		while (++i < m.length)
		{
			var str = m[i];

			// Sub-expressions that were not evaluated return undefined
			if (str === undefined)
			{
				match.push(['', -1]);
			}
			else
			{
				match.push([str, text.indexOf(str, pos)]);
				pos += str.length;
			}
		}

		matches.push(match);
	}

	return matches;
}

/**
* Get the callback for given plugin's parser
*
* @param  {!string}   pluginName
* @return {!function(string, Array)}
*/
function getPluginParser(pluginName)
{
	return plugins[pluginName].parser;
}

/**
* Register a parser
*
* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
* existing plugin
*
* @param  {!string}   pluginName
* @param  {!Function} parser
* @param  {RegExp}   regexp
* @param  {number}   limit
*/
function registerParser(pluginName, parser, regexp, limit)
{
	// Create an empty config for this plugin to ensure it is executed
	if (!plugins[pluginName])
	{
		plugins[pluginName] = {};
	}
	if (regexp)
	{
		plugins[pluginName].regexp = regexp;
		plugins[pluginName].limit  = limit || Infinity;
	}
	plugins[pluginName].parser = parser;
}

//==========================================================================
// Rules handling
//==========================================================================

/**
* Apply closeAncestor rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function closeAncestor(tag)
{
	if (!HINT.closeAncestor)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeAncestor)
		{
			var i = openTags.length;

			while (--i >= 0)
			{
				var ancestor     = openTags[i],
					ancestorName = ancestor.getName();

				if (tagConfig.rules.closeAncestor[ancestorName])
				{
					// We have to close this ancestor. First we reinsert this tag...
					tagStack.push(tag);

					// ...then we add a new end tag for it
					addMagicEndTag(ancestor, tag.getPos());

					return true;
				}
			}
		}
	}

	return false;
}

/**
* Apply closeParent rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function closeParent(tag)
{
	if (!HINT.closeParent)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeParent)
		{
			var parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.closeParent[parentName])
			{
				// We have to close that parent. First we reinsert the tag...
				tagStack.push(tag);

				// ...then we add a new end tag for it
				addMagicEndTag(parent, tag.getPos());

				return true;
			}
		}
	}

	return false;
}

/**
* Apply the createChild rules associated with given tag
*
* @param {!Tag} tag Tag
*/
function createChild(tag)
{
	if (!HINT.createChild)
	{
		return;
	}

	var tagConfig = tagsConfig[tag.getName()];
	if (tagConfig.rules.createChild)
	{
		var priority = -1000,
			_text    = text.substr(pos),
			tagPos   = pos + _text.length - _text.replace(/^[ \n\r\t]+/, '').length;
		tagConfig.rules.createChild.forEach(function(tagName)
		{
			addStartTag(tagName, tagPos, 0, ++priority);
		});
	}
}

/**
* Apply fosterParent rules associated with given tag
*
* NOTE: this rule has the potential for creating an unbounded loop, either if a tag tries to
*       foster itself or two or more tags try to foster each other in a loop. We mitigate the
*       risk by preventing a tag from creating a child of itself (the parent still gets closed)
*       and by checking and increasing the currentFixingCost so that a loop of multiple tags
*       do not run indefinitely. The default tagLimit and nestingLimit also serve to prevent the
*       loop from running indefinitely
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function fosterParent(tag)
{
	if (!HINT.fosterParent)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.fosterParent)
		{
			var parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.fosterParent[parentName])
			{
				if (parentName !== tagName && currentFixingCost < maxFixingCost)
				{
					// Add a 0-width copy of the parent tag right after this tag, with a worse
					// priority and make it depend on this tag
					var child = addCopyTag(parent, tag.getPos() + tag.getLen(), 0, tag.getSortPriority() + 1);
					tag.cascadeInvalidationTo(child);
				}

				// Reinsert current tag
				tagStack.push(tag);

				// And finally close its parent with a priority that ensures it is processed
				// before this tag
				addMagicEndTag(parent, tag.getPos(), tag.getSortPriority() - 1);

				// Adjust the fixing cost to account for the additional tags/processing
				currentFixingCost += 4;

				return true;
			}
		}
	}

	return false;
}

/**
* Apply requireAncestor rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether this tag has an unfulfilled requireAncestor requirement
*/
function requireAncestor(tag)
{
	if (!HINT.requireAncestor)
	{
		return false;
	}

	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName];

	if (tagConfig.rules.requireAncestor)
	{
		var i = tagConfig.rules.requireAncestor.length;
		while (--i >= 0)
		{
			var ancestorName = tagConfig.rules.requireAncestor[i];
			if (cntOpen[ancestorName])
			{
				return false;
			}
		}

		logger.err('Tag requires an ancestor', {
			'requireAncestor' : tagConfig.rules.requireAncestor.join(', '),
			'tag'             : tag
		});

		return true;
	}

	return false;
}

//==========================================================================
// Tag processing
//==========================================================================

/**
* Create and add an end tag for given start tag at given position
*
* @param  {!Tag}    startTag Start tag
* @param  {!number} tagPos   End tag's position (will be adjusted for whitespace if applicable)
* @return {!Tag}
*/
function addMagicEndTag(startTag, tagPos)
{
	var tagName = startTag.getName();

	// Adjust the end tag's position if whitespace is to be minimized
	if (HINT.RULE_IGNORE_WHITESPACE && (startTag.getFlags() & RULE_IGNORE_WHITESPACE))
	{
		tagPos = getMagicPos(tagPos);
	}

	// Add a 0-width end tag that is paired with the given start tag
	var endTag = addEndTag(tagName, tagPos, 0);
	endTag.pairWith(startTag);

	return endTag;
}

/**
* Compute the position of a magic end tag, adjusted for whitespace
*
* @param  {!number} tagPos Rightmost possible position for the tag
* @return {!number}
*/
function getMagicPos(tagPos)
{
	// Back up from given position to the cursor's position until we find a character that
	// is not whitespace
	while (tagPos > pos && WHITESPACE.indexOf(text.charAt(tagPos - 1)) > -1)
	{
		--tagPos;
	}

	return tagPos;
}

/**
* Test whether given start tag is immediately followed by a closing tag
*
* @param  {!Tag} tag Start tag (including self-closing)
* @return {!boolean}
*/
function isFollowedByClosingTag(tag)
{
	return (!tagStack.length) ? false : tagStack[tagStack.length - 1].canClose(tag);
}

/**
* Process all tags in the stack
*/
function processTags()
{
	if (!tagStack.length)
	{
		return;
	}

	// Initialize the count tables
	for (var tagName in tagsConfig)
	{
		cntOpen[tagName]  = 0;
		cntTotal[tagName] = 0;
	}

	// Process the tag stack, close tags that were left open and repeat until done
	do
	{
		while (tagStack.length)
		{
			if (!tagStackIsSorted)
			{
				sortTags();
			}

			currentTag = tagStack.pop();
			processCurrentTag();
		}

		// Close tags that were left open
		openTags.forEach(function (startTag)
		{
			// NOTE: we add tags in hierarchical order (ancestors to descendants) but since
			//       the stack is processed in LIFO order, it means that tags get closed in
			//       the correct order, from descendants to ancestors
			addMagicEndTag(startTag, textLen);
		});
	}
	while (tagStack.length);
}

/**
* Process current tag
*/
function processCurrentTag()
{
	// Invalidate current tag if tags are disabled and current tag would not close the last open
	// tag and is not a system tag
	if ((context.flags & RULE_IGNORE_TAGS)
	 && !currentTag.canClose(openTags[openTags.length - 1])
	 && !currentTag.isSystemTag())
	{
		currentTag.invalidate();
	}

	var tagPos = currentTag.getPos(),
		tagLen = currentTag.getLen();

	// Test whether the cursor passed this tag's position already
	if (pos > tagPos && !currentTag.isInvalid())
	{
		// Test whether this tag is paired with a start tag and this tag is still open
		var startTag = currentTag.getStartTag();

		if (startTag && openTags.indexOf(startTag) >= 0)
		{
			// Create an end tag that matches current tag's start tag, which consumes as much of
			// the same text as current tag and is paired with the same start tag
			addEndTag(
				startTag.getName(),
				pos,
				Math.max(0, tagPos + tagLen - pos)
			).pairWith(startTag);

			// Note that current tag is not invalidated, it's merely replaced
			return;
		}

		// If this is an ignore tag, try to ignore as much as the remaining text as possible
		if (currentTag.isIgnoreTag())
		{
			var ignoreLen = tagPos + tagLen - pos;

			if (ignoreLen > 0)
			{
				// Create a new ignore tag and move on
				addIgnoreTag(pos, ignoreLen);

				return;
			}
		}

		// Skipped tags are invalidated
		currentTag.invalidate();
	}

	if (currentTag.isInvalid())
	{
		return;
	}

	if (currentTag.isIgnoreTag())
	{
		outputIgnoreTag(currentTag);
	}
	else if (currentTag.isBrTag())
	{
		// Output the tag if it's allowed, ignore it otherwise
		if (!HINT.RULE_PREVENT_BR || !(context.flags & RULE_PREVENT_BR))
		{
			outputBrTag(currentTag);
		}
	}
	else if (currentTag.isParagraphBreak())
	{
		outputText(currentTag.getPos(), 0, true);
	}
	else if (currentTag.isVerbatim())
	{
		outputVerbatim(currentTag);
	}
	else if (currentTag.isStartTag())
	{
		processStartTag(currentTag);
	}
	else
	{
		processEndTag(currentTag);
	}
}

/**
* Process given start tag (including self-closing tags) at current position
*
* @param {!Tag} tag Start tag (including self-closing)
*/
function processStartTag(tag)
{
	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName];

	// 1. Check that this tag has not reached its global limit tagLimit
	// 2. Execute this tag's filterChain, which will filter/validate its attributes
	// 3. Apply closeParent, closeAncestor and fosterParent rules
	// 4. Check for nestingLimit
	// 5. Apply requireAncestor rules
	//
	// This order ensures that the tag is valid and within the set limits before we attempt to
	// close parents or ancestors. We need to close ancestors before we can check for nesting
	// limits, whether this tag is allowed within current context (the context may change
	// as ancestors are closed) or whether the required ancestors are still there (they might
	// have been closed by a rule.)
	if (cntTotal[tagName] >= tagConfig.tagLimit)
	{
		logger.err(
			'Tag limit exceeded',
			{
				'tag'      : tag,
				'tagName'  : tagName,
				'tagLimit' : tagConfig.tagLimit
			}
		);
		tag.invalidate();

		return;
	}

	if (!filterTag(tag))
	{
		tag.invalidate();

		return;
	}

	if (fosterParent(tag) || closeParent(tag) || closeAncestor(tag))
	{
		// This tag parent/ancestor needs to be closed, we just return (the tag is still valid)
		return;
	}

	if (cntOpen[tagName] >= tagConfig.nestingLimit)
	{
		logger.err(
			'Nesting limit exceeded',
			{
				'tag'          : tag,
				'tagName'      : tagName,
				'nestingLimit' : tagConfig.nestingLimit
			}
		);
		tag.invalidate();

		return;
	}

	if (!tagIsAllowed(tagName))
	{
		var msg     = 'Tag is not allowed in this context',
			context = {'tag': tag, 'tagName': tagName};
		if (tag.getLen() > 0)
		{
			logger.warn(msg, context);
		}
		else
		{
			logger.debug(msg, context);
		}
		tag.invalidate();

		return;
	}

	if (requireAncestor(tag))
	{
		tag.invalidate();

		return;
	}

	// If this tag has an autoClose rule and it's not paired with an end tag or followed by an
	// end tag, we replace it with a self-closing tag with the same properties
	if (HINT.RULE_AUTO_CLOSE
	 && tag.getFlags() & RULE_AUTO_CLOSE
	 && !tag.getEndTag()
	 && !isFollowedByClosingTag(tag))
	{
		var newTag = new Tag(Tag.SELF_CLOSING_TAG, tagName, tag.getPos(), tag.getLen());
		newTag.setAttributes(tag.getAttributes());
		newTag.setFlags(tag.getFlags());

		tag = newTag;
	}

	if (HINT.RULE_TRIM_FIRST_LINE
	 && tag.getFlags() & RULE_TRIM_FIRST_LINE
	 && !tag.getEndTag()
	 && text.charAt(tag.getPos() + tag.getLen()) === "\n")
	{
		addIgnoreTag(tag.getPos() + tag.getLen(), 1);
	}

	// This tag is valid, output it and update the context
	outputTag(tag);
	pushContext(tag);

	// Apply the createChild rules if applicable
	createChild(tag);
}

/**
* Process given end tag at current position
*
* @param {!Tag} tag End tag
*/
function processEndTag(tag)
{
	var tagName = tag.getName();

	if (!cntOpen[tagName])
	{
		// This is an end tag with no start tag
		return;
	}

	/**
	* @type {!Array.<!Tag>} List of tags need to be closed before given tag
	*/
	var closeTags = [];

	// Iterate through all open tags from last to first to find a match for our tag
	var i = openTags.length;
	while (--i >= 0)
	{
		var openTag = openTags[i];

		if (tag.canClose(openTag))
		{
			break;
		}

		closeTags.push(openTag);
		++currentFixingCost;
	}

	if (i < 0)
	{
		// Did not find a matching tag
		logger.debug('Skipping end tag with no start tag', {'tag': tag});

		return;
	}

	// Only reopen tags if we haven't exceeded our "fixing" budget
	var keepReopening = HINT.RULE_AUTO_REOPEN && (currentFixingCost < maxFixingCost),
		reopenTags    = [];
	closeTags.forEach(function(openTag)
	{
		var openTagName = openTag.getName();

		// Test whether this tag should be reopened automatically
		if (keepReopening)
		{
			if (openTag.getFlags() & RULE_AUTO_REOPEN)
			{
				reopenTags.push(openTag);
			}
			else
			{
				keepReopening = false;
			}
		}

		// Find the earliest position we can close this open tag
		var tagPos = tag.getPos();
		if (HINT.RULE_IGNORE_WHITESPACE && openTag.getFlags() & RULE_IGNORE_WHITESPACE)
		{
			tagPos = getMagicPos(tagPos);
		}

		// Output an end tag to close this start tag, then update the context
		var endTag = new Tag(Tag.END_TAG, openTagName, tagPos, 0);
		endTag.setFlags(openTag.getFlags());
		outputTag(endTag);
		popContext();
	});

	// Output our tag, moving the cursor past it, then update the context
	outputTag(tag);
	popContext();

	// If our fixing budget allows it, peek at upcoming tags and remove end tags that would
	// close tags that are already being closed now. Also, filter our list of tags being
	// reopened by removing those that would immediately be closed
	if (closeTags.length && currentFixingCost < maxFixingCost)
	{
		/**
		* @type {number} Rightmost position of the portion of text to ignore
		*/
		var ignorePos = pos;

		i = tagStack.length;
		while (--i >= 0 && ++currentFixingCost < maxFixingCost)
		{
			var upcomingTag = tagStack[i];

			// Test whether the upcoming tag is positioned at current "ignore" position and it's
			// strictly an end tag (not a start tag or a self-closing tag)
			if (upcomingTag.getPos() > ignorePos
			 || upcomingTag.isStartTag())
			{
				break;
			}

			// Test whether this tag would close any of the tags we're about to reopen
			var j = closeTags.length;

			while (--j >= 0 && ++currentFixingCost < maxFixingCost)
			{
				if (upcomingTag.canClose(closeTags[j]))
				{
					// Remove the tag from the lists and reset the keys
					closeTags.splice(j, 1);

					if (reopenTags[j])
					{
						reopenTags.splice(j, 1);
					}

					// Extend the ignored text to cover this tag
					ignorePos = Math.max(
						ignorePos,
						upcomingTag.getPos() + upcomingTag.getLen()
					);

					break;
				}
			}
		}

		if (ignorePos > pos)
		{
			/**
			* @todo have a method that takes (pos,len) rather than a Tag
			*/
			outputIgnoreTag(new Tag(Tag.SELF_CLOSING_TAG, 'i', pos, ignorePos - pos));
		}
	}

	// Re-add tags that need to be reopened, at current cursor position
	reopenTags.forEach(function(startTag)
	{
		var newTag = addCopyTag(startTag, pos, 0);

		// Re-pair the new tag
		var endTag = startTag.getEndTag();
		if (endTag)
		{
			newTag.pairWith(endTag);
		}
	});
}

/**
* Update counters and replace current context with its parent context
*/
function popContext()
{
	var tag = openTags.pop();
	--cntOpen[tag.getName()];
	context = context.parentContext;
}

/**
* Update counters and replace current context with a new context based on given tag
*
* If given tag is a self-closing tag, the context won't change
*
* @param {!Tag} tag Start tag (including self-closing)
*/
function pushContext(tag)
{
	var tagName   = tag.getName(),
		tagFlags  = tag.getFlags(),
		tagConfig = tagsConfig[tagName];

	++cntTotal[tagName];

	// If this is a self-closing tag, the context remains the same
	if (tag.isSelfClosingTag())
	{
		return;
	}

	// Recompute the allowed tags
	var allowed = [];
	if (HINT.RULE_IS_TRANSPARENT && (tagFlags & RULE_IS_TRANSPARENT))
	{
		context.allowed.forEach(function(v, k)
		{
			allowed.push(tagConfig.allowed[k] & v);
		});
	}
	else
	{
		context.allowed.forEach(function(v, k)
		{
			allowed.push(tagConfig.allowed[k] & ((v & 0xFF00) | (v >> 8)));
		});
	}

	// Use this tag's flags as a base for this context and add inherited rules
	var flags = tagFlags | (context.flags & RULES_INHERITANCE);

	// RULE_DISABLE_AUTO_BR turns off RULE_ENABLE_AUTO_BR
	if (flags & RULE_DISABLE_AUTO_BR)
	{
		flags &= ~RULE_ENABLE_AUTO_BR;
	}

	++cntOpen[tagName];
	openTags.push(tag);
	context = {
		allowed       : allowed,
		flags         : flags,
		parentContext : context
	};
}

/**
* Return whether given tag is allowed in current context
*
* @param  {!string}  tagName
* @return {!boolean}
*/
function tagIsAllowed(tagName)
{
	var n = tagsConfig[tagName].bitNumber;

	return !!(context.allowed[n >> 3] & (1 << (n & 7)));
}

//==========================================================================
// Tag stack
//==========================================================================

/**
* Add a start tag
*
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @param  {number}  prio Tags' priority
* @return {!Tag}
*/
function addStartTag(name, pos, len, prio)
{
	return addTag(Tag.START_TAG, name, pos, len, prio || 0);
}

/**
* Add an end tag
*
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @param  {number}  prio Tags' priority
* @return {!Tag}
*/
function addEndTag(name, pos, len, prio)
{
	return addTag(Tag.END_TAG, name, pos, len, prio || 0);
}

/**
* Add a self-closing tag
*
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @param  {number}  prio Tags' priority
* @return {!Tag}
*/
function addSelfClosingTag(name, pos, len, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, name, pos, len, prio || 0);
}

/**
* Add a 0-width "br" tag to force a line break at given position
*
* @param  {!number} pos  Position of the tag in the text
* @param  {number}  prio Tags' priority
* @return {!Tag}
*/
function addBrTag(pos, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'br', pos, 0, prio || 0);
}

/**
* Add an "ignore" tag
*
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @param  {number}  prio Tags' priority
* @return {!Tag}
*/
function addIgnoreTag(pos, len, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'i', pos, Math.min(len, textLen - pos), prio || 0);
}

/**
* Add a paragraph break at given position
*
* Uses a zero-width tag that is actually never output in the result
*
* @param  {!number} pos  Position of the tag in the text
* @param  {number}  prio Tags' priority
* @return {!Tag}
*/
function addParagraphBreak(pos, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'pb', pos, 0, prio || 0);
}

/**
* Add a copy of given tag at given position and length
*
* @param  {!Tag}    tag Original tag
* @param  {!number} pos Copy's position
* @param  {!number} len Copy's length
* @param  {number}  prio Tags' priority
* @return {!Tag}         Copy tag
*/
function addCopyTag(tag, pos, len, prio)
{
	var copy = addTag(tag.getType(), tag.getName(), pos, len, tag.getSortPriority());
	copy.setAttributes(tag.getAttributes());

	return copy;
}

/**
* Add a tag
*
* @param  {!number} type Tag's type
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @param  {number}  prio Tags' priority
* @return {!Tag}
*/
function addTag(type, name, pos, len, prio)
{
	// Create the tag
	var tag = new Tag(type, name, pos, len, prio || 0);

	// Set this tag's rules bitfield
	if (tagsConfig[name])
	{
		tag.setFlags(tagsConfig[name].rules.flags);
	}

	// Invalidate this tag if it's an unknown tag, a disabled tag, if either of its length or
	// position is negative or if it's out of bounds
	if (!tagsConfig[name] && !tag.isSystemTag())
	{
		tag.invalidate();
	}
	else if (tagsConfig[name] && tagsConfig[name].isDisabled)
	{
		logger.warn(
			'Tag is disabled',
			{
				'tag'     : tag,
				'tagName' : name
			}
		);
		tag.invalidate();
	}
	else if (len < 0 || pos < 0 || pos + len > textLen)
	{
		tag.invalidate();
	}
	else
	{
		insertTag(tag);
	}

	return tag;
}

/**
* Insert given tag in the tag stack
*
* @param {!Tag} tag
*/
function insertTag(tag)
{
	if (!tagStackIsSorted)
	{
		tagStack.push(tag);
	}
	else
	{
		// Scan the stack and copy every tag to the next slot until we find the correct index
		var i = tagStack.length;
		while (i > 0 && compareTags(tagStack[i - 1], tag) > 0)
		{
			tagStack[i] = tagStack[i - 1];
			--i;
		}
		tagStack[i] = tag;
	}
}

/**
* Add a pair of tags
*
* @param  {!string} name     Name of the tags
* @param  {!number} startPos Position of the start tag
* @param  {!number} startLen Length of the start tag
* @param  {!number} endPos   Position of the start tag
* @param  {!number} endLen   Length of the start tag
* @param  {number}  prio     Start tag's priority (the end tag will be set to minus that value)
* @return {!Tag}             Start tag
*/
function addTagPair(name, startPos, startLen, endPos, endLen, prio)
{
	// NOTE: the end tag is added first to try to keep the stack in the correct order
	var endTag   = addEndTag(name, endPos, endLen, -prio || 0),
		startTag = addStartTag(name, startPos, startLen, prio || 0);
	startTag.pairWith(endTag);

	return startTag;
}

/**
* Add a tag that represents a verbatim copy of the original text
*
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @return {!Tag}
*/
function addVerbatim(pos, len, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'v', pos, len, prio || 0);
}

/**
* Sort tags by position and precedence
*/
function sortTags()
{
	tagStack.sort(compareTags);
	tagStackIsSorted = true;
}

/**
* sortTags() callback
*
* Tags are stored as a stack, in LIFO order. We sort tags by position _descending_ so that they
* are processed in the order they appear in the text.
*
* @param  {!Tag}    a First tag to compare
* @param  {!Tag}    b Second tag to compare
* @return {!number}
*/
function compareTags(a, b)
{
	var aPos = a.getPos(),
		bPos = b.getPos();

	// First we order by pos descending
	if (aPos !== bPos)
	{
		return bPos - aPos;
	}

	// If the tags start at the same position, we'll use their sortPriority if applicable. Tags
	// with a lower value get sorted last, which means they'll be processed first. IOW, -10 is
	// processed before 10
	if (a.getSortPriority() !== b.getSortPriority())
	{
		return b.getSortPriority() - a.getSortPriority();
	}

	// If the tags start at the same position and have the same priority, we'll sort them
	// according to their length, with special considerations for  zero-width tags
	var aLen = a.getLen(),
		bLen = b.getLen();

	if (!aLen || !bLen)
	{
		// Zero-width end tags are ordered after zero-width start tags so that a pair that ends
		// with a zero-width tag has the opportunity to be closed before another pair starts
		// with a zero-width tag. For example, the pairs that would enclose each of the letters
		// in the string "XY". Self-closing tags are ordered between end tags and start tags in
		// an attempt to keep them out of tag pairs
		if (!aLen && !bLen)
		{
			var order = {};
			order[Tag.END_TAG]          = 0;
			order[Tag.SELF_CLOSING_TAG] = 1;
			order[Tag.START_TAG]        = 2;

			return order[b.getType()] - order[a.getType()];
		}

		// Here, we know that only one of a or b is a zero-width tags. Zero-width tags are
		// ordered after wider tags so that they have a chance to be processed before the next
		// character is consumed, which would force them to be skipped
		return (aLen) ? -1 : 1;
	}

	// Here we know that both tags start at the same position and have a length greater than 0.
	// We sort tags by length ascending, so that the longest matches are processed first. If
	// their length is identical, the order is undefined as PHP's sort isn't stable
	return aLen - bLen;
}