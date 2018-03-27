@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../heroku/heroku-buildpack-php/bin/heroku-php-apache2
bash "%BIN_TARGET%" %*
