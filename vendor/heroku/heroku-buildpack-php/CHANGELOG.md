# heroku-buildpack-php CHANGELOG

## v132 (2018-03-02)

### ADD

- PHP/5.6.34 [David Zuelke]
- PHP/7.0.28 [David Zuelke]
- PHP/7.1.15 [David Zuelke]
- PHP/7.2.3 [David Zuelke]
- ext-mongodb/1.4.1 [David Zuelke]
- ext-apcu/5.1.10 [David Zuelke]
- ext-apcu_bc/1.0.4 [David Zuelke]

### CHG

- libcassandra/2.8.1 [David Zuelke]

## v131 (2018-02-12)

### ADD

- PHP/7.1.14 [David Zuelke]
- PHP/7.2.2 [David Zuelke]
- ext-blackfire/1.18.2 [David Zuelke]
- ext-mongodb/1.4.0 [David Zuelke]

### CHG

- Enable ext-sodium for PHP 7.2 on stack heroku-16 [David Zuelke]
- Composer/1.6.3 [David Zuelke]
- Use Linux abstract socket for New Relic daemon communications [David Zuelke]

## v130 (2018-01-11)

### ADD

- ext-newrelic/7.7.0.203 [David Zuelke]

## v129 (2018-01-10)

### ADD

- ext-phalcon/3.3.1 [David Zuelke]
- ext-pq/2.1.3 [David Zuelke]

### CHG

- Composer/1.6.2 [David Zuelke]

## v128 (2018-01-04)

### ADD

- PHP/5.6.33 [David Zuelke]
- PHP/7.0.27 [David Zuelke]
- PHP/7.1.13 [David Zuelke]
- PHP/7.2.1 [David Zuelke]
- ext-blackfire/1.18.0 for PHP 7.2 [David Zuelke]
- ext-apcu/5.1.9 [David Zuelke]
- ext-mongodb/1.3.4 [David Zuelke]
- ext-phalcon/3.3.0 [David Zuelke]
- ext-redis/3.1.6 [David Zuelke]

### CHG

- Composer/1.6.0 [David Zuelke]
- librdkafka/0.11.3 [David Zuelke]

## v127 (2017-11-30)

### ADD

- ext-rdkafka/3.0.5 [David Zuelke]
- ext-mongodb/1.3.3 [David Zuelke]
- ext-memcached/3.0.4 [David Zuelke]
- PHP/7.0.26 [David Zuelke]
- PHP/7.1.12 [David Zuelke]
- PHP/7.2.0 [David Zuelke]

### CHG

- libcassandra/2.8.0 [David Zuelke]

### FIX

