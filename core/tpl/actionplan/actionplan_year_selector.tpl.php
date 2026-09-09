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
 * \file    core/tpl/actionplan/actionplan_year_selector.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Year selector of the action plan history
 *
 * Variables expected from calling PHP:
 * - $actionPlanFilters    array  Criteria from digiriskActionPlanGetFilters(), year resolved
 * - $actionPlanYearCounts array  [year => number of corrective actions] from digiriskActionPlanGetYearCounts()
 * - $projectId            int    Displayed project ID
 * - $view                 string Current view ('kanban' or 'gantt')
 * - $langs                Translate
 */

$currentYear = (int) dol_print_date(dol_now(), '%Y');

// Only the closed action plans, the running year has its own tab
$pastYearCounts = array_filter($actionPlanYearCounts, function ($year) use ($currentYear) {
    return $year < $currentYear;
}, ARRAY_FILTER_USE_KEY);

// Switching year keeps the view, the displayed project, the criteria and the menu highlight
$yearUrl  = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&projectid=' . (int) $projectId . '&period=history';
$yearUrl .= digiriskActionPlanFilterUrlParams($actionPlanFilters);
if (GETPOST('mainmenu', 'aZ09')) {
    $yearUrl .= '&mainmenu=' . urlencode(GETPOST('mainmenu', 'aZ09'));
}
if (GETPOST('leftmenu', 'aZ09')) {
    $yearUrl .= '&leftmenu=' . urlencode(GETPOST('leftmenu', 'aZ09'));
}
if (GETPOSTINT('idmenu') > 0) {
    $yearUrl .= '&idmenu=' . GETPOSTINT('idmenu');
}
?>

<div class="actionplan-year-bar">
    <span class="ayb-caption" title="<?php echo dol_escape_htmltag($langs->trans('ActionPlanYearRule')); ?>">
        <i class="fas fa-history"></i> <?php echo $langs->trans('ActionPlanHistoryYear'); ?>
    </span>
    <?php if (empty($pastYearCounts)) : ?>
        <span class="ayb-none"><?php echo $langs->trans('ActionPlanNoHistory'); ?></span>
    <?php else : ?>
        <div class="ayb-years">
            <?php foreach ($pastYearCounts as $year => $yearCount) : ?>
                <a class="ayb-year<?php echo ($year == $actionPlanFilters['year']) ? ' selected' : ''; ?>"
                   href="<?php echo dol_escape_htmltag($yearUrl . '&year=' . (int) $year); ?>">
                    <?php echo (int) $year; ?>
                    <span class="ayb-count"><?php echo (int) $yearCount; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
