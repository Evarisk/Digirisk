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
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
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

// ---- Dictionary options for type / severity / category (native ticket fields).
$dictOptions = function (string $table, ?string $code = null) use ($db, $langs): array {
    $opts = [['id' => '', 'label' => '— ' . $langs->trans('NoneSelected') . ' —']];
    $sql = 'SELECT code, label, active FROM ' . MAIN_DB_PREFIX . $db->escape($table)
        . ' WHERE active = 1 AND entity IN (' . getEntity($table) . ') ORDER BY label';
    $r = $db->query($sql);
    if ($r) {
        while ($row = $db->fetch_object($r)) {
            $opts[] = ['id' => $row->code, 'label' => $langs->trans($row->label) ?: $row->label];
        }
    }
    return $opts;
};
$typeOptions     = $dictOptions('c_ticket_type');
$severityOptions = $dictOptions('c_ticket_severity');
$categoryOptions = $dictOptions('c_ticket_category');

// ---- Third party picker (companies). Capped at 200 to avoid huge dropdowns.
$socOptions = [['id' => 0, 'label' => '— ' . $langs->trans('NoneSelected') . ' —']];
$socRes = $db->query('SELECT rowid, nom FROM ' . MAIN_DB_PREFIX . "societe WHERE status = 1 AND entity IN (" . getEntity('societe') . ') ORDER BY nom LIMIT 200');
if ($socRes) {
    while ($row = $db->fetch_object($socRes)) {
        $socOptions[] = ['id' => (int) $row->rowid, 'label' => $row->nom];
    }
}
$linkedSociete = null;
if ((int) $object->fk_soc > 0) {
    $linkedSociete = new Societe($db);
    $linkedSociete->fetch((int) $object->fk_soc);
}

// ---- All non-Digirisk extrafields, rendered automatically.
// This auto-detects any extrafield added by another module (DigiQuali QcFrequency, EasyURL EasyUrlAllLink, etc.).
$extrafieldsObj = new ExtraFields($db);
$extrafieldsObj->fetch_name_optionals_label($object->table_element);
$digiriskExtrafieldKeys = [
    'digiriskdolibarr_ticket_lastname', 'digiriskdolibarr_ticket_firstname',
    'digiriskdolibarr_ticket_phone', 'digiriskdolibarr_ticket_service',
    'digiriskdolibarr_ticket_location', 'digiriskdolibarr_ticket_date',
    'digiriskdolibarr_condition_message',
];
$otherExtrafields = [];
foreach (($extrafieldsObj->attributes[$object->table_element]['label'] ?? []) as $key => $label) {
    if (in_array($key, $digiriskExtrafieldKeys, true)) {
        continue;
    }
    if (($extrafieldsObj->attributes[$object->table_element]['type'][$key] ?? '') === 'separate') {
        continue;
    }
    if (empty($extrafieldsObj->attributes[$object->table_element]['perms'][$key] ?? 1)) {
        continue;
    }
    $otherExtrafields[$key] = [
        'label' => $label,
        'type'  => $extrafieldsObj->attributes[$object->table_element]['type'][$key] ?? 'varchar',
        'value' => $extra[$key] ?? null,
    ];
}

// ---- Linked files (read-only list).
$uploadDir = $conf->ticket->multidir_output[$conf->entity ?? 1] . '/' . dol_sanitizeFileName($object->ref);
$linkedFiles = is_dir($uploadDir) ? dol_dir_list($uploadDir, 'files', 0) : [];

// ---- Recent events (actioncomm) attached to this ticket — last 10.
$recentEvents = [];
$evtSql = 'SELECT a.id, a.label, a.datep, a.fk_user_author, u.lastname, u.firstname'
    . ' FROM ' . MAIN_DB_PREFIX . 'actioncomm a LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON u.rowid = a.fk_user_author'
    . " WHERE a.fk_element = " . (int) $object->id . " AND a.elementtype = 'ticket'"
    . " AND a.entity IN (" . getEntity('agenda') . ')'
    . ' ORDER BY a.datep DESC LIMIT 10';
