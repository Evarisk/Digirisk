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
// Traceability (#4885) — constant gating the agenda event written on every edit made from this card.
$ticketLogConst    = 'DIGIRISKDOLIBARR_TICKET_LOG_MODIFICATIONS';

/*
 * Actions
 */

// AJAX: inline (on-the-fly) subject save
if ($action === 'setsubject_ajax' && $permissionToWrite) {
    $newSubject      = GETPOST('subject', 'alphanohtml');
    $object->fetch($id);
    $oldSubject      = $object->subject;
    $object->subject = $newSubject;
    if ($object->update($user) > 0) {
        digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('Subject'), $oldSubject, $newSubject, $ticketLogConst);
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
    $object->fetch_optionals();
    $oldValue = $object->array_options['options_' . $field] ?? '';
    $object->array_options['options_' . $field] = $value;
    $res = $object->insertExtraFields();
    if ($res >= 0) {
        [$logLabel, $logOld] = digiriskdolibarr_ticket_extrafield_log_parts($field, $oldValue);
        [, $logNew]          = digiriskdolibarr_ticket_extrafield_log_parts($field, $value);
        digiriskdolibarr_ticket_log_field_change($object, $logLabel, $logOld, $logNew, $ticketLogConst);
    }
    header('Content-Type: application/json');
    print json_encode(['success' => ($res >= 0), 'field' => $field, 'value' => $value]);
    exit;
}

// AJAX: inline (on-the-fly) assignee save — returns getNomUrl so the display stays a proper user link
if ($action === 'setassignee_ajax' && $permissionToWrite) {
    $newUserId = GETPOSTINT('user_id');
    $object->fetch($id);
    $oldUserId = (int) $object->fk_user_assign;
    $object->fk_user_assign = $newUserId;
    $object->update($user);
    if ($newUserId > 0) {
        $assignee = new User($db);
        $assignee->fetch($newUserId);
        $nomUrl = $assignee->getNomUrl(-1);
    } else {
        $nomUrl = '<span class="opacitymedium">' . $langs->trans('NotAssigned') . '</span>';
    }
    $userLogName = static function (int $userId) use ($db, $langs): string {
        if ($userId <= 0) {
            return '';
        }
        $logUser = new User($db);

        return $logUser->fetch($userId) > 0 ? $logUser->getFullName($langs) : (string) $userId;
    };
    digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('AssignedTo'), $userLogName($oldUserId), $userLogName($newUserId), $ticketLogConst);
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'nomurl' => $nomUrl]);
    exit;
}

// AJAX: inline (on-the-fly) thirdparty save — returns structured data to rebuild the badge
if ($action === 'setthirdparty_ajax' && $permissionToWrite) {
    $newSocId = GETPOSTINT('socid');
    $object->fetch($id);
    $oldSocName = '';
    if ((int) $object->socid > 0) {
        $oldSoc = new Societe($db);
        if ($oldSoc->fetch((int) $object->socid) > 0) {
            $oldSocName = trim($oldSoc->name);
        }
    }
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
    digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('ThirdParty'), $oldSocName, $socName, $ticketLogConst);
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'id' => $newSocId, 'name' => $socName, 'cardurl' => $cardUrl, 'historyurl' => $historyUrl]);
    exit;
}

// AJAX: inline (on-the-fly) project save — returns structured data to rebuild the badge
if ($action === 'setproject_ajax' && $permissionToWrite) {
    $newProjectId = GETPOSTINT('projectid');
    $object->fetch($id);
    $oldProjRef = '';
    if ((int) $object->fk_project > 0) {
        $oldProj = new Project($db);
        if ($oldProj->fetch((int) $object->fk_project) > 0) {
            $oldProjRef = trim($oldProj->ref);
        }
    }
    $object->setProject($newProjectId);
    $projRef = '';
    $cardUrl = '';
    if ($newProjectId > 0) {
        $proj = new Project($db);
        $proj->fetch($newProjectId);
        $projRef = trim($proj->ref);
        $cardUrl = DOL_URL_ROOT . '/projet/card.php?id=' . $newProjectId;
    }
    digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('Project'), $oldProjRef, $projRef, $ticketLogConst);
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
    $oldProgress      = (int) $object->progress;
    $object->progress = $newProgress;
    $object->update($user);
    digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('Progression'), $oldProgress . ' %', $newProgress . ' %', $ticketLogConst);
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
    $oldProgress       = (int) $taskObj->progress;
    $taskObj->progress = $newProgress;
    $taskObj->update($user);
    // "id" carries the task id here, so the ticket to log against is passed separately.
    $logTicket = new Ticket($db);
    if ($logTicket->fetch(GETPOSTINT('ticket_id')) > 0) {
        digiriskdolibarr_ticket_log_field_change(
            $logTicket,
            $langs->transnoentities('Progression') . ' — ' . $taskObj->ref,
            $oldProgress . ' %',
            $newProgress . ' %',
            $ticketLogConst
        );
    }
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'progress' => (int) $taskObj->progress]);
    exit;
}

