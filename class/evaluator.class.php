<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    class/evaluator.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a CRUD class file for Evaluator (Create/Read/Update/Delete).
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

require_once __DIR__ . '/dashboarddigiriskstats.class.php';

/**
 * Class for Evaluator.
 */
class Evaluator extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'evaluator';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiriskdolibarr_evaluator';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
     */
    public int $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public int $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for evaluator. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'evaluator@digiriskdolibarr' if picto is file 'img/object_evaluator.png'.
     */
    public string $picto = 'fontawesome_fa-user-check_fas_#d35968';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'           => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'             => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'         => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'          => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'   => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'             => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'import_key'      => array('type' => 'integer', 'label' => 'ImportId', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => 0,),
		'status'          => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 0, 'index' => 1,),
		'duration'        => array('type' => 'smallint', 'label' => 'EvaluationDuration', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1, 'index' => 1,),
		'assignment_date' => array('type' => 'datetime', 'label' => 'AssignmentDate', 'enabled' => '1', 'position' => 90, 'notnull' => 1, 'visible' => 1,),
		'job'             => array('type' => 'varchar(80)', 'label' => 'PostOrFunction', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 1,),
		'fk_user_creat'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 110, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',),
		'fk_user_modif'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 120, 'notnull' => -1, 'visible' => 0,),
		'fk_user'         => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAssigned', 'enabled' => '1', 'position' => 130, 'notnull' => 1, 'visible' => 1, 'default' => 0,),
		'fk_parent'       => array('type' => 'integer:DigiriskElement:digiriskdolibarr/class/digiriskelement.class.php', 'label' => 'ParentElement', 'enabled' => '1', 'position' => 5, 'notnull' => 1, 'visible' => 1, 'default' => 0,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $duration;
	public $assignment_date;
	public $post;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_user;
	public $fk_parent;

    /**
     * Constructor.
     *
     * @param DoliDb $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

	/**
	 * Load object in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchFromParent($parent_id)
	{
		$filter = array('customsql' => 'fk_parent=' . $this->db->escape($parent_id));
		return $this->fetchAll('', '', 0, 0, $filter, 'AND');
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit limit
	 * @param int $offset Offset
	 * @param array $filter Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param string $filtermode Filter mode (AND or OR)
	 * @param  string  $groupby add GROUP BY key word
	 * @return array|int                 int <0 if KO, array of pages if OK
	 * @throws Exception
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $groupby = '')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql                                                                              = 'SELECT ';
		$sql                                                                             .= $this->getFieldList();
		$sql                                                                             .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
		else $sql                                                                        .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($groupby)) {
			$sql .= ' GROUP BY ' . $groupby;
		}

		if ( ! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if ( ! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i   = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load dashboard info evaluator.
	 * - get number employees involved
	 *
	 * @return array
	 * @throws Exception
	 */
	public function load_dashboard()
	{
		global $conf, $langs;

		$arrayNbEmployeesInvolved = $this->getNbEmployeesInvolved();
		$arrayNbEmployees         = $this->getNbEmployees();

		$array['widgets'] = array(
			DashboardDigiriskStats::DASHBOARD_EVALUATOR => array(
				'label'      => array($langs->transnoentities("NbEmployeesInvolved"), $langs->transnoentities("NbEmployees")),
				'content'    => array($arrayNbEmployeesInvolved['nbemployeesinvolved'], $arrayNbEmployees['nbemployees']),
				'tooltip'    => array($langs->transnoentities("NbEmployeesInvolvedTooltip"), (($conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES) ? $langs->transnoentities("NbEmployeesConfTooltip") : $langs->transnoentities("NbEmployeesTooltip"))),
				'picto'      => 'fas fa-user-check',
				'widgetName' => $langs->transnoentities('Evaluator')
			)
		);

		return $array;
	}

	/**
	 * Get number employees involved.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbEmployeesInvolved() {
		// Number employees involved
		$allevaluators = $this->fetchAll('','', 0, 0, array(), 'AND', 'fk_user');
		if (is_array($allevaluators) && !empty($allevaluators)) {
			$array['nbemployeesinvolved'] = count($allevaluators);
		} else {
			$array['nbemployeesinvolved'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get number employees.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbEmployees() {
		global $conf;

		// Number employees
		if ($conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES) {
			$array['nbemployees'] = $conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES;
		} else {
			$user = new User($this->db);
			$allusers = $user->get_full_tree(0, 'u.employee = 1');
			if (!empty($allusers) && is_array($allusers)) {
				$array['nbemployees'] = count($allusers);
			} else {
				$array['nbemployees'] = 'N/A';
			}
		}
		return $array;
	}
}
