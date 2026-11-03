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
 * \file    core/tpl/actions/digiriskdolibarr_actions_gantt.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Gantt of the actions of an actions tab, grouped by type. An action only has a deadline: its
 *          bar starts at the start date of its task, or failing that at its creation (see
 *          digiriskdolibarr_get_actions_gantt()). Hidden while there is no action.
 *          The positions are percentages carried by data attributes, actions.js turns them into CSS
 *          variables.
 *
 * Rendered by core/tpl/actions/digiriskdolibarr_actions_sections.tpl.php (inherits its scope). Expects:
 *   $db, $langs
 *   $sectionConfig  array  Title key of each type of action
 *   $actionsByType  array  Actions of each type, from digiriskdolibarr_get_actions_by_type()
 */

$gantt = digiriskdolibarr_get_actions_gantt($db, $actionsByType);
if (empty($gantt['rows'])) {
    return;
}

$ganttStates = [
    'progress' => $langs->trans('AccidentInvestigationActionInProgress'),
    'done'     => $langs->trans('AccidentInvestigationActionDone'),
    'late'     => $langs->trans('AccidentInvestigationActionLate'),
    'open'     => $langs->trans('AccidentInvestigationActionNoDeadline'),
];

print '<div id="digirisk-actions-gantt" class="digirisk-actions-gantt">';
print '<div class="digirisk-actions-gantt__header">';
print '<span class="digirisk-actions-gantt__icon"><i class="fas fa-stream"></i></span>';
print '<div>';
print '<div class="digirisk-actions-gantt__title">' . $langs->trans('AccidentInvestigationActionsPlanning') . '</div>';
print '<div class="digirisk-actions-gantt__hint">' . $langs->trans('AccidentInvestigationActionsPlanningHint') . '</div>';
print '</div>';
print '</div>';

print '<div class="digirisk-actions-gantt__chart">';

// Lines of the ticks and of today, drawn once over every row
print '<div class="digirisk-actions-gantt__grid" aria-hidden="true">';
foreach ($gantt['ticks'] as $ganttTick) {
    print '<span class="digirisk-actions-gantt__gridline" data-position="' . $ganttTick['position'] . '"></span>';
}
// Near an edge, the label of today stands on the inner side of its line
$ganttTodaySide = $gantt['today'] > 85 ? ' digirisk-actions-gantt__today--end' : ($gantt['today'] < 15 ? ' digirisk-actions-gantt__today--start' : '');
print '<span class="digirisk-actions-gantt__today' . $ganttTodaySide . '" data-position="' . $gantt['today'] . '">';
print '<span class="digirisk-actions-gantt__today-label">' . $langs->trans('Today') . '</span>';
print '</span>';
print '</div>';

print '<div class="digirisk-actions-gantt__row digirisk-actions-gantt__row--axis">';
print '<div class="digirisk-actions-gantt__name"></div>';
print '<div class="digirisk-actions-gantt__track">';
foreach ($gantt['ticks'] as $ganttTickIndex => $ganttTick) {
    // Every other label gives way on narrow screens
    $ganttTickMinor = count($gantt['ticks']) > 3 && $ganttTickIndex % 2 == 1;
    print '<span class="digirisk-actions-gantt__tick' . ($ganttTickMinor ? ' digirisk-actions-gantt__tick--minor' : '') . '" data-position="' . $ganttTick['position'] . '">';
    print dol_escape_htmltag($ganttTick['label']);
    print '</span>';
}
print '</div>';
print '</div>';

foreach ($gantt['rows'] as $ganttType => $ganttRows) {
    print '<div class="digirisk-actions-gantt__row digirisk-actions-gantt__row--group">';
    print '<div class="digirisk-actions-gantt__name">' . $langs->trans($sectionConfig[$ganttType]['titleKey']) . '</div>';
    print '<div class="digirisk-actions-gantt__track"></div>';
    print '</div>';

    foreach ($ganttRows as $ganttRow) {
        $ganttAction   = $ganttRow['action'];
        $ganttProgress = max(0, min(100, (int) $ganttAction->progress));
        $ganttTaskUrl  = dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $ganttAction->rowid);

        $ganttDetails   = [$ganttAction->ref . ' - ' . $ganttAction->label];
        $ganttDetails[] = $langs->transnoentities(empty($ganttAction->dateo) ? 'AccidentInvestigationActionCreatedOn' : 'AccidentInvestigationActionStartsOn', dol_print_date($ganttRow['from'], 'day'));
        $ganttDetails[] = $ganttRow['end'] > 0 ? $langs->transnoentities('AccidentInvestigationActionDueOn', dol_print_date($ganttRow['end'], 'day')) : $langs->transnoentities('AccidentInvestigationActionNoDeadline');
        $ganttDetails[] = $langs->transnoentities('ActionProgress') . ' : ' . $ganttProgress . ' %';
        if ($ganttRow['state'] == 'late') {
            $ganttDetails[] = $langs->transnoentities('AccidentInvestigationActionLateBy', (string) $ganttRow['lateDays']);
        }

        // The value follows the bar, sits inside a long bar reaching the end, or else precedes it
        $ganttBarEnd = $ganttRow['left'] + $ganttRow['length'];
        if ($ganttBarEnd <= 80) {
            $ganttValuePlace = 'after';
        } elseif ($ganttRow['length'] >= 20) {
            $ganttValuePlace = 'inside';
        } else {
            $ganttValuePlace = 'before';
        }
        // The state is spelled out next to the value when the colour alone tells it, except on narrow screens
        $ganttValue = $ganttProgress . ' %';
        if (in_array($ganttRow['state'], ['late', 'open'])) {
            $ganttValue .= '<span class="digirisk-actions-gantt__value-state"> · ' . dol_escape_htmltag($ganttStates[$ganttRow['state']]) . '</span>';
        }

        print '<div class="digirisk-actions-gantt__row">';
        print '<div class="digirisk-actions-gantt__name">';
        print '<a href="' . $ganttTaskUrl . '" target="_blank">' . dol_escape_htmltag($ganttAction->ref) . '</a>';
        print '<span class="digirisk-actions-gantt__label">' . dol_escape_htmltag($ganttAction->label) . '</span>';
        print '</div>';
        print '<div class="digirisk-actions-gantt__track">';
        print '<a href="' . $ganttTaskUrl . '" target="_blank" tabindex="-1"';
        print ' class="digirisk-actions-gantt__bar digirisk-actions-gantt__bar--' . $ganttRow['state'] . ' digirisk-actions-gantt__bar--value-' . $ganttValuePlace . '"';
        print ' data-start="' . $ganttRow['left'] . '" data-length="' . $ganttRow['length'] . '" data-progress="' . $ganttProgress . '"';
        print ' title="' . dol_escape_htmltag(implode("\n", $ganttDetails), 0, 1) . '">';
        print '<span class="digirisk-actions-gantt__fill"></span>';
        print '<span class="digirisk-actions-gantt__value">' . $ganttValue . '</span>';
        print '</a>';
        print '</div>';
        print '</div>';
    }
}

print '</div>'; // __chart

print '<div class="digirisk-actions-gantt__legend">';
foreach ($ganttStates as $ganttState => $ganttStateLabel) {
    print '<span class="digirisk-actions-gantt__legend-item digirisk-actions-gantt__legend-item--' . $ganttState . '">' . $ganttStateLabel . '</span>';
}
print '</div>';

print '</div>';
