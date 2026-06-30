<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/ticket/ticket_action_card.php
 * \ingroup digiriskdolibarr
 * \brief   One-click ticket action card — large tile UI for fast status / assignment / link actions.
 *          Picker (no ticket selected) renders a Kanban board of all open tickets.
 *
 * Issue #4443 — IHM ticket registre.
 */

// Force-load CKEditor so the longtext tap-to-edit (ConditionMessage, Initial message)
// can replace its textarea with a rich-text editor on demand.
if (!defined('FORCE_CKEDITOR')) {
    define('FORCE_CKEDITOR', 1);
}

if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs(['ticket', 'companies']);

// Get parameters
$id      = GETPOSTINT('id');
$trackId = GETPOST('track_id', 'alpha');
$action  = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Ticket($db);
if ($id > 0 || !empty($trackId)) {
    $object->fetch($id, '', $trackId);
    if ($object->id > 0) {
        $object->fetch_optionals();
    }
}

$hookmanager->initHooks([$moduleNameLowerCase . 'ticketactioncard', 'globalcard']);

// Security check
$permissionToRead  = $user->hasRight('ticket', 'read') && $user->hasRight($moduleNameLowerCase, 'read');
$permissionToWrite = $user->hasRight('ticket', 'write');
saturne_check_access($permissionToRead);

/**
 * Create an ActionComm audit event for a ticket kanban modification.
 * Only fires when the given config constant is enabled (non-zero).
 *
 * @param DoliDB $db         Database connection
 * @param User   $user       Current user
 * @param Ticket $ticket     The modified ticket
 * @param string $constName  Config constant controlling whether this event is logged
 * @param string $label      Short event label
 * @param string $note       Detailed private note
 * @return void
 */
function createTicketKanbanEvent($db, $user, $ticket, $constName, $label, $note)
{
    if (empty(getDolGlobalInt($constName))) {
        return;
    }

    $actioncomm               = new ActionComm($db);
    $actioncomm->type_code    = 'AC_OTH_AUTO';
    $actioncomm->code         = 'AC_OTH_AUTO';
    $actioncomm->label        = $label;
    $actioncomm->note_private = $note;
    $actioncomm->fk_element   = $ticket->id;
    $actioncomm->elementtype  = 'ticket';
    $actioncomm->userownerid  = $user->id;
    $actioncomm->datep        = dol_now();
    $actioncomm->percentage   = -1;
    $actioncomm->create($user);
}

/*
 * Actions
 */

