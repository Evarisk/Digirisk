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
 * \file    class/digiriskticketexport.class.php
 * \ingroup digiriskdolibarr
 * \brief   Excel export of the ticket register, one sheet per view of the data
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/digiriskxlsxexport.class.php';

/**
 * Class for DigiriskTicketExport
 *
 * The tickets come from the query of the ticket list, already filtered and sorted: this class reads what Digirisk
 * adds to them (declarant, GP/UT, registers, actions, QQOQCP, accidents), then lays them out under several views,
 * so that a report or a chart can be built from any of them without reworking a flat file.
 */
class DigiriskTicketExport extends DigiriskXlsxExport
{
    /**
     * Statuses of a ticket still to be handled
     */
    public const OPEN_STATUSES = [Ticket::STATUS_NOT_READ, Ticket::STATUS_READ, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_NEED_MORE_INFO, Ticket::STATUS_WAITING];

    /**
     * QQOQCP extrafields of the ticket, by translation key of their question
     */
    public const QQOQCP_FIELDS = [
        'QQOQCPWhat'    => 'digiriskdolibarr_qqoqcp_what',
        'QQOQCPWho'     => 'digiriskdolibarr_qqoqcp_who',
        'QQOQCPWhere'   => 'digiriskdolibarr_qqoqcp_where',
        'QQOQCPWhen'    => 'digiriskdolibarr_qqoqcp_when',
        'QQOQCPHow'     => 'digiriskdolibarr_qqoqcp_how',
        'QQOQCPWhy'     => 'digiriskdolibarr_qqoqcp_why',
        'QQOQCPHowMuch' => 'digiriskdolibarr_qqoqcp_howmuch',
    ];

    /**
     * @var array<int, string> Path of each ticket category, by id
     */
    protected array $categoryPaths = [];

    /**
     * @var array<int, string> Label of each GP/UT, by id
     */
    protected array $elementLabels = [];

    /**
     * Build the workbook of the given tickets and send it to the browser
     *
     * @param  array  $ticketIds Ticket ids, in the order of the list
     * @param  string $fileName  Name of the file, without extension
     * @return void
     */
    public function export(array $ticketIds, string $fileName): void
    {
        $this->build($ticketIds);
        $this->send($fileName);
    }

    /**
     * Add the filters of the ticket list the tickets come from, as the list read them from the request
     *
     * @param  Ticket $ticket Object of the list
     * @return void
     */
    public function addTicketListFilters(Ticket $ticket): void
    {
        global $extrafields, $langs, $search, $search_all, $search_array_options, $search_societe;

        if (!empty($search_all)) {
            $this->filters[$langs->transnoentities('XlsxSearch')] = (string) $search_all;
        }

        // The status may stand for all the open tickets, or all the closed ones
        $criteria = is_array($search) ? $search : [];
        if (!empty($criteria['fk_statut'])) {
            $criteria['fk_statut'] = array_map(function ($value) use ($langs): string {
                $groups = ['openall' => 'OpenAll', 'closeall' => 'ClosedAll'];
                return isset($groups[$value]) ? $langs->transnoentities($groups[$value]) : (string) $value;
            }, (array) $criteria['fk_statut']);
        }
        $this->addFieldFilters($ticket, $criteria);
        if (!empty($search_societe)) {
            $this->filters[$langs->transnoentities('ThirdParty')] = (string) $search_societe;
        }
        if ($extrafields instanceof ExtraFields && is_array($search_array_options)) {
            $this->addExtraFieldFilters($extrafields, $ticket->table_element, $search_array_options);
        }

        // Categories of the filter added to the list by Digirisk
        $categoryLabels = [];
        foreach ((array) GETPOST('search_category_ticket_list', 'array') as $categoryId) {
            if ((int) $categoryId == -2) {
                $categoryLabels[] = $langs->transnoentities('NotCategorized');
            } elseif ((int) $categoryId > 0) {
                $categorie        = new Categorie($this->db);
                $categoryLabels[] = $categorie->fetch((int) $categoryId) > 0 ? $categorie->label : (string) $categoryId;
            }
        }
        if (!empty($categoryLabels)) {
            $this->filters[$langs->transnoentities('Categories')] = implode(', ', $categoryLabels);
        }
    }

