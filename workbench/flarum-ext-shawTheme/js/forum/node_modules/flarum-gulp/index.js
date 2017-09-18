var path = require('path');
var gulp = require('gulp');
var concat = require('gulp-concat');
var babel = require('gulp-babel');
var cached = require('gulp-cached');
var remember = require('gulp-remember');
var order = require('gulp-order');
var streamqueue = require('streamqueue');
var file = require('gulp-file');
var babelCore = require('babel-core');

function handleError(e) {
  console.log(e.toString());
  this.emit('end');
}

module.exports = function(options) {
  options = options || {};

  options.files = options.files || [];
  options.modules = options.modules || {};
  options.outputFile = options.outputFile || 'dist/extension.js';

  gulp.task('default', function() {
    var stream = streamqueue({objectMode: true});

    if (options.includeHelpers) {
      stream.queue(file('helpers.js', babelCore.buildExternalHelpers(null, 'global'), {src: true}));
    }

    stream.queue(gulp.src(options.files));

    for (var prefix in options.modules) {
      var modules = options.modules[prefix];

      stream.queue(
        gulp.src(modules)
          .pipe(order(Array.isArray(modules) ? modules : [modules]))
          .pipe(cached('modules'))
          .pipe(babel({
            presets: [require('babel-preset-es2015'), require('babel-preset-react')],
            plugins: [
              [require('babel-plugin-transform-react-jsx'), {'pragma': 'm'}],
              require('babel-plugin-transform-es2015-modules-systemjs'),
              require('babel-plugin-transform-object-assign'),
              require('babel-plugin-external-helpers')
            ],
            moduleIds: true,
            moduleRoot: prefix
          }))
          .on('error', handleError)
          .pipe(remember('modules'))
      );
    }

    return stream.done()
      .pipe(concat(path.basename(options.outputFile), {newLine: ';\n'}))
      .pipe(gulp.dest(path.dirname(options.outputFile)));
  });

  gulp.task('watch', ['default'], function () {
    gulp.watch(options.files, ['default']);

    for (var prefix in options.modules) {
      var watcher = gulp.watch(options.modules[prefix], ['default']);

      watcher.on('change', function (event) {
        if (event.type === 'deleted') {
          delete cached.caches['modules' + prefix][event.path];
          remember.forget('modules' + prefix, event.path);
        }
      });
    }
  });
};
