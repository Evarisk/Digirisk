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
 * \brief   Body of an actions tab: the Gantt of the actions, then one section per type of action with its counter,
 *          its add-action form and its table. The forms post to the page itself, which handles the actions addAction,
 *          closeAction and updateProgress (js/modules/actions.js saves the progress the same way).
 *
 * Rendered by view/accidentinvestigation/accidentinvestigation_actions.php and view/ticket/ticket_actions.php
 * (inherits their scope). Expects:
 *   $conf, $db, $form, $langs
 *   $sectionConfig        array  Title and add-button keys of each type of action: [type => [titleKey, addKey]]
 *   $actionsByType        array  Actions of each type, from digiriskdolibarr_get_actions_by_type()
 *   $actionsFormUrl       string URL of the page the forms post to
 *   $actionsParentTaskId  int    Task the actions hang under, linked under the sections
 *   $permissiontoadd      bool   Right to add, close and move the actions
 *   $task                 Task   Used for the status labels
 */

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
        print '<label>' . $langs->trans('ActionLabel') . ' *</label><br>';
        print '<input type="text" name="action_label" class="minwidth300" required>';
        print '</td>';

        // Prefixes suffixed with the type: both sections are rendered on the same page and
        // the datepicker fills its hidden day/month/year fields through getElementById
        print '<td class="digirisk-actions-form__start">';
        print '<label>' . $langs->trans('DateStart') . '</label><br>';
        print $form->selectDate('', 'actionstart' . $type, 0, 0, 1, 'addActionForm' . $type);
        print '</td>';

        print '<td class="digirisk-actions-form__deadline">';
        print '<label>' . $langs->trans('ActionDeadline') . '</label><br>';
        print $form->selectDate('', 'actiondeadline' . $type, 0, 0, 1, 'addActionForm' . $type);
        print '</td>';

        print '<td class="digirisk-actions-form__budget">';
        print '<label>' . $langs->trans('Budget') . '</label><br>';
        print '<input type="number" name="action_budget" class="maxwidth100" min="0" step="1">';
        print '</td>';

        print '<td class="digirisk-actions-form__assignee">';
        print '<label>' . $langs->trans('DigiriskActionAssignedTo') . '</label><br>';
        print $form->select_dolusers('', 'assigned_user' . $type, 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth300');
        print '</td>';

        print '<td class="digirisk-actions-form__buttons nowraponall">';
        print '<button type="submit" class="butAction butActionShort">' . $langs->trans('Add') . '</button>';
        print '<button type="button" class="butActionDelete butActionShort digirisk-actions-form__cancel">' . $langs->trans('Cancel') . '</button>';
        print '</td>';

        print '</tr></table>';
        print '</form>';
        print '</div>';
    }

    // Table
    print '<table class="tagtable liste" width="100%">';
    print '<thead><tr class="liste_titre">';
    print '<th class="liste_titre" width="20">&nbsp;</th>';
    print '<th class="liste_titre">' . $langs->trans('Ref') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('ActionLabel') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('DigiriskActionAssignedTo') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('DateStart') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('ActionDeadline') . '</th>';
    print '<th class="liste_titre right">' . $langs->trans('Budget') . '</th>';
    print '<th class="liste_titre" width="200">' . $langs->trans('ActionProgress') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('Status') . '</th>';
    print '<th class="liste_titre" width="40">&nbsp;</th>';
    print '</tr></thead>';
    print '<tbody>';

    if (empty($actions)) {
        print '<tr><td colspan="10" class="opacitymedium center" style="padding:14px">';
        print $langs->trans('NoActionsYet');
        print '</td></tr>';
    }

    foreach ($actions as $row) {
        $pct    = max(0, min(100, (int) $row->progress));
        $isDone = ($pct >= 100);
        print '<tr id="digirisk-action-' . ((int) $row->rowid) . '" class="oddeven">';

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
        print '<td>';
        print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $row->rowid) . '" target="_blank">';
        print dol_escape_htmltag($row->ref);
        print '</a></td>';

        // Label
        print '<td>' . dol_escape_htmltag($row->label) . '</td>';

        // Assignee
        $assignee = !empty($row->user_fullname) ? trim($row->user_fullname) : '';
        print '<td>' . ($assignee !== '' ? dol_escape_htmltag($assignee) : '&mdash;') . '</td>';

        // Start date
        print '<td>' . (!empty($row->dateo) && $row->dateo !== '0000-00-00 00:00:00' ? dol_print_date($db->jdate($row->dateo), 'day') : '&mdash;') . '</td>';

        // Deadline
        print '<td>';
        if (!empty($row->datee) && $row->datee !== '0000-00-00 00:00:00') {
            print dol_print_date($db->jdate($row->datee), 'day');
        } else {
            print '&mdash;';
        }
        print '</td>';

        // Budget
        print '<td class="right nowraponall">' . ($row->budget_amount !== null && $row->budget_amount !== '' ? price($row->budget_amount, 0, $langs, 1, -1, -1, $conf->currency) : '&mdash;') . '</td>';

        // Progress: the slider is dragged to change it and saved on release by actions.js
        print '<td>';
        print '<div class="digirisk-actions-progress' . ($isDone ? ' digirisk-actions-progress--done' : '') . '">';
        print '<input type="range" class="digirisk-actions-progress__slider" min="0" max="100" step="1" value="' . $pct . '"';
        print ' data-task-id="' . ((int) $row->rowid) . '" aria-label="' . dol_escape_htmltag($langs->trans('ActionProgress')) . '"' . ($permissiontoadd ? '' : ' disabled') . '>';
        print '<span class="digirisk-actions-progress__value">' . $pct . ' %</span>';
        print '</div>';
        print '</td>';

        // Status
        print '<td>' . $task->LibStatut((int) $row->fk_statut, 4) . '</td>';

        // External link
        print '<td class="right">';
        print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $row->rowid) . '" target="_blank"';
        print ' title="' . dol_escape_htmltag($langs->trans('OpenTask')) . '">';
        print '<i class="fas fa-external-link-alt"></i>';
        print '</a>';
        print '</td>';

        print '</tr>';
    }

    print '</tbody></table>';

    print '</div>'; // div-table-responsive
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
