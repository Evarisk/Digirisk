<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015 	   Claudio Aschieri     <c.aschieri@19.coop>
 * Copyright (C) 2018 	   Ferran Marcet	    <fmarcet@2byte.es>
 * Copyright (C) 2019 	   Juanjo Menent	    <jmenent@2byte.es>
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
 *   	\file       preventionplan_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for prevention plan
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once('/custom/digiriskdolibarr/class/digiriskdocuments/preventionplan.class.php');
// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies', 'commercial'));
global $conf, $db;
$action      = GETPOST('action', 'alpha');
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOST('show_files', 'int');
$confirm     = GETPOST('confirm', 'alpha');
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'projectlist';

$title = $langs->trans("PreventionPlan");

$preventionplan = new PreventionPlan($db);

$limit     = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", "alpha");
$sortorder = GETPOST("sortorder", 'alpha');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page      = is_numeric($page) ? $page : 0;
$page      = $page == -1 ? 0 : $page;

if (!$sortfield) $sortfield = "t.ref";
if (!$sortorder) $sortorder = "ASC";

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search = array();
foreach ($preventionplan->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($preventionplan->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($preventionplan->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Load Digipreventionplan_element object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_preventionplan
$permissiontoread = $user->rights->digiriskdolibarr->preventionplan->read;
$permissiontoadd = $user->rights->digiriskdolibarr->preventionplan->write;
$permissiontodelete = $user->rights->digiriskdolibarr->preventionplan->delete;

// Security check - Protection if external user
if (!$user->rights->digiriskdolibarr->lire) accessforbidden();

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($preventionplan->fields as $key => $val) {
			$search[$key] = '';
		}

		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;

	$backtopage = dol_buildpath('/digiriskdolibarr/preventionplan_list.php', 1);

}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$title = $langs->trans("PreventionPlan");
$help_url = 'FR:Module_DigipreventionplanDolibarr';

llxHeader("", $title, $help_url);

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array();
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if ($user->rights->projet->creer) $arrayofmassactions['close'] = $langs->trans("Close");
if ($user->rights->societe->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$newcardbutton = '';
if ($user->rights->projet->creer)
{
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewPreventionPlan'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/digiriskdolibarr/preventionplan_card.php?action=create');
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($form->textwithpicto($title, $texthelp), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'preventionplan', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Build and execute select
// --------------------------------------------------------------------

	$sql = 'SELECT ';
	foreach ($preventionplan->fields as $key => $val)
	{
		$sql .= 't.'.$key.', ';
	}
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$preventionplan->table_element]['label'])) {
		foreach ($extrafields->attributes[$preventionplan->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$preventionplan->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
	}
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
	$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= " FROM ".MAIN_DB_PREFIX.$preventionplan->table_element." as t";

	if (is_array($extrafields->attributes[$preventionplan->table_element]['label']) && count($extrafields->attributes[$preventionplan->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$preventionplan->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
	if ($preventionplan->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($preventionplan->element).")";
	else $sql .= " WHERE 1 = 1";
	$sql .= " AND t.type = '".$preventionplan->element . "'";


foreach ($search as $key => $val)
	{
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($preventionplan->isInt($preventionplan->fields[$key]) || $preventionplan->isFloat($preventionplan->fields[$key])) ? 1 : 0);
		if (strpos($preventionplan->fields[$key]['type'], 'integer:') === 0) {
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
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
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
		header("Location: ".dol_buildpath('/digiriskdolibarr/digiriskelement_preventionplan.php', 1).'?id='.$id);
		exit;
	}

if ($search_all)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";
print '<tr class="liste_titre">';

foreach ($preventionplan->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $preventionplan->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		}
		elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		print '</td>';
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
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
foreach ($preventionplan->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}

}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
print $hookmanager->resPrint;

// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";


// Loop on record
// --------------------------------------------------------------------

// contenu
$i = 0;
$totalarray = array();

while ($i < ($limit ? min($num, $limit) : $num)) {
	$obj = $db->fetch_object($resql);

	if (empty($obj)) break; // Should not happen


	// Store properties in $preventionplan
	$preventionplan->setVarsFromFetchObj($obj);
// Show here line of result
	print '<tr class="oddeven preventionplan-row preventionplan_row_'. $preventionplan->id .' preventionplan-row-content-'. $preventionplan->id . '" id="preventionplan_row_'. $preventionplan->id .'">';
	foreach ($preventionplan->fields as $key => $val) {
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'category') $cssforfield .= ($cssforfield ? ' ' : '') . 'preventionplan-category';
		elseif ($key == 'description') $cssforfield .= ($cssforfield ? ' ' : '') . 'preventionplan-description';
		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="width:2%">';
			if ($key == 'status') print $preventionplan->getLibStatut(5);

			else print $preventionplan->showOutputField($val, $key, $preventionplan->$key, '');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!empty($val['isameasure'])) {
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
				$totalarray['val']['t.' . $key] += $preventionplan->$key;
			}
		}
	}
}

print "</table>\n";
print '</div>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
