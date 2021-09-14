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
 *   	\file       firepermit_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for prevention plan
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
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

require_once __DIR__ . '/class/firepermit.class.php';
require_once __DIR__ . '/class/preventionplan.class.php';
require_once __DIR__ . '/class/digiriskresources.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies', 'commercial'));
global $conf, $db;
$action      = GETPOST('action', 'alpha');
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOST('show_files', 'int');
$confirm     = GETPOST('confirm', 'alpha');
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'firepermitlist';

$title = $langs->trans("FirePermit");

$firepermit         = new FirePermit($db);
$preventionplan     = new PreventionPlan($db);
$societe            = new Societe($db);
$contact            = new Contact($db);
$usertmp            = new User($db);
$digiriskresources  = new DigiriskResources($db);

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
foreach ($firepermit->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($firepermit->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();

foreach ($firepermit->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Load Digifirepermit_element object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_firepermit
$permissiontoread = $user->rights->digiriskdolibarr->firepermitdocument->read;
$permissiontoadd = $user->rights->digiriskdolibarr->firepermitdocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->firepermitdocument->delete;

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

	$backtopage = dol_buildpath('/digiriskdolibarr/firepermit_list.php', 1);

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($firepermit->fields as $key => $val) {
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
	if (!$error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
		if (!empty($toselect)) {
			foreach ($toselect as $toselectedid) {

				$firepermittodelete = $firepermit;
				$firepermittodelete->fetch($toselectedid);

				$firepermittodelete->status = 0;
				$result = $firepermittodelete->update($user, true);

				if ($result < 0) {
					// Delete firepermit KO
					if (!empty($firepermit->errors)) setEventMessages(null, $firepermit->errors, 'errors');
					else  setEventMessages($firepermit->error, null, 'errors');
				}
			}

			// Delete firepermit OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
	}


}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$title = $langs->trans("FirePermit");
$help_url = 'FR:Module_DigifirepermitDolibarr';

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
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewFirePermit'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/digiriskdolibarr/firepermit_card.php?action=create');
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

print_barre_liste($form->textwithpicto($title, $texthelp), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'firepermitdocument', 0, $newcardbutton, '', $limit, 0, 0, 1);

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

// Build and execute select
// --------------------------------------------------------------------

$sql = 'SELECT ';
foreach ($firepermit->fields as $key => $val)
	{
		$sql .= 't.'.$key.', ';
	}
// Add fields from extrafields
if (!empty($extrafields->attributes[$firepermit->table_element]['label'])) {
		foreach ($extrafields->attributes[$firepermit->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$firepermit->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
	}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $firepermit); // Note that $action and $firepermitdocument may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX.$firepermit->table_element." as t";

if (is_array($extrafields->attributes[$firepermit->table_element]['label']) && count($extrafields->attributes[$firepermit->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$firepermit->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
if ($firepermit->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($firepermit->element).")";
else $sql .= " WHERE 1 = 1";
$sql .= ' AND status = 1';


foreach ($search as $key => $val)
	{
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($firepermit->isInt($firepermit->fields[$key]) || $firepermit->isFloat($firepermit->fields[$key])) ? 1 : 0);
		if (strpos($firepermit->fields[$key]['type'], 'integer:') === 0) {
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
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $firepermit); // Note that $action and $firepermitdocument may have been modified by hook
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
		header("Location: ".dol_buildpath('/digiriskdolibarr/digiriskelement_firepermit.php', 1).'?id='.$id);
		exit;
	}

if ($search_all)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$arrayfields['MaitreOeuvre'] = array('label'=>'MaitreOeuvre');
$arrayfields['ExtSociety'] = array('label'=>'ExtSociety');
$arrayfields['ExtSocietyResponsible'] = array('label'=>'ExtSocietyResponsible');
$arrayfields['ExtSocietyIntervenants'] = array('label'=>'ExtSocietyIntervenants');

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";
print '<tr class="liste_titre">';

$firepermit->fields['Custom']['FP_MAITRE_OEUVRE'] = 'MaitreOeuvre';
$firepermit->fields['Custom']['FP_EXT_SOCIETY'] = 'ExtSociety';
$firepermit->fields['Custom']['FP_EXT_SOCIETY_RESPONSIBLE'] = 'ExtSocietyResponsible';
$firepermit->fields['Custom']['FP_EXT_SOCIETY_INTERVENANTS'] = 'ExtSocietyIntervenants';

foreach ($firepermit->fields as $key => $val) {
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if (!empty($arrayfields['t.' . $key]['checked'])) {
		print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';

		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $firepermit->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		} elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key]) . '">';
		print '</td>';
	}
	if ($key == 'Custom') {
		foreach ($val as $resource) {
			print '<td>';
			print '';
			print '</td>';
		}
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $firepermit); // Note that $action and $firepermitdocument may have been modified by hook
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

foreach ($firepermit->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		if (preg_match('/MaitreOeuvre/', $arrayfields['t.'.$key]['label']) || preg_match('/StartDate/', $arrayfields['t.'.$key]['label']) || preg_match('/EndDate/', $arrayfields['t.'.$key]['label']) || preg_match('/ExtSociety/', $arrayfields['t.'.$key]['label']) || preg_match('/NbIntervenants/', $arrayfields['t.'.$key]['label']) || preg_match('/NbInterventions/', $arrayfields['t.'.$key]['label']) || preg_match('/Location/', $arrayfields['t.'.$key]['label'])) {
			$disablesort = 1;
		}
		else {
			$disablesort = 0;
		}
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''), $disablesort)."\n";

	}
	if ($key == 'Custom') {
		foreach ($val as $resource) {
			print '<td>';
			print $langs->trans($resource);
			print '</td>';
		}
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $firepermit); // Note that $action and $firepermitdocument may have been modified by hook
print $hookmanager->resPrint;

// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";

$arrayofselected = is_array($toselect) ? $toselect : array();

// Loop on record
// --------------------------------------------------------------------

// contenu
$i = 0;
$totalarray = array();

while ($i < ($limit ? min($num, $limit) : $num)) {

	$obj = $db->fetch_object($resql);

	if (empty($obj)) break; // Should not happen


	// Store properties in $firepermitdocument
	$firepermit->setVarsFromFetchObj($obj);

	$json = json_decode($firepermit->json, false, 512, JSON_UNESCAPED_UNICODE)->FirePermit;

	// Show here line of result
	print '<tr class="oddeven firepermitdocument-row firepermit_row_'. $firepermit->id .' firepermitdocument-row-content-'. $firepermit->id . '" id="firepermit_row_'. $firepermit->id .'">';
	foreach ($firepermit->fields as $key => $val) {
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'category') $cssforfield .= ($cssforfield ? ' ' : '') . 'firepermitdocument-category';
		elseif ($key == 'description') $cssforfield .= ($cssforfield ? ' ' : '') . 'firepermitdocument-description';
		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="width:2%">';
			if ($key == 'status') print $firepermit->getLibStatut(5);
			elseif ($key == 'fk_preventionplan') {
				if ($obj->fk_preventionplan > 0) {
					$preventionplan->fetch($obj->fk_preventionplan);
					print $preventionplan->getNomUrl(1);
				}
			}
			elseif ($key == 'ref') {
				print $firepermit->getNomUrl();
			}
			else print $firepermit->showOutputField($val, $key, $firepermit->$key, '');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!empty($val['isameasure'])) {
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
				$totalarray['val']['t.' . $key] += $firepermit->$key;
			}
		}
		if ($key == 'Custom') {
			foreach ($val as $name => $resource) {
				$resourceLinked = $digiriskresources->fetchResourcesFromObject($name, $firepermit);
				print '<td>';
				if ($resourceLinked > 0) {
					if ($resource == 'ExtSocietyIntervenants' && is_array($resourceLinked)) {
						$resourcesLinked = array_shift($resourceLinked);
						foreach($resourcesLinked as $resourceLinkedSingle) {
							print $resourceLinkedSingle->getNomUrl(1);
							print '<br>';
						}
					} else {
						print $resourceLinked->getNomUrl(1);
					}
				}
				print '</td>';
			}
		}
	}
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected = 0;
		if (in_array($firepermit->id, $arrayofselected)) $selected = 1;
		print '<input id="cb'.$firepermit->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$firepermit->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}

	print '</td>';
	if (!$i) $totalarray['nbfield']++;
	print '</tr>'."\n";
	$i++;
}
// If no record found
if ($num == 0)
{
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}
$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $risk); // Note that $action and $risk may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print '</div>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
