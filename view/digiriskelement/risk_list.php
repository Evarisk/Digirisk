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
 *   	\file       view/digiriskelement/risk_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for risk
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DigiriskDolibarr libraries
require_once './../../class/digiriskelement.class.php';
require_once './../../class/digiriskstandard.class.php';
require_once './../../class/riskanalysis/risk.class.php';
require_once './../../class/digiriskelement.class.php';
require_once './../../class/riskanalysis/riskassessment.class.php';
require_once './../../core/modules/digiriskdolibarr/riskanalysis/risk/mod_risk_standard.php';
require_once './../../core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';
require_once './../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once './../../lib/digiriskdolibarr_function.lib.php';
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$subaction   = GETPOST('subaction', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'risklist'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');
$toselect    = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$limit       = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOST('sortfield', 'alpha');
$sortorder   = GETPOST('sortorder', 'alpha');
$riskType    = GETPOSTISSET('risk_type') ? GETPOST('risk_type') : 'risk';
$page        = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page        = is_numeric($page) ? $page : 0;
$page        = $page == -1 ? 0 : $page;
if (isModEnabled('categorie')) {
    $search_category_array = GETPOST('search_category_risk_list', 'array');
}

$onPhone = $conf->browser->layout == 'phone';

// Initialize technical objects
$object           = new DigiriskStandard($db);
$risk             = new Risk($db);
$evaluation       = new RiskAssessment($db);
$ecmdir           = new EcmDirectory($db);
$project          = new Project($db);
$task             = new SaturneTask($db);
$extrafields      = new ExtraFields($db);

$numberingModuleName = [
    'riskanalysis/' . $risk->element       => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
    'riskanalysis/' . $evaluation->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
    $project->element                      => $conf->global->PROJECT_ADDON,
    'project/task'                         => $conf->global->PROJECT_TASK_ADDON,
];

list($refRiskMod, $refEvaluationMod, $refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
$hookmanager->initHooks(array('risklist', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($risk->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($risk->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if ( ! $sortfield) $sortfield = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? "evaluation.cotation" : "r." . key($risk->fields);; // Set here default search field. By default 1st field in definition.
if ( ! $sortorder) $sortorder         = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? "DESC" : "ASC" ;
if (!isset($evalsortfield) || !$evalsortfield) $evalsortfield = "evaluation." . key($evaluation->fields);

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
	if (!empty($val['searchall'])) $fieldstosearchall['r.' . $key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($risk->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
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
saturne_check_access($permissiontoread);

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
		$toselect              = '';
		$search_array_options  = [];
        $search_category_array = [];
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;

	$backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/risk_list.php?risk_type=' . $riskType, 1);

	require_once './../../core/tpl/riskanalysis/risk/digiriskdolibarr_risk_actions.tpl.php';
}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans(ucfirst($riskType) . 's');
$helpUrl = 'FR:Module_Digirisk#.C3.89valuation_des_Risques';

saturne_header(1,'', $title, $helpUrl);

// Object card
// ------------------------------------------------------------
$allRisks = 1;
if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_RISKS)) {
	$contextpage = 'risklist';
	require_once './../../core/tpl/riskanalysis/risk/digiriskdolibarr_risklist_view.tpl.php';
}
if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS)) {
	$contextpage = 'sharedrisk';
	require_once './../../core/tpl/riskanalysis/risk/digiriskdolibarr_sharedrisklist_view.tpl.php';
}

?>
	<script>
        $('.ulrisklist_selectedfields').attr('style','z-index:1100');
        $('.ulinherited_risklist_selectedfields').attr('style','z-index:1100');
        $('.ulshared_risklist_selectedfields').attr('style','z-index:1100');
	</script>
<?php

// End of page
llxFooter();
$db->close();
