<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file        class/digiriskdocuments.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for DigiriskDocuments (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturnedocuments.class.php';

/**
 * Class for DigiriskDocuments
 */
class DigiriskDocuments extends SaturneDocuments
{
	/**
	 * @var string Module name
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
	 */
	public $table_element = 'saturne_object_documents';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db, $module, $element)
	{
		parent::__construct($db, $module, $element);
	}

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false = launch triggers after, true = disable triggers
     * @return int             0 < if KO, ID of created object if OK
     */
    public function create(User $user, bool $notrigger = false, object $parentObject = null): int
    {
        $this->DigiriskFillJSON();
        return parent::create($user, $notrigger, $parentObject);
    }

	/**
	 * Function for JSON filling before saving in database
	 *
	 */
	public function DigiriskFillJSON() {
        switch ($this->element) {
			case "legaldisplay":
				$this->json = $this->LegalDisplayFillJSON();
				break;
			case "informationssharing":
				$this->json = $this->InformationsSharingFillJSON();
				break;
            case "auditreportdocument":
                $riskAssessmentDocument = new RiskAssessmentDocument($this->db);
                $this->json = $riskAssessmentDocument->RiskAssessmentDocumentFillJSON();
                break;
			case "riskassessmentdocument":
				$this->json = $this->RiskAssessmentDocumentFillJSON();
				break;
			case "preventionplandocument":
				$this->json = $this->PreventionPlanDocumentFillJSON();
				break;
			case "firepermitdocument":
				$this->json = $this->FirePermitDocumentFillJSON();
				break;
		}
	}

	/**
	 *	Load the info information of the object
	 *
	 *	@param  int		$id       ID of object
	 *	@return	int
	 */
	public function info($id)
	{
		$fieldlist = $this->getFieldList();

		if (empty($fieldlist)) return 0;

		$sql = 'SELECT '.$fieldlist;
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
//				if ($obj->fk_user_author)
//				{
//					$cuser = new User($this->db);
//					$cuser->fetch($obj->fk_user_author);
//					$this->user_creation = $cuser;
//				}
//
//				if ($obj->fk_user_valid)
//				{
//					$vuser = new User($this->db);
//					$vuser->fetch($obj->fk_user_valid);
//					$this->user_validation = $vuser;
//				}
//
//				if ($obj->fk_user_cloture)
//				{
//					$cluser = new User($this->db);
//					$cluser->fetch($obj->fk_user_cloture);
//					$this->user_cloture = $cluser;
//				}

				$this->date_creation = $this->db->jdate($obj->date_creation);
				//$this->date_modification = $this->db->jdate($obj->datem);
				//$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    /**
     * Set risk tasks segment
     *
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (projectEntities, riskId, riskTasks)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setRiskTasksTag(Translate $outputLangs, array $moreParam): array
    {
        global $conf, $mc;

        $array = [];

        $result          = 1;
        $projectEntities = $moreParam['projectEntities'];
        if ($moreParam['entity'] == 'shared' && !empty($projectEntities)) {
            $result = !empty($mc->sharings['project']) ? empty(array_diff(array_keys($projectEntities), $mc->sharings['project'])) : 0;
        }

        if ($result == 0) {
            $array['riskTaskUncompleted'] = $outputLangs->trans('NoTaskShared');
            $array['riskTaskCompleted']   = $outputLangs->trans('NoTaskShared');
            return $array;
        }

        $riskId    = $moreParam['riskId'];
        $riskTasks = $moreParam['riskTasks'];
        if (empty($riskTasks) || empty($riskTasks[$riskId])) {
            $array['riskTaskUncompleted'] = '';
            $array['riskTaskCompleted']   = '';
            return $array;
        }

        foreach ($riskTasks[$riskId] as $riskTask) {
            //@todo a refaire
            $AllInitiales = '';
            $related_task_contact_ids = $riskTask->getListContactId();
            if (!empty($related_task_contact_ids) && is_array($related_task_contact_ids)) {
                $userTmp = new User($riskTask->db);
                foreach ($related_task_contact_ids as $related_task_contact_id) {
                    $userTmp->fetch($related_task_contact_id);
                    $AllInitiales .= strtoupper(str_split($userTmp->firstname, 1)[0] . str_split($userTmp->lastname, 1)[0] . ',');
                }
            }

            $contactslistinternal = $riskTask->liste_contact(-1, 'internal');
            $responsible = '';

            if (!empty($contactslistinternal) && is_array($contactslistinternal)) {
                foreach ($contactslistinternal as $contactlistinternal) {
                    if ($contactlistinternal['code'] == 'TASKEXECUTIVE') {
                        $responsible .= $contactlistinternal['firstname'] . ' ' . $contactlistinternal['lastname'] . ', ';
                    }
                }
            }

            $riskTaskProgress = $riskTask->progress;
            if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS')) {
                $timeSpents = $riskTask->getSummaryOfTimeSpent();
                if ($timeSpents['total_duration'] > 0 && !empty($riskTask->planned_workload)) {
                    $riskTaskProgress = round($timeSpents['total_duration'] / $riskTask->planned_workload * 100, 2);
                }
            }

            $riskTaskTypes = ['Uncompleted'];
            if ($riskTaskProgress == 100) {
                if (!getDolGlobalInt('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE')) {
                    $array['riskTaskCompleted'] = $outputLangs->transnoentities('ActionPreventionCompletedTaskDone');
                } else {
                    $riskTaskTypes[] = 'Completed';
                }
            }
            foreach ($riskTaskTypes as $riskTaskType) {
                $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Label') . ' : ' . $riskTask->label . '<br>';
                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT')) {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Ref') . ' : ' . $riskTask->ref . '<br>';
                }
                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT')) {
                    (($responsible) ? $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Responsible') . ' : ' . $responsible . '<br>' : '');
                }

                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT')) {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('DateStart') . ' : ';
                    if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_START_DATE') && !empty($riskTask->dateo)) {
                        $array['riskTask' . $riskTaskType] .= dol_print_date($riskTask->dateo, 'dayreduceformat') . '<br>';
                    } else {
                        $array['riskTask' . $riskTaskType] .= dol_print_date($riskTask->datec, 'dayreduceformat') . '<br>';
                    }
                    if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_END_DATE') && !empty($riskTask->datee)) {
                        $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Deadline') . ' : ' . dol_print_date($riskTask->datee, 'dayreduceformat') . '<br>';
                    }
                }

                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT')) {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->trans('Budget') . ' : ' . price($riskTask->budget_amount, 0, $outputLangs, 1, 0, 0, $conf->currency) . ' - ';
                }
                if ($riskTaskType != 'Completed') {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->trans('DigiriskProgress') . ' : ' . ($riskTaskProgress ?: 0) . ' %'  . '<br>';
                }

                (($AllInitiales) ? $array['riskTask' . $riskTaskType] .= $outputLangs->trans('ContactsAction') . ' : ' . $AllInitiales . '<br>' : '');
                (($riskTask->description) ? $array['riskTask' . $riskTaskType] .= $outputLangs->trans('Description') . ' : ' . $riskTask->description . '<br>' : '');

                $array['riskTask' . $riskTaskType] .= '<br>';
            }
        }

        return $array;
    }

    /**
     * Set risk by risk assessment levels segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, digiriskElements, riskByRiskAssessmentLevels)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setRiskByRiskAssessmentLevelsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment($moreParam['segmentName']);
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $digiriskElements           = $moreParam['digiriskElements'];
            $riskByRiskAssessmentLevels = $moreParam['riskByRiskAssessmentLevels'];
            $riskAssessmentLevel        = explode('Risks', $moreParam['segmentName'])[1];
            if (empty($digiriskElements) || empty($riskByRiskAssessmentLevels) || empty($riskByRiskAssessmentLevels[$riskAssessmentLevel])) {
                $tmpArray['digiriskElementLabel']   = '';
                $tmpArray['picto']                  = '';
                $tmpArray['riskCategoryName']       = '';
                $tmpArray['ref']                    = '';
                $tmpArray['riskAssessmentCotation'] = '';
                $tmpArray['description']            = $outputLangs->trans('NoDescriptionThere');
                $tmpArray['riskAssessmentComment']  = $outputLangs->trans('NoRiskThere');
                $tmpArray['riskTaskUncompleted']    = $outputLangs->trans('NoTaskUnCompletedThere');
                $tmpArray['riskTaskCompleted']      = $outputLangs->trans('NoTaskCompletedThere');
                $tmpArray['riskAssessment_photo']   = $outputLangs->transnoentities('NoFileLinked');

                SaturneDocumentModel::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($riskByRiskAssessmentLevels[$riskAssessmentLevel] as $risk) {
                $digiriskElement = $digiriskElements[$risk->fk_element];
                if (empty($digiriskElement)) {
                    continue; // Skip if digirisk element not found (case of GP/UT fiche with spécific id)
                }

                $depthHyphens                     = str_repeat('- ', $digiriskElement['depth']);
                $tmpArray['digiriskElementLabel'] = $depthHyphens . 'S' . $digiriskElement['object']->entity . ' - ' . $digiriskElement['object']->ref . ' - ' . $digiriskElement['object']->label;

                $tmpArray['picto']                  = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($risk) . '.png';
                $tmpArray['riskCategoryName']       = getDolGlobalInt('DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME') ? $risk->getDangerCategoryName($risk) : ' ';
                $tmpArray['ref']                    = $risk->ref . ' - ' . $risk->riskAssessmentRef;
                $tmpArray['riskAssessmentCotation'] = $risk->riskAssessmentCotation ?: 0;
                $tmpArray['description']            = $risk->description;

                if (!getDolGlobalInt('DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT') && !empty($risk->riskAssessmentComment)) {
                    $tmpArray['riskAssessmentComment'] = dol_print_date((getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') && !empty($risk->riskAssessmentDate) ? $risk->riskAssessmentDate : $risk->riskAssessmentDateCreation), 'dayreduceformat') . ': ';
                }
                $tmpArray['riskAssessmentComment'] = $risk->riskAssessmentComment ?: '';

                $moreParam['riskId']             = $risk->id;
                $riskTask                        = static::setRiskTasksTag($outputLangs, $moreParam);
                $tmpArray['riskTaskUncompleted'] = $riskTask['riskTaskUncompleted'];
                $tmpArray['riskTaskCompleted']   = $riskTask['riskTaskCompleted'];

                $tmpArray['riskAssessment_photo'] = $outputLangs->transnoentities('NoFileLinked');
                if (!empty($risk->riskAssessmentPhoto)) {
                    $entityPath                       = $risk->entity != 1 ? '/' . $risk->entity : '';
                    $path                             = DOL_DATA_ROOT . $entityPath . '/digiriskdolibarr/riskassessment/' . $risk->riskAssessmentRef;
                    $fileSmall                        = saturne_get_thumb_name($risk->riskAssessmentPhoto);
                    $image                            = $path . '/thumbs/' . $fileSmall;
                    $tmpArray['riskAssessment_photo'] = $image;
                }

                SaturneDocumentModel::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Fill risk data for ODT
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (entity (current/shared))
     *
     * @return int                   1 if OK, <=0 if KO
     * @throws Exception
     */
    public function fillRiskData(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        // Replace tags of lines
        try {
            for ($i = 4; $i >= 1; $i--) {
                $moreParam['segmentName'] = $moreParam['entity'] . 'Risks' . $i;
                static::setRiskByRiskAssessmentLevelsSegment($odfHandler, $outputLangs, $moreParam);
            }
        } catch (OdfException $e) {
            $this->error = $e->getMessage();
            dol_syslog($this->error, LOG_WARNING);
            return -1;
        }
        return 0;
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

        $className = $object->parent_type;
        if (file_exists( __DIR__ . '/digiriskelement/' . $className .'.class.php')) {
            require_once __DIR__ . '/digiriskelement/' . $className .'.class.php';
        } else if (file_exists( __DIR__ . '/digiriskdolibarrdocuments/' . $className .'.class.php')) {
            require_once __DIR__ . '/digiriskdolibarrdocuments/' . $className .'.class.php';
        }  else {
            require_once __DIR__ . '/' . $className .'.class.php';
        }

        $parentElement = new $className($this->db);
        $parentElement->fetch($object->parent_id);

        $ret  = parent::getTriggerDescription($object);

        $ret .= $langs->transnoentities('ElementType') . ' : ' . $object->parent_type . '<br>';
        $ret .= $langs->transnoentities('ParentElement') . ' : ' . $parentElement->ref . ' ' . $parentElement->label . '<br>';
        $ret .= $langs->transnoentities('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';

        return $ret;
    }
}
