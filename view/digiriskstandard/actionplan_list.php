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

// Load ActionComm for event logging
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

/**
 * Create an ActionComm event for task modification if the corresponding config toggle is enabled
 *
 * @param  DoliDB   $db        Database handler
 * @param  User     $user      Current user
 * @param  Translate $langs    Language object
 * @param  object   $task      Task object
 * @param  string   $constName Configuration constant name (e.g. DIGIRISKDOLIBARR_ACTIONPLAN_LOG_LABEL)
 * @param  string   $label     Event label (translated)
 * @param  string   $note      Event note with before/after details
 * @return void
 */
function createActionPlanEvent($db, $user, $langs, $task, $constName, $label, $note)
{
    global $conf;

    if (empty(getDolGlobalInt($constName))) {
        return;
    }

    $actioncomm = new ActionComm($db);
    $actioncomm->type_code    = 'AC_OTH_AUTO';
    $actioncomm->code         = 'AC_TASK_MODIFY';
    $actioncomm->label        = $label;
    $actioncomm->note_private = $note;
    $actioncomm->fk_element   = $task->id;
    $actioncomm->elementtype  = 'project_task';
    $actioncomm->userownerid  = $user->id;
    $actioncomm->datep        = dol_now();
    $actioncomm->percentage   = -1;
    $actioncomm->create($user);
}

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
        $oldProgress = $taskToUpdate->progress;
        $taskToUpdate->progress = $newProgress;
        $updateResult = $taskToUpdate->update($user);

        if ($updateResult > 0) {
            createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_PROGRESS', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogProgress'), $langs->trans('ActionPlanTaskProgressChanged', $oldProgress, $newProgress));
            print json_encode(['success' => 1]);
        } else {
            dol_syslog('ActionPlan Kanban: updateTaskProgress error for task ' . $taskId . ' - ' . $taskToUpdate->error, LOG_ERR);
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
        $oldLabel = $taskToUpdate->label;
        $taskToUpdate->label = $newLabel;
        $updateResult = $taskToUpdate->update($user);

        if ($updateResult > 0) {
            createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_LABEL', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogLabel'), $langs->trans('ActionPlanTaskLabelChanged', $oldLabel, $newLabel));
            print json_encode(['success' => 1, 'label' => $newLabel]);
        } else {
            dol_syslog('ActionPlan Kanban: updateTaskLabel error for task ' . $taskId . ' - ' . $taskToUpdate->error, LOG_ERR);
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
            createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_ADD', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogContributorAdd'), $langs->trans('ActionPlanTaskContributorAdded', $addedFullname));
            print json_encode(['success' => 1, 'fullname' => $addedFullname, 'initials' => $addedInitials, 'user_id' => $userId]);
        } else {
            dol_syslog('ActionPlan Kanban: addTaskContributor error for task ' . $taskId . ' - ' . $taskToUpdate->error, LOG_ERR);
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
            createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_CONTRIBUTOR_REMOVE', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogContributorRemove'), $langs->trans('ActionPlanTaskContributorRemoved', $userId));
            print json_encode(['success' => 1]);
        } else {
            dol_syslog('ActionPlan Kanban: removeTaskContributor error for task ' . $taskId . ' user ' . $userId, LOG_ERR);
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
            $oldWorkload = $taskToUpdate->planned_workload > 0 ? convertSecondToTime($taskToUpdate->planned_workload, 'allhourmin') : '-';
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
                createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_WORKLOAD', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogWorkload'), $langs->trans('ActionPlanTaskWorkloadChanged', $oldWorkload, $fmtValue));
                print json_encode(['success' => 1, 'formatted' => $fmtValue, 'raw' => $rawHours]);
            } else {
                dol_syslog('ActionPlan Kanban: updateTaskMeta workload error for task ' . $taskId . ' - ' . $taskToUpdate->error, LOG_ERR);
                print json_encode(['success' => 0, 'error' => $taskToUpdate->error]);
            }
        } elseif ($field == 'budget') {
            $oldBudget = $taskToUpdate->budget_amount > 0 ? price($taskToUpdate->budget_amount, 0, $langs, 1, -1, -1, $conf->currency) : '-';
            $taskToUpdate->budget_amount = (float) str_replace(',', '.', $value);
            $res = $taskToUpdate->update($user);
            if ($res > 0) {
                $fmtValue = $taskToUpdate->budget_amount > 0 ? price($taskToUpdate->budget_amount, 0, $langs, 1, -1, -1, $conf->currency) : '-';
                createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_BUDGET', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogBudget'), $langs->trans('ActionPlanTaskBudgetChanged', $oldBudget, $fmtValue));
                print json_encode(['success' => 1, 'formatted' => $fmtValue, 'raw' => $taskToUpdate->budget_amount]);
            } else {
                dol_syslog('ActionPlan Kanban: updateTaskMeta budget error for task ' . $taskId . ' - ' . $taskToUpdate->error, LOG_ERR);
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
            $oldDate = ($field == 'date_start') ? $taskToUpdate->date_start : $taskToUpdate->date_end;
            $oldDateFmt = $oldDate > 0 ? dol_print_date($oldDate, 'day') : '-';
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
                $logConst = ($field == 'date_start') ? 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_START' : 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_DATE_END';
                $logLabel = ($field == 'date_start') ? 'ActionPlanLogDateStart' : 'ActionPlanLogDateEnd';
                $logTrans = ($field == 'date_start') ? 'ActionPlanTaskDateStartChanged' : 'ActionPlanTaskDateEndChanged';
                createActionPlanEvent($db, $user, $langs, $taskToUpdate, $logConst, $taskToUpdate->ref . ' - ' . $langs->trans($logLabel), $langs->trans($logTrans, $oldDateFmt, $fmtValue));
                print json_encode(['success' => 1, 'formatted' => $fmtValue, 'raw' => $rawValue]);
            } else {
                dol_syslog('ActionPlan Kanban: updateTaskDate error for task ' . $taskId . ' field ' . $field . ' - ' . $taskToUpdate->error, LOG_ERR);
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
                createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_TAG', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogTag'), $langs->trans('ActionPlanTaskTagAdded', $cat->label));
                print json_encode(['success' => 1, 'label' => $cat->label, 'color' => $cat->color, 'id' => $cat->id]);
            } else {
                dol_syslog('ActionPlan Kanban: addTaskCategory error for task ' . $taskId . ' cat ' . $catId . ' - ' . $cat->error, LOG_ERR);
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
                createActionPlanEvent($db, $user, $langs, $taskToUpdate, 'DIGIRISKDOLIBARR_ACTIONPLAN_LOG_TAG', $taskToUpdate->ref . ' - ' . $langs->trans('ActionPlanLogTag'), $langs->trans('ActionPlanTaskTagRemoved', $cat->label));
                print json_encode(['success' => 1]);
            } else {
                dol_syslog('ActionPlan Kanban: removeTaskCategory error for task ' . $taskId . ' cat ' . $catId . ' - ' . $cat->error, LOG_ERR);
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

// Fetch risk links (fk_risk => tasks) — scoped to current project tasks only
$taskRiskMap = [];
$riskObjects = [];
$riskData    = [];
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
if (!empty($allTasks)) {
    $taskIds = array_map(function ($t) { return (int) $t->id; }, $allTasks);
    $sql  = "SELECT fk_object, fk_risk FROM " . MAIN_DB_PREFIX . "projet_task_extrafields";
    $sql .= " WHERE fk_risk > 0 AND fk_object IN (" . implode(',', $taskIds) . ")";
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $taskRiskMap[(int) $obj->fk_object] = (int) $obj->fk_risk;
        }
        $db->free($resql);
    }
}

