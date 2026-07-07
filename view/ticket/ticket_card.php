<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    view/ticket/ticket_card.php
 * \ingroup digiriskdolibarr
 * \brief   Ticket card with native Dolibarr layout — Issue #4443.
 */

// Force-load CKEditor so the rich-text editor is available for the initial message.
if (!defined('FORCE_CKEDITOR')) {
    define('FORCE_CKEDITOR', 1);
}

// Load Digirisk (and Dolibarr) environment — from custom/digiriskdolibarr/view/ticket/
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

saturne_load_langs(['ticket', 'companies', 'projects', 'categories']);

$id      = GETPOSTINT('id');
$trackId = GETPOST('track_id', 'alpha');
$action  = GETPOST('action', 'aZ09');

$now = dol_now();

$object = new Ticket($db);
if ($id > 0 || !empty($trackId)) {
    $object->fetch($id, '', $trackId);
    if ($object->id > 0) {
        $object->fetch_optionals();
    }
}

// Security
if (!$user->hasRight('ticket', 'read')) {
    accessforbidden();
}

$permissionToWrite = $user->hasRight('ticket', 'write') && !$user->socid;
$url_page_current  = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_card.php', 1);
// Kanban AJAX endpoint (reused for on-the-fly tags & assignee — same server behaviour, incl. kanban event logging).
$kanbanAjaxUrl     = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1);

/*
 * Actions
 */

// AJAX: inline (on-the-fly) subject save
if ($action === 'setsubject_ajax' && $permissionToWrite) {
    $newSubject      = GETPOST('subject', 'alphanohtml');
    $object->fetch($id);
    $oldSubject      = $object->subject;
    $object->subject = $newSubject;
    if ($object->update($user) > 0 && trim($newSubject) !== trim($oldSubject)) {
        $actioncomm = new ActionComm($db);
        $actioncomm->type_code   = 'AC_OTH_AUTO';
        $actioncomm->code        = 'AC_TICKET_MODIFY';
        $actioncomm->label       = $langs->trans('Subject') . ' : ' . $oldSubject . ' -> ' . $newSubject;
        $actioncomm->fk_element  = $object->id;
        $actioncomm->elementtype = 'ticket';
        $actioncomm->datep       = dol_now();
        $actioncomm->userownerid = $user->id;
        $actioncomm->percentage  = -1;
        $actioncomm->create($user);
    }
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'subject' => $object->subject]);
    exit;
}

// AJAX: inline (on-the-fly) extrafield save
if ($action === 'setextrafield_ajax' && $permissionToWrite) {
    $field = GETPOST('field', 'alpha');
    $value = GETPOST('value', 'none');
    $object->fetch($id);
    $object->array_options['options_' . $field] = $value;
    $res = $object->insertExtraFields();
    header('Content-Type: application/json');
    print json_encode(['success' => ($res >= 0), 'field' => $field, 'value' => $value]);
    exit;
}

// AJAX: inline (on-the-fly) assignee save — returns getNomUrl so the display stays a proper user link
if ($action === 'setassignee_ajax' && $permissionToWrite) {
    $newUserId = GETPOSTINT('user_id');
    $object->fetch($id);
    $object->fk_user_assign = $newUserId;
    $object->update($user);
    if ($newUserId > 0) {
        $assignee = new User($db);
        $assignee->fetch($newUserId);
        $nomUrl = $assignee->getNomUrl(-1);
    } else {
        $nomUrl = '<span class="opacitymedium">' . $langs->trans('NotAssigned') . '</span>';
    }
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'nomurl' => $nomUrl]);
    exit;
}

// AJAX: inline (on-the-fly) thirdparty save — returns structured data to rebuild the badge
if ($action === 'setthirdparty_ajax' && $permissionToWrite) {
    $newSocId = GETPOSTINT('socid');
    $object->fetch($id);
    $object->setCustomer($newSocId);
    $socName    = '';
    $cardUrl    = '';
    $historyUrl = '';
    if ($newSocId > 0) {
        $soc = new Societe($db);
        $soc->fetch($newSocId);
        $socName    = trim($soc->name);
        $cardUrl    = DOL_URL_ROOT . '/societe/card.php?socid=' . $newSocId;
        $historyUrl = DOL_URL_ROOT . '/ticket/list.php?socid=' . $newSocId . '&sortfield=t.datec&sortorder=desc';
    }
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'id' => $newSocId, 'name' => $socName, 'cardurl' => $cardUrl, 'historyurl' => $historyUrl]);
    exit;
}

// AJAX: inline (on-the-fly) project save — returns structured data to rebuild the badge
if ($action === 'setproject_ajax' && $permissionToWrite) {
    $newProjectId = GETPOSTINT('projectid');
    $object->fetch($id);
    $object->setProject($newProjectId);
    $projRef = '';
    $cardUrl = '';
    if ($newProjectId > 0) {
        $proj = new Project($db);
        $proj->fetch($newProjectId);
        $projRef = trim($proj->ref);
        $cardUrl = DOL_URL_ROOT . '/projet/card.php?id=' . $newProjectId;
    }
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'id' => $newProjectId, 'name' => $projRef, 'cardurl' => $cardUrl]);
    exit;
}

