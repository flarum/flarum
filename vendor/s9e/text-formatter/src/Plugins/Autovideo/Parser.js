var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	addTagPair(tagName, m[0][1], 0, m[0][1] + m[0][0].length, 0, -1).setAttribute(attrName, m[0][0]);
});