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
 * \file        class/digiriskresources.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for DigiriskResources (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for DigiriskResources
 */
class DigiriskResources extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digiriskresources';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_digiriskresources';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for digiriskresources. Must be the part after the 'object_' into object_digiriskresources.png
	 */
	public $picto = 'digiriskresources@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'           => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'comment' => "ref for the object"),
		'ref_ext'       => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 4, 'index' => 1,),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => -1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => -2,),
		'tms'           => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => -2,),
		'status'        => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => -1,),
		'element_type'  => array('type' => 'varchar(50)', 'label' => 'ElementType', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => -1,),
		'element_id'    => array('type' => 'integer', 'label' => 'ElementID', 'enabled' => '1', 'position' => 80, 'notnull' => 1, 'visible' => -1,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 90, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'object_type'   => array('type' => 'varchar(50)', 'label' => 'ObjectType', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => -1,),
		'object_id'     => array('type' => 'integer', 'label' => 'ObjectID', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => -1,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $status;
	public $element_type;
	public $element_id;
	public $fk_user_creat;

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
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

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
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Set resources in database
	 *
	 * @param $db
	 * @param $user_creat
	 * @param string $ref name of resource
	 * @param string $element_type type of resource
	 * @param array $element_id Id of resource
	 * @param int $entity
	 * @param string $object_type
	 * @param int $object_id
	 * @param int $noupdate
	 * @return int
	 * @throws Exception
	 */
	public function digirisk_dolibarr_set_resources($db, $user_creat, $ref, $element_type, $element_id, $entity = 1, $object_type = '', $object_id = 0, $noupdate = 0)
	{
		global $conf;
		$now = dol_now();
		// Clean parameters
		$ref = trim($ref);

		// Check parameters
		if (empty($ref)) {
			dol_print_error($db, "Error: Call to function dolibarr_set_const with wrong parameters", LOG_ERR);
			exit;
		}
		//dol_syslog("dolibarr_set_const name=$name, value=$value type=$type, visible=$visible, note=$note entity=$entity");

		$db->begin();

		if (!$noupdate) {
			// Change le statut des ressources précédentes à 0
			$sql = "UPDATE " . MAIN_DB_PREFIX . "digiriskdolibarr_digiriskresources";
			$sql .= " SET status = 0";
			$sql .= " WHERE ref = " . $db->encrypt($ref, 1);
			if ($entity >= 0) $sql .= " AND entity = " . $entity;
		}

		dol_syslog("admin.lib::digirisk_dolibarr_set_resources", LOG_DEBUG);
		$resql = $db->query($sql);

		if (strcmp($element_type, '') && !empty($element_id))    // true if different. Must work for $value='0' or $value=0
		{
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "digiriskdolibarr_digiriskresources(ref,fk_user_creat,date_creation, element_type,element_id,status,entity, object_type, object_id)";
			$sql .= " VALUES ";
			foreach ($element_id as $id) {
				$sql .= "(" . $db->encrypt($ref, 1);
				$sql .= ", " . $db->encrypt($user_creat, 1);
				$sql .= ", '" . date("Y-m-d", $now);
				$sql .= "', " . $db->encrypt($element_type, 1);
				$sql .= ", " . $id;
				$sql .= ", 1";
				$sql .= ", " . $entity ;
				$sql .=  ", '" . $object_type . "'";
				$sql .=  ", " . $object_id. "),";
			}
			$sql = substr($sql, 0, -1);
			//print "sql".$value."-".pg_escape_string($value)."-".$sql;exit;
			//print "xx".$db->escape($value);
			dol_syslog("admin.lib::dolibarr_set_resources", LOG_DEBUG);
			$resql = $db->query($sql);
		}

		if ($resql) {
			$db->commit();
			$conf->global->ref = $ref;
			return 1;
		} else {
			$error = $db->lasterror();
			$db->rollback();
			return -1;
		}

	}

	/**
	 * Fetch resource in database
	 *
	 * @param string $ref name of resource
	 * @return int
	 */
	public function digirisk_dolibarr_fetch_resource($ref)
	{
		global $langs;
		$allLinks = $this->digirisk_dolibarr_fetch_resources();

		if(!empty($allLinks[$ref])) {
			$id = array_shift($allLinks[$ref]->id);
			return $id;
		}
		return $langs->trans('NoLabourInspectorAssigned');
	}

	/**
	 * Fetch resources in database with parent object
	 *
	 * @param string $ref name of resource
	 * @param $object
	 * @return int
	 */
	public function fetchResourcesFromObject($ref, $object )
	{

		$sql = 'SELECT '.$this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		if (dol_strlen($ref)) {
			$sql .= ' WHERE ref = '. "'" . $ref . "'";
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		$sql .= ' AND object_type = '. "'" . $object->element . "'";
		$sql .= ' AND object_id = '.$object->id;
		$sql .= ' AND status = 1';
		if (empty($id) && isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' AND entity IN ('.getEntity($this->table_element).')';

		$res = $this->db->query($sql);



		if ($res)
		{

			if ($res->num_rows > 1) {
				$num = $this->db->num_rows($res);
				$i = 0;
				while ($i < ($limit ? min($limit, $num) : $num)) {
					$obj = $this->db->fetch_object($res);

					$record = new self($this->db);
					$record->setVarsFromFetchObj($obj);

					if ($record->element_type == 'user') {
						$resourcetmp = new User($this->db);
					} elseif ($record->element_type == 'socpeople') {
						$resourcetmp = new Contact($this->db);
					} elseif ($record->element_type == 'societe') {
						$resourcetmp = new Societe($this->db);
					}

					$resourcetmp->fetch($record->element_id);

					$records[$record->ref][$record->id] = $resourcetmp;

					$i++;
				}
				$this->db->free($res);

				return $records;
			} else {
				$obj = $this->db->fetch_object($res);
				if ($obj)
				{
					$this->setVarsFromFetchObj($obj);

					// Retreive all extrafield
					// fetch optionals attributes and labels
					$this->fetch_optionals();

					if ($this->element_type == 'user') {

						$resourcetmp = new User($this->db);
					} elseif ($this->element_type == 'socpeople') {
						$resourcetmp = new Contact($this->db);
					} elseif ($this->element_type == 'societe') {
						$resourcetmp = new Societe($this->db);
					}
					$resourcetmp->fetch($this->element_id);

					return $resourcetmp;
				}
				else
				{
					return 0;
				}
			}

		}
		else
		{
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
	}

	/**
	 * Fetch all resources in database
	 *
	 * @return array
	 */
	public function digirisk_dolibarr_fetch_resources()
	{
		$resources = new DigiriskResources($this->db);

		$links = $resources->fetchAll();

		$allLinks = array();
		if (!empty ($links) && $links > 0) {
			foreach ($links as $link) {
				if ($allLinks[$link->ref]->ref == $link->ref) {
					array_push($allLinks[$link->ref]->id, $link->element_id);
				} else {
					$allLinks[$link->ref] = new stdClass;
					$allLinks[$link->ref]->id[] = $link->element_id;
					$allLinks[$link->ref]->type = $link->element_type;
					$allLinks[$link->ref]->ref = $link->ref;
				}
			}
		}

		return $allLinks;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id Id object
	 * @param string $ref Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
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
	 * @return array|int                 int <0 if KO, array of pages if OK
	 * @throws Exception
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql .= ' WHERE status = 1';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' AND t.entity IN (' . getEntity($this->table_element) . ')';
		else $sql .= ' AND 1 = 1';
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

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
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
	 * @param User $user User that modifies
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}
}