$evtRes = $db->query($evtSql);
if ($evtRes) {
    while ($row = $db->fetch_object($evtRes)) {
        $recentEvents[] = $row;
    }
}

// ---- User-saved layout (per-user, stored in llx_user_param as JSON).
// Schema v2: { "v": 2, "sections": { "<id>": { "visible": bool, "width": "half"|"full"|"span", "order": int } } }
// Body uses a single 4-column CSS grid with grid-auto-flow: dense:
//   - "half" = span 1 (= 25% body width)
//   - "full" = span 2 (= 50% body width — the historical "1 column")
//   - "span" = span 4 (= 100% body width)
// Default mimics the previous 2-col layout by interleaving left/right groups (full = half body).
$defaultLayout = [
    'v'        => 2,
    'sections' => [
        // Interleaved L/R for an alternating 2-col visual.
        'identification'    => ['visible' => true, 'width' => 'full', 'order' => 0],
        'classification'    => ['visible' => true, 'width' => 'full', 'order' => 1],
        'registres'         => ['visible' => true, 'width' => 'full', 'order' => 2],
        'accidents'         => ['visible' => true, 'width' => 'full', 'order' => 3],
        'condition_message' => ['visible' => true, 'width' => 'full', 'order' => 4],
        'linked_files'      => ['visible' => true, 'width' => 'full', 'order' => 5],
        'other_extras'      => ['visible' => true, 'width' => 'full', 'order' => 6],
        'events'            => ['visible' => true, 'width' => 'full', 'order' => 7],
        'initial_message'   => ['visible' => true, 'width' => 'full', 'order' => 8],
        'related'           => ['visible' => true, 'width' => 'full', 'order' => 9],
        'dates'             => ['visible' => true, 'width' => 'full', 'order' => 10],
    ],
];
$rawLayout  = $user->conf->DIGIRISK_TICKET_CARD_LAYOUT ?? '';
$userLayout = $defaultLayout;
if ($rawLayout) {
    $decoded = json_decode((string) $rawLayout, true);
    if (is_array($decoded) && isset($decoded['sections'])) {
        // Migration: old schema (v1) had a "column" field; we drop it but keep the rest.
        foreach ($decoded['sections'] as $id => $cfg) {
            if (isset($userLayout['sections'][$id]) && is_array($cfg)) {
                $merged = $userLayout['sections'][$id];
                if (isset($cfg['visible'])) {
                    $merged['visible'] = (bool) $cfg['visible'];
                }
                if (isset($cfg['width']) && in_array($cfg['width'], ['half', 'full', 'span'], true)) {
                    $merged['width'] = $cfg['width'];
                }
                if (isset($cfg['order'])) {
                    $merged['order'] = (int) $cfg['order'];
                }
                $userLayout['sections'][$id] = $merged;
            }
        }
    }
}
$layoutJson = json_encode($userLayout);

// ---- Related objects (fetchObjectLinked).
$object->fetchObjectLinked();
$relatedObjects = [];
foreach ($object->linkedObjects as $linkedType => $linkedSet) {
    foreach ($linkedSet as $linkedObj) {
        $relatedObjects[] = [
            'type'  => $linkedType,
            'ref'   => $linkedObj->ref ?? ('#' . ($linkedObj->id ?? '?')),
            'label' => $linkedObj->label ?? $linkedObj->title ?? '',
            'url'   => method_exists($linkedObj, 'getNomUrl') ? $linkedObj->getNomUrl(1) : '',
        ];
    }
}

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
/**
 * Render the layout-mode controls (drag handle, width presets, hide) — shown only when
 * the card is in edit mode. Each section calls this right after its title.
 */