// AJAX: inline (on-the-fly) progress save
if ($action === 'setprogress_ajax' && $permissionToWrite) {
    $newProgress = GETPOSTINT('progress');
    if ($newProgress < 0) {
        $newProgress = 0;
    }
    if ($newProgress > 100) {
        $newProgress = 100;
    }
    $object->fetch($id);
    $object->progress = $newProgress;
    $object->update($user);
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'progress' => (int) $object->progress]);
    exit;
}

// AJAX: inline (on-the-fly) progress save for tasks
if ($action === 'set_task_progress_ajax' && $permissionToWrite) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
    $taskObj = new Task($db);
    $taskObj->fetch(GETPOSTINT('id'));
    $newProgress = GETPOSTINT('progress');
    if ($newProgress < 0) {
        $newProgress = 0;
    }
    if ($newProgress > 100) {
        $newProgress = 100;
    }
    $taskObj->progress = $newProgress;
    $taskObj->update($user);
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'progress' => (int) $taskObj->progress]);
    exit;
}

// Action: create parent task manually (#4881)
if ($action === 'create_parent_task' && $permissionToWrite && !empty($object->fk_project)) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
    $t = new Task($db);
    $t->fk_project = $object->fk_project;
    $t->ref = 'TKP-' . $object->ref;
    $t->label = $langs->trans('Ticket') . ' ' . $object->ref;
    $t->progress = 0;
    $t->create($user);
    $db->query("UPDATE " . MAIN_DB_PREFIX . "projet_task SET ref = '" . $db->escape($t->ref) . "' WHERE rowid = " . (int) $t->id);
    header('Location: ' . $url_page_current . '?id=' . $object->id);
    exit;
}

// Action: add child task from popup (#4881)
if ($action === 'add_child_task_modal' && $permissionToWrite && !empty($object->fk_project)) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
    $t = new Task($db);
    $t->fk_project = $object->fk_project;
    $t->fk_task_parent = GETPOST('task_parent', 'int');
    $t->label = GETPOST('label', 'alphanohtml');
    $t->date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
    $t->date_end = dol_mktime(0, 0, 0, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));
    
    // Parse datetime-local from standard input if used
    $date_start_local = GETPOST('date_start_local', 'alpha');
    if (!empty($date_start_local)) $t->date_start = strtotime($date_start_local);
    $date_end_local = GETPOST('date_end_local', 'alpha');
    if (!empty($date_end_local)) $t->date_end = strtotime($date_end_local);

    $t->planned_workload = 0;
    $t->progress = 0;
    $t->budget_amount = GETPOST('budget', 'int');
    
    // Auto-generate ref using task numbering module
    $classnamemodtask = getDolGlobalString('PROJECT_TASK_ADDON', 'mod_task_simple');
    if (getDolGlobalString('PROJECT_TASK_ADDON') && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON').".php")) {
        require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON').'.php';
        $modTask = new $classnamemodtask();
        $t->ref = $modTask->getNextValue($object->thirdparty, $object);
    }
    
    $t->create($user);
    
    $executive_id = GETPOST('user_id', 'int');
    if ($executive_id > 0) {
        $t->add_contact($executive_id, 'TASKEXECUTIVE', 'internal');
    }
    header('Location: ' . $url_page_current . '?id=' . $object->id);
    exit;
}

// Action: set initial message
if ($action === 'setmessage' && $permissionToWrite) {
    if (GETPOST('cancel', 'alpha')) {
        header('Location: ' . $url_page_current . '?id=' . $id);
        exit;
    } else {
        $object->fetch($id);
        $object->message = GETPOST('message', 'restricthtml');
        $object->update($user);
        header('Location: ' . $url_page_current . '?id=' . $object->id);
        exit;
    }
}

// Action: mark ticket as read
if ($action === 'set_read' && $permissionToWrite) {
    $object->fetch(0, '', GETPOST('track_id', 'alpha'));
    if ($object->markAsRead($user) > 0) {
        setEventMessages($langs->trans('TicketMarkedAsRead'), null, 'mesgs');
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }
    header('Location: ' . $url_page_current . '?track_id=' . urlencode($object->track_id));
    exit;
}

// Action: change ticket status (native status navbar buttons)
if ($action === 'confirm_set_status' && $permissionToWrite && !GETPOST('cancel')) {
    $object->fetch(GETPOSTINT('id'), GETPOST('track_id', 'alpha'));
    $newStatus = GETPOSTINT('new_status');
    if ($object->setStatut($newStatus, null, '', 'TICKET_MODIFY')) {
        header('Location: ' . $url_page_current . '?track_id=' . urlencode($object->track_id));
        exit;
    }
    setEventMessages($object->error, $object->errors, 'errors');
}

/*
 * View
 */

if ($object->id <= 0) {
    saturne_header(0, '', $langs->trans('Ticket'), '');
    print '<div class="error">' . $langs->trans('ErrorRecordNotFound') . '</div>';
    llxFooter();
    $db->close();
    exit;
}

// Load thirdparty if linked
if ($object->socid > 0) {
    $object->fetch_thirdparty();
}

$form     = new Form($db);
$userstat = new User($db);

$title   = $langs->trans('Ticket') . ' ' . $object->ref;
$helpUrl = 'FR:Module_Digirisk';
saturne_header(0, '', $title, $helpUrl);

