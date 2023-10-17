<?php
	$selectedfields_label = 'risklist_selectedfields';
	// Selection of new fields
	require __DIR__ . '/../../../../class/actions_changeselectedfields.php';

	print '<div class="fichecenter risklist wpeo-wrap">';
	print '<form method="POST" id="searchFormListRisks" enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . ($object->element == 'digiriskelement' ? '?id=' . $object->id : '?mainmenu=digiriskdolibarr') . '">' . "\n";
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

	// NOTICES FOR ACTIONS
?>
<!--	RISK ASSESSMENT-->
<div class="messageSuccessEvaluationCreate notice hidden">
	<div class="wpeo-notice notice-success riskassessment-create-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskAssessmentWellCreated') ?></div>
			<a href="">
				<div class="notice-subtitle"><?php echo $langs->trans('TheRiskAssessment') . ' ' . $refEvaluationMod->getLastValue($evaluation) . ' ' . $langs->trans('HasBeenCreatedF') ?></div>
			</a>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorEvaluationCreate notice hidden">
	<div class="wpeo-notice notice-warning riskassessment-create-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskAssessmentNotCreated') ?></div>
			<a href="">
				<div class="notice-subtitle"><?php echo $langs->trans('TheRiskAssessment') . ' ' . $refEvaluationMod->getLastValue($evaluation) . ' ' . $langs->trans('HasNotBeenCreatedF') ?></div>
			</a>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageSuccessEvaluationEdit notice hidden">
	<input type="hidden" class="valueForEditEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
	<input type="hidden" class="valueForEditEvaluation2" value="<?php echo ' ' . $langs->trans('HasBeenEditedF') ?>">
	<div class="wpeo-notice notice-success riskassessment-edit-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskAssessmentWellEdited') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorEvaluationEdit notice hidden">
	<input type="hidden" class="valueForEditEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
	<input type="hidden" class="valueForEditEvaluation2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedF') ?>">
	<div class="wpeo-notice notice-warning riskassessment-edit-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskAssessmentNotEdited') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageSuccessEvaluationDelete notice hidden">
	<input type="hidden" class="valueForDeleteEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
	<input type="hidden" class="valueForDeleteEvaluation2" value="<?php echo ' ' . $langs->trans('HasBeenDeletedF') ?>">
	<div class="wpeo-notice notice-success riskassessment-delete-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskAssessmentWellDeleted') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorEvaluationDelete notice hidden">
	<input type="hidden" class="valueForDeleteEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
	<input type="hidden" class="valueForDeleteEvaluation2" value="<?php echo ' ' . $langs->trans('HasNotBeenDeletedF') ?>">
	<div class="wpeo-notice notice-warning riskassessment-delete-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskAssessmentNotDeleted') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>

<!--	RISK -->
<div class="messageSuccessRiskCreate notice hidden">
	<div class="wpeo-notice notice-success risk-create-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskWellCreated') ?></div>
			<div class="notice-subtitle">
				<a href="#<?php echo $refRiskMod->getLastValue($evaluation) ?>">
					<?php echo $langs->trans('TheRisk') . ' <strong><u> ' . $refRiskMod->getLastValue($risk) . ' </u></strong> ' . $langs->trans('HasBeenCreatedM') ?>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorRiskCreate notice hidden">
	<div class="wpeo-notice notice-warning risk-create-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskNotCreated') ?></div>
			<div class="notice-subtitle"><?php echo $langs->trans('TheRisk') . $langs->trans('HasNotBeenCreatedM') ?></div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageSuccessRiskEdit notice hidden">
	<div class="wpeo-notice notice-success risk-edit-success-notice">
		<input type="hidden" class="valueForEditRisk1" value="<?php echo $langs->trans('TheRisk') . ' ' ?>">
		<input type="hidden" class="valueForEditRisk2" value="<?php echo ' ' . $langs->trans('HasBeenEditedM') ?>">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskWellEdited') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorRiskEdit notice hidden">
	<div class="wpeo-notice notice-warning risk-edit-error-notice">
		<input type="hidden" class="valueForEditRisk1" value="<?php echo $langs->trans('TheRisk') . ' ' ?>">
		<input type="hidden" class="valueForEditRisk2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedM') ?>">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('RiskNotEdited') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>

<!--	RISKASSESSMENT TASKS -->
<div class="messageSuccessTaskCreate notice hidden">
	<div class="wpeo-notice notice-success task-create-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskWellCreated') ?></div>
			<div class="notice-subtitle">
					<?php echo $langs->trans('TheTask') . ' ' . $langs->trans('HasBeenCreatedF') ?>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorTaskCreate notice hidden">
	<input type="hidden" class="valueForCreateTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
	<input type="hidden" class="valueForCreateTask2" value="<?php echo ' ' . $langs->trans('HasNotBeenCreatedF') ?>">
	<div class="wpeo-notice notice-warning task-create-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskNotCreated') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageSuccessTaskEdit notice hidden">
	<input type="hidden" class="valueForEditTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
	<input type="hidden" class="valueForEditTask2" value="<?php echo ' ' . $langs->trans('HasBeenEditedF') ?>">
	<div class="wpeo-notice notice-success riskassessment-task-edit-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskWellEdited') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorTaskEdit notice hidden">
	<input type="hidden" class="valueForEditTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
	<input type="hidden" class="valueForEditTask2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedF') ?>">
	<div class="wpeo-notice notice-warning riskassessment-task-edit-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskNotEdited') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageSuccessTaskDelete notice hidden">
	<input type="hidden" class="valueForDeleteTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
	<input type="hidden" class="valueForDeleteTask2" value="<?php echo ' ' . $langs->trans('HasBeenDeletedF') ?>">
	<div class="wpeo-notice notice-success riskassessment-task-delete-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskWellDeleted') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorTaskDelete notice hidden">
	<input type="hidden" class="valueForDeleteTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
	<input type="hidden" class="valueForDeleteTask2" value="<?php echo ' ' . $langs->trans('HasNotBeenDeletedF') ?>">
	<div class="wpeo-notice notice-warning riskassessment-task-delete-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskNotDeleted') ?></div>
			<div class="notice-subtitle">
				<a href="">
					<span class="text"></span>
				</a>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>

