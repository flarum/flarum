/**
* @constructor
*
* @param {!number} type     Tag's type
* @param {!string} name     Name of the tag
* @param {!number} pos      Position of the tag in the text
* @param {!number} len      Length of text consumed by the tag
* @param {number}  priority This tag's sorting tiebreaker
*/
function Tag(type, name, pos, len, priority)
{
	this.type = +type;
	this.name = name;
	this.pos  = +pos;
	this.len  = +len;
	if (typeof priority !== 'undefined')
	{
		this.sortPriority = +priority;
	}

	this.attributes = {};
	this.cascade    = [];

	// Invalidate this tag now if any value is not a number, they could wreck
	// havoc in other parts of the program
	if (isNaN(type + pos + len))
	{
		this.invalidate();
	}
}

/** @const */
Tag.START_TAG = 1;

/** @const */
Tag.END_TAG = 2;

/** @const */
Tag.SELF_CLOSING_TAG = 3;

/**
* @type {!Object} Dictionary of attributes
*/
Tag.prototype.attributes;

/**
* @type {!Array.<!Tag>} List of tags that are invalidated when this tag is invalidated
*/
Tag.prototype.cascade;

/**
* @type {Tag} End tag that unconditionally ends this start tag
*/
Tag.prototype.endTag;

/**
* @type {!boolean} Whether this tag is be invalid
*/
Tag.prototype.invalid = false;

/**
* @type {!number} Length of text consumed by this tag
*/
Tag.prototype.len;

/**
* @type {!string} Name of this tag
*/
Tag.prototype.name;

/**
* @type {!number} Position of this tag in the text
*/
Tag.prototype.pos;

/**
* @type {!number} Tiebreaker used when sorting identical tags
*/
Tag.prototype.sortPriority;

/**
* @type {Tag} Start tag that is unconditionally closed this end tag
*/
Tag.prototype.startTag;

/**
* @type {!number} Tag type
*/
Tag.prototype.type;

/**
* Add a set of flags to this tag's
*
* @param {!number} flags
*/
Tag.prototype.addFlags = function(flags)
{
	this.flags |= flags;
};

/**
* Set given tag to be invalidated if this tag is invalidated
*
* @param {!Tag} tag
*/
Tag.prototype.cascadeInvalidationTo = function(tag)
{
	this.cascade.push(tag);

	// If this tag is already invalid, cascade it now
	if (this.invalid)
	{
		tag.invalidate();
	}
};

/**
* Invalidate this tag, as well as tags bound to this tag
*/
Tag.prototype.invalidate = function()
{
	// If this tag is already invalid, we can return now. This prevent infinite loops
	if (this.invalid)
	{
		return;
	}

	this.invalid = true;

	this.cascade.forEach(
		/**
		* @param {!Tag} tag
		*/
		function(tag)
		{
			tag.invalidate();
		}
	);
}

/**
* Pair this tag with given tag
*
* @param {!Tag} tag
*/
Tag.prototype.pairWith = function(tag)
{
	if (this.name === tag.name)
	{
		if (this.type === Tag.START_TAG
		 && tag.type  === Tag.END_TAG
		 && tag.pos   >=  this.pos)
		{
			this.endTag  = tag;
			tag.startTag = this;

			this.cascadeInvalidationTo(tag);
		}
		else if (this.type === Tag.END_TAG
		      && tag.type  === Tag.START_TAG
		      && tag.pos   <=  this.pos)
		{
			this.startTag = tag;
			tag.endTag    = this;
		}
	}
}

/**
* Remove a set of flags from this tag's
*
* @param {!number} flags
*/
Tag.prototype.removeFlags = function(flags)
{
	this.flags &= ~flags;
};

/**
* Set the bitfield of boolean rules that apply to this tag
*
* @param  {!number} flags Bitfield of boolean rules that apply to this tag
*/
Tag.prototype.setFlags = function(flags)
{
	this.flags = flags;
}

/**
* Set this tag's tiebreaker
*
* @param  {!number} sortPriority
*/
Tag.prototype.setSortPriority = function(sortPriority)
{
	this.sortPriority = sortPriority;
}

//==========================================================================
// Getters
//==========================================================================

/**
* Return this tag's attributes
*
* @return {!Object}
*/
Tag.prototype.getAttributes = function()
{
	var attributes = {};
	for (var attrName in this.attributes)
	{
		attributes[attrName] = this.attributes[attrName];
	}

	return attributes;
}

/**
* Return this tag's end tag
*
* @return {Tag} This tag's end tag
*/
Tag.prototype.getEndTag = function()
{
	return this.endTag;
}

