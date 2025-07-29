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
 *   	\file       view/digiriskelement/digiriskelement_risk.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view risk
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $hookmanager, $langs, $mc, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id             = GETPOST('id', 'int');
$action         = GETPOST('action', 'aZ09');
$subaction      = GETPOST('subaction', 'aZ09');
$massaction     = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm        = GETPOST('confirm', 'alpha');
$cancel         = GETPOST('cancel', 'aZ09');
$contextpage    = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'riskcard'; // To manage different context of search
$backtopage     = GETPOST('backtopage', 'alpha');
$toselect       = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$limit          = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield      = GETPOST('sortfield', 'alpha');
$sortorder      = GETPOST('sortorder', 'alpha');
$sharedrisks    = GETPOST('sharedrisks', 'int') ? GETPOST('sharedrisks', 'int') : $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS;
$inheritedrisks = GETPOST('inheritedrisks', 'int') ? GETPOST('inheritedrisks', 'int') : $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_LISTINGS;
$riskType       = GETPOSTISSET('risk_type') ? GETPOST('risk_type') : 'risk';
$page           = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page           = is_numeric($page) ? $page : 0;
$page           = $page == -1 ? 0 : $page;
if (isModEnabled('categorie')) {
    $search_category_array = GETPOST('search_category_risk_list', 'array');
}

// Initialize technical objects
$object           = new DigiriskElement($db);
$digiriskelement  = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$risk             = new Risk($db);
$evaluation       = new RiskAssessment($db);
$ecmdir           = new EcmDirectory($db);
$project          = new Project($db);
$task             = new SaturneTask($db);
$extrafields      = new ExtraFields($db);
$DUProject        = new Project($db);

$numberingModuleName = [
	'riskanalysis/' . $risk->element       => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
	'riskanalysis/' . $evaluation->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
	$project->element                      => $conf->global->PROJECT_ADDON,
	'project/task'                         => $conf->global->PROJECT_TASK_ADDON,
];

