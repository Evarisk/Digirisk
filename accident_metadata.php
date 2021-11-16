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
 *   	\file       accident_metadata.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view accident metadata
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

require_once __DIR__ . '/class/digiriskdocuments.class.php';
require_once __DIR__ . '/class/digiriskelement.class.php';
require_once __DIR__ . '/class/digiriskresources.class.php';
require_once __DIR__ . '/class/accident.class.php';
require_once __DIR__ . '/class/preventionplan.class.php';
require_once __DIR__ . '/class/riskanalysis/risk.class.php';
//require_once __DIR__ . '/class/digiriskdocuments/accidentdocument.class.php';
require_once __DIR__ . '/lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/lib/digiriskdolibarr_accident.lib.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/accident/mod_accident_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/accidentdet/mod_accidentdet_standard.php';
//require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/accidentdocument/mod_accidentdocument_standard.php';
//require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/accidentdocument/modules_accidentdocument.php';

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
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'accidentmetadata'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object              = new Accident($db);
$accident            = new AccidentMetaData($db);
$preventionplan      = new PreventionPlan($db);
$preventionplanline  = new PreventionPlanLine($db);
$signatory           = new AccidentSignature($db);
$objectline          = new AccidentLine($db);
//$accidentdocument    = new AccidentDocument($db);
$risk                = new Risk($db);
$contact             = new Contact($db);
$usertmp             = new User($db);
$thirdparty          = new Societe($db);
$extrafields         = new ExtraFields($db);
$digiriskelement     = new DigiriskElement($db);
$digiriskresources   = new DigiriskResources($db);
$project             = new Project($db);
$refAccidentMod      = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON($db);
$refAccidentDetMod   = new $conf->global->DIGIRISKDOLIBARR_ACCIDENTDET_ADDON($db);

// Load object
$object->fetch($id);

// Load resources
$allLinks = $digiriskresources->digirisk_dolibarr_fetch_resources();

