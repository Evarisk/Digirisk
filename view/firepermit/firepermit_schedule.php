<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

// Load DigiriskDolibarr environment.
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/firepermit.class.php';
require_once __DIR__ . '/../../class/openinghours.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
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
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'preventionplanschedule'; // To manage different context of search

// Initialize technical objects
$firepermit        = new FirePermit($db);
$object            = new Openinghours($db);
$digiriskresources = new DigiriskResources($db);
$project           = new Project($db);

$hookmanager->initHooks(array('firepermitschedule', 'globalcard')); // Note that conf->hooks_modules contains array

// Load object
$firepermit->fetch($id);

$morewhere  = ' AND element_id = ' . $id;
$morewhere .= ' AND element_type = ' . "'" . $firepermit->element . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

// Security check

$permissiontoread = $user->rights->digiriskdolibarr->firepermit->read;
$permissiontoadd  = $user->rights->digiriskdolibarr->firepermit->write;

if ( ! $permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $firepermit, $action); // Note that $action and $firepermit may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
		$object->element_type = $firepermit->element;
		$object->element_id   = GETPOST('id');
		$object->status       = 1;

		$object->monday    = GETPOST('monday', 'string');
		$object->tuesday   = GETPOST('tuesday', 'string');
		$object->wednesday = GETPOST('wednesday', 'string');
		$object->thursday  = GETPOST('thursday', 'string');
		$object->friday    = GETPOST('friday', 'string');
		$object->saturday  = GETPOST('saturday', 'string');
		$object->sunday    = GETPOST('sunday', 'string');

		$object->create($user);

		setEventMessages($langs->trans('FirePermitScheduleSave'), null, 'mesgs');
	}
}

/*
 *  View
 */

$title    = $langs->trans("FirePermit") . ' - ' . $langs->trans("Schedule");
$help_url = 'FR:Module_Digirisk#DigiRisk_-_Permis_de_feu';
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

if ( ! empty($firepermit->id)) $res = $firepermit->fetch_optionals();

// Object card
// ------------------------------------------------------------

$head = firepermit_prepare_head($firepermit);
print dol_get_fiche_head($head, 'firepermitSchedule', $langs->trans("FirePermit"), -1, "digiriskdolibarr@digiriskdolibarr");

dol_strlen($firepermit->label) ? $morehtmlref = '<span>' . ' - ' . $firepermit->label . '</span>' : '';
$morehtmlref                             .= '<div class="refidno">';
// External Society -- Société extérieure
$extSociety  = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY', $firepermit);
$morehtmlref .= $langs->trans('ExtSociety') . ' : ' . $extSociety->getNomUrl(1);
// Project
$project->fetch($firepermit->fk_project);
$morehtmlref .= '<br>' . $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$morehtmlref .= '</div>';

$linkback = '<a href="' . dol_buildpath('/digiriskdolibarr/view/firepermit/firepermit_list.php', 1) . '">' . $langs->trans("BackToList") . '</a>';

digirisk_banner_tab($firepermit, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $firepermit->getLibStatut(5));

print dol_get_fiche_end();

print load_fiche_titre($langs->trans("FirePermitSchedule"), '', '');

//Show common fields
require_once __DIR__ . '/../../core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

// End of page
llxFooter();
$db->close();