$sectionControls = function (string $id) use ($langs): void {
    print '<div class="tac-section__controls" aria-label="' . dol_escape_htmltag($langs->trans('LayoutControls')) . '">';
    print '<span class="tac-section__drag" title="' . dol_escape_htmltag($langs->trans('Move')) . '"><i class="fas fa-grip-vertical"></i></span>';
    print '<button type="button" class="tac-section__width-btn" data-width="half" title="' . dol_escape_htmltag($langs->trans('LayoutWidthHalf')) . '"><i class="fas fa-compress-alt"></i></button>';
    print '<button type="button" class="tac-section__width-btn" data-width="full" title="' . dol_escape_htmltag($langs->trans('LayoutWidthFull')) . '"><i class="fas fa-square"></i></button>';
    print '<button type="button" class="tac-section__width-btn" data-width="span" title="' . dol_escape_htmltag($langs->trans('LayoutWidthSpan')) . '"><i class="fas fa-expand-alt"></i></button>';
    print '<button type="button" class="tac-section__hide-btn" title="' . dol_escape_htmltag($langs->trans('Hide')) . '"><i class="fas fa-eye-slash"></i></button>';
    print '<button type="button" class="tac-section__show-btn" title="' . dol_escape_htmltag($langs->trans('Show')) . '"><i class="fas fa-eye"></i></button>';
    print '</div>';
};

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

