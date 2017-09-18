var crypto = require('crypto');
var defaults = require('lodash.defaults');
var through = require('through2');

var plugin = function(name, opt){
  var opts = defaults(opt || {}, {
    optimizeMemory: false
  });

  if (!plugin.caches[name]) {
    plugin.caches[name] = {};
  }

  var stream = through.obj(function(file, enc, callback){
    var contents = file.checksum;

    if (!contents) {
      if (file.isStream()) {
        this.push(file);
        return callback();
      }
      if (file.isBuffer()) {
        contents = file.contents.toString('utf8');

        // slower for each file
        // but good if you need to save on memory
        if (opts.optimizeMemory) {
          contents = crypto.createHash('md5').update(contents).digest('hex');
        }
      }
    }

    var cacheFile = plugin.caches[name][file.path];

    // hit - ignore it
    if (typeof cacheFile !== 'undefined' && cacheFile === contents) {
      callback();
      return;
    }

    // miss - add it and pass it through
    plugin.caches[name][file.path] = contents;
    this.push(file);
    callback();
  });
  return stream;
};

plugin.caches = {};

module.exports = plugin;
