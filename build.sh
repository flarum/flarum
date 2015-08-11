#!/usr/bin/env bash

rm -rf /tmp/flarum-release
mkdir /tmp/flarum-release

git archive --format zip HEAD > /tmp/flarum-release/release.zip

cd /tmp/flarum-release
unzip release.zip -d ./
rm release.zip

# Install all Composer dependencies
cd /tmp/flarum-release/system
composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev

# Install frontend dependencies
cd /tmp/flarum-release/system/vendor/flarum/core/js
bower install

cd /tmp/flarum-release/system/vendor/flarum/core/js/forum
npm install
gulp

cd /tmp/flarum-release/system/vendor/flarum/core/js/admin
npm install
gulp