// AJAX: post a conversation message (note interne here; public recipients + email in later steps)
if ($action === 'post_message_ajax' && $permissionToWrite) {
    $body    = trim(GETPOST('body', 'restricthtml'));
    $subject = trim(GETPOST('subject', 'alphanohtml'));
    $private = GETPOSTINT('private');
    $toList  = GETPOST('to', 'array') ?: [];
    $ccList  = GETPOST('cc', 'array') ?: [];
    if ($body === '') {
        header('Content-Type: application/json');
        print json_encode(['success' => 0, 'message' => $langs->trans('ErrorBadParameters')]);
        exit;
    }
    $object->fetch($id);
    $object->subject = $subject ?: ($object->subject ?? '');
    $object->message = $body;
    $object->private = $private;
    $willMail = (!$private && (!empty($toList) || !empty($ccList)));
    $newMsgId = $object->createTicketMessage($user, 0, [], [], [], $willMail);
    if (!$newMsgId || $newMsgId <= 0) {
        header('Content-Type: application/json');
        print json_encode(['success' => 0, 'message' => $object->error ?: $langs->trans('Error')]);
        exit;
    }
    // Process uploaded attachments: move into the ticket dir + index in ecm_files (linked to the message).
    $fileNameList     = [];
    $mimeTypeList     = [];
    $mimeFileNameList = [];
    if (!empty($_FILES['files']) && !empty($_FILES['files']['name'])) {
        require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
        $ticketDir = $conf->ticket->dir_output . '/' . dol_sanitizeFileName($object->ref);
        dol_mkdir($ticketDir);
        $relDir    = 'ticket/' . dol_sanitizeFileName($object->ref);
        $fileNames = (array) $_FILES['files']['name'];
        $fileTmps  = (array) $_FILES['files']['tmp_name'];
        $nbFiles   = count($fileNames);
        for ($fi = 0; $fi < $nbFiles; $fi++) {
            if (empty($fileNames[$fi]) || empty($fileTmps[$fi])) {
                continue;
            }
            $safeName = dol_sanitizeFileName($fileNames[$fi]);
            $destFile = $ticketDir . '/' . $safeName;
            if (dol_move_uploaded_file($fileTmps[$fi], $destFile, 1) > 0) {
                $fileNameList[]     = $destFile;
                $mimeTypeList[]     = dol_mimetype($destFile);
                $mimeFileNameList[] = $safeName;
                $ecmFile = new EcmFiles($db);
                $ecmFile->filepath        = $relDir;
                $ecmFile->filename        = $safeName;
                $ecmFile->fullpath_orig   = $safeName;
                $ecmFile->gen_or_uploaded = 'uploaded';
                $ecmFile->entity          = $conf->entity;
                $ecmFile->src_object_type = 'ticket';
                $ecmFile->src_object_id   = (int) $object->id;
                $ecmFile->agenda_id       = (int) $newMsgId;
                if ($ecmFile->create($user) <= 0) {
                    dol_syslog('ticket conversation: ecm index failed for ' . $destFile, LOG_WARNING);
                }
            }
        }
    }
    $mailNotice = '';
    if ($willMail && !getDolGlobalString('TICKET_DISABLE_ALL_MAILS')) {
        $sendto   = [];
        $sendtocc = [];
        foreach ($toList as $recipient) {
            $recipient = trim((string) $recipient);
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $sendto[$recipient] = $recipient;
            }
        }
        foreach ($ccList as $recipient) {
            $recipient = trim((string) $recipient);
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $sendtocc[$recipient] = $recipient;
            }
        }
        if (!empty($sendto)) {
            global $mysoc;
            $modelId       = GETPOSTINT('model_id');
            $mailSubject   = $subject;
            $mailBodyInner = $body;
            if ($modelId > 0) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                $formmailTpl = new FormMail($db);
                $tpl = $formmailTpl->getEMailTemplate($db, 'ticket_send', $user, $langs, $modelId);
                if (is_object($tpl)) {
                    if ($mailSubject === '' && !empty($tpl->topic)) {
                        $mailSubject = $tpl->topic;
                    }
                    if (!empty($tpl->content)) {
                        $mailBodyInner = $tpl->content . '<br><br>' . $body;
                    }
                }
            }
            $appli = getDolGlobalString('MAIN_APPLICATION_TITLE', !empty($mysoc->name) ? $mysoc->name : 'Dolibarr');
            if ($mailSubject === '') {
                $mailSubject = '[' . $appli . ' - ' . $langs->transnoentities('Ticket') . ' #' . $object->track_id . '] ' . $langs->transnoentities('TicketNewMessage');
            }
            $intro     = getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO', $langs->transnoentities('TicketMessageMailIntroText'));
            $signature = getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE');
            $urlTicket = dol_buildpath('/ticket/card.php', 2) . '?track_id=' . $object->track_id;
            $mailBody  = ($intro !== '' ? $intro . '<br><br>' : '') . $mailBodyInner . '<br><br>'
                . $langs->transnoentities('TicketNotificationEmailBodyInfosTrackUrlinternal') . ' : <a href="' . $urlTicket . '">' . $object->track_id . '</a>'
                . (!empty($signature) ? '<br><br>' . $signature : '');
            $from    = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM');
            $replyto = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_REPLYTO');
            $sentOk  = $object->sendTicketMessageByEmail($mailSubject, $mailBody, 0, $sendto, $sendtocc, $fileNameList, $mimeTypeList, $mimeFileNameList, $from, $replyto);
            // Persist recipients on the actioncomm so past public messages show To/Cc chips.
            $db->query('UPDATE ' . MAIN_DB_PREFIX . "actioncomm SET email_to = '" . $db->escape(implode(',', array_keys($sendto))) . "', email_tocc = '" . $db->escape(implode(',', array_keys($sendtocc))) . "' WHERE id = " . (int) $newMsgId);
            $mailNotice = $sentOk
                ? ' — ' . $langs->transnoentities('MailSentToNRecipients', (string) count($sendto))
                : ' — ' . $langs->transnoentities('MailNotSent');
        } else {
            $mailNotice = ' — ' . $langs->trans('NoRecipientFound');
        }
    }
    // Notify mentioned agents (@mentions on internal notes) + record them on the message.
    $mentions     = GETPOST('mentions', 'array') ?: [];
    $mentionNames = [];
    if (!empty($mentions)) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        $ticketUrlAbs = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_card.php', 2) . '?id=' . (int) $object->id;
        $mentionFrom  = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM', getDolGlobalString('MAIN_MAIL_EMAIL_FROM'));
        foreach ($mentions as $mentionUid) {
            $mentionUid = (int) $mentionUid;
            if ($mentionUid <= 0) {
                continue;
            }
            $mentionUser = new User($db);
            if ($mentionUser->fetch($mentionUid) <= 0) {
                continue;
            }
            $mentionNames[] = $mentionUser->getFullName($langs) ?: $mentionUser->login;
            if ($mentionUid !== (int) $user->id && !empty($mentionUser->email) && !getDolGlobalString('TICKET_DISABLE_ALL_MAILS')) {
                $mentionSubject = $langs->trans('YouWereMentionedOnTicket', $object->ref);
                $mentionBody    = $langs->trans('YouWereMentionedOnTicketBody', $user->getFullName($langs), $object->ref) . '<br><br>' . $body . '<br><br><a href="' . $ticketUrlAbs . '">' . dol_escape_htmltag($object->ref) . '</a>';
                $mentionMail    = new CMailFile($mentionSubject, $mentionUser->email, $mentionFrom, $mentionBody, [], [], [], '', '', 0, 1);
                $mentionMail->sendfile();
            }
        }
        // Record the mentioned names on the private note (email_to is free for notes).
        if ($private && !empty($mentionNames)) {
            $db->query('UPDATE ' . MAIN_DB_PREFIX . "actioncomm SET email_to = '" . $db->escape(implode(', ', $mentionNames)) . "' WHERE id = " . (int) $newMsgId);
        }
    }
    // Build the V2 bubble via the shared lib helper so it matches the initial render.
    $bubbleItem            = new stdClass();
    $bubbleItem->id        = (int) $newMsgId;
    $bubbleItem->type      = $private ? 'internal' : 'public';
    $bubbleItem->mine      = true;
    $bubbleItem->author    = $user->getFullName($langs) ?: $user->login;
    $bubbleItem->av_uid    = $user->id;
    $bubbleItem->av_first  = $user->firstname;
    $bubbleItem->av_last   = $user->lastname;
    $bubbleItem->av_login  = $user->login;
    $bubbleItem->av_photo  = $user->photo;
    $bubbleItem->ts        = (int) dol_now();
    $bubbleItem->subject   = $subject;
    $bubbleItem->body_html = dolPrintHTML($body);
    $bubbleItem->sent_mail  = (bool) $willMail;
    $bubbleItem->to         = $private ? [] : $toList;
    $bubbleItem->cc         = $private ? [] : $ccList;
    $bubbleItem->mentions   = $private ? $mentionNames : [];
    $bubbleItem->file_count = count($fileNameList);
    $bubble = digiriskdolibarr_ticket_conversation_bubble($langs, $conf, $bubbleItem, (int) dol_now());
    header('Content-Type: application/json');
    print json_encode(['success' => 1, 'message' => $langs->trans('MessagePosted') . $mailNotice, 'message_id' => (int) $newMsgId, 'bubble' => $bubble]);
    exit;
}

