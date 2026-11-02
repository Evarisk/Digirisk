<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * \file    core/tpl/digiriskelement/digiriskelement_risk_overview_tooltip.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Tooltip of a number of the public overview of the risks: the risks it counts, the worst
 *          first, laid out as the tooltip of a risk of the action plan (actionplan_kanban_card.tpl.php).
 *          Kept in a template, inert until js/modules/riskOverview.js shows it: the photos only load
 *          on hover.
 *
 * Variables : $cellRisks (digiriskRiskOverviewCellRisks()), $cell (digiriskRiskOverviewCell()), $position,
 *             $overviewRow, $tooltipTitle, $tooltipHint, $elementUrl, $langs
 *             (core/tpl/digiriskelement/digiriskelement_risk_overview_public.tpl.php)
 */

// The risks of a category cell share the category of the title: a total gives each its own
$showRiskCategory = $position < 0;
// The total of the risk tab counts the psychosocial line of the risk list too, which reads as a level
$uncountedLines = (int) $cell['value'] - count($cellRisks['risks']) - (int) $cellRisks['more'];

?>
<template class="risk-overview__risks">
    <div class="risk-overview__tooltip-title">
        <span class="risk-overview__ref"><?php echo dol_escape_htmltag($overviewRow['ref']); ?></span>
        <span class="risk-overview__tooltip-element"><?php echo dol_escape_htmltag($overviewRow['label']); ?></span>
        <span class="risk-overview__tooltip-category"><?php echo dol_escape_htmltag($tooltipTitle); ?></span>
    </div>
    <?php foreach ($cellRisks['risks'] as $cellRisk) : ?>
        <div class="risk-overview__tooltip-risk">
            <div class="krt-header">
                <?php if ($showRiskCategory && !empty($cellRisk['categoryPicto'])) : ?>
                    <img class="risk-overview__tooltip-picto" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . dol_escape_htmltag($cellRisk['categoryPicto']); ?>" alt="">
                <?php endif; ?>
                <strong><?php echo dol_escape_htmltag($cellRisk['ref']); ?></strong>
                <?php if ($showRiskCategory && !empty($cellRisk['categoryName'])) : ?>
                    : <?php echo dol_escape_htmltag($cellRisk['categoryName']); ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($cellRisk['assessmentRef'])) : ?>
                <div class="krt-eval">
                    <i class="fas fa-chart-line"></i>
                    <span class="krt-cotation" data-scale="<?php echo (int) $cellRisk['scale']; ?>"><?php echo (int) $cellRisk['cotation']; ?></span>
                    <?php if (!empty($cellRisk['photoUrl'])) : ?>
                        <img src="<?php echo dol_escape_htmltag($cellRisk['photoUrl']); ?>" class="krt-photo" alt="">
                    <?php else : ?>
                        <span class="krt-photo-default"><i class="fas fa-image"></i></span>
                    <?php endif; ?>
                    <span><?php echo dol_escape_htmltag($cellRisk['assessmentRef']); ?></span>
                    <?php if (!empty($cellRisk['assessmentDate'])) : ?>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo dol_print_date($cellRisk['assessmentDate'], 'day'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php // The description of a risk often repeats the name of its category: it would read twice ?>
            <?php if ($cellRisk['description'] !== '' && $cellRisk['description'] !== $cellRisk['categoryName']) : ?>
                <div class="krt-comment risk-overview__tooltip-description"><?php echo dol_escape_htmltag(dol_trunc($cellRisk['description'], 140)); ?></div>
            <?php endif; ?>
            <?php if ($cellRisk['comment'] !== '') : ?>
                <div class="krt-comment">
                    <i class="fas fa-comment"></i> <?php echo dol_escape_htmltag(dol_trunc($cellRisk['comment'], 120)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if ($cellRisks['more'] > 0) : ?>
        <div class="risk-overview__tooltip-more">
            <?php echo $langs->trans($cellRisks['more'] > 1 ? 'RiskOverviewTooltipMore' : 'RiskOverviewTooltipMoreOne', (int) $cellRisks['more']); ?>
        </div>
    <?php endif; ?>
    <?php if ($uncountedLines > 0) : ?>
        <div class="risk-overview__tooltip-more"><?php echo $langs->trans('RiskOverviewTooltipPsychosocialLine'); ?></div>
    <?php endif; ?>
    <?php if (!empty($tooltipHint)) : ?>
        <a class="risk-overview__tooltip-hint" href="<?php echo dol_escape_htmltag($elementUrl); ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> <?php echo dol_escape_htmltag($tooltipHint); ?></a>
    <?php endif; ?>
</template>
