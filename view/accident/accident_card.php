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
 *   	\file       view/accident/accident_card.php
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
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

require_once __DIR__ . '/../../class/digiriskdocuments.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
//require_once __DIR__ . '/../../class/digiriskdocuments/accidentdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident.lib.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/accident/mod_accident_standard.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/accident_workstop/mod_accident_workstop_standard.php';
//require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskdocuments/accidentdocument/mod_accidentdocument_standard.php';
//require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskdocuments/accidentdocument/modules_accidentdocument.php';

global $conf, $db, $hookmanager, $langs, $mysoc, $user;

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
$signatory              = new AccidentSignature($db);
$objectline             = new AccidentWorkStop($db);
//$accidentdocument     = new AccidentDocument($db);
$contact                = new Contact($db);
$usertmp                = new User($db);
$thirdparty             = new Societe($db);
$extrafields            = new ExtraFields($db);
$digiriskelement        = new DigiriskElement($db);
$digiriskstandard       = new DigiriskStandard($db);
$project                = new Project($db);
$refAccidentMod         = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON($db);
$refAccidentWorkStopMod = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON($db);

// Load object
$object->fetch($id);

$hookmanager->initHooks(array('accidentcard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[$conf->entity];
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

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/accident/accident_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/view/accident/accident_card.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	if (GETPOST('cancel') || GETPOST('cancelLine')) {
		// Cancel accident
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}

	// Action to add record
	if ($action == 'add' && $permissiontoadd) {
		// Get parameters
		$user_victim_id     = GETPOST('fk_user_victim');
		$user_employer_id   = GETPOST('fk_user_employer');
		$digiriskelement_id = GETPOST('fk_element');
		$label              = GETPOST('label');
		$description        = GETPOST('description');
		$accident_type      = GETPOST('accident_type');
		$external_accident  = GETPOST('external_accident');
		$ext_society_id     = GETPOST('fk_soc');

		// Initialize object accident
		$now                       = dol_now();
		$object->ref               = $refAccidentMod->getNextValue($object);
		$object->ref_ext           = 'digirisk_' . $object->ref;
		$object->date_creation     = $object->db->idate($now);
		$object->tms               = $now;
		$object->import_key        = "";
		$object->status            = 1;
		$object->label             = $label;
		$object->description       = $description;
		$object->accident_type     = $accident_type;
		$object->external_accident = $external_accident;
		$object->fk_project        = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;

		$accident_date = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));

		$object->accident_date = $accident_date;
		if (empty($digiriskelement_id)) {
			$object->fk_element = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
		} else {
			$object->fk_element = $digiriskelement_id;
		}
		$object->fk_soc           = $ext_society_id;
		$object->fk_user_employer = $user_employer_id;
		$object->fk_user_victim   = $user_victim_id;
		$object->fk_user_creat    = $user->id ?: 1;

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

				$filedir = $upload_dir . '/accident/'. $object->ref;

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
						$object->photo = $_FILES['userfile']['name'][0];
					}
				}
			}
		}

		if (!$error) {
			$result = $object->create($user, false);
			if ($result > 0) {
				if (empty($object->fk_user_employer)) {
					$usertmp->fetch('', $mysoc->managers, $mysoc->id, 0, $conf->entity);
				} else {
					$usertmp->fetch($object->fk_user_employer);
				}
				$signatory->setSignatory($object->id,'accident','user', array($usertmp->id), 'ACC_USER_EMPLOYER');

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
		$user_employer_id   = GETPOST('fk_user_employer');
		$digiriskelement_id = GETPOST('fk_element');
		$label              = GETPOST('label');
		$description        = GETPOST('description');
		$accident_type      = GETPOST('accident_type');
		$external_accident  = GETPOST('external_accident');
		$ext_society_id     = GETPOST('fk_soc');

		// Initialize object accident
		$now                       = dol_now();
		$object->tms               = $now;
		$object->label             = $label;
		$object->description       = $description;
		$object->accident_type     = $accident_type;
		$object->external_accident = $external_accident;
		$object->fk_project        = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;

		$accident_date = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));

		$object->accident_date = $accident_date;

		if (empty($digiriskelement_id)) {
			$object->fk_element = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
		} else {
			$object->fk_element = $digiriskelement_id;
		}
		$object->fk_soc           = $ext_society_id;
		$object->fk_user_victim   = $user_victim_id;
		$object->fk_user_employer = $user_employer_id;
		$object->fk_user_creat    = $user->id ? $user->id : 1;

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

				$filedir = $upload_dir . '/accident/'. $object->ref;

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
						$object->photo = $_FILES['userfile']['name'][0];
					}
				}
			}
		}

		if (!$error) {
			$result = $object->update($user, false);
			if ($result > 0) {
				if (empty($object->fk_user_employer)) {
					$usertmp->fetch('', $mysoc->managers, $mysoc->id, 0, $conf->entity);
				} else {
					$usertmp->fetch($object->fk_user_employer);
				}
				$signatory->deleteSignatoriesSignatures($object->id, 'accident');
				$signatory->setSignatory($object->id,'accident', 'user', array($usertmp->id), 'ACC_USER_EMPLOYER');

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
		$objectline->status         = 1;
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

				setEventMessages($langs->trans('AddAccidentWorkStop').' '.$object->ref, array());
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
				setEventMessages($langs->trans('UpdateAccidentWorkStop').' '.$object->ref, array());
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
	if ($action == 'confirm_deleteLine' && GETPOST("confirm") == "yes" && $permissiontodelete) {
		$objectline->fetch($lineid);

		$objectline->status = 0;

		$pathPhoto = $upload_dir . '/accident/'.$object->ref.'/workstop/'.$objectline->ref;
		$filedir   = $upload_dir . '/accident/'.$object->ref.'/archive/workstop/';

		if (!file_exists($filedir))
		{
			if (dol_mkdir($filedir) < 0)
			{
				$object->error = $langs->transnoentities("ErrorCanNotCreateDir", $filedir);
				$error++;
			}
		}

		if (!$error) {
			rename($pathPhoto, $filedir . $objectline->ref);
			$result = $objectline->delete($user, false);
		}

		if ($result > 0) {
			// Deletion accident line OK
			setEventMessages($langs->trans('DeleteAccidentWorkStop').' '.$object->ref, array());
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

	// Add file in accident workstop
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
					if (preg_match('/'. preg_split('/\./', $filetodelete)[0] . '_/', $thumb['name'])) {
						dol_delete_file($accident_upload_dir . 'thumbs/' . $thumb['name'] );
					}
				}
			}
		}
		$action = '';
	}
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

	//User Employer -- Utilisateur responsable de la société
	if (!empty($mysoc->managers)) {
		$usertmp->fetch('', $mysoc->managers, $mysoc->id, 0, $conf->entity);
	}
	$userlist = $form->select_dolusers((($usertmp->id > 0) ? $usertmp->id : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserEmployer', 'UserEmployer_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_employer', $userlist, (($usertmp->id > 0) ? $usertmp->id : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//User Victim -- Utilisateur victime de l'accient
	$userlist = $form->select_dolusers((!empty(GETPOST('fk_user_victim')) ? GETPOST('fk_user_victim') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserVictim', 'UserVictim_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_victim', $userlist, (!empty(GETPOST('fk_user_victim')) ? GETPOST('fk_user_victim') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//AccidentType
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentType").'</td><td>';
	print $form->selectarray('accident_type', array('0'=>$langs->trans('WorkAccidentStatement'), '1'=>$langs->trans('CommutingAccident')), '', 0, 0, 0, '', 0, 0, 0, '', 'minwidth300', 1);
	print '</td></tr>';

	//ExternalAccident
	print '<tr><td class="minwidth400">'.$langs->trans("ExternalAccident").'</td><td>';
	print '<input type="checkbox" id="external_accident" name="external_accident"'.($object->external_accident ? ' checked=""' : '').'>';
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr class="fk_element_field"><td class="minwidth400">'.$langs->trans("AccidentLocation").'</td><td>';
	print $digiriskelement->select_digiriskelement_list($object->fk_element, 'fk_element', '', '',  0, 0, array(), '',  0,  0,  'minwidth100',  GETPOST('id'),  false, 0);
	print '</td></tr>';

	//FkSoc -- Société extérieure
	print '<tr class="fk_soc_field hidden" '. (GETPOST('fk_soc') ?  '' : 'style="display:none"') .'><td class="minwidth400">'.$langs->trans("AccidentLocation").'</td>';
	print '<td>';
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'fk_soc', '', 1, 1, 0, '', 0, 'minwidth300');
	} else {
		print $form->select_company('', 'fk_soc', '', 'SelectThirdParty', 1, 0, '', 0, 'minwidth300');
	}
	print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
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
//	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

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

	//User Employer -- Utilisateur responsable de la société
	$userlist = $form->select_dolusers((!empty($object->fk_user_employer) ? $object->fk_user_employer : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserEmployer', 'UserEmployer_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_employer', $userlist, (!empty($object->fk_user_employer) ? $object->fk_user_employer : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//User Victim -- Utilisateur victime de l'accient
	$userlist = $form->select_dolusers((!empty($object->fk_user_victim) ?$object->fk_user_victim : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserVictim', 'UserVictim_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_victim', $userlist, (!empty($object->fk_user_victim) ? $object->fk_user_victim : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//AccidentType
	print '<tr><td class="minwidth400">'.$langs->trans("AccidentType").'</td><td>';
	print $form->selectarray('accident_type', array('0'=>$langs->trans('WorkAccidentStatement'), '1'=>$langs->trans('CommutingAccident')), $object->accident_type, 0, 0, 0, '', 0, 0, 0, '', 'minwidth400', 1);
	print '</td></tr>';

	//ExternalAccident
	print '<tr><td class="minwidth400">'.$langs->trans("ExternalAccident").'</td><td>';
	print '<input type="checkbox" id="external_accident" name="external_accident"'.($object->external_accident ? ' checked=""' : '').'>';
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr class="'.(empty($object->external_accident) ?  ' fk_element_field' : ' fk_element_field hidden' ).'" style="'.(empty($object->external_accident) ? ' ' : ' display:none').'"><td>'.$langs->trans("AccidentLocation").'</td><td>';
	print $digiriskelement->select_digiriskelement_list($object->fk_element, 'fk_element', '', '',  0, 0, array(), '',  0,  0,  'minwidth100',  GETPOST('id'),  false, 0);
	print '</td></tr>';

	//FkSoc -- Société extérieure
	print '<tr class="'.($object->external_accident ?  ' fk_soc_field' : ' fk_soc_field hidden' ).'" style="'.($object->external_accident ? ' ' : ' display:none').'"><td class="minwidth400">'.$langs->trans("AccidentLocation").'</td>';
	print '<td>';
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'fk_soc', '', 1, 1, 0, '', 0, 'minwidth300');
	} else {
		print $form->select_company($object->fk_soc, 'fk_soc', '', 'SelectThirdParty', 1, 0, '', 0, 'minwidth300');
	}
	print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	print '</td></tr>';

	//Accident Date -- Date de l'accident
	print '<tr><td class="minwidth400"><label for="accident_date">'.$langs->trans("AccidentDate").'</label></td><td>';
	print $form->selectDate(dol_now('tzuser'), 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Description -- Description
	print '<tr class="content_field"><td><label for="content">'.$langs->trans("Description").'</label></td><td>';
	$doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
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
// Confirmation to delete
if ($action == 'deleteline') {
	$objectline->fetch($lineid);
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteAccidentWorkStop'), $langs->trans('ConfirmDeleteAccidentWorkStop', $objectline->ref), 'confirm_deleteLine', '', 0, 1);
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

	$counter = 0;

	$morecssGauge = 'inline-block floatright';
	$move_title_gauge = 1;

	$arrayAccident = array();
	$arrayAccident[] = $object->ref;
	$arrayAccident[] = $object->label;
	$arrayAccident[] = (!empty($object->accident_type) ? $object->accident_type : 0);
	$arrayAccident[] = $object->accident_date;
	$arrayAccident[] = $object->description;
	$arrayAccident[] = $object->photo;
	if (empty($object->external_accident)) {
		$arrayAccident[] = $object->fk_element;
	} else {
		$arrayAccident[] = $object->fk_soc;
	}
	$arrayAccident[] = $object->fk_user_victim;

	$maxnumber  = count($arrayAccident);

	foreach ($arrayAccident as $arrayAccidentData) {
		if (dol_strlen($arrayAccidentData) > 0 ) {
			$counter += 1;
		}
	}

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

	include_once './../../core/tpl/digiriskdolibarr_configuration_gauge_view.tpl.php';

	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element, $object).'</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '','',$morehtmlleft);

	print '<div class="div-table-responsive">';
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	//Unset for order
	unset($object->fields['label']);
	unset($object->fields['accident_type']);
	unset($object->fields['accident_date']);
	unset($object->fields['photo']);
	unset($object->fields['fk_project']);
	unset($object->fields['external_accident']);
	unset($object->fields['fk_soc']);
	unset($object->fields['fk_user_employer']);
	unset($object->fields['fk_user_victim']);
	unset($object->fields['fk_element']);

	//Label -- Libellé
	print '<tr><td class="titlefield">';
	print $langs->trans("Label");
	print '</td>';
	print '<td>';
	print $object->label;
	print '</td></tr>';

	//User Employer -- Responsable de la société
	print '<tr><td class="titlefield">';
	print $langs->trans("UserEmployer");
	print '</td>';
	print '<td>';
	$usertmp->fetch($object->fk_user_employer);
	if ($usertmp > 0) {
		print $usertmp->getNomUrl(1);
	}
	print '</td></tr>';

	//User Victim -- Victime de l'accident
	print '<tr><td class="titlefield">';
	print $langs->trans("UserVictim");
	print '</td>';
	print '<td>';
	$usertmp->fetch($object->fk_user_victim);
	if ($usertmp > 0) {
		print $usertmp->getNomUrl(1);
	}
	print '</td></tr>';

	//Accident type -- Type de l'accident
	print '<tr><td class="titlefield">';
	print $langs->trans("AccidentType");
	print '</td>';
	print '<td>';
	if ($object->accident_type == 0) {
		print $langs->trans('WorkAccidentStatement');
	} elseif ($object->accident_type == 1) {
		print $langs->trans('CommutingAccident');
	}
	print '</td></tr>';

	//Accident date -- Date de l'accident
	print '<tr><td class="titlefield">';
	print $langs->trans("AccidentDate");
	print '</td>';
	print '<td>';
	print dol_print_date($object->accident_date, 'dayhoursec');
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr><td class="titlefield">'.$langs->trans("AccidentLocation").'</td><td>';
	if (empty($object->external_accident)) {
		if ($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD == $object->fk_element) {
			$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
			print $digiriskstandard->getNomUrl(1, 'blank', 1);
		} else {
			$digiriskelement->fetch($object->fk_element);
			print $digiriskelement->getNomUrl(1, 'blank', 1);
		}
	} else {
		$thirdparty->fetch($object->fk_soc);
		print $thirdparty->getNomUrl(1);
	}
	print '</td></tr>';

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
		print '<td class="center" colspan="' . $colspan . '">' . $langs->trans('ActionsLine') . '</td>';
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
					print '<input type="number" name="workstop_days" class="minwidth150" value="' .  $item->workstop_days . '">';
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
					print ' &nbsp; <input type="submit" id ="cancelLine" class="button" name="cancelLine" value="'.$langs->trans("Cancel").'">';
					print '</td>';
					print '</tr>';

					if (is_object($objectline)) {
//						print $objectline->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
					}
					print '</form>';
				} elseif ($item->status == 1) {
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
			print '<input type="number" name="workstop_days" class="minwidth150" value="">';
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

	$morehtmlright = '<a href="' . dol_buildpath('/digiriskdolibarr/view/accident/accident_agenda.php', 1) . '?id=' . $object->id . '">';
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
