matches.forEach(function(m)
{
	var url = m[0][0],
		pos = m[0][1],
		len = url.length,
		// Give that tag priority over other tags such as Autolink's
		tag = addSelfClosingTag(config.tagName, pos, len, -10);

	tag.setAttribute('url', url);
});