<!--	RISKASSESSMENT TASKS TIME SPENT -->
<div class="messageSuccessTaskTimeSpentEdit notice hidden">
	<input type="hidden" class="valueForEditTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
	<input type="hidden" class="valueForEditTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenEditedM') ?>">
	<div class="wpeo-notice notice-success riskassessment-task-timespent-edit-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellEdited') ?></div>
			<div class="notice-subtitle">
				<span class="text"></span>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorTaskTimeSpentEdit notice hidden">
	<input type="hidden" class="valueForEditTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
	<input type="hidden" class="valueForEditTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedM') ?>">
	<div class="wpeo-notice notice-warning riskassessment-task-timespent-edit-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotEdited') ?></div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageSuccessTaskTimeSpentDelete notice hidden">
	<input type="hidden" class="valueForDeleteTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
	<input type="hidden" class="valueForDeleteTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenDeletedM') ?>">
	<div class="wpeo-notice notice-success riskassessment-task-timespent-delete-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellDeleted') ?></div>
			<div class="notice-subtitle">
				<span class="text"></span>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorTaskTimeSpentDelete notice hidden">
	<input type="hidden" class="valueForDeleteTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
	<input type="hidden" class="valueForDeleteTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenDeletedM') ?>">
	<div class="wpeo-notice notice-warning riskassessment-task-timespent-delete-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotDeleted') ?></div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>

<?php

$advancedCotationMethodJson  = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
$advancedCotationMethodArray = json_decode($advancedCotationMethodJson, true);
$digiriskelement             = new DigiriskElement($db);
$riskAssessment              = new RiskAssessment($db);
$digiriskTask                = new DigiriskTask($db);
$extrafields                 = new Extrafields($db);
$usertmp                     = new User($db);
$project                     = new Project($db);
$DUProject                   = new Project($db);

$DUProject->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
$extrafields->fetch_name_optionals_label($digiriskTask->table_element);

$riskAssessment->ismultientitymanaged = 0;

$activeDigiriskElementList = $digiriskelement->getActiveDigiriskElements();
$riskAssessmentList        = $riskAssessment->fetchAll();
$riskAssessmentNextValue   = $refEvaluationMod->getNextValue($evaluation);
$riskAssessmentTaskList    = $risk->getTasksWithFkRisk();
$taskNextValue             = $refTaskMod->getNextValue('', $task);
$usertmp->fetchAll();
$usersList                 = $usertmp->users;
$timeSpentSortedByTasks    = $digiriskTask->fetchAllTimeSpentAllUser('AND ptt.fk_task > 0', 'task_datehour', 'DESC', 1);

$riskAssessment->ismultientitymanaged = 1;

if (is_array($riskAssessmentList) && !empty($riskAssessmentList)) {
	foreach ($riskAssessmentList as $riskAssessmentSingle) {
		$riskAssessmentsOrderedByRisk[$riskAssessmentSingle->fk_risk][$riskAssessmentSingle->id] = $riskAssessmentSingle;
	}
}