// AJAX: update ticket status from kanban drag & drop
if ($action == 'updateTicketStatus' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId  = GETPOSTINT('ticket_id');
    $dimValue  = GETPOST('dim_value', 'alpha');
    $newStatus = $dimValue !== '' ? (int) $dimValue : GETPOSTINT('new_status');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $res = $ticketToUpdate->setStatut($newStatus, $user);
        if ($res > 0) {
            createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_STATUS',
                'Statut modifié (kanban)', 'Statut modifié → ' . $newStatus);
            print json_encode(['success' => 1]);
        } else {
            dol_syslog('ticket_action_card.php updateTicketStatus: setStatut failed ticketId=' . $ticketId . ' - ' . $ticketToUpdate->error, LOG_ERR);
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $ticketToUpdate->error]);
        }
    } else {
        dol_syslog('ticket_action_card.php updateTicketStatus: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: update ticket assignee from inline edit
if ($action == 'updateTicketAssignee' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId = GETPOSTINT('ticket_id');
    $userId   = GETPOSTINT('user_id');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $ticketToUpdate->fk_user_assign = $userId > 0 ? $userId : null;
        $res = $ticketToUpdate->update($user);
        if ($res > 0) {
            $fullname  = '';
            $initials  = '';
            if ($userId > 0) {
                $assignedUser = new User($db);
                $assignedUser->fetch($userId);
                $fullname = $assignedUser->getFullName($langs);
                $initials = strtoupper(mb_substr($assignedUser->firstname, 0, 1) . mb_substr($assignedUser->lastname, 0, 1));
            }
            createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_ASSIGNEE',
                'Assigné modifié (kanban)', 'Assigné → ' . ($fullname ?: $langs->trans('Unassigned')));
            print json_encode(['success' => 1, 'fullname' => $fullname, 'initials' => $initials]);
        } else {
            dol_syslog('ticket_action_card.php updateTicketAssignee: update failed ticketId=' . $ticketId . ' - ' . $ticketToUpdate->error, LOG_ERR);
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $ticketToUpdate->error]);
        }
    } else {
        dol_syslog('ticket_action_card.php updateTicketAssignee: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: add a category to a ticket
if ($action == 'addTicketCategory' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId = GETPOSTINT('ticket_id');
    $catId    = GETPOSTINT('cat_id');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $cat    = new Categorie($db);
        $resCat = $cat->fetch($catId);
        if ($resCat > 0) {
            $res = $cat->add_type($ticketToUpdate, 'ticket');
            if ($res > 0 || $res == -3) {
                createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_CATEGORY_ADD',
                    'Tag ajouté (kanban)', 'Tag ajouté : ' . $cat->label);
                print json_encode(['success' => 1, 'label' => $cat->label, 'color' => $cat->color, 'id' => $cat->id]);
            } else {
                dol_syslog('ticket_action_card.php addTicketCategory: add_type failed ticketId=' . $ticketId . ' catId=' . $catId . ' - ' . $cat->error, LOG_ERR);
                print json_encode(['success' => 0, 'error' => $cat->error]);
            }
        } else {
            dol_syslog('ticket_action_card.php addTicketCategory: category not found catId=' . $catId, LOG_ERR);
            print json_encode(['success' => 0, 'error' => 'Category not found']);
        }
    } else {
        dol_syslog('ticket_action_card.php addTicketCategory: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: remove a category from a ticket
if ($action == 'removeTicketCategory' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId = GETPOSTINT('ticket_id');
    $catId    = GETPOSTINT('cat_id');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $cat    = new Categorie($db);
        $resCat = $cat->fetch($catId);
        if ($resCat > 0) {
            $res = $cat->del_type($ticketToUpdate, 'ticket');
            if ($res >= 0) {
                createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_CATEGORY_REMOVE',
                    'Tag retiré (kanban)', 'Tag retiré : ' . $cat->label);
                print json_encode(['success' => 1]);
            } else {
                dol_syslog('ticket_action_card.php removeTicketCategory: del_type failed ticketId=' . $ticketId . ' catId=' . $catId . ' - ' . $cat->error, LOG_ERR);
                print json_encode(['success' => 0, 'error' => $cat->error]);
            }
        } else {
            dol_syslog('ticket_action_card.php removeTicketCategory: category not found catId=' . $catId, LOG_ERR);
            print json_encode(['success' => 0, 'error' => 'Category not found']);
        }
    } else {
        dol_syslog('ticket_action_card.php removeTicketCategory: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: update ticket type (dim kanban drag & drop)
if ($action == 'updateTicketType' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId = GETPOSTINT('ticket_id');
    $dimValue = GETPOST('dim_value', 'alpha');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $ticketToUpdate->type_code = !empty($dimValue) && $dimValue !== '__none__' ? $dimValue : '';
        $res = $ticketToUpdate->update($user);
        if ($res > 0) {
            createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_TYPE',
                'Type modifié (kanban)', 'Type modifié → ' . ($dimValue ?: 'Non défini'));
            print json_encode(['success' => 1]);
        } else {
            dol_syslog('ticket_action_card.php updateTicketType: update failed ticketId=' . $ticketId . ' - ' . $ticketToUpdate->error, LOG_ERR);
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $ticketToUpdate->error]);
        }
    } else {
        dol_syslog('ticket_action_card.php updateTicketType: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: update ticket group/category (dim kanban drag & drop)
if ($action == 'updateTicketGroup' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId = GETPOSTINT('ticket_id');
    $dimValue = GETPOST('dim_value', 'alpha');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $ticketToUpdate->category_code = !empty($dimValue) && $dimValue !== '__none__' ? $dimValue : '';
        $res = $ticketToUpdate->update($user);
        if ($res > 0) {
            createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_GROUP',
                'Groupe modifié (kanban)', 'Groupe modifié → ' . ($dimValue ?: 'Non défini'));
            print json_encode(['success' => 1]);
        } else {
            dol_syslog('ticket_action_card.php updateTicketGroup: update failed ticketId=' . $ticketId . ' - ' . $ticketToUpdate->error, LOG_ERR);
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $ticketToUpdate->error]);
        }
    } else {
        dol_syslog('ticket_action_card.php updateTicketGroup: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: update ticket severity (dim kanban drag & drop)
if ($action == 'updateTicketSeverity' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId = GETPOSTINT('ticket_id');
    $dimValue = GETPOST('dim_value', 'alpha');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $ticketToUpdate->severity_code = !empty($dimValue) && $dimValue !== '__none__' ? $dimValue : '';
        $res = $ticketToUpdate->update($user);
        if ($res > 0) {
            createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_SEVERITY',
                'Sévérité modifiée (kanban)', 'Sévérité modifiée → ' . ($dimValue ?: 'Non définie'));
            print json_encode(['success' => 1]);
        } else {
            dol_syslog('ticket_action_card.php updateTicketSeverity: update failed ticketId=' . $ticketId . ' - ' . $ticketToUpdate->error, LOG_ERR);
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $ticketToUpdate->error]);
        }
    } else {
        dol_syslog('ticket_action_card.php updateTicketSeverity: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: update ticket subject (inline edit on kanban card)
if ($action == 'updateTicketSubject' && !empty(GETPOSTINT('ticket_id'))) {
    header('Content-Type: application/json');

    $ticketId   = GETPOSTINT('ticket_id');
    $newSubject = GETPOST('subject', 'nohtml');

    $ticketToUpdate = new Ticket($db);
    $result         = $ticketToUpdate->fetch($ticketId);

    if ($result > 0) {
        $ticketToUpdate->subject = $newSubject;
        $res = $ticketToUpdate->update($user);
        if ($res > 0) {
            createTicketKanbanEvent($db, $user, $ticketToUpdate, 'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_SUBJECT',
                'Sujet modifié (kanban)', 'Sujet modifié → "' . $newSubject . '"');
            print json_encode(['success' => 1, 'subject' => $newSubject]);
        } else {
            dol_syslog('ticket_action_card.php updateTicketSubject: update failed ticketId=' . $ticketId . ' - ' . $ticketToUpdate->error, LOG_ERR);
            http_response_code(500);
            print json_encode(['success' => 0, 'error' => $ticketToUpdate->error]);
        }
    } else {
        dol_syslog('ticket_action_card.php updateTicketSubject: ticket not found ticketId=' . $ticketId, LOG_ERR);
        http_response_code(404);
        print json_encode(['success' => 0, 'error' => 'Ticket not found']);
    }
    $db->close();
    exit;
}

// AJAX: quick-create a ticket from the picker drawer
if ($action == 'createTicket' && $permissionToWrite) {
    header('Content-Type: application/json');

    $newTicket                       = new Ticket($db);
    $newTicket->subject              = GETPOST('subject', 'alphanohtml');
    $newTicket->type_code            = GETPOST('type_code', 'alpha');
    $newTicket->severity_code        = GETPOST('severity_code', 'alpha');
    $newTicket->message              = GETPOST('message', 'restricthtml');
    $newTicket->fk_user_create       = $user->id;
    $newTicket->email_from           = $user->email;
    $newTicket->origin_email         = null;
    $newTicket->notify_tiers_at_create = 0;

    $newTicket->type_label     = $langs->getLabelFromKey($db, $newTicket->type_code, 'c_ticket_type', 'code', 'label');
    $newTicket->severity_label = $langs->getLabelFromKey($db, $newTicket->severity_code, 'c_ticket_severity', 'code', 'label');

    // Generate the next ticket reference (required by Ticket::verify() before create).
    $newTicket->ref = $newTicket->getDefaultRef();

    $fkAssign = GETPOSTINT('fk_user_assign');
    if ($fkAssign > 0) {
        $newTicket->fk_user_assign = $fkAssign;
        $newTicket->status         = Ticket::STATUS_ASSIGNED;
    }

    $newId = $newTicket->create($user, 1);
    if ($newId > 0) {
        // Move uploaded attachments into the ticket directory.
        $uploadedNames = !empty($_FILES['attachments']['name']) ? (array) $_FILES['attachments']['name'] : [];
        $uploadedTmps  = !empty($_FILES['attachments']['tmp_name']) ? (array) $_FILES['attachments']['tmp_name'] : [];
        $uploadedErrs  = !empty($_FILES['attachments']['error']) ? (array) $_FILES['attachments']['error'] : [];

        if (!empty($uploadedNames) && $uploadedNames[0] !== '') {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            $destDir = $conf->ticket->dir_output . '/' . dol_sanitizeFileName($newTicket->ref);
            if (!dol_is_dir($destDir)) {
                dol_mkdir($destDir);
            }

            foreach ($uploadedNames as $i => $rawName) {
                if (!empty($uploadedErrs[$i]) || empty($rawName) || empty($uploadedTmps[$i])) {
                    continue;
                }
                $safeName = dol_sanitizeFileName($rawName);
                $destFile = $destDir . '/' . $safeName;
                if (file_exists($destFile)) {
                    $pi       = pathinfo($safeName);
                    $destFile = $destDir . '/' . $pi['filename'] . '-' . dol_print_date(dol_now(), 'dayhourlog') . '.' . ($pi['extension'] ?? 'bin');
                }
                dol_move_uploaded_file($uploadedTmps[$i], $destFile, 1, 0, $uploadedErrs[$i]);
            }
        }

        $detailUrl = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1) . '?id=' . $newId;
        print json_encode(['success' => 1, 'id' => $newId, 'ref' => $newTicket->ref, 'url' => $detailUrl]);
    } else {
        $errMsg = $newTicket->error ?: implode(', ', $newTicket->errors ?: []);
        dol_syslog('ticket_action_card.php createTicket: create failed - ' . $errMsg, LOG_ERR);
        http_response_code(500);
        print json_encode(['success' => 0, 'error' => $errMsg ?: 'Ticket creation failed']);
    }
    $db->close();
    exit;
}

/*
 * View
 */

$title   = $langs->trans('TicketActionCard');
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $helpUrl);

