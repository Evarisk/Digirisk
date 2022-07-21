<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/project/mod_project_simple.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/project/task/mod_task_simple.php';

require_once './../../class/digiriskelement.class.php';
require_once './../../class/digiriskstandard.class.php';
require_once './../../class/riskanalysis/risk.class.php';
require_once './../../class/riskanalysis/riskassessment.class.php';
require_once './../../core/modules/digiriskdolibarr/riskanalysis/risk/mod_risk_standard.php';
require_once './../../core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';
require_once './../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once './../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id             = GETPOST('id', 'int');
$action         = GETPOST('action', 'aZ09');
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
$inheritedrisks = GETPOST('inheritedrisks', 'int') ? GETPOST('inheritedrisks', 'int') : $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS;
$page           = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page           = is_numeric($page) ? $page : 0;
$page           = $page == -1 ? 0 : $page;

// Initialize technical objects
$object           = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$risk             = new Risk($db);
$evaluation       = new RiskAssessment($db);
$ecmdir           = new EcmDirectory($db);
$project          = new Project($db);
$task             = new Task($db);
$extrafields      = new ExtraFields($db);
$refRiskMod       = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
$refEvaluationMod = new $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON();
$refProjectMod    = new $conf->global->PROJECT_ADDON();
$refTaskMod       = new $conf->global->PROJECT_TASK_ADDON();

