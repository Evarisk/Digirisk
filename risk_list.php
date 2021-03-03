<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       risk_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for risk
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       		  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');			// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', '1');		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("XFRAMEOPTIONS_ALLOWALL"))   define('XFRAMEOPTIONS_ALLOWALL', '1');			// Do not add the HTTP header 'X-Frame-Options: SAMEORIGIN' but 'X-Frame-Options: ALLOWALL'

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
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/digiriskevaluation.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/mod_task_simple.php';
dol_include_once('/digiriskdolibarr/class/risk.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskevaluation.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_risk_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_evaluation_standard.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');

// load digiriskdolibarr libraries
require_once __DIR__.'/class/risk.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'risklist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new Risk($db);
$extrafields = new ExtraFields($db);
$evaluation = new DigiriskEvaluation($db);
$diroutputmassaction = $conf->digiriskdolibarr->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('risklist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = "t.".key($object->fields); // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = "ASC";
if (!$evalsortfield) $evalsortfield = "evaluation.".key($evaluation->fields);
// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}
foreach ($evaluation->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['evaluation.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($object->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

foreach ($evaluation->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['evaluation.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key])) {
			$arrayfields["ef.".$key] = array(
				'label'=>$extrafields->attributes[$object->table_element]['label'][$key],
				'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1),
				'position'=>$extrafields->attributes[$object->table_element]['pos'][$key],
				'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]),
				'langfile'=>$extrafields->attributes[$object->table_element]['langfile'][$key]
			);
		}
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$permissiontoread = $user->rights->digiriskdolibarr->risk->read;
$permissiontoadd = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;

// Security check
if (empty($conf->digiriskdolibarr->enabled)) accessforbidden('Module not enabled');
$socid = 0;
if ($user->socid > 0)	// Protection if external user
{
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'digiriskdolibarr', $id, '');
//if (!$permissiontoread) accessforbidden();



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($object->fields as $key => $val)
		{
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

	// Mass actions
	$objectclass = 'Risk';
	$objectlabel = 'Risk';
	$uploaddir = $conf->digiriskdolibarr->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$form = new Form($db);

$now = dol_now();
//$evaluation = new DigiriskEvaluation($db);
//
//$evaluation->fields['ref']['visible'] = 0;
//$evaluation->fields['cotation']['visible'] = 4;

//$help_url="EN:Module_Risk|FR:Module_Risk_FR|ES:Módulo_Risk";
$help_url = '';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("Risks"));


// Build and execute select
// --------------------------------------------------------------------
if (!preg_match('/(evaluation)/', $sortfield)) {

	$sql = 'SELECT ';
	foreach ($object->fields as $key => $val)
	{
			$sql .= 't.'.$key.', ';
	}
// Add fields from extrafields
	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
	}
// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
	$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
	if ($object->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($object->element).")";
	else $sql .= " WHERE 1 = 1";
	foreach ($search as $key => $val)
	{
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
		if (strpos($object->fields[$key]['type'], 'integer:') === 0) {
			if ($search[$key] == '-1') $search[$key] = '';
			$mode_search = 2;
		}
		if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
	}
	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	/* If a group by is required
	$sql.= " GROUP BY ";
	foreach($object->fields as $key => $val)
	{
		$sql.='t.'.$key.', ';
	}
	// Add fields from extrafields
	if (! empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
	}
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListGroupBy',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
	$sql=preg_replace('/,\s*$/','', $sql);
	*/

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
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
	{
		$num = $nbtotalofrecords;
	}
	else
	{
		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if (!$resql)
		{
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}

// Direct jump if only one record found
	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?id='.$id);
		exit;
	}

} else {
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
	if (is_array($extrafields->attributes[$evaluation->table_element]['label']) && count($extrafields->attributes[$evaluation->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$evaluation->table_element."_extrafields as ef on (evaluation.rowid = ef.fk_object)";
	if ($evaluation->ismultientitymanaged == 1) $sql .= " WHERE evaluation.entity IN (".getEntity($evaluation->element).")";
	else $sql .= " WHERE 1 = 1";
	$sql .= " AND status = 1";
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
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
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
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
	{
		$num = $nbtotalofrecords;
	}
	else
	{
		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if (!$resql)
		{
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}

// Direct jump if only one record found
	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?id='.$id);
		exit;
	}
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
foreach ($search as $key => $val)
{
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
	else $param .= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param .= '&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>$langs->trans("Validate"),
	//'generate_doc'=>$langs->trans("ReGeneratePDF"),
	//'builddoc'=>$langs->trans("PDFMerge"),
	//'presend'=>$langs->trans("SendByMail"),
);
if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendRiskRef";
$modelmail = "risk";
$objecttmp = new Risk($db);
$trackid = 'xxxx'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';*/

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (!empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $object->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		}
		elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		print '</td>';
	}
}

foreach ($evaluation->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';
	if (!empty($arrayfields['evaluation.'.$key]['checked']))
	{
		print '<td class="liste_titre'.'">';
		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $evaluation->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		}
		elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		print '</td>';
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
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
foreach ($object->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';

	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}

}

foreach ($evaluation->fields as $key => $val)
{
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';

	if (!empty($arrayfields['evaluation.'.$key]['checked']))
	{
		$cssforfield = '';

		if (GETPOST('sortorder') == 'asc') {
			$sortorder = 'desc';
		} else {
			$sortorder = 'asc';
		}
		print getTitleFieldOfList($arrayfields['evaluation.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 'evaluation.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $evalsortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}

}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Ajouter contenu de la liste titre

//print getTitleFieldOfList($langs->trans('Evaluation') , $_SERVER['PHP_SELF'], '', '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''));



// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val)
	{
		if (preg_match('/\$object/', $val)) $needToFetchEachLine++; // There is at least one compute field that use $object
	}
}


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
		$object->fetch($obj->fk_risk);
	} else {
		// Store properties in $object
		$object->setVarsFromFetchObj($obj);
	}

	// Show here line of result
	print '<tr class="oddeven">';

	foreach ($object->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
		elseif ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';

		if (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield .= ($cssforfield ? ' ' : '').'right';
		//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').' style="width:2%">';
			if ($key == 'status') print $object->getLibStatut(5);
			elseif ($key == 'has_photo') {
			?>
				<div class="table-cell cell-photo" data-title="Photo">
						<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element.'/'.$object->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
						if (count($filearray)) {
							print '<span class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $risk->element).'</span>';
						} else {
							$nophoto = '/public/theme/common/nophoto.png'; ?>
							<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
						<?php } ?>
				</div>
			<?php }
			elseif ($key == 'category') {
				?>
				<div class="table-cell table-50 cell-risk" data-title="Risque">
					<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
						 data-tooltip-persist="true"
						 data-color="red"
						 aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">
						<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $object->get_danger_category($object) . '.png' ; ?>" aria-label="" />
					</div>
				</div>
				<?php
			}
			else print $object->showOutputField($val, $key, $object->$key, '');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!empty($val['isameasure']))
			{
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
				$totalarray['val']['t.'.$key] += $object->$key;
			}
		}

	}
	$lastEvaluation = $evaluation->fetchFromParent($object->id);
	$lastEvaluation = array_shift($lastEvaluation);

	if (empty($evaluation)) break; // Should not happen

	// Store properties in $object


	foreach ($lastEvaluation->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
		elseif ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';

		if (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield .= ($cssforfield ? ' ' : '').'right';
		//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

		if (!empty($arrayfields['evaluation.'.$key]['checked']))
		{
			$cssforfield = '';
			print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
			if ($key == 'status') print $lastEvaluation->getLibStatut(5);
			elseif ($key == 'cotation') {
				?>
				<div class="digirisk-wrap wpeo-wrap">
					<div class="wpeo-table table-flex table-risk main-table">
						<div class="table-row risk-row">
							<div class="table-cell table-50 cell-cotation" data-title="Cot.">
								<div class="cotation-container grid wpeo-modal-event tooltip hover cotation-square" id="cotation_square<?php echo $object->id ?>">
									<div class="action cotation default-cotation modal-open" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>" value="<?php echo $risk->id ?>">
										<span><?php echo $lastEvaluation->cotation; ?></span>
									</div>
											<!-- Modal-EvaluationsList -->
									<div id="cotation_modal<?php echo $risk->id ?>" class="wpeo-modal" value="<?php echo $risk->id ?>">
										<div class="modal-container wpeo-modal-event">
											<!-- Modal-Header -->
											<div class="modal-header">
												<h2 class="modal-title"><?php echo $langs->trans('EvaluationsList') ?></h2>
													<div class="modal-close"><i class="fas fa-times"></i></div>
											</div>
											<!-- Modal-Content -->
											<div class="modal-content" id="#modalContent">
												<ul class="evaluations-list" style="display: grid">
												<?php
													$cotationList = $evaluation->fetchFromParent($object->id);
													if (!empty($cotationList)) :
														foreach ($cotationList as $cotation) : ?>
															<li class="evaluations-item">
																<div class="cotation-container grid">
																	<div class="action cotation default-cotation level<?php echo $cotation->get_evaluation_scale(); ?>">
																		<span><?php echo  $cotation->cotation; ?></span>
																	</div>
																</div>
																<div>
																	<span class="ref"><?php echo 'E'. $cotation->id ; ?></span>
																	<span class="author">
																		<div class="avatar" style="background-color: #50a1ed;">
																			<?php $user = new User($db); ?>
																			<?php $user->fetch($cotation->fk_user_creat); ?>
																			<span><?php echo $user->firstname[0] . $user->lastname[0]; ?></span>
																		</div>
																	</span>
																	<span class="date"><i class="fas fa-calendar-alt"></i> <?php echo date("d/m/Y", $cotation->date_creation); ?></span>
																</div>
																<span class="comment"><?php echo $cotation->comment; ?></span>
																<hr>
															</li>
														<?php endforeach; ?>
												</ul>
											</div>
											<!-- Modal-Footer -->
											<div class="modal-footer">
												<div class="wpeo-button button-grey modal-close">
													<span><?php echo $langs->trans('CloseModal'); ?></span>
												</div>
											</div>
										</div>
									</div>
								<?php endif; ?>
								</div>
							</div>
							<div class="table-cell cell-comment">
								<?php $user->fetch($lastEvaluation->fk_user_creat);
								echo $user->getNomUrl() . ' '. $langs->trans('EvaluatedOn') . ' '. date('Y/m/d H:i:s', $lastEvaluation->date_creation) . ' : ' . $lastEvaluation->comment;
								?>
							</div>
						</div>
					</div>
				</div> <?php
			}
			elseif ($key == 'has_tasks') { ?>
				<div class="table-cell cell-tasks" data-title="Tâches" class="padding">
					<span class="cell-tasks-container">
	<!--				VUE SI Y A DES TACHES   -->
						<?php $related_tasks = $object->get_related_tasks($object);
						if (!empty($related_tasks) ) :
							foreach ($related_tasks as $related_task) :
								$related_task->fetchTimeSpent($related_task->id); ?>
								<span class="ref"><?php echo $related_task->ref; ?></span>
	<!--							// @todo truc sympa pour l'author.-->
								<span class="author">
									<div class="avatar" style="background-color: #50a1ed;">
										<?php $user = new User($db); ?>
										<?php $user->fetch($related_task->fk_user_creat); ?>
										<span><?php echo $user->firstname[0] . $user->lastname[0]; ?></span>
									</div>
								</span>
								<span class="date"><i class="fas fa-calendar-alt"></i><?php echo date("d/m/Y", $related_task->date_c) ?></span>
								<span class="label"><?php echo $related_task->label; ?></span>
								<!--													print '<a target="_blank" href="/dolibarr/htdocs/projet/tasks/time.php?id=' . $related_task->id . '&withproject=1">' . '&nbsp' .  gmdate('H:i', $related_task->duration_effective ) . '</a>';-->
							<?php endforeach; ?>
						<?php else : ?>
							<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>
						<?php endif; ?>
					</span>
					<!--									VUE SI Y EN A PAS   -->
				</div>

			<?php
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
	$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
//	print '<td class="nowrap"><td>'. $langs->trans('Evaluation') .'</td>';
//	print '</td>';
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected = 0;
		if (in_array($object->id, $arrayofselected)) $selected = 1;
		print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}

	print '</td>';
	if (!$i) $totalarray['nbfield']++;

	print '</tr>'."\n";

	$i++;
}


// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0)
{
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}


$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', $arrayofmassactions) && ($nbtotalofrecords === '' || $nbtotalofrecords))
{
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_digiriskdolibarr', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