/**
* Return the bitfield of boolean rules that apply to this tag
*
* @return {!number}
*/
Tag.prototype.getFlags = function()
{
	return this.flags;
}

/**
* Return the length of text consumed by this tag
*
* @return {!number}
*/
Tag.prototype.getLen = function()
{
	return this.len;
}

/**
* Return this tag's name
*
* @return {!string}
*/
Tag.prototype.getName = function()
{
	return this.name;
}

/**
* Return this tag's position
*
* @return {!number}
*/
Tag.prototype.getPos = function()
{
	return this.pos;
}

/**
* Return this tag's tiebreaker
*
* @return {!number}
*/
Tag.prototype.getSortPriority = function()
{
	return this.sortPriority;
}

/**
* Return this tag's start tag
*
* @return {Tag} This tag's start tag
*/
Tag.prototype.getStartTag = function()
{
	return this.startTag;
}

/**
* Return this tag's type
*
* @return {!number}
*/
Tag.prototype.getType = function()
{
	return this.type;
}

//==========================================================================
// Tag's status
//==========================================================================

/**
* Test whether this tag can close given start tag
*
* @param  {!Tag} startTag
* @return {!boolean}
*/
Tag.prototype.canClose = function(startTag)
{
	if (this.invalid
	 || this.name !== startTag.name
	 || startTag.type !== Tag.START_TAG
	 || this.type !== Tag.END_TAG
	 || this.pos < startTag.pos
	 || (this.startTag && this.startTag !== startTag)
	 || (startTag.endTag && startTag.endTag !== this))
	{
		return false;
	}

	return true;
}

/**
* Test whether this tag is a br tag
*
* @return {!boolean}
*/
Tag.prototype.isBrTag = function()
{
	return (this.name === 'br');
}

/**
* Test whether this tag is an end tag (self-closing tags inclusive)
*
* @return {!boolean}
*/
Tag.prototype.isEndTag = function()
{
	return !!(this.type & Tag.END_TAG);
}

/**
* Test whether this tag is an ignore tag
*
* @return {!boolean}
*/
Tag.prototype.isIgnoreTag = function()
{
	return (this.name === 'i');
}

/**
* Test whether this tag is invalid
*
* @return {!boolean}
*/
Tag.prototype.isInvalid = function()
{
	return this.invalid;
}

/**
* Test whether this tag represents a paragraph break
*
* @return {!boolean}
*/
Tag.prototype.isParagraphBreak = function()
{
	return (this.name === 'pb');
}

/**
* Test whether this tag is a self-closing tag
*
* @return {!boolean}
*/
Tag.prototype.isSelfClosingTag = function()
{
	return (this.type === Tag.SELF_CLOSING_TAG);
}

/**
* Test whether this tag is a special tag: "br", "i", "pb" or "v"
*
* @return {!boolean}
*/
Tag.prototype.isSystemTag = function()
{
	return ('br i pb v'.indexOf(this.name) > -1);
}

/**
* Test whether this tag is a start tag (self-closing tags inclusive)
*
* @return {!boolean}
*/
Tag.prototype.isStartTag = function()
{
	return !!(this.type & Tag.START_TAG);
}

/**
* Test whether this tag represents verbatim text
*
* @return {!boolean}
*/
Tag.prototype.isVerbatim = function()
{
	return (this.name === 'v');
}

//==========================================================================
// Attributes handling
//==========================================================================

/**
* Return the value of given attribute
*
* @param  {!string} attrName
* @return {!string}
*/
Tag.prototype.getAttribute = function(attrName)
{
	return this.attributes[attrName];
}

/**
* Return whether given attribute is set
*
* @param  {!string} attrName
* @return {!boolean}
*/
Tag.prototype.hasAttribute = function(attrName)
{
	return (attrName in this.attributes);
}

/**
* Remove given attribute
*
* @param {!string} attrName
*/
Tag.prototype.removeAttribute = function(attrName)
{
	delete this.attributes[attrName];
}

/**
* Set the value of an attribute
*
* @param {!string} attrName  Attribute's name
* @param {*}       attrValue Attribute's value
*/
Tag.prototype.setAttribute = function(attrName, attrValue)
{
	this.attributes[attrName] = attrValue;
}

/**
* Set all of this tag's attributes at once
*
* @param {!Object} attributes
*/
Tag.prototype.setAttributes = function(attributes)
{
	this.attributes = {}

	for (var attrName in attributes)
	{
		this.attributes[attrName] = attributes[attrName];
	}
}