<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       dev/Legaldisplays/Legaldisplay_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2020-11-09 16:48
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
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
dol_include_once('/digiriskdolibarr/class/legaldisplay.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');

$date_start = dol_mktime(0, 0, 0, GETPOST('date_debutmonth', 'int'), GETPOST('date_debutday', 'int'), GETPOST('date_debutyear', 'int'));
$date_end = dol_mktime(0, 0, 0, GETPOST('date_finmonth', 'int'), GETPOST('date_finday', 'int'), GETPOST('date_finyear', 'int'));
$date = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
$ref = GETPOST("ref", 'alpha');

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

if (empty($action) && empty($id) && empty($ref)) $action='create';

// Load object if id or ref is provided as parameter
$object=new Legaldisplay($db);
if (($id > 0 || ! empty($ref)) && $action != 'add')
{
	$result=$object->fetch($id,$ref);
	if ($result < 0) dol_print_error($db);
}

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('Legaldisplay'));
$extrafields = new ExtraFields($db);



/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Action to add record
	if ($action == 'add')
	{
		if (GETPOST('cancel'))
		{
			$urltogo=$backtopage?$backtopage:dol_buildpath('/buildingmanagement/list.php',1);
			header("Location: ".$urltogo);
			exit;
		}

		$error=0;

		/* object_prop_getpost_prop */
		$object->ref = GETPOST("ref");
		$object->date_debut = strtotime(GETPOST("date_debut"));
		$object->date_fin = strtotime(GETPOST("date_fin"));
		$object->fk_soc_labour_doctor = GETPOST("labour_doctor");
		$object->fk_soc_labour_inspector = GETPOST("labour_inspector");
		$object->fk_soc_samu = GETPOST("samu");
		$object->fk_soc_police = GETPOST("police");
		$object->fk_soc_urgency = GETPOST("urgency");
		$object->fk_soc_rights_defender = GETPOST("rights_defender");
		$object->fk_soc_antipoison = GETPOST("antipoison");
		$object->fk_soc_responsible_prevent = GETPOST("responsible_prevent");
	

		if (empty($object->ref))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")),'errors');
		}

		if (! $error)
		{
			$result=$object->create($user);
			if ($result > 0)
			{
				// Creation OK
				$urltogo=$backtopage?$backtopage:dol_buildpath('/custom/digiriskdolibarr/class/legaldisplay_list.php', 1);
				header("Location: ".$urltogo);
				exit;
			}
			{
				// Creation KO
				if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
				$action='create';
			}
		}
		else
		{
			$action='create';
		}
	}

	// Cancel
	if ($action == 'update' && GETPOST('cancel')) $action='view';

	// Action to update record
	if ($action == 'update' && ! GETPOST('cancel'))
	{
		$error=0;

		$object->prop1=GETPOST("field1");
		$object->prop2=GETPOST("field2");



		if (empty($object->ref))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")),null,'errors');
		}

		if (! $error)
		{
			$result=$object->update($user);
			if ($result > 0)
			{
				$action='view';
			}
			else
			{
				// Creation KO
				if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
				$action='edit';
			}
		}
		else
		{
			$action='edit';
		}
	}

	// Action to delete
	if ($action == 'confirm_delete')
	{
		$result=$object->delete($user);
		if ($result > 0)
		{
			// Delete OK
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header("Location: ".dol_buildpath('/buildingmanagement/list.php',1));
			exit;
		}
		else
		{
			if (! empty($object->errors)) setEventMessages(null,$object->errors,'errors');
			else setEventMessages($object->error,null,'errors');
		}
	}
}




/***************************************************
 * VIEW
 ****************************************************/

$title = $langs->trans("LegalDisplay");
//if ( !empty( $conf->global->MAIN_HTML_TITLE ) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE ) && $object->name ) $title = $object->name." - ".$langs->trans('Card');
//$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader( '', $title, '' );

$form = new Form($db);
$soc = new Societe($db);


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
		init_needroot();
	});
});
</script>';


// Part to show a list
//if ($action == 'list' || empty($id))
//{
//	$sql = "SELECT";
//	$sql.= " rowid,";
//
//		$sql.= " ref,";
//		$sql.= " ref_ext,";
//		$sql.= " entity,";
//		$sql.= " date_creation,";
//		$sql.= " tms,";
//		$sql.= " date_valid,";
//		$sql.= " description,";
//		$sql.= " import_key,";
//		$sql.= " status,";
//		$sql.= " fk_user_creat,";
//		$sql.= " fk_user_modif,";
//		$sql.= " fk_user_valid,";
//		$sql.= " model_pdf,";
//		$sql.= " model_odt,";
//		$sql.= " note_affich";
//
//
//	// Add fields for extrafields
////	foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
////	// Add fields from hooks
////	$parameters=array();
////	$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
////	$sql.=$hookmanager->resPrint;
////	$sql.= " FROM ".MAIN_DB_PREFIX."legal_display as t";
////	$sql.= " WHERE field3 = 'xxx'";
////	// Add where from hooks
////	$parameters=array();
////	$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
////	$sql.=$hookmanager->resPrint;
////	$sql.= " ORDER BY field1 ASC";
//
//	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
//
//	if (! empty($moreforfilter))
//	{
//		print '<div class="liste_titre">';
//		print $moreforfilter;
//		$parameters=array();
//		$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
//		print $hookmanager->resPrint;
//		print '</div>';
//	}
//
//	print '<table class="noborder">'."\n";
//
//	// Fields title
//	print '<tr class="liste_titre">';
//	print_liste_field_titre($langs->trans('field1'),$_SERVER['PHP_SELF'],'t.field1','',$param,'',$sortfield,$sortorder);
//	print_liste_field_titre($langs->trans('field2'),$_SERVER['PHP_SELF'],'t.field2','',$param,'',$sortfield,$sortorder);
//	$parameters=array();
//	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
//	print $hookmanager->resPrint;
//	print '</tr>'."\n";
//
//	// Fields title search
//	print '<tr class="liste_titre">';
//	print '<td class="liste_titre">';
//	print '<input type="text" class="flat" name="search_field1" value="'.$search_field1.'" size="10">';
//	print '</td>';
//	print '<td class="liste_titre">';
//	print '<input type="text" class="flat" name="search_field2" value="'.$search_field2.'" size="10">';
//	print '</td>';
//	$parameters=array();
//	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
//	print $hookmanager->resPrint;
//	print '</tr>'."\n";
//
//
//	dol_syslog($script_file, LOG_DEBUG);
//	$resql=$db->query($sql);
//	if ($resql)
//	{
//		$num = $db->num_rows($resql);
//		$i = 0;
//		while ($i < $num)
//		{
//			$obj = $db->fetch_object($resql);
//			if ($obj)
//			{
//				// You can use here results
//				print '<tr>';
//				print '<td>';
//				print $obj->field1;
//				print '</td><td>';
//				print $obj->field2;
//				print '</td>';
//				$parameters=array('obj' => $obj);
//				$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
//				print $hookmanager->resPrint;
//				print '</tr>';
//			}
//			$i++;
//		}
//	}
//	else
//	{
//		$error++;
//		dol_print_error($db);
//	}
//
//	$db->free($resql);
//
//	$parameters=array('sql' => $sql);
//	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
//	print $hookmanager->resPrint;
//
//	print "</table>\n";
//	print "</form>\n";
//}
//


