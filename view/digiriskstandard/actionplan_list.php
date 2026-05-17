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
    $view = 'kanban';
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
        $source = GETPOST('source', 'alpha');
        if (empty($source) || !in_array($source, ['internal', 'external'])) {
            $source = 'internal';
        }
        $addResult = $taskToUpdate->add_contact($userId, 'TASKCONTRIBUTOR', $source);
        if ($addResult > 0) {
            // Fetch fullname for the added contact
            $addedFullname = '';
            $addedInitials = '';
            if ($source == 'internal') {
                $addedUser = new User($db);
                if ($addedUser->fetch($userId) > 0) {
                    $addedFullname = $addedUser->getFullName($langs);
                    $addedInitials = strtoupper(mb_substr($addedUser->firstname, 0, 1) . mb_substr($addedUser->lastname, 0, 1));
                }
            } else {
                require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
                $addedContact = new Contact($db);
                if ($addedContact->fetch($userId) > 0) {
                    $addedFullname = $addedContact->getFullName($langs);
                    $addedInitials = strtoupper(mb_substr($addedContact->firstname, 0, 1) . mb_substr($addedContact->lastname, 0, 1));
                }
            }
            print json_encode(['success' => 1, 'fullname' => $addedFullname, 'initials' => $addedInitials, 'user_id' => $userId]);
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

// AJAX action to remove a contributor from a task
if ($action == 'removeTaskContributor' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId = GETPOSTINT('task_id');
    $userId = GETPOSTINT('user_id');

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        // Find the contact line ID to delete (check both internal and external)
        $allContacts = array_merge(
            (array) $taskToUpdate->liste_contact(-1, 'internal'),
            (array) $taskToUpdate->liste_contact(-1, 'external')
        );
        $deleted = false;
        foreach ($allContacts as $c) {
            if ($c['id'] == $userId && $c['code'] == 'TASKCONTRIBUTOR') {
                $res = $taskToUpdate->delete_contact($c['rowid']);
                $deleted = ($res >= 0);
                break;
            }
        }
        if ($deleted) {
            print json_encode(['success' => 1]);
        } else {
            print json_encode(['success' => 0, 'error' => 'Contact not found or delete failed']);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found']);
    }
    $db->close();
    exit;
}

// AJAX action to update task workload or budget
if ($action == 'updateTaskMeta' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId = GETPOSTINT('task_id');
    $field  = GETPOST('field', 'alpha');
    $value  = GETPOST('value', 'alpha');

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        if ($field == 'planned_workload') {
            // Accept HH:MM (e.g. "22:35") or decimal hours (e.g. "14.5")
            $value = trim($value);
            if (preg_match('/^(\d+):(\d{1,2})$/', $value, $m)) {
                $totalSeconds = ((int) $m[1]) * 3600 + ((int) $m[2]) * 60;
            } else {
                $hours = (float) str_replace(',', '.', $value);
                $totalSeconds = (int) ($hours * 3600);
            }
            $taskToUpdate->planned_workload = $totalSeconds;
            $res = $taskToUpdate->update($user);
            if ($res > 0) {
                $fmtValue = $taskToUpdate->planned_workload > 0 ? convertSecondToTime($taskToUpdate->planned_workload, 'allhourmin') : '-';
                $rawHours = round($taskToUpdate->planned_workload / 3600, 4);
                print json_encode(['success' => 1, 'formatted' => $fmtValue, 'raw' => $rawHours]);
            } else {
                print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
            }
        } elseif ($field == 'budget') {
            $taskToUpdate->budget_amount = (float) str_replace(',', '.', $value);
            $res = $taskToUpdate->update($user);
            if ($res > 0) {
                $fmtValue = $taskToUpdate->budget_amount > 0 ? price($taskToUpdate->budget_amount, 0, $langs, 1, -1, -1, $conf->currency) : '-';
                print json_encode(['success' => 1, 'formatted' => $fmtValue, 'raw' => $taskToUpdate->budget_amount]);
            } else {
                print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
            }
        } else {
            print json_encode(['success' => 0, 'error' => 'Unknown field']);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found']);
    }
    $db->close();
    exit;
}

// AJAX action to update task date (date_start or date_end)
if ($action == 'updateTaskDate' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId = GETPOSTINT('task_id');
    $field  = GETPOST('field', 'alpha');
    $value  = GETPOST('value', 'alpha');

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        if (in_array($field, ['date_start', 'date_end'])) {
            $timestamp = !empty($value) ? strtotime($value) : 0;
            if ($field == 'date_start') {
                $taskToUpdate->date_start = $timestamp > 0 ? $timestamp : null;
            } else {
                $taskToUpdate->date_end = $timestamp > 0 ? $timestamp : null;
            }
            $res = $taskToUpdate->update($user);
            if ($res > 0) {
                $fmtValue = $timestamp > 0 ? dol_print_date($timestamp, 'day') : '-';
                $rawValue = $timestamp > 0 ? dol_print_date($timestamp, 'dayrfc') : '';
                print json_encode(['success' => 1, 'formatted' => $fmtValue, 'raw' => $rawValue]);
            } else {
                print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
            }
        } else {
            print json_encode(['success' => 0, 'error' => 'Unknown field']);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found']);
    }
    $db->close();
    exit;
}

// AJAX action to add a category to a task
if ($action == 'addTaskCategory' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId = GETPOSTINT('task_id');
    $catId  = GETPOSTINT('cat_id');

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        $cat = new Categorie($db);
        $resCat = $cat->fetch($catId);
        if ($resCat > 0) {
            $res = $cat->add_type($taskToUpdate, 'project_task');
            if ($res > 0 || $res == -3) { // -3 = already linked
                print json_encode(['success' => 1, 'label' => $cat->label, 'color' => $cat->color, 'id' => $cat->id]);
            } else {
                print json_encode(['success' => 0, 'error' => $cat->error]);
            }
        } else {
            print json_encode(['success' => 0, 'error' => 'Category not found']);
        }
    } else {
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Task not found']);
    }
    $db->close();
    exit;
}

