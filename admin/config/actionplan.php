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
require_once DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php";

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

// Save Kanban display settings (page size + column thresholds)
if ($action == 'update_kanban') {
    $pageSize = GETPOSTINT('DIGIRISKDOLIBARR_KANBAN_PAGE_SIZE');
    if ($pageSize < 1) {
        $pageSize = 30;
    }
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_KANBAN_PAGE_SIZE', $pageSize, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_KANBAN_DRAFT_MAX', GETPOSTINT('DIGIRISKDOLIBARR_KANBAN_DRAFT_MAX'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_KANBAN_PROGRESS_MAX', GETPOSTINT('DIGIRISKDOLIBARR_KANBAN_PROGRESS_MAX'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_KANBAN_CONTROL_MAX', GETPOSTINT('DIGIRISKDOLIBARR_KANBAN_CONTROL_MAX'), 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
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
print '<td class="center">' . $langs->trans("TutoImage") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

// Each entry: constant => [label key, description key, tuto image name in img/config_tuto/actionplan/]
$actionPlanLogs = [
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_LABEL'              => ['ActionPlanLogLabel', 'ActionPlanLogLabelDesc', 'label'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_WORKLOAD'            => ['ActionPlanLogWorkload', 'ActionPlanLogWorkloadDesc', 'workload'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_BUDGET'              => ['ActionPlanLogBudget', 'ActionPlanLogBudgetDesc', 'budget'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_ADD'      => ['ActionPlanLogContributorAdd', 'ActionPlanLogContributorAddDesc', 'contributor_add'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_REMOVE'   => ['ActionPlanLogContributorRemove', 'ActionPlanLogContributorRemoveDesc', 'contributor_remove'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_PROGRESS'             => ['ActionPlanLogProgress', 'ActionPlanLogProgressDesc', 'progress'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_TAG'                  => ['ActionPlanLogTag', 'ActionPlanLogTagDesc', 'tag'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_START'           => ['ActionPlanLogDateStart', 'ActionPlanLogDateStartDesc', 'date_start'],
    'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_END'             => ['ActionPlanLogDateEnd', 'ActionPlanLogDateEndDesc', 'date_end'],
];

foreach ($actionPlanLogs as $constName => $transKeys) {
    print '<tr class="oddeven"><td>';
    print $langs->trans($transKeys[0]);
    print '</td><td>';
    print $langs->trans($transKeys[1]);
    print '</td>';
    print '<td class="center">';
    print digiriskdolibarr_tuto_image('actionplan', $transKeys[2], $langs->trans($transKeys[0]));
    print '</td>';
    print '<td class="center">';
    print ajax_constantonoff($constName);
    print '</td>';
    print '</tr>';
}

print '</table>';

// Click on a tuto image to display it full size
digiriskdolibarr_tuto_overlay();

// Kanban display settings
print '<br>';
print load_fiche_titre('<i class="fas fa-th-large"></i> ' . $langs->trans("KanbanDisplay"), '', '');
print '<hr>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_kanban">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Value") . '</td>';
print '</tr>';

$kanbanSettings = [
    'DIGIRISKDOLIBARR_KANBAN_PAGE_SIZE'    => ['KanbanPageSize', 'KanbanPageSizeDesc', 30, 1],
    'DIGIRISKDOLIBARR_KANBAN_DRAFT_MAX'    => ['KanbanDraftMax', 'KanbanDraftMaxDesc', 0, 0],
    'DIGIRISKDOLIBARR_KANBAN_PROGRESS_MAX' => ['KanbanProgressMax', 'KanbanProgressMaxDesc', 80, 0],
    'DIGIRISKDOLIBARR_KANBAN_CONTROL_MAX'  => ['KanbanControlMax', 'KanbanControlMaxDesc', 99, 0],
];

foreach ($kanbanSettings as $constName => $cfg) {
    print '<tr class="oddeven"><td>';
    print $langs->trans($cfg[0]);
    print '</td><td>';
    print $langs->trans($cfg[1]);
    print '</td><td class="center">';
    print '<input type="number" class="width75 right" name="' . $constName . '" min="' . $cfg[3] . '" value="' . getDolGlobalInt($constName, $cfg[2]) . '">';
    print '</td></tr>';
}

print '</table>';
print '<div class="center"><input type="submit" class="button button-save" value="' . $langs->trans("Save") . '"></div>';
print '</form>';

// Lists feeding the Kanban boards — their values live in Dolibarr categories and dictionaries,
// the shortcuts below open the screen managing each one
print '<br>';
print load_fiche_titre('<i class="fas fa-list-ul"></i> ' . $langs->trans("ActionPlanDictionaries"), '', '');
print '<hr>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '</tr>';

$kanbanDictionaries = [
    ['ActionPlanTagDictionary', 'ActionPlanTagDictionaryDesc', DOL_URL_ROOT . '/categories/categorie_list.php?type=' . Categorie::TYPE_PROJECT_TASK],
];
if (isModEnabled('ticket')) {
    $kanbanDictionaries[] = ['ActionPlanTicketDictionaries', 'ActionPlanTicketDictionariesDesc', DOL_URL_ROOT . '/admin/dict.php'];
}

foreach ($kanbanDictionaries as $kanbanDictionary) {
    print '<tr class="oddeven"><td>';
    print $langs->trans($kanbanDictionary[0]);
    print '</td><td>';
    print $langs->trans($kanbanDictionary[1]);
    print '</td><td class="center">';
    print '<a class="butAction" href="' . $kanbanDictionary[2] . '" target="_blank"><i class="fas fa-external-link-alt"></i> ' . $langs->trans('ActionPlanManageDictionary') . '</a>';
    print '</td></tr>';
}

print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
