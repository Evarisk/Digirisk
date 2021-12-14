<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       public/ticket/ticket_success.php
 *       \ingroup    digiriskdolibarr
 *       \brief      Public page to view success on ticket
 */

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOLOGIN'))        define("NOLOGIN", 1); // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once '../../lib/digiriskdolibarr_function.lib.php';

global $langs, $mysoc;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other", "errors"));

// Get parameters
$track_id = GETPOST('track_id');

/*
 * View
 */

if (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE))
{
	print $langs->trans('TicketPublicInterfaceForbidden');
	exit;
}

$morejs  = array("/digiriskdolibarr/js/ticket-pad.min.js", "/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeaderTicketDigirisk($langs->trans("CreateTicket"), "", 0, 0, $morejs, $morecss);
?>
<div class="digirisk-signature-container" style="">
	<p class="center"><?php echo $langs->trans("TicketSuccess") . ' ' ?><b><?php echo $track_id; ?> </b></p>
	<b><i><p class="center"</p></b></i>
	<span class="wpeo-notice notice-warning center" style="margin-left: 16%;width: 70%; border-left: solid red 6px; color: red;background: rgba(255, 0, 0, 0.05)">
		<span class="notice-content" >
			<span class="notice-subtitle" style="color: red"><?php echo $langs->trans("YouMustNotifyYourHierarchy") . ' ' ?></span>
		</span>
	</span>
</div>
<?php

// End of page
llxFooter('', 'public');
$db->close();

