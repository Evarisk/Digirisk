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
 * \file    view/digiriskstandard/actionplan_list.php
 * \ingroup digiriskdolibarr
 * \brief   Action plan list with Gantt and Kanban views
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/task/saturnetask.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'aZ09');
$view   = GETPOST('view', 'alpha');

if (empty($view) || !in_array($view, ['gantt', 'kanban'])) {
    $view = 'gantt';
}

// Initialize technical objects
$object  = new DigiriskStandard($db);
$task    = new SaturneTask($db);
$risk    = new Risk($db);

$hookmanager->initHooks(['actionplanlist', 'globalcard']);

// Load object
$object->fetch(0, '', ' AND t.entity = ' . $conf->entity);
$projectId = getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT');

// Security check
$permissiontoread = $user->hasRight('digiriskdolibarr', 'riskassessmentdocument', 'read');
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// AJAX action to update task status from Kanban drag & drop
if ($action == 'updateTaskProgress' && !empty(GETPOSTINT('task_id'))) {
    $taskId      = GETPOSTINT('task_id');
    $newProgress = GETPOSTINT('new_progress');

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        $taskToUpdate->progress = $newProgress;
        $updateResult = $taskToUpdate->update($user);

        if ($updateResult > 0) {
            print json_encode(['success' => 1]);
        } else {
            print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
        }
    } else {
        print json_encode(['success' => 0, 'error' => 'Task not found or wrong project']);
    }
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ActionPlanView');
$help_url = 'FR:Module_Digirisk#Plan_d_action';

saturne_header(0, '', $title, $help_url);

// Fetch all tasks for the DU project
$allTasks = [];
if ($projectId > 0) {
    $tasksList = $task->getTasksArray(0, 0, $projectId);
    if (is_array($tasksList) && !empty($tasksList)) {
        $allTasks = $tasksList;
    }
}

// Fetch risk links (fk_risk => tasks)
$taskRiskMap = [];
$riskObjects = [];
if (!empty($allTasks)) {
    $sql = "SELECT fk_object, fk_risk FROM " . MAIN_DB_PREFIX . "projet_task_extrafields WHERE fk_risk > 0";
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $taskRiskMap[$obj->fk_object] = $obj->fk_risk;
            if (!isset($riskObjects[$obj->fk_risk])) {
                $riskObj = new Risk($db);
                $riskObj->fetch($obj->fk_risk);
                $riskObjects[$obj->fk_risk] = $riskObj;
            }
        }
        $db->free($resql);
    }
}

// Fetch categories/tags for tasks
$taskCategories = [];
$categorie = new Categorie($db);
foreach ($allTasks as $t) {
    $cats = $categorie->containing($t->id, 'project_task');
    if (is_array($cats) && !empty($cats)) {
        $taskCategories[$t->id] = $cats;
    }
}

// Kanban column thresholds (configurable)
$kanbanThresholds = [
    'draft_max'   => getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_DRAFT_MAX', 0),
    'progress_max' => getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_PROGRESS_MAX', 80),
    'control_max'  => getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_CONTROL_MAX', 99),
];

// Prepare JSON data for JS
$tasksJson = [];
foreach ($allTasks as $t) {
    $riskRef = '';
    $riskId  = 0;
    if (isset($taskRiskMap[$t->id]) && isset($riskObjects[$taskRiskMap[$t->id]])) {
        $riskRef = $riskObjects[$taskRiskMap[$t->id]]->ref;
        $riskId  = $riskObjects[$taskRiskMap[$t->id]]->id;
    }

    $cats = [];
    if (isset($taskCategories[$t->id])) {
        foreach ($taskCategories[$t->id] as $cat) {
            $cats[] = ['id' => $cat->id, 'label' => $cat->label, 'color' => $cat->color];
        }
    }

    $tasksJson[] = [
        'id'               => $t->id,
        'ref'              => $t->ref,
        'label'            => $t->label,
        'date_start'       => $t->date_start ? dol_print_date($t->date_start, 'dayrfc') : '',
        'date_end'         => $t->date_end ? dol_print_date($t->date_end, 'dayrfc') : '',
        'planned_workload' => $t->planned_workload,
        'duration_effective' => $t->duration_effective,
        'progress'         => (int) $t->progress,
        'status'           => (int) $t->fk_statut,
        'risk_ref'         => $riskRef,
        'risk_id'          => $riskId,
        'categories'       => $cats,
        'url'              => DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $t->id . '&withproject=1',
    ];
}

// Tab header
$head = [];
$head[0][0] = $_SERVER['PHP_SELF'] . '?view=gantt';
$head[0][1] = '<i class="fas fa-chart-bar pictofixedwidth"></i>' . $langs->trans('ActionPlanGantt');
$head[0][2] = 'gantt';

$head[1][0] = $_SERVER['PHP_SELF'] . '?view=kanban';
$head[1][1] = '<i class="fas fa-columns pictofixedwidth"></i>' . $langs->trans('ActionPlanKanban');
$head[1][2] = 'kanban';

print dol_get_fiche_head($head, $view, $title, -1, 'task');

// Include appropriate TPL
if ($view === 'kanban') {
    require_once __DIR__ . '/../../core/tpl/actionplan/actionplan_list_kanban.tpl.php';
} else {
    require_once __DIR__ . '/../../core/tpl/actionplan/actionplan_list_gantt.tpl.php';
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
