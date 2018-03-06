var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	if (isAllowed(m[0][0]))
	{
		return;
	}

	// NOTE: unlike the PCRE regexp, the JavaScript regexp can consume an extra character at the
	//       start of the match, so we have to adjust the position and length accordingly
	var offset = /^\W/.test(m[0][0]) ? 1 : 0,
		word   = m[0][0].substr(offset),
		tag    = addSelfClosingTag(tagName, m[0][1] + offset, word.length);

	if (HINT.CENSOR_HAS_REPLACEMENTS && config.replacements)
	{
		for (var i = 0; i < config.replacements.length; ++i)
		{
			var regexp      = config.replacements[i][0],
				replacement = config.replacements[i][1];

			if (regexp.test(word))
			{
				tag.setAttribute(attrName, replacement);
				break;
			}
		}
	}
});

/**
* Test whether given word is allowed
*
* @param  {!string}  word
* @return {!boolean}
*/
function isAllowed(word)
{
	return (HINT.CENSOR_HAS_ALLOWED && config.allowed && config.allowed.test(word));
}