$hookmanager->initHooks(array('accidentmetadata', 'globalmetadata')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
// Security check
$permissiontoread   = $user->rights->digiriskdolibarr->accident->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/accident_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/accident_metadata.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	if (GETPOST('cancel')) {
		// Cancel fire permit
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}

	// Action to add record
	if ($action == 'add' && $permissiontoadd) {
		// Get parameters
		//$relative_location = GETPOST('relative_location');
		$lesion_localization = GETPOST('lesion_localization');
		$lesion_nature = GETPOST('lesion_nature');
		//
		$fatal = GETPOST('fatal');
		$accident_investigation = GETPOST('accident_investigation');
		$accident_investigation_link = GETPOST('accident_investigation_link');
		$collateral_victim = GETPOST('collateral_victim');
		$police_report = GETPOST('police_report');
		$cerfa_link = GETPOST('cerfa_link');

		$fk_accident = GETPOST('parent_id');

		// Initialize object accident
		$now                                 = dol_now();
		$object->date_creation               = $object->db->idate($now);
		$object->tms                         = $now;
		$object->status                      = 1;
		//$object->relative_location         = $relative_location;
		$object->lesion_localization         = $lesion_localization;
		$object->lesion_nature               = $lesion_nature;
		//$object->thirdparty_responsibility = $thirdparty_responsibility;
		$object->fatal                       = $fatal;
		$object->accident_investigation      = $accident_investigation;
		$object->accident_investigation_link = $accident_investigation_link;
		$object->collateral_victim           = $collateral_victim;
		$object->police_report               = $police_report;
		$object->cerfa_link                  = $cerfa_link;
		$object->fk_accident                 = $fk_accident;


		// Check parameters
//		if ($maitre_oeuvre_id < 0) {
//			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MaitreOeuvre')), null, 'errors');
//			$error++;
//		} else {
//			$usertmp->fetch($maitre_oeuvre_id);
//			if (!dol_strlen($usertmp->email)) {
//				setEventMessages($langs->trans('ErrorNoEmailForMaitreOeuvre', $langs->transnoentitiesnoconv('MaitreOeuvre')) . ' : ' . '<a target="_blank" href="'.dol_buildpath('/user/metadata.php?id='.$usertmp->id, 2).'">'.$usertmp->lastname . ' ' . $usertmp->firstname.'</a>', null, 'errors');
//				$error++;
//			}
//		}
//
//		if ($extsociety_id < 0) {
//			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSociety')), null, 'errors');
//			$error++;
//		}
//
//		if (is_array($extresponsible_id)) {
//			if (empty(array_filter($extresponsible_id))) {
//				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
//				$error++;
//			}
//		} elseif (empty($extresponsible_id)) {
//			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
//			$error++;
//		}
//
//		if ($labour_inspector_id < 0) {
//			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspectorSociety')), null, 'errors');
//			$error++;
//		}
//
//		if (is_array($labour_inspector_contact_id)) {
//			if (empty(array_filter($labour_inspector_contact_id))) {
//				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
//				$error++;
//			}
//		} elseif (empty($labour_inspector_contact_id)) {
//			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
//			$error++;
//		}


		if (!$error) {
			$result = $object->create($user, false);
			if ($result > 0) {
//				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY', 'societe', array($extsociety_id), $conf->entity, 'accident', $object->id, 0);
//				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_LABOUR_INSPECTOR', 'societe', array($labour_inspector_id), $conf->entity, 'accident', $object->id, 0);
//				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_LABOUR_INSPECTOR_ASSIGNED', 'socpeople', array($labour_inspector_contact_id), $conf->entity, 'accident', $object->id, 0);
//
//				if ($maitre_oeuvre_id > 0) {
//					$signatory->setSignatory($object->id,'user', array($maitre_oeuvre_id), 'FP_MAITRE_OEUVRE');
//				}
//
//				if ($extresponsible_id > 0) {
//					$signatory->setSignatory($object->id,'socpeople', array($extresponsible_id), 'FP_EXT_SOCIETY_RESPONSIBLE');
//				}

				// Creation Accident OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Creation Accident KO
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
		$description                 = GETPOST('description');
		$fk_preventionplan           = GETPOST('fk_preventionplan');

		// Initialize object fire permit
		$now           = dol_now();
		$object->tms   = $now;
		$object->label = $label;

		$date_start = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));
		$date_end   = dol_mktime(GETPOST('dateehour', 'int'), GETPOST('dateemin', 'int'), 0, GETPOST('dateemonth', 'int'), GETPOST('dateeday', 'int'), GETPOST('dateeyear', 'int'));

		$object->description = $description;
		$object->date_start  = $date_start;
		$object->date_end    = $date_end;

		$object->fk_preventionplan = $fk_preventionplan;
		$object->fk_user_creat     = $user->id ? $user->id : 1;

		// Check parameters
		if ($maitre_oeuvre_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MaitreOeuvre')), null, 'errors');
			$error++;
		}   else {
			$usertmp->fetch($maitre_oeuvre_id);
			if (!dol_strlen($usertmp->email)) {
				setEventMessages($langs->trans('ErrorNoEmailForMaitreOeuvre', $langs->transnoentitiesnoconv('MaitreOeuvre')) . ' : ' . '<a target="_blank" href="'.dol_buildpath('/user/metadata.php?id='.$usertmp->id, 2).'">'.$usertmp->lastname . ' ' . $usertmp->firstname.'</a>', null, 'errors');
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
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_EXT_SOCIETY', 'societe', array($extsociety_id), $conf->entity, 'accident', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_LABOUR_INSPECTOR', 'societe', array($labour_inspector_id), $conf->entity, 'accident', $object->id, 0);
				$digiriskresources->digirisk_dolibarr_set_resources($db, $user->id, 'FP_LABOUR_INSPECTOR_ASSIGNED', 'socpeople', array($labour_inspector_contact_id), $conf->entity, 'accident', $object->id, 0);

				$signatory->setSignatory($object->id,'user', array($maitre_oeuvre_id), 'FP_MAITRE_OEUVRE');
				$signatory->setSignatory($object->id,'socpeople', array($extresponsible_id), 'FP_EXT_SOCIETY_RESPONSIBLE');

				// Update fire permit OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Update fire permit KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		} else {
			$action = 'edit';
		}
	}

	// Action to add line
	if ($action == 'addLine' && $permissiontoadd) {
		// Get parameters
		$actions_description = GETPOST('actionsdescription');
		$use_equipment       = GETPOST('use_equipment');
		$location            = GETPOST('fk_element');
		$risk_category_id    = GETPOST('risk_category_id');
		$parent_id           = GETPOST('parent_id');

		// Initialize object accident line
		$objectline->date_creation  = $object->db->idate($now);
		$objectline->ref            = $refAccidentDetMod->getNextValue($objectline);
		$objectline->entity         = $conf->entity;
		$objectline->description    = $actions_description;
		$objectline->category       = $risk_category_id;
		$objectline->use_equipment  = $use_equipment;
		$objectline->fk_accident  = $parent_id;
		$objectline->fk_element     = $location;

		// Check parameters
		if ($parent_id < 1) {
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
				// Creation fire permit line OK
				setEventMessages($langs->trans('AddAccidentLine').' '.$objectline->ref.' '.$langs->trans('AccidentMessage'), array());
				$objectline->call_trigger('FIREPERMITDET_CREATE', $user);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Creation fire permit line KO
				if (!empty($objectline->errors)) setEventMessages(null, $objectline->errors, 'errors');
				else  setEventMessages($objectline->error, null, 'errors');
			}
		}
	}

	// Action to update line
	if ($action == 'updateLine' && $permissiontoadd) {
		// Get parameters
		$actions_description = GETPOST('actionsdescription');
		$use_equipment       = GETPOST('use_equipment');
		$location            = GETPOST('fk_element');
		$risk_category_id    = GETPOST('risk_category_id');
		$parent_id           = GETPOST('parent_id');

		$objectline->fetch($lineid);

		// Initialize object fire permit line
		$objectline->description   = $actions_description;
		$objectline->category      = $risk_category_id;
		$objectline->use_equipment = $use_equipment;
		$objectline->fk_accident = $parent_id;
		$objectline->fk_element    = $location;

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
				// Update fire permit line OK
				setEventMessages($langs->trans('UpdateAccidentLine').' '.$objectline->ref.' '.$langs->trans('AccidentMessage'), array());
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Update fire permit line KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to delete line
	if ($action == 'deleteline' && $permissiontodelete) {
		$objectline->fetch($lineid);
		$result = $objectline->delete($user, false);
		if ($result > 0) {
			// Deletion fire permit line OK
			setEventMessages($langs->trans('DeleteAccidentLine').' '.$objectline->ref.' '.$langs->trans('AccidentMessage'), array());
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Deletion fire permit line KO
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

		$model = GETPOST('model', 'alpha');

		$moreparams['object'] = $object;
		$moreparams['user']   = $user;

		$result = $accidentdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			setEventMessages($langs->trans("FileGenerated") . ' - ' . $accidentdocument->last_main_doc, null);

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
		$options['accident_risk'] = GETPOST('clone_accident_risk');
		$options['attendants'] = GETPOST('clone_attendants');
		$options['schedule'] = GETPOST('clone_schedule');

		if (1 == 0 && !GETPOST('clone_accident_risk') && !GETPOST('clone_attendants') && !GETPOST('clone_schedule')) {
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
	$triggersendname = 'FIREPERMIT_SENTBYMAIL';
	$mode = 'emailfromthirdparty';
	$trackid = 'thi'.$object->id;
	$labour_inspector = $digiriskresources->fetchResourcesFromObject('FP_LABOUR_INSPECTOR', $object);
	$labour_inspector_id = $labour_inspector->id;
	$thirdparty->fetch($labour_inspector_id);
	$object->thirdparty = $thirdparty;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$form      = new Form($db);
$formother = new FormOther($db);

$title         = $langs->trans("AccidentMetaData");
$title_create  = $langs->trans("NewAccidentMetaData");
$title_edit    = $langs->trans("ModifyAccidentMetaData");
$object->picto = 'accident@digiriskdolibarr';

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
	print '<input type="hidden" name="parent_id" value="' . $accident->id . '">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border tableforfieldcreate accident-metadata-table">'."\n";

	//RelativeLocation --
	print '<tr><td class="minwidth400">'.$langs->trans("RelativeLocation").'</td><td>';
	print '<input class="flat" type="text" size="36" name="relative_location" id="relative_location" value="'.GETPOST('relative_location').'">';
	print '</td></tr>';

	//LesionLocalization -- Siège des lésions
	print '<tr><td class="minwidth400">'.$langs->trans("LesionLocalization").'</td><td>';
	print $formother->select_dictionary('lesion_localization','c_lesion_localization', 'ref', 'label', '', 0);
	print '</td></tr>';

	//LesionNature -- Nature des lésions
	print '<tr><td class="minwidth400">'.$langs->trans("LesionNature").'</td><td>';
	print $formother->select_dictionary('lesion_nature','c_lesion_nature', 'ref', 'label', '', 0);
	print '</td></tr>';

	//ThirdPartyResponsability --
	print '<tr><td class="minwidth400">'.$langs->trans("ThirdPartyResponsability").'</td><td>';
	print '<input type="checkbox" id="thirdparty_responsability" name="thirdparty_responsability"'.(GETPOST('thirdparty_responsability') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//Fatal -- Décès
	print '<tr><td class="minwidth400">'.$langs->trans("Fatal").'</td><td>';
	print '<input type="checkbox" id="fatal" name="fatal"'.(GETPOST('fatal') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentInvestigation -- Enquête accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentInvestigation").'</td><td>';
	print '<input type="checkbox" id="accident_investigation" name="accident_investigation"'.(GETPOST('accident_investigation') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentInvestigationLink -- lien vers l'enquête accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentInvestigationLink").'</td><td>';
	print '<input type="checkbox" id="accident_investigation_link" name="accident_investigation_link"'.(GETPOST('accident_investigation_link') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentLocation").'</td><td>';
	print $formother->select_dictionary('accident_location','c_accident_location', 'ref', 'label', '', 0);
	print '</td></tr>';

	//CollateraVictim -- Victime collatérale
	print '<tr><td class="minwidth400">'.$langs->trans("CollateralVictim").'</td><td>';
	print '<input type="checkbox" id="collateral_victim" name="collateral_victim"'.(GETPOST('collateral_victim') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//PoliceReport  -- Rapport de police
	print '<tr><td class="minwidth400">'.$langs->trans("PoliceReport").'</td><td>';
	print '<input type="checkbox" id="police_report" name="police_report"'.(GETPOST('police_report') ? ' checked=""' : '').'>';
	print $form->textwithpicto('', $langs->trans(''));
	print '</td></tr>';

	//CerfaLink -- Lien vers le Cerfa
	print '<tr><td class="minwidth400">'.$langs->trans("CerfaLink").'</td><td>';
	print '<input class="flat" type="text" size="36" name="cerfa_link" id="cerfa_link" value="'.GETPOST('cerfa_link').'">';
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

	print '<table class="border centpercent tableforfieldedit accident-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Ref").'</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.$object->label.'">';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr class="oddeven"><td><label for="date_debut">'.$langs->trans("StartDate").'</label></td><td>';
	print $form->selectDate($object->date_start,'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr class="oddeven"><td><label for="date_fin">'.$langs->trans("EndDate").'</label></td><td>';
	print $form->selectDate($object->date_end, 'datee', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Maitre d'oeuvre
	$maitre_oeuvre = is_array($object_signatories['FP_MAITRE_OEUVRE']) ? array_shift($object_signatories['FP_MAITRE_OEUVRE'])->element_id : '';
	$userlist = $form->select_dolusers($maitre_oeuvre, '', 1, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">'.img_picto('','user').' '.$form->editfieldkey('MaitreOeuvre', 'MaitreOeuvre_id', '', $object, 0).'</td>';
	print '<td>';
	print $form->selectarray('maitre_oeuvre', $userlist,$maitre_oeuvre, 1, null, null, null, "40%", 0, 0, 0, 'minwidth300',1);
	print ' <a href="'.DOL_URL_ROOT.'/user/metadata.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddUser").'"></span></a>';
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
		$ext_society_id = is_array($object_resources['FP_EXT_SOCIETY']) ? array_shift($object_resources['FP_EXT_SOCIETY'])->id : '';
		print $form->select_company($ext_society_id, 'ext_society', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	}
	print ' <a href="'.DOL_URL_ROOT.'/societe/metadata.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	print '</td></tr>';

	$ext_society_responsible_id = is_array($object_signatories['FP_EXT_SOCIETY_RESPONSIBLE']) ? array_shift($object_signatories['FP_EXT_SOCIETY_RESPONSIBLE'])->element_id : GETPOST('ext_society_responsible');
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
	$ext_society = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY', $object);
	print '<tr class="oddeven"><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('','address').' '.$langs->trans("ExtSocietyResponsible");
	print $form->textwithpicto($htmltext, $langs->trans('ContactNoEmail'));
	print '</td><td>';
	print digirisk_selectcontacts($ext_society->id, dol_strlen($contact->email) ? $ext_society_responsible_id : -1, 'ext_society_responsible', 0, $contacts_no_email, '', 0, 'minwidth300', false, 0, array(), false, '', 'ext_society_responsible');
	print '</td></tr>';

	if (is_array($object_resources['FP_LABOUR_INSPECTOR']) && $object_resources['FP_LABOUR_INSPECTOR'] > 0) {
		$labour_inspector_society  = array_shift($object_resources['FP_LABOUR_INSPECTOR']);
	}
	if (is_array($object_resources['FP_LABOUR_INSPECTOR_ASSIGNED']) && $object_resources['FP_LABOUR_INSPECTOR_ASSIGNED'] > 0) {
		$labour_inspector_assigned = array_shift($object_resources['FP_LABOUR_INSPECTOR_ASSIGNED']);
	}
	//Labour inspector Society -- Entreprise Inspecteur du travail
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('','building').' '.$langs->trans("LabourInspectorSociety");
	print '</td>';
	print '<td>';
	$events = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labour_inspector_contact', 'params' => array('add-customer-contact' => 'disabled'));
	print $form->select_company($labour_inspector_society->id, 'labour_inspector', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	print ' <a href="'.DOL_URL_ROOT.'/societe/metadata.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	print '<a href="'.DOL_URL_ROOT.'/custom/digiriskdolibarr/admin/securityconf.php'.'" target="_blank">'.$langs->trans("ConfigureLabourInspector").'</a>';
	print '</td></tr>';

	$labour_inspector_contact = !empty($digiriskresources->fetchResourcesFromObject('FP_LABOUR_INSPECTOR_ASSIGNED', $object)) ? $digiriskresources->fetchResourcesFromObject('FP_LABOUR_INSPECTOR_ASSIGNED', $object) : GETPOST('labour_inspector_contact');
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
	$labour_inspector_society = $digiriskresources->fetchResourcesFromObject('FP_LABOUR_INSPECTOR', $object);
	print '<tr><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('','address').' '.$langs->trans("LabourInspector");
	print $form->textwithpicto($htmltext, $langs->trans('ContactNoEmail'));
	print '</td><td>';
	print digirisk_selectcontacts($labour_inspector_society->id, dol_strlen($contact->email) ? $labour_inspector_contact->id : -1, 'labour_inspector_contact', 0, $contacts_no_email_labour_inspector, '', 0, 'minwidth300', false, 0, array(), false, '', 'labour_inspector_contact');
	print '</td></tr>';

	//FK PREVENTION PLAN
	print '<tr class="oddeven"><td>'.$langs->trans("PreventionPlanLinked").'</td><td>';
	print $preventionplan->select_preventionplan_list($object->fk_preventionplan);
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
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('LockAccident'), $langs->trans('ConfirmLockAccident', $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
}

// setPendingSignature confirmation
if (($action == 'setPendingSignature' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateAccident'), $langs->trans('ConfirmValidateAccident', $object->ref), 'confirm_setPendingSignature', '', 'yes', 'actionButtonPendingSignature', 350, 600);
}

// setInProgress confirmation
if (($action == 'setInProgress' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpenAccident'), $langs->trans('ConfirmReOpenAccident', $object->ref), 'confirm_setInProgress', '', 'yes', 'actionButtonInProgress', 350, 600);
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	// Define confirmation messages
	$formquestionclone = array(
		'text' => $langs->trans("ConfirmClone"),
		array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("NewRefForCloneAccident"), 'value' => empty($tmpcode) ? $langs->trans("CopyOf").' '.$object->ref : $tmpcode, 'size'=>24),
		array('type' => 'checkbox', 'name' => 'clone_accident_risk', 'label' => $langs->trans("CloneAccidentRisk"), 'value' => 1),
		array('type' => 'checkbox', 'name' => 'clone_attendants', 'label' => $langs->trans("CloneAttendantsAccident"), 'value' => 1),
		array('type' => 'checkbox', 'name' => 'clone_schedule', 'label' => $langs->trans("CloneScheduleAccident"), 'value' => 1),
	);

	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAccident', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'actionButtonClone', 350, 600);
}

//	// Confirmation to delete
//	if ($action == 'delete') {
//		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteAccident'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
//	}

// Call Hook formConfirm
$parameters = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

// Print form confirm
print $formconfirm;

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {
	// Object metadata
	// ------------------------------------------------------------
	$res = $object->fetch_optionals();

	$head = accidentPrepareHead($object);
	print dol_get_fiche_head($head, 'accidentMetadata', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';
	$morehtmlref .= '<div class="refidno">';
	// External Society -- Société extérieure
	//$ext_society = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY', $object);
	//$morehtmlref .= $langs->trans('ExtSociety').' : '.$ext_society->getNomUrl(1);
	// Project
	$project->fetch($object->fk_project);
	$morehtmlref .= '<br>'.$langs->trans('Project').' : '.getNomUrlProject($project, 1, 'blank');
	$morehtmlref .= '</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, '', $object->getLibStatut(5));

	print '<div class="div-table-responsive">';
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	//Unset for order
	unset($object->fields['fk_project']);

	$object = $accident;

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

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

		if (empty($reshook)) {
			print '<a class="' . ($object->status == 1 ? 'butAction' : 'butActionRefused classfortooltip') . '" id="actionButtonEdit" title="' . ($object->status == 1 ? '' : dol_escape_htmltag($langs->trans("AccidentMustBeInProgress"))) . '" href="' . ($object->status == 1 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit') : '#') . '">' . $langs->trans("Modify") . '</a>';
			print '<span class="' . ($object->status == 1 ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . ($object->status == 1 ? 'actionButtonPendingSignature' : '') . '" title="' . ($object->status == 1 ? '' : dol_escape_htmltag($langs->trans("AccidentMustBeInProgressToValidate"))) . '" href="' . ($object->status == 1 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=setPendingSignature') : '#') . '">' . $langs->trans("Validate") . '</span>';
			print '<span class="' . ($object->status == 2 ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . ($object->status == 2 ? 'actionButtonInProgress' : '') . '" title="' . ($object->status == 2 ? '' : dol_escape_htmltag($langs->trans("AccidentMustBeValidated"))) . '" href="' . ($object->status == 2 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=setInProgress') : '#') . '">' . $langs->trans("ReOpenDigi") . '</span>';
			print '<a class="' . (($object->status == 2 && !$signatory->checkSignatoriesSignatures($object->id)) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="actionButtonSign" title="' . (($object->status == 2 && !$signatory->checkSignatoriesSignatures($object->id)) ? '' : dol_escape_htmltag($langs->trans("AccidentMustBeValidatedToSign"))) . '" href="' . (($object->status == 2 && !$signatory->checkSignatoriesSignatures($object->id)) ? $url : '#') . '">' . $langs->trans("Sign") . '</a>';
			print '<span class="' . (($object->status == 2 && $signatory->checkSignatoriesSignatures($object->id)) ? 'butAction' : 'butActionRefused classfortooltip') . '" id="' . (($object->status == 2 && $signatory->checkSignatoriesSignatures($object->id)) ? 'actionButtonLock' : '') . '" title="' . (($object->status == 2 && $signatory->checkSignatoriesSignatures($object->id)) ? '' : dol_escape_htmltag($langs->trans("AllSignatoriesMustHaveSigned"))) . '">' . $langs->trans("Lock") . '</span>';
			print '<a class="' . ($object->status == 3 ? 'butAction' : 'butActionRefused classfortooltip') . '" id="actionButtonSign" title="' . dol_escape_htmltag($langs->trans("AccidentMustBeLockedToSendEmail")) . '" href="' . ($object->status == 3 ? ($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle&sendto=' . $allLinks['LabourInspectorSociety']->id[0]) : '#') . '">' . $langs->trans('SendMail') . '</a>';
			print '<a class="' . ($object->status == 3 ? 'butAction' : 'butActionRefused classfortooltip') . '" id="actionButtonClose" title="' . ($object->status == 3 ? '' : dol_escape_htmltag($langs->trans("AccidentMustBeLocked"))) . '" href="' . ($object->status == 3 ? ($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=setArchived') : '#') . '">' . $langs->trans("Close") . '</a>';
			print '<span class="butAction" id="actionButtonClone" title="" href="'.$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=clone'.'">' . $langs->trans("ToClone") . '</span>';

			$langs->load("mails");
			if ($object->date_end == dol_now()) {
				$object->setArchived($user, false);
			}
		}
		print '</div>';
	}
	if ($permissiontoadd) {
		print '</div><div class="fichehalfright">';
	} else {
		print '</div><div class="">';
	}

	$MAXEVENT = 10;

	$morehtmlright = '<a href="' . dol_buildpath('/digiriskdolibarr/accident_agenda.php', 1) . '?id=' . $object->id . '">';
	$morehtmlright .= $langs->trans("SeeAll");
	$morehtmlright .= '</a>';

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element . '@digiriskdolibarr', '', 1, '', $MAXEVENT, '', $morehtmlright);

	print '</div></div></div>';
}

// End of page
llxFooter();
$db->close();
