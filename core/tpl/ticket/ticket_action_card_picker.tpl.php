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
 * \file    core/tpl/ticket/ticket_action_card_picker.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Kanban picker with 3 classification tabs (Type, Groupe, Sévérité).
 *          Each tab shows a drag-and-drop kanban whose columns correspond to
 *          the values in the respective ticket dictionary.
 *
 * Variables expected from ticket_action_card.php:
 *   $conf, $db, $langs, $user
 *   $pickerAllUsers           array  Active internal users
 *   $pickerAllCategories      array  All ticket tag categories
 *   $pickerTicketsJson        array  All ticket data (flat)
 *   $pickerTypeColumns        array  Column defs for Type dimension
 *   $pickerTypeTickets        array  Tickets grouped by type_code
 *   $pickerGroupColumns       array  Column defs for Group dimension
 *   $pickerGroupTickets       array  Tickets grouped by category_code
 *   $pickerSeverityColumns    array  Column defs for Severity dimension
 *   $pickerSeverityTickets    array  Tickets grouped by severity_code
 */

if (empty($conf) || empty($db) || empty($langs) || empty($user)) {
    print 'Error, missing parameters';
    exit;
}

/**
 * Render a single kanban board for one dimension.
 *
 * @param  array  $columns     Column definitions  [code => [label, icon, color]]
 * @param  array  $colTickets  Tickets per column  [code => [ticket data, ...]]
 * @param  string $dim         Dimension key: 'type', 'group', 'severity'
 * @param  array  $allUsers    All active users (for assignee select)
 * @param  array  $allCats     All tag categories
 * @param  array  $allSevCols  Severity column definitions (for inline severity select)
 * @param  object $langs       $langs global
 * @return void
 */
