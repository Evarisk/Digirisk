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
 * \file    core/tpl/actionplan/actionplan_filters.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Filter bar of the action plan (GP/UT, risk level, tags)
 *
 * Variables expected from calling PHP:
 * - $actionPlanFilters      array  Criteria from digiriskActionPlanGetFilters()
 * - $elementTree            array  GP/UT tree from digiriskActionPlanGetElementTree()
 * - $allAvailableCategories array  Categories usable as tags
 * - $risk                   Risk   Risk object (cotation scale labels and colors)
 * - $view                   string Current view ('kanban' or 'gantt')
 * - $globalTaskCount        int    Displayed corrective actions
 * - $unfilteredTaskCount    int    Corrective actions of the project, criteria aside
 * - $form                   Form
 * - $langs                  Translate
 */

// GP/UT options, indented as the organization tree so a work unit reads under its groupment.
// The option labels are html escaped by selectarray(), the indent is made of non breaking spaces.
$elementOptions = [];
foreach ($elementTree['flat'] as $elementID => $elementInfo) {
    $elementOptions[$elementID] = str_repeat("\u{00A0}", 4 * $elementInfo['depth']) . $elementInfo['ref'] . ' - ' . $elementInfo['label'];
}

// Risk levels, same scale (label and color) as the risk list and the dashboard graphs
$cotationLevels = $risk->getCotations();

// Keep the project switch and the menu highlight while filtering
$menuMain = GETPOST('mainmenu', 'aZ09');
$menuLeft = GETPOST('leftmenu', 'aZ09');
$menuId   = GETPOSTINT('idmenu');

$hasFilters = digiriskActionPlanHasFilters($actionPlanFilters);
?>

<form class="actionplan-filter-bar" id="actionPlanFilterForm" method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="hidden" name="view" value="<?php echo dol_escape_htmltag($view); ?>">
    <input type="hidden" name="projectid" value="<?php echo (int) $projectId; ?>">
    <?php if (!empty($menuMain)) : ?>
        <input type="hidden" name="mainmenu" value="<?php echo dol_escape_htmltag($menuMain); ?>">
    <?php endif; ?>
    <?php if (!empty($menuLeft)) : ?>
        <input type="hidden" name="leftmenu" value="<?php echo dol_escape_htmltag($menuLeft); ?>">
    <?php endif; ?>
    <?php if ($menuId > 0) : ?>
        <input type="hidden" name="idmenu" value="<?php echo $menuId; ?>">
    <?php endif; ?>

    <div class="apf-row">
        <div class="apf-field apf-field-element">
            <label for="search_element"><i class="fas fa-sitemap"></i> <?php echo $langs->trans('ActionPlanElement'); ?></label>
            <?php echo $form->selectarray('search_element', $elementOptions, $actionPlanFilters['element'], 1, 0, 0, '', 0, 0, 0, '', 'maxwidth300 apf-select'); ?>
            <?php // Unchecked boxes are not submitted: the hidden twin carries the "without the sub elements" choice ?>
            <input type="hidden" name="search_element_children" value="0">
            <label class="apf-children-toggle" title="<?php echo dol_escape_htmltag($langs->trans('ActionPlanFilterElementChildrenHelp')); ?>">
                <input type="checkbox" name="search_element_children" value="1" <?php echo !empty($actionPlanFilters['element_children']) ? 'checked' : ''; ?>>
                <?php echo $langs->trans('ActionPlanFilterElementChildren'); ?>
            </label>
        </div>

        <div class="apf-field apf-field-scale">
            <label for="search_scale"><i class="fas fa-exclamation-triangle"></i> <?php echo $langs->trans('ActionPlanFilterScale'); ?></label>
            <select class="flat apf-select" id="search_scale" name="search_scale">
                <option value="0"><?php echo $langs->trans('ActionPlanFilterAllScales'); ?></option>
                <?php foreach ($cotationLevels as $cotationLevel => $cotation) : ?>
                    <option value="<?php echo (int) $cotationLevel; ?>" <?php echo ($actionPlanFilters['scale'] == $cotationLevel) ? 'selected' : ''; ?>>
                        <?php echo dol_escape_htmltag($cotation['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="apf-scale-dots">
                <?php foreach ($cotationLevels as $cotationLevel => $cotation) : ?>
                    <span class="apf-scale-dot <?php echo ($actionPlanFilters['scale'] == $cotationLevel) ? 'selected' : ''; ?>"
                          style="background: <?php echo dol_escape_htmltag($cotation['color']); ?>"
                          title="<?php echo dol_escape_htmltag($cotation['label']); ?>"></span>
                <?php endforeach; ?>
            </span>
        </div>

        <div class="apf-actions">
            <button type="submit" class="apf-btn apf-btn-search" name="button_search" value="1">
                <i class="fas fa-search"></i> <?php echo $langs->trans('Search'); ?>
            </button>
            <?php if ($hasFilters) : ?>
                <button type="submit" class="apf-btn apf-btn-reset" name="button_removefilter" value="1">
                    <i class="fas fa-eraser"></i> <?php echo $langs->trans('RemoveFilter'); ?>
                </button>
            <?php endif; ?>
            <span class="apf-count <?php echo $hasFilters ? 'filtered' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <?php echo $langs->trans('ActionPlanFilterCount', $globalTaskCount, $unfilteredTaskCount); ?>
            </span>
        </div>
    </div>

    <?php if (!empty($allAvailableCategories)) : ?>
        <div class="apf-row apf-row-tags">
            <label><i class="fas fa-tags"></i> <?php echo $langs->trans('ActionPlanFilterTags'); ?></label>
            <div class="apf-tags">
                <?php foreach ($allAvailableCategories as $availableCategory) :
                    $isSelected = in_array((int) $availableCategory['id'], $actionPlanFilters['tags'], true);
                    $tagColor   = !empty($availableCategory['color']) ? '#' . dol_escape_htmltag($availableCategory['color']) : '#8c8c8c';
                ?>
                    <label class="apf-tag<?php echo $isSelected ? ' selected' : ''; ?>" style="border-color: <?php echo $tagColor; ?>; <?php echo $isSelected ? 'background: ' . $tagColor . ';' : ''; ?>">
                        <input type="checkbox" name="search_tags[]" value="<?php echo (int) $availableCategory['id']; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                        <span class="apf-tag-dot" style="background: <?php echo $tagColor; ?>"></span>
                        <?php echo dol_escape_htmltag($availableCategory['label']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</form>
