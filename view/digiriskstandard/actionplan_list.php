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

// AJAX action to update task progress from Kanban drag & drop
if ($action == 'updateTaskProgress' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

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
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found or wrong project']);
    }
    $db->close();
    exit;
}

// AJAX action to update task label (inline edit from Kanban)
if ($action == 'updateTaskLabel' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId   = GETPOSTINT('task_id');
    $newLabel = GETPOST('new_label', 'alphanohtml');

    if (empty($newLabel)) {
        http_response_code(400);
        print json_encode(['success' => 0, 'error' => 'Empty label']);
        $db->close();
        exit;
    }

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        $taskToUpdate->label = $newLabel;
        $updateResult = $taskToUpdate->update($user);

        if ($updateResult > 0) {
            print json_encode(['success' => 1, 'label' => $newLabel]);
        } else {
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found or wrong project']);
    }
    $db->close();
    exit;
}

// AJAX action to update task responsible (TASKEXECUTIVE contact)
if ($action == 'updateTaskResponsible' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId    = GETPOSTINT('task_id');
    $newUserId = GETPOSTINT('user_id'); // 0 = remove

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        // Remove existing TASKEXECUTIVE contacts
        $existingContacts = $taskToUpdate->liste_contact(-1, 'internal');
        if (is_array($existingContacts)) {
            foreach ($existingContacts as $c) {
                if ($c['code'] == 'TASKEXECUTIVE') {
                    $taskToUpdate->delete_contact($c['rowid']);
                }
            }
        }

        // Add new one if user_id > 0
        if ($newUserId > 0) {
            $addResult = $taskToUpdate->add_contact($newUserId, 'TASKEXECUTIVE', 'internal');
            if ($addResult > 0) {
                // Get user name for response
                $newUser = new User($db);
                $newUser->fetch($newUserId);
                print json_encode(['success' => 1, 'fullname' => $newUser->getFullName($langs)]);
            } else {
                http_response_code(500);
                print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
            }
        } else {
            print json_encode(['success' => 1, 'fullname' => '']);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found']);
    }
    $db->close();
    exit;
}