// Create
if ($action == 'create')
{
	print_fiche_titre($langs->trans("LegalDisplay"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="create">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	dol_fiche_head();

	print '<table class="border centpercent">'."\n";
	print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td>';
	print '<input class="flat" type="text" size="36" name="ref" value="'.$ref.'">';
	print '</td></tr>';

	// Date start
	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("DateStart").'</td>';
	print '<td>';
	print $form->selectDate($date_start ? $date_start : -1, 'date_debut', 0, 0, 0, '', 1, 1);
	print '</td>';
	print '</tr>';

	// Date end
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td>';
	print '<td>';
	print $form->selectDate($date_end ? $date_end : -1, 'date_fin', 0, 0, 0, '', 1, 1);
	print '</td>';
	print '</tr>';

	if ( $soc->id > 0 && ( ! GETPOST( 'fac_rec', 'int' ) || !empty( $invoice_predefined->frequency ) ) ) {
		// If thirdparty known and not a predefined invoiced without a recurring rule
		print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td>';
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		// Outstanding Bill
		$arrayoutstandingbills = $soc->getOutstandingBills();
		$outstandingBills = $arrayoutstandingbills['opened'];
		print ' ('.$langs->trans('CurrentOutstandingBill').': ';
		print price($outstandingBills, '', $langs, 0, 0, -1, $conf->currency);
		if ($soc->outstanding_limit != '')
		{
			if ($outstandingBills > $soc->outstanding_limit) print img_warning($langs->trans("OutstandingBillReached"));
			print ' / '.price($soc->outstanding_limit, '', $langs, 0, 0, -1, $conf->currency);
		}
		print ')';
		print '</td>';
		print '</tr>'."\n";
	}
	else
	{
		print '<tr><td class="fieldrequired">'.$langs->trans('LabourDoctor').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'labour_doctor', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');

		print '<tr><td class="fieldrequired">'.$langs->trans('LabourInspector').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'labour_inspector', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');

		print '<tr><td class="fieldrequired">'.$langs->trans('Samu').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'samu', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');

		print '<tr><td class="fieldrequired">'.$langs->trans('Police').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'police', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');

		print '<tr><td class="fieldrequired">'.$langs->trans('Urgency').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'urgency', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');

		print '<tr><td class="fieldrequired">'.$langs->trans('RightsDefender').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'rights_defender', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');

		print '<tr><td class="fieldrequired">'.$langs->trans('Antipoison').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'antipoison', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');

		print '<tr><td class="fieldrequired">'.$langs->trans('ResponsiblePrevent').'</td>';
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'responsible_prevent', '', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
		// Option to reload page to retrieve customer informations. Note, this clear other input
		if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE))
		{
			print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
			        var fac_rec = $(\'#fac_rec\').val();
					// reload page
        			window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&fac_rec="+fac_rec;
				});
			});
			</script>';
		}
		print '</td>';
		print '</tr>'."\n";
	}

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="add" value="'.$langs->trans("Create").'"> &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

	print '</form>';
}



// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';

	dol_fiche_head();

	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="add" value="'.$langs->trans("Create").'"></div>';

	print '</form>';
}



// Part to show record
if ($id && (empty($action) || $action == 'view'))
{
	dol_fiche_head();



	dol_fiche_end();


	// Buttons
	print '<div class="tabsAction">'."\n";
	$parameters=array();
	$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	if (empty($reshook))
	{
		if ($user->rights->mymodule->write)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>'."\n";
		}

		if ($user->rights->mymodule->delete)
		{
			if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))	// We can't use preloaded confirm form with jmobile
			{
				print '<div class="inline-block divButAction"><span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span></div>'."\n";
			}
			else
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>'."\n";
			}
		}
	}
	print '</div>'."\n";


	// Example 2 : Adding links to objects
	//$somethingshown=$form->showLinkedObjectBlock($object);
	//$linktoelem = $form->showLinkToObjectBlock($object);
	//if ($linktoelem) print '<br>'.$linktoelem;

}


// End of page
llxFooter();
$db->close();
