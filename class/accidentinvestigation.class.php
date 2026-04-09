<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    class/accidentinvestigation.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a CRUD class file for AccidentInvestigation
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';

/**
* Class for AccidentInvestigation
*/
class AccidentInvestigation extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object
     */
    public $element = 'accidentinvestigation';

    /**
     * @var string Name of table without prefix where object is stored
     * This is also the key used for extrafields management
     */
    public $table_element = 'digiriskdolibarr_accident_investigation';

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string Name of icon for accidentinvestigation
     * Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size')
     * or 'accidentinvestigation@digiriskdolibarr' if picto is file 'img/object_accidentinvestigation.png'
     */
    public string $picto = 'fontawesome_fa-search_fas_#d35968';

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
     * 'size' limit the length of a fields
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var
     * It may be modified by constructor
     */
    public $fields = [
        'rowid'                 => ['type' => 'integer',      'label' => 'TechnicalID',            'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => "Id"],
        'ref'                   => ['type' => 'varchar(128)', 'label' => 'Ref',                    'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'comment' => "Reference of object"],
        'ref_ext'               => ['type' => 'varchar(128)', 'label' => 'RefExt',                 'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0,],
        'entity'                => ['type' => 'integer',      'label' => 'Entity',                 'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0,],
        'date_creation'         => ['type' => 'datetime',     'label' => 'DateCreation',           'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2,],
        'tms'                   => ['type' => 'timestamp',    'label' => 'DateModification',       'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0,],
        'import_key'            => ['type' => 'varchar(14)',  'label' => 'ImportId',               'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'                => ['type' => 'smallint',     'label' => 'Status',                 'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 2, 'noteditable' => 1, 'default' => 0, 'index' => 0, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'Validated', 2 => 'Locked', 3 => 'Archived']],
        'seniority_in_position' => ['type' => 'varchar(255)', 'label' => 'SeniorityInPosition',    'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 1, 'css' => 'maxwidth200'],
        'date_start'            => ['type' => 'datetime',     'label' => 'StartDateInvestigate',   'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 1,],
        'date_end'              => ['type' => 'datetime',     'label' => 'EndDateInvestigate',     'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 1,],
        'note_public'           => ['type' => 'html',         'label' => 'NotePublic',             'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => -1,],
        'note_private'          => ['type' => 'html',         'label' => 'NotePrivate',            'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0,],
        'victim_skills'         => ['type' => 'html',         'label' => 'VictimSkills',           'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => -1,],
        'collective_equipment'  => ['type' => 'html',         'label' => 'CollectiveEquipment',    'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => -1,],
        'individual_equipment'  => ['type' => 'html',         'label' => 'IndividualEquipment',    'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => -1,],
        'circumstances'         => ['type' => 'html',         'label' => 'Circumstances',          'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => -1,],
        'causality_tree'        => ['type' => 'text',         'label' => 'CausalityTree',          'enabled' => 1, 'position' => 170, 'notnull' => 0, 'visible' => 0,],
        'fk_accident'           => ['type' => 'integer:Accident:digiriskdolibarr/class/accident/accident.class.php', 'label' => 'Accident', 'picto' => 'fontawesome_fa-user-injured_fas' ,'enabled' => 1, 'position' => 11, 'notnull' => 1, 'visible' => 1, 'foreignkey' => 'digiriskdolibarr_accident.rowid', 'css' => 'maxwidth300'],
        'fk_task'               => ['type' => 'integer:Task:projet/class/task.class.php',       'label' => 'Task',       'picto' => 'task',    'enabled' => 1, 'position' => 12,  'notnull' => 0, 'visible' => 5,  'noteditable' => 1, 'default' => null, 'foreignkey' => 'projet_task.rowid', 'help' => 'TaskWillBeCreatedAfterValidation'],
        'fk_project'            => ['type' => 'integer:Project:projet/class/project.class.php', 'label' => 'Project',    'picto' => 'project', 'enabled' => 1, 'position' => 13,  'notnull' => 0, 'visible' => 0],
        'fk_user_creat'         => ['type' => 'integer:User:user/class/user.class.php',         'label' => 'UserAuthor', 'picto' => 'user',    'enabled' => 1, 'position' => 190, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'         => ['type' => 'integer:User:user/class/user.class.php',         'label' => 'UserModif',  'picto' => 'user',    'enabled' => 1, 'position' => 200, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
    ];

    /**
     * @var int|string Seniority at post
     */
    public $seniority_in_position;

    /**
     * @var int|string Date investigation start
     */
    public $date_start;

    /**
     * @var int|string Date investigation end
     */
    public $date_end;

    /**
     * @var string|null Victim skills
     */
    public ?string $victim_skills = '';

    /**
     * @var string|null Collective Equipment
     */
    public ?string $collective_equipment = '';

    /**
     * @var string|null Individual Equipment
     */
    public ?string $individual_equipment = '';

    /**
     * @var string|null Circumstances
     */
    public ?string $circumstances = '';

    /**
     * @var string|null Causality tree
     */
    public ?string $causality_tree = '';

    /**
     * @var int|null Task ID
     */
    public ?int $fk_task = null;

    /**
     * @var int Accident ID
     */
    public int $fk_accident = 0;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Create object into database
     *
     * @param  User        $user      User that creates
     * @param  int<0,1>    $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,max>            Return integer 0 < if KO, ID of created object if OK
     * @throws Exception
     */
    public function create(User $user, int $noTrigger = 0): int
    {
        $this->fk_project = getDolGlobalInt('DIGIRISKDOLIBARR_ACCIDENT_PROJECT');

        $result = parent::create($user, $noTrigger);
        if ($result > 0 && $this->context != 'createfromclone') {
            $signatory = new SaturneSignature($this->db, $this->module, $this->element);

            $signatory->setSignatory($result, $this->element, 'user', [$user->id], 'Investigator', 1);
        }

        return $result;
    }

    /**
     * Set draft status
     *
     * @param  User      $user      Object user that modify
     * @param  int<0,1>  $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,1>            Return integer 0 < if KO, > 0 if OK
     */
    public function setDraft(User $user, int $noTrigger = 0): int
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}
		require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

		$signatory = new SaturneSignature($this->db);
		$signatory->deleteSignatoriesSignatures($this->id, $this->element);

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $noTrigger, 'ACCIDENTINVESTIGATION_UNVALIDATE');
	}

    /**
     * Write information of trigger description
     *
     * @return string Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(): string
    {
        require_once __DIR__ . '/accident.class.php';
        require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';

        global $langs;

        $accident = new Accident($this->db);
        $accident->fetch($this->fk_accident);
        if ($this->fk_task > 0) {
            $task = new SaturneTask($this->db);
            $task->fetch($this->fk_task);
        }

        $ret  = parent::getTriggerDescription();
        $ret .= $langs->transnoentities('Accident') . ' : ' . $accident->ref . ' - ' . $accident->label . '<br>';
        $ret .= ($this->fk_task > 0 ? $langs->transnoentities('Task') . ' : ' . $task->ref . ' - ' . $task->label . '<br>': '');
        $ret .= (dol_strlen($this->seniority_in_position) > 0 ? $langs->transnoentities('SeniorityInPosition') . ' : ' . $this->seniority_in_position . '<br>' : '');
        $ret .= (dol_strlen($this->victim_skills) > 0 ? $langs->transnoentities('VictimSkills') . ' : ' . $this->victim_skills . '<br>' : '');
        $ret .= (dol_strlen($this->collective_equipment) > 0 ? $langs->transnoentities('CollectiveEquipment') . ' : ' . $this->collective_equipment . '<br>' : '');
        $ret .= (dol_strlen($this->individual_equipment) > 0 ? $langs->transnoentities('IndividualEquipment') . ' : ' . $this->individual_equipment . '<br>' : '');
        $ret .= (dol_strlen($this->circumstances) > 0 ? $langs->transnoentities('Circumstances') . ' : ' . $this->circumstances . '<br>' : '');
        $ret .= (dol_strlen($this->causality_tree) > 0 ? $langs->transnoentities('CausalityTree') . ' : ' . $this->causality_tree . '<br>' : '');

        return $ret;
    }

	/**
	 * Clone an object into another one.
	 *
	 * @param  User      $user    User that creates
	 * @param  int       $fromID  ID of object to clone.
	 * @return int                New object created, <0 if KO.
	 * @throws Exception
	 */
	public function createFromClone(User $user, int $fromID): int
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$object = new self($this->db);
		$this->db->begin();

		// Load source object.
		$object->fetchCommon($fromID);

		// Reset some properties.
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields.
		if (property_exists($object, 'ref')) {
			$object->ref = '';
		}
		if (!empty($options['fk_accident'])) {
			if (property_exists($object, 'fk_accident')) {
				$object->fk_accident = $options['fk_accident'];
			}
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}

		// Create clone
		$object->causality_tree = '';
		$object->fk_task        = 0;
		$object->context        = 'createfromclone';
		$investigationId        = $object->create($user);

		if ($investigationId > 0) {
			// Load signatory from source object.
			$signatory   = new SaturneSignature($this->db, $this->module, $this->element);
			$signatories = $signatory->fetchSignatory('', $fromID, $this->element);
			if (is_array($signatories) && !empty($signatories)) {
				foreach ($signatories as $arrayRole) {
					foreach ($arrayRole as $signatoryRole) {
						$signatory->createFromClone($user, $signatoryRole->id, $investigationId);
					}
				}
			}
		} else {
			$error++;
			$this->error  = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context);

		// End.
		if (!$error) {
			$this->db->commit();
			return $investigationId;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}