// AJAX action to add a contributor (TASKCONTRIBUTOR contact)
if ($action == 'addTaskContributor' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId = GETPOSTINT('task_id');
    $userId = GETPOSTINT('user_id');

    if ($userId <= 0) {
        http_response_code(400);
        print json_encode(['success' => 0, 'error' => 'No user selected']);
        $db->close();
        exit;
    }

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        $addResult = $taskToUpdate->add_contact($userId, 'TASKCONTRIBUTOR', 'internal');
        if ($addResult > 0) {
            // Count contributors after addition
            $contacts = $taskToUpdate->liste_contact(-1, 'internal');
            $contribCount = 0;
            $contribNames = [];
            if (is_array($contacts)) {
                foreach ($contacts as $c) {
                    if ($c['code'] != 'TASKEXECUTIVE') {
                        $contribCount++;
                        $contribNames[] = trim($c['firstname'] . ' ' . $c['lastname']);
                    }
                }
            }
            print json_encode(['success' => 1, 'count' => $contribCount, 'names' => implode(', ', $contribNames)]);
        } else {
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found']);
    }
    $db->close();
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ActionPlanView');
$help_url = 'FR:Module_Digirisk#Plan_d_action';

saturne_header(0, '', $title, $help_url);

// Hidden token for AJAX requests (normally provided by digirisk_header sidebar, but we use saturne_header for full-width)
print '<input type="hidden" name="token" value="' . newToken() . '">';

// Load all internal users for responsible dropdown
$allUsers = [];
$sqlUsers = "SELECT u.rowid, u.firstname, u.lastname, u.photo FROM " . MAIN_DB_PREFIX . "user u WHERE u.statut = 1 AND u.entity IN (" . getEntity('user') . ") ORDER BY u.lastname, u.firstname";
$resUsers = $db->query($sqlUsers);
if ($resUsers) {
    while ($objU = $db->fetch_object($resUsers)) {
        $photoUrl = '';
        if (!empty($objU->photo)) {
            $photoUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $conf->entity . '&file=' . urlencode($objU->rowid . '/thumbs/' . preg_replace('/(\.\w+)$/', '_mini$1', $objU->photo));
        }
        $allUsers[] = [
            'id'       => (int) $objU->rowid,
            'fullname' => trim($objU->firstname . ' ' . $objU->lastname),
            'photo'    => $photoUrl,
        ];
    }
    $db->free($resUsers);
}

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

// Prepare enriched data for templates
$tasksJson = [];
foreach ($allTasks as $t) {
    // Risk data
    $riskRef     = '';
    $riskId      = 0;
    $riskNomUrl  = '';
    if (isset($taskRiskMap[$t->id]) && isset($riskObjects[$taskRiskMap[$t->id]])) {
        $riskObj    = $riskObjects[$taskRiskMap[$t->id]];
        $riskRef    = $riskObj->ref;
        $riskId     = $riskObj->id;
        $riskNomUrl = $riskObj->getNomUrl(1);
    }

    // Categories
    $cats = [];
    if (isset($taskCategories[$t->id])) {
        foreach ($taskCategories[$t->id] as $cat) {
            $cats[] = ['id' => $cat->id, 'label' => $cat->label, 'color' => $cat->color];
        }
    }

    // Contacts: responsible (TASKEXECUTIVE) and associated people
    $taskObj = new SaturneTask($db);
    $taskObj->fetch($t->id);
    $contactsInternal = $taskObj->liste_contact(-1, 'internal');
    $contactsExternal = $taskObj->liste_contact(-1, 'external');

    $responsible   = [];
    $contributors  = [];
    if (is_array($contactsInternal)) {
        foreach ($contactsInternal as $c) {
            // Build photo URL
            $photoUrl = '';
            $userTmp = new User($db);
            if ($userTmp->fetch($c['id']) > 0 && !empty($userTmp->photo)) {
                $photoUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $conf->entity . '&file=' . urlencode($userTmp->id . '/thumbs/' . preg_replace('/(\.\w+)$/', '_mini$1', $userTmp->photo));
            }
            $contactInfo = [
                'id'       => $c['id'],
                'fullname' => trim($c['firstname'] . ' ' . $c['lastname']),
                'photo'    => $photoUrl,
            ];
            // TASKEXECUTIVE = responsable de la tâche
            if ($c['code'] == 'TASKEXECUTIVE') {
                $responsible[] = $contactInfo;
            } else {
                $contributors[] = $contactInfo;
            }
        }
    }
    if (is_array($contactsExternal)) {
        foreach ($contactsExternal as $c) {
            $associated[] = [
                'id'       => $c['id'],
                'fullname' => trim($c['firstname'] . ' ' . $c['lastname']),
                'photo'    => '',
            ];
        }
    }

    // File count
    $fileCount = 0;
    $sqlFiles  = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "ecm_files WHERE src_object_type = 'projet_task' AND src_object_id = " . ((int) $t->id);
    $resFiles  = $db->query($sqlFiles);
    if ($resFiles) {
        $objFiles  = $db->fetch_object($resFiles);
        $fileCount = (int) $objFiles->nb;
        $db->free($resFiles);
    }

    // Budget
    $budget = property_exists($t, 'budget_amount') ? (float) $t->budget_amount : 0;

    $tasksJson[] = [
        'id'                 => $t->id,
        'ref'                => $t->ref,
        'label'              => $t->label,
        'date_start'         => $t->dateo ? dol_print_date($t->dateo, 'dayrfc') : '',
        'date_start_fmt'     => $t->dateo ? dol_print_date($t->dateo, 'day') : '',
        'date_end'           => $t->datee ? dol_print_date($t->datee, 'dayrfc') : '',
        'date_end_fmt'       => $t->datee ? dol_print_date($t->datee, 'day') : '',
        'planned_workload'   => $t->planned_workload,
        'planned_workload_fmt' => $t->planned_workload > 0 ? convertSecondToTime($t->planned_workload, 'allhourmin') : '',
        'duration_effective' => $t->duration_effective,
        'progress'           => (int) $t->progress,
        'status'             => (int) $t->fk_statut,
        'risk_ref'           => $riskRef,
        'risk_id'            => $riskId,
        'risk_nomurl'        => $riskNomUrl,
        'categories'         => $cats,
        'responsible'        => $responsible,
        'contributors'       => $contributors,
        'file_count'         => $fileCount,
        'budget'             => $budget,
        'budget_fmt'         => $budget > 0 ? price($budget, 0, $langs, 1, -1, -1, $conf->currency) : '',
        'url'                => DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $t->id . '&withproject=1',
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
