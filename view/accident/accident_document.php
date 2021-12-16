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
 *  \file       view/accident/accident_document.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Tab for documents linked to Accident
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "companies", "other", "mails"));

// Get parameters
$action    = GETPOST('action', 'aZ09');
$confirm   = GETPOST('confirm');
$id        = (GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
$ref       = GETPOST('ref', 'alpha');
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset   = $liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}
//if (! $sortfield) $sortfield="position_name";

// Initialize technical objects
$object      = new Accident($db);
$extrafields = new ExtraFields($db);
$project     = new Project($db);

$diroutputmassaction = $conf->digiriskdolibarr->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('accidentdocument', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity ? $object->entity : $conf->entity]."/accident/".get_exdir(0, 0, 0, 1, $object);
}

$permissiontoadd = $user->rights->digiriskdolibarr->accident->write; // Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles.inc.php

// Security check
$permissiontoread   = $user->rights->digiriskdolibarr->accident->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("Accident").' - '.$langs->trans("Files");
$help_url = '';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if ($object->id) {
	$head = accidentPrepareHead($object);

	print dol_get_fiche_head($head, 'accidentDocument', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	// Object card
	// ------------------------------------------------------------
	dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';
	$morehtmlref .= '<div class="refidno">';
	// Project
	$project->fetch($object->fk_project);
	$morehtmlref .= $langs->trans('Project').' : '.getNomUrlProject($project, 1, 'blank');
	$morehtmlref .= '</div>';

	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element, $object).'</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '','',$morehtmlleft);

	print '<div class="fichecenter">';
	print '<table class="border centpercent tableforfield">';

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$workstopDirectories =  dol_dir_list($upload_dir . '/workstop/', 'directories', 0);
	foreach ($workstopDirectories as $workstopDirectory) {
		$workstopFileArray = dol_dir_list($workstopDirectory['fullname'], 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
		foreach ($workstopFileArray as $workstopFile) {
			$workstopFile['name'] = $workstopFile['level1name'] . '/' . $workstopFile['name'];
			$filearray[] = $workstopFile;
		}
	}
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	$modulepart = 'accident';
	//$permissiontoadd = $user->rights->digiriskdolibarr->myobject->write;
	$permissiontoadd = 1;
	//$permtoedit = $user->rights->digiriskdolibarr->myobject->write;
	$permtoedit = 1;
	$param = '&id='.$object->id;

	$relativepathwithnofile = 'accident/'.dol_sanitizeFileName($object->ref).'/';

	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
