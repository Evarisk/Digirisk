<?php
define('INC_FROM_CRON_SCRIPT', true);
require 'c:/wamp64/www/dolibarr23/htdocs/master.inc.php';
global $db;
$db->query("UPDATE " . MAIN_DB_PREFIX . "c_email_templates SET label = 'PP - Intervenant EE' WHERE label = 'PP - Intervenant' AND type_template = 'preventionplan'");
$db->query("UPDATE " . MAIN_DB_PREFIX . "c_email_templates SET label = 'PF - Intervenant EE' WHERE label = 'PF - Intervenant' AND type_template = 'firepermit'");
echo "Done.";
