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
 * \file        class/digiriskelement.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for DigiriskElement (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/riskanalysis/riskassessment.class.php';

/**
 * Class for DigiriskElement
 */
class DigiriskElement extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digiriskelement';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_digiriskelement';

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
	public $picto = 'digiriskelement@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'         => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref'           => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'ref_ext'       => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>0,),
		'entity'        => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>-2,),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-2,),
		'import_key'    => array('type'=>'integer', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>-2,),
		'status'        => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'default' => 1, 'visible'=>1, 'index'=>1,),
		'label'         => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>80, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth400', 'help'=>"Help text", 'showoncombobox'=>'1',),
		'description'   => array('type'=>'textarea', 'label'=>'Description', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>3,),
		'element_type'  => array('type'=>'varchar(50)', 'label'=>'ElementType', 'enabled'=>'1', 'position'=>100, 'notnull'=>-1, 'visible'=>1,),
		'photo'         => array('type'=>'varchar(255)', 'label'=>'Photo', 'enabled'=>'1', 'position'=>105, 'notnull'=>-1, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>110, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>120, 'notnull'=>-1, 'visible'=>-2,),
		'fk_parent'     => array('type'=>'integer', 'label'=>'ParentElement', 'enabled'=>'1', 'position'=>130, 'notnull'=>1, 'visible'=>1, 'default'=>0,),
		'fk_standard'   => array('type'=>'integer', 'label'=>'Standard', 'enabled'=>'1', 'position'=>140, 'notnull'=>1, 'visible'=>0, 'default'=>1,),
		'rank'          => array('type'=>'integer', 'label'=>'Order', 'enabled'=>'1', 'position'=>150, 'notnull'=>1, 'visible'=>0),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $label;
	public $description;
	public $element_type;
	public $photo;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_parent;
	public $fk_standard;
	public $rank;

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
		global $conf;
		$this->element = $this->element_type . '@digiriskdolibarr';
		$this->fk_standard = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
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
	 * Load ordered flat list of DigiriskElement in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchDigiriskElementFlat($parent_id)
	{
		$object = new DigiriskElement($this->db);
		$objects = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0' ));

		$elements = recurse_tree($parent_id, 0, $objects);
		if ($elements > 0 && !empty($elements)) {
			// Super fonction itérations flat.
			$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($elements));
			foreach ($it as $key => $v) {
				$element[$key][$v] = $v;
			}
			if (is_array($element)) {
				$children_id = array_shift($element);
			}

			if (!empty ($children_id)) {
				foreach ($children_id as $id) {
					$object = new DigiriskElement($this->db);
					$result = $object->fetch($id);
					if (!empty ($result)) {
						$depth = 'depth' . $id;

						$digiriskelementlist[$id]['object'] = $object;
						$digiriskelementlist[$id]['depth'] = array_shift($element[$depth]);
					}
				}
			}
		}
		return $digiriskelementlist;
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
		global $conf;

		$this->fk_parent = $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH;
		return $this->update($user);
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

	public function getRiskAssessmentCategoriesNumber() {
		$risk = new Risk($this->db);
		$risks = $risk->fetchFromParent($this->id);
		$scale_counter = array(
			1 => 0,
			2 => 0,
			3 => 0,
			4 => 0
		);
		if(!empty($risks) && $risks > 0) {
			foreach ($risks as $risk) {
				$riskassessment = new RiskAssessment($this->db);
				$riskassessment = $riskassessment->fetchFromParent($risk->id, 1);
				if (!empty($riskassessment) && $riskassessment > 0) {
					$riskassessment = array_shift($riskassessment);
					$scale = $riskassessment->get_evaluation_scale();
					$scale_counter[$scale] += 1;
				}
			}
		}

		return $scale_counter;
	}

	/**
	 *  Output html form to select a third party.
	 *  Note, you must use the select_company to get the component to select a third party. This function must only be called by select_company.
	 *
	 * @param string $selected   Preselected type
	 * @param string $htmlname   Name of field in form
	 * @param string $filter     Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
	 * @param string $showempty  Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
	 * @param int    $showtype   Show third party type in combolist (customer, prospect or supplier)
	 * @param int    $forcecombo Force to use standard HTML select component without beautification
	 * @param array  $events     Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 * @param string $filterkey  Filter on key value
	 * @param int    $outputmode 0=HTML select string, 1=Array
	 * @param int    $limit      Limit number of answers
	 * @param string $morecss    Add more css styles to the SELECT component
	 * @param string $moreparam  Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 * @param bool   $multiple   add [] in the name of element and add 'multiple' attribut
	 * @return       string      HTML string with
	 * @throws Exception
	 */
	public function select_digiriskelement_list($selected = '', $htmlname = 'socid', $filter = '', $showempty = '1', $showtype = 0, $forcecombo = 0, $events = array(), $filterkey = '', $outputmode = 0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $multiple = false, $noroot = 0)
	{
		// phpcs:enable
		global $conf, $user, $langs;

		$out = '';
		$num = 0;
		$outarray = array();

		if ($selected === '') $selected = array();
		elseif (!is_array($selected)) $selected = array($selected);

		// Clean $filter that may contains sql conditions so sql code
		if (function_exists('testSqlAndScriptInject')) {
			if (testSqlAndScriptInject($filter, 3) > 0) {
				$filter = '';
			}
		}
		// On recherche les societes
		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."digiriskdolibarr_digiriskelement as s";

		$sql .= " WHERE s.entity IN (".getEntity($this->table_element).")";
		if ($filter) $sql .= " AND (".$filter.")";
		if ($moreparam > 0 ) {
			$children = $this->fetchDigiriskElementFlat($moreparam);
			if (! empty($children) && $children > 0) {
				foreach ($children as $key => $value) {
					$sql .= " AND NOT s.rowid =" . $key;
				}
			}
			$sql .= " AND NOT s.rowid =" . $moreparam;
		}
		if ($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) {
			$masked_content = $this->fetchDigiriskElementFlat($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);
			if (! empty($masked_content) && $masked_content > 0) {
				foreach ($masked_content as $key => $value) {
					$sql .= " AND NOT s.rowid =" . $key;
				}
			}
			$sql .= " AND NOT s.rowid =" . $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH;

		}
		$sql .= $this->db->order("rowid", "ASC");
		$sql .= $this->db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this)."::select_digiriskelement_list", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if (!$forcecombo)
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, 0);
			}

			// Construct $out and $outarray
			$out .= '<select id="'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'"'.($moreparam ? ' '.$moreparam : '').' name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').'>'."\n";

			$num = $this->db->num_rows($resql);
			$i = 0;
			if (!$noroot) $out .= '<option value="0" selected>'.$langs->trans('Root') . ' : ' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</option>';

			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$label = $obj->ref . ' - ' . $obj->label;


					if (empty($outputmode))
					{
						if (in_array($obj->rowid, $selected))
						{
							$out .= '<option value="'.$obj->rowid.'" selected>'.$label.'</option>';
						}
						else
						{
							$out .= '<option value="'.$obj->rowid.'">'.$label.'</option>';
						}
					}
					else
					{
						array_push($outarray, array('key'=>$obj->rowid, 'value'=>$label, 'label'=>$label));
					}

					$i++;
					if (($i % 10) == 0) $out .= "\n";
				}
			}
			$out .= '</select>'."\n";
		}
		else
		{
			dol_print_error($this->db);
		}

		$this->result = array('nbofdigiriskelement'=>$num);

		if ($outputmode) return $outarray;
		return $out;
	}

	/**
	 * Return fk_object from wp_digi_id extrafields
	 *
	 * @param $wp_digi_id
	 * @return array|int                 int <0 if KO, array of pages if OK
	 * @throws Exception
	 */
	public function fetch_id_from_wp_digi_id($wp_digi_id)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT ';
		$sql .= ' *';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.'_extrafields as t';
		$sql .= ' WHERE wp_digi_id ='.$wp_digi_id;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < 1)
			{
				$obj = $this->db->fetch_object($resql);
				$i++;
			}
			$this->db->free($resql);

			return $obj->fk_object;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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
	function getNomUrl($withpicto = 0, $option = '', $addlabel = 0, $moreinpopup = '', $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
	{
		global $conf, $langs, $user, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '';
		if ($option != 'nolink') $label =  '<i class="fas fa-info-circle"></i> <u class="paddingrightonly">'.$langs->trans(ucwords($this->element_type, 'k')).'</u>';
		$label .= ($label ? '<br>' : '').'<b>'.$langs->trans('Ref').': </b>'.$this->ref; // The space must be after the : to not being explode when showing the title in img_picto
		$label .= ($label ? '<br>' : '').'<b>'.$langs->trans('Label').': </b>'.$this->label; // The space must be after the : to not being explode when showing the title in img_picto
		if ($moreinpopup) $label .= '<br>'.$moreinpopup;

		$url = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1).'?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if ($option == 'blank'){
			$linkclose .= ' target=_blank';
		}

		if (empty($notooltip) && $user->rights->digiriskdolibarr->digiriskelement->read)
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowDigiriskElement");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		}
		else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= '<i class="fas fa-info-circle"></i>' . ' ';
		if ($withpicto != 2) $result .= $this->ref;
		if ($withpicto != 2) $result .= (($addlabel && $this->label) ? $sep.dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('digiriskelementtdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $this may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 * 	Return list of deleted elements
	 *
	 * 	@param	bool   $only_ids
	 * 	@return	array  Array with ids
	 */
	function getTrashList($only_ids = true)
	{
		global $conf, $langs, $user, $hookmanager;
		$objects = $this->fetchAll('',  'rank',  0,  0);
		$recurse_tree = recurse_tree($this->id, 0, $objects);
		$ids = [];

		array_walk_recursive($recurse_tree, 	function($item, $key) use (&$ids) {
			if (is_object($item)) {
				$ids[ $item->id] = $item->id;
			}
		}, $ids);

		return $ids;
	}
}
