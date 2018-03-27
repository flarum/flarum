var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	var entity = m[0][0],
		chr    = html_entity_decode(entity);

	if (chr === entity || chr.charCodeAt(0) < 32)
	{
		// If the entity was not decoded, we assume it's not valid and we ignore it.
		// Same thing if it's a control character
		return;
	}

	addSelfClosingTag(tagName, m[0][1], entity.length).setAttribute(attrName, chr);
});