// AJAX: edit own conversation message (author-only)
if ($action === 'edit_message_ajax' && $permissionToWrite) {
    $msgId   = GETPOSTINT('message_id');
    $newBody = trim(GETPOST('body', 'restricthtml'));
    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
    $ac = new ActionComm($db);
    $ac->fetch($msgId);
    header('Content-Type: application/json');
    if ($ac->id > 0 && (int) $ac->userownerid === (int) $user->id && strpos((string) $ac->code, 'TICKET_MSG') === 0 && $newBody !== '') {
        $ac->note_private = $newBody;
        $ac->note         = $newBody;
        if ($ac->update($user) > 0) {
            digiriskdolibarr_ticket_log_event($object, $langs->transnoentities('TicketMessageEdited'), dol_trunc(dol_string_nohtmltag($newBody), 500), $ticketLogConst);
            print json_encode(['success' => 1, 'body_html' => dolPrintHTML($newBody)]);
            exit;
        }
    }
    print json_encode(['success' => 0, 'message' => $langs->trans('NotAllowed')]);
    exit;
}

// AJAX: delete own conversation message (author-only)
if ($action === 'delete_message_ajax' && $permissionToWrite) {
    $msgId = GETPOSTINT('message_id');
    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
    $ac = new ActionComm($db);
    $ac->fetch($msgId);
    header('Content-Type: application/json');
    if ($ac->id > 0 && (int) $ac->userownerid === (int) $user->id && strpos((string) $ac->code, 'TICKET_MSG') === 0) {
        $deletedBody = dol_trunc(dol_string_nohtmltag((string) $ac->note_private), 500);
        if ($ac->delete($user) > 0) {
            digiriskdolibarr_ticket_log_event($object, $langs->transnoentities('TicketMessageDeleted'), $deletedBody, $ticketLogConst);
            print json_encode(['success' => 1]);
            exit;
        }
    }
    print json_encode(['success' => 0, 'message' => $langs->trans('NotAllowed')]);
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
    digiriskdolibarr_ticket_log_event($object, $langs->transnoentities('TicketTaskCreated', $t->ref), $t->label, $ticketLogConst);
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
    digiriskdolibarr_ticket_log_event($object, $langs->transnoentities('TicketTaskCreated', $t->ref), $t->label, $ticketLogConst);
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
        $oldMessage      = (string) $object->message;
        $object->message = GETPOST('message', 'restricthtml');
        $object->update($user);
        digiriskdolibarr_ticket_log_field_change(
            $object,
            $langs->transnoentities('TicketInitialMessage'),
            dol_trunc(dol_string_nohtmltag($oldMessage), 250),
            dol_trunc(dol_string_nohtmltag((string) $object->message), 250),
            $ticketLogConst
        );
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
    $oldStatus = $object->getLibStatut(1);
    if ($object->setStatut($newStatus, null, '', 'TICKET_MODIFY')) {
        $object->fetch($object->id);
        digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('Status'), $oldStatus, $object->getLibStatut(1), $ticketLogConst);
        header('Location: ' . $url_page_current . '?track_id=' . urlencode($object->track_id));
        exit;
    }
    setEventMessages($object->error, $object->errors, 'errors');
}

