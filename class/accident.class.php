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
 * \file        class/accident.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for Accident
 */

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';
require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/digiriskdocuments.class.php';
require_once __DIR__ . '/evaluator.class.php';

/**
 * Class for Accident
 */
class Accident extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Error string
	 * @see        $errors
	 */
	public $error;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = [];

	/**
	 * @var array Result array.
	 */
	public $result = [];

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var AccidentWorkStop[]     Array of subtable lines
	 */
	public $lines = [];

	/**
	 * @var string ID to identify managed object.
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
	public int $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'fontawesome_fa-user-injured_fas_#d35968';

	const STATUS_DELETED   = -1;
	const STATUS_DRAFT     = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_LOCKED    = 2;

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
		'rowid'             => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => '1', 'position' => 1,  'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
		'ref'               => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"],
		'ref_ext'           => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,],
		'entity'            => ['type' => 'integer',      'label' => 'Entity',           'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,],
		'date_creation'     => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 2,],
		'tms'               => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,],
		'status'            => ['type' => 'smallint',     'label' => 'Status',           'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 2, 'index' => 0,],
		'label'             => ['type' => 'varchar(255)', 'label' => 'Label',            'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'help' => "Help text", 'showoncombobox' => '1',],
		'fk_user_victim'    => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserVictim',   'enabled' => '1', 'position' => 81, 'notnull' => -1, 'visible' => 1,],
		'fk_user_employer'  => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserEmployer', 'enabled' => '1', 'position' => 82, 'notnull' => -1, 'visible' => 1,],
		'accident_type'     => ['type' => 'text',         'label' => 'AccidentType',     'enabled' => '1', 'position' => 90, 'notnull' => -1, 'visible' => 1,  'css' => 'minwidth150',],
		'fk_element'        => ['type' => 'integer',      'label' => 'AccidentLocation', 'enabled' => '1', 'position' => 91, 'notnull' => -1, 'visible' => 1,  'css' => 'minwidth150',],
		'fk_standard'       => ['type' => 'integer',      'label' => 'AccidentLocation', 'enabled' => '1', 'position' => 94, 'notnull' => -1, 'visible' => 0,  'css' => 'minwidth150',],
		'fk_soc'            => ['type' => 'integer',      'label' => 'ExtSociety',       'enabled' => '1', 'position' => 95, 'notnull' => -1, 'visible' => 3,],
		'accident_location' => ['type' => 'text',         'label' => 'AccidentLocation', 'enabled' => '1', 'position' => 96, 'notnull' => -1, 'visible' => 3,  'css' => 'minwidth150',],
		'accident_date'     => ['type' => 'datetime',     'label' => 'AccidentDate',     'enabled' => '1', 'position' => 100, 'notnull' => -1, 'visible' => 1, 'css' => 'minwidth150',],
		'description'       => ['type' => 'text',         'label' => 'Description',      'enabled' => '1', 'position' => 110, 'notnull' => -1, 'visible' => 1,],
		'photo'             => ['type' => 'text',         'label' => 'Photo',            'enabled' => '1', 'position' => 120, 'notnull' => -1, 'visible' => 3,],
		'external_accident' => ['type' => 'smallint',     'label' => 'ExternalAccident', 'enabled' => '1', 'position' => 130, 'notnull' => -1, 'visible' => 3, 'arrayofkeyval' => ['1' => 'No', '2' => 'Yes', '3' => 'Other'],],
		'fk_project'        => ['type' => 'integer',      'label' => 'FKProject',        'enabled' => '1', 'position' => 140, 'notnull' => 1, 'visible' => 0,],
		'fk_user_creat'     => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 150, 'notnull' => 1,  'visible' => 0, 'foreignkey' => 'user.rowid',],
		'fk_user_modif'     => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'enabled' => '1', 'position' => 160, 'notnull' => -1, 'visible' => 0,],
	];

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
	public $accident_location;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_element;
	public $fk_standard;
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
		return parent::__construct($db, $this->module, $this->element);
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

		global $conf;

		$error  = 0;
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
		if (property_exists($object, 'ref_ext')) {
			$object->ref_ext = '';
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_VALIDATED;
		}

		$refAccidentMod = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON($this->db);
		$object->ref    = $refAccidentMod->getNextValue($object);

		// Create clone
		$object->context = 'createfromclone';
		$investigationId = $object->create($user);

		if ($investigationId > 0) {
			// Load signatory from source object.
			$signatory   = new SaturneSignature($this->db);
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

	/**
	 * Load object in memory from the database
	 *
	 * @param  int $parent_id Id parent object
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchFromParent(int $parent_id)
	{
		$filter = ['customsql' => 'fk_element=' . $this->db->escape($parent_id)];

		return $this->fetchAll('', '', 0, 0, $filter);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut(int $status, int $mode = 0): string
	{
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;

			$this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
			$this->labelStatus[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');

			$this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
			$this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');

		}

		$statusType = 'status' . $status;

		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}
		if ($status == self::STATUS_LOCKED) {
			$statusType = 'status6';
		}
		if ($status == self::STATUS_DELETED) {
			$statusType = 'status9';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 * Load dashboard info accident
	 *
	 * @return array
	 * @throws Exception
	 */
	public function load_dashboard()
	{
		global $conf, $langs;

		$arrayNbDaysWithoutAccident    = $this->getNbDaysWithoutAccident();
		$arrayNbAccidents              = $this->getNbAccidents();
		$arrayNbWorkstopDays           = $this->getNbWorkstopDays();
		$arrayNbAccidentsByEmployees   = $this->getNbAccidentsByEmployees();
		$arrayNbPresquAccidents        = $this->getNbPresquAccidents();
		$arrayNbAccidentInvestigations = $this->getNbAccidentInvestigations();
		$arrayFrequencyIndex           = $this->getFrequencyIndex();
		$arrayFrequencyRate            = $this->getFrequencyRate();
		//$arrayGravityIndex           = $this->getGravityIndex();
		$arrayGravityRate              = $this->getGravityRate();

		$array['widgets'] = array(
			DigiriskDolibarrDashboard::DASHBOARD_ACCIDENT => array(
				'label'      => array($langs->transnoentities("DayWithoutAccident"), $langs->transnoentities("WorkStopDays"), $langs->transnoentities("NbAccidentsByEmployees"), $langs->transnoentities("NbPresquAccidents"), $langs->transnoentities("NbAccidentInvestigations")),
				'content'    => array($arrayNbDaysWithoutAccident['daywithoutaccident'], $arrayNbWorkstopDays['nbworkstopdays'], $arrayNbAccidentsByEmployees['nbaccidentsbyemployees'], $arrayNbPresquAccidents['nbpresquaccidents'], $arrayNbAccidentInvestigations['nbaccidentinvestigations']),
				'picto'      => 'fas fa-user-injured',
				'widgetName' => $langs->transnoentities('Accident')
			),
			DigiriskDolibarrDashboard::DASHBOARD_ACCIDENT_INDICATOR_RATE => array(
				'label'      => array($langs->transnoentities("FrequencyIndex"), $langs->transnoentities("FrequencyRate"), $langs->transnoentities("GravityRate")),
				'content'    => array($arrayFrequencyIndex['frequencyindex'], $arrayFrequencyRate['frequencyrate'], $arrayGravityRate['gravityrate']),
				'tooltip'    => array(
					(($conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES) ? $langs->transnoentities("FrequencyIndexTooltip") . '<br>' . $langs->transnoentities("NbEmployeesConfTooltip") : $langs->transnoentities("FrequencyIndexTooltip")),
					(($conf->global->DIGIRISKDOLIBARR_NB_WORKED_HOURS > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS) ? $langs->transnoentities("FrequencyRateTooltip") . '<br>' . $langs->transnoentities("NbWorkedHoursTooltip") : $langs->transnoentities("FrequencyRateTooltip")),
					(($conf->global->DIGIRISKDOLIBARR_NB_WORKED_HOURS > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS) ? $langs->transnoentities("GravityRateTooltip") . '<br>' . $langs->transnoentities("NbWorkedHoursTooltip") : $langs->transnoentities("GravityRateTooltip"))
				],
				'picto'      => 'fas fa-chart-bar',
				'widgetName' => $langs->transnoentities('AccidentRateIndicator')
			]
		];

		$array['dataset'] = 2;

		$array['graphs'] = $arrayNbAccidents;

		return $array;
	}

	/**
	 * Get number days without accident.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbDaysWithoutAccident() {
		// Number days without accident
		$lastAccident = $this->fetchAll('DESC', 'accident_date', 1, 0 );
		if (is_array($lastAccident) && !empty($lastAccident)) {
			$lastTimeAccident = dol_now() - reset($lastAccident)->accident_date;
			$array['daywithoutaccident'] = abs(round($lastTimeAccident / 86400));
		} else {
			$array['daywithoutaccident'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get number accidents.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbAccidents() {
		global $langs;

		// Number accidents
		$array['title'] = $langs->transnoentities('AccidentRepartition');
		$array['picto'] = '<i class="fas fa-user-injured"></i>';
		$array['labels'] = array(
			'accidents' => array(
				'label' => $langs->transnoentities('AccidentWithDIAT'),
				'color' => '#e05353'
			),
			'accidentswithoutDIAT' => array(
				'label' => $langs->transnoentities('AccidentWithoutDIAT'),
				'color' => '#e9ad4f'
			),
		);
		$allaccidents = $this->fetchAll('','',0,0,['customsql' => ' t.status > 0 ']);

		if (is_array($allaccidents) && !empty($allaccidents)) {
			$accidentworkstop = new AccidentWorkStop($this->db);
			foreach ($allaccidents as $accident) {
				$allaccidentworkstop = $accidentworkstop->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $accident->id]);
				if (is_array($allaccidentworkstop) && !empty($allaccidentworkstop)) {
					$nbaccidents += 1;
				} else {
					$nbaccidentswithoutDIAT += 1;
				}
			}
			$array['data']['accidents'] = $nbaccidents;
			$array['data']['accidentswithoutDIAT'] = $nbaccidentswithoutDIAT;
		} else {
			$array['data']['accidents'] = 0;
			$array['data']['accidentswithoutDIAT'] = 0;
		}
		return $array;
	}

	/**
	 * Get number workstop days.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbWorkstopDays() {
		// Number workstop days
		$allaccidents = $this->fetchAll();
		if (is_array($allaccidents) && !empty($allaccidents)) {
			$accidentworkstop = new AccidentWorkStop($this->db);
			foreach ($allaccidents as $accident) {
				$allaccidentworkstop = $accidentworkstop->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $accident->id]);;
				if (is_array($allaccidentworkstop) && !empty($allaccidentworkstop)) {
					foreach ($allaccidentworkstop as $accidentworkstop) {
						if ($accidentworkstop->id > 0) {
							$nbworkstopdays += $accidentworkstop->workstop_days;
						}
					}
				}
			}
			$array['nbworkstopdays'] = $nbworkstopdays ?: 0;
		} else {
			$array['nbworkstopdays'] = 0;
		}
		return $array;
	}

	/**
	 * Get number accidents by employees.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbAccidentsByEmployees() {
		$evaluator = new Evaluator($this->db);

		// Number accidents by employees
		$arrayNbAccidents = $this->getNbAccidents();
		$arrayNbEmployees = $evaluator->getNbEmployees();
		if ($arrayNbEmployees['nbemployees'] > 0) {
			$nbaccidentsbyemployees = ($arrayNbAccidents['data']['accidents'] + $arrayNbAccidents['data']['accidentswithoutDIAT']) / $arrayNbEmployees['nbemployees'];
			if ($nbaccidentsbyemployees > 0) {
				$array['nbaccidentsbyemployees'] = price2Num($nbaccidentsbyemployees, 2);
			} else {
				$array['nbaccidentsbyemployees'] = 'N/A';
			}
		} else {
			$array['nbaccidentsbyemployees'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get number presqu'accidents.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbPresquAccidents() {
		global $langs;

		$category = new Categorie($this->db);

		// Number accidents presqu'accidents
		$category->fetch(0, $langs->transnoentities('PresquAccident'));
		$alltickets = $category->getObjectsInCateg(Categorie::TYPE_TICKET);
		if (is_array($alltickets) && !empty($alltickets)) {
			$array['nbpresquaccidents'] = count($alltickets);
		} else {
			$array['nbpresquaccidents'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get number accident investigations.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbAccidentInvestigations() {
		// Number accident investigations
		$allaccidents = $this->fetchAll();
		if (is_array($allaccidents) && !empty($allaccidents)) {
			$accidentmetadata = new AccidentMetaData($this->db);
			foreach ($allaccidents as $accident) {
				$filter = ' AND t.fk_accident = ' . $accident->id . ' AND t.status = 1 AND t.accident_investigation = 1';
				$result = $accidentmetadata->fetch(0, '', $filter);
				if ($result > 0) {
					$nbaccidentinvestigations += 1;
				}
			}
			if ($nbaccidentinvestigations > 0) {
				$array['nbaccidentinvestigations'] = $nbaccidentinvestigations;
			} else {
				$array['nbaccidentinvestigations'] = 'N/A';
			}
		} else {
			$array['nbaccidentinvestigations'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get frequency index (number accidents with DIAT by employees) x 1000.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getFrequencyIndex() {
		$evaluator = new Evaluator($this->db);

		// (Number accidents with DIAT by employees) x 1 000
		$arrayNbAccidents = $this->getNbAccidents();
		$arrayNbEmployees = $evaluator->getNbEmployees();
		if ($arrayNbEmployees['nbemployees'] > 0) {
			$frequencyindex = ($arrayNbAccidents['data']['accidents']/$arrayNbEmployees['nbemployees']) * 1000;
			if ($frequencyindex > 0) {
				$array['frequencyindex'] = price2Num($frequencyindex, 2);
			} else {
				$array['frequencyindex'] = 'N/A';
			}
		} else {
			$array['frequencyindex'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get frequency rate (number accidents with DIAT by working hours) x 1 000 000.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getFrequencyRate() {
		// (Number accidents with DIAT by working hours) x 1 000 000
		$arrayNbAccidents = $this->getNbAccidents();
		$total_workhours  = getWorkedHours();

		if ($total_workhours > 0) {
			$frequencyrate = ($arrayNbAccidents['data']['accidents']/$total_workhours) * 1000000;
			if ($frequencyrate > 0) {
				$array['frequencyrate'] = price2Num($frequencyrate, 5);
			} else {
				$array['frequencyrate'] = 'N/A';
			}
		} else {
			$array['frequencyrate'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get gravity rate (number workstop days by working hours) x 1 000.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getGravityRate() {
		// (Number workstop days by working hours) x 1 000
		$arrayNbWorkstopDays = $this->getNbWorkstopDays();
		$total_workhours     = getWorkedHours();
		if ($total_workhours > 0) {
			$gravityrate = ($arrayNbWorkstopDays['nbworkstopdays']/$total_workhours) * 1000;
			if ($gravityrate > 0) {
				$array['gravityrate'] = price2Num($gravityrate, 5);
			} else {
				$array['gravityrate'] = 'N/A';
			}
		} else {
			$array['gravityrate'] = 'N/A';
		}
		return $array;
	}
}

/**
 *	Class to manage accident workstop.
 *  Saved into database table llx_digiriskdolibarr_accident_workstop
 */
