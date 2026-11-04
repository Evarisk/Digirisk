<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    view/task/task_card.php
 * \ingroup digiriskdolibarr
 * \brief   Digirisk card of a task, opened by every link to a task in Digirisk (action plan, risks, tickets, accident
 *          investigations…), laid out and edited on the spot as the ticket card (view/ticket/ticket_card.php).
 */

// Force-load CKEditor so the rich-text editor is available for the description.
if (!defined('FORCE_CKEDITOR')) {
    define('FORCE_CKEDITOR', 1);
}

// Load Digirisk (and Dolibarr) environment — from custom/digiriskdolibarr/view/task/
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_task.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_action.lib.php';

global $conf, $db, $langs, $user;

saturne_load_langs(['projects', 'ticket', 'categories', 'other']);

$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$now = dol_now();

$object  = new Task($db);
$project = new Project($db);
// The previous / next arrows of the banner (dol_banner_tab) open the card by its ref
if ($id > 0 || !empty($ref)) {
    if ($object->fetch($id, $ref) > 0) {
        $project->fetch($object->fk_project);
        $object->project = $project;
        $id              = $object->id;
    }
}

// Security: the same as the native task card
restrictedArea($user, 'projet', $object->fk_project, 'projet&project');

$permissionToWrite = $user->hasRight('projet', 'creer');
$url_page_current  = dol_buildpath('/digiriskdolibarr/view/task/task_card.php', 1);

// What the task comes from: the ticket it is an action of, the risk of the action plan, the accident investigation…
$origins = $object->id > 0 ? digiriskdolibarr_task_get_origins($db, $object) : [];
$ticket  = $origins['ticket'] ?? null;

// Traceability (#4885) — every edit is written in the history of the ticket of the task, else in that of the task
$logTaskChange = static function (string $fieldLabel, $oldValue, $newValue) use ($object, $ticket): void {
    digiriskdolibarr_task_log_field_change($object, $ticket, $fieldLabel, $oldValue, $newValue);
};

// Responsibles of the task (TASKEXECUTIVE), the first one is the one edited on the spot
$printExecutives = static function () use ($db, $langs, $object): string {
    $executivesHtml = '';
    $executives     = $object->liste_contact(-1, 'internal', 0, 'TASKEXECUTIVE');
    foreach (is_array($executives) ? $executives : [] as $executive) {
        $executiveUser = new User($db);
        if ($executiveUser->fetch((int) $executive['id']) > 0) {
            $executivesHtml .= $executiveUser->getNomUrl(-1) . ' ';
        }
    }

    return $executivesHtml !== '' ? trim($executivesHtml) : '<span class="opacitymedium">' . $langs->trans('NotAssigned') . '</span>';
};

/*
 * Actions
 *
 * The fields are edited by js/modules/ticket.js, under the action names and parameters of the ticket card.
 */

// AJAX: inline (on-the-fly) label save, from the editor of the subject of a ticket
if ($action === 'setsubject_ajax' && $permissionToWrite && $object->id > 0) {
    $oldLabel = (string) $object->label;
    $newLabel = trim(GETPOST('subject', 'alphanohtml'));
    $result   = 0;
    if ($newLabel !== '') {
        $object->label = $newLabel;
        $result        = $object->update($user);
    }
    if ($result > 0) {
        $logTaskChange($langs->transnoentities('Label'), $oldLabel, $newLabel);
    }
    header('Content-Type: application/json');
    print json_encode(['success' => ($result > 0 ? 1 : 0), 'subject' => ($result > 0 ? $object->label : $oldLabel)]);
    exit;
}

// AJAX: inline (on-the-fly) description save, from the editor of the initial message of a ticket
if ($action === 'setmessage_ajax' && $permissionToWrite && $object->id > 0) {
    $oldDescription      = (string) $object->description;
    $object->description = GETPOST('message', 'restricthtml');
    $result              = $object->update($user);
    if ($result > 0) {
        $logTaskChange(
            $langs->transnoentities('Description'),
            dol_trunc(dol_string_nohtmltag($oldDescription), 250),
            dol_trunc(dol_string_nohtmltag((string) $object->description), 250)
        );
    }
    header('Content-Type: application/json');
    print json_encode(['success' => ($result > 0 ? 1 : 0), 'message_html' => dol_htmlentitiesbr((string) $object->description)]);
    exit;
}

// AJAX: inline (on-the-fly) save of the dates, the budget and the planned workload, from the editor of the extrafields
// of a ticket. The value comes back as it is printed: the editor would show the Y-m-d date or the bare amount typed.
if ($action === 'setextrafield_ajax' && $permissionToWrite && $object->id > 0) {
    $field  = GETPOST('field', 'aZ09');
    $change = null;
    if (in_array($field, ['date_start', 'date_end', 'budget'], true)) {
        // Same rules as the actions edited in place: a deadline lasts until the end of its day
        $change = digiriskdolibarr_update_action_field($object, $field, $user);
    } elseif ($field === 'planned_workload') {
        // Typed in hours, 1.5 for an hour and a half
        $hours       = GETPOST('value', 'alpha');
        $oldWorkload = $object->planned_workload;
        $object->planned_workload = $hours !== '' ? (int) round((float) price2num($hours) * 3600) : '';
        if ($object->update($user) > 0) {
            $change = [
                'label' => $langs->transnoentities('PlannedWorkload'),
                'old'   => $oldWorkload !== '' && $oldWorkload !== null ? convertSecondToTime((int) $oldWorkload, 'allhourmin') : '',
                'new'   => $object->planned_workload !== '' ? convertSecondToTime((int) $object->planned_workload, 'allhourmin') : '',
            ];
        }
    }

    header('Content-Type: application/json');
    if ($change === null) {
        // An error status, so that the editor puts the previous value back
        http_response_code(400);
        print json_encode(['success' => 0, 'error' => implode(', ', $object->errors ?: [$object->error ?: $langs->trans('ErrorBadParameters')])]);
        exit;
    }
    $logTaskChange($change['label'], $change['old'], $change['new']);
    print json_encode(['success' => 1, 'field' => $field, 'display' => $change['new']]);
    exit;
}

