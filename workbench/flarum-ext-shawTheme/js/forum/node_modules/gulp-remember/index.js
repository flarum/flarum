'use strict';

var through = require('through2'),
    util = require('gulp-util'),
    pluginName = 'gulp-remember', // name of our plugin for error logging purposes
    caches = {}, // will hold named file caches
    defaultName = '_default'; // name to give a cache if not provided

/**
 * Return a through stream that will:
 *   1. Remember all files that ever pass through it.
 *   2. Add all remembered files back into the stream when not present.
 * @param cacheName {string} Name to give your cache.
 *                           Caches with different names can know about different sets of files.
 */
function gulpRemember(cacheName) {
  var cache; // the files we've ever put our hands on in the current stream

  if (cacheName !== undefined && typeof cacheName !== 'number' && typeof cacheName !== 'string') {
    throw new util.PluginError(pluginName, 'Usage: require("gulp-remember")(name); where name is undefined, number or string');
  }
  cacheName = cacheName || defaultName; // maybe need to use a default cache
  caches[cacheName] = caches[cacheName] || {}; // maybe initialize the named cache
  cache = caches[cacheName];

  function transform(file, enc, callback) {
    var fileKey = file.path.toLowerCase();
    cache[fileKey] = file; // add file to our cache
    callback();
  }

  function flush(callback) {
    // add all files we've ever seen back into the stream
    for (var key in cache) {
      if (cache.hasOwnProperty(key)) {
        this.push(cache[key]); // add this file back into the current stream
      }
    }
    callback();
  }

  return through.obj(transform, flush);
}

/**
 * Forget about a file.
 * A warning is logged if either the named cache or file do not exist.
 *
 * @param cacheName {string} name of the cache from which to drop the file
 * @param path {string} path of the file to forget
 */
gulpRemember.forget = function (cacheName, path) {
  if (arguments.length === 1) {
    path = cacheName;
    cacheName = defaultName;
  }
  path = path.toLowerCase();
  if (typeof cacheName !== 'number' && typeof cacheName !== 'string') {
    throw new util.PluginError(pluginName, 'Usage: require("gulp-remember").forget(cacheName, path); where cacheName is undefined, number or string and path is a string');
  }
  if (caches[cacheName] === undefined) {
    util.log(pluginName, '- .forget() warning: cache ' + cacheName + ' not found');
  } else if (caches[cacheName][path] === undefined) {
    util.log(pluginName, '- .forget() warning: file ' + path + ' not found in cache ' + cacheName);
  } else {
    delete caches[cacheName][path];
  }
};

/**
 * Forget all files in one cache.
 * A warning is logged if the cache does not exist.
 *
 * @param cacheName {string} name of the cache to wipe
 */
gulpRemember.forgetAll = function (cacheName) {
  if (arguments.length === 0) {
    cacheName = defaultName;
  }
  if (typeof cacheName !== 'number' && typeof cacheName !== 'string') {
    throw new util.PluginError(pluginName, 'Usage: require("gulp-remember").forgetAll(cacheName); where cacheName is undefined, number or string');
  }
  if (caches[cacheName] === undefined) {
    util.log(pluginName, '- .forget() warning: cache ' + cacheName + ' not found');
  } else {
    caches[cacheName] = {};
  }
}

/**
 * Return a raw cache by name.
 * Useful for checking state. Manually adding or removing files is NOT recommended.
 *
 * @param cacheName {string} name of the cache to retrieve
 */
gulpRemember.cacheFor = function (cacheName) {
  if (arguments.length === 0) {
    cacheName = defaultName;
  }
  if (typeof cacheName !== 'number' && typeof cacheName !== 'string') {
    throw new util.PluginError(pluginName, 'Usage: require("gulp-remember").cacheFor(cacheName); where cacheName is undefined, number or string');
  }
  return caches[cacheName];
}

module.exports = gulpRemember;
