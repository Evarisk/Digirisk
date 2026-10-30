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
 * \file    class/digiriskaccidentexport.class.php
 * \ingroup digiriskdolibarr
 * \brief   Excel export of the accident list, one sheet per view of the data
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/accident.class.php';
require_once __DIR__ . '/accidentinvestigation.class.php';
require_once __DIR__ . '/evaluator.class.php';
require_once __DIR__ . '/digiriskxlsxexport.class.php';
require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';

/**
 * Class for DigiriskAccidentExport
 *
 * The accidents come from the query of the accident list, already filtered and sorted: this class reads their
 * victim, work stops, lesions, details and investigations, then lays them out under several views, with the
 * indicators of the accident dashboard of Digirisk.
 */
class DigiriskAccidentExport extends DigiriskXlsxExport
{
    /**
     * @var array<int, string> Label of each GP/UT, by id
     */
    protected array $elementLabels = [];

    /**
     * Build the workbook of the given accidents and send it to the browser
     *
     * @param  array  $accidentIds Accident ids, in the order of the list
     * @param  string $fileName    Name of the file, without extension
     * @return void
     */
    public function export(array $accidentIds, string $fileName): void
    {
        $this->build($accidentIds);
        $this->send($fileName);
    }

    /**
     * Build the workbook: a summary, then the detailed sheets, then the sheets that sum the accidents up
     *
     * @param  array $accidentIds Accident ids, in the order of the list
     * @return void
     */
    public function build(array $accidentIds): void
    {
        global $langs, $mysoc;

        $this->start($langs->transnoentities('AccidentXlsxTitle') . ' - ' . $mysoc->name, 'Digirisk');

        $records = $this->fetchRecords(array_map('intval', $accidentIds));

        $this->addAccidentsSheet($records);
        $this->addWorkStopsSheet($records);
        $this->addLesionsSheet($records);
        $this->addInvestigationsSheet($records);
        $this->addActionsSheet($records);
        $this->addElementsSheet($records);
        $this->addLesionMapSheet($records);
        $this->addMonthsSheet($records);
        $this->addVictimsSheet($records);
        $this->fillSummarySheet($records);
    }

