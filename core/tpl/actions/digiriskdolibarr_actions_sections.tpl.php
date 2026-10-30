<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/actions/digiriskdolibarr_actions_sections.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Body of an actions tab: the switch between the condensed and the detailed view, the Gantt of the actions,
 *          then one section per type of action with its counter, its add-action form and its table. The forms post to
 *          the page itself, which handles the actions addAction, closeAction, updateProgress and updateActionField
 *          (js/modules/actions.js saves the progress and the fields edited in place the same way).
 *
 * The condensed view, shown by default, leaves out the Gantt and the secondary columns (detail classes); actions.js
 * remembers the view chosen in the browser.
 *
 * Rendered by view/accidentinvestigation/accidentinvestigation_actions.php and view/ticket/ticket_actions.php
 * (inherits their scope). Expects:
 *   $conf, $db, $form, $langs
 *   $sectionConfig        array  Title and add-button keys of each type of action: [type => [titleKey, addKey]]
 *   $actionsByType        array  Actions of each type, from digiriskdolibarr_get_actions_by_type()
 *   $actionsFormUrl       string URL of the page the forms post to
 *   $actionsParentTaskId  int    Task the actions hang under, linked under the sections
 *   $permissiontoadd      bool   Right to add, close, move and edit the actions
 *   $task                 Task   Used for the status labels
 */

$actionsWithTags   = isModEnabled('category');
$actionsTagOptions = $actionsWithTags ? digiriskdolibarr_get_action_tag_options($form) : [];

$actionsIds = [];
foreach ($actionsByType as $actions) {
    foreach ($actions as $row) {
        $actionsIds[] = (int) $row->rowid;
    }
}
$actionsTags = $actionsWithTags ? digiriskdolibarr_get_actions_tags($db, $actionsIds) : [];

// Cell of the table that actions.js turns into an editor on click, starting from its raw value
$actionsCell = static function (string $field, string $value, string $html, string $moreClass = '') use ($permissiontoadd, $langs): string {
    $class = trim($moreClass . ($permissiontoadd ? ' digirisk-actions-cell' : ''));
    $out   = '<td' . ($class !== '' ? ' class="' . $class . '"' : '');
    if ($permissiontoadd) {
        $out .= ' data-field="' . $field . '" data-value="' . dol_escape_htmltag($value) . '" title="' . dol_escape_htmltag($langs->trans('ClickToEdit')) . '"';
    }

    return $out . '>' . $html . '</td>';
};

print '<div id="digirisk-actions" class="digirisk-actions digirisk-actions--condensed">';

// Switch between the condensed view and the detailed one
print '<div class="digirisk-actions__toolbar">';
foreach (['condensed' => ['fa-bars', 'ActionsViewCondensed'], 'detailed' => ['fa-th-list', 'ActionsViewDetailed']] as $actionsView => $actionsViewConfig) {
    print '<a href="#" class="btnTitle digirisk-actions__view' . ($actionsView === 'condensed' ? ' btnTitleSelected' : '') . '" data-view="' . $actionsView . '"';
    print ' title="' . dol_escape_htmltag($langs->trans($actionsViewConfig[1])) . '" aria-label="' . dol_escape_htmltag($langs->trans($actionsViewConfig[1])) . '">';
    print '<span class="fa ' . $actionsViewConfig[0] . ' imgforviewmode valignmiddle btnTitle-icon"></span>';
    print '</a>';
}
print '</div>';

// Gantt of every action, above their sections
include __DIR__ . '/digiriskdolibarr_actions_gantt.tpl.php';

