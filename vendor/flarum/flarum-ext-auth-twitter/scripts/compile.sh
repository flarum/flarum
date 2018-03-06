#!/usr/bin/env bash

# This script compiles the extension so that it can be used in a Flarum
# installation. It should be run from the root directory of the extension.

base=$PWD

cd "${base}/js"

if [ -f bower.json ]; then
  bower install
fi

for app in forum admin; do
  cd "${base}/js"

  if [ -d $app ]; then
    cd $app

    if [ -f bower.json ]; then
      bower install
    fi

    npm install
    gulp --production
  fi
done
