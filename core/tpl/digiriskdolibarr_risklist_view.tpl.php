<?php
	print '<div class="fichecenter wpeo-wrap">';
	print '<form method="POST" id="searchFormList" enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	// NOTICES FOR ACTIONS
	?>
<!--	RISK ASSESSMENT-->
	<div class="messageSuccessEvaluationCreate notice hidden">
		<div class="wpeo-notice notice-success riskassessment-create-success-notice">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskAssessmentWellCreated') ?></div>
				<div class="notice-subtitle"><?php echo $langs->trans('TheRiskAssessment') . ' ' . $refEvaluationMod->getLastValue($evaluation) . ' ' . $langs->trans('HasBeenCreatedF') ?></div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>
	<div class="messageErrorEvaluationCreate notice hidden">
		<div class="wpeo-notice notice-warning riskassessment-create-error-notice">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskAssessmentNotCreated') ?></div>
				<div class="notice-subtitle"><?php echo $langs->trans('TheRiskAssessment') . ' ' . $refEvaluationMod->getLastValue($evaluation) . ' ' . $langs->trans('HasNotBeenCreatedF') ?></div>
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
					<span class="text"></span>
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
					<span class="text"></span>
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
						<?php echo $langs->trans('TheRisk') . ' ' . $refRiskMod->getLastValue($risk) . ' ' . $langs->trans('HasBeenCreatedM') ?>
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
				<div class="notice-subtitle"><?php echo $langs->trans('TheRisk') . ' ' . $refRiskMod->getLastValue($risk) . ' ' . $langs->trans('HasNotBeenCreatedM') ?></div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>

	<div class="messageSuccessRiskEdit notice hidden">
		<div class="wpeo-notice notice-success risk-edit-success-notice">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskWellEdited') ?></div>
				<div class="notice-subtitle">
					<a href="#<?php echo $refRiskMod->getLastValue($evaluation) ?>">
						<?php echo $langs->trans('TheRisk') . ' ' . $refRiskMod->getLastValue($risk) . ' ' . $langs->trans('HasBeenEditedM') ?>
					</a>
				</div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>
	<div class="messageErrorRiskEdit notice hidden">
		<div class="wpeo-notice notice-warning risk-edit-error-notice">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskNotEdited') ?></div>
				<div class="notice-subtitle"><?php echo $langs->trans('TheRisk') . ' ' . $refRiskMod->getLastValue($risk) . ' ' . $langs->trans('HasNotBeenEditedM') ?></div>
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
		<div class="wpeo-notice notice-warning task-create-error-notice">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('TaskNotCreated') ?></div>
				<div class="notice-subtitle"><?php echo $langs->trans('TheTask') . ' ' . $langs->trans('HasNotBeenCreatedF') ?></div>
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
					<span class="text"></span>
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
					<span class="text"></span>
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
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>


	<?php

	$advanced_method_cotation_json  = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
	$advanced_method_cotation_array = json_decode($advanced_method_cotation_json, true);
	$digiriskelement = new DigiriskElement($db);
	$digiriskelement->fetch($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);
	$trashList = $digiriskelement->getTrashList();
	// Build and execute select
	// --------------------------------------------------------------------
	if (!preg_match('/(evaluation)/', $sortfield)) {
		$sql = 'SELECT ';
		foreach ($risk->fields as $key => $val)
		{
			$sql .= 't.'.$key.', ';
		}
		// Add fields from extrafields
		if (!empty($extrafields->attributes[$risk->table_element]['label'])) {
			foreach ($extrafields->attributes[$risk->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$risk->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
		}
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $risk); // Note that $action and $risk may have been modified by hook
		$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
		$sql = preg_replace('/,\s*$/', '', $sql);
		$sql .= " FROM ".MAIN_DB_PREFIX.$risk->table_element." as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$digiriskelement->table_element." as e on (t.fk_element = e.rowid)";
		if (is_array($extrafields->attributes[$risk->table_element]['label']) && count($extrafields->attributes[$risk->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$risk->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
		if ($risk->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($risk->element).")";
		else $sql .= " WHERE 1 = 1";
		if (!$allRisks) {
			$sql .= " AND fk_element = ".$id;
		} else {
			foreach ($trashList as $deleted_element => $element_id) {
				$sql .= " AND fk_element !=" . $element_id;
			}
			$sql .= " AND fk_element > 0 ";
			$sql .= " AND e.entity IN (".getEntity($risk->element).") ";
		}

		foreach ($search as $key => $val)
		{
			if ($key == 'status' && $search[$key] == -1) continue;
			$mode_search = (($risk->isInt($risk->fields[$key]) || $risk->isFloat($risk->fields[$key])) ? 1 : 0);
			if (strpos($risk->fields[$key]['type'], 'integer:') === 0) {
				if ($search[$key] == '-1') $search[$key] = '';
				$mode_search = 2;
			}
			if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
		if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
		// Add where from extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $risk); // Note that $action and $risk may have been modified by hook
		$sql .= $hookmanager->resPrint;

		$sql .= $db->order($sortfield, $sortorder);

		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$resql = $db->query($sql);

			$nbtotalofrecords = $db->num_rows($resql);
			if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
			{
				$page = 0;
				$offset = 0;
			}
		}

		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if (!$resql)
		{
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);

		// Direct jump if only one record found
		if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page)
		{
			$obj = $db->fetch_object($resql);
			$id = $obj->rowid;
			header("Location: ".dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1).'?id='.$id);
			exit;
		}
	}
	else {

		$sql = 'SELECT ';
		foreach ($evaluation->fields as $key => $val)
		{
			$sql .= 'evaluation.'.$key.', ';
		}
		// Add fields from extrafields
		if (!empty($extrafields->attributes[$evaluation->table_element]['label'])) {
			foreach ($extrafields->attributes[$evaluation->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$evaluation->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
		}
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
		$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
		$sql = preg_replace('/,\s*$/', '', $sql);
		$sql .= " FROM ".MAIN_DB_PREFIX.$evaluation->table_element." as evaluation";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$risk->table_element." as r on (evaluation.fk_risk = r.rowid)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$digiriskelement->table_element." as e on (r.fk_element = e.rowid)";
		if (is_array($extrafields->attributes[$evaluation->table_element]['label']) && count($extrafields->attributes[$evaluation->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$evaluation->table_element."_extrafields as ef on (evaluation.rowid = ef.fk_object)";
		if ($evaluation->ismultientitymanaged == 1) $sql .= " WHERE evaluation.entity IN (".getEntity($evaluation->element).")";
		else $sql .= " WHERE 1 = 1";
		$sql .= " AND evaluation.status = 1";
		if (!$allRisks) {
			$sql .= " AND r.fk_element =" . $id;
		} else {
			foreach ($trashList as $deleted_element => $element_id) {
				$sql .= " AND r.fk_element !=" . $element_id;
			}
			$sql .= " AND r.fk_element > 0";
			$sql .= " AND e.entity IN (".getEntity($evaluation->element).")";
		}

		foreach ($search as $key => $val)
		{
			if ($key == 'status' && $search[$key] == -1) continue;
			$mode_search = (($evaluation->isInt($evaluation->fields[$key]) || $evaluation->isFloat($evaluation->fields[$key])) ? 1 : 0);
			if (strpos($evaluation->fields[$key]['type'], 'integer:') === 0) {
				if ($search[$key] == '-1') $search[$key] = '';
				$mode_search = 2;
			}
			if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
		if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
		// Add where from extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
		$sql .= $hookmanager->resPrint;

		$sql .= $db->order($sortfield, $sortorder);

		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if (!$resql)
		{
			dol_print_error($db);
			exit;
		}
		$num = $db->num_rows($resql);
	}

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	$param .= '&id='.$id;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	foreach ($search as $key => $val)
	{
		if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
		else $param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array();
	if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");

	if ($action != 'list') {
		$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	} ?>
	<?php if (!$allRisks) : ?>
		<!-- BUTTON MODAL RISK ADD -->
		<?php if ($permissiontoadd) {
		$newcardbutton = '<div class="risk-add wpeo-button button-square-40 button-blue wpeo-tooltip-event modal-open" aria-label="'. $langs->trans('AddRisk').'" value="'.$object->id.'"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add animated"></i></div>';
		} else {
			$newcardbutton = '<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="'. $langs->trans('PermissionDenied').'" data-direction="left" value="'.$object->id.'"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add animated"></i></div>';
		} ?>
		<!-- RISK ADD MODAL-->
		<?php if ($conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT == 0 && $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION == 0 && $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD ==0 ) : ?>
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
										$dangerCategories = $risk->get_danger_categories();
										if ( ! empty( $dangerCategories ) ) :
											foreach ( $dangerCategories as $dangerCategory ) : ?>
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
											<span><?php echo $langs->trans('SimpleCotation') ?></span>
										</div>
										<div class="wpeo-button evaluation-advanced select-evaluation-method button-grey button-radius-2">
											<span><?php echo $langs->trans('AdvancedCotation') ?></span>
										</div>
										<i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans("HowToSetMultipleRiskAssessmentMethod") ?>"></i>
									<?php endif; ?>
									<input class="risk-evaluation-method" type="hidden" value="standard">
									<input class="risk-evaluation-multiple-method" type="hidden" value="1">
									<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
										<input type="hidden" class="type-from" value="riskassessment"/>
										<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
									</div>
								</div>
								<div class="risk-evaluation-content-wrapper">
									<div class="risk-evaluation-content">
										<div class="cotation-container">
											<div class="cotation-standard">
												<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?><required>*</required></span>
												<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
													<?php
													$defaultCotation = array(0, 48, 51, 100);
													if ( ! empty( $defaultCotation )) :
														foreach ( $defaultCotation as $request ) :
															$evaluation->cotation = $request; ?>
															<div data-id="<?php echo 0; ?>"
																 data-evaluation-method="standard"
																 data-evaluation-id="<?php echo $request; ?>"
																 data-variable-id="<?php echo 152+$request; ?>"
																 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
														<?php endforeach;
													endif; ?>
												</div>
											</div>
											<input class="risk-evaluation-seuil" type="hidden" value="undefined">
											<?php
											$evaluation_method = $advanced_method_cotation_array[0];
											$evaluation_method_survey = $evaluation_method['option']['variable'];
											?>
											<div class="wpeo-gridlayout cotation-advanced" style="display:none">
												<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
												<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
												<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectCotation') ?><required>*</required></span>
												<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
													<div class="table-row table-header">
														<div class="table-cell">
															<span></span>
														</div>
														<?php for ( $l = 0; $l < count($evaluation_method_survey); $l++ ) : ?>
															<div class="table-cell">
																<span><?php echo $l; ?></span>
															</div>
														<?php endfor; ?>
													</div>
													<?php $l = 0; ?>
													<?php foreach($evaluation_method_survey as $critere) :
														$name = strtolower($critere['name']); ?>
														<div class="table-row">
															<div class="table-cell"><?php echo $critere['name'] ; ?></div>
															<?php foreach($critere['option']['survey']['request'] as $request) : ?>
																<div class="table-cell can-select cell-<?php echo  $risk->id ? $risk->id : 0 ; ?>"
																	 data-type="<?php echo $name ?>"
																	 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
																	 data-evaluation-id="<?php echo $evaluation_id ? $evaluation_id : 0 ; ?>"
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

									<?php include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_photo_view.tpl.php'; ?>

									<div class="risk-evaluation-calculated-cotation" style="display: none">
										<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?><required>*</required></span>
										<div data-scale="1" class="risk-evaluation-cotation cotation">
											<span><?php echo 0 ?></span>
										</div>
									</div>
									<div class="risk-evaluation-comment">
										<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
										<?php print '<textarea name="evaluationComment'. $risk->id .'" class="minwidth150" cols="50" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
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
							print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RK0' , 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, '/riskassessment/tmp/RK0');
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
									$dangerCategories = $risk->get_danger_categories();
									if ( ! empty( $dangerCategories ) ) :
										foreach ( $dangerCategories as $dangerCategory ) : ?>
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
								<?php print '<textarea name="riskComment" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
							</div>
						<?php endif; ?>
						<hr>
					</div>
					<div class="risk-evaluation-container standard">
						<span class="section-title"><?php echo ' ' . $langs->trans('RiskAssessment'); ?></span>
						<div class="risk-evaluation-header">
							<?php if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method selected button-blue">
									<span><?php echo $langs->trans('SimpleCotation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method button-grey">
									<span><?php echo $langs->trans('AdvancedCotation') ?></span>
								</div>
							<?php endif; ?>
							<input class="risk-evaluation-method" type="hidden" value="standard">
							<input class="risk-evaluation-multiple-method" type="hidden" value="1">
							<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
								<input type="hidden" class="type-from" value="riskassessment"/>
								<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
							</div>
						</div>
						<div class="risk-evaluation-content-wrapper">
							<div class="risk-evaluation-content">
								<div class="cotation-container">
									<div class="cotation-standard">
										<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?><required>*</required></span>
										<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
											<?php
											$defaultCotation = array(0, 48, 51, 100);
											if ( ! empty( $defaultCotation )) :
												foreach ( $defaultCotation as $request ) :
													$evaluation->cotation = $request; ?>
													<div data-id="<?php echo 0; ?>"
														 data-evaluation-method="standard"
														 data-evaluation-id="<?php echo $request; ?>"
														 data-variable-id="<?php echo 152+$request; ?>"
														 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
														 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
														 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
												<?php endforeach;
											endif; ?>
										</div>
									</div>
									<input class="risk-evaluation-seuil" type="hidden" value="undefined">
									<?php
									$evaluation_method = $advanced_method_cotation_array[0];
									$evaluation_method_survey = $evaluation_method['option']['variable'];
									?>
									<div class="wpeo-gridlayout cotation-advanced" style="display:none">
										<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
										<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
										<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectCotation') ?><required>*</required></span>
										<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
											<div class="table-row table-header">
												<div class="table-cell">
													<span></span>
												</div>
												<?php for ( $l = 0; $l < count($evaluation_method_survey); $l++ ) : ?>
													<div class="table-cell">
														<span><?php echo $l; ?></span>
													</div>
												<?php endfor; ?>
											</div>
											<?php $l = 0; ?>
											<?php foreach($evaluation_method_survey as $critere) :
												$name = strtolower($critere['name']); ?>
												<div class="table-row">
													<div class="table-cell"><?php echo $critere['name'] ; ?></div>
													<?php foreach($critere['option']['survey']['request'] as $request) : ?>
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

							<?php include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_photo_view.tpl.php'; ?>

							<div class="risk-evaluation-calculated-cotation" style="display: none">
								<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?><required>*</required></span>
								<div data-scale="1" class="risk-evaluation-cotation cotation">
									<span><?php echo 0 ?></span>
								</div>
							</div>
							<div class="risk-evaluation-comment">
								<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
								<?php print '<textarea name="evaluationComment'. $risk->id .'" class="minwidth150" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
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
						</div>
					<?php endif; ?>
					<div class="element-linked-medias element-linked-medias-0 risk-new">
						<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
						<?php
						$relativepath = 'digiriskdolibarr/medias/thumbs';
						print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RK0' , 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, '/riskassessment/tmp/RK0');
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
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $num, 'digiriskdolibarr32px.png@digiriskdolibarr', 0, $newcardbutton, '', $limit, 0, 0, 1);

	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($search_all)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (!empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$menuConf = 'MAIN_SELECTEDFIELDS_' . $varpage;

	if (!preg_match('/t.description/', $user->conf->$menuConf) && $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) {
		$user->conf->$menuConf = (($varpage == 'risklist') ? 't.fk_element,' : '').'t.ref,evaluation.cotation,t.category,t.description,';

	} elseif (!$conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) {
		$user->conf->$menuConf = preg_replace('/t.description,/', '', $user->conf->$menuConf);
	}

	if (!preg_match('/evaluation.has_tasks/', $user->conf->$menuConf) && $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
		$user->conf->$menuConf .= (($varpage == 'risklist') ? 't.fk_element,' : '').'t.ref,evaluation.cotation,t.category,evaluation.has_tasks,';

	} elseif (!$conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
		$user->conf->$menuConf = preg_replace('/evaluation.has_tasks,/', '', $user->conf->$menuConf);
	}


	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	?>
	<?php
	print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($risk->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
			if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			elseif (strpos($val['type'], 'integer:') === 0) {
				print $risk->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
			}
			elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
			print '</td>';
		}
	}

	foreach ($evaluation->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
		if (!empty($arrayfields['evaluation.'.$key]['checked']))
		{
			print '<td class="liste_titre'.'">';
			print '</td>';
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>'."\n";

	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($risk->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
		}

	}

	foreach ($evaluation->fields as $key => $val)
	{
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
		if (!empty($arrayfields['evaluation.'.$key]['checked']))
		{
			print getTitleFieldOfList($arrayfields['evaluation.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 'evaluation.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	print '</tr>'."\n";

	// Loop on record
	// --------------------------------------------------------------------

	// contenu
	$i = 0;
	$totalarray = array();

	while ($i < ($limit ? min($num, $limit) : $num))
	{
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
		print '<tr class="oddeven risk-row risk_row_'. $risk->id .' risk-row-content-'. $risk->id . '" id="risk_row_'. $risk->id .'">';
		foreach ($risk->fields as $key => $val)
		{
			$cssforfield = (empty($val['css']) ? '' : $val['css']);
			if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
			elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			elseif ($key == 'category') $cssforfield .= ($cssforfield ? ' ' : '').'risk-category';
			elseif ($key == 'description') $cssforfield .= ($cssforfield ? ' ' : '').'risk-description-'.$risk->id;
			if (!empty($arrayfields['t.'.$key]['checked']))
			{
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').' style="width:2%">';
				if ($key == 'status') print $risk->getLibStatut(5);
				elseif ($key == 'fk_element') { ?>
					<?php $parent_element = new DigiriskElement($db);
					$result = $parent_element->fetch($risk->fk_element);
					if ($result > 0) {
						print $parent_element->getNomUrl(1, 'blank');
					}
				}
				elseif ($key == 'category') { ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risk->get_danger_category_name($risk) ?>">
							<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($risk) . '.png' ; ?>"/>
						</div>
					</div>
					<?php
				}
				elseif ($key == 'ref') {
					?>
					<div class="risk-container" value="<?php echo $risk->id ?>">
						<!-- BUTTON MODAL RISK EDIT -->
						<?php if ($permissiontoadd) : ?>
							<div><i class="fas fa-exclamation-triangle"></i><?php echo ' ' . $risk->ref; ?> <i class="risk-edit wpeo-tooltip-event modal-open fas fa-pencil-alt" aria-label="<?php echo $langs->trans('EditRisk'); ?>" value="<?php echo $risk->id; ?>" id="<?php echo $risk->ref; ?>"></i></div>
						<?php else : ?>
							<div class="risk-edit-no-perm" value="<?php echo $risk->id ?>"><i class="fas fa-exclamation-triangle"></i><?php echo ' ' . $risk->ref; ?></div>
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
											<?php if (!$conf->global->DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT) {
												$htmltooltip = '';
												$htmltooltip .= $langs->trans("HowToEnableRiskCategoryEdit");
											} else {
												$htmltooltip = '';
												$htmltooltip .= $langs->trans("HowToEditRiskCategory");
											}
											print '<span class="center">';
											print $form->textwithpicto($langs->trans('Risk'), $htmltooltip, 1, 0);
											print '</span>';
											?>
											</span>
												<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">

													<input class="input-hidden-danger" type="hidden" name="risk_category_id" value=<?php echo $risk->category ?> />
													<div class="dropdown-toggle dropdown-add-button button-cotation wpeo-tooltip-event" aria-label="<?php echo $risk->get_danger_category_name($risk) ?>">
														<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($risk) . '.png'?>"" />
													</div>

													<?php if ($conf->global->DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT) : ?>

													<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
														<?php
														$dangerCategories = $risk->get_danger_categories();
														if ( ! empty( $dangerCategories ) ) :
															foreach ( $dangerCategories as $dangerCategory ) : ?>
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
												<?php print '<textarea name="riskComment" rows="'.ROWS_2.'">'.$risk->description.'</textarea>'."\n"; ?>
											</div>
										<?php else : ?>
											<div class="risk-description">
												<span class="title">
													<?php
													$htmltooltip = '';
													$htmltooltip .= $langs->trans("HowToEnableRiskDescription");

													print '<span class="center">';
													print $form->textwithpicto($langs->trans('Description'), $htmltooltip, 1, 0);
													print '</span>'; ?>
												</span>
												<?php echo $langs->trans('RiskDescriptionNotEnabled'); ?>
											</div>
										<?php endif; ?>
									</div>
									<div class="move-risk">
										<span class="title"><?php echo $langs->trans('MoveRisk'); ?></span>
										<?php $objecttmp = new DigiriskElement($db);
										$objecttmp->fetch($risk->fk_element);
										?>
										<?php if ($conf->global->DIGIRISKDOLIBARR_MOVE_RISKS) : ?>
											<input type="hidden" class="current-element-ref" value="<?php echo $objecttmp->ref; ?>">
											<?php print $objecttmp->select_digiriskelement_list( $objecttmp->id,  'socid',  '',  '1',  0, 1, array(), '', 0, 0, 'disabled', '', false, 1); ?>
										<?php else : ?>
											<?php print '<span class="opacitymedium">'.$langs->trans("SetConfToMoveRisk")."</span><br>\n"; ?>
										<?php endif; ?>
									</div>
								</div>
								<!-- Modal-Footer -->
								<div class="modal-footer">
									<?php if ($permissiontoadd) : ?>
										<div class="risk-save wpeo-button button-green save" value="<?php echo $risk->id ?>">
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
				}
				elseif ($key == 'description') {
					if ($conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION == 0 ) {
						print $langs->trans('RiskDescriptionNotActivated');
					} else {
						print dol_trunc($risk->description, 120);
					}
				}
				else print $risk->showOutputField($val, $key, $risk->$key, '');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!empty($val['isameasure']))
				{
					if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
					$totalarray['val']['t.'.$key] += $risk->$key;
				}
			}
		}

		// Store properties in $lastEvaluation
		foreach ($evaluation->fields as $key => $val)
		{
			$cssforfield = (empty($val['css']) ? '' : $val['css']);
			if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
			elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			elseif ($key == 'cotation') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			if (!empty($arrayfields['evaluation.'.$key]['checked']))
			{
				$cssforfield = '';
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').' style="vertical-align: top;">';
				if ($key == 'cotation') {
					require './../../core/tpl/digiriskdolibarr_riskassessment_view.tpl.php';
				}
				elseif ($key == 'has_tasks' && $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
					require './../../core/tpl/digiriskdolibarr_riskassessment_task_view.tpl.php';
				}
				elseif ($conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT == 0) {
					print $langs->trans('TaskManagementNotActivated');
				}
				else print $lastEvaluation->showOutputField($val, $key, $lastEvaluation->$key, '');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!empty($val['isameasure']))
				{
					if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
					$totalarray['val']['t.'.$key] += $lastEvaluation->$key;
				}
			}
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'object'=>$risk, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $risk); // Note that $action and $risk may have been modified by hook
		print $hookmanager->resPrint;

		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($risk->id, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$risk->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$risk->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}

		print '</td>';
		if (!$i) $totalarray['nbfield']++;
		print '</tr>'."\n";
		$i++;
	}

	// If no record found
	if ($num == 0)
	{
		$colspan = 1;
		foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
	}

	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	print $hookmanager->resPrint; ?>



	<?php print '</table>'."\n";
	print '<!-- End table -->';
	print '</div>'."\n";
	print '<!-- End div class="div-table-responsive" -->';
	print '</form>'."\n";
	print '<!-- End form -->';
	print '</div>'."\n";
	print '<!-- End div class="fichecenter" -->';

	dol_fiche_end();
