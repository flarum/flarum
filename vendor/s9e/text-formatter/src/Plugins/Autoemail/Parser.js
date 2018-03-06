var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	// Create a zero-width start tag right before the address
	var startTag = addStartTag(tagName, m[0][1], 0);
	startTag.setAttribute(attrName, m[0][0]);

	// Create a zero-width end tag right after the address
	var endTag = addEndTag(tagName, m[0][1] + m[0][0].length, 0);

	// Pair the tags together
	startTag.pairWith(endTag);
});