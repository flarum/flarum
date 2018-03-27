/**
* @constructor
*/
function Logger()
{
}

/**
* @type {string} Name of the attribute being processed
*/
Logger.prototype.attrName;

/**
* @type {!Object.<string,!Array>} 2D array of [<log type> => [<callbacks>]]
*/
Logger.prototype.callbacks = {};

/**
* @type {!Array.<!Array>} Log entries in the form [[<type>,<msg>,<context>]]
*/
Logger.prototype.logs = [];

/**
* @type {Tag} Tag being processed
*/
Logger.prototype.tag;

/**
* Add a log entry
*
* @param  {!string}  type    Log type
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
Logger.prototype.add = function(type, msg, context)
{
	context = context || {};

	if (!('attrName' in context) && this.attrName)
	{
		context['attrName'] = this.attrName;
	}

	if (!('tag' in context) && this.tag)
	{
		context['tag'] = this.tag;
	}

	// Execute callbacks
	if (this.callbacks[type])
	{
		this.callbacks[type].forEach(function(callback)
		{
			callback(msg, context);
		});
	}

	this.logs.push([type, msg, context]);
}

/**
* Clear the log
*/
Logger.prototype.clear = function()
{
	this.logs = [];
	this.unsetAttribute();
	this.unsetTag();
}

/**
* Return the logs
*
* @return {!Object}
*/
Logger.prototype['get'] = function()
{
	return this.logs;
}

/**
* Attach a callback to be executed when a message of given type is logged
*
* @param {!string}   type     Log type
* @param {!Function} callback Callback
*/
Logger.prototype['on'] = function(type, callback)
{
	this.callbacks[type].push(callback);
}

/**
* Record the name of the attribute being processed
*
* @param  {!string} attrName
*/
Logger.prototype.setAttribute = function(attrName)
{
	this.attrName = attrName;
}

/**
* Record the tag being processed
*
* @param  {!Tag} tag
*/
Logger.prototype.setTag = function(tag)
{
	this.tag = tag;
}

/**
* Unset the name of the attribute being processed
*/
Logger.prototype.unsetAttribute = function()
{
	delete this.attrName;
}

/**
* Unset the tag being processed
*/
Logger.prototype.unsetTag = function()
{
	delete this.tag;
}

//==========================================================================
// Log levels
//==========================================================================

/**
* Add a "debug" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
Logger.prototype.debug = function(msg, context)
{
	this.add('debug', msg, context);
}

/**
* Add an "err" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
Logger.prototype.err = function(msg, context)
{
	this.add('err', msg, context);
}

/**
* Add an "info" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
Logger.prototype.info = function(msg, context)
{
	this.add('info', msg, context);
}

/**
* Add a "warn" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
Logger.prototype.warn = function(msg, context)
{
	this.add('warn', msg, context);
}