function renderDimKanban($columns, $colTickets, $dim, $allUsers, $allCats, $allSevCols, $langs)
{
    $sevColors = [
        'LOW'      => ['bg' => '#e9ecef', 'text' => '#495057'],
        'NORMAL'   => ['bg' => '#cfe2ff', 'text' => '#0a58ca'],
        'HIGH'     => ['bg' => '#fff3cd', 'text' => '#664d03'],
        'BLOCKING' => ['bg' => '#f8d7da', 'text' => '#842029'],
    ];

    echo '<div class="kanban-board tac-picker-kanban-board tac-picker-kanban-' . dol_escape_htmltag($dim) . '" '
        . 'data-dim="' . dol_escape_htmltag($dim) . '" '
        . 'data-token="' . newToken() . '">';

    foreach ($columns as $colCode => $colDef) {
        $colTicketList = $colTickets[$colCode] ?? [];
        $safeCode = dol_escape_htmltag((string) $colCode);
        $safeColor = dol_escape_htmltag($colDef['color']);
        ?>
        <div class="kanban-column tac-picker-column" data-dim-value="<?= $safeCode ?>">
            <div class="kanban-column-header" style="border-top: 3px solid <?= $safeColor ?>">
                <span class="kanban-column-icon"><i class="fas <?= dol_escape_htmltag($colDef['icon']) ?>"></i></span>
                <span class="kanban-column-title"><?= dol_escape_htmltag($colDef['label']) ?></span>
                <span class="kanban-column-count"><?= count($colTicketList) ?></span>
            </div>
            <div class="kanban-column-body kanban-picker-sortable" data-dim="<?= dol_escape_htmltag($dim) ?>" data-dim-value="<?= $safeCode ?>">
                <?php if (empty($colTicketList)) : ?>
                    <div class="kanban-empty"><?= $langs->trans('NoTickets') ?></div>
                <?php endif; ?>

                <?php foreach ($colTicketList as $t) :
                    $sevKey = strtoupper((string) $t['severity_code']);
                    $sevBg   = $sevColors[$sevKey]['bg']   ?? '';
                    $sevText = $sevColors[$sevKey]['text']  ?? '';
                ?>
                    <?php
                    $dimFieldMap   = ['type' => 'type_code', 'group' => 'category_code', 'severity' => 'severity_code', 'status' => 'status'];
                    $dimValueField = $dimFieldMap[$dim] ?? 'status';
                    ?>
                    <div class="kanban-card tac-picker-card"
                         data-ticket-id="<?= (int) $t['id'] ?>"
                         data-dim-value="<?= dol_escape_htmltag((string) $t[$dimValueField]) ?>">

                        <div class="kanban-card-header">
                            <a href="<?= dol_escape_htmltag($t['url']) ?>" class="kanban-card-ref"><?= dol_escape_htmltag($t['ref']) ?></a>

                            <?php if (!empty($t['severity_code']) && !empty($sevBg)) : ?>
                                <span class="tac-picker-card__severity tac-picker-sev-badge"
                                      style="background:<?= $sevBg ?>;color:<?= $sevText ?>"
                                      data-ticket-id="<?= (int) $t['id'] ?>"
                                      data-severity-code="<?= dol_escape_htmltag($t['severity_code']) ?>">
                                    <?= dol_escape_htmltag($t['severity_label'] ?? $t['severity_code']) ?>
                                </span>
                                <select class="tac-picker-sev-inline-select" data-ticket-id="<?= (int) $t['id'] ?>">
                                    <?php foreach ($allSevCols as $sevColCode => $sevColDef) :
                                        if ($sevColCode === '__none__') { continue; }
                                    ?>
                                        <option value="<?= dol_escape_htmltag($sevColCode) ?>" <?= ($t['severity_code'] === $sevColCode) ? 'selected' : '' ?>>
                                            <?= dol_escape_htmltag($sevColDef['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>

                            <?php if (!empty($t['company_name'])) : ?>
                                <span class="tac-picker-card__company" title="<?= dol_escape_htmltag($t['company_name']) ?>">
                                    <i class="fas fa-building"></i> <?= dol_escape_htmltag(dol_trunc($t['company_name'], 18)) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($t['date_c'])) : ?>
                                <span class="tac-picker-card__date">
                                    <i class="fas fa-calendar-alt"></i> <?= dol_escape_htmltag($t['date_c']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="kanban-card-label">
                            <span class="tac-picker-card__subject-text"
                                  contenteditable="true"
                                  data-ticket-id="<?= (int) $t['id'] ?>"><?= dol_escape_htmltag($t['subject']) ?></span>
                            <a href="<?= dol_escape_htmltag($t['url']) ?>" class="tac-picker-card__subject-link" title="<?= dol_escape_htmltag($langs->trans('OpenTicket') !== 'OpenTicket' ? $langs->trans('OpenTicket') : 'Ouvrir') ?>">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>

                        <div class="kanban-card-contacts">
                            <div class="kanban-responsible-wrapper" data-ticket-id="<?= (int) $t['id'] ?>" data-current-user="<?= (int) $t['assign_id'] ?>">
                                <?php
                                $hasAssign = $t['assign_id'] > 0;
                                ?>
                                <?php if (!empty($t['assign_photo'])) : ?>
                                    <img class="kanban-initial kanban-initial-responsible tac-picker-card__assignee-img"
                                         src="<?= dol_escape_htmltag($t['assign_photo']) ?>"
                                         alt="<?= dol_escape_htmltag($t['assign_initials']) ?>"
                                         title="<?= dol_escape_htmltag($t['assign_fullname']) ?>"
                                         style="background:<?= dol_escape_htmltag($colDef['color']) ?>">
                                <?php else : ?>
                                    <span class="kanban-initial kanban-initial-responsible<?= !$hasAssign ? ' kanban-initial-empty' : '' ?>"
                                          title="<?= dol_escape_htmltag($hasAssign ? $t['assign_fullname'] : $langs->trans('Unassigned')) ?>"
                                          style="background:<?= dol_escape_htmltag($colDef['color']) ?>">
                                        <?= dol_escape_htmltag($hasAssign ? $t['assign_initials'] : '?') ?>
                                    </span>
                                <?php endif; ?>

                                <select class="kanban-responsible-select tac-picker-assignee-select" data-ticket-id="<?= (int) $t['id'] ?>">
                                    <option value="0"><?= dol_escape_htmltag($langs->trans('Unassigned')) ?></option>
                                    <?php foreach ($allUsers as $u) : ?>
                                        <option value="<?= (int) $u['id'] ?>"
                                                data-initial="<?= dol_escape_htmltag($u['initials']) ?>"
                                                data-photo="<?= dol_escape_htmltag($u['photo']) ?>"
                                                <?= ($t['assign_id'] == $u['id']) ? 'selected' : '' ?>>
                                            <?= dol_escape_htmltag($u['fullname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="kanban-card-tags" data-ticket-id="<?= (int) $t['id'] ?>">
                            <?php foreach ($t['categories'] as $cat) :
                                $tagBg = !empty($cat['color']) ? '#' . dol_escape_htmltag($cat['color']) : '#8c8c8c';
                            ?>
                                <span class="kanban-tag" data-cat-id="<?= (int) $cat['id'] ?>" style="background:<?= $tagBg ?>">
                                    <?= dol_escape_htmltag($cat['label']) ?>
                                    <span class="kanban-tag-remove" title="<?= dol_escape_htmltag($langs->trans('Remove')) ?>">&times;</span>
                                </span>
                            <?php endforeach; ?>

                            <div class="kanban-tag-dropdown-wrapper">
                                <button class="kanban-add-tag-btn" title="<?= dol_escape_htmltag($langs->trans('AddCategory')) ?>">
                                    <i class="fas fa-tag"></i><i class="fas fa-plus" style="font-size:7px;margin-left:1px"></i>
                                </button>
                                <?php $assignedCatIds = array_column($t['categories'], 'id'); ?>
                                <div class="kanban-tag-dropdown" data-ticket-id="<?= (int) $t['id'] ?>">
                                    <?php foreach ($allCats as $ac) :
                                        $isAssigned = in_array($ac['id'], $assignedCatIds);
                                        $dotColor   = !empty($ac['color']) ? '#' . dol_escape_htmltag($ac['color']) : '#8c8c8c';
                                    ?>
                                        <div class="kanban-tag-option<?= $isAssigned ? ' assigned' : '' ?>"
                                             data-value="<?= (int) $ac['id'] ?>"
                                             data-color="<?= dol_escape_htmltag($ac['color']) ?>">
                                            <span class="kanban-tag-dot" style="background:<?= $dotColor ?>"></span>
                                            <?= dol_escape_htmltag($ac['label']) ?>
                                            <?php if ($isAssigned) : ?>
                                                <i class="fas fa-check" style="margin-left:auto;font-size:9px;color:#28a745"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    echo '</div>'; // .tac-picker-kanban-board
}
?>

<div class="tac-picker-wrapper">

    <!-- Settings popover (shared across all tabs) -->
    <div class="kanban-settings-wrapper">
        <button type="button" class="kanban-settings-btn" id="kanbanPickerSettingsBtn" title="<?= dol_escape_htmltag($langs->trans('Settings')) ?>">
            <i class="fas fa-cog"></i>
        </button>
        <div class="kanban-settings-popover" id="kanbanPickerSettingsPopover">
            <div class="ksp-row">
                <label><i class="fas fa-arrows-alt-h"></i> <?= $langs->trans('ColumnWidth') ?></label>
                <input type="range" id="kanbanPickerColWidth" min="220" max="500" value="300" step="10">
                <span class="ksp-val" id="kanbanPickerColWidthVal">300px</span>
            </div>
            <div class="ksp-row">
                <label><i class="fas fa-columns"></i> <?= $langs->trans('ColumnGap') ?></label>
                <input type="range" id="kanbanPickerColGap" min="8" max="50" value="16" step="2">
                <span class="ksp-val" id="kanbanPickerColGapVal">16px</span>
            </div>
        </div>
    </div>

    <!-- Quick-create button -->
    <?php if ($user->hasRight('ticket', 'write')) : ?>
    <div class="tac-picker-topbar">
        <button type="button" class="tac-picker-topbar__btn tac-quick-create-open" id="tacQuickCreateBtn">
            <i class="fas fa-plus"></i> <?= $langs->trans('NewTicket') ?>
        </button>
    </div>
    <?php endif; ?>


    <!-- Tab navigation -->
    <div class="tac-dim-tabs" id="tacDimTabs">
        <button class="tac-dim-tab active" data-dim="status">
            <i class="fas fa-list-ol"></i> <?= $langs->trans('Status') ?>
        </button>
        <button class="tac-dim-tab" data-dim="type">
            <i class="fas fa-tag"></i> <?= $langs->trans('Type') ?>
        </button>
        <button class="tac-dim-tab" data-dim="group">
            <i class="fas fa-folder"></i> <?= $langs->trans('TicketCategory') ?>
        </button>
        <button class="tac-dim-tab" data-dim="severity">
            <i class="fas fa-exclamation-triangle"></i> <?= $langs->trans('Severity') ?>
        </button>
    </div>

    <!-- Board panels -->
    <div class="tac-dim-panels" id="tacDimPanels">

        <div class="tac-dim-panel active" data-dim="status">
            <?php renderDimKanban($pickerStatusColumns, $pickerStatusTickets, 'status', $pickerAllUsers, $pickerAllCategories, $pickerSeverityColumns, $langs); ?>
        </div>

        <div class="tac-dim-panel" data-dim="type">
            <?php renderDimKanban($pickerTypeColumns, $pickerTypeTickets, 'type', $pickerAllUsers, $pickerAllCategories, $pickerSeverityColumns, $langs); ?>
        </div>

        <div class="tac-dim-panel" data-dim="group">
            <?php renderDimKanban($pickerGroupColumns, $pickerGroupTickets, 'group', $pickerAllUsers, $pickerAllCategories, $pickerSeverityColumns, $langs); ?>
        </div>

        <div class="tac-dim-panel" data-dim="severity">
            <?php renderDimKanban($pickerSeverityColumns, $pickerSeverityTickets, 'severity', $pickerAllUsers, $pickerAllCategories, $pickerSeverityColumns, $langs); ?>
        </div>

    </div>

</div>

<?php if ($user->hasRight('ticket', 'write')) : ?>
<!-- Quick-create drawer (slide-in from right) -->
<div class="tac-quick-create" id="tacQuickCreate" aria-hidden="true">
    <div class="tac-quick-create__overlay tac-quick-create-close"></div>
    <div class="tac-quick-create__drawer" role="dialog" aria-modal="true" aria-label="<?= dol_escape_htmltag($langs->trans('NewTicket')) ?>">
        <div class="tac-quick-create__header">
            <span class="tac-quick-create__title"><i class="fas fa-ticket-alt"></i> <?= $langs->trans('NewTicket') ?></span>
            <button type="button" class="tac-quick-create__close tac-quick-create-close" aria-label="<?= dol_escape_htmltag($langs->trans('Close')) ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form class="tac-quick-create__form" id="tacQuickCreateForm" novalidate>
            <input type="hidden" name="token" value="<?= newToken() ?>">

            <!-- Sujet -->
            <div class="tac-qc-field tac-qc-field--required">
                <label class="tac-qc-label" for="tacQcSubject">
                    <i class="fas fa-heading"></i> <?= $langs->trans('Subject') ?>
                </label>
                <input type="text" id="tacQcSubject" name="subject" class="tac-qc-input" required
                       placeholder="<?= dol_escape_htmltag($langs->trans('TicketSubjectPlaceholder') !== 'TicketSubjectPlaceholder' ? $langs->trans('TicketSubjectPlaceholder') : 'Titre du ticket...') ?>">
            </div>

            <!-- Type (pleine largeur) -->
            <div class="tac-qc-field">
                <label class="tac-qc-label" for="tacQcType">
                    <i class="fas fa-tag"></i> <?= $langs->trans('Type') ?>
                </label>
                <select id="tacQcType" name="type_code" class="tac-qc-select">
                    <option value=""><?= dol_escape_htmltag($langs->trans('SelectNothing')) ?></option>
                    <?php foreach ($createFormTypes as $code => $label) : ?>
                    <option value="<?= dol_escape_htmltag($code) ?>"><?= dol_escape_htmltag($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sévérité + Assigné (2 colonnes) -->
            <div class="tac-qc-row">
                <div class="tac-qc-field">
                    <label class="tac-qc-label" for="tacQcSeverity">
                        <i class="fas fa-exclamation-triangle"></i> <?= $langs->trans('Severity') ?>
                    </label>
                    <select id="tacQcSeverity" name="severity_code" class="tac-qc-select">
                        <option value=""><?= dol_escape_htmltag($langs->trans('SelectNothing')) ?></option>
                        <?php foreach ($createFormSeverities as $code => $label) : ?>
                        <option value="<?= dol_escape_htmltag($code) ?>"><?= dol_escape_htmltag($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tac-qc-field">
                    <label class="tac-qc-label" for="tacQcAssignee">
                        <i class="fas fa-user-check"></i> <?= $langs->trans('AssignedTo') ?>
                    </label>
                    <select id="tacQcAssignee" name="fk_user_assign" class="tac-qc-select">
                        <option value="0"><?= dol_escape_htmltag($langs->trans('Unassigned')) ?></option>
                        <?php foreach ($createFormUsers as $u) : ?>
                        <option value="<?= (int) $u['id'] ?>"><?= dol_escape_htmltag($u['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Message initial -->
            <div class="tac-qc-field">
                <label class="tac-qc-label" for="tacQcMessage">
                    <i class="fas fa-comment-alt"></i> <?= $langs->trans('TicketInitialMessage') ?>
                </label>
                <textarea id="tacQcMessage" name="message" class="tac-qc-textarea"
                          rows="4" placeholder="<?= dol_escape_htmltag($langs->trans('OptionalDescription')) ?>"></textarea>
            </div>

            <!-- Pièces jointes -->
            <div class="tac-qc-field">
                <label class="tac-qc-label">
                    <i class="fas fa-paperclip"></i> <?= $langs->trans('QuickCreateAttachments') ?>
                </label>
                <div class="tac-qc-dropzone" id="tacQcDropzone" tabindex="0" role="button"
                     aria-label="<?= dol_escape_htmltag($langs->trans('QuickCreateDropzone')) ?>">
                    <input type="file" id="tacQcFiles" name="attachments[]" multiple
                           class="tac-qc-file-input" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt,.zip">
                    <div class="tac-qc-dropzone__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="tac-qc-dropzone__label"><?= $langs->trans('QuickCreateDropzone') ?></div>
                    <div class="tac-qc-dropzone__hint"><?= $langs->trans('QuickCreateAttachmentHint') ?></div>
                </div>
                <ul class="tac-qc-file-list" id="tacQcFileList"></ul>
            </div>

            <!-- Toast interne au formulaire -->
            <div class="tac-qc-toast" id="tacQcToast" aria-live="polite"></div>

            <!-- Actions -->
            <div class="tac-qc-actions">
                <button type="button" class="tac-qc-btn tac-qc-btn--secondary tac-quick-create-close">
                    <?= $langs->trans('Cancel') ?>
                </button>
                <button type="submit" class="tac-qc-btn tac-qc-btn--primary" id="tacQcSubmit">
                    <i class="fas fa-plus-circle"></i> <?= $langs->trans('CreateTicket') ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
