<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    class/ticketdashboard.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class file for manage TicketDashboard
 */

// load Dolibarr librairies
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// load DigiriskDolibarr librairies
require_once __DIR__ . '/digiriskelement.class.php';
require_once __DIR__ . '/accident.class.php';

/**
 * Class to manage stats for tickets
 */
class TicketDashboard extends DigiriskDolibarrDashboard
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Module name
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string SQL FROM
     */
    public string $from = '';

    /**
     * @var string|null SQL JOIN
     */
    public ?string $join = '';

    /**
     * @var string|null SQL WHERE
     */
    public ?string $where = '';

    /**
     * Constructor
     *
     * @param DoliDB $db             Database handler
     * @param string|null $moreJoin  More SQL JOIN
     * @param string|null $moreWhere More SQL filters (' AND ...')
     */
    public function __construct(DoliDB $db, ?string $moreJoin = '', ?string $moreWhere = '')
    {
        $this->db = $db;

        $this->from = MAIN_DB_PREFIX . 'ticket as t';
        if (dol_strlen($moreJoin) > 0) {
            $this->join .= $moreJoin;
        }
        $this->where  = 't.fk_statut > 0';
        $this->where .= ' AND t.entity IN (' . getEntity('ticket') . ')';
        if (dol_strlen($moreWhere) > 0) {
            $this->where .= $moreWhere;
        }
    }

    /**
     * Return ticket number by month for a year
     *
     * @param  int       $year Year
     * @return array           Array of values
     * @throws Exception
     */
    public function getNbByMonth(int $year): array
    {
        $sql  = "SELECT date_format(t.datec,'%m') as dc, COUNT(*) as nb";
        $sql .= ' FROM ' . $this->from;
        $sql .= $this->join;
        $sql .= " WHERE t.datec BETWEEN '" . $this->db->idate(dol_get_first_day($year)) . "' AND '" . $this->db->idate(dol_get_last_day($year)) . "'";
        $sql .= ' AND ' . $this->where;
        $sql .= ' GROUP BY dc';
        $sql .= $this->db->order('dc', 'DESC');

        return $this->_getNbByMonth($sql);
    }

    /**
     * Return nb, total and average
     *
     * @return array     Array of values
     * @throws Exception
     */
    public function getAllByYear()
    {
        $sql  = "SELECT date_format(t.datec,'%Y') as year, COUNT(*) as nb";
        $sql .= ' FROM ' . $this->from;
        $sql .= $this->join;
        $sql .= ' WHERE ' . $this->where;
        $sql .= ' GROUP BY year';
        $sql .= $this->db->order('year', 'DESC');

        return $this->_getAllByYear($sql);
    }

    /**
     * Return nb ticket by GP/UT and Ticket tags
     *
     * @param  int       $date_start Timestamp date start
     * @param  int       $dateEnd    Timestamp date end
     * @return array                 Array of values
     * @throws Exception
     */
    public function getNbTicketByDigiriskElementAndTicketTags(int $dateStart, int $dateEnd): array
    {
        global $conf, $langs;

        $digiriskElement  = new DigiriskElement($this->db);
        $categorie        = new Categorie($this->db);
        $accident         = new Accident($this->db);
        $accidentWorkStop = new AccidentWorkStop($this->db);
        $ticket           = new Ticket($this->db);

        $filter               = " AND o.datec BETWEEN '" . $this->db->idate($dateStart) . "' AND '" . $this->db->idate($dateEnd) . "'";
        $digiriskElementFlats = $digiriskElement->fetchDigiriskElementFlat(0);
        if (is_array($digiriskElementFlats) && !empty($digiriskElementFlats)) {
            foreach ($digiriskElementFlats as $digiriskElement) {
                $digiriskElements[$digiriskElement['object']->id] = $digiriskElement['object'];
            }
        }

        $digiriskElements   = dol_sort_array($digiriskElements, 'ranks');
        $mainCategoryObject = $categorie->rechercher(getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY'), '', 'ticket', true);
        $categories         = $mainCategoryObject[0]->get_filles();

        $arrayReturn             = [];
        $ticketCategoriesCounter = [];

        //Creating columns labels
        if (is_array($categories) && !empty($categories)) {
            // Main categories
            foreach($categories as $category) {
                $labelsArray['labels'][$category->id] = $category->label;

                $categorie->fetch($category->id);
                $tickets[$category->id] = getObjectsInCategDigirisk($categorie, 'ticket', 0, 0, 0, '', 'ASC', $filter);

                if (is_array($tickets[$category->id]) && !empty($tickets[$category->id])) {
                    $ticketCategoriesCounter[$langs->trans('Register') . ' ' . $category->label] = count($tickets[$category->id]);
                }

                $mainCategoriesIds[$category->id] = $category->id;
                $childrenCategories = $category->get_filles();

                // Children categories
                if (is_array($childrenCategories) && !empty($childrenCategories)) {
                    foreach($childrenCategories as $childCategory) {
                        $labelsArray['labels'][$childCategory->id] = $childCategory->label;

                        $categorie->fetch($childCategory->id);
                        $tickets[$childCategory->id] = getObjectsInCategDigirisk($categorie, 'ticket', 0, 0, 0, '', 'ASC', $filter);

                        // Categories sub ranges
                        if ($childCategory->label == $langs->trans('AccidentWithDIAT')) {
                            $accidentWorkStopTimeRangesJson = $conf->global->DIGIRISKDOLIBARR_TICKET_STATISTICS_ACCIDENT_TIME_RANGE;
                            $accidentWorkStopTimeRanges     = json_decode($accidentWorkStopTimeRangesJson, true);
                            if (is_array($accidentWorkStopTimeRanges) && !empty($accidentWorkStopTimeRanges)) {
                                foreach($accidentWorkStopTimeRanges as $accidentWorkStopTimeRangeLabel => $accidentWorkStopTimeRange) {
                                    $labelsArray['labels'][$accidentWorkStopTimeRangeLabel] = $accidentWorkStopTimeRange;
                                }
                            }
                        }
                    }
                }
            }
            $labelsArray['labels']['Total'] = $langs->trans('Total');
        }

        $allAccidentsWithFkTicket = $accident->fetchAll('', '', 0, 0, ['customsql' => 'fk_ticket > 0']);

        if (is_array($digiriskElements) && !empty($digiriskElements)) {
            foreach($digiriskElements as $digiriskElement) {
                $arrayKey = $digiriskElement->ref . ' - ' . $digiriskElement->label;
                if (is_array($labelsArray['labels']) && !empty($labelsArray['labels'])) {
                    foreach($labelsArray['labels'] as $categoryId => $categoryLabel) {
                        $arrayReturn[$arrayKey][$categoryId] = 0;

                        if (is_int($categoryId)) {
                            if (is_array($tickets[$categoryId]) && !empty($tickets[$categoryId])) {
                                foreach($tickets[$categoryId] as $ticketsWithThisCategory) {
                                    if ($ticketsWithThisCategory->array_options['options_digiriskdolibarr_ticket_service'] == $digiriskElement->id) {
                                        $arrayReturn[$arrayKey][$categoryId] += 1;
                                    }
                                }
                            }
                        } else if (strstr($categoryLabel, ':')) {
                            $accidentWorkStopTimeRangeDetails = explode(':', $categoryLabel);
                            $constraintMultiplicator          = $accidentWorkStopTimeRangeDetails[2] == 'days' ? 1 : ($accidentWorkStopTimeRangeDetails[2] == 'years' ? 365 : 21);
                            $constraintInDays                 = $accidentWorkStopTimeRangeDetails[1];
                            $constraintComparator             = $accidentWorkStopTimeRangeDetails[0] == 'less' ? '<' : '>';

                            // Find accidents with filter and add it to counter
                            if (is_array($allAccidentsWithFkTicket) && !empty($allAccidentsWithFkTicket)) {
                                foreach($allAccidentsWithFkTicket as $accidentWithFkTicket) {
                                    $ticket->fetch($accidentWithFkTicket->fk_ticket);
                                    if ($ticket->array_options['options_digiriskdolibarr_ticket_service'] == $digiriskElement->id) {
                                        $accidentWorkStopList = $accidentWorkStop->fetchFromParent($accidentWithFkTicket->id);
                                        $accidentWorkStopDaysCounter = 0;
                                        if (is_array($accidentWorkStopList) && !empty($accidentWorkStopList)) {
                                            foreach ($accidentWorkStopList as $accidentWorkStop) {
                                                $accidentWorkStopDaysCounter += $accidentWorkStop->workstop_days;
                                            }
                                        }

                                        // Turn constraint assertion (less:2:days) into executable logical condition
                                        $condition = "\$result = \$accidentWorkStopDaysCounter $constraintComparator \$constraintInDays*$constraintMultiplicator;";
                                        eval($condition);

                                        if ($result) {
                                            $arrayReturn[$arrayKey][$categoryId] += 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // Total for given digirisk element
                foreach($arrayReturn[$arrayKey] as $digiriskElementCategoryId => $digiriskElementCategoriesCounter) {
                    if (in_array($digiriskElementCategoryId, $mainCategoriesIds)) {
                        $arrayReturn[$arrayKey]['Total'] += $digiriskElementCategoriesCounter;
                    }
                }
            }
        }

        foreach($arrayReturn as $data) {
            foreach($data as $categoryId => $categoryCounter) {
                $totalArray['Total'][$categoryId] += $categoryCounter;
            }
        }

        // Array replace instead of array merge to avoid losing keys
        $arrayReturn = array_replace($labelsArray, $arrayReturn, $totalArray);

        return [$arrayReturn, $ticketCategoriesCounter];
    }

    /**
     * Load dashboard info ticket
     *
     * @param  array     $moreParams Parameters for load dashboard info
     * @return array
     * @throws Exception
     */
    public function load_dashboard(array $moreParams = []): array
    {
        global $langs;

        $confName        = dol_strtoupper($this->module) . '_DASHBOARD_CONFIG';
        $dashboardConfig = json_decode(getDolUserString($confName));
        $array = ['graphs' => [], 'lists' => [], 'disabledGraphs' => []];

        $mainCategories = saturne_fetch_all_object_type('Categorie', '', '', 0, 0, ['customsql' => 'fk_parent = ' . getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY')]);

        $digiriskElement = new DigiriskElement($this->db);

        $moreParams['filter'] = ' AND t.element_type = "groupment"';
        $digiriskElements     = $digiriskElement->getActiveDigiriskElements('current', $moreParams);

        if (empty($dashboardConfig->graphs->NumberOfTicketsByMonth->hide)) {
            $array['graphs'][] = $this->getTicketsByMonth();
        } else {
            $array['disabledGraphs']['NumberOfTicketsByMonth'] = $langs->transnoentities('NumberOfTicketsByMonth');
        }
        if (empty($dashboardConfig->graphs->NumberOfTicketsByMainTagAndByDigiriskElement->hide)) {
            $array['graphs'][] = $this->getTicketsByMainTagAndByDigiriskElement($mainCategories, $digiriskElements);
        } else {
            $array['disabledGraphs']['NumberOfTicketsByMainTagAndByDigiriskElement'] = $langs->transnoentities('NumberOfTicketsByMainTagAndByDigiriskElement');
        }
        if (empty($dashboardConfig->graphs->NumberOfTicketsByMainSubTagAndByDigiriskElement->hide)) {
            $array['graphs'][] = $this->getTicketsByMainSubTagAndByDigiriskElement($mainCategories, $digiriskElements);
        } else {
            $array['disabledGraphs']['NumberOfTicketsByMainSubTagAndByDigiriskElement'] = $langs->transnoentities('NumberOfTicketsByMainSubTagAndByDigiriskElement');
        }
        if (empty($dashboardConfig->graphs->NumberOfTicketsByYear->hide)) {
            //$array['lists'][] = $this->getTicketsByYear();
        } else {
            $array['disabledGraphs']['NumberOfTicketsByYear'] = $langs->transnoentities('NumberOfTicketsByYear');
        }

        return $array;
    }

    /**
     * Get tickets by month
     *
     * @return array $array Graph datas (label/color/type/title/data etc..)
     */
    public function getTicketsByMonth(): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NumberOfTicketsByMonth');
        $array['name']  = 'NumberOfTicketsByMonth';
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $dateStart = dol_mktime(0, 0, 0, GETPOST('dateStartmonth', 'int'), GETPOST('dateStartday', 'int'), GETPOST('dateStartyear', 'int'));
        $dateEnd   = dol_mktime(23, 59, 59, GETPOST('dateEndmonth', 'int'), GETPOST('dateEndday', 'int'), GETPOST('dateEndyear', 'int'));
        $startYear = !empty($dateStart) ? strftime('%Y', $dateStart) : strftime('%Y', dol_now()) - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
        $endYear   = strftime('%Y', !empty($dateEnd) ? $dateEnd : dol_now());

        $labels = [];
        for ($i = $startYear; $i <= $endYear; $i++) {
            $labels[] = $i;
        }
        foreach ($labels as $label) {
            $array['labels'][] = ['label' => $label];
        }

        $tickets = $this->getNbByMonthWithPrevYear($startYear, $endYear, getDolGlobalInt('SOCIETE_FISCAL_MONTH_START'));
        if (is_array($tickets) && !empty($tickets)) {
            if (!empty($dateStart) && !empty($dateEnd) && $startYear == $endYear) {
                // Extract month values from POST parameters, assuming zero-based indexing
                $startMonth = intval(GETPOST('dateStartmonth', 'int')) - 1;
                $endMonth   = intval(GETPOST('dateEndmonth', 'int')) - 1;

                // Iterate through $tickets and filter based on month range
                foreach ($tickets as $key => $ticket) {
                    if ($key >= $startMonth && $key <= $endMonth) {
                        $array['data'][] = $ticket;
                    }
                }
            } else {
                $array['data'] = $tickets;
            }
        }

        return $array;
    }

    /**
     * Get tickets by main tag and digirisk element
     *
     * @param  array|int $mainCategories Int <0 if KO, array of main categories if OK
     * @return array     $array          Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getTicketsByMainTagAndByDigiriskElement($mainCategories, $digiriskElements): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NumberOfTicketsByMainTagAndByDigiriskElement');
        $array['name']  = 'NumberOfTicketsByMainTagAndByDigiriskElement';
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        if (is_array($mainCategories) && !empty($mainCategories)) {
            foreach ($mainCategories as $mainCategory) {
                $array['labels'][$mainCategory->id] = [
                    'label' => $mainCategory->label,
                    'color' => '#' . $mainCategory->color
                ];
                if (is_array($digiriskElements) && !empty($digiriskElements)) {
                    foreach ($digiriskElements as $digiriskElement) {
                        $tickets                                = saturne_fetch_all_object_type('Ticket', '', '', 0, 0, ['customsql' => 'cp.fk_categorie = ' . $mainCategory->id  . ' AND eft.digiriskdolibarr_ticket_service = ' . $digiriskElement->id . ' AND ' . $this->where], 'AND', true, true, true, $this->join);
                        $array['data'][$digiriskElement->id][0] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
                        $array['data'][$digiriskElement->id][]  = is_array($tickets) && !empty($tickets) ? count($tickets) : 0;
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Get tickets by main sub tag and digirisk element
     *
     * @param  array|int $mainCategories Int <0 if KO, array of main categories if OK
     * @return array     $array          Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getTicketsByMainSubTagAndByDigiriskElement($mainCategories, $digiriskElements): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NumberOfTicketsByMainSubTagAndByDigiriskElement');
        $array['name']  = 'NumberOfTicketsByMainSubTagAndByDigiriskElement';
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $category        = new Categorie($this->db);

        if (is_array($mainCategories) && !empty($mainCategories)) {
            foreach ($mainCategories as $mainCategory) {
                $category->fetch($mainCategory->id);
                $mainSubCategories = $category->get_filles();
                if (is_array($mainSubCategories) && !empty($mainSubCategories)) {
                    foreach ($mainSubCategories as $mainSubCategory) {
                        $array['labels'][$mainSubCategory->id] = [
                            'label' => $mainSubCategory->label,
                            'color' => dol_strlen($mainSubCategory->color) > 0 ? '#' . $mainSubCategory->color : '',
                        ];
                        if (is_array($digiriskElements) && !empty($digiriskElements)) {
                            foreach ($digiriskElements as $digiriskElement) {
                                $tickets                                = saturne_fetch_all_object_type('Ticket', '', '', 0, 0, ['customsql' => 'cp.fk_categorie = ' . $mainSubCategory->id  . ' AND eft.digiriskdolibarr_ticket_service = ' . $digiriskElement->id . ' AND ' . $this->where], 'AND', true, true, true, $this->join);
                                $array['data'][$digiriskElement->id][0] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
                                $array['data'][$digiriskElement->id][]  = is_array($tickets) && !empty($tickets) ? count($tickets) : 0;
                            }
                        }
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Get tickets by year
     *
     * @return array $array Graph datas (label/color/type/title/data etc..)
     */
    public function getTicketsByYear(): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NumberOfTicketsByYear');
        $array['name']  = 'NumberOfTicketsByYear';
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['type']   = 'list';
        $array['labels'] = ['Year', 'Tickets', 'Percentage'];

        $arrayTicketByYear = [];
        $tickets           = $this->getAllByYear();
        if (is_array($tickets) && !empty($tickets)) {
            foreach ($tickets as $key => $ticket) {
                $arrayTicketByYear[$key]['Ref']['value']        = $ticket['year'];
                $arrayTicketByYear[$key]['Tickets']['value']    = $ticket['nb'];
                $arrayTicketByYear[$key]['Percentage']['value'] = price2num($ticket['avg'] ?? 0, 2) . ' %';
            }
        }

        $array['data'] = $arrayTicketByYear;

        return $array;
    }
}
