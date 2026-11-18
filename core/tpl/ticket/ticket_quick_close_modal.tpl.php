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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/ticket/ticket_quick_close_modal.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Quick close modal of the tickets, the way the to-do board of ReedCRM closes an event: at 100 % the ticket
 *          is closed, below only its progress changes (action quickCloseTicket of view/ticket/ticket_action_card.php,
 *          js/modules/ticket_quick_close.js). Opened from the percentage of an open ticket on the ticket kanban, and
 *          from its state on the list of the registers.
 *
 * Variables expected:
 *   $langs
 *   $ticketQuickCloseListTickets array Optional, list of the registers only: open tickets of the page the state
 *                                      opens the modal on, [ticket ID => ['ref' => ..., 'subject' => ...]]
 */

if (empty($langs)) {
    print 'Error: missing variables for ticket_quick_close_modal.tpl.php';
    return;
}
?>

<!-- Saturne closes any modal on a click on its backdrop, a selection released there included: the JS closes this one
     itself, only when the press started on the backdrop -->
<div class="wpeo-modal modal-ticket-quick-close modal-close-only-with-button" id="tacQuickCloseModal"
     data-url="<?= dol_escape_htmltag(dol_buildpath('/digiriskdolibarr/view/ticket/ticket_action_card.php', 1)) ?>"
     data-closed-status="<?= Ticket::STATUS_CLOSED ?>"
     data-trans-tooltip="<?= dol_escape_htmltag($langs->trans('TicketQuickCloseTooltip')) ?>"
     data-trans-title-close="<?= dol_escape_htmltag($langs->trans('TicketQuickCloseTitle')) ?>"
     data-trans-title-progress="<?= dol_escape_htmltag($langs->trans('TicketQuickCloseProgressTitle')) ?>"
     data-trans-confirm-close="<?= dol_escape_htmltag($langs->trans('TicketActionClose')) ?>"
     data-trans-confirm-progress="<?= dol_escape_htmltag($langs->trans('Save')) ?>"
     data-trans-error="<?= dol_escape_htmltag($langs->trans('TicketQuickCloseError')) ?>"
     data-trans-empty="<?= dol_escape_htmltag($langs->trans('NoTicketsFound')) ?>"
     <?php if (!empty($ticketQuickCloseListTickets)) : ?>data-list-tickets="<?= dol_escape_htmltag(json_encode($ticketQuickCloseListTickets)) ?>"<?php endif; ?>>
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title"><?= dol_escape_htmltag($langs->trans('TicketQuickCloseTitle')) ?></h2>
            <div class="modal-close"><i class="fas fa-times"></i></div>
        </div>
        <div class="modal-content">
            <?php // Which ticket is on the table, filled in by the JS from the clicked card or row ?>
            <div class="tac-quick-close__ticket">
                <span class="tac-quick-close__ref"></span>
                <span class="tac-quick-close__subject"></span>
            </div>

            <div class="tac-quick-close__progress">
                <label class="tac-quick-close__label" for="tacQuickCloseProgress"><?= dol_escape_htmltag($langs->trans('Progression')) ?></label>
                <input type="range" id="tacQuickCloseProgressRange" class="tac-quick-close__range" min="0" max="100" step="5" value="100">
                <input type="number" id="tacQuickCloseProgress" class="tac-quick-close__value" min="0" max="100" step="1" value="100">
                <span>%</span>
            </div>
            <span class="tac-quick-close__help"><?= dol_escape_htmltag($langs->trans('TicketQuickCloseProgressHelp')) ?></span>

            <label class="tac-quick-close__label" for="tacQuickCloseComment"><?= dol_escape_htmltag($langs->trans('Comment')) ?></label>
            <textarea id="tacQuickCloseComment" class="tac-quick-close__comment" rows="4" placeholder="<?= dol_escape_htmltag($langs->trans('TicketQuickCloseCommentPlaceholder')) ?>"></textarea>

            <div class="tac-quick-close__error" aria-live="polite"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="wpeo-button button-grey tac-quick-close-cancel"><?= dol_escape_htmltag($langs->trans('Cancel')) ?></button>
            <button type="button" class="wpeo-button button-blue tac-quick-close-confirm"><i class="fas fa-check"></i>&nbsp;<span class="tac-quick-close__confirm-label"><?= dol_escape_htmltag($langs->trans('TicketActionClose')) ?></span></button>
        </div>
    </div>
</div>
