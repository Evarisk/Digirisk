<?php
define('INC_FROM_CRON_SCRIPT', true);
require 'c:/wamp64/www/dolibarr23/htdocs/master.inc.php';
global $db;
$suffix = "<br>__USER_FULLNAME__<br>__USER_EMAIL__<br>__USER_PHONEPRO__<br>__MYCOMPANY_NAME__<br>__MYCOMPANY_FULLADDRESS__";

$labels = ['PP - Responsable EE', 'PP - Intervenant EE', 'PF - Responsable EE', 'PF - Intervenant EE'];

foreach($labels as $label) {
    $sql = "UPDATE " . MAIN_DB_PREFIX . "c_email_templates SET joinfiles = 1, content = CONCAT(content, '" . $db->escape($suffix) . "') WHERE label = '" . $db->escape($label) . "' AND content NOT LIKE '%__USER_FULLNAME__%'";
    if ($db->query($sql)) {
        echo "Updated $label\n";
    } else {
        echo "Failed $label: " . $db->lasterror() . "\n";
    }
}
echo "Done.";
