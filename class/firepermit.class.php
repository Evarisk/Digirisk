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

// Load DigiriskDolibarr libraries.

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
    public string $picto = 'fontawesome_fa-fire-alt_fas_#d35968';

	const STATUS_DELETED   = 0;
	const STATUS_DRAFT     = 1;
	const STATUS_VALIDATED = 2;
	const STATUS_LOCKED    = 3;
	const STATUS_ARCHIVED  = 4;

	/**
	 * @var FirePermitLine[]     Array of subtable lines
	 */
	public $lines = [];

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public array $fields = [
		'rowid'                => ['type' => 'integer',                                        'label' => 'TechnicalID',       'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
		'ref'                  => ['type' => 'varchar(128)',                                   'label' => 'Ref',               'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"],
		'ref_ext'              => ['type' => 'varchar(128)',                                   'label' => 'RefExt',            'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,],
		'entity'               => ['type' => 'integer',                                        'label' => 'Entity',            'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,],
		'date_creation'        => ['type' => 'datetime',                                       'label' => 'DateCreation',      'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,],
		'tms'                  => ['type' => 'timestamp',                                      'label' => 'DateModification',  'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,],
		'status'               => ['type' => 'smallint',                                       'label' => 'Status',            'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 1, 'index' => 0,],
		'label'                => ['type' => 'varchar(255)',                                   'label' => 'Label',             'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'help' => "Help text", 'showoncombobox' => '1',],
		'date_start'           => ['type' => 'datetime',                                       'label' => 'StartDate',         'enabled' => '1', 'position' => 90, 'notnull' => -1, 'visible' => 1,],
		'date_end'             => ['type' => 'datetime',                                       'label' => 'EndDate',           'enabled' => '1', 'position' => 100, 'notnull' => -1, 'visible' => 1,],
		'last_email_sent_date' => ['type' => 'datetime',                                       'label' => 'LastEmailSentDate', 'enabled' => '1', 'position' => 110, 'notnull' => -1, 'visible' => -2,],
		'fk_project'           => ['type' => 'integer:Project:projet/class/project.class.php', 'label' => 'Project',           'enabled' => '1', 'position' => 115, 'notnull' => 1, 'visible' => 1,],
		'fk_user_creat'        => ['type' => 'integer:User:user/class/user.class.php',         'label' => 'UserAuthor',        'enabled' => '1', 'position' => 120, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',],
		'fk_user_modif'        => ['type' => 'integer:User:user/class/user.class.php',         'label' => 'UserModif',         'enabled' => '1', 'position' => 130, 'notnull' => -1, 'visible' => 0,],
		'fk_preventionplan'    => ['type' => 'integer',                                        'label' => 'PreventionPlan',    'enabled' => '1', 'position' => 140, 'notnull' => -1, 'visible' => -2,],
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
	public $fkPreventionPlan;

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
		global $conf, $moduleNameLowerCase;
		$error = 0;

		$signatory         = new SaturneSignature($this->db, $this->module, $this->element);
		$digiriskresources = new DigiriskResources($this->db);
		$saturneSchedules      = new SaturneSchedules($this->db);

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

		$saturneSchedules->fetch(0, '', $morewhere);

		// Load signatory and ressources form source object
		$signatories = $signatory->fetchSignatory("", $fromid, 'firepermit');
		$resources   = $digiriskresources->fetchResourcesFromObject('', $object);

		if ( ! empty($signatories) && $signatories > 0) {
			foreach ($signatories as $arrayRole) {
				foreach ($arrayRole as $signatoryRole) {
					$signatoriesID[$signatoryRole->role] = $signatoryRole->id;
					if ($signatoryRole->role == 'ExtSocietyAttendant') {
						$extIntervenantsIds[] = $signatoryRole->id;
					}
				}
			}
		}

		// Load numbering modules
		$numberingModules = [
			'digiriskelement/' . $objectLine->element => $conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON,
		];

		list($refFirePermitDetMod) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = $refFirePermitDetMod->getNextValue($object);
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
			$digiriskresources->setDigiriskResources($this->db, $user->id, 'ExtSociety', 'societe', array(array_shift($resources['ExtSociety'])->id), $conf->entity, 'firepermit', $firepermtid, 1);
			$digiriskresources->setDigiriskResources($this->db, $user->id, 'LabourInspector', 'societe', array(array_shift($resources['LabourInspector'])->id), $conf->entity, 'firepermit', $firepermtid, 1);
			$digiriskresources->setDigiriskResources($this->db, $user->id, 'LabourInspectorAssigned', 'socpeople', array(array_shift($resources['LabourInspectorAssigned'])->id), $conf->entity, 'firepermit', $firepermtid, 1);
			if (!empty($signatoriesID)) {
				$signatory->createFromClone($user, $signatoriesID['MasterWorker'], $firepermtid);
				$signatory->createFromClone($user, $signatoriesID['ExtSocietyResponsible'], $firepermtid);
			}

			if ( ! empty($options['schedule'])) {
				if ( ! empty($saturneSchedules)) {
					$saturneSchedules->element_id = $firepermtid;
					$saturneSchedules->create($user);
				}
			}

			if ( ! empty($options['attendants'])) {
				if ( ! empty($extIntervenantsIds) && $extIntervenantsIds > 0) {
					foreach ($extIntervenantsIds as $extintervenant_id) {
						$signatory->createFromClone($user, $extintervenant_id, $firepermtid);
					}
				}
			}

			if ( ! empty($options['firepermit_risk'])) {
				$num = (!empty($object->lines) ? count($object->lines) : 0);
				for ($i = 0; $i < $num; $i++) {
					$line                = $object->lines[$i];
					if (property_exists($line, 'ref')) {
						$line->ref = $line->getNextNumRef();
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

		$signatory = new SaturneSignature($this->db, $this->module, $this->element);
		$signatory->deleteSignatoriesSignatures($this->id, $this->element);
		return parent::setDraft($user, $notrigger);
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
	public function LibStatut($status, $mode = 0): string
	{
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load("digiriskdolibarr@digiriskdolibarr");

			$this->labelStatus[self::STATUS_DRAFT]     = $langs->trans('InProgress');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('ValidatePendingSignature');
			$this->labelStatus[self::STATUS_LOCKED]    = $langs->trans('Locked');
			$this->labelStatus[self::STATUS_ARCHIVED]  = $langs->trans('Archived');
		}

		$statusType                                        = 'status' . $status;
		if ($status == self::STATUS_VALIDATED) $statusType = 'status3';
		if ($status == self::STATUS_LOCKED) $statusType    = 'status8';
		if ($status == self::STATUS_ARCHIVED) $statusType  = 'status8';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}
}

/**
 *	Class to manage invoice lines.
 *  Saved into database table llx_firepermitdet
 */
class FirePermitLine extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

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
		'rowid'             => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'               => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'           => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'            => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'     => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'               => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'category'          => array('type' => 'integer', 'label' => 'INRSRisk', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => -1,),
		'description'       => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => -1,),
		'used_equipment'    => array('type' => 'text', 'label' => 'UsedEquipment', 'enabled' => '1', 'position' => 80, 'notnull' => -1, 'visible' => -1,),
		'fk_firepermit' => array('type' => 'integer', 'label' => 'FkPreventionPlan', 'enabled' => '1', 'position' => 90, 'notnull' => 1, 'visible' => 0,),
		'fk_element'        => array('type' => 'integer', 'label' => 'FkElement', 'enabled' => '1', 'position' => 100, 'notnull' => 1, 'visible' => 0,),
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
		parent::__construct($db, $this->module, $this->element);
	}
}
