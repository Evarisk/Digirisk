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
 *   	\file       accident_metadata.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view accident metadata
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
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

require_once __DIR__ . '/class/accident.class.php';
require_once __DIR__ . '/lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/lib/digiriskdolibarr_accident.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'accidentmetadata'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object           = new Accident($db);
$accidentmetadata = new AccidentMetaData($db);
$project          = new Project($db);

// Load object
$object->fetch($id);
$accidentmetadata = $accidentmetadata->fetchFromParent($object->id);

$hookmanager->initHooks(array('accidentmetadata', 'globalmetadata')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
// Security check
$permissiontoread   = $user->rights->digiriskdolibarr->accident->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/accident_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/accident_metadata.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	if (GETPOST('cancel')) {
		// Cancel fire permit
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}

	// Action to add record
	if ($action == 'add' && $permissiontoadd) {
		// Get parameters
		$relative_location           = GETPOST('relative_location');
		$lesion_localization         = GETPOST('lesion_localization');
		$lesion_nature               = GETPOST('lesion_nature');
		$thirdparty_responsibility   = GETPOST('thirdparty_responsibility');
		$fatal                       = GETPOST('fatal');
		$accident_investigation      = GETPOST('accident_investigation');
		$accident_investigation_link = GETPOST('accident_investigation_link');
		$collateral_victim           = GETPOST('collateral_victim');
		$police_report               = GETPOST('police_report');
		$cerfa_link                  = GETPOST('cerfa_link');
		$fk_accident                 = GETPOST('parent_id');

		// Initialize object AccidentMetaData
		$now                                           = dol_now();
		$accidentmetadata->date_creation               = $accidentmetadata->db->idate($now);
		$accidentmetadata->tms                         = $now;
		$accidentmetadata->status                      = 1;
		$accidentmetadata->relative_location           = $relative_location;
		$accidentmetadata->lesion_localization         = $lesion_localization;
		$accidentmetadata->lesion_nature               = $lesion_nature;
		$accidentmetadata->thirdparty_responsibility   = $thirdparty_responsibility;
		$accidentmetadata->fatal                       = $fatal;
		$accidentmetadata->accident_investigation      = $accident_investigation;
		$accidentmetadata->accident_investigation_link = $accident_investigation_link;
		$accidentmetadata->collateral_victim           = $collateral_victim;
		$accidentmetadata->police_report               = $police_report;
		$accidentmetadata->cerfa_link                  = $cerfa_link;
		$accidentmetadata->fk_accident                 = $fk_accident;

		if (!$error) {
			$result = $accidentmetadata->create($user, false);
			if ($result > 0) {
				// Creation Accident metadata OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Creation Accident metadata KO
				if (!empty($accidentmetadata->errors)) setEventMessages(null, $accidentmetadata->errors, 'errors');
				else  setEventMessages($accidentmetadata->error, null, 'errors');
			}
		} else {
			$action = 'create';
		}
	}

	// Action to update record
	if ($action == 'update' && $permissiontoadd) {
		// Get parameters
		$relative_location           = GETPOST('relative_location');
		$lesion_localization         = GETPOST('lesion_localization');
		$lesion_nature               = GETPOST('lesion_nature');
		$thirdparty_responsibility   = GETPOST('thirdparty_responsibility');
		$fatal                       = GETPOST('fatal');
		$accident_investigation      = GETPOST('accident_investigation');
		$accident_investigation_link = GETPOST('accident_investigation_link');
		$collateral_victim           = GETPOST('collateral_victim');
		$police_report               = GETPOST('police_report');
		$cerfa_link                  = GETPOST('cerfa_link');

		// Initialize object AccidentMetaData
		$accidentmetadata = array_shift($accidentmetadata);

		$now                                           = dol_now();
		$accidentmetadata->tms                         = $now;
		$accidentmetadata->relative_location           = $relative_location;
		$accidentmetadata->lesion_localization         = $lesion_localization;
		$accidentmetadata->lesion_nature               = $lesion_nature;
		$accidentmetadata->thirdparty_responsibility   = $thirdparty_responsibility;
		$accidentmetadata->fatal                       = $fatal;
		$accidentmetadata->accident_investigation      = $accident_investigation;
		$accidentmetadata->accident_investigation_link = $accident_investigation_link;
		$accidentmetadata->collateral_victim           = $collateral_victim;
		$accidentmetadata->police_report               = $police_report;
		$accidentmetadata->cerfa_link                  = $cerfa_link;

		if (!$error) {
			$result = $accidentmetadata->update($user, false);
			if ($result > 0) {
				// Update Accident metadata OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Update Accident metadata KO
				if (!empty($accidentmetadata->errors)) setEventMessages(null, $accidentmetadata->errors, 'errors');
				else  setEventMessages($accidentmetadata->error, null, 'errors');
			}
		} else {
			$action = 'edit';
		}
	}
}

