<?php
	$selectedfields_label = 'inherited_risklist_selectedfields';
	// Selection of new fields
	require './../../class/actions_changeselectedfields.php';

	print '<div class="fichecenter inheritedrisklist wpeo-wrap">';
	print '<form method="POST" id="searchFormInheritedListRisks" enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id .'">' . "\n";
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
	if ($object->fk_parent > 0) {
		$advanced_method_cotation_json = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
		$advancedCotationMethodArray = json_decode($advanced_method_cotation_json, true);

		$digiriskelement                = new DigiriskElement($db);
		$riskAssessment                 = new RiskAssessment($db);
		$digiriskTask                   = new SaturneTask($db);
		$extrafields                    = new Extrafields($db);
		$usertmp                        = new User($db);
		$project                        = new Project($db);
		$DUProject                      = new Project($db);

		$advanced_method_cotation_json  = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
		$advancedCotationMethodArray = json_decode($advanced_method_cotation_json, true);

		$alldigiriskelement = $digiriskelement->getActiveDigiriskElements();

		$DUProject->fetch($riskType == 'risk' ? $conf->global->DIGIRISKDOLIBARR_DU_PROJECT : $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT);
		$extrafields->fetch_name_optionals_label($digiriskTask->table_element);

		$riskAssessmentList        = $riskAssessment->fetchAll();
		$riskAssessmentNextValue   = $refEvaluationMod->getNextValue($evaluation);
		$riskAssessmentTaskList    = $risk->getTasksWithFkRisk();
		$taskNextValue             = $refTaskMod->getNextValue('', $task);
		$usertmp->fetchAll();
		$usersList                 = $usertmp->users;
		$timeSpentSortedByTasks    = $digiriskTask->fetchAllTimeSpentAllUsers('AND fk_element > 0', 'element_datehour', 'DESC', 1);

		if (is_array($riskAssessmentList) && !empty($riskAssessmentList)) {
			foreach ($riskAssessmentList as $riskAssessmentSingle) {
				$riskAssessmentsOrderedByRisk[$riskAssessmentSingle->fk_risk][$riskAssessmentSingle->id] = $riskAssessmentSingle;
			}
		}
		// Build and execute select
		// --------------------------------------------------------------------
		if (!preg_match('/(evaluation)/', $sortfield)) {
			$sql = 'SELECT ';
			foreach ($risk->fields as $key => $val) {
				$sql .= 'r.' . $key . ', ';
			}
			// Add fields from extrafields
			if (!empty($extrafields->attributes[$risk->table_element]['label'])) {
				foreach ($extrafields->attributes[$risk->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$risk->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
			}
			// Add fields from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $risk); // Note that $action and $risk may have been modified by hook
			$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
			$sql = preg_replace('/,\s*$/', '', $sql);
			$sql .= " FROM " . MAIN_DB_PREFIX . $risk->table_element . " as r";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $digiriskelement->table_element . " as e on (r.fk_element = e.rowid)";
			if (is_array($extrafields->attributes[$risk->table_element]['label']) && count($extrafields->attributes[$risk->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $risk->table_element . "_extrafields as ef on (r.rowid = ef.fk_object)";
			if ($risk->ismultientitymanaged == 1) $sql .= " WHERE r.entity IN (" . getEntity($risk->element) . ")";
			else $sql .= " WHERE 1 = 1";
			if (!$allRisks) {
				$inherited_risk_id = $object->fk_parent;
				$sql .= " AND r.fk_element IN (" . $inherited_risk_id;
				while ($inherited_risk_id > 0) {
					$inherited_risk_id = $alldigiriskelement[$inherited_risk_id]->fk_parent;
					if ($inherited_risk_id > 0) {
						$sql .= ',' . $inherited_risk_id;
					}
				}
				$sql .= ")";
			} else {
				if (is_array($alldigiriskelement) && !empty($alldigiriskelement)) {
					$digiriskElementSqlFilter = '(';
					foreach (array_keys($alldigiriskelement) as $elementId) {
						$digiriskElementSqlFilter .= $elementId . ', ';
					}
					if (preg_match('/, /', $digiriskElementSqlFilter)) {
						$digiriskElementSqlFilter = rtrim($digiriskElementSqlFilter, ', ');
					}
					$digiriskElementSqlFilter .= ')';
					$sql .= " AND r.fk_element IN " . $digiriskElementSqlFilter;
				}
				$sql .= " AND fk_element > 0 ";
				$sql .= " AND e.entity IN (" . getEntity($risk->element) . ") ";
			}
            $sql .= ' AND r.type = "' . $riskType . '"';

			foreach ($search as $key => $val) {
				if ($key == 'status' && $search[$key] == -1) continue;
				$mode_search = (($risk->isInt($risk->fields[$key]) || $risk->isFloat($risk->fields[$key])) ? 1 : 0);
				if (strpos($risk->fields[$key]['type'], 'integer:') === 0) {
					if ($search[$key] == '-1') $search[$key] = '';
					$mode_search = 2;
				}
				if ($key == 'category') {
					$mode_search = 1;
				}
				if ($search[$key] == '-1') {
					$search[$key] = '';
				}
				if ($search[$key] != '') {
					if ($key == 'ref') {
						$sql .= " AND (r.ref = '$search[$key]')";
					} else {
						$sql .= natural_search('r.' . $key, $search[$key], (($key == 'status') ? 2 : $mode_search));
					}
				}
			}
			if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
			// Add where from extra fields
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
			// Add where from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $risk); // Note that $action and $risk may have been modified by hook
			$sql .= $hookmanager->resPrint;

			$sql .= $db->order($sortfield, $sortorder);

			// Count total nb of records
			$nbtotalofrecords = '';
			if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
				$resql = $db->query($sql);

				$nbtotalofrecords = $db->num_rows($resql);

				if (($page * $limit) > $nbtotalofrecords) {    // if total of record found is smaller than page * limit, goto and load page 0
					$page = 0;
					$offset = 0;
				}
			}

			// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
			if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
				$num = $nbtotalofrecords;
			} else {
				if ($limit) $sql .= $db->plimit($limit + 1, $offset);

				$resql = $db->query($sql);
				if (!$resql) {
					dol_print_error($db);
					exit;
				}

				$num = $db->num_rows($resql);
			}

			// Direct jump if only one record found
			if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page) {
				$obj = $db->fetch_object($resql);
				$id = $obj->rowid;
				header("Location: " . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $id);
				exit;
			}
		} else {
			$sql = 'SELECT ';
			foreach ($evaluation->fields as $key => $val) {
				$sql .= 'evaluation.' . $key . ', ';
			}
			// Add fields from extrafields
			if (!empty($extrafields->attributes[$evaluation->table_element]['label'])) {
				foreach ($extrafields->attributes[$evaluation->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$evaluation->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
			}
			// Add fields from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
			$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
			$sql = preg_replace('/,\s*$/', '', $sql);
			$sql .= " FROM " . MAIN_DB_PREFIX . $evaluation->table_element . " as evaluation";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $risk->table_element . " as r on (evaluation.fk_risk = r.rowid)";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $digiriskelement->table_element . " as e on (r.fk_element = e.rowid)";
			if (is_array($extrafields->attributes[$evaluation->table_element]['label']) && count($extrafields->attributes[$evaluation->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $evaluation->table_element . "_extrafields as ef on (evaluation.rowid = ef.fk_object)";
			if ($evaluation->ismultientitymanaged == 1) $sql .= " WHERE evaluation.entity IN (" . getEntity($evaluation->element) . ")";
			else $sql .= " WHERE 1 = 1";
			$sql .= " AND evaluation.status = 1";
			if (!$allRisks) {
				$inherited_risk_id = $object->fk_parent;
				$sql .= " AND r.fk_element IN (" . $inherited_risk_id;
				while ($inherited_risk_id > 0) {
					$inherited_risk_id = $alldigiriskelement[$inherited_risk_id]->fk_parent;
					if ($inherited_risk_id > 0) {
						$sql .= ',' . $inherited_risk_id;
					}
				}
				$sql .= ")";
			} else {
				if (is_array($alldigiriskelement) && !empty($alldigiriskelement)) {
					$digiriskElementSqlFilter = '(';
					foreach (array_keys($alldigiriskelement) as $elementId) {
						$digiriskElementSqlFilter .= $elementId . ', ';
					}
					if (preg_match('/, /', $digiriskElementSqlFilter)) {
						$digiriskElementSqlFilter = rtrim($digiriskElementSqlFilter, ', ');
					}
					$digiriskElementSqlFilter .= ')';
					$sql .= " AND r.fk_element IN " . $digiriskElementSqlFilter;
				}
				$sql .= " AND r.fk_element > 0";
				$sql .= " AND e.entity IN (" . getEntity($evaluation->element) . ")";
			}
            $sql .= ' AND r.type = "' . $riskType . '"';

			foreach ($search as $key => $val) {
				if ($key == 'status' && $search[$key] == -1) continue;
				$mode_search = (($evaluation->isInt($evaluation->fields[$key]) || $evaluation->isFloat($evaluation->fields[$key])) ? 1 : 0);
				if (strpos($evaluation->fields[$key]['type'], 'integer:') === 0) {
					if ($search[$key] == '-1') $search[$key] = '';
					$mode_search = 2;
				}
				if ($key == 'category') {
					$mode_search = 1;
				}
				if ($search[$key] == '-1') {
					$search[$key] = '';
				}
				if ($search[$key] != '') {
					if ($key == 'ref') {
						$sql .= " AND (r.ref = '$search[$key]')";
					} else {
						$sql .= natural_search('r.' . $key, $search[$key], (($key == 'status') ? 2 : $mode_search));
					}
				}
			}
			if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
			// Add where from extra fields
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
			// Add where from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
			$sql .= $hookmanager->resPrint;

			$sql .= $db->order($sortfield, $sortorder);

			// Count total nb of records
			$nbtotalofrecords = '';
			if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
				$resql = $db->query($sql);

				$nbtotalofrecords = $db->num_rows($resql);

				if (($page * $limit) > $nbtotalofrecords) {    // if total of record found is smaller than page * limit, goto and load page 0
					$page = 0;
					$offset = 0;
				}
			}

			// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
			if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
				$num = $nbtotalofrecords;
			} else {
				if ($limit) $sql .= $db->plimit($limit + 1, $offset);

				$resql = $db->query($sql);
				if (!$resql) {
					dol_print_error($db);
					exit;
				}

				$num = $db->num_rows($resql);
			}

			// Direct jump if only one record found
			if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page) {
				$obj = $db->fetch_object($resql);
				$id = $obj->rowid;
				header("Location: " . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $id);
				exit;
			}
		}
	} else {
		$num = 0;
		$nbtotalofrecords = 0;
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
	$arrayofmassactions = [];
    $massactionbutton   = $form->selectMassAction('', $arrayofmassactions);

	$title = $langs->trans('DigiriskElementInherited' . ucfirst($riskType) . 'sList');
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'digiriskdolibarr_color.png@digiriskdolibarr', 0, '', '', $limit, 0, 0, 1);

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

	$arrayfields['r.fk_element']['label'] = $langs->trans('ParentElement');
	$arrayfields['r.fk_element']['checked'] = 1;
	$arrayfields['r.fk_element']['enabled'] = 1;
	$arrayfields['r.fk_element']['position'] = 1;

	$arrayfields = dol_sort_array($arrayfields, 'position');

	$selectedfields  = $form->multiSelectArrayWithCheckbox('inherited_risklist_selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
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
				print $digiriskelement->selectDigiriskElementList($search['fk_element'], 'search_fk_element', [], 1, 0, array(), 0, 0, 'minwidth100 maxwidth300', 0, false, 1);
			} elseif ($key == 'category') { ?>
				<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding" style="position: inherit">
					<input class="input-hidden-danger" type="hidden" name="<?php echo 'search_' . $key ?>" value="<?php echo dol_escape_htmltag($search[$key]) ?>" />
					<?php if (dol_strlen(dol_escape_htmltag($search[$key])) == 0) : ?>
						<div class="dropdown-toggle dropdown-add-button button-cotation">
							<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i></span>
							<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
						</div>
					<?php else : ?>
						<div class="dropdown-toggle dropdown-add-button button-cotation wpeo-tooltip-event" aria-label="<?php echo (empty(dol_escape_htmltag($search[$key]))) ? $risk->getDangerCategoryName($risk, $riskType) : $risk->getDangerCategoryNameByPosition($search[$key], $riskType); ?>">
							<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . ((empty(dol_escape_htmltag($search[$key]))) ? $risk->getDangerCategory($risk, $riskType) : $risk->getDangerCategoryByPosition($search[$key], $riskType)) . '.png'?>" />
						</div>
					<?php endif; ?>
					<ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
						<?php
						$dangerCategories = Risk::getDangerCategories($riskType);
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
					if (is_object($alldigiriskelement[$risk->fk_element])) {
						print $alldigiriskelement[$risk->fk_element]->getNomUrl(1, 'blank', 0, '', -1, 1);
					}
				} elseif ($key == 'category') { ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risk->getDangerCategoryName($risk, $riskType) ?>">
							<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($risk, $riskType) . '.png' ; ?>"/>
						</div>
					</div>
					<?php
				} elseif ($key == 'ref') {
					print $risk->getNomUrl(1, 'nolink');
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

	if ($object->fk_parent > 0 ) {
		$db->free($resql);
	}

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
