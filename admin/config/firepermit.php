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
require_once __DIR__ . '/../../class/firepermit.class.php';

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$error = 0;

// Initialize technical objects
$usertmp = new User($db);
$object  = new FirePermit($db);

// Initialize view objects
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$form = new Form($db);

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

// Actions set_mod, update_mask
require_once __DIR__ . '/../../../saturne/core/tpl/actions/admin_conf_actions.tpl.php';

if ($action == 'setMaitreOeuvre') {
	$masterWorkerId = GETPOST('maitre_oeuvre');

	if ( ! $error) {
		$constforval = 'DIGIRISKDOLIBARR_' . strtoupper($object->element) . "_MAITRE_OEUVRE";
		dolibarr_set_const($db, $constforval, $masterWorkerId, 'integer', 0, '', $conf->entity);
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}

/*
 * View
 */

$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl  = 'FR:Module_Digirisk';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'firepermit', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

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
	$formproject->select_projects(-1,  $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_PROJECT, 'FPRProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}

$objectModSubdir = 'digiriskelement';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$object = new FirePermitLine($db);

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

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
$userlist = $form->select_dolusers(( ! empty($conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE) ? $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300', 0, 1);
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
