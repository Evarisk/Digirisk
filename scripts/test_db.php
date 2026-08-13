<?php
require 'c:/wamp64/www/dolibarr23/htdocs/master.inc.php';
global $db;
$res = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "saturne_object_signature ORDER BY rowid DESC LIMIT 1");
if (!$res) {
    echo $db->lasterror();
} else {
    print_r($db->fetch_object($res));
}
