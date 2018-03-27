/**
* Test whether a given tag has at least one non-default attribute
*
* @param  {!Tag}     tag The original tag
* @return {!boolean}     Whether the tag contains an attribute not named "url"
*/
function (tag)
{
	for (var attrName in tag.getAttributes())
	{
		if (attrName !== 'url')
		{
			return true;
		}
	}

	return false;
}