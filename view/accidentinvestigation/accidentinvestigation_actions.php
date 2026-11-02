<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/accidentinvestigation/accidentinvestigation_actions.php
 * \ingroup digiriskdolibarr
 * \brief   Tab showing curative and corrective actions for an accident investigation
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load Saturne libraries
dol_include_once('/saturne/class/task/saturnetask.class.php');

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/accidentinvestigation.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accidentinvestigation.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_action.lib.php';

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$action_type = GETPOSTINT('action_type');
$taskid      = GETPOST('taskid', 'int');
$cancel      = GETPOST('cancel', 'aZ09');

// Initialize technical objects
$accident = new Accident($db);
$object   = new AccidentInvestigation($db);
$task     = new Task($db);

$numRefConf          = strtoupper($task->element) . '_ADDON';
$numberingModuleName = ['project/task' => $conf->global->$numRefConf];
list($modTask)       = saturne_require_objects_mod($numberingModuleName);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['accidentinvestigationactions', 'digiriskdolibarrglobal', 'globalcard']);

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once.

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->accidentinvestigation->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accidentinvestigation->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accidentinvestigation->delete;

saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */
if ($cancel) {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

if ($action === 'addAction' && $permissiontoadd) {
    $object->fetch($id);
    $accident->fetch($object->fk_accident);

    // Actions are grouped under the T1/T2 container task created when the investigation is validated
    $containerTaskId = digiriskdolibarr_get_action_container_task($db, (int) $object->fk_task, $action_type);

    $newTask                 = new Task($db);
    $newTask->fk_project     = $accident->fk_project;
    $newTask->fk_task_parent = $containerTaskId > 0 ? $containerTaskId : (int) $object->fk_task;
    $newTask->ref            = $modTask->getNextValue(0, $newTask);

    $newTask->array_options['options_digirisk_action_type']     = (int) $action_type;
    $newTask->array_options['options_fk_accident']              = $accident->id;
    $newTask->array_options['options_fk_accidentinvestigation'] = $object->id;

    $result = digiriskdolibarr_create_action($newTask, (int) $action_type, $user);
    if ($result > 0) {
        setEventMessages($langs->trans('ActionAdded'), []);
    } else {
        setEventMessages($newTask->error, $newTask->errors, 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

// Only the actions of this investigation can be closed or moved from its tab, not any task of the instance
$investigationActionIds = [];
if ($taskid > 0) {
    foreach ([1, 2] as $type) {
        foreach (digiriskdolibarr_get_actions_by_type($db, (int) $object->fk_task, $type) as $actionRow) {
            $investigationActionIds[] = (int) $actionRow->rowid;
        }
    }
}

if ($action === 'closeAction' && $permissiontoadd && in_array((int) $taskid, $investigationActionIds, true)) {
    $taskToClose           = new Task($db);
    $taskToClose->fetch($taskid);
    $taskToClose->progress = 100;
    $result                = $taskToClose->update($user);
    if ($result > 0) {
        setEventMessages($langs->trans('ActionClosed'), []);
    } else {
        setEventMessages($taskToClose->error, [], 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

if ($action === 'updateProgress' && $permissiontoadd && in_array((int) $taskid, $investigationActionIds, true)) {
    $taskToUpdate           = new Task($db);
    $taskToUpdate->fetch($taskid);
    $taskToUpdate->progress = min(100, max(0, GETPOST('progress_value', 'int')));
    $result                 = $taskToUpdate->update($user);
    if ($result <= 0) {
        setEventMessages($taskToUpdate->error, [], 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

// A field edited in place in the table (actions.js): the page sent back refreshes the row, a refusal is told in plain text
if ($action === 'updateActionField' && $permissiontoadd && in_array((int) $taskid, $investigationActionIds, true)) {
    $taskToUpdate = new Task($db);
    $taskToUpdate->fetch((int) $taskid);
    if (digiriskdolibarr_update_action_field($taskToUpdate, GETPOST('field', 'aZ09'), $user) === null) {
        http_response_code(400);
        print dol_escape_htmltag($taskToUpdate->errorsToString());
        exit();
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

/*
 * View
 */
$title   = $langs->trans('AccidentInvestigation');
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

saturne_header(1, '', $title, $helpUrl);

if ($id > 0 || !empty($ref)) {
    $object->fetch($id);
    $accident->fetch($object->fk_accident);

    saturne_get_fiche_head($object, 'actions', $title);
    saturne_banner_tab($object);

    print '<div class="fichecenter">';

    if ((int) $object->fk_task <= 0) {
        print '<div class="opacitymedium center" style="padding:30px">';
        print '<i class="fas fa-info-circle fa-2x" style="color:#888;display:block;margin-bottom:10px"></i>';
        print dol_escape_htmltag($langs->trans('InvestigationNotValidatedYet'));
        print '</div>';
    } else {
        $sectionConfig = [
            1 => ['titleKey' => 'CurativeActions',  'addKey' => 'AddCurativeAction'],
            2 => ['titleKey' => 'CorrectiveActions', 'addKey' => 'AddCorrectiveAction'],
        ];

        $actionsByType = [];
        foreach (array_keys($sectionConfig) as $type) {
            $actionsByType[$type] = digiriskdolibarr_get_actions_by_type($db, (int) $object->fk_task, $type);
        }

        $actionsFormUrl      = $_SERVER['PHP_SELF'] . '?id=' . $id;
        $actionsParentTaskId = (int) $object->fk_task;
        include __DIR__ . '/../../core/tpl/actions/digiriskdolibarr_actions_sections.tpl.php';
    }

    print '</div>'; // fichecenter

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