print '<div class="digirisk-ticket-card">';

// Ticket tabs — same as native (incl. Digirisk hook tabs) but the "Ticket" tab returns
// to this custom card instead of the native /ticket/card.php.
$head = digiriskdolibarr_ticket_prepare_head($object);
print dol_get_fiche_head($head, 'tabTicket', $langs->trans('Ticket'), -1, 'ticket');

// ---- Build morehtmlref (native Dolibarr banner style) ----
$morehtmlref = '<div class="refidno">';

// Tracking id
$morehtmlref .= '<span class="opacitymedium">' . $langs->trans('TicketTrackId') . ' : </span>' . dolPrintLabel($object->track_id);

// Author
if ($object->fk_user_create > 0) {
    $morehtmlref .= '<br>';
    $fuser = new User($db);
    $fuser->fetch($object->fk_user_create);
    $morehtmlref .= $fuser->getNomUrl(-1);
}

// Thirdparty (reedcrm-style inline badge: logo + nav icon + editable name + hidden select2)
if (isModEnabled('societe')) {
    $morehtmlref .= '<br>';
    $hasSoc    = ($object->socid > 0 && is_object($object->thirdparty));
    $socUrl    = $hasSoc ? DOL_URL_ROOT . '/societe/card.php?socid=' . (int) $object->socid : '#';
    $histUrl   = $hasSoc ? DOL_URL_ROOT . '/ticket/list.php?socid=' . (int) $object->socid . '&sortfield=t.datec&sortorder=desc' : '';
    $emptyLabel = $langs->trans('SetThirdParty');
    $morehtmlref .= '<span class="dtc-inline-badge dtc-thirdparty" data-ticket-id="' . (int) $object->id . '" data-url="' . dol_escape_htmltag($url_page_current) . '" data-empty-label="' . dol_escape_htmltag($emptyLabel) . '">';
    $morehtmlref .= img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="dtc-badge-logo"');
    $morehtmlref .= '<a class="dtc-badge-nav" href="' . dol_escape_htmltag($socUrl) . '" title="' . dol_escape_htmltag($langs->trans('ThirdParty')) . '"' . ($hasSoc ? '' : ' style="display:none;"') . '><i class="fas fa-building"></i></a>';
    $nameClass = 'dtc-thirdparty-name dtc-badge-name' . ($permissionToWrite ? ' dtc-inline-edit' : '') . ($hasSoc ? '' : ' is-empty');
    $nameText  = $hasSoc ? dol_escape_htmltag($object->thirdparty->name) : dol_escape_htmltag($emptyLabel);
    $nameTitle = $permissionToWrite ? $langs->trans('SetThirdParty') : $langs->trans('ThirdParty');
    $morehtmlref .= '<a class="' . $nameClass . '" href="' . dol_escape_htmltag($socUrl) . '" title="' . dol_escape_htmltag($nameTitle) . '">' . $nameText . '</a>';
    $morehtmlref .= '<a class="dtc-badge-history" href="' . dol_escape_htmltag($histUrl) . '" title="' . dol_escape_htmltag($langs->trans('TicketHistory')) . '"' . ($hasSoc ? '' : ' style="display:none;"') . '><i class="fas fa-ticket-alt"></i></a>';
    if ($permissionToWrite) {
        $morehtmlref .= '<span class="dtc-thirdparty-selector dtc-inline-selector" style="display:none;">';
        $morehtmlref .= $form->select_company((int) $object->socid, 'dtc_thirdparty_select', '', 1, 0, 1, [], 0, 'dtc-inline-select minwidth200');
        $morehtmlref .= '</span>';
    }
    $morehtmlref .= '</span>';
}

// Project (reedcrm-style inline badge: logo + nav icon + editable name + hidden select2)
if (isModEnabled('project')) {
    $morehtmlref .= '<br>';
    $object->fetchProject();
    $hasProject      = (!empty($object->fk_project) && is_object($object->project));
    $projUrl         = $hasProject ? DOL_URL_ROOT . '/projet/card.php?id=' . (int) $object->fk_project : '#';
    $emptyProjLabel  = $langs->trans('SetProject');
    $morehtmlref .= '<span class="dtc-inline-badge dtc-project" data-ticket-id="' . (int) $object->id . '" data-url="' . dol_escape_htmltag($url_page_current) . '" data-empty-label="' . dol_escape_htmltag($emptyProjLabel) . '">';
    $morehtmlref .= img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="dtc-badge-logo"');
    $morehtmlref .= '<a class="dtc-badge-nav" href="' . dol_escape_htmltag($projUrl) . '" title="' . dol_escape_htmltag($langs->trans('Project')) . '"' . ($hasProject ? '' : ' style="display:none;"') . '><i class="fas fa-project-diagram"></i></a>';
    $projNameClass = 'dtc-project-name dtc-badge-name' . ($permissionToWrite ? ' dtc-inline-edit' : '') . ($hasProject ? '' : ' is-empty');
    $projNameText  = $hasProject ? dol_escape_htmltag($object->project->ref) : dol_escape_htmltag($emptyProjLabel);
    $projNameTitle = $permissionToWrite ? $langs->trans('SetProject') : $langs->trans('Project');
    $morehtmlref .= '<a class="' . $projNameClass . '" href="' . dol_escape_htmltag($projUrl) . '" title="' . dol_escape_htmltag($projNameTitle) . '">' . $projNameText . '</a>';
    if ($permissionToWrite) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
        $formproject = new FormProjets($db);
        // mode=1 → array; we build a plain <select> (avoids the search-to-select autocompleter), select2-ified by JS on reveal.
        $projectArray = $formproject->select_projects_list((int) $object->socid, (string) $object->fk_project, 'dtc_project_select', 24, 0, 0, 0, 0, 0, 1, '', 1, 0, '', '', '');
        $morehtmlref .= '<span class="dtc-project-selector dtc-inline-selector" style="display:none;">';
        $morehtmlref .= '<select class="dtc-inline-select minwidth200" id="dtc_project_select" name="dtc_project_select">';
        $morehtmlref .= '<option value="0"' . (empty($object->fk_project) ? ' selected' : '') . '></option>';
        if (is_array($projectArray)) {
            foreach ($projectArray as $projLine) {
                $projId = (int) $projLine['key'];
                if ($projId <= 0) {
                    continue;
                }
                $selectedAttr = ($projId === (int) $object->fk_project) ? ' selected' : '';
                $morehtmlref .= '<option value="' . $projId . '"' . $selectedAttr . '>' . dol_escape_htmltag($projLine['value']) . '</option>';
            }
        }
        $morehtmlref .= '</select>';
        $morehtmlref .= '</span>';
    }
    $morehtmlref .= '</span>';
}

