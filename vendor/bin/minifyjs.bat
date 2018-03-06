@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../matthiasmullie/minify/bin/minifyjs
php "%BIN_TARGET%" %*
