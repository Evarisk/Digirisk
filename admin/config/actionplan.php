<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    admin/config/actionplan.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr action plan configuration page.
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
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Initialize ActionPlan logging constants with defaults (label ON, others OFF)
if (!isset($conf->global->DIGIRISKDOLIBARR_ACTIONPLAN_LOG_LABEL)) {
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_LABEL', 1, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl = 'FR:Module_Digirisk#Plan_d_action';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'actionplan', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

print load_fiche_titre('<i class="fas fa-columns"></i> ' . $langs->trans("ActionPlanLogging"), '', '');
print '<hr>';

// ActionComm logging toggles
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

$actionPlanLogs = [
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_LABEL'              => ['ActionPlanLogLabel', 'ActionPlanLogLabelDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_WORKLOAD'            => ['ActionPlanLogWorkload', 'ActionPlanLogWorkloadDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_BUDGET'              => ['ActionPlanLogBudget', 'ActionPlanLogBudgetDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_ADD'      => ['ActionPlanLogContributorAdd', 'ActionPlanLogContributorAddDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_REMOVE'   => ['ActionPlanLogContributorRemove', 'ActionPlanLogContributorRemoveDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_PROGRESS'             => ['ActionPlanLogProgress', 'ActionPlanLogProgressDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_TAG'                  => ['ActionPlanLogTag', 'ActionPlanLogTagDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_START'           => ['ActionPlanLogDateStart', 'ActionPlanLogDateStartDesc'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_END'             => ['ActionPlanLogDateEnd', 'ActionPlanLogDateEndDesc'],
];

foreach ($actionPlanLogs as $constName => $transKeys) {
    print '<tr class="oddeven"><td>';
    print $langs->trans($transKeys[0]);
    print '</td><td>';
    print $langs->trans($transKeys[1]);
    print '</td>';
    print '<td class="center">';
    print ajax_constantonoff($constName);
    print '</td>';
    print '</tr>';
}

print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
