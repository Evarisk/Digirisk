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
 * \file    lib/digiriskdolibarr_riskoverview.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Functions of the public overview of the risks: one row per groupment and work unit, one
 *          column per danger category, the number of risks on the colour of the worst one.
 */

/**
 * Danger categories of each tab, by position in js/json/dangerCategories.json.
 * The risks of the INRS brochure ED 840, then the strenuousness factors (C2P and the other ones the
 * single document has to list), vibrations being in both.
 */
const DIGIRISK_RISK_OVERVIEW_CATEGORIES = [
    'risk'       => [0, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 31],
    'penibility' => [28, 22, 18, 30, 29, 23, 27, 24, 25, 26],
];

/**
 * Tabs of the overview, in their display order.
 */
const DIGIRISK_RISK_OVERVIEW_TABS = ['risk', 'penibility', 'rps'];

/**
 * Whether the public overview is open: enabled in the setup, with a key to put in the link.
 *
 * @return bool
 */
function digiriskRiskOverviewPublicEnabled(): bool
{
    return getDolGlobalInt('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_INTERFACE') && dol_strlen(getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY')) > 0;
}

/**
 * Check the key given to the public overview.
 *
 * @param  string $key Key read from the URL
 * @return bool        True when the overview is open and the key matches
 */
function digiriskRiskOverviewCheckPublicKey(string $key): bool
{
    return digiriskRiskOverviewPublicEnabled() && hash_equals(getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY'), $key);
}

/**
 * Link to the public overview, to share or to print as a QR code.
 *
 * @param  string $tab Tab to open, empty for the first one
 * @return string      Absolute URL, empty when the overview is closed
 */
function digiriskRiskOverviewPublicUrl(string $tab = ''): string
{
    global $conf;

    if (!digiriskRiskOverviewPublicEnabled()) {
        return '';
    }

    $params = ['entity' => $conf->entity, 'key' => getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY')];
    if (in_array($tab, DIGIRISK_RISK_OVERVIEW_TABS, true)) {
        $params['tab'] = $tab;
    }

    return dol_buildpath('/custom/digiriskdolibarr/public/digiriskelement/digiriskelement_risk_overview.php', 3) . '?' . http_build_query($params);
}

/**
 * Columns of a tab of danger categories, in the order of the tab.
 *
 * @param  string $tab risk or penibility
 * @return array       Columns: position, name, short name (without "Risques liés aux"...), pictogram file
 */
function digiriskRiskOverviewCategoryColumns(string $tab): array
{
    $categories = [];
    foreach (Risk::getDangerCategories() as $category) {
        $categories[(int) $category['position']] = $category;
    }

    $columns = [];
    foreach (DIGIRISK_RISK_OVERVIEW_CATEGORIES[$tab] ?? [] as $position) {
        if (!isset($categories[$position])) {
            continue;
        }
        $name = $categories[$position]['name'];

        $columns[$position] = [
            'position'  => $position,
            'name'      => $name,
            // The column heads are narrow: "Risques liés aux ambiances thermiques" reads "Ambiances thermiques"
            'shortName' => dol_ucfirst(preg_replace('/^Risques\s+(et nuisances\s+)?(liés\s+(aux|au|à la|à l\')\s*|de\s+|d\'|d’)/u', '', $name)),
            'picto'     => $categories[$position]['thumbnail_name'] . '.png',
        ];
    }

    return $columns;
}

/**
 * Rows of the overview: every active groupment and work unit of the entity, in the order of the tree,
 * with the risks of each category and the psychosocial assessment.
 *
 * The risk cells only count the risks of the element itself, as the risk list of the element does. A
 * psychosocial cell reads the consolidated assessment of the work unit; a groupment shows the average
 * of its assessed work units, which carry the psychosocial risks.
 *
 * @param  DoliDB $db                        Database handler
 * @param  array  $activeDigiriskElementList Active elements of the entity, by id
 * @param  array  $rpsLines                  Psychosocial lines of the work units, from digiriskdolibarr_rps_scope_data.tpl.php
 *                                           (which also declares rps_scope_band(), used here)
 * @param  array  $rpsSubCategories          Psychosocial factors F1 to F6, by position
 * @return array                             Rows, in the order of the tree
 * @throws Exception
 */
function digiriskRiskOverviewRows(DoliDB $db, array $activeDigiriskElementList, array $rpsLines, array $rpsSubCategories): array
{
    $rows = [];
    foreach (flatten_tree(recurse_tree(0, 0, $activeDigiriskElementList)) as $elementId => $node) {
        $rows[$elementId] = [
            'id'     => (int) $elementId,
            'ref'    => $node['object']->ref,
            'label'  => $node['object']->label,
            'type'   => $node['object']->element_type,
            'depth'  => (int) $node['depth'],
            'parent' => (int) $node['object']->fk_parent,
            'cells'  => [],
            'rps'    => ['state' => null, 'levels' => [], 'average' => null, 'band' => 0, 'reason' => ''],
        ];
    }

    // Risks of the element itself, with their current assessment: count and worst level per category
    $risk           = new Risk($db);
    $riskAssessment = new RiskAssessment($db);
    $riskInfos      = $risk->loadRiskInfos(['filterRisk' => " AND t.type = 'risk'"]);
    foreach ($riskInfos['current']['risks'] as $currentRisk) {
        $elementId = (int) $currentRisk->fk_element;
        if (!isset($rows[$elementId])) {
            continue;
        }

        $riskAssessment->cotation = $currentRisk->riskAssessmentCotation;
        $scale                    = $riskAssessment->getEvaluationScale();
        $category                 = (int) $currentRisk->category;

        $cell = $rows[$elementId]['cells'][$category] ?? ['count' => 0, 'scale' => 0];
        $cell['count']++;
        $cell['scale'] = max($cell['scale'], $scale);

        $rows[$elementId]['cells'][$category] = $cell;
    }

    // Psychosocial assessment of the work units, gathered on every groupment above them
    $groupments = [];
    foreach ($rpsLines as $rpsLine) {
        $elementId = (int) $rpsLine['id'];
        if (!isset($rows[$elementId])) {
            continue;
        }

        $rows[$elementId]['rps'] = [
            'state'   => (int) $rpsLine['state'],
            'levels'  => $rpsLine['levels'],
            'average' => $rpsLine['done'] ? (float) $rpsLine['average'] : null,
            'band'    => $rpsLine['done'] ? (int) $rpsLine['band'] : 0,
            // Justification of a work unit out of scope, expected by the single document
            'reason'  => $rpsLine['state'] === 0 ? (string) $rpsLine['reason'] : '',
        ];

        $parentId = $rows[$elementId]['parent'];
        $guard    = 0;
        while (isset($rows[$parentId]) && $guard++ < 50) {
            $groupments[$parentId]['states'][] = (int) $rpsLine['state'];
            if ($rpsLine['done']) {
                $groupments[$parentId]['averages'][] = (float) $rpsLine['average'];
                foreach ($rpsLine['levels'] as $factor => $level) {
                    $groupments[$parentId]['levels'][$factor][] = (int) $level;
                }
            }
            $parentId = $rows[$parentId]['parent'];
        }
    }

    foreach ($groupments as $groupmentId => $groupment) {
        $averages = $groupment['averages'] ?? [];
        $levels   = [];
        foreach ($groupment['levels'] ?? [] as $factor => $factorLevels) {
            $levels[$factor] = array_sum($factorLevels) / count($factorLevels);
        }

        // Concerned when one of its work units is, out of scope when all of them are
        $states = array_unique($groupment['states']);
        $state  = in_array(1, $states, true) ? 1 : ($states === [0] ? 0 : -1);

        $rows[$groupmentId]['rps'] = [
            'state'   => $state,
            'levels'  => $levels,
            'average' => empty($averages) ? null : array_sum($averages) / count($averages),
            'band'    => empty($averages) ? 0 : rps_scope_band(array_sum($averages) / count($averages)),
            'reason'  => '',
        ];
    }

    return array_values($rows);
}

/**
 * Format an average of psychosocial levels for a cell: one decimal, without a trailing ",0".
 *
 * @param  float  $value Average from 1 to 4
 * @return string        "2,5", "3"
 */
function digiriskRiskOverviewFormatLevel(float $value): string
{
    $rounded = round($value, 1);

    return floor($rounded) == $rounded ? (string) (int) $rounded : price($rounded, 0, '', 0, 1, 1);
}
