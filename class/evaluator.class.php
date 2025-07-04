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
 * \file    class/evaluator.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a CRUD class file for Evaluator (Create/Read/Update/Delete).
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';


/**
 * Class for Evaluator.
 */
class Evaluator extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'evaluator';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiriskdolibarr_evaluator';

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
     * @var string Name of icon for evaluator. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'evaluator@digiriskdolibarr' if picto is file 'img/object_evaluator.png'.
     */
    public string $picto = 'fontawesome_fa-user-check_fas_#d35968';

    public const STATUS_DELETED   = -1;
    public const STATUS_VALIDATED = 1;
    public const STATUS_ARCHIVED  = 3;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'           => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'             => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'         => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'          => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'   => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'             => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'import_key'      => array('type' => 'integer', 'label' => 'ImportId', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => 0,),
		'status'          => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 0, 'index' => 1,),
		'duration'        => array('type' => 'smallint', 'label' => 'EvaluationDuration', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1, 'index' => 1,),
		'assignment_date' => array('type' => 'datetime', 'label' => 'AssignmentDate', 'enabled' => '1', 'position' => 90, 'notnull' => 1, 'visible' => 1,),
		'job'             => array('type' => 'varchar(80)', 'label' => 'PostOrFunction', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 1,),
		'fk_user_creat'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 110, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',),
		'fk_user_modif'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 120, 'notnull' => -1, 'visible' => 0,),
		'fk_user'         => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAssigned', 'enabled' => '1', 'position' => 130, 'notnull' => 1, 'visible' => 1, 'default' => 0,),
		'fk_parent'       => array('type' => 'integer:DigiriskElement:digiriskdolibarr/class/digiriskelement.class.php', 'label' => 'ParentElement', 'enabled' => '1', 'position' => 5, 'notnull' => 1, 'visible' => 1, 'default' => 0,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $duration;
	public $assignment_date;
	public $post;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_user;
	public $fk_parent;

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
	 * Load object in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchFromParent($parent_id)
	{
		$filter = array('customsql' => 'fk_parent=' . $this->db->escape($parent_id) . ' AND status > ' . $this::STATUS_DELETED);
		return $this->fetchAll('', '', 0, 0, $filter, 'AND');
	}

    /**
     * Load evaluator infos
     *
     * @param  array     $moreParam More param (filter/filterEvaluator)
     * @return array     $array     Array of evaluators / evaluatorByEntities
     * @throws Exception
     */
    public static function loadEvaluatorInfos(array $moreParam = []): array
    {
        $array = [];

        $select       = ', d.ref AS digiriskElementRef, d.entity AS digiriskElementEntity, d.label AS digiriskElementLabel, u.lastname AS userLastName, u.firstname AS userFirstName';
        $moreSelects  = ['digiriskElementRef', 'digiriskElementEntity', 'digiriskElementLabel', 'userLastName', 'userFirstName'];
        $join         = ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskelement AS d ON d.rowid = t.fk_parent';
        $join        .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'user AS u ON u.rowid = t.fk_user';
        $filter       = 'd.status = ' . DigiriskElement::STATUS_VALIDATED . ' AND t.status = ' . self::STATUS_VALIDATED . ($moreParam['filter'] ?? '') .  ($moreParam['filterEvaluator'] ?? '');
        $evaluators   = saturne_fetch_all_object_type('Evaluator', '', '', 0, 0, ['customsql' => $filter], 'AND', false, false, false, $join, [], $select, $moreSelects);
        if (!is_array($evaluators) || empty($evaluators)) {
            $evaluators = [];
        }

        $array['nbEvaluators'] = count($evaluators);

        $array['evaluators'] = [];
        foreach ($evaluators as $evaluator) {
            $array['evaluators'][$evaluator->id] = $evaluator;
            $array['nbEvaluatorByEntities'][$evaluator->entity]++;
        }

        return $array;
    }

    /**
     * Load dashboard info evaluator
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        global $conf, $langs;

        $arrayNbEmployeesInvolved = $this->getNbEmployeesInvolved();
        $arrayNbEmployees         = $this->getNbEmployees();

        $array['widgets'] = [
            'employees' => [
                'title'      => $langs->transnoentities('Employees'),
                'picto'      => 'fas fa-user',
                'pictoColor' => '#32E592',
                'label'      => [$langs->transnoentities('NbEmployeesInvolved') ?? '', $langs->transnoentities('NbEmployees') ?? ''],
                'content'    => [$arrayNbEmployeesInvolved['nbemployeesinvolved'] ?? 0, $arrayNbEmployees['nbemployees'] ?? 0],
                'tooltip'    => [$langs->transnoentities('NbEmployeesInvolvedTooltip'), (($conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES > 0 && $conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES) ? $langs->transnoentities('NbEmployeesConfTooltip') : $langs->transnoentities('NbEmployeesTooltip'))],
                'widgetName' => $langs->transnoentities('Employees'),
            ]
        ];

        return $array;
    }

	/**
	 * Get number employees involved.
	 *
     * @param  array    $moreParam More param (Object/user/etc)
     * @return array
	 * @throws Exception
	 */
	public function getNbEmployeesInvolved(array $moreParam = []) {
		// Number employees involved
		$allevaluators = $this->fetchAll('','', 0, 0, ['customsql' => 't.status = ' . Evaluator::STATUS_VALIDATED . ($moreParam['filter'] ?? '')]);
		if (is_array($allevaluators) && !empty($allevaluators)) {
			$array['nbemployeesinvolved'] = count($allevaluators);
		} else {
			$array['nbemployeesinvolved'] = 'N/A';
		}
		return $array;
	}

    /**
     * Get number employees
     *
     * @param  array    $moreParam More param (Object/user/etc)
     * @return array
     * @throws Exception
     */
    public function getNbEmployees(array $moreParam = []): array
    {
        global $user;

        if (getDolGlobalInt('DIGIRISKDOLIBARR_NB_EMPLOYEES') > 0 && getDolGlobalInt('DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES')) {
            $array['nbemployees'] = getDolGlobalInt('DIGIRISKDOLIBARR_NB_EMPLOYEES');
        } else {
            $users = $user->get_full_tree(0, 'u.employee = 1' . ($moreParam['filter'] ?? ''));
            if (!empty($users) && is_array($users)) {
                $array['nbemployees'] = count($users);
            } else {
                $array['nbemployees'] = 0;
            }
        }
        return $array;
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

        $ret = parent::getTriggerDescription($object);

        $now             = dol_now();
        $userstat        = new User($this->db);
        $digiriskelement = new DigiriskElement($this->db);

        $digiriskelement->fetch($object->fk_parent);
        $userstat->fetch($object->fk_user);
        $langs->load('companies');

        $ret .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
        $ret .= $langs->trans('UserAssigned') . ' : ' . $userstat->firstname . " " . $userstat->lastname . '<br>';
        $ret .= $langs->trans('PostOrFunction') . ' : ' . (!empty($object->job) ? $object->job : 'N/A') . '<br>';
        $ret .= $langs->trans('AssignmentDate') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
        $ret .= $langs->trans('EvaluationDuration') . ' : ' . convertSecondToTime($object->duration * 60, 'allhourmin') . ' min' . '<br>';

        return $ret;
    }
}
