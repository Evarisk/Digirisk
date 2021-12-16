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
 * \file        class/accident.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for Accident
 */

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

require_once __DIR__ . '/digiriskdocuments.class.php';
require_once __DIR__ . '/digirisksignature.class.php';
require_once __DIR__ . '/openinghours.class.php';


/**
 * Class for Accident
 */
class Accident extends CommonObject
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'accident';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_accident';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element_line = 'digiriskdolibarr_accidentdet';


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
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'accident@digiriskdolibarr';

	const STATUS_IN_PROGRESS       = 1;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'              => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref'                => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'ref_ext'            => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>0,),
		'entity'             => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>0,),
		'date_creation'      => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>0,),
		'tms'                => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>0,),
		'status'             => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>2, 'index'=>0,),
		'label'              => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth200', 'help'=>"Help text", 'showoncombobox'=>'1',),
		'fk_user_victim'     => array('type'=>'integer', 'label'=>'UserVictim', 'enabled'=>'1', 'position'=>81, 'notnull'=>-1, 'visible'=>1,),
		'fk_user_employer'   => array('type'=>'integer', 'label'=>'UserEmployer', 'enabled'=>'1', 'position'=>82, 'notnull'=>-1, 'visible'=>1,),
		'accident_type'      => array('type'=>'text', 'label'=>'AccidentType', 'enabled'=>'1', 'position'=>90, 'notnull'=>-1, 'visible'=>1, 'css'=>'minwidth150',),
		'fk_element'         => array('type'=>'integer', 'label'=>'AccidentLocation', 'enabled'=>'1', 'position'=>91, 'notnull'=>-1, 'visible'=>1,'css'=>'minwidth150',),
		'fk_soc'             => array('type'=>'integer', 'label'=>'ExtSociety', 'enabled'=>'1', 'position'=>92, 'notnull'=>-1, 'visible'=>3,),
		'accident_date'      => array('type'=>'datetime', 'label'=>'AccidentDate', 'enabled'=>'1', 'position'=>100, 'notnull'=>-1, 'visible'=>1, 'css'=>'minwidth150',),
		'description'        => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>110, 'notnull'=>-1, 'visible'=>1,),
		'photo'              => array('type'=>'text', 'label'=>'Photo', 'enabled'=>'1', 'position'=>120, 'notnull'=>-1, 'visible'=>3,),
		'external_accident'  => array('type'=>'boolean', 'label'=>'ExternalAccident', 'enabled'=>'1', 'position'=>130, 'notnull'=>-1, 'visible'=>3,),
		'fk_project'         => array('type'=>'integer', 'label'=>'FKProject', 'enabled'=>'1', 'position'=>140, 'notnull'=>1, 'visible'=>0,),
		'fk_user_creat'      => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>150, 'notnull'=>1, 'visible'=>0, 'foreignkey'=>'user.rowid',),
		'fk_user_modif'      => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>160, 'notnull'=>-1, 'visible'=>0,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $status;
	public $label;
	public $accident_date;
	public $description;
	public $photo;
	public $accident_type;
	public $external_accident;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_element;
	public $fk_soc;
	public $fk_user_victim;
	public $fk_user_employer;

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
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
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
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{

		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort))
		{
			global $langs;
			$langs->load("digiriskdolibarr@digiriskdolibarr");

			$this->labelStatus[self::STATUS_IN_PROGRESS] = $langs->trans('InProgress');
		}

		$statusType = 'status'.$status;

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *    	Return a link on thirdparty (with picto)
	 *
	 *		@param	int		$withpicto		          Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
	 *		@param	string	$option			          Target of link ('', 'customer', 'prospect', 'supplier', 'project')
	 *		@param	int		$maxlen			          Max length of name
	 *      @param	int  	$notooltip		          1=Disable tooltip
	 *      @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *		@return	string					          String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0, $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$name = $this->ref;



		$result = ''; $label = '';
		$linkstart = ''; $linkend = '';

		if (!empty($this->logo) && class_exists('Form'))
		{
			$label .= '<div class="photointooltip">';
			$label .= Form::showphoto('societe', $this, 0, 40, 0, '', 'mini', 0); // Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$label .= '</div><div style="clear: both;"></div>';
		}
		elseif (!empty($this->logo_squarred) && class_exists('Form'))
		{
			/*$label.= '<div class="photointooltip">';
			$label.= Form::showphoto('societe', $this, 0, 40, 0, 'photowithmargin', 'mini', 0);	// Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$label.= '</div><div style="clear: both;"></div>';*/
		}

		$label .= '<div class="centpercent">';


		// By default
		if (empty($linkstart))
		{
			$label .= '<u>'.$langs->trans("Accident").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/custom/digiriskdolibarr/view/accident/accident_card.php?id='.$this->id;
		}

		if (!empty($this->ref))
		{
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}

		$label .= '</div>';

		$linkstart .= '"';

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowCompany");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip refurl"';
		}
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= ($maxlen ?dol_trunc($name, $maxlen) : $name);
		$result .= $linkend;

		 $result .= $hookmanager->resPrint;

		return $result;
	}

}

