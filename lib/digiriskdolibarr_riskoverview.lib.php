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
 * Columns of the psychosocial tab: the factors F1 to F6.
 *
 * @param  array $rpsSubCategories Psychosocial factors, by position
 * @return array                   Columns: position, name, factor key (F1...)
 */
function digiriskRiskOverviewFactorColumns(array $rpsSubCategories): array
{
    global $langs;

    $columns = [];
    foreach ($rpsSubCategories as $position => $subCategory) {
        $columns[(int) $position] = [
            'position'  => (int) $position,
            'name'      => $langs->transnoentities($subCategory['label']),
            'shortName' => $langs->transnoentities($subCategory['label']),
            'factor'    => 'F' . ((int) $position + 1),
        ];
    }

    return $columns;
}

/**
 * Content of a cell of the overview, shared by the page and the Excel export.
 *
 * A risk cell holds the number of risks of the category with the level of the worst one, a
 * psychosocial cell the level of a factor or the average of the factors. A cell without anything
 * reads NC, its state telling why.
 *
 * @param  array  $row      Row of digiriskRiskOverviewRows()
 * @param  string $tab      risk, penibility or rps
 * @param  int    $position Danger category, or factor on the rps tab; -1 for the total column
 * @param  array  $columns  Columns of the tab, to add up the total
 * @return array            value (null for NC), scale (0 when the cell has no level), kind (count, level,
 *                          total) and state of an empty cell (empty, notConcerned, notAssessed,
 *                          toQualify, noWorkUnit, notRated)
 */
