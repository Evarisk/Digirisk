<?php
/* Copyright (C) 2007-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2020 Eoxia <dev@eoxia.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/custom/digiriskdolibarr/view/legaldisplay.list.php
 *  \ingroup    digiriskdolibarr
 *  \brief      List File for legal display
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/digiriskdolibarr/class/legaldisplay.class.php');
dol_include_once('/digiriskdolibarr/class/digirisk_documents.class.php');

// Load traductions files requiredby by page
$langs->load("digiriskdolibarr");
$langs->load("other");

$action     = GETPOST('action','alpha');
$massaction = GETPOST('massaction','alpha');
$show_files = GETPOST('show_files','int');
$confirm    = GETPOST('confirm','alpha');
$toselect   = GETPOST('toselect', 'array');

$id         = GETPOST('id','int');
$backtopage = GETPOST('backtopage');
$myparam    = GETPOST('myparam','alpha');

$search_all     = trim(GETPOST("sall"));
$date_debut     = GETPOST("date_debut","int");
$ref            = GETPOST("ref");
$search_myfield = GETPOST('search_myfield');
$optioncss      = GETPOST('optioncss','alpha');
// Load variable for pagination
$limit     = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
//$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield = "t.rowid"; // Set here default search field
if (! $sortorder) $sortorder = "ASC";

// Protection if external user
$socid = 0;
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
	//accessforbidden();
}

// Initialize technical object to manage context to save list fields
$contextpage = GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'legaldisplaylist';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('digiriskdolibarrlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('legaldisplay');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	't.ref'=>'Ref',
	't.fk_socpeople_labour_doctor'=>'MedecinTravail',
);
if (empty($user->socid)) $fieldstosearchall["t.note_private"]="NotePrivate";

// Definition of fields for list
$arrayfields = array(
	// ICI C'EST LES REF DU HEADER DE LA LISTE YOUHOU
	't.ref' => array('label'=>$langs->trans("Ref"), 'checked'=>1),
	't.date_creation' => array('label'=>$langs->trans("Date de création"), 'checked'=>1),
	't.last_main_doc' => array('label'=>$langs->trans("DOC"), 'checked'=>1)
	//'t.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		$arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
	}
}


// Load object if id or ref is provided as parameter
$object=new Legaldisplay($db);
if (($id > 0 || ! empty($ref)) && $action != 'add')
{
	$result=$object->fetch($id,$ref);
	if ($result < 0) dol_print_error($db);
}

/*******************************************************************
 * ACTIONS
 ********************************************************************/

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") ||GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
	{
		$rowid='';
		$ref='';
		$search_date_creation='';
		$search_date_update='';
		$toselect='';
		$search_array_options=array();
	}

	// Mass actions
	$objectclass='Digiriskdolibarr';
	$objectlabel='Digiriskdolibarr';
	$permtoread = $user->rights->digiriskdolibarr->read;
	$permtodelete = $user->rights->digiriskdolibarr->delete;
	$uploaddir = $conf->digiriskdolibarr->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

/***************************************************
 * VIEW
 ****************************************************/

$now = dol_now();

$form = new Form($db);

$help_url='';
$title = $langs->trans('LegalDisplayList');

// Put here content of your page
/*
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
*/

$digirisk_documents = new DigiriskDocuments($db);
$objects = $digirisk_documents->fetch_all('legaldisplay');

llxHeader('', $title, $help_url);

$arrayofselected = is_array($toselect)?$toselect:array();

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($rowid != '') $param.= '&amp;search_field1='.urlencode($rowid);
if ($ref != '') $param.= '&amp;search_field2='.urlencode($ref);
if ($optioncss != '') $param.='&optioncss='.$optioncss;

$arrayofmassactions =  array(
	'presend'=>$langs->trans("SendByMail"),
	'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->digiriskdolibarr->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
if ($massaction == 'presend') $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);

if ($sall)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
}
/*
$moreforfilter = '';
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('Filtres') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';
*/
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (! empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

// Fields title
print '<tr class="liste_titre">';

// LIST_OF_TD_TITLE_FIELDS
if (! empty($arrayfields['t.ref']['checked'])) print_liste_field_titre($arrayfields['t.ref']['label'],$_SERVER['PHP_SELF'],'t.ref','',$param,'',$sortfield,$sortorder);

// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($arrayfields["ef.".$key]['checked']))
		{
			$align=$extrafields->getAlignFlag($key);
			print_liste_field_titre($langs->trans($extralabels[$key]),$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
		}
	}
}
// Hook fields
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['t.datec']['checked']))  print_liste_field_titre($arrayfields['t.datec']['label'],$_SERVER["PHP_SELF"],"t.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['t.tms']['checked']))    print_liste_field_titre($arrayfields['t.tms']['label'],$_SERVER["PHP_SELF"],"t.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
//if (! empty($arrayfields['t.status']['checked'])) print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"t.status","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');

// Fields title search
// LIST_OF_TD_TITLE_SEARCH
//if (! empty($arrayfields['t.field1']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_field1" value="'.$rowid.'" size="10"></td>';
//if (! empty($arrayfields['t.field2']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_field2" value="'.$ref.'" size="10"></td>';
print '</tr>';
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($arrayfields["ef.".$key]['checked']))
		{
			$align=$extrafields->getAlignFlag($key);
			$typeofextrafield=$extrafields->attribute_type[$key];
			print '<td class="liste_titre'.($align?' '.$align:'').'">';
			if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
			{
				$crit=$val;
				$tmpkey=preg_replace('/search_options_/','',$key);
				$searchclass='';
				if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
				if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
				print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
			}
			print '</td>';
		}
	}
}
// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$objstatic = new Legaldisplay($db);

if ( ! empty( $objects ) ) {
	foreach ( $objects as $obj ) {

		$objstatic->id = $obj->rowid;
		$objstatic->ref = $obj->ref;
		$objstatic->date_creation = $obj->date_creation;
		$objstatic->last_main_doc = $obj->last_main_doc;

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';

		print '<tr class="oddeven">';
		// Ref
		if (! empty($arrayfields['t.ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $objstatic->getNomUrl(1);
			print '</td>';
		}

		// Name
		if (! empty($arrayfields['t.last_main_doc']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $objstatic->last_main_doc;
			print '</td>';
		}

		// Date de création
		if (! empty($arrayfields['t.date_creation']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $objstatic->date_creation;
			print '</td>';
		}
		print '</tr>';
	}
}


		// Action column
//		print '<td class="wrap" align="center">';
//
//		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
//		{
//			$selected=0;
//			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
//			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
//		}
//		print '</td>';

// Show total line
if (isset($totalarray['date_creation']))
{
	print '<tr class="liste_total">';
	$i=0;
	while ($i < $totalarray['nbfield'])
	{
		$i++;
		if ($i == 1)
		{
			if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
			else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
		}
		elseif ($totalarray['totalhtfield'] == $i) print '<td align="right">'.price($totalarray['totalht']).'</td>';
		elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
		elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
		else print '<td></td>';
	}
	print '</tr>';
}

$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

// End of page
llxFooter();
$db->close();
