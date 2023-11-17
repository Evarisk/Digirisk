<?php
if ( ! $error && $action == 'add' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$fk_element  = GETPOST('id');
	$riskComment = $data['description'];
	$cotation    = $data['cotation'];
	$method      = $data['method'];
	$category    = $data['category'];
	$photo       = $data['photo'];

	if ($riskComment !== 'undefined') {
		$risk->description = $riskComment;
	}

	$risk->fk_element = $fk_element ?: 0;
	$risk->fk_projet  = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
	$risk->category   = $category;
	$risk->ref        = $refRiskMod->getNextValue($risk);
	$risk->status     = 1;

	if ( ! $error) {
		$result = $risk->create($user);

		if ($result > 0) {
			$evaluationComment  = $data['comment'];
			$riskAssessmentDate = $data['date'];

			$evaluation->photo               = $photo;
			$evaluation->cotation            = $cotation;
			$evaluation->fk_risk             = $risk->id;
			$evaluation->status              = 1;
			$evaluation->method              = $method;
			$evaluation->ref                 = $refEvaluationMod->getNextValue($evaluation);
			$evaluation->comment             = $evaluationComment;
			$evaluation->date_riskassessment = $riskAssessmentDate != 'undefined' ? strtotime(preg_replace('/\//', '-', $riskAssessmentDate)) : dol_now();

			if ($method == 'advanced') {
				$formation  = $data['criteres']['formation'];
				$protection = $data['criteres']['protection'];
				$occurrence = $data['criteres']['occurrence'];
				$gravite    = $data['criteres']['gravite'];
				$exposition = $data['criteres']['exposition'];

				$evaluation->formation  = $formation;
				$evaluation->protection = $protection;
				$evaluation->occurrence = $occurrence;
				$evaluation->gravite    = $gravite;
				$evaluation->exposition = $exposition;
			}

			$pathToTmpPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RA0/';
			$files          = dol_dir_list($pathToTmpPhoto);

			if ( ! empty($files)) {
				foreach ($files as $file) {
					$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $evaluation->ref;

					if ( ! is_dir($pathToEvaluationPhoto)) {
						mkdir($pathToEvaluationPhoto);
					}
					if ( ! is_file($pathToEvaluationPhoto . '/' . $file['name'])) {
						copy($file['fullname'], $pathToEvaluationPhoto . '/' . $file['name']);
					}

					global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
					$destfull = $pathToEvaluationPhoto . '/' . $file['name'];

					// Create thumbs
					$imgThumbLarge = vignette($destfull, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE, '_large', 50, "thumbs");
					$imgThumbMedium = vignette($destfull, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM, '_medium', 50, "thumbs");
					$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
					$imgThumbMini  = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
					unlink($file['fullname']);
				}
			}
			$filesThumbs = dol_dir_list($pathToTmpPhoto . '/thumbs/');
			if ( ! empty($filesThumbs)) {
				foreach ($filesThumbs as $fileThumb) {
					unlink($fileThumb['fullname']);
				}
			}

			$result2 = $evaluation->create($user, false);

			if ($result2 > 0) {
				$tasktitle = $data['task'];
				$dateStart = $data['dateStart'];
				$hourStart = $data['hourStart'];
				$minStart  = $data['minStart'];
				$dateEnd   = $data['dateEnd'];
				$hourEnd   = $data['hourEnd'];
				$minEnd    = $data['minEnd'];
				$budget    = $data['budget'];
				if ( ! empty($tasktitle) && $tasktitle !== 'undefined') {
					$extrafields->fetch_name_optionals_label($task->table_element);

					$task->ref                              = $refTaskMod->getNextValue('', $task);
					$task->label                            = $tasktitle;
					$task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
					$task->date_c                           = dol_now();
					if (!empty($dateStart)) {
						$task->date_start = strtotime(preg_replace('/\//', '-', $dateStart));
						$task->date_start = dol_time_plus_duree($task->date_start, $hourStart, 'h');
						$task->date_start = dol_time_plus_duree($task->date_start, $minStart, 'i');
					} else {
						$task->date_start = dol_now('tzuser');
					}
					if (!empty($dateEnd)) {
						$task->date_end = strtotime(preg_replace('/\//', '-', $dateEnd));
						$task->date_end = dol_time_plus_duree($task->date_end, $hourEnd, 'h');
						$task->date_end = dol_time_plus_duree($task->date_end, $minEnd, 'i');
					}
					$task->budget_amount                    = $budget;
					$task->array_options['options_fk_risk'] = $risk->id;

					$result3 = $task->create($user, true);

					if ($result3 > 0) {
						if (!empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_CREATE)) {
							$task->call_trigger('TASK_CREATE', $user);
						}

						$DUProject->add_contact($user->id, $conf->global->DIGIRISKDOLIBARR_DEFAULT_PROJECT_CONTACT_TYPE, 'internal');
						$task->add_contact($user->id, $conf->global->DIGIRISKDOLIBARR_DEFAULT_TASK_CONTACT_TYPE, 'internal');

						// Creation risk + evaluation + task OK
						$urltogo = str_replace('__ID__', $result3, $backtopage);
						$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
						header("Location: " . $urltogo);
					} else {
						// Creation task KO
						if ( ! empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
						else setEventMessages($task->error, null, 'errors');
					}
				}
			} else {
				// Creation evaluation KO
				if ( ! empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
				else setEventMessages($evaluation->error, null, 'errors');
			}
		} else {
			// Creation risk KO
			if ( ! empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
			else setEventMessages($risk->error, null, 'errors');
		}
	}
}

if ( ! $error && $action == 'saveRisk' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$riskID      = $data['riskID'];
	$description = $data['comment'];
	$category    = $data['category'];

	$risk->fetch($riskID);
	if ($data['newParent'] > 0) {
		$risk->fk_element = $data['newParent'];
	}
	$risk->description = $description;
	$risk->category    = $category;

	$result = $risk->update($user);

	if ($result > 0) {
		// Update risk OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
	} else {
		// Update risk KO
		if ( ! empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
		else setEventMessages($risk->error, null, 'errors');
	}
}

if ( ! $error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
	if ( ! empty($toselect)) {

		foreach ($toselect as $toselectedid) {
			$riskAssessmentList = $evaluation->fetchFromParent($toselectedid, 0);
			$risk->fetch($toselectedid);

			if (is_array($riskAssessmentList) && ! empty($riskAssessmentList)) {
				foreach ($riskAssessmentList as $riskRiskAssessment) {
					$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $riskRiskAssessment->ref;

					if ( file_exists($pathToEvaluationPhoto) && ! (empty($riskRiskAssessment->ref))) {
						$files = dol_dir_list($pathToEvaluationPhoto);
						if ( ! empty($files)) {
							foreach ($files as $file) {
								if (is_file($file['fullname'])) {
									unlink($file['fullname']);
								}
							}
						}

						$files = dol_dir_list($pathToEvaluationPhoto . '/thumbs');
						if ( ! empty($files)) {
							foreach ($files as $file) {
								unlink($file['fullname']);
							}
						}
						dol_delete_dir($pathToEvaluationPhoto . '/thumbs');
						dol_delete_dir($pathToEvaluationPhoto);

                    }
                    $riskRiskAssessment->delete($user, true);
                }
			}
            $result = $risk->delete($user);

            if ($result > 0) {
				setEventMessages($langs->trans('RiskDeleted', $risk->ref), null);
			} else {
				// Delete risk KO
				if ( ! empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
				else setEventMessages($risk->error, null, 'errors');
			}
		}

		// Delete risk OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}
}

if ( ! $error && $action == 'addEvaluation' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$evaluationComment  = $data['comment'];
	$riskAssessmentDate = $data['date'];
	$riskID             = $data['riskId'];
	$cotation           = $data['cotation'];
	$method             = $data['method'];
	$photo              = $data['photo'];

	$risktmp = new Risk($db);
	$risktmp->fetch($riskID);
	$risk->fetch($riskID);

	$evaluation->photo               = $photo;
	$evaluation->cotation            = $cotation;
	$evaluation->fk_risk             = $risk->id;
	$evaluation->status              = 1;
	$evaluation->method              = $method;
	$evaluation->ref                 = $refEvaluationMod->getNextValue($evaluation);
	$evaluation->comment             = $evaluationComment;
	$evaluation->date_riskassessment = strtotime(preg_replace('/\//', '-', $riskAssessmentDate));

	if ($method == 'advanced') {
		$formation  = $data['criteres']['formation'];
		$protection = $data['criteres']['protection'];
		$occurrence = $data['criteres']['occurrence'];
		$gravite    = $data['criteres']['gravite'];
		$exposition = $data['criteres']['exposition'];

		$evaluation->formation  = $formation;
		$evaluation->protection = $protection;
		$evaluation->occurrence = $occurrence;
		$evaluation->gravite    = $gravite;
		$evaluation->exposition = $exposition;
	}

	$pathToTmpPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RA0/' . $risktmp->ref;
	$files          = dol_dir_list($pathToTmpPhoto);

	if ( ! empty($files)) {
		foreach ($files as $file) {
			$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $evaluation->ref;

			mkdir($pathToEvaluationPhoto);
			copy($file['fullname'], $pathToEvaluationPhoto . '/' . $file['name']);

			global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
			$destfull = $pathToEvaluationPhoto . '/' . $file['name'];

			// Create thumbs
			// We can't use $object->addThumbs here because there is no $object known
			// Used on logon for example
			$imgThumbLarge = vignette($destfull, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE, '_large', 50, "thumbs");
			$imgThumbMedium = vignette($destfull, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM, '_medium', 50, "thumbs");
			$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
			// Create mini thumbs for image (Ratio is near 16/9)
			// Used on menu or for setup page for example
			$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
			unlink($file['fullname']);
		}
	}
	$filesThumbs = dol_dir_list($pathToTmpPhoto . '/thumbs/');
	if ( ! empty($filesThumbs)) {
		foreach ($filesThumbs as $fileThumb) {
			unlink($fileThumb['fullname']);
		}
	}

	$result = $evaluation->create($user);

	if ($result > 0) {
		// Creation evaluation OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
	} else {
		// Creation evaluation KO
		if ( ! empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
		else setEventMessages($evaluation->error, null, 'errors');
	}
}

if ( ! $error && $action == 'saveEvaluation' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$evaluationID       = $data['evaluationID'];
	$cotation           = $data['cotation'];
	$method             = $data['method'];
	$evaluationComment  = $data['comment'];
	$riskAssessmentDate = $data['date'];

	$evaluation->fetch($evaluationID);

	$evaluation->cotation            = $cotation;
	$evaluation->method              = $method;
	$evaluation->comment             = $evaluationComment;
	$evaluation->date_riskassessment = strtotime(preg_replace('/\//', '-', $riskAssessmentDate));

	if ($method == 'advanced') {
		$formation  = $data['criteres']['formation'];
		$protection = $data['criteres']['protection'];
		$occurrence = $data['criteres']['occurrence'];
		$gravite    = $data['criteres']['gravite'];
		$exposition = $data['criteres']['exposition'];

		$evaluation->formation  = $formation;
		$evaluation->protection = $protection;
		$evaluation->occurrence = $occurrence;
		$evaluation->gravite    = $gravite;
		$evaluation->exposition = $exposition;
	}
	$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';

	$result = $evaluation->update($user);

	if ($result > 0) {
		// Update evaluation OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
	} else {
		// Update evaluation KO
		if ( ! empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
		else setEventMessages($evaluation->error, null, 'errors');
	}
}

if ( ! $error && $action == "deleteEvaluation" && $permissiontodelete) {
    $evaluationId = GETPOST('deletedEvaluationId');

    $evaluation->fetch($evaluationId);

    $pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $evaluation->ref;
    dol_delete_dir_recursive($pathToEvaluationPhoto);

    $previousEvaluation = $evaluation;
    $result             = $evaluation->delete($user, false, false);

    if ($result > 0) {
        $previousEvaluation->updatePreviousRiskAssessmentStatus($user, $evaluation->fk_risk);
        // Delete evaluation OK
        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
        exit;
    } elseif (!empty($evaluation->errors)) {
        // Delete evaluation KO
        setEventMessages('', $evaluation->errors, 'errors');
    } else {
        setEventMessages($evaluation->error, [], 'errors');
    }
}

if ( ! $error && $action == 'addRiskAssessmentTask' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$riskID    = $data['riskToAssign'];
	$tasktitle = $data['tasktitle'];
	$dateStart = $data['dateStart'];
	$hourStart = $data['hourStart'];
	$minStart  = $data['minStart'];
	$dateEnd   = $data['dateEnd'];
	$hourEnd   = $data['hourEnd'];
	$minEnd    = $data['minEnd'];
	$budget    = $data['budget'];

	$extrafields->fetch_name_optionals_label($task->table_element);

	$task->ref        = $refTaskMod->getNextValue('', $task);
	$task->label      = $tasktitle;
	$task->fk_project = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
	$task->datec     = dol_now();
	if (!empty($dateStart)) {
		$task->date_start = strtotime(preg_replace('/\//', '-', $dateStart));
		$task->date_start = dol_time_plus_duree($task->date_start, $hourStart, 'h');
		$task->date_start = dol_time_plus_duree($task->date_start, $minStart, 'i');
	} else {
		$task->date_start = dol_now('tzuser');
	}
	if (!empty($dateEnd)) {
		$task->date_end = strtotime(preg_replace('/\//', '-', $dateEnd));
		$task->date_end = dol_time_plus_duree($task->date_end, $hourEnd, 'h');
		$task->date_end = dol_time_plus_duree($task->date_end, $minEnd, 'i');
	}
	$task->budget_amount                    = $budget;
	$task->fk_task_parent                   = 0;
	$task->array_options['options_fk_risk'] = $riskID;

	$result = $task->create($user, true);

	if ($result > 0) {
		if (!empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_CREATE)) $task->call_trigger('TASK_CREATE', $user);
		// Creation task OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Delete task KO
		header('HTTP/1.1 500 Internal Server Booboo');
		die(json_encode(array('message' => html_entity_decode($langs->transnoentities($task->errors[0])), 'code' => '1339')));
	}
}

if ( ! $error && $action == 'saveRiskAssessmentTask' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$riskAssessmentTaskID = $data['riskAssessmentTaskID'];
	$tasktitle            = $data['tasktitle'];
	$dateStart            = $data['dateStart'];
	$hourStart            = $data['hourStart'];
	$minStart             = $data['minStart'];
	$dateEnd              = $data['dateEnd'];
	$hourEnd              = $data['hourEnd'];
	$minEnd               = $data['minEnd'];
	$budget               = $data['budget'];
	$taskProgress         = $data['taskProgress'];

	$task->fetch($riskAssessmentTaskID);

	$task->label         = $tasktitle;

	if (!empty($dateStart)) {
		$task->date_start = strtotime(preg_replace('/\//', '-', $dateStart));
		$task->date_start = dol_time_plus_duree($task->date_start, $hourStart, 'h');
		$task->date_start = dol_time_plus_duree($task->date_start, $minStart, 'i');
	} else {
		$task->date_start = dol_now('tzuser');
	}
	if (!empty($dateEnd)) {
		$task->date_end = strtotime(preg_replace('/\//', '-', $dateEnd));
		$task->date_end = dol_time_plus_duree($task->date_end, $hourEnd, 'h');
		$task->date_end = dol_time_plus_duree($task->date_end, $minEnd, 'i');
	}
	$task->budget_amount = is_int($budget) ? $budget : ($task->budget ?? 0);

	if ($taskProgress == 1) {
		$task->progress = 100;
	} else {
		$task->progress = 0;
	}

	$result = $task->update($user, empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_MODIFY));

	if ($result > 0) {
		// Update task OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Delete task KO
		header('HTTP/1.1 500 Internal Server Booboo');
		die(json_encode(array('message' => html_entity_decode($langs->transnoentities($task->errors[0])), 'code' => '1338')));
	}
}

if ( ! $error && $action == "deleteRiskAssessmentTask" && $permissiontodelete) {
	$deleteRiskAssessmentTaskId = GETPOST('deletedRiskAssessmentTaskId');

	$task->fetch($deleteRiskAssessmentTaskId);

	$result = $task->delete($user, empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_DELETE));

	if ($result > 0) {
		// Delete task OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Delete task KO
		header('HTTP/1.1 500 Internal Server Booboo');
		die(json_encode(array('message' => $langs->transnoentities($task->error), 'code' => '1337')));
	}
}

if ( ! $error && $action == 'addRiskAssessmentTaskTimeSpent' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$taskID   = $data['taskID'];
	$date     = $data['date'];
	$hour     = $data['hour'];
	$min      = $data['min'];
	$comment  = $data['comment'];
	$duration = $data['duration'];

	$task->fetch($taskID);

	if (!empty($date)) {
		$task->timespent_date = strtotime(preg_replace('/\//', '-', $date));
		$task->timespent_date = dol_time_plus_duree($task->timespent_date, $hour, 'h');
		$task->timespent_date = dol_time_plus_duree($task->timespent_date, $min, 'i');
	} else {
		$task->timespent_date = dol_now('tzuser');
	}
	$task->timespent_note     = $comment;
	$task->timespent_duration = $duration * 60;
	$task->timespent_fk_user  = $user->id;

	$result = $task->addTimeSpent($user, empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_CREATE));

	if ($result > 0) {
		// Creation task time spent OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Creation task time spent KO
		if ( ! empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
		else setEventMessages($task->error, null, 'errors');
	}
}

if ( ! $error && $action == 'saveRiskAssessmentTaskTimeSpent' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$taskID 					   = $data['taskID'];
	$riskAssessmentTaskTimeSpentID = $data['riskAssessmentTaskTimeSpentID'];
	$date                          = $data['date'];
	$hour                          = $data['hour'];
	$min                           = $data['min'];
	$comment                       = $data['comment'];
	$duration                      = $data['duration'];

	$task->fetchTimeSpent($riskAssessmentTaskTimeSpentID);
	$task->fetch($taskID);

	if (!empty($date)) {
		$task->timespent_datehour = strtotime(preg_replace('/\//', '-', $date));
		$task->timespent_datehour = dol_time_plus_duree($task->timespent_datehour, $hour, 'h');
		$task->timespent_datehour = dol_time_plus_duree($task->timespent_datehour, $min, 'i');
	}
	$task->timespent_note     =  $comment;
	$task->timespent_duration = $duration * 60;

	$result = $task->updateTimeSpent($user, empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_MODIFY));

	if ($result > 0) {
		// Update task time spent OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Update task time spent KO
		if ( ! empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
		else setEventMessages($task->error, null, 'errors');
	}
}

if ( ! $error && $action == "deleteRiskAssessmentTaskTimeSpent" && $permissiontodelete) {
	$deleteRiskAssessmentTaskTimeSpentId = GETPOST('deletedRiskAssessmentTaskTimeSpentId');

	$task->fetchTimeSpent($deleteRiskAssessmentTaskTimeSpentId);
	$task->fetch($task->id);

	$result = $task->delTimeSpent($user, false);

	if ($result > 0) {
		// Delete task time spent OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Delete task time spent KO
		if ( ! empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
		else setEventMessages($task->error, null, 'errors');
	}
}

if ( ! $error && $action == 'checkTaskProgress' && $permissiontoadd) {
	$data = json_decode(file_get_contents('php://input'), true);

	$riskAssessmentTaskID = $data['riskAssessmentTaskID'];
	$taskProgress         = $data['taskProgress'];

	$task->fetch($riskAssessmentTaskID);

	if ($taskProgress == 1) {
		$task->progress = 100;
	} else {
		$task->progress = 0;
	}

	$result = $task->update($user, empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_DELETE));

	if ($result > 0) {
		// Update task OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Update task KO
		if ( ! empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
		else setEventMessages($task->error, null, 'errors');
	}
}

// Action import shared risks
if ($action == 'confirm_import_shared_risks' && $confirm == 'yes') {

	$digiriskelementtmp = new DigiriskElement($db);

//	$AllSharingsRisks = $conf->mc->sharings['risk'];
//
//	foreach ($AllSharingsRisks as $Allsharingsrisk) {
//		$filter .= $Allsharingsrisk . ',';
//	}
//
//	$filter = rtrim($filter, ',');

	$allrisks = $risk->fetchAll('', '', 0, 0, array('customsql' => 'status > 0 AND entity NOT IN (' . $conf->entity . ') AND fk_element > 0'));

	foreach ($allrisks as $key => $risks) {
		$digiriskelementtmp->fetch($risks->fk_element);
		$options['import_shared_risks'][$risks->id] = GETPOST($risks->id);

		if ($options['import_shared_risks'][$risks->id] == 'on') {
			if ($object->id > 0) {
				$object->element = $digiriskelementtmp->element;
				$result = $object->add_object_linked('digiriskdolibarr_' . $risk->element, $risks->id);
				if ($result > 0) {
					$risks->applied_on = $object->id;
					$risks->call_trigger('RISK_IMPORT', $user);
					continue;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
		}
	}

	$urltogo = str_replace('__ID__', $object->id, $backtopage);
	$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
	header("Location: " . $urltogo);
	exit;
}

if (! $error && $action == 'unlinkSharedRisk' && $permissiontodelete) {
	$data = json_decode(file_get_contents('php://input'), true);

	$risk_id = $data['riskID'];

	$risk            = new Risk($db);
	$digiriskelement = new DigiriskElement($db);

	$risk->fetch($risk_id);
	$digiriskelement->fetch($risk->fk_element);

	$result = deleteObjectLinkedDigirisk($digiriskelement, $risk->id, 'digiriskdolibarr_' . $risk->element, $object->id, 'digiriskdolibarr_' . $digiriskelement->element);

	if ($result > 0) {
		// Unlink shared risk OK
		$risk->applied_on = $object->id;
		$risk->call_trigger('RISK_UNLINK', $user);
		$urltogo = str_replace('__ID__', $object->id, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	} else {
		// Unlink shared risk KO
		if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
		else setEventMessages($object->error, null, 'errors');
	}
}