$morehtmlref .= '</div>';

$linkback = '<a href="' . dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1) . '"><strong>' . $langs->trans('BackToList') . '</strong></a>';

dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';

/*
 * Left column
 */
print '<div class="fichehalfleft" style="overflow: hidden; min-width: 0;">';
print '<div class="underbanner clearboth"></div>';

// ---- Titre du message (label + subject on the same line, inline on-the-fly edit) ----
$subjectRaw = (string) ($object->subject ?? '');
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">';
print '<span class="dtc-head-label">' . $langs->trans('Subject') . '</span>';
print '<span class="dtc-subject" data-value="' . dol_escape_htmltag($subjectRaw) . '">';
if ($subjectRaw !== '') {
    print '<span class="dtc-subject-value">' . dolPrintLabel($subjectRaw) . '</span>';
} else {
    print '<span class="dtc-subject-value opacitymedium">' . $langs->trans('NoSubject') . '</span>';
}
print '</span>';
print '</div></td></tr>';
print '</tbody></table>';

// ---- Message initial (editable rich text) ----
print '<form method="POST" action="' . dol_escape_htmltag($url_page_current . '?id=' . $object->id) . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setmessage">';
print '<input type="hidden" name="id" value="' . (int) $object->id . '">';
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">';
print $langs->trans('TicketInitialMessage');
print '</div></td></tr>';
print '<tr><td colspan="2">';
if ($action === 'editmessage' && $permissionToWrite) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
    $doleditor = new DolEditor('message', $object->message, '', 120, 'dolibarr_details', 'In', false, true, getDolGlobalString('FCKEDITOR_ENABLE_TICKET'), ROWS_9, '95%');
    $doleditor->Create();
    print '<br>';
    print '<input type="submit" class="button button-edit smallpaddingimp" value="' . $langs->trans('Modify') . '"> ';
    print '<input type="submit" class="button button-cancel smallpaddingimp" name="cancel" value="' . $langs->trans('Cancel') . '">';
} else {
    $msgClass = $permissionToWrite ? ' class="dtc-message-value wpeo-tooltip-event edit-message-on-click" title="' . dol_escape_htmltag($langs->trans('Modify')) . '" style="cursor:pointer;" data-edit-url="' . dol_escape_htmltag($url_page_current . '?id=' . $object->id . '&action=editmessage&token=' . newToken()) . '"' : '';
    print '<div' . $msgClass . '>';
    print !empty($object->message) ? dol_htmlentitiesbr($object->message) : '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
    print '</div>';
}
print '</td></tr>';
print '</tbody></table>';
print '</form>';

// ---- Informations registres (Digirisk ticket extrafields) ----
$extra = [];
foreach (($object->array_options ?? []) as $rawKey => $val) {
    $cleanKey = (strpos($rawKey, 'options_') === 0) ? substr($rawKey, strlen('options_')) : $rawKey;
    $extra[$cleanKey] = $val;
}

$regLastname  = (string) ($extra['digiriskdolibarr_ticket_lastname'] ?? '');
$regFirstname = (string) ($extra['digiriskdolibarr_ticket_firstname'] ?? '');
$regPhone     = (string) ($extra['digiriskdolibarr_ticket_phone'] ?? '');
$regLocation  = (string) ($extra['digiriskdolibarr_ticket_location'] ?? '');
$regDateRaw   = $extra['digiriskdolibarr_ticket_date'] ?? null;
$regDate      = !empty($regDateRaw) ? dol_print_date((int) $regDateRaw, 'day', 'tzuser') : '';
$regCondition = (string) ($extra['digiriskdolibarr_condition_message'] ?? '');