    /**
     * Build the workbook: a summary, then the detailed sheets, then the sheets that sum the tickets up
     *
     * @param  array $ticketIds Ticket ids, in the order of the list
     * @return void
     */
    public function build(array $ticketIds): void
    {
        global $langs, $mysoc;

        $this->start($langs->transnoentities('TicketXlsxTitle') . ' - ' . $mysoc->name, 'Digirisk');

        $records = $this->fetchRecords(array_map('intval', $ticketIds));

        $this->addTicketsSheet($records);
        $this->addActionsSheet($records);
        $this->addQqoqcpSheet($records);
        $this->addCategoriesSheet($records);
        $this->addElementsSheet($records);
        $this->addMapSheet($records);
        $this->addMonthsSheet($records);
        $this->addAssigneesSheet($records);
        $this->fillSummarySheet($records);
    }

    /**
     * Load the tickets and everything Digirisk adds to them
     *
     * @param  array $ticketIds Ticket ids, in the order of the list
     * @return array            Records of the tickets, in the order of the list
     */
    protected function fetchRecords(array $ticketIds): array
    {
        global $langs;

        $ticket  = new Ticket($this->db);
        $tickets = [];
        foreach (array_chunk($ticketIds, 500) as $chunkIds) {
            // The extrafields come first: their rowid and fk_object would otherwise hide the ones of the ticket
            $sql  = 'SELECT ef.*, t.rowid, t.ref, t.subject, t.message, t.type_code, t.severity_code, t.fk_statut, t.progress, t.datec, t.date_close,';
            $sql .= ' t.fk_project, t.fk_user_assign, t.origin_email, s.nom as thirdparty_name, p.ref as project_ref, p.title as project_title';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'ticket as t';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'ticket_extrafields as ef ON ef.fk_object = t.rowid';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON s.rowid = t.fk_soc';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet as p ON p.rowid = t.fk_project';
            $sql .= ' WHERE t.rowid IN (' . implode(',', $chunkIds) . ')';

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $tickets[(int) $obj->rowid] = $obj;
                }
                $this->db->free($resql);
            }
        }

        $categoriesByTicket = $this->fetchCategories(array_keys($tickets));
        $actionsByTicket    = $this->fetchTicketActions($tickets);
        $accidentsByTicket  = $this->fetchAccidents(array_keys($tickets));

        // The GP/UT, the assignees and the people of the QQOQCP are read once for all the tickets
        $elementIds = [];
        $userIds    = [];
        foreach ($tickets as $obj) {
            $elementIds = array_merge($elementIds, $this->getIds($obj->digiriskdolibarr_ticket_service ?? ''));
            $userIds    = array_merge($userIds, $this->getIds($obj->digiriskdolibarr_qqoqcp_who ?? ''), [(int) $obj->fk_user_assign]);
        }
        $this->fetchElementLabels($elementIds);
        $this->fetchUserNames($userIds);

        $records = [];
        foreach ($ticketIds as $ticketId) {
            if (!isset($tickets[$ticketId])) {
                continue;
            }
            $obj     = $tickets[$ticketId];
            $status  = (int) $obj->fk_statut;
            $created = $this->getTimestamp($obj->datec);
            $closed  = $this->getTimestamp($obj->date_close);
            // The date of the declaration is the one typed on the public interface, when the ticket comes from it
            $date = $this->getTimestamp($obj->digiriskdolibarr_ticket_date ?? null) ?: $created;

            $elements = [];
            foreach ($this->getIds($obj->digiriskdolibarr_ticket_service ?? '') as $elementId) {
                $elements[$elementId] = $this->elementLabels[$elementId] ?? (string) $elementId;
            }

            $statusGroup = in_array($status, self::OPEN_STATUSES) ? 'open' : ($status == Ticket::STATUS_CANCELED ? 'canceled' : 'closed');

            $records[$ticketId] = [
                'ticket'      => $obj,
                'url'         => DOL_MAIN_URL_ROOT . '/ticket/card.php?id=' . $ticketId,
                'date'        => $date,
                'created'     => $created,
                'closed'      => $statusGroup !== 'open' ? $closed : 0,
                'status'      => $status,
                'statusLabel' => isset($ticket->labelStatus[$status]) ? $this->getPlainText($langs->transnoentitiesnoconv($ticket->labelStatus[$status])) : (string) $status,
                'statusGroup' => $statusGroup,
                'categories'  => $categoriesByTicket[$ticketId] ?? [],
                'elements'    => $elements,
                'declarant'   => trim(($obj->digiriskdolibarr_ticket_lastname ?? '') . ' ' . ($obj->digiriskdolibarr_ticket_firstname ?? '')),
                'assignee'    => $this->userNames[(int) $obj->fk_user_assign] ?? '',
                'delay'       => ($statusGroup === 'closed' && $closed >= $created && !empty($closed)) ? round(($closed - $created) / 86400, 1) : null,
                'age'         => $statusGroup === 'open' && !empty($created) ? round(($this->now - $created) / 86400, 1) : null,
                'actions'     => $actionsByTicket[$ticketId] ?? [],
                'accidents'   => $accidentsByTicket[$ticketId] ?? [],
                'qqoqcp'      => $this->getQqoqcp($obj),
            ];
        }

        return $records;
    }

    /**
     * Load the categories of the tickets, as their path from the top category
     *
     * @param  array $ticketIds Ticket ids
     * @return array            Category paths by category id, by ticket id
     */
    protected function fetchCategories(array $ticketIds): array
    {
        $categoriesByTicket = [];
        if (empty($ticketIds) || !isModEnabled('categorie')) {
            return $categoriesByTicket;
        }

        $categorie  = new Categorie($this->db);
        $categories = [];
        $sql        = 'SELECT rowid, label, fk_parent FROM ' . MAIN_DB_PREFIX . 'categorie';
        $sql       .= ' WHERE type = ' . (int) $categorie->MAP_ID['ticket'] . ' AND entity IN (' . getEntity('category') . ')';
        $resql      = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $categories[(int) $obj->rowid] = $obj;
            }
            $this->db->free($resql);
        }
        foreach ($categories as $categoryId => $category) {
            $labels = [$category->label];
            $parent = (int) $category->fk_parent;
            // A broken tree must not loop: a path never goes deeper than the number of categories
            while ($parent > 0 && isset($categories[$parent]) && count($labels) <= count($categories)) {
                array_unshift($labels, $categories[$parent]->label);
                $parent = (int) $categories[$parent]->fk_parent;
            }
            $this->categoryPaths[$categoryId] = implode(' > ', $labels);
        }

        foreach (array_chunk($ticketIds, 500) as $chunkIds) {
            $sql   = 'SELECT fk_ticket, fk_categorie FROM ' . MAIN_DB_PREFIX . 'categorie_ticket WHERE fk_ticket IN (' . implode(',', $chunkIds) . ')';
            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    if (isset($this->categoryPaths[(int) $obj->fk_categorie])) {
                        $categoriesByTicket[(int) $obj->fk_ticket][(int) $obj->fk_categorie] = $this->categoryPaths[(int) $obj->fk_categorie];
                    }
                }
                $this->db->free($resql);
            }
        }
        foreach ($categoriesByTicket as $ticketId => $categoryPaths) {
            // A register is implied by its sub-register: a ticket filed in both only counts in the sub-register
            foreach ($categoryPaths as $categoryId => $categoryPath) {
                foreach ($categoryPaths as $otherPath) {
                    if (strpos($otherPath, $categoryPath . ' > ') === 0) {
                        unset($categoryPaths[$categoryId]);
                        break;
                    }
                }
            }
            natcasesort($categoryPaths);
            $categoriesByTicket[$ticketId] = $categoryPaths;
        }

        return $categoriesByTicket;
    }

    /**
     * Load the actions and the tasks of the tickets, hanging under the parent task TKP-<ref> of each ticket
     *
     * @param  array $tickets Rows of the tickets, by id
     * @return array          Records of the actions, by ticket id
     */
    protected function fetchTicketActions(array $tickets): array
    {
        $refs        = [];
        $ticketByRef = [];
        foreach ($tickets as $ticketId => $obj) {
            if ((int) $obj->fk_project > 0) {
                $refs[] = "'" . $this->db->escape('TKP-' . $obj->ref) . "'";

                $ticketByRef['TKP-' . $obj->ref . '@' . (int) $obj->fk_project] = $ticketId;
            }
        }

        // The parent task of a ticket is found by its ref in the project of the ticket
        $ticketByParent = [];
        foreach (array_chunk($refs, 500) as $chunkRefs) {
            $resql = $this->db->query('SELECT rowid, ref, fk_projet FROM ' . MAIN_DB_PREFIX . 'projet_task WHERE ref IN (' . implode(',', $chunkRefs) . ')');
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $key = $obj->ref . '@' . (int) $obj->fk_projet;
                    if (isset($ticketByRef[$key])) {
                        $ticketByParent[(int) $obj->rowid] = $ticketByRef[$key];
                    }
                }
                $this->db->free($resql);
            }
        }

        return $this->fetchActions($ticketByParent);
    }

    /**
     * Load the accidents declared from the tickets
     *
     * @param  array $ticketIds Ticket ids
     * @return array            Accident refs, by ticket id
     */
    protected function fetchAccidents(array $ticketIds): array
    {
        $accidentsByTicket = [];
        foreach (array_chunk($ticketIds, 500) as $chunkIds) {
            $sql   = 'SELECT rowid, ref, fk_ticket FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident WHERE status >= 0 AND fk_ticket IN (' . implode(',', array_map('intval', $chunkIds)) . ') ORDER BY rowid ASC';
            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $accidentsByTicket[(int) $obj->fk_ticket][(int) $obj->rowid] = $obj->ref;
                }
                $this->db->free($resql);
            }
        }

        return $accidentsByTicket;
    }

    /**
     * Load the labels of the GP/UT
     *
     * @param  array $elementIds GP/UT ids
     * @return void
     */
    protected function fetchElementLabels(array $elementIds): void
    {
        foreach (array_chunk(array_unique($elementIds), 500) as $chunkIds) {
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
     * Answers of the QQOQCP analysis of a ticket
     *
     * @param  stdClass $obj Row of the ticket
     * @return array         Answer, by translation key of its question, empty when nothing was analysed
     */
    protected function getQqoqcp(stdClass $obj): array
    {
        $answers = [];
        foreach (self::QQOQCP_FIELDS as $labelKey => $field) {
            if ($field === 'digiriskdolibarr_qqoqcp_who') {
                $names = [];
                foreach ($this->getIds($obj->$field ?? '') as $userId) {
                    $names[] = $this->userNames[$userId] ?? (string) $userId;
                }
                $answer = implode(', ', $names);
            } else {
                $answer = $this->getPlainText($obj->$field ?? '');
            }
            $answers[$labelKey] = $answer;
        }

        return implode('', $answers) !== '' ? $answers : [];
    }

    /**
     * Sheet of the tickets: one line per ticket
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addTicketsSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('TicketXlsxSheetTickets'), $langs->transnoentities('TicketXlsxSheetTicketsDesc'));
        $columns = [
            ['label' => $langs->transnoentities('Ref'), 'width' => 16],
            ['label' => $langs->transnoentities('TicketXlsxDeclarationDate'), 'width' => 16, 'format' => 'datetime'],
            ['label' => $langs->transnoentities('DateCreation'), 'width' => 16, 'format' => 'datetime'],
            ['label' => $langs->transnoentities('Register'), 'width' => 30, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('Subject'), 'width' => 30, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('Message'), 'width' => 50, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('GP/UT'), 'width' => 26, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('Location'), 'width' => 20, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('TicketXlsxDeclarant'), 'width' => 20],
            ['label' => $langs->transnoentities('Phone'), 'width' => 14],
            ['label' => $langs->transnoentities('Email'), 'width' => 24],
            ['label' => $langs->transnoentities('ThirdParty'), 'width' => 20],
            ['label' => $langs->transnoentities('Project'), 'width' => 24],
            ['label' => $langs->transnoentities('Type'), 'width' => 14],
            ['label' => $langs->transnoentities('TicketSeverity'), 'width' => 12],
            ['label' => $langs->transnoentities('Status'), 'width' => 14],
            ['label' => $langs->transnoentities('Progression'), 'width' => 11, 'format' => 'percent'],
            ['label' => $langs->transnoentities('AssignedTo'), 'width' => 18],
            ['label' => $langs->transnoentities('DateClosing'), 'width' => 16, 'format' => 'datetime'],
            ['label' => $langs->transnoentities('TicketXlsxProcessingDays'), 'width' => 12, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('TicketXlsxOpenDays'), 'width' => 12, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('DigiriskXlsxActions'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsDone'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsOverdue'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('TicketXlsxQqoqcpDone'), 'width' => 11],
            ['label' => $langs->transnoentities('Accident'), 'width' => 14],
        ];

        $lines = [];
        foreach ($records as $record) {
            $obj = $record['ticket'];
            [$nbActions, $nbDone, $nbOverdue] = $this->countActions($record['actions']);

            $lines[] = [
                ['value' => $obj->ref, 'url' => $record['url']],
                $this->getExcelDate($record['date'], true),
                $this->getExcelDate($record['created'], true),
                implode("\n", $record['categories']),
                $obj->subject,
                $this->getPlainText($obj->message),
                implode("\n", $record['elements']),
                $obj->digiriskdolibarr_ticket_location ?? '',
                $record['declarant'],
                $obj->digiriskdolibarr_ticket_phone ?? '',
                $obj->origin_email,
                $obj->thirdparty_name,
                !empty($obj->project_ref) ? $obj->project_ref . ' - ' . $obj->project_title : '',
                !empty($obj->type_code) ? $langs->getLabelFromKey($this->db, 'TicketTypeShort' . $obj->type_code, 'c_ticket_type', 'code', 'label', $obj->type_code) : '',
                !empty($obj->severity_code) ? $langs->getLabelFromKey($this->db, 'TicketSeverityShort' . $obj->severity_code, 'c_ticket_severity', 'code', 'label', $obj->severity_code) : '',
                $record['statusLabel'],
                $obj->progress !== null && $obj->progress !== '' ? (int) $obj->progress / 100 : null,
                $record['assignee'],
                $this->getExcelDate($record['closed'], true),
                $record['delay'],
                $record['age'],
                $nbActions,
                $nbDone,
                $nbOverdue,
                !empty($record['qqoqcp']) ? $langs->transnoentities('Yes') : '',
                implode(', ', $record['accidents']),
            ];
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Sheet of the actions: one line per action or task of a ticket
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addActionsSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('TicketXlsxSheetActions'), $langs->transnoentities('TicketXlsxSheetActionsDesc'));
        $columns = array_merge([
            ['label' => $langs->transnoentities('Ticket'), 'width' => 16],
            ['label' => $langs->transnoentities('Subject'), 'width' => 28, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('GP/UT'), 'width' => 24, 'format' => 'longtext'],
        ], $this->getActionColumns());

        $lines = [];
        foreach ($records as $record) {
            foreach ($record['actions'] as $action) {
                $lines[] = array_merge([
                    ['value' => $record['ticket']->ref, 'url' => $record['url']],
                    $record['ticket']->subject,
                    implode("\n", $record['elements']),
                ], $this->getActionCells($action));
            }
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Sheet of the QQOQCP analyses: one line per analysed ticket
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addQqoqcpSheet(array $records): void
    {
        global $langs;

        $sheet   = $this->addSheet($langs->transnoentities('TicketXlsxSheetQqoqcp'), $langs->transnoentities('TicketXlsxSheetQqoqcpDesc'));
        $columns = [
            ['label' => $langs->transnoentities('Ticket'), 'width' => 16],
            ['label' => $langs->transnoentities('Subject'), 'width' => 28, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('GP/UT'), 'width' => 24, 'format' => 'longtext'],
        ];
        foreach (array_keys(self::QQOQCP_FIELDS) as $labelKey) {
            $columns[] = ['label' => $langs->transnoentities($labelKey), 'width' => 30, 'format' => 'longtext'];
        }

        $lines = [];
        foreach ($records as $record) {
            if (!empty($record['qqoqcp'])) {
                $lines[] = array_merge([
                    ['value' => $record['ticket']->ref, 'url' => $record['url']],
                    $record['ticket']->subject,
                    implode("\n", $record['elements']),
                ], array_values($record['qqoqcp']));
            }
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Columns summing a group of tickets up
     *
     * @param  string $label Head of the column of the group labels
     * @return array         Columns
     */
    protected function getStatsColumns(string $label): array
    {
        global $langs;

        return [
            ['label' => $label, 'width' => 40, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('XlsxTotal'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('TicketXlsxOpen'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('TicketXlsxClosed'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('TicketXlsxCanceled'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('TicketXlsxClosedShare'), 'width' => 11, 'format' => 'percent'],
            ['label' => $langs->transnoentities('TicketXlsxAverageProcessingDays'), 'width' => 13, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('DigiriskXlsxActions'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsDone'), 'width' => 10, 'format' => 'int'],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsOverdue'), 'width' => 10, 'format' => 'int'],
        ];
    }

    /**
     * Cells summing a group of tickets up, after its label
     *
     * @param  array $records Records of the tickets of the group
     * @return array          Cells
     */
    protected function getStatsCells(array $records): array
    {
        $groups    = ['open' => 0, 'closed' => 0, 'canceled' => 0];
        $delays    = [];
        $nbActions = 0;
        $nbDone    = 0;
        $nbOverdue = 0;
        foreach ($records as $record) {
            $groups[$record['statusGroup']]++;
            if ($record['delay'] !== null) {
                $delays[] = $record['delay'];
            }
            [$actions, $done, $overdue] = $this->countActions($record['actions']);
            $nbActions += $actions;
            $nbDone    += $done;
            $nbOverdue += $overdue;
        }

        return [
            count($records),
            $groups['open'],
            $groups['closed'],
            $groups['canceled'],
            count($records) > 0 ? $groups['closed'] / count($records) : null,
            !empty($delays) ? round(array_sum($delays) / count($delays), 1) : null,
            $nbActions,
            $nbDone,
            $nbOverdue,
        ];
    }

    /**
     * Write a sheet of groups of tickets, each group with its figures
     *
     * @param  string $titleKey       Translation key of the sheet title
     * @param  string $descriptionKey Translation key of the sheet description
     * @param  string $label          Head of the column of the group labels
     * @param  array  $groups         Records of the tickets, by group label
     * @return void
     */
    protected function addGroupsSheet(string $titleKey, string $descriptionKey, string $label, array $groups): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        uksort($groups, function ($a, $b) use ($notFilled): int {
            // What was not filled in comes last
            return [$a === $notFilled, (string) $a] <=> [$b === $notFilled, (string) $b] ?: strnatcasecmp((string) $a, (string) $b);
        });

        $sheet = $this->addSheet($langs->transnoentities($titleKey), $langs->transnoentities($descriptionKey));
        $lines = [];
        foreach ($groups as $groupLabel => $groupRecords) {
            $lines[] = array_merge([(string) $groupLabel], $this->getStatsCells($groupRecords));
        }

        $this->writeTable($sheet, 1, $this->getStatsColumns($label), $lines, 1);
    }

    /**
     * Sheet of the registers: the tickets of each category, a ticket counting in each of its categories
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addCategoriesSheet(array $records): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        $groups    = $this->groupBy($records, function (array $record) use ($notFilled): array {
            return !empty($record['categories']) ? array_values($record['categories']) : [$notFilled];
        });

        $this->addGroupsSheet('TicketXlsxSheetCategories', 'TicketXlsxSheetCategoriesDesc', $langs->transnoentities('Register'), $groups);
    }

    /**
     * Sheet of the GP/UT: the tickets of each GP/UT, a ticket counting in each of its GP/UT
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addElementsSheet(array $records): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        $groups    = $this->groupBy($records, function (array $record) use ($notFilled): array {
            return !empty($record['elements']) ? array_values($record['elements']) : [$notFilled];
        });

        $this->addGroupsSheet('TicketXlsxSheetElements', 'TicketXlsxSheetElementsDesc', $langs->transnoentities('GP/UT'), $groups);
    }

    /**
     * Sheet of the assignees: the tickets each user is in charge of
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addAssigneesSheet(array $records): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        $groups    = $this->groupBy($records, function (array $record) use ($notFilled): array {
            return [$record['assignee'] !== '' ? $record['assignee'] : $notFilled];
        });

        $this->addGroupsSheet('TicketXlsxSheetAssignees', 'TicketXlsxSheetAssigneesDesc', $langs->transnoentities('AssignedTo'), $groups);
    }

    /**
     * Sheet crossing the GP/UT with the registers
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addMapSheet(array $records): void
    {
        global $langs;

        $notFilled = $langs->transnoentities('XlsxNotFilled');
        $rows      = [];
        $columns   = [];
        $counts    = [];
        $rowTotals = [];
        foreach ($records as $record) {
            $elements   = !empty($record['elements']) ? $record['elements'] : ['' => $notFilled];
            $categories = !empty($record['categories']) ? $record['categories'] : ['' => $notFilled];
            foreach ($elements as $elementKey => $elementLabel) {
                $rows['e' . $elementKey] = $elementLabel;
                $rowTotals['e' . $elementKey] = ($rowTotals['e' . $elementKey] ?? 0) + 1;
                foreach ($categories as $categoryKey => $categoryLabel) {
                    $columns['c' . $categoryKey] = $categoryLabel;
                    $counts['e' . $elementKey]['c' . $categoryKey] = ($counts['e' . $elementKey]['c' . $categoryKey] ?? 0) + 1;
                }
            }
        }
        $this->sortLabels($rows, $notFilled);
        $this->sortLabels($columns, $notFilled);

        $this->addMatrixSheet($langs->transnoentities('TicketXlsxSheetMap'), $langs->transnoentities('TicketXlsxSheetMapDesc'), $langs->transnoentities('GP/UT'), $rows, $columns, $counts, $rowTotals, count($records));
    }

    /**
     * Sheet of the months: the tickets declared, closed and still open each month, and the declared ones of each
     * register
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function addMonthsSheet(array $records): void
    {
        global $langs;

        $notFilled  = $langs->transnoentities('XlsxNotFilled');
        $first      = 0;
        $last       = 0;
        $declared   = [];
        $closed     = [];
        $byCategory = [];
        $categories = [];
        foreach ($records as $record) {
            $first = empty($first) ? $record['date'] : min($first, $record['date']);
            $last  = max($last, $record['date'], $record['closed']);
            $month = $this->getMonthKey($record['date']);
            $declared[$month] = ($declared[$month] ?? 0) + 1;
            if ($record['statusGroup'] === 'closed' && !empty($record['closed'])) {
                $closedMonth = $this->getMonthKey($record['closed']);
                $closed[$closedMonth] = ($closed[$closedMonth] ?? 0) + 1;
            }
            foreach (!empty($record['categories']) ? $record['categories'] : ['' => $notFilled] as $categoryKey => $categoryLabel) {
                $categories['c' . $categoryKey] = $categoryLabel;
                $byCategory[$month]['c' . $categoryKey] = ($byCategory[$month]['c' . $categoryKey] ?? 0) + 1;
            }
        }
        $this->sortLabels($categories, $notFilled);

        $sheet   = $this->addSheet($langs->transnoentities('TicketXlsxSheetMonths'), $langs->transnoentities('TicketXlsxSheetMonthsDesc'));
        $columns = [
            ['label' => $langs->transnoentities('XlsxMonth'), 'width' => 16, 'format' => 'month'],
            ['label' => $langs->transnoentities('TicketXlsxDeclared'), 'width' => 11, 'format' => 'int'],
            ['label' => $langs->transnoentities('TicketXlsxClosed'), 'width' => 11, 'format' => 'int'],
            ['label' => $langs->transnoentities('TicketXlsxOpenAtMonthEnd'), 'width' => 13, 'format' => 'int'],
        ];
        foreach ($categories as $categoryLabel) {
            $columns[] = ['label' => $categoryLabel, 'width' => 14, 'format' => 'int'];
        }

        $lines = [];
        foreach ($this->getMonthKeys($first, $last) as $month) {
            // Still open at the end of a month: declared up to it, and neither closed nor canceled by then
            $open = 0;
            foreach ($records as $record) {
                $endMonth = $record['statusGroup'] !== 'open' && !empty($record['closed']) ? $this->getMonthKey($record['closed']) : '';
                $open    += ($this->getMonthKey($record['date']) <= $month && ($endMonth === '' || $endMonth > $month)) ? 1 : 0;
            }
            $line = [$this->getMonthExcelDate($month), $declared[$month] ?? 0, $closed[$month] ?? 0, $open];
            foreach (array_keys($categories) as $categoryKey) {
                $line[] = $byCategory[$month][$categoryKey] ?? 0;
            }
            $lines[] = $line;
        }

        $this->writeTable($sheet, 1, $columns, $lines, 1);
    }

    /**
     * Summary: what was exported, the key figures, the statuses, the actions and the contents of the workbook
     *
     * @param  array $records Records of the tickets
     * @return void
     */
    protected function fillSummarySheet(array $records): void
    {
        global $langs;

        $nbTickets    = count($records);
        $statuses     = [];
        $statusLabels = [];
        $groups       = ['open' => 0, 'closed' => 0, 'canceled' => 0];
        $delays       = [];
        $ages         = [];
        $withElement  = 0;
        $withQqoqcp   = 0;
        $withAccident = 0;
        $actionTypes  = [];
        $nbActions    = 0;
        $nbDone       = 0;
        $nbOverdue    = 0;
        $first        = 0;
        $last         = 0;
        foreach ($records as $record) {
            $statuses[$record['status']] = ($statuses[$record['status']] ?? 0) + 1;
            $statusLabels[$record['status']] = $record['statusLabel'];
            $groups[$record['statusGroup']]++;
            if ($record['delay'] !== null) {
                $delays[] = $record['delay'];
            }
            if ($record['age'] !== null) {
                $ages[] = $record['age'];
            }
            $withElement  += !empty($record['elements']) ? 1 : 0;
            $withQqoqcp   += !empty($record['qqoqcp']) ? 1 : 0;
            $withAccident += !empty($record['accidents']) ? 1 : 0;
            foreach ($record['actions'] as $action) {
                $actionTypes[$action['type']] = ($actionTypes[$action['type']] ?? 0) + 1;
            }
            [$actions, $done, $overdue] = $this->countActions($record['actions']);
            $nbActions += $actions;
            $nbDone    += $done;
            $nbOverdue += $overdue;
            $first      = empty($first) ? $record['date'] : min($first, $record['date']);
            $last       = max($last, $record['date']);
        }
        sort($delays);

        $row = $this->writeSummaryHead($langs->transnoentities('TicketXlsxTitle'));
        $row = $this->writeSummaryFilters($row);
        $row = $this->writeSummaryKeyFigures($row, [
            ['label' => $langs->transnoentities('TicketXlsxTicketsNumber'), 'value' => $nbTickets],
            ['label' => $langs->transnoentities('TicketXlsxPeriod'), 'value' => !empty($first) ? dol_print_date($first, 'day') . ' - ' . dol_print_date($last, 'day') : null, 'format' => 'text'],
            ['label' => $langs->transnoentities('TicketXlsxOpen'), 'value' => $groups['open'], 'total' => $nbTickets],
            ['label' => $langs->transnoentities('TicketXlsxClosed'), 'value' => $groups['closed'], 'total' => $nbTickets],
            ['label' => $langs->transnoentities('TicketXlsxCanceled'), 'value' => $groups['canceled'], 'total' => $nbTickets],
            ['label' => $langs->transnoentities('TicketXlsxAverageProcessingDays'), 'value' => !empty($delays) ? round(array_sum($delays) / count($delays), 1) : null, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('TicketXlsxMedianProcessingDays'), 'value' => !empty($delays) ? $delays[(int) floor((count($delays) - 1) / 2)] : null, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('TicketXlsxAverageOpenDays'), 'value' => !empty($ages) ? round(array_sum($ages) / count($ages), 1) : null, 'format' => 'decimal'],
            ['label' => $langs->transnoentities('TicketXlsxWithElement'), 'value' => $withElement, 'total' => $nbTickets],
            ['label' => $langs->transnoentities('TicketXlsxWithQqoqcp'), 'value' => $withQqoqcp, 'total' => $nbTickets],
            ['label' => $langs->transnoentities('TicketXlsxWithAccident'), 'value' => $withAccident, 'total' => $nbTickets],
            ['label' => $langs->transnoentities('DigiriskXlsxActions'), 'value' => $nbActions],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsDone'), 'value' => $nbDone, 'total' => $nbActions],
            ['label' => $langs->transnoentities('DigiriskXlsxActionsOverdue'), 'value' => $nbOverdue, 'total' => $nbActions],
        ]);

        // The statuses in the order a ticket goes through them
        ksort($statuses);
        $lines = [];
        foreach ($statuses as $status => $count) {
            $lines[] = [$statusLabels[$status], $count, $count / $nbTickets];
        }
        $row = $this->writeSummaryTable($row, $langs->transnoentities('Status'), [$langs->transnoentities('Status'), $langs->transnoentities('TicketXlsxTicketsNumber'), $langs->transnoentities('XlsxShare')], $lines, ['text', 'int', 'percent']);

        if (!empty($actionTypes)) {
            ksort($actionTypes);
            $lines = [];
            foreach ($actionTypes as $label => $count) {
                $lines[] = [(string) $label, $count, $count / $nbActions];
            }
            $row = $this->writeSummaryTable($row, $langs->transnoentities('DigiriskActionType'), [$langs->transnoentities('DigiriskActionType'), $langs->transnoentities('DigiriskXlsxActions'), $langs->transnoentities('XlsxShare')], $lines, ['text', 'int', 'percent']);
        }

        $this->writeSummaryContents($row);
    }
}
