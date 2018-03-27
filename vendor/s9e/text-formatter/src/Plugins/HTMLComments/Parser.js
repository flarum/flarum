var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	// Decode HTML entities
	var content = html_entity_decode(m[0][0].substr(4, m[0][0].length - 7));

	// Remove angle brackets from the content
	content = content.replace(/[<>]/g, '');

	// Remove the illegal sequence "--" from the content
	content = content.replace(/--/g, '');

	addSelfClosingTag(tagName, m[0][1], m[0][0].length).setAttribute(attrName, content);
});