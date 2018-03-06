# studio

Develop your Composer libraries with style.

This package makes it easy to develop Composer packages while using them.

Instead of installing the packages you're working on from the Packagist repository, use Studio to symlink them from the filesystem instead.
Under the hood, it uses Composer's [path repositories](https://getcomposer.org/doc/05-repositories.md#path) to do so.
As a result, you won't have to develop in the `vendor` directory.

Studio also knows how to configure development tools that might be part of your workflow.
This includes the following:

- Autoloading (`src` and `tests`)
- PhpUnit
- PhpSpec
- TravisCI

This list will only get longer in the future.

## Installation

Studio can be installed globally or per project, with Composer:

Globally (recommended): `composer global require franzl/studio`
(use as `studio`)

> Make sure that the ~/.composer/vendor/bin directory is added to your PATH, so that the `studio` executable can be located by your system.

Per project: `composer require --dev franzl/studio`
(use as `vendor/bin/studio`)

## Usage

All commands should be run from the root of your project, where the `composer.json` file is located.

### Workflow

Typically, you will want to require one of your own Composer packages in an application.
With Studio, you can pull in your local copy of the package *instead of* the version hosted on Packagist.
The kicker: You can keep developing your library while you dogfood it, but **you won't need to change your composer.json file**.

#### Loading local packages

To use one of your own libraries within your application, you need to tell Studio where it can locate the library.
You can do so with the `load` command.
When Composer resolves its dependencies, Studio will then pitch in and symlink your local directory to Composer's `vendor` directory.

So, to have Studio manage your awesome world domination library, all you have to do is run the following command:

    $ studio load path/to/world-domination

This command should create a `studio.json` file in the current working directory.
It contains a list of directories for Studio to load.

Next, if you haven't already done so, make sure you actually require the package in your composer.json:

    "require": {
        "my/world-domination": "dev-master"
    }
    
And finally, tell Studio to set up the symlinks:

    $ composer update my/world-domination

If all goes well, you should now see a brief message along the following as part of Composer's output:

> [Studio] Loading path installer

This is what will happen under the hood:

1. Composer begins checking dependencies for updates.
2. Studio jumps in and informs Composer to prefer packages from the directories listed in the `studio.json` file over downloading them from Packagist.
3. Composer symlinks these packages into the `vendor` directory or any other appropriate place (e.g. for [custom installers](https://getcomposer.org/doc/articles/custom-installers.md)).
   Thus, to your application, these packages will behave just like "normal" Composer packages.
4. Composer generates proper autoloading rules for the Studio packages.
5. For non-Studio packages, Composer works as always.

**Pro tip:** If you keep all your libraries in one directory, you can let Studio find all of them by using a wildcard:

    $ studio load path/to/my/libraries/*

#### Kickstarting package development

If you haven't started world domination yet, Studio also includes a handy generator for new Composer packages.
Besides the usual ceremony, it contains several optional components, such as configuration for unit tests, continuous integration on Travis-CI and others.

First, we need to create the local directory for the development package:

    $ studio create domination
    # or if you want to clone a git repo
    $ studio create domination --git git@github.com:vendor/domination.git

After asking you a series of questions, this will create (or download) a package in the  `domination` subdirectory inside the current working directory.
There is a good chance that you need a little time to develop this package before publishing it on Packagist.
Therefore, if you ran this command in a Composer application, Studio will offer you to load your new package immediately.
This essentially comes down to running `studio load domination`.

Finally, don't forget to use `composer require` to actually add your package as a dependency.

### Command Reference

#### create: Create a new package skeleton

    $ studio create foo/bar

This command creates a skeleton for a new Composer package, already filled with some helpful files to get you started.
In the above example, we're creating a new package in the folder `foo/bar` in your project root.
All its dependencies will be available when using Composer.

During creation, you will be asked a series of questions to configure your skeleton.
This will include things like configuration for testing tools, Travis CI, and autoloading.

#### create --git: Manage existing packages by cloning a Git repository

    $ studio create bar --git git@github.com:me/myrepo.git

This will clone the given Git repository to the `bar` directory and install its dependencies.

#### load: Make all packages from the given local path available to Composer

    $ studio load baz

This will make sure all packages in the `baz` directory (paths with wildcards are supported, too) will be autoloadable using Composer.

#### unload: Stop managing a local path

    $ studio unload foo
 
This will remove the path `foo` from the studio.json configuration file.
This means any packages in that path will not be available to Composer anymore (unless they are still hosted on Packagist).

This does not remove the package contents from the file system.
See `scrap` for completely removing a package.

You can reload the path using the `load` command.

#### scrap: Remove a package

Sometimes you want to throw away a package.
You can do so with the `scrap` command, passing a path for a Studio-managed package:

    $ studio scrap foo

Don't worry - you'll be asked for confirmation first.

## License

This code is published under the [MIT License](http://opensource.org/licenses/MIT).
This means you can do almost anything with it, as long as the copyright notice and the accompanying license file is left intact.

## Contributing

Feel free to send pull requests or create issues if you come across problems or have great ideas.
Any input is appreciated!
