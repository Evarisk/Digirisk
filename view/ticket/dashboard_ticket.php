<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       view/ticket/dashboard_ticket.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Dashboard page of Ticket
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once './../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';

global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("boxes", "digiriskdolibarr@digiriskdolibarr"));

$digiriskelement = new DigiriskElement($db);

// Get parameters
$action = GETPOST('action', 'aZ09');

$upload_dir = $conf->categorie->multidir_output[$conf->entity];

//Permission for digiriskelement_evaluator
$permissiontoread = $user->rights->digiriskdolibarr->lire;

// Security check
if ( ! $permissiontoread && $user->rights->ticket->read) accessforbidden();

/*
 * View
 */

$help_url = 'FR:Module_Digirisk#Statistiques_des_tickets';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader("", $langs->trans("DashBoard"), $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($langs->trans("DashBoard"), '', 'digiriskdolibarr_color.png@digiriskdolibarr');

/*
 * Dashboard Ticket
 */

require_once __DIR__ . '/../core/tpl/digiriskdolibarr_dashboard_ticket.tpl.php';

// End of page
llxFooter();
$db->close();
