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
 *	\file       core/modules/digiriskdolibarr/digiriskdolibarrdocuments/accidentinvestigationdocument/doc_accidentinvestigationdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

// Load Digirisk libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';

// Load DigiriskDolibarr libraries.
require_once __DIR__ . '/../../../../../class/digiriskstandard.class.php';
require_once __DIR__ . '/modules_accidentinvestigationdocument.php';
require_once __DIR__ . '/mod_accidentinvestigationdocument_standard.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_accidentinvestigationdocument_odt extends ModeleODTAccidentInvestigationDocument
{
	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.5 = array(5, 5)
	 */
	public array $phpmin = [7, 4];

	/**
	 * @var string Dolibarr version of the loaded document.
	 */
	public string $version = 'dolibarr';

	/**
	 * @var string Module.
	 */
	public string $module = 'digiriskdolibarr';

	/**
	 * @var string Document type.
	 */
	public string $document_type = 'accidentinvestigationdocument';

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db, $this->module, $this->document_type);
	}

	/**
	 * Return description of a module.
	 *
	 * @param  Translate $langs Lang object to use for output.
	 * @return string           Description.
	 */
	public function info(Translate $langs): string
	{
		return parent::info($langs);
	}

	/**
	 * Set attendants segment.
	 *
	 * @param  Odf       $odfHandler  Object builder odf library.
	 * @param  Translate $outputLangs Lang object to use for output.
	 * @param  array     $moreParam   More param (Object/user/etc).
	 *
	 * @throws Exception
	 */
	public function setRiskSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam)
	{
		global $conf;

		$usertmp               = new User($this->db);
		$project               = new Project($this->db);
		$DUProject             = new Project($this->db);
		$risk                  = new Risk($this->db);
		$digiriskelementobject = new DigiriskElement($this->db);
		$risks                 = $risk->fetchRisksOrderedByCotation($moreParam['gp_ut_id'], true, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

		$DUProject->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);

		$activeDigiriskElements = $digiriskelementobject->getActiveDigiriskElements($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS ? 1 : 0);
		$tasksSortedByRisk = $risk->getTasksWithFkRisk();

		for ($i = 1; $i <= 4; $i++ ) {
			$listlines = $odfHandler->setSegment('risk' . $i);
			if (is_array($risks) && ! empty($risks)) {
				foreach ($risks as $line) {
					$j++;
					if ($line->fk_element > 0 && in_array($line->fk_element, array_keys($activeDigiriskElements))) {
						$tmparray['actionPreventionUncompleted'] = "";
						$tmparray['actionPreventionCompleted']   = "";
						$lastEvaluation                          = $line->lastEvaluation;

						if ($lastEvaluation->cotation >= 0 && !empty($lastEvaluation) && is_object($lastEvaluation)) {
							$scale = $lastEvaluation->get_evaluation_scale();

							if ($scale == $i) {
								$element = $activeDigiriskElements[$line->fk_element];
								$linked_element = $activeDigiriskElements[$line->appliedOn];
								if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN) {
									$nomElement = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
									if ($line->fk_element != $line->appliedOn) {
										$nomElement .= "\n" . $outputLangs->trans('AppliedOn') . ' ' . $linked_element->ref . ' - ' . $linked_element->label;
									}
								} else {
									if ($linked_element->id > 0) {
										$nomElement = "\n" . $linked_element->ref . ' - ' . $linked_element->label;
									} else {
										$nomElement = "\n" . $element->ref . ' - ' . $element->label;
									}
								}

								$tmparray['nomElement']            = $nomElement;
								$tmparray['nomDanger']             = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $line->get_danger_category($line) . '.png';
								$tmparray['nomPicto']              = (!empty($conf->global->DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME) ? $line->get_danger_category_name($line) : ' ');
								$tmparray['identifiantRisque']     = $line->ref . ' - ' . $lastEvaluation->ref;
								$tmparray['quotationRisque']       = $lastEvaluation->cotation ?: 0;
								$tmparray['descriptionRisque']     = $line->description;
								$tmparray['commentaireEvaluation'] = $lastEvaluation->comment ? dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && (!empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation), 'dayreduceformat') . ': ' . $lastEvaluation->comment : '';

								$related_tasks = $tasksSortedByRisk[$line->id];
								if (!empty($related_tasks) && is_array($related_tasks)) {
									foreach ($related_tasks as $related_task) {
										if(is_object($related_task)) {
											if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS)) {
												$project->fetch($related_task->fk_project);
												if ($project->entity != $conf->entity) {
													$result = !empty($conf->mc->sharings['project']) ? in_array($project->entity, $conf->mc->sharings['project']) : 0;
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
														(($related_task->ref) ? $tmparray['actionPreventionCompleted'] .= $outputLangs->trans('Ref') . ' : ' . $related_task->ref . "\n" : '');
														(($responsible) ? $tmparray['actionPreventionCompleted'] .= $outputLangs->trans('Responsible') . ' : ' . $responsible . "\n" : '');
														$tmparray['actionPreventionCompleted'] .= $outputLangs->trans('DateStart') . ' : ';
														if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && !empty($related_task->date_start)) {
															$tmparray['actionPreventionCompleted'] .= dol_print_date(($related_task->date_start), 'dayreduceformat');
														} else {
															$tmparray['actionPreventionCompleted'] .= dol_print_date(($related_task->date_c), 'dayreduceformat');
														}
														if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && !empty($related_task->date_end)) {
															$tmparray['actionPreventionCompleted'] .= "\n" . $outputLangs->transnoentities('Deadline') . ' : ' . dol_print_date($related_task->date_end, 'dayreduceformat') . "\n";
														} else {
															$tmparray['actionPreventionCompleted'] .= ' - ' . $outputLangs->transnoentities('Deadline') . ' : ' . $outputLangs->trans('NoData') . "\n";
														}
														$tmparray['actionPreventionCompleted'] .= $outputLangs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $outputLangs, 1, 0, 0, $conf->currency) . "\n";
														(($AllInitiales) ? $tmparray['actionPreventionCompleted'] .= $outputLangs->trans('ContactsAction') . ' : ' . $AllInitiales . "\n" : '');
														(($related_task->label) ? $tmparray['actionPreventionCompleted'] .= $outputLangs->trans('Label') . ' : ' . $related_task->label . "\n" : '');
														(($related_task->description) ? $tmparray['actionPreventionCompleted'] .= $outputLangs->trans('Description') . ' : ' . $related_task->description . "\n" : '');
														$tmparray['actionPreventionCompleted'] .= "\n";
													} else {
														$tmparray['actionPreventionCompleted'] = $outputLangs->transnoentities('ActionPreventionCompletedTaskDone');
													}
												} else {
													(($related_task->ref) ? $tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('Ref') . ' : ' . $related_task->ref . "\n" : '');
													(($responsible) ? $tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('Responsible') . ' : ' . $responsible . "\n" : '');
													$tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('DateStart') . ' : ';
													if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && !empty($related_task->date_start)) {
														$tmparray['actionPreventionUncompleted'] .= dol_print_date(($related_task->date_start), 'dayreduceformat');
													} else {
														$tmparray['actionPreventionUncompleted'] .= dol_print_date(($related_task->date_c), 'dayreduceformat');
													}
													if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && !empty($related_task->date_end)) {
														$tmparray['actionPreventionUncompleted'] .= "\n" . $outputLangs->transnoentities('Deadline') . ' : ' . dol_print_date($related_task->date_end, 'dayreduceformat') . "\n";
													} else {
														$tmparray['actionPreventionUncompleted'] .= ' - ' . $outputLangs->transnoentities('Deadline') . ' : ' . $outputLangs->trans('NoData') . "\n";
													}
													$tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $outputLangs, 1, 0, 0, $conf->currency) . ' - ';
													$tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('DigiriskProgress') . ' : ' . ($task_progress ?: 0) . ' %' . "\n";
													(($AllInitiales) ? $tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('ContactsAction') . ' : ' . $AllInitiales . "\n" : '');
													(($related_task->label) ? $tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('Label') . ' : ' . $related_task->label . "\n" : '');
													(($related_task->description) ? $tmparray['actionPreventionUncompleted'] .= $outputLangs->trans('Description') . ' : ' . $related_task->description . "\n" : '');
													$tmparray['actionPreventionUncompleted'] .= "\n";
												}
											} else {
												$tmparray['actionPreventionUncompleted'] = $outputLangs->trans('NoTaskShared');
												$tmparray['actionPreventionCompleted'] = $outputLangs->trans('NoTaskShared');
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
									$tmparray['photoAssociee'] = $outputLangs->transnoentities('NoFileLinked');
								}

								unset($tmparray['object_fields']);

								complete_substitutions_array($tmparray, $outputLangs, $moreParam['object'], $line, "completesubstitutionarray_lines");

								foreach ($tmparray as $key => $val) {
									try {
										if ($key == 'photoAssociee') {
											if (file_exists($val)) {
												$listlines->setImage($key, $val);
											} else {
												$listlines->setVars($key, $outputLangs->trans('NoData'), true, 'UTF-8');
											}
										} elseif ($key == 'nomDanger') {
											if (file_exists($val)) {
												$listlines->setImage($key, $val);
											} else {
												$listlines->setVars($key, $outputLangs->trans('NoData'), true, 'UTF-8');
											}
										} elseif (empty($val) && $val != '0') {

											$listlines->setVars($key, $outputLangs->trans('NoData'), true, 'UTF-8');
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
				$tmparray['nomElement']                  = $outputLangs->trans('NoData');
				$tmparray['nomDanger']                   = $outputLangs->trans('NoData');
				$tmparray['nomPicto']                    = $outputLangs->trans('NoData');
				$tmparray['identifiantRisque']           = $outputLangs->trans('NoData');
				$tmparray['quotationRisque']             = $outputLangs->trans('NoData');
				$tmparray['descriptionRisque']           = $outputLangs->trans('NoDescriptionThere');
				$tmparray['commentaireEvaluation']       = $outputLangs->trans('NoRiskThere');
				$tmparray['actionPreventionUncompleted'] = $outputLangs->trans('NoTaskUnCompletedThere');
				$tmparray['actionPreventionCompleted']   = $outputLangs->trans('NoTaskCompletedThere');
				$tmparray['photoAssociee']               = $outputLangs->transnoentities('NoFileLinked');
				foreach ($tmparray as $key => $val) {
					try {
						if (empty($val)) {
							$listlines->setVars($key, $outputLangs->trans('NoData'), true, 'UTF-8');
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

	/**
	 * Set attendants segment.
	 *
	 * @param  Odf       $odfHandler  Object builder odf library.
	 * @param  Translate $outputLangs Lang object to use for output.
	 * @param  array     $moreParam   More param (Object/user/etc).
	 *
	 * @throws Exception
	 */
	public function setTaskSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam)
	{
		global $conf, $moduleNameLowerCase, $langs;

		// Get tasks.
		$foundTagForLines = 1;
		$tmpArray         = [];
		$now              = dol_now();
		try {
			$listLinesCur  = $odfHandler->setSegment('cur_task');
			$listLinesPrev = $odfHandler->setSegment('prev_task');
		} catch (OdfException $e) {
			// We may arrive here if tags for lines not present into template.
			$foundTagForLines = 0;
			$listLinesCur     = '';
			$listLinesPrev    = '';
			dol_syslog($e->getMessage());
		}

		$curativeActionTasks   = saturne_fetch_all_object_type('SaturneTask', '', '', 0, 0, ['customsql' => 'fk_task_parent = ' . $moreParam['curativeTaskId']]);
		$preventiveActionTasks = saturne_fetch_all_object_type('SaturneTask', '', '', 0, 0, ['customsql' => 'fk_task_parent = ' . $moreParam['preventiveTaskId']]);

		if ($foundTagForLines) {
			if (is_array($curativeActionTasks) && !empty($curativeActionTasks)) {
				foreach ($curativeActionTasks as $line) {
					$taskExecutive = $line->liste_contact(-1, 'internal', 0, 'TASKEXECUTIVE');

					$tmpArray['cur_task_ref']         = $line->ref;
					$tmpArray['cur_task_description'] = $line->description;

					$delay  = $line->datee > 0 ? round(($line->datee - $now) / 60 /60 / 24) : 0;
					$delay .= ' ' . ($delay > 1 ? $langs->trans('Days') : $langs->trans('Day'));

					$tmpArray['cur_task_resp']   = $taskExecutive[0]['lastname'] . ' ' . $taskExecutive[0]['firstname'];
					$tmpArray['cur_task_delay']  = $delay;
					$tmpArray['cur_task_budget'] = price($line->budget_amount,0, '', 1, -1, -1, 'auto');
					$this->setTmpArrayVars($tmpArray, $listLinesCur, $outputLangs);
				}
			} else {
				$tmpArray['cur_task_ref']         = '';
				$tmpArray['cur_task_description'] = '';
				$tmpArray['cur_task_resp']        = '';
				$tmpArray['cur_task_delay']       = '';
				$tmpArray['cur_task_budget']      = '';
				$this->setTmpArrayVars($tmpArray, $listLinesCur, $outputLangs);
			}
			$odfHandler->mergeSegment($listLinesCur);

			if (is_array($preventiveActionTasks) && !empty($preventiveActionTasks)) {
				foreach ($preventiveActionTasks as $line) {
					$tmpArray['prev_task_ref']         = $line->ref;
					$tmpArray['prev_task_description'] = $line->description;

					$delay  = $line->datee > 0 ? round(($line->datee - $now) / 60 /60 / 24) : 0;
					$delay .= ' ' . ($delay > 1 ? $langs->trans('Days') : $langs->trans('Day'));

					$tmpArray['prev_task_resp']   = $taskExecutive[0]['lastname'] . ' ' . $taskExecutive[0]['firstname'];;
					$tmpArray['prev_task_delay']  = $delay;
					$tmpArray['prev_task_budget'] = price($line->budget_amount,0, '', 1, -1, -1, 'auto');
					$this->setTmpArrayVars($tmpArray, $listLinesPrev, $outputLangs);
				}
			} else {
				$tmpArray['prev_task_ref']         = '';
				$tmpArray['prev_task_description'] = '';
				$tmpArray['prev_task_resp']        = '';
				$tmpArray['prev_task_delay']       = '';
				$tmpArray['prev_task_budget']      = '';
				$this->setTmpArrayVars($tmpArray, $listLinesPrev, $outputLangs);
			}
			$odfHandler->mergeSegment($listLinesPrev);
		}
	}

	/**
	 * Fill all odt tags for segments lines.
	 *
	 * @param  Odf       $odfHandler  Object builder odf library.
	 * @param  Translate $outputLangs Lang object to use for output.
	 * @param  array     $moreParam   More param (Object/user/etc).
	 *
	 * @return int                    1 if OK, <=0 if KO.
	 * @throws Exception
	 */
	public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
	{
		// Replace tags of lines.
		try {
			$this->setAttendantsSegment($odfHandler, $outputLangs, $moreParam);
			$this->setRiskSegment($odfHandler, $outputLangs, $moreParam);
			$this->setTaskSegment($odfHandler, $outputLangs, $moreParam);
		} catch (OdfException $e) {
			$this->error = $e->getMessage();
			dol_syslog($this->error, LOG_WARNING);
			return -1;
		}
		return 0;
	}

	/**
	 * Function to build a document on disk.
	 *
	 * @param  SaturneDocuments $objectDocument  Object source to build document.
	 * @param  Translate        $outputLangs     Lang object to use for output.
	 * @param  string           $srcTemplatePath Full path of source filename for generator using a template file.
	 * @param  int              $hideDetails     Do not show line details.
	 * @param  int              $hideDesc        Do not show desc.
	 * @param  int              $hideRef         Do not show ref.
	 * @param  array            $moreParam       More param (Object/user/etc).
	 * @return int                               1 if OK, <=0 if KO.
	 * @throws Exception
	 */
	public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
	{
		require_once __DIR__ . '/../../../../../../saturne/class/task/saturnetask.class.php';

		global $conf, $db, $langs;

		$object           = $moreParam['object'];
		$accident         = new Accident($db);
		$accidentMetadata = new AccidentMetaData($db);
		$victim           = new User($db);
		$signatory        = new SaturneSignature($this->db, $this->module, $object->element);
		$totalBudget      = 0;

		$accident->fetch($object->fk_accident);
		$accidentMetadata->fetch(0, '', 'AND status = 1 AND fk_accident = ' . $accident->id);
		$victim->fetch($accident->fk_user_victim);

		$curativeActionTask   = saturne_fetch_all_object_type('SaturneTask', '', '', 0, 0, ['customsql' => 'fk_task_parent = ' . $object->fk_task . ') AND (label LIKE "%- T1 -%"']);
		$curativeActionTask   = array_pop($curativeActionTask);
		$preventiveActionTask = saturne_fetch_all_object_type('SaturneTask', '', '', 0, 0, ['customsql' => 'fk_task_parent = ' . $object->fk_task . ') AND (label LIKE "%- T2 -%"']);
		$preventiveActionTask = array_pop($preventiveActionTask);
		$totalCATask          = $curativeActionTask->hasChildren();
		$totalPATask          = $preventiveActionTask->hasChildren();
		$totalBudget          = getRecursiveTaskBudget($object->fk_task);

		$moreParam['curativeTaskId']   = $curativeActionTask->id;
		$moreParam['preventiveTaskId'] = $preventiveActionTask->id;

		$tmpArray['investigation_date_start'] = dol_print_date($object->date_start, 'dayhour', 'tzuser');
		$tmpArray['investigation_date_end']   = dol_print_date($object->date_end, 'dayhour', 'tzuser');
		$tmpArray['total_curative_action']    = $totalCATask > 0 ? $totalCATask :$langs->trans('None');
		$tmpArray['total_preventive_action']  = $totalPATask > 0 ? $totalPATask : $langs->trans('None');
		$tmpArray['total_planned_budget']     = price($totalBudget,0, '', 1, -1, -1, 'auto');

		$signatoriesArray = $signatory->fetchSignatories($moreParam['object']->id, $moreParam['object']->element);
		if (!empty($signatoriesArray) && is_array($signatoriesArray)) {
			$tmpArray['attendants_number'] = count($signatoriesArray);
		}

		$tmpArray['mycompany_name']    = $conf->global->MAIN_INFO_SOCIETE_NOM;
		$tmpArray['mycompany_siret']   = $conf->global->MAIN_INFO_SIRET;
		$tmpArray['mycompany_address'] = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
		$tmpArray['mycompany_contact'] = $conf->global->MAIN_INFO_SOCIETE_MANAGERS;
		$tmpArray['mycompany_mail']    = $conf->global->MAIN_INFO_SOCIETE_MAIL;
		$tmpArray['mycompany_phone']   = $conf->global->MAIN_INFO_SOCIETE_PHONE;

		$tmpArray['victim_lastname']        = $victim->lastname;
		$tmpArray['victim_firstname']       = $victim->firstname;
		$tmpArray['seniority_at_post']      = $object->seniority_at_post . ' ' . ($object->seniority_at_post <= 1 ? $langs->trans('Day') : $langs->trans('Days'));
		$tmpArray['victim_date_employment'] = dol_print_date($victim->dateemployment, 'day', 'tzuser');

		$tmpArray['accident_date'] = dol_print_date($accident->accident_date, 'day');
		$tmpArray['accident_hour'] = dol_print_date($accident->accident_date, 'hour');
		$tmpArray['accident_day']  = $langs->trans(date('l', $accident->accident_date));

		if ($accident->external_accident == 1) {
			if ($accident->fk_element > 0) {
				$element = new DigiriskElement($db);
				$element->fetch($accident->fk_element);
				$tmpArray['gp_ut']     = $element->ref . ' - ' . $element->label;
				$moreParam['gp_ut_id'] = $accident->fk_element;
			} else {
				$element = new DigiriskStandard($db);
				$element->fetch($accident->fk_standard);
				$tmpArray['gp_ut']     = $element->ref . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
				$moreParam['gp_ut_id'] = $accident->fk_standard;
			}
		} else if ($accident->external_accident == 2) {
			$societe = new Societe($db);
			$societe->fetch($accident->fk_soc);
			$tmpArray['gp_ut'] = $societe->name;
		} else {
			$tmpArray['gp_ut'] = $accident->accident_location;
		}

		$tmpArray['victim_skills']        = $object->victim_skills;
		$tmpArray['collective_equipment'] = $object->collective_equipment;
		$tmpArray['individual_equipment'] = $object->individual_equipment;
		$tmpArray['circumstances']        = $object->circumstances;
		$tmpArray['public_note']          = $object->note_public;
		$tmpArray['relative_location']    = $accidentMetadata->relative_location;

		$pathPhoto                        = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident_investigation/'. $object->ref . '/causality_tree/thumbs/';
		$causalityTreePath                = $pathPhoto . getThumbName($object->causality_tree, 'medium');
		$tmpArray['causality_tree_photo'] = $causalityTreePath;

		$moreParam['tmparray'] = $tmpArray;

		return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
	}
}