$hookmanager->initHooks(array('riskcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($risk->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($risk->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if ( ! $sortfield) $sortfield = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? "evaluation.cotation" : "t." . key($risk->fields);; // Set here default search field. By default 1st field in definition.
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
	if ($val['searchall']) $fieldstosearchall['t.' . $key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($risk->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if ($val['label'] == 'Entity' || $val['label'] == 'ParentElement') {
		$val['visible'] = 0;
	}
	if ( ! empty($val['visible'])) $arrayfields['t.' . $key] = array('label' => $val['label'], 'checked' => (($val['visible'] < 0) ? 0 : 1), 'enabled' => ($val['enabled'] && ($val['visible'] != 3)), 'position' => $val['position']);
}

foreach ($evaluation->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if ( ! empty($val['visible'])) $arrayfields['evaluation.' . $key] = array('label' => $val['label'], 'checked' => (($val['visible'] < 0) ? 0 : 1), 'enabled' => ($val['enabled'] && ($val['visible'] != 3)), 'position' => $val['position']);
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
if ( ! $permissiontoread) accessforbidden();
require_once './../../core/tpl/digirisk_security_checks.php';

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $risk, $action); // Note that $action and $risk may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

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

	$backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__');

	require_once './../../core/tpl/riskanalysis/risk/digiriskdolibarr_risk_actions.tpl.php';
}

/*
 * View
 */

$form = new Form($db);
$title    = $langs->trans("DigiriskElementRisk");
$help_url = 'FR:Module_DigiriskDolibarr#Risques';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader($title, $help_url, $morejs, $morecss);

print '<div id="cardContent" value="">';

if ($sharedrisks) {
	$formconfirm = '';

	// Import shared risks confirmation
	if (($action == 'import_shared_risks' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))        // Output when action = clone if jmobile or no js
		|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {                            // Always output when not jmobile nor js

		$digiriskelementtmp = new DigiriskElement($db);
		$risk_assessment = new RiskAssessment($db);

//		$AllSharingsRisks = $conf->mc->sharings['risk'];
//
//		foreach ($AllSharingsRisks as $Allsharingsrisk) {
//			$filter .= $Allsharingsrisk . ',';
//		}
//
//		$filter = rtrim($filter, ',');

		$allrisks = $risk->fetchAll('ASC', 'fk_element', 0, 0, array('customsql' => 'status > 0 AND entity NOT IN (' . $conf->entity . ') AND fk_element > 0'));

		$formquestionimportsharedrisks = array(
			'text' => '<i class="fas fa-circle-info"></i>' . $langs->trans("ConfirmImportSharedRisks"),
		);

		$formquestionimportsharedrisks[] = array('type' => 'checkbox', 'name' =>'select_all_shared_risks', 'value' => 0);

		foreach ($allrisks as $key => $risks) {
			$digiriskelementtmp->fetch($risks->fk_element);
			$digiriskelementtmp->element = 'digiriskdolibarr';
			$digiriskelementtmp->fetchObjectLinked($risks->id, 'digiriskdolibarr_risk', $object->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
			$alreadyImported = !empty($digiriskelementtmp->linkedObjectsIds) ? 1 : 0;
			$nameEntity = dolibarr_get_const($db, 'MAIN_INFO_SOCIETE_NOM', $risks->entity);
			$lastEvaluation = $risk_assessment->fetchFromParent($risks->id, 1);
			if (!empty($lastEvaluation)) {
				$lastEvaluation = array_shift($lastEvaluation);
			}

//			$pathToThumb = DOL_URL_ROOT . '/viewimage.php?modulepart=digiriskdolibarr&entity=' . $risks->entity . '&file=' . urlencode($digiriskelementtmp->element_type . '/' . $digiriskelementtmp->ref . '/thumbs/');
//			$filearray   = dol_dir_list($conf->digiriskdolibarr->multidir_output[$risks->entity] . '/' . $digiriskelementtmp->element_type . '/' . $digiriskelementtmp->ref . '/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
//
//			if (count($filearray)) {
//				$photoDigiriskElement = '<span class="floatleft inline-block valignmiddle divphotoref open-medias-linked modal-open digirisk-element digirisk-element-' . $digiriskelementtmp->id . '" value="' . $digiriskelementtmp->id . '">';
//				$photoDigiriskElement .= '<img width="50" height="50" class="photo clicked-photo-preview" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode($digiriskelementtmp->element_type . '/' . $digiriskelementtmp->ref . '/thumbs/' . preg_replace('/\./', '_small.', $digiriskelementtmp->photo)) . '" >';
//				$photoDigiriskElement .= '<input type="hidden" class="filepath-to-digiriskelement" value="' . $pathToThumb . '"/>';
//				$photoDigiriskElement .= '</span>';
//			} else {
//				$nophoto = '/public/theme/common/nophoto.png';
//				$photoDigiriskElement = '<div class="open-media-gallery modal-open digiriskelement digirisk-element-' . $digiriskelementtmp->id . '" value="'. $digiriskelementtmp->id . '">';
//				$photoDigiriskElement .= '<input type="hidden" class="type-from" value="digiriskelement"/>';
//				$photoDigiriskElement .= '<input type="hidden" class="filepath-to-digiriskelement" value="' . $pathToThumb . '"/>';
//				$photoDigiriskElement .= '<span class="floatleft inline-block valignmiddle divphotoref"><img width="50" height="50" class="photo photowithmargin clicked-photo-preview" alt="No photo" src=' . DOL_URL_ROOT . $nophoto . '></span>';
//				$photoDigiriskElement .= '</div>';
//			}

			$photoRisk = '<img class="danger-category-pic hover" src=' . DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risks->get_danger_category($risks) . '.png' . '>';

			$importValue = '<div class="importsharedrisk"><span class="importsharedrisk-ref">' . 'S' . $risks->entity . '</span>';
			$importValue .= '<span>' . dol_trunc($nameEntity, 32) . '</span>';
			$importValue .= '</div>';

			$importValue .= '<div class="importsharedrisk"><span class="importsharedrisk-ref">' . $digiriskelementtmp->ref . '</span>';
			$importValue .= '<span>' . dol_trunc($digiriskelementtmp->label, 32) . '</span>';
			$importValue .= '</div>';

			$importValue .= '<div class="importsharedrisk">';
			$importValue .= $photoRisk;
			$importValue .= '<span class="importsharedrisk-ref">' . $risks->ref  . '</span>';
			$importValue .= '<span>' . dol_trunc($risks->description, 32) . '</span>';
			$importValue .= '</div>';

			$importValue .= '<div class="importsharedrisk risk-evaluation-cotation"  data-scale="'. $lastEvaluation->get_evaluation_scale() .'">';
			$importValue .= '<span class="importsharedrisk-risk-assessment">' . $lastEvaluation->cotation  . '</span>';
			$importValue .= '</div>';

			$relativepath = 'digiriskdolibarr/medias/thumbs/';
			$entity = ($conf->entity > 1) ? '/' . $risks->entity : '';
			$modulepart   = $entity . 'ecm';
			$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
			$pathToThumb  = DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $risks->entity . '&file=' . urlencode($lastEvaluation->element . '/' . $lastEvaluation->ref . '/thumbs/');
			$nophoto      = DOL_URL_ROOT.'/public/theme/common/nophoto.png';

			$importValue .= '<div class="risk-evaluation-photo risk-evaluation-photo-'. ($lastEvaluation->id > 0 ? $lastEvaluation->id : 0) .  ($risk->id > 0 ? ' risk-' . $risk->id : ' risk-new') .' open-medias-linked">';
			$importValue .= '<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">';
			$importValue .= '<input class="filepath-to-riskassessment filepath-to-riskassessment-'.( $risk->id > 0 ? $risk->id : 'new') .'" type="hidden" value="'. $pathToThumb .'">';
			$importValue .=	'<input class="filename" type="hidden" value="">';
			if (isset($lastEvaluation->photo) && dol_strlen($lastEvaluation->photo) > 0) {
				$accessallowed = 1;

//				$importValue .=	 '<img width="40" class="photo clicked-photo-preview" src="' . $conf->digiriskdolibarr->multidir_output[$risks->entity?:1] . $lastEvaluation->element . '/' . $lastEvaluation->ref . '/thumbs/' . preg_replace('/\./', '_small.', $lastEvaluation->photo)) . '" >';
//				$importValue .=	 '<img width="40" class="photo clicked-photo-preview" src="' . DOL_DATA_ROOT . $entity . '/digiriskdolibarr/'. $lastEvaluation->element . '/' . $lastEvaluation->ref . '/thumbs/' . preg_replace('/\./', '_small.', $lastEvaluation->photo) . '" >';
				$importValue .=	 '<img width="40" class="photo clicked-photo-preview" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $risks->entity . '&file=' . urlencode($lastEvaluation->element . '/' . $lastEvaluation->ref . '/thumbs/' . preg_replace('/\./', '_small.', $lastEvaluation->photo)) . '" >';
			} else {
				$importValue .=	 '<img width="40" class="photo clicked-photo-preview" src="'. $nophoto .'" >';
			}
			$importValue .= '</span></div>';

			$importValue .= '<div class="importsharedrisk">';
			$importValue .= '<span class="importsharedrisk-risk-assessment">' ;
			$importValue .=  nl2br(dol_trunc($lastEvaluation->comment, 120));
			$importValue .=  '</span>';
			$importValue .= '</div>';

			if ($alreadyImported > 0) {
				$formquestionimportsharedrisks[] = array('type' => 'checkbox', 'name' => 'import_shared_risks' . '_S' . $risks->entity . '_' . $digiriskelementtmp->ref . '_' . $risks->ref, 'label' => $importValue . '<span class="importsharedrisk imported">' . $langs->trans('AlreadyImported') . '</span>', 'value' => 0, 'disabled' => 1);
			} else {
				$formquestionimportsharedrisks[] = array('type' => 'checkbox', 'name' => 'import_shared_risks' . '_S' . $risks->entity . '_' . $digiriskelementtmp->ref . '_' . $risks->ref, 'label' => $importValue, 'value' => 0);
			}
		}
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ImportSharedRisks'), '', 'confirm_import_shared_risks', $formquestionimportsharedrisks, 'yes', 'actionButtonImportSharedRisks', 800, 800);
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

	$head = digiriskelementPrepareHead($object);
	dol_fiche_head($head, 'elementRisk', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	// Object card
	// ------------------------------------------------------------
	$height                                   = 80;
	$width                                    = 80;
	dol_strlen($object->label) ? $morehtmlref = ' - ' . $object->label : '';
	$morehtmlref                             .= '<div class="refidno">';
	// ParentElement
	$parent_element = new DigiriskElement($db);
	$result         = $parent_element->fetch($object->fk_parent);
	if ($result > 0) {
		$morehtmlref .= $langs->trans("Description") . ' : ' . $object->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $parent_element->getNomUrl(1, 'blank', 1);
	} else {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		$morehtmlref .= $langs->trans("Description") . ' : ' . $object->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $digiriskstandard->getNomUrl(1, 'blank', 1);
	}
	$morehtmlref .= '</div>';
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">' . digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type, 'small', 5, 0, 0, 0, $height, $width, 0, 0, 0, $object->element_type, $object) . '</div>';
	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	// Buttons for actions
	print '<div class="tabsAction" >';
	if ($permissiontoadd && !empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS)) {
		print '<span class="butAction" id="actionButtonImportSharedRisks" title="" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=import_shared_risks' . '">' . $langs->trans("ImportSharedRisks") . '</span>';
	}
	print '</div>';

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_RISKS)) {
		$contextpage = 'riskcard';
		require_once './../../core/tpl/riskanalysis/risk/digiriskdolibarr_risklist_view.tpl.php';
	}

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS)) {
		$contextpage = 'inheritedrisk';
		require_once './../../core/tpl/riskanalysis/risk/digiriskdolibarr_inheritedrisklist_view.tpl.php';
	}

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS)) {
		$contextpage = 'sharedrisk';
		require_once './../../core/tpl/riskanalysis/risk/digiriskdolibarr_sharedrisklist_view.tpl.php';
	}
}

print '</div>' . "\n";
print '<!-- End div class="cardcontent" -->';

// End of page
llxFooter();
$db->close();
