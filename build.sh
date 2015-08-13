#!/usr/bin/env bash

base=${PWD}

rm -rf /tmp/flarum-release
mkdir /tmp/flarum-release

git archive --format zip --worktree-attributes HEAD > /tmp/flarum-release/release.zip

cd /tmp/flarum-release
unzip release.zip -d ./
rm release.zip

# Delete files
rm -rf /tmp/flarum-release/build.sh
rm -rf /tmp/flarum-release/Vagrantfile
rm -rf /tmp/flarum-release/flarum/vagrant
rm -rf /tmp/flarum-release/flarum/core
rm -rf /tmp/flarum-release/flarum/studio.json

# Install all Composer dependencies
cd /tmp/flarum-release/flarum
composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev
composer config repositories.flarum git https://github.com/flarum/core-private.git
composer require flarum/core:dev-master@dev --prefer-dist --update-no-dev

# Copy public files
cp -R /tmp/flarum-release/flarum/vendor/flarum/core/public/* /tmp/flarum-release/assets

# Install frontend dependencies
# Assumes: npm install -g gulp flarum-gulp
cd /tmp/flarum-release/flarum/vendor/flarum/core/js
bower install

for app in forum admin; do
  cd "/tmp/flarum-release/flarum/vendor/flarum/core/js/${app}"
  npm link gulp flarum-gulp babel-core
  gulp --production
  rm -rf "/tmp/flarum-release/flarum/vendor/flarum/core/js/${app}/node_modules"
done

rm -rf /tmp/flarum-release/flarum/vendor/flarum/core/js/bower_components

# Bundle extensions
for extension in bbcode emoji likes lock markdown mentions pusher sticky subscriptions suspend tags; do
  mkdir "/tmp/flarum-release/extensions/${extension}"
  cd "${base}/extensions/${extension}"
  git archive --format zip --worktree-attributes HEAD > "/tmp/flarum-release/extensions/${extension}/release.zip"

  cd "/tmp/flarum-release/extensions/${extension}"
  unzip release.zip -d ./
  rm release.zip
  composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev

  cd "/tmp/flarum-release/extensions/${extension}/js"
  bower install

  for app in forum admin; do
    cd "/tmp/flarum-release/extensions/${extension}/js"

    if [ -d $app ]; then
      cd $app
      bower install
      npm link gulp flarum-gulp
      gulp --production
      rm -rf node_modules bower_components
    fi
  done

  rm -rf "/tmp/flarum-release/extensions/${extension}/js/bower_components"
  wait
done
