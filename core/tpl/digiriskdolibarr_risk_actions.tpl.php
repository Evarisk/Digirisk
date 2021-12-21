<?php
if (!$error && $action == 'add' && $permissiontoadd) {

	$data        = json_decode(file_get_contents('php://input'), true);

	$fk_element  = GETPOST('id');
	$riskComment = $data['description'];
	$cotation    = $data['cotation'];
	$method      = $data['method'];
	$category    = $data['category'];
	$photo       = $data['photo'];

	if ($riskComment !== 'undefined') {
		$risk->description = $riskComment;
	}

	$risk->fk_element  = $fk_element ?: 0;
	$risk->fk_projet   = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
	$risk->category    = $category;
	$risk->ref         = $refRiskMod->getNextValue($risk);
	$risk->status      = 1;

	if (!$error) {
		$result = $risk->create($user);

		if ($result > 0) {
			$lastRiskAdded = $risk->ref;

			$evaluationComment  = $data['comment'];
			$riskAssessmentDate = $data['date'];

			$evaluation->photo               = $photo;
			$evaluation->cotation            = $cotation;
			$evaluation->fk_risk             = $risk->id;
			$evaluation->status              = 1;
			$evaluation->method              = $method;
			$evaluation->ref                 = $refEvaluationMod->getNextValue($evaluation);
			$evaluation->comment             = $evaluationComment;
			$evaluation->date_riskassessment = $riskAssessmentDate != 'undefined' ? strtotime(preg_replace('/\//', '-',$riskAssessmentDate)) : dol_now();

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

			$pathToTmpPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RK0/';
			$files = dol_dir_list($pathToTmpPhoto);

			if (!empty($files)) {
				foreach($files as $file) {
					$pathToEvaluationPhoto =$conf->digiriskdolibarr->multidir_output[$conf->entity] .  '/riskassessment/' . $evaluation->ref;

					if (!is_dir($pathToEvaluationPhoto)) {
						mkdir($pathToEvaluationPhoto);
					}
					if (!is_file($pathToEvaluationPhoto . '/' . $file['name'])) {
						copy($file['fullname'],$pathToEvaluationPhoto . '/' . $file['name']);
					}

					global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
					$destfull = $pathToEvaluationPhoto . '/' . $file['name'];

					// Create thumbs
					$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
					$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
					unlink($file['fullname']);
				}
			}
			$filesThumbs = dol_dir_list($pathToTmpPhoto . '/thumbs/');
			if (!empty($filesThumbs)) {
				foreach($filesThumbs as $fileThumb) {
					unlink($fileThumb['fullname']);

				}
			}

			$result2 = $evaluation->create($user, true);

			if ($result2 > 0) {
				$tasktitle = $data['task'];
				if (!empty($tasktitle) && $tasktitle !== 'undefined') {
					$extrafields->fetch_name_optionals_label($task->table_element);

					$task->ref = $refTaskMod->getNextValue('', $task);
					$task->label = $tasktitle;
					$task->fk_project = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
					$task->date_c = dol_now();
					$task->array_options['options_fk_risk'] = $risk->id;

					$result3 = $task->create($user, true);

					if ($result3 > 0) {
						// Creation risk + evaluation + task OK
						$urltogo = str_replace('__ID__', $result3, $backtopage);
						$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
						header("Location: " . $urltogo);
					} else {
						// Creation task KO
						if (!empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
						else  setEventMessages($task->error, null, 'errors');
					}
				}
			} else {
				// Creation evaluation KO
				if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
				else  setEventMessages($evaluation->error, null, 'errors');
			}
		}
		else
		{
			// Creation risk KO
			if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
			else  setEventMessages($risk->error, null, 'errors');
		}
	}
}

if (!$error && $action == 'saveRisk' && $permissiontoadd) {

	$data = json_decode(file_get_contents('php://input'), true);

	$riskID      = $data['riskID'];
	$description = $data['comment'];
	$category    = $data['category'];
	$digiriskelement = new DigiriskElement($db);

	if (dol_strlen($data['newParent'])) {
		$parent_element = $digiriskelement->fetchAll('','',0,0, array('ref' => $data['newParent']));
		$parent_id = array_keys($parent_element)[0];
	}

	$risk->fetch($riskID);
	if($parent_id > 0) {
		$risk->fk_element = $parent_id;
	}
	$risk->description =  $description;
	$risk->category    = $category;

	$result = $risk->update($user);

	if ($result > 0) {
		// Update risk OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
	} else {
		// Update risk KO
		if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
		else  setEventMessages($risk->error, null, 'errors');
	}
}

if (!$error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
	if (!empty($toselect)) {
		foreach ($toselect as $toselectedid) {
			$ListEvaluations =  $evaluation->fetchFromParent($toselectedid,0);
			$risk->fetch($toselectedid);

			if (!empty ($ListEvaluations) && $ListEvaluations > 0) {
				foreach ($ListEvaluations as $lastEvaluation ) {
					$pathToEvaluationPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/riskassessment/' . $lastEvaluation->ref;

					if ( file_exists( $pathToEvaluationPhoto ) && !(empty($lastEvaluation->ref))) {
						$files = dol_dir_list($pathToEvaluationPhoto);
						if (!empty($files)) {
							foreach ($files as $file) {
								if (is_file($file['fullname'])) {
									unlink($file['fullname']);
								}
							}
						}

						$files = dol_dir_list($pathToEvaluationPhoto . '/thumbs');
						if (!empty($files)) {
							foreach ($files as $file) {
								unlink($file['fullname']);
							}
						}
						dol_delete_dir($pathToEvaluationPhoto . '/thumbs');
						dol_delete_dir($pathToEvaluationPhoto);

						$lastEvaluation->delete($user, true);
					}
				}
			}

			$result = $risk->delete($user);

			if ($result < 0) {
				// Delete risk KO
				if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
				else  setEventMessages($risk->error, null, 'errors');
			}
		}

		// Delete risk OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
		exit;
	}
}

if (!$error && $action == 'addEvaluation' && $permissiontoadd) {
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
	$evaluation->date_riskassessment = strtotime(preg_replace('/\//', '-',$riskAssessmentDate));

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

	$pathToTmpPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/' . $risktmp->ref;
	$files = dol_dir_list($pathToTmpPhoto);

	if (!empty($files)) {
		foreach($files as $file) {
			$pathToEvaluationPhoto =$conf->digiriskdolibarr->multidir_output[$conf->entity] .  '/riskassessment/' . $evaluation->ref;

			mkdir($pathToEvaluationPhoto);
			copy($file['fullname'],$pathToEvaluationPhoto . '/' . $file['name']);

			global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
			$destfull = $pathToEvaluationPhoto . '/' . $file['name'];

			// Create thumbs
			// We can't use $object->addThumbs here because there is no $object known
			// Used on logon for example
			$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
			// Create mini thumbs for image (Ratio is near 16/9)
			// Used on menu or for setup page for example
			$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
			unlink($file['fullname']);

		}
	}
	$filesThumbs = dol_dir_list($pathToTmpPhoto . '/thumbs/');
	if (!empty($filesThumbs)) {
		foreach($filesThumbs as $fileThumb) {
			unlink($fileThumb['fullname']);

		}
	}

	$result = $evaluation->create($user);

	if ($result > 0) {
		// Creation evaluation OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
	}
	else {
		// Creation evaluation KO
		if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
		else  setEventMessages($evaluation->error, null, 'errors');
	}
}

if (!$error && $action == 'saveEvaluation' && $permissiontoadd) {

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
	$evaluation->date_riskassessment = strtotime(preg_replace('/\//', '-',$riskAssessmentDate));

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
		header("Location: ".$urltogo);
	}
	else {
		// Update evaluation KO
		if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
		else  setEventMessages($evaluation->error, null, 'errors');
	}
}

