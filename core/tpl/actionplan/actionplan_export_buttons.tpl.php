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
 * \file    core/tpl/actionplan/actionplan_export_buttons.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Top-right export toolbar for the action plan (CSV / A3 PDF / Gantt PNG)
 *
 * Variables expected from calling PHP:
 * - $view              string    Current view ('kanban' or 'gantt')
 * - $actionPlanFilters array     Criteria from digiriskActionPlanGetFilters(), carried over to the exports
 * - $user              User      Current user (permission checks)
 * - $langs             Translate Translation object
 */

// Server-side exports POST to the current view (CSRF token included); the Gantt
// PNG export is a client-side capture triggered through a GET flag on the URL
$exportAction = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view);
?>

<div class="actionplan-export-toolbar">
    <?php if ($user->hasRight('digiriskdolibarr', 'riskassessmentdocument', 'read')) : ?>
        <form method="POST" action="<?php echo $exportAction; ?>" class="ap-export-form">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="exportCsv">
            <?php echo digiriskActionPlanFilterHiddenInputs($actionPlanFilters); ?>
            <button type="submit" class="ap-export-btn" title="<?php echo dol_escape_htmltag($langs->trans('ActionPlanExportCsv')); ?>">
                <i class="fas fa-download"></i>
                <span class="ap-export-label">CSV</span>
            </button>
        </form>
    <?php endif; ?>
    <?php if ($user->hasRight('projet', 'creer')) : ?>
        <form method="POST" action="<?php echo $exportAction; ?>" class="ap-export-form">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="builddoc">
            <input type="hidden" name="model" value="<?php echo dol_escape_htmltag(getDolGlobalString('DIGIRISKDOLIBARR_PROJECTDOCUMENT_DEFAULT_MODEL', 'papripact_a3_paysage_projectdocument')); ?>">
            <?php echo digiriskActionPlanFilterHiddenInputs($actionPlanFilters); ?>
            <button type="submit" class="ap-export-btn" title="<?php echo dol_escape_htmltag($langs->trans('ActionPlanExportA3')); ?>">
                <i class="fas fa-download"></i>
                <span class="ap-export-label">A3</span>
            </button>
        </form>
    <?php endif; ?>
    <?php
    // Gantt PNG export hidden for now (the Gantt tab itself is hidden since #4738).
    // The capture logic (html2canvas in actionplan_gantt.js) is kept in place;
    // flip $showGanttExport to true to restore the button.
    $showGanttExport = false;
    if ($showGanttExport) : ?>
        <a class="ap-export-btn ap-export-gantt" href="<?php echo $_SERVER['PHP_SELF'] . '?view=gantt&export=png'; ?>" title="<?php echo dol_escape_htmltag($langs->trans('ActionPlanExportGantt')); ?>">
            <i class="fas fa-download"></i>
            <span class="ap-export-label">Gantt</span>
        </a>
    <?php endif; ?>
</div>
