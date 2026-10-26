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
 * \file    core/tpl/digiriskelement/digiriskelement_risk_overview_public.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   View of the public overview of the risks: one row per groupment and work unit.
 *          A risk cell holds the number of risks of the category on the colour of the worst one,
 *          a psychosocial cell the level of the factor or the average of the factors.
 *
 * Variables : $accessAllowed, $tab, $tabUrl, $overviewRows, $overviewColumns, $rpsSubCategories, $rpsScale,
 *             $langs, $mysoc (public/digiriskelement/digiriskelement_risk_overview.php)
 */

$overviewTabs = [
    'risk'       => ['icon' => 'fa-exclamation-triangle', 'label' => 'RiskOverviewTabRisks'],
    'penibility' => ['icon' => 'fa-hard-hat',             'label' => 'RiskOverviewTabPenibility'],
    'rps'        => ['icon' => 'fa-brain',                'label' => 'RiskOverviewTabPsychosocial'],
];
$riskLevelLabels = [1 => 'GreyRisk', 2 => 'OrangeRisk', 3 => 'RedRisk', 4 => 'BlackRisk'];

/**
 * Why a psychosocial cell holds nothing: the cell reads NC like any empty cell, the state goes
 * in its tooltip.
 *
 * @param  array $rps Psychosocial assessment of the row
 * @return string     Tooltip of the cell
 */
$rpsStateTitle = function (array $rps) use ($langs): string {
    if ($rps['state'] === 0) {
        return $langs->trans('RiskOverviewPsychosocialNotConcerned') . (dol_strlen($rps['reason']) ? ' : ' . $rps['reason'] : '');
    }
    if ($rps['state'] === 1) {
        return $langs->trans('RiskOverviewNotAssessed');
    }
    if ($rps['state'] === -1) {
        return $langs->trans('RiskOverviewToQualify');
    }
    return $langs->trans('RiskOverviewNoWorkUnit');
};

