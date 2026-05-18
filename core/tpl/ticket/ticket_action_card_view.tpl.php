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
 * \brief   Reworked ticket card — issue #4443. Displays the full ticket data
 *          (core + Digirisk extrafields + classification + linked accidents)
 *          with tap-to-edit inline editing and a sticky bar for destructive
 *          actions. Coexists with the standard Dolibarr ticket card via the
 *          "Détails complets" button.
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
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

$status       = (int) $object->fk_statut;
$isClosed     = in_array($status, [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELED], true);
$assignedUser = null;
if ((int) $object->fk_user_assign > 0) {
    $assignedUser = new User($db);
    $assignedUser->fetch((int) $object->fk_user_assign);
}

$backUrl     = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1);
$ajaxUrl     = dol_buildpath('/custom/digiriskdolibarr/core/ajax/ticket_action_card.php', 1);
$fullCardUrl = DOL_URL_ROOT . '/ticket/card.php?id=' . (int) $object->id;

// Build the user dropdown options once, shared by the assignee tap-to-edit and (later) other user pickers.
$userPickerOptions = [['id' => 0, 'label' => $langs->trans('NotAssigned')]];
$tmpUserSql = 'SELECT rowid, login, lastname, firstname FROM ' . MAIN_DB_PREFIX . 'user WHERE statut = 1 AND entity IN (' . getEntity('user') . ') ORDER BY lastname, firstname';
$tmpRes = $db->query($tmpUserSql);
if ($tmpRes) {
    while ($row = $db->fetch_object($tmpRes)) {
        $label = trim(($row->lastname ?: '') . ' ' . ($row->firstname ?: '')) ?: $row->login;
        $userPickerOptions[] = ['id' => (int) $row->rowid, 'label' => $label];
    }
}

// Ticket status options for the status tap-to-edit.
$statusOptions = [
    Ticket::STATUS_NOT_READ       => $langs->trans('Unread'),
    Ticket::STATUS_READ           => $langs->trans('Read'),
    Ticket::STATUS_ASSIGNED       => $langs->trans('Assigned'),
    Ticket::STATUS_IN_PROGRESS    => $langs->trans('InProgress'),
    Ticket::STATUS_NEED_MORE_INFO => $langs->trans('NeedMoreInformationShort'),
    Ticket::STATUS_WAITING        => $langs->trans('OnHold'),
    Ticket::STATUS_CLOSED         => $langs->trans('SolvedClosed'),
    Ticket::STATUS_CANCELED       => $langs->trans('Canceled'),
];

// Load all categories for this ticket (Classification section).
$catObj          = new Categorie($db);
$ticketCategories = $catObj->containing($object->id, Categorie::TYPE_TICKET);
if (!is_array($ticketCategories)) {
    $ticketCategories = [];
}

// Load linked accidents (Digirisk Accident class).
$linkedAccidents = [];
$accidentClassPath = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/accident.class.php';
if (file_exists($accidentClassPath)) {
    require_once $accidentClassPath;
    $accident       = new Accident($db);
    $linkedAccidents = $accident->fetchAll('', '', 0, 0, ['customsql' => 't.fk_ticket = ' . (int) $object->id]);
    if (!is_array($linkedAccidents)) {
        $linkedAccidents = [];
    }
}

// Standard Dolibarr ticket tabs (Contacts, Documents, Events…) so the user can hop to native screens.
$dolibarrTabs = ticket_prepare_head($object);

// Resolve all Digirisk extrafield values up-front so the TPL stays read-only.
$extra = $object->array_options ?? [];

/**
 * Render a tap-to-edit field.
 *
 * @param string $field    Logical field name sent to the AJAX endpoint (e.g. "subject", "fk_statut", "options_digiriskdolibarr_ticket_lastname").
 * @param string $type     UI type: "text" | "longtext" | "number" | "date" | "select" | "user" | "link" | "readonly".
 * @param string $label    Translation key for the field label.
 * @param mixed  $value    Current raw value (DB form).
 * @param string $display  Current display value (already HTML-safe).
 * @param array  $opts     Extra options. For 'select'/'user': 'options' => [{id,label}, ...]. For 'date': 'format' => 'day'/'dayhour'.
 */