- Heroku\Buildpack\PHP\Downloader::download() is missing optional third argument [David Zuelke]
- Files like `composer.js` or similar are inaccessible in web root (#247) [David Zuelke]

## v126 (2017-10-29)

### ADD

- PHP/5.6.32 [David Zuelke]
- PHP/7.0.25 [David Zuelke]
- PHP/7.1.11 [David Zuelke]
- ext-newrelic/7.6.0.201 [David Zuelke]
- ext-mongodb/1.3.1 [David Zuelke]
- ext-amqp/1.9.3 [David Zuelke]
- ext-phalcon/3.2.4 [David Zuelke]
- Apache/2.4.29 [David Zuelke]

### CHG

- Ignore `require-dev` when building platform package dependency graph (#240) [David Zuelke]
- Rewrite `provide` sections with PHP extensions in package definitions to `replace` for known polyfill packages [David Zuelke]
- libcassandra/2.7.1 [David Zuelke]
- librdkafka/0.11.1 [David Zuelke]

### FIX

- gmp.h lookup patching broken since v125 / d024b14 [David Zuelke]

## v125 (2017-10-04)

### ADD

- PHP/7.0.24 [David Zuelke]
- PHP/7.1.10 [David Zuelke]
- ext-redis/3.1.4 [David Zuelke]
- ext-mongodb/1.3.0 [David Zuelke]
- ext-blackfire/1.18.0 [David Zuelke]

### CHG

- Composer/1.5.2 [David Zuelke]

## v124 (2017-09-07)

### FIX

- Use Composer/1.5.1 [David Zuelke]

## v123 (2017-09-07)

### ADD

- ext-mongo/1.6.16 [David Zuelke]
- ext-newrelic/7.5.0.199 [David Zuelke]
- ext-cassandra/1.3.2 [David Zuelke]
- ext-rdkafka/3.0.4 [David Zuelke]
- ext-phalcon/3.2.2 [David Zuelke]
- PHP/7.1.9 [David Zuelke]
- PHP/7.0.23 [David Zuelke]
- ext-mongodb/1.2.10 [David Zuelke]

### CHG

- Support "heroku-sys-library" package type in platform installer [David Zuelke]
- Add new argument for "provide" platform package manifest entry to `manifest.py` [David Zuelke]
- Move libcassandra to its own package, installed as a dependency by platform installer [David Zuelke]
- Move libmemcached to its own package, installed as a dependency by platform installer (if the platform doesn't already provide it) [David Zuelke]
- Move librdkafka to its own package, installed as a dependency by platform installer [David Zuelke]
- libcassandra/2.7.0 [David Zuelke]
- librdkafka/0.11.0 [David Zuelke]
- Composer/1.5.1 [David Zuelke]

## v122 (2017-08-03)

### ADD

- ext-mongodb/1.2.9 [David Zuelke]
- ext-amqp/1.9.1 [David Zuelke]
- ext-blackfire/1.17.3 [David Zuelke]
- ext-newrelic/7.4.0.198 [David Zuelke]
- ext-phalcon/3.2.1 [David Zuelke]
- ext-pq/2.1.2 [David Zuelke]
- ext-redis/3.1.3 [David Zuelke]
- ext-rdkafka/3.0.3 [David Zuelke]
- PHP/7.0.22 [David Zuelke]
- PHP/7.1.8 [David Zuelke]
- PHP/5.6.31 [David Zuelke]

### CHG

- Do not auto-enable ext-newrelic and ext-blackfire in Heroku CI runs [David Zuelke]
- Composer/1.4.2 [David Zuelke]
- Do not error if buildpack package is installed during Heroku CI runs [David Zuelke]

## v121 (2017-03-28)

### ADD

- ext-blackfire/1.15.0 [David Zuelke]
- PHP/7.0.17 [David Zuelke]
- PHP/7.1.3 [David Zuelke]
- ext-cassandra/1.3.0 [David Zuelke]
- ext-mongodb/1.2.8 [David Zuelke]
- ext-amqp/1.9.0 (for heroku-16 only) [David Zuelke]
- ext-newrelic/7.1.0.187 [David Zuelke]
- ext-redis/3.1.2 [David Zuelke]
- ext-event/2.3.0 [David Zuelke]
- ext-phalcon/3.1.1 [David Zuelke]

### CHG

- Default to `web: heroku-php-apache2` process in case of empty `Procfile` [David Zuelke]
- libcassandra-2.6.0 [David Zuelke]
- librdkafka/0.9.4 [David Zuelke]
- Composer/1.4.1 [David Zuelke]
- Default to `web: heroku-php-apache2` (without explicit composer bin dir) process in case of missing `Procfile` [David Zuelke]

### FIX

- Failed download during bootstrap fails without meaningful error message [David Zuelke]

## v120 (2017-02-20)

### ADD

- ext-blackfire/1.14.3 [David Zuelke]
- ext-mongodb/1.2.5 [David Zuelke]
- ext-redis/3.1.1 [David Zuelke]
- ext-imagick/3.4.3 [David Zuelke]
- ext-rdkafka/3.0.1 [David Zuelke]
- PHP/7.0.16 [David Zuelke]
- PHP/7.1.2 [David Zuelke]
- ext-memcached/3.0.3 [David Zuelke]

### CHG

- Allow overwriting of Apache access log format (now named `heroku`) in config include [David Zuelke]
- Composer/1.3.2 [David Zuelke]
- Use system libmcrypt and libmemcached on heroku-16 [David Zuelke]
- librdkafka/0.9.3 [David Zuelke]
- Enable `mod_proxy_wstunnel` in Apache config [David Zuelke]

## v119 (2017-01-21)

### FIX

- Revert: ext-redis/3.1.0 [David Zuelke]
- Revert: Composer/1.3.1 [David Zuelke]

## v118 (2017-01-20)

### ADD

- ext-redis/3.1.0 [David Zuelke]
- ext-rdkafka/3.0.0 [David Zuelke]
- ext-phalcon/3.0.3 [David Zuelke]
- ext-blackfire/1.14.2 [David Zuelke]
- ext-apcu/5.1.8 [David Zuelke]
- ext-mongodb/1.2.3 [David Zuelke]
- PHP/5.6.30 [David Zuelke]
- PHP/7.0.15 [David Zuelke]
- PHP/7.1.1 [David Zuelke]
- ext-newrelic/6.9.0 [David Zuelke]

### CHG

- Composer/1.3.1 [David Zuelke]
- Ignore `WEB_CONCURRENCY` values with leading zeroes [David Zuelke]
- Default `NEW_RELIC_APP_NAME` to `HEROKU_APP_NAME` [Christophe Coevoet]

## v117 (2016-12-09)

### ADD

- ext-ev/1.0.4 [David Zuelke]
- ext-mongodb/1.2.1 [David Zuelke]
- PHP/7.0.14 [David Zuelke]
- PHP/5.6.29 [David Zuelke]

### CHG

- Composer/1.2.4 [David Zuelke]

## v116 (2016-12-01)

### ADD

- PHP/7.1.0 [David Zuelke]
- ext-phalcon/3.0.2 [David Zuelke]
- ext-rdkafka/2.0.1 [David Zuelke]
- ext-mongodb/1.2.0 [David Zuelke]

### FIX

- Implicit and explicit stability flags for platform packages got ignored [David Zuelke]

## v115 (2016-11-23)

### ADD

- ext-blackfire/1.14.1 [David Zuelke]

### CHG

- composer.json "require" or dependencies must now contain a runtime version requirement if "require-dev" or dependencies contain one [David Zuelke]

## v114 (2016-11-10)

### ADD

- ext-apcu/5.1.7 [David Zuelke]
- ext-mongodb/1.1.9 [David Zuelke]
- ext-newrelic/6.8.0.177 [David Zuelke]
- PHP/7.0.13 [David Zuelke]
- PHP/5.6.28 [David Zuelke]
- ext-event/2.2.1 [David Zuelke]

### CHG

- Composer/1.2.2 [David Zuelke]
- Update to librdkafka-0.9.2 final for ext-rdkafka [David Zuelke]

## v113 (2016-10-19)

### ADD

- ext-newrelic/6.7.0 [David Zuelke]
- ext-blackfire/1.13.0 [David Zuelke]
- ext-apcu/5.1.6 [David Zuelke]
- PHP/5.6.27 [David Zuelke]
- PHP/7.0.12 [David Zuelke]
- ext-rdkafka/1.0.0 [David Zuelke]
- ext-rdkafka/2.0.0 [David Zuelke]

## v112 (2016-09-20)

### FIX

- Use Composer/1.2.1 [David Zuelke]

## v111 (2016-09-20)

### ADD

- ext-newrelic/6.6.1.172 [David Zuelke]
- PHP/5.6.26 [David Zuelke]
- PHP/7.0.11 [David Zuelke]

### CHG

- Use Composer/1.2.1 [David Zuelke]

## v110 (2016-08-26)

### ADD

- ext-ev/1.0.3 [David Zuelke]
- ext-phalcon/2.0.13 [David Zuelke]
- ext-cassandra/1.2.2 [David Zuelke]
- ext-blackfire/1.12.0 [David Zuelke]
- ext-newrelic/6.6.0 [David Zuelke]
- PHP/5.6.25 [David Zuelke]
- PHP/7.0.10 [David Zuelke]
- ext-phalcon/3.0.1 [David Zuelke]

### CHG

- Retry downloads up to three times during bootstrapping [David Zuelke]
- Composer/1.2.0 [David Zuelke]

## v109 (2016-07-21)

### ADD

- PHP/7.0.9 [David Zuelke]
- PHP/5.6.24 [David Zuelke]
- PHP/5.5.38 [David Zuelke]

## v108 (2016-07-08)

### ADD

- ext-oauth/2.0.2 [David Zuelke]
- ext-mongodb/1.1.8 [David Zuelke]
- ext-blackfire/1.11.1 [David Zuelke]
- PHP/5.5.37 [David Zuelke]
- PHP/5.6.23 [David Zuelke]
- PHP/7.0.8 [David Zuelke]

### CHG

- Composer/1.1.3 [David Zuelke]

### FIX

- Revert to ext-redis/2.2.7 due to reported segfaults/memleaks [David Zuelke]

## v107 (2016-06-18)

### ADD

- ext-redis/2.2.8 [David Zuelke]
- ext-redis/3.0.0 [David Zuelke]
- ext-newrelic/6.4.0 [David Zuelke]
- ext-blackfire/1.10.6 [David Zuelke]

### FIX

- Custom `COMPOSER` env var breaks platform installs [David Zuelke]

## v106 (2016-06-08)

### ADD

- ext-mongodb/1.1.7 [David Zuelke]
- ext-cassandra/1.1.0 [David Zuelke]
- ext-apcu/5.1.5 [David Zuelke]
- ext-event/2.1.0 [David Zuelke]

### CHG

- Use Composer/1.1.2 [David Zuelke]

## v105 (2016-05-27)

### ADD

- PHP/5.5.36 [David Zuelke]
- PHP/5.6.22 [David Zuelke]
- PHP/7.0.7 [David Zuelke]

## v104 (2016-05-20)

### ADD

- ext-pq/1.1.1 and 2.1.1 [David Zuelke]

## v103 (2016-05-20)

### ADD

- ext-pq/1.0.1 and 2.0.1 [David Zuelke]
- ext-apcu/5.1.4 [David Zuelke]
- ext-newrelic/6.3.0.161 [David Zuelke]
- ext-ev/1.0.0 [David Zuelke]

### CHG

- Composer/1.1.1 [David Zuelke]

## v102 (2016-04-29)

### ADD

- ext-newrelic/6.2.0 [David Zuelke]
- ext-blackfire/1.10.5 [David Zuelke]
- ext-apcu/4.0.11 [David Zuelke]
- ext-event/2.0.4 [David Zuelke]
- ext-imagick/3.4.2 [David Zuelke]
- ext-mongo/1.6.14 [David Zuelke]
- PHP/5.5.35 [David Zuelke]
- PHP/5.6.21 [David Zuelke]
- PHP/7.0.6 [David Zuelke]

### CHG

- Bundle `blackfire` CLI binary with ext-blackfire [David Zuelke]
- Build PHP with `php-cgi` executable [David Zuelke]
- Composer/1.0.3 [David Zuelke]

## v101 (2016-04-12)

### ADD

- ext-event/2.0.2 [David Zuelke]
- ext-mongodb/1.1.6 [David Zuelke]
- Apache/2.4.20 [David Zuelke]
- ext-blackfire/1.10.3 [David Zuelke]

### CHG

- Use Composer/1.0.0 stable [David Zuelke]

## v100 (2016-03-31)

### ADD

- ext-imap for all PHP versions [David Zuelke]
- ext-pq/1.0.0 and 2.0.0 [David Zuelke]
- PHP/7.0.5 [David Zuelke]
- PHP/5.6.20 [David Zuelke]
- PHP/5.5.34 [David Zuelke]

### CHG

- Return to using built-in default value for the `pcre.jit` PHP INI setting [David Zuelke]
- Use Composer/1.0.0beta2 [David Zuelke]
- Use first configured platform repository to load components for bootstrapping [David Zuelke]

## v99 (2016-03-23)

### FIX

- Automatic extensions (blackfire, newrelic) may fail to get installed with many dependencies [David Zuelke]

## v98 (2016-03-21)

### ADD

- ext-event/2.0.1 [David Zuelke]
- ext-mongo/1.6.13 [David Zuelke]
- ext-mongodb/1.1.5 [David Zuelke]
- ext-oauth/2.0.1 [David Zuelke]
- ext-newrelic/6.1.0.157 [David Zuelke]
- ext-blackfire/1.10.0 [David Zuelke]

### CHG

- Remove GitHub API rate limit checks during build time [David Zuelke]
- Change pcre.jit to 0 in php.ini [David Zuelke]

## v97 (2016-03-10)

### CHG

- Temporarily downgrade to ext-newrelic/5.1.1.130 [David Zuelke]

## v96 (2016-03-10)

### ADD

- ext-imagick/3.4.1 for all PHP versions, with platform imagemagick [David Zuelke]
- ext-mongodb/1.1.3 [David Zuelke]
- ext-ldap, with SASL, for PHP builds (#131) [David Zuelke]
- ext-gmp for PHP builds (#117) [David Zuelke]
- ext-event/2.0.0 [David Zuelke]
- apcu_bc for ext-apcu on PHP 7 (#137) [David Zuelke]
- ext-newrelic/6.0.1.156 (#153) [David Zuelke]

### CHG

- Use Composer/1.0.0beta1 [David Zuelke]
- Remove vendored ICU library and use platform ICU52 for PHP [David Zuelke]
- Remove vendored zlib and use platform version for PHP and Apache [David Zuelke]
- Remove vendored pcre library and use platform version for Apache [David Zuelke]
- Use platform pcre and zlib for Nginx [David Zuelke]
- Update vendored gettext to 0.19.7 and build only its runtime parts [David Zuelke]
- Use platform libsasl for libmemcached [David Zuelke]
- Strip platform packages on build install [David Zuelke]
- Ignore platform package replace/provide/conflict from root `composer.json` on platform package install [David Zuelke]

### FIX

- Platform installer is incompatible with PHP 5.5 [David Zuelke]

## v95 (2016-03-03)

### ADD

- PHP/5.5.33 [David Zuelke]
- PHP/5.6.19 [David Zuelke]
- PHP/7.0.4 [David Zuelke]
- ext-blackfire/1.9.2 [David Zuelke]
- Nginx/1.8.1 [David Zuelke]
- Apache/2.4.18 [David Zuelke]

## v94 (2016-02-26)

### FIX

- No web servers get selected when a `composer.lock` is missing [David Zuelke]

## v93 (2016-02-26)

### ADD

- Support custom platform repositories via space separated `HEROKU_PHP_PLATFORM_REPOSITORIES` env var; leading "-" entry disables default repository [David Zuelke]

### CHG

- A `composer.phar` in the project root will no longer be aliased to `composer` on dyno startup [David Zuelke]
- Runtimes, extensions and web servers are now installed as fully self-contained Composer packages [David Zuelke]
- Perform boot script startup checks without loading unnecessary PHP configs or extensions [David Zuelke]
- ext-blackfire builds are now explicitly versioned (currently v1.9.1) [David Zuelke]
- Append `composer config bin-dir` to `$PATH` for runtime [David Zuelke]
- Check for lock file freshness using `composer validate` (#141) [David Zuelke]
- Change PHP `expose_php` to `off`, Apache `ServerTokens` to `Prod` and Nginx `server_tokens` to `off` for builds (#91, #92) [David Zuelke]
- Respect "provide", "replace" and "conflict" platform packages in dependencies and composer.json for platform package installs [David Zuelke]

### FIX

- Internal `php-min` symlink ends up in root of built apps [David Zuelke]
- Manifest for ext-apcu/4.0.10 does not declare ext-apc replacement [David Zuelke]
- Boot scripts exit with status 0 when given invalid flag as argument [David Zuelke]
- Manifest for ext-memcached/2.2.0 declares wrong PHP requirement for PHP 5.6 build [David Zuelke]
- Setting `NEW_RELIC_CONFIG_FILE` breaks HHVM builds (#149) [David Zuelke]

## v92 (2016-02-09)

### ADD

- ext-apcu/5.1.3 [David Zuelke]
- PHP/5.5.32 [David Zuelke]
- PHP/5.6.18 [David Zuelke]
- PHP/7.0.3 [David Zuelke]
- ext-phalcon/2.0.10 [David Zuelke]
- ext-blackfire for PHP 7 [David Zuelke]

### CHG

- Refactor and improve build manifest helpers, add bucket sync tooling [David Zuelke]
- Use Bob 0.0.7 for builds [David Zuelke]

### FIX

- PHP 7 extension formulae use wrong API version in folder name [David Zuelke]
- Composer build formula depends on removed PHP formula [Stefan Siegl]

## v91 (2016-01-08)

### ADD

- ext-phalcon/2.0.9 [David Zuelke]
- PHP/7.0.2 [David Zuelke]
- PHP/5.6.17 [David Zuelke]
- PHP/5.5.31 [David Zuelke]
- ext-apcu/5.1.2 [David Zuelke]
- ext-mongodb/1.1.2 [David Zuelke]
- ext-oauth/2.0.0 [David Zuelke]

## v90 (2015-12-18)

### ADD

- PHP/7.0.1 [David Zuelke]

### CHG

- Double default INI setting values for `opcache.memory_consumption`, `opcache.interned_strings_buffer` and `opcache.max_accelerated_files` [David Zuelke]

## v89 (2015-12-15)

### FIX

- HHVM builds failing when trying to install New Relic or Blackfire [David Zuelke]

## v88 (2015-12-15)

### CHG

- Big loud warnings if `composer.lock` is outdated (or even broken) [David Zuelke]
- Auto-install `ext-blackfire` and `ext-newrelic` at the very end of the build to avoid them instrumenting build steps or cluttering output with startup messages [David Zuelke]

### FIX

- Buildpack does not export PATH for multi-buildpack usage [David Zuelke]
- Composer limitation leads to lower than possible PHP versions getting resolved [David Zuelke]
- `lib-` platform package requirements may prevent dependency resolution [David Zuelke]
- Invalid/broken `composer.lock` produces confusing error message [David Zuelke]

## v87 (2015-12-11)

### CHG

- Further improve error information on failed system package install [David Zuelke]
- Notice if implicit version selection based on dependencies' requirements is made [David Zuelke]

### FIX

- "`|`" operators in `composer.lock` platform package requirements break system package dependency resolution [David Zuelke]
- Notice about missing runtime version selector does not show up in all cases [David Zuelke]

## v86 (2015-12-10)

### ADD

- PHP/7.0.0 [David Zuelke]
- PHP/5.6.16 [David Zuelke]
- ext-apcu/4.0.10 [David Zuelke]
- ext-mongo/1.6.12 [David Zuelke]
- ext-imagick/3.3.0 [David Zuelke]
- ext-blackfire/1.7.0 [David Zuelke]

### CHG

- Rewrite most of the build process; system packages are now installed using a custom Composer installer and Composer repository [David Zuelke]

## v83 (2015-11-16)

### ADD

- Composer/1.0.0-alpha11 [David Zuelke]
- PHP/7.0.0RC7 [David Zuelke]

### CHG

- Improve Composer vendor and bin dir detection in build sources [David Zuelke]
- Deprecate concurrent installs of HHVM and PHP [David Zuelke]
- Start New Relic daemon manually on Dyno boot to ensure correct behavior with non web PHP programs [David Zuelke]

### FIX

- Wrong Apache dist URL in support/build [David Zuelke]
- Build failure if `heroku-*-*` boot scripts are committed to Git in Composer bin dir [David Zuelke]
- Broken signal handling in boot scripts on Linux [David Zuelke]

## v82 (2015-10-31)

### CHG

- Downgrade Apache 2.4.17 to Apache 2.4.16 due to `REDIRECT_URL` regression [David Zuelke]

## v81 (2015-10-30)

### ADD

- PHP/7.0.0RC6 [David Zuelke]
- PHP/5.6.15 [David Zuelke]

## v80 (2015-10-15)

### ADD

- Nginx/1.8.0 [David Zuelke]
- Apache/2.4.17 [David Zuelke]
- PHP/7.0.0RC5 [David Zuelke]

### CHG

- Use system default php.ini config instead of buildpacks' if no custom config given [David Zuelke]

## v79 (2015-10-08)

### CHG

- Enable Apache modules `ssl_module` and `mod_proxy_html` (with `mod_xml2enc` dependency) by default [David Zuelke]

## v78 (2015-10-01)

### ADD

- PHP/7.0.0RC4 [David Zuelke]
- PHP/5.5.30 [David Zuelke]
- PHP/5.6.14 [David Zuelke]

## v77 (2015-09-17)

### ADD

- PHP/7.0.0RC3 [David Zuelke]

## v76 (2015-09-08)

### ADD

- ext-mongo/1.6.11 [David Zuelke]
- PHP/7.0.0RC2 [David Zuelke]
- PHP/5.5.29 [David Zuelke]
- PHP/5.6.13 [David Zuelke]

## v75 (2015-08-21)

### FIX

- Prevent potential (benign) Python notice during builds

## v74 (2015-08-21)

### FIX

- Warning about missing composer.lock is thrown incorrectly for some composer.json files

## v72 (2015-08-21)

### ADD

- PHP/5.6.12 [David Zuelke]
- PHP/5.5.28 [David Zuelke]
- ext-newrelic/4.23.4.113 [David Zuelke]
- PHP/7.0.0RC1 [David Zuelke]
- Support custom `composer.json`/`composer.lock` file names via `$COMPOSER` env var [David Zuelke]

### CHG

- A composer.lock is now required if there is any entry in the "require" section of composer.json [David Zuelke]

## v71 (2015-07-14)

### ADD

- ext-newrelic/4.23.1.107 [David Zuelke]

### FIX

- Apache `mod_proxy_fgci`'s "disablereuse=off" config flag causes intermittent blank pages with HTTPD 2.4.11+ [David Zuelke]
- Applications on cedar-10 can select non-existing PHP 7.0.0beta1 package via composer.json [David Zuelke]

## v70 (2015-07-10)

### ADD

- PHP/7.0.0beta1 [David Zuelke]
- PHP/5.6.11 [David Zuelke]
- PHP/5.5.27 [David Zuelke]
- ext-newrelic/4.23.0.102 [David Zuelke]
- ext-mongo/1.6.10 [David Zuelke]
- Support auto-tuning for IX dyno type [David Zuelke]

### CHG

- Warn about missing extensions for "blackfire" and "newrelic" add-ons during startup [David Zuelke]

## v69 (2015-06-12)

### ADD

- PHP/5.5.26 [David Zuelke]
- PHP/5.6.10 [David Zuelke]
- ext-newrelic/4.22.0.99 [David Zuelke]
- ext-mongo/1.6.9 [David Zuelke]

## v68 (2015-05-18)

### ADD

- PHP/5.6.9 [David Zuelke]
- PHP/5.5.25 [David Zuelke]
- ext-newrelic/4.21.0.97 [David Zuelke]
- ext-mongo/1.6.8 [David Zuelke]

### CHG

- Use Composer/1.0.0alpha10 [David Zuelke]
- Link only `.heroku/php/` subfolder and not all of `.heroku/` during compile to prevent potential collisions in multi BP scenarios [David Zuelke]

### FIX

- Typo in log messages [Christophe Coevoet]
- Newrelic 4.21 agent startup complaining about missing pidfile location config [David Zuelke]

## v67 (2015-03-24)

### ADD

- ext-mongo/1.6.6 [David Zuelke]
- PHP/5.6.7 [David Zuelke]
- PHP/5.5.23 [David Zuelke]

### CHG

- Don't run composer install for empty composer.json [David Zuelke]
- Unset GIT_DIR at beginning of compile [David Zuelke]

## v66 (2015-03-05)

### ADD

- ext-newrelic/4.19.0.90 [David Zuelke]

## v65 (2015-03-03)

### ADD

- ext-redis/2.2.7 [David Zuelke]
- ext-mongo/1.6.4 [David Zuelke]
- HHVM/3.3.4 [David Zuelke]

### CHG

- Composer uses stderr now for most output, indent that accordingly [David Zuelke]

## v64 (2015-02-19)

### ADD

- HHVM/3.5.1 [David Zuelke]
- PHP/5.6.6 [David Zuelke]
- PHP/5.5.22 [David Zuelke]
- ext-newrelic/4.18.0.89 [David Zuelke]
- ext-mongo/1.6.3 [David Zuelke]

## v63 (2015-02-11)

### ADD

- ext-mongo/1.6.2 [David Zuelke]

### CHG

- Tweak auto-tuning messages (tag: v63) [David Zuelke]
- Move 'booting...' message to after startup has finished [David Zuelke]
- Ignore SIGINT when running under foreman etc to ensure clean shutdown [David Zuelke]
- Prevent redundant messages when loading HHVM configs [David Zuelke]
- Echo "running workers..." message to stderr on boot [David Zuelke]

### FIX

- Incorrect 'child 123 said into stderr' removal for lines that are deemed to long by FPM and cut off using a terminating '...' sequence instead of closing double quotes [David Zuelke]

## v62 (2015-02-04)

### FIX

- Broken PHP memlimit check [David Zuelke]

## v61 (2015-02-04)

### CHG

- Port autotuning to HHVM-Nginx [David Zuelke]

### FIX

- Workaround for Composer's complaining about outdated version warnings on stdout instead of stderr, breaking calls in a few places under certain circumstances [David Zuelke]

## v60 (2015-02-04)

### ADD

- Auto-tune number of workers based on dyno size and configured memory limit [David Zuelke]

## v59 (2015-01-29)

### ADD

- ext-mongo/1.6.0 (tag: v59) [David Zuelke]

### CHG

- Improvements to INI handling for HHVM, including new `-I` switch to allow passing additional INI files at boot [David Zuelke]
- Massively improved subprocess and signal handling in boot scripts [David Zuelke]

## v58 (2015-01-26)

### ADD

- HHVM/3.5.0 [David Zuelke]
- PHP/5.6.5 [David Zuelke]
- PHP/5.5.21 [David Zuelke]

## v57 (2015-01-19)

### CHG

- Update to Composer dev version for `^` selector support [David Zuelke]

## v56 (2015-01-13)

### ADD

- ext/oauth 1.2.3 [David Zuelke]
- HHVM/3.3.3 [David Zuelke]
- Run 'composer compile' for custom scripts at the end of deploy [David Zuelke]

## v55 (2015-01-07)

### FIX

- Standard logs have the wrong $PORT in the file name if the -p option is used in boot scripts [David Zuelke]

## v54 (2015-01-05)

### ADD

- ext-newrelic/4.17.0.83 [David Zuelke]

### CHG

- Auto-set and follow (but not enable, for now) the FPM slowlog [David Zuelke]
