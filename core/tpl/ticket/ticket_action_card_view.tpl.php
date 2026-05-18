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
 * \file    core/tpl/ticket/ticket_action_card_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   1-click action tile UI for a single ticket.
 *
 * The following vars must be defined before include:
 *   $conf, $db, $langs, $user, $object (loaded Ticket)
 */

// Protection
if (empty($conf) || empty($db) || empty($langs) || empty($user) || empty($object) || $object->id <= 0) {
    print 'Error, missing parameters';
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$status         = (int) $object->fk_statut;
$isClosed       = in_array($status, [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELED], true);
$assignedUser   = null;
if ((int) $object->fk_user_assign > 0) {
    $assignedUser = new User($db);
    $assignedUser->fetch((int) $object->fk_user_assign);
}

$backUrl = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1);
$ajaxUrl = dol_buildpath('/custom/digiriskdolibarr/core/ajax/ticket_action_card.php', 1);

/**
 * Render a single action tile.
 *
 * Tile data attributes are read by js/modules/ticket_action_card.js to dispatch the
 * AJAX call and toggle UI state. Disabled tiles are dimmed and not clickable.
 */
$renderTile = function (string $action, string $icon, string $label, string $color, bool $disabled = false, array $extra = []) use ($langs): void {
    $classes  = ['ticket-action-tile', 'ticket-action-tile--' . $color];
    if ($disabled) {
        $classes[] = 'ticket-action-tile--disabled';
    }
    $dataAttr = 'data-action="' . dol_escape_htmltag($action) . '"';
    foreach ($extra as $key => $value) {
        $dataAttr .= ' data-' . dol_escape_htmltag($key) . '="' . dol_escape_htmltag((string) $value) . '"';
    }
    print '<button type="button" class="' . implode(' ', $classes) . '" ' . $dataAttr . ($disabled ? ' disabled' : '') . '>';
    print '<span class="ticket-action-tile__icon"><i class="' . dol_escape_htmltag($icon) . '"></i></span>';
    print '<span class="ticket-action-tile__label">' . dol_escape_htmltag($langs->trans($label)) . '</span>';
    print '</button>';
};
?>

<div class="ticket-action-card" data-ticket-id="<?php print (int) $object->id; ?>" data-ajax-url="<?php print dol_escape_htmltag($ajaxUrl); ?>">
    <input type="hidden" name="token" value="<?php print newToken(); ?>">

    <!-- Header: ref / subject / status / assignee -->
    <div class="ticket-action-card__header">
        <div class="ticket-action-card__back">
            <a href="<?php print dol_escape_htmltag($backUrl); ?>" class="ticket-action-card__back-link">
                <i class="fas fa-arrow-left"></i> <?php print $langs->trans('BackToList'); ?>
            </a>
        </div>
        <div class="ticket-action-card__title">
            <div class="ticket-action-card__ref"><?php print dol_escape_htmltag($object->ref); ?></div>
            <div class="ticket-action-card__subject"><?php print dol_escape_htmltag($object->subject ?: $langs->trans('NoSubject')); ?></div>
        </div>
        <div class="ticket-action-card__meta">
            <span class="ticket-action-card__meta-item ticket-action-card__status" data-status="<?php print (int) $status; ?>">
                <?php print $object->getLibStatut(2); ?>
            </span>
            <?php if ($assignedUser) : ?>
                <span class="ticket-action-card__meta-item">
                    <i class="fas fa-user"></i> <?php print dol_escape_htmltag($assignedUser->getFullName($langs)); ?>
                </span>
            <?php else : ?>
                <span class="ticket-action-card__meta-item opacitymedium">
                    <i class="fas fa-user-slash"></i> <?php print $langs->trans('NotAssigned'); ?>
                </span>
            <?php endif; ?>
            <?php if (!empty($object->datec)) : ?>
                <span class="ticket-action-card__meta-item">
                    <i class="fas fa-calendar"></i> <?php print dol_print_date($object->datec, 'day'); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tile grid sections -->
    <div class="ticket-action-card__body">

        <div class="ticket-action-card__section">
            <h3 class="ticket-action-card__section-title"><?php print $langs->trans('TicketActionCardStatusSection'); ?></h3>
            <div class="ticket-action-card__tiles">
                <?php
                $renderTile('mark_read',    'fas fa-eye',          'TicketActionMarkRead',    'blue',   $status !== Ticket::STATUS_NOT_READ);
                $renderTile('set_progress', 'fas fa-rocket',       'TicketActionInProgress',  'green',  $status === Ticket::STATUS_IN_PROGRESS || $isClosed);
                $renderTile('set_waiting',  'fas fa-pause-circle', 'TicketActionWaiting',     'orange', $status === Ticket::STATUS_WAITING || $isClosed);
                $renderTile('set_closed',   'fas fa-check-circle', 'TicketActionClose',       'green',  $isClosed, ['confirm' => 'TicketActionCloseConfirm']);
                ?>
            </div>
        </div>

        <div class="ticket-action-card__section">
            <h3 class="ticket-action-card__section-title"><?php print $langs->trans('TicketActionCardAssignSection'); ?></h3>
            <div class="ticket-action-card__tiles">
                <?php
                $renderTile('assign_self',   'fas fa-user-check', 'TicketActionAssignSelf',   'blue', (int) $object->fk_user_assign === (int) $user->id);
                $renderTile('assign_other',  'fas fa-user-edit',  'TicketActionAssignOther',  'blue', $isClosed);
                ?>
            </div>
        </div>

        <div class="ticket-action-card__section">
            <h3 class="ticket-action-card__section-title"><?php print $langs->trans('TicketActionCardContentSection'); ?></h3>
            <div class="ticket-action-card__tiles">
                <?php
                $renderTile('add_message',     'fas fa-comment-dots',   'TicketActionAddMessage',    'purple', false);
                $renderTile('link_accident',   'fas fa-link',           'TicketActionLinkAccident',  'purple', false);
                $renderTile('open_full_card',  'fas fa-external-link-alt', 'TicketActionOpenFull',   'grey',   false);
                ?>
            </div>
        </div>

        <div class="ticket-action-card__section ticket-action-card__section--danger">
            <h3 class="ticket-action-card__section-title"><?php print $langs->trans('TicketActionCardDangerSection'); ?></h3>
            <div class="ticket-action-card__tiles">
                <?php
                $renderTile('set_cancelled', 'fas fa-times-circle', 'TicketActionCancel', 'red', $isClosed, ['confirm' => 'TicketActionCancelConfirm']);
                ?>
            </div>
        </div>

    </div>

    <!-- Toast/feedback area populated by JS -->
    <div class="ticket-action-card__toast" role="status" aria-live="polite"></div>
</div>
