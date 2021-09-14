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
 * \file        class/firepermitdocument.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for FirePermit
 */

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

require_once __DIR__ . '/digiriskdocuments.class.php';

/**
 * Class for FirePermit
 */
class FirePermit extends CommonObject
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'firepermit';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_firepermit';

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
	public $picto = 'firepermitdocument@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'              => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref'                => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'ref_ext'            => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>0,),
		'entity'             => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>-1,),
		'date_creation'      => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>-2,),
		'tms'                => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-2,),
		'status'             => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1, 'index'=>0,),
		'label'              => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth200', 'help'=>"Help text", 'showoncombobox'=>'1',),
		'date_start'         => array('type'=>'datetime', 'label'=>'StartDate', 'enabled'=>'1', 'position'=>100, 'notnull'=>-1, 'visible'=>1,),
		'date_end'           => array('type'=>'datetime', 'label'=>'EndDate', 'enabled'=>'1', 'position'=>130, 'notnull'=>-1, 'visible'=>1,),
		'fk_user_creat'      => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>110, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif'      => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>120, 'notnull'=>-1, 'visible'=>-2,),
		'fk_preventionplan'  => array('type'=>'integer', 'label'=>'PreventionPlan', 'enabled'=>'1', 'position'=>130, 'notnull'=>-1, 'visible'=>-2,),
	);

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
	 *	Return label of contact status
	 *
	 *	@param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * 	@return 	string				Label of contact status
	 */
	public function getLibStatut($mode)
	{
		return '';
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
			$label .= '<u>'.$langs->trans("FirePermit").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/custom/digiriskdolibarr/firepermit_card.php?id='.$this->id;
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
 *	Class to manage invoice lines.
 *  Saved into database table llx_firepermitdet
 */
class FirePermitLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'firepermitdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'firepermitdet';

	public $ref = '';

	public $date_creation = '';

	public $description = '';

	public $category = '';

	public $use_equipment = '';

	public $fk_firepermit = '';

	public $fk_element = '';


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
	 *	Load invoice line from database
	 *
	 *	@param	int		$rowid      id of invoice line to get
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		global $db;

		$sql = 'SELECT t.rowid, t.ref, t.date_creation, t.description, t.category, t.use_equipment, t.fk_firepermit, t.fk_element ';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'digiriskdolibarr_firepermitdet as t';
		$sql .= ' WHERE t.rowid = '.$rowid;
		$sql .= ' AND entity IN ('.getEntity($this->table_element).')';

		$result = $db->query($sql);
		if ($result)
		{
			$objp = $db->fetch_object($result);

			$this->id                = $objp->rowid;
			$this->ref               = $objp->ref;
			$this->date_creation     = $objp->date_creation;
			$this->description       = $objp->description;
			$this->category          = $objp->category;
			$this->use_equipment     = $objp->use_equipment;
			$this->fk_firepermit     = $objp->fk_firepermit;
			$this->fk_element        = $objp->fk_element;

			$db->free($result);

			return 1;
		}
		else
		{
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *	Load firepermit line line from database
	 *
	 *	@param	int		$rowid      id of firepermit line line to get
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function fetchAll($parent_id = 0, $limit = 0)
	{
		global $db;
		$sql = 'SELECT t.rowid, t.ref, t.date_creation, t.description, t.category, t.use_equipment, t.fk_element';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'digiriskdolibarr_firepermitdet as t';
		if ($parent_id > 0) {
			$sql .= ' WHERE t.fk_firepermit = '.$parent_id;
		} else {
			$sql .= ' WHERE 1=1';
		}
		$sql .= ' AND entity IN ('.getEntity($this->table_element).')';


		$result = $db->query($sql);

		if ($result)
		{
			$num = $db->num_rows($result);

			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num))
			{
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id                = $obj->rowid;
				$record->ref               = $obj->ref;
				$record->date_creation     = $obj->date_creation;
				$record->description       = $obj->description;
				$record->category          = $obj->category;
				$record->use_equipment     = $obj->use_equipment;
				$record->fk_firepermit     = $obj->fk_firepermit;
				$record->fk_element        = $obj->fk_element;

				$records[$record->id] = $record;

				$i++;
			}


			$db->free($result);

			return $records;
		}
		else
		{
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      int		$notrigger		                 1 no triggers
	 *  @param      int     $noerrorifdiscountalreadylinked  1=Do not make error if lines is linked to a discount and discount already linked to another
	 *	@return		int						                 <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0, $noerrorifdiscountalreadylinked = 0)
	{
		global $db, $user;

		$error = 0;


		// Clean parameters
		$this->description = trim($this->description);

		$db->begin();
		$now = dol_now();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'digiriskdolibarr_firepermitdet';
		$sql .= ' (ref, entity, date_creation, description, category, use_equipment, fk_firepermit, fk_element';
		$sql .= ')';
		$sql .= " VALUES (";
		$sql .= "'" . $db->escape($this->ref) . "'" . ", ";
		$sql .= $this->entity . ", ";
		$sql .= "'" . $db->escape($db->idate($now)) . "'" . ", ";
		$sql .= "'" . $db->escape($this->description) . "'" . ", ";
		$sql .= $this->category . ", ";
		$sql .= "'" . $db->escape($this->use_equipment) . "'" . ", ";
		$sql .= $this->fk_firepermit . ", ";
		$sql .= $this->fk_element ;

		$sql .= ')';

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql)
		{
			$this->id = $db->last_insert_id(MAIN_DB_PREFIX.'firepermitdet');
			$this->rowid = $this->id; // For backward compatibility

			$db->commit();
			return $this->id;
		}
		else
		{
			$this->error = $db->lasterror();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *	Update line into database
	 *
	 *	@param		User	$user		User object
	 *	@param		int		$notrigger	Disable triggers
	 *	@return		int					<0 if KO, >0 if OK
	 */
	public function update($user = '', $notrigger = 1)
	{
		global $user, $conf, $db;

		$error = 0;

		// Clean parameters
		$this->description = trim($this->description);

		$db->begin();
		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."digiriskdolibarr_firepermitdet SET";
		$sql .= " ref='".$db->escape($this->ref)."',";
		$sql .= " description='".$db->escape($this->description)."',";
		$sql .= " category=".$db->escape($this->category) . ",";
		$sql .= " use_equipment='".$db->escape($this->use_equipment)."'" . ",";
		$sql .= " fk_firepermit=".$db->escape($this->fk_firepermit) . ",";
		$sql .= " fk_element=".$db->escape($this->fk_element);
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql)
		{
			$db->commit();
			return 1;
		}
		else
		{
			$this->error = $db->error();
			$db->rollback();
			return -2;
		}
	}

	/**
	 * 	Delete line in database
	 *
	 *	@return	    int		           <0 if KO, >0 if OK
	 */
	public function delete()
	{
		global $user, $db;

		$db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."digiriskdolibarr_firepermitdet WHERE rowid = ".$this->id;
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		if ($db->query($sql))
		{
			$db->commit();
			return 1;
		}
		else
		{
			$this->error = $db->error()." sql=".$sql;
			$db->rollback();
			return -1;
		}
	}

}
