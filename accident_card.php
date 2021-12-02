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
 *   	\file       accident_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view accident
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
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/accident_workstop/mod_accident_workstop_standard.php';
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
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'accidentcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object                 = new Accident($db);
$preventionplan         = new PreventionPlan($db);
$preventionplanline     = new PreventionPlanLine($db);
$objectline             = new AccidentWorkStop($db);
//$accidentdocument     = new AccidentDocument($db);
$risk                   = new Risk($db);
$contact                = new Contact($db);
$usertmp                = new User($db);
$thirdparty             = new Societe($db);
$extrafields            = new ExtraFields($db);
$digiriskelement        = new DigiriskElement($db);
$digiriskresources      = new DigiriskResources($db);
$project                = new Project($db);
$refAccidentMod         = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON($db);
$refAccidentWorkStopMod = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON($db);

// Load object
$object->fetch($id);

// Load resources
$allLinks = $digiriskresources->digirisk_dolibarr_fetch_resources();

$hookmanager->initHooks(array('accidentcard', 'globalcard')); // Note that conf->hooks_modules contains array

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
			else $backtopage = dol_buildpath('/digiriskdolibarr/accident_card.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
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
		$user_victim_id     = GETPOST('fk_user_victim');
		$digiriskelement_id = GETPOST('fk_element');
		$label              = GETPOST('label');
		$description        = GETPOST('description');

		// Initialize object accident
		$now                   = dol_now();
		$object->ref           = $refAccidentMod->getNextValue($object);
		$object->ref_ext       = 'digirisk_' . $object->ref;
		$object->date_creation = $object->db->idate($now);
		$object->tms           = $now;
		$object->import_key    = "";
		$object->status        = 1;
		$object->label         = $label;
		$object->description   = $description;
		$object->fk_project    = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;

		$accident_date = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));

		$object->accident_date = $accident_date;

		$object->fk_element     = $digiriskelement_id;
		$object->fk_user_victim = $user_victim_id;
		$object->fk_user_creat  = $user->id ? $user->id : 1;

		// Check parameters
		if ($user_victim_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UserVictim')), null, 'errors');
			$error++;
		}

		// Submit file
		if (!empty($conf->global->MAIN_UPLOAD_DOC)) {
			if (!empty($_FILES) && !empty($_FILES['userfile']['name'][0])) {
				if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
				else $userfiles = array($_FILES['userfile']['tmp_name']);

				foreach ($userfiles as $key => $userfile) {
					if (empty($_FILES['userfile']['tmp_name'][$key])) {
						$error++;
						if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
							setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
						}
					}
				}

				$filedir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/accident/'. $object->ref;
				if (!file_exists($filedir))
				{
					if (dol_mkdir($filedir) < 0)
					{

						$object->error = $langs->transnoentities("ErrorCanNotCreateDir", $filedir);
						$error++;
					}
				}

				if (!$error) {
					dol_mkdir($filedir);
					if (!empty($filedir)) {
						$result = dol_add_file_process($filedir, 0, 1, 'userfile', '', null, '', 1, $object);
					}
				}
			}
		}

		if (!$error) {
			$result = $object->create($user, false);
			if ($result > 0) {
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
		$user_victim_id     = GETPOST('fk_user_victim');
		$digiriskelement_id = GETPOST('fk_element');
		$label              = GETPOST('label');
		$description        = GETPOST('description');

		// Initialize object accident
		$now                 = dol_now();
		$object->tms         = $now;
		$object->label       = $label;
		$object->description = $description;
		$object->fk_project  = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;

		$accident_date = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));

		$object->accident_date = $accident_date;

		$object->fk_element     = $digiriskelement_id;
		$object->fk_user_victim = $user_victim_id;
		$object->fk_user_creat  = $user->id ? $user->id : 1;

		// Check parameters
		if ($user_victim_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UserVictim')), null, 'errors');
			$error++;
		}

		// Submit file
		if (!empty($conf->global->MAIN_UPLOAD_DOC)) {
			if (!empty($_FILES)) {
				if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
				else $userfiles = array($_FILES['userfile']['tmp_name']);

				foreach ($userfiles as $key => $userfile) {
					if (empty($_FILES['userfile']['tmp_name'][$key])) {
						$error++;
						if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
							setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
						}
					}
				}

				$filedir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/accident/'. $object->ref;
				if (!file_exists($filedir))
				{
					if (dol_mkdir($filedir) < 0)
					{
						$object->error = $langs->transnoentities("ErrorCanNotCreateDir", $filedir);
						$error++;
					}
				}

				if (!$error) {
					dol_mkdir($filedir);
					if (!empty($filedir)) {
						$result = dol_add_file_process($filedir, 0, 1, 'userfile', '', null, '', 1, $object);
					}
				}
			}
		}

		if (!$error) {
			$result = $object->update($user, false);
			if ($result > 0) {
				// Update Accident OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Update Accident KO
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
		$workstop_days = GETPOST('workstop_days');
		$parent_id     = GETPOST('parent_id');

		// Initialize object accident line
		$objectline->date_creation  = $object->db->idate($now);
		$objectline->ref            = $refAccidentWorkStopMod->getNextValue($objectline);
		$objectline->entity         = $conf->entity;
		$objectline->workstop_days  = $workstop_days;
		$objectline->fk_accident    = $parent_id;


		// Check parameters
		if (empty($workstop_days)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('WorkStopDays')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $objectline->insert($user, false);
			if ($result > 0) {
				// Creation accident line OK
				rename($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident/' . $object->ref . '/workstop/temp/', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident/' . $object->ref . '/workstop/' . $objectline->ref);

				setEventMessages($langs->trans('AddAccidentLine').' '.$objectline->ref, array());
				$objectline->call_trigger('ACCIDENT_WORKSTOP_CREATE', $user);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Creation accident line KO
				if (!empty($objectline->errors)) setEventMessages(null, $objectline->errors, 'errors');
				else  setEventMessages($objectline->error, null, 'errors');
			}
		}
	}

	// Action to update line
	if ($action == 'updateLine' && $permissiontoadd) {
		// Get parameters
		$workstop_days = GETPOST('workstop_days');
		$parent_id     = GETPOST('parent_id');

		$objectline->fetch($lineid);

		// Initialize object accident line
		$objectline->workstop_days = $workstop_days;
		$objectline->fk_accident   = $parent_id;

		if (!$error) {
			$result = $objectline->update($user, false);
			if ($result > 0) {
				// Update accident line OK
				setEventMessages($langs->trans('UpdateAccidentLine').' '.$objectline->ref, array());
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Update accident line KO
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
			// Deletion accident line OK
			setEventMessages($langs->trans('DeleteAccidentLine').' '.$objectline->ref, array());
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Deletion accident line KO
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


	// Add file in ticket form
	if ($action == 'sendfile') {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$objectlineid = GETPOST('objectlineid');
		if ($objectlineid > 0) {
			$objectline->fetch($objectlineid);
			$folder =  $objectline->ref;
		} else {
			$folder = 'temp';
		}
		$object->fetch($id);

		if (!empty($_FILES) && !empty($_FILES['files']['name'][0])) {
			if (is_array($_FILES['files']['tmp_name'])) $filess = $_FILES['files']['tmp_name'];
			else $filess = array($_FILES['files']['tmp_name']);

			foreach ($filess as $key => $files) {
				if (empty($_FILES['files']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['files']['error'][$key] == 1 || $_FILES['files']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					}
				}
			}

			$filedir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/accident/'. $object->ref . '/workstop/' . $folder;
			if (!file_exists($filedir))
			{
				if (dol_mkdir($filedir) < 0)
				{
					$object->error = $langs->transnoentities("ErrorCanNotCreateDir", $filedir);
					$error++;
				}
			}

			if (!$error) {
				dol_mkdir($filedir);
				if (!empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'files', '', null, '', 1, $object);
				}
			}
		}
	}

	if ($action == 'removefile') {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$filetodelete = GETPOST('filetodelete');
		$objectlineid = GETPOST('objectlineid');
		if ($objectlineid > 0) {
			$objectline->fetch($objectlineid);
			$folder = $objectline->ref;
		} else {
			$folder = 'temp';
		}


		$accident_upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident/' . $object->ref . '/workstop/' . $folder . '/';
		//Add files linked
		$fileList          = dol_dir_list($accident_upload_dir);

		if (is_file($accident_upload_dir . $filetodelete)) {
			dol_delete_file($accident_upload_dir . $filetodelete);

			$thumbsList = dol_dir_list($accident_upload_dir . 'thumbs/');
			if (!empty($thumbsList)) {
				foreach ($thumbsList as $thumb) {
					if (preg_match('/'. preg_split('/\./', $filetodelete)[0] . '/', $thumb['name'])) {
						dol_delete_file($accident_upload_dir . 'thumbs/' . $thumb['name'] );
					}
				}
			}
		}
		$action = '';
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

$form = new Form($db);

$title         = $langs->trans("Accident");
$title_create  = $langs->trans("NewAccident");
$title_edit    = $langs->trans("ModifyAccident");
$object->picto = 'accident@digiriskdolibarr';

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($title_create, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate accident-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Ref").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="'.$refAccidentMod->getNextValue($object).'">';
	print $refAccidentMod->getNextValue($object);
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="minwidth400">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.GETPOST('label').'">';
	print '</td></tr>';

	//User Victim -- Utilisateur victime de l'accient
	$userlist = $form->select_dolusers((!empty(GETPOST('fk_user_victim')) ? GETPOST('fk_user_victim') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserVictim', 'UserVictim_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_victim', $userlist, (!empty(GETPOST('fk_user_victim')) ? GETPOST('fk_user_victim') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//ParentElement -- Element parent
	print '<tr><td>'.$langs->trans("ParentElement").'</td><td>';
	print $digiriskelement->select_digiriskelement_list($object->fk_element, 'fk_element', '', '',  0, 0, array(), '',  0,  0,  'minwidth100',  GETPOST('id'),  false, 0);
	print '</td></tr>';

	//Accident Date -- Date de l'accident
	print '<tr><td class="minwidth400"><label for="accident_date">'.$langs->trans("AccidentDate").'</label></td><td>';
	print $form->selectDate(dol_now('tzuser'), 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Description -- Description
	print '<tr class="content_field"><td><label for="content">'.$langs->trans("Description").'</label></td><td>';
	$doleditor = new DolEditor('description', GETPOST('description'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr><td class="titlefield">'.$form->editfieldkey($langs->trans("Photo"), 'Photo', '', $object, 0).'</td><td>';
	print '<input class="flat" type="file" name="userfile[]" id="Photo" />';
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

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit accident-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Ref").'</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.$object->label.'">';
	print '</td></tr>';

	//User Victim -- Utilisateur victime de l'accient
	$userlist = $form->select_dolusers((!empty(GETPOST('fk_user_victim')) ? GETPOST('fk_user_victim') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserVictim', 'UserVictim_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_victim', $userlist, (!empty(GETPOST('fk_user_victim')) ? GETPOST('fk_user_victim') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//ParentElement -- Element parent
	print '<tr><td>'.$langs->trans("ParentElement").'</td><td>';
	print $digiriskelement->select_digiriskelement_list($object->fk_element, 'fk_element', '', '',  0, 0, array(), '',  0,  0,  'minwidth100',  GETPOST('id'),  false, 0);
	print '</td></tr>';

	//Accident Date -- Date de l'accident
	print '<tr><td class="minwidth400"><label for="accident_date">'.$langs->trans("AccidentDate").'</label></td><td>';
	print $form->selectDate(dol_now('tzuser'), 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Description -- Description
	print '<tr class="content_field"><td><label for="content">'.$langs->trans("Description").'</label></td><td>';
	$doleditor = new DolEditor('description', GETPOST('description'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr><td class="titlefield">'.$form->editfieldkey($langs->trans("Photo"), 'Photo', '', $object, 0).'</td><td>';
	print '<input class="flat" type="file" name="userfile[]" id="Photo" />';
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
	// Object card
	// ------------------------------------------------------------
	$res = $object->fetch_optionals();

	$head = accidentPrepareHead($object);
	print dol_get_fiche_head($head, 'accidentCard', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';
	$morehtmlref .= '<div class="refidno">';
	// Project
	$project->fetch($object->fk_project);
	$morehtmlref .= $langs->trans('Project').' : '.getNomUrlProject($project, 1, 'blank');
	$morehtmlref .= '</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, '', $object->getLibStatut(5));

	print '<div class="div-table-responsive">';
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	//Unset for order
	unset($object->fields['label']);
	unset($object->fields['accident_date']);
	unset($object->fields['photo']);
	unset($object->fields['fk_project']);

	//Label -- Libellé
	print '<tr><td class="titlefield">';
	print $langs->trans("Label");
	print '</td>';
	print '<td>';
	print $object->label;
	print '</td></tr>';

	//Accident date -- Date de l'accident
	print '<tr><td class="titlefield">';
	print $langs->trans("AccidentDate");
	print '</td>';
	print '<td>';
	print dol_print_date($object->accident_date, 'dayhoursec');
	print '</td></tr>';

	//Parent Element -- Elément parent
	print '<tr><td class="titlefield">'.$langs->trans("ParentElement").'</td><td>';
	$result = $digiriskelement->fetch($object->fk_kelement);
	if ($result > 0) {
		print $digiriskelement->ref . ( !empty($digiriskelement->label) ?  ' - ' . $digiriskelement->label : '');
	}
	else {
		print $conf->global->MAIN_INFO_SOCIETE_NOM;
	}
	print '</td></tr>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	print '<tr><td class="titlefield">';
	print $langs->trans("Photo") . '</td>';
	print '<td>';
	$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/accident/'.$object->ref, "files", 0, '', '(\.odt|\.zip)', 'date', 'asc', 1);
	if (count($filearray)) : ?>
		<?php $file = array_shift($filearray); ?>
		<span class="">
			<?php print '<img class="" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode('/accident/'.$object->ref.'/thumbs/'. preg_replace('/\./', '_small.',$file['name'])).'" >'; ?>
		</span>
	<?php else: ?>
		<?php $nophoto = DOL_URL_ROOT.'/public/theme/common/nophoto.png'; ?>
		<span class="">
			<img class="" alt="No photo" src="<?php echo $nophoto ?>">
		</span>
	<?php endif; ?>
	<?php print '</td></tr>';

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

		// ACCIDENT LINES
		print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
		print load_fiche_titre($langs->trans("AccidentRiskList"), '', '');
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		global $forceall, $forcetoshowtitlelines;

		if (empty($forceall)) $forceall = 0;

		// Define colspan for the button 'Add'
		$colspan = 3; // Columns: total ht + col edit + col delete

		// Accident Lines
		$accidentlines = $objectline->fetchAll($object->id);

		print '<tr class="liste_titre">';
		print '<td><span>' . $langs->trans('Ref.') . '</span></td>';
		print '<td>' . $langs->trans('WorkStopDays') . '</td>';
		print '<td>' . $langs->trans('WorkStopDocument') . '</td>';
		print '<td class="center" colspan="' . $colspan . '">' . $langs->trans('ActionsAccidentRisk') . '</td>';
		print '</tr>';

		if (!empty($accidentlines) && $accidentlines > 0) {
			foreach ($accidentlines as $key => $item) {
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

					$coldisplay++;
					print '<td>';
					print '<input type="integer" name="workstop_days" class="minwidth150" value="' .  $item->workstop_days . '">';
					print '</td>';

					$coldisplay++;
					print '<td>'; ?>
					<div class="wpeo-gridlayout grid-2">
						<span class="form-label"><?php print $langs->trans("FilesLinked"); ?></span>
						<label class="wpeo-button button-blue" for="sendfile">
							<i class="fas fa-image button-icon"></i>
							<span class="button-label"><?php print $langs->trans('AddDocument'); ?></span>
							<input type="file" name="userfile[]" class="sendfile" multiple="multiple" id="sendfile" onchange="window.eoxiaJS.accident.tmpStockFile(<?php echo $item->id ?>)"  style="display: none"/>
						</label>
					</div>
					<div id="sendFileForm<?php echo $item->id ?>">
						<div id="fileLinkedTable<?php echo $item->id ?>" class="tableforinputfields objectline" value="<?php echo 0 ?>">
							<?php $fileLinkedList = dol_dir_list($conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/accident/' . $object->ref . '/workstop/'. $item->ref .'/'); ?>
							<div class="wpeo-table table-flex table-3 objectline" value="<?php echo $item->id ?>"">
								<?php
								if (!empty($fileLinkedList)) {
									foreach ($fileLinkedList as $fileLinked) {
										if (preg_split('/\./', $fileLinked['name'])[1] == 'png' || preg_split('/\./', $fileLinked['name'])[1] == 'jpg' || preg_split('/\./', $fileLinked['name'])[1] == 'jpeg') :
											?>
											<div class="table-row">
												<div class="table-cell">
													<?php print '<img class="photo"  width="50" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('/accident/' . $object->ref . '/workstop/' . $item->ref . '/thumbs/' . preg_split('/\./', $fileLinked['name'])[0] . '_mini.' . preg_split('/\./', $fileLinked['name'])[1]) . '" title="' . dol_escape_htmltag($alt) . '">'; ?>
												</div>
												<div class="table-cell">
													<?php print preg_replace('/_mini/', '', $fileLinked['name']); ?>
												</div>
												<div class="table-cell table-50 table-end table-padding-0">
													<?php print '<div class="linked-file-delete-workstop wpeo-button button-square-50 button-transparent" value="' . $fileLinked['name'] . '"><i class="fas fa-trash button-icon"></i></div>'; ?>
												</div>
											</div> <?php
										elseif ($fileLinked['type'] != 'dir') : ?>
											<div class="table-row">
												<div class="table-cell  table-padding-100">
													<i class="fas fa-file"></i>
												</div>
												<div class="table-cell">
													<?php print preg_replace('/_mini/', '', $fileLinked['name']); ?>
												</div>
												<div class="table-cell table-50 table-end table-padding-0">
													<?php print '<div class="linked-file-delete-workstop wpeo-button button-square-50 button-transparent" value="' . $fileLinked['name'] . '"><i class="fas fa-trash button-icon"></i></div>'; ?>
												</div>
											</div>
										<?php
										endif;
									} ?> </div>
						</div> <?php
						} else {
							?>
							<div class="table-row">
								<div class="table-cell"><?php print $langs->trans('NoFileLinked'); ?></div>
							</div>
							<?php
						}
						?>
					</div> <?php
					print '</td>';

					$coldisplay += $colspan;
					print '<td class="center" colspan="' . $colspan . '">';
					print '<input type="submit" class="button" value="' . $langs->trans('Save') . '" name="updateLine" id="updateLine">';
					print '</td>';
					print '</tr>';

					if (is_object($objectline)) {
//						print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
					}
					print '</form>';
				} else {
					print '<td>';
					print $item->ref;
					print '</td>';

					$coldisplay++;
					print '<td>';
					print $item->workstop_days;
					print '</td>';

					$coldisplay++;
					print '<td>'; ?>
					<?php $fileLinkedList = dol_dir_list($conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/accident/' . $object->ref . '/workstop/'. $item->ref .'/'); ?>
					<div class="wpeo-table table-flex table-3 objectline" value="<?php echo $item->id ?>">
						<?php
						if (!empty($fileLinkedList)) {
							foreach ($fileLinkedList as $fileLinked) {
								if (preg_split('/\./', $fileLinked['name'])[1] == 'png' || preg_split('/\./', $fileLinked['name'])[1] == 'jpg' || preg_split('/\./', $fileLinked['name'])[1] == 'jpeg') :
									?>
									<div class="table-row">
										<div class="table-cell">
											<?php print '<img class="photo"  width="50" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode('/accident/'. $object->ref . '/workstop/'. $item->ref .'/thumbs/' . preg_split('/\./', $fileLinked['name'])[0] . '_mini.'. preg_split('/\./', $fileLinked['name'])[1]).'" title="'.dol_escape_htmltag($alt).'">'; ?>
										</div>
										<div class="table-cell">
											<?php print preg_replace('/_mini/','', $fileLinked['name']); ?>
										</div>
									</div> <?php
								elseif ($fileLinked['type'] != 'dir') : ?>
									<div class="table-row">
										<div class="table-cell  table-padding-100">
											<i class="fas fa-file"></i>
										</div>
										<div class="table-cell">
											<?php print preg_replace('/_mini/','', $fileLinked['name']); ?>
										</div>
									</div>
								<?php
								endif;
							}
						} else {
							?>
							<div class="table-row">
								<div class="table-cell"><?php print $langs->trans('NoFileLinked'); ?></div>
							</div>
							<?php
						}
						?>
					</div> <?php
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
//						print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
					}
					print '</tr>';
				}
			}
			print '</tr>';
		}
		if ($object->status == 1 && $permissiontoadd && $action != 'editline') {
			print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="addLine">';
			print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
			print '<input type="hidden" name="parent_id" value="' . $object->id . '">';

			print '<tr>';
			print '<td>';
			print $refAccidentWorkStopMod->getNextValue($objectline);
			print '</td>';

			$coldisplay++;
			print '<td>';
			print '<input type="integer" name="workstop_days" class="minwidth150" value="">';
			print '</td>';

			$coldisplay++;
			print '<td class="maxwidth100">';
			?>

			<div class="form-element">
				<div class="wpeo-gridlayout grid-2">
					<span class="form-label"><?php print $langs->trans("FilesLinked"); ?></span>
					<label class="wpeo-button button-blue" for="sendfile">
						<i class="fas fa-image button-icon"></i>
						<span class="button-label"><?php print $langs->trans('AddDocument'); ?></span>
						<input type="file" name="userfile[]" class="sendfile" multiple="multiple" id="sendfile" onchange="window.eoxiaJS.accident.tmpStockFile(<?php echo 0 ?>)"  style="display: none"/>
					</label>
				</div>

				<div id="sendFileForm<?php echo 0 ?>">
					<div id="fileLinkedTable<?php echo 0 ?>" class="tableforinputfields objectline" value="<?php echo 0 ?>">
						<?php $fileLinkedList = dol_dir_list($conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/accident/' . $object->ref . '/workstop/temp/'); ?>
						<div class="wpeo-table table-flex table-3">
							<?php
							if (!empty($fileLinkedList)) {
								foreach ($fileLinkedList as $fileLinked) {
									if (preg_split('/\./', $fileLinked['name'])[1] == 'png' || preg_split('/\./', $fileLinked['name'])[1] == 'jpg' || preg_split('/\./', $fileLinked['name'])[1] == 'jpeg') :
									?>
										<div class="table-row">
											<div class="table-cell">
												<?php print '<img class="photo"  width="50" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode('/accident/'. $object->ref . '/workstop/temp/thumbs/' . preg_split('/\./', $fileLinked['name'])[0] . '_mini.'. preg_split('/\./', $fileLinked['name'])[1]).'" title="'.dol_escape_htmltag($alt).'">'; ?>
											</div>
											<div class="table-cell">
												<?php print preg_replace('/_mini/','', $fileLinked['name']); ?>
											</div>
											<div class="table-cell table-50 table-end table-padding-0">
												<?php print '<div class="linked-file-delete-workstop wpeo-button button-square-50 button-transparent" value="'. $fileLinked['name'] .'"><i class="fas fa-trash button-icon"></i></div>'; ?>
											</div>
										</div> <?php
									elseif ($fileLinked['type'] != 'dir') : ?>
										<div class="table-row">
											<div class="table-cell  table-padding-100">
												<i class="fas fa-file"></i>
											</div>
											<div class="table-cell">
												<?php print preg_replace('/_mini/','', $fileLinked['name']); ?>
											</div>
											<div class="table-cell table-50 table-end table-padding-0">
												<?php print '<div class="linked-file-delete-workstop wpeo-button button-square-50 button-transparent" value="'. $fileLinked['name'] .'"><i class="fas fa-trash button-icon"></i></div>'; ?>
											</div>
										</div>
										<?php
									endif;
								}
							} else {
								?>
								<div class="table-row">
									<div class="table-cell"><?php print $langs->trans('NoFileLinked'); ?></div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>

			</div>
			<?php
			print '</td>';

			$coldisplay += $colspan;
			print '<td class="center" colspan="' . $colspan . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">';
			print '</td>';
			print '</tr>';

			if (is_object($objectline)) {
//				print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
			}
			print '</form>';
		}
		print '</table>';
		print '</div>';
	}
	// Document Generation -- Génération des documents
	$includedocgeneration = 0;
	if ($includedocgeneration) {
		print '<div class="fichecenter"><div class="accidentDocument fichehalfleft">';

		$objref = dol_sanitizeFileName($object->ref);
		$dir_files = $accidentdocument->element . '/' . $objref;
		$filedir = $upload_dir . '/' . $dir_files;
		$urlsource = $_SERVER["PHP_SELF"] . '?id='. $id;

		$modulepart = 'digiriskdolibarr:AccidentDocument';
		$defaultmodel = $conf->global->DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_DEFAULT_MODEL;
		$title = $langs->trans('AccidentDocument');

		print digiriskshowdocuments($modulepart, $dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, $defaultmodel, 1, 0, 28, 0, '', $title, '', $langs->defaultlang, '', $accidentdocument, 0, 'remove_file', $object->status == 3, $langs->trans('AccidentMustBeLocked'));
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

	// Presend form
	$labour_inspector = $digiriskresources->fetchResourcesFromObject('FP_LABOUR_INSPECTOR', $object);
	$labour_inspector_id = $labour_inspector->id;
	$thirdparty->fetch($labour_inspector_id);
	$object->thirdparty = $thirdparty;

	$modelmail = 'accident';
	$defaulttopic = 'Information';
	$diroutput = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element . 'document';
	$ref = $object->ref . '/';
	$trackid = 'thi'.$object->id;

	if ($action == 'presend') {
		$langs->load("mails");

		$titreform = 'SendMail';

		$object->fetch_projet();

		if (!in_array($object->element, array('societe', 'user', 'member'))) {
			$ref = dol_sanitizeFileName($object->ref);
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$fileparams = dol_most_recent_file($diroutput.'/'.$ref, '');
			$file = $fileparams['fullname'];
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id'])) {
			$newlang = $_REQUEST['lang_id'];
		}
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}

		if (!empty($newlang)) {
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
		if ($forcebuilddoc) {   // If there is no default value for supplier invoice, we do not generate file, even if modelpdf was set by a manual generation
			if ((!$file || !is_readable($file)) && method_exists($object, 'generateDocument')) {
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
		$maitre_oeuvre = $signatory->fetchSignatory('FP_MAITRE_OEUVRE', $object->id);
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

		$labour_inspector_contact = $digiriskresources->fetchResourcesFromObject('FP_LABOUR_INSPECTOR_ASSIGNED', $object);

		if (!empty($object->socid) && $object->socid > 0 && !is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}
		if (is_object($object->thirdparty)) {
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
