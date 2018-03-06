# Building Custom Platform Packages and Repositories

## Introduction

**Please note that Heroku cannot provide support for issues related to custom platform repositories and packages.**

### How it all works

When an application is deployed, `bin/compile` extracts all platform dependencies from the application's `composer.lock` and constructs a new `composer.json` (with all package names prefixed with `heroku-sys/`, so `php` becomes `heroku-sys/php`), which gets `composer install`ed using a custom Composer repository.

This `composer.json` gets written to `.heroku/php/` and is distinct from the application's `composer.json`. It only holds information on required platform packages.

The custom Composer repository, running off an S3 bucket, provides all of these packages; the Composer installer plugin in `support/installer/` handles extraction, activation, configuration etc.

### Custom platform packages and repositories

To use custom platform packages (either new ones, or modifications of existing ones), a new Composer repository has to be created (see [the instructions in the main README](../../README.md#custom-platform-repositories) for usage info). All the tooling in here is designed to work with S3, since it is reliable and cheap. The bucket permissions should be set up so that a public listing is allowed.

The folder `support/build` contains [Bob](http://github.com/kennethreitz/bob-builder) build formulae for all packages and their dependencies.

These build formulae can have dependencies (e.g. an extension formula depends on the correct version of PHP needed to build it, and maybe on a vendored library); Bob handles downloading of dependencies prior to a build, and it's the responsibility of a build formula to remove these dependencies again if they're not needed e.g. in between running `make` and `make install`.

The build formulae are also expected to generate a [manifest](#about-manifests), which is a `composer.json` containing all relevant information about a package.

In `support/build/_util`, three scripts (`deploy.sh` to deploy a package with its [manifest](#about-manifests), `mkrepo.sh` to (re-)generate a [repository](#about-repositories) from all existing manifests, and `sync.sh` to [sync between repos](#syncing-repositories)) take care of most of the heavy lifting.

## Preparations

You may either build packages on a Heroku dyno, or locally using a Docker container. The instructions below use `heroku run`; simply replace them with the appropriate `docker run` call if you are using Docker.

The following environment variables are required:

- `WORKSPACE_DIR`, must be "`/app/support/build`"
- `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` with credentials for the S3 bucket
- `S3_BUCKET` with the name of the S3 bucket to use for builds
- `S3_PREFIX` (just a slash, or a prefix directory name **with a trailing, but no leading, slash**)
- `STACK` (currently, only "`cedar-14`" makes any sense)

The following environment variables are highly recommended (see section *Understanding Upstream Buckets*):

- `UPSTREAM_S3_BUCKET` where dependencies are pulled from if they can't be found in `S3_BUCKET+S3_PREFIX`, should probably be set to "`lang-php`", the official Heroku bucket
- `UPSTREAM_S3_PREFIX`, where dependencies are pulled from if they can't be found in `S3_BUCKET+S3_PREFIX` should probably be set to "`dist-cedar-14-stable/`", the official Heroku stable repository prefix for the [cedar-14 stack](https://devcenter.heroku.com/articles/stack#cedar).

The following environment variables are optional:

- `S3_REGION`, to be set to the AWS region name (e.g. "`s3-eu-west-1`") for any non-standard region, otherwise "`s3`" (for region "us-east-1")

### Understanding Prefixes

It is recommended to use a prefix like "`dist-cedar-14-develop/`" for `$S3_PREFIX`. The contents of this prefix will act as a development repository, where all building happens. The `support/build/_util/sync.sh` helper can later be used to synchronize to another prefix, e.g. "`dist-cedar-14-stable/`" that is used for production. For more info, see the [section on syncing repositories](#syncing-repositories) further below.

### Understanding Upstream Buckets

If you want to, for example, host only a few PECL extensions in a custom repository, your bucket would still have to contain the build-time dependencies for those extensions - that's PHP in its various versions.

Due to the order in which Composer looks up packages from repositories, including PHP builds in your custom repositories may lead to those builds getting used on deploy, which is not what you want - you want to use Heroku's official PHP builds, but still have access to your custom-built extensions.

That's where the `UPSTREAM_S3_BUCKET` and `UPSTREAM_S3_PREFIX` env vars come into play; you'll usually set them to "`lang-php`" and "`dist-cedar-14-stable/`", respectively.

That way, if your Bob formula for an extension contains e.g. this dependency declaration at the top:

    # Build Path: /app/.heroku/php/
    # Build Deps: php-7.0.4

then on build, Bob will first look for "`php-7.0.4`" in your S3 bucket, and then fall back to pulling it from the upstream bucket. This frees you of the burden of hosting and maintaining unnecessary packages yourself.

### Build Environment Setup

#### Using Heroku

To get started, create a Python app (*Bob* is a Python application) on Heroku inside a clone of this repository, and set your S3 config vars:

    $ heroku create --buildpack heroku/python
    $ heroku config:set WORKSPACE_DIR=/app/support/build
    $ heroku config:set AWS_ACCESS_KEY_ID=<your_aws_key>
    $ heroku config:set AWS_SECRET_ACCESS_KEY=<your_aws_secret>
    $ heroku config:set S3_BUCKET=<your_s3_bucket_name>
    $ heroku config:set S3_PREFIX=<optional_s3_subfolder_to_upload_to_without_leading_but_with_trailing_slash>
    $ heroku config:set UPSTREAM_S3_BUCKET=lang-php
    $ heroku config:set UPSTREAM_S3_PREFIX=dist-cedar-14-stable/
    $ heroku config:set STACK=cedar-14
    $ git push heroku master
    $ heroku ps:scale web=0

#### Using Docker

Refer to the [README in `support/build/_docker/`](_docker/README.md) for setup instructions.

## Building a Package

To verify a formula, `bob build` can be used to build it:

    $ heroku run bash
    Running `bash` attached to terminal... up, run.6880
    ~ $ bob build extensions/no-debug-non-zts-20121212/yourextension-1.2.3
    
    Fetching dependencies... found 1:
      - php-5.5.31
    Building formula extensions/no-debug-non-zts-20121212/yourextension-1.2.3
    ...

If that works, a `bob deploy` would build it first, and then upload it to your bucket (you can specify `--overwrite` to overwrite existing packages).

However, that alone is not enough - the *manifest* needs to be in place as well, and using that manifest, you can then generate a Composer repository metadata file.

The next two sections contain important info about manifests and repositories; the *tl;dr* is: **do not use `bob deploy`, but `support/build/_util/deploy.sh`, to deploy a package**, because it will take care of manifest uploading:

    $ support/build/_util/deploy.sh extensions/no-debug-non-zts-20121212/yourextension-1.2.3

In addition to an `--overwrite` option, the `deploy.sh` script also accepts a `--publish` option that will cause the package to immediately be published into the repository by [re-generating that repo](#re-generating-repositories). **This should be used with caution**, as several parallel `deploy.sh` invocations could result in a race condition when re-generating the repository.

## About Manifests

After a `bob build` or `bob deploy`, you'll be prompted to upload a manifest. It obviously only makes sense to perform this upload after a `bob deploy`.

To perform the deploy and the manifest upload in one step, **the `deploy.sh` utility in `support/build/_util/` should be used instead of `bob deploy`**:

    $ support/build/_util/deploy.sh extensions/no-debug-non-zts-20121212/yourextension-1.2.3

This will upload the manifest to the S3 bucket if the package build and deploy succeeded. Like `bob deploy`, this script accepts a `--overwrite` flag.

The manifest is a `composer.json` specific to your built package, and it is **unrelated to Bob**, the utility that performs the builds. All manifests of your bucket together need to be combined into a [repository](#about-repositories).

All packages in the official Heroku S3 bucket use manifests, even for packages that are not exposed as part of the repository, such as library dependencies, the minimal PHP used for bootstrapping a build, or Composer.

### Manifest Contents

A manifest looks roughly like this (example is for `ext-apcu/5.1.3` for PHP 7):

    {
    	"conflict": {
    		"heroku-sys/hhvm": "*"
    	},
    	"dist": {
    		"type": "heroku-sys-tar",
    		"url": "https://lang-php.s3.amazonaws.com/dist-cedar-14-stable/extensions/no-debug-non-zts-20151012/apcu-5.1.3.tar.gz"
    	},
    	"name": "heroku-sys/ext-apcu",
    	"require": {
    		"heroku-sys/cedar": "^14.0.0",
    		"heroku-sys/php": "7.0.*",
    		"heroku/installer-plugin": "^1.2.0"
    	},
    	"time": "2016-02-16 01:18:50",
    	"type": "heroku-sys-php-extension",
    	"version": "5.1.3"
    }

Package `name`s must be prefixed with "`heroku-sys/`". Possible `type`s are `heroku-sys-php`, `heroku-sys-hhvm`, `heroku-sys-php-extension` or `heroku-sys-webserver`. The `dist` type must be "`heroku-sys-tar`". If the package is a `heroku-sys-php-extension`, it's important to specify a `conflict` with "`heroku-sys/hhvm`".

The special package type `heroku-sys-php-package` is used for generic packages that should not be available to applications during app deploys (such as vendored libraries used to build PHP or an extension).

The `require`d package `heroku/installer-plugin` will be available during install. Package `heroku-sys/cedar` is a virtual package `provide`d by the platform `composer.json` generated in `bin/compile` and has the right stack version; the selector for `heroku-sys/php` ensures that the package only applies to PHP 7.0.x.

### Manifest Helpers

All formulae use the `manifest.py` helper to generate the information above. **Use it for maximum reliability!** You can take a look at the existing formulae and the script to get a feeling for how it works.

## About Repositories

The repository is a `packages.json` of all manifests, which can be used by Composer as a `packagist` repository type. See [Usage in Applications](#usage-in-applications) for instructions on how to use such a repository with an application.

**Important: due to a limitation of Composer, extensions of identical version but for different PHP versions must be ordered within the repository in descending PHP version order, i.e. `ext-mongodb:1.1.2` for `php:7.0.*` must appear before `ext-mongodb:1.1.2` for `php:5.6.*`. Otherwise, deploys may select a lower PHP version than possible. The `mkrepo.sh` script takes care of this ordering.**

### (Re-)generating Repositories

The normal flow is to run `support/build/_util/deploy.sh` first to deploy one or more packages, and then to use `support/build/_util/mkrepo.sh` to re-generate the repo:

    $ support/build/_util/mkrepo.sh --upload

This will generate `packages.json` and upload it right away, or, if the `--upload` is not given, print upload instructions for `s3cmd`.

Alternatively, `deploy.sh` can be called with `--publish` as the first argument, in which case `mkrepo.sh --upload` will be called after the package deploy and manifest upload was successful:

    $ support/build/_util/deploy.sh --publish php-6.0.0

**This should be used with caution, as several parallel `deploy.sh` invocations could result in a race condition when re-generating the repository.**

### Syncing Repositories

It is often desirable to have a bucket with two repositories under different prefixes, e.g. `dist-cedar-14-develop/` and `dist-cedar-14-stable/`, with the latter usually used by apps for deploys. The "develop" bucket prefix would be set via `S3_PREFIX` on the Heroku package builder app or Docker container, so all builds would always end up there.

After testing builds, the contents of that "develop" repository can then be synced to "stable" using `support/build/_util/sync.sh`:

    $ support/build/_util/sync.sh my-bucket dist-cedar-14-stable/ my-bucket dist-cedar-14-develop/

*The `sync.sh` script takes destination bucket info as arguments first, then source bucket info*.

The `sync.sh` script automatically detects additions, updates and removals based on manifests. It will also warn if the source `packages.json` is not up to date with its manifests, and prompt for confirmation before syncing.

#### Syncing from Upstream

You will usually use an [Upstream Bucket](#understanding-upstream-buckets) to ensure that Bob will pull dependencies from Heroku's official bucket without having to worry about maintaining packages up the dependency tree, such as library or PHP prerequsites for an extension.

However, in rare circumstances, such as when you want to fully host all platform packages including PHP yourself and have the official repository disabled for your app, you either need to build all packages from scratch, or sync the Heroku builds from the official repository:

    $ heroku run "support/build/_util/sync.sh $S3_BUCKET $S3_PREFIX $UPSTREAM_S3_BUCKET $UPSTREAM_S3_PREFIX"

### Removing Packages

The `support/build/_util/remove.sh` helper removes a package manifest and its tarball from a bucket, and re-generates the repository. It accepts one or more names of a JSON manifest file from the bucket (optionally without "`.composer.json`" suffix) as arguments:

    $ support/build/_util/remove.sh ext-imagick-3.3.0_php-5.5.composer.json ext-imagick-3.3.0_php-5.6.composer.json

Unless the `--no-publish` option is given, the repository will be re-generated immediately after removal. Otherwise, the manifests and tarballs would be removed, but the main repository would remain in place, pointing to non-existing packages, so usage of this flag is only recommended for debugging purposes or similar.

## Usage in Applications

Please refer to [the instructions in the main README](../../README.md#custom-platform-repositories) for details on how to use a custom repository during application builds.

## Tips & Tricks

- To speed things up drastically during compilation, it'll usually be a good idea to `heroku run bash --size Performance-L`.
- All manifests generated by Bob formulas, by `support/build/_util/mkrepo.sh` and by `support/build/_util/sync.sh` use an S3 region of "s3" by default, so resulting URLs look like "`https://your-bucket.s3.amazonaws.com/your-prefix/...`". You can `heroku config:set S3_REGION` to change "s3" to another region such as "s3-eu-west-1".
- If any dependencies are not yet deployed, you need to deploy them first, or use `UPSTREAM_S3_BUCKET` and `UPSTREAM_S3_PREFIX` (recommended).
