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
 * \file        class/riskassessment.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for RiskAssessment (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for RiskAssessment
 */
class RiskAssessment extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'riskassessment';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_riskassessment';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for riskassessment. Must be the part after the 'object_' into object_riskassessment.png
	 */
	public $picto = 'riskassessment@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>31, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>32, 'notnull'=>1, 'visible'=>-0,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>33, 'notnull'=>0, 'visible'=>0,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>34, 'notnull'=>1, 'visible'=>0, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>35, 'notnull'=>-1, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>36, 'notnull'=>-1, 'visible'=>0,),
		'ref_ext' => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>37, 'notnull'=>0, 'visible'=>0,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>38, 'notnull'=>0, 'visible'=>0,),
		'cotation' => array('type'=>'integer', 'label'=>'Evaluation', 'enabled'=>'1', 'position'=>39, 'notnull'=>0, 'visible'=>4,),
		'has_tasks' => array('type'=>'integer', 'label'=>'Tasks', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>4,),
		'method' => array('type'=>'varchar(50)', 'label'=>'EvaluationMethod', 'enabled'=>'1', 'position'=>41, 'notnull'=>0, 'visible'=>0,),
		'fk_risk' => array('type'=>'integer', 'label'=>'ParentRisk', 'enabled'=>'1', 'position'=>42, 'notnull'=>0, 'visible'=>0,),
		'gravite' => array('type'=>'integer', 'label'=>'Gravity', 'enabled'=>'1', 'position'=>43, 'notnull'=>0, 'visible'=>0,),
		'protection' => array('type'=>'integer', 'label'=>'Protection', 'enabled'=>'1', 'position'=>44, 'notnull'=>0, 'visible'=>0,),
		'occurrence' => array('type'=>'integer', 'label'=>'Occurrence', 'enabled'=>'1', 'position'=>45, 'notnull'=>0, 'visible'=>0,),
		'formation' => array('type'=>'integer', 'label'=>'Formation', 'enabled'=>'1', 'position'=>46, 'notnull'=>0, 'visible'=>0,),
		'exposition' => array('type'=>'integer', 'label'=>'Exposition', 'enabled'=>'1', 'position'=>47, 'notnull'=>0, 'visible'=>0,),
		'comment' => array('type'=>'text', 'label'=>'Comment', 'enabled'=>'1', 'position'=>48, 'notnull'=>0, 'visible'=>0,),
		'photo' => array('type'=>'varchar(128)', 'label'=>'Photo', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>0,),
		'entity' => array('type'=>'integer', 'label'=>'entity', 'enabled'=>'1', 'position'=>49, 'notnull'=>0, 'visible'=>0,),
	);
	public $rowid;
	public $ref;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $ref_ext;
	public $status;
	public $cotation;
	public $has_tasks;
	public $method;
	public $fk_risk;
	public $gravite;
	public $protection;
	public $occurrence;
	public $formation;
	public $exposition;
	public $comment;
	public $photo;
	public $entity;

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
		$previousEvaluation = $this->fetchFromParent($this->fk_risk, 1);

		// Change le statut des ressources précédentes à 0
		$sql = "UPDATE ".MAIN_DB_PREFIX."digiriskdolibarr_digiriskevaluation";
		$sql .= " SET status = 0";
		$sql .= " WHERE fk_risk = ".$this->fk_risk;
		$resql = $this->db->query($sql);

		//RAJOUTER LIGNE POUR LE SELECT ENTITY
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
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $parent_id   Id parent object
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchFromParent($parent_id, $active = 0)
	{
		$filter = array('customsql' => 'fk_risk=' . $this->db->escape($parent_id));
		if ($active) $filter['status'] = 1;

		$result = $this->fetchAll('', '', 0, 0, $filter, 'AND');
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
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
		global $conf;

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
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @return int             <0 if KO, >0 if OK
	 */
	public function updateEvaluationStatus(User $user, $risk_id)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);
		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		$sql .= ' AND fk_risk = '.$risk_id;
		$sql .= ' ORDER BY t.rowid DESC';
		$sql .= ' LIMIT 1';

		$resql = $this->db->query($sql);

		if ($resql) {
			$evaluation = new RiskAssessment($this->db);
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			$evaluation->fetch($obj->rowid);
			$evaluation->status = 1;
			$records = $evaluation->update($user);
			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
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
	 * Return scale level for risk evaluation
	 *
	 * @return	int			between 1 and 4
	 */
	public function get_evaluation_scale() {
		if (!$this->cotation) {
			return 1;
		}
		switch ($this->cotation) {
			case ($this->cotation < 48):
				return 1;
			case ($this->cotation < 51) :
				return 2;
			case( $this->cotation < 80):
				return 3;
			case ($this->cotation >= 80):
				return 4;
		}
	}

	/**
	 * Return scale level for risk evaluation
	 *
	 * @return	int			between 1 and 4
	 */
	public function show_photo_evaluation($element) {
		global $conf;

		$risk = new Risk($this->db);

		$relativepath = 'digiriskdolibarr/medias';
		$modulepart   = 'ecm';
		$path         = DOL_URL_ROOT .'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
		$filearray    = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element->element.'/'.$element->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);

		if (count($filearray)) : ?>
			<?php print '<span class="floatleft inline-block valignmiddle divphotoref">'.$risk->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element->element, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $element->element).'</span>'; ?>
		<?php else : ?>
			<?php $nophoto = '/public/theme/common/nophoto.png'; ?>
			<div class="action photo default-photo evaluation-photo-open modal-open" value="0">
				<span class="floatleft inline-block valignmiddle divphotoref photo-edit0">
					<input type="hidden" value="<?php echo $path ?>" id="pathToPhoto0">
					<img class="photo maxwidth50"  src="<?php echo DOL_URL_ROOT. $nophoto ?>">
				</span>
			</div>
		<?php endif; ?>
		<?php
	}
}