// Kanban picker data — loaded only when no specific ticket is requested.
$pickerAllUsers          = [];
$pickerAllCategories     = [];
$pickerTicketsJson       = [];
$pickerTypeColumns       = [];
$pickerTypeTickets       = [];
$pickerGroupColumns      = [];
$pickerGroupTickets      = [];
$pickerSeverityColumns   = [];
$pickerSeverityTickets   = [];
$pickerStatusColumns     = [];
$pickerStatusTickets     = [];

if ($object->id <= 0) {
    // Hidden token for AJAX requests
    print '<input type="hidden" name="token" value="' . newToken() . '">';

    // All active internal users for the assignee picker
    $sqlUsers = 'SELECT u.rowid, u.firstname, u.lastname, u.photo FROM ' . MAIN_DB_PREFIX . 'user u'
        . ' WHERE u.statut = 1 AND u.entity IN (' . getEntity('user') . ')'
        . ' ORDER BY u.lastname, u.firstname';
    $resUsers = $db->query($sqlUsers);
    if ($resUsers) {
        while ($objU = $db->fetch_object($resUsers)) {
            $photoUrl = '';
            if (!empty($objU->photo)) {
                $photoFile = DOL_DATA_ROOT . '/users/' . (int) $objU->rowid . '/' . $objU->photo;
                if (file_exists($photoFile)) {
                    $photoUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $conf->entity
                        . '&file=' . urlencode($objU->rowid . '/' . $objU->photo)
                        . '&cache=' . (int) $objU->rowid;
                }
            }
            $pickerAllUsers[] = [
                'id'       => (int) $objU->rowid,
                'fullname' => trim($objU->firstname . ' ' . $objU->lastname),
                'initials' => strtoupper(mb_substr($objU->firstname, 0, 1) . mb_substr($objU->lastname, 0, 1)),
                'photo'    => $photoUrl,
            ];
        }
        $db->free($resUsers);
    }

    // All available ticket categories
    $categorie       = new Categorie($db);
    $ticketCatTypeId = (int) $categorie->MAP_ID['ticket'];
    $sqlCats         = 'SELECT rowid, label, color FROM ' . MAIN_DB_PREFIX . 'categorie'
        . ' WHERE type = ' . $ticketCatTypeId . ' AND entity IN (' . getEntity('category') . ')'
        . ' ORDER BY label';
    $resCats = $db->query($sqlCats);
    if ($resCats) {
        while ($objCat = $db->fetch_object($resCats)) {
            $pickerAllCategories[] = ['id' => (int) $objCat->rowid, 'label' => $objCat->label, 'color' => $objCat->color];
        }
        $db->free($resCats);
    }

    // Tickets with enriched data (assignee, severity, type, group, company).
    // Closed and canceled tickets are limited by the admin config to avoid overloading the board.
    $closedStatusList  = implode(',', [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELED]);
    $closedLimit       = getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_KANBAN_CLOSED_LIMIT', 50);
    $closedLimitClause = $closedLimit > 0 ? ' AND t.fk_statut NOT IN (' . $closedStatusList . ')' : '';

    $sqlTickets = 'SELECT t.rowid, t.ref, t.subject, t.fk_statut, t.datec, t.severity_code,'
        . ' t.type_code, t.category_code,'
        . ' t.fk_user_assign, t.fk_soc,'
        . ' u.firstname as assign_firstname, u.lastname as assign_lastname, u.photo as assign_photo,'
        . ' s.nom as company_name'
        . ' FROM ' . MAIN_DB_PREFIX . 'ticket t'
        . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON u.rowid = t.fk_user_assign'
        . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON s.rowid = t.fk_soc'
        . ' WHERE t.entity IN (' . getEntity('ticket') . ')'
        . $closedLimitClause
        . ' ORDER BY t.datec DESC'
        . ' LIMIT 500';

    if ($closedLimit > 0) {
        $sqlClosed = 'SELECT t.rowid, t.ref, t.subject, t.fk_statut, t.datec, t.severity_code,'
            . ' t.type_code, t.category_code,'
            . ' t.fk_user_assign, t.fk_soc,'
            . ' u.firstname as assign_firstname, u.lastname as assign_lastname, u.photo as assign_photo,'
            . ' s.nom as company_name'
            . ' FROM ' . MAIN_DB_PREFIX . 'ticket t'
            . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON u.rowid = t.fk_user_assign'
            . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON s.rowid = t.fk_soc'
            . ' WHERE t.entity IN (' . getEntity('ticket') . ')'
            . ' AND t.fk_statut IN (' . $closedStatusList . ')'
            . ' ORDER BY t.datec DESC'
            . ' LIMIT ' . (int) $closedLimit;
    }
    $resTickets = $db->query($sqlTickets);
    $ticketIds  = [];
    if ($resTickets) {
        while ($row = $db->fetch_object($resTickets)) {
            $assignId       = (int) $row->fk_user_assign;
            $assignInitials = '';
            $assignFullname = '';
            $assignPhoto    = '';
            if ($assignId > 0) {
                $assignFullname = trim($row->assign_firstname . ' ' . $row->assign_lastname);
                $assignInitials = strtoupper(mb_substr($row->assign_firstname, 0, 1) . mb_substr($row->assign_lastname, 0, 1));
                if (!empty($row->assign_photo)) {
                    $photoFile = DOL_DATA_ROOT . '/users/' . $assignId . '/' . $row->assign_photo;
                    if (file_exists($photoFile)) {
                        $assignPhoto = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $conf->entity
                            . '&file=' . urlencode($assignId . '/' . $row->assign_photo)
                            . '&cache=' . $assignId;
                    }
                }
            }

            $detailUrl = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1) . '?id=' . (int) $row->rowid;

            $sevCode  = strtoupper((string) ($row->severity_code ?: ''));
            $sevTrans = $sevCode !== '' ? $langs->trans('TicketSeverityShort' . $sevCode) : '';
            if ($sevTrans === 'TicketSeverityShort' . $sevCode) {
                $sevTrans = $sevCode;
            }

            $pickerTicketsJson[] = [
                'id'              => (int) $row->rowid,
                'ref'             => $row->ref,
                'subject'         => $row->subject,
                'status'          => (int) $row->fk_statut,
                'severity_code'   => $sevCode,
                'severity_label'  => $sevTrans,
                'type_code'       => (string) ($row->type_code ?: ''),
                'category_code'   => (string) ($row->category_code ?: ''),
                'date_c'          => $row->datec ? dol_print_date($db->jdate($row->datec), 'day') : '',
                'company_name'    => $row->company_name ?: '',
                'assign_id'       => $assignId,
                'assign_fullname' => $assignFullname,
                'assign_initials' => $assignInitials,
                'assign_photo'    => $assignPhoto,
                'categories'      => [],
                'url'             => $detailUrl,
            ];
            $ticketIds[] = (int) $row->rowid;
        }
        $db->free($resTickets);
    }

    // Append closed/canceled tickets (limited by admin config)
    if ($closedLimit > 0 && !empty($sqlClosed)) {
        $resClosed = $db->query($sqlClosed);
        if ($resClosed) {
            while ($row = $db->fetch_object($resClosed)) {
                $assignId       = (int) $row->fk_user_assign;
                $assignInitials = '';
                $assignFullname = '';
                $assignPhoto    = '';
                if ($assignId > 0) {
                    $assignFullname = trim($row->assign_firstname . ' ' . $row->assign_lastname);
                    $assignInitials = strtoupper(mb_substr($row->assign_firstname, 0, 1) . mb_substr($row->assign_lastname, 0, 1));
                    if (!empty($row->assign_photo)) {
                        $photoFile = DOL_DATA_ROOT . '/users/' . $assignId . '/' . $row->assign_photo;
                        if (file_exists($photoFile)) {
                            $assignPhoto = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $conf->entity
                                . '&file=' . urlencode($assignId . '/' . $row->assign_photo)
                                . '&cache=' . $assignId;
                        }
                    }
                }

                $detailUrl = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1) . '?id=' . (int) $row->rowid;
                $sevCode   = strtoupper((string) ($row->severity_code ?: ''));
                $sevTrans  = $sevCode !== '' ? $langs->trans('TicketSeverityShort' . $sevCode) : '';
                if ($sevTrans === 'TicketSeverityShort' . $sevCode) {
                    $sevTrans = $sevCode;
                }

                $pickerTicketsJson[] = [
                    'id'              => (int) $row->rowid,
                    'ref'             => $row->ref,
                    'subject'         => $row->subject,
                    'status'          => (int) $row->fk_statut,
                    'severity_code'   => $sevCode,
                    'severity_label'  => $sevTrans,
                    'type_code'       => (string) ($row->type_code ?: ''),
                    'category_code'   => (string) ($row->category_code ?: ''),
                    'date_c'          => $row->datec ? dol_print_date($db->jdate($row->datec), 'day') : '',
                    'company_name'    => $row->company_name ?: '',
                    'assign_id'       => $assignId,
                    'assign_fullname' => $assignFullname,
                    'assign_initials' => $assignInitials,
                    'assign_photo'    => $assignPhoto,
                    'categories'      => [],
                    'url'             => $detailUrl,
                ];
                $ticketIds[] = (int) $row->rowid;
            }
            $db->free($resClosed);
        }
    }

    // Batch-load categories for all tickets
    if (!empty($ticketIds)) {
        $sqlTktCats = 'SELECT ct.fk_ticket, c.rowid, c.label, c.color'
            . ' FROM ' . MAIN_DB_PREFIX . 'categorie c'
            . ' JOIN ' . MAIN_DB_PREFIX . 'categorie_ticket ct ON ct.fk_categorie = c.rowid'
            . ' WHERE ct.fk_ticket IN (' . implode(',', $ticketIds) . ')';
        $resTktCats = $db->query($sqlTktCats);
        $catsByTicket = [];
        if ($resTktCats) {
            while ($row = $db->fetch_object($resTktCats)) {
                $catsByTicket[(int) $row->fk_ticket][] = [
                    'id'    => (int) $row->rowid,
                    'label' => $row->label,
                    'color' => $row->color,
                ];
            }
            $db->free($resTktCats);
        }
        foreach ($pickerTicketsJson as &$t) {
            if (isset($catsByTicket[$t['id']])) {
                $t['categories'] = $catsByTicket[$t['id']];
            }
        }
        unset($t);
    }

    // -----------------------------------------------------------------------
    // Dimension 1 — Type
    // -----------------------------------------------------------------------
    $pickerTypeColumns  = [];
    $pickerTypeTickets  = [];
    $dimColorPalette    = ['#5b8cdb', '#e9ad4f', '#28a745', '#9c6ade', '#17a2b8', '#fd7e14', '#6f42c1', '#6c757d'];

    $sqlTypes = 'SELECT code, label FROM ' . MAIN_DB_PREFIX . 'c_ticket_type'
        . ' WHERE active = 1 AND entity IN (' . getEntity('c_ticket_type') . ')'
        . ' ORDER BY label';
    $resTypes = $db->query($sqlTypes);
    $typeColorIdx = 0;
    if ($resTypes) {
        while ($r = $db->fetch_object($resTypes)) {
            $transKey = 'TicketTypeShort' . $r->code;
            $label    = $langs->trans($transKey);
            if ($label === $transKey) {
                $label = $r->label;
            }
            $pickerTypeColumns[$r->code] = [
                'label' => $label,
                'icon'  => 'fa-tag',
                'color' => $dimColorPalette[$typeColorIdx % count($dimColorPalette)],
            ];
            $typeColorIdx++;
        }
        $db->free($resTypes);
    }
    $pickerTypeColumns['__none__'] = ['label' => $langs->trans('Undefined'), 'icon' => 'fa-tag', 'color' => '#adb5bd'];

    foreach (array_keys($pickerTypeColumns) as $tc) {
        $pickerTypeTickets[$tc] = [];
    }
    foreach ($pickerTicketsJson as $t) {
        $tc = !empty($t['type_code']) ? $t['type_code'] : '__none__';
        if (!isset($pickerTypeTickets[$tc])) {
            $pickerTypeTickets['__none__'][] = $t;
        } else {
            $pickerTypeTickets[$tc][] = $t;
        }
    }

    // -----------------------------------------------------------------------
    // Dimension 2 — Group (category_code)
    // -----------------------------------------------------------------------
    $pickerGroupColumns = [];
    $pickerGroupTickets = [];

    $sqlGroups = 'SELECT code, label FROM ' . MAIN_DB_PREFIX . 'c_ticket_category'
        . ' WHERE active = 1 AND entity IN (' . getEntity('c_ticket_category') . ')'
        . ' ORDER BY label';
    $resGroups = $db->query($sqlGroups);
    $groupColorIdx = 0;
    if ($resGroups) {
        while ($r = $db->fetch_object($resGroups)) {
            $transKey = 'TicketCategoryShort' . $r->code;
            $label    = $langs->trans($transKey);
            if ($label === $transKey) {
                $label = $r->label;
            }
            $pickerGroupColumns[$r->code] = [
                'label' => $label,
                'icon'  => 'fa-folder',
                'color' => $dimColorPalette[$groupColorIdx % count($dimColorPalette)],
            ];
            $groupColorIdx++;
        }
        $db->free($resGroups);
    }
    $pickerGroupColumns['__none__'] = ['label' => $langs->trans('Undefined'), 'icon' => 'fa-folder', 'color' => '#adb5bd'];

    foreach (array_keys($pickerGroupColumns) as $gc) {
        $pickerGroupTickets[$gc] = [];
    }
    foreach ($pickerTicketsJson as $t) {
        $gc = !empty($t['category_code']) ? $t['category_code'] : '__none__';
        if (!isset($pickerGroupTickets[$gc])) {
            $pickerGroupTickets['__none__'][] = $t;
        } else {
            $pickerGroupTickets[$gc][] = $t;
        }
    }

    // -----------------------------------------------------------------------
    // Dimension 3 — Severity
    // -----------------------------------------------------------------------
    $pickerSeverityColumns = [];
    $pickerSeverityTickets = [];
    $sevColorMap = [
        'LOW'      => '#28a745',
        'NORMAL'   => '#007bff',
        'HIGH'     => '#fd7e14',
        'BLOCKING' => '#dc3545',
    ];

    $sqlSevs = 'SELECT code, label FROM ' . MAIN_DB_PREFIX . 'c_ticket_severity'
        . ' WHERE active = 1 AND entity IN (' . getEntity('c_ticket_severity') . ')'
        . ' ORDER BY code';
    $resSevs = $db->query($sqlSevs);
    if ($resSevs) {
        while ($r = $db->fetch_object($resSevs)) {
            $transKey = 'TicketSeverityShort' . $r->code;
            $label    = $langs->trans($transKey);
            if ($label === $transKey) {
                $label = $r->label;
            }
            $pickerSeverityColumns[$r->code] = [
                'label' => $label,
                'icon'  => 'fa-exclamation-triangle',
                'color' => $sevColorMap[$r->code] ?? '#6c757d',
            ];
        }
        $db->free($resSevs);
    }
    $pickerSeverityColumns['__none__'] = ['label' => $langs->trans('Undefined'), 'icon' => 'fa-exclamation-triangle', 'color' => '#adb5bd'];

    foreach (array_keys($pickerSeverityColumns) as $sc) {
        $pickerSeverityTickets[$sc] = [];
    }
    foreach ($pickerTicketsJson as $t) {
        $sc = !empty($t['severity_code']) ? $t['severity_code'] : '__none__';
        if (!isset($pickerSeverityTickets[$sc])) {
            $pickerSeverityTickets['__none__'][] = $t;
        } else {
            $pickerSeverityTickets[$sc][] = $t;
        }
    }

    // -----------------------------------------------------------------------
    // Dimension 4 — Status (fk_statut)
    // -----------------------------------------------------------------------
    $tempTicket = new Ticket($db);
    $statusDefs = [
        Ticket::STATUS_NOT_READ       => ['icon' => 'fa-inbox',            'color' => '#6c757d'],
        Ticket::STATUS_READ           => ['icon' => 'fa-eye',              'color' => '#17a2b8'],
        Ticket::STATUS_ASSIGNED       => ['icon' => 'fa-user',             'color' => '#5b8cdb'],
        Ticket::STATUS_IN_PROGRESS    => ['icon' => 'fa-cogs',             'color' => '#e9ad4f'],
        Ticket::STATUS_NEED_MORE_INFO => ['icon' => 'fa-question-circle',  'color' => '#9c6ade'],
        Ticket::STATUS_CLOSED         => ['icon' => 'fa-check-circle',     'color' => '#28a745'],
        Ticket::STATUS_CANCELED       => ['icon' => 'fa-times-circle',     'color' => '#dc3545'],
    ];

    // STATUS_WAITING (7 = OnHold) is only available when TICKET_INCLUDE_SUSPENDED_STATUS is enabled
    if (getDolGlobalString('TICKET_INCLUDE_SUSPENDED_STATUS')) {
        $statusDefs[Ticket::STATUS_WAITING] = ['icon' => 'fa-pause-circle', 'color' => '#fd7e14'];
    }

    foreach ($statusDefs as $statusInt => $statusMeta) {
        $transKey = !empty($tempTicket->labelStatusShort[$statusInt]) ? $tempTicket->labelStatusShort[$statusInt] : '';
        $label    = $transKey !== '' ? $langs->transnoentitiesnoconv($transKey) : (string) $statusInt;
        $pickerStatusColumns[$statusInt] = [
            'label' => $label,
            'icon'  => $statusMeta['icon'],
            'color' => $statusMeta['color'],
        ];
    }
    $pickerStatusColumns['__none__'] = ['label' => $langs->trans('Other'), 'icon' => 'fa-question', 'color' => '#adb5bd'];

    foreach (array_keys($pickerStatusColumns) as $sts) {
        $pickerStatusTickets[$sts] = [];
    }
    foreach ($pickerTicketsJson as $t) {
        $sts = isset($pickerStatusColumns[$t['status']]) ? $t['status'] : '__none__';
        if (!isset($pickerStatusTickets[$sts])) {
            $pickerStatusTickets['__none__'][] = $t;
        } else {
            $pickerStatusTickets[$sts][] = $t;
        }
    }
}

