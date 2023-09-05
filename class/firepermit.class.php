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
 * \file    class/firepermit.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a CRUD class file for Firepermit (Create/Read/Update/Delete).
 */

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

require_once __DIR__ . '/digiriskdocuments.class.php';
require_once __DIR__ . '/digirisksignature.class.php';
require_once __DIR__ . '/openinghours.class.php';

/**
 * Class for FirePermit.
 */
class FirePermit extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'firepermit';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiriskdolibarr_firepermit';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element_line = 'digiriskdolibarr_firepermitdet';

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
     * @var string Name of icon for firepermit. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'firepermit@digiriskdolibarr' if picto is file 'img/object_firepermit.png'.
     */
    public string $picto = 'fontawesome_fa-fire-alt_fas_#d35968';

    public const STATUS_DRAFT             = 1;
    public const STATUS_IN_PROGRESS       = 1;
    public const STATUS_VALIDATED         = 2;
    public const STATUS_VALIDATED = 2;
    public const STATUS_LOCKED            = 3;
    public const STATUS_ARCHIVED          = 4;

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
		'rowid'                => ['type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
		'ref'                  => ['type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"],
		'ref_ext'              => ['type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,],
		'entity'               => ['type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,],
		'date_creation'        => ['type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,],
		'tms'                  => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,],
		'status'               => ['type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 1, 'index' => 0,],
		'label'                => ['type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'help' => "Help text", 'showoncombobox' => '1',],
		'date_start'           => ['type' => 'datetime', 'label' => 'StartDate', 'enabled' => '1', 'position' => 90, 'notnull' => -1, 'visible' => 1,],
		'date_end'             => ['type' => 'datetime', 'label' => 'EndDate', 'enabled' => '1', 'position' => 100, 'notnull' => -1, 'visible' => 1,],
		'last_email_sent_date' => ['type' => 'datetime', 'label' => 'LastEmailSentDate', 'enabled' => '1', 'position' => 110, 'notnull' => -1, 'visible' => -2,],
		'fk_project'           => ['type' => 'integer:Project:projet/class/project.class.php', 'label' => 'Project', 'enabled' => '1', 'position' => 115, 'notnull' => 1, 'visible' => 1,],
		'fk_user_creat'        => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 120, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',],
		'fk_user_modif'        => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 130, 'notnull' => -1, 'visible' => 0,],
		'fk_preventionplan'    => ['type' => 'integer', 'label' => 'PreventionPlan', 'enabled' => '1', 'position' => 140, 'notnull' => -1, 'visible' => -2,],
    ];

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $status;
	public $label;
	public $date_start;
	public $date_end;
	public $last_email_sent_date;
	public $fk_project;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_preventionplan;

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
	 * Clone an object into another one
	 *
	 * @param User $user User that creates
	 * @param int $fromid Id of object to clone
	 * @param $options
	 * @return    mixed                New object created, <0 if KO
	 * @throws Exception
	 */
	public function createFromClone(User $user, $fromid, $options)
	{
		global $conf, $langs;
		$error = 0;

		$signatory         = new FirePermitSignature($this->db);
		$digiriskresources = new DigiriskResources($this->db);
		$openinghours      = new Openinghours($this->db);

		$refFirePermitMod    = new $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_ADDON($this->db);
		$refFirePermitDetMod = new $conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON($this->db);

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && ! empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// Load openinghours form source object
		$morewhere  = ' AND element_id = ' . $object->id;
		$morewhere .= ' AND element_type = ' . "'" . $object->element . "'";
		$morewhere .= ' AND status = 1';

		$openinghours->fetch(0, '', $morewhere);

		// Load signatory and ressources form source object
		$signatories = $signatory->fetchSignatory("", $fromid, 'firepermit');
		$resources   = $digiriskresources->fetchResourcesFromObject('', $object);

		if ( ! empty($signatories) && $signatories > 0) {
			foreach ($signatories as $arrayRole) {
				foreach ($arrayRole as $signatoryRole) {
					$signatoriesID[$signatoryRole->role] = $signatoryRole->id;
					if ($signatoryRole->role == 'FP_EXT_SOCIETY_INTERVENANTS') {
						$extintervenant_ids[] = $signatoryRole->id;
					}
				}
			}
		}

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = $refFirePermitMod->getNextValue($object);
		}
		if (property_exists($object, 'ref_ext')) {
			$object->ref_ext = 'digirisk_' . $object->ref;
		}
		if (property_exists($object, 'label')) {
			$object->label = $options['clone_label'];
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'status')) {
			$object->status = 1;
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$firepermtid                        = $object->create($user);

		if ($firepermtid > 0) {
			$digiriskresources->digirisk_dolibarr_set_resources($this->db, $user->id, 'FP_EXT_SOCIETY', 'societe', array(array_shift($resources['FP_EXT_SOCIETY'])->id), $conf->entity, 'firepermit', $firepermtid, 1);
			$digiriskresources->digirisk_dolibarr_set_resources($this->db, $user->id, 'FP_LABOUR_INSPECTOR', 'societe', array(array_shift($resources['FP_LABOUR_INSPECTOR'])->id), $conf->entity, 'firepermit', $firepermtid, 1);
			$digiriskresources->digirisk_dolibarr_set_resources($this->db, $user->id, 'FP_LABOUR_INSPECTOR_ASSIGNED', 'socpeople', array(array_shift($resources['FP_LABOUR_INSPECTOR_ASSIGNED'])->id), $conf->entity, 'firepermit', $firepermtid, 1);
			if (!empty($signatoriesID)) {
				$signatory->createFromClone($user, $signatoriesID['FP_MAITRE_OEUVRE'], $firepermtid);
				$signatory->createFromClone($user, $signatoriesID['FP_EXT_SOCIETY_RESPONSIBLE'], $firepermtid);
			}

			if ( ! empty($options['schedule'])) {
				if ( ! empty($openinghours)) {
					$openinghours->element_id = $firepermtid;
					$openinghours->create($user);
				}
			}

			if ( ! empty($options['attendants'])) {
				if ( ! empty($extintervenant_ids) && $extintervenant_ids > 0) {
					foreach ($extintervenant_ids as $extintervenant_id) {
						$signatory->createFromClone($user, $extintervenant_id, $firepermtid);
					}
				}
			}

			if ( ! empty($options['firepermit_risk'])) {
				$num = (!empty($object->lines) ? count($object->lines) : 0);
				for ($i = 0; $i < $num; $i++) {
					$line                = $object->lines[$i];
					if (property_exists($line, 'ref')) {
						$line->ref = $refFirePermitDetMod->getNextValue($line);
					}
					$line->category      = empty($line->category) ? 0 : $line->category;
					$line->fk_firepermit = $firepermtid;

					$result = $line->insert($user, 1);
					if ($result < 0) {
						$this->error = $this->db->lasterror();
						$this->db->rollback();
						return -1;
					}
				}
			}
		} else {
			$error++;
			$this->error  = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if ( ! $error) {
			$this->db->commit();
			return $firepermtid;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set in progress status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setInProgress($user, $notrigger = 0)
	{
		global $conf;

		$signatory = new PreventionPlanSignature($this->db);
		$signatory->deleteSignatoriesSignatures($this->id, 'firepermit');
		return $this->setStatusCommon($user, self::STATUS_IN_PROGRESS, $notrigger || !$conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_INPROGRESS, 'FIREPERMIT_INPROGRESS');
	}

	/**
	 *	Set pending signature status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setPendingSignature($user, $notrigger = 0)
	{
		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'FIREPERMIT_PENDINGSIGNATURE');
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
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load("digiriskdolibarr@digiriskdolibarr");

			$this->labelStatus[self::STATUS_DELETE]           = $langs->trans('Deleted');
			$this->labelStatus[self::STATUS_IN_PROGRESS]       = $langs->trans('InProgress');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('ValidatePendingSignature');
			$this->labelStatus[self::STATUS_LOCKED]            = $langs->trans('Locked');
			$this->labelStatus[self::STATUS_ARCHIVED]          = $langs->trans('Archived');
		}

		$statusType                                                = 'status' . $status;
		if ($status == self::STATUS_VALIDATED) $statusType = 'status3';
		if ($status == self::STATUS_LOCKED) $statusType            = 'status8';
		if ($status == self::STATUS_ARCHIVED) $statusType          = 'status8';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}
}

/**
 *	Class to manage invoice lines.
 *  Saved into database table llx_firepermitdet
 */
class FirePermitLine extends CommonObjectLine
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error string
	 */
	public $error;

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'firepermitdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'digiriskdolibarr_firepermitdet';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'           => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'       => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'           => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'category'      => array('type' => 'integer', 'label' => 'INRSRisk', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => -1,),
		'description'   => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => -1,),
		'used_equipment' => array('type' => 'text', 'label' => 'UsedEquipment', 'enabled' => '1', 'position' => 80, 'notnull' => -1, 'visible' => -1,),
		'fk_firepermit' => array('type' => 'integer', 'label' => 'FkFirePermit', 'enabled' => '1', 'position' => 90, 'notnull' => 1, 'visible' => 0,),
		'fk_element'    => array('type' => 'integer', 'label' => 'FkElement', 'enabled' => '1', 'position' => 100, 'notnull' => 1, 'visible' => 0,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $category;
	public $description;
	public $used_equipment;
	public $fk_firepermit;
	public $fk_element;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']        = 0;
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

		$sql  = 'SELECT t.rowid, t.ref, t.date_creation, t.description, t.category, t.used_equipment, t.fk_firepermit, t.fk_element ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_firepermitdet as t';
		$sql .= ' WHERE t.rowid = ' . $rowid;
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';

		$result = $db->query($sql);
		if ($result) {
			$objp = $db->fetch_object($result);

			$this->id            = $objp->rowid;
			$this->ref           = $objp->ref;
			$this->date_creation = $objp->date_creation;
			$this->description   = $objp->description;
			$this->category      = $objp->category;
			$this->used_equipment = $objp->used_equipment;
			$this->fk_firepermit = $objp->fk_firepermit;
			$this->fk_element    = $objp->fk_element;

			$db->free($result);

			return $this->id;
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}

	/**
	 * Load firepermit line line from database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param array $filter filter array
	 * @param string $filtermode filter mode (AND or OR)
	 * @param int $parent_id
	 * @return array|int
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $parent_id = 0)
	{
		global $db;
		$sql  = 'SELECT t.rowid, t.ref, t.date_creation, t.description, t.category, t.used_equipment, t.fk_element';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_firepermitdet as t';
		if ($parent_id > 0) {
			$sql .= ' WHERE t.fk_firepermit = ' . $parent_id;
		} else {
			$sql .= ' WHERE 1=1';
		}
		$sql .= ' AND entity IN (' . getEntity($this->table_element) . ')';


		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			$records = array();
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $db->fetch_object($result);

				$record = new self($db);

				$record->id            = $obj->rowid;
				$record->ref           = $obj->ref;
				$record->date_creation = $obj->date_creation;
				$record->description   = $obj->description;
				$record->category      = $obj->category;
				$record->used_equipment = $obj->used_equipment;
				$record->fk_firepermit = $obj->fk_firepermit;
				$record->fk_element    = $obj->fk_element;

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
		global $db, $user, $conf;

		// Clean parameters
		$this->description = trim($this->description);

		$db->begin();
		$now = dol_now();

		// Insertion dans base de la ligne
		$sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_firepermitdet';
		$sql .= ' (ref, entity, date_creation, description, category, used_equipment, fk_firepermit, fk_element';
		$sql .= ')';
		$sql .= " VALUES (";
		$sql .= "'" . $db->escape($this->ref) . "'" . ", ";
		$sql .= $this->entity . ", ";
		$sql .= "'" . $db->escape($db->idate($now)) . "'" . ", ";
		$sql .= "'" . $db->escape($this->description) . "'" . ", ";
		$sql .= $this->category . ", ";
		$sql .= "'" . $db->escape($this->used_equipment) . "'" . ", ";
		$sql .= $this->fk_firepermit . ", ";
		$sql .= $this->fk_element;

		$sql .= ')';

		dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$this->id    = $db->last_insert_id(MAIN_DB_PREFIX . 'firepermitdet');
			$this->rowid = $this->id; // For backward compatibility

			$db->commit();
			// Triggers
			if ( ! $notrigger) {
				// Call triggers
				if (!empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_CREATE)) $this->call_trigger(strtoupper(get_class($this)) . '_CREATE', $user);
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
	 * @param string $user User object
	 * @param bool $notrigger Disable triggers
	 * @return        int                    <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function update($user = '', $notrigger = false)
	{
		global $user, $db, $conf;

		// Clean parameters
		$this->description = trim($this->description);

		$db->begin();
		// Mise a jour ligne en base
		$sql  = "UPDATE " . MAIN_DB_PREFIX . "digiriskdolibarr_firepermitdet SET";
		$sql .= " ref='" . $db->escape($this->ref) . "',";
		$sql .= " description='" . $db->escape($this->description) . "',";
		$sql .= " category=" . $db->escape($this->category) . ",";
		$sql .= " used_equipment='" . $db->escape($this->used_equipment) . "'" . ",";
		$sql .= " fk_firepermit=" . $db->escape($this->fk_firepermit) . ",";
		$sql .= " fk_element=" . $db->escape($this->fk_element);
		$sql .= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $db->query($sql);

		if ($resql) {
			$db->commit();
			// Triggers
			if ( ! $notrigger) {
				// Call triggers
				if (!empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_MODIFY)) $this->call_trigger(strtoupper(get_class($this)) . '_MODIFY', $user);
				// End call triggers
			}
			return $this->id;
		} else {
			$this->error = $db->error();
			$db->rollback();
			return -2;
		}
	}

	/**
	 *    Delete line in database
	 *
	 * @param User $user
	 * @param bool $notrigger
	 * @return        int                   <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function delete(User $user, $notrigger = false)
	{
		global $user, $db, $conf;

		$db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "digiriskdolibarr_firepermitdet WHERE rowid = " . $this->id;
		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
		if ($db->query($sql)) {
			$db->commit();
			// Triggers
			if ( ! $notrigger) {
				// Call trigger
				if (!empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_DELETE)) $this->call_trigger(strtoupper(get_class($this)) . '_DELETE', $user);
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
 * Class FirePermitSignature
 */
class FirePermitSignature extends DigiriskSignature
{
	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $object_type = 'firepermit';

	/**
	 * @var array Context element object
	 */
	public $context = array();

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
	 * Clone an object into another one
	 *
	 * @param User $user User that creates
	 * @param int $fromid Id of object to clone
	 * @param $firepermitid
	 * @return    mixed                New object created, <0 if KO
	 * @throws Exception
	 */
	public function createFromClone(User $user, $fromid, $firepermitid)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);
		unset($object->signature);
		unset($object->signature_date);
		unset($object->last_email_sent_date);

		// Clear fields
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'fk_object')) {
			$object->fk_object = $firepermitid;
		}
		if (property_exists($object, 'status')) {
			$object->status = 1;
		}
		if (property_exists($object, 'signature_url')) {
			$object->signature_url = generate_random_id(16);
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result                             = $object->createCommon($user);
		unset($object->context['createfromclone']);

		// End
		if ( ! $error) {
			$this->db->commit();
			return $result;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}