class AccidentWorkStop extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

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
	public $element = 'accident_workstop';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'digiriskdolibarr_accident_workstop';

	/**
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'fontawesome_fa-user-injured_fas_#d35968';

	const STATUS_DELETED = -1;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = [
		'rowid'               => ['type' => 'integer',      'label' => 'TechnicalID',       'enabled' => '1', 'position' => 1,  'notnull' => 1,  'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
		'ref'                 => ['type' => 'varchar(128)', 'label' => 'Ref',               'enabled' => '1', 'position' => 10, 'notnull' => 1,  'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"],
		'entity'              => ['type' => 'integer',      'label' => 'Entity',            'enabled' => '1', 'position' => 30, 'notnull' => 1,  'visible' => 0,],
		'date_creation'       => ['type' => 'datetime',     'label' => 'DateCreation',      'enabled' => '1', 'position' => 40, 'notnull' => 1,  'visible' => 0,],
		'tms'                 => ['type' => 'timestamp',    'label' => 'DateModification',  'enabled' => '1', 'position' => 50, 'notnull' => 0,  'visible' => 0,],
		'status'              => ['type' => 'smallint',     'label' => 'Status',            'enabled' => '1', 'position' => 60, 'notnull' => 0,  'visible' => 0, 'index' => 0,],
		'workstop_days'       => ['type' => 'integer',      'label' => 'WorkStopDays',      'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => -1,],
		'date_start_workstop' => ['type' => 'datetime',     'label' => 'DateStartWorkStop', 'enabled' => '1', 'position' => 80, 'notnull' => 0,  'visible' => 0,],
		'date_end_workstop'   => ['type' => 'datetime',     'label' => 'DateEndWorkStop',   'enabled' => '1', 'position' => 81, 'notnull' => 0,  'visible' => 0,],
		'declaration_link'    => ['type' => 'text',         'label' => 'DeclarationLink',   'enabled' => '1', 'position' => 82, 'notnull' => 0,  'visible' => 0,],
		'fk_accident'         => ['type' => 'integer',      'label' => 'FkAccident',        'enabled' => '1', 'position' => 90, 'notnull' => 1,  'visible' => 0,],
	];

	public $rowid;
	public $ref;
	public $entity;
	public $date_creation;
	public $tms;
	public $status;
	public $workstop_days;
	public $date_start_workstop;
	public $date_end_workstop;
	public $declaration_link;
	public $fk_accident;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		return parent::__construct($db, $this->module, $this->element);
	}
}

/**
 *	Class to manage accident metadata.
 *  Saved into database table llx_digiriskdolibarr_accident_metadata
 */