foreach ($sectionConfig as $type => $cfg) {
    $actions = $actionsByType[$type];
    $total   = count($actions);
    $done    = 0;
    foreach ($actions as $row) {
        if ((int) $row->progress >= 100) {
            $done++;
        }
    }

    $formId = 'digirisk-add-action-form-' . $type;

    print '<div class="div-table-responsive" style="margin-bottom:24px">';

    // Section header, with the add button on the right like the lists of Dolibarr
    $sectionTitle  = $langs->trans($cfg['titleKey']);
    $sectionTitle .= ' <span id="digirisk-actions-counter-' . $type . '" class="badge badge-secondary marginleftonlyshort">' . $langs->trans('XActionsOfY', $done, $total) . '</span>';
    $addButtonParams = ['forcenohideoftext' => 1];
    if ($permissiontoadd) {
        $addButtonParams['attr'] = ['class' => 'digirisk-actions-add', 'data-form' => $formId];
    }
    print load_fiche_titre($sectionTitle, dolGetButtonTitle($langs->trans($cfg['addKey']), '', 'fa fa-plus-circle', '#', '', $permissiontoadd ? 1 : 0, $addButtonParams), '');

    // Add-action form, opened by the add button of the header
    if ($permissiontoadd) {
        print '<div id="' . $formId . '" class="digirisk-actions-form hideobject">';
        print '<form method="POST" action="' . $actionsFormUrl . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="addAction">';
        print '<input type="hidden" name="action_type" value="' . $type . '">';
        print '<table class="centpercent"><tr>';

        print '<td class="digirisk-actions-form__label">';
        print '<label for="action_label' . $type . '">' . $langs->trans('ActionLabel') . ' *</label>';
        print '<input type="text" id="action_label' . $type . '" name="action_label" required>';
        print '</td>';

        // Prefixes suffixed with the type: both sections are rendered on the same page and
        // the datepicker fills its hidden day/month/year fields through getElementById.
        // The start date is today unless changed, the action usually starts when it is entered
        print '<td class="digirisk-actions-form__start">';
        print '<label for="actionstart' . $type . '">' . $langs->trans('DateStart') . '</label>';
        print $form->selectDate(dol_now(), 'actionstart' . $type, 0, 0, 1, 'addActionForm' . $type);
        print '</td>';

        print '<td class="digirisk-actions-form__deadline">';
        print '<label for="actiondeadline' . $type . '">' . $langs->trans('ActionDeadline') . '</label>';
        print $form->selectDate('', 'actiondeadline' . $type, 0, 0, 1, 'addActionForm' . $type);
        print '</td>';

        print '<td class="digirisk-actions-form__budget">';
        print '<label for="action_budget' . $type . '">' . $langs->trans('Budget') . '</label>';
        print '<input type="number" id="action_budget' . $type . '" name="action_budget" min="0" step="1">';
        print '</td>';

        print '<td class="digirisk-actions-form__assignee">';
        print '<label for="assigned_user' . $type . '">' . $langs->trans('DigiriskActionAssignedTo') . '</label>';
        print $form->select_dolusers('', 'assigned_user' . $type, 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth300');
        print '</td>';

        if ($actionsWithTags) {
            print '<td class="digirisk-actions-form__tags">';
            print '<label for="action_tags' . $type . '">' . $langs->trans('Categories') . '</label>';
            print $form->multiselectarray('action_tags' . $type, $actionsTagOptions, [], 0, 0, 'minwidth200', 0, '100%');
            print '</td>';
        }

        print '<td class="digirisk-actions-form__buttons nowraponall">';
        print '<button type="submit" class="butAction butActionShort">' . $langs->trans('Add') . '</button>';
        print '<button type="button" class="butActionDelete butActionShort digirisk-actions-form__cancel">' . $langs->trans('Cancel') . '</button>';
        print '</td>';

        print '</tr></table>';
        print '</form>';
        print '</div>';
    }

    // Table: the detail columns are left out of the condensed view
    print '<table class="tagtable liste digirisk-actions-table" width="100%">';
    print '<thead><tr class="liste_titre">';
    print '<th class="liste_titre" width="20">&nbsp;</th>';
    print '<th class="liste_titre digirisk-actions__detail">' . $langs->trans('Ref') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('ActionLabel') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('DigiriskActionAssignedTo') . '</th>';
    print '<th class="liste_titre digirisk-actions__detail">' . $langs->trans('DateStart') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('ActionDeadline') . '</th>';
    print '<th class="liste_titre right digirisk-actions__detail">' . $langs->trans('Budget') . '</th>';
    if ($actionsWithTags) {
        print '<th class="liste_titre">' . $langs->trans('Categories') . '</th>';
    }
    print '<th class="liste_titre" width="200">' . $langs->trans('ActionProgress') . '</th>';
    print '<th class="liste_titre digirisk-actions__detail">' . $langs->trans('Status') . '</th>';
    print '<th class="liste_titre" width="40">&nbsp;</th>';
    print '</tr></thead>';
    print '<tbody>';

    if (empty($actions)) {
        print '<tr><td colspan="11" class="opacitymedium center" style="padding:14px">';
        print $langs->trans('NoActionsYet');
        print '</td></tr>';
    }

    foreach ($actions as $row) {
        $pct    = max(0, min(100, (int) $row->progress));
        $isDone = ($pct >= 100);
        print '<tr id="digirisk-action-' . ((int) $row->rowid) . '" class="oddeven" data-task-id="' . ((int) $row->rowid) . '">';

        // Checkbox to close action
        print '<td class="center">';
        if ($permissiontoadd) {
            if (!$isDone) {
                print '<form method="POST" action="' . $actionsFormUrl . '" style="margin:0">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="closeAction">';
                print '<input type="hidden" name="taskid" value="' . ((int) $row->rowid) . '">';
                print '<input type="checkbox" title="' . dol_escape_htmltag($langs->trans('MarkAsDone')) . '" onchange="this.form.submit()">';
                print '</form>';
            } else {
                print '<input type="checkbox" checked disabled>';
            }
        }
        print '</td>';

        // Ref (link to task)
        print '<td class="nowraponall digirisk-actions__detail">';
        print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $row->rowid) . '" target="_blank">';
        print dol_escape_htmltag($row->ref);
        print '</a></td>';

        // Label
        print $actionsCell('label', (string) $row->label, dol_escape_htmltag($row->label));

        // Assignee
        $assignee = !empty($row->user_fullname) ? trim($row->user_fullname) : '';
        print $actionsCell('assignee', (string) (int) $row->user_id, $assignee !== '' ? dol_escape_htmltag($assignee) : '&mdash;');

        // Start date and deadline, edited as Y-m-d
        foreach (['date_start' => $row->dateo, 'date_end' => $row->datee] as $dateField => $dateValue) {
            $date = (!empty($dateValue) && $dateValue !== '0000-00-00 00:00:00') ? $db->jdate($dateValue) : '';
            print $actionsCell($dateField, $date !== '' ? dol_print_date($date, '%Y-%m-%d') : '', $date !== '' ? dol_print_date($date, 'day') : '&mdash;', 'nowraponall' . ($dateField === 'date_start' ? ' digirisk-actions__detail' : ''));
        }

        // Budget
        $hasBudget = $row->budget_amount !== null && $row->budget_amount !== '';
        print $actionsCell('budget', $hasBudget ? (string) price2num($row->budget_amount) : '', $hasBudget ? price($row->budget_amount, 0, $langs, 1, -1, -1, $conf->currency) : '&mdash;', 'right nowraponall digirisk-actions__detail');

        // Tags, coloured like their category
        if ($actionsWithTags) {
            $rowTags  = $actionsTags[(int) $row->rowid] ?? [];
            $tagsHtml = '';
            foreach ($rowTags as $tag) {
                $tagColor  = preg_replace('/[^0-9a-f]/i', '', $tag['color']);
                $tagsHtml .= '<span class="digirisk-actions-tag"' . ($tagColor !== '' ? ' style="--tag-color: #' . $tagColor . '"' : '') . '>' . dol_escape_htmltag($tag['label']) . '</span>';
            }
            print $actionsCell('tags', implode(',', array_column($rowTags, 'id')), $tagsHtml !== '' ? $tagsHtml : '&mdash;');
        }

        // Progress: the slider is dragged to change it and saved on release by actions.js
        print '<td>';
        print '<div class="digirisk-actions-progress' . ($isDone ? ' digirisk-actions-progress--done' : '') . '">';
        print '<input type="range" class="digirisk-actions-progress__slider" min="0" max="100" step="1" value="' . $pct . '"';
        print ' data-task-id="' . ((int) $row->rowid) . '" aria-label="' . dol_escape_htmltag($langs->trans('ActionProgress')) . '"' . ($permissiontoadd ? '' : ' disabled') . '>';
        print '<span class="digirisk-actions-progress__value">' . $pct . ' %</span>';
        print '</div>';
        print '</td>';

        // Status
        print '<td class="digirisk-actions__detail">' . $task->LibStatut((int) $row->fk_statut, 4) . '</td>';

        // External link
        print '<td class="right">';
        print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $row->rowid) . '" target="_blank"';
        print ' title="' . dol_escape_htmltag($langs->trans('OpenTask')) . ' ' . dol_escape_htmltag($row->ref) . '">';
        print '<i class="fas fa-external-link-alt"></i>';
        print '</a>';
        print '</td>';

        print '</tr>';
    }

    print '</tbody></table>';

    print '</div>'; // div-table-responsive
}

// Lists actions.js copies into a cell to edit its assignee or its tags, rendered once for every row
if ($permissiontoadd && !empty($actionsIds)) {
    print '<div id="digirisk-actions-editors" class="hideobject">';
    print $form->select_dolusers('', 'digirisk_actions_editor_assignee', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'digirisk-actions-cell__select', 0, 0, false, 1);
    if ($actionsWithTags) {
        print '<select id="digirisk_actions_editor_tags" class="digirisk-actions-cell__select" multiple>';
        foreach ($actionsTagOptions as $tagId => $tagLabel) {
            print '<option value="' . ((int) $tagId) . '">' . dol_escape_htmltag($tagLabel) . '</option>';
        }
        print '</select>';
    }
    print '</div>';
}

// Footer note
print '<div class="opacitymedium" style="font-size:0.85em;text-align:right;margin-top:4px">';
print $langs->trans('ActionsAreProjectTasks');
if ($actionsParentTaskId > 0) {
    print '&nbsp;&mdash;&nbsp;';
    print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . $actionsParentTaskId . '" target="_blank">';
    print $langs->trans('SeeProjectTasksView');
    print '</a>';
}
print '</div>';

print '</div>'; // digirisk-actions
