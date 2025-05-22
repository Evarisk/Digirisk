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
require_once __DIR__ . '/digiriskdolibarrdashboard.class.php';
require_once __DIR__ . '/evaluator.class.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/accidentlesion/mod_accidentlesion_standard.php';

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
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'fontawesome_fa-user-injured_fas_#d35968';

	const STATUS_DELETED   = -1;
	const STATUS_DRAFT     = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_LOCKED    = 2;
	const STATUS_ARCHIVED  = 3;

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
		'status'            => ['type' => 'smallint',     'label' => 'Status',           'enabled' => '1', 'position' => 70, 'notnull' => 1, 'visible' => 2, 'index' => 0, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'Validated', 2 => 'Locked', 3 => 'Archived']],
		'label'             => ['type' => 'varchar(255)', 'label' => 'Label',            'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'help' => "Help text", 'showoncombobox' => '1',],
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
        'fk_ticket'         => ['type' => 'integer:Ticket:ticket/class/ticket.class.php', 'label' => 'FkTicket',  'enabled' => '1', 'position' => 145, 'notnull' => -1, 'visible' => 1,],
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
    public $fk_ticket;
	public $fk_soc;
	public $fk_user_employer;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
        //Transform fk_user_victim into victim signatory for every accidents (backward compatibility)
        if (empty(getDolGlobalInt("DIGIRISKDOLIBARR_ACCIDENT_REMOVE_FK_USER_VICTIM"))) {
            $this->fields['fk_user_victim'] = ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserVictim',   'enabled' => '1', 'position' => 81, 'notnull' => -1, 'visible' => 1,];
        }
		return parent::__construct($db, $this->module, $this->element);
	}


	/**
	 * Clone an object into another one.
	 *
	 * @param  User      $user    User that creates
	 * @param  int       $fromID  ID of object to clone.
     * @param  array     $options Options array.
	 * @return int                New object created, <0 if KO.
	 * @throws Exception
	 */
	public function createFromClone(User $user, int $fromID, array $options): int
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $conf, $langs;

		$error  = 0;
		$object = new self($this->db);
		$this->db->begin();

		// Load source object.
		$object->fetchCommon($fromID);
        $objectRef = $object->ref;
        $objectId  = $object->id;

		// Reset some properties.
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields.
		if (property_exists($object, 'ref')) {
            $object->ref = $object->getNextNumRef();
        }
        if (!empty($options['label'])) {
            if (property_exists($object, 'label')) {
                $object->label = $options['label'];
            }
        } else {
            $object->label = $langs->trans('CloneFrom') . ' ' . $objectRef;
        }
		if (property_exists($object, 'ref_ext')) {
			$object->ref_ext = '';
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
        if (empty($options['photos'])) {
            $object->photo = '';
        }

        // Create clone
        $object->context = 'createfromclone';
		$accidentId      = $object->create($user);

		if ($accidentId > 0) {
            // Add Photos.
            if (!empty($options['photos'])) {
                $dir  = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident';
                $path = $dir . '/' . $objectRef . '/photos';
                dol_mkdir($dir . '/' . $object->ref . '/photos');
                dolCopyDir($path,$dir . '/' . $object->ref . '/photos', 0, 1);
            }
            if (!empty($options['workstop'])) {
                $accidentWorkstop = new AccidentWorkStop($this->db);
                $workstops        = $accidentWorkstop->fetchFromParent($objectId);

                if (is_array($workstops) && !empty($workstops)) {
                    foreach($workstops as $workstop) {
                        $workstop->fk_accident = $accidentId;
                        $workstop->ref         = $accidentWorkstop->getNextNumRef();
                        $workstop->context     = 'createfromclone';
                        $workstop->create($user);
                    }
                }
            }
            if (!empty($options['lesion'])) {
                $accidentLesion = new AccidentLesion($this->db);
                $lesions        = $accidentLesion->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $objectId]);

                if (is_array($lesions) && !empty($lesions)) {
                    foreach($lesions as $lesion) {
                        $lesion->fk_accident = $accidentId;
                        $lesion->ref         = $lesion->getNextNumRef();
                        $lesion->context     = 'createfromclone';
                        $lesion->create($user);
                    }
                }
            }
            if (!empty($options['metadata'])) {
                $accidentMetadata = new AccidentMetaData($this->db);
                $accidentMetadata->fetch(0, '', ' AND t.fk_accident =' . $objectId . ' AND t.status = 1');
                $accidentMetadata->fk_accident = $accidentId;
                $accidentMetadata->context     = 'createfromclone';
                $accidentMetadata->create($user);
            }
            if (!empty($options['categories'])) {
                $cat        = new Categorie($this->db);
                $categories = $cat->containing($objectId, 'accident');
                if (is_array($categories) && !empty($categories)) {
                    $categoryIds = [];
                    foreach ($categories as $cat) {
                        $categoryIds[] = $cat->id;
                    }
                    $object->setCategories($categoryIds);
                }
            }
            if (!empty($options['attendants'])) {
                // Load signatory from source object.
                $signatory   = new SaturneSignature($this->db, $this->module, $this->element);
                $signatories = $signatory->fetchSignatory('', $fromID, $this->element);
                if (is_array($signatories) && !empty($signatories)) {
                    foreach ($signatories as $arrayRole) {
                        foreach ($arrayRole as $signatoryRole) {
                            $signatory->createFromClone($user, $signatoryRole->id, $accidentId);
                        }
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
			return $accidentId;
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
     * Load accident infos
     *
     * @param  array     $moreParam More param (filter)
     * @return array     $array     Array of accidents
     * @throws Exception
     */
    public function loadAccidentInfos(array $moreParam = []): array
    {
        $array = [];

        $select             = ', SUM(aw.workstop_days) AS nbAccidentWorkStop';
        $moreSelects        = ['nbAccidentWorkStop'];
        $join               = ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_workstop AS aw ON t.rowid = aw.fk_accident';
        $filter             = 't.status = ' . self::STATUS_VALIDATED . ($moreParam['filter'] ?? '');
        $groupBy            = ' GROUP BY ' . $this->getFieldList('t');
        $array['accidents'] = saturne_fetch_all_object_type('Accident', '', '', 0, 0, ['customsql' => $filter], 'AND', false, true, false, $join, [], $select, $moreSelects, $groupBy);
        if (!is_array($array['accidents'] ) || empty($array['accidents'] )) {
            $array['accidents'] = [];
        }

        $array['nbAccidents'] = count($array['accidents']);

        return $array;
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
            $this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatus[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');

			$this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
		}

        $statusType = 'status' . $status;
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status4';
        }
        if ($status == self::STATUS_LOCKED || $status == self::STATUS_ARCHIVED) {
            $statusType = 'status8';
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
    public function load_dashboard(): array
    {
        global $langs, $conf;

        $confName        = dol_strtoupper($this->module) . '_DASHBOARD_CONFIG';
        $dashboardConfig = json_decode(getDolUserString($confName));
        $array = ['graphs' => [], 'disabledGraphs' => []];

        $join                   = ' LEFT JOIN ' . MAIN_DB_PREFIX . $this->table_element . ' as a ON a.rowid = t.fk_accident';
        $accidentsWithWorkStops = saturne_fetch_all_object_type('AccidentWorkStop', 'DESC', 't.rowid', 0, 0, [], 'AND', false, true, false, $join);
        $accidents              = $this->fetchAll('', '', 0, 0, ['customsql' => ' t.status > ' . self::STATUS_DRAFT]);

        if (empty($accidents) && !is_array($accidents)) {
            $accidents = [];
        }
        if (empty($accidentsWithWorkStops) && !is_array($accidentsWithWorkStops)) {
            $accidentsWithWorkStops = [];
        }

        $arrayNbDaysWithoutAccident = $this->getNbDaysWithoutAccident($accidents);
        $arrayNbWorkstopDays        = $this->getNbWorkstopDays($accidentsWithWorkStops);

        $arrayNbPresquAccidents        = $this->getNbPresquAccidents();
        $arrayNbAccidentInvestigations = $this->getNbAccidentInvestigations();

        $evaluator                   = new Evaluator($this->db);
        $employees                   = $evaluator->getNbEmployees();
        $arrayNbAccidentsByEmployees = $this->getNbAccidentsByEmployees($accidents, $accidentsWithWorkStops, $employees);
        $arrayFrequencyIndex         = $this->getFrequencyIndex($accidentsWithWorkStops, $employees);
        $arrayFrequencyRate          = $this->getFrequencyRate($accidentsWithWorkStops);
        $arrayGravityRate            = $this->getGravityRate($accidentsWithWorkStops);

        $array['widgets'] = [
            'accident' => [
                'title'      => $langs->transnoentities('Accidents'),
                'picto'      => 'fas fa-user-injured',
                'pictoColor' => '#F39B1F',
                'label'      => [$langs->transnoentities('DayWithoutAccident') ?? '', $langs->transnoentities('WorkStopDays') ?? '', $langs->transnoentities('NbAccidentsByEmployees') ?? '', $langs->transnoentities('NbPresquAccidents') ?? '', $langs->transnoentities('NbAccidentInvestigations') ?? ''],
                'content'    => [$arrayNbDaysWithoutAccident['daywithoutaccident'] ?? 0, $arrayNbWorkstopDays['nbworkstopdays'] ?? 0, $arrayNbAccidentsByEmployees['nbaccidentsbyemployees'] ?? 0, $arrayNbPresquAccidents['nbpresquaccidents'] ?? 0, $arrayNbAccidentInvestigations['nbaccidentinvestigations'] ?? 0],
                'widgetName' => $langs->transnoentities('Accident')
            ],
            'accidentrateindicator' => [
                'title'      => $langs->transnoentities('Frequency'),
                'picto'      => 'fas fa-chart-bar',
                'pictoColor' => '#9735FF',
                'label'      => [$langs->transnoentities('FrequencyIndex') ?? '', $langs->transnoentities('FrequencyRate') ?? '', $langs->transnoentities('GravityRate') ?? ''],
                'content'    => [$arrayFrequencyIndex['frequencyindex'] ?? 0, $arrayFrequencyRate['frequencyrate'] ?? 0, $arrayGravityRate['gravityrate'] ?? 0],
                'tooltip'    => [
                    (($conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES) ? $langs->transnoentities('FrequencyIndexTooltip') . '<br>' . $langs->transnoentities('NbEmployeesConfTooltip') : $langs->transnoentities('FrequencyIndexTooltip')),
                    (($conf->global->DIGIRISKDOLIBARR_NB_WORKED_HOURS > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS) ? $langs->transnoentities('FrequencyRateTooltip') . '<br>' . $langs->transnoentities('NbWorkedHoursTooltip') : $langs->transnoentities('FrequencyRateTooltip')),
                    (($conf->global->DIGIRISKDOLIBARR_NB_WORKED_HOURS > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS) ? $langs->transnoentities('GravityRateTooltip') . '<br>' . $langs->transnoentities('NbWorkedHoursTooltip') : $langs->transnoentities('GravityRateTooltip'))
                ],
                'widgetName' => $langs->transnoentities('AccidentRateIndicator')
            ]
        ];

        if (empty($dashboardConfig->graphs->AccidentRepartition->hide)) {
            $array['graphs'][] = $this->getNbAccidents($accidents, $accidentsWithWorkStops);
        } else {
            $array['disabledGraphs']['AccidentRepartition'] = $langs->transnoentities('AccidentRepartition');
        }
        if (empty($dashboardConfig->graphs->AccidentByYear->hide)) {
            $array['graphs'][] = $this->getNbAccidentsLast3years($accidents);
        } else {
            $array['disabledGraphs']['AccidentByYear'] = $langs->transnoentities('AccidentByYear');
        }

        return $array;
    }

    /**
     * Get number days without accident
     *
     * @param  array $accidents Array of accidents
     * @return array
     */
    public function getNbDaysWithoutAccident(array $accidents = []): array
    {
        $lastAccident = end($accidents);
        if ($lastAccident != null) {
            $lastTimeAccident            = dol_now() - $lastAccident->accident_date;
            $array['daywithoutaccident'] = abs(round($lastTimeAccident / 86400));
        } else {
            $array['daywithoutaccident'] = 'N/A';
        }
        return $array;
    }

    /**
     * Get number accidents
     *
     * @param array $accidents              Array of accidents
     * @param array $accidentsWithWorkStops Array of accidents with work stops
     * @return array
     */
    public function getNbAccidents(array $accidents = [], array $accidentsWithWorkStops = []): array
    {
        global $conf, $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('AccidentRepartition');
        $array['name']  = 'AccidentRepartition';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        $array['showlegend'] = $conf->browser->layout == 'phone' ? 1 : 2;
        $array['dataset']    = 1;

        $array['labels'] = [
            'accidents' => [
                'label' => $langs->transnoentities('AccidentWithDIAT'),
                'color' => '#e05353'
            ],
            'accidentswithoutDIAT' => [
                'label' => $langs->transnoentities('AccidentWithoutDIAT'),
                'color' => '#e9ad4f'
            ],
        ];

        $array['data']['accidents']            = count($accidentsWithWorkStops);
        $array['data']['accidentswithoutDIAT'] = count($accidents) - $array['data']['accidents'];

        return $array;
    }

    /**
     * Get number accidents for last 3 years
     *
     * @param  array $accidents Array of accidents
     * @return array
     */
    public function getNbAccidentsLast3years(array $accidents = []): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('AccidentByYear');
        $array['name']  = 'AccidentByYear';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 3;

        $array['labels'] = [
            'pastlastyear' => [
                'label' => date('Y', strtotime('-2 year')),
                'color' => '#9567aa'
            ],
            'lastyear' => [
                'label' => date('Y', strtotime('-1 year')),
                'color' => '#4f9ebe'
            ],
            'currentyear' => [
                'label' => date('Y'),
                'color' => '#fac461'
            ],
        ];

        $accidentsByYear = [];
        $accidentsArray  = [];
        if (is_array($accidents) && !empty($accidents)) {
            foreach($accidents as $accident) {
                $accidentDate = dol_getdate($accident->accident_date);
                $yearKey      = $accidentDate['year'];
                $monthKey     = $accidentDate['mon'];
                if (!isset($accidentsByYear[$yearKey][$monthKey - 1])) {
                    $accidentsByYear[$yearKey][$monthKey - 1] = 0;
                }
                $accidentsByYear[$yearKey][$monthKey - 1] += 1;
            }

            for ($i = 1; $i < 13; $i++) {
                $month = $langs->transnoentitiesnoconv('MonthShort'.sprintf('%02d', $i));
                $accidentsArray[$i - 1] = [$month];
                for ($j = 0; $j < 3; $j++) {
                    $accidentsArray[$i - 1][date('Y') - 2 + $j] = 0;
                }
            }

            foreach($accidentsByYear as $year => $accidentByYear) {
                foreach($accidentByYear as $month => $accidentByMonth) {
                    if (isset($accidentsArray[$month][$year])) {
                        $accidentsArray[$month][$year] = $accidentByMonth;
                    }
                }
            }

            foreach($accidentsArray as $accidentArray) {
                $array['data'][] = array_values($accidentArray);
            }
        }

        return $array;
    }

    /**
     * Get number workstop days
     *
     * @param  array $accidentsWithWorkStops Array of accidents with work stops
     * @return array
     */
    public function getNbWorkstopDays(array $accidentsWithWorkStops = []): array
    {
        $nbWorkStopDays = 0;
        if (!empty($accidentsWithWorkStops)) {
            foreach ($accidentsWithWorkStops as $accidentsWithWorkStop) {
                $nbWorkStopDays += $accidentsWithWorkStop->workstop_days;
            }
        }
        $array['nbworkstopdays'] = $nbWorkStopDays;

        return $array;
    }

    /**
     * Get number accidents by employees
     *
     * @param  array $accidents              Array of accidents
     * @param  array $accidentsWithWorkStops Array of accidents with work stops
     * @param  array $employees              Array of employees
     * @return array
     */
    public function getNbAccidentsByEmployees(array $accidents = [], array $accidentsWithWorkStops = [], array $employees = []): array
    {
        $arrayNbAccidents = $this->getNbAccidents($accidents, $accidentsWithWorkStops);
        if ($employees['nbemployees'] > 0 && ($arrayNbAccidents['data']['accidents'] + $arrayNbAccidents['data']['accidentswithoutDIAT']) > 0) {
            $nbAccidentsByEmployees          = ($arrayNbAccidents['data']['accidents'] + $arrayNbAccidents['data']['accidentswithoutDIAT']) / $employees['nbemployees'];
            $array['nbaccidentsbyemployees'] = price2Num($nbAccidentsByEmployees, 2);
        } else {
            $array['nbaccidentsbyemployees'] = 'N/A';
        }
        return $array;
    }

    /**
     * Get number presqu'accidents
     *
     * @return array
     * @throws Exception
     */
    public function getNbPresquAccidents(): array
    {
        global $conf, $langs;

        $array['nbpresquaccidents'] = 'N/A';

        $category = new Categorie($this->db);

        $result = $category->fetch(0, $langs->transnoentities('PresquAccident'));
        if ($result <= 0) {
            return $array;
        }

        $filter  = 't.fk_statut > 0 AND t.entity = ' . $conf->entity . ' AND cp.fk_categorie IN (' . $category->id  . ')';
        $tickets = saturne_fetch_all_object_type('Ticket', '', '', 0, 0, ['customsql' => $filter], 'AND', false, false, true);
        if (!is_array($tickets) || empty($tickets)) {
            return $array;
        }

        $array['nbpresquaccidents'] = count($tickets);

        return $array;
    }

    /**
     * Get number accident investigations
     *
     * @param  array    $moreParam More param (Object/user/etc)
     * @return array
     * @throws Exception
     */
    public function getNbAccidentInvestigations(array $moreParam = []): array
    {
        require_once __DIR__ . '/accidentinvestigation.class.php';

        $accidentInvestigation = new AccidentInvestigation($this->db);
        if (isset($moreParam['filter']) && strpos($moreParam['filter'], 't.entity') !== false) {
            $accidentInvestigation->ismultientitymanaged = 0;
        }
        $accidentInvestigations = $accidentInvestigation->fetchAll('', '', 0, 0, ['customsql' => ' t.status > ' . AccidentInvestigation::STATUS_DRAFT . ($moreParam['filter'] ?? '')]);
        if (!empty($accidentInvestigations) && is_array($accidentInvestigations)) {
            $array['nbaccidentinvestigations'] = count($accidentInvestigations);
        } else {
            $array['nbaccidentinvestigations'] = 'N/A';
        }
        return $array;
    }

    /**
     * Get frequency index (number accidents with DIAT by employees) x 1000
     *
     * @param  array $accidentsWithWorkStops Array of accidents with work stops
     * @param  array $employees              Array of employees
     * @return array
     */
    public function getFrequencyIndex(array $accidentsWithWorkStops = [], array $employees = []): array
    {
        if ($employees['nbemployees'] > 0) {
            $frequencyIndex = (count($accidentsWithWorkStops) / $employees['nbemployees']) * 1000;
            if ($frequencyIndex > 0) {
                $array['frequencyindex'] = price2Num($frequencyIndex, 2);
            } else {
                $array['frequencyindex'] = 'N/A';
            }
        } else {
            $array['frequencyindex'] = 'N/A';
        }
        return $array;
    }

    /**
     * Get frequency rate (number accidents with DIAT by working hours) x 1 000 000
     *
     * @param  array $accidentsWithWorkStops Array of accidents with work stops
     * @return array
     */
    public function getFrequencyRate(array $accidentsWithWorkStops = []): array
    {
        $workHours = getWorkedHours();
        if ($workHours > 0) {
            $frequencyRate = (count($accidentsWithWorkStops) / $workHours) * 1000000;
            if ($frequencyRate > 0) {
                $array['frequencyrate'] = price2Num($frequencyRate, 2);
            } else {
                $array['frequencyrate'] = 'N/A';
            }
        } else {
            $array['frequencyrate'] = 'N/A';
        }
        return $array;
    }

    /**
     * Get gravity rate (number workstop days by working hours) x 1 000
     *
     * @param  array $accidentsWithWorkStops Array of accidents with work stops
     * @return array
     */
    public function getGravityRate(array $accidentsWithWorkStops = []): array
    {
        $arrayNbWorkstopDays = $this->getNbWorkstopDays($accidentsWithWorkStops);
        $workHours           = getWorkedHours();
        if ($workHours > 0) {
            $gravityRate = ($arrayNbWorkstopDays['nbworkstopdays'] / $workHours) * 1000;
            if ($gravityRate > 0) {
                $array['gravityrate'] = price2Num($gravityRate, 5);
            } else {
                $array['gravityrate'] = 'N/A';
            }
        } else {
            $array['gravityrate'] = 'N/A';
        }
        return $array;
    }

    /**
     * Get user victim object.
     *
     * @return User
     */
    public function getUserVictim():User {
        $user = new User($this->db);
        $signatory = new SaturneSignature($this->db);

        $victimSignatory = $signatory->fetchSignatory('Victim', $this->id, 'accident');

        if (is_array($victimSignatory) && !empty($victimSignatory)) {
            $victimSignatory = array_shift($victimSignatory);
            $user->fetch($victimSignatory->element_id);
        }
        return $user;
    }

    /**
     * Write information of trigger description
     *
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

        $userEmployer = new User($this->db);
        $userVictim   = $this->getUserVictim();

        $userEmployer->fetch($object->fk_user_employer);

        //1 : Accident in DU / GP, 2 : Accident in society, 3 : Accident in another location
        switch ($object->external_accident) {
            case 1:
                if (!empty($object->fk_standard)) {
                    require_once __DIR__ . '/digiriskstandard.class.php';
                    $digiriskStandard = new DigiriskStandard($this->db);
                    $digiriskStandard->fetch($object->fk_standard);
                    $accidentLocation = $digiriskStandard->ref . " - " . $conf->global->MAIN_INFO_SOCIETE_NOM;
                } else if (!empty($object->fk_element)) {
                    require_once __DIR__ . '/digiriskelement.class.php';
                    $digiriskElement  = new DigiriskElement($this->db);
                    $digiriskElement->fetch($object->fk_element);
                    $accidentLocation = $digiriskElement->ref . " - " . $digiriskElement->label;
                }
                break;
            case 2:
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                $society          = new Societe($this->db);
                $society->fetch($object->fk_soc);
                $accidentLocation = $society->ref . " - " . $society->label;
            case 3:
                $accidentLocation = (dol_strlen($object->accident_location) > 0 ? $object->accident_location : $langs->trans('NoData'));
                break;
        }

        $ret  = parent::getTriggerDescription($object);
        $ret .= $userVictim->id > 0 ? $langs->trans('UserVictim') . ' : ' . $userVictim->firstname . $userVictim->lastname . '<br>' : '';
        $ret .= $langs->trans('UserEmployer') . ' : ' . $userEmployer->firstname . $userEmployer->lastname . '<br>';
        $ret .= $langs->trans('AccidentLocation') . ' : ' . $accidentLocation  . '<br>';
        $ret .= $langs->trans('AccidentType') . ' : ' . ($object->accident_type ? $langs->trans('CommutingAccident') : $langs->trans('WorkAccidentStatement')) . '<br>';
        $ret .= (dol_strlen($object->accident_date) > 0 ? $langs->trans('AccidentDate') . ' : ' . dol_print_date($object->accident_date, 'dayhoursec') . '<br>' : '');

        return $ret;
    }

    /**
     * Return banner tab content.
     * @return array
     * @throws Exception
     */
    public function getBannerTabContent() : array
    {
        global $langs;

        $workstopLine      = new AccidentWorkStop($this->db);
        $accidentLines     = $workstopLine->fetchFromParent($this->id);
        $totalWorkStopDays = 0;

        if (!empty($accidentLines) && $accidentLines > 0) {
            foreach ($accidentLines as $accidentLine) {
                if ($accidentLine->status > 0) {
                    $totalWorkStopDays += $accidentLine->workstop_days;
                }
            }
            $moreHtmlRef      = $langs->trans('TotalWorkStopDays') . ' : ' . $totalWorkStopDays;
            $lastaccidentline = end($accidentLines);
            if ($this->status == Accident::STATUS_LOCKED) {
                $moreHtmlRef     .= '<br>' . $langs->trans('ReturnWorkDate') . ' : ' . dol_print_date($lastaccidentline->date_end_workstop, 'dayhour');
            }
        } else {
            $moreHtmlRef = $langs->trans('RegisterAccident');
        }
        $moreHtmlRef .= '<br>';
        $moreParams = [];

        return [$moreHtmlRef, $moreParams];
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
	public $element = 'accidentworkstop';

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
		'workstop_days'       => ['type' => 'integer',      'label' => 'WorkStopDays',      'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => -1,],
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

    /*
     * Load object in memory from the database
     *
     * @param  int       $parent_id Id parent object
     * @return array|int            <0 if KO, 0 if not found, >0 if OK
     * @throws Exception
     */
    public function fetchFromParent(int $parent_id)
    {
        $filter = ['customsql' => 'fk_accident =' . $this->db->escape($parent_id) . ' AND t.status >= 0'];

        return $this->fetchAll('', '', 0, 0, $filter);
    }

    /*
     * Write information of trigger description
     *
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $langs;

        $ret  = parent::getTriggerDescription($object);
        $ret .= $langs->transnoentities('WorkStopDays') . ' : ' . $object->workstop_days . '<br>';
        $ret .= $langs->transnoentities('WorkStopDocument') . ' : ' . (!empty($object->declaration_link) ? $object->declaration_link : 'N/A') . '<br>';
        $ret .= (dol_strlen($object->date_start_workstop) > 0 ? $langs->transnoentities('DateStartWorkStop') . ' : ' . dol_print_date($object->date_start_workstop, 'dayhoursec') . '<br>' : '');
        $ret .= (dol_strlen($object->date_end_workstop) > 0 ? $langs->transnoentities('DateEndWorkStop') . ' : ' . dol_print_date($object->date_end_workstop, 'dayhoursec') . '<br>' : '');

        return $ret;
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
	public $isextrafieldmanaged = 1;

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
	public $element = 'accidentlesion';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'digiriskdolibarr_accident_lesion';

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'fontawesome_fa-user-injured_fas_#d35968';

    const STATUS_DELETED   = -1;

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

    /**
     * Write information of trigger description
     *
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $langs;

        $ret  = parent::getTriggerDescription($object);
        $ret .= $langs->transnoentities('LesionLocalization') . ' : ' . $object->lesion_localization . '<br>';
        $ret .= $langs->transnoentities('LesionNature') . ' : ' . $object->lesion_nature . '<br>';

        return $ret;
    }
}
