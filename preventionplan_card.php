<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       preventionplan_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view preventionplan
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdocuments/preventionplan.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_function.lib.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/preventionplan/mod_preventionplan_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/preventionplan/modules_preventionplan.php');

global $db, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'preventionplancard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object = new PreventionPlan($db);

$object->fetch($id);

$preventionplan = new PreventionPlan($db);
$digiriskelement = new DigiriskElement($db);
$refPreventionPlanMod = new $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON($db);
$hookmanager->initHooks(array('preventionplancard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
$permissiontoread   = $user->rights->digiriskdolibarr->preventionplan->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->preventionplan->write;
$permissiontodelete = $user->rights->digiriskdolibarr->preventionplan->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/preventionplan_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/preventionplan_card.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	// Action to add record
	if ($action == 'add' && $permissiontoadd) {

		$former_id	            = GETPOST('former');
		$maitre_oeuvre_id       = GETPOST('maitre_oeuvre');
		$extintervenant_ids     = GETPOST('ext_intervenants');
		$intervenants_ids       = GETPOST('intervenants_ids');
		$morethan400hours       = GETPOST('morethan400hours');
		$imminentdanger         = GETPOST('imminent_danger');
		$extsociety_id          = GETPOST('ext_society');
		$location               = GETPOST('location');
		$prevention_ids         = GETPOST('prevention_ids');
		$date_debut             = GETPOST('date_debut');
		$date_fin               = GETPOST('date_fin');

		$now = dol_now();
		$preventionplan->ref           = $refPreventionPlanMod->getNextValue($preventionplan);
		$preventionplan->ref_ext       = 'digirisk_' . $preventionplan->ref;
		$preventionplan->date_creation = $preventionplan->db->idate($now);
		$preventionplan->tms           = $now;
		$preventionplan->import_key    = "";
		$preventionplan->status        = 1;
		$preventionplan->type          = $preventionplan->element;
		$preventionplan->element       = $preventionplan->element . '@digiriskdolibarr';

		$preventionplan->fk_user_creat = $user->id ? $user->id : 1;

		$digiriskelement = new DigiriskElement($db);
		$digiriskelement->fetch($location);
		$preventionplan->parent_id     = $digiriskelement->id;
		$preventionplan->parent_type   = $digiriskelement->element_type;

//		if (!empty($prevention_ids)) {
//			foreach ($prevention_ids as $prevention_id) {
//				$prevention = $prevention->fetch($prevention_id);
//				$preventions[$prevention_id]['moyen_prevention'] = $prevention->moyen;
//				$preventions[$prevention_id]['action'] = $prevention->moyen;
//
//				$preventions[$prevention_id]['location'] = $prevention->moyen;
//			}
//		}
//

		$preventionplan->json          = $preventionplan->PreventionPlanFillJSON($intervenants_ids, $preventions_ids, $former_id,$maitre_oeuvre_id,$extintervenant_ids,$morethan400hours,$imminentdanger,$extsociety_id,$date_debut,$date_fin,$location);

		if (!$error) {
			$result = $preventionplan->createCommon($user, true);

			if ($result > 0) {
				// Creation risk + evaluation + task OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				// Creation risk KO
				if (!empty($preventionplan->errors)) setEventMessages(null, $preventionplan->errors, 'errors');
				else  setEventMessages($preventionplan->error, null, 'errors');
			}
		}
	}

	// Action to add record
	if ($action == 'update' && $permissiontoadd) {
//		GETPOST machin
//		appel de digiriskfilljson
	}

	// Action to add record
	if ($action == 'delete' && $permissiontoadd) {
//		GETPOST machin
//		appel de digiriskfilljson
	}

	// Action to build doc
	if ($action == 'builddoc' && $permissiontoadd) {
		$outputlangs = $langs;
		$newlang = '';

		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// To be sure vars is defined
		if (empty($hidedetails)) $hidedetails = 0;
		if (empty($hidedesc)) $hidedesc = 0;
		if (empty($hideref)) $hideref = 0;
		if (empty($moreparams)) $moreparams = null;

		$model      = GETPOST('model', 'alpha');

		$moreparams['object'] = $object;
		$moreparams['user']   = $user;

		$result = $preventionplan->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			if (empty($donotredirect))
			{
				setEventMessages($langs->trans("FileGenerated") . ' - ' . $preventionplan->last_main_doc, null);

				$urltoredirect = $_SERVER['REQUEST_URI'];
				$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
				$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

				header('Location: ' . $urltoredirect . '#builddoc');
				exit;
			}
		}
	}

}

/*
 * View
 */

$form        = new Form($db);
$emptyobject = new stdClass($db);