list($refRiskMod, $refEvaluationMod, $refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

$DUProject->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
$hookmanager->initHooks(array('riskcard', 'digiriskelementview', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($risk->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($risk->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if ( ! $sortfield) $sortfield = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? "evaluation.cotation" : "r." . key($risk->fields);; // Set here default search field. By default 1st field in definition.
if ( ! $sortorder) $sortorder         = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? "DESC" : "ASC" ;
if ( ! $evalsortfield) $evalsortfield = "evaluation." . key($evaluation->fields);

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search     = array();
foreach ($risk->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha') !== '') $search[$key] = GETPOST('search_' . $key, 'alpha');

	if ($key == 'fk_element' && $contextpage == 'sharedrisk') {
		$search[$key] = GETPOST('search_' . $key . '_sharedrisk', 'alpha');
	}
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($risk->fields as $key => $val) {
	if ($val['searchall']) $fieldstosearchall['r.' . $key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($risk->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if ($val['label'] == 'Entity' || $val['label'] == 'ParentElement') {
		$val['visible'] = 0;
	}
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible'], 1);
        $arrayfields['r.' . $key] = [
            'label'       => $val['label'],
            'checked'     => (($visible < 0) ? 0 : 1),
            'enabled'     => ($visible != 3 && dol_eval($val['enabled'], 1)),
            'position'    => $val['position'],
            'help'        => $val['help']
        ];
    }
}

foreach ($evaluation->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible'], 1);
        $arrayfields['evaluation.' . $key] = [
            'label'       => $val['label'],
            'checked'     => (($visible < 0) ? 0 : 1),
            'enabled'     => ($visible != 3 && dol_eval($val['enabled'], 1)),
            'position'    => $val['position'],
            'help'        => $val['help']
        ];
    }
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$risk->fields = dol_sort_array($risk->fields, 'position');
$evaluation->fields = dol_sort_array($evaluation->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_risk
$permissiontoread   = $user->rights->digiriskdolibarr->risk->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $risk, $action); // Note that $action and $risk may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($risk->fields as $key => $val) {
			$search[$key] = '';
		}
		foreach ($evaluation->fields as $key => $val) {
			$search[$key] = '';
		}
		$toselect             = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;

	$backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__') . '&risk_type=' . $riskType;

	require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_risk_actions.tpl.php';
}

/*
 * View
 */

$form    = new Form($db);
$title   = $langs->trans(ucfirst($riskType) . 's');
$helpUrl = 'FR:Module_Digirisk#.C3.89valuation_des_Risques';

digirisk_header($title, $helpUrl);

if ($conf->browser->layout == 'phone') {
    $onPhone = 1;
} else {
    $onPhone = 0;
}

print '<div id="cardContent" value="">';

if ($sharedrisks) {
	$formconfirm = '';

	$alldigiriskelement = $digiriskelement->getActiveDigiriskElements('shared');

	// Import shared risks confirmation
	if (($action == 'import_shared_risks' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))        // Output when action = clone if jmobile or no js
		|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {                            // Always output when not jmobile nor js

		$allrisks = $risk->fetchAll('ASC', 'fk_element', 0, 0, array('customsql' => 'status > 0 AND type = "' . $riskType . '" AND entity NOT IN (' . $conf->entity . ') AND fk_element > 0'));
		$formquestionimportsharedrisks = array(
			'text' => '<i class="fas fa-circle-info"></i>' . $langs->trans('ConfirmImportShared' . ucfirst($riskType) . 's'),
		);

        $evaluation->ismultientitymanaged = 0;
		$riskAssessmentList = $evaluation->fetchAll('', '', 0, 0, array('customsql' => ' entity NOT IN (' . $conf->entity . ')'), 'AND', 1);
        $evaluation->ismultientitymanaged = 1;

		if (is_array($riskAssessmentList) && !empty($riskAssessmentList)) {
			foreach ($riskAssessmentList as $riskAssessmentSingle) {
				$riskAssessmentsOrderedByRisk[$riskAssessmentSingle->fk_risk][$riskAssessmentSingle->id] = $riskAssessmentSingle;
			}
		}

		$formquestionimportsharedrisks[] = array('type' => 'checkbox', 'name' =>'select_all_shared_elements', 'value' => 0);

		$previousDigiriskElement = 0;
		foreach ($allrisks as $key => $risks) {
			$digiriskelementtmp = $alldigiriskelement[$risks->fk_element];

			if(is_object($digiriskelementtmp)) {
				$digiriskelementtmp->element = 'digiriskdolibarr';
				$digiriskelementtmp->fetchObjectLinked($risks->id, 'digiriskdolibarr_risk', $object->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
				$alreadyImported = !empty($digiriskelementtmp->linkedObjectsIds['digiriskdolibarr_risk']);
				if (!isset($entityName[$risks->entity])) {
					$entityName[$risks->entity] = dolibarr_get_const($db, 'MAIN_INFO_SOCIETE_NOM', $risks->entity);
				}
				$riskAssessmentsOfRisk = $riskAssessmentsOrderedByRisk[$risks->id];

				if (is_array($riskAssessmentsOfRisk) && !empty($riskAssessmentsOfRisk)) {
					$lastEvaluation    = array_filter($riskAssessmentsOfRisk, function($lastRiskAssessment) {
						return $lastRiskAssessment->status == 1;
					});
					$lastEvaluation = array_shift($lastEvaluation);
				} else {
					$lastEvaluation = new RiskAssessment($db);
				}

				if (array_key_exists($digiriskelementtmp->id, $alldigiriskelement)) {
					$photoRisk = '<img class="danger-category-pic hover" src=' . DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risks->getDangerCategory($risks, $risks->type) . '.png' . '>';

					$importValue = '<div class="importsharedrisk"><span class="importsharedrisk-ref">' . 'S' . $risks->entity . '</span>';
					$importValue .= '<span>' . dol_trunc($entityName[$risks->entity], 32) . '</span>';
					$importValue .= '</div>';

					$importValue .= '<div class="importsharedrisk"><span class="importsharedrisk-ref">' . $digiriskelementtmp->ref . '</span>';
					$importValue .= '<span>' . dol_trunc($digiriskelementtmp->label, 32) . '</span>';
					$importValue .= '</div>';

					$importValue .= '<div class="importsharedrisk">';
					$importValue .= $photoRisk;
					$importValue .= '<span class="importsharedrisk-ref">' . $risks->ref  . '</span>';
					$importValue .= '<span>' . dol_trunc($risks->description, 32) . '</span>';
					$importValue .= '</div>';

					$importValue .= '<div class="importsharedrisk risk-evaluation-cotation"  data-scale="'. $lastEvaluation->getEvaluationScale() .'">';
					$importValue .= '<span class="importsharedrisk-risk-assessment">' . (!empty($lastEvaluation->cotation) ? $lastEvaluation->cotation : 0) . '</span>';
					$importValue .= '</div>';

					$relativepath = 'digiriskdolibarr/medias/thumbs/';
					$entity = ($conf->entity > 1) ? '/' . $risks->entity : '';
					$modulepart   = $entity . 'ecm';
					$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
					$pathToThumb  = DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $risks->entity . '&file=' . urlencode($lastEvaluation->element . '/' . $lastEvaluation->ref . '/thumbs/');
					$nophoto      = DOL_URL_ROOT.'/public/theme/common/nophoto.png';

					$importValue .= '<div class="risk-evaluation-photo risk-evaluation-photo-'. ($lastEvaluation->id > 0 ? $lastEvaluation->id : 0) .  ($risk->id > 0 ? ' risk-' . $risk->id : ' risk-new') .' open-medias-linked" style="margin-right: 0.5em">';
					$importValue .= '<span class="risk-evaluation-photo-single">';
					$importValue .= '<input class="filepath-to-riskassessment filepath-to-riskassessment-'.( $risk->id > 0 ? $risk->id : 'new') .'" type="hidden" value="'. $pathToThumb .'">';
					$importValue .=	'<input class="filename" type="hidden" value="">';
					if (isset($lastEvaluation->photo) && dol_strlen($lastEvaluation->photo) > 0) {
						$accessallowed = 1;
						$thumb_name = getThumbName($lastEvaluation->photo);
						$importValue .=	 '<img width="40" height="40" class="photo clicked-photo-preview" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $risks->entity . '&file=' . urlencode($lastEvaluation->element . '/' . $lastEvaluation->ref . '/thumbs/' . $thumb_name) . '" >';
					} else {
						$importValue .=	 '<img width="40" height="40" class="photo clicked-photo-preview" src="'. $nophoto .'" >';
					}
					$importValue .= '</span></div>';

					$importValue .= '<div class="importsharedrisk">';
					$importValue .= '<span class="importsharedrisk-risk-assessment">' ;
					$importValue .=  nl2br(dol_trunc($lastEvaluation->comment, 120));
					$importValue .=  '</span>';
					$importValue .= '</div>';

					if ($alreadyImported == 0 && $previousDigiriskElement != $digiriskelementtmp->id) {
						$importValue .= '<input type="checkbox" id="select_all_shared_elements_by_digiriskelement" name="' . $digiriskelementtmp->id . '" value="0">';
					}
					$previousDigiriskElement = $digiriskelementtmp->id;

					if ($alreadyImported > 0) {
						$formquestionimportsharedrisks[] = array('type' => 'checkbox', 'morecss' => 'importsharedelement-digiriskelement-'.$digiriskelementtmp->id, 'name' => $risks->id, 'label' => $importValue . '<span class="importsharedrisk imported">' . $langs->trans('AlreadyImported') . '</span>', 'value' => 0, 'disabled' => 1);
					} else {
						$formquestionimportsharedrisks[] = array('type' => 'checkbox', 'morecss' => 'importsharedelement-digiriskelement-'.$digiriskelementtmp->id, 'name' => $risks->id, 'label' => $importValue, 'value' => 0);
					}
				}
			}

		}
		$formconfirm .= digiriskformconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&risk_type=' . $riskType, $langs->trans('ImportShared' . ucfirst($riskType) . 's'), '', 'confirm_import_shared_risks', $formquestionimportsharedrisks, 'yes', 'actionButtonImportSharedRisks', 800, 800);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'object' => $object);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;
}

if ($object->id > 0) {
	$res = $object->fetch_optionals();

	saturne_get_fiche_head($object, 'element' . ucfirst($riskType), $title);

	// Object card
	// ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

	// Buttons for actions
	print '<div class="tabsAction" >';
	if ($permissiontoadd && !empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS)) {
		print '<span class="butAction" id="actionButtonImportSharedRisks" title="" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&risk_type=' . $riskType . '&action=import_shared_risks' . '">' . $langs->trans('ImportShared' . ucfirst($riskType) . 's') . '</span>';
	}
	print '</div>';

	if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKS == 1) {
		$contextpage = 'risklist';
		require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_risklist_view.tpl.php';
	}

	if ($conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_LISTINGS == 1) {
		$contextpage = 'inheritedrisk';
		require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_inheritedrisklist_view.tpl.php';
	}

	if ($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS == 1) {
		$contextpage = 'sharedrisk';
		require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_sharedrisklist_view.tpl.php';
	}

	require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_psychosocial_risk_modal.tpl.php';
}

?>
<script>
	$('.ulrisklist_selectedfields').attr('style','z-index:1050')
	$('.ulinherited_risklist_selectedfields').attr('style','z-index:1050')
	$('.ulshared_risklist_selectedfields').attr('style','z-index:1050')
</script>
<?php
print '</div>' . "\n";
print '<!-- End div class="cardcontent" -->';

// End of page
llxFooter();
$db->close();