class AccidentMetaData extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

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
	 * @var string ID to identify managed object.
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
	public int $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'fontawesome_fa-user-injured_fas_#d35968';


	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = [
		'rowid'                                => ['type' => 'integer',      'label' => 'TechnicalID',                      'enabled' => '1', 'position' => 1,   'notnull' => 1,  'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
		'entity'                               => ['type' => 'integer',      'label' => 'Entity',                           'enabled' => '1', 'position' => 10,  'notnull' => 1,  'visible' => 0,],
		'date_creation'                        => ['type' => 'datetime',     'label' => 'DateCreation',                     'enabled' => '1', 'position' => 20,  'notnull' => 1,  'visible' => 0,],
		'tms'                                  => ['type' => 'timestamp',    'label' => 'DateModification',                 'enabled' => '1', 'position' => 30,  'notnull' => 0,  'visible' => 0,],
		'status'                               => ['type' => 'smallint',     'label' => 'Status',                           'enabled' => '1', 'position' => 40,  'notnull' => 0,  'visible' => 1, 'index' => 0,],
		'relative_location'                    => ['type' => 'varchar(255)', 'label' => 'RelativeLocation',                 'enabled' => '1', 'position' => 50,  'notnull' => -1, 'visible' => 1,],
		'victim_activity'                      => ['type' => 'text',         'label' => 'VictimActivity',                   'enabled' => '1', 'position' => 60,  'notnull' => -1, 'visible' => 1,],
		'accident_nature'                      => ['type' => 'text',         'label' => 'AccidentNature',                   'enabled' => '1', 'position' => 70,  'notnull' => -1, 'visible' => 1,],
		'accident_object'                      => ['type' => 'text',         'label' => 'AccidentObject',                   'enabled' => '1', 'position' => 80,  'notnull' => -1, 'visible' => 1,],
		'accident_nature_doubt'                => ['type' => 'text',         'label' => 'AccidentNatureDoubt',              'enabled' => '1', 'position' => 90,  'notnull' => -1, 'visible' => 1,],
		'accident_nature_doubt_link'           => ['type' => 'url',          'label' => 'AccidentNatureDoubtLink',          'enabled' => '1', 'position' => 100, 'notnull' => -1, 'visible' => 1,],
		'victim_transported_to'                => ['type' => 'text',         'label' => 'VictimTransportedTo',              'enabled' => '1', 'position' => 110, 'notnull' => -1, 'visible' => 1,],
		'collateral_victim'                    => ['type' => 'boolean',      'label' => 'CollateralVictim',                 'enabled' => '1', 'position' => 120, 'notnull' => -1, 'visible' => 1,],
		'workhours_morning_date_start'         => ['type' => 'datetime',     'label' => 'WorkHoursMorningDateStart',        'enabled' => '1', 'position' => 130, 'notnull' => -1, 'visible' => 1,],
		'workhours_morning_date_end'           => ['type' => 'datetime',     'label' => 'WorkHoursMorningDateEnd',          'enabled' => '1', 'position' => 131, 'notnull' => -1, 'visible' => 1,],
		'workhours_afternoon_date_start'       => ['type' => 'datetime',     'label' => 'WorkHoursAfternoonDateStart',      'enabled' => '1', 'position' => 132, 'notnull' => -1, 'visible' => 1,],
		'workhours_afternoon_date_end'         => ['type' => 'datetime',     'label' => 'WorkHoursAfternoonDateEnd',        'enabled' => '1', 'position' => 133, 'notnull' => -1, 'visible' => 1,],
		'accident_noticed'                     => ['type' => 'text',         'label' => 'AccidentNoticed',                  'enabled' => '1', 'position' => 140, 'notnull' => -1, 'visible' => 1,],
		'accident_notice_date'                 => ['type' => 'datetime',     'label' => 'AccidentNoticeDate',               'enabled' => '1', 'position' => 150, 'notnull' => -1, 'visible' => 1,],
		'accident_notice_by'                   => ['type' => 'text',         'label' => 'AccidentNoticeBy',                 'enabled' => '1', 'position' => 160, 'notnull' => -1, 'visible' => 1,],
		'accident_described_by_victim'         => ['type' => 'boolean',      'label' => 'AccidentDescribedByVictim',        'enabled' => '1', 'position' => 170, 'notnull' => -1, 'visible' => 1,],
		'registered_in_accident_register'      => ['type' => 'boolean',      'label' => 'RegisteredInAccidentRegister',     'enabled' => '1', 'position' => 180, 'notnull' => -1, 'visible' => 1,],
		'register_date'                        => ['type' => 'datetime',     'label' => 'RegisterDate',                     'enabled' => '1', 'position' => 190, 'notnull' => -1, 'visible' => 1,],
		'register_number'                      => ['type' => 'varchar(255)', 'label' => 'RegisterNumber',                   'enabled' => '1', 'position' => 200, 'notnull' => -1, 'visible' => 1,],
		'consequence'                          => ['type' => 'text',         'label' => 'Consequence',                      'enabled' => '1', 'position' => 210, 'notnull' => -1, 'visible' => 1,],
		'police_report'                        => ['type' => 'boolean',      'label' => 'PoliceReport',                     'enabled' => '1', 'position' => 220, 'notnull' => -1, 'visible' => 1,],
		'police_report_by'                     => ['type' => 'text',         'label' => 'PoliceReportBy',                   'enabled' => '1', 'position' => 230, 'notnull' => -1, 'visible' => 1,],
		'first_person_noticed_is_witness'      => ['type' => 'text',         'label' => 'FirstPersonNoticedIsWitness',      'enabled' => '1', 'position' => 240, 'notnull' => -1, 'visible' => 1,],
		'thirdparty_responsibility'            => ['type' => 'boolean',      'label' => 'ThirdPartyResponsability',         'enabled' => '1', 'position' => 250, 'notnull' => -1, 'visible' => 1,],
		'accident_investigation'               => ['type' => 'boolean',      'label' => 'AccidentInvestigation',            'enabled' => '1', 'position' => 260, 'notnull' => -1, 'visible' => 1,],
		'accident_investigation_link'          => ['type' => 'url',          'label' => 'AccidentInvestigationLink',        'enabled' => '1', 'position' => 270, 'notnull' => -1, 'visible' => 1,],
		'cerfa_link'                           => ['type' => 'url',          'label' => 'CerfaLink',                        'enabled' => '1', 'position' => 280, 'notnull' => -1, 'visible' => 1,],
		'json'                                 => ['type' => 'text',         'label' => 'Json',                             'enabled' => '1', 'position' => 290, 'notnull' => -1, 'visible' => 1,],
		'fk_user_witness'                      => ['type' => 'integer',      'label' => 'FirstPersonNoticedIsWitness',      'enabled' => '1', 'position' => 241, 'notnull' => -1, 'visible' => 1,],
		'fk_soc_responsible'                   => ['type' => 'integer',      'label' => 'FkSocResponsible',                 'enabled' => '1', 'position' => 251, 'notnull' => -1, 'visible' => 1,],
		'fk_soc_responsible_insurance_society' => ['type' => 'integer',      'label' => 'FkSocResponsibleInsuranceSociety', 'enabled' => '1', 'position' => 252, 'notnull' => -1, 'visible' => 1,],
		'fk_accident'                          => ['type' => 'integer',      'label' => 'FkAccident',                       'enabled' => '1', 'position' => 330, 'notnull' => -1, 'visible' => -2,],
	];

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
		return parent::__construct($db, $this->module, $this->element);
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 * @throws Exception
	 */
	public function create(User $user, bool $notrigger = false): int
	{
		$result = $this->createCommon($user, $notrigger);

		if ($result > 0) {
			$sql                                                                              = "UPDATE " . MAIN_DB_PREFIX . "$this->table_element";
			$sql                                                                             .= " SET status = 0";
			if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';
			else $sql                                                                        .= ' WHERE 1 = 1';
			$sql                                                                             .= " AND fk_accident = " . $this->fk_accident;
			$sql                                                                             .= " AND rowid != " . $result;

			dol_syslog("accidentmetadata.class::create", LOG_DEBUG);
			$this->db->query($sql);
		}

		return $result;
	}
}

