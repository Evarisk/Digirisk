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

if (empty($conf) || empty($db) || empty($langs) || empty($user)) {
    print 'Error, missing parameters';
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

// User's watchlist (CSV of ticket ids stored in llx_user_param) for the "watched" filter + star.
$rawWatchList = $user->conf->DIGIRISK_TICKET_WATCHLIST ?? '';
$watchSet     = array_flip(array_filter(array_map('intval', explode(',', (string) $rawWatchList))));
$showOnlyWatched = (GETPOST('watched', 'aZ09') === '1');

// Open tickets only.
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
$pickerTicket->fetchAll($user, 'DESC', 't.datec', 50, 0, 0, $filter);

// Restrict to watched tickets when the toggle is on.
$tickets = is_array($pickerTicket->lines) ? $pickerTicket->lines : [];
if ($showOnlyWatched) {
    $tickets = array_filter($tickets, static fn($t) => isset($watchSet[(int) $t->id]));
}

$watchedCount = count($watchSet);

print load_fiche_titre($langs->trans('TicketActionCard'), '', 'ticket');

print '<div class="ticket-action-picker">';
print '<p class="opacitymedium">' . $langs->trans('TicketActionCardPickerIntro') . '</p>';

// Filter toolbar: Tous / Suivis seulement.
$baseUrl  = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1);
$allUrl   = $baseUrl;
$watchUrl = $baseUrl . '?watched=1';
print '<div class="ticket-action-picker-toolbar">';
print '<a class="ticket-action-picker-toolbar__btn' . (!$showOnlyWatched ? ' is-active' : '') . '" href="' . dol_escape_htmltag($allUrl) . '">'
    . $langs->trans('AllTickets') . '</a>';
print '<a class="ticket-action-picker-toolbar__btn' . ($showOnlyWatched ? ' is-active' : '') . '" href="' . dol_escape_htmltag($watchUrl) . '">'
    . '<i class="fas fa-star"></i> ' . $langs->trans('WatchedOnly') . ' (' . (int) $watchedCount . ')</a>';
print '</div>';

if (!empty($tickets)) {
    print '<div class="ticket-action-picker-grid">';
    foreach ($tickets as $tkt) {
        $url = $baseUrl . '?id=' . (int) $tkt->id;
        $isWatched = isset($watchSet[(int) $tkt->id]);

        print '<a class="ticket-action-picker-item' . ($isWatched ? ' ticket-action-picker-item--watched' : '') . '" href="' . dol_escape_htmltag($url) . '">';
        if ($isWatched) {
            print '<i class="fas fa-star ticket-action-picker-item__star" title="' . dol_escape_htmltag($langs->trans('WatchedTicket')) . '"></i>';
        }
        print '<div class="ticket-action-picker-item__ref">' . dol_escape_htmltag($tkt->ref) . '</div>';
        print '<div class="ticket-action-picker-item__subject">' . dol_escape_htmltag(dol_trunc((string) $tkt->subject, 80)) . '</div>';
        print '<div class="ticket-action-picker-item__status">' . $tkt->getLibStatut(2) . '</div>';
        print '</a>';
    }
    print '</div>';
} else {
    print '<div class="opacitymedium center">' . $langs->trans($showOnlyWatched ? 'NoWatchedTicket' : 'NoTicketOpen') . '</div>';
}

print '</div>';