$title        = $langs->trans("PreventionPlan");
$title_create = $langs->trans("NewPreventionPlan");
$title_edit   = $langs->trans("ModifyPreventionPlan");
$object->picto = 'preventionplan@digiriskdolibarr';

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Part to create
if ($action == 'create')
{
	print load_fiche_titre($title_create, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	dol_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	$type = 'DIGIRISKDOLIBARR_'.strtoupper($preventionplan->element).'_ADDON';
	$digirisk_addon = $conf->global->$type;
	$modele = new $digirisk_addon($db);

	print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="'.$modele->getNextValue($object).'">';
	print $modele->getNextValue($object);
	print '</td></tr>';
//	$intervenants, $preventions, $former_id,$maitre_oeuvre_id,$extintervenant,$intervenants_ids,$morethan400hours,$imminentdanger,$extsociety_id

	//Former -- Formateur
	$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);

	print '<tr>';
	print '<td style="width:10%">'.$form->editfieldkey('Former', 'former_id', '', $object, 0).'</td>';
	print '<td class="maxwidthonsmartphone">';

	if (!GETPOSTISSET('backtopage')) print ' <a href="'.DOL_URL_ROOT.'/user/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	//@todo enlever le multiselect
	print $form->selectarray('former', $userlist, 0, null, null, null, null, "40%");

	print '</td></tr>';

	//Maitre d'oeuvre
	$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);

	print '<tr>';
	print '<td style="width:10%">'.$form->editfieldkey('MaitreOeuvre', 'MaitreOeuvre_id', '', $object, 0).'</td>';
	print '<td class="maxwidthonsmartphone">';

	if (!GETPOSTISSET('backtopage')) print ' <a href="'.DOL_URL_ROOT.'/user/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';

	//@todo enlever le multiselect
	print $form->selectarray('maitre_oeuvre', $userlist, 0, null, null, null, null, "40%");

	print '</td></tr>';

	//Intervenants extérieurs
	$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);

	print '<tr>';
	print '<td style="width:10%">'.$form->editfieldkey('ExtIntervenants', 'TitularsCSE_id', '', $object, 0).'</td>';
	print '<td class="maxwidthonsmartphone">';

	if (!GETPOSTISSET('backtopage')) print ' <a href="'.DOL_URL_ROOT.'/user/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';

	//@todo enlever le multiselect
	print $form->selectarray('ext_intervenants', $userlist, 0, 0, null, null, null, "40%");

	print '</td></tr>';

	//@todo preventions et intervenants
	$prevention_ids         = GETPOST('prevention_ids');
	$intervenants_ids       = GETPOST('intervenants_ids');

	//Location
	print '<tr><td class="tdtop">';
	print $langs->trans("Location");
	print '</td>';
	print '<td>';
	print $digiriskelement->select_digiriskelement_list('', 'location');
	print '<br>';
	print '</td></tr>';

	// Duration
	print '<tr><td class="tdtop">';
	print $langs->trans("Durée");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="morethan400hours" name="morethan400hours"'.(GETPOSTISSET('morethan400hours') ? (GETPOST('morethan400hours', 'alpha') != '' ? ' checked=""' : '') : ' checked=""').'"> ';
	$htmltext = $langs->trans("PreventionPlanLastsMoreThan400Hours");
	print $form->textwithpicto($langs->trans("MoreThan400Hours"), $htmltext);
	print '<br>';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr class="oddeven"><td><label for="date_debut">'.$langs->trans("StartDate").'</label></td><td>';
	print $form->selectDate('', 'date_debut', 0, 0, 0);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr class="oddeven"><td><label for="date_fin">'.$langs->trans("EndDate").'</label></td><td>';
	print $form->selectDate('', 'date_fin', 0, 0, 0);
	print '</td></tr>';

	//Imminent danger -- Danger imminent
	print '<tr><td class="tdtop">';
	print $langs->trans("ImminentDanger");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="imminent_danger" name="imminent_danger"'.(GETPOSTISSET('imminent_danger') ? (GETPOST('imminent_danger', 'alpha') != '' ? ' checked=""' : '') : ' checked=""').'"> ';
	$htmltext = $langs->trans("ImminentDanger");
	print $form->textwithpicto('', $htmltext);
	print '<br>';
	print '</td></tr>';

	//External society -- Société extérieure
	print '<tr><td class="tdtop">';
	print $langs->trans("ExternalSociety");
	print '</td>';
	print '<td>';
	print $form->select_thirdparty_list('', 'ext_society');
	print '<br>';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" id ="actionButtonCreate" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelCreate" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($title_edit, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	dol_fiche_head();

	unset($object->fields['status']);
	unset($object->fields['element_type']);
	//unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = preventionplanPrepareHead($object);

	dol_fiche_head($head, 'elementCard', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	$formconfirm = '';
	// Confirmation to delete
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeletePreventionPlan'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Object card
	// ------------------------------------------------------------
	$width = 80; $cssclass = 'photoref';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';
	if (isset($object->element_type)) {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';
	} else {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';
	}

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	if ($object->id) {

		print '<tr><td class="titlefield">' . $langs->trans("ElementType") . '</td><td>';
		print $langs->trans($object->element_type);
		print '</td></tr>';

		print '<div class="titlefield hidden elementID" id="elementID" value="'.$object->id.'">'.$langs->trans("ID").'</div>';
		print '<tr><td class="titlefield">'.$langs->trans("ParentElement").'</td><td>';
		$parent_element = new PreventionPlan($db);
		$result = $parent_element->fetch($object->fk_parent);
		if ($result > 0) {
			print $parent_element->ref . ( !empty($parent_element->description) ?  ' - ' . $parent_element->description : '');
		}
		else
		{
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
		}

		print '</td></tr>';

	}

	//Show common fields
//	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// label
	print '<td class="titlefield tdtop">'.$langs->trans("label").'</td><td>';
	print dol_htmlentitiesbr($object->label);
	print '</td></tr>';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Modify
			if ($permissiontoadd) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>' . "\n";
			}
		}
		print '</div>' . "\n";

		// Document Generation -- Génération des documents
		$includedocgeneration = 1;
		if ($includedocgeneration) {
			print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';

			$objref = dol_sanitizeFileName($object->ref);
			$dir_files = $preventionplan->element . '/' . $objref;
			$filedir = $upload_dir . '/' . $dir_files;
			$urlsource = $_SERVER["PHP_SELF"] . '?id='. $id;

			print digiriskshowdocuments($modulepart, $dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, $defaultmodel, 1, 0, 28, 0, '', $title, '', $langs->defaultlang, '', $preventionplan);
		}


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="' . dol_buildpath('/digiriskdolibarr/preventionplan_agenda.php', 1) . '?id=' . $object->id . '">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element_type . '@digiriskdolibarr', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}
}

// End of page
llxFooter();
$db->close();
