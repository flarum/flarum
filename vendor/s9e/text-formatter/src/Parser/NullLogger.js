/**@constructor*/function Logger(){}Logger.prototype={add:/**
* Add a log entry
*
* @param  {!string}  type    Log type
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
function(type, msg, context){},clear:/**
* Clear the log
*/
function(){},setAttribute:/**
* Record the name of the attribute being processed
*
* @param  {!string} attrName
*/
function(attrName){},setTag:/**
* Record the tag being processed
*
* @param  {!Tag} tag
*/
function(tag){},unsetAttribute:/**
* Unset the name of the attribute being processed
*/
function(){},unsetTag:/**
* Unset the tag being processed
*/
function(){},debug:/**
* Add a "debug" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
function(msg, context){},err:/**
* Add an "err" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
function(msg, context){},info:/**
* Add an "info" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
function(msg, context){},warn:/**
* Add a "warn" type log entry
*
* @param  {!string}  msg     Log message
* @param  {!Object=} context Log context
*/
function(msg, context){}}