$renderField = function (string $field, string $type, string $label, $value, string $display, array $opts = []) use ($langs): void {
    $classes  = ['tac-field', 'tac-field--' . $type];
    if ($type === 'readonly') {
        $classes[] = 'tac-field--readonly';
    }
    $attrs = [
        'class'           => implode(' ', $classes),
        'data-edit-field' => $field,
        'data-edit-type'  => $type,
        'data-edit-value' => is_scalar($value) ? (string) $value : '',
    ];
    if (!empty($opts['options'])) {
        $attrs['data-edit-options'] = json_encode(array_values($opts['options']));
    }
    if (!empty($opts['format'])) {
        $attrs['data-edit-format'] = $opts['format'];
    }
    $attrStr = '';
    foreach ($attrs as $k => $v) {
        $attrStr .= ' ' . $k . '="' . dol_escape_htmltag((string) $v) . '"';
    }
    print '<div' . $attrStr . '>';
    print '<div class="tac-field__label">' . dol_escape_htmltag($langs->trans($label)) . '</div>';
    print '<div class="tac-field__value" tabindex="0">' . ($display !== '' ? $display : '<span class="tac-field__empty">—</span>') . '</div>';
    print '</div>';
};
?>

<div class="ticket-action-card tac-card" data-ticket-id="<?php print (int) $object->id; ?>" data-ajax-url="<?php print dol_escape_htmltag($ajaxUrl); ?>">
    <input type="hidden" name="token" value="<?php print newToken(); ?>">

    <!-- ====== HERO HEADER ====== -->
    <div class="tac-hero">
        <div class="tac-hero__top">
            <a href="<?php print dol_escape_htmltag($backUrl); ?>" class="tac-hero__back">
                <i class="fas fa-arrow-left"></i> <?php print $langs->trans('BackToList'); ?>
            </a>
            <a href="<?php print dol_escape_htmltag($fullCardUrl); ?>" class="tac-hero__full" title="<?php print dol_escape_htmltag($langs->trans('TicketActionOpenFull')); ?>">
                <i class="fas fa-external-link-alt"></i> <?php print $langs->trans('TicketActionOpenFull'); ?>
            </a>
        </div>

        <div class="tac-hero__main">
            <div class="tac-hero__ref"><?php print dol_escape_htmltag($object->ref); ?></div>
            <div class="tac-hero__subject"
                 data-edit-field="subject"
                 data-edit-type="text"
                 data-edit-value="<?php print dol_escape_htmltag($object->subject ?? ''); ?>">
                <?php print dol_escape_htmltag($object->subject ?: $langs->trans('NoSubject')); ?>
            </div>
        </div>

        <div class="tac-hero__chips">
            <span class="tac-chip tac-chip--status"
                  data-edit-field="fk_statut"
                  data-edit-type="select"
                  data-edit-value="<?php print (int) $status; ?>"
                  data-edit-options='<?php print htmlspecialchars(json_encode(array_map(fn($k, $v) => ['id' => $k, 'label' => $v], array_keys($statusOptions), $statusOptions)), ENT_QUOTES); ?>'>
                <?php print $object->getLibStatut(2); ?>
            </span>

            <span class="tac-chip tac-chip--assignee"
                  data-edit-field="fk_user_assign"
                  data-edit-type="select"
                  data-edit-value="<?php print (int) $object->fk_user_assign; ?>"
                  data-edit-options='<?php print htmlspecialchars(json_encode($userPickerOptions), ENT_QUOTES); ?>'>
                <i class="fas fa-user"></i>
                <?php print $assignedUser ? dol_escape_htmltag($assignedUser->getFullName($langs)) : $langs->trans('NotAssigned'); ?>
            </span>

            <span class="tac-chip tac-chip--progress"
                  data-edit-field="progress"
                  data-edit-type="number"
                  data-edit-value="<?php print (int) $object->progress; ?>">
                <i class="fas fa-tasks"></i>
                <?php print (int) $object->progress; ?>%
            </span>

            <span class="tac-chip tac-chip--readonly" title="<?php print dol_escape_htmltag($langs->trans('DateCreation')); ?>">
                <i class="fas fa-calendar-plus"></i>
                <?php print dol_print_date($object->datec, 'day'); ?>
            </span>

            <?php if (!empty($object->date_read)) : ?>
            <span class="tac-chip tac-chip--readonly" title="<?php print dol_escape_htmltag($langs->trans('TicketReadOn')); ?>">
                <i class="fas fa-eye"></i>
                <?php print dol_print_date($object->date_read, 'day'); ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Dolibarr native tabs (Contacts, Documents, Events) inline as quick links -->
        <div class="tac-hero__nav-tabs">
            <?php foreach ($dolibarrTabs as $tab) :
                $tabUrl = $tab[0] ?? '';
                $tabLab = $tab[1] ?? '';
                if (!$tabUrl || !$tabLab) {
                    continue;
                }
                // Skip the "Ticket" tab itself — we ARE the ticket view.
                if (($tab[2] ?? '') === 'tabTicket') {
                    continue;
                }
                ?>
                <a class="tac-hero__nav-tab" href="<?php print dol_escape_htmltag($tabUrl); ?>"><?php print $tabLab; ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ====== BODY: 2-column grid ====== -->
    <div class="tac-body">

        <!-- LEFT COLUMN -->
        <div class="tac-col tac-col--left">

            <!-- Section: Informations registres (Digirisk extrafields) -->
            <section class="tac-section">
                <h3 class="tac-section__title"><i class="fas fa-clipboard-list"></i> <?php print $langs->trans('TicketActionCardRegistresSection'); ?></h3>
                <div class="tac-grid">
                    <?php
                    $renderField('options_digiriskdolibarr_ticket_lastname',  'text',     'LastName',
                        $extra['digiriskdolibarr_ticket_lastname']  ?? '',
                        dol_escape_htmltag((string) ($extra['digiriskdolibarr_ticket_lastname']  ?? '')));

                    $renderField('options_digiriskdolibarr_ticket_firstname', 'text',     'FirstName',
                        $extra['digiriskdolibarr_ticket_firstname'] ?? '',
                        dol_escape_htmltag((string) ($extra['digiriskdolibarr_ticket_firstname'] ?? '')));

                    $renderField('options_digiriskdolibarr_ticket_phone',     'text',     'Phone',
                        $extra['digiriskdolibarr_ticket_phone']     ?? '',
                        dol_escape_htmltag((string) ($extra['digiriskdolibarr_ticket_phone']     ?? '')));

                    // GP/UT — readonly link display (edit handled by full Dolibarr card for now).
                    $serviceId = $extra['digiriskdolibarr_ticket_service'] ?? '';
                    $serviceDisplay = '';
                    if (!empty($serviceId) && file_exists(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php')) {
                        require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskelement.class.php';
                        $de = new DigiriskElement($db);
                        if ($de->fetch((int) $serviceId) > 0) {
                            $serviceDisplay = $de->getNomUrl(1);
                        }
                    }
                    $renderField('options_digiriskdolibarr_ticket_service', 'readonly', 'GP/UT', $serviceId, $serviceDisplay);

                    $renderField('options_digiriskdolibarr_ticket_location',  'text',     'Location',
                        $extra['digiriskdolibarr_ticket_location']  ?? '',
                        dol_escape_htmltag((string) ($extra['digiriskdolibarr_ticket_location']  ?? '')));

                    $dateDeclar = $extra['digiriskdolibarr_ticket_date'] ?? null;
                    $renderField('options_digiriskdolibarr_ticket_date',      'date',     'DeclarationDate',
                        $dateDeclar ? (int) $dateDeclar : '',
                        $dateDeclar ? dol_print_date((int) $dateDeclar, 'day') : '',
                        ['format' => 'day']);
                    ?>
                </div>
            </section>

            <!-- Section: Condition message (longtext) -->
            <section class="tac-section">
                <h3 class="tac-section__title"><i class="fas fa-file-signature"></i> <?php print $langs->trans('ConditionMessage'); ?></h3>
                <div class="tac-grid tac-grid--single">
                    <?php
                    $condition = $extra['digiriskdolibarr_condition_message'] ?? '';
                    $renderField('options_digiriskdolibarr_condition_message', 'longtext', 'ConditionMessage',
                        $condition,
                        $condition !== '' ? dolPrintHTML($condition) : '');
                    ?>
                </div>
            </section>

            <!-- Section: Initial message — display only, edit through Dolibarr card. -->
            <section class="tac-section">
                <h3 class="tac-section__title"><i class="fas fa-envelope-open-text"></i> <?php print $langs->trans('InitialMessage'); ?></h3>
                <div class="tac-rich-text">
                    <?php print $object->message ? dolPrintHTML($object->message) : '<span class="opacitymedium">—</span>'; ?>
                </div>
                <a class="tac-section__edit-link" href="<?php print dol_escape_htmltag($fullCardUrl); ?>&action=edit_message_init">
                    <i class="fas fa-pen"></i> <?php print $langs->trans('Modify'); ?>
                </a>
            </section>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="tac-col tac-col--right">

            <!-- Section: Classification (tags) -->
            <section class="tac-section">
                <h3 class="tac-section__title"><i class="fas fa-tags"></i> <?php print $langs->trans('TicketActionCardClassificationSection'); ?></h3>
                <div class="tac-tags">
                    <?php foreach ($ticketCategories as $cat) :
                        $catColor = !empty($cat->color) ? '#' . dol_escape_htmltag($cat->color) : '#e5e7eb';
                        ?>
                        <span class="tac-tag" style="background:<?php print $catColor; ?>;">
                            <?php print dol_escape_htmltag($cat->label); ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if (empty($ticketCategories)) : ?>
                        <span class="tac-tag tac-tag--empty"><?php print $langs->trans('NoneSelected'); ?></span>
                    <?php endif; ?>
                </div>
                <a class="tac-section__edit-link" href="<?php print dol_escape_htmltag($fullCardUrl); ?>">
                    <i class="fas fa-pen"></i> <?php print $langs->trans('Modify'); ?>
                </a>
            </section>

            <!-- Section: Linked accidents -->
            <section class="tac-section">
                <h3 class="tac-section__title"><i class="fas fa-exclamation-triangle"></i> <?php print $langs->trans('AccidentsLinked'); ?></h3>
                <ul class="tac-list">
                    <?php foreach ($linkedAccidents as $acc) : ?>
                        <li class="tac-list__item"><?php print $acc->getNomUrl(1); ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($linkedAccidents)) : ?>
                        <li class="tac-list__item tac-list__item--empty">—</li>
                    <?php endif; ?>
                </ul>
                <a class="tac-section__edit-link" href="<?php print dol_buildpath('/custom/digiriskdolibarr/view/accident/accident_card.php?action=create&fk_ticket=' . (int) $object->id, 1); ?>">
                    <i class="fas fa-plus"></i> <?php print $langs->trans('TicketActionLinkAccident'); ?>
                </a>
            </section>

            <!-- Section: Key dates (read-only) -->
            <section class="tac-section">
                <h3 class="tac-section__title"><i class="fas fa-calendar"></i> <?php print $langs->trans('TicketActionCardDatesSection'); ?></h3>
                <div class="tac-grid">
                    <div class="tac-field tac-field--readonly">
                        <div class="tac-field__label"><?php print $langs->trans('DateCreation'); ?></div>
                        <div class="tac-field__value"><?php print dol_print_date($object->datec, 'dayhour'); ?></div>
                    </div>
                    <div class="tac-field tac-field--readonly">
                        <div class="tac-field__label"><?php print $langs->trans('TicketReadOn'); ?></div>
                        <div class="tac-field__value"><?php print !empty($object->date_read) ? dol_print_date($object->date_read, 'dayhour') : '<span class="tac-field__empty">—</span>'; ?></div>
                    </div>
                    <div class="tac-field tac-field--readonly">
                        <div class="tac-field__label"><?php print $langs->trans('TicketCloseOn'); ?></div>
                        <div class="tac-field__value"><?php print !empty($object->date_close) ? dol_print_date($object->date_close, 'dayhour') : '<span class="tac-field__empty">—</span>'; ?></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- ====== STICKY ACTION BAR ====== -->
    <div class="tac-sticky-bar">
        <button type="button" class="tac-sticky-bar__btn tac-sticky-bar__btn--orange ticket-action-tile" data-action="set_waiting" <?php print $isClosed || $status === Ticket::STATUS_WAITING ? 'disabled' : ''; ?>>
            <i class="fas fa-pause-circle"></i> <?php print $langs->trans('TicketActionWaiting'); ?>
        </button>
        <button type="button" class="tac-sticky-bar__btn tac-sticky-bar__btn--green ticket-action-tile" data-action="set_closed" data-confirm="TicketActionCloseConfirm" <?php print $isClosed ? 'disabled' : ''; ?>>
            <i class="fas fa-check-circle"></i> <?php print $langs->trans('TicketActionClose'); ?>
        </button>
        <button type="button" class="tac-sticky-bar__btn tac-sticky-bar__btn--red ticket-action-tile" data-action="set_cancelled" data-confirm="TicketActionCancelConfirm" <?php print $isClosed ? 'disabled' : ''; ?>>
            <i class="fas fa-times-circle"></i> <?php print $langs->trans('TicketActionCancel'); ?>
        </button>
    </div>

    <!-- Toast/feedback area populated by JS -->
    <div class="ticket-action-card__toast tac-toast" role="status" aria-live="polite"></div>
</div>
