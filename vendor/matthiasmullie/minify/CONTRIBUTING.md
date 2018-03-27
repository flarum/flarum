# How to contribute


## Issues

When [filing bugs](https://github.com/matthiasmullie/minify/issues/new),
try to be as thorough as possible:
* What version did you use?
* What did you try to do? ***Please post the relevant parts of your code.***
* What went wrong? ***Please include error messages, if any.***
* What was the expected result?


## Pull requests

Bug fixes and general improvements to the existing codebase are always welcome.
New features are also welcome, but will be judged on an individual basis. If
you'd rather not risk wasting your time implementing a new feature only to see
it turned down, please start the discussion by
[opening an issue](https://github.com/matthiasmullie/minify/issues/new).

Don't forget to add your changes to the [changelog](CHANGELOG.md).


### Testing

Please include tests for every change or addition to the code.
To run the complete test suite:

```sh
vendor/bin/phpunit
```

When submitting a new pull request, please make sure that that the test suite
passes (Travis CI will run it & report back on your pull request.)

To run the tests on Windows, run `tests/convert_symlinks_to_windows_style.sh`
from the command line in order to convert Linux-style test symlinks to
Windows-style.


### Coding standards

All code must follow [PSR-2](http://www.php-fig.org/psr/psr-2/). Just make sure
to run php-cs-fixer before submitting the code, it'll take care of the
formatting for you:

```sh
vendor/bin/php-cs-fixer fix src
vendor/bin/php-cs-fixer fix tests
```

Document the code thoroughly!


## License

Note that minify is MIT-licensed, which basically allows anyone to do
anything they like with it, without restriction.
