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
 *  \file       core/ajax/ticket_action_card.php
 *  \brief      AJAX endpoint dispatched by the 1-click ticket action tile UI (issue #4443).
 *              All responses are JSON: {success: bool, message: string, ticket: {...}}.
 */

if ( ! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if ( ! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if ( ! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res = 0;
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

top_httphead('application/json');

/**
 * Send a JSON response and exit.
 */
function respond(bool $success, string $message, array $extra = []): void
{
    print json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

/**
 * Sanitize and render a thread message body, keeping <blockquote> intact
 * (dolPrintHTML's "common" whitelist drops it, breaking quote-replies).
 */
function renderThreadBody(string $raw): string
{
    $stringWithEntities = dol_htmlentitiesbr($raw);
    $clean              = dol_htmlwithnojs(dol_string_onlythesehtmltags($stringWithEntities, 1, 1, 1));
    return dol_escape_htmltag(
        $clean,
        1,
        1,
        'html,body,a,b,em,hr,i,u,ul,ol,li,br,div,img,font,p,span,strong,table,tr,td,th,tbody,h1,h2,h3,h4,h5,h6,header,footer,nav,section,menu,menuitem,blockquote,pre,code,kbd',
        0,
        1
    );
}

global $conf, $db, $langs, $user;

$langs->loadLangs(['digiriskdolibarr@digiriskdolibarr', 'ticket']);

// Inputs — 'aZ09' permits a-z, 0-9, _, -, . so update_field passes through fine.
$ticketId = GETPOSTINT('ticket_id');
$action   = GETPOST('action', 'aZ09');
$param    = GETPOST('param', 'alpha');

// Permission gate — all writes require ticket->write + digiriskdolibarr->lire.
if ( ! ($user->hasRight('ticket', 'write') && $user->hasRight('digiriskdolibarr', 'lire'))) {
    respond(false, $langs->transnoentities('NotEnoughPermissions'));
}

if ($ticketId <= 0 || empty($action)) {
    respond(false, $langs->transnoentities('ErrorBadParameters'));
}

$object = new Ticket($db);
if ($object->fetch($ticketId) <= 0 || $object->id <= 0) {
    respond(false, $langs->transnoentities('RecordNotFound'));
}

/**
 * Build the payload returned to the client so the UI can refresh status + assignee
 * without a full page reload.
 */
$buildPayload = static function (Ticket $tkt) use ($db, $langs): array {
    $assignedLabel = '';
    if ((int) $tkt->fk_user_assign > 0) {
        $assignedUser = new User($db);
        $assignedUser->fetch((int) $tkt->fk_user_assign);
        $assignedLabel = $assignedUser->getFullName($langs);
    }
    $severityKey = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string) ($tkt->severity_code ?? '')));
    if (!in_array($severityKey, ['low', 'normal', 'high', 'blocking'], true)) {
        $severityKey = 'default';
    }
    return [
        'ticket' => [
            'id'            => (int) $tkt->id,
            'status'        => (int) $tkt->fk_statut,
            'status_html'   => $tkt->getLibStatut(2),
            'assigned_id'   => (int) $tkt->fk_user_assign,
            'assigned_name' => $assignedLabel,
            'severity_key'  => $severityKey,
        ],
    ];
};

$res = 0;

switch ($action) {
    /*
     * search_options — server-side search for combos backed by large tables
     * (thirdparties, projects) that are too big to embed in the page. Returns
     * up to 50 matching {id, label} rows for the given source + term.
     */
    case 'search_options':
        $source = GETPOST('source', 'aZ09');
        $term   = trim((string) GETPOST('term', 'alphanohtml'));
        $like   = '%' . $db->escape($term) . '%';
        $results = [];
        if ($source === 'societe') {
            $sql = 'SELECT rowid, nom FROM ' . MAIN_DB_PREFIX . 'societe'
                . " WHERE status = 1 AND entity IN (" . getEntity('societe') . ')'
                . ($term !== '' ? " AND nom LIKE '" . $like . "'" : '')
                . ' ORDER BY nom LIMIT 50';
            $r = $db->query($sql);
            if ($r) {
                while ($row = $db->fetch_object($r)) {
                    $results[] = ['id' => (int) $row->rowid, 'label' => $row->nom];
                }
            }
        } elseif ($source === 'project') {
            $sql = 'SELECT rowid, ref, title FROM ' . MAIN_DB_PREFIX . 'projet'
                . " WHERE fk_statut > 0 AND entity IN (" . getEntity('project') . ')'
                . ($term !== '' ? " AND (ref LIKE '" . $like . "' OR title LIKE '" . $like . "')" : '')
                . ' ORDER BY ref LIMIT 50';
            $r = $db->query($sql);
            if ($r) {
                while ($row = $db->fetch_object($r)) {
                    $label = trim(((string) $row->ref !== '' ? $row->ref . ' — ' : '') . ((string) ($row->title ?? '')));
                    $results[] = ['id' => (int) $row->rowid, 'label' => ($label !== '' ? $label : '#' . (int) $row->rowid)];
                }
            }
        } else {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        // Always offer the "none" option at the top so users can clear the value.
        array_unshift($results, ['id' => 0, 'label' => '— ' . $langs->transnoentities('NoneSelected') . ' —']);
        respond(true, '', ['results' => $results]);

    case 'mark_read':
        $res = $object->markAsRead($user);
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->transnoentities('TicketActionMarkedRead'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->transnoentities('Error'));
        // no break — respond() exits.

    case 'set_progress':
        $res = $object->setStatut(Ticket::STATUS_IN_PROGRESS, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->transnoentities('TicketActionInProgressDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->transnoentities('Error'));

    case 'set_waiting':
        $res = $object->setStatut(Ticket::STATUS_WAITING, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->transnoentities('TicketActionWaitingDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->transnoentities('Error'));

    case 'set_closed':
        $res = $object->setStatut(Ticket::STATUS_CLOSED, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->transnoentities('TicketActionClosedDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->transnoentities('Error'));

    case 'set_cancelled':
        $res = $object->setStatut(Ticket::STATUS_CANCELED, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->transnoentities('TicketActionCancelledDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->transnoentities('Error'));

    case 'assign_self':
        $res = $object->assignUser($user, (int) $user->id, 1);
        if ($res > 0) {
            // Dolibarr core convention: switch unread tickets to STATUS_ASSIGNED on assignment.
            if ((int) $object->fk_statut === Ticket::STATUS_NOT_READ) {
                $object->setStatut(Ticket::STATUS_ASSIGNED, null, '', 'TICKET_MODIFY');
            }
            $object->fetch($ticketId);
            respond(true, $langs->transnoentities('TicketActionAssignedSelfDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->transnoentities('Error'));

    /*
     * post_message — append a new TICKET_MSG ActionComm to the ticket. Lightweight
     * wrapper over Ticket::createTicketMessage(). Returns the freshly-built bubble
     * HTML so the JS can append it to the thread without a reload.
     */
    case 'post_message':
        $subject = trim((string) GETPOST('subject', 'alphanohtml'));
        $body    = trim((string) GETPOST('body', 'restricthtml'));
        $private = (int) GETPOST('private', 'int');
        $byEmail = (int) GETPOST('by_email', 'int');
        if ($body === '') {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        // Hand to the existing Dolibarr method so triggers fire normally.
        $object->subject = $subject ?: ($object->subject ?? '');
        $object->message = $body;
        $object->private = $private;
        $newId = $object->createTicketMessage($user, 0, [], [], [], (bool) $byEmail);
        if (!$newId || $newId <= 0) {
            respond(false, $object->error ?: $langs->transnoentities('Error'));
        }
        // Actually fire the email if the user requested it. createTicketMessage with
        // $send_email=true only flags the actioncomm code as _SENTBYMAIL, it does
        // not send anything. Mirror what htdocs/ticket/card.php does on add_message.
        $mailNotice = '';
        if ($byEmail && !getDolGlobalString('TICKET_DISABLE_ALL_MAILS')) {
            global $mysoc;
            $object->fetch_thirdparty($object->fk_soc);

            // Build the recipient list: internal contacts always; external + thirdparty
            // email only when the message is not flagged private.
            $sendto       = [];
            $internalList = $object->getInfosTicketInternalContact(1);
            if (is_array($internalList)) {
                foreach ($internalList as $info) {
                    $email = (string) ($info['email'] ?? '');
                    if ($email !== '' && (int) ($info['id'] ?? 0) !== (int) $user->id) {
                        $sendto[$email] = trim(((string) ($info['firstname'] ?? '')) . ' ' . ((string) ($info['lastname'] ?? ''))) . ' <' . $email . '>';
                    }
                }
            }
            if (!$private) {
                $externalList = $object->getInfosTicketExternalContact(1);
                if (is_array($externalList)) {
                    foreach ($externalList as $info) {
                        $email = (string) ($info['email'] ?? '');
                        if ($email !== '' && !isset($sendto[$email])) {
                            $sendto[$email] = trim(((string) ($info['firstname'] ?? '')) . ' ' . ((string) ($info['lastname'] ?? ''))) . ' <' . $email . '>';
                        }
                    }
                }
                // Fallback: thirdparty's own email when no external contact is linked.
                if (empty($externalList) && !empty($object->thirdparty->email)) {
                    $em = $object->thirdparty->email;
                    if (!isset($sendto[$em])) {
                        $sendto[$em] = $object->thirdparty->name . ' <' . $em . '>';
                    }
                }
            }

            if (!empty($sendto)) {
                $appli       = getDolGlobalString('MAIN_APPLICATION_TITLE', !empty($mysoc->name) ? $mysoc->name : 'Dolibarr');
                $mailSubject = $subject !== '' ? $subject : '[' . $appli . ' - ' . $langs->transnoentities('Ticket') . ' #' . $object->track_id . '] ' . $langs->transnoentities('TicketNewMessage');
                $intro       = getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO', $langs->transnoentities('TicketMessageMailIntroText'));
                $signature   = getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE');
                $url         = dol_buildpath('/ticket/card.php', 2) . '?track_id=' . $object->track_id;
                $mailBody    = $intro . '<br><br>' . $body . '<br><br>'
                    . '==============================================<br>'
                    . $langs->transnoentities('TicketNotificationEmailBodyInfosTrackUrlinternal') . ' : <a href="' . $url . '">' . $object->track_id . '</a>';
                if (!empty($signature)) {
                    $mailBody .= '<br><br>' . $signature;
                }
                $from    = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM');
                $replyto = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_REPLYTO');
                $nRecipients = count($sendto);
                $ok          = $object->sendTicketMessageByEmail($mailSubject, $mailBody, 0, $sendto, [], [], [], [], $from, $replyto);
                // transnoentities() runs its own sprintf with empty defaults, which would
                // turn our %d into 0 if we wrapped it ourselves. Pass the count as $param1
                // so the internal sprintf substitutes it correctly.
                $mailNotice  = $ok
                    ? ' — ' . $langs->transnoentities('MailSentToNRecipients', (string) $nRecipients)
                    : ' — ' . $langs->transnoentities('MailNotSent');
            } else {
                $mailNotice = ' — ' . $langs->transnoentities('NoRecipientFound');
            }
        }
        // Build the bubble HTML — mirrors the TPL renderer (SMS layout, avatar, action buttons).
        $authorName = $user->getFullName($langs) ?: ($user->login ?: $langs->transnoentities('Unknown'));
        $initials   = strtoupper(substr((string) ($user->firstname ?: $user->lastname ?: $user->login ?: '?'), 0, 1));
        if (!empty($user->photo) && (int) $user->id > 0 && file_exists(DOL_DATA_ROOT . '/users/' . (int) $user->id . '/' . $user->photo)) {
            $thumbUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . (int) ($conf->entity ?? 1) . '&file=' . urlencode((int) $user->id . '/' . $user->photo) . '&cache=' . (int) $user->id;
            $avatar   = '<img class="tac-thread__avatar tac-thread__avatar--img" src="' . dol_escape_htmltag($thumbUrl) . '" alt="' . dol_escape_htmltag($initials) . '">';
        } else {
            $avatar   = '<div class="tac-thread__avatar">' . dol_escape_htmltag($initials) . '</div>';
        }
        $cls        = 'tac-thread__msg tac-thread__msg--mine' . ($private ? ' tac-thread__msg--private' : '');
        $authorUrl  = DOL_URL_ROOT . '/user/card.php?id=' . (int) $user->id;
        $tagPrivate = $private ? '<span class="tac-thread__tag tac-thread__tag--private"><i class="fas fa-lock"></i> ' . dol_escape_htmltag($langs->transnoentities('Private')) . '</span>' : '';
        $tagMail    = $byEmail ? '<span class="tac-thread__tag tac-thread__tag--mail"><i class="fas fa-envelope"></i> ' . dol_escape_htmltag($langs->transnoentities('SentByMail')) . '</span>' : '';
        $subjectHtml = $subject !== '' ? '<div class="tac-thread__subject">' . dol_escape_htmltag($subject) . '</div>' : '';
        $bodyLinkified = preg_replace_callback(
            '#(?<!href=["\'])(https?://[^\s<>"\']+)#i',
            static fn($m) => '<a href="' . dol_escape_htmltag($m[1]) . '" target="_blank" rel="noopener">' . dol_escape_htmltag($m[1]) . '</a>',
            renderThreadBody($body)
        );
        $bubble = '<li class="' . $cls . '" data-msg-id="' . (int) $newId . '" data-msg-mine="1">'
            . $avatar
            . '<div class="tac-thread__bubble">'
            . '<div class="tac-thread__meta">'
            . '<a class="tac-thread__author" href="' . dol_escape_htmltag($authorUrl) . '">' . dol_escape_htmltag($authorName) . '</a>'
            . '<span class="tac-thread__date">' . dol_escape_htmltag($langs->transnoentities('JustNow')) . '</span>'
            . $tagPrivate . $tagMail
            . '</div>'
            . $subjectHtml
            . '<div class="tac-thread__body" data-msg-body>' . $bodyLinkified . '</div>'
            . '<div class="tac-thread__actions">'
            . '<button type="button" class="tac-thread__action" data-msg-quote title="' . dol_escape_htmltag($langs->transnoentities('Quote')) . '"><i class="fas fa-quote-right"></i></button>'
            . '<button type="button" class="tac-thread__action" data-msg-edit title="' . dol_escape_htmltag($langs->transnoentities('Edit')) . '"><i class="fas fa-pen"></i></button>'
            . '<button type="button" class="tac-thread__action tac-thread__action--danger" data-msg-delete title="' . dol_escape_htmltag($langs->transnoentities('Delete')) . '"><i class="fas fa-trash"></i></button>'
            . '</div>'
            . '</div>'
            . '</li>';
        respond(true, $langs->transnoentities('MessagePosted') . $mailNotice, ['message_id' => (int) $newId, 'bubble' => $bubble]);

    /*
     * edit_message — update a TICKET_MSG ActionComm's body (and optionally subject).
     * Only the message's author can edit their own posts.
     */
    case 'edit_message':
        $msgId   = (int) GETPOST('message_id', 'int');
        $body    = trim((string) GETPOST('body', 'restricthtml'));
        $subject = trim((string) GETPOST('subject', 'alphanohtml'));
        if ($msgId <= 0 || $body === '') {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $ac = new ActionComm($db);
        if ($ac->fetch($msgId) <= 0) {
            respond(false, $langs->transnoentities('ErrorRecordNotFound'));
        }
        if ((int) $ac->elementid !== (int) $object->id || (int) $ac->authorid !== (int) $user->id) {
            respond(false, $langs->transnoentities('NotAllowed'));
        }
        $ac->note_private = $body;
        if ($subject !== '') {
            $ac->label = $subject;
        }
        if ($ac->update($user) <= 0) {
            respond(false, $ac->error ?: $langs->transnoentities('Error'));
        }
        $bodyLinkified = preg_replace_callback(
            '#(?<!href=["\'])(https?://[^\s<>"\']+)#i',
            static fn($m) => '<a href="' . dol_escape_htmltag($m[1]) . '" target="_blank" rel="noopener">' . dol_escape_htmltag($m[1]) . '</a>',
            renderThreadBody($body)
        );
        respond(true, $langs->transnoentities('MessageEdited'), [
            'message_id' => $msgId,
            'body_html'  => $bodyLinkified,
            'subject'    => $subject,
        ]);

    /*
     * delete_message — remove a TICKET_MSG ActionComm. Only the original author may delete.
     */
    case 'delete_message':
        $msgId = (int) GETPOST('message_id', 'int');
        if ($msgId <= 0) {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $ac = new ActionComm($db);
        if ($ac->fetch($msgId) <= 0) {
            respond(false, $langs->transnoentities('ErrorRecordNotFound'));
        }
        if ((int) $ac->elementid !== (int) $object->id || (int) $ac->authorid !== (int) $user->id) {
            respond(false, $langs->transnoentities('NotAllowed'));
        }
        if ($ac->delete($user) <= 0) {
            respond(false, $ac->error ?: $langs->transnoentities('Error'));
        }
        respond(true, $langs->transnoentities('MessageDeleted'), ['message_id' => $msgId]);

    /*
     * upload_file — receive a single file via FormData and drop it in the ticket's
     * upload directory. Caller-side drag&drop sends one request per file.
     * Sanitizes the filename + checks size against PHP's upload_max_filesize.
     */
    case 'upload_file':
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        if (empty($_FILES['upload']) || !is_uploaded_file($_FILES['upload']['tmp_name'] ?? '')) {
            respond(false, $langs->transnoentities('ErrorFileNotUploaded'));
        }
        if (!empty($_FILES['upload']['error'])) {
            respond(false, $langs->transnoentities('Error') . ' (' . (int) $_FILES['upload']['error'] . ')');
        }
        $name = dol_sanitizeFileName((string) $_FILES['upload']['name']);
        if ($name === '' || $name[0] === '.') {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        $uploadDir = $conf->ticket->multidir_output[$conf->entity ?? 1] . '/' . dol_sanitizeFileName($object->ref);
        if (!is_dir($uploadDir)) {
            dol_mkdir($uploadDir);
        }
        $target = $uploadDir . '/' . $name;
        $moved  = dol_move_uploaded_file($_FILES['upload']['tmp_name'], $target, 1, 0, 0, 0, 'upload');
        if ($moved <= 0) {
            respond(false, $langs->transnoentities('ErrorFailedToWriteFile') . ' ' . $name);
        }
        respond(true, $langs->transnoentities('FileUploaded') . ': ' . $name, ['file_name' => $name]);

    /*
     * toggle_watch — bookmark/unbookmark this ticket for the current user.
     * Persists in llx_user_param under the DIGIRISK_TICKET_WATCHLIST key as a CSV of ids.
     */
    case 'toggle_watch':
        $sqlSel = 'SELECT value FROM ' . MAIN_DB_PREFIX . 'user_param'
            . ' WHERE fk_user = ' . ((int) $user->id) . ' AND entity = ' . ((int) $conf->entity)
            . " AND param = 'DIGIRISK_TICKET_WATCHLIST'";
        $existing = '';
        $rWatch = $db->query($sqlSel);
        if ($rWatch && ($row = $db->fetch_object($rWatch))) {
            $existing = (string) $row->value;
        }
        $ids = array_values(array_filter(array_map('intval', explode(',', $existing))));
        $idsKey = array_search((int) $object->id, $ids, true);
        $nowWatched = false;
        if ($idsKey === false) {
            $ids[] = (int) $object->id;
            $nowWatched = true;
        } else {
            unset($ids[$idsKey]);
            $ids = array_values($ids);
        }
        $newVal = implode(',', $ids);
        $db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'user_param'
            . ' WHERE fk_user = ' . ((int) $user->id) . ' AND entity = ' . ((int) $conf->entity)
            . " AND param = 'DIGIRISK_TICKET_WATCHLIST'");
        $db->query('INSERT INTO ' . MAIN_DB_PREFIX . "user_param (fk_user, entity, param, value)"
            . ' VALUES (' . ((int) $user->id) . ', ' . ((int) $conf->entity) . ", 'DIGIRISK_TICKET_WATCHLIST', '" . $db->escape($newVal) . "')");
        respond(true, $langs->transnoentities($nowWatched ? 'TicketWatched' : 'TicketUnwatched'), ['watched' => $nowWatched]);

    /*
     * delete_file — remove a file attached to the ticket's upload directory.
     * The filename is sanitized and resolved against the canonical ticket dir to
     * prevent any path traversal (../something) from escaping the sandbox.
     */
    case 'delete_file':
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        $rawName = (string) GETPOST('file_name', 'alphanohtml');
        $name    = dol_sanitizeFileName($rawName);
        if ($name === '' || $name[0] === '.') {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        $uploadDir = $conf->ticket->multidir_output[$conf->entity ?? 1] . '/' . dol_sanitizeFileName($object->ref);
        $target    = $uploadDir . '/' . $name;
        // Resolve to absolute path and check it still lives under the upload dir.
        $real      = realpath($target);
        $realDir   = realpath($uploadDir);
        if (!$real || !$realDir || strpos($real, $realDir . DIRECTORY_SEPARATOR) !== 0) {
            respond(false, $langs->transnoentities('RecordNotFound'));
        }
        $ok = dol_delete_file($real, 0, 0, 0, $object);
        if (!$ok) {
            respond(false, $langs->transnoentities('Error'));
        }
        respond(true, $langs->transnoentities('FileDeleted'), ['file_name' => $name]);

    /*
     * add_category / remove_category — 1-click tag toggle on the ticket. Uses Dolibarr's
     * Categorie::add_type / del_type which mutate the llx_categorie_ticket link table.
     */
    case 'add_category':
    case 'remove_category':
        $categoryId = (int) GETPOST('category_id', 'int');
        if ($categoryId <= 0) {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $catObj = new Categorie($db);
        if ($catObj->fetch($categoryId) <= 0) {
            respond(false, $langs->transnoentities('RecordNotFound'));
        }
        // After fetch(), $catObj->type is the integer code from llx_categorie.type (12 for ticket).
        // MAP_ID translates the symbolic name to that int — robust against future renumbering.
        $expectedTicketTypeId = (int) ($catObj->MAP_ID['ticket'] ?? 12);
        if ((int) $catObj->type !== $expectedTicketTypeId) {
            respond(false, $langs->transnoentities('RecordNotFound'));
        }
        if ($action === 'add_category') {
            $res = $catObj->add_type($object, 'ticket');
            // add_type returns 1 on success, -3 if the link already exists. Treat both as OK.
            $ok = ($res > 0 || $res === -3);
            $msg = $langs->transnoentities('TagAdded');
        } else {
            $res = $catObj->del_type($object, 'ticket');
            $ok = ($res > 0);
            $msg = $langs->transnoentities('TagRemoved');
        }
        if (!$ok) {
            respond(false, $catObj->error ?: ($langs->transnoentities('Error') . ' (' . (int) $res . ')'));
        }
        respond(true, $msg, [
            'category_id' => $categoryId,
            'label'       => $catObj->label,
            'color'       => $catObj->color,
        ]);

    /*
     * reset_layout — wipe the user's saved layout so the page reverts to defaults.
     */
    case 'reset_layout':
        $sqlDel = 'DELETE FROM ' . MAIN_DB_PREFIX . 'user_param'
            . ' WHERE fk_user = ' . ((int) $user->id)
            . ' AND entity = ' . ((int) $conf->entity)
            . " AND param = 'DIGIRISK_TICKET_CARD_LAYOUT'";
        $ok = $db->query($sqlDel);
        if (!$ok) {
            respond(false, $db->lasterror() ?: 'ResetFailed');
        }
        respond(true, $langs->transnoentities('LayoutReset'));

    /*
     * save_layout — per-user persistence of the card layout (visible/width/order
     * for each section). Writes a JSON blob to llx_user_param so user->conf->DIGIRISK_TICKET_CARD_LAYOUT
     * is populated automatically on subsequent logins.
     */
    case 'save_layout':
        $layoutRaw = GETPOST('layout', 'restricthtml');
        $decoded = json_decode((string) $layoutRaw, true);
        if (!is_array($decoded)) {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        // Re-encode to canonicalize and strip anything unexpected.
        // Schema v2: top-level density (compact|cozy|spacious) + { visible, width, order } per section.
        $sanitized = [
            'v'           => 2,
            'density'     => in_array($decoded['density']     ?? '', ['compact', 'cozy', 'spacious'], true) ? $decoded['density']     : 'cozy',
            'tagsMode'    => in_array($decoded['tagsMode']    ?? '', ['chips', 'selector'],            true) ? $decoded['tagsMode']    : 'chips',
            'actionsMode' => in_array($decoded['actionsMode'] ?? '', ['bar', 'menu'],                  true) ? $decoded['actionsMode'] : 'bar',
            'sections'    => [],
        ];
        foreach (($decoded['sections'] ?? []) as $id => $cfg) {
            if (!preg_match('/^[a-z_]+$/', (string) $id) || !is_array($cfg)) {
                continue;
            }
            $sanitized['sections'][$id] = [
                'visible' => !empty($cfg['visible']),
                'width'   => in_array($cfg['width'] ?? '', ['half', 'full', 'span'], true) ? $cfg['width'] : 'full',
                'order'   => isset($cfg['order']) ? (int) $cfg['order'] : 0,
            ];
        }
        $json = json_encode($sanitized);

        // Upsert into llx_user_param.
        $sqlDel = 'DELETE FROM ' . MAIN_DB_PREFIX . 'user_param WHERE fk_user = ' . ((int) $user->id)
            . ' AND entity = ' . ((int) $conf->entity)
            . " AND param = 'DIGIRISK_TICKET_CARD_LAYOUT'";
        $db->query($sqlDel);
        $sqlIns = 'INSERT INTO ' . MAIN_DB_PREFIX . "user_param (fk_user, entity, param, value)"
            . ' VALUES (' . ((int) $user->id) . ', ' . ((int) $conf->entity) . ", 'DIGIRISK_TICKET_CARD_LAYOUT', '" . $db->escape($json) . "')";
        $ok = $db->query($sqlIns);
        if (!$ok) {
            respond(false, $db->lasterror() ?: 'SaveFailed');
        }
        respond(true, $langs->transnoentities('LayoutSaved'), ['layout' => $sanitized]);

    case 'assign_other':
        $userIdToAssign = (int) $param;
        if ($userIdToAssign <= 0) {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }
        $res = $object->assignUser($user, $userIdToAssign, 1);
        if ($res > 0) {
            if ((int) $object->fk_statut === Ticket::STATUS_NOT_READ) {
                $object->setStatut(Ticket::STATUS_ASSIGNED, null, '', 'TICKET_MODIFY');
            }
            $object->fetch($ticketId);
            respond(true, $langs->transnoentities('TicketActionAssignedOtherDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->transnoentities('Error'));

    /*
     * update_field — generic tap-to-edit dispatcher used by the reworked card.
     *
     * Inputs:  field (string), value (raw scalar, may be empty), ticket_id.
     * Outputs: success+message+ticket{} as the other actions, plus 'display' = best-effort
     *          re-rendered string for the field (so the JS can replace the value cell
     *          without a full page reload).
     */
    case 'update_field':
        // 'aZ09' already permits underscores — using 'aZ09_' would be an unknown filter and silently return ''.
        $field = GETPOST('field', 'aZ09');
        $rawValue = GETPOST('value', 'restricthtml');
        if ($field === '') {
            respond(false, $langs->transnoentities('ErrorBadParameters'));
        }

        $display = '';
        $msg     = $langs->transnoentities('Saved');

        if ($field === 'subject') {
            $object->subject = trim((string) $rawValue);
            $res = $object->update($user) > 0;
            $display = dol_escape_htmltag($object->subject ?: $langs->trans('NoSubject'));
        } elseif ($field === 'message') {
            // Native ticket.message column — rich HTML from CKEditor. dolPrintHTML preserves
            // safe tags (b/strong/em/etc.) for the inline post-save replacement.
            $object->message = (string) $rawValue;
            $res = $object->update($user) > 0;
            $display = !empty($object->message) ? dolPrintHTML($object->message) : '';
        } elseif ($field === 'fk_statut') {
            $newStatus = (int) $rawValue;
            $res = $object->setStatut($newStatus, null, '', 'TICKET_MODIFY') > 0;
            $object->fetch($ticketId);
            $display = $object->getLibStatut(2);
        } elseif ($field === 'fk_user_assign') {
            $newAssignee = (int) $rawValue;
            if ($newAssignee === 0) {
                // Dolibarr Ticket has no native "unassign" — write the column directly via update().
                $object->fk_user_assign = null;
                $res = $object->update($user) > 0;
                $display = '<i class="fas fa-user-slash"></i> ' . dol_escape_htmltag($langs->trans('NotAssigned'));
            } else {
                $res = $object->assignUser($user, $newAssignee, 1) > 0;
                if ($res && (int) $object->fk_statut === Ticket::STATUS_NOT_READ) {
                    $object->setStatut(Ticket::STATUS_ASSIGNED, null, '', 'TICKET_MODIFY');
                }
                $object->fetch($ticketId);
                $newUser = new User($db);
                $newUser->fetch($newAssignee);
                $display = '<i class="fas fa-user"></i> ' . dol_escape_htmltag($newUser->getFullName($langs));
            }
        } elseif ($field === 'progress') {
            $newProgress = max(0, min(100, (int) $rawValue));
            $res = $object->setProgression($newProgress) >= 0;
            $object->progress = $newProgress;
            $display = '<i class="fas fa-tasks"></i> ' . $newProgress . '%';
        } elseif (in_array($field, ['type_code', 'severity_code', 'category_code'], true)) {
            // Type / severity / category — string code in the corresponding c_ticket_* dictionary.
            $object->{$field} = (string) $rawValue;
            $res = $object->update($user) > 0;
            // Resolve the human label for the display.
            $table = ['type_code' => 'c_ticket_type', 'severity_code' => 'c_ticket_severity', 'category_code' => 'c_ticket_category'][$field];
            $labelRes = $db->query('SELECT label FROM ' . MAIN_DB_PREFIX . $db->escape($table) . " WHERE code = '" . $db->escape((string) $rawValue) . "' LIMIT 1");
            $display = '';
            if ($labelRes && ($row = $db->fetch_object($labelRes))) {
                $display = dol_escape_htmltag($langs->trans($row->label) ?: $row->label);
            }
        } elseif ($field === 'fk_soc') {
            // Third party link — accepts 0 to detach.
            $newSocId = (int) $rawValue;
            $object->fk_soc = $newSocId > 0 ? $newSocId : null;
            $res = $object->update($user) > 0;
            if ($newSocId > 0) {
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                $soc = new Societe($db);
                $soc->fetch($newSocId);
                $display = $soc->getNomUrl(1);
            } else {
                $display = '<span class="opacitymedium">—</span>';
            }
        } elseif ($field === 'fk_project') {
            // Project link — accepts 0 to detach.
            $newProjectId = (int) $rawValue;
            $object->fk_project = $newProjectId > 0 ? $newProjectId : null;
            $res = $object->update($user) > 0;
            if ($newProjectId > 0) {
                require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                $project = new Project($db);
                $project->fetch($newProjectId);
                $display = $project->getNomUrl(1) . ($project->title ? ' - ' . dol_escape_htmltag($project->title) : '');
            } else {
                $display = '<span class="opacitymedium">—</span>';
            }
        } elseif (strpos($field, 'options_') === 0) {
            // Generic extrafield write path — catches Digirisk + any other module's extrafield on ticket.
            $extraKey = substr($field, strlen('options_'));
            $valueToStore = $rawValue;

            // Detect the extrafield type so we can normalize date values to timestamps.
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $efTmp = new ExtraFields($db);
            $efTmp->fetch_name_optionals_label($object->table_element);
            $efType = $efTmp->attributes[$object->table_element]['type'][$extraKey] ?? 'varchar';

            if (in_array($efType, ['date', 'datetime'], true) && $rawValue !== '') {
                $ts = strtotime((string) $rawValue);
                $valueToStore = $ts ?: '';
            }

            $object->array_options['options_' . $extraKey] = $valueToStore;
            $res = $object->insertExtraFields() >= 0;

            if (in_array($efType, ['date', 'datetime'], true)) {
                $display = $valueToStore ? dol_print_date((int) $valueToStore, 'day', 'tzuser') : '';
            } elseif (in_array($efType, ['text', 'html'], true)) {
                // dol_escape_htmltag would strip <strong>/<b>; dolPrintHTML preserves rich HTML safely.
                $display = dolPrintHTML((string) $valueToStore);
            } elseif ($extraKey === 'digiriskdolibarr_ticket_service') {
                // GP/UT extrafield — render the linked DigiriskElement's HTML link, not the raw id.
                // $addLabel=1 (last arg) appends " - <label>" to the ref so users see "GP1 - Siège Evarisk".
                $display = '';
                $newId = (int) $valueToStore;
                if ($newId > 0 && file_exists(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php')) {
                    require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php';
                    $de = new DigiriskElement($db);
                    if ($de->fetch($newId) > 0) {
                        $display = $de->getNomUrl(1, '', 0, '', -1, 1);
                    }
                }
            } else {
                $display = dol_escape_htmltag((string) $valueToStore);
            }
        } else {
            respond(false, $langs->transnoentities('UnknownFieldToUpdate', $field));
        }

        if (!$res) {
            respond(false, $object->error ?: $langs->transnoentities('Error'));
        }

        $payload = $buildPayload($object);
        $payload['field']   = $field;
        $payload['display'] = $display;
        respond(true, $msg, $payload);

    default:
        respond(false, $langs->transnoentities('ErrorBadParameters'));
}
