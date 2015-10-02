#!/usr/bin/env bash

# This script builds a release of Flarum by installing dependencies and bundled
# extensions, compiling production assets, removing development files, and
# zipping up the result. It should be run from the root directory.

base=$PWD
release=/tmp/flarum

# Make a copy of the files
rm -rf ${release}
mkdir ${release}
git archive --format tar --worktree-attributes HEAD | tar -xC ${release}

# Install dependencies
cd ${release}/flarum
composer require flarum/core:dev-master@dev --prefer-dist --update-no-dev
composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev

# Copy public files
cp -R ${release}/flarum/vendor/flarum/core/public/* ${release}/assets

# Compile assets
cd ${release}/flarum/vendor/flarum/core
bash scripts/compile.sh

# Delete dev files
cd ${release}
rm -rf Vagrantfile
rm -rf scripts
rm -rf flarum/core
rm -rf flarum/studio.json
rm -rf `find . -type d -name node_modules`
rm -rf `find . -type d -name bower_components`

# Bundle default extensions
for extension in akismet approval bbcode emoji english flags likes lock markdown mentions pusher sticky subscriptions suspend tags; do

  # Download and extract the extension archive
  cd ${release}/extensions
  curl "https://github.com/flarum/${extension}/archive/master.zip" -L -o ${extension}.zip
  unzip ${extension}.zip -d ./${extension}
  rm ${extension}.zip

  # Compile assets
  cd $extension
  bash scripts/compile.sh

  # Delete dev files
  rm -rf `find . -type d -name node_modules`
  rm -rf `find . -type d -name bower_components`

done

# Set file permissions
cd $release
find . -type d -exec chmod 0750 {} +
find . -type f -exec chmod 0644 {} +
chmod 0775 .
chmod -R 0775 assets flarum/storage

# Create the release archive
zip -r release.zip ./
mv release.zip ${base}/release.zip
