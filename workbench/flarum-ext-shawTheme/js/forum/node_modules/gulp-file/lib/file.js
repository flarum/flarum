var gutil = require('gulp-util')
  , through = require('through2');

/**
 * Create vinyl file from string or buffer and add to gulp stream.
 *
 * @param {String} name
 * @param {String|Buffer} source
 * @param {Object=} options
 * @param {Boolean=false} options.src
 * @return {stream.Transform}
 * @api public
 */
module.exports = function(name, source, options) {
  var file = new gutil.File({
    cwd: "",
    base: "",
    path: name,
    contents: ((source instanceof Buffer) ? source : new Buffer(source))
  });

  var stream = through.obj(function(file, enc, callback) {
    this.push(file);

    return callback();
  });

  stream.write(file);

  if (options && options.src) {
    stream.end();
  }

  return stream;
};
