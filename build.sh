#!/usr/bin/env bash

base=${PWD}
release=/tmp/flarum-release

rm -rf ${release}
mkdir ${release}

git archive --format zip --worktree-attributes HEAD > ${release}/release.zip

cd ${release}
unzip release.zip -d ./
rm release.zip

# Delete files
rm -rf ${release}/build.sh
rm -rf ${release}/Vagrantfile
rm -rf ${release}/flarum/vagrant
rm -rf ${release}/flarum/core
rm -rf ${release}/flarum/studio.json

# Install all Composer dependencies
cd ${release}/flarum
composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev
composer config repositories.flarum git https://github.com/flarum/core-private.git
composer require flarum/core:dev-master@dev --prefer-dist --update-no-dev

# Copy public files
cp -R ${release}/flarum/vendor/flarum/core/public/* ${release}/assets

# Install frontend dependencies
# Assumes: npm install -g gulp flarum-gulp babel-core
cd ${release}/flarum/vendor/flarum/core/js
bower install

for app in forum admin; do
  cd "${release}/flarum/vendor/flarum/core/js/${app}"
  npm link gulp flarum-gulp babel-core
  gulp --production
  rm -rf "${release}/flarum/vendor/flarum/core/js/${app}/node_modules"
done

rm -rf ${release}/flarum/vendor/flarum/core/js/bower_components

# Bundle extensions
for extension in bbcode emoji likes lock markdown mentions pusher sticky subscriptions suspend tags; do
  mkdir "${release}/extensions/${extension}"
  cd "${base}/extensions/${extension}"
  git archive --format zip --worktree-attributes HEAD > "${release}/extensions/${extension}/release.zip"

  cd "${release}/extensions/${extension}"
  unzip release.zip -d ./
  rm release.zip
  composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev

  cd "${release}/extensions/${extension}/js"

  if [ -f bower.json ]; then
    bower install
  fi

  for app in forum admin; do
    cd "${release}/extensions/${extension}/js"

    if [ -d $app ]; then
      cd $app

      if [ -f bower.json ]; then
        bower install
      fi

      npm link gulp flarum-gulp
      gulp --production
      rm -rf node_modules bower_components
    fi
  done

  rm -rf "${release}/extensions/${extension}/js/bower_components"
  wait
done

# Finally, create the release archive
cd ${release}
zip -r release.zip ./
