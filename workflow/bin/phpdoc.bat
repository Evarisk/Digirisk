@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../phpdocumentor/phpdocumentor/bin/phpdoc
php "%BIN_TARGET%" %*