?>
<div class="public-card__container risk-overview<?php echo $accessAllowed ? '' : ' risk-overview--closed'; ?>" data-public-interface="true">
    <?php if (!$accessAllowed) : ?>
        <div class="risk-overview__closed">
            <i class="fas fa-lock"></i>
            <div><?php echo $langs->trans('RiskOverviewPublicClosed'); ?></div>
        </div>
    <?php else : ?>
        <!-- Same header as the other public interfaces (electronic signature, registers): what the page is,
             for whom, and the object it is about -->
        <div class="public-card__header wpeo-gridlayout grid-2 grid-gap-2">
            <div class="header-information">
                <div class="information-title"><?php echo $langs->trans('RiskOverviewPublicTitle'); ?></div>
                <div class="information-user"><?php echo dol_escape_htmltag($mysoc->name); ?></div>
            </div>
            <div class="header-objet">
                <div class="objet-container">
                    <div class="objet-info">
                        <div class="objet-type"><?php echo $langs->trans('RiskAssessmentDocument'); ?></div>
                        <div class="objet-label"><?php echo $langs->trans('RiskOverviewPublicSubtitle', count($overviewRows), dol_print_date(dol_now(), 'day')); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <nav class="risk-overview__tabs">
            <?php foreach ($overviewTabs as $overviewTab => $overviewTabMeta) : ?>
                <a class="risk-overview__tab<?php echo $overviewTab == $tab ? ' is-active' : ''; ?>" data-tab="<?php echo $overviewTab; ?>" href="<?php echo dol_escape_htmltag($tabUrl($overviewTab)); ?>">
                    <i class="fas <?php echo $overviewTabMeta['icon']; ?>"></i> <?php echo $langs->trans($overviewTabMeta['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php // The three tabs are in the page: js/modules/riskOverview.js shows one without reloading
        foreach ($overviewTabs as $panelTab => $overviewTabMeta) :
            $columns = $overviewColumns[$panelTab] ?? []; ?>
        <div class="risk-overview__card" data-tab="<?php echo $panelTab; ?>"<?php echo $panelTab == $tab ? '' : ' hidden'; ?>>
            <?php if (empty($overviewRows)) : ?>
                <div class="risk-overview__empty"><?php echo $langs->trans('RiskOverviewEmpty'); ?></div>
            <?php else : ?>
                <div class="risk-overview__scroll">
                    <table class="risk-overview__table risk-overview__table--<?php echo $panelTab; ?>">
                        <thead>
                        <tr>
                            <th class="risk-overview__element" scope="col"><?php echo $langs->trans('RiskOverviewElement'); ?></th>
                            <?php if ($panelTab == 'rps') : ?>
                                <th class="risk-overview__total" scope="col" title="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewPsychosocialAverageHelp')); ?>">
                                    <?php echo $langs->trans('RiskOverviewPsychosocialAverage'); ?>
                                </th>
                                <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                                    <th class="risk-overview__head" scope="col" title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">
                                        <span class="risk-overview__factor">F<?php echo (int) $rpsPosition + 1; ?></span>
                                        <span class="risk-overview__head-name"><?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?></span>
                                    </th>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <th class="risk-overview__total" scope="col" title="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewRiskCountHelp')); ?>">
                                    <?php echo $langs->trans('RiskOverviewRiskCount'); ?>
                                </th>
                                <?php foreach ($columns as $column) : ?>
                                    <th class="risk-overview__head" scope="col" title="<?php echo dol_escape_htmltag($column['name']); ?>">
                                        <span class="risk-overview__head-name"><?php echo dol_escape_htmltag($column['shortName']); ?></span>
                                        <img class="risk-overview__picto" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . dol_escape_htmltag($column['picto']); ?>" alt="">
                                    </th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($overviewRows as $overviewRow) :
                            $rps = $overviewRow['rps']; ?>
                            <tr class="risk-overview__row risk-overview__row--<?php echo $overviewRow['type'] == 'groupment' ? 'groupment' : 'workunit'; ?>">
                                <th class="risk-overview__element" scope="row" data-depth="<?php echo min((int) $overviewRow['depth'], 5); ?>">
                                    <span class="risk-overview__ref"><?php echo dol_escape_htmltag($overviewRow['ref']); ?></span>
                                    <span class="risk-overview__label"><?php echo dol_escape_htmltag($overviewRow['label']); ?></span>
                                </th>

                                <?php if ($panelTab == 'rps') :
                                    // A cell without a level reads NC, whatever the reason: not concerned, to qualify, not assessed, factor not rated
                                    $rpsEmptyTitle = $rps['average'] !== null ? $langs->trans('RiskOverviewFactorNotRated') : $rpsStateTitle($rps); ?>
                                    <?php if ($rps['average'] !== null) : ?>
                                        <td class="risk-overview__total risk-overview__cell" data-scale="<?php echo (int) $rps['band']; ?>"
                                            title="<?php echo dol_escape_htmltag($rpsScale[$rps['band']]['range'] . ' · ' . $rpsScale[$rps['band']]['label']); ?>">
                                            <?php echo digiriskRiskOverviewFormatLevel($rps['average']); ?>
                                        </td>
                                    <?php else : ?>
                                        <td class="risk-overview__total risk-overview__cell is-empty" title="<?php echo dol_escape_htmltag($rpsEmptyTitle); ?>"><?php echo $langs->trans('RiskOverviewNotConcerned'); ?></td>
                                    <?php endif; ?>
                                    <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                                        $rpsLevel = $rps['levels'][$rpsPosition] ?? null; ?>
                                        <?php if ($rpsLevel === null) : ?>
                                            <td class="risk-overview__cell is-empty" title="<?php echo dol_escape_htmltag($rpsEmptyTitle); ?>"><?php echo $langs->trans('RiskOverviewNotConcerned'); ?></td>
                                        <?php else :
                                            $rpsLevelScale = rps_scope_band((float) $rpsLevel); ?>
                                            <td class="risk-overview__cell" data-scale="<?php echo $rpsLevelScale; ?>"
                                                title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — ' . $rpsScale[$rpsLevelScale]['label']); ?>">
                                                <?php echo digiriskRiskOverviewFormatLevel((float) $rpsLevel); ?>
                                            </td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else :
                                    $riskCount = 0;
                                    foreach ($columns as $column) {
                                        if ($column['position'] != Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
                                            $riskCount += $overviewRow['cells'][$column['position']]['count'] ?? 0;
                                        }
                                    } ?>
                                    <td class="risk-overview__total"><?php echo $riskCount; ?></td>
                                    <?php foreach ($columns as $column) :
                                        $cell = $overviewRow['cells'][$column['position']] ?? null; ?>
                                        <?php if ($column['position'] == Risk::PSYCHOSOCIAL_CATEGORY_POSITION) : ?>
                                            <?php if ($rps['average'] !== null) : ?>
                                                <td class="risk-overview__cell" data-scale="<?php echo (int) $rps['band']; ?>"
                                                    title="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewPsychosocialAverageHelp')); ?>">
                                                    <?php echo digiriskRiskOverviewFormatLevel($rps['average']); ?>
                                                </td>
                                            <?php else : ?>
                                                <td class="risk-overview__cell is-empty" title="<?php echo dol_escape_htmltag($rpsStateTitle($rps)); ?>"><?php echo $langs->trans('RiskOverviewNotConcerned'); ?></td>
                                            <?php endif; ?>
                                        <?php elseif (!empty($cell['count'])) : ?>
                                            <td class="risk-overview__cell" data-scale="<?php echo (int) $cell['scale']; ?>"
                                                title="<?php echo dol_escape_htmltag($column['name'] . ' — ' . $langs->trans($riskLevelLabels[$cell['scale']] ?? 'GreyRisk')); ?>">
                                                <?php echo (int) $cell['count']; ?>
                                            </td>
                                        <?php else : ?>
                                            <td class="risk-overview__cell is-empty" title="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewNotConcernedHelp')); ?>"><?php echo $langs->trans('RiskOverviewNotConcerned'); ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="risk-overview__legend">
                    <?php if ($panelTab == 'rps') : ?>
                        <span class="risk-overview__legend-text"><?php echo $langs->trans('RiskOverviewLegendPsychosocial'); ?></span>
                        <span class="risk-overview__keys">
                            <?php foreach ($rpsScale as $rpsLevel => $rpsMeta) : ?>
                                <span class="risk-overview__key"><i class="risk-overview__swatch" data-scale="<?php echo (int) $rpsLevel; ?>"></i><?php echo $rpsLevel . ' ' . dol_escape_htmltag($rpsMeta['label']); ?></span>
                            <?php endforeach; ?>
                            <span class="risk-overview__key"><i class="risk-overview__swatch is-empty"></i><?php echo $langs->trans('RiskOverviewNotConcerned') . ' : ' . $langs->trans('RiskOverviewNoRatingHelp'); ?></span>
                        </span>
                    <?php else : ?>
                        <span class="risk-overview__legend-text">
                            <?php echo $langs->trans('RiskOverviewLegendRisks'); ?>
                            <?php if ($panelTab == 'risk') : ?>
                                <?php echo $langs->trans('RiskOverviewLegendRisksPsychosocial'); ?>
                            <?php endif; ?>
                        </span>
                        <span class="risk-overview__keys">
                            <?php foreach ($riskLevelLabels as $riskLevel => $riskLevelLabel) : ?>
                                <span class="risk-overview__key"><i class="risk-overview__swatch" data-scale="<?php echo $riskLevel; ?>"></i><?php echo $langs->trans($riskLevelLabel); ?></span>
                            <?php endforeach; ?>
                            <span class="risk-overview__key"><i class="risk-overview__swatch is-empty"></i><?php echo $langs->trans('RiskOverviewNotConcerned') . ' : ' . $langs->trans('RiskOverviewNotConcernedHelp'); ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