// AJAX: inline (on-the-fly) responsible save, from the editor of the assignee of a ticket — "id" carries the task id
if ($action === 'setassignee_ajax' && $permissionToWrite && $object->id > 0) {
    $change = digiriskdolibarr_set_action_assignee($object, GETPOSTINT('user_id'));
    if ($change !== null) {
        $logTaskChange($change['label'], $change['old'], $change['new']);
    }
    header('Content-Type: application/json');
    print json_encode(['success' => ($change !== null ? 1 : 0), 'nomurl' => $printExecutives()]);
    exit;
}

// AJAX: inline (on-the-fly) progress save — "id" carries the task id
if ($action === 'setprogress_ajax' && $permissionToWrite && $object->id > 0) {
    $oldProgress      = (int) $object->progress;
    $object->progress = min(max(GETPOSTINT('progress'), 0), 100);
    $result           = $object->update($user);
    if ($result > 0) {
        $logTaskChange($langs->transnoentities('Progression'), $oldProgress . ' %', $object->progress . ' %');
    }
    header('Content-Type: application/json');
    print json_encode(['success' => ($result > 0 ? 1 : 0), 'progress' => ($result > 0 ? (int) $object->progress : $oldProgress)]);
    exit;
}

// AJAX: add / remove a tag, from the tag picker of a ticket, whose action names are kept
if (in_array($action, ['addTicketCategory', 'removeTicketCategory'], true) && $permissionToWrite && $object->id > 0) {
    $category = new Categorie($db);
    $oldTags  = implode(', ', array_column(digiriskdolibarr_get_actions_tags($db, [$object->id])[$object->id] ?? [], 'label'));
    $result   = -1;
    if ($category->fetch(GETPOSTINT('cat_id')) > 0) {
        // -3: already linked
        $result = $action === 'addTicketCategory' ? $category->add_type($object, Categorie::TYPE_PROJECT_TASK) : $category->del_type($object, Categorie::TYPE_PROJECT_TASK);
    }
    header('Content-Type: application/json');
    if ($result >= 0 || $result == -3) {
        $logTaskChange($langs->transnoentities('Categories'), $oldTags, implode(', ', array_column(digiriskdolibarr_get_actions_tags($db, [$object->id])[$object->id] ?? [], 'label')));
        print json_encode(['success' => 1, 'label' => $category->label, 'color' => $category->color, 'id' => $category->id]);
    } else {
        print json_encode(['success' => 0, 'error' => $category->error]);
    }
    exit;
}

// Action: change the status of the task, as the buttons of the native task card
if ($action === 'set_task_status' && $permissionToWrite && $object->id > 0) {
    $newStatus = GETPOSTINT('status');
    if (in_array($newStatus, [Task::STATUS_DRAFT, Task::STATUS_VALIDATED, Task::STATUS_CLOSED], true)) {
        $oldStatus = $object->getLibStatut(1);
        if ($object->setStatusCommon($user, $newStatus) >= 0) {
            $logTaskChange($langs->transnoentities('Status'), $oldStatus, $object->getLibStatut(1));
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    header('Location: ' . $url_page_current . '?id=' . $object->id);
    exit;
}

// Files of the task, as on the native task card: generated in, and deleted from, its directory in the project one
$permissiontoadd = $permissionToWrite ? 1 : 0;
$upload_dir      = $conf->project->multidir_output[$object->entity ?? $conf->entity] ?? $conf->project->dir_output;
if ($object->id > 0) {
    include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';
    // The generation fetches the task again, without its project
    $object->project = $project;
}

/*
 * View
 */

if ($object->id <= 0) {
    saturne_header(0, '', $langs->trans('Task'), '');
    print '<div class="error">' . $langs->trans('ErrorRecordNotFound') . '</div>';
    llxFooter();
    $db->close();
    exit;
}

$form     = new Form($db);
$userstat = new User($db);

// Back to where the task comes from: its ticket, its accident investigation, the action plan of its risk, else the
// tasks of its project
$ticketUrl = $ticket !== null ? dol_buildpath('/digiriskdolibarr/view/ticket/ticket_card.php', 1) . '?id=' . (int) $ticket->id : '';
if ($ticket !== null) {
    $linkbackUrl   = $ticketUrl;
    $linkbackLabel = 'BackToTicket';
} elseif ($origins['accidentInvestigation'] !== null) {
    $linkbackUrl   = dol_buildpath('/digiriskdolibarr/view/accidentinvestigation/accidentinvestigation_actions.php', 1) . '?id=' . (int) $origins['accidentInvestigation']->id;
    $linkbackLabel = 'BackToAccidentInvestigation';
} elseif ($origins['risk'] !== null) {
    $linkbackUrl   = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/actionplan_list.php', 1) . '?projectid=' . (int) $object->fk_project;
    $linkbackLabel = 'BackToActionPlan';
} else {
    $linkbackUrl   = DOL_URL_ROOT . '/projet/tasks.php?id=' . (int) $object->fk_project;
    $linkbackLabel = 'BackToList';
}

$title   = $langs->trans('Task') . ' ' . $object->ref;
$helpUrl = 'FR:Module_Digirisk';
saturne_header(0, '', $title, $helpUrl);

require_once __DIR__ . '/../../core/tpl/task/task_card_view.tpl.php';

llxFooter();
$db->close();
