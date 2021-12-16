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
 *   	\file       view/preventionplan/preventionplan_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view preventionplan
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

require_once __DIR__ . '/../../class/digiriskdocuments.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/digiriskdocuments/preventionplandocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_preventionplan.lib.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/preventionplan/mod_preventionplan_standard.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/preventionplandet/mod_preventionplandet_standard.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskdocuments/preventionplandocument/mod_preventionplandocument_standard.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskdocuments/preventionplandocument/modules_preventionplandocument.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));
// Get parameters
$id                  = GETPOST('id', 'int');
$lineid              = GETPOST('lineid', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'preventionplancard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object                  = new PreventionPlan($db);
$objectline              = new PreventionPlanLine($db);
$signatory               = new PreventionPlanSignature($db);
$preventionplandocument  = new PreventionPlanDocument($db);
$digiriskelement         = new DigiriskElement($db);
$digiriskresources       = new DigiriskResources($db);
$risk                    = new Risk($db);
$contact                 = new Contact($db);
$usertmp                 = new User($db);
$extrafields             = new ExtraFields($db);
$resources               = new DigiriskResources($db);
$thirdparty              = new Societe($db);
$project                 = new Project($db);
$refPreventionPlanMod    = new $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON($db);
$refPreventionPlanDetMod = new  $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON($db);

// Load object
$object->fetch($id);

// Load resources
$allLinks = $resources->digirisk_dolibarr_fetch_resources();

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$extrafields->fetch_name_optionals_label($objectline->table_element);

$hookmanager->initHooks(array('preventionplancard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
// Security check
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

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/preventionplan/preventionplan_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/view/preventionplan/preventionplan_card.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	if (GETPOST('cancel') || GETPOST('cancelLine')) {
		// Cancel prevention plan
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}

	// Action to add record
	if ($action == 'add' && $permissiontoadd) {
		// Get parameters
		$maitre_oeuvre_id            = GETPOST('maitre_oeuvre');
		$extsociety_id               = GETPOST('ext_society');
		$extresponsible_id           = GETPOST('ext_society_responsible');
		$extintervenant_ids          = GETPOST('ext_intervenants');
		$labour_inspector_id         = GETPOST('labour_inspector');
		$labour_inspector_contact_id = GETPOST('labour_inspector_contact');
		$label                       = GETPOST('label');
		$prior_visit_bool            = GETPOST('prior_visit_bool');
		$prior_visit_text            = GETPOST('prior_visit_text');
		$cssct_intervention          = GETPOST('cssct_intervention');

		// Initialize object preventionplan
		$now                   = dol_now();
		$object->ref           = $refPreventionPlanMod->getNextValue($object);
		$object->ref_ext       = 'digirisk_' . $object->ref;
		$object->date_creation = $object->db->idate($now);
		$object->tms           = $now;
		$object->import_key    = "";
		$object->label         = $label;
		$object->fk_project    = $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT;

		$date_start       = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));
		$date_end         = dol_mktime(GETPOST('dateehour', 'int'), GETPOST('dateemin', 'int'), 0, GETPOST('dateemonth', 'int'), GETPOST('dateeday', 'int'), GETPOST('dateeyear', 'int'));
		$prior_visit_date = dol_mktime(GETPOST('dateihour', 'int'), GETPOST('dateimin', 'int'), 0, GETPOST('dateimonth', 'int'), GETPOST('dateiday', 'int'), GETPOST('dateiyear', 'int'));

		$object->date_start  = $date_start;
		$object->date_end    = $date_end;

		$object->prior_visit_bool = $prior_visit_bool;
		if ($prior_visit_bool) {
			$object->prior_visit_text = $prior_visit_text;
			$object->prior_visit_date = $prior_visit_date;
		}

		$object->cssct_intervention = $cssct_intervention;

		$object->fk_user_creat = $user->id ? $user->id : 1;

		// Check parameters
		if ($maitre_oeuvre_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MaitreOeuvre')), null, 'errors');
			$error++;
		} else {
			$usertmp->fetch($maitre_oeuvre_id);
			if (!dol_strlen($usertmp->email)) {
				setEventMessages($langs->trans('ErrorNoEmailForMaitreOeuvre', $langs->transnoentitiesnoconv('MaitreOeuvre')) . ' : ' . '<a target="_blank" href="'.dol_buildpath('/user/card.php?id='.$usertmp->id, 2).'">'.$usertmp->lastname . ' ' . $usertmp->firstname.'</a>', null, 'errors');
				$error++;
			}
		}

		if ($extsociety_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSociety')), null, 'errors');
			$error++;
		}

		if (is_array($extresponsible_id)) {
			if (empty(array_filter($extresponsible_id))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
				$error++;
			}
		} elseif (empty($extresponsible_id)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
			$error++;
		}

		if ($labour_inspector_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspectorSociety')), null, 'errors');
			$error++;
		}

		if (is_array($labour_inspector_contact_id)) {
			if (empty(array_filter($labour_inspector_contact_id))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
				$error++;
			}
		} elseif (empty($labour_inspector_contact_id)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $object->create($user, false);
			if ($result > 0) {
				$object->setInProgress($user, true);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'PP_EXT_SOCIETY', 'societe', array($extsociety_id), $conf->entity, 'preventionplan', $object->id, 1);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'PP_LABOUR_INSPECTOR', 'societe', array($labour_inspector_id), $conf->entity, 'preventionplan', $object->id, 1);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'PP_LABOUR_INSPECTOR_ASSIGNED', 'socpeople', array($labour_inspector_contact_id), $conf->entity, 'preventionplan', $object->id, 1);

				if ($maitre_oeuvre_id > 0) {
					$signatory->setSignatory($object->id, 'preventionplan', 'user', array($maitre_oeuvre_id), 'PP_MAITRE_OEUVRE');
				}

				if ($extresponsible_id > 0) {
					$signatory->setSignatory($object->id, 'preventionplan','socpeople', array($extresponsible_id), 'PP_EXT_SOCIETY_RESPONSIBLE');
				}

				// Creation prevention plan OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Creation prevention plan KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		} else {
			$action = 'create';
		}
	}

	// Action to update record
	if ($action == 'update' && $permissiontoadd) {
		// Get parameters
		$maitre_oeuvre_id            = GETPOST('maitre_oeuvre');
		$extsociety_id               = GETPOST('ext_society');
		$extresponsible_id           = GETPOST('ext_society_responsible');
		$extintervenant_ids          = GETPOST('ext_intervenants');
		$labour_inspector_id         = GETPOST('labour_inspector');
		$labour_inspector_contact_id = GETPOST('labour_inspector_contact') ? GETPOST('labour_inspector_contact') : 0;
		$label                       = GETPOST('label');
		$prior_visit_bool            = GETPOST('prior_visit_bool');
		$prior_visit_text            = GETPOST('prior_visit_text');
		$cssct_intervention          = GETPOST('cssct_intervention');

		// Initialize object preventionplan
		$now           = dol_now();
		$object->tms   = $now;
		$object->label = $label;

		$date_start = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));
		$date_end = dol_mktime(GETPOST('dateehour', 'int'), GETPOST('dateemin', 'int'), 0, GETPOST('dateemonth', 'int'), GETPOST('dateeday', 'int'), GETPOST('dateeyear', 'int'));
		$prior_visit_date = dol_mktime(GETPOST('dateihour', 'int'), GETPOST('dateimin', 'int'), 0, GETPOST('dateimonth', 'int'), GETPOST('dateiday', 'int'), GETPOST('dateiyear', 'int'));

		$object->date_start = $date_start;
		$object->date_end   = $date_end;

		$object->prior_visit_bool = $prior_visit_bool;
		if ($prior_visit_bool) {
			$object->prior_visit_text   = $prior_visit_text;
			$object->prior_visit_date   = $prior_visit_date;
		}
		$object->cssct_intervention = $cssct_intervention;

		$object->fk_user_creat = $user->id ? $user->id : 1;

		// Check parameters
		if ($maitre_oeuvre_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MaitreOeuvre')), null, 'errors');
			$error++;
		}   else {
			$usertmp->fetch($maitre_oeuvre_id);
			if (!dol_strlen($usertmp->email)) {
				setEventMessages($langs->trans('ErrorNoEmailForMaitreOeuvre', $langs->transnoentitiesnoconv('MaitreOeuvre')) . ' : ' . '<a target="_blank" href="'.dol_buildpath('/user/card.php?id='.$usertmp->id, 2).'">'.$usertmp->lastname . ' ' . $usertmp->firstname.'</a>', null, 'errors');
				$error++;
			}
		}

		if ($extsociety_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSociety')), null, 'errors');
			$error++;
		}

		if (is_array($extresponsible_id)) {
			if (empty(array_filter($extresponsible_id))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
				$error++;
			}
		} elseif (empty($extresponsible_id)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
			$error++;
		}

		if ($labour_inspector_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspectorSociety')), null, 'errors');
			$error++;
		}

		if (is_array($labour_inspector_contact_id)) {
			if (empty(array_filter($labour_inspector_contact_id))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
				$error++;
			}
		} elseif (empty($labour_inspector_contact_id)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $object->update($user, false);
			if ($result > 0) {
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'PP_EXT_SOCIETY', 'societe', array($extsociety_id), $conf->entity, 'preventionplan', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'PP_LABOUR_INSPECTOR', 'societe', array($labour_inspector_id), $conf->entity, 'preventionplan', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'PP_LABOUR_INSPECTOR_ASSIGNED', 'socpeople', array($labour_inspector_contact_id), $conf->entity, 'preventionplan', $object->id, 0);

				$signatory->setSignatory($object->id, 'preventionplan', 'user', array($maitre_oeuvre_id), 'PP_MAITRE_OEUVRE');
				$signatory->setSignatory($object->id, 'preventionplan', 'socpeople', array($extresponsible_id), 'PP_EXT_SOCIETY_RESPONSIBLE');

				// Update prevention plan OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				// Update prevention plan KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}  else {
			$action = 'edit';
		}
	}

	// Action to add line
	if ($action == 'addLine' && $permissiontoadd) {
		// Get parameters
		$actions_description = GETPOST('actionsdescription');
		$prevention_method   = GETPOST('preventionmethod');
		$location            = GETPOST('fk_element');
		$risk_category_id    = GETPOST('risk_category_id');
		$parent_id           = GETPOST('parent_id');

		// Initialize object preventionplan line
		$objectline->date_creation      = $object->db->idate($now);
		$objectline->ref                = $refPreventionPlanDetMod->getNextValue($objectline);
		$objectline->entity             = $conf->entity;
		$objectline->description        = $actions_description;
		$objectline->category           = $risk_category_id;
		$objectline->prevention_method  = $prevention_method;
		$objectline->fk_preventionplan  = $parent_id;
		$objectline->fk_element         = $location;

		// Check parameters
		if ($location < 1) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Location')), null, 'errors');
			$error++;
		}

		if ($risk_category_id < 0 || $risk_category_id == 'undefined') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('INRSRisk')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $objectline->insert($user, false);
			if ($result > 0) {
				// Creation prevention plan line OK
				setEventMessages($langs->trans('AddPreventionPlanLine').' '.$objectline->ref.' '.$langs->trans('PreventionPlanMessage'), array());
				$objectline->call_trigger('PREVENTIONPLANDET_CREATE', $user);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Creation prevention plan line KO
				if (!empty($objectline->errors)) setEventMessages(null, $objectline->errors, 'errors');
				else  setEventMessages($objectline->error, null, 'errors');
			}
		}
	}

	// Action to update line
	if ($action == 'updateLine' && $permissiontoadd) {
		// Get parameters
		$actions_description = GETPOST('actionsdescription');
		$prevention_method   = GETPOST('preventionmethod');
		$location            = GETPOST('fk_element');
		$risk_category_id    = GETPOST('risk_category_id');
		$parent_id           = GETPOST('parent_id');

		$objectline->fetch($lineid);

		// Initialize object prevention plan line
		$objectline->description        = $actions_description;
		$objectline->category           = $risk_category_id;
		$objectline->prevention_method  = $prevention_method;
		$objectline->fk_preventionplan  = $parent_id;
		$objectline->fk_element         = $location;

		// Check parameters
		if ($parent_id < 1) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Location')), null, 'errors');
			$error++;
		}
		if ($risk_category_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('INRSRisk')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $objectline->update($user, false);
			if ($result > 0) {
				// Update prevention plan line OK
				setEventMessages($langs->trans('UpdatePreventionPlanLine').' '.$objectline->ref.' '.$langs->trans('PreventionPlanMessage'), array());
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				// Update prevention plan line KO
				if (!empty($objectline->errors)) setEventMessages(null, $objectline->errors, 'errors');
				else  setEventMessages($objectline->error, null, 'errors');
			}
		}
	}

	// Action to delete line
	if ($action == 'deleteline' && $permissiontodelete) {
		$objectline->fetch($lineid);
		$result = $objectline->delete($user, false);
		if ($result > 0) {
			// Delete prevention plan line OK
			setEventMessages($langs->trans('DeletePreventionPlanLine').' '.$objectline->ref.' '.$langs->trans('PreventionPlanMessage'), array());
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Delete prevention plan line KO
			if (!empty($objectline->errors)) setEventMessages(null, $objectline->errors, 'errors');
			else  setEventMessages($objectline->error, null, 'errors');
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

		$model = GETPOST('model', 'alpha');

		$moreparams['object'] = $object;
		$moreparams['user']   = $user;

		$result = $preventionplandocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			setEventMessages($langs->trans("FileGenerated") . ' - ' . $preventionplandocument->last_main_doc, null);

			$signatories = $signatory->fetchSignatory("",$object->id);

			if (!empty ($signatories) && $signatories > 0) {
				foreach ($signatories as $arrayRole) {
					foreach ($arrayRole as $signatory) {
						$signatory->signature = $langs->trans("FileGenerated");
						$signatory->update($user, false);
					}
				}
			}

			$urltoredirect = $_SERVER['REQUEST_URI'];
			$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
			$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

			header('Location: ' . $urltoredirect . '#builddoc');
			exit;
		}
	}

	// Delete file in doc form
	if ($action == 'remove_file' && $permissiontodelete) {
		if (!empty($upload_dir)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

			$langs->load("other");
			$filetodelete = GETPOST('file', 'alpha');
			$file = $upload_dir.'/'.$filetodelete;
			$ret = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret) setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
			else setEventMessages($langs->trans("ErrorFailToDeleteFile", $filetodelete), null, 'errors');

			// Make a redirect to avoid to keep the remove_file into the url that create side effects
			$urltoredirect = $_SERVER['REQUEST_URI'];
			$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
			$urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

			header('Location: '.$urltoredirect);
			exit;
		}
		else {
			setEventMessages('BugFoundVarUploaddirnotDefined', null, 'errors');
		}
	}

	// Action to set status STATUS_INPROGRESS
	if ($action == 'confirm_setInProgress') {
		$object->fetch($id);
		if (!$error) {
			$result = $object->setInProgress($user, false);
			if ($result > 0) {
				// Set In progress OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Set In progress KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_PENDING_SIGNATURE
	if ($action == 'confirm_setPendingSignature') {
		$object->fetch($id);
		if (!$error) {
			$result = $object->setPendingSignature($user, false);
			if ($result > 0) {
				// Set pending signature OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else
			{
				// Set pending signature KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_LOCKED
	if ($action == 'confirm_setLocked') {
		$object->fetch($id);
		if (!$error) {
			$result = $object->setLocked($user, false);
			if ($result > 0) {
				// Set locked OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Set locked KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_ARCHIVED
	if ($action == 'setArchived') {
		$object->fetch($id);
		if (!$error) {
			$result = $object->setArchived($user, false);
			if ($result > 0) {
				// Set Archived OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Set Archived KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes') {
		$options['preventionplan_risk'] = GETPOST('clone_preventionplan_risk');
		$options['attendants'] = GETPOST('clone_attendants');
		$options['schedule'] = GETPOST('clone_schedule');

		if (1 == 0 && !GETPOST('clone_preventionplan_risk') && !GETPOST('clone_attendants') && !GETPOST('clone_schedule')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				$result = $object->createFromClone($user, $object->id, $options);
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit();
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
		}
	}

	// Actions to send emails
	$triggersendname = 'PREVENTIONPLAN_SENTBYMAIL';
	$mode = 'emailfromthirdparty';
	$trackid = 'thi'.$object->id;
	$labour_inspector = $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR', $object);
	$labour_inspector_id = $labour_inspector->id;
	$thirdparty->fetch($labour_inspector_id);
	$object->thirdparty = $thirdparty;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$form = new Form($db);

$title         = $langs->trans("PreventionPlan");
$title_create  = $langs->trans("NewPreventionPlan");
$title_edit    = $langs->trans("ModifyPreventionPlan");
$object->picto = 'preventionplandocument@digiriskdolibarr';

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($title_create, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate preventionplan-table">';

	//Ref -- Ref
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Ref").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="'.$refPreventionPlanMod->getNextValue($object).'">';
	print $refPreventionPlanMod->getNextValue($object);
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="minwidth400">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.GETPOST('label').'">';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr><td class="minwidth400"><label for="date_debut">'.$langs->trans("StartDate").'</label></td><td>';
	print $form->selectDate(dol_now('tzuser'), 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr><td class="minwidth400"><label for="date_fin">'.$langs->trans("EndDate").'</label></td><td>';
	print $form->selectDate(dol_time_plus_duree(dol_now('tzuser'),1,'y'), 'datee', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Maitre d'oeuvre
	if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE < 0 || empty($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE)) {
		$userlist = $form->select_dolusers((!empty(GETPOST('maitre_oeuvre')) ? GETPOST('maitre_oeuvre') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('MaitreOeuvre', 'MaitreOeuvre_id', '', $object, 0) . '</td>';
		print '<td>';
		print $form->selectarray('maitre_oeuvre', $userlist, (!empty(GETPOST('maitre_oeuvre')) ? GETPOST('maitre_oeuvre') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
		print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';
	} else {
		$usertmp->fetch($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('MaitreOeuvre', 'MaitreOeuvre_id', '', $object, 0) . '</td>';
		print '<td>'.$usertmp->getNomUrl(1).'</td>';
		print '<input type="hidden" name="maitre_oeuvre" value="'.$conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE.'">';
		print '</td></tr>';
	}

	//External society -- Société extérieure
	print '<tr><td class="fieldrequired minwidth400">'.img_picto('','building').' '.$langs->trans("ExtSociety").'</td><td>';
	$events = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_society_responsible', 'params' => array('add-customer-contact' => 'disabled'));
	print $form->select_company(GETPOST('ext_society'), 'ext_society', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	print '</td></tr>';

	$ext_society_responsible_id = GETPOST('ext_society_responsible');
	$contacts = fetchAllSocPeople('',  '',  0,  0, array('customsql' => "s.rowid = $ext_society_responsible_id AND c.email IS NULL OR c.email = ''" ));
	$contacts_no_email = array();
	if (is_array($contacts) && !empty ($contacts) && $contacts > 0) {
		foreach ($contacts as $element) {
			$contacts_no_email[$element->id] = $element->id;
		}
	}

	//External responsible -- Responsable de la société extérieure
	print '<tr><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('','address').' '.$langs->trans("ExtSocietyResponsible");
	print $form->textwithpicto($htmltext, $langs->trans('ContactNoEmail'));
	print '</td><td>';
	print digirisk_selectcontacts((empty(GETPOST('ext_society', 'int')) ? -1 : GETPOST('ext_society', 'int')), GETPOST('ext_society_responsible'), 'ext_society_responsible', 1, $contacts_no_email, '', 0, 'minwidth300', false, 0, array(), false, '', 'ext_society_responsible');
	print '</td></tr>';

	// CSSCT Intervention
	print '<tr><td class="minwidth300">'.$langs->trans("CSSCTIntervention").'</td><td>';
	print '<input type="checkbox" id="cssct_intervention" name="cssct_intervention" '.(GETPOST('cssct_intervention') ? ' checked=""' : '').'>';
	print '</td></tr>';

	//Prior Visit -- Inspection commune préalable
	print '<tr><td class="minwidth400">'.$langs->trans("PriorVisit").'</td><td>';
	print '<input type="checkbox" id="prior_visit_bool" name="prior_visit_bool"'.(GETPOST('prior_visit_bool') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans('CSEMustBeAlerted3DaysBeforeVisit'));
	print '</td></tr>';

	//Prior Visit Date -- Date de l'inspection commune préalable
	print '<tr class="prior_visit_date_field hidden" '. (GETPOST('prior_visit_bool') ?  '' : 'style="display:none"') .'><td class="minwidth400"><label for="prior_visit_date">'.$langs->trans("PriorVisitDate").'</label></td><td>';
	print $form->selectDate(dol_now('tzuser'), 'datei', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Prior Visit Texte -- Note de l'inspection
	print '<tr  class="prior_visit_text_field hidden"'. (GETPOST('prior_visit_bool') ?  '' : 'style="display:none"') .'><td class="minwidth400"><label for="prior_visit_text">'.$langs->trans("PriorVisitText").'</label></td><td>';
	$doleditor = new DolEditor('prior_visit_text', GETPOST('prior_visit_text'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//Labour inspector Society -- Entreprise Inspecteur du travail
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('','building').' '.$langs->trans("LabourInspectorSociety");
	print '</td>';
	print '<td>';
	$events = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labour_inspector_contact', 'params' => array('add-customer-contact' => 'disabled'));
	print $form->select_company((GETPOST('labour_inspector') ? GETPOST('labour_inspector') : ($allLinks['LabourInspectorSociety']->id[0] ?: 0)), 'labour_inspector', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	print '<a href="'.DOL_URL_ROOT.'/custom/digiriskdolibarr/admin/securityconf.php'.'" target="_blank">'.$langs->trans("ConfigureLabourInspector").'</a>';
	print '</td></tr>';

	$labour_inspector_contact_id = (GETPOST('labour_inspector_contact') ? GETPOST('labour_inspector_contact') : ($allLinks['LabourInspectorContact']->id[0] ?: -1));
	$contacts = fetchAllSocPeople('',  '',  0,  0, array('customsql' => "s.rowid = $labour_inspector_contact_id AND c.email IS NULL OR c.email = ''" ));
	$contacts_no_email_labour_inspector = array();
	if (is_array($contacts) && !empty ($contacts) && $contacts > 0) {
		foreach ($contacts as $element) {
			$contacts_no_email_labour_inspector[$element->id] = $element->id;
		}
	}

	if (!empty($allLinks['LabourInspectorContact'])) {
		$contact->fetch($allLinks['LabourInspectorContact']->id[0]);
	}

	//Labour inspector -- Inspecteur du travail
	print '<tr><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('','address').' '.$langs->trans("LabourInspector");
	print $form->textwithpicto($htmltext, $langs->trans('ContactNoEmail'));
	print '</td><td>';
	print digirisk_selectcontacts((GETPOST('labour_inspector') ? GETPOST('labour_inspector') : ($allLinks['LabourInspectorSociety']->id[0] ?: -1)), dol_strlen($contact->email) ? $labour_inspector_contact_id : -1, 'labour_inspector_contact', 1, $contacts_no_email_labour_inspector, '', 0, 'minwidth300', false, 0, array(), false, '', 'labour_inspector_contact');
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" id ="actionButtonCreate" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelCreate" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($title_edit, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	$object_resources = $digiriskresources->fetchResourcesFromObject('', $object);
	$object_signatories = $signatory->fetchSignatory('',$object->id);

	print '<table class="border centpercent tableforfieldedit  preventionplan-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Ref").'</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="minwidth400">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.$object->label.'">';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr class="oddeven"><td class="minwidth400"><label for="date_debut">'.$langs->trans("StartDate").'</label></td><td>';
	print $form->selectDate($object->date_start,'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr class="oddeven"><td class="minwidth400"><label for="date_fin">'.$langs->trans("EndDate").'</label></td><td>';
	print $form->selectDate($object->date_end, 'datee', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Maitre d'oeuvre
	$maitre_oeuvre = is_array($object_signatories['PP_MAITRE_OEUVRE']) ? array_shift($object_signatories['PP_MAITRE_OEUVRE'])->element_id : '';
	$userlist = $form->select_dolusers($maitre_oeuvre, '', 1, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">'.img_picto('','user').' '.$form->editfieldkey('MaitreOeuvre', 'MaitreOeuvre_id', '', $object, 0).'</td>';
	print '<td>';
	print $form->selectarray('maitre_oeuvre', $userlist,$maitre_oeuvre, 1, null, null, null, "40%", 0, 0, 0, 'minwidth300',1);
	print ' <a href="'.DOL_URL_ROOT.'/user/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddUser").'"></span></a>';
	print '</td></tr>';

	//External society -- Société extérieure
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('','building').' '.$langs->trans("ExtSociety");
	print '</td>';
	print '<td>';
	$events = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_society_responsible', 'params' => array('add-customer-contact' => 'disabled'));
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'ext_society', '', 1, 1, 0, $events, 0, 'minwidth300');
	} else {
		$ext_society_id = is_array($object_resources['PP_EXT_SOCIETY']) ? array_shift($object_resources['PP_EXT_SOCIETY'])->id : '';
		print $form->select_company($ext_society_id, 'ext_society', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	}
	print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	print '</td></tr>';

	$ext_society_responsible_id = is_array($object_signatories['PP_EXT_SOCIETY_RESPONSIBLE']) ? array_shift($object_signatories['PP_EXT_SOCIETY_RESPONSIBLE'])->element_id : GETPOST('ext_society_responsible');
	$contacts = fetchAllSocPeople('',  '',  0,  0, array('customsql' => "s.rowid = $ext_society_responsible_id AND c.email IS NULL OR c.email = ''" ));
	$contacts_no_email = array();
	if (is_array($contacts) && !empty ($contacts) && $contacts > 0) {
		foreach ($contacts as $element) {
			$contacts_no_email[$element->id] = $element->id;
		}
	}

	if ($ext_society_responsible_id > 0) {
		$contact->fetch($ext_society_responsible_id);
	}

	//External responsible -- Responsable de la société extérieure
	$ext_society = $digiriskresources->fetchResourcesFromObject('PP_EXT_SOCIETY', $object);
	print '<tr class="oddeven"><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('','address').' '.$langs->trans("ExtSocietyResponsible");
	print $form->textwithpicto($htmltext, $langs->trans('ContactNoEmail'));
	print '</td><td>';
	print digirisk_selectcontacts($ext_society->id, dol_strlen($contact->email) ? $ext_society_responsible_id : -1, 'ext_society_responsible', 0, $contacts_no_email, '', 0, 'minwidth300', false, 0, array(), false, '', 'ext_society_responsible');
	print '</td></tr>';

	// CSSCT Intervention
	print '<tr><td class="minwidth400">';
	print $langs->trans("CSSCTIntervention");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="cssct_intervention" name="cssct_intervention"'.($object->cssct_intervention ? ' checked=""' : '').'"> ';
	$htmltext = $langs->trans("CSSCTInterventionText");
	print $form->textwithpicto('', $htmltext);
	print '<br>';
	print '</td></tr>';

	//Prior Visit -- Inspection commune préalable
	print '<tr class="oddeven"><td class="minwidth400">';
	print $langs->trans("PriorVisit");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="prior_visit_bool" name="prior_visit_bool"'.($object->prior_visit_bool? ' checked=""' : '').'"> ';
	print '</td></tr>';

	//Prior Visit Date -- Date de l'inspection commune préalable
	print '<tr class="'.($object->prior_visit_bool ?  ' prior_visit_date_field' : ' prior_visit_date_field hidden' ).'" style="'.($object->prior_visit_bool ? ' ' : ' display:none').'"><td class="minwidth400"><label for="prior_visit_date">'.$langs->trans("PriorVisitDate").'</label></td><td>';
	print $form->selectDate($object->date_start,'datei', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Prior Visit Text -- Note de l'inspection
	print '<tr class="'.($object->prior_visit_bool ?  ' prior_visit_date_field' : ' prior_visit_date_field hidden' ).'" style="'.($object->prior_visit_bool ? ' ' : ' display:none').'"><td class="minwidth400"><label for="prior_visit_text">'.$langs->trans("PriorVisitText").'</label></td><td>';
	$doleditor = new DolEditor('prior_visit_text', $object->prior_visit_text, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	if (is_array($object_resources['PP_LABOUR_INSPECTOR']) && $object_resources['PP_LABOUR_INSPECTOR'] > 0) {
		$labour_inspector_society  = array_shift($object_resources['PP_LABOUR_INSPECTOR']);
	}
	if (is_array($object_resources['PP_LABOUR_INSPECTOR_ASSIGNED']) && $object_resources['PP_LABOUR_INSPECTOR_ASSIGNED'] > 0) {
		$labour_inspector_assigned = array_shift($object_resources['PP_LABOUR_INSPECTOR_ASSIGNED']);
	}
	//Labour inspector Society -- Entreprise Inspecteur du travail
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('','building').' '.$langs->trans("LabourInspectorSociety");
	print '</td>';
	print '<td>';
	$events = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labour_inspector_contact', 'params' => array('add-customer-contact' => 'disabled'));
	print $form->select_company($labour_inspector_society->id, 'labour_inspector', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	print '<a href="'.DOL_URL_ROOT.'/custom/digiriskdolibarr/admin/securityconf.php'.'" target="_blank">'.$langs->trans("ConfigureLabourInspector").'</a>';
	print '</td></tr>';

	$labour_inspector_contact = !empty($digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR_ASSIGNED', $object)) ? $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR_ASSIGNED', $object) : GETPOST('labour_inspector_contact');
	$contacts = fetchAllSocPeople('',  '',  0,  0, array('customsql' => "s.rowid = $labour_inspector_contact->id AND c.email IS NULL OR c.email = ''" ));
	$contacts_no_email_labour_inspector = array();
	if (is_array($contacts) && !empty ($contacts) && $contacts > 0) {
		foreach ($contacts as $element) {
			$contacts_no_email_labour_inspector[$element->id] = $element->id;
		}
	}

	if ($labour_inspector_contact->id > 0) {
		$contact->fetch($labour_inspector_contact->id);
	}

	//Labour inspector -- Inspecteur du travail
	$labour_inspector_society = $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR', $object);
	print '<tr><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('','address').' '.$langs->trans("LabourInspector");
	print $form->textwithpicto($htmltext, $langs->trans('ContactNoEmail'));
	print '</td><td>';
	print digirisk_selectcontacts($labour_inspector_society->id, dol_strlen($contact->email) ? $labour_inspector_contact->id : -1, 'labour_inspector_contact', 0, $contacts_no_email_labour_inspector, '', 0, 'minwidth300', false, 0, array(), false, '', 'labour_inspector_contact');
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

$formconfirm = '';

// SetLocked confirmation
if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('LockPreventionPlan'), $langs->trans('ConfirmLockPreventionPlan', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
}

// setPendingSignature confirmation
if (($action == 'setPendingSignature' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidatePreventionPlan'), $langs->trans('ConfirmValidatePreventionPlan', $object->ref), 'confirm_setPendingSignature', '', 'yes', 'actionButtonPendingSignature', 350, 600);
}

// setInProgress confirmation
if (($action == 'setInProgress' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpenPreventionPlan'), $langs->trans('ConfirmReOpenPreventionPlan', $object->ref), 'confirm_setInProgress', '', 'yes', 'actionButtonInProgress', 350, 600);
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	// Define confirmation messages
	$formquestionclone = array(
		'text' => $langs->trans("ConfirmClone"),
		array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("NewRefForClonePreventionPlan"), 'value' => empty($tmpcode) ? $langs->trans("CopyOf").' '.$object->ref : $tmpcode, 'size'=>24),
		array('type' => 'checkbox', 'name' => 'clone_preventionplan_risk', 'label' => $langs->trans("ClonePreventionPlanRisk"), 'value' => 1),
		array('type' => 'checkbox', 'name' => 'clone_attendants', 'label' => $langs->trans("CloneAttendantsPreventionPlan"), 'value' => 1),
		array('type' => 'checkbox', 'name' => 'clone_schedule', 'label' => $langs->trans("CloneSchedulePreventionPlan"), 'value' => 1),
	);

	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmClonePreventionPlan', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'actionButtonClone', 350, 600);
}

// Call Hook formConfirm
$parameters = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

// Print form confirm
print $formconfirm;

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {
	// Object card
	// ------------------------------------------------------------

	$object->fetch_optionals();

	$head = preventionplanPrepareHead($object);
	print dol_get_fiche_head($head, 'preventionplanCard', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	$width = 80; $cssclass = 'photoref';
	dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';
	$morehtmlref .= '<div class="refidno">';
	// External Society -- Société extérieure
	$ext_society = $digiriskresources->fetchResourcesFromObject('PP_EXT_SOCIETY', $object);
	$morehtmlref .= $langs->trans('ExtSociety').' : '.$ext_society->getNomUrl(1);
	// Project
	$project->fetch($object->fk_project);
	$morehtmlref .= '<br>'.$langs->trans('Project').' : '.getNomUrlProject($project, 1, 'blank');
	$morehtmlref .= '</div>';




	//$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft, $object->getLibStatut(5));

	print '<div class="div-table-responsive">';
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	//Unset for order
	unset($object->fields['date_start']);
	unset($object->fields['date_end']);
	unset($object->fields['prior_visit_bool']);
	unset($object->fields['prior_visit_date']);
	unset($object->fields['prior_visit_text']);
	unset($object->fields['label']);
	unset($object->fields['fk_project']);

	//Label -- Libellé
	print '<tr><td class="titlefield">';
	print $langs->trans("Label");
	print '</td>';
	print '<td>';
	print $object->label;
	print '</td></tr>';

	//StartDate -- Date de début
	print '<tr><td class="titlefield">';
	print $langs->trans("StartDate");
	print '</td>';
	print '<td>';
	print dol_print_date($object->date_start, 'dayhoursec');
	print '</td></tr>';

	//EndDate -- Date de fin
	print '<tr><td class="titlefield">';
	print $langs->trans("EndDate");
	print '</td>';
	print '<td>';
	print dol_print_date($object->date_end, 'dayhoursec');
	print '</td></tr>';

	//Prior Visit -- Inspection commune préalable
	print '<tr><td class="titlefield">';
	print $langs->trans("PriorVisit");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="prior_visit_bool" name="prior_visit_bool"'.($object->prior_visit_bool? ' checked=""' : '').'" disabled> ';
	print '</td></tr>';

	if ($object->prior_visit_bool) {
		print '<tr><td class="titlefield">';
		print $langs->trans("PriorVisitDate");
		print '</td>';
		print '<td>';
		print dol_print_date($object->prior_visit_date, 'dayhoursec');
		print '</td></tr>';

		print '<tr><td class="titlefield">';
		print $langs->trans("PriorVisitText");
		print '</td>';
		print '<td>';
		print $object->prior_visit_text;
		print '</td></tr>';
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	//Labour inspector Society -- Entreprise Inspecteur du travail
	print '<tr><td class="titlefield">';
	print $langs->trans("LabourInspectorSociety");
	print '</td>';
	print '<td>';
	$labour_inspector = $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR', $object);
	if ($labour_inspector > 0) {
		print $labour_inspector->getNomUrl(1);
	}
	print '</td></tr>';

	//Labour inspector -- Inspecteur du travail
	print '<tr><td class="titlefield">';
	print $langs->trans("LabourInspector");
	print '</td>';
	print '<td>';
	$labour_inspector_contact = $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR_ASSIGNED', $object);
	if ($labour_inspector_contact > 0) {
		print $labour_inspector_contact->getNomUrl(1);
	}
	print '</td></tr>';

	//Attendants -- Participants
	print '<tr><td class="titlefield">';
	print $langs->trans("Attendants");
	print '</td>';
	print '<td>';
	$attendants = count($signatory->fetchSignatory('PP_MAITRE_OEUVRE', $object->id, 'preventionplan'));
	$attendants += count($signatory->fetchSignatory('PP_EXT_SOCIETY_RESPONSIBLE', $object->id, 'preventionplan'));
	$attendants += count($signatory->fetchSignatory('PP_EXT_SOCIETY_INTERVENANTS', $object->id, 'preventionplan'));
	$url = dol_buildpath('/custom/digiriskdolibarr/view/preventionplan/preventionplan_attendants.php?id='.$object->id, 3);
	print '<a href="'.$url.'">'.$attendants.'</a>';
	print '<a class="'. ($object->status == 1 ? 'butAction' : 'butActionRefused classfortooltip').'" id="actionButtonAddAttendants" title="'.dol_escape_htmltag($langs->trans("PreventionPlanMustBeInProgress")).'" href="'.$url.'">'.$langs->trans('AddAttendants').'</a>';
	print '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

	print dol_get_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >';
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook) && $permissiontoadd) {
			print '<a class="'. ($object->status == 1 ? 'butAction' : 'butActionRefused classfortooltip').'" id="actionButtonEdit" title="'.($object->status == 1 ? '' : dol_escape_htmltag($langs->trans("PreventionPlanMustBeInProgress"))).'" href="'.($object->status == 1 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit') : '#').'">' . $langs->trans("Modify") . '</a>';
			print '<span class="'. ($object->status == 1 ? 'butAction' : 'butActionRefused classfortooltip').'" id="'.($object->status == 1 ? 'actionButtonPendingSignature' : '').'" title="'.($object->status == 1 ? '' : dol_escape_htmltag($langs->trans("PreventionPlanMustBeInProgressToValidate"))).'" href="'.($object->status == 1 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=setPendingSignature') : '#').'">' . $langs->trans("Validate") . '</span>';
			print '<span class="'. ($object->status == 2 ? 'butAction' : 'butActionRefused classfortooltip').'" id="'.($object->status == 2 ? 'actionButtonInProgress' : '').'" title="'.($object->status == 2 ? '' : dol_escape_htmltag($langs->trans("PreventionPlanMustBeValidated"))).'" href="'.($object->status == 2 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=setInProgress') :'#').'">' . $langs->trans("ReOpenDigi") . '</span>';
			print '<a class="'. (($object->status == 2 && !$signatory->checkSignatoriesSignatures($object->id, 'preventionplan')) ? 'butAction' : 'butActionRefused classfortooltip').'" id="actionButtonSign" title="'.(($object->status == 2 && !$signatory->checkSignatoriesSignatures($object->id, 'preventionplan')) ? '' : dol_escape_htmltag($langs->trans("PreventionPlanMustBeValidatedToSign"))).'" href="'.(($object->status == 2 && !$signatory->checkSignatoriesSignatures($object->id, 'preventionplan')) ? $url : '#').'">'. $langs->trans("Sign").'</a>';
			print '<span class="'. (($object->status == 2 && $signatory->checkSignatoriesSignatures($object->id, 'preventionplan')) ? 'butAction' : 'butActionRefused classfortooltip').'" id="'.(($object->status == 2 && $signatory->checkSignatoriesSignatures($object->id, 'preventionplan')) ? 'actionButtonLock' :'').'" title="'.(($object->status == 2 && $signatory->checkSignatoriesSignatures($object->id, 'preventionplan')) ? '' : dol_escape_htmltag($langs->trans("AllSignatoriesMustHaveSigned"))).'">' . $langs->trans("Lock") . '</span>';
			print '<a class="'. ($object->status == 3 ? 'butAction' : 'butActionRefused classfortooltip').'" id="actionButtonSign" title="'.dol_escape_htmltag($langs->trans("PreventionPlanMustBeLockedToSendEmail")).'" href="'.($object->status == 3 ? ($_SERVER['PHP_SELF'].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle&sendto='.$allLinks['LabourInspectorSociety']->id[0]) : '#').'">'.$langs->trans('SendMail').'</a>';
			print '<a class="'. ($object->status == 3 ? 'butAction' : 'butActionRefused classfortooltip').'" id="actionButtonClose" title="'.($object->status == 3 ? '' : dol_escape_htmltag($langs->trans("PreventionPlanMustBeLocked"))).'" href="'.($object->status == 3 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=setArchived') : '#').'">' . $langs->trans("Close") . '</a>';
			print '<span class="butAction" id="actionButtonClone" title="" href="'.$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=clone'.'">' . $langs->trans("ToClone") . '</span>';

			$langs->load("mails");
			if ($object->date_end == dol_now()){
				$object->setArchived($user, false);
			}
		}
		print '</div>';

		// PREVENTIONPLAN LINES
		print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
		print load_fiche_titre($langs->trans("PreventionPlanRiskList"), '', '');
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		global $forceall, $forcetoshowtitlelines;

		if (empty($forceall)) $forceall = 0;

		// Define colspan for the button 'Add'
		$colspan = 3;

		// Lines
		$objectlines = $objectline->fetchAll(GETPOST('id'));

		print '<tr class="liste_titre">';
		print '<td><span>' . $langs->trans('Ref.') . '</span></td>';
		print '<td>' . $langs->trans('Location') . '</td>';
		print '<td>' . $form->textwithpicto($langs->trans('ActionsDescription'), $langs->trans("ActionsDescriptionTooltip")) . '</td>';
		print '<td class="center">' . $form->textwithpicto($langs->trans('INRSRisk'), $langs->trans('INRSRiskTooltip')) . '</td>';
		print '<td>' . $form->textwithpicto($langs->trans('PreventionMethod'), $langs->trans('PreventionMethodTooltip')) . '</td>';
		print '<td class="center" colspan="' . $colspan . '">'.$langs->trans('ActionsPreventionPlanRisk').'</td>';
		print '</tr>';

		if (!empty($objectlines) && $objectlines > 0) {
			print '<tr>';
			foreach ($objectlines as $key => $item) {
				if ($action == 'editline' && $lineid == $key) {

					print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
					print '<input type="hidden" name="token" value="' . newToken() . '">';
					print '<input type="hidden" name="action" value="updateLine">';
					print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
					print '<input type="hidden" name="lineid" value="' . $item->id . '">';
					print '<input type="hidden" name="parent_id" value="' . $object->id . '">';

					print '<tr>';
					print '<td>';
					print $item->ref;
					print '</td>';

					print '<td>';
					print $digiriskelement->select_digiriskelement_list($item->fk_element, 'fk_element', '', '', 0, 0, array(), '', 0, 0, 'minwidth100', GETPOST('id'), false, 1);
					print '</td>';

					$coldisplay++;
					print '<td>';
					print '<textarea name="actionsdescription" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . $item->description . '</textarea>' . "\n";
					print '</td>';

					$coldisplay++;
					print '<td class="center">'; ?>
					<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
						<div class="dropdown-toggle dropdown-add-button button-cotation">
							<input class="input-hidden-danger" type="hidden" name="risk_category_id"
								   value="<?php echo $item->category ?>"/>
							<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
								 aria-label="<?php echo $risk->get_danger_category_name($item) ?>">
								<img class="danger-category-pic hover"
									 src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($item) . '.png'; ?>"/>
							</div>
						</div>

						<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
							<?php
							$dangerCategories = $risk->get_danger_categories();
							if (!empty($dangerCategories)) :
								foreach ($dangerCategories as $dangerCategory) : ?>
									<li class="item dropdown-item wpeo-tooltip-event"
										data-is-preset="<?php echo ''; ?>"
										data-id="<?php echo $dangerCategory['position'] ?>"
										aria-label="<?php echo $dangerCategory['name'] ?>">
										<img
											src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png' ?>"
											class="attachment-thumbail size-thumbnail photo photowithmargin" alt="">
									</li>
								<?php endforeach;
							endif; ?>
						</ul>
					</div>
					<?php
					print '</td>';

					$coldisplay++;
					print '<td>';
					print '<textarea name="preventionmethod" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . $item->prevention_method . '</textarea>' . "\n";
					print '</td>';

					$coldisplay += $colspan;
					print '<td class="center" colspan="' . $colspan . '">';
					print '<input type="submit" class="button" value="' . $langs->trans('Save') . '" name="updateLine" id="updateLine">';
					print ' &nbsp; <input type="submit" id ="cancelLine" class="button" name="cancelLine" value="'.$langs->trans("Cancel").'">';
					print '</td>';
					print '</tr>';

					if (is_object($objectline)) {
						print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
					}
					print '</form>';
				} else {
					print '<tr>';
					print '<td>';
					print $item->ref;
					print '</td>';

					print '<td>';
					$digiriskelement->fetch($item->fk_element);
					print $digiriskelement->ref . " - " . $digiriskelement->label;
					print '</td>';

					$coldisplay++;
					print '<td>';
					print $item->description;
					print '</td>';

					$coldisplay++;
					print '<td class="center">'; ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
							 aria-label="<?php echo $risk->get_danger_category_name($item) ?>">
							<img class="danger-category-pic hover"
								 src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($item) . '.png'; ?>"
								 alt=""/>
						</div>
					</div>
					<?php
					print '</td>';

					$coldisplay++;
					print '<td>';
					print $item->prevention_method;
					print '</td>';

					$coldisplay += $colspan;

					//Actions buttons
					if ($object->status == 1) {
						print '<td class="center">';
						$coldisplay++;
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=editline&amp;lineid=' . $item->id . '" style="padding-right: 20px"><i class="fas fa-pencil-alt" style="color: #666"></i></a>';
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=deleteline&amp;lineid=' . $item->id . '">';
						print img_delete();
						print '</a>';
						print '</td>';
					} else {
						print '<td class="center">';
						print '-';
						print '</td>';
					}

					if (is_object($objectline)) {
						print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
					}
					print '</tr>';
				}
			}
			print '</tr>';
		}
		if ($object->status == 1 && $permissiontoadd) {
			print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="addLine">';
			print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
			print '<input type="hidden" name="parent_id" value="' . $object->id . '">';

			print '<tr>';
			print '<td>';
			print $refPreventionPlanDetMod->getNextValue($objectline);
			print '</td>';
			print '<td>';
			print $digiriskelement->select_digiriskelement_list('', 'fk_element', '', '1', 0, 0, array(), '', 0, 0, 'minwidth100', '', false, 1);
			print '</td>';

			$coldisplay++;
			print '<td>';
			print '<textarea name="actionsdescription" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n";
			print '</td>';

			$coldisplay++;
			print '<td class="center">'; ?>
			<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
				<input class="input-hidden-danger" type="hidden" name="risk_category_id" value="undefined"/>
				<div class="dropdown-toggle dropdown-add-button button-cotation">
					<span class="wpeo-button button-square-50 button-grey"><i
						class="fas fa-exclamation-triangle button-icon"></i><i
						class="fas fa-plus-circle button-add"></i></span>
					<img class="danger-category-pic wpeo-tooltip-event hidden" src="" aria-label=""/>
				</div>
				<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
					<?php
					$dangerCategories = $risk->get_danger_categories();
					if (!empty($dangerCategories)) :
						foreach ($dangerCategories as $dangerCategory) : ?>
							<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="<?php echo ''; ?>"
								data-id="<?php echo $dangerCategory['position'] ?>"
								aria-label="<?php echo $dangerCategory['name'] ?>">
								<img
									src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png' ?>"
									class="attachment-thumbail size-thumbnail photo photowithmargin" alt="">
							</li>
						<?php endforeach;
					endif; ?>
				</ul>
			</div>
			<?php
			print '</td>';

			$coldisplay++;
			print '<td>';
			print '<textarea name="preventionmethod" class="minwidth150" cols="50" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n";
			print '</td>';

			$coldisplay += $colspan;
			print '<td class="center" colspan="' . $colspan . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">';
			print '</td>';
			print '</tr>';

			if (is_object($objectline)) {
				print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
			}
			print '</form>';
		}
		print '</table>';
		print '</div>';
	}
	// Document Generation -- Génération des documents
	$includedocgeneration = 1;
	if ($includedocgeneration && $permissiontoadd) {
		print '<div class=""><div class="preventionplanDocument fichehalfleft">';

		$objref    = dol_sanitizeFileName($object->ref);
		$dir_files = $preventionplandocument->element . '/' . $objref;
		$filedir   = $upload_dir . '/' . $dir_files;
		$urlsource = $_SERVER["PHP_SELF"] . '?id='. $id;

		$modulepart   = 'digiriskdolibarr:PreventionPlanDocument';
		$defaultmodel = $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_DEFAULT_MODEL;
		$title        = $langs->trans('PreventionPlanDocument');

		print digiriskshowdocuments($modulepart, $dir_files, $filedir, $urlsource, $permissiontoadd, 0, $defaultmodel, 1, 0, 28, 0, '', $title, '', $langs->defaultlang, '', $preventionplandocument, 0, 'remove_file', $object->status == 3 && empty(dol_dir_list($filedir)), $langs->trans('PreventionPlanMustBeLocked') );
	}

	if ($permissiontoadd) {
		print '</div><div class="fichehalfright">';
	} else {
		print '</div><div class="">';
	}

	$MAXEVENT = 10;

	$morehtmlright = '<a href="' . dol_buildpath('/digiriskdolibarr/view/preventionplan/preventionplan_agenda.php', 1) . '?id=' . $object->id . '">';
	$morehtmlright .= $langs->trans("SeeAll");
	$morehtmlright .= '</a>';

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element . '@digiriskdolibarr','', 1, '', $MAXEVENT, '', $morehtmlright);


	print '</div></div></div>';

	// Presend form
	$labour_inspector = $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR', $object);
	$labour_inspector_id = $labour_inspector->id;
	$thirdparty->fetch($labour_inspector_id);
	$object->thirdparty = $thirdparty;

	$modelmail = 'preventionplan';
	$defaulttopic = 'Information';
	$diroutput = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element . 'document';
	$ref = $object->ref . '/';
	$trackid = 'thi'.$object->id;

	if ($action == 'presend')
	{
		$langs->load("mails");

		$titreform = 'SendMail';

		$object->fetch_projet();

		if (!in_array($object->element, array('societe', 'user', 'member')))
		{
			$ref = dol_sanitizeFileName($object->ref);
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$fileparams = dol_most_recent_file($diroutput.'/'.$ref, '');
			$file = $fileparams['fullname'];
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id']))
		{
			$newlang = $_REQUEST['lang_id'];
		}
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		{
			$newlang = $object->thirdparty->default_lang;
		}

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			// Load traductions files required by page
			$outputlangs->loadLangs(array('digiriskdolibarr'));
		}

		$topicmail = '';
		if (empty($object->ref_client)) {
			$topicmail = $outputlangs->trans($defaulttopic, '__REF__');
		} elseif (!empty($object->ref_client)) {
			$topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
		}

		// Build document if it not exists
		$forcebuilddoc = true;
		if ($forcebuilddoc)    // If there is no default value for supplier invoice, we do not generate file, even if modelpdf was set by a manual generation
		{
			if ((!$file || !is_readable($file)) && method_exists($object, 'generateDocument'))
			{
				$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) {
					dol_print_error($db, $object->error, $object->errors);
					exit();
				}
				$fileparams = dol_most_recent_file($diroutput.'/'.$ref, preg_quote($ref, '/').'[^\-]+');
				$file = $fileparams['fullname'];
			}
		}

		print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
		print '<div class="clearboth"></div>';
		print '<br>';
		print load_fiche_titre($langs->trans($titreform));

		print dol_get_fiche_head('');

		// Create form for email
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$maitre_oeuvre = $signatory->fetchSignatory('PP_MAITRE_OEUVRE', $object->id);
		$maitre_oeuvre = array_shift($maitre_oeuvre);

		$formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
		$formmail->fromtype      = (GETPOST('fromtype') ?GETPOST('fromtype') : (!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));
		$formmail->fromid        = $maitre_oeuvre->id;
		$formmail->trackid       = $trackid;
		$formmail->fromname      = $maitre_oeuvre->firstname . ' ' . $maitre_oeuvre->lastname;
		$formmail->frommail      = $maitre_oeuvre->email;
		$formmail->fromalsorobot = 1;
		$formmail->withfrom      = 1;

		// Fill list of recipient with email inside <>.
		$liste = array();

		$labour_inspector_contact = $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR_ASSIGNED', $object);

		if (!empty($object->socid) && $object->socid > 0 && !is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}
		if (is_object($object->thirdparty))
		{
			foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
				$liste[$key] = $value;
			}
		}

		if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
			$listeuser = array();
			$fuserdest = new User($db);

			$result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql'=>'t.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''), 'AND', true);
			if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
				foreach ($fuserdest->users as $uuserdest) {
					$listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
				}
			} elseif ($result < 0) {
				setEventMessages(null, $fuserdest->errors, 'errors');
			}
			if (count($listeuser) > 0) {
				$formmail->withtouser = $listeuser;
				$formmail->withtoccuser = $listeuser;
			}
		}


		$withto = array($labour_inspector_contact->id => $labour_inspector_contact->firstname . ' ' .$labour_inspector_contact->lastname." <".$labour_inspector_contact->email.">");

		$formmail->withto = $withto;
		$formmail->withtofree = (GETPOSTISSET('sendto') ? (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1') : '1');
		$formmail->withtocc = $liste;
		$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
		$formmail->withtopic = $topicmail;
		$formmail->withfile = 2;
		$formmail->withbody = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel = 1;

		//$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
		if (!isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude = null;

		// Make substitution in email content
		$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
		$substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty)) ? '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$object->thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
		$substitutionarray['__PERSONALIZED__'] = ''; // deprecated
		$substitutionarray['__CONTACTCIVNAME__'] = '';
		$parameters = array(
			'mode' => 'formemail'
		);
		complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

		// Find the good contact address
		$tmpobject = $object;

		$contactarr = array();
		$contactarr = $tmpobject->liste_contact(-1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$contactstatic = new Contact($db);

			foreach ($contactarr as $contact) {
				$contactstatic->fetch($contact['id']);
				$substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($outputlangs, 1);
			}
		}

		// Array of substitutions
		$formmail->substit = $substitutionarray;

		// Array of other parameters
		$formmail->param['action'] = 'send';
		$formmail->param['models'] = $modelmail;
		$formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
		$formmail->param['id'] = $object->id;
		$formmail->param['returnurl'] = $_SERVER["PHP_SELF"].'?id='.$object->id;
		$formmail->param['fileinit'] = array($file);

		// Show form
		print $formmail->get_form();

		print dol_get_fiche_end();
	}
}

// End of page
llxFooter();
$db->close();
