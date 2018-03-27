#!/usr/bin/env bash

# This script compiles Flarum's core assets so that they can be used in-browser.
# It should be run from the root directory of the core.

base=$PWD

cd "${base}/js"
bower install

cd "${base}/js/forum"
npm install
gulp

cd "${base}/js/admin"
npm install
gulp
