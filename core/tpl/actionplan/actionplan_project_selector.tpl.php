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
 * \file    core/tpl/actionplan/actionplan_project_selector.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Displayed project banner and project switcher for the action plan
 *
 * Variables expected from calling PHP:
 * - $project     Project     Currently displayed project (fetched, may be empty)
 * - $projectId   int         Currently displayed project ID
 * - $duProjectId int         Document Unique project ID (default project)
 * - $formproject FormProjets Dolibarr project form helper
 * - $view        string      Current view ('kanban' or 'gantt')
 * - $langs       Translate
 */

// Keep the menu highlighted after the switch (the selector reloads the page)
$menuMain = GETPOST('mainmenu', 'aZ09');
$menuLeft = GETPOST('leftmenu', 'aZ09');
$menuId   = GETPOSTINT('idmenu');
?>

<div class="actionplan-project-bar">
    <div class="app-project-current">
        <i class="fas fa-project-diagram"></i>
        <span class="app-project-caption"><?php echo $langs->trans('ActionPlanDisplayedProject'); ?></span>
        <?php if ($projectId > 0 && $project->id > 0) : ?>
            <?php echo $project->getNomUrl(1); ?>
            <span class="app-project-title"><?php echo dol_escape_htmltag($project->title); ?></span>
            <?php if ($projectId == $duProjectId) : ?>
                <span class="app-project-badge"><?php echo $langs->trans('ActionPlanDefaultProject'); ?></span>
            <?php endif; ?>
        <?php else : ?>
            <span class="app-project-none"><?php echo $langs->trans('ActionPlanNoProjectConfigured'); ?></span>
        <?php endif; ?>
    </div>
    <form class="app-project-form" method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="view" value="<?php echo dol_escape_htmltag($view); ?>">
        <?php if (!empty($menuMain)) : ?>
            <input type="hidden" name="mainmenu" value="<?php echo dol_escape_htmltag($menuMain); ?>">
        <?php endif; ?>
        <?php if (!empty($menuLeft)) : ?>
            <input type="hidden" name="leftmenu" value="<?php echo dol_escape_htmltag($menuLeft); ?>">
        <?php endif; ?>
        <?php if ($menuId > 0) : ?>
            <input type="hidden" name="idmenu" value="<?php echo $menuId; ?>">
        <?php endif; ?>
        <label for="projectid"><i class="fas fa-exchange-alt"></i> <?php echo $langs->trans('ActionPlanChangeProject'); ?></label>
        <?php $formproject->select_projects(-1, $projectId, 'projectid', 0, 0, 0, 0, 0, 0, 0, '', 0, 1, 'maxwidth300'); ?>
    </form>
</div>
