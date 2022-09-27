<?php
// BEGIN PHP TEMPLATE digiriskdolibarr_fillriskdata.tpl.php
for ($i = 1; $i <= 4; $i++ ) {
	$listlines = $odfHandler->setSegment('risk' . $i);
	if (is_array($risks) && ! empty($risks)) {
		$digiriskelementobject->fetch($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);
		$trashList = $digiriskelementobject->getMultiEntityTrashList();
		foreach ($risks as $line) {
			if ( ! in_array($line->fk_element, $trashList) && $line->fk_element > 0) {
				$tmparray['actionPreventionUncompleted'] = "";
				$tmparray['actionPreventionCompleted']   = "";
				$lastEvaluation                          = $line->lastEvaluation;

				if ($lastEvaluation->cotation > 0 && !empty($lastEvaluation) && is_object($lastEvaluation)) {
					$scale = $lastEvaluation->get_evaluation_scale();

					if ($scale == $i) {
						$element = new DigiriskElement($this->db);
						$linked_element = new DigiriskElement($this->db);
						$element->fetch($line->fk_element);
						$linked_element->fetch($line->appliedOn);

						if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN) {
							$nomElement = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
							if ($line->fk_element != $line->appliedOn) {
								$nomElement .= "\n" . $langs->trans('AppliedOn') . ' ' . $linked_element->ref . ' - ' . $linked_element->label;
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
						$tmparray['nomPicto']              = $line->get_danger_category_name($line);
						$tmparray['identifiantRisque']     = $line->ref . ' - ' . $lastEvaluation->ref;
						$tmparray['quotationRisque']       = $lastEvaluation->cotation ?: '0';
						$tmparray['descriptionRisque']     = $line->description;
						$tmparray['commentaireEvaluation'] = $lastEvaluation->comment ? dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && (!empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation), 'dayreduceformat') . ': ' . $lastEvaluation->comment : '';

						$related_tasks = $line->get_related_tasks($line);
						$usertmp = new User($this->db);

						if (!empty($related_tasks) && is_array($related_tasks)) {
							foreach ($related_tasks as $related_task) {
								$AllInitiales = '';
								$related_task_contact_ids = $related_task->getListContactId();
								if (!empty($related_task_contact_ids) && is_array($related_task_contact_ids)) {
									foreach ($related_task_contact_ids as $related_task_contact_id) {
										$usertmp->fetch($related_task_contact_id);
										$AllInitiales .= strtoupper(str_split($usertmp->firstname, 1)[0] . str_split($usertmp->lastname, 1)[0] . ',');
									}
								}

								$contactslistinternal = $related_task->liste_contact(-1, 'internal');

								if (!empty($contactslistinternal) && is_array($contactslistinternal)) {
									$responsible = '';
									foreach ($contactslistinternal as $contactlistinternal) {
										if ($contactlistinternal['code'] == 'TASKEXECUTIVE') {
											$responsible .= $contactlistinternal['firstname'] . ' ' . $contactlistinternal['lastname'] . ', ';
										}
									}
								}

								if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
									$tmparray = $related_task->getSummaryOfTimeSpent();
									if ($tmparray['total_duration'] > 0 && !empty($related_task->planned_workload)) {
										$task_progress = round($tmparray['total_duration'] / $related_task->planned_workload * 100, 2);
									} else {
										$task_progress = 0;
									}
								} else {
									$task_progress = $related_task->progress;
								}

								if ($task_progress == 100) {
									if ($conf->global->DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE > 0) {
										$tmparray['actionPreventionCompleted'] .= $langs->trans('Ref') . ' : ' . ($related_task->ref ?: $langs->trans('NoData')) . "\n";
										$tmparray['actionPreventionCompleted'] .= $langs->trans('Responsible') . ' : ' . ($responsible ?: $langs->trans('NoData')) . "\n";
										$tmparray['actionPreventionCompleted'] .= $langs->trans('DateStart') . ' : ' . dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && (!empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c), 'dayreduceformat') . ' - ' . $langs->trans('Deadline') . ' : ' . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && (!empty($related_task->date_end))) ? ' - ' . dol_print_date($related_task->date_end, 'dayreduceformat') : $langs->trans('NoData')) . "\n";
										if (strcmp($related_task->budget_amount, '')) {
											$tmparray['actionPreventionCompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . "\n";
										} else {
											$tmparray['actionPreventionCompleted'] .= $langs->trans('Budget') . ' : ' . $langs->trans('NoData') . "\n";
										}
										$tmparray['actionPreventionCompleted'] .= $langs->trans('ContactsAction') . ' : ' . ($AllInitiales ?: $langs->trans('NoData')) . "\n";
										$tmparray['actionPreventionCompleted'] .= $langs->trans('Label') . ' : ' . ($related_task->label ?: $langs->trans('NoData')) . "\n";
										$tmparray['actionPreventionCompleted'] .= $langs->trans('Description') . ' : ' . ($related_task->description ?: $langs->trans('NoData')) . "\n\n";
									} else {
										$tmparray['actionPreventionCompleted'] = $langs->transnoentities('ActionPreventionCompletedTaskDone');
									}
								} else {
									$tmparray['actionPreventionUncompleted'] .= $langs->trans('Ref') . ' : ' . ($related_task->ref ?: $langs->trans('NoData')) . "\n";
									$tmparray['actionPreventionUncompleted'] .= $langs->trans('Responsible') . ' : ' . ($responsible ?: $langs->trans('NoData')) . "\n";
									$tmparray['actionPreventionUncompleted'] .= $langs->trans('DateStart') . ' : ' . dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && (!empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c), 'dayreduceformat') . ' - ' . $langs->trans('Deadline') . ' : ' . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && (!empty($related_task->date_end))) ? ' - ' . dol_print_date($related_task->date_end, 'dayreduceformat') : $langs->trans('NoData')) . "\n";
									if (strcmp($related_task->budget_amount, '')) {
										$tmparray['actionPreventionUncompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . ' - ';
									} else {
										$tmparray['actionPreventionUncompleted'] .= $langs->trans('Budget') . ' : ' . $langs->trans('NoData') . ' - ';
									}
									$tmparray['actionPreventionUncompleted'] .= $langs->trans('DigiriskProgress') . ' : ' . ($task_progress ?: 0) . ' %' . "\n";
									$tmparray['actionPreventionUncompleted'] .= $langs->trans('ContactsAction') . ' : ' . ($AllInitiales ?: $langs->trans('NoData')) . "\n";
									$tmparray['actionPreventionUncompleted'] .= $langs->trans('Label') . ' : ' . ($related_task->label ?: $langs->trans('NoData')) . "\n";
									$tmparray['actionPreventionUncompleted'] .= $langs->trans('Description') . ' : ' . ($related_task->description ?: $langs->trans('NoData')) . "\n\n";
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

						complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
						// Call the ODTSubstitutionLine hook
						$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
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
								dol_syslog($e->getMessage(), LOG_INFO);
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
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
				dol_syslog($e->getMessage(), LOG_INFO);
			}
		}
		$listlines->merge();
	}
	$odfHandler->mergeSegment($listlines);
}
// END PHP TEMPLATE digiriskdolibarr_fillriskdata.tpl.php
