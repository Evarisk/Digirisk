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
 * \file        class/riskanalysis/riskassessment.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for RiskAssessment (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

require_once __DIR__ . '/risk.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/class/saturneobject.class.php';

/**
 * Class for RiskAssessment
 */
class RiskAssessment extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'riskassessment';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_riskassessment';

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
     * @var string Name of icon for riskassessment. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'riskassessment@digiriskdolibarr' if picto is file 'img/object_riskassessment.png'
     */
    public string $picto = 'fontawesome_fa-chart-line_fas_#d35968';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'               => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'                 => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'             => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'              => array('type' => 'integer', 'label' => 'entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'date_creation'       => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => -0,),
		'tms'                 => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'import_key'          => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => 0,),
		'status'              => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 0,),
		'method'              => array('type' => 'varchar(50)', 'label' => 'EvaluationMethod', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 0,),
		'cotation'            => array('type' => 'integer', 'label' => 'Evaluation', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 4,),
		'gravite'             => array('type' => 'integer', 'label' => 'Gravity', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 0,),
		'protection'          => array('type' => 'integer', 'label' => 'Protection', 'enabled' => '1', 'position' => 110, 'notnull' => 0, 'visible' => 0,),
		'occurrence'          => array('type' => 'integer', 'label' => 'Occurrence', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => 0,),
		'formation'           => array('type' => 'integer', 'label' => 'Formation', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => 0,),
		'exposition'          => array('type' => 'integer', 'label' => 'Exposition', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 0,),
		'date_riskassessment' => array('type' => 'datetime', 'label' => 'RiskAssessmentDate', 'enabled' => '1', 'position' => 141, 'notnull' => -1, 'visible' => 0,),
		'comment'             => array('type' => 'text', 'label' => 'Comment', 'enabled' => '1', 'position' => 150, 'notnull' => 0, 'visible' => 0,),
		'photo'               => array('type' => 'varchar(255)', 'label' => 'Photo', 'enabled' => '1', 'position' => 160, 'notnull' => 0, 'visible' => 0,),
		'has_tasks'           => array('type' => 'integer', 'label' => 'Tasks', 'enabled' => '$conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT', 'position' => 170, 'notnull' => 0, 'visible' => '$conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT'),
		'fk_user_creat'       => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 180, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',),
		'fk_user_modif'       => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 190, 'notnull' => -1, 'visible' => 0,),
		'fk_risk'             => array('type' => 'integer', 'label' => 'ParentRisk', 'enabled' => '1', 'position' => 200, 'notnull' => 1, 'visible' => 0,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $cotation;
	public $method;
	public $gravite;
	public $protection;
	public $occurrence;
	public $formation;
	public $exposition;
	public $date_riskassessment;
	public $comment;
	public $photo;
	public $has_tasks;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_risk;

    public $advancedCotation = [];

	/**
	 * Constructor.
	 *
	 * @param DoliDb $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
        $this->advancedCotation = [1 => 'gravite', 2 => 'exposition', 3 => 'occurrence', 4 => 'formation', 5 => 'protection'];
		parent::__construct($db, $this->module, $this->element);
	}


	/**
	 * Create object into database
	 *
	 * @param  User $user         User that creates
	 * @param  bool $notrigger    False=launch triggers after, true=disable triggers
	 * @param  bool $updatestatus Update previous riskassessment status
	 * @return int                if < KO, ID of created object if OK
	 */
	public function create(User $user, bool $notrigger = false, bool $updatestatus = true): int
	{
		$result = $this->createCommon($user, $notrigger);

		if ($result > 0 && $updatestatus > 0) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . "digiriskdolibarr_riskassessment";
			$sql .= " SET status = 0";
			$sql .= " WHERE fk_risk = " . $this->fk_risk;
			$sql .= " AND rowid != " . $result;
			$this->db->query($sql);
		}

		return $result;
	}

	/**
	 * Load object in memory from the database with Parent ID
	 *
	 * @param int $parent_id Id parent object
	 * @param int $active
	 * @param string $desc
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchFromParent($parent_id, $active = 0, $desc = '')
	{
		$filter                        = array('customsql' => 'fk_risk=' . $this->db->escape($parent_id));
		if ($active) $filter['status'] = 1;

		return $this->fetchAll($desc, 'rowid', 0, 0, $filter, 'AND', 1);
	}

    /**
     * Update risk assessment status into database
     *
     * @param  User      $user   User that modifies
     * @param  int       $riskID Risk id
     * @throws Exception
     */
    public function updatePreviousRiskAssessmentStatus(User $user, int $riskID)
    {
        $riskAssessments = $this->fetchAll('DESC', 'rowid', 1, 0, ['customsql' => 'fk_risk = ' . $riskID . ' AND status = 0']);
        if (is_array($riskAssessments) && !empty($riskAssessments)) {
            foreach ($riskAssessments as $riskAssessment) {
                $riskAssessment->setValueFrom('status', 1, '', '', 'int', '', $user);
            }
        }
    }

    /**
     * check if risk assessment not exists for a risk
     *
     * @param  int       $limit Limit
     * @return array|int        Int <0 if KO, array of pages if OK
     * @throws Exception
     */
    public function checkNotExistsRiskForRiskAssessment(int $limit = 0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $risk = new Risk($this->db);

        $sql  = 'SELECT ';
        $sql .= $this->getFieldList('t');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE !EXISTS';
        $sql .= ' ( SELECT ';
        $sql .= $risk->getFieldList('r');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $risk->table_element . ' as r';
        $sql .= ' WHERE r.rowid = t.fk_risk )';

        $records = [];
        $resql   = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new $this($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }

	/**
	 * Return scale level for risk assessment
	 *
	 * @return	int			between 1 and 4
	 */
	public function getEvaluationScale()
	{
		if ( ! $this->cotation) {
			return 1;
		}
		switch ($this->cotation) {
			case ($this->cotation < 48):
				return 1;
			case ($this->cotation < 51) :
				return 2;
			case( $this->cotation < 80):
				return 3;
			case ($this->cotation >= 80):
				return 4;
			default :
				return -1;
		}
	}

	/**
	 * Get riskassessment categories number
	 *
	 * @param  int       $digiriskelementID Digiriskelement ID
	 * @return array
	 * @throws Exception
	 */
	public function getRiskAssessmentCategoriesNumber($riskAssessmentList = [], $riskList = [], $digiriskelementID = 0)
	{
		$scaleCounter = [
			1 => 0,
			2 => 0,
			3 => 0,
			4 => 0
		];

		$riskListOrderedByIds = [];
		if (is_array($riskList) && !empty($riskList)) {
			foreach ($riskList as $risk) {
				$riskListOrderedByIds[$risk->id] = $risk;
			}
		}

		if (is_array($riskAssessmentList) && !empty($riskAssessmentList)) {
			foreach ($riskAssessmentList as $riskAssessment) {
				if ($digiriskelementID > 0) {
					if (is_array($riskListOrderedByIds) && !empty($riskListOrderedByIds)) {
						if (is_object($riskListOrderedByIds[$riskAssessment->fk_risk]) && $riskListOrderedByIds[$riskAssessment->fk_risk]->appliedOn == $digiriskelementID) {
							$scale = $riskAssessment->getEvaluationScale();
							$scaleCounter[$scale] += 1;
						}
					}
				} else {
					$scale = $riskAssessment->getEvaluationScale();
					$scaleCounter[$scale] += 1;
				}
			}
		}

		return $scaleCounter;
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

        $risk = new Risk($this->db);
        $risk->fetch($object->fk_risk);

        $ret = parent::getTriggerDescription($object);

        $ret .= $langs->trans('ParentRisk') . ' : ' . $risk->ref . '<br>';
        $ret .= $langs->trans('Comment') . ' : ' . (!empty($object->comment) ? $object->comment : 'N/A') . '<br>';
        $ret .= ((!empty($object->date_riskassessment) && $conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) ? $langs->trans('RiskAssessmentDate') . ' : ' . dol_print_date($object->date_riskassessment, 'day') . '<br>' : '');
        $ret .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
        if ($object->method == 'advanced') {
            $ret .= $langs->trans('Evaluation') . ' : ' . $object->cotation . '<br>';
            $ret .= $langs->trans('Gravity') . ' : ' . $object->gravite . '<br>';
            $ret .= $langs->trans('Protection') . ' : ' . $object->protection . '<br>';
            $ret .= $langs->trans('Occurrence') . ' : ' . $object->occurrence . '<br>';
            $ret .= $langs->trans('Formation') . ' : ' . $object->formation . '<br>';
            $ret .= $langs->trans('Exposition') . ' : ' . $object->exposition . '<br>';
        }

        return $ret;
    }
}
