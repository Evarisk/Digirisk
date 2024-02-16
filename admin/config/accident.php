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
 * \file    admin/config/accident.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr accident page.
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
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/accidentinvestigation.class.php';

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Initialize technical objects
$usertmp  = new User($db);
$accident = new Accident($db);
$workstop = new AccidentWorkStop($db);
$lesion   = new AccidentLesion($db);

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$error = 0;

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$ACCProject = GETPOST('ACCProject', 'none');
	$ACCProject = preg_split('/_/', $ACCProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_ACCIDENT_PROJECT", $ACCProject[0], 'integer', 0, '', $conf->entity);

	if ($action != 'updateedit') {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

// Actions update_mask
require_once __DIR__ . '/../../../saturne/core/tpl/actions/admin_conf_actions.tpl.php';

if ($action == 'setmod') {
    if (preg_match('_accidentinvestigation_', $value)) {
        $constforval = 'DIGIRISKDOLIBARR_ACCIDENTINVESTIGATION_ADDON';
    } else if (preg_match('_accidentworkstop_', $value)) {
        $constforval = 'DIGIRISKDOLIBARR_ACCIDENTWORKSTOP_ADDON';
    } else if (preg_match('_accidentlesion_', $value)) {
        $constforval = 'DIGIRISKDOLIBARR_ACCIDENTLESION_ADDON';
    } else if (preg_match('_accident_', $value)) {
        $constforval = 'DIGIRISKDOLIBARR_ACCIDENT_ADDON';
    } else {
        $constforval = '';
    }

    dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$form = new Form($db);

$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'accident', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

/*
 *  Numbering module
 */

print load_fiche_titre('<i class="fas fa-user-injured"></i> ' . $langs->trans("AccidentManagement"), '', '');

print '<hr>';

$object = new Accident($db);

$objectModSubdir = 'digiriskelement';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

print load_fiche_titre('<i class="fas fa-user-injured"></i> ' . $langs->trans("AccidentWorkstopManagement"), '', '');

print '<hr>';

$object = new AccidentWorkStop($db);

$objectModSubdir = 'digiriskelement';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

print load_fiche_titre('<i class="fas fa-user-injured"></i> ' . $langs->trans("AccidentLesionManagement"), '', '');

print '<hr>';

$object = new AccidentLesion($db);

$objectModSubdir = 'digiriskelement';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

print load_fiche_titre('<i class="fas fa-user-injured"></i> ' . $langs->trans("AccidentInvestigationManagement"), '', '');

print '<hr>';

$object = new AccidentInvestigation($db);

$objectModSubdir = '';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

// Project
if (isModEnabled('project')) {
	print load_fiche_titre($langs->trans("LinkedProject"), '', '');

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
	print '<tr class="oddeven"><td><label for="ACCProject">' . $langs->trans("ACCProject") . '</label></td><td>';
	$formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT, 'ACCProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
