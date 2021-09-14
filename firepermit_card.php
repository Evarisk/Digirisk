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
 *   	\file       firepermit_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view firepermit
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

require_once __DIR__ . '/class/digiriskdocuments.class.php';
require_once __DIR__ . '/class/digiriskelement.class.php';
require_once __DIR__ . '/class/digiriskresources.class.php';
require_once __DIR__ . '/class/firepermit.class.php';
require_once __DIR__ . '/class/preventionplan.class.php';
require_once __DIR__ . '/class/riskanalysis/risk.class.php';
require_once __DIR__ . '/class/digiriskdocuments/firepermitdocument.class.php';
require_once __DIR__ . '/lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/lib/digiriskdolibarr_firepermit.lib.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/firepermit/mod_firepermit_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/firepermitdet/mod_firepermitdet_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/firepermitdocument/mod_firepermitdocument_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/firepermitdocument/modules_firepermitdocument.php';

global $user, $db, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$lineid                  = GETPOST('lineid', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'firepermitcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object                 = new FirePermit($db);
$preventionplan         = new PreventionPlan($db);
$objectline             = new FirePermitLine($db);
$firepermitdocument     = new FirePermitDocument($db);
$risk                   = new Risk($db);

$object->fetch($id);

$digiriskelement   = new DigiriskElement($db);
$digiriskresources = new DigiriskResources($db);

$refFirePermitMod    = new $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_ADDON($db);
$refFirePermitDetMod = new $conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON($db);

$hookmanager->initHooks(array('firepermitcard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
$permissiontoread   = $user->rights->digiriskdolibarr->firepermitdocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->firepermitdocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->firepermitdocument->delete;

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

	$backurlforlist = dol_buildpath('/digiriskdolibarr/firepermit_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/firepermit_card.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	// Action to add record
	if ($action == 'add' && $permissiontoadd) {

		$maitre_oeuvre_id       = GETPOST('maitre_oeuvre');
		$extsociety_id          = GETPOST('ext_society');
		$extresponsible_id      = GETPOST('ext_society_responsible');
		$extintervenant_ids     = GETPOST('ext_intervenants');
		$labour_inspector_id    = GETPOST('labour_inspector');

		$label                  = GETPOST('label');
		$date_debut             = GETPOST('date_debut');
		$date_fin               = GETPOST('date_fin');
		$description            = GETPOST('description');
		$fk_preventionplan      = GETPOST('fk_preventionplan');

		$now = dol_now();
		$object->ref           = $refFirePermitMod->getNextValue($object);
		$object->ref_ext       = 'digirisk_' . $object->ref;
		$object->date_creation = $object->db->idate($now);
		$object->tms           = $now;
		$object->import_key    = "";
		$object->status        = 1;
		$object->label         = $label;

		$date_debut = DateTime::createFromFormat('d/m/Y',$date_debut);
		$date_fin = DateTime::createFromFormat('d/m/Y',$date_fin);

		$object->description   = $description;
		$object->date_start    = dol_print_date($date_debut->getTimestamp(), 'dayhourrfc');
		$object->date_end      = dol_print_date($date_fin->getTimestamp(), 'dayhourrfc');

		$object->fk_preventionplan      = $fk_preventionplan;
		$object->fk_user_creat          = $user->id ? $user->id : 1;

		if (!$error) {
			$result = $object->create($user, false);

			if ($result > 0) {

				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_MAITRE_OEUVRE', 'user', array($maitre_oeuvre_id), $conf->entity, 'firepermit', $object->id, 1);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY', 'societe', array($extsociety_id), $conf->entity, 'firepermit', $object->id, 1);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY_RESPONSIBLE', 'socpeople', $extresponsible_id, $conf->entity, 'firepermit', $object->id, 1);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_LABOUR_INSPECTOR_ASSIGNED', 'societe', array($labour_inspector_id), $conf->entity, 'firepermit', $object->id, 1);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY_INTERVENANTS', 'socpeople', $extintervenant_ids, $conf->entity, 'firepermit', $object->id, 1);

				// Creation OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				// Creation KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to update record
	if ($action == 'update' && $permissiontoadd) {

		$maitre_oeuvre_id       = GETPOST('maitre_oeuvre');
		$extsociety_id          = GETPOST('ext_society');
		$extresponsible_id      = GETPOST('ext_society_responsible');
		$extintervenant_ids     = GETPOST('ext_intervenants');
		$labour_inspector_id    = GETPOST('labour_inspector');

		$label                  = GETPOST('label');
		$date_debut             = GETPOST('date_debut');
		$date_fin               = GETPOST('date_fin');
		$description 			= GETPOST('description');
		$fk_preventionplan      = GETPOST('fk_preventionplan');

		$now = dol_now();
		$object->tms           = $now;
		$object->label         = $label;

		$date_debut = DateTime::createFromFormat('d/m/Y',$date_debut);
		$date_fin = DateTime::createFromFormat('d/m/Y',$date_fin);

		$object->description         = $description;
		$object->date_start          = dol_print_date($date_debut->getTimestamp(), 'dayhourrfc');
		$object->date_end            = dol_print_date($date_fin->getTimestamp(), 'dayhourrfc');
		$object->fk_preventionplan   = $fk_preventionplan;

		$object->fk_user_creat = $user->id ? $user->id : 1;

		if (!$error) {
			$result = $object->update($user, false);

			if ($result > 0) {

				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_MAITRE_OEUVRE', 'user', array($maitre_oeuvre_id), $conf->entity, 'firepermit', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY', 'societe', array($extsociety_id), $conf->entity, 'firepermit', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY_RESPONSIBLE', 'socpeople', $extresponsible_id, $conf->entity, 'firepermit', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_LABOUR_DOCTOR_ASSIGNED', 'societe', array($labour_inspector_id), $conf->entity, 'firepermit', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY_INTERVENANTS', 'socpeople', $extintervenant_ids, $conf->entity, 'firepermit', $object->id, 0);

				// Update OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				// Update KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to delete record
	if ($action == 'delete' && $permissiontoadd) {

	}

	// Action to add line
	if ($action == 'addLine' && $permissiontoadd) {

		$actions_description = GETPOST('actionsdescription');
		$use_equipment       = GETPOST('use_equipment');
		$location            = GETPOST('fk_element');
		$risk_category_id    = GETPOST('risk_category_id');
		$parent_id           = GETPOST('parent_id');

		$objectline->date_creation      = $object->db->idate($now);
		$objectline->ref                = $refFirePermitDetMod->getNextValue($objectline);
		$objectline->entity             = $conf->entity;
		$objectline->description        = $actions_description;
		$objectline->category           = $risk_category_id;
		$objectline->use_equipment      = $use_equipment;
		$objectline->fk_firepermit      = $parent_id;
		$objectline->fk_element         = $location;

		if ($parent_id < 1) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Location')), null, 'errors');
			$error++;
		}

		if ($risk_category_id < 0 || $risk_category_id == 'undefined') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('INRSRisk')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $objectline->insert(1);

			if ($result > 0) {
				$objectline->call_trigger('FIREPERMITDET_CREATE', $user);

				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				setEventMessages($objectline->error, $objectline->errors, 'errors');
			}
		}
	}

	// Action to update line
	if ($action == 'updateLine' && $permissiontoadd) {

		$actions_description = GETPOST('actionsdescription');
		$use_equipment       = GETPOST('use_equipment');
		$location            = GETPOST('fk_element');
		$risk_category_id    = GETPOST('risk_category_id');
		$parent_id           = GETPOST('parent_id');

		$objectline = new FirePermitLine($db);
		$objectline->fetch($lineid);

		$objectline->description        = $actions_description;
		$objectline->category           = $risk_category_id;
		$objectline->use_equipment      = $use_equipment;
		$objectline->fk_firepermit      = $parent_id;
		$objectline->fk_element         = $location;

		if ($parent_id < 1) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Location')), null, 'errors');
			$error++;
		}
		if ($risk_category_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('INRSRisk')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $objectline->update(1);

			if ($result > 0) {

				// Creation ligne OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				// Creation ligne KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to delete line
	if ($action == 'deleteline' && $permissiontodelete) {

		$objectline = new FirePermitLine($db);
		$result = $objectline->fetch($lineid);

		if ($result > 0) {
			$objectline->delete();
			// Deletion line OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Deletion line KO
			if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else  setEventMessages($object->error, null, 'errors');
		}
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

		$result = $firepermitdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			if (empty($donotredirect))
			{
				setEventMessages($langs->trans("FileGenerated") . ' - ' . $object->last_main_doc, null);

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

$title        = $langs->trans("FirePermit");
$title_create = $langs->trans("NewFirePermit");
$title_edit   = $langs->trans("ModifyFirePermit");
$object->picto = 'firepermitdocument@digiriskdolibarr';

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

	$type = 'DIGIRISKDOLIBARR_'.strtoupper($object->element).'_ADDON';
	$digirisk_addon = $conf->global->$type;
	$modele = new $digirisk_addon($db);

	print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("Ref").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="'.$modele->getNextValue($object).'">';
	print $modele->getNextValue($object);
	print '</td></tr>';


	print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="">';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr class="oddeven"><td><label for="date_debut">'.$langs->trans("StartDate").'</label></td><td>';
	print $form->selectDate('', 'date_debut', 1, 1, 0);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr class="oddeven"><td><label for="date_fin">'.$langs->trans("EndDate").'</label></td><td>';
	print $form->selectDate(dol_time_plus_duree(dol_now(),1,'y'), 'date_fin', 1, 1, 0);
	print '</td></tr>';

	//Maitre d'oeuvre
	$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);

	print '<tr class="oddeven">';
	print '<td style="width:10%">'.$form->editfieldkey('MaitreOeuvre', 'MaitreOeuvre_id', '', $object, 0).'</td>';
	print '<td class="maxwidthonsmartphone">';


	print $form->selectarray('maitre_oeuvre', $userlist, '', $langs->trans('SelectUser'), null, null, null, "40%", 0,0,'','',1);
	if (!GETPOSTISSET('backtopage')) print ' <a href="'.DOL_URL_ROOT.'/user/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';

	print '</td></tr>';

	//External society -- Société extérieure
	print '<tr class="oddeven"><td class="tdtop">';
	print $langs->trans("ExternalSociety");
	print '</td>';

	print '<td>';

	$events = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_society_responsible', 'params' => array('add-customer-contact' => 'disabled'));
	$events[2] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_intervenants', 'params' => array('add-customer-contact' => 'disabled'));
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'ext_society', '', 1, 1, 0, $events, 0, 'minwidth300');
	} else {
		print $form->select_company('', 'ext_society', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	}
	if (!GETPOSTISSET('backtopage')) print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';

	print '<br>';

	print '</td></tr>';

	//External responsible -- Responsable de la société extérieure
	print '<tr class="oddeven"><td>'.$langs->trans("ExternalSocietyResponsible").'</td><td>';
	print $form->selectcontacts(GETPOST('ext_society', 'int'), '', 'ext_society_responsible[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, '', 'ext_society_responsible');

	print '</td></tr>';

	//Intervenants extérieurs
	print '<tr class="oddeven"><td>'.$langs->trans("ExternalIntervenants").'</td><td>';
	print $form->selectcontacts(GETPOST('ext_society', 'int'), '', 'ext_intervenants[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'ext_intervenants');

	print '</td></tr>';

	//Labour inspector -- Inspecteur du travail
	print '<tr class="oddeven"><td class="tdtop">';
	print $langs->trans("LabourInspector");
	print '</td>';
	print '<td>';

	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'labour_inspector', '', 1, 1, 0, $events, 0, 'minwidth300');
	} else {

		print $form->select_company($digiriskresources->digirisk_dolibarr_fetch_resource('SAMU'), 'labour_inspector', '', 'SelectThirdParty', 1, 0, '', 0, 'minwidth300');
	}	print '<br>';

	print '</td></tr>';

	//FK PREVENTION PLAN
	print '<tr class="oddeven"><td>'.$langs->trans("PreventionPlanLinked").'</td><td>';
	print $preventionplan->select_preventionplan_list();
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

	$object_resources = $digiriskresources->fetchResourcesFromObject('', $object);

	print '<table class="border centpercent tableforfieldedit">'."\n";

	print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td>';
	print $object->ref;
	print '</td></tr>';


	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.$object->label.'">';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr class="oddeven"><td><label for="date_debut">'.$langs->trans("StartDate").'</label></td><td>';
	print $form->selectDate('', 'date_debut', 1, 1, 0);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr class="oddeven"><td><label for="date_fin">'.$langs->trans("EndDate").'</label></td><td>';
	print $form->selectDate(dol_time_plus_duree(dol_now(),1,'y'), 'date_fin', 1, 1, 0);
	print '</td></tr>';

	//Maitre d'oeuvre
	$userlist 	  = $form->select_dolusers(is_array($object_resources['FP_MAITRE_OEUVRE']) ? array_shift($object_resources['FP_MAITRE_OEUVRE'])->id : '', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);

	print '<tr>';
	print '<td style="width:10%">'.$form->editfieldkey('MaitreOeuvre', 'MaitreOeuvre_id', '', $object, 0).'</td>';
	print '<td class="maxwidthonsmartphone">';

	if (!GETPOSTISSET('backtopage')) print ' <a href="'.DOL_URL_ROOT.'/user/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';

	print $form->selectarray('maitre_oeuvre', $userlist, 0, null, null, null, null, "40%");

	print '</td></tr>';

	//External society -- Société extérieure
	print '<tr><td class="tdtop">';
	print $langs->trans("ExternalSociety");
	print '</td>';

	print '<td>';
	if (!GETPOSTISSET('backtopage')) print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';

	$events = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_society_responsible', 'params' => array('add-customer-contact' => 'disabled'));
	$events[2] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_intervenants', 'params' => array('add-customer-contact' => 'disabled'));
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'ext_society', '', 1, 1, 0, $events, 0, 'minwidth300');
	} else {
		$ext_society_id = is_array($object_resources['FP_EXT_SOCIETY']) ? array_shift($object_resources['FP_EXT_SOCIETY'])->id : '';

		print $form->select_company($ext_society_id, 'ext_society', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	}	print '<br>';

	print '</td></tr>';

	//External responsible -- Responsable de la société extérieure
	$ext_society_responsible_id = is_array($object_resources['FP_EXT_SOCIETY_RESPONSIBLE']) ? array_shift($object_resources['FP_EXT_SOCIETY_RESPONSIBLE'])->id : '';
	print '<tr class="oddeven"><td>'.$langs->trans("ExternalSocietyResponsible").'</td><td>';
	print $form->selectcontacts(GETPOST('ext_society', 'int'), $ext_society_responsible_id, 'ext_society_responsible[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, '', 'ext_society_responsible');

	print '</td></tr>';

	//Intervenants extérieurs
	$resources_ids = array();

	if (!empty ($object_resources['FP_EXT_SOCIETY_INTERVENANTS']) && $object_resources['FP_EXT_SOCIETY_INTERVENANTS'] > 0) {
		foreach ($object_resources['FP_EXT_SOCIETY_INTERVENANTS'] as $resource) {
			$resources_ids[] = $resource->id;
		}
	}
	print '<tr class="oddeven"><td>'.$langs->trans("ExternalIntervenants").'</td><td>';
	print $form->selectcontacts(GETPOST('ext_society', 'int'),$resources_ids, 'ext_intervenants[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'ext_intervenants');

	print '</td></tr>';

	// CSSCT Intervention
	print '<tr><td class="tdtop">';
	print $langs->trans("CSSCTIntervention");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="cssct_intervention" name="cssct_intervention"'.($object->cssct_intervention? ' checked=""' : '').'"> ';
	$htmltext = $langs->trans("CSSCTInterventionText");
	print $form->textwithpicto('', $htmltext);
	print '<br>';
	print '</td></tr>';

	//Prior Visit -- Visite préalable
	print '<tr><td class="tdtop">';
	print $langs->trans("PriorVisit");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="prior_visit_bool" name="prior_visit_bool"'.($object->prior_visit_bool? ' checked=""' : '').'"> ';
	$htmltext = $langs->trans("PriorVisitText");
	print $form->textwithpicto('', $htmltext);

	print '<input class="flat" type="text" size="36" name="prior_visit_text" id="prior_visit_text" value="'.$object->prior_visit_text.'">';
	print '</td></tr>';

	//Labour inspector -- Inspecteur du travail
	print '<tr><td class="tdtop">';
	print $langs->trans("LabourInspector");
	print '</td>';
	print '<td>';

	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'labour_inspector', '', 1, 1, 0, $events, 0, 'minwidth300');
	} else {

		print $form->select_company($digiriskresources->digirisk_dolibarr_fetch_resource('SAMU'), 'labour_inspector', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	}	print '<br>';

	print '</td></tr>';

	//FK PREVENTION PLAN
	print '<tr class="oddeven"><td>'.$langs->trans("PreventionPlanLinked").'</td><td>';
	print $preventionplan->select_preventionplan_list($object->fk_preventionplan);
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit')))
{
	$res = $object->fetch_optionals();

	$head = firepermitPrepareHead($object);

	dol_fiche_head($head, 'firepermitCard', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	$formconfirm = '';
	// Confirmation to delete
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteFirePermit'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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
	$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';

	dol_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";

	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	//Master builder -- Maitre Oeuvre
	print '<tr><td class="tdtop">';
	print $langs->trans("MaitreOeuvre");
	print '</td>';
	print '<td>';
	$master_builder = $digiriskresources->fetchResourcesFromObject('FP_MAITRE_OEUVRE', $object);

	if ($master_builder > 0) {

		print $master_builder->getNomUrl(1);
	}
	print '<br>';
	print '</td></tr>';

	//External Society -- Société extérieure
	print '<tr><td class="tdtop">';
	print $langs->trans("ExtSociety");
	print '</td>';
	print '<td>';
	$ext_society = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY', $object);
	if ($ext_society > 0) {
		print $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY', $object)->getNomUrl(1);
	}
	print '<br>';
	print '</td></tr>';

	//External Society Responsible -- Responsable Société extérieure
	print '<tr><td class="tdtop">';
	print $langs->trans("ExtSocietyResponsible");
	print '</td>';
	print '<td>';
	$ext_society_responsible = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY_RESPONSIBLE', $object);
	if ($ext_society_responsible > 0) {
		print $ext_society_responsible->getNomUrl(1);
	}
	print '<br>';
	print '</td></tr>';

	//External Society Intervenants -- Intervenants Société extérieure
	print '<tr><td class="tdtop">';
	print $langs->trans("ExtSocietyIntervenants");
	print '</td>';
	print '<td>';
	$ext_society_intervenants = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY_INTERVENANTS', $object);

	if (is_array($ext_society_intervenants) && !empty ($ext_society_intervenants) && $ext_society_intervenants > 0) {
		$ext_society_intervenants = array_shift($ext_society_intervenants);
		foreach($ext_society_intervenants as $ext_society_intervenant) {
			print $ext_society_intervenant->getNomUrl(1);
			print '<br>';
		}
	} elseif (!empty ($ext_society_intervenants) && $ext_society_intervenants > 0){
		print $ext_society_intervenants->getNomUrl(1);
	}

	//FK PREVENTION PLAN
	$preventionplan->fetch($object->fk_preventionplan);
	print '<tr class="oddeven"><td>'.$langs->trans("PreventionPlanLinked").'</td><td>';
	print $preventionplan->ref;
	print '</td></tr>';

	print '<br>';
	print '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

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

		// PreventionPlan LINES
		print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">' . "\n";
		print '<table id="tablelinespreventionplan" class="noborder noshadow" width="100%">';

		global $forceall, $forcetoshowtitlelines;

		if (empty($forceall)) $forceall = 0;

		// Define colspan for the button 'Add'
		$colspan = 3; // Columns: total ht + col edit + col delete


		// Linked prevention plan Lines
		$preventionplanline = new PreventionPlanLine($db);
		$preventionplanline->db = $db;
		$preventionplanlines = $preventionplanline->fetchAll($object->fk_preventionplan);

		print '<tr class="liste_titre nodrag nodrop">';
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum center"></td>';
		}
		print '<td class="linecolref minwidth200imp">';
		print '<div id="add"></div><span class="hideonsmartphone">'.$langs->trans('Ref.').'</span>';
		print '</td>';
		print '<td class="linecollocation">'.$langs->trans('Location').'</td>';
		print '<td class="linecolactionsdescription">'.$form->textwithpicto($langs->trans('ActionsDescription'), $langs->trans("ActionsDescriptionTooltip")).'</td>';
		print '<td class="linecolriskcategory">'.$form->textwithpicto($langs->trans('INRSRisk'), $langs->trans('INRSRiskTooltip')).'</td>';
		print '<td class="linecolpreventionmethod">'.$form->textwithpicto($langs->trans('PreventionMethod'), $langs->trans('PreventionMethodTooltip')).'</td>';
		print '<td class="linecoledit" colspan="'.$colspan.'">&nbsp;</td>';
		print '</tr>';

		if ($object->fk_preventionplan > 0) {
			if (! empty($preventionplanlines) && $preventionplanlines > 0) {
				print '<tr>';
				foreach($preventionplanlines as $key => $item) {

					print '<td class="bordertop nobottom linecolref minwidth500imp">';
					print $item->ref;
					print '</td>';

					print '<td class="bordertop nobottom linecollocation">';
					$digiriskelement->fetch($item->fk_element);
					print $digiriskelement->ref . " - " . $digiriskelement->label;
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecolactionsdescription">';
					print $item->description;
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecolriskcategory">'; ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risk->get_danger_category_name($item) ?>">
							<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($item) . '.png' ; ?>"/>
						</div>
					</div>
					<?php
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecolpreventionmethod">';
					print $item->prevention_method;
					print '</td>';

					$coldisplay += $colspan;

					print '</tr>';

					if (is_object($objectline)) {
						print $objectline->showOptionals($extrafields, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', 1);
					}
				}
				print '</tr>';
			} else {
				print '<tr>';
				print '<td class="bordertop nobottom ">';
				print $langs->trans('NoPreventionPlanRisk');
				print '</td></tr>';
			}
		}  else {
			print '<tr>';
			print '<td class="bordertop nobottom ">';
			print $langs->trans('NoPreventionPlanLinked');
			print '</td></tr>';
		}

		print '</table></div>';

		// FIREPERMIT LINES
		print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">' . "\n";
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		global $forceall, $forcetoshowtitlelines;

		if (empty($forceall)) $forceall = 0;

		// Define colspan for the button 'Add'
		$colspan = 3; // Columns: total ht + col edit + col delete


		// Fire permit Lines
		$firepermitline = new FirePermitLine($db);
		$firepermitline->db = $db;
		$firepermitlines = $firepermitline->fetchAll(GETPOST('id'));

		print '<tr class="liste_titre nodrag nodrop">';
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum center"></td>';
		}
		print '<td class="linecoldescription">';
		print '<div id="add"></div><span class="hideonsmartphone">'.$langs->trans('Ref.').'</span>';
		print '</td>';
		print '<td class="linecollocation">'.$langs->trans('Location').'</td>';
		print '<td class="linecolactionsdescription">'.$form->textwithpicto($langs->trans('ActionsDescription'), $langs->trans("ActionsDescriptionTooltip")).'</td>';
		print '<td class="linecolriskcategory">'.$form->textwithpicto($langs->trans('INRSRisk'), $langs->trans('INRSRiskTooltip')).'</td>';
		print '<td class="linecolpreventionmethod">'.$form->textwithpicto($langs->trans('UsedMaterial'), $langs->trans('UsedMaterialTooltip')).'</td>';
		print '<td class="linecoledit" colspan="'.$colspan.'">&nbsp;</td>';
		print '</tr>';
		print '<tr>';

		if (! empty($firepermitlines) && $firepermitlines > 0) {
			foreach($firepermitlines as $key => $item) {
				if ($action == 'editline' && $lineid == $key) {

					print '<form method="POST" action="'.$_SERVER["PHP_SELF"] . '?id=' . $object->id .'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="updateLine">';
					print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
					print '<input type="hidden" name="lineid" value="'.$item->id.'">';
					print '<input type="hidden" name="parent_id" value="'.$object->id.'">';

					print '<tr class="pair nodrag nodrop nohoverpair'.(($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create').'">';

					print '<td class="bordertop nobottom linecolref minwidth500imp">';
					print $item->ref;
					print '</td>';

					print '<td class="bordertop nobottom linecollocation">';
					print $digiriskelement->select_digiriskelement_list($item->fk_element, 'fk_element', '', '',  0, 0, array(), '',  0,  0,  'minwidth100',  GETPOST('id'),  false);
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecolactionsdescription">';
					print '<textarea name="actionsdescription" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . $item->description . '</textarea>' . "\n";
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecolriskcategory">'; ?>
					<span class="title"><?php echo $langs->trans('Risk'); ?><required>*</required></span>
					<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
						<div class="dropdown-toggle dropdown-add-button button-cotation">
							<input class="input-hidden-danger" type="hidden" name="risk_category_id" value="<?php echo $item->category ?>" />
							<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risk->get_danger_category_name($item) ?>">
								<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($item) . '.png' ; ?>"/>
							</div>
						</div>

						<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
							<?php
							$dangerCategories = $risk->get_danger_categories();
							if ( ! empty( $dangerCategories ) ) :
								foreach ( $dangerCategories as $dangerCategory ) : ?>
									<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>" aria-label="<?php echo $dangerCategory['name'] ?>">
										<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
									</li>
								<?php endforeach;
							endif; ?>
						</ul>
					</div>
					<?php
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecoluseequipment">';
					print '<textarea name="use_equipment" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . $item->use_equipment . '</textarea>' . "\n";
					print '</td>';

					$coldisplay += $colspan;
					print '<td class="bordertop nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
					print '<input type="submit" class="button" value="'.$langs->trans('Save').'" name="updateLine" id="updateLine">';
					print '</td>';
					print '</tr>';

					if (is_object($objectline)) {
						print $objectline->showOptionals($extrafields, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', 1);
					}
					print '</form>';
					?>
					<script>
						/* JQuery for product free or predefined select */
						jQuery(document).ready(function() {
							/* When changing predefined product, we reload list of supplier prices required for margin combo */
							$("#idprod").change(function()
							{
								console.log("#idprod change triggered");

								/* To set focus */
								if (jQuery('#idprod').val() > 0)
								{
									/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
									jQuery('#dp_desc').focus();
									/* focus if CKEDITOR */
									if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
									{
										var editor = CKEDITOR.instances['dp_desc'];
										if (editor) { editor.focus(); }
									}
								}
							});
						});
					</script> <?php
				} else {
					print '<td class="bordertop nobottom linecolref minwidth500imp">';
					print $item->ref;
					print '</td>';

					print '<td class="bordertop nobottom linecollocation">';
					$digiriskelement->fetch($item->fk_element);
					print $digiriskelement->ref . " - " . $digiriskelement->label;
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecolactionsdescription">';
					print $item->description;
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecolriskcategory">'; ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event" aria-label="<?php echo $risk->get_danger_category_name($item) ?>">
							<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($item) . '.png' ; ?>"/>
						</div>
					</div>
					<?php
					print '</td>';

					$coldisplay++;
					print '<td class="bordertop nobottom linecoluseequipment">';
					print $item->use_equipment;
					print '</td>';

					$coldisplay += $colspan;

					//Actions buttons
					print '<td class="linecoledit center">';
					$coldisplay++;
					if (($item->info_bits & 2) == 2 || !empty($disableedit)) {
					} else {
						print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=editline&amp;lineid='.$item->id.'">'.img_edit().'</a>';
					}
					print '</td>';

					print '<td class="linecoldelete center">';
					$coldisplay++;

					//La suppression n'est autorisée que si il n'y a pas de ligne dans une précédente situation
					print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=deleteline&amp;lineid='.$item->id.'">';
					print img_delete();
					print '</a>';
					print '</td>';

					print '</tr>';

					if (is_object($objectline)) {
						print $objectline->showOptionals($extrafields, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', 1);
					}
				}
			}
			print '</tr>';
		}

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"] . '?id=' . $object->id .'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="addLine">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="parent_id" value="'.$object->id.'">';

		print '<tr class="pair nodrag nodrop nohoverpair'.(($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create').'">';

		print '<td class="bordertop nobottom linecolref minwidth500imp">';
		print $refFirePermitDetMod->getNextValue($firepermitline);
		print '</td>';
		print '<td class="bordertop nobottom linecollocation">';
		print $digiriskelement->select_digiriskelement_list('', 'fk_element', '', '',  0, 0, array(), '',  0,  0,  'minwidth100',  GETPOST('id'),  false);
		print '</td>';

		$coldisplay++;
		print '<td class="bordertop nobottom linecolactionsdescription">';
		print '<textarea name="actionsdescription" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n";
		print '</td>';

		$coldisplay++;
		print '<td class="bordertop nobottom linecolriskcategory">'; ?>
			<span class="title"><?php echo $langs->trans('Risk'); ?><required>*</required></span>
			<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
				<input class="input-hidden-danger" type="hidden" name="risk_category_id" value="undefined" />
				<div class="dropdown-toggle dropdown-add-button button-cotation">
					<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add"></i></span>
					<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
				</div>
				<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
					<?php
					$dangerCategories = $risk->get_danger_categories();
					if ( ! empty( $dangerCategories ) ) :
						foreach ( $dangerCategories as $dangerCategory ) : ?>
							<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>" aria-label="<?php echo $dangerCategory['name'] ?>">
								<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
							</li>
						<?php endforeach;
					endif; ?>
				</ul>
			</div>
		<?php
		print '</td>';

		$coldisplay++;
		print '<td class="bordertop nobottom linecolpreventionmethod">';
		print '<textarea name="preventionmethod" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n";
		print '</td>';

		$coldisplay += $colspan;
		print '<td class="bordertop nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
		print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline" id="addline">';
		print '</td>';
		print '</tr>';

		if (is_object($objectline)) {
			print $objectline->showOptionals($extrafields, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', 1);
		}
		?>
		<script>
			/* JQuery for product free or predefined select */
			jQuery(document).ready(function() {
				/* When changing predefined product, we reload list of supplier prices required for margin combo */
				$("#idprod").change(function()
				{
					console.log("#idprod change triggered");

					/* To set focus */
					if (jQuery('#idprod').val() > 0)
					{
						/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
						jQuery('#dp_desc').focus();
						/* focus if CKEDITOR */
						if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
						{
							var editor = CKEDITOR.instances['dp_desc'];
							if (editor) { editor.focus(); }
						}
					}
				});
			});
		</script> <?php
		print '</form>';
		print '</table>';
		print '</div>';

		// Document Generation -- Génération des documents
		$includedocgeneration = 1;
		if ($includedocgeneration) {
			print '<div class="fichecenter"><div class="fichehalfleft firepermitDocument">';

			$objref = dol_sanitizeFileName($object->ref);
			$dir_files = $firepermitdocument->element . '/' . $objref;
			$filedir = $upload_dir . '/' . $dir_files;
			$urlsource = $_SERVER["PHP_SELF"] . '?id='. $id;

			$modulepart = 'digiriskdolibarr:FirePermitDocument';
			$defaultmodel = $conf->global->DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_DEFAULT_MODEL;
			$title = $langs->trans('FirePermitDocument');

			print digiriskshowdocuments($modulepart, $dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, $defaultmodel, 1, 0, 28, 0, '', $title, '', $langs->defaultlang, '', $firepermitdocument);
		}


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="' . dol_buildpath('/digiriskdolibarr/firepermit_agenda.php', 1) . '?id=' . $object->id . '">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element . '@digiriskdolibarr', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}
}

// End of page
llxFooter();
$db->close();
