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
 * \file        class/risk.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for Risk (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

/**
 * Class for Risk
 */
class Risk extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'risk';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_risk';

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
	 * @var string String with name of icon for risk. Must be the part after the 'object_' into object_risk.png
	 */
	public $picto = 'risk@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'fk_element' => array('type'=>'integer', 'label'=>'ParentElement', 'enabled'=>'1', 'position'=>9, 'notnull'=>1, 'visible'=>1,),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'ref_ext' => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>0,),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>0,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>70, 'notnull'=>-1, 'visible'=>0,),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>0,),
		'category' => array('type'=>'varchar(255)', 'label'=>'RiskCategory', 'enabled'=>'1', 'position'=>21, 'notnull'=>0, 'visible'=>1,),
		'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>23, 'notnull'=>0, 'visible'=>-1,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>110, 'notnull'=>1, 'visible'=>0, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>120, 'notnull'=>-1, 'visible'=>0,),
		'fk_projet' => array('type'=>'integer:Project:projet/class/project.class.php', 'label'=>'Projet', 'enabled'=>'1', 'position'=>140, 'notnull'=>1, 'visible'=>0,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $category;
	public $description;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_element;
	public $fk_projet;

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
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
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
	 * @param int    $parent_id   Id parent object
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchFromParent($parent_id)
	{
		$filter = array('customsql' => 'fk_element=' . $this->db->escape($parent_id));
		return $this->fetchAll('', '', 0, 0, $filter, 'AND');
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int        $parent_id   Id parent object
	 * @param boolean    $get_children_data  Get children risks data
	 * @param boolean    $get_parents_data   Get parents risks data
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchRisksOrderedByCotation($parent_id, $get_children_data = false, $get_parents_data = false)
	{
		$object  = new DigiriskElement($this->db);
		$objects = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0' ));
		$risk    = new Risk($this->db);
		$result  = $risk->fetchFromParent($parent_id);

		// RISQUES de l'élément appelant.
		if ($result > 0 && !empty ($result)) {
			foreach ( $result as $risk ) {
				$evaluation = new RiskAssessment($this->db);
				$lastEvaluation = $evaluation->fetchFromParent($risk->id,1);
				if ( $lastEvaluation > 0  && !empty($lastEvaluation) ) {
					$lastEvaluation = array_shift($lastEvaluation);
					$risk->lastEvaluation = $lastEvaluation->cotation;
				}

				$risks[$risk->id] = $risk;
			}
		}

		if ( $get_children_data ) {
			$elements = recurse_tree($parent_id,0,$objects);
			if ( $elements > 0  && !empty($elements) ) {
				// Super fonction itérations flat.
				$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($elements));
				foreach($it as $key => $v) {
					$element[$key][$v] = $v;
				}

				if (is_array($element)) {
					$children_id = array_shift ($element);
				}

				// RISQUES des enfants du parent.
				if (!empty ($children_id)) {
					foreach ($children_id as $element) {

						$risk = new Risk($this->db);

						$result = $risk->fetchFromParent($element);
						if (!empty ($result)) {
							foreach ($result as $risk) {
								$evaluation = new RiskAssessment($this->db);
								$lastEvaluation = $evaluation->fetchFromParent($risk->id,1);
								if ( $lastEvaluation > 0  && !empty($lastEvaluation) ) {
									$lastEvaluation = array_shift($lastEvaluation);
									$risk->lastEvaluation = $lastEvaluation->cotation;
								}

								$risks[$risk->id] = $risk;
							}
						}
					}
				}
			}
		}

		if ( $get_parents_data ) {
			$parent_element_id = $objects[$parent_id]->id;
			while ($parent_element_id > 0) {
				$result  = $risk->fetchFromParent($parent_element_id);
				if ($result > 0 && !empty ($result)) {
					foreach ( $result as $risk ) {
						$evaluation = new RiskAssessment($this->db);
						$lastEvaluation = $evaluation->fetchFromParent($risk->id,1);
						if ( $lastEvaluation > 0  && !empty($lastEvaluation) ) {
							$lastEvaluation = array_shift($lastEvaluation);
							$risk->lastEvaluation = $lastEvaluation->cotation;
						}

						$risks[$risk->id] = $risk;
					}
				}
				$parent_element_id = $objects[$parent_element_id]->fk_parent;
			}
		}

		if ( !empty($risks) ) {
			usort($risks,function($first,$second){
				return $first->lastEvaluation < $second->lastEvaluation;
			});
			return $risks;
		} else {
			return -1;
		}
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

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
	 * Get risk categories json in /digiriskdolibarr/js/json/
	 *
	 * @return	array $risk_categories
	 */
	public function get_danger_categories()
	{
		$json_categories = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/dangerCategories.json');
		$risk_categories = json_decode($json_categories, true);

		return $risk_categories;
	}

	/**
	 * Get danger category picto path
	 *
	 * @return	string $category['thumbnail_name']     path to danger category picto, -1 if don't exist
	 */
	public function get_danger_category($object)
	{
		$risk_categories = $this->get_danger_categories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['thumbnail_name'];
			}
		}

		return -1;
	}

	/**
	 * Get danger category picto name
	 *
	 * @return	string $category['name']     name to danger category picto, -1 if don't exist
	 */
	public function get_danger_category_name($object)
	{
		$risk_categories = $this->get_danger_categories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['name'];
			}
		}

		return -1;
	}

	/**
	 * Get danger category picto name
	 *
	 * @return	string $category['name']     name to danger category picto, -1 if don't exist
	 */
	public function get_danger_category_position_by_name($name)
	{
		$risk_categories = $this->get_danger_categories();
		foreach ($risk_categories as $category) {
			if ($category['name'] == $name) {
				return $category['position'];
			}
		}

		return -1;
	}

	/**
	 * Get fire permit risk categories json in /digiriskdolibarr/js/json/
	 *
	 * @return	array $risk_categories
	 */
	public function get_fire_permit_danger_categories()
	{
		$json_categories = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/firePermitDangerCategories.json');
		$risk_categories = json_decode($json_categories, true);

		return $risk_categories;
	}

	/**
	 * Get fire permit danger category picto path
	 *
	 * @return	string $category['thumbnail_name']     path to fire permit danger category picto, -1 if don't exist
	 */
	public function get_fire_permit_danger_category($object)
	{
		$risk_categories = $this->get_fire_permit_danger_categories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['thumbnail_name'];
			}
		}

		return -1;
	}

	/**
	 * Get fire permit danger category picto name
	 *
	 * @return	string $category['name']     name to fire permit danger category picto, -1 if don't exist
	 */
	public function get_fire_permit_danger_category_name($object)
	{
		$risk_categories = $this->get_fire_permit_danger_categories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['name'];
			}
		}

		return -1;
	}

	/**
	 * Get children tasks
	 *
	 * @return	array	$records or -1 if error
	 */
	public function get_related_tasks($risk)
	{
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX.'projet_task_extrafields' . ' WHERE fk_risk =' . $risk->id;

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$record = new Task($this->db);
				$record->fetch($obj->fk_object);
				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}
}