// Build and execute select
// --------------------------------------------------------------------
if ( ! preg_match('/(evaluation)/', $sortfield)) {
	$sql = 'SELECT DISTINCT ';
	foreach ($risk->fields as $key => $val) {
		$sql .= 'r.' . $key . ', ';
	}
	// Add fields from extrafields
	if ( ! empty($extrafields->attributes[$risk->table_element]['label'])) {
		foreach ($extrafields->attributes[$risk->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$risk->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
	}
	// Add fields from hooks
	$parameters                                                                                                                                    = array();
	$reshook                                                                                                                                       = $hookmanager->executeHooks('printFieldListSelect', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	$sql                                                                                                                                          .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql                                                                                                                                           = preg_replace('/,\s*$/', '', $sql);
	$sql                                                                                                                                          .= " FROM " . MAIN_DB_PREFIX . $risk->table_element . " as r";
	$sql                                                                                                                                          .= " LEFT JOIN " . MAIN_DB_PREFIX . $digiriskelement->table_element . " as e on (r.fk_element = e.rowid)";
	if (is_array($extrafields->attributes[$risk->table_element]['label']) && count($extrafields->attributes[$risk->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $risk->table_element . "_extrafields as ef on (r.rowid = ef.fk_object)";
	if ($risk->ismultientitymanaged == 1) $sql                                                                                                    .= " WHERE r.entity IN (" . getEntity($risk->element) . ")";
	else $sql                                                                                                                                     .= " WHERE 1 = 1";

	if ( ! $allRisks) {
		$sql .= " AND fk_element = " . $id;
	} else {
		if (is_array($activeDigiriskElementList) && !empty($activeDigiriskElementList)) {
			$digiriskElementSqlFilter = '(';
			foreach (array_keys($activeDigiriskElementList) as $elementId) {
				$digiriskElementSqlFilter .= $elementId . ', ';
			}
			if (preg_match('/, /', $digiriskElementSqlFilter)) {
				$digiriskElementSqlFilter = rtrim($digiriskElementSqlFilter, ', ');
			}
			$digiriskElementSqlFilter .= ')';
			$sql .= " AND r.fk_element IN " . $digiriskElementSqlFilter;
		}
		$sql .= " AND fk_element IN " . $digiriskElementSqlFilter;
		$sql .= " AND fk_element > 0 ";
		$sql .= " AND e.entity IN (" . $conf->entity . ") ";
	}

	foreach ($search as $key => $val) {
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($risk->isInt($risk->fields[$key]) || $risk->isFloat($risk->fields[$key])) ? 1 : 0);
		if (strpos($risk->fields[$key]['type'], 'integer:') === 0) {
			if ($search[$key] == '-1') $search[$key] = '';
			$mode_search                             = 2;
		}
		if ($key == 'category') {
			$mode_search = 1;
		}
		if($search[$key] == '-1') {
			$search[$key] = '';
		}
		if ($search[$key] != '') {
			if ($key == 'ref') {
				$sql .= " AND (r.ref = '$search[$key]')";
			} else {
				$sql .= natural_search('r.'.$key, $search[$key], (($key == 'status') ? 2 : $mode_search));
			}
		}
	}
	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook    = $hookmanager->executeHooks('printFieldListWhere', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	$sql       .= $hookmanager->resPrint;

	$sql .= $db->order($sortfield, $sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$resql = $db->query($sql);

		$nbtotalofrecords = $db->num_rows($resql);

		if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
			$page   = 0;
			$offset = 0;
		}
	}

	// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
		$num = $nbtotalofrecords;
	} else {
		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if ( ! $resql) {
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}

	// Direct jump if only one record found
	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && ! $page) {
		$obj = $db->fetch_object($resql);
		$id  = $obj->rowid;
		header("Location: " . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $id);
		exit;
	}
} else {
	$sql = 'SELECT DISTINCT ';
	foreach ($evaluation->fields as $key => $val) {
		$sql .= 'evaluation.' . $key . ', ';
	}
	// Add fields from extrafields
	if ( ! empty($extrafields->attributes[$evaluation->table_element]['label'])) {
		foreach ($extrafields->attributes[$evaluation->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$evaluation->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
	}
	// Add fields from hooks
	$parameters                                                                                                                                                = array();
	$reshook                                                                                                                                                   = $hookmanager->executeHooks('printFieldListSelect', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
	$sql                                                                                                                                                      .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql                                                                                                                                                       = preg_replace('/,\s*$/', '', $sql);
	$sql                                                                                                                                                      .= " FROM " . MAIN_DB_PREFIX . $evaluation->table_element . " as evaluation";
	$sql                                                                                                                                                      .= " LEFT JOIN " . MAIN_DB_PREFIX . $risk->table_element . " as r on (evaluation.fk_risk = r.rowid)";
	$sql                                                                                                                                                      .= " LEFT JOIN " . MAIN_DB_PREFIX . $digiriskelement->table_element . " as e on (r.fk_element = e.rowid)";
	if (is_array($extrafields->attributes[$evaluation->table_element]['label']) && count($extrafields->attributes[$evaluation->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $evaluation->table_element . "_extrafields as ef on (evaluation.rowid = ef.fk_object)";
	if ($sortfield == 'evaluation.has_tasks')                                                                                                            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields as taskextrafields ON (taskextrafields.fk_risk = r.rowid)';
	if ($evaluation->ismultientitymanaged == 1) $sql                                                                                                          .= " WHERE evaluation.entity IN (" . getEntity($evaluation->element) . ")";
	else $sql                                                                                                                                                 .= " WHERE 1 = 1";
	$sql                                                                                                                                                      .= " AND evaluation.status = 1";
	if ( ! $allRisks) {
		$sql .= " AND r.fk_element =" . $id;
	} else {
		if (is_array($activeDigiriskElementList) && !empty($activeDigiriskElementList)) {
			$digiriskElementSqlFilter = '(';
			foreach (array_keys($activeDigiriskElementList) as $elementId) {
				$digiriskElementSqlFilter .= $elementId . ', ';
			}
			if (preg_match('/, /', $digiriskElementSqlFilter)) {
				$digiriskElementSqlFilter = rtrim($digiriskElementSqlFilter, ', ');
			}
			$digiriskElementSqlFilter .= ')';
			$sql .= " AND r.fk_element IN " . $digiriskElementSqlFilter;
		}
		$sql .= " AND r.fk_element > 0";
		$sql .= " AND e.entity IN (" . $conf->entity . ")";
	}

	foreach ($search as $key => $val) {
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($evaluation->isInt($evaluation->fields[$key]) || $evaluation->isFloat($evaluation->fields[$key])) ? 1 : 0);
		if (strpos($evaluation->fields[$key]['type'], 'integer:') === 0) {
			if ($search[$key] == '-1') $search[$key] = '';
			$mode_search                             = 2;
		}
		if ($key == 'category') {
			$mode_search = 1;
		}
		if($search[$key] == '-1') {
			$search[$key] = '';
		}
		if ($search[$key] != '') {
			if ($key == 'ref') {
				$sql .= " AND (r.ref = '$search[$key]')";
			} else {
				$sql .= natural_search('r.'.$key, $search[$key], (($key == 'status') ? 2 : $mode_search));
			}
		}
	}
	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);

	// Add where from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook    = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
	$sql       .= $hookmanager->resPrint;

	if ($sortfield == 'evaluation.has_tasks') {
		$sql .= ' ORDER BY ' . 'taskextrafields.fk_object ' . $sortorder;
	} else {
		$sql .= $db->order($sortfield, $sortorder);
	}

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$resql = $db->query($sql);

		$nbtotalofrecords = $db->num_rows($resql);

		if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
			$page   = 0;
			$offset = 0;
		}
	}

	// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
		$num = $nbtotalofrecords;
	} else {
		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if ( ! $resql) {
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}

	// Direct jump if only one record found
	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && ! $page) {
		$obj = $db->fetch_object($resql);
		$id  = $obj->rowid;
		header("Location: " . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $id);
		exit;
	}
}

$arrayofselected = is_array($toselect) ? $toselect : array();

$param                                                                      = '';
if ( ! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
$param                                                                     .= '&id=' . $id;
if ($limit > 0 && $limit != $conf->liste_limit) $param                     .= '&limit=' . urlencode($limit);
foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_' . $key . '[]=' . urlencode($skey);
	else $param                                                                                  .= '&search_' . $key . '=' . urlencode($search[$key]);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions                                       = array();
if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>' . $langs->trans("Delete");

if ($action != 'list') {
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
} ?>
<?php if ( ! $allRisks) : ?>
	<!-- BUTTON MODAL RISK ADD -->
	<?php if ($permissiontoadd) {
		$newcardbutton = '<div class="risk-add wpeo-button button-square-40 button-blue wpeo-tooltip-event modal-open"  aria-label="' . $langs->trans('AddRisk') . '"  value="' . $object->id . '">';
		$newcardbutton .= '<i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add animated"></i>';
		$newcardbutton .= '	<input type="hidden" class="modal-options" data-modal-to-open="risk_add'. $object->id .'" data-from-id="'. $object->id .'" data-from-type="digiriskelement" data-from-subtype="photo" data-from-subdir="photos"/>';
		$newcardbutton .= '</div>';
	} else {
		$newcardbutton = '<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="' . $langs->trans('PermissionDenied') . '" data-direction="left" value="' . $object->id . '"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add animated"></i></div>';
	} ?>
	<!-- RISK ADD MODAL-->
	<?php if ($conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT == 0 && $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION == 0 && $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD == 0 ) : ?>
	<div class="risk-add-modal" value="<?php echo $object->id ?>">
		<div class="wpeo-modal modal-risk-0 modal-risk" id="risk_add<?php echo $object->id ?>" value="new">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('AddRiskTitle') . ' ' . $refRiskMod->getNextValue($risk);  ?></h2>
					<div class="modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- Modal-ADD Risk Content-->
				<div class="modal-content" id="#modalContent">
					<!-- PHOTO -->
					<div class="messageSuccessSavePhoto notice hidden">
						<div class="wpeo-notice notice-success save-photo-success-notice">
							<div class="notice-content">
								<div class="notice-title"><?php echo $langs->trans('PhotoWellSaved') ?></div>
							</div>
							<div class="notice-close"><i class="fas fa-times"></i></div>
						</div>
					</div>
					<div class="messageErrorSavePhoto notice hidden">
						<div class="wpeo-notice notice-warning save-photo-error-notice">
							<div class="notice-content">
								<div class="notice-title"><?php echo $langs->trans('PhotoNotSaved') ?></div>
							</div>
							<div class="notice-close"><i class="fas fa-times"></i></div>
						</div>
					</div>
					<div class="risk-content">
						<div class="risk-category">
							<span class="title"><?php echo $langs->trans('Risk'); ?><required>*</required></span>
							<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
								<input class="input-hidden-danger" type="hidden" name="risk_category_id" value="undefined" />
								<input class="input-risk-description-prefill" type="hidden" name="risk_description_prefill" value="<?php echo $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL; ?>" />
								<div class="dropdown-toggle dropdown-add-button button-cotation">
									<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add"></i></span>
									<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
								</div>
								<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
									<?php
									$dangerCategories = $risk->getDangerCategories();
									if ( ! empty($dangerCategories) ) :
										foreach ($dangerCategories as $dangerCategory) : ?>
											<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>" aria-label="<?php echo $dangerCategory['name'] ?>">
												<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
											</li>
										<?php endforeach;
									endif; ?>
								</ul>
							</div>
						</div>
						<div class="risk-evaluation-container standard">
							<span class="section-title"><?php echo ' ' . $langs->trans('RiskAssessment'); ?></span>
							<div class="risk-evaluation-header">
								<?php if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) : ?>
									<div class="wpeo-button evaluation-standard select-evaluation-method selected button-blue button-radius-2">
										<span><?php echo $langs->trans('SimpleEvaluation') ?></span>
									</div>
									<div class="wpeo-button evaluation-advanced select-evaluation-method button-grey button-radius-2">
										<span><?php echo $langs->trans('AdvancedEvaluation') ?></span>
									</div>
									<i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans("HowToSetMultipleRiskAssessmentMethod") ?>"></i>
								<?php endif; ?>
								<input class="risk-evaluation-method" type="hidden" value="standard">
								<input class="risk-evaluation-multiple-method" type="hidden" value="1">
								<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
									<input type="hidden" class="type-from" value="riskassessment"/>
									<input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="0" data-from-type="riskassessment" data-from-subtype="" data-from-subdir=""/>
									<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
								</div>
							</div>
							<div class="risk-evaluation-content-wrapper">
								<div class="risk-evaluation-content">
									<div class="cotation-container">
										<div class="cotation-standard">
											<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('RiskAssessment'); ?><required>*</required></span>
											<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
												<?php
												$defaultCotation = array(0, 48, 51, 100);
												if ( ! empty($defaultCotation)) :
													foreach ($defaultCotation as $request) :
														$evaluation->cotation = $request; ?>
														<div data-id="<?php echo 0; ?>"
															 data-evaluation-method="standard"
															 data-evaluation-id="<?php echo $request; ?>"
															 data-variable-id="<?php echo 152 + $request; ?>"
															 data-seuil="<?php echo  $evaluation->getEvaluationScale(); ?>"
															 data-scale="<?php echo  $evaluation->getEvaluationScale(); ?>"
															 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
													<?php endforeach;
												endif; ?>
											</div>
										</div>
										<input class="risk-evaluation-seuil" type="hidden" value="undefined">
										<?php
										$evaluationMethod        = $advancedCotationMethodArray[0];
										$evaluationMethodSurvey = $evaluationMethod['option']['variable'];
										?>
										<div class="wpeo-gridlayout cotation-advanced" style="display:none">
											<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
											<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
											<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectEvaluation') ?><required>*</required></span>
											<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluationMethodSurvey) + 1; ?>">
												<div class="table-row table-header">
													<div class="table-cell">
														<span></span>
													</div>
													<?php for ( $l = 0; $l < count($evaluationMethodSurvey); $l++ ) : ?>
														<div class="table-cell">
															<span><?php echo $l; ?></span>
														</div>
													<?php endfor; ?>
												</div>
												<?php $l = 0; ?>
												<?php foreach ($evaluationMethodSurvey as $critere) :
													$name = strtolower($critere['name']); ?>
													<div class="table-row">
														<div class="table-cell"><?php echo $critere['name'] ; ?></div>
														<?php foreach ($critere['option']['survey']['request'] as $request) : ?>
															<div class="table-cell can-select cell-<?php echo  $risk->id ? $risk->id : 0 ; ?>"
																 data-type="<?php echo $name ?>"
																 data-id="<?php echo  $risk->id ?: 0 ; ?>"
																 data-evaluation-id="<?php echo $evaluationId ? $evaluationId : 0 ; ?>"
																 data-variable-id="<?php echo $l ; ?>"
																 data-seuil="<?php echo  $request['seuil']; ?>">
																<?php echo  $request['question'] ; ?>
															</div>
														<?php endforeach; $l++; ?>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
                                <div class="photo riskassessment-from-risk-create" style="margin: auto">
                                    <?php
                                    $data = json_decode(file_get_contents('php://input'), true);
                                    if ($subaction != 'unlinkFile') {
                                        $fileName =  $data['filename'];
                                    }
                                    if (dol_strlen($fileName) > 0) {
                                        $evaluation->photo = $fileName;
                                        $showOnlyFavorite = 1;
                                    } else {
                                        $showOnlyFavorite = 0;
                                    }
                                    print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RA0', 'small', 1, 0, 0, 0, 50, 50, 0, 0, 0, '/riskassessment/tmp/RA0', $evaluation, 'photo', 0, 0, 0, $showOnlyFavorite);							?>
                                </div>
								<div class="risk-evaluation-calculated-cotation" style="display: none">
									<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedEvaluation'); ?><required>*</required></span>
									<div data-scale="1" class="risk-evaluation-cotation cotation">
										<span><?php echo 0 ?></span>
									</div>
								</div>
								<div class="risk-evaluation-comment">
									<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
									<?php print '<textarea name="evaluationComment' . $risk->id . '" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n"; ?>
								</div>
							</div>
							<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) : ?>
								<div class="risk-evaluation-date">
									<span class="title"><?php echo $langs->trans('Date'); ?></span>
									<?php print $form->selectDate('', 'RiskAssessmentDate0', 0, 0, 0, '', 1, 1); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<div class="element-linked-medias element-linked-medias-0 risk-new">
						<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
						<?php
						$relativepath = 'digiriskdolibarr/medias/thumbs';
						print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RK0', 'small', 0, 0, 0, 0, 150, 150, 1, 0, 0, '/riskassessment/tmp/RK0');
						?>
					</div>
				</div>
				<!-- Modal-Footer -->
				<div class="modal-footer">
					<?php if ($permissiontoadd) : ?>
						<div class="risk-create wpeo-button button-primary button-disable modal-close">
							<span><i class="fas fa-plus"></i>  <?php echo $langs->trans('AddRiskButton'); ?></span>
						</div>
					<?php else : ?>
						<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
							<span><i class="fas fa-plus"></i>  <?php echo $langs->trans('AddRiskButton'); ?></span>
						</div>
					<?php endif;?>
				</div>
			</div>
		</div>
	</div>
	<?php else : ?>
	<div class="risk-add-modal" value="<?php echo $object->id ?>">
		<div class="wpeo-modal modal-risk-0 modal-risk" id="risk_add<?php echo $object->id ?>" value="new">
		<div class="modal-container wpeo-modal-event">
			<!-- Modal-Header -->
			<div class="modal-header">
				<h2 class="modal-title"><?php echo $langs->trans('AddRiskTitle') . ' ' . $refRiskMod->getNextValue($risk);  ?></h2>
				<div class="modal-close"><i class="fas fa-times"></i></div>
			</div>
			<!-- Modal-ADD Risk Content-->
			<div class="modal-content" id="#modalContent">
				<!-- PHOTO -->
				<div class="messageSuccessSavePhoto notice hidden">
					<div class="wpeo-notice notice-success save-photo-success-notice">
						<div class="notice-content">
							<div class="notice-title"><?php echo $langs->trans('PhotoWellSaved') ?></div>
						</div>
						<div class="notice-close"><i class="fas fa-times"></i></div>
					</div>
				</div>
				<div class="messageErrorSavePhoto notice hidden">
					<div class="wpeo-notice notice-warning save-photo-error-notice">
						<div class="notice-content">
							<div class="notice-title"><?php echo $langs->trans('PhotoNotSaved') ?></div>
						</div>
						<div class="notice-close"><i class="fas fa-times"></i></div>
					</div>
				</div>
				<div class="risk-content">
					<div class="risk-category">
						<span class="title"><?php echo $langs->trans('Risk'); ?><required>*</required></span>
						<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
							<input class="input-hidden-danger" type="hidden" name="risk_category_id" value="undefined" />
							<input class="input-risk-description-prefill" type="hidden" name="risk_description_prefill" value="<?php echo $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL; ?>" />
							<div class="dropdown-toggle dropdown-add-button button-cotation">
								<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add"></i></span>
								<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
							</div>
							<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
								<?php
								$dangerCategories = $risk->getDangerCategories();
								if ( ! empty($dangerCategories) ) :
									foreach ($dangerCategories as $dangerCategory) : ?>
										<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>" aria-label="<?php echo $dangerCategory['name'] ?>">
											<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
										</li>
									<?php endforeach;
								endif; ?>
							</ul>
						</div>
					</div>
					<?php if ($conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) : ?>
						<div class="risk-description">
							<span class="title"><?php echo $langs->trans('Description'); ?></span>
							<?php print '<textarea name="riskComment" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n"; ?>
						</div>
					<?php endif; ?>
					<hr>
				</div>
				<div class="risk-evaluation-container standard">
					<span class="section-title"><?php echo ' ' . $langs->trans('RiskAssessment'); ?></span>
					<div class="risk-evaluation-header">
						<?php if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) : ?>
							<div class="wpeo-button evaluation-standard select-evaluation-method selected button-blue">
								<span><?php echo $langs->trans('SimpleEvaluation') ?></span>
							</div>
							<div class="wpeo-button evaluation-advanced select-evaluation-method button-grey">
								<span><?php echo $langs->trans('AdvancedEvaluation') ?></span>
							</div>
						<?php endif; ?>
						<input class="risk-evaluation-method" type="hidden" value="standard">
						<input class="risk-evaluation-multiple-method" type="hidden" value="1">
						<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
							<input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="0" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-risk-create"/>
							<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
						</div>
					</div>
					<div class="risk-evaluation-content-wrapper">
						<div class="risk-evaluation-content">
							<div class="cotation-container">
								<div class="cotation-standard">
									<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('RiskAssessment'); ?><required>*</required></span>
									<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
										<?php
										$defaultCotation = array(0, 48, 51, 100);
										if ( ! empty($defaultCotation)) :
											foreach ($defaultCotation as $request) :
												$evaluation->cotation = $request; ?>
												<div data-id="<?php echo 0; ?>"
													 data-evaluation-method="standard"
													 data-evaluation-id="<?php echo $request; ?>"
													 data-variable-id="<?php echo 152 + $request; ?>"
													 data-seuil="<?php echo  $evaluation->getEvaluationScale(); ?>"
													 data-scale="<?php echo  $evaluation->getEvaluationScale(); ?>"
													 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
											<?php endforeach;
										endif; ?>
									</div>
								</div>
								<input class="risk-evaluation-seuil" type="hidden" value="undefined">
								<?php
								$evaluationMethod        = $advancedCotationMethodArray[0];
								$evaluationMethodSurvey = $evaluationMethod['option']['variable'];
								?>
								<div class="wpeo-gridlayout cotation-advanced" style="display:none">
									<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
									<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
									<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectEvaluation') ?><required>*</required></span>
									<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluationMethodSurvey) + 1; ?>">
										<div class="table-row table-header">
											<div class="table-cell">
												<span></span>
											</div>
											<?php for ( $l = 0; $l < count($evaluationMethodSurvey); $l++ ) : ?>
												<div class="table-cell">
													<span><?php echo $l; ?></span>
												</div>
											<?php endfor; ?>
										</div>
										<?php $l = 0; ?>
										<?php foreach ($evaluationMethodSurvey as $critere) :
											$name = strtolower($critere['name']); ?>
											<div class="table-row">
												<div class="table-cell"><?php echo $critere['name'] ; ?></div>
												<?php foreach ($critere['option']['survey']['request'] as $request) : ?>
													<div class="table-cell can-select cell-0"
														 data-type="<?php echo $name ?>"
														 data-id="<?php echo 0 ; ?>"
														 data-evaluation-id="<?php echo 0 ; ?>"
														 data-variable-id="<?php echo $l ; ?>"
														 data-seuil="<?php echo  $request['seuil']; ?>">
														<?php echo  $request['question'] ; ?>
													</div>
												<?php endforeach; $l++; ?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>
						<div class="photo riskassessment-from-risk-create" style="margin: auto">
							<?php
							$data = json_decode(file_get_contents('php://input'), true);
							if ($subaction != 'unlinkFile') {
								$fileName =  $data['filename'];
							}
							if (dol_strlen($fileName) > 0) {
								$evaluation->photo = $fileName;
								$showOnlyFavorite = 1;
							} else {
								$showOnlyFavorite = 0;
							}
							print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RA0', 'small', 1, 0, 0, 0, 50, 50, 0, 0, 0, '/riskassessment/tmp/RA0', $evaluation, 'photo', 0, 0, 0, $showOnlyFavorite);							?>
						</div>
						<div class="risk-evaluation-calculated-cotation" style="display: none">
							<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedEvaluation'); ?><required>*</required></span>
							<div data-scale="1" class="risk-evaluation-cotation cotation">
								<span><?php echo 0 ?></span>
							</div>
						</div>
						<div class="risk-evaluation-comment">
							<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
							<?php print '<textarea name="evaluationComment' . $risk->id . '" class="minwidth150" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n"; ?>
						</div>
					</div>
					<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) : ?>
						<div class="risk-evaluation-date">
							<span class="title"><?php echo $langs->trans('Date'); ?></span>
							<?php print $form->selectDate('', 'RiskAssessmentDate', 0, 0, 0, '', 1, 1); ?>
						</div>
					<?php endif; ?>
				</div>
				<?php if ($conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) : ?>
					<div class="riskassessment-task">
						<span class="section-title"><?php echo $langs->trans('Task'); ?></span>
						<span class="title"><?php echo $langs->trans('Label'); ?> <input type="text" class="" name="label" value=""></span>
						<div class="riskassessment-task-date wpeo-gridlayout grid-2">
							<div>
								<span class="title"><?php echo $langs->trans('DateStart'); ?></span>
								<?php print $form->selectDate(dol_now('tzuser'), 'RiskassessmentTaskDateStartModalRisk', 1, 1, 0, '', 1, 1); ?>
							</div>
							<div>
								<span class="title"><?php echo $langs->trans('Deadline'); ?></span>
								<?php print $form->selectDate(-1,'RiskassessmentTaskDateEndModalRisk', 1, 1, 0, '', 1, 1); ?>
							</div>
						</div>
						<span class="title"><?php echo $langs->trans('Budget'); ?></span>
						<input type="text" class="riskassessment-task-budget" name="budget" value="">
					</div>
				<?php endif; ?>
				<div class="linked-medias riskassessment-from-risk-create">
					<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
					<?php
					$relativepath = 'digiriskdolibarr/medias/thumbs';
					print '<div class="wpeo-grid grid-5">';
					print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RA0', 'small', 0, 0, 0, 0, 150, 150, 0, 0, 0, '/riskassessment/tmp/RA0', $evaluation);
					print '</div>';
					?>
				</div>
			</div>
			<!-- Modal-Footer -->
			<div class="modal-footer">
				<?php if ($permissiontoadd) : ?>
					<div class="risk-create wpeo-button button-primary button-disable modal-close">
						<span><i class="fas fa-plus"></i>  <?php echo $langs->trans('AddRiskButton'); ?></span>
					</div>
				<?php else : ?>
					<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
						<span><i class="fas fa-plus"></i>  <?php echo $langs->trans('AddRiskButton'); ?></span>
					</div>
				<?php endif;?>
			</div>
		</div>
	</div>
	</div>
	<?php endif; ?>
