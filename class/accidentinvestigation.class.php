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
 * \file        class/accident_investigation.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for Accident Investigation
 */

require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
*	Class to manage accident investigations.
*  Saved into database table llx_digiriskdolibarr_accident_investigation
*/
class AccidentInvestigation extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'accident_investigation';

	/**
	* @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	*/
	public $table_element = 'digiriskdolibarr_accident_investigation';

	/**
	* @var int  Does this object support multicompany module ?
	* 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	*/
	public int $ismultientitymanaged = 1;

	/**
	* @var int  Does object support extrafields ? 0=No, 1=Yes
	*/
	public int $isextrafieldmanaged = 1;

	/**
	* @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	*/
	public string $picto = 'fontawesome_fa-tasks_fas_#d35968';

	public const STATUS_DELETED   = -1;
	public const STATUS_DRAFT     = 0;
	public const STATUS_VALIDATED = 1;
	public const STATUS_LOCKED    = 2;
	public const STATUS_ARCHIVED  = 3;

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
	 * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	* @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	*/
	public $fields = [
		'rowid'                => ['type' => 'integer',      'label' => 'TechnicalID',            'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0,  'noteditable' => 1, 'index' => 1, 'comment' => "Id"],
		'ref'                  => ['type' => 'varchar(128)', 'label' => 'Ref',                    'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4,  'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'comment' => "Reference of object"],
		'ref_ext'              => ['type' => 'varchar(128)', 'label' => 'RefExt',                 'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0,],
		'entity'               => ['type' => 'integer',      'label' => 'Entity',                 'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0,],
		'date_creation'        => ['type' => 'datetime',     'label' => 'DateCreation',           'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2,],
		'tms'                  => ['type' => 'timestamp',    'label' => 'DateModification',       'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0,],
		'import_key'           => ['type' => 'varchar(14)',  'label' => 'ImportId',               'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0,  'index' => 0],
		'status'               => ['type' => 'smallint',     'label' => 'Status',                 'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => -5, 'noteditable' => 1, 'default' => 0, 'index' => 0,],
		'note_public'          => ['type' => 'html',         'label' => 'NotePublic',             'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => -4,],
		'note_private'         => ['type' => 'html',         'label' => 'NotePrivate',            'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => -4,],
		'seniority_in_company' => ['type' => 'integer',      'label' => 'SeniorityInCompany',     'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 4,],
		'date_start'           => ['type' => 'timestamp',    'label' => 'DateInvestigationStart', 'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 4,],
		'date_end'             => ['type' => 'timestamp',    'label' => 'DateInvestigationEnd',   'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 4,],
		'victim_skills'        => ['type' => 'text',         'label' => 'VictimSkills',           'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => -4,],
		'collective_equipment' => ['type' => 'text',         'label' => 'CollectiveEquipment',    'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => -4,],
		'individual_equipment' => ['type' => 'text',         'label' => 'IndividualEquipment',    'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => -4,],
		'circumstances'        => ['type' => 'text',         'label' => 'Circumstances',          'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => -4,],
		'fk_accident'          => ['type' => 'integer:Accident:custom/digiriskdolibarr/class/accident/accident.class.php', 'label' => 'FkAccident', 'enabled' => 1, 'position' => 170, 'notnull' => 1, 'visible' => 1,],
		'fk_task'              => ['type' => 'integer:Task:projet/class/task.class.php', 'label' => 'FkTask',     'picto' => 'Task', 'enabled' => 1, 'position' => 180, 'notnull' => 1, 'visible' => 4,  'noteditable' => 1],
		'fk_user_creat'        => ['type' => 'integer:User:user/class/user.class.php',   'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 190, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
		'fk_user_modif'        => ['type' => 'integer:User:user/class/user.class.php',   'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 200, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
	];

	/**
	 * @var int ID.
	 */
	public int $rowid;

	/**
	 * @var string Ref.
	 */
	public $ref;

	/**
	 * @var string Ref ext.
	 */
	public $ref_ext;

	/**
	 * @var int Entity.
	 */
	public $entity;

	/**
	 * @var int|string Creation date.
	 */
	public $date_creation;

	/**
	 * @var int|string Timestamp.
	 */
	public $tms;

	/**
	 * @var string Import key.
	 */
	public $import_key;

	/**
	 * @var int Status.
	 */
	public $status;

	/**
	 * @var string Public note.
	 */
	public $note_public;

	/**
	 * @var string Private note.
	 */
	public $note_private;

	/**
	 * @var int Seniority in company.
	 */
	public $seniority_in_company;

	/**
	 * @var int|string Date investigation start.
	 */
	public $date_start;

	/**
	 * @var string|null Victim skills.
	 */
	public ?string $victim_skills;

	/**
	 * @var string|null Collective Equipment.
	 */
	public ?string $collective_equipment;

	/**
	 * @var string|null Individual Equipment.
	 */
	public ?string $individual_equipment;

	/**
	 * @var string|null Circumstances.
	 */
	public ?string $circumstances;

	/**
	 * @var int Task ID.
	 */
	public int $fk_task;

	/**
	 * @var int Accident ID.
	 */
	public int $fk_accident;

	/**
	 * @var int User ID.
	 */
	public int $fk_user_creat;

	/**
	 * @var int|null User ID.
	 */
	public ?int $fk_user_modif;

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
	 * Create object into database.
	 *
	 * @param  User $user      User that creates.
	 * @param  bool $notrigger false = launch triggers after, true = disable triggers.
	 * @return int             0 < if KO, ID of created object if OK.
	 */
	public function create(User $user, bool $notrigger = false): int
	{
		$this->ref    = $this->getNextNumRef();
		$this->status = $this->status ?: self::STATUS_DRAFT;

		return parent::create($user, $notrigger);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 	      Label of status
	 */
	public function getLibStatut(int $mode = 0): string
	{
		return $this->LibStatut($this->status, $mode);
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
			$this->labelStatus[self::STATUS_LOCKED]         = $langs->transnoentitiesnoconv('Locked');
			$this->labelStatus[self::STATUS_ARCHIVED]       = $langs->transnoentitiesnoconv('Archived');
			$this->labelStatus[self::STATUS_DELETED]        = $langs->transnoentitiesnoconv('Deleted');

			$this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
			$this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
			$this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
		}

		$statusType = 'status' . $status;
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}
		if ($status == self::STATUS_LOCKED) {
			$statusType = 'status6';
		}
		if ($status == self::STATUS_ARCHIVED) {
			$statusType = 'status8';
		}
		if ($status == self::STATUS_DELETED) {
			$statusType = 'status9';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}
}

