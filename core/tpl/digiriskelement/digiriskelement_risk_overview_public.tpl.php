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
 *          a psychosocial cell the level of the factor or the average of the factors
 *          (digiriskRiskOverviewCell(), shared with the Excel export). A number of risks lists them
 *          on hover (digiriskelement_risk_overview_tooltip.tpl.php), a psychosocial average its
 *          factors (digiriskelement_risk_overview_psychosocial_tooltip.tpl.php). The register tab
 *          counts the tickets of each register (digiriskelement_risk_overview_register_cell.tpl.php).
 *          With a PIN code in the setup, a visitor who has not given it yet only gets its field.
 *
 * Variables : $accessAllowed, $pinAsked, $pinError, $key, $tab, $tabUrl, $exportUrl, $overviewTabNames,
 *             $overviewRows, $overviewColumns, $rpsScale, $conf, $langs, $mysoc
 *             (public/digiriskelement/digiriskelement_risk_overview.php)
 */

// The tabs the setup shows
$overviewTabs = array_intersect_key([
    'risk'       => ['icon' => 'fa-exclamation-triangle', 'label' => 'RiskOverviewTabRisks'],
    'penibility' => ['icon' => 'fa-hard-hat',             'label' => 'RiskOverviewTabPenibility'],
    'rps'        => ['icon' => 'fa-brain',                'label' => 'RiskOverviewTabPsychosocial'],
    'register'   => ['icon' => 'fa-ticket-alt',           'label' => 'RiskOverviewTabRegisters'],
], array_flip($overviewTabNames));
$riskLevelLabels = [1 => 'GreyRisk', 2 => 'OrangeRisk', 3 => 'RedRisk', 4 => 'BlackRisk'];

// Why a cell reads NC, in its tooltip
$emptyCellTitles = [
    'empty'        => $langs->trans('RiskOverviewNotConcernedHelp'),
    'notConcerned' => $langs->trans('RiskOverviewPsychosocialNotConcerned'),
    'notAssessed'  => $langs->trans('RiskOverviewNotAssessed'),
    'toQualify'    => $langs->trans('RiskOverviewToQualify'),
    'noWorkUnit'   => $langs->trans('RiskOverviewNoWorkUnit'),
    'notRated'     => $langs->trans('RiskOverviewFactorNotRated'),
];

