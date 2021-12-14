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
 * \file    admin/accident.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr accident page.
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

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php');
dol_include_once('/custom/digiriskdolibarr/class/digiriskdocuments.class.php');

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$type          = 'accident';
$error         = 0;

// Initialize technical objects
$usertmp = new User($db);

/*
 * Actions
 */

if (($action == 'update' && !GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$ACCProject = GETPOST('ACCProject', 'none');
	$ACCProject  = preg_split('/_/', $ACCProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_ACCIDENT_PROJECT", $ACCProject[0], 'integer', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'updateMask') {
	$maskconstaccident = GETPOST('maskconstaccident', 'alpha');
	$maskaccident      = GETPOST('maskaccident', 'alpha');

	if ($maskconstaccident) $res = dolibarr_set_const($db, $maskconstaccident, $maskaccident, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setmod') {
	$constforval = 'DIGIRISKDOLIBARR_'.strtoupper($type)."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setmodAccidentWorkStop') {
	$constforval = 'DIGIRISKDOLIBARR_'.strtoupper('accident_workstop')."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setmodAccidentLesion') {
	$constforval = 'DIGIRISKDOLIBARR_'.strtoupper('accident_lesion')."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }
$form = new Form($db);

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_.C3.89l.C3.A9ment_Digirisk';
$title    = $langs->trans("Accident");

$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'accident', '', -1, "digiriskdolibarr@digiriskdolibarr");

print load_fiche_titre('<i class="fas fa-user-injured"></i> ' . $langs->trans("AccidentManagement"), '', '');
print '<hr>';
print load_fiche_titre($langs->trans("LinkedProject"), '', '');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="social_form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("SelectProject").'</td>';
print '<td>'.$langs->trans("Action").'</td>';
print '</tr>';

// Project
if (!empty($conf->projet->enabled)) {
	$langs->load("projects");
	print '<tr class="oddeven"><td><label for="ACCProject">'.$langs->trans("ACCProject").'</label></td><td>';
	$numprojet = $formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT, 'ACCProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '</td></tr>';
}

print '</table>';
print '</form>';

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DigiriskAccidentNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();
$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/".$type."/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir.$filebis;

					$module = new $classname($db);

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>';
						print $langs->trans($module->name);
						print "</td><td>";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON.'.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue").': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval = $langs->trans($nextval);
								$htmltooltip .= $nextval.'<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error).'<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON.'.php' == $file) { // If module is the one used, we show existing errors
							if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>";
					}
				}
			}
		}
		closedir($handle);
	}
}

print '</table>';

/*
 *  Numbering module Accident WorkStop
 */

print load_fiche_titre($langs->trans("DigiriskAccidentWorkStopNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();
$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/accident_workstop/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir.$filebis;

					$module = new $classname($db);

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>';
						print $langs->trans($module->name);
						print "</td><td>";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON.'.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmodAccidentWorkStop&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue").': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval = $langs->trans($nextval);
								$htmltooltip .= $nextval.'<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error).'<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON.'.php' == $file) {  // If module is the one used, we show existing errors
							if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>";
					}
				}
			}
		}
		closedir($handle);
	}
}

print '</table>';

/*
 *  Numbering module Accident Lesion
 */

print load_fiche_titre($langs->trans("DigiriskAccidentLesionNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();
$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/accident_lesion/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir.$filebis;

					$module = new $classname($db);

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>';
						print $langs->trans($module->name);
						print "</td><td>";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON.'.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmodAccidentLesion&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue").': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval = $langs->trans($nextval);
								$htmltooltip .= $nextval.'<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error).'<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON.'.php' == $file) {  // If module is the one used, we show existing errors
							if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>";
					}
				}
			}
		}
		closedir($handle);
	}
}

print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
