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
 * \file        class/riskanalysis/risk.class.php
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
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = array();

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
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'fk_element' => array('type' => 'integer', 'label' => 'ParentElement', 'enabled' => '1', 'position' => 9, 'notnull' => 1, 'visible' => 1,),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext' => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 30, 'notnull' => 0, 'visible' => 0,),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 8, 'notnull' => 1, 'visible' => 0,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 50, 'notnull' => 1, 'visible' => 0,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => 0,),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => 0,),
		'status' => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 0,),
		'category' => array('type' => 'varchar(255)', 'label' => 'RiskCategory', 'enabled' => '1', 'position' => 21, 'notnull' => 0, 'visible' => 1,),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 23, 'notnull' => 0, 'visible' => -1,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 110, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 120, 'notnull' => -1, 'visible' => 0,),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php', 'label' => 'Projet', 'enabled' => '1', 'position' => 140, 'notnull' => 1, 'visible' => 0,),
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
		$filter = array('customsql' => 'fk_element=' . $this->db->escape($parent_id));
		return $this->fetchAll('', '', 0, 0, $filter, 'AND');
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @param bool $get_children_data Get children risks data
	 * @param bool $get_parents_data Get parents risks data
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchRisksOrderedByCotation($parent_id, $get_children_data = false, $get_parents_data = false, $get_shared_data = false)
	{
		global $conf;
		$object  = new DigiriskElement($this->db);
		$objects = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0' ));
		$risk    = new Risk($this->db);
		if ($parent_id > 0) {
			$result  = $risk->fetchFromParent($parent_id);

			$filter = '';

			// RISKS de l'élément parent.
			if ($result > 0 && ! empty($result)) {
				foreach ($result as $risk) {
					$evaluation     = new RiskAssessment($this->db);
					$lastEvaluation = $evaluation->fetchFromParent($risk->id, 1);
					if ( $lastEvaluation > 0 && ! empty($lastEvaluation) && is_array($lastEvaluation)) {
						$lastEvaluation       = array_shift($lastEvaluation);
						$risk->lastEvaluation = $lastEvaluation->cotation;
					}

					$risks[$risk->id] = $risk;
				}
			}

		}

		if ( $get_children_data ) {
			if (is_array($objects)) {
				$elements = recurse_tree($parent_id, 0, $objects);
			} else {
				return -1;
			}

			if ( $elements > 0 && ! empty($elements) ) {
				// Super function iterations flat.
				$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($elements));
				$element = array();
				foreach ($it as $key => $v) {
					$element[$key][$v] = $v;
				}

				$children_id = array_shift($element);

				// RISKS parent children.
				if ( ! empty($children_id)) {
					foreach ($children_id as $element) {
						$risk = new Risk($this->db);

						$result = $risk->fetchFromParent($element);
						if ( ! empty($result)) {
							foreach ($result as $risk) {
								$evaluation     = new RiskAssessment($this->db);
								$lastEvaluation = $evaluation->fetchFromParent($risk->id, 1);
								if ( $lastEvaluation > 0 && ! empty($lastEvaluation)  && is_array($lastEvaluation)) {
									$lastEvaluation       = array_shift($lastEvaluation);
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
				$result = $risk->fetchFromParent($parent_element_id);
				if ($result > 0 && ! empty($result)) {
					foreach ($result as $risk) {
						$evaluation     = new RiskAssessment($this->db);
						$lastEvaluation = $evaluation->fetchFromParent($risk->id, 1);
						if ( $lastEvaluation > 0 && ! empty($lastEvaluation)  && is_array($lastEvaluation)) {
							$lastEvaluation       = array_shift($lastEvaluation);
							$risk->lastEvaluation = $lastEvaluation->cotation;
						}

						$risks[$risk->id] = $risk;
					}
				}
				$parent_element_id = $objects[$parent_element_id]->fk_parent;
			}
		}

		if ( $get_shared_data ) {
			$digiriskelementtmp = new DigiriskElement($this->db);

//			$AllSharingsRisks = $conf->mc->sharings['risk'];
//
//			foreach ($AllSharingsRisks as $Allsharingsrisk) {
//				$filter .= $Allsharingsrisk . ',';
//			}
//
//			$filter = rtrim($filter, ',');

			$allrisks = $risk->fetchAll('', '', 0, 0, array('customsql' => 'status > 0 AND entity NOT IN (' . $conf->entity . ')'), 'AND', 0);

			foreach ($allrisks as $key => $allrisk) {
				$digiriskelementtmp->fetch($allrisk->fk_element);
				$digiriskelementtmp->element = 'digiriskdolibarr';
				$digiriskelementtmp->fetchObjectLinked($allrisk->id, 'digiriskdolibarr_risk', $object->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
				$alreadyImported = !empty($digiriskelementtmp->linkedObjectsIds) ? 1 : 0;
				if ($alreadyImported > 0) {
					$evaluation     = new RiskAssessment($this->db);
					$lastEvaluation = $evaluation->fetchFromParent($allrisk->id, 1);
					if ( $lastEvaluation > 0 && ! empty($lastEvaluation)  && is_array($lastEvaluation)) {
						$lastEvaluation       = array_shift($lastEvaluation);
						$allrisk->lastEvaluation = $lastEvaluation->cotation;
					}

					$risks[$allrisk->id] = $allrisk;
				}
			}
		}

		if ( ! empty($risks) ) {
			usort($risks, function ($first, $second) {
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

		$sql                                                                              = 'SELECT ';
		$sql                                                                             .= $this->getFieldList();
		$sql                                                                             .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntity($this->element) . ')';
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
	 * Get risk categories json in /digiriskdolibarr/js/json/
	 *
	 * @return	array $risk_categories
	 */
	public function get_danger_categories()
	{
		$json_categories = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/dangerCategories.json');
		return json_decode($json_categories, true);
	}

	/**
	 * Get danger category picto path
	 *
	 * @param $object
	 * @return    string $category['thumbnail_name']     path to danger category picto, -1 if don't exist
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
	 * @param $object
	 * @return    string $category['name']     name to danger category picto, -1 if don't exist
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
	 * @param $name
	 * @return    string $category['name']     name to danger category picto, -1 if don't exist
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
	 * Get danger category picto path
	 *
	 * @param int $position
	 * @return    string $category['thumbnail_name']     path to danger category picto, -1 if don't exist
	 */
	public function get_danger_category_by_position($position)
	{
		$risk_categories = $this->get_danger_categories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $position) {
				return $category['thumbnail_name'];
			}
		}

		return -1;
	}

	/**
	 * Get danger category picto path
	 *
	 * @param int $position
	 * @return    string $category['thumbnail_name']     path to danger category picto, -1 if don't exist
	 */
	public function get_danger_category_name_by_position($position)
	{
		$risk_categories = $this->get_danger_categories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $position) {
				return $category['name'];
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
		return json_decode($json_categories, true);
	}

	/**
	 * Get fire permit danger category picto path
	 *
	 * @param $object
	 * @return    string $category['thumbnail_name']     path to fire permit danger category picto, -1 if don't exist
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
	 * @param $object
	 * @return    string $category['name']     name to fire permit danger category picto, -1 if don't exist
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
	 * @param $risk
	 * @return array|int $records or -1 if error
	 * @throws Exception
	 */
	public function get_related_tasks($risk)
	{
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . 'projet_task_extrafields' . ' WHERE fk_risk =' . $risk->id;

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i   = 0;
			$records = array();
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$record = new Task($this->db);
				$record->fetch($obj->fk_object);
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
	 * 	Return clickable name (with picto eventually)
	 *
	 * 	@param	int		$withpicto		          0=No picto, 1=Include picto into link, 2=Only picto
	 * 	@param	string	$option			          Variant where the link point to ('', 'nolink')
	 * 	@param	int		$addlabel		          0=Default, 1=Add label into string, >1=Add first chars into string
	 *  @param	string	$moreinpopup	          Text to add into popup
	 *  @param	string	$sep			          Separator between ref and label if option addlabel is set
	 *  @param	int   	$notooltip		          1=Disable tooltip
	 *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param	string	$morecss				  More css on a link
	 * 	@return	string					          String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $addlabel = 0, $moreinpopup = '', $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
	{
		global $conf, $langs, $user, $hookmanager;

		if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label                          = '';
		if ($option != 'nolink') $label = '<i class="fas fa-exclamation-triangle"></i> <u class="paddingrightonly">' . $langs->trans('Risk') . '</u>';
		$label                         .= ($label ? '<br>' : '') . '<b>' . $langs->trans('Ref') . ': </b>' . $this->ref; // The space must be after the : to not being explode when showing the title in img_picto
		if ($moreinpopup) $label       .= '<br>' . $moreinpopup;

		$url = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $this->fk_element;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if ($option == 'blank') {
			$linkclose .= ' target=_blank';
		}

		if (empty($notooltip) && $user->rights->digiriskdolibarr->risk->read) {
			if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label      = $langs->trans("ShowRisk");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		$linkstart  = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend    = '</a>';

		$result                      .= $linkstart;
		if ($withpicto) $result      .= '<i class="fas fa-exclamation-triangle"></i>' . ' ';
		if ($withpicto != 2) $result .= $this->ref;
		$result                      .= $linkend;

		global $action;
		$hookmanager->initHooks(array('riskdao'));
		$parameters               = array('id' => $this->id, 'getnomurl' => $result);
		$reshook                  = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $this may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result             .= $hookmanager->resPrint;

		return $result;
	}
}
