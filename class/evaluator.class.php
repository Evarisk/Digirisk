<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file        class/evaluator.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for Evaluator (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

require_once __DIR__ . '/dashboarddigiriskstats.class.php';

/**
 * Class for Evaluator
 */
class Evaluator extends CommonObject
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = array();

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'evaluator';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_evaluator';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for evaluator. Must be the part after the 'object_' into object_evaluator.png
	 */
	public $picto = 'evaluator@digiriskdolibarr';

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
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']        = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$this->element = $this->element . '@digiriskdolibarr';
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
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
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql    = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql   .= ' fk_user_creat, fk_user_modif';
		$sql   .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql   .= ' WHERE t.rowid = ' . $id;
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj      = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
//				if ($obj->fk_user_author) {
//					$cuser = new User($this->db);
//					$cuser->fetch($obj->fk_user_author);
//					$this->user_creation = $cuser;
//				}
//
//				if ($obj->fk_user_valid) {
//					$vuser = new User($this->db);
//					$vuser->fetch($obj->fk_user_valid);
//					$this->user_validation = $vuser;
//				}
//
//				if ($obj->fk_user_cloture) {
//					$cluser = new User($this->db);
//					$cluser->fetch($obj->fk_user_cloture);
//					$this->user_cloture = $cluser;
//				}

				$this->date_creation     = $this->db->jdate($obj->date_creation);
//				$this->date_modification = $this->db->jdate($obj->datem);
//				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
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
				'tooltip' => array($langs->transnoentities("NbEmployeesInvolvedTooltip"), (($conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES > 0) ? $langs->transnoentities("NbEmployeesConfTooltip") : $langs->transnoentities("NbEmployeesTooltip"))),
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
		if ($conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES > 0) {
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
