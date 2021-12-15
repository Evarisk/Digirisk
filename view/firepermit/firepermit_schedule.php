<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *   	\file       view/firepermit/firepermit_schedule.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view Fire Permit Schedule
 */

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

require_once __DIR__ . '/../../class/firepermit.class.php';
require_once __DIR__ . '/../../class/openinghours.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_firepermit.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $langs, $user, $hookmanager;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'preventionplanschedule'; // To manage different context of search

// Initialize technical objects
$firepermit = new FirePermit($db);
$object     = new Openinghours($db);

$hookmanager->initHooks(array('firepermitschedule', 'globalcard')); // Note that conf->hooks_modules contains array

// Load object
$firepermit->fetch($id);

$morewhere = ' AND element_id = ' . $id;
$morewhere .= ' AND element_type = ' . "'" . $firepermit->element . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

// Security check
$permissiontoread = $user->rights->digiriskdolibarr->firepermit->read;
$permissiontoadd  = $user->rights->digiriskdolibarr->firepermit->write;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $firepermit, $action); // Note that $action and $firepermit may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if (($action == 'update' && !GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
		$object->element_type = $firepermit->element;
		$object->element_id   = GETPOST('id');
		$object->status       = 1;

		$object->monday    = GETPOST('monday', 'string');
		$object->tuesday   = GETPOST('tuesday', 'string');
		$object->wednesday = GETPOST('wednesday', 'string');
		$object->thursday  = GETPOST('thursday', 'string');
		$object->friday    = GETPOST('friday', 'string');
		$object->saturday = GETPOST('saturday', 'string');
		$object->sunday   = GETPOST('sunday', 'string');

		$object->create($user);

		setEventMessages($langs->trans('FirePermitScheduleSave'), null, 'mesgs');
	}
}

/*
 *  View
 */

$title    = $langs->trans("FirePermit")  . ' - ' . $langs->trans("Schedule");
$help_url = '';
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

if (!empty($firepermit->id)) $res = $firepermit->fetch_optionals();

// Object card
// ------------------------------------------------------------

$head = firepermitPrepareHead($firepermit);
print dol_get_fiche_head($head, 'firepermitSchedule', $langs->trans("FirePermit"), -1, "digiriskdolibarr@digiriskdolibarr");
dol_strlen($firepermit->label) ? $morehtmlref = ' - ' . $firepermit->label : '';
digirisk_banner_tab($firepermit, 'ref', '', 0, 'ref', 'ref', $morehtmlref, 0, '', '', $firepermit->getLibStatut(5));

print dol_get_fiche_end();

print load_fiche_titre($langs->trans("FirePermitSchedule"), '', '');

//Show common fields
require_once './../../core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

// End of page
llxFooter();
$db->close();
