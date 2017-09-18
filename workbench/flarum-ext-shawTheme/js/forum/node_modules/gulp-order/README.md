# gulp-order

The gulp plugin `gulp-order` allows you to reorder a stream of files using the same syntax as of `gulp.src`.

## Motivation

Assume you want to concatenate the following files in the given order (with `gulp-concat`):

- `vendor/js1.js`
- `vendor/**/*.{coffee,js}`
- `app/coffee1.coffee`
- `app/**/*.{coffee,js}`

You'll need two streams:

- A stream that emits the JavaScript files, and
- a stream that emits the compiled CoffeeScript files.
 
To combine the streams you can pipe into another `gulp.src` or use `es.merge` (from `event-stream`). But you'll notice that in both cases the files are emitted in the same order as they come in - and this can seem very random. With `gulp-order` you can reorder the files.

## Usage

`require("gulp-order")` returns a function that takes an array of patterns (as `gulp.src` would take).

```javascript
var order = require("gulp-order");
var coffee = require("gulp-coffee");
var concat = require("gulp-concat");

gulp
  .src("**/*.coffee")
  .pipe(coffee())
  .pipe(gulp.src("**/*.js")) // gulp.src passes through input
  .pipe(order([
    "vendor/js1.js",
    "vendor/**/*.js",
    "app/coffee1.js",
    "app/**/*.js"
  ]))
  .pipe(concat("all.js"))
  .pipe(gulp.dest("dist"));
```

## Options

```javascript
gulp
  .src("**/*.coffee")
  // ...
  .pipe(order([...], options))
```

#### `base`

Some plugins might provide a wrong `base` on the Vinyl file objects. `base` allows you to set a base directory (for example: your application root directory) for all files.

## Features

Uses [`minimatch`](https://github.com/isaacs/minimatch) for matching.

## Tips

- Try to move your ordering out of your `gulp.src(...)` calls into `order(...)` instead.

## Alternative Approaches

- [`gulp-if`](https://github.com/robrich/gulp-if)

## Contributors

- [Marcel Jackwerth](http://twitter.com/sirlantis)

## License

MIT - Copyright © 2014 Marcel Jackwerth
