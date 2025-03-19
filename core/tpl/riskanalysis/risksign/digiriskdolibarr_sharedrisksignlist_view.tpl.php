<?php
$selectedfields_label = 'shared_risksignlist_selectedfields';
// Selection of new fields
require './../../class/actions_changeselectedfields.php';

print '<div class="fichecenter sharedrisksignlist wpeo-wrap">';
print '<form method="POST" id="searchFormSharedListRiskSigns" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">' . "\n";
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
	<!--	RISK SIGN-->
	<div class="messageSuccessRiskSignUnlinkShared notice hidden">
		<div class="wpeo-notice notice-success risksign-unlink-shared-success-notice">
			<input type="hidden" class="valueForUnlinkSharedRiskSign1" value="<?php echo $langs->trans('TheRiskSign') . ' ' ?>">
			<input type="hidden" class="valueForUnlinkSharedRiskSign2" value="<?php echo ' ' . $langs->trans('HasBeenUnlinkSharedF') ?>">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskSignWellUnlinkShared') ?></div>
				<div class="notice-subtitle">
					<a href="">
						<span class="text"></span>
					</a>
				</div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>
	<div class="messageErrorRiskSignUnlinkShared notice hidden">
		<div class="wpeo-notice notice-warning risksign-unlink-shared--error-notice">
			<input type="hidden" class="valueForUnlinkSharedRiskSign1" value="<?php echo $langs->trans('TheRiskSign') . ' ' ?>">
			<input type="hidden" class="valueForUnlinkSharedRiskSign2" value="<?php echo ' ' . $langs->trans('HasNotBeenUnlinkSharedF') ?>">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('RiskSignNotUnlinkShared') ?></div>
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

$digiriskelement    = new DigiriskElement($db);
$digiriskelement->fetch($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);
$trashList = $digiriskelement->getMultiEntityTrashList();
$digiriskelementtmp = new DigiriskElement($db);
$alldigiriskelement = $digiriskelementtmp->fetchAll('', '', 0, 0, array('customsql' => 'status > 0'), 'AND');

// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT DISTINCT ';
foreach ($risksign->fields as $key => $val) {
	$sql .= 't.' . $key . ', ';
}
// Add fields from extrafields
if ( ! empty($extrafields->attributes[$risksign->table_element]['label'])) {
	foreach ($extrafields->attributes[$risksign->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$risksign->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
}
// Add fields from hooks
$parameters                                                                                                                                            = array();
$reshook                                                                                                                                               = $hookmanager->executeHooks('printFieldListSelect', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
$sql                                                                                                                                                  .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql                                                                                                                                                   = preg_replace('/,\s*$/', '', $sql);
$sql                                                                                                                                                  .= " FROM " . MAIN_DB_PREFIX . $risksign->table_element . " as t";
$sql                                                                                                                                          		  .= " LEFT JOIN " . MAIN_DB_PREFIX . $digiriskelement->table_element . " as e on (t.fk_element = e.rowid)";
$sql                                                                                                                                         		  .= " INNER JOIN " . MAIN_DB_PREFIX . 'element_element' . " as el on (t.rowid = el.fk_source)";
if (isset($extrafields->attributes[$risksign->table_element]['label']) && is_array($extrafields->attributes[$risksign->table_element]['label']) && count($extrafields->attributes[$risksign->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $risksign->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
//if ($risksign->ismultientitymanaged == 1) $sql                                                                                                        .= " WHERE t.entity IN (" . getEntity($risksign->element) . ")";
//else $sql                                                                                                                                             .= " WHERE 1 = 1";
//$sql                                                                                                                                                  .= " AND fk_element = " . $id;
if ( empty($allRisks)) {
	$sql .= " AND el.fk_target = " . $id;
	$sql .= " AND el.sourcetype = 'digiriskdolibarr_risksign'";
	$sql .= " AND t.entity IN (" . getEntity($risksign->element) . ") ";
	foreach ($trashList as $deleted_element => $element_id) {
		$sql .= " AND fk_element !=" . $element_id;
	}
} else {
	foreach ($trashList as $deleted_element => $element_id) {
		$sql .= " AND fk_element !=" . $element_id;
	}
	$sql .= " AND fk_element > 0";
	$sql .= " AND el.fk_target IN (";
	foreach ($alldigiriskelement as $digiriskelementsingle) {
		$digiriskelementList[] = $digiriskelementsingle->id;
	}
	$digiriskelementList = array_unique($digiriskelementList);
	foreach ($digiriskelementList as $digiriskelementsinglefinal) {
		$sql .= $digiriskelementsinglefinal . ',';
	}
	$sql = dol_substr($sql, 0, -1);
	$sql .= ")";
	$sql .= " AND el.sourcetype = 'digiriskdolibarr_risksign'";
	$sql .= " AND t.entity IN (" . getEntity($risksign->element) . ") ";
}

$search['fk_element_shared'] = GETPOST('search_fk_element_shared');

foreach ($search as $key => $val) {
    // Skip 'status' search when value equals -1
    if ($key == 'status' && $val == -1) continue;

    // Safely retrieve the field definition
    $field = isset($risksign->fields[$key]) ? $risksign->fields[$key] : null;

    // Determine search mode based on field type (integer/float) if defined
    $mode_search = ($field && ($risksign->isInt($field) || $risksign->isFloat($field))) ? 1 : 0;

    // If field type is defined as an integer type, adjust search mode and value
    if (is_array($field) && isset($field['type']) && strpos($field['type'], 'integer:') === 0) {
        if ($val === '-1') {
            $val = '';
            $search[$key] = '';
        }
        $mode_search = 2;
    }

    // Force search mode to 1 for 'category'
    if ($key == 'category') {
        $mode_search = 1;
    }

    // Convert search value '-1' to empty string
    if ($val === '-1') {
        $val = '';
        $search[$key] = '';
    }

    // Build SQL only if search value is not empty and key is not 'fk_element'
    if ($val !== '' && $key != 'fk_element') {
        if ($key == 'ref') {
            $sql .= " AND (t.ref = '$val')";
        } elseif ($key == 'fk_element') {
            if ($val > 0) {
                $sql .= " AND (e.rowid = '$val')";
            }
        } elseif ($key == 'entity') {
            $sql .= " AND (e.entity = '$val')";
        } elseif ($key == 'fk_element_shared') {
            $sql .= " AND (t.fk_element = '$val')";
        } else {
            $sql .= natural_search('t.' . $key, $val, ($key == 'status' ? 2 : $mode_search));
        }
    }
}

if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook    = $hookmanager->executeHooks('printFieldListWhere', $parameters, $risksign); // Note that $action and $risksign may have been modified by hook
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
	$resql            = $db->query($sql);
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
	header("Location: " . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risksign.php', 1) . '?id=' . $id);
	exit;
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
$massactionbutton   = $form->selectMassAction('', $arrayofmassactions); ?>

	<!-- BUTTON MODAL RISKSIGN ADD -->
<?php if ($permissiontoadd) {
	$newcardbutton = '<div class="risksign-add wpeo-button button-square-40 button-blue modal-open" value="' . $object->id . '"><i class="fas fa-map-signs button-icon"></i><i class="fas fa-plus-circle button-add animated"></i></div>';
} else {
	$newcardbutton = '<div class="wpeo-button button-square-40 button-grey" value="' . $object->id . '"><i class="fas fa-map-signs button-icon wpeo-tooltip-event" aria-label="' . $langs->trans('PermissionDenied') . '"></i><i class="fas fa-plus-circle button-add animated"></i></div>';
} ?>

<?php $title = $langs->trans('DigiriskElementSharedRiskSignsList');
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

$arrayfields['t.entity']['checked']  = 1;
$arrayfields['t.entity']['label']    = $langs->trans('Entity');
$arrayfields['t.entity']['enabled']  = 1;
$arrayfields['t.entity']['position'] = 1;

$arrayfields['t.fk_element']['checked']  = 1;
$arrayfields['t.fk_element']['label']    = $langs->trans('ParentElement');
$arrayfields['t.fk_element']['enabled']  = 1;
$arrayfields['t.fk_element']['position'] = 5;

$arrayfields = dol_sort_array($arrayfields, 'position');

$selectedfields  = $form->multiSelectArrayWithCheckbox('shared_risksignlist_selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($risksign->fields as $key => $val) {
    // Set CSS classes for the field, add "center" for status field
    $cssforfield = !empty($val['css']) ? $val['css'] : '';
    if ($key === 'status') {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    }
    // Check if the field is checked in the arrayfields configuration
    if (!empty($arrayfields['t.' . $key]['checked'])) {
        print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
        // Use null coalescing to avoid undefined index warnings for search values
        $searchValue = $search[$key] ?? '';
        if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval'] )) {
            print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $searchValue, $val['notnull'] ?? '', 0, 0, '', 1, 0, 0, '', 'maxwidth75');
        } elseif (isset($val['type']) && strpos($val['type'], 'integer:') === 0) {
            print $risksign->showInputField($val, $key, $searchValue, '', '', 'search_', 'maxwidth150', 1);
        } elseif ($key === 'entity') {
            // Use null coalescing operator for search entity value
            $searchEntity = $search['entity'] ?? '';
            print select_entity_list($searchEntity, 'search_entity', 'e.rowid NOT IN (' . $conf->entity . ')');
        } elseif ($key === 'fk_element') {
			// Use null coalescing operator for shared fk_element search value
			$searchFkElement = $search['fk_element_shared'] ?? '';

			// Ensure that $conf->entity is defined and properly formatted as an integer or a comma‐separated list
			$entity = isset($conf->entity) ? $conf->entity : 0;
			if (is_array($entity)) {
				// Convert array values to integers and join with commas
				$entityList = implode(',', array_map('intval', $entity));
			} else {
				// Force single value to integer for safety
				$entityList = intval($entity);
			}

			// Remove alias 's.' because the alias might not exist in the query context
			$customsql = 'entity NOT IN (' . $entityList . ')';

			// Call the function with the corrected custom SQL clause
			print $digiriskelement->selectDigiriskElementList(
				$searchFkElement,
				'search_fk_element_shared',
				['customsql' => $customsql],
				1,
				0,
				[],
				0,
				0,
				'minwidth100 maxwidth300',
				0,
				false,
				1,
				$contextpage,
				false
			);

        } elseif ($key === 'category') { ?>
            <div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding" style="position: inherit">
                <input class="input-hidden-danger" type="hidden" name="<?php echo 'search_' . $key ?>" value="<?php echo dol_escape_htmltag($searchValue) ?>" />
                <?php if (dol_strlen(dol_escape_htmltag($searchValue)) == 0) : ?>
                    <div class="dropdown-toggle dropdown-add-button">
                        <span class="wpeo-button button-square-50 button-grey"><i class="fas fa-map-signs button-icon"></i></span>
                        <img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
                    </div>
                <?php else : ?>
                    <div class="dropdown-toggle dropdown-add-button wpeo-tooltip-event" aria-label="<?php echo (empty(dol_escape_htmltag($searchValue))) ? $risksign->getRiskSignCategoryName($risksign) : $risksign->getRiskSignCategoryNameByPosition($searchValue); ?>">
                        <img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . ((empty(dol_escape_htmltag($searchValue))) ? $risksign->getRiskSignCategory($risksign) : $risksign->getRiskSignCategoryByPosition($searchValue)) ?>" />
                    </div>
                <?php endif; ?>
                <ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
                    <?php
                    $risksignCategories = $risksign->getRiskSignCategories();
                    if (!empty($risksignCategories)) :
                        foreach ($risksignCategories as $risksignCategory) : ?>
                            <li class="item dropdown-item wpeo-tooltip-event classfortooltip" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $risksignCategory['position'] ?>" aria-label="<?php echo $risksignCategory['name'] ?>">
                                <img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $risksignCategory['name_thumbnail'] ?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
                            </li>
                        <?php endforeach;
                    endif; ?>
                </ul>
            </div>
        <?php } elseif (!preg_match('/^(date|timestamp)/', $val['type'] ?? '') && $key !== 'category') {
            print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag($searchValue) . '">';
        }
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
			elseif ($key == 'entity') { ?>
				<?php
				print getNomUrlEntity($risksign, 1, 'nolink', 1);
			} elseif ($key == 'fk_element') { ?>
				<?php $parent_element = new DigiriskElement($db);
				$result               = $parent_element->fetch($risksign->fk_element);
				if ($result > 0) {
					print $parent_element->getNomUrl(1, 'nolink', 0, '', -1, 1);
				}
			} elseif ($key == 'category') { ?>
				<div class="table-cell table-50 cell-risksign-category">
					<div class="wpeo-dropdown dropdown-large risksign-category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risksign->getRiskSignCategoryName($risksign); ?>">
						<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $risksign->getRiskSignCategory($risksign) ?>"/>
					</div>
				</div>
				<?php
			} elseif ($key == 'ref') {
				?>
				<div class="risksign-container" value="<?php echo $risksign->id ?>">
					<div><i class="fas fa-map-signs"></i><?php echo ' ' . $risksign->ref; ?></div>
				</div>
				<?php
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
	if ($permissiontoadd) {
		print '<i class="risksign-unlink-shared wpeo-tooltip-event fas fa-unlink button-icon" aria-label="' . $langs->trans('UnlinkSharedRiskSign') . '" value="' . $risksign->id . '"></i>';
	}
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

$db->free($resql);

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

print dol_get_fiche_end();