function digiriskRiskOverviewCell(array $row, string $tab, int $position, array $columns): array
{
    $rps = $row['rps'];

    // Psychosocial average of the element, or why there is none
    $rpsAverage = function () use ($rps): array {
        if ($rps['average'] !== null) {
            return ['value' => round($rps['average'], 1), 'scale' => (int) $rps['band'], 'kind' => 'level', 'state' => ''];
        }
        $states = [0 => 'notConcerned', 1 => 'notAssessed', -1 => 'toQualify'];
        return ['value' => null, 'scale' => 0, 'kind' => 'level', 'state' => $states[$rps['state']] ?? 'noWorkUnit'];
    };

    if ($tab == 'rps') {
        if ($position < 0) {
            return $rpsAverage();
        }
        if (isset($rps['levels'][$position])) {
            $level = (float) $rps['levels'][$position];
            return ['value' => round($level, 1), 'scale' => rps_scope_band($level), 'kind' => 'level', 'state' => ''];
        }
        $empty = $rpsAverage();
        return ['value' => null, 'scale' => 0, 'kind' => 'level', 'state' => $empty['value'] !== null ? 'notRated' : $empty['state']];
    }

    // Total of the risks of the row, psychosocial risks excluded: their column holds an average
    if ($position < 0) {
        $count = 0;
        foreach ($columns as $column) {
            if ($column['position'] != Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
                $count += $row['cells'][$column['position']]['count'] ?? 0;
            }
        }
        return ['value' => $count, 'scale' => 0, 'kind' => 'total', 'state' => ''];
    }

    if ($position == Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
        return $rpsAverage();
    }

    $cell = $row['cells'][$position] ?? [];
    if (!empty($cell['count'])) {
        return ['value' => (int) $cell['count'], 'scale' => (int) $cell['scale'], 'kind' => 'count', 'state' => ''];
    }

    return ['value' => null, 'scale' => 0, 'kind' => 'count', 'state' => 'empty'];
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
            'id'       => (int) $elementId,
            'ref'      => $node['object']->ref,
            'label'    => $node['object']->label,
            'type'     => $node['object']->element_type,
            'depth'    => (int) $node['depth'],
            'parent'   => (int) $node['object']->fk_parent,
            // Elements right under it: a groupment holding some can be folded on the page
            'children' => 0,
            'cells'    => [],
            'rps'      => ['state' => null, 'levels' => [], 'average' => null, 'band' => 0, 'reason' => ''],
        ];
    }
    foreach ($rows as $row) {
        if (isset($rows[$row['parent']])) {
            $rows[$row['parent']]['children']++;
        }
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

/**
 * Send the overview as an Excel workbook, laid out as the single document presentation workbook: one
 * sheet per tab, one row per groupment and work unit, the cells coloured as on the page.
 *
 * @param  array $overviewRows     Rows of digiriskRiskOverviewRows()
 * @param  array $overviewColumns  Columns of each tab, by tab
 * @param  array $rpsScale         Psychosocial levels, for the legend
 * @return void
 */
function digiriskRiskOverviewExportXlsx(array $overviewRows, array $overviewColumns, array $rpsScale): void
{
    global $langs, $mysoc;

    require_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpspreadsheet/src/autoloader.php';
    require_once DOL_DOCUMENT_ROOT . '/includes/Psr/autoloader.php';
    require_once PHPEXCELNEW_PATH . 'Spreadsheet.php';

    // Colours of the Digirisk cotation chips (css/scss/element/_cotation.scss): background, text
    $levelColours = [1 => ['ECECEC', '000000'], 2 => ['E9AD4F', 'FFFFFF'], 3 => ['E05353', 'FFFFFF'], 4 => ['2B2B2B', 'FFFFFF']];
    $emptyColours = ['E8F6ED', '15803D'];
    $headColours  = ['263C5C', 'FFFFFF'];

    $tabLabels   = ['risk' => 'RiskOverviewTabRisks', 'penibility' => 'RiskOverviewTabPenibility', 'rps' => 'RiskOverviewTabPsychosocial'];
    $levelLabels = [1 => 'GreyRisk', 2 => 'OrangeRisk', 3 => 'RedRisk', 4 => 'BlackRisk'];

    $fill = function (array $colours, bool $bold = false): array {
        return [
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $colours[0]]],
            'font' => ['color' => ['rgb' => $colours[1]], 'bold' => $bold],
        ];
    };
    $cellName = function (int $column, int $row): string {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row;
    };

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $spreadsheet->getProperties()->setCreator('Digirisk')->setTitle($langs->transnoentities('RiskOverviewPublicTitle') . ' - ' . $mysoc->name);
    $spreadsheet->removeSheetByIndex(0);

    foreach (DIGIRISK_RISK_OVERVIEW_TABS as $tab) {
        $columns = $overviewColumns[$tab] ?? [];
        $sheet   = $spreadsheet->createSheet();
        // A sheet name holds 31 characters at most, without []:*?/\
        $sheet->setTitle(dol_trunc(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $langs->transnoentities($tabLabels[$tab])), 31, 'right', 'UTF-8', 1));

        // Heads: element, reference, total, then one narrow column per category or factor, its name upwards
        $heads = [$langs->transnoentities('RiskOverviewElement'), $langs->transnoentities('Ref'), $langs->transnoentities($tab == 'rps' ? 'RiskOverviewPsychosocialAverage' : 'RiskOverviewRiskCount')];
        foreach ($columns as $column) {
            $heads[] = ($tab == 'rps' ? $column['factor'] . ' - ' : '') . $column['shortName'];
        }
        foreach ($heads as $index => $head) {
            $sheet->setCellValue($cellName($index + 1, 1), $head);
        }
        $lastColumn = count($heads);
        $sheet->getStyle($cellName(1, 1) . ':' . $cellName($lastColumn, 1))->applyFromArray($fill($headColours, true) + [
            'alignment' => ['wrapText' => true, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM],
        ]);
        $sheet->getStyle($cellName(4, 1) . ':' . $cellName($lastColumn, 1))->getAlignment()->setTextRotation(90)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight($tab == 'rps' ? 120 : 170);
        $sheet->getColumnDimension('A')->setWidth(44);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(12);
        for ($index = 4; $index <= $lastColumn; $index++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index))->setWidth($tab == 'rps' ? 12 : 7);
        }

        // One row per element, indented as in the tree
        $rowIndex = 1;
        foreach ($overviewRows as $overviewRow) {
            $rowIndex++;
            $sheet->setCellValue($cellName(1, $rowIndex), $overviewRow['label']);
            $sheet->setCellValue($cellName(2, $rowIndex), $overviewRow['ref']);
            $sheet->getStyle($cellName(1, $rowIndex))->getAlignment()->setIndent((int) $overviewRow['depth']);
            if ($overviewRow['type'] == 'groupment') {
                $sheet->getStyle($cellName(1, $rowIndex) . ':' . $cellName(2, $rowIndex))->getFont()->setBold(true);
            }

            $positions = array_merge([-1], array_keys($columns));
            foreach ($positions as $index => $position) {
                $cell       = digiriskRiskOverviewCell($overviewRow, $tab, (int) $position, $columns);
                $coordinate = $cellName($index + 3, $rowIndex);
                if ($cell['value'] === null) {
                    $sheet->setCellValue($coordinate, $langs->transnoentities('RiskOverviewNotConcerned'));
                    $sheet->getStyle($coordinate)->applyFromArray($fill($emptyColours));
                } else {
                    $sheet->setCellValue($coordinate, $cell['value']);
                    if ($cell['scale'] > 0) {
                        $sheet->getStyle($coordinate)->applyFromArray($fill($levelColours[$cell['scale']], true));
                    } else {
                        $sheet->getStyle($coordinate)->getFont()->setBold(true);
                    }
                }
            }
        }

        $sheet->getStyle($cellName(3, 2) . ':' . $cellName($lastColumn, $rowIndex))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($cellName(1, 1) . ':' . $cellName($lastColumn, $rowIndex))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D9D9D9');
        $sheet->freezePane('D2');

        // Legend under the table, as on the page
        $rowIndex += 2;
        $sheet->setCellValue($cellName(1, $rowIndex), $langs->transnoentities($tab == 'rps' ? 'RiskOverviewLegendPsychosocial' : 'RiskOverviewLegendRisks'));
        $legend = [];
        foreach ($levelColours as $level => $colours) {
            $legend[] = [$colours, $tab == 'rps' ? $level . ' ' . $rpsScale[$level]['label'] : $langs->transnoentities($levelLabels[$level])];
        }
        $legend[] = [$emptyColours, $langs->transnoentities('RiskOverviewNotConcerned') . ' : ' . $langs->transnoentities($tab == 'rps' ? 'RiskOverviewNoRatingHelp' : 'RiskOverviewNotConcernedHelp')];
        foreach ($legend as $key) {
            $rowIndex++;
            $sheet->getStyle($cellName(3, $rowIndex))->applyFromArray($fill($key[0]));
            $sheet->setCellValue($cellName(1, $rowIndex), $key[1]);
        }

        // Printed on one page width, landscape, with the company and the date
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getHeaderFooter()->setOddHeader('&L' . $mysoc->name . '&R' . dol_print_date(dol_now(), 'day'));
    }
    $spreadsheet->setActiveSheetIndex(0);

    $fileName = dol_sanitizeFileName(dol_string_unaccent($langs->transnoentities('RiskOverviewPublicTitle') . ' ' . $mysoc->name . ' ' . dol_print_date(dol_now(), '%Y-%m-%d'))) . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $fileName) . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
}
