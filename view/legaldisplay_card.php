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
dol_include_once('../custom/digiriskdolibarr/class/html.formfile.class.php');
dol_include_once('../custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php');
dol_include_once('../contact/class/contact.class.php');
dol_include_once('../core/lib/functions2.lib.php');
dol_include_once('../core/class/html.formorder.class.php');
dol_include_once('../core/class/html.formmargin.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
global $conf;
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
$object = new Legaldisplay($db);
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

		$digirisklinks = digirisk_dolibarr_fetch_links($db, 'all');

		$object->id = GETPOST("id");
		$object->ref = GETPOST("ref");

		$object->fk_socpeople_labour_doctor = $digirisklinks['LabourDoctor']->fk_contact;
		$object->fk_socpeople_labour_inspector =  $digirisklinks['LabourInspector']->fk_contact;
		$object->fk_soc_samu =  $digirisklinks['SAMU']->fk_soc;
		$object->fk_soc_pompiers =  $digirisklinks['Pompiers']->fk_soc;

		$object->fk_soc_police =  $digirisklinks['Police']->fk_soc;
		$object->fk_soc_urgency =  $digirisklinks['AllEmergencies']->fk_soc;
		$object->fk_soc_rights_defender = $digirisklinks['RightsDefender']->fk_soc;
		$object->fk_soc_antipoison =  $digirisklinks['Antipoison']->fk_soc;
		$object->fk_soc_responsible_prevent =  $digirisklinks['Responsible']->fk_soc;

		$object->note_consigne_detaillee = $conf->global->LOCATION_OF_DETAILED_INSTRUCTION;
		$object->note_derogation_permanente = $conf->global->DEROGATION_SCHEDULE_PERMANENT;
		$object->note_derogation_occas = $conf->global->DEROGATION_SCHEDULE_OCCASIONAL;
		$object->note_convention_collective = $conf->global->COLLECTIVE_AGREEMENT_TITLE;
		$object->note_lieu_cc = $conf->global->COLLECTIVE_AGREEMENT_LOCATION;
		$object->note_lieu_du = $conf->global->DUER_LOCATION;
		$object->note_accord_participation = $conf->global->PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE;

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
				$urltogo=$backtopage?$backtopage:dol_buildpath('/custom/digiriskdolibarr/view/legaldisplay_card.php'.'?id='.$object->id, 1);
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
		$object->ref = GETPOST("ref");
		$object->fk_socpeople_labour_doctor = GETPOST("labour_doctor");
		$object->fk_socpeople_labour_inspector = GETPOST("labour_inspector");
		$object->fk_soc_samu = GETPOST("samu");
		$object->fk_soc_pompiers = GETPOST("pompiers");
		$object->fk_soc_police = GETPOST("police");
		$object->fk_soc_urgency = GETPOST("urgency");
		$object->fk_soc_rights_defender = GETPOST("rights_defender");
		$object->fk_soc_antipoison = GETPOST("antipoison");
		$object->fk_soc_responsible_prevent = GETPOST("responsible_prevent");

		$digirisklinks = digirisk_dolibarr_fetch_links($db, 'all');
		$digiriskconst = digirisk_dolibarr_fetch_const($db);

		$object->note_consigne_detaillee = GETPOST("consigne_detaillee");
		$object->note_derogation_permanente = GETPOST("derogation_permanente");
		$object->note_derogation_occas = GETPOST("derogation_occas");
		$object->note_convention_collective = GETPOST("convention_collective");
		$object->note_lieu_cc = GETPOST("lieu_cc");
		$object->note_accord_participation = GETPOST("accord_participation");


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
	else
	{
		/***************************************************
		 * VIEW
		 ****************************************************/

		// Appelle actions_builddoc.inc qui envoie les headers
		// Doit absolument être avant les print sinon les headers ne fonctionnent pas
		$upload_dir = $conf->digiriskdolibarr->multidir_output[1] . '/'. get_exdir(0, 0, 0, 0, $object, 'member');
		$permissiontoadd = 1;
		include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

		$title = $langs->trans("LegalDisplay");

		//if ( !empty( $conf->global->MAIN_HTML_TITLE ) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE ) && $object->name ) $title = $object->name." - ".$langs->trans('Card');
		//$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
		$form = new Form($db);
		$soc = new Societe($db);
		$formfile = new FormFile($db);
		$formmargin = new FormMargin($db);

		llxHeader('', $title, '');

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
				if ($object->id > 0) {
					$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
					$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
				}

			}

			print '</table>'."\n";

			dol_fiche_end();

			print '<div class="center"><input type="submit" class="button" name="add" value="'.$langs->trans("Create").'"> &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

			print '</form>';

		}
		else
		{

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
			// View

			$legaldisplay = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->LegalDisplay;
			
			print '<h1>'.$action.'</h1><br/>';
			dol_fiche_head($head, 'card', $langs->trans("LegalDisplay"), -1, 'trip');

			// Ref
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
			print '</td></tr>';

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Créé par

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Créé par").'</td>';
			print '<td>';

			if ($object->fk_user_creat > 0)
			{
				$usercreat = new User($db);
				$result = $usercreat->fetch($object->fk_user_creat);
				if ($result < 0) dol_print_error('', $usercreat->error);
				elseif ($result > 0) print $usercreat->getNomUrl(-1);
			}
			print '</td></tr>';

			// Médecin du travail

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("LabourDoctor").'</td>';
			print '<td>';
			print $legaldisplay->occupational_health_service->name;
			print '</td></tr>';

			// Inspecteur du travail
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("LabourInspector").'</td>';
			print '<td>';
			print $legaldisplay->detective_work->name;
			print '</td></tr>';

			// SAMU

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("SAMU").'</td>';
			print '<td>';
			print $legaldisplay->emergency_service->samu;
			print '</td></tr>';

			// Pompiers

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Pompiers").'</td>';
			print '<td>';
			print $legaldisplay->emergency_service->pompier;
			print '</td></tr>';

			// Police

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Police").'</td>';
			print '<td>';
			print $legaldisplay->emergency_service->police;
			print '</td></tr>';

			// Urgences

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("TouteUrgence").'</td>';
			print '<td>';
			print $legaldisplay->emergency_service->emergency;
			print '</td></tr>';

			// Défenseur du droit du travail

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("RightsDefender").'</td>';
			print '<td>';
			print $legaldisplay->emergency_service->right_defender;
			print '</td></tr>';

			// Antipoison

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Antipoison").'</td>';
			print '<td>';
			print $legaldisplay->emergency_service->poison_control_center;
			print '</td></tr>';

			// Responsable de prévention

			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("ResponsiblePrevent").'</td>';
			print '<td>';
			print $legaldisplay->safety_rule->responsible_for_preventing;
			print '</td></tr>';


			print('</table>');
			print('</div></div>');

			dol_fiche_end();


				// Buttons
				/*
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
			*/
					// Actions to build doc

				// Example 2 : Adding links to objects
				//$somethingshown=$form->showLinkedObjectBlock($object);
				//$linktoelem = $form->showLinkToObjectBlock($object);
				//if ($linktoelem) print '<br>'.$linktoelem;

			// Select mail models is same action as presend
			if ($action != 'presend')
			{
				print '<a name="builddoc"></a>'; // ancre
				// Documents
				$filename = dol_sanitizeFileName($object->ref);
				$relativepath = $objref.'/'.$objref.'.pdf';
				$filedir = DOL_DATA_ROOT . '/digiriskdolibarr/legaldisplay/'.get_exdir(0, 0, 0, 0, $object, 'member');

				$urlsource = $_SERVER["PHP_SELF"]."?id=".$id;
				$genallowed = 1;
				$delallowed = 1;
				//	echo '<pre>'; var_dump([$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang, '', $object]); echo '</pre>'; exit;

				print $formfile->showdocuments('digiriskdolibarr:legaldisplay', $filename, $filedir, $urlsource, $genallowed, $delallowed);
				$usercancreate = 1;
			}
		}
	}
}







// End of page
llxFooter();
$db->close();
