matches.forEach(function(m)
{
	if (HINT.EMOTICONS_NOT_AFTER && config.notAfter && m[0][1] && config.notAfter.test(text.charAt(m[0][1] - 1)))
	{
		return;
	}

	addSelfClosingTag(config.tagName, m[0][1], m[0][0].length);
});