matches.forEach(function(m)
{
	// Linkify the trimmed URL
	linkifyUrl(m[0][1], trimUrl(m[0][0]));
});

/**
* Linkify given URL at given position
*
* @param {!number} tagPos URL's position in the text
* @param {!string} url    URL
*/
function linkifyUrl(tagPos, url)
{
	// Ensure that the anchor (scheme/www) is still there
	if (!/^www\.|^[^:]+:/i.test(url))
	{
		return;
	}

	// Create a zero-width end tag right after the URL
	var endTag = addEndTag(config.tagName, tagPos + url.length, 0);

	// If the URL starts with "www." we prepend "http://"
	if (url.charAt(3) === '.')
	{
		url = 'http://' + url;
	}

	// Create a zero-width start tag right before the URL, with a slightly worse priority to
	// allow specialized plugins to use the URL instead
	var startTag = addStartTag(config.tagName, tagPos, 0, 1);
	startTag.setAttribute(config.attrName, url);

	// Pair the tags together
	startTag.pairWith(endTag);
};

/**
* Remove trailing punctuation from given URL
*
* We remove most ASCII non-letters from the end of the string.
* Exceptions:
*  - dashes (some YouTube URLs end with a dash due to the video ID)
*  - equal signs (because of "foo?bar="),
*  - trailing slashes,
*  - closing parentheses are balanced separately.
*
* @param  {!string} url Original URL
* @return {!string}     Trimmed URL
*/
function trimUrl(url)
{
	while (1)
	{
		url = url.replace(/(?![-=\/)])[\s!-.:-@[-`{-~]+$/, '');
		if (url.substr(-1) === ')' && url.replace(/[^(]+/g, '').length < url.replace(/[^)]+/g, '').length)
		{
			url = url.substr(0, url.length - 1);
			continue;
		}
		break;
	}

	return url;
}