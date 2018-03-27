/**
* @param  {!Tag}     tag   The original tag
* @param  {!Object}  sites Map of [host => siteId]
* @return {!boolean}       Always false
*/
function (tag, sites)
{
	function in_array(needle, haystack)
	{
		var k;
		for (k in haystack)
		{
			if (haystack[k] === needle)
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Filter a MEDIA tag
	*
	* This will always invalidate the original tag, and possibly replace it with the tag that
	* corresponds to the media site
	*
	* @param  {!Tag}     tag   The original tag
	* @param  {!Object}  sites Map of [host => siteId]
	* @return {!boolean}       Always false
	*/
	function filterTag(tag, sites)
	{
		if (tag.hasAttribute('site'))
		{
			addTagFromMediaId(tag, sites);
		}
		else if (tag.hasAttribute('url'))
		{
			addTagFromMediaUrl(tag, sites);
		}

		return false;
	}

	/**
	* Add a site tag
	*
	* @param {!Tag}    tag    The original tag
	* @param {!string} siteId Site ID
	*/
	function addSiteTag(tag, siteId)
	{
		var endTag = tag.getEndTag() || tag;

		// Compute the boundaries of our new tag
		var lpos = tag.getPos(),
			rpos = endTag.getPos() + endTag.getLen();

		// Create a new tag and copy this tag's attributes and priority
		addTagPair(siteId.toUpperCase(), lpos, 0, rpos, 0, tag.getSortPriority()).setAttributes(tag.getAttributes());
	}

	/**
	* Add a media site tag based on the attributes of a MEDIA tag
	*
	* @param {!Tag}    tag   The original tag
	* @param {!Object} sites Map of [host => siteId]
	*/
	function addTagFromMediaId(tag, sites)
	{
		var siteId = tag.getAttribute('site').toLowerCase();
		if (in_array(siteId, sites))
		{
			addSiteTag(tag, siteId);
		}
	}

	/**
	* Add a media site tag based on the url attribute of a MEDIA tag
	*
	* @param {!Tag}    tag   The original tag
	* @param {!Object} sites Map of [host => siteId]
	*/
	function addTagFromMediaUrl(tag, sites)
	{
		// Capture the scheme and (if applicable) host of the URL
		var p = /^(?:([^:]+):)?(?:\/\/([^\/]+))?/.exec(tag.getAttribute('url')), siteId;
		if (p[1] && sites[p[1] + ':'])
		{
			siteId = sites[p[1] + ':'];
		}
		else if (p[2])
		{
			siteId = findSiteIdByHost(p[2], sites);
		}

		if (siteId)
		{
			addSiteTag(tag, siteId);
		}
	}

	/**
	* Match a given host to a site ID
	*
	* @param  {!string}          host  Host
	* @param  {!Object}          sites Map of [host => siteId]
	* @return {!string|!boolean}       Site ID or FALSE
	*/
	function findSiteIdByHost(host, sites)
	{
		// Start with the full host then pop domain labels off the start until we get a match
		do
		{
			if (sites[host])
			{
				return sites[host];
			}

			var pos = host.indexOf('.');
			if (pos < 0)
			{
				break;
			}

			host = host.substr(1 + pos);
		}
		while (host > '');

		return false;
	}

	return filterTag(tag, sites);
}