// AJAX action to remove a category from a task
if ($action == 'removeTaskCategory' && !empty(GETPOSTINT('task_id'))) {
    header('Content-Type: application/json');

    $taskId = GETPOSTINT('task_id');
    $catId  = GETPOSTINT('cat_id');

    $taskToUpdate = new SaturneTask($db);
    $result = $taskToUpdate->fetch($taskId);

    if ($result > 0 && $taskToUpdate->fk_project == $projectId) {
        $cat = new Categorie($db);
        $resCat = $cat->fetch($catId);
        if ($resCat > 0) {
            $res = $cat->del_type($taskToUpdate, 'project_task');
            if ($res >= 0) {
                print json_encode(['success' => 1]);
            } else {
                print json_encode(['success' => 0, 'error' => $cat->error]);
            }
        } else {
            print json_encode(['success' => 0, 'error' => 'Category not found']);
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
            'initials' => strtoupper(mb_substr($objU->firstname, 0, 1) . mb_substr($objU->lastname, 0, 1)),
            'photo'    => $photoUrl,
        ];
    }
    $db->free($resUsers);
}

// Load all external contacts (societe contacts) for contributor dropdown
$allContacts = [];
$sqlContacts = "SELECT sp.rowid, sp.firstname, sp.lastname, s.nom as society_name FROM " . MAIN_DB_PREFIX . "socpeople sp";
$sqlContacts .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = sp.fk_soc";
$sqlContacts .= " WHERE sp.statut = 1 AND sp.entity IN (" . getEntity('contact') . ")";
$sqlContacts .= " ORDER BY sp.lastname, sp.firstname";
$resContacts = $db->query($sqlContacts);
if ($resContacts) {
    while ($objC = $db->fetch_object($resContacts)) {
        $fullname = trim($objC->firstname . ' ' . $objC->lastname);
        if (!empty($objC->society_name)) {
            $fullname .= ' (' . $objC->society_name . ')';
        }
        $allContacts[] = [
            'id'       => (int) $objC->rowid,
            'fullname' => $fullname,
        ];
    }
    $db->free($resContacts);
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

// Load all available categories for tag selector (project + project_task types)
$allAvailableCategories = [];
$projectTaskType = (int)$categorie->MAP_ID['project_task'];
$projectType     = (int)$categorie->MAP_ID['project'];
$sqlCats = "SELECT rowid, label, color FROM " . MAIN_DB_PREFIX . "categorie WHERE type IN (" . $projectType . ", " . $projectTaskType . ") AND entity IN (" . getEntity('category') . ") ORDER BY label";
$resCats = $db->query($sqlCats);
if ($resCats) {
    while ($objCat = $db->fetch_object($resCats)) {
        $allAvailableCategories[] = ['id' => (int)$objCat->rowid, 'label' => $objCat->label, 'color' => $objCat->color];
    }
    $db->free($resCats);
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
                'initials' => strtoupper(mb_substr($c['firstname'], 0, 1) . mb_substr($c['lastname'], 0, 1)),
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
            $contactInfo = [
                'id'       => $c['id'],
                'fullname' => trim($c['firstname'] . ' ' . $c['lastname']),
                'initials' => strtoupper(mb_substr($c['firstname'], 0, 1) . mb_substr($c['lastname'], 0, 1)),
                'photo'    => '',
            ];
            if ($c['code'] == 'TASKCONTRIBUTOR') {
                $contributors[] = $contactInfo;
            }
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
        'date_start'         => $t->date_start ? dol_print_date($t->date_start, 'dayrfc') : '',
        'date_start_fmt'     => $t->date_start ? dol_print_date($t->date_start, 'day') : '',
        'date_end'           => $t->date_end ? dol_print_date($t->date_end, 'dayrfc') : '',
        'date_end_fmt'       => $t->date_end ? dol_print_date($t->date_end, 'day') : '',
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
$head[0][0] = $_SERVER['PHP_SELF'] . '?view=kanban';
$head[0][1] = '<i class="fas fa-columns pictofixedwidth"></i>' . $langs->trans('ActionPlanKanban');
$head[0][2] = 'kanban';

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
