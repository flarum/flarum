@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../franzl/studio/bin/studio
php "%BIN_TARGET%" %*