if (!$error && $action == "deleteEvaluation" && $permissiontodelete) {
	$evaluation_id = GETPOST('deletedEvaluationId');

	$evaluation->fetch($evaluation_id);

	$pathToEvaluationPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/riskassessment/' . $evaluation->ref;
	$files = dol_dir_list($pathToEvaluationPhoto);
	foreach ($files as $file) {
		if (is_file($file['fullname'])) {
			unlink($file['fullname']);
		}
	}

	$files = dol_dir_list($pathToEvaluationPhoto . '/thumbs');
	foreach ($files as $file) {
		unlink($file['fullname']);
	}

	dol_delete_dir($pathToEvaluationPhoto . '/thumbs');
	dol_delete_dir($pathToEvaluationPhoto);

	$previousEvaluation = $evaluation;
	$result = $evaluation->delete($user);
	$previousEvaluation->updateEvaluationStatus($user,$evaluation->fk_risk);

	if ($result > 0) {
		// Delete evaluation OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
		exit;
	}
	else {
		// Delete evaluation KO
		if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
		else  setEventMessages($evaluation->error, null, 'errors');
	}
}

if (!$error && $action == 'addRiskAssessmentTask' && $permissiontoadd) {
	$riskID    = GETPOST('riskToAssign');
	$tasktitle = GETPOST('tasktitle');

	$extrafields->fetch_name_optionals_label($task->table_element);

	$task->ref                              = $refTaskMod->getNextValue('', $task);
	$task->label                            = $tasktitle;
	$task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
	$task->date_c                           = dol_now();
	$task->fk_task_parent                   = 0;
	$task->array_options['options_fk_risk'] = $riskID;

	$result = $task->create($user, true);

	if ($result > 0) {
		// Creation task OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
		exit;
	} else {
		// Creation task KO
		if (!empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
		else  setEventMessages($task->error, null, 'errors');
	}
}

if (!$error && $action == 'saveRiskAssessmentTask' && $permissiontoadd) {
	$riskAssessmentTaskID  = GETPOST('riskAssessmentTaskID');
	$tasktitle             = GETPOST('tasktitle');

	$task->fetch($riskAssessmentTaskID);

	$task->label = $tasktitle;

	$result = $task->update($user, true);

	if ($result > 0) {
		// Update task OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
		exit;
	} else {
		// Update task KO
		if (!empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
		else  setEventMessages($task->error, null, 'errors');
	}
}

if (!$error && $action == "deleteRiskAssessmentTask" && $permissiontodelete) {
	$deleteRiskAssessmentTaskId = GETPOST('deletedRiskAssessmentTaskId');

	$task->fetch($deleteRiskAssessmentTaskId);

	$result = $task->delete($user, true);

	if ($result > 0) {
		// Delete task OK
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: ".$urltogo);
		exit;
	}
	else
	{
		// Delete $task KO
		if (!empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
		else  setEventMessages($task->error, null, 'errors');
	}
}

if (!$error && $action == "addFiles" && $permissiontodelete && !GETPOST('digiriskelement_id')) {

	$riskassessment_id = GETPOST('riskassessment_id');
	$risk_id = GETPOST('risk_id');
	$filenames = GETPOST('filenames');
	$riskassessment = new RiskAssessment($db);
	$risktmp = new Risk($db);
	$risktmp->fetch($risk_id);
	$riskassessment->fetch($riskassessment_id);
	if (dol_strlen($riskassessment->ref) > 0) {
		$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $riskassessment->ref;
	} else {
		$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/' .( dol_strlen($risktmp->ref) > 0 ? $risktmp->ref : 'RK0');
	}
	$filenames = preg_split('/vVv/', $filenames);
	array_pop($filenames);

	if ( !(empty($filenames))) {
		if (!is_dir($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/')) {
			dol_mkdir($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/');
		}
		$riskassessment->photo = $filenames[0];

		foreach ($filenames as $filename) {
			$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';


			if (is_file( $conf->ecm->multidir_output[$conf->entity] . '/digiriskdolibarr/medias/' . $filename)) {

				$pathToECMPhoto =  $conf->ecm->multidir_output[$conf->entity] . '/digiriskdolibarr/medias/' . $filename;

				if (!is_dir($pathToEvaluationPhoto)) {
					mkdir($pathToEvaluationPhoto);
				}
				copy($pathToECMPhoto,$pathToEvaluationPhoto . '/' . $filename);

				global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
				$destfull = $pathToEvaluationPhoto . '/' . $filename;

				// Create thumbs
				$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
				// Create mini thumbs for image (Ratio is near 16/9)
				$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
			}

		}
		$riskassessment->update($user, true);
	}


	exit;
}

if (!$error && $action == "unlinkFile" && $permissiontodelete && !GETPOST('digiriskelement_id')) {

	$riskassessment_id = GETPOST('riskassessment_id');
	$filename = GETPOST('filename');
	$risk_id = GETPOST('risk_id');
	$riskassessment = new RiskAssessment($db);
	$riskassessment->fetch($riskassessment_id);
	$risktmp = new Risk($db);
	$risktmp->fetch($risk_id);

	//edit evaluation
	if ($riskassessment->id > 0) {
		$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] .'/riskassessment/' . $riskassessment->ref;
	} elseif ($risk_id > 0) {
		//create evaluation
		$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] .'/riskassessment/tmp/' . $risktmp->ref;
	} elseif ($risk_id == 'new') {
		//create risk
		$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] .'/riskassessment/tmp/RK0/';
	}


	$files = dol_dir_list($pathToEvaluationPhoto);

	foreach ($files as $file) {
		if (is_file($file['fullname']) && $file['name'] == $filename) {
			unlink($file['fullname']);
		}
	}

	$files = dol_dir_list($pathToEvaluationPhoto . '/thumbs');
	foreach ($files as $file) {
		if (preg_match('/' . preg_split('/\./',$filename)[0] . '/', $file['name'])) {
			unlink($file['fullname']);
		}
	}
	if ($riskassessment->photo == $filename) {
		$riskassessment->photo = '';
		$riskassessment->update($user, true);
	}
	$urltogo = str_replace('__ID__', $id, $backtopage);
	$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
	header("Location: ".$urltogo);
	exit;
}

if (!$error && $action == "addToFavorite" && $permissiontodelete && !GETPOST('digiriskelement_id')) {

	$riskassessment_id = GETPOST('riskassessment_id');
	$filename = GETPOST('filename');
	$riskassessment = new RiskAssessment($db);
	$riskassessment->fetch($riskassessment_id);
	$pathToEvaluationPhoto = $conf->digiriskdolibarr->multidir_output[$conf->entity] .'/riskassessment/' . $riskassessment->ref;
	$riskassessment->photo = $filename;
	$riskassessment->update($user, true);

	$urltogo = str_replace('__ID__', $riskassessment_id, $backtopage);
	$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
	header("Location: ".$urltogo);
	exit;
}
