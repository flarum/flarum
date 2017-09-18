[![Build Status](https://travis-ci.org/contra/gulp-cached.png?branch=master)](https://travis-ci.org/contra/gulp-cached)

[![NPM version](https://badge.fury.io/js/gulp-cached.png)](http://badge.fury.io/js/gulp-cached)

## Information

<table>
<tr>
<td>Package</td><td>gulp-cached</td>
</tr>
<tr>
<td>Description</td>
<td>A simple in-memory file cache for gulp</td>
</tr>
<tr>
<td>Node Version</td>
<td>>= 0.9</td>
</tr>
</table>

## Usage

This keeps an in-memory cache of files (and their contents) that have passed through it. If a file has already passed through on the last run it will not be passed downstream. This means you only process what you need and save time + resources.

Take this example:

```javascript
var cache = require('gulp-cached');

gulp.task('lint', function(){
  return gulp.src('files/*.js')
    .pipe(cache('linting'))
    .pipe(jshint())
    .pipe(jshint.reporter())
});

gulp.task('watch', function(){
  gulp.watch('files/*.js', ['lint']);
});

gulp.task('default', ['watch','lint']);
```

- User saves `files/a.js` and the `lint` task is called
  - the files do not exist in the cache yet
  - `files/a.js` and `files/b.js` are linted
- User saves `files/b.js` and the `lint` task is called
  - the contents of the file changed from the previous value
  - `files/b.js` is linted
- User saves `files/a.js` and the `lint` task is called
  - the contents of the file have not changed from the previous value
  - nothing is linted

So the first run will emit all of the items downstream. Runs after that will only emit if it has been changed from the last file that passed through it with the same path.

Please note that this will not work with plugins that operate on sets of files (concat for example).

### cache(cacheName[, opt])

Creates a new cache hash or uses an existing one.

Cache key = file.path + file.contents

If a file exists in the cache, it is ignored.

If a file doesn't exist in the cache, it is passed through as is and added to the cache.

The last cache for this path is cleared so if you modify a file to a, then to b, then back to a all 3 will be a cache miss.

#### Possible options

`optimizeMemory` - Uses md5 instead of storing the whole file contents. Better if you are worried about large files and their effect on memory consumption. Default is `false`. In my experience this doesn't make much of a difference (only 25mb vs 26mb) but with a lot of files or a few large files this could be a big deal.

### Clearing the cache

#### Clearing the whole cache

```js
cache.caches = {};
```

#### Clearing a specific

```js
delete cache.caches['cache name yo'];
```

## LICENSE

(MIT License)

Copyright (c) 2015 Fractal <contact@contra.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
