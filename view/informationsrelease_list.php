<?php
/* Copyright (C) 2007-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       dev/digiriskdolibarr/digiriskdolibarr_list.php
 *		\ingroup    digiriskdolibarr othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Put here some comments
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/digiriskdolibarr/class/legaldisplay.class.php');

// Load traductions files requiredby by page
$langs->load("digiriskdolibarr");
$langs->load("other");

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$id			= GETPOST('id','int');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');

$search_all=trim(GETPOST("sall"));
$date_debut = GETPOST("date_debut","int");
$ref=GETPOST("ref");
$search_myfield=GETPOST('search_myfield');
$optioncss = GETPOST('optioncss','alpha');
// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
//$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="t.rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

// Protection if external user
$socid=0;
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
	//accessforbidden();
}

// Initialize technical object to manage context to save list fields
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'legaldisplaylist';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('digiriskdolibarrlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('legaldisplay');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	't.ref'=>'Ref',
	't.fk_soc_labour_doctor'=>'MedecinTravail',
);
if (empty($user->socid)) $fieldstosearchall["t.note_private"]="NotePrivate";

// Definition of fields for list
$arrayfields=array(
	// ICI C'EST LES REF DU HEADER DE LA LISTE YOUHOU 
	't.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	't.date_creation'=>array('label'=>$langs->trans("Date de création"), 'checked'=>0),
	't.date_debut'=>array('label'=>$langs->trans("Date de début"), 'checked'=>1),
	't.date_fin'=>array('label'=>$langs->trans("Date de fin"), 'checked'=>1),

	't.fk_soc_labour_doctor'=>array('label'=>$langs->trans("Médecin du travail"), 'checked'=>1),
	't.fk_soc_labour_inspector'=>array('label'=>$langs->trans("Inspecteur du travail"), 'checked'=>0),
	't.fk_soc_samu'=>array('label'=>$langs->trans("SAMU"), 'checked'=>0),
	't.fk_soc_police'=>array('label'=>$langs->trans("Police"), 'checked'=>0),
	't.fk_soc_urgency'=>array('label'=>$langs->trans("Urgences"), 'checked'=>0),
	't.fk_soc_rights_defender'=>array('label'=>$langs->trans("Défenseur du droit du travail"), 'checked'=>0),
	't.fk_soc_antipoison'=>array('label'=>$langs->trans("Centre Antipoison"), 'checked'=>0),
	't.fk_soc_responsible_prevent'=>array('label'=>$langs->trans("Responsable de la prévention"), 'checked'=>0),

	't.description'=>array('label'=>$langs->trans("Description"), 'checked'=>0),
	't.status'=>array('label'=>$langs->trans("Statut"), 'checked'=>0),
	't.model_pdf'=>array('label'=>$langs->trans("Modèle PDF"), 'checked'=>0),
	't.model_odt'=>array('label'=>$langs->trans("Modèle ODT"), 'checked'=>0),
	't.note_affich'=>array('label'=>$langs->trans("NoteAffich"), 'checked'=>0)

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
 *
 * Put here all code to do according to value of "action" parameter
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
 *
 * Put here all code to build page
 ****************************************************/

$now=dol_now();

$form=new Form($db);

//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url='';
$title = $langs->trans('Legal Display List');

// Put here content of your page

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


$sql = "SELECT";
$sql .= " t.rowid";
$sql .= ", t.ref";
$sql .= ", t.entity";
$sql .= ", t.date_creation";
$sql .= ", t.date_debut";
$sql .= ", t.date_fin";
$sql .= ", t.fk_soc_labour_doctor";
$sql .= ", t.fk_soc_labour_inspector";
$sql .= ", t.fk_soc_samu";
$sql .= ", t.fk_soc_police";
$sql .= ", t.fk_soc_urgency";
$sql .= ", t.fk_soc_rights_defender";
$sql .= ", t.fk_soc_antipoison";
$sql .= ", t.fk_soc_responsible_prevent";
$sql .= ", t.description";
$sql .= ", t.import_key";
$sql .= ", t.status";
$sql .= ", t.fk_user_creat";
$sql .= ", t.model_pdf";
$sql .= ", t.model_odt";
$sql .= ", t.note_affich";
$sql .= ", s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client";

// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."legal_display as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on (s.rowid  = t.fk_soc_labour_doctor)";

if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."legal_display_extrafields as ef on (t.rowid = ef.fk_object)";
$sql.= " WHERE 1 = 1";
//$sql.= " WHERE u.entity IN (".getEntity('legal_display',1).")";

if ($rowid) $sql.= natural_search("rowid",$rowid);
if ($ref) $sql.= natural_search("ref",$ref);



if ($sall)          $sql.= natural_search(array_keys($fieldstosearchall), $sall);
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
	$crit=$val;
	$tmpkey=preg_replace('/search_options_/','',$key);
	$typ=$extrafields->attribute_type[$tmpkey];
	$mode=0;
	if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
	if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
	{
		$sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
	}
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=$db->order($sortfield,$sortorder);
//$sql.= $db->plimit($conf->liste_limit+1, $offset);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

dol_syslog($script_file, LOG_DEBUG);
$resql=$db->query($sql);
if (! $resql)
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/digiriskdolibarr/sql/legaldisplay_card.php?id='.$id);
	exit;
}

llxHeader('', $title, $help_url);

$arrayofselected=is_array($toselect)?$toselect:array();

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($rowid != '') $param.= '&amp;search_field1='.urlencode($rowid);
if ($ref != '') $param.= '&amp;search_field2='.urlencode($ref);
if ($optioncss != '') $param.='&optioncss='.$optioncss;
// Add $param from extra fields
foreach ($search_array_options as $key => $val)
{
	$crit=$val;
	$tmpkey=preg_replace('/search_options_/','',$key);
	if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
}

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
if (! empty($arrayfields['t.date_creation']['checked'])) print_liste_field_titre($arrayfields['t.date_creation']['label'],$_SERVER['PHP_SELF'],'t.date_creation','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.date_debut']['checked'])) print_liste_field_titre($arrayfields['t.date_debut']['label'],$_SERVER['PHP_SELF'],'t.date_debut','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.date_fin']['checked'])) print_liste_field_titre($arrayfields['t.date_fin']['label'],$_SERVER['PHP_SELF'],'t.date_fin','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.fk_soc_labour_doctor']['checked'])) print_liste_field_titre($arrayfields['t.fk_soc_labour_doctor']['label'],$_SERVER['PHP_SELF'],'t.fk_soc_labour_doctor','',$param,'',$sortfield,$sortorder);

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
print '<tr class="liste_titre">';
// LIST_OF_TD_TITLE_SEARCH
//if (! empty($arrayfields['t.field1']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_field1" value="'.$rowid.'" size="10"></td>';
//if (! empty($arrayfields['t.field2']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_field2" value="'.$ref.'" size="10"></td>';
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
/*
if (! empty($arrayfields['t.date_debut']['checked']))
{
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
*/
$legaldisplaystatic = new Legaldisplay($db);
$thirdpartystatic = new Societe($db);


print  '</tr>';

/*if (! empty($arrayfields['u.statut']['checked']))
{
    // Status
    print '<td class="liste_titre" align="center">';
    print $form->selectarray('search_statut', array('-1'=>'','0'=>$langs->trans('Disabled'),'1'=>$langs->trans('Enabled')),$search_statut);
    print '</td>';
}*/
// Action column
/*
print '<td class="liste_titre" align="right">';
$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
print $searchpitco;
print '</td>';
print '</tr>'."\n";
*/

