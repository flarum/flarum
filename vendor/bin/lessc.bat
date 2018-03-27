@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../oyejorge/less.php/bin/lessc
php "%BIN_TARGET%" %*
