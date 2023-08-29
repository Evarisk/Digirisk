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
 * \file    class/digiriskelement.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a CRUD class file for DigiriskElement (Create/Read/Update/Delete).
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';

/**
 * Class for DigiriskElement.
 */
class DigiriskElement extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'digiriskelement';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiriskdolibarr_digiriskelement';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public int $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for firepermit. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'firepermit@digiriskdolibarr' if picto is file 'img/object_firepermit.png'.
     */
    public string $picto = 'digiriskelement@digiriskdolibarr';

	public const STATUS_DRAFT     = 0;
	public const STATUS_VALIDATED = 1;

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     * 'label' the translation key.
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     * 'position' is the sort order of field.
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     * 'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     * 'index' if we want an index in database.
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     * 'comment' is not used. You can store here any text of your choice. It is not used by application.
     * 'validate' is 1 if you need to validate with $this->validateField()
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
	public array $fields = [
		'rowid'            => ['type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
		'ref'              => ['type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"],
		'ref_ext'          => ['type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,],
		'entity'           => ['type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => -1,],
		'date_creation'    => ['type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => -2,],
		'tms'              => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => -2,],
		'import_key'       => ['type' => 'integer', 'label' => 'ImportId', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => -2,],
		'status'           => ['type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 1, 'default' => 1, 'visible' => 1, 'index' => 1,],
		'label'            => ['type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 80, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth400', 'help' => "Help text", 'showoncombobox' => '1',],
		'description'      => ['type' => 'textarea', 'label' => 'Description', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 3,],
		'element_type'     => ['type' => 'varchar(50)', 'label' => 'ElementType', 'enabled' => '1', 'position' => 100, 'notnull' => -1, 'visible' => 1,],
		'photo'            => ['type' => 'varchar(255)', 'label' => 'Photo', 'enabled' => '1', 'position' => 105, 'notnull' => -1, 'visible' => -2,],
		'show_in_selector' => ['type' => 'boolean', 'label' => 'ShowInSelectOnPublicTicketInterface', 'enabled' => '1', 'position' => 106, 'notnull' => 1, 'visible' => 1, 'default' => 1,],
		'fk_user_creat'    => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 110, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',],
		'fk_user_modif'    => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 120, 'notnull' => -1, 'visible' => -2,],
		'fk_parent'        => ['type' => 'integer', 'label' => 'ParentElement', 'enabled' => '1', 'position' => 130, 'notnull' => 1, 'visible' => 1, 'default' => 0,],
		'fk_standard'      => ['type' => 'integer', 'label' => 'Standard', 'enabled' => '1', 'position' => 140, 'notnull' => 1, 'visible' => 0, 'default' => 1,],
		'ranks'            => ['type' => 'integer', 'label' => 'Order', 'enabled' => '1', 'position' => 150, 'notnull' => 1, 'visible' => 0],
    ];

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
	public $show_in_selector;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_parent;
	public $fk_standard;
	public $ranks;

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
     * Create object into database.
     *
     * @param  User $user      User that creates.
     * @param  bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, ID of created object if OK.
     */
    public function create(User $user, bool $notrigger = false): int
	{
		global $conf;
		if (empty($this->ref)) {
			$type           = 'DIGIRISKDOLIBARR_' . strtoupper($this->element_type) . '_ADDON';
			$digirisk_addon = $conf->global->$type;
			$modele         = new $digirisk_addon($this->db);
			$ref = $modele->getNextValue($this);
			$this->ref = $ref;
		}
		$this->element     = $this->element_type . '@digiriskdolibarr';
		$this->fk_standard = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
		$this->status      = 1;

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load ordered flat list of DigiriskElement in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @return array         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchDigiriskElementFlat($parent_id)
	{
		$object  = new DigiriskElement($this->db);
		$objects = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0' ));

		if (is_array($objects)) {
			$elements = recurse_tree($parent_id, 0, $objects);
			$digiriskelementlist = array();
			if ($elements > 0 && ! empty($elements)) {
				// Super function iterations flat.
				$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($elements));
				$element = array();
				foreach ($it as $key => $v) {
					$element[$key][$v] = $v;
				}
				$children_id = array_shift($element);

				if ( ! empty($children_id)) {
					foreach ($children_id as $id) {
						$object = $objects[$id];
						$depth = 'depth' . $id;
						$digiriskelementlist[$id]['object'] = $object;
						$digiriskelementlist[$id]['depth']  = array_shift($element[$depth]);
					}
				}
			}
			return $digiriskelementlist;
		} else {
			return array();
		}
	}

    /**
     * Delete object in database.
     *
     * @param  User $user       User that deletes.
     * @param  bool $notrigger  false = launch triggers after, true = disable triggers.
     * @param  bool $softDelete Don't delete object.
     * @return int              0 < if KO, > 0 if OK.
     */
    public function delete(User $user, bool $notrigger = false, bool $softDelete = true): int
	{
		global $conf;

		$this->fk_parent = $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH;

		$result = $this->update($user, true);
		if ($result > 0 && !empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_DELETE)) {
			$this->call_trigger('DIGIRISKELEMENT_DELETE', $user);
		}

		return $result;
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non-existing supplied categories.
	 * Existing categories are left untouched.
	 *
	 * @param  int[]|int $categories Category or categories IDs.
	 * @return float|int
	 */
	public function setCategories($categories)
	{
		return 1;
	}

	/**
	 *  Output html form to select a digirisk element.
	 *
	 * @param  string 		$selected 			Preselected type
	 * @param  string 		$htmlname 			Name of field in form
	 * @param  string 		$filter 			Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
	 * @param  string 		$showempty 			Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
	 * @param  int 			$forcecombo 		Force to use standard HTML select component without beautification
	 * @param  array 		$events 			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 * @param  int 			$outputmode 		0=HTML select string, 1=Array
	 * @param  int 			$limit 				Limit number of answers
	 * @param  string 		$morecss 			Add more css styles to the SELECT component
	 * @param  int	 		$current_element 	id of current digiriskelement
	 * @param  bool 		$multiple 			add [] in the name of element and add 'multiple' attribut
	 * @param  int 			$noroot  			Don't show root element
	 * @param  string 		$contextpage 		Context page
	 * @param  bool 		$multientitymanaged Multi entity filter
	 * @param  bool 		$hideref 			Hide ref
	 * @return string 							HTML string with
	 * @throws Exception
	 */
	public function select_digiriskelement_list($selected = '', $htmlname = 'fk_element', $filter = '', $showempty = '1', $forcecombo = 0, $events = array(), $outputmode = 0, $limit = 0, $morecss = 'minwidth100', $current_element = 0, $multiple = false, $noroot = 0, $contextpage = '', $multientitymanaged = true, $hideref  = false)
	{
		global $conf, $langs;

		$out      = '';
		$outarray = array();

		$selected = array($selected);

		// Clean $filter that may contains sql conditions so sql code
		/*if (function_exists('testSqlAndScriptInject')) {
			if (testSqlAndScriptInject($filter, 3) > 0) {
				$filter = '';
			}
		}*/

		$deleted_elements = $this->getMultiEntityTrashList();

		$sql  = "SELECT " . $this->getFieldList();
		$sql .= " FROM " . MAIN_DB_PREFIX . "digiriskdolibarr_digiriskelement as s";

		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1 && $multientitymanaged) $sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';
		else $sql                                                                        .= ' WHERE 1 = 1';

		if ($filter) $sql .= " AND (" . $filter . ")";
		$sql .= " AND s.status > 0";

		if ($current_element > 0 ) {
			$children = $this->fetchDigiriskElementFlat($current_element);
			if ( ! empty($children) && $children > 0) {
				foreach ($children as $key => $value) {
					$sql .= " AND NOT s.rowid =" . $key;
				}
			}
			$sql .= " AND NOT s.rowid =" . $current_element;
		}

		if (!empty($deleted_elements) && is_array($deleted_elements)) {
			foreach ($deleted_elements as $deleted_element) {
				$sql .= " AND NOT s.rowid =" . $deleted_element;
			}
		}

		$sql .= $this->db->order("ranks", "ASC");
		$sql .= $this->db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this) . "::select_digiriskelement_list", LOG_DEBUG);
		$resql = $this->db->query($sql);
		$num = '';
		if ($resql) {
			if ( ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, 0);
			}

			// Construct $out and $outarray
			$out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($current_element ? ' ' . $current_element : '') . ' name="' . $htmlname . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . '>' . "\n";
			$num                  = $this->db->num_rows($resql);
			$i                    = 0;

			$textifempty          = (($showempty && ! is_numeric($showempty)) ? $langs->trans($showempty) : '');

			if ($showempty) $out .= '<option value="-1">' . $textifempty . '</option>' . "\n";

			if ( ! $noroot) $out .= '<option value="0" selected>' . $langs->trans('Root') . ' : ' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</option>';

			$digiriskelementlist = $this->fetchDigiriskElementFlat(0);

			if ( ! empty($digiriskelementlist) ) {
				foreach ($digiriskelementlist as $line) {
					$depthHyphens = '';
					for ($k = 0; $k < $line['depth']; $k++) {
						$depthHyphens .= '- ';
					}
					$depth[$line['object']->id] = $depthHyphens;
				}
			}

			if ($num) {
				while ($i < $num) {
					$obj   = $this->db->fetch_object($resql);
					if ((!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) && $contextpage == 'sharedrisk') || (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS) && $contextpage == 'sharedrisksign')) {
						$label = $depth[$obj->rowid] . 'S'. $obj->entity . ' - ' . ($hideref ?  '' : $obj->ref . ' - ') . $obj->label;
					} else {
						$label = $depth[$obj->rowid] . ($hideref ?  '' : $obj->ref . ' - ') . $obj->label;
					}

					if (empty($outputmode)) {
						if (in_array($obj->rowid, $selected)) {
							$out .= '<option value="' . $obj->rowid . '" selected>' . $label . '</option>';
						} else {
							$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
						}
					} else {
						$outarray[$obj->rowid] = $label;
					}

					$i++;
					if (($i % 10) == 0) $out .= "\n";
				}
			}
			$out .= '</select>' . "\n";
		} else {
			dol_print_error($this->db);
		}

		$this->result = array('nbofdigiriskelement' => $num);

		if ($outputmode) {
			return $outarray;
		}
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
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql  = 'SELECT ';
		$sql .= ' *';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . '_extrafields as t';
		$sql .= ' WHERE wp_digi_id =' . $wp_digi_id;
		$sql .= ' AND entity =' . $conf->entity;

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i   = 0;
			$obj = new stdClass();
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$i++;
			}
			$this->db->free($resql);

			return $obj->fk_object;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Return list of deleted elements
	 *
	 * 	@return    array  Array with ids
	 * 	@throws Exception
	 */
	public function getTrashList()
	{
		global $conf;
		$objects = $this->fetchAll('',  'ranks');
		if (is_array($objects)) {
			$recurse_tree = recurse_tree($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH, 0, $objects);
			$ids          = [];

			array_walk_recursive($recurse_tree, function ($item) use (&$ids) {
				if (is_object($item)) {
					$ids[$item->id] = $item->id;
				}
			}, $ids);

			return $ids;
		} else {
			return array();
		}
	}

	/**
	 *  Return list of deleted elements for all entities
	 *
	 * 	@return    array  Array with ids
	 * 	@throws Exception
	 */
	public function getMultiEntityTrashList()
	{
		$this->ismultientitymanaged = 0;
		$objects = $this->fetchAll('',  'ranks', 0,0, array('customsql' => ' status > 0'));
		$digiriskelement_trashes = $this->fetchAll('',  'ranks', 0,0, array('customsql' => ' status = 0'));
		$this->ismultientitymanaged = 1;
		if (is_array($digiriskelement_trashes) && !empty($digiriskelement_trashes)) {
			$ids          = [];
			foreach($digiriskelement_trashes as $digiriskelement_trash) {
				$recurse_tree = recurse_tree($digiriskelement_trash->id, 0, $objects);

				array_walk_recursive($recurse_tree, function ($item) use (&$ids) {
					if (is_object($item)) {
						$ids[$item->id] = $item->id;
					}
				}, $ids);
			}
			return $ids;
		} else {
			return array();
		}
	}

	/**
	 *  Return list of parent ids for an element
	 *
	 * 	@return    array  Array with ids
	 * 	@throws Exception
	 */
	public function getBranch($parent_id)
	{
		$object = new self($this->db);
		$object->fetch($parent_id);
		$branch_ids = array($parent_id);

		while ($object->fk_parent > 0) {
			$branch_ids[] = $object->fk_parent;
			$object->fetch($object->fk_parent);
		}
		return $branch_ids;
	}

	/**
	 *  Return list of non deleted digirisk elements
	 *
	 * 	@return    array  Array with ids
	 * 	@throws Exception
	 */
	public function getActiveDigiriskElements($allEntities = 0)
	{
		global $conf;
		$object = new self($this->db);
		if ($allEntities == 1) {
			$object->ismultientitymanaged = 0;
		}
		$objects = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0'));

		$trashList = $object->getMultiEntityTrashList();
		if (!empty($trashList) && is_array($trashList)) {
			foreach($trashList as $trash_element_id) {
				unset($objects[$trash_element_id]);
			}
		}

		return $objects;
	}

	/**
	 * Return the status.
	 *
	 * @param  int    $status ID status.
	 * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto.
	 * @return string         Label of status.
	 */
	public function LibStatut(int $status, int $mode = 0): string
	{
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;

			$this->labelStatus[self::STATUS_DRAFT]          = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatus[self::STATUS_VALIDATED]      = $langs->transnoentitiesnoconv('Validated');

			$this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
		}

		$statusType = 'status' . $status;
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}
}