/**
 *	Class to manage accident workstop.
 *  Saved into database table llx_digiriskdolibarr_accident_workstop
 */
class AccidentWorkStop extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'accident_workstop';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'digiriskdolibarr_accident_workstop';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'         => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref'           => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'entity'        => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>0,),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>0,),
		'status'        => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>0, 'index'=>0,),
		'workstop_days' => array('type'=>'integer', 'label'=>'WorkStopDays', 'enabled'=>'1', 'position'=>70, 'notnull'=>-1, 'visible'=>-1,),
		'fk_accident'   => array('type'=>'integer', 'label'=>'FkAccident', 'enabled'=>'1', 'position'=>80, 'notnull'=>1, 'visible'=>0,),
	);

	public $ref;
	public $entity;
	public $date_creation;
	public $tms;
	public $status;
	public $workstop_days;
	public $fk_accident;

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

	}

	/**
	 *    Load invoice line from database
	 *
	 * @param int $rowid id of invoice line to get
	 * @return    int                    <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		global $db;

		$sql = 'SELECT t.rowid, t.ref, t.date_creation, t.status, t.workstop_days, t.fk_accident';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_workstop as t';
		$sql .= ' WHERE t.rowid = ' . $rowid;
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';

		$result = $db->query($sql);
		if ($result) {
			$objp = $db->fetch_object($result);

			$this->id = $objp->rowid;
			$this->ref = $objp->ref;
			$this->date_creation = $objp->date_creation;
			$this->status = $objp->status;
			$this->workstop_days = $objp->workstop_days;
			$this->fk_accident = $objp->fk_accident;

			$db->free($result);

			return 1;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *    Load accident line line from database
	 *
	 * @param int $rowid id of accident line line to get
	 * @return    int                    <0 if KO, >0 if OK
	 */
	public function fetchAll($parent_id = 0, $limit = 0)
	{
		global $db;
		$sql = 'SELECT t.rowid, t.ref, t.date_creation, t.status, t.workstop_days';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_workstop as t';
		if ($parent_id > 0) {
			$sql .= ' WHERE t.fk_accident = ' . $parent_id;
		} else {
			$sql .= ' WHERE 1=1';
		}
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';


		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id = $obj->rowid;
				$record->ref = $obj->ref;
				$record->date_creation = $obj->date_creation;
				$record->status = $obj->status;
				$record->workstop_days = $obj->workstop_days;
				$record->fk_accident = $obj->fk_accident;

				$records[$record->id] = $record;

				$i++;
			}


			$db->free($result);

			return $records;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *    Insert line into database
	 *
	 * @param User $user
	 * @param bool $notrigger 1 no triggers
	 * @return        int                                         <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function insert(User $user, $notrigger = false)
	{
		global $db, $user;

		$db->begin();
		$now = dol_now();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_workstop';
		$sql .= ' (ref, entity, date_creation, status, workstop_days, fk_accident';
		$sql .= ')';
		$sql .= " VALUES (";
		$sql .= "'" . $db->escape($this->ref) . "'" . ", ";
		$sql .= $this->entity . ", ";
		$sql .= "'" . $db->escape($db->idate($now)) . "'" . ", ";
		$sql .= $this->status . ", ";
		$sql .= $this->workstop_days . ", ";
		$sql .= $this->fk_accident;

		$sql .= ')';

		dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$this->id = $db->last_insert_id(MAIN_DB_PREFIX . 'accident_workstop');
			$this->rowid = $this->id; // For backward compatibility

			$db->commit();
			// Triggers
			if (!$notrigger)
			{
				// Call triggers
				$this->call_trigger(strtoupper(get_class($this)).'_CREATE', $user);
				// End call triggers
			}
			return $this->id;
		} else {
			$this->error = $db->lasterror();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *    Update line into database
	 *
	 * @param User $user User object
	 * @param int $notrigger Disable triggers
	 * @return        int                    <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function update($user = '', $notrigger = false)
	{
		global $user, $db;

		$db->begin();
		// Mise a jour ligne en base
		$sql = "UPDATE " . MAIN_DB_PREFIX . "digiriskdolibarr_accident_workstop SET";
		$sql .= " ref='" . $db->escape($this->ref) . "',";
		$sql .= " status=" . $this->status . ",";
		$sql .= " workstop_days=" . $this->workstop_days . ",";
		$sql .= " fk_accident=" . $db->escape($this->fk_accident);
		$sql .= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$db->commit();
			// Triggers
			if (!$notrigger)
			{
				// Call triggers
				$this->call_trigger(strtoupper(get_class($this)).'_MODIFY', $user);
				// End call triggers
			}
			return 1;
		} else {
			$this->error = $db->error();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *    Delete line in database
	 *
	 * @return        int                   <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function delete(User $user, $notrigger = false)
	{
		// Triggers
		if (!$notrigger) {
			// Call trigger
			$this->call_trigger(strtoupper(get_class($this)).'_DELETE', $user);
			// End call triggers
		}
		return $this->update($user,true);
	}
}

/**
 *	Class to manage accident metadata.
 *  Saved into database table llx_digiriskdolibarr_accident_metadata
 */
class AccidentMetaData extends CommonObject
{
	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'accidentmetadata';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_accidentmetadata';

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
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'accident@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'                                => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'entity'                               => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>0,),
		'date_creation'                        => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>0,),
		'tms'                                  => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>0,),
		'status'                               => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>1, 'index'=>0,),
		'relative_location'                    => array('type'=>'varchar(255)', 'label'=>'RelativeLocation', 'enabled'=>'1', 'position'=>50, 'notnull'=>-1, 'visible'=>1,),
		'victim_activity'                      => array('type'=>'text', 'label'=>'VictimActivity', 'enabled'=>'1', 'position'=>60, 'notnull'=>-1, 'visible'=>1,),
		'accident_nature'                      => array('type'=>'text', 'label'=>'AccidentNature', 'enabled'=>'1', 'position'=>70, 'notnull'=>-1, 'visible'=>1,),
		'accident_object'                      => array('type'=>'text', 'label'=>'AccidentObject', 'enabled'=>'1', 'position'=>80, 'notnull'=>-1, 'visible'=>1,),
		'accident_nature_doubt'                => array('type'=>'text', 'label'=>'AccidentNatureDoubt', 'enabled'=>'1', 'position'=>90, 'notnull'=>-1, 'visible'=>1,),
		'accident_nature_doubt_link'           => array('type'=>'url', 'label'=>'AccidentNatureDoubtLink', 'enabled'=>'1', 'position'=>100, 'notnull'=>-1, 'visible'=>1,),
		'victim_transported_to'                => array('type'=>'text', 'label'=>'VictimTransportedTo', 'enabled'=>'1', 'position'=>110, 'notnull'=>-1, 'visible'=>1,),
		'collateral_victim'                    => array('type'=>'boolean', 'label'=>'CollateralVictim', 'enabled'=>'1', 'position'=>120, 'notnull'=>-1, 'visible'=>1,),
		'workhours_morning_date_start'         => array('type'=>'datetime', 'label'=>'WorkHoursMorningDateStart', 'enabled'=>'1', 'position'=>130, 'notnull'=>-1, 'visible'=>1,),
		'workhours_morning_date_end'           => array('type'=>'datetime', 'label'=>'WorkHoursMorningDateEnd', 'enabled'=>'1', 'position'=>131, 'notnull'=>-1, 'visible'=>1,),
		'workhours_afternoon_date_start'       => array('type'=>'datetime', 'label'=>'WorkHoursAfternoonDateStart', 'enabled'=>'1', 'position'=>132, 'notnull'=>-1, 'visible'=>1,),
		'workhours_afternoon_date_end'         => array('type'=>'datetime', 'label'=>'WorkHoursAfternoonDateEnd', 'enabled'=>'1', 'position'=>133, 'notnull'=>-1, 'visible'=>1,),
		'accident_noticed'                     => array('type'=>'text', 'label'=>'AccidentNoticed', 'enabled'=>'1', 'position'=>140, 'notnull'=>-1, 'visible'=>1,),
		'accident_notice_date'                 => array('type'=>'datetime', 'label'=>'AccidentNoticeDate', 'enabled'=>'1', 'position'=>150, 'notnull'=>-1, 'visible'=>1,),
		'accident_notice_by'                   => array('type'=>'text', 'label'=>'AccidentNoticeBy', 'enabled'=>'1', 'position'=>160, 'notnull'=>-1, 'visible'=>1,),
		'accident_described_by_victim'         => array('type'=>'boolean', 'label'=>'AccidentDescribedByVictim', 'enabled'=>'1', 'position'=>170, 'notnull'=>-1, 'visible'=>1,),
		'registered_in_accident_register'      => array('type'=>'boolean', 'label'=>'RegisteredInAccidentRegister', 'enabled'=>'1', 'position'=>180, 'notnull'=>-1, 'visible'=>1,),
		'register_date'                        => array('type'=>'datetime', 'label'=>'RegisterDate', 'enabled'=>'1', 'position'=>190, 'notnull'=>-1, 'visible'=>1,),
		'register_number'                      => array('type'=>'varchar(255)', 'label'=>'RegisterNumber', 'enabled'=>'1', 'position'=>200, 'notnull'=>-1, 'visible'=>1,),
		'consequence'                          => array('type'=>'text', 'label'=>'Consequence', 'enabled'=>'1', 'position'=>210, 'notnull'=>-1, 'visible'=>1, 'arrayofkeyval'=>array(0=>'Draft', 1=>'Validated', 9=>'Canceled')),
		'police_report'                        => array('type'=>'boolean', 'label'=>'PoliceReport', 'enabled'=>'1', 'position'=>220, 'notnull'=>-1, 'visible'=>1,),
		'police_report_by'                     => array('type'=>'text', 'label'=>'PoliceReportBy', 'enabled'=>'1', 'position'=>230, 'notnull'=>-1, 'visible'=>1,),
		'first_person_noticed_is_witness'      => array('type'=>'text', 'label'=>'FirstPersonNoticedIsWitness', 'enabled'=>'1', 'position'=>240, 'notnull'=>-1, 'visible'=>1,),
		'thirdparty_responsibility'            => array('type'=>'boolean', 'label'=>'ThirdPartyResponsability', 'enabled'=>'1', 'position'=>250, 'notnull'=>-1, 'visible'=>1,),
		'accident_investigation'               => array('type'=>'boolean', 'label'=>'AccidentInvestigation', 'enabled'=>'1', 'position'=>260, 'notnull'=>-1, 'visible'=>1,),
		'accident_investigation_link'          => array('type'=>'url', 'label'=>'AccidentInvestigationLink', 'enabled'=>'1', 'position'=>270, 'notnull'=>-1, 'visible'=>1,),
		'cerfa_link'                           => array('type'=>'url', 'label'=>'CerfaLink', 'enabled'=>'1', 'position'=>280, 'notnull'=>-1, 'visible'=>1,),
		'json'                                 => array('type'=>'text', 'label'=>'Json', 'enabled'=>'1', 'position'=>290, 'notnull'=>-1, 'visible'=>1,),
		'fk_user_witness'                      => array('type'=>'integer', 'label'=>'UserVictim', 'enabled'=>'1', 'position'=>300, 'notnull'=>-1, 'visible'=>-2,),
		'fk_soc_responsible'                   => array('type'=>'integer', 'label'=>'SocResponsible', 'enabled'=>'1', 'position'=>310, 'notnull'=>-1, 'visible'=>-2,),
		'fk_soc_responsible_insurance_society' => array('type'=>'integer', 'label'=>'SocResponsibleInsuranceSociety', 'enabled'=>'1', 'position'=>320, 'notnull'=>-1, 'visible'=>-2,),
		'fk_accident'                          => array('type'=>'integer', 'label'=>'FkAccident', 'enabled'=>'1', 'position'=>330, 'notnull'=>-1, 'visible'=>-2,),
	);

	public $rowid;
	public $entity;
	public $date_creation;
	public $tms;
	public $status;
	public $relative_location;
	public $victim_activity;
	public $accident_nature;
	public $accident_object;
	public $accident_nature_doubt;
	public $accident_nature_doubt_link;
	public $victim_transported_to;
	public $collateral_victim;
	public $workhours_morning_date_start;
	public $workhours_morning_date_end;
	public $workhours_afternoon_date_start;
	public $workhours_afternoon_date_end;
	public $accident_noticed;
	public $accident_notice_date;
	public $accident_notice_by;
	public $accident_described_by_victim;
	public $registered_in_accident_register;
	public $register_date;
	public $register_number;
	public $consequence;
	public $police_report;
	public $police_report_by;
	public $first_person_noticed_is_witness;
	public $thirdparty_responsibility;
	public $accident_investigation;
	public $accident_investigation_link;
	public $cerfa_link;
	public $json;
	public $fk_user_witness;
	public $fk_soc_responsible;
	public $fk_soc_responsible_insurance_society;
	public $fk_accident;

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
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 * @throws Exception
	 */
	public function create(User $user, $notrigger = false)
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . "$this->table_element";
		$sql .= " SET status = 0";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';
		else $sql .= ' WHERE 1 = 1';
		$sql .= " AND fk_accident = " . $this->fk_accident;

		dol_syslog("accidentmetadata.class::create", LOG_DEBUG);
		$this->db->query($sql);
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	int    $id				Id object
	 * @param	string $ref				Ref
	 * @param	string	$morewhere		More SQL filters (' AND ...')
	 * @return 	int         			<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $morewhere = '')
	{
		return $this->fetchCommon($id, $ref, $morewhere);
	}
}