// Quick-create drawer data — always loaded so the drawer is available on both views.
$createFormTypes      = [];
$createFormSeverities = [];
$createFormUsers      = $pickerAllUsers; // already populated when no ticket is selected

if ($object->id > 0) {
    // On the detail view the picker arrays are empty — re-fetch users.
    $sqlU = 'SELECT rowid, firstname, lastname FROM ' . MAIN_DB_PREFIX . 'user'
        . ' WHERE statut = 1 AND entity IN (' . getEntity('user') . ') ORDER BY lastname, firstname';
    $resU = $db->query($sqlU);
    if ($resU) {
        while ($u = $db->fetch_object($resU)) {
            $createFormUsers[] = [
                'id'       => (int) $u->rowid,
                'fullname' => trim($u->firstname . ' ' . $u->lastname),
            ];
        }
        $db->free($resU);
    }
}

$sqlCft = 'SELECT code, label FROM ' . MAIN_DB_PREFIX . 'c_ticket_type WHERE active = 1 AND entity IN (' . getEntity('c_ticket_type') . ') ORDER BY label';
$resCft = $db->query($sqlCft);
if ($resCft) {
    while ($r = $db->fetch_object($resCft)) {
        $transKey = 'TicketTypeShort' . $r->code;
        $lbl      = $langs->trans($transKey);
        $createFormTypes[$r->code] = ($lbl === $transKey) ? $r->label : $lbl;
    }
    $db->free($resCft);
}


$sqlCfs = 'SELECT code, label FROM ' . MAIN_DB_PREFIX . 'c_ticket_severity WHERE active = 1 AND entity IN (' . getEntity('c_ticket_severity') . ') ORDER BY code';
$resCfs = $db->query($sqlCfs);
if ($resCfs) {
    while ($r = $db->fetch_object($resCfs)) {
        $transKey = 'TicketSeverityShort' . $r->code;
        $lbl      = $langs->trans($transKey);
        $createFormSeverities[$r->code] = ($lbl === $transKey) ? $r->label : $lbl;
    }
    $db->free($resCfs);
}

// Page entry point — kanban picker if no ticket loaded, otherwise the tile card.
if ($object->id <= 0) {
    require_once __DIR__ . '/../../core/tpl/ticket/ticket_action_card_picker.tpl.php';
} else {
    require_once __DIR__ . '/../../core/tpl/ticket/ticket_action_card_view.tpl.php';
}

// End of page
llxFooter();
$db->close();