?>
<div class="public-card__container risk-overview<?php echo $accessAllowed && !$pinAsked ? '' : ' risk-overview--closed'; ?>" data-public-interface="true"
     data-label-fold="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewFold')); ?>"
     data-label-unfold="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewUnfold')); ?>">
    <?php if (!$accessAllowed) : ?>
        <div class="risk-overview__closed">
            <i class="fas fa-lock"></i>
            <div><?php echo $langs->trans('RiskOverviewPublicClosed'); ?></div>
        </div>
    <?php elseif ($pinAsked) : ?>
        <form class="risk-overview__pin" method="POST" action="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="action" value="checkpin">
            <input type="hidden" name="entity" value="<?php echo (int) $conf->entity; ?>">
            <input type="hidden" name="key" value="<?php echo dol_escape_htmltag($key); ?>">
            <input type="hidden" name="tab" value="<?php echo dol_escape_htmltag($tab); ?>">
            <i class="fas fa-lock"></i>
            <div class="risk-overview__pin-title"><?php echo $langs->trans('RiskOverviewPublicTitle'); ?></div>
            <label for="risk-overview-pin"><?php echo $langs->trans('RiskOverviewPinPrompt'); ?></label>
            <input id="risk-overview-pin" class="risk-overview__pin-input" type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="8" autocomplete="off" required autofocus>
            <?php if ($pinError == 'wrong' || $pinError == 'locked') : ?>
                <div class="risk-overview__pin-error" role="alert"><?php echo $langs->trans($pinError == 'locked' ? 'RiskOverviewPinLocked' : 'RiskOverviewPinWrong'); ?></div>
            <?php endif; ?>
            <button type="submit" class="wpeo-button button-blue"><?php echo $langs->trans('RiskOverviewPinSubmit'); ?></button>
        </form>
    <?php else : ?>
        <!-- Same header as the other public interfaces (electronic signature, registers): what the page is,
             for whom, and the object it is about, with its download button -->
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
                    <div class="objet-actions">
                        <a class="wpeo-button button-square-40 button-rounded button-blue wpeo-tooltip-event" href="<?php echo dol_escape_htmltag($exportUrl); ?>"
                           aria-label="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewExport')); ?>" data-direction="left">
                            <i class="fas fa-file-excel button-icon"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="risk-overview__toolbar">
            <?php // A tab alone needs no switching between tabs ?>
            <?php if (count($overviewTabs) > 1) : ?>
                <nav class="risk-overview__tabs">
                    <?php foreach ($overviewTabs as $overviewTab => $overviewTabMeta) : ?>
                        <a class="risk-overview__tab<?php echo $overviewTab == $tab ? ' is-active' : ''; ?>" data-tab="<?php echo $overviewTab; ?>" href="<?php echo dol_escape_htmltag($tabUrl($overviewTab)); ?>">
                            <i class="fas <?php echo $overviewTabMeta['icon']; ?>"></i> <?php echo $langs->trans($overviewTabMeta['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <div class="risk-overview__fold-actions">
                <button type="button" class="risk-overview__fold-all"><i class="fas fa-compress-alt"></i> <?php echo $langs->trans('RiskOverviewFoldAll'); ?></button>
                <button type="button" class="risk-overview__unfold-all"><i class="fas fa-expand-alt"></i> <?php echo $langs->trans('RiskOverviewUnfoldAll'); ?></button>
            </div>
        </div>

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
                            <?php elseif ($panelTab == 'register') : ?>
                                <th class="risk-overview__total" scope="col" title="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewTicketCountHelp')); ?>">
                                    <?php echo $langs->trans('RiskOverviewTicketCount'); ?>
                                </th>
                            <?php else :
                                // The total of the risk tab counts the psychosocial line only when the setup shows the psychosocial risks
                                $totalHelp = $panelTab != 'risk' ? 'RiskOverviewRiskCountHelp' : (isset($columns[Risk::PSYCHOSOCIAL_CATEGORY_POSITION]) ? 'RiskOverviewRiskCountListHelp' : 'RiskOverviewRiskCountListNoPsychosocialHelp'); ?>
                                <th class="risk-overview__total" scope="col" title="<?php echo dol_escape_htmltag($langs->trans($totalHelp)); ?>">
                                    <?php echo $langs->trans('RiskOverviewRiskCount'); ?>
                                </th>
                            <?php endif; ?>
                            <?php foreach ($columns as $column) : ?>
                                <th class="risk-overview__head" scope="col" title="<?php echo dol_escape_htmltag($column['name']); ?>">
                                    <?php if ($panelTab == 'rps') : ?>
                                        <span class="risk-overview__factor"><?php echo $column['factor']; ?></span>
                                        <span class="risk-overview__head-name"><?php echo dol_escape_htmltag($column['shortName']); ?></span>
                                    <?php elseif ($panelTab == 'register') : ?>
                                        <span class="risk-overview__head-name"><?php echo dol_escape_htmltag($column['shortName']); ?></span>
                                    <?php else : ?>
                                        <span class="risk-overview__head-name"><?php echo dol_escape_htmltag($column['shortName']); ?></span>
                                        <img class="risk-overview__picto" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . dol_escape_htmltag($column['picto']); ?>" alt="">
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($overviewRows as $overviewRow) : ?>
                            <tr class="risk-overview__row risk-overview__row--<?php echo $overviewRow['type'] == 'groupment' ? 'groupment' : 'workunit'; ?>"
                                data-id="<?php echo (int) $overviewRow['id']; ?>" data-parent="<?php echo (int) $overviewRow['parent']; ?>">
                                <th class="risk-overview__element" scope="row" data-depth="<?php echo min((int) $overviewRow['depth'], 5); ?>">
                                    <?php if ($overviewRow['children'] > 0) : ?>
                                        <button type="button" class="risk-overview__toggle" aria-expanded="true" title="<?php echo dol_escape_htmltag($langs->trans('RiskOverviewFold')); ?>">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    <?php else : ?>
                                        <span class="risk-overview__toggle-space"></span>
                                    <?php endif; ?>
                                    <span class="risk-overview__name">
                                        <span class="risk-overview__ref"><?php echo dol_escape_htmltag($overviewRow['ref']); ?></span>
                                        <span class="risk-overview__label"><?php echo dol_escape_htmltag($overviewRow['label']); ?></span>
                                    </span>
                                </th>

                                <?php
                                foreach (array_merge([-1], array_keys($columns)) as $position) :
                                    $cell = digiriskRiskOverviewCell($overviewRow, $panelTab, (int) $position, $columns);
                                    // A number of tickets has a cell of its own, with no level
                                    if ($panelTab == 'register') {
                                        require __DIR__ . '/digiriskelement_risk_overview_register_cell.tpl.php';
                                        continue;
                                    }
                                    $totalClass = $position < 0 ? 'risk-overview__total ' : '';
                                    // A number opens the risk list of the element, the worst risks first, filtered on
                                    // the category of the cell so that the list holds what the cell counts
                                    $elementUrl = digiriskRiskOverviewElementUrl((int) $overviewRow['id'], (int) $cell['category']);
                                    $openTitle  = $cell['category'] >= 0
                                        ? $langs->trans('RiskOverviewOpenCategoryRisks', $columns[$position]['shortName'], $overviewRow['ref'])
                                        : $langs->trans('RiskOverviewOpenRisks', $overviewRow['ref']);
                                    // A number of risks shows them on hover, the worst first: its title then only
                                    // stays for screen readers, or the browser would show it over the tooltip
                                    $cellRisks      = in_array($cell['kind'], ['count', 'total'], true) ? digiriskRiskOverviewCellRisks($overviewRow, $panelTab, (int) $position, $columns) : ['risks' => []];
                                    $hasRiskTooltip = !empty($cellRisks['risks']);
                                    // A psychosocial average (its column of the risk tab, the average of the psychosocial
                                    // tab) shows the factors it is made of, F1 to F6
                                    $hasPsychosocialTooltip = $cell['kind'] == 'level' && $cell['value'] !== null && ($position < 0 || $position == Risk::PSYCHOSOCIAL_CATEGORY_POSITION);
                                    $titleAttribute         = $hasRiskTooltip || $hasPsychosocialTooltip ? 'aria-label' : 'title';
                                    $tooltipHint    = $cell['link'] ? $openTitle : '';
                                    $tooltipTitle   = $position >= 0 ? $columns[$position]['name'] : $langs->trans($panelTab == 'risk' ? 'RiskOverviewTooltipAllCategories' : 'RiskOverviewTabPenibility'); ?>
                                    <?php if ($cell['kind'] == 'total') : ?>
                                        <td class="risk-overview__total<?php echo $hasRiskTooltip ? ' has-risk-tooltip' : ''; ?>">
                                            <?php if ($cell['link']) : ?>
                                                <a class="risk-overview__link" href="<?php echo dol_escape_htmltag($elementUrl); ?>" target="_blank" rel="noopener"
                                                   <?php echo $titleAttribute; ?>="<?php echo dol_escape_htmltag($openTitle); ?>"><?php echo (int) $cell['value']; ?></a>
                                            <?php else : ?>
                                                <?php echo (int) $cell['value']; ?>
                                            <?php endif; ?>
                                            <?php if ($hasRiskTooltip) {
                                                require __DIR__ . '/digiriskelement_risk_overview_tooltip.tpl.php';
                                            } ?>
                                        </td>
                                    <?php elseif ($cell['value'] === null) :
                                        $cellTitle = $emptyCellTitles[$cell['state']] ?? '';
                                        if ($cell['state'] == 'notConcerned' && dol_strlen($overviewRow['rps']['reason'])) {
                                            $cellTitle .= ' : ' . $overviewRow['rps']['reason'];
                                        } ?>
                                        <td class="<?php echo $totalClass; ?>risk-overview__cell is-empty" title="<?php echo dol_escape_htmltag($cellTitle); ?>"><?php echo $langs->trans('RiskOverviewNotConcerned'); ?></td>
                                    <?php else :
                                        // A count reads the level of the worst risk, a psychosocial value its level of the scale
                                        // The psychosocial column of the risk tab shows the rounded average: the exact one in its tooltip
                                        $cellName  = $position < 0 ? $langs->trans('RiskOverviewPsychosocialAverage') : ($position == Risk::PSYCHOSOCIAL_CATEGORY_POSITION && $panelTab != 'rps' ? $langs->trans('RiskOverviewPsychosocialAverageHelp') . ' : ' . digiriskRiskOverviewFormatLevel((float) $overviewRow['rps']['average']) : $columns[$position]['name']);
                                        $cellLevel = $cell['kind'] == 'count' ? $langs->trans($riskLevelLabels[$cell['scale']] ?? 'GreyRisk') : $rpsScale[$cell['scale']]['range'] . ' · ' . $rpsScale[$cell['scale']]['label']; ?>
                                        <td class="<?php echo $totalClass; ?>risk-overview__cell<?php echo $cell['kind'] == 'level' ? ' is-psychosocial' : ''; ?><?php echo $hasRiskTooltip || $hasPsychosocialTooltip ? ' has-risk-tooltip' : ''; ?>" data-scale="<?php echo (int) $cell['scale']; ?>">
                                            <a class="risk-overview__link" href="<?php echo dol_escape_htmltag($elementUrl); ?>" target="_blank" rel="noopener"
                                               <?php echo $titleAttribute; ?>="<?php echo dol_escape_htmltag($cellName . ' — ' . $cellLevel . '. ' . $openTitle); ?>"><?php echo $cell['decimals'] ? digiriskRiskOverviewFormatLevel((float) $cell['value']) : (int) $cell['value']; ?></a>
                                            <?php if ($hasRiskTooltip) {
                                                require __DIR__ . '/digiriskelement_risk_overview_tooltip.tpl.php';
                                            } elseif ($hasPsychosocialTooltip) {
                                                $rpsColumns = $overviewColumns['rps'] ?? [];
                                                require __DIR__ . '/digiriskelement_risk_overview_psychosocial_tooltip.tpl.php';
                                            } ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="risk-overview__legend">
                    <?php if ($panelTab == 'register') : ?>
                        <span class="risk-overview__legend-text"><?php echo $langs->trans('RiskOverviewLegendRegisters'); ?></span>
                    <?php elseif ($panelTab == 'rps') : ?>
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
                            <?php if (isset($columns[Risk::PSYCHOSOCIAL_CATEGORY_POSITION])) : ?>
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