<?php endif; ?>
<?php $title = $langs->trans('DigiriskElementRisksList');
print '<div class="div-title-and-table-responsive">';
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $risk->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . join(', ', $fieldstosearchall) . '</div>';
}

$moreforfilter                       = '';
$parameters                          = array();
$reshook                             = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $risk); // Note that $action and $risk may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter                  = $hookmanager->resPrint;

if ( ! empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage  = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$menuConf = 'MAIN_SELECTEDFIELDS_' . $varpage;

if (dol_strlen($user->conf->$menuConf) < 1 || preg_match('/t./', $user->conf->$menuConf)) {
	$user->conf->$menuConf = ($contextpage == 'risklist' ? 'r.fk_element,' : '') . 'r.ref,r.category,evaluation.cotation,';
}

if ( ! preg_match('/r.description/', $user->conf->$menuConf) && $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) {
	$user->conf->$menuConf = $user->conf->$menuConf . 'r.description,';
} elseif ( ! $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) {
	$user->conf->$menuConf = preg_replace('/r.description,/', '', $user->conf->$menuConf);
	$arrayfields['r.description']['enabled'] = 0;
}

if ( ! preg_match('/evaluation.has_tasks/', $user->conf->$menuConf) && $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
	$user->conf->$menuConf .= $user->conf->$menuConf . 'evaluation.has_tasks,';
} elseif ( ! $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
	$user->conf->$menuConf = preg_replace('/evaluation.has_tasks,/', '', $user->conf->$menuConf);
	$arrayfields['evaluation.has_tasks']['enabled'] = 0;
}

$selectedfields  = $form->multiSelectArrayWithCheckbox('risklist_selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
?>
<?php
print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($risk->fields as $key => $val) {
	$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if ( ! empty($arrayfields['r.' . $key]['checked'])) {
		print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $risk->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		} elseif ($key == 'fk_element') {
			print $digiriskelement->selectDigiriskElementList($search['fk_element'], 'search_fk_element', [], 1, 0, array(), 0, 0, 'minwidth100', 0, false, 1);
		} elseif ($key == 'category') { ?>
			<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding" style="position: inherit">
				<input class="input-hidden-danger" type="hidden" name="<?php echo 'search_' . $key ?>" value="<?php echo dol_escape_htmltag($search[$key]) ?>" />
				<?php if (dol_strlen(dol_escape_htmltag($search[$key])) == 0) : ?>
					<div class="dropdown-toggle dropdown-add-button button-cotation">
						<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i></span>
						<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
					</div>
				<?php else : ?>
					<div class="dropdown-toggle dropdown-add-button button-cotation wpeo-tooltip-event" aria-label="<?php echo (empty(dol_escape_htmltag($search[$key]))) ? $risk->getDangerCategoryName($risk) : $risk->getDangerCategoryNameByPosition($search[$key]); ?>">
						<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . ((empty(dol_escape_htmltag($search[$key]))) ? $risk->getDangerCategory($risk) : $risk->getDangerCategoryByPosition($search[$key])) . '.png'?>" />
					</div>
				<?php endif; ?>
				<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
					<?php
					$dangerCategories = $risk->getDangerCategories();
					if ( ! empty($dangerCategories) ) :
						foreach ($dangerCategories as $dangerCategory) : ?>
							<li class="item dropdown-item wpeo-tooltip-event classfortooltip" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>" aria-label="<?php echo $dangerCategory['name'] ?>">
								<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
							</li>
						<?php endforeach;
					endif; ?>
				</ul>
			</div>
		<?php } elseif ( ! preg_match('/^(date|timestamp)/', $val['type']) && $key != 'category') print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key]) . '">';
		print '</td>';
	}
}

