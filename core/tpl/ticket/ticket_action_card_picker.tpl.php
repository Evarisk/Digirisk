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
 * \file    core/tpl/ticket/ticket_action_card_picker.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Picker shown when ticket_action_card.php is opened without an id/track_id.
 *
 * The following vars must be defined before include:
 *   $db, $langs, $user
 */

// Protection
if (empty($conf) || empty($db) || empty($langs) || empty($user)) {
    print 'Error, missing parameters';
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

// Open tickets only — assigned to the current user when possible, all others as fallback.
// Ticket::fetchAll signature is ($user, $sortorder, $sortfield, $limit, $offset, $arch, $filter).
// It populates $this->lines instead of returning the list.
$pickerTicket = new Ticket($db);
$filter       = [
    't.fk_statut' => [
        Ticket::STATUS_NOT_READ,
        Ticket::STATUS_READ,
        Ticket::STATUS_ASSIGNED,
        Ticket::STATUS_IN_PROGRESS,
        Ticket::STATUS_NEED_MORE_INFO,
        Ticket::STATUS_WAITING,
    ],
];
$pickerTicket->fetchAll($user, 'DESC', 't.datec', 20, 0, 0, $filter);

print load_fiche_titre($langs->trans('TicketActionCard'), '', 'ticket');

print '<div class="ticket-action-picker">';
print '<p class="opacitymedium">' . $langs->trans('TicketActionCardPickerIntro') . '</p>';

if (is_array($pickerTicket->lines) && !empty($pickerTicket->lines)) {
    print '<div class="ticket-action-picker-grid">';
    foreach ($pickerTicket->lines as $tkt) {
        $url = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1) . '?id=' . (int) $tkt->id;

        print '<a class="ticket-action-picker-item" href="' . dol_escape_htmltag($url) . '">';
        print '<div class="ticket-action-picker-item__ref">' . dol_escape_htmltag($tkt->ref) . '</div>';
        print '<div class="ticket-action-picker-item__subject">' . dol_escape_htmltag(dol_trunc((string) $tkt->subject, 80)) . '</div>';
        print '<div class="ticket-action-picker-item__status">' . $tkt->getLibStatut(2) . '</div>';
        print '</a>';
    }
    print '</div>';
} else {
    print '<div class="opacitymedium center">' . $langs->trans('NoTicketOpen') . '</div>';
}

print '</div>';