if (!empty($taskRiskMap)) {
    $riskIds = array_unique(array_values($taskRiskMap));

    // Batch load all risks — single query via ORM
    $riskLoader  = new Risk($db);
    $riskObjects = $riskLoader->fetchAll('', '', 0, 0, ['customsql' => 't.rowid IN (' . implode(',', $riskIds) . ')']);
    if (!is_array($riskObjects)) {
        $riskObjects = [];
    }

    // Batch load latest validated risk assessment per risk — single query via ORM
    $riskAssessmentLoader = new RiskAssessment($db);
    $allValidatedRA       = $riskAssessmentLoader->fetchAll('DESC', 'date_creation', 0, 0, [
        'customsql' => 'fk_risk IN (' . implode(',', $riskIds) . ') AND status = ' . RiskAssessment::STATUS_VALIDATED,
    ]);
    $latestRAByRisk = [];
    if (is_array($allValidatedRA)) {
        foreach ($allValidatedRA as $ra) {
            if (!isset($latestRAByRisk[$ra->fk_risk])) {
                $latestRAByRisk[$ra->fk_risk] = $ra;
            }
        }
    }

    // Batch load assessor user initials — single query
    $assessorIds    = array_unique(array_filter(array_map(function ($ra) { return (int) $ra->fk_user_creat; }, $latestRAByRisk)));
    $assessorInitials = [];
    if (!empty($assessorIds)) {
        $sqlAssessors = "SELECT rowid, firstname, lastname FROM " . MAIN_DB_PREFIX . "user WHERE rowid IN (" . implode(',', $assessorIds) . ")";
        $resAssessors = $db->query($sqlAssessors);
        if ($resAssessors) {
            while ($objU = $db->fetch_object($resAssessors)) {
                $assessorInitials[(int) $objU->rowid] = strtoupper(mb_substr($objU->firstname, 0, 1) . mb_substr($objU->lastname, 0, 1));
            }
            $db->free($resAssessors);
        }
    }

    // Build riskData map
    foreach ($riskIds as $riskId) {
        if (!isset($riskObjects[$riskId])) {
            continue;
        }
        $riskObj = $riskObjects[$riskId];
        $lastRA  = $latestRAByRisk[$riskId] ?? null;

        $cotation = $lastRA ? (int) $lastRA->cotation : 0;
        if ($cotation >= 80) {
            $cotColor = '#2b2b2b';
        } elseif ($cotation >= 51) {
            $cotColor = '#e05353';
        } elseif ($cotation >= 48) {
            $cotColor = '#e9ad4f';
        } else {
            $cotColor = '#ececec';
        }

        $dangerCatName = $riskObj->getDangerCategoryName($riskObj, $riskObj->type ?: 'risk');
        if ($dangerCatName == -1) {
            $dangerCatName = '';
        }

        $raPhotoUrl = '';
        if ($lastRA) {
            $raDir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $lastRA->ref;
            if (is_dir($raDir)) {
                $raFiles = scandir($raDir);
                foreach ($raFiles as $raFile) {
                    if ($raFile == '.' || $raFile == '..' || $raFile == 'thumbs') {
                        continue;
                    }
                    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $raFile)) {
                        $thumbName = preg_replace('/(\.\w+)$/', '_small$1', $raFile);
                        $thumbPath = $raDir . '/thumbs/' . $thumbName;
                        if (file_exists($thumbPath)) {
                            $raPhotoUrl = DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('riskassessment/' . $lastRA->ref . '/thumbs/' . $thumbName);
                        } else {
                            $raPhotoUrl = DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('riskassessment/' . $lastRA->ref . '/' . $raFile);
                        }
                        break;
                    }
                }
            }
        }

        $riskData[$riskId] = [
            'ref'            => $riskObj->ref,
            'fk_element'     => $riskObj->fk_element,
            'description'    => $riskObj->description,
            'category_name'  => $dangerCatName,
            'cotation'       => $cotation,
            'cotation_color' => $cotColor,
            'ra_ref'         => $lastRA ? $lastRA->ref : '',
            'ra_date'        => $lastRA && $lastRA->date_creation ? dol_print_date($lastRA->date_creation, 'day') : '',
            'ra_comment'     => $lastRA ? $lastRA->comment : '',
            'ra_photo_url'   => $raPhotoUrl,
            'ra_user'        => $lastRA && $lastRA->fk_user_creat > 0 ? ($assessorInitials[$lastRA->fk_user_creat] ?? '') : '',
        ];
    }
}