$i=0;
$var=true;
$totalarray=array();
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);
	
	$legaldisplaystatic->id = $obj->rowid;
	$legaldisplaystatic->ref = $obj->ref;
	$legaldisplaystatic->entity = $obj->entity;
	$legaldisplaystatic->date_creation = $obj->date_creation;
	$legaldisplaystatic->date_debut = $db->jdate($obj->date_debut);
	$legaldisplaystatic->date_fin = $db->jdate($obj->date_fin);
	$legaldisplaystatic->fk_soc_labour_doctor = $db->jdate($obj->fk_soc_labour_doctor);
	$legaldisplaystatic->fk_soc_labour_inspector = $db->jdate($obj->fk_soc_labour_inspector);
	$legaldisplaystatic->fk_soc_samu = $db->jdate($obj->fk_soc_samu);
	$legaldisplaystatic->fk_soc_police = $db->jdate($obj->fk_soc_police);
	$legaldisplaystatic->fk_soc_urgency = $obj->fk_soc_urgency;
	$legaldisplaystatic->description = $obj->description;
	$legaldisplaystatic->import_key = $obj->import_key;
	$legaldisplaystatic->status = $obj->status;
	$legaldisplaystatic->fk_user_creat = $obj->fk_user_creat;
	$legaldisplaystatic->model_pdf = $obj->model_pdf;
	$legaldisplaystatic->model_odt = $obj->model_odt;
	$legaldisplaystatic->note_affich = $obj->note_affich;

	$thirdpartystatic->id = $obj->fk_soc_labour_doctor;
	$thirdpartystatic->name = $obj->name;
	$thirdpartystatic->client = $obj->client;
	$thirdpartystatic->fournisseur = $obj->fournisseur;
	$thirdpartystatic->code_client = $obj->code_client;
	$thirdpartystatic->code_compta_client = $obj->code_compta_client;
	$thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
	$thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
	$thirdpartystatic->email = $obj->email;
	$thirdpartystatic->country_code = $obj->country_code;
	
	if ($obj)
	{
		
		$var = !$var;

		// Extra fields
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
		{
			foreach($extrafields->attribute_label as $key => $val)
			{
				if (! empty($arrayfields["ef.".$key]['checked']))
				{
					print '<td';
					$align=$extrafields->getAlignFlag($key);
					if ($align) print ' align="'.$align.'"';
					print '>';
					$tmpkey='options_'.$key;
					print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
					print '</td>';
					if (! $i) $totalarray['nbfield']++;
				}
			}
		}
		// Fields from hook
		/*$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		
		print $hookmanager->resPrint;*/
		print '<tr class="oddeven">';

		// Ref
		if (! empty($arrayfields['t.ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $legaldisplaystatic->getNomUrl(1);
			print '</td>';
		}
		// Date creation

		if (! empty($arrayfields['t.date_debut']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print dol_print_date($db->jdate($obj->date_debut), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['t.date_fin']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print dol_print_date($db->jdate($obj->date_fin), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		//User
		if (!empty($arrayfields['t.fk_soc_labour_doctor']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			if ($contextpage == 'postList')
			{
				print $thirdpartystatic->id;
				
			}
			else
			{
				print $thirdpartystatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Status
		/*
		if (! empty($arrayfields['u.statut']['checked']))
		{
		  $userstatic->statut=$obj->statut;
		  print '<td align="center">'.$userstatic->getLibStatut(3).'</td>';
		}*/

		// Action column
		print '<td class="wrap" align="center">';

		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
		print '</tr>';

	}
	$i++;
}

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

/*
if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)
{
	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->facture->lire;
	$delallowed=$user->rights->facture->lire;

	print $formfile->showdocuments('massfilesarea_digiriskdolibarr','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'');
}
else
{
	print '<br><a name="show_files"></a><a href="'.$_SERVER["PHP_SELF"].'?show_files=1'.$param.'#show_files">'.$langs->trans("ShowTempMassFilesArea").'</a>';
}

*/
// End of page
llxFooter();
$db->close();
