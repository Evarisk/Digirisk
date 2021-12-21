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
 *   	\file       view/accident/accident_metadata_lesion.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view accident metadata lesion
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident.lib.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/accident_lesion/mod_accident_lesion_standard.php';

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
$objectline           = new AccidentLesion($db);
$project              = new Project($db);
$refAccidentLesionMod = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON($db);

// Load object
$object->fetch($id);

$hookmanager->initHooks(array('accidentmetadatalesion', 'globalcard')); // Note that conf->hooks_modules contains array

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

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/accident/accident_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/view/accident/accident_metadata_lesion.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	if (GETPOST('cancel') || GETPOST('cancelLine')) {
		// Cancel accident
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}

	// Action to add line
	if ($action == 'addLine' && $permissiontoadd) {
		// Get parameters
		$lesion_localization = GETPOST('lesion_localization');
		$lesion_nature       = GETPOST('lesion_nature');
		$parent_id           = GETPOST('parent_id');

		// Initialize object accident line
		$now                             = dol_now();
		$objectline->date_creation       = $object->db->idate($now);
		$objectline->ref                 = $refAccidentLesionMod->getNextValue($objectline);
		$objectline->entity              = $conf->entity;
		$objectline->lesion_localization = $lesion_localization;
		$objectline->lesion_nature       = $lesion_nature;
		$objectline->fk_accident         = $parent_id;

		// Check parameters
		if ($lesion_localization < 0) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('LesionLocalization')), null, 'errors');
			$error++;
		}

		if ($lesion_nature <0) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('LesionNature')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $objectline->insert($user, false);
			if ($result > 0) {
				// Creation accident lesion OK
				setEventMessages($langs->trans('AddAccidentLesion').' '.$object->ref, array());
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
				setEventMessages($langs->trans('UpdateAccidentLesion').' '.$object->ref, array());
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
			setEventMessages($langs->trans('DeleteAccidentLesion').' '.$object->ref, array());
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

$title         = $langs->trans("AccidentMetaDataLesion");
$object->picto = 'accident@digiriskdolibarr';

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Object metadata lesion
// ------------------------------------------------------------
$head = accidentPrepareHead($object);
print dol_get_fiche_head($head, 'accidentMetadataLesion', $title, -1, "digiriskdolibarr@digiriskdolibarr");

dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';
$morehtmlref .= '<div class="refidno">';
// Project
$project->fetch($object->fk_project);
$morehtmlref .= $langs->trans('Project').' : '.getNomUrlProject($project, 1, 'blank');
$morehtmlref .= '</div>';

$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element, $object).'</div>';

digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '','',$morehtmlleft);

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
			print digirisk_select_dictionary('lesion_localization','c_lesion_localization', 'label', 'label', $item->lesion_localization, 1);
			print '<a href="'.DOL_URL_ROOT.'/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="'.$langs->trans('ConfigDico').'">' .' '. img_picto('', 'globe').'</a>';
			print '</td>';

			$coldisplay++;
			//LesionNature -- Nature des lésions
			print '<td>';
			print digirisk_select_dictionary('lesion_nature','c_lesion_nature', 'label', 'label', $item->lesion_nature, 1);
			print '<a href="'.DOL_URL_ROOT.'/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="'.$langs->trans('ConfigDico').'">' .' '. img_picto('', 'globe').'</a>';
			print '</td>';

			$coldisplay += $colspan;
			print '<td class="center" colspan="' . $colspan . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('Save') . '" name="updateLine" id="updateLine">';
			print ' &nbsp; <input type="submit" id ="cancelLine" class="button" name="cancelLine" value="'.$langs->trans("Cancel").'">';
			print '</td>';
			print '</tr>';

			print '</form>';
		} else {
			print '<td>';
			print $item->ref;
			print '</td>';

			$coldisplay++;
			print '<td>';
			print $langs->transnoentities($item->lesion_localization);
			print '</td>';

			$coldisplay++;
			print '<td>';
			print $langs->transnoentities($item->lesion_nature);
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
	print digirisk_select_dictionary('lesion_localization','c_lesion_localization', 'label', 'label', '', 1);
	print '<a href="'.DOL_URL_ROOT.'/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="'.$langs->trans('ConfigDico').'">' .' '. img_picto('', 'globe').'</a>';
	print '</td>';

	$coldisplay++;
	//LesionNature -- Nature des lésions
	print '<td>';
	print digirisk_select_dictionary('lesion_nature','c_lesion_nature', 'label', 'label', '', 1);
	print '<a href="'.DOL_URL_ROOT.'/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="'.$langs->trans('ConfigDico').'">' .' '. img_picto('', 'globe').'</a>';
	print '</td>';

	$coldisplay += $colspan;
	print '<td class="center" colspan="' . $colspan . '">';
	print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">';
	print '</td>';
	print '</tr>';

	print '</form>';
}
print '</table>';
print '</div>';

print dol_get_fiche_end();
// End of page
llxFooter();
$db->close();
