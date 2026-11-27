<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    class/digiriskrisklibraryexport.class.php
 * \ingroup digiriskdolibarr
 * \brief   Excel export of the risk library (#5838): the grid of the GP/UT and the models, then the models
 */

// Load Saturne libraries
dol_include_once('/saturne/class/saturnexlsxexport.class.php');

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../lib/digiriskdolibarr_risklibrary.lib.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Class for DigiriskRiskLibraryExport
 *
 * The grid of the risk library tab, one row per GP/UT in the order of the tree and one column per model, each cell as
 * on the page: the current cotation of the risk of the GP/UT in the colour of its level, NC with its reason, or ? for
 * a GP/UT to qualify. Its last row gives the number of GP/UT concerned by each model.
 */
class DigiriskRiskLibraryExport extends SaturneXlsxExport
{
    /**
     * Colours of each level of the cotation scale, those of the cotation chips of the risk lists: background, text
     */
    public const LEVEL_COLOURS = [1 => ['ECECEC', '000000'], 2 => ['E9AD4F', 'FFFFFF'], 3 => ['E05353', 'FFFFFF'], 4 => ['2B2B2B', 'FFFFFF']];

    /**
     * Colours of the other states of a cell, those of the page (css/scss/page/_page-risklibrary.scss): background, text
     */
    public const STATE_COLOURS = [
        'notconcerned' => ['F3F4F6', '6B7280'],
        'toqualify'    => ['FFFFFF', '9CA3AF'],
        'missing'      => ['FEF3C7', 'B45309'],
    ];

    /**
     * @var Risk Risk, for the level of a cotation
     */
    protected Risk $risk;

    /**
     * Build the workbook of the library and send it to the browser
     *
     * @param  array<int, stdClass>             $models                 Models, from digiriskdolibarr_risklibrary_fetch_all()
     * @param  array<int, array<int, stdClass>> $scopes                 Assignments, from digiriskdolibarr_risklibrary_fetch_scopes()
     * @param  array<int, DigiriskElement>      $activeDigiriskElements Active GP/UT, by ID
     * @param  array<int, array<string, mixed>> $dangerCategories       Danger categories, by position
     * @param  string                           $fileName               Name of the file, without extension
     * @return void
     */
    public function export(array $models, array $scopes, array $activeDigiriskElements, array $dangerCategories, string $fileName): void
    {
        $this->build($models, $scopes, $activeDigiriskElements, $dangerCategories);
        $this->send($fileName);
    }

    /**
     * Build the workbook: a summary, the grid, then the models
     *
     * @param  array<int, stdClass>             $models                 Models, from digiriskdolibarr_risklibrary_fetch_all()
     * @param  array<int, array<int, stdClass>> $scopes                 Assignments, from digiriskdolibarr_risklibrary_fetch_scopes()
     * @param  array<int, DigiriskElement>      $activeDigiriskElements Active GP/UT, by ID
     * @param  array<int, array<string, mixed>> $dangerCategories       Danger categories, by position
     * @return void
     */
    public function build(array $models, array $scopes, array $activeDigiriskElements, array $dangerCategories): void
    {
        global $langs, $mysoc;

        $this->risk = new Risk($this->db);
        $this->start($langs->transnoentities('RiskLibrary') . ' - ' . $mysoc->name, 'Digirisk');

        // The GP/UT in the order of the tree, as the grid of the page
        $elementNodes = !empty($activeDigiriskElements) ? flatten_tree(recurse_tree(0, 0, $activeDigiriskElements)) : [];

        $states = $this->addGridSheet($models, $scopes, $elementNodes);
        $this->addModelsSheet($models, $scopes, $activeDigiriskElements, $dangerCategories);
        $this->fillSummarySheet(count($models), count($elementNodes), $states);
    }

    /**
     * Cell of the grid: the cotation of the risk of the GP/UT in the colour of its level and opening its card, NC with
     * the reason, ? or the risk missing, in the colours of the page
     *
     * @param  array{state: string, cotation: int, label: string} $cell  Content of the cell, from digiriskdolibarr_risklibrary_cell()
     * @param  stdClass|null                                      $scope Assignment of the model to the GP/UT
     * @return array<string, mixed>                                      Cell
     */
    protected function getGridCell(array $cell, ?stdClass $scope): array
    {
        global $langs;

        if ($cell['state'] === 'risk') {
            // A cotation of the advanced method is a number, one of the standard method the range of its level
            return [
                'value'   => ctype_digit($cell['label']) ? (int) $cell['label'] : $cell['label'],
                'colours' => self::LEVEL_COLOURS[$this->risk->getCotationLevel($cell['cotation'])] ?? self::LEVEL_COLOURS[1],
                'url'     => $this->getAbsoluteUrl(dol_buildpath('/digiriskdolibarr/view/risk/risk_card.php', 1) . '?id=' . (int) $scope->fk_risk),
            ];
        }
        if ($cell['state'] === 'notconcerned') {
            $reason = $scope ? trim((string) $scope->reason) : '';

            return [
                'value'   => $reason !== '' ? $langs->transnoentities('RiskLibraryXlsxNotConcerned', $reason) : $langs->transnoentities('RiskLibraryStateNotConcernedShort'),
                'colours' => self::STATE_COLOURS['notconcerned'],
                'bold'    => false,
            ];
        }
        if ($cell['state'] === 'missing') {
            return ['value' => $langs->transnoentities('RiskLibraryXlsxRiskMissing'), 'colours' => self::STATE_COLOURS['missing']];
        }

        return ['value' => '?', 'colours' => self::STATE_COLOURS['toqualify']];
    }

    /**
     * Add the sheet of the grid: one row per GP/UT, one column per model, then the number of GP/UT concerned by model
     *
     * @param  array<int, stdClass>             $models       Models
     * @param  array<int, array<int, stdClass>> $scopes       Assignments, by model ID then GP/UT ID
     * @param  array<int, array<string, mixed>> $elementNodes GP/UT in the order of the tree, with their depth, by ID
     * @return array<string, int>                             Number of cells in each state
     */
    protected function addGridSheet(array $models, array $scopes, array $elementNodes): array
    {
        global $langs;

        $sheet = $this->addSheet($langs->transnoentities('RiskLibraryGrid'), $langs->transnoentities('RiskLibraryXlsxGridDesc'));

        $columns = [
            ['label' => $langs->transnoentities('Ref'), 'width' => 12],
            ['label' => $langs->transnoentities('RiskLibraryElement'), 'width' => 44],
        ];
        foreach ($models as $model) {
            $columns[] = ['label' => (string) $model->label, 'width' => 18];
        }

        $states          = ['risk' => 0, 'missing' => 0, 'notconcerned' => 0, 'toqualify' => 0];
        $concernedCounts = array_fill_keys(array_keys($models), 0);
        $lines           = [];
        foreach ($elementNodes as $elementId => $elementNode) {
            $element = $elementNode['object'];
            $line    = [
                ['value' => (string) $element->ref, 'url' => $this->getAbsoluteUrl(dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . (int) $elementId)],
                ['value' => (string) $element->label, 'bold' => $element->element_type === 'groupment'],
            ];
            foreach (array_keys($models) as $modelId) {
                $scope = $scopes[$modelId][(int) $elementId] ?? null;
                $cell  = digiriskdolibarr_risklibrary_cell($scope);
                $states[$cell['state']]++;
                // Concerned, as the foot of the grid on the page counts it: its risk there or missing
                if ($scope && $scope->concerned == 1) {
                    $concernedCounts[$modelId]++;
                }
                $line[] = $this->getGridCell($cell, $scope);
            }
            $lines[] = $line;
        }

        $totalLine = [['value' => $langs->transnoentities('RiskLibraryConcernedTotal'), 'bold' => true], ''];
        foreach ($concernedCounts as $concernedCount) {
            $totalLine[] = ['value' => $concernedCount, 'bold' => true];
        }
        $lines[] = $totalLine;

        $lastRow = $this->writeTable($sheet, 1, $columns, $lines, 2);

        // The GP/UT indented as in the tree, the cells centred as on the page
        $row = 1;
        foreach ($elementNodes as $elementNode) {
            $row++;
            if ((int) $elementNode['depth'] > 0) {
                $sheet->getStyle('B' . $row)->getAlignment()->setIndent((int) $elementNode['depth']);
            }
        }
        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle('C1:' . $lastColumn . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(45);

        return $states;
    }

    /**
     * Add the sheet of the models: danger category, label, description, indicative cotation and assignment
     *
     * @param  array<int, stdClass>             $models                 Models
     * @param  array<int, array<int, stdClass>> $scopes                 Assignments, by model ID then GP/UT ID
     * @param  array<int, DigiriskElement>      $activeDigiriskElements Active GP/UT, by ID
     * @param  array<int, array<string, mixed>> $dangerCategories       Danger categories, by position
     * @return void
     */
    protected function addModelsSheet(array $models, array $scopes, array $activeDigiriskElements, array $dangerCategories): void
    {
        global $langs;

        $sheet = $this->addSheet($langs->transnoentities('RiskLibraryModels'), $langs->transnoentities('RiskLibraryXlsxModelsDesc'));

        $columns = [
            ['label' => $langs->transnoentities('RiskCategory'), 'width' => 30],
            ['label' => $langs->transnoentities('Label'), 'width' => 34],
            ['label' => $langs->transnoentities('Description'), 'width' => 60, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('RiskLibraryCotation'), 'width' => 14],
            ['label' => $langs->transnoentities('RiskLibraryStateConcerned'), 'width' => 12, 'format' => 'int'],
            ['label' => $langs->transnoentities('RiskLibraryStateNotConcerned'), 'width' => 12, 'format' => 'int'],
            ['label' => $langs->transnoentities('RiskLibraryStateToQualify'), 'width' => 12, 'format' => 'int'],
        ];

        $standardCotations = digiriskdolibarr_risklibrary_standard_cotations();
        $lines             = [];
        foreach ($models as $modelId => $model) {
            // The assignment as the list of the models counts it, among the active GP/UT
            $counts = [1 => 0, 0 => 0, -1 => 0];
            foreach (array_keys($activeDigiriskElements) as $elementId) {
                $concerned = isset($scopes[$modelId][$elementId]) ? (int) $scopes[$modelId][$elementId]->concerned : -1;
                $counts[isset($counts[$concerned]) ? $concerned : -1]++;
            }

            $lines[] = [
                (string) ($dangerCategories[$model->category]['name'] ?? ''),
                (string) $model->label,
                $this->getPlainText((string) $model->description),
                ['value' => $standardCotations[$model->cotation] ?? (string) $model->cotation, 'colours' => self::LEVEL_COLOURS[$this->risk->getCotationLevel($model->cotation)] ?? self::LEVEL_COLOURS[1]],
                $counts[1],
                $counts[0],
                $counts[-1],
            ];
        }

        $lastRow = $this->writeTable($sheet, 1, $columns, $lines, 2);
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Fill the summary: the key figures of the grid, the legend of its cells and the contents of the workbook
     *
     * @param  int                $nbModels   Number of models
     * @param  int                $nbElements Number of GP/UT of the grid
     * @param  array<string, int> $states     Number of cells in each state
     * @return void
     */
    protected function fillSummarySheet(int $nbModels, int $nbElements, array $states): void
    {
        global $langs;

        $nbCells = $nbModels * $nbElements;

        $row = $this->writeSummaryHead($langs->transnoentities('RiskLibrary'));
        $row = $this->writeSummaryKeyFigures($row, [
            ['label' => $langs->transnoentities('RiskLibraryModels'), 'value' => $nbModels],
            ['label' => $langs->transnoentities('RiskLibraryXlsxElements'), 'value' => $nbElements],
            ['label' => $langs->transnoentities('RiskLibraryXlsxCellsConcerned'), 'value' => $states['risk'] + $states['missing'], 'total' => $nbCells],
            ['label' => $langs->transnoentities('RiskLibraryXlsxCellsNotConcerned'), 'value' => $states['notconcerned'], 'total' => $nbCells],
            ['label' => $langs->transnoentities('RiskLibraryXlsxCellsToQualify'), 'value' => $states['toqualify'], 'total' => $nbCells],
            ['label' => $langs->transnoentities('RiskLibraryXlsxCellsRiskMissing'), 'value' => $states['missing'], 'total' => $nbCells],
        ]);

        // What a cell of the grid holds, in its colours: the levels of the scale, then the other states
        $lines             = [];
        $standardCotations = digiriskdolibarr_risklibrary_standard_cotations();
        foreach (digiriskdolibarr_risklibrary_cotations() as $cotation => $levelKey) {
            $lines[] = [['value' => $standardCotations[$cotation] ?? (string) $cotation, 'colours' => self::LEVEL_COLOURS[$this->risk->getCotationLevel($cotation)] ?? self::LEVEL_COLOURS[1]], $langs->transnoentities($levelKey)];
        }
        $lines[] = [['value' => $langs->transnoentities('RiskLibraryStateNotConcernedShort'), 'colours' => self::STATE_COLOURS['notconcerned'], 'bold' => false], $langs->transnoentities('RiskLibraryXlsxLegendNotConcerned')];
        $lines[] = [['value' => '?', 'colours' => self::STATE_COLOURS['toqualify']], $langs->transnoentities('RiskLibraryStateToQualify')];
        $lines[] = [['value' => $langs->transnoentities('RiskLibraryXlsxRiskMissing'), 'colours' => self::STATE_COLOURS['missing']], $langs->transnoentities('RiskLibraryRiskMissing')];
        $row     = $this->writeSummaryTable($row, $langs->transnoentities('RiskLibraryXlsxLegend'), [$langs->transnoentities('RiskLibraryXlsxCell'), $langs->transnoentities('Description')], $lines);

        $this->writeSummaryContents($row);
    }
}