<div class="ticket-action-card tac-card"
     data-ticket-id="<?php print (int) $object->id; ?>"
     data-ajax-url="<?php print dol_escape_htmltag($ajaxUrl); ?>"
     data-layout="<?php print dol_escape_htmltag($layoutJson); ?>">
    <input type="hidden" name="token" value="<?php print newToken(); ?>">

    <!-- ====== HERO HEADER ====== -->
    <div class="tac-hero">
        <div class="tac-hero__top">
            <a href="<?php print dol_escape_htmltag($backUrl); ?>" class="tac-hero__back">
                <i class="fas fa-arrow-left"></i> <?php print $langs->trans('BackToList'); ?>
            </a>
            <span class="tac-hero__top-right">
                <button type="button" class="tac-hero__reset" data-layout-reset title="<?php print dol_escape_htmltag($langs->trans('LayoutResetTitle')); ?>">
                    <i class="fas fa-undo"></i> <?php print $langs->trans('LayoutReset'); ?>
                </button>
                <button type="button" class="tac-hero__customize" data-customize-toggle>
                    <i class="fas fa-th-large"></i> <span class="tac-hero__customize-label" data-edit-on-label="<?php print dol_escape_htmltag($langs->trans('LayoutDone')); ?>" data-edit-off-label="<?php print dol_escape_htmltag($langs->trans('LayoutCustomize')); ?>"><?php print $langs->trans('LayoutCustomize'); ?></span>
                </button>
                <a href="<?php print dol_escape_htmltag($fullCardUrl); ?>" class="tac-hero__full" title="<?php print dol_escape_htmltag($langs->trans('TicketActionOpenFull')); ?>">
                    <i class="fas fa-external-link-alt"></i> <?php print $langs->trans('TicketActionOpenFull'); ?>
                </a>
            </span>
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

    <!-- ====== BODY: flat 4-col grid (dense packing) ====== -->
    <!-- All sections are direct children. Width classes (--width-half / --width-full / --width-span)
         control how many sub-columns each section spans. -->
    <div class="tac-body">

            <!-- Section: Identification (native ticket fields) -->
            <section class="tac-section" data-section-id="identification">
                <h3 class="tac-section__title"><i class="fas fa-id-card"></i> <?php print $langs->trans('TicketActionCardIdentificationSection'); ?></h3>
                <?php $sectionControls('identification'); ?>
                <div class="tac-grid">
                    <!-- Tracking ID — readonly identifier -->
                    <div class="tac-field tac-field--readonly">
                        <div class="tac-field__label"><?php print $langs->trans('TicketTrackId'); ?></div>
                        <div class="tac-field__value"><code><?php print dol_escape_htmltag($object->track_id ?? ''); ?></code></div>
                    </div>

                    <!-- Type (select from c_ticket_type) -->
                    <?php
                    $typeDisplay = '';
                    foreach ($typeOptions as $opt) {
                        if ((string) $opt['id'] === (string) ($object->type_code ?? '')) {
                            $typeDisplay = dol_escape_htmltag($opt['label']);
                            break;
                        }
                    }
                    $renderField('type_code', 'select', 'Type', $object->type_code ?? '', $typeDisplay, ['options' => $typeOptions]);

                    // Severity (select from c_ticket_severity)
                    $severityDisplay = '';
                    foreach ($severityOptions as $opt) {
                        if ((string) $opt['id'] === (string) ($object->severity_code ?? '')) {
                            $severityDisplay = dol_escape_htmltag($opt['label']);
                            break;
                        }
                    }
                    $renderField('severity_code', 'select', 'Severity', $object->severity_code ?? '', $severityDisplay, ['options' => $severityOptions]);

                    // Category (select from c_ticket_category)
                    $categoryDisplay = '';
                    foreach ($categoryOptions as $opt) {
                        if ((string) $opt['id'] === (string) ($object->category_code ?? '')) {
                            $categoryDisplay = dol_escape_htmltag($opt['label']);
                            break;
                        }
                    }
                    $renderField('category_code', 'select', 'TicketCategory', $object->category_code ?? '', $categoryDisplay, ['options' => $categoryOptions]);

                    // Third party (select limited to 200 most-active companies; full picker stays on Dolibarr card)
                    $socDisplay = $linkedSociete ? $linkedSociete->getNomUrl(1) : '';
                    $renderField('fk_soc', 'select', 'ThirdParty', (int) $object->fk_soc, $socDisplay, ['options' => $socOptions]);
                    ?>
                </div>
            </section>

            <!-- Section: Informations registres (Digirisk extrafields) -->
            <section class="tac-section" data-section-id="registres">
                <h3 class="tac-section__title"><i class="fas fa-clipboard-list"></i> <?php print $langs->trans('TicketActionCardRegistresSection'); ?></h3>
                <?php $sectionControls('registres'); ?>
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
            <section class="tac-section" data-section-id="condition_message">
                <h3 class="tac-section__title"><i class="fas fa-file-signature"></i> <?php print $langs->trans('ConditionMessage'); ?></h3>
                <?php $sectionControls('condition_message'); ?>
                <div class="tac-grid tac-grid--single">
                    <?php
                    $condition = $extra['digiriskdolibarr_condition_message'] ?? '';
                    $renderField('options_digiriskdolibarr_condition_message', 'longtext', 'ConditionMessage',
                        $condition,
                        $condition !== '' ? dolPrintHTML($condition) : '');
                    ?>
                </div>
            </section>

            <!-- Section: Other extrafields (auto-detected from non-Digirisk modules) -->
            <?php if (!empty($otherExtrafields)) : ?>
            <section class="tac-section" data-section-id="other_extras">
                <h3 class="tac-section__title"><i class="fas fa-puzzle-piece"></i> <?php print $langs->trans('TicketActionCardOtherFieldsSection'); ?></h3>
                <?php $sectionControls('other_extras'); ?>
                <div class="tac-grid">
                    <?php foreach ($otherExtrafields as $key => $info) :
                        $val = $info['value'];
                        $type = $info['type'];
                        // Map Dolibarr extrafield types to our tap-to-edit ui types.
                        $uiType = 'text';
                        if (in_array($type, ['int', 'double', 'price'], true)) {
                            $uiType = 'number';
                        } elseif ($type === 'text' || $type === 'html') {
                            $uiType = 'longtext';
                        } elseif ($type === 'date' || $type === 'datetime') {
                            $uiType = 'date';
                        } elseif (in_array($type, ['boolean', 'checkbox'], true)) {
                            $uiType = 'select';
                        }
                        // Build display value.
                        $display = '';
                        if ($val !== null && $val !== '') {
                            if ($uiType === 'date') {
                                $display = dol_print_date(is_numeric($val) ? (int) $val : strtotime((string) $val), 'day');
                            } elseif ($uiType === 'longtext') {
                                $display = dolPrintHTML((string) $val);
                            } else {
                                $display = dol_escape_htmltag((string) $val);
                            }
                        }
                        $opts = [];
                        if ($uiType === 'select') {
                            $opts['options'] = [
                                ['id' => '0', 'label' => $langs->trans('No')],
                                ['id' => '1', 'label' => $langs->trans('Yes')],
                            ];
                        }
                        $renderField('options_' . $key, $uiType, $info['label'], $val, $display, $opts);
                    endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Section: Initial message — display only, edit through Dolibarr card. -->
            <!-- (The next group was the old RIGHT column; everything is now flat in the body grid.) -->
            <section class="tac-section" data-section-id="initial_message">
                <h3 class="tac-section__title"><i class="fas fa-envelope-open-text"></i> <?php print $langs->trans('InitialMessage'); ?></h3>
                <?php $sectionControls('initial_message'); ?>
                <div class="tac-rich-text">
                    <?php print $object->message ? dolPrintHTML($object->message) : '<span class="opacitymedium">—</span>'; ?>
                </div>
                <a class="tac-section__edit-link" href="<?php print dol_escape_htmltag($fullCardUrl); ?>&action=edit_message_init">
                    <i class="fas fa-pen"></i> <?php print $langs->trans('Modify'); ?>
                </a>
            </section>

            <!-- Section: Classification (tags) -->
            <section class="tac-section" data-section-id="classification">
                <h3 class="tac-section__title"><i class="fas fa-tags"></i> <?php print $langs->trans('TicketActionCardClassificationSection'); ?></h3>
                <?php $sectionControls('classification'); ?>
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
            <section class="tac-section" data-section-id="accidents">
                <h3 class="tac-section__title"><i class="fas fa-exclamation-triangle"></i> <?php print $langs->trans('AccidentsLinked'); ?></h3>
                <?php $sectionControls('accidents'); ?>
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

            <!-- Section: Linked files (read-only list + upload link) -->
            <section class="tac-section" data-section-id="linked_files">
                <h3 class="tac-section__title"><i class="fas fa-paperclip"></i> <?php print $langs->trans('LinkedFiles'); ?></h3>
                <?php $sectionControls('linked_files'); ?>
                <ul class="tac-list">
                    <?php foreach ($linkedFiles as $f) :
                        $name = $f['name'] ?? '';
                        $size = isset($f['size']) ? dol_print_size((int) $f['size']) : '';
                        $href = DOL_URL_ROOT . '/document.php?modulepart=ticket&file=' . urlencode(dol_sanitizeFileName($object->ref) . '/' . $name);
                        ?>
                        <li class="tac-list__item">
                            <a href="<?php print dol_escape_htmltag($href); ?>" target="_blank">
                                <i class="fas fa-file"></i> <?php print dol_escape_htmltag($name); ?>
                            </a>
                            <span class="opacitymedium"><?php print $size; ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($linkedFiles)) : ?>
                        <li class="tac-list__item tac-list__item--empty">—</li>
                    <?php endif; ?>
                </ul>
                <a class="tac-section__edit-link" href="<?php print DOL_URL_ROOT . '/ticket/document.php?id=' . (int) $object->id; ?>">
                    <i class="fas fa-upload"></i> <?php print $langs->trans('Upload'); ?>
                </a>
            </section>

            <!-- Section: Recent events (read-only, last 10) -->
            <section class="tac-section" data-section-id="events">
                <h3 class="tac-section__title"><i class="fas fa-history"></i> <?php print $langs->trans('TicketActionCardEventsSection'); ?></h3>
                <?php $sectionControls('events'); ?>
                <ul class="tac-list tac-list--events">
                    <?php foreach ($recentEvents as $evt) :
                        $userName = trim(($evt->firstname ?: '') . ' ' . ($evt->lastname ?: ''));
                        ?>
                        <li class="tac-list__item">
                            <div class="tac-list__item-meta">
                                <?php print dol_print_date($db->jdate($evt->datep), 'dayhour'); ?>
                                <?php if ($userName) : ?> · <?php print dol_escape_htmltag($userName); ?><?php endif; ?>
                            </div>
                            <div><?php print dol_escape_htmltag(dol_trunc((string) $evt->label, 80)); ?></div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($recentEvents)) : ?>
                        <li class="tac-list__item tac-list__item--empty">—</li>
                    <?php endif; ?>
                </ul>
                <a class="tac-section__edit-link" href="<?php print DOL_URL_ROOT . '/ticket/agenda.php?id=' . (int) $object->id; ?>">
                    <i class="fas fa-external-link-alt"></i> <?php print $langs->trans('SeeAll'); ?>
                </a>
            </section>

            <!-- Section: Related objects (fetchObjectLinked) -->
            <?php if (!empty($relatedObjects)) : ?>
            <section class="tac-section" data-section-id="related">
                <h3 class="tac-section__title"><i class="fas fa-link"></i> <?php print $langs->trans('TicketActionCardRelatedSection'); ?></h3>
                <?php $sectionControls('related'); ?>
                <ul class="tac-list">
                    <?php foreach ($relatedObjects as $rel) : ?>
                        <li class="tac-list__item">
                            <?php print $rel['url'] ?: (dol_escape_htmltag($rel['type']) . ' · ' . dol_escape_htmltag($rel['ref'])); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- Section: Key dates (read-only) -->
            <section class="tac-section" data-section-id="dates">
                <h3 class="tac-section__title"><i class="fas fa-calendar"></i> <?php print $langs->trans('TicketActionCardDatesSection'); ?></h3>
                <?php $sectionControls('dates'); ?>
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

    <!-- ====== STICKY ACTION BAR ====== -->
    <div class="tac-sticky-bar">
        <!-- Quick-creation actions: link out to Dolibarr screens with the ticket pre-linked. -->
        <a class="tac-sticky-bar__btn tac-sticky-bar__btn--neutral" href="<?php print DOL_URL_ROOT . '/ticket/agenda.php?action=presend&mode=init&id=' . (int) $object->id; ?>">
            <i class="fas fa-envelope"></i> <?php print $langs->trans('SendMail'); ?>
        </a>
        <a class="tac-sticky-bar__btn tac-sticky-bar__btn--neutral" href="<?php print DOL_URL_ROOT . '/ticket/messaging.php?action=presend&id=' . (int) $object->id; ?>">
            <i class="fas fa-comment-dots"></i> <?php print $langs->trans('TicketAddMessage'); ?>
        </a>
        <a class="tac-sticky-bar__btn tac-sticky-bar__btn--neutral" href="<?php print dol_buildpath('/custom/digiriskdolibarr/view/accident/accident_card.php?action=create&fk_ticket=' . (int) $object->id, 1); ?>">
            <i class="fas fa-exclamation-triangle"></i> <?php print $langs->trans('NewAccident'); ?>
        </a>
        <?php if (isModEnabled('intervention')) : ?>
        <a class="tac-sticky-bar__btn tac-sticky-bar__btn--neutral" href="<?php print DOL_URL_ROOT . '/fichinter/card.php?action=create&origin=ticket&originid=' . (int) $object->id; ?>">
            <i class="fas fa-wrench"></i> <?php print $langs->trans('CreateIntervention'); ?>
        </a>
        <?php endif; ?>

        <!-- Spacer pushes destructive group to the right. -->
        <span class="tac-sticky-bar__spacer"></span>

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
