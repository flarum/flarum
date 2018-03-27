# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests. Please review these guidelines before submitting any pull requests.

## Guidelines

* Please follow the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PHP-FIG Naming Conventions](https://github.com/php-fig/fig-standards/blob/master/bylaws/002-psr-naming-conventions.md).
* Ensure that the current tests pass, and if you've added something new, add the tests where relevant.
* Remember that we follow [SemVer](http://semver.org). If you are changing the behaviour, or the public api, you may need to update the docs.
* Send a coherent commit history, making sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash](http://git-scm.com/book/en/Git-Tools-Rewriting-History) them before submitting.
* You may also need to [rebase](http://git-scm.com/book/en/Git-Branching-Rebasing) to avoid merge conflicts.


## Running Tests

You will need an install of [Composer](https://getcomposer.org) before continuing.

First, install the dependencies:

```bash
$ composer install
```

Then run phpunit:

```bash
$ vendor/bin/phpunit
```

If the test suite passes on your local machine you should be good to go.

When you make a pull request, the tests will automatically be run again by [Travis CI](https://travis-ci.org/) on multiple php versions and hhvm.
