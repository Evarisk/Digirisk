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
require_once __DIR__ . '/../../saturne/class/saturneschedules.class.php';

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
	public $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for firepermit. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'firepermit@digiriskdolibarr' if picto is file 'img/object_firepermit.png'.
     */
    public string $picto = 'fontawesome_fa-fire-alt_fas_#d35968';

    const STATUS_DELETED   = -1;
	const STATUS_DRAFT     = 1;
	const STATUS_VALIDATED = 2;
	const STATUS_LOCKED    = 3;
	const STATUS_ARCHIVED  = 4;

	/**
	 * @var FirePermitLine[]     Array of subtable lines
	 */
	public $lines = [];

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'                => ['type' => 'integer',      'label' => 'TechnicalID',       'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'                  => ['type' => 'varchar(128)', 'label' => 'Ref',               'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' =>1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'              => ['type' => 'varchar(128)', 'label' => 'RefExt',            'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'               => ['type' => 'integer',      'label' => 'Entity',            'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'        => ['type' => 'datetime',     'label' => 'DateCreation',      'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
        'tms'                  => ['type' => 'timestamp',    'label' => 'DateModification',  'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'status'               => ['type' => 'smallint',     'label' => 'Status',            'enabled' => 1, 'position' => 180, 'notnull' => 1, 'visible' => 2, 'default' => 0, 'index' => 1, 'arrayofkeyval' => [1 => 'InProgress', 2 => 'ValidatePendingSignature', 3 => 'Locked', 4 => 'Archived']],
        'label'                => ['type' => 'varchar(255)', 'label' => 'Label',             'enabled' => 1, 'position' => 60,  'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'showoncombobox' => 2, 'validate' => 1, 'autofocusoncreate' => 1],
        'date_start'           => ['type' => 'date',         'label' => 'DateStart',         'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => 1],
        'date_end'             => ['type' => 'date',         'label' => 'DateEnd',           'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 1],
        'fk_user_creat'        => ['type' => 'integer:User:user/class/user.class.php',                                   'label' => 'UserAuthor',     'picto' => 'user',    'enabled' => 1,                         'position' => 140, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'        => ['type' => 'integer:User:user/class/user.class.php',                                   'label' => 'UserModif',      'picto' => 'user',    'enabled' => 1,                         'position' => 150, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_project'           => ['type' => 'integer:Project:projet/class/project.class.php:1',                         'label' => 'Project',        'picto' => 'project', 'enabled' => '$conf->project->enabled', 'position' => 85,  'notnull' => 1, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'projet.rowid'],
        'fk_preventionplan'    => ['type' => 'integer:PreventionPlan:digiriskdolibarr/class/preventionplan.class.php:1', 'label' => 'PreventionPlan', 'picto' => 'project', 'enabled' => 1,                         'position' => 160, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'preventionplan.rowid'],
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
	public $fk_project;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_preventionplan;

    /**
     * @var string Name of subtable line
     */
    public $table_element_line = 'digiriskdolibarr_firepermitdet';

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
     * Clone an object into another one.
     *
     * @param  User      $user    User that creates
     * @param  int       $fromID  ID of object to clone
     * @param  array     $options Options array
     * @return int                New object created, <0 if KO
     * @throws Exception
     */
    public function createFromClone(User $user, int $fromID, array $options): int
    {
        global $conf, $moduleNameLowerCase;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $object            = new self($this->db);
        $signatory         = new SaturneSignature($this->db, $this->module, $this->element);
        $digiriskResources = new DigiriskResources($this->db);

        $this->db->begin();

        // Load source object
        $object->fetch($fromID);
        $firepermitdets = $object->lines;

        // Load signatory and ressources form source object
        $signatories = $signatory->fetchSignatory('', $fromID, $object->element);
        $resources   = $digiriskResources->fetchResourcesFromObject('', $object);

        if (!empty($signatories) && $signatories > 0) {
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
            'digiriskelement/firepermit'    => $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_ADDON,
            'digiriskelement/firepermitdet' => $conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON,
        ];

        list($refFirePermitMod, $refFirePermitDetMod) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

        // Reset some properties
        unset($object->id);
        unset($object->fk_user_creat);
        unset($object->import_key);

        // Clear fields
        $object->ref           = $refFirePermitMod->getNextValue($object);
        $object->label         = $options['clone_label'];
        $object->date_creation = dol_now();
        $object->status        = self::STATUS_DRAFT;

        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $firePermitID                       = $object->create($user);

        if ($firePermitID > 0) {
            $digiriskResources->setDigiriskResources($this->db, $user->id, 'ExtSociety', 'societe', [array_shift($resources['ExtSociety'])->id], $conf->entity, 'firepermit', $firePermitID, 1);
            $digiriskResources->setDigiriskResources($this->db, $user->id, 'LabourInspector', 'societe', [array_shift($resources['LabourInspector'])->id], $conf->entity, 'firepermit', $firePermitID, 1);
            $digiriskResources->setDigiriskResources($this->db, $user->id, 'LabourInspectorAssigned', 'socpeople', [array_shift($resources['LabourInspectorAssigned'])->id], $conf->entity, 'firepermit', $firePermitID, 1);
            if (!empty($signatoriesID)) {
                $signatory->createFromClone($user, $signatoriesID['MasterWorker'], $firePermitID);
                $signatory->createFromClone($user, $signatoriesID['ExtSocietyResponsible'], $firePermitID);
            }

            if (!empty($options['schedule'])) {
                $saturneSchedules = new SaturneSchedules($this->db);

                // Load openinghours form source object
                $moreWhere  = ' AND element_id = ' . $fromID;
                $moreWhere .= ' AND element_type = ' . "'" . $object->element . "'";
                $moreWhere .= ' AND status = 1';

                $saturneSchedules->fetch(0, '', $moreWhere);
                if (!empty($saturneSchedules)) {
                    $saturneSchedules->element_type = 'firepermit';
                    $saturneSchedules->element_id   = $firePermitID;
                    $saturneSchedules->create($user);
                }
            }

            if (!empty($options['attendants'])) {
                if (!empty($extIntervenantsIds) && $extIntervenantsIds > 0) {
                    foreach ($extIntervenantsIds as $extIntervenantID) {
                        $signatory->createFromClone($user, $extIntervenantID, $firePermitID);
                    }
                }
            }

            if (!empty($options['firepermit_risk'])) {
                if (is_array($firepermitdets) && !empty($firepermitdets)) {
                    foreach ($firepermitdets as $line) {
                        $line->ref           = $refFirePermitDetMod->getNextValue($line);
                        $line->fk_firepermit = $firePermitID;
                        $line->create($user, 1);
                    }
                }
            }

            if (!empty($options['categories'])) {
                $cat        = new Categorie($this->db);
                $categories = $cat->containing($fromID, 'firepermit');
                if (is_array($categories) && !empty($categories)) {
                    $categoryIds = [];
                    foreach ($categories as $cat) {
                        $categoryIds[] = $cat->id;
                    }
                    $object->setCategories($categoryIds);
                }
            }
        } else {
            $this->error  = $object->error;
            $this->errors = $object->errors;
        }

        // End
        if (!$this->error) {
            $this->db->commit();
            return $firePermitID;
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

            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatus[self::STATUS_DRAFT]     = $langs->trans('InProgress');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('ValidatePendingSignature');
			$this->labelStatus[self::STATUS_LOCKED]    = $langs->trans('Locked');
			$this->labelStatus[self::STATUS_ARCHIVED]  = $langs->trans('Archived');

            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('InProgress');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingSignature');
            $this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
		}

		$statusType                                        = 'status' . $status;
        if ($status == self::STATUS_DELETED) $statusType   = 'status9';
        if ($status == self::STATUS_VALIDATED) $statusType = 'status3';
		if ($status == self::STATUS_LOCKED) $statusType    = 'status8';
		if ($status == self::STATUS_ARCHIVED) $statusType  = 'status8';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

    /**
     * Write information of trigger description
     *
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $langs;

        require_once __DIR__ . '/digiriskresources.class.php';
        require_once __DIR__ . '/preventionplan.class.php';
        require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

        $digiriskResources = new DigiriskResources($this->db);
        $saturneSignature  = new SaturneSignature($this->db, $object->module, $object->element);
        $preventionplan    = new PreventionPlan($this->db);
        $societies         = $digiriskResources->fetchResourcesFromObject('', $object);
        $signatories       = $saturneSignature->fetchSignatories($object->id, $object->element);
        $preventionplan->fetch($object->fk_preventionplan);

        $ret  = parent::getTriggerDescription($object);

        $ret .= (dol_strlen($object->date_start) > 0 ? $langs->transnoentities('StartDate') . ' : ' . dol_print_date($object->date_start, 'dayhoursec') . '<br>' : '');
        $ret .= (dol_strlen($object->date_end) > 0 ? $langs->transnoentities('EndDate') . ' : ' . dol_print_date($object->date_end, 'dayhoursec') . '<br>' : '');
        if (is_array($signatories) && !empty($signatories)) {
            foreach($signatories as $signatory) {
                $ret .= $langs->transnoentities($signatory->role) . ' : ' . $signatory->firstname . ' ' . $signatory->lastname . '<br>';
            }
        }
        if (is_array($societies) && !empty($societies)) {
            foreach ($societies as $societename => $key) {
                $ret .= $langs->transnoentities($societename) . ' : ';
                foreach ($key as $societe) {
                    if ($societename == 'LabourInspectorAssigned') {
                        $ret .= $societe->firstname . ' ' . $societe->lastname . '<br>';
                    } else {
                        $ret .= $societe->name . '<br>';
                    }
                    if ($societename == 'ExtSociety') {
                        $ret .= (dol_strlen($societe->address) > 0 ? $langs->transnoentities('Address') . ' : ' . $societe->address . '<br>' : '');
                        $ret .= (dol_strlen($societe->idprof2) > 0 ? $langs->transnoentities('SIRET') . ' : ' . $societe->idprof2 . '<br>' : '');
                    }
                }
            }
        }
        $ret .= $langs->transnoentities('PreventionPlan') . ' : ' . $preventionplan->ref . (!empty($preventionplan->label) ? ' ' . $preventionplan->label : '') . '<br>';

        return $ret;
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

    const STATUS_DELETED   = -1;
    const STATUS_VALIDATED = 1;

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = [
        'rowid'          => ['type' => 'integer',      'label' => 'TechnicalID',       'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'            => ['type' => 'varchar(128)', 'label' => 'Ref',               'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' =>1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'        => ['type' => 'varchar(128)', 'label' => 'RefExt',            'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'         => ['type' => 'integer',      'label' => 'Entity',            'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'  => ['type' => 'datetime',     'label' => 'DateCreation',      'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
        'tms'            => ['type' => 'timestamp',    'label' => 'DateModification',  'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'status'         => ['type' => 'smallint',     'label' => 'Status',            'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => 0, 'default' => 0, 'index' => 1],
        'category'       => ['type' => 'integer',      'label' => 'INRSRisk',          'enabled' => 1, 'position' => 60, 'notnull' => -1, 'visible' => -1,],
        'description'    => ['type' => 'text',         'label' => 'Description',       'enabled' => 1, 'position' => 70, 'notnull' => -1, 'visible' => -1,],
        'used_equipment' => ['type' => 'text',         'label' => 'UsedEquipment',     'enabled' => 1, 'position' => 80, 'notnull' => -1, 'visible' => -1,],
        'fk_firepermit'  => ['type' => 'integer',      'label' => 'FkFirePermit',      'enabled' => 1, 'position' => 90, 'notnull' => 1, 'visible' => 0,],
        'fk_element'     => ['type' => 'integer',      'label' => 'FkElement',         'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 0,],
    ];

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
    public $status;
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

    /**
     * Write information of trigger description
     *
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $langs;

        require_once __DIR__ . '/digiriskelement.class.php';
        require_once __DIR__ . '/riskanalysis/risk.class.php';

        $ret = parent::getTriggerDescription($object);

        $risk            = new Risk($this->db);
        $digiriskelement = new DigiriskElement($this->db);
        $digiriskelement->fetch($object->fk_element);

        $ret .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
        $ret .= $langs->trans('INRSRisk') . ' : ' .  $risk->getFirePermitDangerCategoryName($object) . '<br>';
        $ret .= $langs->trans('UsedEquipment') . ' : ' . (!empty($object->used_equipment) ? $object->used_equipment : 'N/A') . '<br>';

        return $ret;
    }
}
