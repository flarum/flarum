/**
* @param  {!string} str
* @return {!string}
*/
function html_entity_decode(str)
{
	var b = document.createElement('b');

	html_entity_decode = function (str)
	{
		// We escape left brackets so that we don't inadvertently evaluate some nasty HTML such as
		// <img src=... onload=evil() />
		b.innerHTML = str.replace(/</g, '&lt;');

		return b.textContent;
	}

	return html_entity_decode(str);
}

/**
* @param  {!string} str
* @return {!string}
*/
function htmlspecialchars_compat(str)
{
	var t = {
		'<' : '&lt;',
		'>' : '&gt;',
		'&' : '&amp;',
		'"' : '&quot;'
	};
	return str.replace(
		/[<>&"]/g,
		/**
		* @param {!string} c
		*/
		function(c)
		{
			return t[c];
		}
	);
}

/**
* @param  {!string} str
* @return {!string}
*/
function htmlspecialchars_noquotes(str)
{
	var t = {
		'<' : '&lt;',
		'>' : '&gt;',
		'&' : '&amp;'
	};
	return str.replace(
		/[<>&]/g,
		/**
		* @param {!string} c
		*/
		function(c)
		{
			return t[c];
		}
	);
}

/**
* @return {!boolean}
*/
function returnFalse()
{
	return false;
}

/**
* @return {!boolean}
*/
function returnTrue()
{
	return true;
}