<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/ticket/ticket_actions.php
 * \ingroup digiriskdolibarr
 * \brief   Tab of the corrective and preventive actions of a ticket (#5549). The actions are project tasks typed by
 *          digirisk_action_type, under one container task per type, itself a child of the parent task "TKP-<ref>" of
 *          the ticket. Same tab as the actions of the accident investigation (core/tpl/actions/).
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_action.lib.php';

// Load translation files required by the page
saturne_load_langs(['ticket', 'projects']);

// Get parameters
$id         = GETPOSTINT('id');
$ref        = GETPOST('ref', 'alpha');
$trackId    = GETPOST('track_id', 'alpha');
$action     = GETPOST('action', 'aZ09');
$actionType = GETPOSTINT('action_type');
$taskid     = GETPOSTINT('taskid');

// Initialize technical objects
$object = new Ticket($db);
$task   = new Task($db);

$numRefConf          = strtoupper($task->element) . '_ADDON';
$numberingModuleName = ['project/task' => getDolGlobalString($numRefConf)];
list($modTask)       = saturne_require_objects_mod($numberingModuleName);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['ticketactions', 'digiriskdolibarrglobal', 'globalcard']);

if ($id > 0 || !empty($ref) || !empty($trackId)) {
    $object->fetch($id, $ref, $trackId);
}

// Security check - Protection if external user
$permissiontoread = $user->hasRight('ticket', 'read') && $user->hasRight('projet', 'lire') && isModEnabled('project');
$permissiontoadd  = $user->hasRight('ticket', 'write') && $user->hasRight('projet', 'creer') && !$user->socid;

saturne_check_access($permissiontoread, $object);

// Traceability (#4885): the changes are logged on the ticket like those made from its card
$ticketLogConst = 'DIGIRISKDOLIBARR_TICKET_LOG_MODIFICATIONS';
$sectionConfig  = digiriskdolibarr_ticket_action_sections();
$pageUrl        = $_SERVER['PHP_SELF'] . '?id=' . (int) $object->id;

$parentTaskId  = digiriskdolibarr_ticket_get_parent_task($db, $object);
$actionsByType = digiriskdolibarr_ticket_get_actions($db, $parentTaskId);

// Only the actions of this ticket can be closed or moved from here
$ticketActionIds = [];
foreach ($actionsByType as $actions) {
    foreach ($actions as $actionRow) {
        $ticketActionIds[] = (int) $actionRow->rowid;
    }
}

/*
 * Actions
 */