// GP/UT — linked DigiriskElement (chkbxlst stores comma-separated ids; keep the first).
$regServiceRaw  = $extra['digiriskdolibarr_ticket_service'] ?? '';
$firstServiceId = (int) (is_string($regServiceRaw) ? strtok($regServiceRaw, ',') : $regServiceRaw);
$regService     = '';
if (file_exists(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php')) {
    require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php';
    if ($firstServiceId > 0) {
        $digiriskElement = new DigiriskElement($db);
        if ($digiriskElement->fetch($firstServiceId) > 0) {
            $regService = $digiriskElement->getNomUrl(1, '', 0, '', -1, 1);
        }
    }
}

// Helper to render an inline editable field
$renderInlineEditable = static function (string $field, string $type, string $val, string $placeholder, string $rawVal = '', array $options = []) use ($langs, $permissionToWrite, $url_page_current, $object): string {
    if (!$permissionToWrite) {
        return $val === '' ? '<span class="opacitymedium">' . dol_escape_htmltag($placeholder) . '</span>' : $val;
    }
    $isEmpty = ($val === '');
    $displayVal = $isEmpty ? dol_escape_htmltag($placeholder) : $val;
    $rawValAttr = ' data-raw="' . dol_escape_htmltag($rawVal === '' && $type !== 'select' ? $val : $rawVal) . '"';
    $tabAttr = '';
    if (isset($options['tabindex'])) {
        $tabAttr = ' tabindex="' . (int)$options['tabindex'] . '"';
        unset($options['tabindex']);
    }
    $optionsAttr = !empty($options) ? ' data-options="' . dol_escape_htmltag(json_encode($options)) . '"' : '';
    $classes = 'dtc-extrafield-value dtc-inline-editable' . ($isEmpty ? ' is-empty' : '');
    
    return '<span class="' . $classes . '"' . $tabAttr . ' title="' . dol_escape_htmltag($langs->trans('Modify')) . '" data-placeholder="' . dol_escape_htmltag($placeholder) . '" data-field="' . dol_escape_htmltag($field) . '" data-type="' . dol_escape_htmltag($type) . '" data-ticket-id="' . (int) $object->id . '" data-url="' . dol_escape_htmltag($url_page_current) . '"' . $rawValAttr . $optionsAttr . '>' . $displayVal . '</span>';
};

$drPicto = img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule" style="margin-right: 5px;"');

// Fetch all GP/UT options for select
$serviceOptions = ['' => '']; // Empty option
if (file_exists(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php')) {
    require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php';
    $digiriskElementTmp = new DigiriskElement($db);
    $elementList = $digiriskElementTmp->fetchDigiriskElementFlat(0);
    if (is_array($elementList)) {
        foreach ($elementList as $el) {
            $obj = $el['object'] ?? null;
            if ($obj && isset($obj->id)) {
                $depth = (int)($el['depth'] ?? 0);
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                $serviceOptions[$obj->id] = $indent . dol_escape_htmltag($obj->ref . ' - ' . $obj->label);
            }
        }
    }
}

// Build the direct select HTML for GP/UT
$serviceSelectHtml = '';
if (!$permissionToWrite) {
    $serviceSelectHtml = $regService !== '' ? $regService : '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
} else {
    $serviceSelectHtml = '<select class="dtc-direct-select flat" data-field="digiriskdolibarr_ticket_service" tabindex="5" style="width: 100%; max-width: 200px;">';
    foreach ($serviceOptions as $optVal => $optText) {
        $selected = ((string)$optVal === (string)$firstServiceId) ? ' selected="selected"' : '';
        // optText is already escaped and contains HTML (&nbsp;), so we don't escape it here
        $serviceSelectHtml .= '<option value="' . dol_escape_htmltag((string)$optVal) . '"' . $selected . '>' . $optText . '</option>';
    }
    $serviceSelectHtml .= '</select>';
}

print '<table class="border centpercent tableforfield dtc-registres-table"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="4"><div class="dtc-head">' . img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"') . ' ' . $langs->trans('TicketActionCardRegistresSection') . '</div></td></tr>';

print '<tr>';
print '<td class="titlefieldmiddle">' . $drPicto . $langs->trans('LastName') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_lastname', 'text', dol_escape_htmltag($regLastname), $langs->trans('LastName'), '', ['tabindex' => 3]) . '</td>';
print '<td class="titlefieldmiddle">' . $drPicto . $langs->trans('FirstName') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_firstname', 'text', dol_escape_htmltag($regFirstname), $langs->trans('FirstName'), '', ['tabindex' => 7]) . '</td>';
print '</tr>';

print '<tr>';
print '<td class="titlefieldmiddle">' . $drPicto . $langs->trans('Phone') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_phone', 'text', dol_print_phone($regPhone), $langs->trans('Phone'), $regPhone, ['tabindex' => 4]) . '</td>';
print '<td class="titlefieldmiddle">' . $drPicto . $langs->trans('DeclarationDate') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_date', 'date', $regDate, $langs->trans('DeclarationDate'), (string)$regDateRaw, ['tabindex' => 8]) . '</td>';
print '</tr>';

print '<tr>';
print '<td class="titlefieldmiddle">' . $drPicto . $langs->trans('GP/UT') . '</td><td>' . $serviceSelectHtml . '</td>';
print '<td class="titlefieldmiddle">' . $drPicto . $langs->trans('Location') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_location', 'text', dol_escape_htmltag($regLocation), $langs->trans('Location'), '', ['tabindex' => 9]) . '</td>';
print '</tr>';

print '<tr><td class="titlefieldmiddle">' . $drPicto . $langs->trans('Condition') . '</td><td colspan="3">' . $renderInlineEditable('digiriskdolibarr_condition_message', 'textarea', ($regCondition !== '' ? dolPrintHTML($regCondition) : ''), $langs->trans('ConditionMessage'), $regCondition, ['tabindex' => 6]) . '</td></tr>';

// "Registre signé": disabled indicator. TODO (#4443 step 2): reflect real SaturneSignature state
// and add the "Conditions à accepter pour la signature" (ValidateText) + category-scoped
// "Date de départ anticipé" rows (see actions_digiriskdolibarr.class.php printCommonFooter:324-337 / 291-310).
print '<tr><td class="titlefieldmiddle">' . $drPicto . $langs->trans('RegisterSigned') . '</td><td colspan="3"><input type="checkbox" disabled></td></tr>';

print '</tbody></table>';

// ---- Accidents linked to this ticket ----
$linkedAccidents = [];
if (file_exists(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/accident.class.php')) {
    require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/accident.class.php';
    $accident = new Accident($db);
    $fetched  = $accident->fetchAll('', '', 0, 0, ['customsql' => 't.fk_ticket = ' . (int) $object->id]);
    if (is_array($fetched)) {
        $linkedAccidents = $fetched;
    }
}
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td><div class="dtc-head">' . img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"') . ' ' . $langs->trans('Accidents') . '</div></td></tr>';
print '<tr><td>';
if (!empty($linkedAccidents)) {
    print '<ul class="nomargin">';
    foreach ($linkedAccidents as $acc) {
        print '<li>' . $acc->getNomUrl(1) . '</li>';
    }
    print '</ul>';
} else {
    print '<span class="opacitymedium">' . $langs->trans('AccidentsLinked') . ' : ' . $langs->trans('None') . '</span>';
}
print '</td></tr>';
print '</tbody></table>';

print '</div>'; // fichehalfleft

/*
 * Right column
 */
print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

// ---- Prepare parent task info ----
$parentRef = 'TKP-' . $object->ref;
$parentTaskId = 0;
$parentTask = null;
if (!empty($object->fk_project) && isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "projet_task WHERE ref = '" . $db->escape($parentRef) . "' AND fk_projet = " . (int) $object->fk_project;
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $objTask = $db->fetch_object($resql);
        $parentTaskId = $objTask->rowid;
        $parentTask = new Task($db);
        $parentTask->fetch($parentTaskId);
    }
}

// ---- Responsable et avancement ----
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . $langs->trans('TicketResponsibleAndProgress') . '</div></td></tr>';

// Assigned user (inline, on-the-fly editable)
print '<tr><td class="titlefieldmiddle">' . $langs->trans('AssignedTo') . '</td>';
print '<td class="dtc-assignee" data-ticket-id="' . (int) $object->id . '" data-assign-url="' . dol_escape_htmltag($url_page_current) . '">';
print '<span class="dtc-assignee-value' . ($permissionToWrite ? ' dtc-editable' : '') . '">';
if ($object->fk_user_assign > 0) {
    $userstat->fetch($object->fk_user_assign);
    print $userstat->getNomUrl(-1);
} else {
    print '<span class="opacitymedium">' . $langs->trans('NotAssigned') . '</span>';
}
print '</span>';
if ($permissionToWrite) {
    print '<span class="dtc-assignee-editor" style="display:none;">';
    // forcecombo = 1 → plain <select> (no select2) so the native "change" event fires reliably.
    print $form->select_dolusers($object->fk_user_assign, 'dtc_assignee_select', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'dtc-assignee-select maxwidth200', 0, 0, false, 1);
    print '</span>';
}
print '</td></tr>';

// Progression (inline editable — modifying PARENT TASK if it exists)
print '<tr><td class="titlefieldmiddle">' . $langs->trans('Progression') . '</td>';
if ($parentTask) {
    print '<td class="dtc-progress" data-ticket-id="' . (int) $parentTask->id . '" data-action="set_task_progress_ajax" data-progress-url="' . dol_escape_htmltag($url_page_current) . '" data-value="' . (int) $parentTask->progress . '">';
    print '<span class="dtc-progress-value' . ($permissionToWrite ? ' dtc-editable' : '') . '"' . ($permissionToWrite ? ' contenteditable="true"' : '') . '>' . ((int) $parentTask->progress) . '</span> %';
    print '</td></tr>';
} else {
    print '<td><span class="opacitymedium">0 %</span></td></tr>';
}
print '</tbody></table>';

// ---- Linked tasks table (data wired for #4881) ----
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
// Add actions to header
$tasksHeaderActions = '';
if (!empty($object->fk_project) && $permissionToWrite) {
    if ($parentTask) {
        $tasksHeaderActions .= '<span style="margin-left:5px;">' . $parentTask->getNomUrl(2) . '</span>';
        $tasksHeaderActions .= '<a style="margin-left:5px; cursor:pointer;" class="modal-open wpeo-tooltip-event" title="' . dol_escape_htmltag($langs->trans('NewTask')) . '"><input type="hidden" class="modal-options" data-modal-to-open="ticket_task_add_modal" />' . img_picto('', 'plus') . '</a>';
    } else {
        $tasksHeaderActions .= '<a style="margin-left:5px" href="' . dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_card.php', 1) . '?id=' . $object->id . '&action=create_parent_task" title="' . dol_escape_htmltag($langs->trans('Add')) . '">' . img_picto('', 'plus') . '</a>';
    }
}
print '<th>' . $langs->trans('RefTask') . $tasksHeaderActions . '</th>';
print '<th>' . $langs->trans('Label') . '</th>';
print '<th class="center">' . $langs->trans('DateStart') . '</th>';
print '<th class="center">' . $langs->trans('Deadline') . '</th>';
print '<th class="center">' . $langs->trans('Progress') . '</th>';
print '<th class="right">' . $langs->trans('Resp') . '</th>';
print '</tr>';

$tasksarray = [];
if ($parentTask) {
    $taskObj = new Task($db);
    $extrafieldsObj = new ExtraFields($db);
    $allTasks = $taskObj->getTasksArray(null, null, $object->fk_project, 0, 0, '', '-1', '', 0, 0, $extrafieldsObj);
    if (!empty($allTasks)) {
        foreach ($allTasks as $tsk) {
            if ($tsk->fk_task_parent == $parentTaskId) {
                $tasksarray[] = $tsk;
            }
        }
    }
}

if (!empty($tasksarray)) {
    foreach ($tasksarray as $t) {
        print '<tr class="oddeven">';
        print '<td>' . $t->getNomUrl(1, 'withproject') . '</td>';
        print '<td>' . dol_escape_htmltag($t->label) . '</td>';
        print '<td class="center">' . dol_print_date($t->date_start, 'day') . '</td>';
        print '<td class="center">' . dol_print_date($t->date_end, 'day') . '</td>';
        
        // Progress inline editable (using dynamic data-action added to JS)
        print '<td class="center dtc-progress" data-ticket-id="' . (int) $t->id . '" data-action="set_task_progress_ajax" data-progress-url="' . dol_escape_htmltag($url_page_current) . '" data-value="' . (int) $t->progress . '">';
        print '<span class="dtc-progress-value' . ($permissionToWrite ? ' dtc-editable' : '') . '"' . ($permissionToWrite ? ' contenteditable="true"' : '') . '>' . ((int) $t->progress) . '</span> %';
        print '</td>';
        
        // Responsable (display names/avatars)
        print '<td class="right">';
        $contacts = $t->getListContactId('internal');
        if (!empty($contacts)) {
            $userstat = new User($db);
            foreach ($contacts as $contactId) {
                if ($userstat->fetch($contactId) > 0) {
                    print $userstat->getNomUrl(-2, '', 0, 0, 24, 0, 'paddingright') . ' ';
                }
            }
        } else {
            print '<span class="opacitymedium">' . $langs->trans('NotAssigned') . '</span>';
        }
        print '</td>';
        print '</tr>';
    }
} else {
    print '<tr class="oddeven"><td colspan="6" class="opacitymedium center">' . $langs->trans('NoRecordFound') . '</td></tr>';
}

print '</table>';
print '</div>';

// ---- Classification (categories) + key dates ----
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . $langs->trans('TicketActionCardClassificationSection') . '</div></td></tr>';

if (isModEnabled('category')) {
    $catObj           = new Categorie($db);
    $ticketCategories = $catObj->containing($object->id, Categorie::TYPE_TICKET);
    if (!is_array($ticketCategories)) {
        $ticketCategories = [];
    }
    $allTicketCategories = $catObj->get_full_arbo(Categorie::TYPE_TICKET);
    if (!is_array($allTicketCategories)) {
        $allTicketCategories = [];
    }
    $assignedCategoryIds = array_map(static fn($c) => (int) $c->id, $ticketCategories);

    print '<tr><td class="titlefieldmiddle">' . $langs->trans('Categories') . '</td><td>';
    // Exact same markup as the ticket kanban (core/tpl/ticket/ticket_action_card_picker.tpl.php)
    // so it inherits the identical kanban tag styling (_page-actionplan.scss).
    print '<div class="kanban-card-tags" data-ticket-id="' . (int) $object->id . '" data-tag-url="' . dol_escape_htmltag($kanbanAjaxUrl) . '">';

    foreach ($ticketCategories as $cat) {
        $tagBg = !empty($cat->color) ? '#' . dol_escape_htmltag($cat->color) : '#8c8c8c';
        print '<span class="kanban-tag" data-cat-id="' . (int) $cat->id . '" style="background:' . $tagBg . '">';
        print dol_escape_htmltag($cat->label);
        if ($permissionToWrite) {
            print '<span class="kanban-tag-remove" title="' . dol_escape_htmltag($langs->trans('Remove')) . '">&times;</span>';
        }
        print '</span>';
    }

    if ($permissionToWrite) {
        print '<div class="kanban-tag-dropdown-wrapper">';
        print '<button class="kanban-add-tag-btn" title="' . dol_escape_htmltag($langs->trans('AddCategory')) . '"><i class="fas fa-tag"></i><i class="fas fa-plus" style="font-size:7px;margin-left:1px"></i></button>';
        print '<div class="kanban-tag-dropdown" data-ticket-id="' . (int) $object->id . '">';
        foreach ($allTicketCategories as $ac) {
            $acId = (int) ($ac['rowid'] ?? 0);
            if ($acId <= 0) {
                continue;
            }
            $isAssigned = in_array($acId, $assignedCategoryIds, true);
            $dotColor   = !empty($ac['color']) ? '#' . dol_escape_htmltag($ac['color']) : '#8c8c8c';
            print '<div class="kanban-tag-option' . ($isAssigned ? ' assigned' : '') . '" data-value="' . $acId . '" data-color="' . dol_escape_htmltag((string) ($ac['color'] ?? '')) . '">';
            print '<span class="kanban-tag-dot" style="background:' . $dotColor . '"></span>';
            print dol_escape_htmltag($ac['label']);
            if ($isAssigned) {
                print '<i class="fas fa-check" style="margin-left:auto;font-size:9px;color:#28a745"></i>';
            }
            print '</div>';
        }
        print '</div>';
        print '</div>';
    }

    print '</div>';
    print '</td></tr>';
}

// Creation date + elapsed time
print '<tr><td class="titlefieldmiddle">' . $langs->trans('DateCreation') . '</td><td>';
print dol_print_date($object->datec, 'dayhour', 'tzuser');
print '<span class="opacitymedium"><span class="small"> - ' . $langs->trans('TimeElapsedSince') . ': <b><i>' . convertSecondToTime(roundUpToNextMultiple($now - $object->datec, 60)) . '</i></b></span></span>';
print '</td></tr>';

// Read date
print '<tr><td class="titlefieldmiddle">' . $langs->trans('TicketReadOn') . '</td><td>';
print !empty($object->date_read) ? dol_print_date($object->date_read, 'dayhour', 'tzuser') : '';
print '</td></tr>';

// Close date
print '<tr><td class="titlefieldmiddle">' . $langs->trans('TicketCloseOn') . '</td><td>';
print !empty($object->date_close) ? dol_print_date($object->date_close, 'dayhour', 'tzuser') : '';
print '</td></tr>';

print '</tbody></table>';

// ---- Status change buttons (native ticket navbar) — above recent events ----
if ($permissionToWrite && isset($object->status) && $object->status < Ticket::STATUS_CLOSED) {
    $actionobject = new ActionsTicket($db);
    $actionobject->viewStatusActions($object);
}

// ---- Recent events (native Dolibarr widget) ----
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
$formactions = new FormActions($db);
$formactions->showactions($object, 'ticket', 0, 1, 'listactions', 5);

print '</div>'; // fichehalfright
print '</div>'; // fichecenter

print dol_get_fiche_end();

print '</div>'; // digirisk-ticket-card

// --- Modal Add Child Task (#4881) ---
if (!empty($object->fk_project) && $permissionToWrite && $parentTask) {
    print '<div class="wpeo-modal modal-task" id="ticket_task_add_modal">';
    print '<div class="modal-container wpeo-modal-event">';
    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add_child_task_modal">';
    print '<input type="hidden" name="task_parent" value="' . $parentTask->id . '">';
    print '<div class="modal-header">';
    print '<h2 class="modal-title">' . $langs->trans('NewTask') . '</h2>';
    print '<div class="modal-close"><i class="fas fa-times"></i></div>';
    print '</div>';
    print '<div class="modal-content">';
    print '<div class="riskassessment-task-container">';
    print '<div class="riskassessment-task">';
    print '<div class="wpeo-gridlayout flex flex-row items-center">';
    print '<i class="fas fa-paragraph" style="margin-right:1em;"></i>';
    print '<input type="text" class="riskassessment-task-label" name="label" required="required" placeholder="' . dol_escape_htmltag($langs->trans('Label')) . '">';
    print '</div>';
    print '<div class="riskassessment-task-date wpeo-gridlayout grid-3" style="margin-top: 1em; margin-bottom: 1em;">';
    print '<div class="flex flex-row items-center">';
    print '<i class="fas fa-calendar-day" style="margin-right: 1em;"></i>';
    print '<input type="datetime-local" name="date_start_local" required>';
    print '</div>';
    print '<div class="flex flex-row items-center">';
    print '<i class="fas fa-calendar-check" style="margin-right: 1em;"></i>';
    print '<input type="datetime-local" name="date_end_local">';
    print '</div>';
    print '<div class="flex flex-row items-center paddingright">';
    print '<i class="fas fa-euro-sign" style="margin-right: 1em;"></i>';
    print '<input type="number" step="0.01" class="riskassessment-task-budget" name="budget" placeholder="Budget">';
    print '</div>';
    print '</div>';
    print '<div>';
    print '<div class="flex flex-row items-center justify-center">';
    print '<i class="fas fa-user-tie" style="margin-right: 1em;"></i>';
    print $form->select_dolusers(0, 'user_id', 1, null, 0, '', 0, '', 0, 'minwidth200');
    print '</div>';
    print '</div>';
    print '</div>';
    print '</div>';
    print '</div>';
    print '<div class="modal-footer">';
    print '<button type="submit" class="wpeo-button button-blue" style="color: #fff"><i class="fas fa-plus"></i></button>';
    print '</div>';
    print '</form>';
    print '</div>';
    print '</div>';
}
// ------------------------------------

llxFooter();
$db->close();