/**
 *	Class to manage accident lesion.
 *  Saved into database table llx_digiriskdolibarr_accident_lesion
 */
class AccidentLesion extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'accident_lesion';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'digiriskdolibarr_accident_lesion';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'               => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref'                 => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'entity'              => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>0,),
		'date_creation'       => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>0,),
		'tms'                 => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>0,),
		'lesion_localization' => array('type'=>'text', 'label'=>'LesionLocalization', 'enabled'=>'1', 'position'=>60, 'notnull'=>-1, 'visible'=>1,),
		'lesion_nature'       => array('type'=>'text', 'label'=>'LesionNature', 'enabled'=>'1', 'position'=>70, 'notnull'=>-1, 'visible'=>1,),
		'fk_accident'         => array('type'=>'integer', 'label'=>'FkAccident', 'enabled'=>'1', 'position'=>80, 'notnull'=>1, 'visible'=>0,),
	);

	public $ref;
	public $entity;
	public $date_creation;
	public $tms;
	public $lesion_localization;
	public $lesion_nature;
	public $fk_accident;

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

	}

	/**
	 *    Load accident lesion from database
	 *
	 * @param int $rowid id of accident lesion to get
	 * @return    int                    <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		global $db;

		$sql = 'SELECT t.rowid, t.ref, t.date_creation, t.lesion_localization, t.lesion_nature, t.fk_accident';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_lesion as t';
		$sql .= ' WHERE t.rowid = ' . $rowid;
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';

		$result = $db->query($sql);
		if ($result) {
			$objp = $db->fetch_object($result);

			$this->id = $objp->rowid;
			$this->ref = $objp->ref;
			$this->date_creation = $objp->date_creation;
			$this->lesion_localization = $objp->lesion_localization;
			$this->lesion_nature = $objp->lesion_nature;
			$this->fk_accident = $objp->fk_accident;

			$db->free($result);

			return 1;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *    Load accident lesion from database
	 *
	 * @param int $rowid id of accident lesion to get
	 * @return    int                    <0 if KO, >0 if OK
	 */
	public function fetchAll($parent_id = 0, $limit = 0)
	{
		global $db;
		$sql = 'SELECT t.rowid, t.ref, t.date_creation, t.lesion_localization, t.lesion_nature';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_lesion as t';
		if ($parent_id > 0) {
			$sql .= ' WHERE t.fk_accident = ' . $parent_id;
		} else {
			$sql .= ' WHERE 1=1';
		}
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';

		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id = $obj->rowid;
				$record->ref = $obj->ref;
				$record->date_creation = $obj->date_creation;
				$record->lesion_localization = $obj->lesion_localization;
				$record->lesion_nature = $obj->lesion_nature;
				$record->fk_accident = $obj->fk_accident;

				$records[$record->id] = $record;

				$i++;
			}


			$db->free($result);

			return $records;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *    Insert line into database
	 *
	 * @param User $user
	 * @param bool $notrigger 1 no triggers
	 * @return        int                                         <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function insert(User $user, $notrigger = false)
	{
		global $db, $user;

		$db->begin();
		$now = dol_now();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_lesion';
		$sql .= ' (ref, entity, date_creation, lesion_localization, lesion_nature, fk_accident';
		$sql .= ')';
		$sql .= " VALUES (";
		$sql .= "'" . $db->escape($this->ref) . "'" . ", ";
		$sql .= $this->entity . ", ";
		$sql .= "'" . $db->escape($db->idate($now)) . "'" . ", ";
		$sql .= "'" . $db->escape($this->lesion_localization) . "'" . ", ";
		$sql .= "'" . $db->escape($this->lesion_nature) . "'" . ", ";
		$sql .= $this->fk_accident;

		$sql .= ')';

		dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$this->id = $db->last_insert_id(MAIN_DB_PREFIX . 'accident_lesion');
			$this->rowid = $this->id; // For backward compatibility

			$db->commit();
			// Triggers
			if (!$notrigger)
			{
				// Call triggers
				$this->call_trigger(strtoupper(get_class($this)).'_CREATE', $user);
				// End call triggers
			}
			return $this->id;
		} else {
			$this->error = $db->lasterror();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *    Update line into database
	 *
	 * @param User $user User object
	 * @param int $notrigger Disable triggers
	 * @return        int                    <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function update($user = '', $notrigger = false)
	{
		global $user, $db;

		$db->begin();
		// Mise a jour ligne en base
		$sql = "UPDATE " . MAIN_DB_PREFIX . "digiriskdolibarr_accident_lesion SET";
		$sql .= " ref='" . $db->escape($this->ref) . "',";
		$sql .= " lesion_localization='" . $db->escape($this->lesion_localization) . "',";
		$sql .= " lesion_nature='" . $db->escape($this->lesion_nature) . "',";
		$sql .= " fk_accident=" . $db->escape($this->fk_accident);
		$sql .= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$db->commit();
			// Triggers
			if (!$notrigger)
			{
				// Call triggers
				$this->call_trigger(strtoupper(get_class($this)).'_MODIFY', $user);
				// End call triggers
			}
			return 1;
		} else {
			$this->error = $db->error();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *    Delete line in database
	 *
	 * @return        int                   <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function delete(User $user, $notrigger = false)
	{
		global $user, $db;

		$db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "digiriskdolibarr_accident_lesion WHERE rowid = " . $this->id;
		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
		if ($db->query($sql)) {
			$db->commit();
			// Triggers
			if (!$notrigger)
			{
				// Call trigger
				$this->call_trigger(strtoupper(get_class($this)).'_DELETE', $user);
				// End call triggers
			}
			return 1;
		} else {
			$this->error = $db->error() . " sql=" . $sql;
			$db->rollback();
			return -1;
		}
	}
}

/**
 *	Class to manage accident signature.
 *  Saved into database table llx_digiriskdolibarr_object_signature
 */
class AccidentSignature extends DigiriskSignature
{
	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */

	public $object_type = 'accident';

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
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $old_table_element = '')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();
		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		if (dol_strlen($old_table_element)) {
			$sql .= ' FROM '.MAIN_DB_PREFIX.$old_table_element;
		} else {
			$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		$sql .= ' AND object_type = "' . $this->object_type . '"';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 'rowid') {
					$sqlwhere[] = $key.'='.$value;
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key.' IN ('.$this->db->sanitize($this->db->escape($value)).')';
				} else {
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
}