/**
 *	Class to manage accident lesion.
 *  Saved into database table llx_digiriskdolibarr_accident_lesion
 */
class AccidentLesion extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

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
	public $element = 'accident_lesion';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'digiriskdolibarr_accident_lesion';

	/**
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'fontawesome_fa-user-injured_fas_#d35968';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = [
		'rowid'               => ['type' => 'integer',      'label' => 'TechnicalID',       'enabled' => '1',  'position' => 1,  'notnull' => 1,  'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
		'ref'                 => ['type' => 'varchar(128)', 'label' => 'Ref',                'enabled' => '1', 'position' => 10, 'notnull' => 1,  'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"],
		'entity'              => ['type' => 'integer',      'label' => 'Entity',             'enabled' => '1', 'position' => 30, 'notnull' => 1,  'visible' => 0,],
		'date_creation'       => ['type' => 'datetime',     'label' => 'DateCreation',       'enabled' => '1', 'position' => 40, 'notnull' => 1,  'visible' => 0,],
		'tms'                 => ['type' => 'timestamp',    'label' => 'DateModification',   'enabled' => '1', 'position' => 50, 'notnull' => 0,  'visible' => 0,],
		'lesion_localization' => ['type' => 'text',         'label' => 'LesionLocalization', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => 1,],
		'lesion_nature'       => ['type' => 'text',         'label' => 'LesionNature',       'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => 1,],
		'fk_accident'         => ['type' => 'integer',      'label' => 'FkAccident',         'enabled' => '1', 'position' => 80, 'notnull' => 1,  'visible' => 0,],
	];

	public $rowid;
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
		return parent::__construct($db, $this->module, $this->element);
	}
}