// Ticket attached-files box — native generate/delete handling for the showdocuments widget (same as the native ticket card).
$upload_dir         = $conf->ticket->dir_output;
$permissiontoadd    = $permissionToWrite ? 1 : 0;
$permissiontodelete = $permissionToWrite ? 1 : 0;
// Captured before the include: actions_builddoc.inc.php resets $action once it is done.
$fileActionBefore   = $action;
$fileNameBefore     = GETPOST('file', 'alpha') ?: GETPOST('urlfile', 'alpha');
include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

// Traceability (#4885) — the attached-files box goes through the native include, which emits no event.
if ($object->id > 0 && $permissionToWrite && in_array($fileActionBefore, ['builddoc', 'remove_file'], true)) {
    if ($fileActionBefore === 'builddoc') {
        digiriskdolibarr_ticket_log_event($object, $langs->transnoentities('TicketDocumentGenerated'), (string) $object->model_pdf, $ticketLogConst);
    } else {
        digiriskdolibarr_ticket_log_event($object, $langs->transnoentities('TicketFileDeleted'), basename((string) $fileNameBefore), $ticketLogConst);
    }
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
print '<span class="dtc-head-label"><i class="fas fa-heading dtc-field-picto"></i>' . $langs->trans('Subject') . '</span>';
print '<span class="dtc-subject" data-value="' . dol_escape_htmltag($subjectRaw) . '">';
if ($subjectRaw !== '') {
    print '<span class="dtc-subject-value dtc-tabfield" tabindex="0">' . dolPrintLabel($subjectRaw) . '</span>';
} else {
    print '<span class="dtc-subject-value dtc-tabfield opacitymedium" tabindex="0">' . $langs->trans('NoSubject') . '</span>';
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
print '<i class="fas fa-comment-alt dtc-field-picto"></i>' . $langs->trans('TicketInitialMessage');
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
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
if ($firstServiceId > 0) {
    $digiriskElement = new DigiriskElement($db);
    if ($digiriskElement->fetch($firstServiceId) > 0) {
        $regService = $digiriskElement->getNomUrl(1, '', 0, '', -1, 1);
    }
}

// Helper to render an inline editable field.
// tabindex="0" (never a positive value, which would hijack the tab order of the whole page):
// the fields are chained in DOM order by the "dtc-tabfield" class, see js/modules/ticket.js.
$renderInlineEditable = static function (string $field, string $type, string $val, string $placeholder, string $rawVal = '', array $options = []) use ($langs, $permissionToWrite, $url_page_current, $object): string {
    if (!$permissionToWrite) {
        return $val === '' ? '<span class="opacitymedium">' . dol_escape_htmltag($placeholder) . '</span>' : $val;
    }
    $isEmpty = ($val === '');
    $displayVal = $isEmpty ? dol_escape_htmltag($placeholder) : $val;
    $rawValAttr = ' data-raw="' . dol_escape_htmltag($rawVal === '' && $type !== 'select' ? $val : $rawVal) . '"';
    $optionsAttr = !empty($options) ? ' data-options="' . dol_escape_htmltag(json_encode($options)) . '"' : '';
    $classes = 'dtc-extrafield-value dtc-inline-editable dtc-tabfield' . ($isEmpty ? ' is-empty' : '');

    return '<span class="' . $classes . '" tabindex="0" title="' . dol_escape_htmltag($langs->trans('Modify')) . '" data-placeholder="' . dol_escape_htmltag($placeholder) . '" data-field="' . dol_escape_htmltag($field) . '" data-type="' . dol_escape_htmltag($type) . '" data-ticket-id="' . (int) $object->id . '" data-url="' . dol_escape_htmltag($url_page_current) . '"' . $rawValAttr . $optionsAttr . '>' . $displayVal . '</span>';
};

// Field pictos (#4885) — one meaningful icon per field instead of the module logo repeated on every row.
$fieldPicto = static function (string $icon): string {
    return '<i class="fas fa-' . $icon . ' dtc-field-picto"></i>';
};

// Fetch all GP/UT options for select
$serviceOptions = ['' => '']; // Empty option
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
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

// Build the direct select HTML for GP/UT
$serviceSelectHtml = '';
if (!$permissionToWrite) {
    $serviceSelectHtml = $regService !== '' ? $regService : '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
} else {
    $serviceSelectHtml = '<select class="dtc-direct-select dtc-tabfield flat" data-field="digiriskdolibarr_ticket_service" tabindex="0">';
    foreach ($serviceOptions as $optVal => $optText) {
        $selected = ((string)$optVal === (string)$firstServiceId) ? ' selected="selected"' : '';
        // optText is already escaped and contains HTML (&nbsp;), so we don't escape it here
        $serviceSelectHtml .= '<option value="' . dol_escape_htmltag((string)$optVal) . '"' . $selected . '>' . $optText . '</option>';
    }
    $serviceSelectHtml .= '</select>';
}

// A colgroup + table-layout:fixed (SCSS) keeps the two label/value column pairs aligned, whatever
// the content length and whatever the colspan used by the full-width rows below.
print '<table class="border centpercent tableforfield dtc-registres-table">';
print '<colgroup><col class="dtc-reg-col-label"><col class="dtc-reg-col-value"><col class="dtc-reg-col-label"><col class="dtc-reg-col-value"></colgroup>';
print '<tbody>';
print '<tr class="liste_titre trforfield"><td colspan="4"><div class="dtc-head">' . img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"') . ' ' . $langs->trans('TicketActionCardRegistresSection') . '</div></td></tr>';

print '<tr>';
print '<td class="dtc-reg-label">' . $fieldPicto('user') . $langs->trans('LastName') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_lastname', 'text', dol_escape_htmltag($regLastname), $langs->trans('LastName')) . '</td>';
print '<td class="dtc-reg-label">' . $fieldPicto('id-card') . $langs->trans('FirstName') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_firstname', 'text', dol_escape_htmltag($regFirstname), $langs->trans('FirstName')) . '</td>';
print '</tr>';

print '<tr>';
print '<td class="dtc-reg-label">' . $fieldPicto('phone') . $langs->trans('Phone') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_phone', 'text', dol_print_phone($regPhone), $langs->trans('Phone'), $regPhone) . '</td>';
print '<td class="dtc-reg-label">' . $fieldPicto('calendar-day') . $langs->trans('DeclarationDate') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_date', 'date', $regDate, $langs->trans('DeclarationDate'), (string) $regDateRaw) . '</td>';
print '</tr>';

print '<tr>';
print '<td class="dtc-reg-label">' . $fieldPicto('sitemap') . $langs->trans('GP/UT') . '</td><td>' . $serviceSelectHtml . '</td>';
print '<td class="dtc-reg-label">' . $fieldPicto('map-marker-alt') . $langs->trans('Location') . '</td><td>' . $renderInlineEditable('digiriskdolibarr_ticket_location', 'text', dol_escape_htmltag($regLocation), $langs->trans('Location')) . '</td>';
print '</tr>';

print '<tr><td class="dtc-reg-label">' . $fieldPicto('comment-dots') . $langs->trans('Condition') . '</td><td colspan="3">' . $renderInlineEditable('digiriskdolibarr_condition_message', 'textarea', ($regCondition !== '' ? dolPrintHTML($regCondition) : ''), $langs->trans('ConditionMessage'), $regCondition) . '</td></tr>';

// "Registre signé": disabled indicator. TODO (#4443 step 2): reflect real SaturneSignature state
// and add the "Conditions à accepter pour la signature" (ValidateText) + category-scoped
// "Date de départ anticipé" rows (see actions_digiriskdolibarr.class.php printCommonFooter:324-337 / 291-310).
print '<tr><td class="dtc-reg-label">' . $fieldPicto('file-signature') . $langs->trans('RegisterSigned') . '</td><td colspan="3"><input type="checkbox" disabled></td></tr>';

print '</tbody></table>';

// ---- Accidents linked to this ticket ----
$linkedAccidents = [];
dol_include_once('/digiriskdolibarr/class/accident.class.php');
$accident = new Accident($db);
$fetched  = $accident->fetchAll('', '', 0, 0, ['customsql' => 't.fk_ticket = ' . (int) $object->id]);
if (is_array($fetched)) {
    $linkedAccidents = $fetched;
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

// ---- Attached files box (native Dolibarr showdocuments widget: generate + list + preview + delete), same as the native ticket card ----
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
$formfileTicket  = new FormFile($db);
$ticketFilesName = dol_sanitizeFileName($object->ref);
$ticketFilesDir  = $conf->ticket->dir_output . '/' . $ticketFilesName;
$genallowed      = $permissionToWrite ? 1 : 0;
$delallowed      = $permissionToWrite ? 1 : 0;
print '<div class="dtc-files-box">';
print $formfileTicket->showdocuments('ticket', $ticketFilesName, $ticketFilesDir, $url_page_current . '?id=' . $object->id, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', '');
print '</div>';

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
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . $fieldPicto('user-clock') . $langs->trans('TicketResponsibleAndProgress') . '</div></td></tr>';

// Assigned user (inline, on-the-fly editable)
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('user-tie') . $langs->trans('AssignedTo') . '</td>';
print '<td class="dtc-assignee" data-ticket-id="' . (int) $object->id . '" data-assign-url="' . dol_escape_htmltag($url_page_current) . '">';
print '<span class="dtc-assignee-value' . ($permissionToWrite ? ' dtc-editable dtc-tabfield' : '') . '"' . ($permissionToWrite ? ' tabindex="0"' : '') . '>';
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
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('tasks') . $langs->trans('Progression') . '</td>';
if ($parentTask) {
    print '<td class="dtc-progress" data-ticket-id="' . (int) $parentTask->id . '" data-action="set_task_progress_ajax" data-log-ticket-id="' . (int) $object->id . '" data-progress-url="' . dol_escape_htmltag($url_page_current) . '" data-value="' . (int) $parentTask->progress . '">';
    print '<span class="dtc-progress-value' . ($permissionToWrite ? ' dtc-editable dtc-tabfield' : '') . '"' . ($permissionToWrite ? ' contenteditable="true" tabindex="0"' : '') . '>' . ((int) $parentTask->progress) . '</span> %';
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
        print '<td class="center dtc-progress" data-ticket-id="' . (int) $t->id . '" data-action="set_task_progress_ajax" data-log-ticket-id="' . (int) $object->id . '" data-progress-url="' . dol_escape_htmltag($url_page_current) . '" data-value="' . (int) $t->progress . '">';
        print '<span class="dtc-progress-value' . ($permissionToWrite ? ' dtc-editable dtc-tabfield' : '') . '"' . ($permissionToWrite ? ' contenteditable="true" tabindex="0"' : '') . '>' . ((int) $t->progress) . '</span> %';
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
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . $fieldPicto('tags') . $langs->trans('TicketActionCardClassificationSection') . '</div></td></tr>';

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

    print '<tr><td class="titlefieldmiddle">' . $fieldPicto('tag') . $langs->trans('Categories') . '</td><td>';
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
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('calendar-plus') . $langs->trans('DateCreation') . '</td><td>';
print dol_print_date($object->datec, 'dayhour', 'tzuser');
print '<span class="opacitymedium"><span class="small"> - ' . $langs->trans('TimeElapsedSince') . ': <b><i>' . convertSecondToTime(roundUpToNextMultiple($now - $object->datec, 60)) . '</i></b></span></span>';
print '</td></tr>';

// Read date
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('eye') . $langs->trans('TicketReadOn') . '</td><td>';
print !empty($object->date_read) ? dol_print_date($object->date_read, 'dayhour', 'tzuser') : '';
print '</td></tr>';

// Close date
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('calendar-check') . $langs->trans('TicketCloseOn') . '</td><td>';
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

/*
 * Conversation (full-width) — ticket conversation system.
 * Timeline = synthetic initial (from $object->message) + all ticket actioncomm rows
 * (TICKET_MSG* = message cards, others = discrete event lines), oldest first.
 */
$threadItems = [];
if (!empty($object->message)) {
    $init = new stdClass();
    $init->kind = 'message';
    $init->id   = 0;
    $init->type = 'initial';
    $init->mine = false;
    if ($object->fk_user_create > 0) {
        $creatorUser = new User($db);
        $creatorUser->fetch($object->fk_user_create);
        $init->author   = $creatorUser->getFullName($langs) ?: ($creatorUser->login ?: $langs->trans('Unknown'));
        $init->av_uid   = $creatorUser->id;
        $init->av_first = $creatorUser->firstname;
        $init->av_last  = $creatorUser->lastname;
        $init->av_login = $creatorUser->login;
        $init->av_photo = $creatorUser->photo;
    } else {
        $init->author = $langs->trans('Unknown');
        $init->av_uid = 0;
        $init->av_first = $init->av_last = $init->av_login = $init->av_photo = '';
    }
    $init->ts        = (int) $object->datec;
    $init->subject   = '';
    $init->body_html = dolPrintHTML((string) $object->message);
    $init->sent_mail = false;
    $init->to        = [];
    $init->cc        = [];
    $threadItems[]   = $init;
}
$sqlThread = 'SELECT a.id, a.code, a.label, a.note as note_private, a.datep,'
    . ' a.fk_user_author, a.email_to, a.email_tocc,'
    . ' u.firstname, u.lastname, u.login, u.photo'
    . ' FROM ' . MAIN_DB_PREFIX . 'actioncomm a'
    . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON u.rowid = a.fk_user_author'
    . ' WHERE a.fk_element = ' . (int) $object->id . " AND a.elementtype = 'ticket'"
    . ' ORDER BY a.datep ASC, a.id ASC';
$resThread = $db->query($sqlThread);
if ($resThread) {
    while ($rowThread = $db->fetch_object($resThread)) {
        if ($rowThread->code === 'AC_TICKET_CREATE') {
            // Redundant with the synthetic initial rendered above.
            continue;
        }
        $tsThread = (int) $db->jdate($rowThread->datep);
        if (strpos((string) $rowThread->code, 'TICKET_MSG') === 0) {
            $msgItem = new stdClass();
            $msgItem->kind     = 'message';
            $msgItem->id       = (int) $rowThread->id;
            $msgItem->type     = preg_match('/PRIVATE/', (string) $rowThread->code) ? 'internal' : 'public';
            $msgItem->mine     = ((int) $rowThread->fk_user_author === (int) $user->id);
            $msgItem->author   = trim(((string) $rowThread->firstname) . ' ' . ((string) $rowThread->lastname)) ?: ((string) $rowThread->login ?: $langs->trans('Unknown'));
            $msgItem->av_uid   = (int) $rowThread->fk_user_author;
            $msgItem->av_first = $rowThread->firstname;
            $msgItem->av_last  = $rowThread->lastname;
            $msgItem->av_login = $rowThread->login;
            $msgItem->av_photo = $rowThread->photo;
            $msgItem->ts       = $tsThread;
            $msgItem->subject  = (string) $rowThread->label;
            $msgItem->body_html = dolPrintHTML((string) $rowThread->note_private);
            $msgItem->sent_mail = (bool) preg_match('/SENTBYMAIL/', (string) $rowThread->code);
            $isPublicMsg        = ($msgItem->type === 'public');
            $msgItem->to        = ($isPublicMsg && !empty($rowThread->email_to)) ? explode(',', (string) $rowThread->email_to) : [];
            $msgItem->cc        = ($isPublicMsg && !empty($rowThread->email_tocc)) ? explode(',', (string) $rowThread->email_tocc) : [];
            $msgItem->mentions  = (!$isPublicMsg && !empty($rowThread->email_to)) ? explode(',', (string) $rowThread->email_to) : [];
            $threadItems[]      = $msgItem;
        } else {
            $evItem = new stdClass();
            $evItem->kind = 'event';
            $evItem->ts   = $tsThread;
            $evWho        = trim(((string) $rowThread->firstname) . ' ' . ((string) $rowThread->lastname)) ?: (string) $rowThread->login;
            $evItem->text = ((string) ($rowThread->label ?: $rowThread->code)) . ($evWho !== '' ? ' · ' . $evWho : '') . ' · ' . dol_print_date($tsThread, 'dayhour', 'tzuser');
            $threadItems[] = $evItem;
        }
    }
    $db->free($resThread);
}
$threadMsgCount = 0;
$threadMsgIds   = [];
foreach ($threadItems as $threadItem) {
    if ($threadItem->kind === 'message') {
        $threadMsgCount++;
        $threadItem->file_count = 0;
        if ((int) $threadItem->id > 0) {
            $threadMsgIds[] = (int) $threadItem->id;
        }
    }
}
if (!empty($threadMsgIds)) {
    $resFiles = $db->query('SELECT agenda_id, COUNT(*) as nb FROM ' . MAIN_DB_PREFIX . 'ecm_files WHERE agenda_id IN (' . implode(',', $threadMsgIds) . ') GROUP BY agenda_id');
    if ($resFiles) {
        $fileCounts = [];
        while ($rowFile = $db->fetch_object($resFiles)) {
            $fileCounts[(int) $rowFile->agenda_id] = (int) $rowFile->nb;
        }
        $db->free($resFiles);
        foreach ($threadItems as $threadItem) {
            if ($threadItem->kind === 'message' && isset($fileCounts[(int) $threadItem->id])) {
                $threadItem->file_count = $fileCounts[(int) $threadItem->id];
            }
        }
    }
}

$nowConv = dol_now();
print '<div class="dtc-conversation" data-ticket-id="' . (int) $object->id . '" data-url="' . dol_escape_htmltag($url_page_current) . '" data-lang-confirm-delete="' . dol_escape_htmltag($langs->transnoentities('ConfirmDeleteMessage')) . '">';
print '<div class="dtc-conversation__head"><i class="fas fa-comments"></i> ' . $langs->trans('Conversation') . ' <span class="opacitymedium">(' . (int) $threadMsgCount . ')</span></div>';
print '<ul class="dtc-thread">';
$lastDay = '';
foreach ($threadItems as $threadItem) {
    $dayLabel = dol_print_date((int) $threadItem->ts, 'day', 'tzuser');
    if ($dayLabel !== $lastDay) {
        print '<li class="dtc-thread__daysep"><span>' . dol_escape_htmltag($dayLabel) . '</span></li>';
        $lastDay = $dayLabel;
    }
    if ($threadItem->kind === 'event') {
        print '<li class="dtc-event">' . dol_escape_htmltag($threadItem->text) . '</li>';
    } else {
        print digiriskdolibarr_ticket_conversation_bubble($langs, $conf, $threadItem, (int) $nowConv);
    }
}
if ($threadMsgCount === 0) {
    print '<li class="dtc-thread__empty"><i class="fas fa-comments"></i><div>' . $langs->trans('NoMessageYet') . '</div><div class="opacitymedium">' . $langs->trans('BeFirstToReply') . '</div></li>';
}
print '</ul>';
// Composer — note interne (public recipients/attachments/mentions added in later tasks).
if ($permissionToWrite) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
    // Preloaded recipient suggestions (external ticket contacts + thirdparty email).
    $convSuggestions = [];
    $object->fetch_thirdparty();
    $convExtContacts = $object->liste_contact(-1, 'external');
    if (is_array($convExtContacts)) {
        foreach ($convExtContacts as $convContact) {
            $convEmail = (string) ($convContact['email'] ?? '');
            if ($convEmail !== '') {
                $convName = trim(((string) ($convContact['firstname'] ?? '')) . ' ' . ((string) ($convContact['lastname'] ?? ''))) ?: $convEmail;
                $convSuggestions[$convEmail] = $convName . ' <' . $convEmail . '>';
            }
        }
    }
    if (is_object($object->thirdparty) && !empty($object->thirdparty->email)) {
        $convSuggestions[$object->thirdparty->email] = $object->thirdparty->name . ' <' . $object->thirdparty->email . '>';
    }
    // Available ticket_send email templates.
    $convTemplates = [];
    $resConvTpl = $db->query("SELECT rowid, label FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE type_template = 'ticket_send' AND active = 1 AND entity IN (" . getEntity('c_email_templates') . ") ORDER BY label");
    if ($resConvTpl) {
        while ($objConvTpl = $db->fetch_object($resConvTpl)) {
            $convTemplates[(int) $objConvTpl->rowid] = $objConvTpl->label;
        }
        $db->free($resConvTpl);
    }
    // Internal agents for @mentions.
    $convAgents = [];
    $resConvAg = $db->query('SELECT rowid, firstname, lastname, login FROM ' . MAIN_DB_PREFIX . 'user WHERE statut = 1 AND entity IN (' . getEntity('user') . ') ORDER BY lastname, firstname');
    if ($resConvAg) {
        while ($objConvAg = $db->fetch_object($resConvAg)) {
            $convAgents[(int) $objConvAg->rowid] = trim(((string) $objConvAg->firstname) . ' ' . ((string) $objConvAg->lastname)) ?: (string) $objConvAg->login;
        }
        $db->free($resConvAg);
    }
    print '<form class="dtc-composer dtc-composer--internal" data-mode="internal" data-lang-savenote="' . dol_escape_htmltag($langs->transnoentities('SaveNote')) . '" data-lang-send="' . dol_escape_htmltag($langs->transnoentities('Send')) . '">';
    print '<div class="dtc-composer__switch">';
    print '<button type="button" class="dtc-composer__tab is-active" data-mode="internal"><i class="fas fa-lock"></i> ' . $langs->trans('PrivateMessage') . '</button>';
    print '<button type="button" class="dtc-composer__tab" data-mode="public"><i class="fas fa-share"></i> ' . $langs->trans('PublicMessage') . '</button>';
    print '</div>';
    print '<div class="dtc-composer__public" style="display:none;">';
    print '<datalist id="dtc_recipient_suggestions">';
    foreach ($convSuggestions as $convEmail => $convLabel) {
        print '<option value="' . dol_escape_htmltag($convEmail) . '">' . dol_escape_htmltag($convLabel) . '</option>';
    }
    print '</datalist>';
    print '<div class="dtc-recipients" data-target="to"><span class="dtc-recipients__label">' . $langs->trans('ConvTo') . '</span><span class="dtc-chips"></span><input type="text" class="dtc-chip-input" list="dtc_recipient_suggestions" placeholder="' . dol_escape_htmltag($langs->trans('AddRecipient')) . '"></div>';
    print '<div class="dtc-recipients" data-target="cc"><span class="dtc-recipients__label">' . $langs->trans('ConvCc') . '</span><span class="dtc-chips"></span><input type="text" class="dtc-chip-input" list="dtc_recipient_suggestions" placeholder="Cc"></div>';
    print '<input type="text" class="dtc-composer__subject" name="subject" placeholder="' . dol_escape_htmltag($langs->trans('Subject')) . '" value="' . dol_escape_htmltag('Re: ' . (string) $object->subject . ' — ' . (string) $object->ref) . '">';
    if (!empty($convTemplates)) {
        print '<select class="dtc-model-select" name="model_id"><option value="0">' . dol_escape_htmltag($langs->trans('EMailTemplates')) . '…</option>';
        foreach ($convTemplates as $convTplId => $convTplLabel) {
            print '<option value="' . (int) $convTplId . '">' . dol_escape_htmltag($convTplLabel) . '</option>';
        }
        print '</select>';
    }
    print '</div>';
    print '<div class="dtc-composer__mentions">';
    print '<span class="dtc-recipients__label" title="' . dol_escape_htmltag($langs->trans('MentionAgents')) . '"><i class="fas fa-at"></i></span>';
    print $form->multiselectarray('dtc_mentions', $convAgents, [], 0, 0, 'dtc-mention-select', 0, 0, '', '', $langs->trans('MentionAgents'));
    print '</div>';
    print '<div class="dtc-composer__editor">';
    $convEditor = new DolEditor('dtc_body', '', '', 140, 'dolibarr_notes', 'In', false, true, getDolGlobalString('FCKEDITOR_ENABLE_TICKET'), ROWS_4, '100%');
    $convEditor->Create();
    print '</div>';
    print '<div class="dtc-composer__attach">';
    print '<input type="file" class="dtc-file-input" multiple style="display:none;">';
    print '<button type="button" class="dtc-attach-btn"><i class="fas fa-paperclip"></i> ' . $langs->trans('AddFile') . '</button>';
    print '<span class="dtc-file-list"></span>';
    print '</div>';
    print '<div class="dtc-composer__foot">';
    print '<button type="button" class="dtc-composer__send" data-thread-send><i class="fas fa-paper-plane"></i> <span class="dtc-composer__send-label">' . $langs->trans('SaveNote') . '</span></button>';
    print '<span class="opacitymedium dtc-composer__hint"><kbd>Ctrl</kbd>+<kbd>Enter</kbd></span>';
    print '</div>';
    print '</form>';
}
print '</div>';

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
