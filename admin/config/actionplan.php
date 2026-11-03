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
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formcompany.class.php";
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$project = new Project($db);
$task    = new Task($db);

// Security check - Protection if external user
$permissiontoread  = $user->rights->digiriskdolibarr->adminpage->read;
$permissiontowrite = saturne_check_admin_write_access();
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
    $columnSource = GETPOST('DIGIRISKDOLIBARR_KANBAN_COLUMN_SOURCE', 'aZ09');
    if (!in_array($columnSource, ['thresholds', 'dictionary'])) {
        $columnSource = 'thresholds';
    }
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_KANBAN_COLUMN_SOURCE', $columnSource, 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
}

// Save the projects holding the tasks of the risks, their default contact types and the time spent duration
if ((($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) && $permissiontowrite) {
	$DUProject             = GETPOST('DUProject', 'none');
	$DUProject             = preg_split('/_/', $DUProject);
    $EnvironmentProject    = GETPOST('EnvironmentProject', 'none');
    $EnvironmentProject    = preg_split('/_/', $EnvironmentProject);
	$taskTimeSpentDuration = GETPOST('TaskTimeSpentDuration', 'alpha');

    if ($DUProject[0] > 0 && $DUProject[0] != $conf->global->DIGIRISKDOLIBARR_DU_PROJECT) {
        dolibarr_set_const($db, "DIGIRISKDOLIBARR_DU_PROJECT", $DUProject[0], 'integer', 0, '', $conf->entity);

        $url = '/projet/tasks.php?id=' . $DUProject[0];

        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
        $sql .= " url='".$db->escape($url)."'";
        $sql .= " WHERE leftmenu='digiriskactionplan'";
        $sql .= " AND entity=" . $conf->entity;

        $resql = $db->query($sql);
        if (!$resql) {
            $error = "Error ".$db->lasterror();
            return -1;
        }
    }

    if ($EnvironmentProject[0] > 0 && $EnvironmentProject[0] != $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT) {
        dolibarr_set_const($db, "DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT", $EnvironmentProject[0], 'integer', 0, '', $conf->entity);

        $url = '/projet/tasks.php?id=' . $EnvironmentProject[0];

        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
        $sql .= " url='".$db->escape($url)."'";
        $sql .= " WHERE leftmenu='digiriskenvironmentalactionplan'";
        $sql .= " AND entity=" . $conf->entity;

        $resql = $db->query($sql);
        if (!$resql) {
            $error = "Error ".$db->lasterror();
            return -1;
        }
    }

	if (!empty($taskTimeSpentDuration) || $taskTimeSpentDuration === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION", $taskTimeSpentDuration, 'integer', 0, '', $conf->entity);
	}

	if (!empty(GETPOST('project_contact_type'))) {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEFAULT_PROJECT_CONTACT_TYPE", GETPOST('project_contact_type'), 'integer', 0, '', $conf->entity);
	}

	if (!empty(GETPOST('task_contact_type'))) {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEFAULT_TASK_CONTACT_TYPE", GETPOST('task_contact_type'), 'integer', 0, '', $conf->entity);
	}

	if ($action != 'updateedit') {
		setEventMessage($langs->trans('SavedConfig'));
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

// Actions set_mod, update_mask and the set_/del_ switch of the module constants
require_once dol_buildpath('/saturne/core/tpl/actions/admin_conf_actions.tpl.php');

/*
 * View
 */

if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$formcompany = new FormCompany($db);
$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl = 'FR:Module_Digirisk#Plan_d_action';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'actionplan', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

// Tasks
print load_fiche_titre('<i class="fas fa-tasks"></i> ' . $langs->trans("TaskConfig"), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("TasksManagement"), '', '');

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
	print '<tr class="oddeven"><td><label for="DUProject">' . $langs->trans("DUProject") . '</label></td><td>';
	$formproject->select_projects(-1,  $conf->global->DIGIRISKDOLIBARR_DU_PROJECT, 'DUProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

    print '<tr class="oddeven"><td><label for="EnvironmentProject">' . $langs->trans("EnvironmentProject") . '</label></td><td>';
    $formproject->select_projects(-1,  $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT, 'EnvironmentProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
    print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
    print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
    print '</td></tr>';

	print '<tr class="oddeven"><td><label for="projectContactType">' . $langs->trans("DefaultProjectContactType") . '</label></td><td class="maxwidth500">';
	$formcompany->selectTypeContact($project, $conf->global->DIGIRISKDOLIBARR_DEFAULT_PROJECT_CONTACT_TYPE, 'project_contact_type', 'internal', 'position', 0, 'minwidth500');
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '<tr class="oddeven"><td><label for="taskContactType">' . $langs->trans("DefaultTaskContactType") . '</label></td><td>';
	$formcompany->selectTypeContact($task, $conf->global->DIGIRISKDOLIBARR_DEFAULT_TASK_CONTACT_TYPE, 'task_contact_type', 'internal', 'position', 0, 'minwidth500');
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}

$constArray[$moduleNameLowerCase] = [
	'TasksManagement' => [
		'name'        => 'TasksManagement',
		'description' => 'TasksManagementDescription',
		'code'        => 'DIGIRISKDOLIBARR_TASK_MANAGEMENT',
	],
	'ShowTaskStartDate' => [
		'name'        => 'ShowTaskStartDate',
		'description' => 'ShowTaskStartDateDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASK_START_DATE',
		'tutoImage'   => digiriskdolibarr_tuto_image('riskassessmentdocument', 'date_start', $langs->trans('ShowTaskStartDate')),
	],
	'ShowTaskEndDate' => [
		'name'        => 'ShowTaskEndDate',
		'description' => 'ShowTaskEndDateDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASK_END_DATE',
		'tutoImage'   => digiriskdolibarr_tuto_image('riskassessmentdocument', 'date_end', $langs->trans('ShowTaskEndDate')),
	],
	'ShowTasksDone' => [
		'name'        => 'ShowTasksDone',
		'description' => $langs->trans('ShowTasksDoneDescription') . ' % ' . $langs->trans('ShowTasksDoneDescriptionExtend'),
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASKS_DONE',
	],
	'ShowTaskCalculatedProgress' => [
		'name'        => 'ShowTaskCalculatedProgress',
		'description' => 'ShowTaskCalculatedProgressDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS',
		'tutoImage'   => digiriskdolibarr_tuto_image('riskassessmentdocument', 'progress', $langs->trans('ShowTaskCalculatedProgress')),
	],
	'ShowAllTasks' => [
		'name'        => 'ShowAllTasks',
		'description' => 'ShowAllTasksDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_ALL_TASKS',
	],
    'TaskHideRefInDocument' => [
        'name'        => 'TaskHideRefInDocument',
        'description' => 'TaskHideRefInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT',
        'tutoImage'   => digiriskdolibarr_tuto_image('riskassessmentdocument', 'ref', $langs->trans('TaskHideRefInDocument')),
    ],
    'TaskHideResponsibleInDocument' => [
        'name'        => 'TaskHideResponsibleInDocument',
        'description' => 'TaskHideResponsibleInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT',
        'tutoImage'   => digiriskdolibarr_tuto_image('riskassessmentdocument', 'responsible', $langs->trans('TaskHideResponsibleInDocument')),
    ],
    'TaskHideDateInDocument' => [
        'name'        => 'TaskHideDateInDocument',
        'description' => 'TaskHideDateInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT',
        'tutoImage'   => digiriskdolibarr_tuto_image('riskassessmentdocument', 'date', $langs->trans('TaskHideDateInDocument')),
    ],
    'TaskHideBudgetInDocument' => [
        'name'        => 'TaskHideBudgetInDocument',
        'description' => 'TaskHideBudgetInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT',
        'tutoImage'   => digiriskdolibarr_tuto_image('riskassessmentdocument', 'budget', $langs->trans('TaskHideBudgetInDocument')),
    ]
];

require dol_buildpath('/saturne/core/tpl/admin/object/object_const_view.tpl.php');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="TaskTimeSpentDuration">' . $langs->trans("TaskTimeSpentDuration") . '</label></td>';
print '<td>' . $langs->trans("TaskTimeSpentDurationDescription") . '</td>';
print '<td><input type="number" name="TaskTimeSpentDuration" value="' . $conf->global->DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// Action plan logging
print '<br>';
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
    print saturne_constant_onoff($constName, $permissiontowrite);
    print '</td>';
    print '</tr>';
}

print '</table>';

// Click on a tuto image to display it full size
digiriskdolibarr_tuto_overlay();

// Yearly action plan settings
print '<br>';
print load_fiche_titre('<i class="fas fa-calendar-alt"></i> ' . $langs->trans("ActionPlanYearDisplay"), '', '');
print '<hr>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ActionPlanCarryOverLate');
print '</td><td>';
print $langs->trans('ActionPlanCarryOverLateDesc');
print '</td><td class="center">';
print saturne_constant_onoff('DIGIRISKDOLIBARR_ACTIONPLAN_CARRY_OVER_LATE', $permissiontowrite);
print '</td></tr>';

print '</table>';

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

// Source of the Kanban columns: the percentage thresholds below (default) or the column dictionary
$columnSource = getDolGlobalString('DIGIRISKDOLIBARR_KANBAN_COLUMN_SOURCE', 'thresholds');

print '<tr class="oddeven"><td>';
print $langs->trans('KanbanColumnSource');
print '</td><td>';
print $langs->trans('KanbanColumnSourceDesc');
print '</td><td class="center">';
print '<select name="DIGIRISKDOLIBARR_KANBAN_COLUMN_SOURCE" class="flat minwidth200">';
print '<option value="thresholds" ' . ($columnSource != 'dictionary' ? 'selected' : '') . '>' . $langs->trans('KanbanColumnSourceThresholds') . '</option>';
print '<option value="dictionary" ' . ($columnSource == 'dictionary' ? 'selected' : '') . '>' . $langs->trans('KanbanColumnSourceDictionary') . '</option>';
print '</select>';
print '</td></tr>';

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
    ['ActionPlanColumnDictionary', 'ActionPlanColumnDictionaryDesc', DOL_URL_ROOT . '/admin/dict.php'],
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
