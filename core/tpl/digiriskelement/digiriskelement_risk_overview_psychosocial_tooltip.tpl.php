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
 * \file    core/tpl/digiriskelement/digiriskelement_risk_overview_psychosocial_tooltip.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Tooltip of a psychosocial average of the public overview of the risks: the average and the
 *          level of each factor F1 to F6 it is made of, so that the risk tab gives them without a
 *          switch to the psychosocial tab. Shown by js/modules/riskOverview.js, as the risk tooltip.
 *
 * Variables : $overviewRow, $rpsColumns (digiriskRiskOverviewFactorColumns()), $rpsScale, $tooltipHint, $langs
 *             (core/tpl/digiriskelement/digiriskelement_risk_overview_public.tpl.php); rps_scope_band() is
 *             declared by digiriskdolibarr_rps_scope_data.tpl.php
 */

$rpsAverage = (float) $overviewRow['rps']['average'];
$rpsBand    = (int) $overviewRow['rps']['band'];

?>
<template class="risk-overview__risks">
    <div class="risk-overview__tooltip-title">
        <span class="risk-overview__ref"><?php echo dol_escape_htmltag($overviewRow['ref']); ?></span>
        <span class="risk-overview__tooltip-element"><?php echo dol_escape_htmltag($overviewRow['label']); ?></span>
        <span class="risk-overview__tooltip-category">
            <?php echo $langs->trans('RiskOverviewTabPsychosocial'); ?>
            <?php // A groupment has no assessment of its own ?>
            <?php if ($overviewRow['type'] == 'groupment') : ?>
                · <?php echo $langs->trans('RiskOverviewTooltipGroupmentAverage'); ?>
            <?php endif; ?>
        </span>
    </div>
    <div class="risk-overview__tooltip-average">
        <span class="krt-cotation" data-scale="<?php echo $rpsBand; ?>"><?php echo digiriskRiskOverviewFormatLevel($rpsAverage); ?></span>
        <strong><?php echo $langs->trans('RiskOverviewPsychosocialAverage'); ?></strong>
        <?php if (isset($rpsScale[$rpsBand])) : ?>
            <span>· <?php echo dol_escape_htmltag($rpsScale[$rpsBand]['label']); ?></span>
        <?php endif; ?>
    </div>
    <div class="risk-overview__tooltip-factors">
        <?php foreach ($rpsColumns as $rpsColumn) :
            $factorLevel = $overviewRow['rps']['levels'][$rpsColumn['position']] ?? null; ?>
            <div class="risk-overview__tooltip-factor<?php echo $factorLevel === null ? ' is-empty' : ''; ?>">
                <?php if ($factorLevel !== null) : ?>
                    <span class="krt-cotation" data-scale="<?php echo rps_scope_band((float) $factorLevel); ?>"><?php echo digiriskRiskOverviewFormatLevel((float) $factorLevel); ?></span>
                <?php else : ?>
                    <span class="krt-cotation is-empty"><?php echo $langs->trans('RiskOverviewNotConcerned'); ?></span>
                <?php endif; ?>
                <strong class="risk-overview__tooltip-factor-key"><?php echo $rpsColumn['factor']; ?></strong>
                <span><?php echo dol_escape_htmltag($rpsColumn['name']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($tooltipHint)) : ?>
        <div class="risk-overview__tooltip-hint"><i class="fas fa-external-link-alt"></i> <?php echo dol_escape_htmltag($tooltipHint); ?></div>
    <?php endif; ?>
</template>
