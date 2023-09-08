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
 * \file    admin/config/firepermit.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr firepermit page.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$type  = 'firepermit';
$error = 0;

// Initialize technical objects
$usertmp = new User($db);

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$FPRProject = GETPOST('FPRProject', 'none');
	$FPRProject = preg_split('/_/', $FPRProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_FIREPERMIT_PROJECT", $FPRProject[0], 'integer', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'updateMask') {
	$firePermitMaskConst = GETPOST('maskconstfirepermit', 'alpha');
	$firePermitMask      = GETPOST('maskfirepermit', 'alpha');

	if ($firePermitMaskConst) $res = dolibarr_set_const($db, $firePermitMaskConst, $firePermitMask, 'chaine', 0, '', $conf->entity);

	if ( ! $res > 0) $error++;

	if ( ! $error) {
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setmod') {
	$constforval = 'DIGIRISKDOLIBARR_' . strtoupper($type) . "_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setmodFirePermitDet') {
	$constforval = 'DIGIRISKDOLIBARR_' . strtoupper('firepermitdet') . "_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setMaitreOeuvre') {
	$masterWorkerId = GETPOST('maitre_oeuvre');

	if ( ! $error) {
		$constforval = 'DIGIRISKDOLIBARR_' . strtoupper($type) . "_MAITRE_OEUVRE";
		dolibarr_set_const($db, $constforval, $masterWorkerId, 'integer', 0, '', $conf->entity);
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}

/*
 * View
 */

if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$form = new Form($db);

$helpUrl  = 'FR:Module_Digirisk';
$title    = $langs->trans("FirePermit");

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'firepermit', '', -1, "digiriskdolibarr@digiriskdolibarr");

print load_fiche_titre('<i class="fas fa-fire-alt"></i> ' . $langs->trans("FirePermitManagement"), '', '');
print '<hr>';
print load_fiche_titre($langs->trans("LinkedProject"), '', '');


// Project
if (isModEnabled('project')) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<table class="noborder centpercent editmode">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("SelectProject") . '</td>';
	print '<td>' . $langs->trans("Action") . '</td>';
	print '</tr>';

	$langs->load("projects");
	print '<tr class="oddeven"><td><label for="FPRProject">' . $langs->trans("FPRProject") . '</label></td><td>';
	$formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_PROJECT, 'FPRProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DigiriskFirePermitNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();
$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/" . $type . "/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

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
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_FIREPERMIT_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_ADDON . '.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setmod&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '&token=' . newToken() . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue") . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_FIREPERMIT_ADDON . '.php' == $file) { // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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
 *  Numbering module Fire Permit Det
 */

print load_fiche_titre($langs->trans("DigiriskFirePermitDetNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();
$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/firepermitdet/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

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
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON . '.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setmodFirePermitDet&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '&token=' . newToken() . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue") . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON . '.php' == $file) {  // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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

print load_fiche_titre($langs->trans("FirePermitData"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="fire_permit_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMaitreOeuvre">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MasterWorker">' . $langs->trans("MasterWorker") . '</label></td>';
print '<td>' . $langs->trans("MasterWorkerDescription") . '</td>';
$userlist = $form->select_dolusers(( ! empty($conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE) ? $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
print '<td>';
print $form->selectarray('maitre_oeuvre', $userlist, ( ! empty($conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE) ? $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
print '</td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
