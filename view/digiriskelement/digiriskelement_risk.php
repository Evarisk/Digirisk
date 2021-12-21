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
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/mod_project_simple.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/mod_task_simple.php';

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
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'riskcard'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');
$toselect    = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$limit       = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOST('sortfield', 'alpha');
$sortorder   = GETPOST('sortorder', 'alpha');
$page        = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page        = is_numeric($page) ? $page : 0;
$page        = $page == -1 ? 0 : $page;

// Initialize technical objects
$object            = new DigiriskElement($db);
$digiriskstandard  = new DigiriskStandard($db);
$risk              = new Risk($db);
$evaluation        = new RiskAssessment($db);
$ecmdir            = new EcmDirectory($db);
$project           = new Project($db);
$task              = new Task($db);
$extrafields       = new ExtraFields($db);
$refRiskMod        = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
$refEvaluationMod  = new $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON();
$refProjectMod     = new $conf->global->PROJECT_ADDON();
$refTaskMod        = new $conf->global->PROJECT_TASK_ADDON();

$hookmanager->initHooks(array('riskcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($risk->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($risk->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? "evaluation.cotation" : "t.".key($risk->fields);; // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? "DESC" : "ASC" ;
if (!$evalsortfield) $evalsortfield = "evaluation.".key($evaluation->fields);

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search = array();
foreach ($risk->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($risk->fields as $key => $val) {
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($risk->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if ($val['label'] == 'ParentElement') {
		$val['visible'] = 0;
	}
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}
foreach ($evaluation->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['evaluation.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_risk
$permissiontoread = $user->rights->digiriskdolibarr->risk->read;
$permissiontoadd = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;

// Security check
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $risk, $action); // Note that $action and $risk may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($risk->fields as $key => $val) {
			$search[$key] = '';
		}
		foreach ($evaluation->fields as $key => $val) {
			$search[$key] = '';
		}
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;

	$backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1).'?id='.($id > 0 ? $id : '__ID__');

	require_once './../../core/tpl/digiriskdolibarr_risk_actions.tpl.php';

}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("DigiriskElementRisk");
$help_url = 'FR:Module_DigiriskDolibarr#Risques';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader('', $title, $help_url, '', '', '', $morejs, $morecss, '', 'classforhorizontalscrolloftabs');

print '<div id="cardContent" value="">';

if ($object->id > 0) {
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);
	dol_fiche_head($head, 'elementRisk', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	// Object card
	// ------------------------------------------------------------
	$width = 80;
	dol_strlen($object->label) ? $morehtmlref = ' - ' . $object->label : '';
	$morehtmlref .= '<div class="refidno">';
	// ParentElement
	$parent_element = new DigiriskElement($db);
	$result = $parent_element->fetch($object->fk_parent);
	if ($result > 0) {
		$morehtmlref .= $langs->trans("Description").' : '.$object->description;
		$morehtmlref .= '<br>'.$langs->trans("ParentElement").' : '.$parent_element->getNomUrl(1, 'blank', 1);
	}
	else {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		$morehtmlref .= $langs->trans("Description").' : '.$object->description;
		$morehtmlref .= '<br>'.$langs->trans("ParentElement").' : '.$digiriskstandard->getNomUrl(1, 'blank', 1);
	}
	$morehtmlref .= '</div>';
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';
	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	require_once './../../core/tpl/digiriskdolibarr_risklist_view.tpl.php';
}

print '</div>'."\n";
print '<!-- End div class="cardcontent" -->';

// End of page
llxFooter();
$db->close();
