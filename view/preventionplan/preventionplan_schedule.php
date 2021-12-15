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
 *   	\file       view/preventionplan/preventionplan_schedule.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view Prevention Plan Schedule
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

require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/openinghours.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_preventionplan.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

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
$preventionplan = new PreventionPlan($db);
$object         = new Openinghours($db);

$hookmanager->initHooks(array('preventionplanschedule', 'globalcard')); // Note that conf->hooks_modules contains array

// Load object
$preventionplan->fetch($id);

$morewhere = ' AND element_id = ' . $id;
$morewhere .= ' AND element_type = ' . "'" . $preventionplan->element . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->preventionplan->read;
$permissiontoadd  = $user->rights->digiriskdolibarr->preventionplan->write;
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $preventionplan, $action); // Note that $action and $preventionplan may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if (($action == 'update' && !GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
		$object->element_type = $preventionplan->element;
		$object->element_id = $id;
		$object->status = 1;

		$object->monday = GETPOST('monday', 'string');
		$object->tuesday = GETPOST('tuesday', 'string');
		$object->wednesday = GETPOST('wednesday', 'string');
		$object->thursday = GETPOST('thursday', 'string');
		$object->friday = GETPOST('friday', 'string');
		$object->saturday = GETPOST('saturday', 'string');
		$object->sunday = GETPOST('sunday', 'string');

		$object->create($user);

		setEventMessages($langs->trans('PreventionPlanScheduleSave'), null, 'mesgs');
	}
}

/*
 *  View
 */

$title    = $langs->trans("PreventionPlan") . ' - ' . $langs->trans("Schedule");
$help_url = '';
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

if (!empty($preventionplan->id)) $res = $preventionplan->fetch_optionals();

// Object card
// ------------------------------------------------------------

$head = preventionplanPrepareHead($preventionplan);
print dol_get_fiche_head($head, 'preventionplanSchedule', $langs->trans("PreventionPlan"), -1, "digiriskdolibarr@digiriskdolibarr");
dol_strlen($preventionplan->label) ? $morehtmlref = ' - ' . $preventionplan->label : '';
//$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';

digirisk_banner_tab($preventionplan, 'ref', '', 0, 'ref', 'ref', $morehtmlref, 0, '', '', $preventionplan->getLibStatut(5));

print dol_get_fiche_end();

print load_fiche_titre($langs->trans("PreventionPlanSchedule"), '', '');

//Show common fields
require_once './../../core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

// End of page
llxFooter();
$db->close();