if ($action === 'addAction' && $permissiontoadd && isset($sectionConfig[$actionType]) && $object->id > 0 && !empty($object->fk_project)) {
    if ($parentTaskId <= 0) {
        $parentTaskId = digiriskdolibarr_ticket_create_parent_task($object, $user, $ticketLogConst);
        if ($parentTaskId <= 0) {
            header('Location: ' . $pageUrl);
            exit;
        }
    }

    // One container task per type, created with the first action of the type
    $containerTaskId = digiriskdolibarr_get_action_container_task($db, $parentTaskId, $actionType);
    if ($containerTaskId <= 0) {
        $containerTask                 = new Task($db);
        $containerTask->fk_project     = $object->fk_project;
        $containerTask->fk_task_parent = $parentTaskId;
        $containerTask->ref            = $modTask->getNextValue(0, $containerTask);
        $containerTask->label          = $object->ref . ' - ' . $langs->transnoentities($sectionConfig[$actionType]['titleKey']);
        $containerTask->progress       = 0;

        $containerTask->array_options['options_digirisk_action_type'] = $actionType;

        $containerTaskId = $containerTask->create($user);
        if ($containerTaskId <= 0) {
            setEventMessages($containerTask->error, $containerTask->errors, 'errors');
            header('Location: ' . $pageUrl);
            exit;
        }
    }

    // Each section renders its own form, so field names are suffixed with the action type
    $deadlinePrefix = 'actiondeadline' . $actionType;
    $startPrefix    = 'actionstart' . $actionType;

    $newTask                 = new Task($db);
    $newTask->fk_project     = $object->fk_project;
    $newTask->fk_task_parent = $containerTaskId;
    $newTask->ref            = $modTask->getNextValue(0, $newTask);
    $newTask->label          = GETPOST('action_label', 'alphanohtml');
    $newTask->date_start     = dol_mktime(0, 0, 0, GETPOSTINT($startPrefix . 'month'), GETPOSTINT($startPrefix . 'day'), GETPOSTINT($startPrefix . 'year'));
    // Task::create() only writes date_end, the datee property is deprecated and ignored
    $newTask->date_end       = dol_mktime(23, 59, 59, GETPOSTINT($deadlinePrefix . 'month'), GETPOSTINT($deadlinePrefix . 'day'), GETPOSTINT($deadlinePrefix . 'year'));
    $newTask->progress       = 0;
    // Left empty, the action gets no budget rather than a zero one
    if (GETPOST('action_budget', 'alpha') !== '') {
        $newTask->budget_amount = (float) price2num(GETPOST('action_budget', 'alpha'));
    }

    $newTask->array_options['options_digirisk_action_type'] = $actionType;

    $result = $newTask->create($user);
    if ($result > 0) {
        $assignedUser = GETPOSTINT('assigned_user' . $actionType);
        if ($assignedUser > 0) {
            $newTask->add_contact($assignedUser, 'TASKEXECUTIVE', 'internal');
        }
        digiriskdolibarr_ticket_log_event($object, $langs->transnoentities('TicketActionCreated', $newTask->ref), $langs->transnoentities($sectionConfig[$actionType]['titleKey']) . ' : ' . $newTask->label, $ticketLogConst);
        setEventMessages($langs->trans('ActionAdded'), []);
    } else {
        setEventMessages($newTask->error, $newTask->errors, 'errors');
    }

    header('Location: ' . $pageUrl);
    exit;
}

if (($action === 'closeAction' || $action === 'updateProgress') && $permissiontoadd && in_array($taskid, $ticketActionIds, true)) {
    $actionTask = new Task($db);
    $actionTask->fetch($taskid);

    $oldProgress          = (int) $actionTask->progress;
    $actionTask->progress = $action === 'closeAction' ? 100 : min(100, max(0, GETPOSTINT('progress_value')));

    $result = $actionTask->update($user);
    if ($result > 0) {
        digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('Progression') . ' — ' . $actionTask->ref, $oldProgress . ' %', $actionTask->progress . ' %', $ticketLogConst);
        if ($action === 'closeAction') {
            setEventMessages($langs->trans('ActionClosed'), []);
        }
    } else {
        setEventMessages($actionTask->error, $actionTask->errors, 'errors');
    }

    header('Location: ' . $pageUrl);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('Ticket') . ' ' . $object->ref . ' - ' . $langs->trans('TicketActionsTab');
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $helpUrl);

$head = digiriskdolibarr_ticket_prepare_head($object);
print dol_get_fiche_head($head, 'digiriskactions', $langs->trans('Ticket'), -1, 'ticket');

$morehtmlref  = '<div class="refidno">';
$morehtmlref .= '<span class="opacitymedium">' . $langs->trans('TicketTrackId') . ' : </span>' . dolPrintLabel($object->track_id);
if (!empty($object->fk_project)) {
    $project = new Project($db);
    if ($project->fetch((int) $object->fk_project) > 0) {
        $morehtmlref .= '<br>' . $project->getNomUrl(1) . ($project->title ? ' - ' . dol_escape_htmltag($project->title) : '');
    }
}
$morehtmlref .= '</div>';

$linkback = '<a href="' . dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1) . '"><strong>' . $langs->trans('BackToList') . '</strong></a>';

dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

if (empty($object->fk_project)) {
    print '<div class="opacitymedium center" style="padding:30px">';
    print '<i class="fas fa-info-circle fa-2x" style="color:#888;display:block;margin-bottom:10px"></i>';
    print dol_escape_htmltag($langs->trans('TicketActionsNeedProject'));
    print '</div>';
} else {
    $actionsFormUrl      = $pageUrl;
    $actionsParentTaskId = $parentTaskId;
    include __DIR__ . '/../../core/tpl/actions/digiriskdolibarr_actions_sections.tpl.php';
}

print '</div>'; // fichecenter

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
