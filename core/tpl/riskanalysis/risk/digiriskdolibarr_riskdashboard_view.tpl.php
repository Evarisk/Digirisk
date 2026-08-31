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
 * \file    core/tpl/riskanalysis/risk/digiriskdolibarr_riskdashboard_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Dashboard shown above the risk list: number of risks by level of the cotation scale
 *
 * Variables expected from calling PHP:
 * - $risk                      Risk      Risk object, holds the cotation scale
 * - $riskCountsByCotationLevel array     Counts from Risk::getRiskCountsByCotationLevel()
 * - $riskType                  string    Type of risk listed ('risk' or 'riskenvironmental')
 * - $searchCotation            int       Level the list is currently filtered on, 0 for none
 * - $langs                     Translate Translation object
 */

$riskListUrl = dol_buildpath('/digiriskdolibarr/view/digiriskelement/risk_list.php', 1) . '?mainmenu=digiriskdolibarr&risk_type=' . urlencode($riskType);

// The total opens the unfiltered list, each other tile filters the list on its own level of the scale.
// Clicking the level the list already shows removes the filter, so a tile toggles its own criteria.
$riskDashboardTiles = [[
    'scale'    => 'total',
    'label'    => $langs->transnoentities(ucfirst($riskType) . 's'),
    'count'    => $riskCountsByCotationLevel['total'],
    'selected' => empty($searchCotation),
    'url'      => $riskListUrl
]];

foreach ($risk->getCotations() as $cotationLevel => $cotation) {
    $riskDashboardTiles[] = [
        'scale'    => $cotationLevel,
        'label'    => $cotation['label'],
        'count'    => $riskCountsByCotationLevel[$cotationLevel],
        'selected' => $searchCotation == $cotationLevel,
        'url'      => $riskListUrl . ($searchCotation == $cotationLevel ? '' : '&search_cotation=' . $cotationLevel)
    ];
}

// Risks left to assess only clutter the dashboard of a document unique where every risk is assessed.
// Sorting the list on a cotation leaves them out, so their tile sorts the list on the risk reference instead.
if ($riskCountsByCotationLevel[Risk::COTATION_NOT_ASSESSED] > 0) {
    $riskDashboardTiles[] = [
        'scale'    => 'notassessed',
        'label'    => $langs->transnoentities('RisksNotAssessed'),
        'count'    => $riskCountsByCotationLevel[Risk::COTATION_NOT_ASSESSED],
        'selected' => $searchCotation == Risk::COTATION_NOT_ASSESSED,
        'url'      => $riskListUrl . ($searchCotation == Risk::COTATION_NOT_ASSESSED ? '' : '&search_cotation=' . Risk::COTATION_NOT_ASSESSED . '&sortfield=r.ref&sortorder=ASC')
    ];
}
?>

<div class="fichecenter risk-dashboard<?php echo ($riskType == 'riskenvironmental' ? ' risk-dashboard--riskenvironmental' : ''); ?>">
    <?php foreach ($riskDashboardTiles as $riskDashboardTile) : ?>
        <a class="risk-dashboard__tile<?php echo ($riskDashboardTile['selected'] ? ' selected' : ''); ?>" href="<?php echo $riskDashboardTile['url']; ?>">
            <span class="risk-dashboard__count" data-scale="<?php echo $riskDashboardTile['scale']; ?>"><?php echo $riskDashboardTile['count']; ?></span>
            <span class="risk-dashboard__label"><?php echo dol_escape_htmltag($riskDashboardTile['label']); ?></span>
        </a>
    <?php endforeach; ?>
</div>
