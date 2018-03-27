config.generics.forEach(function(entry)
{
	var tagName        = entry[0],
		regexp         = entry[1],
		passthroughIdx = entry[2],
		map            = entry[3],
		m;

	// Reset the regexp
	regexp.lastIndex = 0;

	while (m = regexp.exec(text))
	{
		var startTagPos = m['index'],
			matchLen    = m[0].length,
			tag;

		if (HINT.PREG_HAS_PASSTHROUGH && passthroughIdx && m[passthroughIdx] !== '')
		{
			// Compute the position and length of the start tag, end tag, and the content in
			// between. m.index gives us the position of the start tag but we don't know its length.
			// We use indexOf() to locate the content part so that we know how long the start tag
			// is. It is an imperfect solution but it should work well enough in most cases.
			var contentPos  = text.indexOf(m[passthroughIdx], startTagPos),
				contentLen  = m[passthroughIdx].length,
				startTagLen = contentPos - startTagPos,
				endTagPos   = contentPos + contentLen,
				endTagLen   = matchLen - (startTagLen + contentLen);

			tag = addTagPair(tagName, startTagPos, startTagLen, endTagPos, endTagLen, -100);
		}
		else
		{
			tag = addSelfClosingTag(tagName, startTagPos, matchLen, -100);
		}

		map.forEach(function(attrName, i)
		{
			// NOTE: subpatterns with no name have an empty entry to preserve the array indices
			if (attrName && typeof m[i] !== 'undefined')
			{
				tag.setAttribute(attrName, m[i]);
			}
		});
	}
});