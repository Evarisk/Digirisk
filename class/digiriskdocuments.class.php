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
     * Fill risk data for ODT
     *
     * @param Odf       $odfHandler             Object builder odf library
     * @param Object    $object                 Object source to build document
     * @param Translate $outputLangs            Lang object to use for output
     * @param array     $tmparray               Array filled with data
     * @param string    $file                   Filename
     * @param array     $risk                   Array data of risks
     * @param array     $activeDigiriskElements Array of active digirisk elements
     * @param bool      $sharedRisk             Boolean to know if risks are shared
     *
     * @return void
     * @throws Exception
     */
    public function fillRiskData(Odf $odfHandler, $object, Translate $outputLangs, $tmparray, $file, $risks, $activeDigiriskElements = null, bool $sharedRisk = false)
    {
        global $action, $conf, $hookmanager, $langs, $mc;

        $usertmp               = new User($this->db);
        $project               = new Project($this->db);
        $DUProject             = new Project($this->db);
        $risk                  = new Risk($this->db);
        if ($activeDigiriskElements == null) {
            $digiriskelementobject = new DigiriskElement($this->db);
            $activeDigiriskElements = $digiriskelementobject->getActiveDigiriskElements($sharedRisk);
        }

        $DUProject->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
        $tasksSortedByRisk      = $risk->getTasksWithFkRisk();

        for ($i = 4; $i >= 1; $i--) {
            $foundTagForLines = 1;
            try {
                $listlines = $odfHandler->setSegment(($sharedRisk ? 'shared' : '') . 'risk' . $i);
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                // We may arrive here if tags for lines not present into template
                $foundTagForLines = 0;
                $listlines        = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                if (is_array($risks) && ! empty($risks)) {
                    foreach ($risks as $line) {
                        if ($line->fk_element > 0 && ((!$sharedRisk && $line->origin_type != 'shared') || ($sharedRisk && $line->origin_type == 'shared'))) {
                            $tmparray['actionPreventionUncompleted'] = "";
                            $tmparray['actionPreventionCompleted']   = "";
                            $lastEvaluation                          = $line->lastEvaluation;

                            if ($lastEvaluation->cotation >= 0 && !empty($lastEvaluation) && is_object($lastEvaluation)) {
                                $scale = $lastEvaluation->getEvaluationScale();

                                if ($scale == $i) {
                                    $element        = $activeDigiriskElements[$line->fk_element];
                                    $linked_element = $activeDigiriskElements[$line->appliedOn];
                                    $nomElement     = '';
                                    $dash           = getDolGlobalInt('DIGIRISKDOLIBARR_RISK_LIST_PARENT_VIEW') > 0;
                                    if (getDolGlobalInt('DIGIRISKDOLIBARR_RISK_LIST_PARENT_VIEW') > 0) {
                                        $digiriskElementIds = $activeDigiriskElements[$line->fk_element]->getBranch($line->fk_element);

                                        if (!empty($digiriskElementIds)) {
                                            $digiriskElementIds = array_reverse($digiriskElementIds);
                                            array_pop($digiriskElementIds);

                                            foreach ($digiriskElementIds as $key => $digiriskElementId) {
                                                $nomElement .= '<br>' . str_repeat(' - ', count($digiriskElementIds) + 1 - $key) . $activeDigiriskElements[$digiriskElementId]->ref . ' - ' . $activeDigiriskElements[$digiriskElementId]->label . chr(0x0A) . chr(0x0A);
                                            }
                                        }
                                    }

                                    if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN) {
                                        $nomElement .= '<br>' . (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $element->entity : '') . ' - ' . $element->ref . ' - ' . $element->label;
                                        if ($line->fk_element != $line->appliedOn) {
                                            $nomElement .= '<br>' . $langs->trans('AppliedOn') . ' ' . $linked_element->ref . ' - ' . $linked_element->label;
                                        }
                                    } else {
                                        if ($linked_element->id > 0) {
                                            $nomElement .= '<br>' . ($dash > 0 ? ' - ' : '') . $linked_element->ref . ' - ' . $linked_element->label;
                                        } else {
                                            $nomElement .= '<br>' . ($dash > 0 ? ' - ' : '') . $element->ref . ' - ' . $element->label;
                                        }
                                    }

                                    $tmparray['nomElement']        = $nomElement;
                                    $tmparray['nomDanger']         = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $line->getDangerCategory($line) . '.png';
                                    $tmparray['nomPicto']          = (!empty($conf->global->DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME) ? $line->getDangerCategoryName($line) : ' ');
                                    $tmparray['identifiantRisque'] = $line->ref . ' - ' . $lastEvaluation->ref;
                                    $tmparray['quotationRisque']   = $lastEvaluation->cotation ?: 0;
                                    $tmparray['descriptionRisque'] = $line->description;

                                    if (!getDolGlobalInt('DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT') && dol_strlen($lastEvaluation->comment)) {
                                        $tmparray['commentaireEvaluation'] = dol_print_date((getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') && !empty($lastEvaluation->date_riskassessment) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation), 'dayreduceformat') . ': ';
                                    } else {
                                        $tmparray['commentaireEvaluation'] = '';
                                    }
                                    $tmparray['commentaireEvaluation'] .= !empty($lastEvaluation->comment) ? $lastEvaluation->comment : '';

                                    $related_tasks = $tasksSortedByRisk[$line->id];
                                    if (!empty($related_tasks) && is_array($related_tasks)) {
                                        foreach ($related_tasks as $related_task) {
                                            if(is_object($related_task)) {
                                                if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS)) {
                                                    $project->fetch($related_task->fk_projet);
                                                    if ($project->entity != $conf->entity) {
                                                        $result = !empty($mc->sharings['project']) ? in_array($project->entity, $mc->sharings['project']) : 0;
                                                    } else {
                                                        $result = 1;
                                                    }
                                                } else {
                                                    $result = 1;
                                                }
                                                if ($result > 0) {
                                                    $AllInitiales = '';
                                                    $related_task_contact_ids = $related_task->getListContactId();
                                                    if (!empty($related_task_contact_ids) && is_array($related_task_contact_ids)) {
                                                        foreach ($related_task_contact_ids as $related_task_contact_id) {
                                                            $usertmp->fetch($related_task_contact_id);
                                                            $AllInitiales .= strtoupper(str_split($usertmp->firstname, 1)[0] . str_split($usertmp->lastname, 1)[0] . ',');
                                                        }
                                                    }

                                                    $contactslistinternal = $related_task->liste_contact(-1, 'internal');
                                                    $responsible = '';

                                                    if (!empty($contactslistinternal) && is_array($contactslistinternal)) {
                                                        foreach ($contactslistinternal as $contactlistinternal) {
                                                            if ($contactlistinternal['code'] == 'TASKEXECUTIVE') {
                                                                $responsible .= $contactlistinternal['firstname'] . ' ' . $contactlistinternal['lastname'] . ', ';
                                                            }
                                                        }
                                                    }

                                                    if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
                                                        $timeSpentArray = $related_task->getSummaryOfTimeSpent();
                                                        if ($timeSpentArray['total_duration'] > 0 && !empty($related_task->planned_workload)) {
                                                            $task_progress = round($timeSpentArray['total_duration'] / $related_task->planned_workload * 100, 2);
                                                        } else {
                                                            $task_progress = 0;
                                                        }
                                                    } else {
                                                        $task_progress = $related_task->progress;
                                                    }

                                                    if ($task_progress == 100) {
                                                        if ($conf->global->DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE > 0) {
                                                            (($related_task->label) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Label') . ' : ' . $related_task->label . "\n" : '');
                                                            if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT')) {
                                                                (($related_task->ref) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Ref') . ' : ' . $related_task->ref . "\n" : '');
                                                            }
                                                            if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT')) {
                                                                (($responsible) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Responsible') . ' : ' . $responsible . "\n" : '');
                                                            }
                                                            if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT')) {
                                                                $tmparray['actionPreventionCompleted'] .= $langs->trans('DateStart') . ' : ';
                                                                if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && !empty($related_task->dateo)) {
                                                                    $tmparray['actionPreventionCompleted'] .= dol_print_date(($related_task->dateo), 'dayreduceformat');
                                                                } else {
                                                                    $tmparray['actionPreventionCompleted'] .= dol_print_date(($related_task->datec), 'dayreduceformat');
                                                                }
                                                                if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && !empty($related_task->datee)) {
                                                                    $tmparray['actionPreventionCompleted'] .= "\n" . $langs->transnoentities('Deadline') . ' : ' . dol_print_date($related_task->datee, 'dayreduceformat') . "\n";
                                                                } else {
                                                                    $tmparray['actionPreventionCompleted'] .= ' - ' . $langs->transnoentities('Deadline') . ' : ' . $langs->trans('NoData') . "\n";
                                                                }
                                                            }
                                                            if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT')) {
                                                                $tmparray['actionPreventionCompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . "\n";
                                                            }
                                                            (($AllInitiales) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('ContactsAction') . ' : ' . $AllInitiales . "\n" : '');
                                                            (($related_task->description) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Description') . ' : ' . $related_task->description . "\n" : '');
                                                            $tmparray['actionPreventionCompleted'] .= "\n";
                                                        } else {
                                                            $tmparray['actionPreventionCompleted'] = $langs->transnoentities('ActionPreventionCompletedTaskDone');
                                                        }
                                                    } else {
                                                        (($related_task->label) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Label') . ' : ' . $related_task->label . "\n" : '');
                                                        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT')) {
                                                            (($related_task->ref) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Ref') . ' : ' . $related_task->ref . "\n" : '');
                                                        }
                                                        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT')) {
                                                            (($responsible) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Responsible') . ' : ' . $responsible . "\n" : '');
                                                        }
                                                        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT')) {
                                                            $tmparray['actionPreventionUncompleted'] .= $langs->trans('DateStart') . ' : ';
                                                            if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && !empty($related_task->dateo)) {
                                                                $tmparray['actionPreventionUncompleted'] .= dol_print_date(($related_task->dateo), 'dayreduceformat');
                                                            } else {
                                                                $tmparray['actionPreventionUncompleted'] .= dol_print_date(($related_task->datec), 'dayreduceformat');
                                                            }
                                                            if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && !empty($related_task->datee)) {
                                                                $tmparray['actionPreventionUncompleted'] .= "\n" . $langs->transnoentities('Deadline') . ' : ' . dol_print_date($related_task->datee, 'dayreduceformat') . "\n";
                                                            } else {
                                                                $tmparray['actionPreventionUncompleted'] .= ' - ' . $langs->transnoentities('Deadline') . ' : ' . $langs->trans('NoData') . "\n";
                                                            }

                                                        }
                                                        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT')) {
                                                            $tmparray['actionPreventionUncompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . ' - ';
                                                        }
                                                        $tmparray['actionPreventionUncompleted'] .= $langs->trans('DigiriskProgress') . ' : ' . ($task_progress ?: 0) . ' %' . "\n";
                                                        (($AllInitiales) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('ContactsAction') . ' : ' . $AllInitiales . "\n" : '');
                                                        (($related_task->description) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Description') . ' : ' . $related_task->description . "\n" : '');
                                                        $tmparray['actionPreventionUncompleted'] .= "\n";
                                                    }
                                                } else {
                                                    $tmparray['actionPreventionUncompleted'] = $langs->trans('NoTaskShared');
                                                    $tmparray['actionPreventionCompleted'] = $langs->trans('NoTaskShared');
                                                }
                                            }
                                        }
                                    } else {
                                        $tmparray['actionPreventionUncompleted'] = "";
                                        $tmparray['actionPreventionCompleted']   = "";
                                    }

                                    if (dol_strlen($lastEvaluation->photo) && $lastEvaluation !== 'undefined') {
                                        $entity                    = $lastEvaluation->entity > 1 ? '/' . $lastEvaluation->entity : '';
                                        $path                      = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessment/' . $lastEvaluation->ref;
                                        $thumb_name                = getThumbName($lastEvaluation->photo);
                                        $image                     = $path . '/thumbs/' . $thumb_name;
                                        $tmparray['photoAssociee'] = $image;
                                    } else {
                                        $tmparray['photoAssociee'] = $langs->transnoentities('NoFileLinked');
                                    }

                                    unset($tmparray['object_fields']);

                                    complete_substitutions_array($tmparray, $outputLangs, $object, $line, "completesubstitutionarray_lines");

                                    // Call the ODTSubstitutionLine hook
                                    $parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray, 'line' => $line);
                                    $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

                                    foreach ($tmparray as $key => $val) {
                                        try {
                                            if ($key == 'photoAssociee') {
                                                if (file_exists($val)) {
                                                    $listlines->setImage($key, $val);
                                                } else {
                                                    $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                                }
                                            } elseif ($key == 'nomDanger') {
                                                if (file_exists($val)) {
                                                    $listlines->setImage($key, $val);
                                                } else {
                                                    $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                                }
                                            } elseif (empty($val) && $val != '0') {

                                                $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                                            } else {

                                                $listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                                            }
                                        } catch (OdfException $e) {
                                            dol_syslog($e->getMessage());
                                        }
                                    }
                                    $listlines->merge();
                                }
                            }
                        }
                    }
                } else {
                    $tmparray['nomElement']                  = $langs->trans('NoData');
                    $tmparray['nomDanger']                   = $langs->trans('NoData');
                    $tmparray['nomPicto']                    = $langs->trans('NoData');
                    $tmparray['identifiantRisque']           = $langs->trans('NoData');
                    $tmparray['quotationRisque']             = $langs->trans('NoData');
                    $tmparray['descriptionRisque']           = $langs->trans('NoDescriptionThere');
                    $tmparray['commentaireEvaluation']       = $langs->trans('NoRiskThere');
                    $tmparray['actionPreventionUncompleted'] = $langs->trans('NoTaskUnCompletedThere');
                    $tmparray['actionPreventionCompleted']   = $langs->trans('NoTaskCompletedThere');
                    $tmparray['photoAssociee']               = $langs->transnoentities('NoFileLinked');
                    foreach ($tmparray as $key => $val) {
                        try {
                            if (empty($val)) {
                                $listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                            } else {
                                $listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                            }
                        } catch (SegmentException $e) {
                            dol_syslog($e->getMessage());
                        }
                    }
                    $listlines->merge();
                }
                $odfHandler->mergeSegment($listlines);
            }
        }
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