    /**
     * Load the accidents and everything recorded about them
     *
     * @param  array $accidentIds Accident ids, in the order of the list
     * @return array              Records of the accidents, in the order of the list
     */
    protected function fetchRecords(array $accidentIds): array
    {
        global $langs, $mysoc;

        $accidents = [];
        foreach (array_chunk($accidentIds, 500) as $chunkIds) {
            $sql  = 'SELECT t.rowid, t.ref, t.label, t.status, t.accident_type, t.external_accident, t.fk_element, t.fk_standard, t.fk_soc,';
            $sql .= ' t.accident_location, t.accident_date, t.description, t.fk_ticket, t.fk_user_employer, t.date_creation,';
            $sql .= ' s.nom as thirdparty_name, tk.ref as ticket_ref, ds.ref as standard_ref';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident as t';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON s.rowid = t.fk_soc';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'ticket as tk ON tk.rowid = t.fk_ticket';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskstandard as ds ON ds.rowid = t.fk_standard';
            $sql .= ' WHERE t.rowid IN (' . implode(',', $chunkIds) . ')';

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $accidents[(int) $obj->rowid] = $obj;
                }
                $this->db->free($resql);
            }
        }

        $ids                      = array_keys($accidents);
        $victimsByAccident        = $this->fetchVictims($ids);
        $workStopsByAccident      = $this->fetchByAccident('digiriskdolibarr_accident_workstop', 'rowid, ref, fk_accident, workstop_days, date_start_workstop, date_end_workstop, declaration_link', 'status > 0', $ids);
        $lesionsByAccident        = $this->fetchByAccident('digiriskdolibarr_accident_lesion', 'rowid, fk_accident, lesion_localization, lesion_nature', '1 = 1', $ids);
        $metadataByAccident       = $this->fetchByAccident('digiriskdolibarr_accidentmetadata', '*', 'status = 1', $ids);
        $investigationsByAccident = $this->fetchByAccident('digiriskdolibarr_accident_investigation', 'rowid, ref, fk_accident, status, date_start, date_end, fk_task', 'status >= 0', $ids);

        // The actions of an investigation hang under its task
        $investigationByTask = [];
        foreach ($investigationsByAccident as $investigations) {
            foreach ($investigations as $investigation) {
                if ((int) $investigation->fk_task > 0) {
                    $investigationByTask[(int) $investigation->fk_task] = (int) $investigation->rowid;
                }
            }
        }
        $actionsByInvestigation = $this->fetchActions($investigationByTask);

        $elementIds = [];
        $userIds    = [];
        foreach ($accidents as $obj) {
            $elementIds[] = (int) $obj->fk_element;
            $userIds[]    = (int) $obj->fk_user_employer;
        }
        foreach ($victimsByAccident as $victim) {
            $userIds[] = (int) $victim->element_id;
        }
        $this->fetchElementLabels($elementIds);
        $this->fetchUserNames($userIds);

        $accident              = new Accident($this->db);
        $accidentInvestigation = new AccidentInvestigation($this->db);
        $notFilled             = $langs->transnoentities('XlsxNotFilled');

        $records = [];
        foreach ($accidentIds as $accidentId) {
            if (!isset($accidents[$accidentId])) {
                continue;
            }
            $obj = $accidents[$accidentId];

            $workStops = [];
            $days      = 0;
            foreach ($workStopsByAccident[$accidentId] ?? [] as $workStop) {
                $start = $this->getTimestamp($workStop->date_start_workstop);
                $end   = $this->getTimestamp($workStop->date_end_workstop);
                // As the accident list reads it: the span of its dates when both are filled in, its number of days otherwise
                $duration    = (!empty($start) && !empty($end)) ? abs($end - $start) / 86400 : (float) $workStop->workstop_days;
                $days       += $duration;
                $workStops[] = ['workStop' => $workStop, 'start' => $start, 'end' => $end, 'days' => $duration, 'bucket' => $langs->transnoentities(ucfirst($accident->getWorkStopDurationBucket($duration)))];
            }

            $investigations = [];
            foreach ($investigationsByAccident[$accidentId] ?? [] as $investigation) {
                $investigations[] = [
                    'investigation' => $investigation,
                    'url'           => dol_buildpath('/digiriskdolibarr/view/accidentinvestigation/accidentinvestigation_card.php', 2) . '?id=' . $investigation->rowid,
                    'status'        => $this->getPlainText($accidentInvestigation->LibStatut((int) $investigation->status, 0)),
                    'actions'       => $actionsByInvestigation[(int) $investigation->rowid] ?? [],
                ];
            }

            $victim     = $victimsByAccident[$accidentId] ?? null;
            $victimName = '';
            if ($victim !== null) {
                $victimName = $this->userNames[(int) $victim->element_id] ?? trim($victim->firstname . ' ' . $victim->lastname);
            }

            $records[$accidentId] = [
                'accident'       => $obj,
                'url'            => dol_buildpath('/digiriskdolibarr/view/accident/accident_card.php', 2) . '?id=' . $accidentId,
                'date'           => $this->getTimestamp($obj->accident_date),
                'type'           => $langs->transnoentities($obj->accident_type == 1 ? 'CommutingAccident' : 'WorkAccidentStatement'),
                'location'       => $this->getLocation($obj, $mysoc->name),
                'element'        => ($obj->external_accident == 1 && (int) $obj->fk_element > 0) ? ($this->elementLabels[(int) $obj->fk_element] ?? '') : '',
                'status'         => $this->getPlainText($langs->transnoentities($accident->fields['status']['arrayofkeyval'][(int) $obj->status] ?? (string) $obj->status)),
                'victim'         => $victimName !== '' ? $victimName : $notFilled,
                'workStops'      => $workStops,
                'days'           => $days,
                'lesions'        => $lesionsByAccident[$accidentId] ?? [],
                'metadata'       => !empty($metadataByAccident[$accidentId]) ? end($metadataByAccident[$accidentId]) : null,
                'investigations' => $investigations,
            ];
        }

        return $records;
    }

    /**
     * Load rows of a table hanging under the accidents
     *
     * @param  string $table       Table, without prefix
     * @param  string $fields      Fields to select
     * @param  string $where       Condition on the rows
     * @param  array  $accidentIds Accident ids
     * @return array               Rows by accident id, oldest first
     */
    protected function fetchByAccident(string $table, string $fields, string $where, array $accidentIds): array
    {
        $rowsByAccident = [];
        foreach (array_chunk($accidentIds, 500) as $chunkIds) {
            $sql   = 'SELECT ' . $fields . ' FROM ' . MAIN_DB_PREFIX . $table . ' WHERE ' . $where . ' AND fk_accident IN (' . implode(',', array_map('intval', $chunkIds)) . ') ORDER BY rowid ASC';
            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $rowsByAccident[(int) $obj->fk_accident][] = $obj;
                }
                $this->db->free($resql);
            }
        }

        return $rowsByAccident;
    }

    /**
     * Load the victim of each accident, its signatory of role Victim
     *
     * @param  array $accidentIds Accident ids
     * @return array              Signatory row, by accident id
     */
    protected function fetchVictims(array $accidentIds): array
    {
        $victims = [];
        foreach (array_chunk($accidentIds, 500) as $chunkIds) {
            $sql  = 'SELECT fk_object, element_id, firstname, lastname FROM ' . MAIN_DB_PREFIX . 'saturne_object_signature';
            $sql .= " WHERE object_type = 'accident' AND role = 'Victim' AND status > 0 AND fk_object IN (" . implode(',', array_map('intval', $chunkIds)) . ')';
            $sql .= ' ORDER BY rowid ASC';

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    if (!isset($victims[(int) $obj->fk_object])) {
                        $victims[(int) $obj->fk_object] = $obj;
                    }
                }
                $this->db->free($resql);
            }
        }

        return $victims;
    }

    /**
     * Load the labels of the GP/UT
     *
     * @param  array $elementIds GP/UT ids
     * @return void
     */
    protected function fetchElementLabels(array $elementIds): void
    {
        foreach (array_chunk(array_filter(array_unique($elementIds)), 500) as $chunkIds) {
            $resql = $this->db->query('SELECT rowid, ref, label FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskelement WHERE rowid IN (' . implode(',', array_map('intval', $chunkIds)) . ')');
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $this->elementLabels[(int) $obj->rowid] = $obj->ref . ' - ' . $obj->label;
                }
                $this->db->free($resql);
            }
        }
    }

    /**
     * Place of an accident, as the accident list shows it: a GP/UT or the whole society, a third party, or a free text
     *
     * @param  stdClass $obj         Row of the accident
     * @param  string   $companyName Name of the society
     * @return string                Place, empty when it is not filled in
     */
    protected function getLocation(stdClass $obj, string $companyName): string
    {
        switch ((int) $obj->external_accident) {
            case 1:
                if ((int) $obj->fk_standard > 0) {
                    return trim($obj->standard_ref . ' - ' . $companyName, ' -');
                }
                return (int) $obj->fk_element > 0 ? ($this->elementLabels[(int) $obj->fk_element] ?? '') : '';
            case 2:
                return (string) $obj->thirdparty_name;
            case 3:
                return $this->getPlainText($obj->accident_location);
        }

        return '';
    }

    /**
     * Sheet of the accidents: one line per accident
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addAccidentsSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('AccidentXlsxSheetAccidents'), $langs->transnoentities('AccidentXlsxSheetAccidentsDesc'));
        $columns = [
            ['label' => $langs->transnoentities('Ref'), 'width' => 14],
            ['label' => $langs->transnoentities('Label'), 'width' => 26, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('AccidentDate'), 'width' => 16, 'format' => 'datetime'],
            ['label' => $langs->transnoentities('AccidentType'), 'width' => 18],
            ['label' => $langs->transnoentities('AccidentLocation'), 'width' => 26, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('Victim'), 'width' => 20],
            ['label' => $langs->transnoentities('UserEmployer'), 'width' => 20],
            ['label' => $langs->transnoentities('Status'), 'width' => 12],
            ['label' => $langs->transnoentities('Description'), 'width' => 50, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('AccidentXlsxWorkStops'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('AccidentXlsxWorkStopDays'), 'width' => 11, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('WorkStopDuration'), 'width' => 16],
            ['label' => $langs->transnoentities('AccidentXlsxLesions'), 'width' => 36, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('VictimActivity'), 'width' => 30, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('AccidentNature'), 'width' => 30, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('AccidentObject'), 'width' => 30, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('VictimTransportedTo'), 'width' => 20],
            ['label' => $langs->transnoentities('RegisteredInAccidentRegister'), 'width' => 12],
            ['label' => $langs->transnoentities('RegisterNumber'), 'width' => 12],
            ['label' => $langs->transnoentities('Consequence'), 'width' => 24, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('AccidentInvestigation'), 'width' => 14],
            ['label' => $langs->transnoentities('Ticket'), 'width' => 14],
        ];

        $lines = [];
        foreach ($records as $record) {
            $obj      = $record['accident'];
            $metadata = $record['metadata'];
            $buckets  = array_unique(array_column($record['workStops'], 'bucket'));

            $lines[] = [
                ['value' => $obj->ref, 'url' => $record['url']],
                $obj->label,
                $this->getExcelDate($record['date'], true),
                $record['type'],
                $record['location'],
                $record['victim'],
                $this->userNames[(int) $obj->fk_user_employer] ?? '',
                $record['status'],
                $this->getPlainText($obj->description),
                count($record['workStops']),
                !empty($record['workStops']) ? round($record['days'], 1) : null,
                implode(', ', $buckets),
                implode("\n", array_map([$this, 'getLesionLabel'], $record['lesions'])),
                $metadata !== null ? $this->getPlainText($metadata->victim_activity) : '',
                $metadata !== null ? $this->getPlainText($metadata->accident_nature) : '',
                $metadata !== null ? $this->getPlainText($metadata->accident_object) : '',
                $metadata !== null ? (string) $metadata->victim_transported_to : '',
                $metadata !== null && $metadata->registered_in_accident_register !== null ? $langs->transnoentities($metadata->registered_in_accident_register ? 'Yes' : 'No') : '',
                $metadata !== null ? (string) $metadata->register_number : '',
                $metadata !== null ? $this->getPlainText($metadata->consequence) : '',
                implode(', ', array_map(function (array $investigation): string {
                    return $investigation['investigation']->ref;
                }, $record['investigations'])),
                !empty($obj->ticket_ref) ? ['value' => $obj->ticket_ref, 'url' => DOL_MAIN_URL_ROOT . '/ticket/card.php?id=' . $obj->fk_ticket] : '',
            ];
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Label of a lesion: its seat and its nature
     *
     * @param  stdClass $lesion Lesion
     * @return string           Label
     */
    protected function getLesionLabel(stdClass $lesion): string
    {
        global $langs;

        return trim($langs->transnoentities((string) $lesion->lesion_localization) . ' : ' . $langs->transnoentities((string) $lesion->lesion_nature), ' :');
    }

    /**
     * Columns locating an accident: its ref, its date, its place and its victim
     *
     * @return array Columns
     */
    protected function getAccidentColumns(): array
    {
        global $langs;

        return [
            ['label' => $langs->transnoentities('Accident'), 'width' => 14],
            ['label' => $langs->transnoentities('AccidentDate'), 'width' => 12, 'format' => 'date'],
            ['label' => $langs->transnoentities('AccidentLocation'), 'width' => 24, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('Victim'), 'width' => 20],
        ];
    }

    /**
     * Cells locating an accident, in the order of getAccidentColumns()
     *
     * @param  array $record Record of the accident
     * @return array         Cells
     */
    protected function getAccidentCells(array $record): array
    {
        return [['value' => $record['accident']->ref, 'url' => $record['url']], $this->getExcelDate($record['date']), $record['location'], $record['victim']];
    }

    /**
     * Sheet of the work stops: one line per work stop
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addWorkStopsSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('AccidentXlsxSheetWorkStops'), $langs->transnoentities('AccidentXlsxSheetWorkStopsDesc'));
        $columns = array_merge($this->getAccidentColumns(), [
            ['label' => $langs->transnoentities('Ref'), 'width' => 12],
            ['label' => $langs->transnoentities('DateStartWorkStop'), 'width' => 13, 'format' => 'date'],
            ['label' => $langs->transnoentities('DateEndWorkStop'), 'width' => 13, 'format' => 'date'],
            ['label' => $langs->transnoentities('AccidentXlsxWorkStopDays'), 'width' => 11, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('WorkStopDuration'), 'width' => 16],
            ['label' => $langs->transnoentities('AccidentXlsxDeclarationLink'), 'width' => 30],
        ]);

        $lines = [];
        foreach ($records as $record) {
            foreach ($record['workStops'] as $workStop) {
                $lines[] = array_merge($this->getAccidentCells($record), [
                    $workStop['workStop']->ref,
                    $this->getExcelDate($workStop['start']),
                    $this->getExcelDate($workStop['end']),
                    round($workStop['days'], 1),
                    $workStop['bucket'],
                    (string) $workStop['workStop']->declaration_link,
                ]);
            }
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Sheet of the lesions: one line per lesion
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addLesionsSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('AccidentXlsxSheetLesions'), $langs->transnoentities('AccidentXlsxSheetLesionsDesc'));
        $columns = array_merge($this->getAccidentColumns(), [
            ['label' => $langs->transnoentities('LesionLocalization'), 'width' => 26],
            ['label' => $langs->transnoentities('LesionNature'), 'width' => 26],
        ]);

        $lines = [];
        foreach ($records as $record) {
            foreach ($record['lesions'] as $lesion) {
                $lines[] = array_merge($this->getAccidentCells($record), [
                    $langs->transnoentities((string) $lesion->lesion_localization),
                    $langs->transnoentities((string) $lesion->lesion_nature),
                ]);
            }
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Sheet of the investigations: one line per investigation, with the count of its actions
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addInvestigationsSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('AccidentXlsxSheetInvestigations'), $langs->transnoentities('AccidentXlsxSheetInvestigationsDesc'));
        $columns = array_merge([['label' => $langs->transnoentities('AccidentInvestigation'), 'width' => 14]], $this->getAccidentColumns(), [
            ['label' => $langs->transnoentities('Status'), 'width' => 12],
            ['label' => $langs->transnoentities('StartDateInvestigate'), 'width' => 13, 'format' => 'date'],
            ['label' => $langs->transnoentities('EndDateInvestigate'), 'width' => 13, 'format' => 'date'],
            ['label' => $langs->transnoentities('DigiriskXlsxActions'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsDone'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsOverdue'), 'width' => 10, 'format' => 'int'],
        ]);

        $lines = [];
        foreach ($records as $record) {
            foreach ($record['investigations'] as $investigation) {
                [$nbActions, $nbDone, $nbOverdue] = $this->countActions($investigation['actions']);

                $lines[] = array_merge([['value' => $investigation['investigation']->ref, 'url' => $investigation['url']]], $this->getAccidentCells($record), [
                    $investigation['status'],
                    $this->getExcelDate($investigation['investigation']->date_start),
                    $this->getExcelDate($investigation['investigation']->date_end),
                    $nbActions,
                    $nbDone,
                    $nbOverdue,
                ]);
            }
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Sheet of the actions of the investigations: one line per curative or corrective action
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addActionsSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('AccidentXlsxSheetActions'), $langs->transnoentities('AccidentXlsxSheetActionsDesc'));
        $columns = array_merge([
            ['label' => $langs->transnoentities('AccidentInvestigation'), 'width' => 14],
            ['label' => $langs->transnoentities('Accident'), 'width' => 14],
        ], $this->getActionColumns());

        $lines = [];
        foreach ($records as $record) {
            foreach ($record['investigations'] as $investigation) {
                foreach ($investigation['actions'] as $action) {
                    $lines[] = array_merge([
                        ['value' => $investigation['investigation']->ref, 'url' => $investigation['url']],
                        ['value' => $record['accident']->ref, 'url' => $record['url']],
                    ], $this->getActionCells($action));
                }
            }
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Columns summing a group of accidents up
     *
     * @param  string $label Head of the column of the group labels
     * @return array         Columns
     */
    protected function getStatsColumns(string $label): array
    {
        global $langs;

        return [
            ['label' => $label, 'width' => 36, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('XlsxTotal'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('WorkAccidentStatement'), 'width' => 12, 'format' => 'int'],
            ['label' => $langs->transnoentities('CommutingAccident'), 'width' => 12, 'format' => 'int'],
            ['label' => $langs->transnoentities('AccidentXlsxWithWorkStop'), 'width' => 12, 'format' => 'int'],
            ['label' => $langs->transnoentities('AccidentXlsxWorkStopDays'), 'width' => 11, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('AccidentXlsxLesionsNumber'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('AccidentXlsxInvestigations'), 'width' => 11, 'format' => 'int'],
            ['label' => $langs->transnoentities('AccidentXlsxLastAccident'), 'width' => 13, 'format' => 'date'],
        ];
    }

    /**
     * Cells summing a group of accidents up, after its label
     *
     * @param  array $records Records of the accidents of the group
     * @return array          Cells
     */
    protected function getStatsCells(array $records): array
    {
        $commuting      = 0;
        $withWorkStop   = 0;
        $days           = 0;
        $lesions        = 0;
        $investigations = 0;
        $last           = 0;
        foreach ($records as $record) {
            $commuting      += $record['accident']->accident_type == 1 ? 1 : 0;
            $withWorkStop   += !empty($record['workStops']) ? 1 : 0;
            $days           += $record['days'];
            $lesions        += count($record['lesions']);
            $investigations += count($record['investigations']);
            $last            = max($last, $record['date']);
        }

        return [count($records), count($records) - $commuting, $commuting, $withWorkStop, round($days, 1), $lesions, $investigations, $this->getExcelDate($last)];
    }

    /**
     * Write a sheet of groups of accidents, each group with its figures
     *
     * @param  string $titleKey       Translation key of the sheet title
     * @param  string $descriptionKey Translation key of the sheet description
     * @param  string $label          Head of the column of the group labels
     * @param  array  $groups         Records of the accidents, by group label
     * @return void
     */
    protected function addGroupsSheet(string $titleKey, string $descriptionKey, string $label, array $groups): void
    {
        global $langs;

        $labels = array_combine(array_keys($groups), array_keys($groups));
        $this->sortLabels($labels, $langs->transnoentities('XlsxNotFilled'));

        $sheet = $this->addSheet($langs->transnoentities($titleKey), $langs->transnoentities($descriptionKey));
        $lines = [];
        foreach ($labels as $groupLabel) {
            $lines[] = array_merge([(string) $groupLabel], $this->getStatsCells($groups[$groupLabel]));
        }

        $this->writeTable($sheet, 1, $this->getStatsColumns($label), $lines, 1);
    }

    /**
     * Sheet of the places: the accidents of each GP/UT, third party or place
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addElementsSheet(array $records): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        $groups    = $this->groupBy($records, function (array $record) use ($notFilled): array {
            return [$record['location'] !== '' ? $record['location'] : $notFilled];
        });

        $this->addGroupsSheet('AccidentXlsxSheetLocations', 'AccidentXlsxSheetLocationsDesc', $langs->transnoentities('AccidentLocation'), $groups);
    }

    /**
     * Sheet of the victims: the accidents of each victim
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addVictimsSheet(array $records): void
    {
        global $langs;

        $groups = $this->groupBy($records, function (array $record): array {
            return [$record['victim']];
        });

        $this->addGroupsSheet('AccidentXlsxSheetVictims', 'AccidentXlsxSheetVictimsDesc', $langs->transnoentities('Victim'), $groups);
    }

    /**
     * Sheet crossing the seats of the lesions with their natures
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addLesionMapSheet(array $records): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        $rows      = [];
        $columns   = [];
        $counts    = [];
        $nbLesions = 0;
        foreach ($records as $record) {
            foreach ($record['lesions'] as $lesion) {
                $seat   = $lesion->lesion_localization !== null && $lesion->lesion_localization !== '' ? $langs->transnoentities($lesion->lesion_localization) : $notFilled;
                $nature = $lesion->lesion_nature !== null && $lesion->lesion_nature !== '' ? $langs->transnoentities($lesion->lesion_nature) : $notFilled;

                $rows[$seat]            = $seat;
                $columns[$nature]       = $nature;
                $counts[$seat][$nature] = ($counts[$seat][$nature] ?? 0) + 1;
                $nbLesions++;
            }
        }
        $this->sortLabels($rows, $notFilled);
        $this->sortLabels($columns, $notFilled);

        $this->addMatrixSheet($langs->transnoentities('AccidentXlsxSheetLesionMap'), $langs->transnoentities('AccidentXlsxSheetLesionMapDesc'), $langs->transnoentities('LesionLocalization'), $rows, $columns, $counts, [], $nbLesions);
    }

    /**
     * Sheet of the months: the accidents of each month, with their work stops and their lesions
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function addMonthsSheet(array $records): void
    {
        global $langs;

        $first   = 0;
        $last    = 0;
        $byMonth = [];
        foreach ($records as $record) {
            if (empty($record['date'])) {
                continue;
            }
            $first = empty($first) ? $record['date'] : min($first, $record['date']);
            $last  = max($last, $record['date']);

            $byMonth[$this->getMonthKey($record['date'])][] = $record;
        }

        $sheet   = $this->addSheet($langs->transnoentities('AccidentXlsxSheetMonths'), $langs->transnoentities('AccidentXlsxSheetMonthsDesc'));
        $columns = $this->getStatsColumns($langs->transnoentities('XlsxMonth'));
        // The month is a date, the last accident of a month says nothing more
        $columns[0] = ['label' => $langs->transnoentities('XlsxMonth'), 'width' => 16, 'format' => 'month'];
        array_pop($columns);

        $lines = [];
        foreach ($this->getMonthKeys($first, $last) as $month) {
            $cells = $this->getStatsCells($byMonth[$month] ?? []);
            array_pop($cells);
            $lines[] = array_merge([$this->getMonthExcelDate($month)], $cells);
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Summary: what was exported, the key figures and indicators, the work stops and the contents of the workbook
     *
     * @param  array $records Records of the accidents
     * @return void
     */
    protected function fillSummarySheet(array $records): void
    {
        global $langs;

        $nbAccidents    = count($records);
        $commuting      = 0;
        $withWorkStop   = 0;
        $nbWorkStops    = 0;
        $days           = 0;
        $nbLesions      = 0;
        $victims        = [];
        $nbInvestigated = 0;
        $fromTicket     = 0;
        $buckets        = [];
        $statuses       = [];
        $nbActions      = 0;
        $nbDone         = 0;
        $nbOverdue      = 0;
        $first          = 0;
        $last           = 0;
        foreach ($records as $record) {
            $commuting      += $record['accident']->accident_type == 1 ? 1 : 0;
            $withWorkStop   += !empty($record['workStops']) ? 1 : 0;
            $nbWorkStops    += count($record['workStops']);
            $days           += $record['days'];
            $nbLesions      += count($record['lesions']);
            $nbInvestigated += !empty($record['investigations']) ? 1 : 0;
            $fromTicket     += (int) $record['accident']->fk_ticket > 0 ? 1 : 0;
            $statuses[(int) $record['accident']->status] = [$record['status'], ($statuses[(int) $record['accident']->status][1] ?? 0) + 1];
            $victims[$record['victim']] = true;
            foreach ($record['workStops'] as $workStop) {
                $buckets[$workStop['bucket']] = ($buckets[$workStop['bucket']] ?? 0) + 1;
            }
            foreach ($record['investigations'] as $investigation) {
                [$actions, $done, $overdue] = $this->countActions($investigation['actions']);
                $nbActions += $actions;
                $nbDone    += $done;
                $nbOverdue += $overdue;
            }
            if (!empty($record['date'])) {
                $first = empty($first) ? $record['date'] : min($first, $record['date']);
                $last  = max($last, $record['date']);
            }
        }
        unset($victims[$langs->transnoentities('XlsxNotFilled')]);

        $row = $this->writeSummaryHead($langs->transnoentities('AccidentXlsxTitle'));
        $row = $this->writeSummaryFilters($row);

        // The indicators of the accident dashboard of Digirisk, from the number of employees and of worked hours of its setup
        $employees   = (new Evaluator($this->db))->getNbEmployees();
        $nbEmployees = (int) ($employees['nbemployees'] ?? 0);
        $workedHours = (float) getWorkedHours();

        $row = $this->writeSummaryKeyFigures($row, [
            ['label' => $langs->transnoentities('AccidentXlsxAccidentsNumber'), 'value' => $nbAccidents],
            ['label' => $langs->transnoentities('AccidentXlsxPeriod'), 'value' => !empty($first) ? dol_print_date($first, 'day') . ' - ' . dol_print_date($last, 'day') : null, 'format' => 'text'],
            ['label' => $langs->transnoentities('AccidentXlsxDaysSinceLast'), 'value' => !empty($last) ? (int) floor(($this->now - $last) / 86400) : null],
            ['label' => $langs->transnoentities('WorkAccidentStatement'), 'value' => $nbAccidents - $commuting, 'total' => $nbAccidents],
            ['label' => $langs->transnoentities('CommutingAccident'), 'value' => $commuting, 'total' => $nbAccidents],
            ['label' => $langs->transnoentities('AccidentXlsxWithWorkStop'), 'value' => $withWorkStop, 'total' => $nbAccidents],
            ['label' => $langs->transnoentities('AccidentXlsxWorkStops'), 'value' => $nbWorkStops],
            ['label' => $langs->transnoentities('AccidentXlsxWorkStopDays'), 'value' => round($days, 1), 'format' => 'decimal'],
            ['label' => $langs->transnoentities('AccidentXlsxAverageWorkStopDays'), 'value' => $nbWorkStops > 0 ? round($days / $nbWorkStops, 1) : null, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('AccidentXlsxLesionsNumber'), 'value' => $nbLesions],
            ['label' => $langs->transnoentities('AccidentXlsxVictimsNumber'), 'value' => count($victims)],
            ['label' => $langs->transnoentities('AccidentXlsxInvestigated'), 'value' => $nbInvestigated, 'total' => $nbAccidents],
            ['label' => $langs->transnoentities('AccidentXlsxFromTicket'), 'value' => $fromTicket, 'total' => $nbAccidents],
            ['label' => $langs->transnoentities('DigiriskXlsxActions'), 'value' => $nbActions],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsDone'), 'value' => $nbDone, 'total' => $nbActions],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsOverdue'), 'value' => $nbOverdue, 'total' => $nbActions],
            ['label' => $langs->transnoentities('FrequencyIndex'), 'value' => $nbEmployees > 0 ? round($withWorkStop / $nbEmployees * 1000, 2) : null, 'format' => 'decimal2', 'help' => $langs->transnoentities('AccidentXlsxFrequencyIndexHelp', $nbEmployees)],
            ['label' => $langs->transnoentities('FrequencyRate'), 'value' => $workedHours > 0 ? round($withWorkStop / $workedHours * 1000000, 2) : null, 'format' => 'decimal2', 'help' => $langs->transnoentities('AccidentXlsxFrequencyRateHelp', price2num($workedHours, 0))],
            ['label' => $langs->transnoentities('GravityRate'), 'value' => $workedHours > 0 ? round($days / $workedHours * 1000, 2) : null, 'format' => 'decimal2', 'help' => $langs->transnoentities('AccidentXlsxGravityRateHelp', price2num($workedHours, 0))],
        ]);

        if (!empty($buckets)) {
            $lines = [];
            foreach (array_keys(Accident::WORK_STOP_DURATION_LIMITS) as $bucket) {
                $label = $langs->transnoentities(ucfirst($bucket));
                if (isset($buckets[$label])) {
                    $lines[] = [$label, $buckets[$label], $buckets[$label] / $nbWorkStops];
                }
            }
            $row = $this->writeSummaryTable($row, $langs->transnoentities('WorkStopDuration'), [$langs->transnoentities('WorkStopDuration'), $langs->transnoentities('AccidentXlsxWorkStops'), $langs->transnoentities('XlsxShare')], $lines, ['text', 'int', 'percent']);
        }

        ksort($statuses);
        $lines = [];
        foreach ($statuses as [$label, $count]) {
            $lines[] = [$label, $count, $count / $nbAccidents];
        }
        $row = $this->writeSummaryTable($row, $langs->transnoentities('Status'), [$langs->transnoentities('Status'), $langs->transnoentities('AccidentXlsxAccidentsNumber'), $langs->transnoentities('XlsxShare')], $lines, ['text', 'int', 'percent']);

        $this->writeSummaryContents($row);
    }
}
