<?php
$selectedfields_label = 'inherited_risksignlist_selectedfields';
// Selection of new fields
require './../../class/actions_changeselectedfields.php';

print '<div class="fichecenter inheritedrisksignlist wpeo-wrap">';
print '<form method="POST" id="searchFormInheritedListRiskSigns" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">' . "\n";
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="id" value="' . $id . '">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

if ($object->fk_parent > 0) {
	// Build and execute select
	// --------------------------------------------------------------------
	$sql = 'SELECT ';
	foreach ($risksign->fields as $key => $val) {
		$sql .= 't.' . $key . ', ';
	}
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$risksign->table_element]['label'])) {
		foreach ($extrafields->attributes[$risksign->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$risksign->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
	}
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
	$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= " FROM " . MAIN_DB_PREFIX . $risksign->table_element . " as t";
	if (is_array($extrafields->attributes[$risksign->table_element]['label']) && count($extrafields->attributes[$risksign->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $risksign->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
	if ($risksign->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (" . getEntity($risksign->element) . ")";
	else $sql .= " WHERE 1 = 1";
	$inherited_risksign_id = $object->fk_parent;
	$sql .= " AND t.fk_element IN (" . $inherited_risksign_id;
	while ($inherited_risksign_id > 0) {
		$digiriskelementtmp = new DigiriskElement($db);
		$digiriskelementtmp->fetch($inherited_risksign_id);
		$inherited_risksign_id = $digiriskelementtmp->fk_parent;
		if ($inherited_risksign_id > 0) {
			$sql .= ',' . $inherited_risksign_id;
		}
	}
	$sql .= ")";
    $sql .= ' AND t.status > 0'; // We only want to show inherited risksigns that are not deleted

	foreach ($search as $key => $val) {
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($risksign->isInt($risksign->fields[$key]) || $risksign->isFloat($risksign->fields[$key])) ? 1 : 0);
		if (strpos($risksign->fields[$key]['type'], 'integer:') === 0) {
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
				$sql .= " AND (t.ref = '$search[$key]')";
			} else {
				$sql .= natural_search('t.' . $key, $search[$key], (($key == 'status') ? 2 : $mode_search));
			}
		}
	}
	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
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
		header("Location: " . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risksign.php', 1) . '?id=' . $id);
		exit;
	}
}  else {
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

$title = $langs->trans('DigiriskElementInheritedRiskSignsList');
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'digiriskdolibarr_color.png@digiriskdolibarr', 0, '', '', $limit, 0, 0, 1);

include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . join(', ', $fieldstosearchall) . '</div>';
}

$moreforfilter                       = '';
$parameters                          = array();
$reshook                             = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter                  = $hookmanager->resPrint;

if ( ! empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$arrayfields['t.fk_element']['checked']  = 1;
$arrayfields['t.fk_element']['label']    = $langs->trans('ParentElement');
$arrayfields['t.fk_element']['enabled']  = 1;
$arrayfields['t.fk_element']['position'] = 1;

$arrayfields = dol_sort_array($arrayfields, 'position');

$selectedfields  = $form->multiSelectArrayWithCheckbox('inherited_risksignlist_selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($risksign->fields as $key => $val) {
	$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if ( ! empty($arrayfields['t.' . $key]['checked'])) {
		print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $risksign->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		} elseif ($key == 'fk_element') {
			print $digiriskelement->selectDigiriskElementList($search['fk_element'], 'search_fk_element', [], 1, 0, array(), 0, 0, 'minwidth100 maxwidth300', 0, false, 1);
		} elseif ($key == 'category') { ?>
			<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding" style="position: inherit">
				<input class="input-hidden-danger" type="hidden" name="<?php echo 'search_' . $key ?>" value="<?php echo dol_escape_htmltag($search[$key]) ?>" />
				<?php if (dol_strlen(dol_escape_htmltag($search[$key])) == 0) : ?>
					<div class="dropdown-toggle dropdown-add-button">
						<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-map-signs button-icon"></i></span>
						<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
					</div>
				<?php else : ?>
					<div class="dropdown-toggle dropdown-add-button wpeo-tooltip-event" aria-label="<?php echo (empty(dol_escape_htmltag($search[$key]))) ? $risksign->getRiskSignCategoryName($risksign) : $risksign->getRiskSignCategoryNameByPosition($search[$key]); ?>">
						<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . ((empty(dol_escape_htmltag($search[$key]))) ? $risksign->getRiskSignCategory($risksign) : $risksign->getRiskSignCategoryByPosition($search[$key])) ?>" />
					</div>
				<?php endif; ?>
				<ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
					<?php
					$risksignCategories = $risksign->getRiskSignCategories();
					if ( ! empty($risksignCategories) ) :
						foreach ($risksignCategories as $risksignCategory) : ?>
							<li class="item dropdown-item wpeo-tooltip-event classfortooltip" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $risksignCategory['position'] ?>" aria-label="<?php echo $risksignCategory['name'] ?>">
								<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $risksignCategory['name_thumbnail'] ?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
							</li>
						<?php endforeach;
					endif; ?>
				</ul>
			</div>
		<?php } elseif ( ! preg_match('/^(date|timestamp)/', $val['type']) && $key != 'category') print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key]) . '">';
		print '</td>';
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook    = $hookmanager->executeHooks('printFieldListOption', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
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
foreach ($risksign->fields as $key => $val) {
	$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if ( ! empty($arrayfields['t.' . $key]['checked'])) {
		print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook    = $hookmanager->executeHooks('printFieldListTitle', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
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

	// Store properties in $risksign
	$risksign->setVarsFromFetchObj($obj);

	// Show here line of result
	print '<tr class="oddeven risksign-row risksign_row_' . $risksign->id . '" id="risksign_row_' . $risksign->id . '">';

	foreach ($risksign->fields as $key => $val) {
		$cssforfield                         = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield  .= ($cssforfield ? ' ' : '') . 'center';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		if ( ! empty($arrayfields['t.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . '>';
			if ($key == 'status') print $risksign->getLibStatut(5);
			elseif ($key == 'fk_element') { ?>
				<?php $parent_element = new DigiriskElement($db);
				$result               = $parent_element->fetch($risksign->fk_element);
				if ($result > 0) {
					print $parent_element->getNomUrl(1, 'blank', 0, '', -1, 1);
				}
			} elseif ($key == 'category') { ?>
				<div class="table-cell table-50 cell-risksign-category">
					<div class="wpeo-dropdown dropdown-large risksign-category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risksign->getRiskSignCategoryName($risksign); ?>">
						<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $risksign->getRiskSignCategory($risksign) ?>"/>
					</div>
				</div>
				<?php
			} elseif ($key == 'ref') {
				print $risksign->getNomUrl(1, 'blank');
			} elseif ($key == 'description') {
				print dol_htmlentitiesbr(dol_trunc($risksign->description, 128, 'wrap', 'UTF-8', 0, 1));
			} else print $risksign->showOutputField($val, $key, $risksign->$key, '');
			print '</td>';
			if ( ! $i) $totalarray['nbfield']++;
			if ( ! empty($val['isameasure'])) {
				if ( ! $i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
				$totalarray['val']['t.' . $key]                      += $risksign->$key;
			}
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'object' => $risksign, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook    = $hookmanager->executeHooks('printFieldListValue', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected                                                = 0;
		if (in_array($risksign->id, $arrayofselected)) $selected = 1;
		print '<input id="cb' . $risksign->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $risksign->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
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
$reshook    = $hookmanager->executeHooks('printFieldListFooter', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
print $hookmanager->resPrint; ?>

<?php print '</table>' . "\n";
print '<!-- End table -->';
print '</div>' . "\n";
print '<!-- End div class="div-table-responsive" -->';
print '</form>' . "\n";
print '<!-- End form -->';
print '</div>' . "\n";
print '<!-- End div class="fichecenter" -->';