foreach ($evaluation->fields as $key => $val) {
	$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if ( ! empty($arrayfields['evaluation.' . $key]['checked'])) {
		print '<td class="liste_titre' . '">';
		print '</td>';
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook    = $hookmanager->executeHooks('printFieldListOption', $parameters, $risk); // Note that $action and $risk may have been modified by hook
print $hookmanager->resPrint;

// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>' . "\n";

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($risk->fields as $key => $val) {
	$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if ( ! empty($arrayfields['r.' . $key]['checked'])) {
		print getTitleFieldOfList($arrayfields['r.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 'r.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
	}
}

foreach ($evaluation->fields as $key => $val) {
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if ( ! empty($arrayfields['evaluation.' . $key]['checked'])) {
		print getTitleFieldOfList($arrayfields['evaluation.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 'evaluation.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook    = $hookmanager->executeHooks('printFieldListTitle', $parameters, $risk); // Note that $action and $risk may have been modified by hook
print $hookmanager->resPrint;

// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
print '</tr>' . "\n";

// Loop on record
// --------------------------------------------------------------------

// contenu
$i          = 0;
$totalarray = array();

while ($i < ($limit ? min($num, $limit) : $num)) {
	$obj = $db->fetch_object($resql);

	if (empty($obj)) break; // Should not happen

	// Si on trie avec un champ d'une évaluation, on fetch le risque et non l'évaluation
	if ($obj->fk_risk > 0) {
		$risk->fetch($obj->fk_risk);
	} else {
		// Store properties in $risk
		$risk->setVarsFromFetchObj($obj);
	}

	// Show here line of result
	print '<tr class="oddeven risk-row risk_row_' . $risk->id . ' risk-row-content-' . $risk->id . '" id="risk_row_' . $risk->id . '">';
	foreach ($risk->fields as $key => $val) {
		$cssforfield                                 = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield          .= ($cssforfield ? ' ' : '') . 'center';
		elseif ($key == 'ref') $cssforfield         .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'category') $cssforfield    .= ($cssforfield ? ' ' : '') . 'risk-category';
		elseif ($key == 'description') $cssforfield .= ($cssforfield ? ' ' : '') . 'risk-description-' . $risk->id;
		if ( ! empty($arrayfields['r.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="width:2%">';
			if ($key == 'status') print $risk->getLibStatut(5);
			elseif ($key == 'fk_element') {
				if (is_object($activeDigiriskElementList[$risk->fk_element])) {
					print $activeDigiriskElementList[$risk->fk_element]->getNomUrl(1, 'blank', 1);
				}
			} elseif ($key == 'category') { ?>
				<div class="table-cell table-50 cell-risk" data-title="Risque">
					<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risk->getDangerCategoryName($risk) ?>">
						<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($risk) . '.png' ; ?>"/>
					</div>
				</div>
				<?php
			} elseif ($key == 'ref') {
				?>
				<div class="risk-container" value="<?php echo $risk->id ?>">
					<!-- BUTTON MODAL RISK EDIT -->
					<?php if ($permissiontoadd) : ?>
						<div><?php
							echo $risk->getNomUrl(1, 'blank'); ?>
							<i class="risk-edit wpeo-tooltip-event modal-open fas fa-pencil-alt" aria-label="<?php echo $langs->trans('EditRisk'); ?>" value="<?php echo $risk->id; ?>" id="<?php echo $risk->ref; ?>">
								<input type="hidden" class="modal-options" data-modal-to-open="risk_edit<?php echo $risk->id ?>" data-from-id="<?php echo $risk->id ?>" data-from-type="risk" data-from-subtype="" data-from-subdir=""/>
							</i>
						</div>
					<?php else : ?>
						<div class="risk-edit-no-perm" value="<?php echo $risk->id ?>"><?php echo $risk->getNomUrl(1, 'blank'); ?></div>
					<?php endif; ?>
					<!-- RISK EDIT MODAL -->
					<div id="risk_edit<?php echo $risk->id ?>" class="wpeo-modal modal-risk-<?php echo $risk->id ?>">
						<div class="modal-container wpeo-modal-event">
							<!-- Modal-Header -->
							<div class="modal-header">
								<h2 class="modal-title"><?php echo $langs->trans('EditRisk') . ' ' . $risk->ref ?></h2>
								<div class="modal-close"><i class="fas fa-times"></i></div>
							</div>
							<!-- MODAL RISK EDIT CONTENT -->
							<div class="modal-content" id="#modalContent">
								<div class="risk-content">
									<div class="risk-category">
										<span class="title">
										<?php if ( ! $conf->global->DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT) {
											$htmltooltip  = '';
											$htmltooltip .= $langs->trans("HowToEnableRiskCategoryEdit");
										} else {
											$htmltooltip  = '';
											$htmltooltip .= $langs->trans("HowToEditRiskCategory");
										}
										print '<span class="center">';
										print $form->textwithpicto($langs->trans('Risk'), $htmltooltip, 1, 0);
										print '</span>';
										?>
										</span>
											<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">

												<input class="input-hidden-danger" type="hidden" name="risk_category_id" value=<?php echo $risk->category ?> />
												<div class="dropdown-toggle dropdown-add-button button-cotation">
													<img class="danger-category-pic tooltip wpeo-tooltip-event hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($risk) . '.png'?>" aria-label="<?php echo $risk->getDangerCategoryName($risk) ?>">
												</div>

												<?php if ($conf->global->DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT) : ?>
												<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
													<?php
													$dangerCategories = $risk->getDangerCategories();
													if ( ! empty($dangerCategories) ) :
														foreach ($dangerCategories as $dangerCategory) : ?>
															<li class="item dropdown-item wpeo-tooltip-event classfortooltip" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>" aria-label="<?php echo $dangerCategory['name'] ?>">
																<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
															</li>
														<?php endforeach;
													endif; ?>
												</ul>
												<?php endif; ?>
											</div>
									</div>
									<?php if ($conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) : ?>
										<div class="risk-description">
											<span class="title"><?php echo $langs->trans('Description'); ?></span>
											<?php print '<textarea name="riskComment" rows="' . ROWS_2 . '">' . $risk->description . '</textarea>' . "\n"; ?>
										</div>
									<?php else : ?>
										<div class="risk-description">
											<span class="title">
												<?php
												$htmltooltip  = '';
												$htmltooltip .= $langs->trans("HowToEnableRiskDescription");

												print '<span class="center">';
												print $form->textwithpicto($langs->trans('Description'), $htmltooltip, 1, 0);
												print '</span>'; ?>
											</span>
											<?php echo $langs->trans('RiskDescriptionNotEnabled'); ?>
										</div>
									<?php endif; ?>
								</div>
								<div class="move-risk <?php echo $conf->global->DIGIRISKDOLIBARR_MOVE_RISKS ? '' : 'move-disabled'?>">
									<span class="title"><?php echo $langs->trans('MoveRisk'); ?></span>
									<?php if (is_object($activeDigiriskElementList[$risk->fk_element])) {
										if ($conf->global->DIGIRISKDOLIBARR_MOVE_RISKS) : ?>
											<input type="hidden" class="current-element-ref" value="<?php echo $activeDigiriskElementList[$risk->fk_element]->ref; ?>">
											<?php print $activeDigiriskElementList[$risk->fk_element]->selectDigiriskElementList($activeDigiriskElementList[$risk->fk_element]->id, 'socid', [], 0, 0, array(), 0, 0, 'disabled', 0, false, 1); ?>
										<?php else : ?>
											<?php print '<span class="opacitymedium">' . '<a href="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/admin/config/riskassessmentdocument.php" target="_blank">' . $langs->trans('SetConfToMoveRisk') . '</a>' . "</span><br>\n"; ?>
										<?php endif; ?>
									<?php } ?>

								</div>
							</div>
							<!-- Modal-Footer -->
							<div class="modal-footer">
								<?php if ($permissiontoadd) : ?>
									<div class="risk-save wpeo-button button-green save modal-close" value="<?php echo $risk->id ?>">
										<span><i class="fas fa-save"></i>  <?php echo $langs->trans('UpdateData'); ?></span>
									</div>
								<?php else : ?>
									<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
										<i class="fas fa-plus"></i> <?php echo $langs->trans('UpdateData'); ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
				<?php
			} elseif ($key == 'description') {
				if ($conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION == 0 ) {
					print $langs->trans('RiskDescriptionNotActivated');
				} else {
					print dol_trunc($risk->description, 120);
				}
			} else print $risk->showOutputField($val, $key, $risk->$key, '');
			print '</td>';
			if ( ! $i) $totalarray['nbfield']++;
			if ( ! empty($val['isameasure'])) {
				if ( ! $i) $totalarray['pos'][$totalarray['nbfield']] = 'r.' . $key;
				$totalarray['val']['r.' . $key]                      += $risk->$key;
			}
		}
	}

	// Store properties in $lastEvaluation
	foreach ($evaluation->fields as $key => $val) {
		$cssforfield                              = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield       .= ($cssforfield ? ' ' : '') . 'center';
		elseif ($key == 'ref') $cssforfield      .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'cotation') $cssforfield .= ($cssforfield ? ' ' : '') . 'risk-evaluation-list-container-' . $risk->id;
		elseif ($key == 'has_tasks') $cssforfield .= ($cssforfield ? ' ' : '') . 'tasks-list-container-' . $risk->id;
		if ( ! empty($arrayfields['evaluation.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="vertical-align: top;">';
			if ($key == 'cotation') {
				require './../../core/tpl/riskanalysis/riskassessment/digiriskdolibarr_riskassessment_view.tpl.php';
			} elseif ($key == 'has_tasks' && $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
				require './../../core/tpl/riskanalysis/riskassessmenttask/digiriskdolibarr_riskassessment_task_view.tpl.php';
			} elseif ($conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT == 0) {
				print $langs->trans('TaskManagementNotActivated');
			} else print $lastEvaluation->showOutputField($val, $key, $lastEvaluation->$key, '');
			print '</td>';
			if ( ! $i) $totalarray['nbfield']++;
			if ( ! empty($val['isameasure'])) {
				if ( ! $i) $totalarray['pos'][$totalarray['nbfield']] = 'r.' . $key;
				$totalarray['val']['r.' . $key]                      += $lastEvaluation->$key;
			}
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'object' => $risk, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook    = $hookmanager->executeHooks('printFieldListValue', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected                                            = 0;
		if (in_array($risk->id, $arrayofselected)) $selected = 1;
		print '<input id="cb' . $risk->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $risk->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
	}

	print '</td>';
	if ( ! $i) $totalarray['nbfield']++;
	print '</tr>' . "\n";
	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if ( ! empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoRecordFound") . '</td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook    = $hookmanager->executeHooks('printFieldListFooter', $parameters, $risk); // Note that $action and $risk may have been modified by hook
print $hookmanager->resPrint; ?>

<?php print '</table>' . "\n";
print '<!-- End table -->';
print '</div>' . "\n";
print '<!-- End div class="div-table-responsive" -->';
print '</div>' . "\n";
print '<!-- End div class="div-title-and-table-responsive" -->';
print '</form>' . "\n";
print '<!-- End form -->';
print '</div>' . "\n";
print '<!-- End div class="fichecenter" -->';