/*
 * View
 */

$form      = new Form($db);
$formother = new FormOther($db);

$title         = $langs->trans("AccidentMetaData");
$title_create  = $langs->trans("NewAccidentMetaData");
$title_edit    = $langs->trans("ModifyAccidentMetaData");
$object->picto = 'accident@digiriskdolibarr';

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($title_create, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="parent_id" value="' . $object->id . '">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border tableforfieldcreate accident-metadata-table">'."\n";

	//RelativeLocation --
	print '<tr><td class="minwidth400">'.$langs->trans("RelativeLocation").'</td><td>';
	print '<input class="flat" type="text" size="36" name="relative_location" id="relative_location" value="'.GETPOST('relative_location').'">';
	print '</td></tr>';

	//LesionLocalization -- Siège des lésions
	print '<tr><td class="minwidth400">'.$langs->trans("LesionLocalization").'</td><td>';
	print $formother->select_dictionary('lesion_localization','c_lesion_localization', 'ref', 'label', '', 0);
	print '</td></tr>';

	//LesionNature -- Nature des lésions
	print '<tr><td class="minwidth400">'.$langs->trans("LesionNature").'</td><td>';
	print $formother->select_dictionary('lesion_nature','c_lesion_nature', 'ref', 'label', '', 0);
	print '</td></tr>';

	//ThirdPartyResponsability --
	print '<tr><td class="minwidth400">'.$langs->trans("ThirdPartyResponsability").'</td><td>';
	print '<input type="checkbox" id="thirdparty_responsability" name="thirdparty_responsability"'.(GETPOST('thirdparty_responsability') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//Fatal -- Décès
	print '<tr><td class="minwidth400">'.$langs->trans("Fatal").'</td><td>';
	print '<input type="checkbox" id="fatal" name="fatal"'.(GETPOST('fatal') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentInvestigation -- Enquête accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentInvestigation").'</td><td>';
	print '<input type="checkbox" id="accident_investigation" name="accident_investigation"'.(GETPOST('accident_investigation') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentInvestigationLink -- lien vers l'enquête accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentInvestigationLink").'</td><td>';
	print '<input type="checkbox" id="accident_investigation_link" name="accident_investigation_link"'.(GETPOST('accident_investigation_link') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentLocation").'</td><td>';
	print $formother->select_dictionary('accident_location','c_accident_location', 'ref', 'label', '', 0);
	print '</td></tr>';

	//CollateralVictim -- Victime collatérale
	print '<tr><td class="minwidth400">'.$langs->trans("CollateralVictim").'</td><td>';
	print '<input type="checkbox" id="collateral_victim" name="collateral_victim"'.(GETPOST('collateral_victim') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//PoliceReport  -- Rapport de police
	print '<tr><td class="minwidth400">'.$langs->trans("PoliceReport").'</td><td>';
	print '<input type="checkbox" id="police_report" name="police_report"'.(GETPOST('police_report') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//CerfaLink -- Lien vers le Cerfa
	print '<tr><td class="minwidth400">'.$langs->trans("CerfaLink").'</td><td>';
	print '<input class="flat" type="text" size="36" name="cerfa_link" id="cerfa_link" value="'.GETPOST('cerfa_link').'">';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" id ="actionButtonCreate" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelCreate" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($title_edit, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border tableforfieldedit accident-metadata-table">'."\n";

	//RelativeLocation --
	print '<tr><td class="minwidth400">'.$langs->trans("RelativeLocation").'</td><td>';
	print '<input class="flat" type="text" size="36" name="relative_location" id="relative_location" value="'.$accidentmetadata->relative_location.'">';
	print '</td></tr>';

	//LesionLocalization -- Siège des lésions
	print '<tr><td class="minwidth400">'.$langs->trans("LesionLocalization").'</td><td>';
	print $formother->select_dictionary('lesion_localization','c_lesion_localization', 'ref', 'label', $accidentmetadata->lesion_localization, 0);
	print '</td></tr>';

	//LesionNature -- Nature des lésions
	print '<tr><td class="minwidth400">'.$langs->trans("LesionNature").'</td><td>';
	print $formother->select_dictionary('lesion_nature','c_lesion_nature', 'ref', 'label', $accidentmetadata->lesion_nature, 0);
	print '</td></tr>';

	//ThirdPartyResponsability --
	print '<tr><td class="minwidth400">'.$langs->trans("ThirdPartyResponsability").'</td><td>';
	print '<input type="checkbox" id="thirdparty_responsability" name="thirdparty_responsability"'.($accidentmetadata->thirdparty_responsibility ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//Fatal -- Décès
	print '<tr><td class="minwidth400">'.$langs->trans("Fatal").'</td><td>';
	print '<input type="checkbox" id="fatal" name="fatal"'.($accidentmetadata->fatal ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentInvestigation -- Enquête accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentInvestigation").'</td><td>';
	print '<input type="checkbox" id="accident_investigation" name="accident_investigation"'.($accidentmetadata->accident_investigation ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentInvestigationLink -- lien vers l'enquête accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentInvestigationLink").'</td><td>';
	print '<input type="checkbox" id="accident_investigation_link" name="accident_investigation_link"'.($accidentmetadata->accident_investigation_link ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentLocation").'</td><td>';
	print $formother->select_dictionary('accident_location','c_accident_location', 'ref', 'label', $accidentmetadata->accident_location, 0);
	print '</td></tr>';

	//CollateralVictim -- Victime collatérale
	print '<tr><td class="minwidth400">'.$langs->trans("CollateralVictim").'</td><td>';
	print '<input type="checkbox" id="collateral_victim" name="collateral_victim"'.($accidentmetadata->collateral_victim ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//PoliceReport  -- Rapport de police
	print '<tr><td class="minwidth400">'.$langs->trans("PoliceReport").'</td><td>';
	print '<input type="checkbox" id="police_report" name="police_report"'.($accidentmetadata->police_report ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//CerfaLink -- Lien vers le Cerfa
	print '<tr><td class="minwidth400">'.$langs->trans("CerfaLink").'</td><td>';
	print '<input class="flat" type="text" size="36" name="cerfa_link" id="cerfa_link" value="'.$accidentmetadata->cerfa_link.'">';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {
	// Object metadata
	// ------------------------------------------------------------
	$res = $object->fetch_optionals();

	$head = accidentPrepareHead($object);
	print dol_get_fiche_head($head, 'accidentMetadata', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';
	$morehtmlref .= '<div class="refidno">';
	// Project
	$project->fetch($object->fk_project);
	$morehtmlref .= $langs->trans('Project').' : '.getNomUrlProject($project, 1, 'blank');
	$morehtmlref .= '</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, '', $object->getLibStatut(5));

	print '<div class="div-table-responsive">';
	print '<div class="fichecenter">';
	print '<table class="border centpercent tableforfield">';

	//Unset for order

	$object = array_shift($accidentmetadata);

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print dol_get_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >';
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			print '<a class="' . ($object->status == 1 ? 'butAction' : 'butActionRefused classfortooltip') . '" id="actionButtonEdit" title="' . ($object->status == 1 ? '' : dol_escape_htmltag($langs->trans("AccidentMustBeInProgress"))) . '" href="' . ($object->status == 1 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->fk_accident . '&action=edit') : '#') . '">' . $langs->trans("Modify") . '</a>';
		}
		print '</div>';
	}
	if ($permissiontoadd) {
		print '</div><div class="fichehalfright">';
	} else {
		print '</div><div class="">';
	}

	$MAXEVENT = 10;

	$morehtmlright = '<a href="' . dol_buildpath('/digiriskdolibarr/accident_agenda.php', 1) . '?id=' . $object->id . '">';
	$morehtmlright .= $langs->trans("SeeAll");
	$morehtmlright .= '</a>';

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element . '@digiriskdolibarr', '', 1, '', $MAXEVENT, '', $morehtmlright);

	print '</div></div></div>';
}

// End of page
llxFooter();
$db->close();
