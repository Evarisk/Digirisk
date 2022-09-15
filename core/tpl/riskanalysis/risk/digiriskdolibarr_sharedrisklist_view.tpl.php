<?php
	$selectedfields_label = 'shared_risklist_selectedfields';
	// Selection of new fields
	require './../../class/actions_changeselectedfields.php';

	print '<div class="fichecenter sharedrisklist wpeo-wrap">';
	print '<form method="POST" id="searchFormSharedListRisks" enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . ($object->element == 'digiriskelement' ? '?id=' . $object->id : '') . '">' . "\n";
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
	<!--	RISK -->
	<div class="messageSuccessRiskUnlinkShared notice hidden">
		<div class="wpeo-notice notice-success risk-unlink-shared-success-notice">
			<input type="hidden" class="valueForUnlinkSharedRisk1" value="<?php echo $langs->trans('TheRisk') . ' ' ?>">
			<input type="hidden" class="valueForUnlinkSharedRisk2" value="<?php echo ' ' . $langs->trans('HasBeenUnlinkSharedM') ?>">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskWellUnlinkShared') ?></div>
				<div class="notice-subtitle">
					<a href="">
						<span class="text"></span>
					</a>
				</div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>
	<div class="messageErrorRiskUnlinkShared notice hidden">
		<div class="wpeo-notice notice-warning risk-unlink-shared--error-notice">
			<input type="hidden" class="valueForUnlinkSharedRisk1" value="<?php echo $langs->trans('TheRisk') . ' ' ?>">
			<input type="hidden" class="valueForUnlinkSharedRisk2" value="<?php echo ' ' . $langs->trans('HasNotBeenUnlinkSharedM') ?>">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskNotUnlinkShared') ?></div>
				<div class="notice-subtitle">
					<a href="">
						<span class="text"></span>
					</a>
				</div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>

	<?php

	$advanced_method_cotation_json  = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
	$advanced_method_cotation_array = json_decode($advanced_method_cotation_json, true);
	$digiriskelement                = new DigiriskElement($db);
	$digiriskelement->fetch($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);
	$trashList = $digiriskelement->getMultiEntityTrashList();

	$risktmp = new Risk($db);
	$digiriskelementtmp = new DigiriskElement($db);
	$alldigiriskelement = $digiriskelementtmp->fetchAll('', '', 0, 0, array('customsql' => 'status > 0'), 'AND');
	if (!empty($trashList) && is_array($trashList)) {
		foreach($trashList as $trash_element_id) {
			unset($alldigiriskelement[$trash_element_id]);
		}
	}

	// Build and execute select
	// --------------------------------------------------------------------
	if ( ! preg_match('/(evaluation)/', $sortfield)) {
		$sql = 'SELECT DISTINCT ';
		foreach ($risk->fields as $key => $val) {
			$sql .= 't.' . $key . ', ';
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
		$sql                                                                                                                                          .= " FROM " . MAIN_DB_PREFIX . $risk->table_element . " as t";
		$sql                                                                                                                                          .= " LEFT JOIN " . MAIN_DB_PREFIX . $digiriskelement->table_element . " as e on (t.fk_element = e.rowid)";
		$sql                                                                                                                                          .= " INNER JOIN " . MAIN_DB_PREFIX . 'element_element' . " as el on (t.rowid = el.fk_source)";
		if (is_array($extrafields->attributes[$risk->table_element]['label']) && count($extrafields->attributes[$risk->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $risk->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
		//if ($risk->ismultientitymanaged == 1) $sql                                                                                                    .= " WHERE t.entity IN (" . getEntity($risk->element) . ")";
		//else $sql                                                                                                                                     .= " WHERE 1 = 1";
		if ( ! $allRisks) {
			$sql .= " AND el.fk_target = " . $id;
			$sql .= " AND el.sourcetype = 'digiriskdolibarr_risk'";
			foreach ($trashList as $deleted_element => $element_id) {
				$sql .= " AND fk_element !=" . $element_id;
			}
		} else {
			foreach ($trashList as $deleted_element => $element_id) {
				$sql .= " AND fk_element !=" . $element_id;
			}
			$sql .= " AND fk_element > 0 ";
			$sql .= " AND el.fk_target IN (";
			foreach ($alldigiriskelement as $digiriskelementsingle) {
				$digiriskelementList[] = $digiriskelementsingle->id;
			}
			$digiriskelementList = array_unique($digiriskelementList);
			foreach ($digiriskelementList as $digiriskelementsinglefinal) {
				$sql .= $digiriskelementsinglefinal . ',';
			}
			$sql = dol_substr($sql, 0 , -1);
			$sql .= ")";
			$sql .= " AND el.sourcetype = 'digiriskdolibarr_risk'";
			//$sql .= " AND e.entity IN (" . getEntity($risk->element) . ") ";
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
					$sql .= " AND (t.ref = '$search[$key]')";
				} elseif ($key == 'fk_element') {
					if ( $search[$key] > 0){
						$sql .= " AND (e.rowid = '$search[$key]')";
					}
				} elseif ($key == 'entity') {
					$sql .= " AND (e.entity = '$search[$key]')";
				} else {
					$sql .= natural_search('t.'.$key, $search[$key], (($key == 'status') ? 2 : $mode_search));
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
		$sql 																																					  .= " INNER JOIN " . MAIN_DB_PREFIX . 'element_element' . " as el on (r.rowid = el.fk_source)";
		$sql 																																					  .= " LEFT JOIN " . MAIN_DB_PREFIX . $digiriskelement->table_element . " as e on (r.fk_element = e.rowid)";
		if (is_array($extrafields->attributes[$evaluation->table_element]['label']) && count($extrafields->attributes[$evaluation->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $evaluation->table_element . "_extrafields as ef on (evaluation.rowid = ef.fk_object)";
		//if ($evaluation->ismultientitymanaged == 1) $sql                                                                                                          .= " WHERE evaluation.entity IN (" . getEntity($evaluation->element) . ")";
		else $sql                                                                                                                                                 .= " WHERE 1 = 1";
		$sql                                                                                                                                                      .= " AND evaluation.status = 1";

		if ( ! $allRisks) {
			$sql .= " AND el.fk_target = " . $id;
			$sql .= " AND el.sourcetype = 'digiriskdolibarr_risk'";
			foreach ($trashList as $deleted_element => $element_id) {
				$sql .= " AND fk_element !=" . $element_id;
			}
			//$sql .= " AND r.fk_element =" . $id;
		} else {
			foreach ($trashList as $deleted_element => $element_id) {
				$sql .= " AND r.fk_element !=" . $element_id;
			}
			$sql .= " AND r.fk_element > 0";
			$sql .= " AND el.fk_target IN (";
			foreach ($alldigiriskelement as $digiriskelementsingle) {
				$digiriskelementList[] = $digiriskelementsingle->id;
			}
			$digiriskelementList = array_unique($digiriskelementList);
			foreach ($digiriskelementList as $digiriskelementsinglefinal) {
				$sql .= $digiriskelementsinglefinal . ',';
			}
			$sql = dol_substr($sql, 0 , -1);
			$sql .= ")";
			$sql .= " AND el.sourcetype = 'digiriskdolibarr_risk'";
			//$sql .= " AND e.entity IN (" . getEntity($evaluation->element) . ")";
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
				} elseif ($key == 'fk_element') {
					if ( $search[$key] > 0){
						$sql .= " AND (e.rowid = '$search[$key]')";
					}
				} elseif ($key == 'entity') {
					$sql .= " AND (evaluation.entity = '$search[$key]')";
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
	}

	$arrayofselected = is_array($toselect) ? $toselect : array();
	$param                                                                      = '';
	if ( ! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
	$param                                                                     .= '&id=' . $id;
    $param                                                                     .= '&sharedrisks=' . 1;
	if ($limit > 0 && $limit != $conf->liste_limit) $param                     .= '&limit=' . urlencode($limit);
	foreach ($search as $key => $val) {
		if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_' . $key . '[]=' . urlencode($skey);
		else $param                                                                                  .= '&search_' . $key . '=' . urlencode($search[$key]);
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array();

	if ($action != 'list') {
		$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	} ?>

	<?php $title = $langs->trans('DigiriskElementSharedRisksList');
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'digiriskdolibarr32px.png@digiriskdolibarr', 0, '', '', $limit, 0, 0, 1);

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

	$arrayfields['t.entity']['checked']  = 1;
	$arrayfields['t.entity']['label']    = $langs->trans('Entity');
	$arrayfields['t.entity']['enabled']  = 1;
	$arrayfields['t.entity']['position'] = 1;

	$arrayfields['t.fk_element']['checked']  = 1;
	$arrayfields['t.fk_element']['label']    = $langs->trans('ParentElement');
	$arrayfields['t.fk_element']['enabled']  = 1;
	$arrayfields['t.fk_element']['position'] = 2;

	$arrayfields['t.applied_on']['checked']  = 1;
	$arrayfields['t.applied_on']['label']    = $langs->trans('AppliedOn');
	$arrayfields['t.applied_on']['enabled']  = 1;
	$arrayfields['t.applied_on']['position'] = 9;
	$arrayfields['t.applied_on']['disablesort'] = 1;

	$arrayfields = dol_sort_array($arrayfields, 'position');

	$menuConf = 'MAIN_SELECTEDFIELDS_' . $varpage;

	if (dol_strlen($user->conf->$menuConf) < 1) {
		$user->conf->$menuConf = 't.entity,t.fk_element,t.applied_on,t.ref,t.category,evaluation.cotation,';
	}

	if ( ! preg_match('/t.description/', $user->conf->$menuConf) && $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) {
		$user->conf->$menuConf = $user->conf->$menuConf  . 't.description,';
	} elseif ( ! $conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION) {
		$user->conf->$menuConf = preg_replace('/t.description,/', '', $user->conf->$menuConf);
		$arrayfields['t.description']['enabled'] = 0;
	}

	if ( ! preg_match('/evaluation.has_tasks/', $user->conf->$menuConf) && $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
		$user->conf->$menuConf .= $user->conf->$menuConf  . 'evaluation.has_tasks,';
	} elseif ( ! $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
		$user->conf->$menuConf = preg_replace('/evaluation.has_tasks,/', '', $user->conf->$menuConf);
		$arrayfields['evaluation.has_tasks']['enabled'] = 0;
	}

	$selectedfields  = $form->multiSelectArrayWithCheckbox('shared_risklist_selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	?>
	<?php
	print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';

	$risk->fields['applied_on'] = $arrayfields['t.applied_on'] ;
	$risk->fields = dol_sort_array($risk->fields, 'position');

	foreach ($risk->fields as $key => $val) {
		$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		if ( ! empty($arrayfields['t.' . $key]['checked'])) {
			print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
			if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			elseif (strpos($val['type'], 'integer:') === 0) {
				print $risk->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
			} elseif ($key == 'entity') {
				print select_entity_list($search['entity'], 'search_entity', 'e.rowid NOT IN (' . $conf->entity . ')');
			} elseif ($key == 'fk_element') {
				print $digiriskelement->select_digiriskelement_list($search['fk_element'], 'search_fk_element_sharedrisk', 's.entity NOT IN (' . $conf->entity . ')', 1, 0, array(), 0, 0, 'minwidth100', 0, false, 1, $contextpage, false);
			}  elseif ($key == 'applied_on') {
//				print $digiriskelement->select_digiriskelement_list($search['search_applied_on_sharedrisk'], 'search_applied_on_sharedrisk', '', 1, 0, array(), 0, 0, 'minwidth100', 0, false, 1, $contextpage);
			} elseif ($key == 'category') { ?>
				<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding" style="position: inherit">
					<input class="input-hidden-danger" type="hidden" name="<?php echo 'search_' . $key ?>" value="<?php echo dol_escape_htmltag($search[$key]) ?>" />
					<?php if (dol_strlen(dol_escape_htmltag($search[$key])) == 0) : ?>
						<div class="dropdown-toggle dropdown-add-button button-cotation">
							<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i></span>
							<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
						</div>
					<?php else : ?>
						<div class="dropdown-toggle dropdown-add-button button-cotation wpeo-tooltip-event" aria-label="<?php echo (empty(dol_escape_htmltag($search[$key]))) ? $risk->get_danger_category_name($risk) : $risk->get_danger_category_name_by_position($search[$key]); ?>">
							<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . ((empty(dol_escape_htmltag($search[$key]))) ? $risk->get_danger_category($risk) : $risk->get_danger_category_by_position($search[$key])) . '.png'?>" />
						</div>
					<?php endif; ?>
					<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
						<?php
						$dangerCategories = $risk->get_danger_categories();
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
		if ( ! empty($arrayfields['t.' . $key]['checked'])) {
			print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : ''), $arrayfields['t.' . $key]['disablesort']) . "\n";
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
	unset($risk->fields['applied_on']);

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

		$risk->fetchObjectLinked($risk->id, 'digiriskdolibarr_risk');

		// Show here line of result
		print '<tr class="oddeven risk-row risk_row_' . $risk->id . ' risk-row-content-' . $risk->id . '" id="risk_row_' . $risk->id . '">';
		$risk->fields['applied_on'] = $arrayfields['t.applied_on'] ;
		$risk->fields = dol_sort_array($risk->fields, 'position');

		foreach ($risk->fields as $key => $val) {
			$cssforfield                                 = (empty($val['css']) ? '' : $val['css']);
			if ($key == 'status') $cssforfield          .= ($cssforfield ? ' ' : '') . 'center';
			elseif ($key == 'ref') $cssforfield         .= ($cssforfield ? ' ' : '') . 'nowrap';
			elseif ($key == 'category') $cssforfield    .= ($cssforfield ? ' ' : '') . 'risk-category';
			elseif ($key == 'description') $cssforfield .= ($cssforfield ? ' ' : '') . 'risk-description-' . $risk->id;
			if ( ! empty($arrayfields['t.' . $key]['checked'])) {
				print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="width:2%">';
				if ($key == 'status') print $risk->getLibStatut(5);
				elseif ($key == 'entity') { ?>
					<?php
					print getNomUrlEntity($risk, 1, 'nolink', 1);
				} elseif ($key == 'fk_element') { ?>
					<?php $parent_element = new DigiriskElement($db);
					$result               = $parent_element->fetch($risk->fk_element);
					if ($result > 0) {
						print $parent_element->getNomUrl(1, 'nolink');
					}
				} elseif ($key == 'category') { ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risk->get_danger_category_name($risk) ?>">
							<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($risk) . '.png' ; ?>"/>
						</div>
					</div>
					<?php
				} elseif ($key == 'ref') {
					?>
					<div class="risk-container" value="<?php echo $risk->id ?>">
						<div><i class="fas fa-exclamation-triangle"></i><?php echo ' ' . $risk->ref; ?></div>
					</div>
					<?php
				} elseif ($key == 'description') {
					if ($conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION == 0 ) {
						print $langs->trans('RiskDescriptionNotActivated');
					} else {
						print dol_trunc($risk->description, 120);
					}
				} elseif ($key == 'applied_on') {
					if (!empty($risk->linkedObjectsIds['digiriskdolibarr_digiriskelement']) && is_array($risk->linkedObjectsIds['digiriskdolibarr_digiriskelement'])) {
						foreach($risk->linkedObjectsIds['digiriskdolibarr_digiriskelement'] as $digiriskelement_id) {
							if (array_key_exists($digiriskelement_id, $alldigiriskelement)) {
								$digiriskelement->fetch($digiriskelement_id);
								print $digiriskelement->getNomUrl(1);
								print '<br>';
							}
						}
					}
				} else print $risk->showOutputField($val, $key, $risk->$key, '');
				print '</td>';
				if ( ! $i) $totalarray['nbfield']++;
				if ( ! empty($val['isameasure'])) {
					if ( ! $i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
					$totalarray['val']['t.' . $key]                      += $risk->$key;
				}
			}
		}
		unset($risk->fields['applied_on']);

		// Store properties in $lastEvaluation
		foreach ($evaluation->fields as $key => $val) {
			$cssforfield                              = (empty($val['css']) ? '' : $val['css']);
			if ($key == 'status') $cssforfield       .= ($cssforfield ? ' ' : '') . 'center';
			elseif ($key == 'ref') $cssforfield      .= ($cssforfield ? ' ' : '') . 'nowrap';
			elseif ($key == 'cotation') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
			if ( ! empty($arrayfields['evaluation.' . $key]['checked'])) {
				$cssforfield = '';
				print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="vertical-align: top;">';
				if ($key == 'cotation') {
					require './../../core/tpl/riskanalysis/riskassessment/digiriskdolibarr_riskassessment_view.tpl.php';
				} elseif ($key == 'has_tasks' && $conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
					require './../../core/tpl/riskanalysis/riskassessment/digiriskdolibarr_riskassessment_task_view.tpl.php';
				} elseif ($conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT == 0) {
					print $langs->trans('TaskManagementNotActivated');
				} else print $lastEvaluation->showOutputField($val, $key, $lastEvaluation->$key, '');
				print '</td>';
				if ( ! $i) $totalarray['nbfield']++;
				if ( ! empty($val['isameasure'])) {
					if ( ! $i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
					$totalarray['val']['t.' . $key]                      += $lastEvaluation->$key;
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
		if ($permissiontoadd) {
			print '<i class="risk-unlink-shared wpeo-tooltip-event fas fa-unlink button-icon" aria-label="' . $langs->trans('UnlinkSharedRisk') . '" value="' . $risk->id . '"></i>';
		}
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
	print '</form>' . "\n";
	print '<!-- End form -->';
	print '</div>' . "\n";
	print '<!-- End div class="fichecenter" -->';

	dol_fiche_end();
