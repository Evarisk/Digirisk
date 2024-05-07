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
require_once __DIR__ . '/digiriskstats.php';
require_once __DIR__ . '/digiriskelement.class.php';
require_once __DIR__ . '/accident.class.php';

/**
 * Class to manage stats for tickets
 */
class TicketDashboard extends DigiriskStats
{
    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element;

    public $socid;
    public $userid;

    public $from;
    public $field;
    public $where;
    public $join;

	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			          Database handler
	 * 	@param 	int			$socid		          ID third party for filter. This value must be forced during the new to external user company if user is an external user.
	 * 	@param	int			$userid    	          ID user for filter (creation user)
	 * 	@param	int			$userassignid    	  ID user for filter (user assign)
	 * 	@param	int			$categticketid        ID category of ticket for filter
	 */
	public function __construct($db, $socid = 0, $userid = 0, $userassignid = 0, $categticketid = 0, $moreFrom = '', $moreJoin = '', $moreWhere = '')
	{
		$this->db = $db;
		$this->socid = ($socid > 0 ? $socid : 0);
		$this->userid = $userid;
		$this->join = '';

		$object = new Ticket($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element." as tk";
        if (dol_strlen($moreFrom) > 0) {
            $this->from .= $moreFrom;
        }
        if (dol_strlen($moreJoin) > 0) {
            $this->join .= $moreJoin;
        }
		$this->where = "tk.fk_statut >= 0";
		$this->where .= " AND tk.entity IN (".getEntity('ticket').")";
		if ($this->socid) {
			$this->where .= " AND tk.fk_soc = ".((int) $this->socid);
		}

		if (is_array($this->userid) && count($this->userid) > 0) {
			$this->where .= ' AND tk.fk_user_create IN ('.$this->db->sanitize(join(',', $this->userid)).')';
		} elseif ($this->userid > 0) {
			$this->where .= " AND tk.fk_user_create = ".((int) $this->userid);
		}

		if ($userassignid) {
			$this->where .= " AND tk.fk_user_assign = ".((int) $userassignid);
		}

		if ($categticketid) {
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_ticket as ctk ON ctk.fk_ticket = tk.rowid';
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as c ON c.rowid = ctk.fk_categorie';
			$this->where .= ' AND c.rowid = '.((int) $categticketid);
		}
        if (dol_strlen($moreWhere) > 0) {
            $this->where .= $moreWhere;
        }
	}

	/**
	 * 	Return ticket number by month for a year
	 *
	 *	@param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *	@return	array				Array of values
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(tk.datec,'%m') as dc, COUNT(*) as nb";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE tk.datec BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dc";
		$sql .= $this->db->order('dc', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);

		return $res;
	}

	/**
	 *	Return nb, total and average
	 *
	 *	@return	array	Array of values
	 */
	public function getAllByYear()
	{
		$sql = "SELECT date_format(tk.datec,'%Y') as year, COUNT(*) as nb";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}

    /**
     * Return nb ticket by GP/UT and Ticket tags
     *
     * @param  int		$date_start		Timestamp date start
     * @param  int		$date_end		Timestamp date end
     *
     * @return array                    Array of values
     * @throws Exception
     */
    public function getNbTicketByDigiriskElementAndTicketTags($date_start = 0, $date_end = 0) {
        global $conf, $langs;

        $digiriskelement  = new DigiriskElement($this->db);
        $categorie        = new Categorie($this->db);
        $accident         = new Accident($this->db);
        $accidentWorkStop = new AccidentWorkStop($this->db);
        $ticket           = new Ticket($this->db);

        $digiriskelement_flatlist = $digiriskelement->fetchDigiriskElementFlat(0);
        if (is_array($digiriskelement_flatlist) && !empty($digiriskelement_flatlist)) {
            foreach ($digiriskelement_flatlist as $digiriskelementobject) {
                $digiriskelementlist[$digiriskelementobject['object']->id] = $digiriskelementobject['object'];
            }
        }

        $digiriskelementlist = dol_sort_array($digiriskelementlist, 'ranks');
        $mainCategoryObject = $categorie->rechercher($conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY, '', 'ticket', true);
        $allCategories = $mainCategoryObject[0]->get_filles();

        $arrayReturn = [];
        $ticketCategoriesCounter = [];

        //Creating columns labels
        if (is_array($allCategories) && !empty($allCategories)) {
            // Main categories
            foreach($allCategories as $category) {
                $labelsArray['labels'][$category->id] = $category->label;

                $categorie->fetch($category->id);
                $alltickets[$category->id] = getObjectsInCategDigirisk($categorie, 'ticket', 0, 0, 0, '', 'ASC', $filter);

                if (is_array($alltickets[$category->id]) && !empty($alltickets[$category->id])) {
                    $ticketCategoriesCounter[$langs->trans('Register') . ' ' . $category->label] = count($alltickets[$category->id]);
                }


                $mainCategoriesIds[$category->id] = $category->id;
                $childrenCategories = $category->get_filles();


                // Children categories
                if (is_array($childrenCategories) && !empty($childrenCategories)) {
                    foreach($childrenCategories as $childCategory) {
                        $labelsArray['labels'][$childCategory->id] = $childCategory->label;

                        $categorie->fetch($childCategory->id);
                        $alltickets[$childCategory->id] = getObjectsInCategDigirisk($categorie, 'ticket', 0, 0, 0, '', 'ASC', $filter);

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

        if (is_array($digiriskelementlist) && !empty($digiriskelementlist)) {
            foreach($digiriskelementlist as $digiriskelement) {
                $arrayKey = $digiriskelement->ref . ' - ' . $digiriskelement->label;
                if (is_array($labelsArray['labels']) && !empty($labelsArray['labels'])) {
                    foreach($labelsArray['labels'] as $categoryId => $categoryLabel) {
                        $arrayReturn[$arrayKey][$categoryId] = 0;

                        if (is_int($categoryId)) {
                            if (is_array($alltickets[$categoryId]) && !empty($alltickets[$categoryId])) {
                                foreach($alltickets[$categoryId] as $ticketsWithThisCategory) {
                                    if ($ticketsWithThisCategory->array_options['options_digiriskdolibarr_ticket_service'] == $digiriskelement->id) {
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
                                    if ($ticket->array_options['options_digiriskdolibarr_ticket_service'] == $digiriskelement->id) {
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
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        $getTicketsByMonth = $this->getTicketsByMonth();

        $category = new Categorie($this->db);
        $category->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY'));
        $mainCategories = $category->get_filles();

        $getTicketsByMainTagAndByDigiriskElement    = $this->getTicketsByMainTagAndByDigiriskElement($mainCategories);
        $getTicketsByMainSubTagAndByDigiriskElement = $this->getTicketsByMainSubTagAndByDigiriskElement($mainCategories);

        $getTicketsByYear = $this->getTicketsByYear();

        $array['graphs'] = [$getTicketsByMonth, $getTicketsByMainTagAndByDigiriskElement, $getTicketsByMainSubTagAndByDigiriskElement];
        $array['lists']  = [$getTicketsByYear];

        return $array;
    }

    /**
     * Get tickets by month
     *
     * @return array $array Graph datas (label/color/type/title/data etc..)
     */
    public function getTicketsByMonth(): array
    {
        global $conf, $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NumberOfTicketsByMonth');
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $date_start = dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
        $date_end   = dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));
        $startyear  = strftime("%Y", !empty($date_start) ? $date_start : dol_now());
        $endyear    = strftime("%Y", !empty($date_end) ? $date_end : dol_now() + 1);
        $legend     = [];

        for ($i = $startyear; $i <= $endyear; $i++) {
            $legend[] = $i;
        }

        $array['dataset'] = 3;

        foreach ($legend as $key => $legendPart) {
            $array['labels'][] = ['label' => $legendPart];
        }

        $tickets = $this->getNbByMonthWithPrevYear($endyear, $startyear, 0, 0, $conf->global->SOCIETE_FISCAL_MONTH_START);
        if (is_array($tickets) && !empty($tickets)) {
            foreach ($tickets as $ticket) {
                $value[] = $ticket;
            }
        }

        $array['data'] = $value;

        return $array;
    }

    /**
     * Get tickets by main tag and digirisk element
     *
     * @param  array|int $mainCategories Int <0 if KO, array of main categories if OK
     * @return array     $array          Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getTicketsByMainTagAndByDigiriskElement($mainCategories): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NumberOfTicketsByMainTagAndByDigiriskElement');
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $digiriskElement = new DigiriskElement($this->db);

        if (is_array($mainCategories) && !empty($mainCategories)) {
            foreach ($mainCategories as $mainCategory) {
                $array['labels'][$mainCategory->id] = [
                    'label' => $mainCategory->label,
                    'color' => '#' . $mainCategory->color
                ];
                $moreParams['filter'] = ' AND t.element_type = "groupment"';
                $digiriskElements     = $digiriskElement->getActiveDigiriskElements(0, $moreParams);
                if (is_array($digiriskElements) && !empty($digiriskElements)) {
                    foreach ($digiriskElements as $digiriskElement) {
                        $tickets                                = saturne_fetch_all_object_type('Ticket', '', '', 0, 0, ['customsql' => 'cp.fk_categorie = ' . $mainCategory->id  . ' AND eft.digiriskdolibarr_ticket_service = ' . $digiriskElement->id], 'AND', true, true, true);
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
    public function getTicketsByMainSubTagAndByDigiriskElement($mainCategories): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('NumberOfTicketsByMainSubTagAndByDigiriskElement');
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $category        = new Categorie($this->db);
        $digiriskElement = new DigiriskElement($this->db);

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
                        $moreParams['filter'] = ' AND t.element_type = "groupment"';
                        $digiriskElements     = $digiriskElement->getActiveDigiriskElements(0, $moreParams);
                        if (is_array($digiriskElements) && !empty($digiriskElements)) {
                            foreach ($digiriskElements as $digiriskElement) {
                                $tickets                                = saturne_fetch_all_object_type('Ticket', '', '', 0, 0, ['customsql' => 'cp.fk_categorie = ' . $mainSubCategory->id  . ' AND eft.digiriskdolibarr_ticket_service = ' . $digiriskElement->id], 'AND', true, true, true);
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
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['type']   = 'list';
        $array['labels'] = ['Year', 'Ticket', 'Percentage'];

        $arrayTicketByYear = [];
        $tickets           = $this->getAllByYear();
        if (is_array($tickets) && !empty($tickets)) {
            foreach ($tickets as $key => $ticket) {
                $arrayTicketByYear[$key]['Ref']['value']        = $ticket['year'];
                $arrayTicketByYear[$key]['Ticket']['value']     = $ticket['nb'];
                $arrayTicketByYear[$key]['Percentage']['value'] = ($ticket['avg'] ?: 0) . ' %';
            }
        }

        $array['data'] = $arrayTicketByYear;

        return $array;
    }
}
