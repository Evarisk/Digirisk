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
 * Risk list of a groupment or work unit in the application, the worst risks first: where a number of
 * the overview leads. Only a user logged in to Dolibarr reaches it, a visitor gets the login page.
 *
 * @param  int    $elementId Groupment or work unit
 * @param  int    $category  Danger category the list is filtered on, -1 for the whole list
 * @return string            URL of digiriskelement_risk.php sorted by descending cotation
 */
function digiriskRiskOverviewElementUrl(int $elementId, int $category = -1): string
{
    $params = ['id' => $elementId, 'sortfield' => 'evaluation.cotation', 'sortorder' => 'DESC'];
    if ($category >= 0) {
        $params['search_category'] = $category;
    }

    return dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?' . http_build_query($params);
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
 * A number leads to the risk list of the element, so it must count what the list shows: a category
 * cell opens the list filtered on its category, the total of the risk tab the whole list.
 *
 * @param  array  $row      Row of digiriskRiskOverviewRows()
 * @param  string $tab      risk, penibility or rps
 * @param  int    $position Danger category, or factor on the rps tab; -1 for the total column
 * @param  array  $columns  Columns of the tab, to add up the total
 * @return array            value (null for NC), scale (0 when the cell has no level), kind (count, level,
 *                          total), decimals shown, state of an empty cell (empty, notConcerned,
 *                          notAssessed, toQualify, noWorkUnit, notRated), link (whether the number opens
 *                          the risk list) and category (the list filter, -1 for the whole list)
 */
function digiriskRiskOverviewCell(array $row, string $tab, int $position, array $columns): array
{
    $rps  = $row['rps'];
    $cell = ['value' => null, 'scale' => 0, 'kind' => 'count', 'decimals' => 0, 'state' => '', 'link' => false, 'category' => -1];

    // Psychosocial average of the element, or why there is none: it opens the whole list, at the top
    // of which the psychosocial block shows the factors. The psychosocial tab gives it with a decimal;
    // among the counts of the risk tab it reads as a whole number, the average rounded, which is the
    // level of its colour (the levels change at .5)
    $rpsAverage = function (int $decimals) use ($rps, $cell): array {
        if ($rps['average'] !== null) {
            return array_merge($cell, ['value' => round($rps['average'], $decimals), 'scale' => (int) $rps['band'], 'kind' => 'level', 'decimals' => $decimals, 'link' => true]);
        }
        $states = [0 => 'notConcerned', 1 => 'notAssessed', -1 => 'toQualify'];
        return array_merge($cell, ['kind' => 'level', 'state' => $states[$rps['state']] ?? 'noWorkUnit']);
    };

    if ($tab == 'rps') {
        if ($position < 0) {
            return $rpsAverage(1);
        }
        if (isset($rps['levels'][$position])) {
            $level = (float) $rps['levels'][$position];
            return array_merge($cell, ['value' => round($level, 1), 'scale' => rps_scope_band($level), 'kind' => 'level', 'decimals' => 1, 'link' => true]);
        }
        $empty = $rpsAverage(1);
        return array_merge($cell, ['kind' => 'level', 'state' => $empty['value'] !== null ? 'notRated' : $empty['state']]);
    }

    if ($position < 0) {
        // Risk tab: the lines of the risk list of the element, every category, the psychosocial risks
        // being a single line there as well
        if ($tab == 'risk') {
            $count = 0;
            foreach ($row['cells'] as $category => $categoryCell) {
                if ($category != Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
                    $count += $categoryCell['count'];
                }
            }
            // A work unit out of the psychosocial scope does not show its psychosocial line
            if ($row['psychosocialLine'] && ($row['type'] != 'workunit' || $rps['state'] === null || $rps['state'] === 1)) {
                $count++;
            }
            return array_merge($cell, ['value' => $count, 'kind' => 'total', 'link' => true]);
        }

        // Other tabs: the risks of their categories, which no single list shows
        $count = 0;
        foreach ($columns as $column) {
            $count += $row['cells'][$column['position']]['count'] ?? 0;
        }
        return array_merge($cell, ['value' => $count, 'kind' => 'total']);
    }

    if ($position == Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
        return $rpsAverage(0);
    }

    $categoryCell = $row['cells'][$position] ?? [];
    if (!empty($categoryCell['count'])) {
        return array_merge($cell, ['value' => (int) $categoryCell['count'], 'scale' => (int) $categoryCell['scale'], 'link' => true, 'category' => $position]);
    }

    return array_merge($cell, ['state' => 'empty']);
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
            // Whether its risk list holds a psychosocial line
            'psychosocialLine' => false,
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

        // The risk list of the element shows one psychosocial line, for a risk without a factor: the
        // risks of a factor are the detail of the assessment (digiriskdolibarr_risklist_view.tpl.php)
        if ($category == Risk::PSYCHOSOCIAL_CATEGORY_POSITION && ($currentRisk->sub_category === null || $currentRisk->sub_category === '' || (int) $currentRisk->sub_category < 0)) {
            $rows[$elementId]['psychosocialLine'] = true;
        }
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
 * Format a psychosocial level or average for a cell: always one decimal, so that a level and an
 * average read alike in the column.
 *
 * @param  float  $value Level or average from 1 to 4
 * @return string        "2,5", "3,0"
 */
function digiriskRiskOverviewFormatLevel(float $value): string
{
    return price(round($value, 1), 0, '', 0, 1, 1);
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
                    // A psychosocial value of the psychosocial sheet keeps its decimal, as on the page: 3,0 rather than 3
                    if ($cell['decimals']) {
                        $sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('0.0');
                    }
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
