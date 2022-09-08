<?php
/* Copyright (C) 2022 EOXIA <dev@eoxia.com>
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
 *       \file       class/ticketdigiriskstats.class.php
 *       \ingroup    digiriskdolibarr
 *       \brief      Fichier de la classe de gestion des stats des tickets
 */

include_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/digiriskstats.php';
require_once __DIR__ . '/digiriskelement.class.php';

/**
 *	Class to manage stats for tickets
 */
class TicketDigiriskStats extends DigiriskStats
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
	 * 	@param	int			$digiriskelementid    ID digiriskelement for filter
	 * 	@param	int			$categticketid        ID category of ticket for filter
	 */
	public function __construct($db, $socid = 0, $userid = 0, $userassignid = 0, $digiriskelementid = 0, $categticketid = 0)
	{
		$this->db = $db;
		$this->socid = ($socid > 0 ? $socid : 0);
		$this->userid = $userid;
		$this->join = '';

		$object = new Ticket($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element." as tk";
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

		if ($digiriskelementid) {
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ticket_extrafields as tkextra ON tk.rowid = tkextra.fk_object';
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'digiriskdolibarr_digiriskelement as e ON tkextra.digiriskdolibarr_ticket_service = e.rowid';
			$this->where .= ' AND e.rowid = '.((int) $digiriskelementid);
		}

		if ($categticketid) {
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_ticket as ctk ON ctk.fk_ticket = tk.rowid';
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as c ON c.rowid = ctk.fk_categorie';
			$this->where .= ' AND c.rowid = '.((int) $categticketid);
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
	 * 	Return ticket number per year
	 *
	 *	@return		array	Array with number by year
	 */
	public function getNbByYear()
	{
		$sql = "SELECT date_format(tk.datec,'%Y') as dc, COUNT(*), SUM(c.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY dc";
		$sql .= $this->db->order('dc', 'DESC');

		return $this->_getNbByYear($sql);
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
	 * @return array     Array of values
	 * @throws Exception
	 */
	public function getNbTicketByDigiriskElementAndTicketTags() {
		$digiriskelement = new DigiriskElement($this->db);
		$categorie       = new Categorie($this->db);

		$digiriskelement_flatlist = $digiriskelement->fetchDigiriskElementFlat(0);
		if (is_array($digiriskelement_flatlist) && !empty($digiriskelement_flatlist)) {
			foreach ($digiriskelement_flatlist as $digiriskelementobject) {
				$digiriskelementlist[$digiriskelementobject['object']->id] = $digiriskelementobject['object'];
			}
		}

		$digiriskelementlist = dol_sort_array($digiriskelementlist, 'ranks');

		$allCategories = $categorie->get_all_categories('ticket');
		if (is_array($allCategories) && !empty($allCategories)) {
			foreach ($allCategories as $category) {
				$arrayCats[$category->label] = array(
					'id' => $category->id,
					'name' => $category->label
				);
			}
		}

		if (is_array($digiriskelementlist) && !empty($digiriskelementlist)) {
			if (is_array($arrayCats) && !empty($arrayCats)) {
				foreach ($digiriskelementlist as $digiriskelement) {
					foreach ($arrayCats as $key => $cat) {
						$nbticket = 0;
						$categorie->fetch($cat['id']);
						$alltickets = $categorie->getObjectsInCateg(Categorie::TYPE_TICKET);
						if (is_array($alltickets) && !empty($alltickets)) {
							foreach ($alltickets as $ticket) {
								if (!empty($ticket->array_options['options_digiriskdolibarr_ticket_service']) && $ticket->array_options['options_digiriskdolibarr_ticket_service'] == $digiriskelement->id && $ticket->fk_statut != 9) {
									$nbticket++;
								}
							}
						}
						$array[$digiriskelement->ref . ' - ' . $digiriskelement->label][html_entity_decode($key, ENT_QUOTES | ENT_HTML5)] = $nbticket;
					}
				}
			}
		}

		if (!empty($array)) {
			return $array;
		} else {
			return -1;
		}
	}
}