// Fetch categories/tags for tasks (single batch query instead of N+1)
$taskCategories = [];
$categorie = new Categorie($db);
if (!empty($allTasks)) {
    $taskIds     = array_map(function ($t) { return (int) $t->id; }, $allTasks);
    $sqlTaskCats  = "SELECT ct.fk_project_task, c.rowid, c.label, c.color";
    $sqlTaskCats .= " FROM " . MAIN_DB_PREFIX . "categorie_project_task as ct";
    $sqlTaskCats .= " INNER JOIN " . MAIN_DB_PREFIX . "categorie as c ON ct.fk_categorie = c.rowid";
    $sqlTaskCats .= " WHERE ct.fk_project_task IN (" . implode(',', $taskIds) . ")";
    $sqlTaskCats .= " AND c.entity IN (" . getEntity('category') . ")";
    $resTaskCats = $db->query($sqlTaskCats);
    if ($resTaskCats) {
        while ($objCat = $db->fetch_object($resTaskCats)) {
            $catObj        = new Categorie($db);
            $catObj->id    = (int) $objCat->rowid;
            $catObj->label = $objCat->label;
            $catObj->color = $objCat->color;
            $taskCategories[(int) $objCat->fk_project_task][] = $catObj;
        }
        $db->free($resTaskCats);
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

// Batch file counts for all tasks (single query instead of N+1)
$taskFileCounts = [];
if (!empty($allTasks)) {
    $taskIds       = array_map(function ($t) { return (int) $t->id; }, $allTasks);
    $sqlFileCounts  = "SELECT src_object_id, COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "ecm_files";
    $sqlFileCounts .= " WHERE src_object_type = 'projet_task' AND src_object_id IN (" . implode(',', $taskIds) . ")";
    $sqlFileCounts .= " GROUP BY src_object_id";
    $resFileCounts = $db->query($sqlFileCounts);
    if ($resFileCounts) {
        while ($objFC = $db->fetch_object($resFileCounts)) {
            $taskFileCounts[(int) $objFC->src_object_id] = (int) $objFC->nb;
        }
        $db->free($resFileCounts);
    }
}

// Batch load all task contacts (internal users + external contacts) — 2 queries instead of 2 per task (N+1)
$taskContactsInternal = [];
$taskContactsExternal = [];
if (!empty($allTasks)) {
    $taskIds = array_map(function ($t) { return (int) $t->id; }, $allTasks);

    // Internal contacts (users)
    $sqlCInt  = "SELECT ec.element_id, ec.fk_socpeople as id, tc.code, u.firstname, u.lastname, u.photo";
    $sqlCInt .= " FROM " . MAIN_DB_PREFIX . "element_contact as ec";
    $sqlCInt .= " INNER JOIN " . MAIN_DB_PREFIX . "c_type_contact as tc ON ec.fk_c_type_contact = tc.rowid";
    $sqlCInt .= " INNER JOIN " . MAIN_DB_PREFIX . "user as u ON ec.fk_socpeople = u.rowid";
    $sqlCInt .= " WHERE ec.element_id IN (" . implode(',', $taskIds) . ")";
    $sqlCInt .= " AND tc.element = 'project_task' AND tc.source = 'internal' AND tc.active = 1";
    $resCInt = $db->query($sqlCInt);
    if ($resCInt) {
        while ($objC = $db->fetch_object($resCInt)) {
            $taskContactsInternal[(int) $objC->element_id][] = $objC;
        }
        $db->free($resCInt);
    }

    // External contacts (socpeople)
    $sqlCExt  = "SELECT ec.element_id, ec.fk_socpeople as id, tc.code, sp.firstname, sp.lastname";
    $sqlCExt .= " FROM " . MAIN_DB_PREFIX . "element_contact as ec";
    $sqlCExt .= " INNER JOIN " . MAIN_DB_PREFIX . "c_type_contact as tc ON ec.fk_c_type_contact = tc.rowid";
    $sqlCExt .= " INNER JOIN " . MAIN_DB_PREFIX . "socpeople as sp ON ec.fk_socpeople = sp.rowid";
    $sqlCExt .= " WHERE ec.element_id IN (" . implode(',', $taskIds) . ")";
    $sqlCExt .= " AND tc.element = 'project_task' AND tc.source = 'external' AND tc.active = 1";
    $resCExt = $db->query($sqlCExt);
    if ($resCExt) {
        while ($objC = $db->fetch_object($resCExt)) {
            $taskContactsExternal[(int) $objC->element_id][] = $objC;
        }
        $db->free($resCExt);
    }
}

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

    // Contacts: responsible (TASKEXECUTIVE) and associated people (pre-fetched in batch above)
    $contactsInternal = $taskContactsInternal[$t->id] ?? [];
    $contactsExternal = $taskContactsExternal[$t->id] ?? [];

    $responsible   = [];
    $contributors  = [];
    foreach ($contactsInternal as $c) {
        $photoUrl = '';
        if (!empty($c->photo)) {
            $photoUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $conf->entity . '&file=' . urlencode($c->id . '/thumbs/' . preg_replace('/(\.\w+)$/', '_mini$1', $c->photo));
        }
        $contactInfo = [
            'id'       => (int) $c->id,
            'fullname' => trim($c->firstname . ' ' . $c->lastname),
            'initials' => strtoupper(mb_substr($c->firstname, 0, 1) . mb_substr($c->lastname, 0, 1)),
            'photo'    => $photoUrl,
        ];
        // TASKEXECUTIVE = responsable de la tâche
        if ($c->code == 'TASKEXECUTIVE') {
            $responsible[] = $contactInfo;
        } else {
            $contributors[] = $contactInfo;
        }
    }
    foreach ($contactsExternal as $c) {
        $contactInfo = [
            'id'       => (int) $c->id,
            'fullname' => trim($c->firstname . ' ' . $c->lastname),
            'initials' => strtoupper(mb_substr($c->firstname, 0, 1) . mb_substr($c->lastname, 0, 1)),
            'photo'    => '',
        ];
        if ($c->code == 'TASKCONTRIBUTOR') {
            $contributors[] = $contactInfo;
        }
    }

    // File count (pre-fetched in batch above)
    $fileCount = $taskFileCounts[$t->id] ?? 0;

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
        'risk_data'          => isset($riskData[$riskId]) ? $riskData[$riskId] : [],
        'categories'         => $cats,
        'responsible'        => $responsible,
        'contributors'       => $contributors,
        'file_count'         => $fileCount,
        'budget'             => $budget,
        'budget_fmt'         => $budget > 0 ? price($budget, 0, $langs, 1, -1, -1, $conf->currency) : '',
        'url'                => DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $t->id . '&withproject=1',
    ];
}

// Compute global PAPRIPACT progress: average of all corrective action progress percentages
$globalProgress  = 0;
$globalTaskCount = count($tasksJson);
if ($globalTaskCount > 0) {
    $progressSum = 0;
    foreach ($tasksJson as $t) {
        $progressSum += $t['progress'];
    }
    $globalProgress = (int) round($progressSum / $globalTaskCount);
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
