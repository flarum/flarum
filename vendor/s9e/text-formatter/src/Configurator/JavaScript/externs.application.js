/*
 * Copyright 2008 The Closure Compiler Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// This file was auto-generated.
// See https://github.com/google/closure-compiler for the original source.
// See https://github.com/s9e/TextFormatter/blob/master/scripts/generateExterns.php for details.

/**
 * @const
 */
var punycode = {};
/**
 * @param {string} domain
 * @return {string}
 */
punycode.toASCII;
/** @constructor */
function XSLTProcessor() {}
/**
 * @type {string}
 * @implicitCast
 */
Element.prototype.innerHTML;
/**
 * @constructor
 */
function DOMParser() {}
/**
 * @param {string} src The UTF16 string to be parsed.
 * @param {string} type The content type of the string.
 * @return {Document}
 */
DOMParser.prototype.parseFromString = function(src, type) {};
/**
 * @type {!Window}
 */
var window;
/**
 * @constructor
 * @extends {Node}
 */
function Document() {}
/**
 * @return {!DocumentFragment}
 * @nosideeffects
 */
Document.prototype.createDocumentFragment = function() {};
/**
 * @param {string} tagName
 * @param {string=} opt_typeExtension
 * @return {!Element}
 * @nosideeffects
 */
Document.prototype.createElement = function(tagName, opt_typeExtension) {};
/**
 * @constructor
 * @extends {Node}
 */
function DocumentFragment() {}
/**
 * @constructor
 * @implements {IObject<(string|number), T>}
 * @implements {IArrayLike<T>}
 * @implements {Iterable<T>}
 * @template T
 */
function NamedNodeMap() {}
/**
 * @param {number} index
 * @return {Node}
 * @nosideeffects
 */
NamedNodeMap.prototype.item = function(index) {};
/**
 * @type {number}
 */
NamedNodeMap.prototype.length;
/**
 * @constructor
 */
function Node() {}
/**
 * @param {Node} newChild
 * @return {Node}
 */
Node.prototype.appendChild = function(newChild) {};
/**
 * @type {!NodeList<!Node>}
 */
Node.prototype.childNodes;
/**
 * @param {boolean} deep
 * @return {!Node}
 * @nosideeffects
 */
Node.prototype.cloneNode = function(deep) {};
/**
 * @type {Node}
 */
Node.prototype.firstChild;
/**
 * @param {Node} newChild
 * @param {Node} refChild
 * @return {!Node}
 */
Node.prototype.insertBefore = function(newChild, refChild) {};
/**
 * @type {string}
 */
Node.prototype.nodeName;
/**
 * @type {number}
 */
Node.prototype.nodeType;
/**
 * @type {string}
 */
Node.prototype.nodeValue;
/**
 * @type {Document}
 */
Node.prototype.ownerDocument;
/**
 * @type {Node}
 */
Node.prototype.parentNode;
/**
 * @param {Node} oldChild
 * @return {!Node}
 */
Node.prototype.removeChild = function(oldChild) {};
/**
 * @constructor
 * @implements {IArrayLike<T>}
 * @implements {Iterable<T>}
 * @template T
 */
function NodeList() {}
/**
 * @type {number}
 */
NodeList.prototype.length;
/**
 * @constructor
 * @extends {Node}
 */
function Element() {}
/**
 * @constructor
 */
function Window() {}
/**
 * @param {Node} externalNode
 * @param {boolean} deep
 * @return {Node}
 */
Document.prototype.importNode = function(externalNode, deep) {};
/**
 * @constructor
 * @extends {Document}
 */
function HTMLDocument() {}
/**
 * @constructor
 * @extends {Element}
 */
function HTMLElement() {}
/**
 * @param {?string} namespaceURI
 * @param {string} localName
 * @return {string}
 * @nosideeffects
 */
Element.prototype.getAttributeNS = function(namespaceURI, localName) {};
/**
 * @param {?string} namespaceURI
 * @param {string} localName
 * @return {boolean}
 * @nosideeffects
 */
Element.prototype.hasAttributeNS = function(namespaceURI, localName) {};
/**
 * @param {?string} namespaceURI
 * @param {string} localName
 * @return {undefined}
 */
Element.prototype.removeAttributeNS = function(namespaceURI, localName) {};
/**
 * @param {?string} namespaceURI
 * @param {string} qualifiedName
 * @param {string|number|boolean} value Values are converted to strings with
 * @return {undefined}
 */
Element.prototype.setAttributeNS = function(namespaceURI, qualifiedName, value) {};
/**
 * @param {Node} arg
 * @return {boolean}
 * @nosideeffects
 */
Node.prototype.isEqualNode = function(arg) {};
/**
 * @type {string}
 */
Node.prototype.namespaceURI;
/**
 * @type {string}
 * @implicitCast
 */
Node.prototype.textContent;
/**
 * @type {!HTMLDocument}
 * @const
 */
var document;
