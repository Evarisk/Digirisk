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

global $conf, $db, $langs, $user;

$langs->loadLangs(['digiriskdolibarr@digiriskdolibarr', 'ticket']);

// Inputs — 'aZ09' permits a-z, 0-9, _, -, . so update_field passes through fine.
$ticketId = GETPOSTINT('ticket_id');
$action   = GETPOST('action', 'aZ09');
$param    = GETPOST('param', 'alpha');

// Permission gate — all writes require ticket->write + digiriskdolibarr->lire.
if ( ! ($user->hasRight('ticket', 'write') && $user->hasRight('digiriskdolibarr', 'lire'))) {
    respond(false, $langs->trans('NotEnoughPermissions'));
}

if ($ticketId <= 0 || empty($action)) {
    respond(false, $langs->trans('ErrorBadParameters'));
}

$object = new Ticket($db);
if ($object->fetch($ticketId) <= 0 || $object->id <= 0) {
    respond(false, $langs->trans('RecordNotFound'));
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
    return [
        'ticket' => [
            'id'            => (int) $tkt->id,
            'status'        => (int) $tkt->fk_statut,
            'status_html'   => $tkt->getLibStatut(2),
            'assigned_id'   => (int) $tkt->fk_user_assign,
            'assigned_name' => $assignedLabel,
        ],
    ];
};

$res = 0;

switch ($action) {
    case 'mark_read':
        $res = $object->markAsRead($user);
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->trans('TicketActionMarkedRead'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->trans('Error'));
        // no break — respond() exits.

    case 'set_progress':
        $res = $object->setStatut(Ticket::STATUS_IN_PROGRESS, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->trans('TicketActionInProgressDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->trans('Error'));

    case 'set_waiting':
        $res = $object->setStatut(Ticket::STATUS_WAITING, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->trans('TicketActionWaitingDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->trans('Error'));

    case 'set_closed':
        $res = $object->setStatut(Ticket::STATUS_CLOSED, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->trans('TicketActionClosedDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->trans('Error'));

    case 'set_cancelled':
        $res = $object->setStatut(Ticket::STATUS_CANCELED, null, '', 'TICKET_MODIFY');
        if ($res > 0) {
            $object->fetch($ticketId);
            respond(true, $langs->trans('TicketActionCancelledDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->trans('Error'));

    case 'assign_self':
        $res = $object->assignUser($user, (int) $user->id, 1);
        if ($res > 0) {
            // Dolibarr core convention: switch unread tickets to STATUS_ASSIGNED on assignment.
            if ((int) $object->fk_statut === Ticket::STATUS_NOT_READ) {
                $object->setStatut(Ticket::STATUS_ASSIGNED, null, '', 'TICKET_MODIFY');
            }
            $object->fetch($ticketId);
            respond(true, $langs->trans('TicketActionAssignedSelfDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->trans('Error'));

    case 'assign_other':
        $userIdToAssign = (int) $param;
        if ($userIdToAssign <= 0) {
            respond(false, $langs->trans('ErrorBadParameters'));
        }
        $res = $object->assignUser($user, $userIdToAssign, 1);
        if ($res > 0) {
            if ((int) $object->fk_statut === Ticket::STATUS_NOT_READ) {
                $object->setStatut(Ticket::STATUS_ASSIGNED, null, '', 'TICKET_MODIFY');
            }
            $object->fetch($ticketId);
            respond(true, $langs->trans('TicketActionAssignedOtherDone'), $buildPayload($object));
        }
        respond(false, $object->error ?: $langs->trans('Error'));

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
            respond(false, $langs->trans('ErrorBadParameters'));
        }

        $display = '';
        $msg     = $langs->trans('Saved');

        if ($field === 'subject') {
            $object->subject = trim((string) $rawValue);
            $res = $object->update($user) > 0;
            $display = dol_escape_htmltag($object->subject ?: $langs->trans('NoSubject'));
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
                $display = $valueToStore ? dol_print_date((int) $valueToStore, 'day') : '';
            } else {
                $display = dol_escape_htmltag((string) $valueToStore);
            }
        } else {
            respond(false, $langs->trans('UnknownFieldToUpdate', $field));
        }

        if (!$res) {
            respond(false, $object->error ?: $langs->trans('Error'));
        }

        $payload = $buildPayload($object);
        $payload['field']   = $field;
        $payload['display'] = $display;
        respond(true, $msg, $payload);

    default:
        respond(false, $langs->trans('ErrorBadParameters'));
}
