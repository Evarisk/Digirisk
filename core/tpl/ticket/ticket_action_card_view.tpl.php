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
// All option labels use transnoentities() because they end up serialized to JSON and rendered by
// jQuery .text() — which would print "&eacute;" literally if trans() returned HTML-encoded text.
$userPickerOptions = [['id' => 0, 'label' => $langs->transnoentities('NotAssigned')]];
$tmpUserSql = 'SELECT rowid, login, lastname, firstname FROM ' . MAIN_DB_PREFIX . 'user WHERE statut = 1 AND entity IN (' . getEntity('user') . ') ORDER BY lastname, firstname';
$tmpRes = $db->query($tmpUserSql);
if ($tmpRes) {
    while ($row = $db->fetch_object($tmpRes)) {
        $label = trim(($row->lastname ?: '') . ' ' . ($row->firstname ?: '')) ?: $row->login;
        $userPickerOptions[] = ['id' => (int) $row->rowid, 'label' => $label];
    }
}

// Ticket status options for the status tap-to-edit. transnoentities() because they go through JSON → JS .text().
$statusOptions = [
    Ticket::STATUS_NOT_READ       => $langs->transnoentities('Unread'),
    Ticket::STATUS_READ           => $langs->transnoentities('Read'),
    Ticket::STATUS_ASSIGNED       => $langs->transnoentities('Assigned'),
    Ticket::STATUS_IN_PROGRESS    => $langs->transnoentities('InProgress'),
    Ticket::STATUS_NEED_MORE_INFO => $langs->transnoentities('NeedMoreInformationShort'),
    Ticket::STATUS_WAITING        => $langs->transnoentities('OnHold'),
    Ticket::STATUS_CLOSED         => $langs->transnoentities('SolvedClosed'),
    Ticket::STATUS_CANCELED       => $langs->transnoentities('Canceled'),
];

// Load all categories for this ticket (Classification section).
$catObj          = new Categorie($db);
$ticketCategories = $catObj->containing($object->id, Categorie::TYPE_TICKET);
if (!is_array($ticketCategories)) {
    $ticketCategories = [];
}
// All available ticket categories — used to render the "+ Tag" 1-click chips for unassigned ones.
$allTicketCategories = $catObj->get_full_arbo(Categorie::TYPE_TICKET);
if (!is_array($allTicketCategories)) {
    $allTicketCategories = [];
}
// Quick lookup of category IDs already on this ticket.
$assignedCategoryIds = array_map(static fn($c) => (int) $c->id, $ticketCategories);

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

// Resolve all extrafield values up-front so the TPL stays read-only.
// $object->array_options keys come in prefixed with "options_" — strip that so the
// rest of the TPL can do $extra['my_field'] without repeating the prefix everywhere.
$extra = [];
foreach (($object->array_options ?? []) as $rawKey => $val) {
    $cleanKey = (strpos($rawKey, 'options_') === 0) ? substr($rawKey, strlen('options_')) : $rawKey;
    $extra[$cleanKey] = $val;
}

// ---- Dictionary options for type / severity / category (native ticket fields).
$dictOptions = function (string $table, ?string $code = null) use ($db, $langs): array {
    $opts = [['id' => '', 'label' => '— ' . $langs->transnoentities('NoneSelected') . ' —']];
    $sql = 'SELECT code, label, active FROM ' . MAIN_DB_PREFIX . $db->escape($table)
        . ' WHERE active = 1 AND entity IN (' . getEntity($table) . ') ORDER BY label';
    $r = $db->query($sql);
    if ($r) {
        while ($row = $db->fetch_object($r)) {
            $opts[] = ['id' => $row->code, 'label' => $langs->transnoentities($row->label) ?: $row->label];
        }
    }
    return $opts;
};
$typeOptions     = $dictOptions('c_ticket_type');
$severityOptions = $dictOptions('c_ticket_severity');
$categoryOptions = $dictOptions('c_ticket_category');

