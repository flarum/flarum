**Gulp wrapper for Flarum JavaScript compilation.**

This package sets up a [Gulp](http://gulpjs.com/) program that compiles JavaScript for use in Flarum. Example usage:

```js
// Gulpfile.js
var gulp = require('flarum-gulp');

gulp({
  files: [
    'bower_components/foo/bar.js'
  ],
  modules: {
    'vendor/package': 'src/**/*.js'
  }
});
```

```bash
$ gulp         # compile
$ gulp watch   # compile and watch for changes
```

## Options

* `files` An array of individual files to concatenate.
* `modules` A map of module prefixes to their source files.
    * Modules are transpiled to ES5 using Babel, including `Object.assign`.
    * JSX is converted into Mithril's `m` syntax.
* `outputFile` The resulting file to write to. Defaults to `dist/extension.js`.
