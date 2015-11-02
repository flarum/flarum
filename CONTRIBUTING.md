# Contributing to Flarum

Howdy! We're really excited that you are interested in contributing to Flarum. Before submitting your contribution, please take a moment and read through the following guidelines.

## Reporting Bugs

- Before opening an issue, debug your problem by following [these instructions](http://flarum.org/docs/contributing). Only open an issue if you are confident it is a bug with Flarum, not with your own setup.

- All issues should be reported on the [flarum/core](https://github.com/flarum/core/issues) repository. Issues pertaining to a specific extension should include the extension name in their title, e.g. `[Tags] Issue title`.

- Try to search for your issue – it may have already been answered or even fixed in the development branch.

- Check if the issue is reproducible with the latest version of Flarum. If you are using a pre-release or development version, please indicate the specific version you are using.

- It is **required** that you clearly describe the steps necessary to reproduce the issue you are running into. Issues with no clear repro steps will not be triaged. If an issue labeled "needs verification" receives no further input from the issue author for more than 5 days, it will be closed.

### Security Vulnerabilities

If you discover a security vulnerability within Flarum, please send an email to [security@flarum.org](mailto:security@flarum.org).

## Pull Request Guidelines

- Read the [Contributor License Agreement](#contributor-license-agreement).

- Checkout a topic branch from `master` and merge back against `master`.

- Do NOT checkin the JavaScript `dist` files in commits.

- [Squash the commits](http://davidwalsh.name/squash-commits-git) if there are too many small ones.

- Follow the [code style](#code-style).

## Code Style

- PHP: [PSR-2 coding standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md), [PHPDoc](http://www.phpdoc.org/docs/latest/index.html)

- JavaScript: [Airbnb Style Guide](https://github.com/airbnb/javascript), [ESDoc](https://esdoc.org/tags.html)

- When in doubt, read the source code.

## Development Setup

[flarum/flarum](https://github.com/flarum/flarum) is a "skeleton" application which uses Composer to download [flarum/core](https://github.com/flarum/core) and a [bunch of extensions](https://github.com/flarum). In order to work on these, you will need to change their versions to `dev-master` in `composer.json` and install them from source:

```bash
$ composer update --prefer-source
```

Flarum's front-end code is written in ES6 and transpiled into JavaScript. The compiled JavaScript is only committed when we tag a release; during development you will need to do it yourself. To recompile the JavaScript you will need [Node.js](http://nodejs.org).

```bash
$ npm install -g gulp
$ cd vendor/flarum/core
$ ./scripts/compile.sh
```

Check out the [Roadmap](https://github.com/flarum/core/issues/74) for an overview of what needs to be done. See the [Good For New Contributors](https://github.com/flarum/core/labels/Good%20for%20New%20Contributors) label for a list of issues that should be relatively easy to get started with.

## Contributor License Agreement

By contributing your code to Flarum you grant Toby Zerner a non-exclusive, irrevocable, worldwide, royalty-free, sublicensable, transferable license under all of Your relevant intellectual property rights (including copyright, patent, and any other rights), to use, copy, prepare derivative works of, distribute and publicly perform and display the Contributions on any licensing terms, including without limitation: (a) open source licenses like the MIT license; and (b) binary, proprietary, or commercial licenses. Except for the licenses granted herein, You reserve all right, title, and interest in and to the Contribution.

You confirm that you are able to grant us these rights. You represent that You are legally entitled to grant the above license. If Your employer has rights to intellectual property that You create, You represent that You have received permission to make the Contributions on behalf of that employer, or that Your employer has waived such rights for the Contributions.

You represent that the Contributions are Your original works of authorship, and to Your knowledge, no other person claims, or has the right to claim, any right in any invention or patent related to the Contributions. You also represent that You are not legally obligated, whether by entering into an agreement or otherwise, in any way that conflicts with the terms of this license.

Toby Zerner acknowledges that, except as explicitly described in this Agreement, any Contribution which you provide is on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING, WITHOUT LIMITATION, ANY WARRANTIES OR CONDITIONS OF TITLE, NON-INFRINGEMENT, MERCHANTABILITY, OR FITNESS FOR A PARTICULAR PURPOSE.