// ---- Third party picker (companies). Capped at 200 to avoid huge dropdowns.
$socOptions = [['id' => 0, 'label' => '— ' . $langs->transnoentities('NoneSelected') . ' —']];
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
// Extrafields that duplicate dedicated card sections and therefore shouldn't show in "Autres infos".
$duplicateExtrafieldKeys = [
    'ticket_categories', // covered by the Classification section
];
$otherExtrafields = [];
foreach (($extrafieldsObj->attributes[$object->table_element]['label'] ?? []) as $key => $label) {
    if (in_array($key, $digiriskExtrafieldKeys, true)) {
        continue;
    }
    if (in_array($key, $duplicateExtrafieldKeys, true)) {
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
// mode=1 makes dol_dir_list() load each entry's size + date + perm. With mode=0
// (the default) those fields come back empty and the UI would just show the name.
$uploadDir = $conf->ticket->multidir_output[$conf->entity ?? 1] . '/' . dol_sanitizeFileName($object->ref);
$linkedFiles = is_dir($uploadDir) ? dol_dir_list($uploadDir, 'files', 0, '', null, 'date', SORT_DESC, 1) : [];

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
    'v'           => 2,
    'density'     => 'cozy',  // compact | cozy | spacious — drives padding/gap/font scale on .tac-card
    'tagsMode'    => 'chips', // chips | selector — Classification section UI for adding tags
    'actionsMode' => 'bar',   // bar | menu — sticky-bar buttons or single kebab (⋮) overflow menu
    'sections'    => [
        // Order by relevance for ticket reading flow:
        //   1. Identification — who/what/where (ref, type, severity, third party)
        //   2. Initial message — the actual user request, read FIRST after the headline
        //   3. Registres — Digirisk-specific extrafields (Nom, Prénom, Phone, Lieu...)
        //   4. Classification — tags help triage
        //   5. Condition message + Accidents — workflow context for SST registers
        //   6. Other extrafields (3rd party modules: Tâche, QcFrequency, Deadline...)
        //   7. Reference data (linked files, events history, related objects, dates)
        'identification'    => ['visible' => true, 'width' => 'full', 'order' => 0],
        'initial_message'   => ['visible' => true, 'width' => 'full', 'order' => 1],
        'registres'         => ['visible' => true, 'width' => 'full', 'order' => 2],
        'classification'    => ['visible' => true, 'width' => 'full', 'order' => 3],
        'condition_message' => ['visible' => true, 'width' => 'full', 'order' => 4],
        'accidents'         => ['visible' => true, 'width' => 'full', 'order' => 5],
        'other_extras'      => ['visible' => true, 'width' => 'full', 'order' => 6],
        'linked_files'      => ['visible' => true, 'width' => 'full', 'order' => 7],
        'events'            => ['visible' => true, 'width' => 'full', 'order' => 8],
        'related'           => ['visible' => true, 'width' => 'full', 'order' => 9],
        'dates'             => ['visible' => true, 'width' => 'full', 'order' => 10],
    ],
];
$rawLayout  = $user->conf->DIGIRISK_TICKET_CARD_LAYOUT ?? '';
$userLayout = $defaultLayout;
if ($rawLayout) {
    $decoded = json_decode((string) $rawLayout, true);
    if (is_array($decoded)) {
        // density override (independent of sections)
        if (isset($decoded['density']) && in_array($decoded['density'], ['compact', 'cozy', 'spacious'], true)) {
            $userLayout['density'] = $decoded['density'];
        }
        if (isset($decoded['tagsMode']) && in_array($decoded['tagsMode'], ['chips', 'selector'], true)) {
            $userLayout['tagsMode'] = $decoded['tagsMode'];
        }
        if (isset($decoded['actionsMode']) && in_array($decoded['actionsMode'], ['bar', 'menu'], true)) {
            $userLayout['actionsMode'] = $decoded['actionsMode'];
        }
        if (isset($decoded['sections'])) {
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

<div class="ticket-action-card tac-card tac-density-<?php print dol_escape_htmltag($userLayout['density']); ?>"
     data-ticket-id="<?php print (int) $object->id; ?>"
     data-ajax-url="<?php print dol_escape_htmltag($ajaxUrl); ?>"
     data-layout="<?php print dol_escape_htmltag($layoutJson); ?>"
     data-density="<?php print dol_escape_htmltag($userLayout['density']); ?>"
     data-tags-mode="<?php print dol_escape_htmltag($userLayout['tagsMode']); ?>"
     data-actions-mode="<?php print dol_escape_htmltag($userLayout['actionsMode']); ?>">
    <input type="hidden" name="token" value="<?php print newToken(); ?>">

    <!-- ====== HERO HEADER ====== -->
    <div class="tac-hero">
        <div class="tac-hero__top">
            <a href="<?php print dol_escape_htmltag($backUrl); ?>" class="tac-hero__back">
                <i class="fas fa-arrow-left"></i> <?php print $langs->trans('BackToList'); ?>
            </a>
            <span class="tac-hero__top-right">
                <span class="tac-hero__density" role="toolbar" aria-label="<?php print dol_escape_htmltag($langs->trans('LayoutDensity')); ?>">
                    <button type="button" class="tac-hero__density-btn<?php print $userLayout['density'] === 'compact' ? ' is-active' : ''; ?>" data-density="compact" title="<?php print dol_escape_htmltag($langs->trans('LayoutDensityCompact')); ?>">
                        <i class="fas fa-compress-alt"></i>
                    </button>
                    <button type="button" class="tac-hero__density-btn<?php print $userLayout['density'] === 'cozy' ? ' is-active' : ''; ?>" data-density="cozy" title="<?php print dol_escape_htmltag($langs->trans('LayoutDensityCozy')); ?>">
                        <i class="fas fa-equals"></i>
                    </button>
                    <button type="button" class="tac-hero__density-btn<?php print $userLayout['density'] === 'spacious' ? ' is-active' : ''; ?>" data-density="spacious" title="<?php print dol_escape_htmltag($langs->trans('LayoutDensitySpacious')); ?>">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                </span>
                <span class="tac-hero__tagsmode" role="toolbar" aria-label="<?php print dol_escape_htmltag($langs->trans('LayoutTagsMode')); ?>">
                    <button type="button" class="tac-hero__tagsmode-btn<?php print $userLayout['tagsMode'] === 'chips' ? ' is-active' : ''; ?>" data-tagsmode="chips" title="<?php print dol_escape_htmltag($langs->trans('LayoutTagsModeChips')); ?>">
                        <i class="fas fa-tags"></i>
                    </button>
                    <button type="button" class="tac-hero__tagsmode-btn<?php print $userLayout['tagsMode'] === 'selector' ? ' is-active' : ''; ?>" data-tagsmode="selector" title="<?php print dol_escape_htmltag($langs->trans('LayoutTagsModeSelector')); ?>">
                        <i class="fas fa-caret-square-down"></i>
                    </button>
                </span>
                <span class="tac-hero__actionsmode" role="toolbar" aria-label="<?php print dol_escape_htmltag($langs->trans('LayoutActionsMode')); ?>">
                    <button type="button" class="tac-hero__actionsmode-btn<?php print $userLayout['actionsMode'] === 'bar' ? ' is-active' : ''; ?>" data-actionsmode="bar" title="<?php print dol_escape_htmltag($langs->trans('LayoutActionsModeBar')); ?>">
                        <i class="fas fa-grip-horizontal"></i>
                    </button>
                    <button type="button" class="tac-hero__actionsmode-btn<?php print $userLayout['actionsMode'] === 'menu' ? ' is-active' : ''; ?>" data-actionsmode="menu" title="<?php print dol_escape_htmltag($langs->trans('LayoutActionsModeMenu')); ?>">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </span>
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
                                ['id' => '0', 'label' => $langs->transnoentities('No')],
                                ['id' => '1', 'label' => $langs->transnoentities('Yes')],
                            ];
                        }
                        $renderField('options_' . $key, $uiType, $info['label'], $val, $display, $opts);
                    endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Section: Initial message — tap-to-edit rich-text (CKEditor) on the native ticket.message column. -->
            <!-- (The next group was the old RIGHT column; everything is now flat in the body grid.) -->
            <section class="tac-section" data-section-id="initial_message">
                <h3 class="tac-section__title"><i class="fas fa-envelope-open-text"></i> <?php print $langs->trans('InitialMessage'); ?></h3>
                <?php $sectionControls('initial_message'); ?>
                <div class="tac-grid tac-grid--single">
                    <?php $renderField('message', 'longtext', 'InitialMessage',
                        $object->message ?? '',
                        !empty($object->message) ? dolPrintHTML($object->message) : ''); ?>
                </div>
            </section>

            <!-- Section: Classification (tags) — 1-click add/remove. -->
            <section class="tac-section" data-section-id="classification">
                <h3 class="tac-section__title"><i class="fas fa-tags"></i> <?php print $langs->trans('TicketActionCardClassificationSection'); ?></h3>
                <?php $sectionControls('classification'); ?>
                <div class="tac-tags" data-tags-container>
                    <?php foreach ($ticketCategories as $cat) :
                        $catColor = !empty($cat->color) ? '#' . dol_escape_htmltag($cat->color) : '#e5e7eb';
                        ?>
                        <span class="tac-tag tac-tag--assigned" style="background:<?php print $catColor; ?>;" data-category-id="<?php print (int) $cat->id; ?>">
                            <?php print dol_escape_htmltag($cat->label); ?>
                            <button type="button" class="tac-tag__remove" title="<?php print dol_escape_htmltag($langs->trans('Remove')); ?>" data-tag-remove>×</button>
                        </span>
                    <?php endforeach; ?>
                    <?php if (empty($ticketCategories)) : ?>
                        <span class="tac-tag tac-tag--empty"><?php print $langs->trans('NoneSelected'); ?></span>
                    <?php endif; ?>
                </div>
                <?php
                // Compute the unassigned pool — used by both chips mode and selector mode.
                $unassigned = [];
                foreach ($allTicketCategories as $cat) {
                    $cid = (int) ($cat['rowid'] ?? 0);
                    if ($cid > 0 && !in_array($cid, $assignedCategoryIds, true)) {
                        $unassigned[] = $cat;
                    }
                }
                if (!empty($unassigned)) :
                    if ($userLayout['tagsMode'] === 'selector') : ?>
                        <!-- Selector mode — single dropdown, light footprint when there are many categories. -->
                        <div class="tac-tags-selector" data-tags-selector>
                            <select class="tac-tags-selector__input" data-tag-add-select>
                                <option value="">— <?php print $langs->trans('AddTag'); ?> —</option>
                                <?php foreach ($unassigned as $cat) : ?>
                                    <option value="<?php print (int) $cat['rowid']; ?>"><?php print dol_escape_htmltag($cat['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else : ?>
                        <!-- Chips mode — every unassigned tag visible as a button. -->
                        <div class="tac-tags-pool" data-tags-pool>
                            <span class="tac-tags-pool__label"><i class="fas fa-plus"></i> <?php print $langs->trans('AddTag'); ?></span>
                            <?php foreach ($unassigned as $cat) :
                                $catColor = !empty($cat['color']) ? '#' . dol_escape_htmltag($cat['color']) : '';
                                $bgStyle  = $catColor ? 'style="border-color:' . $catColor . ';"' : '';
                                ?>
                                <button type="button" class="tac-tag tac-tag--available" data-tag-add data-category-id="<?php print (int) $cat['rowid']; ?>" <?php print $bgStyle; ?>>
                                    + <?php print dol_escape_htmltag($cat['label']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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

            <!-- Section: Linked files — compact list with preview / delete actions. -->
            <section class="tac-section" data-section-id="linked_files">
                <h3 class="tac-section__title"><i class="fas fa-paperclip"></i> <?php print $langs->trans('LinkedFiles'); ?></h3>
                <?php $sectionControls('linked_files'); ?>
                <?php
                /** Map a file extension to a Font Awesome icon class. Falls back to fa-file. */
                $iconForExt = static function (string $ext): string {
                    $ext = strtolower($ext);
                    if (in_array($ext, ['pdf'], true))                                           return 'fas fa-file-pdf';
                    if (in_array($ext, ['doc', 'docx', 'odt', 'rtf'], true))                     return 'fas fa-file-word';
                    if (in_array($ext, ['xls', 'xlsx', 'ods', 'csv'], true))                     return 'fas fa-file-excel';
                    if (in_array($ext, ['ppt', 'pptx', 'odp'], true))                            return 'fas fa-file-powerpoint';
                    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'], true))                 return 'fas fa-file-archive';
                    if (in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'm4a'], true))              return 'fas fa-file-audio';
                    if (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'], true))              return 'fas fa-file-video';
                    if (in_array($ext, ['txt', 'md', 'log'], true))                              return 'fas fa-file-alt';
                    if (in_array($ext, ['html', 'htm', 'php', 'js', 'css', 'json', 'xml'], true)) return 'fas fa-file-code';
                    return 'fas fa-file';
                };
                ?>
                <?php if (!empty($linkedFiles)) : ?>
                <ul class="tac-files-list">
                    <?php foreach ($linkedFiles as $f) :
                        $name = (string) ($f['name'] ?? '');
                        if ($name === '' || $name[0] === '.') {
                            continue;
                        }
                        $sizeRaw = (int) ($f['size'] ?? 0);
                        $size    = $sizeRaw ? dol_print_size($sizeRaw) : '';
                        $dateRaw = (int) ($f['date'] ?? 0);
                        $relPath = dol_sanitizeFileName($object->ref) . '/' . $name;
                        // Plain download URL (used by the filename link → opens with native dialog).
                        $href    = DOL_URL_ROOT . '/document.php?modulepart=ticket&file=' . urlencode($relPath);
                        // Inline URL for the lightbox: attachment=0 makes document.php send
                        // Content-Disposition: inline so <img>/<iframe> can render the bytes.
                        $hrefInline = $href . '&attachment=0';
                        $ext     = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'], true);
                        $isPdf   = ($ext === 'pdf');
                        $previewable = $isImage || $isPdf;
                        ?>
                        <li class="tac-files-list__item" data-file-name="<?php print dol_escape_htmltag($name); ?>">
                            <span class="tac-files-list__icon">
                                <i class="<?php print dol_escape_htmltag($iconForExt($ext)); ?>"></i>
                                <?php if ($ext) : ?><span class="tac-files-list__ext"><?php print dol_escape_htmltag(strtoupper($ext)); ?></span><?php endif; ?>
                            </span>
                            <a class="tac-files-list__name" href="<?php print dol_escape_htmltag($href); ?>" target="_blank" title="<?php print dol_escape_htmltag($name); ?>">
                                <?php print dol_escape_htmltag($name); ?>
                            </a>
                            <span class="tac-files-list__meta">
                                <?php if ($size) : ?><span class="tac-files-list__size"><?php print $size; ?></span><?php endif; ?>
                                <?php if ($dateRaw) : ?><span class="tac-files-list__date"><?php print dol_print_date($dateRaw, 'day'); ?></span><?php endif; ?>
                            </span>
                            <span class="tac-files-list__actions">
                                <?php if ($previewable) : ?>
                                    <button type="button" class="tac-files-list__btn tac-files-list__btn--preview"
                                            data-file-preview
                                            data-file-href="<?php print dol_escape_htmltag($hrefInline); ?>"
                                            data-file-kind="<?php print $isImage ? 'image' : 'pdf'; ?>"
                                            data-file-name="<?php print dol_escape_htmltag($name); ?>"
                                            title="<?php print dol_escape_htmltag($langs->trans('Preview')); ?>">
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="tac-files-list__btn tac-files-list__btn--delete"
                                        data-file-delete
                                        data-file-name="<?php print dol_escape_htmltag($name); ?>"
                                        title="<?php print dol_escape_htmltag($langs->trans('Delete')); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                    <div class="tac-files-list__empty">—</div>
                <?php endif; ?>
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

    <!-- ====== STICKY ACTION BAR (bar mode) OR KEBAB MENU (menu mode) ====== -->
    <?php
    // Pre-compute the button list once. Each item: ['kind'=>'link'|'button', html-attrs..., 'label', 'icon', 'variant'].
    $sendMailUrl   = DOL_URL_ROOT . '/ticket/agenda.php?action=presend&mode=init&id=' . (int) $object->id;
    $addMsgUrl     = DOL_URL_ROOT . '/ticket/messaging.php?action=presend&id=' . (int) $object->id;
    $newAccUrl     = dol_buildpath('/custom/digiriskdolibarr/view/accident/accident_card.php?action=create&fk_ticket=' . (int) $object->id, 1);
    $newInterUrl   = DOL_URL_ROOT . '/fichinter/card.php?action=create&origin=ticket&originid=' . (int) $object->id;

    $actionItems = [
        ['kind' => 'link',   'href' => $sendMailUrl, 'icon' => 'fas fa-envelope',           'label' => $langs->trans('SendMail'),          'variant' => 'neutral', 'enabled' => true],
        ['kind' => 'link',   'href' => $addMsgUrl,   'icon' => 'fas fa-comment-dots',       'label' => $langs->trans('TicketAddMessage'),  'variant' => 'neutral', 'enabled' => true],
        ['kind' => 'link',   'href' => $newAccUrl,   'icon' => 'fas fa-exclamation-triangle','label' => $langs->trans('NewAccident'),      'variant' => 'neutral', 'enabled' => true],
    ];
    if (isModEnabled('intervention')) {
        // Use Dolibarr core "AddIntervention" lang key (Créer intervention) — it's already shipped, no need for our own.
        $langs->load('interventions');
        $actionItems[] = ['kind' => 'link', 'href' => $newInterUrl, 'icon' => 'fas fa-wrench', 'label' => $langs->trans('AddIntervention'), 'variant' => 'neutral', 'enabled' => true];
    }
    // Spacer + destructive group (set_waiting / set_closed / set_cancelled).
    $actionItems[] = ['kind' => 'spacer'];
    $actionItems[] = ['kind' => 'button', 'action' => 'set_waiting',  'icon' => 'fas fa-pause-circle',  'label' => $langs->trans('TicketActionWaiting'), 'variant' => 'orange', 'enabled' => !$isClosed && $status !== Ticket::STATUS_WAITING];
    $actionItems[] = ['kind' => 'button', 'action' => 'set_closed',   'icon' => 'fas fa-check-circle',  'label' => $langs->trans('TicketActionClose'),   'variant' => 'green',  'enabled' => !$isClosed, 'confirm' => 'TicketActionCloseConfirm'];
    $actionItems[] = ['kind' => 'button', 'action' => 'set_cancelled','icon' => 'fas fa-times-circle',  'label' => $langs->trans('TicketActionCancel'),  'variant' => 'red',    'enabled' => !$isClosed, 'confirm' => 'TicketActionCancelConfirm'];

    if ($userLayout['actionsMode'] === 'menu') : ?>
        <!-- Menu mode: a single kebab (⋮) button reveals all actions in a popup. -->
        <div class="tac-sticky-bar tac-sticky-bar--menu">
            <span class="tac-sticky-bar__spacer"></span>
            <div class="tac-kebab" data-kebab>
                <button type="button" class="tac-kebab__trigger" data-kebab-toggle title="<?php print dol_escape_htmltag($langs->trans('LayoutActionsModeMenu')); ?>">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="tac-kebab__menu" data-kebab-menu>
                    <?php foreach ($actionItems as $item) :
                        if (($item['kind'] ?? '') === 'spacer') :
                            ?><hr class="tac-kebab__sep"><?php
                            continue;
                        endif;
                        $iconHtml = '<i class="' . dol_escape_htmltag($item['icon']) . '"></i>';
                        $cls = 'tac-kebab__item tac-kebab__item--' . dol_escape_htmltag($item['variant']);
                        if (empty($item['enabled'])) {
                            $cls .= ' is-disabled';
                        }
                        if ($item['kind'] === 'link') : ?>
                            <a class="<?php print $cls; ?>" href="<?php print dol_escape_htmltag($item['href']); ?>">
                                <?php print $iconHtml; ?> <?php print dol_escape_htmltag($item['label']); ?>
                            </a>
                        <?php else : ?>
                            <button type="button" class="<?php print $cls; ?> ticket-action-tile"
                                    data-action="<?php print dol_escape_htmltag($item['action']); ?>"
                                    <?php if (!empty($item['confirm'])) { print 'data-confirm="' . dol_escape_htmltag($item['confirm']) . '"'; } ?>
                                    <?php if (empty($item['enabled'])) { print 'disabled'; } ?>>
                                <?php print $iconHtml; ?> <?php print dol_escape_htmltag($item['label']); ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="tac-sticky-bar">
            <?php foreach ($actionItems as $item) :
                if (($item['kind'] ?? '') === 'spacer') :
                    ?><span class="tac-sticky-bar__spacer"></span><?php
                    continue;
                endif;
                $iconHtml = '<i class="' . dol_escape_htmltag($item['icon']) . '"></i>';
                $cls = 'tac-sticky-bar__btn tac-sticky-bar__btn--' . dol_escape_htmltag($item['variant']);
                if ($item['kind'] === 'link') : ?>
                    <a class="<?php print $cls; ?>" href="<?php print dol_escape_htmltag($item['href']); ?>">
                        <?php print $iconHtml; ?> <?php print dol_escape_htmltag($item['label']); ?>
                    </a>
                <?php else : ?>
                    <button type="button" class="<?php print $cls; ?> ticket-action-tile"
                            data-action="<?php print dol_escape_htmltag($item['action']); ?>"
                            <?php if (!empty($item['confirm'])) { print 'data-confirm="' . dol_escape_htmltag($item['confirm']) . '"'; } ?>
                            <?php if (empty($item['enabled'])) { print 'disabled'; } ?>>
                        <?php print $iconHtml; ?> <?php print dol_escape_htmltag($item['label']); ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Lightbox for file preview (hidden until JS injects the right src). -->
    <div class="tac-lightbox" data-lightbox aria-hidden="true">
        <div class="tac-lightbox__header">
            <span class="tac-lightbox__title" data-lightbox-title></span>
            <a class="tac-lightbox__open" data-lightbox-open target="_blank" rel="noopener" title="<?php print dol_escape_htmltag($langs->trans('OpenInNewTab')); ?>">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <button type="button" class="tac-lightbox__close" data-lightbox-close aria-label="Close">×</button>
        </div>
        <div class="tac-lightbox__content" data-lightbox-content></div>
    </div>

    <!-- Toast/feedback area populated by JS -->
    <div class="ticket-action-card__toast tac-toast" role="status" aria-live="polite"></div>
</div>
