<?php
require 'c:/wamp64/www/dolibarr23/htdocs/main.inc.php';
global $langs;
$langs->setDefaultLang('fr_FR');
$langs->load('digiriskdolibarr@digiriskdolibarr');
$error = $langs->trans('MobilePPWarningEmailNotConfigured');
$msg = $langs->trans('MobilePPWarningEmailNotSentDetail', $error);
echo json_encode(['success' => false, 'message' => $msg]);
echo "\nJSON Error: " . json_last_error_msg();
