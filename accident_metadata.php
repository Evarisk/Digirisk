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
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/accident_lesion/mod_accident_lesion_standard.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$lineid              = GETPOST('lineid', 'int');
$action              = GETPOST('action', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'accidentmetadata'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object               = new Accident($db);
$accidentmetadata     = new AccidentMetaData($db);
$objectline           = new AccidentLesion($db);
$project              = new Project($db);
$refAccidentLesionMod = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON($db);

// Load object
$object->fetch($id);

$hookmanager->initHooks(array('accidentmetadata', 'globalmetadata')); // Note that conf->hooks_modules contains array

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

	$backurlforlist = dol_buildpath('/digiriskdolibarr/accident_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/accident_metadata.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	// Action to update record
	if ($action == 'update' && $permissiontoadd) {
		// Get parameters
		$relative_location           = GETPOST('relative_location');
		$thirdparty_responsibility   = GETPOST('thirdparty_responsibility');
		$fatal                       = GETPOST('fatal');
		$accident_investigation      = GETPOST('accident_investigation');
		$accident_investigation_link = GETPOST('accident_investigation_link');
		$accident_location           = GETPOST('accident_location');
		$collateral_victim           = GETPOST('collateral_victim');
		$police_report               = GETPOST('police_report');
		$cerfa_link                  = GETPOST('cerfa_link');
		$fk_accident                 = GETPOST('id');

		// Initialize object AccidentMetaData
		$now                                           = dol_now();
		$accidentmetadata->date_creation               = $accidentmetadata->db->idate($now);
		$accidentmetadata->tms                         = $now;
		$accidentmetadata->status                      = 1;
		$accidentmetadata->relative_location           = $relative_location;
		$accidentmetadata->thirdparty_responsibility   = $thirdparty_responsibility;
		$accidentmetadata->fatal                       = $fatal;
		$accidentmetadata->accident_investigation      = $accident_investigation;
		$accidentmetadata->accident_investigation_link = $accident_investigation_link;
		$accidentmetadata->accident_location           = $accident_location;
		$accidentmetadata->collateral_victim           = $collateral_victim;
		$accidentmetadata->police_report               = $police_report;
		$accidentmetadata->cerfa_link                  = $cerfa_link;
		$accidentmetadata->fk_accident                 = $fk_accident;

		if (!$error) {
			$result = $accidentmetadata->create($user, false);
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

	// Action to add line
	if ($action == 'addLine' && $permissiontoadd) {
		// Get parameters
		$lesion_localization = GETPOST('lesion_localization');
		$lesion_nature       = GETPOST('lesion_nature');
		$parent_id           = GETPOST('parent_id');

		// Initialize object accident line
		$objectline->date_creation       = $object->db->idate($now);
		$objectline->ref                 = $refAccidentLesionMod->getNextValue($objectline);
		$objectline->entity              = $conf->entity;
		$objectline->lesion_localization = $lesion_localization;
		$objectline->lesion_nature       = $lesion_nature;
		$objectline->fk_accident         = $parent_id;

		// Check parameters
		if (empty($lesion_localization)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('LesionLocalization')), null, 'errors');
			$error++;
		}

//		if (empty($lesion_nature)) {
//			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('LesionNature')), null, 'errors');
//			$error++;
//		}

		if (!$error) {
			$result = $objectline->insert($user, false);
			if ($result > 0) {
				// Creation accident lesion OK
				setEventMessages($langs->trans('AddAccidentLesion').' '.$objectline->ref, array());
				$objectline->call_trigger('ACCIDENT_LESION_CREATE', $user);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Creation accident lesion KO
				if (!empty($objectline->errors)) setEventMessages(null, $objectline->errors, 'errors');
				else  setEventMessages($objectline->error, null, 'errors');
			}
		}
	}

	// Action to update line
	if ($action == 'updateLine' && $permissiontoadd) {
		// Get parameters
		$lesion_localization = GETPOST('lesion_localization');
		$lesion_nature       = GETPOST('lesion_nature');
		$parent_id           = GETPOST('parent_id');

		$objectline->fetch($lineid);

		// Initialize object accident line
		$objectline->lesion_localization = $lesion_localization;
		$objectline->lesion_nature       = $lesion_nature;
		$objectline->fk_accident         = $parent_id;

		if (!$error) {
			$result = $objectline->update($user, false);
			if ($result > 0) {
				// Update accident lesion OK
				setEventMessages($langs->trans('UpdateAccidentLesion').' '.$objectline->ref, array());
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Update accident lesion KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to delete line
	if ($action == 'deleteline' && $permissiontodelete) {
		$objectline->fetch($lineid);
		$result = $objectline->delete($user, false);
		if ($result > 0) {
			// Deletion accident lesion OK
			setEventMessages($langs->trans('DeleteAccidentlesion').' '.$objectline->ref, array());
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Deletion accident lesion KO
			if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else  setEventMessages($object->error, null, 'errors');
		}
	}
}

/*
 * View
 */

$form      = new Form($db);
$formother = new FormOther($db);

$morewhere = ' AND fk_accident = ' . $object->id;
$morewhere .= ' AND status = 1';

$accidentmetadata->fetch(0, '', $morewhere);

$title         = $langs->trans("AccidentMetaData");
$object->picto = 'accident@digiriskdolibarr';

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Object metadata
// ------------------------------------------------------------
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

print load_fiche_titre($title, '', "digiriskdolibarr32px@digiriskdolibarr");

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

print dol_get_fiche_head();

print '<table class="border tableforfieldedit accident-metadata-table">';

//AccidentLocation -- Lieu de l'accident
print '<tr><td class="minwidth400">'.$langs->trans("AccidentLocation").'</td><td>';
print '<input class="flat" type="text" size="36" name="accident_location" id="accident_location" value="'.$accidentmetadata->accident_location.'">';
print '</td></tr>';

//RelativeLocation -- Précisions complémentaires sur le lieu de l’accident
print '<tr><td class="minwidth400">'.$langs->trans("RelativeLocation").'</td><td>';
print $formother->select_dictionary('relative_location','c_relative_location', 'ref', 'label', $accidentmetadata->relative_location, 1);
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

print '</table>';

print dol_get_fiche_end();

print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';
print '</div>';
print '</div>';

// ACCIDENT LESION
print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
print load_fiche_titre($langs->trans("AccidentLesionList"), '', '');
print '<table id="tablelines" class="noborder noshadow" width="100%">';

global $forceall, $forcetoshowtitlelines;

if (empty($forceall)) $forceall = 0;

// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete

// Accident Lines
$accidentlines = $objectline->fetchAll($object->id);

print '<tr class="liste_titre">';
print '<td><span>' . $langs->trans('Ref.') . '</span></td>';
print '<td>' . $langs->trans('LesionLocalization') . '</td>';
print '<td>' . $langs->trans('LesionNature') . '</td>';
print '<td class="center" colspan="' . $colspan . '">' . $langs->trans('ActionsLine') . '</td>';
print '</tr>';

if (!empty($accidentlines) && $accidentlines > 0) {
	foreach ($accidentlines as $key => $item) {
		if ($action == 'editline' && $lineid == $key) {
			print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="updateLine">';
			print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
			print '<input type="hidden" name="lineid" value="' . $item->id . '">';
			print '<input type="hidden" name="parent_id" value="' . $object->id . '">';

			print '<tr>';
			print '<td>';
			print $item->ref;
			print '</td>';

			$coldisplay++;
			//LesionLocalization -- Siège des lésions
			print '<td>';
			print $formother->select_dictionary('lesion_localization','c_lesion_localization', 'ref', 'label', $item->lesion_localization, 1);
			print '</td>';

			$coldisplay++;
			//LesionNature -- Nature des lésions
			print '<td>';
			print $formother->select_dictionary('lesion_nature','c_lesion_nature', 'ref', 'label', $item->lesion_nature, 1);
			print '</td>';

			$coldisplay += $colspan;
			print '<td class="center" colspan="' . $colspan . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('Save') . '" name="updateLine" id="updateLine">';
			print '</td>';
			print '</tr>';

			if (is_object($objectline)) {
				print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
			}
			print '</form>';
		} else {
			print '<td>';
			print $item->ref;
			print '</td>';

			$coldisplay++;
			print '<td>';
			print $item->lesion_localization;
			print '</td>';

			$coldisplay++;
			print '<td>';
			print $item->lesion_nature;
			print '</td>';

			$coldisplay += $colspan;

			//Actions buttons
			if ($object->status == 1) {
				print '<td class="center">';
				$coldisplay++;
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=editline&amp;lineid=' . $item->id . '" style="padding-right: 20px"><i class="fas fa-pencil-alt" style="color: #666"></i></a>';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=deleteline&amp;lineid=' . $item->id . '">';
				print img_delete();
				print '</a>';
				print '</td>';
			} else {
				print '<td class="center">';
				print '-';
				print '</td>';
			}

			if (is_object($objectline)) {
				print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
			}
			print '</tr>';
		}
	}
	print '</tr>';
}
if ($object->status == 1 && $permissiontoadd) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="addLine">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	print '<input type="hidden" name="parent_id" value="' . $object->id . '">';

	print '<tr>';
	print '<td>';
	print $refAccidentLesionMod->getNextValue($objectline);
	print '</td>';

	$coldisplay++;
	//LesionLocalization -- Siège des lésions
	print '<td>';
	print $formother->select_dictionary('lesion_localization','c_lesion_localization', 'ref', 'label', '', 1);
	print '</td>';

	$coldisplay++;
	//LesionNature -- Nature des lésions
	print '<td>';
	print $formother->select_dictionary('lesion_nature','c_lesion_nature', 'ref', 'label', '', 1);
	print '</td>';

	$coldisplay += $colspan;
	print '<td class="center" colspan="' . $colspan . '">';
	print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">';
	print '</td>';
	print '</tr>';

	if (is_object($objectline)) {
		print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
	}
	print '</form>';
}
print '</table>';
print '</div>';

print dol_get_fiche_end();
// End of page
llxFooter();
$db->close();
