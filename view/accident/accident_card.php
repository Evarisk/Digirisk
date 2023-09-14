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

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident.lib.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/accident/mod_accident_standard.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/accident_workstop/mod_accident_workstop_standard.php';

global $conf, $db, $hookmanager, $langs, $mysoc, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOST('id', 'int');
$lineid              = GETPOST('lineid', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accidentcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');
$fromiduser          = GETPOST('fromiduser', 'int'); //element id

// Initialize technical objects
$object           = new Accident($db);
$signatory        = new SaturneSignature($db, $object->module, $object->element);
$objectline       = new AccidentWorkStop($db);
$contact          = new Contact($db);
$usertmp          = new User($db);
$thirdparty       = new Societe($db);
$extrafields      = new ExtraFields($db);
$digiriskelement  = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$project          = new Project($db);

// Load object
$object->fetch($id);

$hookmanager->initHooks(['accidentcard', 'globalcard']); // Note that conf->hooks_modules contains array

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity];

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->accident_investigation->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident_investigation->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident_investigation->delete;
saturne_check_access($permissiontoread);

$refAccidentMod         = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_ADDON($db);
$refAccidentWorkStopMod = new $conf->global->DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON($db);

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/accident/accident_list.php', 1) . (!empty($fromiduser) ? '?fromiduser=' . $fromiduser : '');

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage                                                                              = dol_buildpath('/digiriskdolibarr/view/accident/accident_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
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
		$accident_location  = GETPOST('accident_location');
		$ext_society_id     = GETPOST('fk_soc');

		// Initialize object accident
		$now                       = dol_now();
		$object->ref               = $refAccidentMod->getNextValue($object);
		$object->ref_ext           = 'digirisk_' . $object->ref;
		$object->date_creation     = $object->db->idate($now);
		$object->tms               = $now;
		$object->import_key        = "";
		$object->status            = Accident::STATUS_VALIDATED;
		$object->label             = $label;
		$object->description       = $description;
		$object->accident_type     = $accident_type;
		$object->external_accident = $external_accident;
		$object->accident_location = $accident_location;
		$object->fk_project        = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;

		$accident_date = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));

		$object->accident_date = $accident_date;
		$object->fk_soc        = $ext_society_id;

		switch ($external_accident) {
			case 1:
				if ($digiriskelement_id == 0) {
					$object->fk_standard = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
					$object->fk_element  = 0;
					$object->fk_soc      = 0;
				} else if ($digiriskelement_id > 0) {
					$object->fk_element  = $digiriskelement_id;
					$object->fk_standard = 0;
					$object->fk_soc      = 0;
				}
					break;
			case 2:
				$object->fk_element  = 0;
				$object->fk_standard = 0;
				$object->fk_soc      = $ext_society_id;
				$object->accident_location = '';
				break;
			case 3:
				$object->fk_element  = 0;
				$object->fk_standard = 0;
				$object->fk_soc      = 0;
				$object->accident_location = $accident_location;
				break;
		}
		$object->fk_user_employer = $user_employer_id;
		$object->fk_user_victim   = $user_victim_id;
		$object->fk_user_creat    = $user->id ?: 1;

		// Check parameters
		if ($user_victim_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UserVictim')), [], 'errors');
			$error++;
		}

		if ($user_employer_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UserEmployer')), [], 'errors');
			$error++;
		}

		// Submit file
		if (!empty($conf->global->MAIN_UPLOAD_DOC)) {
			if (!empty($_FILES) && ! empty($_FILES['userfile']['name'][0])) {
				if (is_array($_FILES['userfile']['tmp_name'])) {
					$userfiles = $_FILES['userfile']['tmp_name'];
				} else {
					$userfiles = array($_FILES['userfile']['tmp_name']);
				}

				foreach ($userfiles as $key => $userfile) {
					if (empty($_FILES['userfile']['tmp_name'][$key])) {
						$error++;
						if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
							setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
						}
					}
				}

				$filedir = $upload_dir . '/accident/' . $object->ref;

				if (!file_exists($filedir)) {
					if (dol_mkdir($filedir) < 0) {
						$object->error = $langs->transnoentities("ErrorCanNotCreateDir", $filedir);
						$error++;
					}
				}

				if (!$error) {
					dol_mkdir($filedir);
					if (!empty($filedir)) {
						$result        = digirisk_dol_add_file_process($filedir, 0, 1, 'userfile', '', null, '', 1, $object);
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
				$signatory->setSignatory($object->id, 'accident', 'user', array($usertmp->id), 'ACC_USER_EMPLOYER');

				// Creation Accident OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Creation Accident KO
				if (!empty($object->errors)) {
					setEventMessages('', $object->errors, 'errors');
				} else {
					setEventMessages($object->error, [], 'errors');
				}
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
		$description        = GETPOST('description', 'restricthtml');
		$accident_type      = GETPOST('accident_type');
		$external_accident  = GETPOST('external_accident');
		$accident_location  = GETPOST('accident_location');
		$ext_society_id     = GETPOST('fk_soc');

		// Initialize object accident
		$now                       = dol_now();
		$object->tms               = $now;
		$object->label             = $label;
		$object->description       = $description;
		$object->accident_type     = $accident_type;
		$object->external_accident = $external_accident;
		$object->accident_location = $accident_location;
		$object->fk_project        = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;

		$accident_date = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));

		$object->accident_date = $accident_date;
		$object->fk_soc        = $ext_society_id;

		switch ($external_accident) {
			case 1:
				if ($digiriskelement_id == 0) {
					$object->fk_standard = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
					$object->fk_element  = 0;
					$object->fk_soc      = 0;
				} else if ($digiriskelement_id > 0) {
					$object->fk_element  = $digiriskelement_id;
					$object->fk_standard = 0;
					$object->fk_soc      = 0;
				}
				break;
			case 2:
				$object->fk_element  = 0;
				$object->fk_standard = 0;
				$object->fk_soc      = $ext_society_id;
				$object->accident_location = '';
				break;
			case 3:
				$object->fk_element  = 0;
				$object->fk_standard = 0;
				$object->fk_soc      = 0;
				$object->accident_location = $accident_location;
				break;
		}
		$object->fk_user_victim   = $user_victim_id;
		$object->fk_user_employer = $user_employer_id;
		$object->fk_user_creat    = $user->id > 0 ? $user->id : 1;

		// Check parameters
		if ($user_victim_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UserVictim')), [], 'errors');
			$error++;
		}

		if ($user_employer_id < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UserEmployer')), [], 'errors');
			$error++;
		}

		// Submit file
		if (!empty($conf->global->MAIN_UPLOAD_DOC)) {
			if (!empty($_FILES) && !empty($_FILES['userfile']['name'][0])) {
				if (is_array($_FILES['userfile']['tmp_name'])) {
					$userfiles = $_FILES['userfile']['tmp_name'];
				} else {
					$userfiles = array($_FILES['userfile']['tmp_name']);
				}

				foreach ($userfiles as $key => $userfile) {
					if (empty($_FILES['userfile']['tmp_name'][$key])) {
						$error++;
						if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
							setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
						}
					}
				}

				$filedir = $upload_dir . '/accident/' . $object->ref;

				if (!file_exists($filedir)) {
					if (dol_mkdir($filedir) < 0) {
						$object->error = $langs->transnoentities("ErrorCanNotCreateDir", $filedir);
						$error++;
					}
				}

				if (!$error) {
					dol_mkdir($filedir);
					if (!empty($filedir)) {
						$result        = digirisk_dol_add_file_process($filedir, 0, 1, 'userfile', '', null, '', 1, $object);
						$object->photo = $_FILES['userfile']['name'][0];
					}
				}
			}
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result > 0) {
				if (empty($object->fk_user_employer)) {
					$usertmp->fetch('', $mysoc->managers, $mysoc->id, 0, $conf->entity);
				} else {
					$usertmp->fetch($object->fk_user_employer);
				}
				$signatory->deleteSignatoriesSignatures($object->id, 'accident');
				$signatory->setSignatory($object->id, 'accident', 'user', array($usertmp->id), 'ACC_USER_EMPLOYER');

				// Update Accident OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Update Accident KO
				if ( ! empty($object->errors)) {
					setEventMessages('', $object->errors, 'errors');
				} else {
					setEventMessages($object->error, [], 'errors');
				}
			}
		} else {
			$action = 'edit';
		}
	}

	// Action to add line
	if ($action == 'addLine' && $permissiontoadd) {
		// Get parameters
		$workstop_days    = GETPOST('workstop_days');
		$parent_id        = GETPOST('parent_id');
		$declaration_link = GETPOST('declarationLink');


		// Initialize object accident line
		$now                          = dol_now();
		$objectline->date_creation    = $object->db->idate($now);
		$objectline->status           = 1;
		$objectline->ref              = $refAccidentWorkStopMod->getNextValue($objectline);
		$objectline->entity           = $conf->entity;
		$objectline->workstop_days    = $workstop_days;
		$objectline->declaration_link = $declaration_link;

		$date_start_workstop = dol_mktime(GETPOST('datestarthour', 'int'), GETPOST('datestartmin', 'int'), 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
		$date_end_workstop   = dol_mktime(GETPOST('dateendhour', 'int'), GETPOST('dateendmin', 'int'), 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));

		$objectline->date_start_workstop = $date_start_workstop;
		$objectline->date_end_workstop   = $date_end_workstop;
		$objectline->fk_accident         = $parent_id;

		// Check parameters
		if ($workstop_days <= 0) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('WorkStopDays')), null, 'errors');
			$error++;
		}

		if (!$error) {
			$result = $objectline->create($user, false);
			if ($result > 0) {
				// Creation accident line OK
				rename($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident/' . $object->ref . '/workstop/temp/', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident/' . $object->ref . '/workstop/' . $objectline->ref);

				setEventMessages($langs->trans('AddAccidentWorkStop') . ' ' . $object->ref, []);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Creation accident line KO
				if (!empty($objectline->errors)) {
					setEventMessages('', $objectline->errors, 'errors');
				} else {
					setEventMessages($objectline->error, [], 'errors');
				}
			}
		}
	}

	// Action to update line
	if ($action == 'updateLine' && $permissiontoadd) {
		// Get parameters
		$workstop_days    = GETPOST('workstop_days');
		$parent_id        = GETPOST('parent_id');
		$declaration_link = GETPOST('declarationLink');

		$objectline->fetch($lineid);

		// Initialize object accident line
		$objectline->workstop_days = $workstop_days;

		$date_start_workstop = dol_mktime(GETPOST('datestarthour', 'int'), GETPOST('datestartmin', 'int'), 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
		$date_end_workstop   = dol_mktime(GETPOST('dateendhour', 'int'), GETPOST('dateendmin', 'int'), 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));

		$objectline->date_start_workstop = $date_start_workstop;
		$objectline->date_end_workstop   = $date_end_workstop;
		$objectline->fk_accident         = $parent_id;
		$objectline->declaration_link    = $declaration_link;

		// Check parameters
		if ($workstop_days <= 0) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('WorkStopDays')), [], 'errors');
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=editline&lineid=' .  $lineid);
			exit;
			$error++;
		}

		if (!$error) {
			$result = $objectline->update($user, false);
			if ($result > 0) {
				// Update accident line OK
				setEventMessages($langs->trans('UpdateAccidentWorkStop') . ' ' . $object->ref, []);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Update accident line KO
				if (!empty($object->errors)) {
					setEventMessages('', $object->errors, 'errors');
				} else {
					setEventMessages($object->error, [], 'errors');
				}
			}
		}
	}

	// Action to delete line
	if ($action == 'confirm_deleteLine' && GETPOST("confirm") == "yes" && $permissiontodelete) {
		$objectline->fetch($lineid);

		$objectline->status = 0;

		$pathPhoto = $upload_dir . '/accident/' . $object->ref . '/workstop/' . $objectline->ref;
		$filedir   = $upload_dir . '/accident/' . $object->ref . '/archive/workstop/';

		if (!file_exists($filedir)) {
			if (dol_mkdir($filedir) < 0) {
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
			setEventMessages($langs->trans('DeleteAccidentWorkStop') . ' ' . $object->ref, []);
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Deletion accident line KO
			if (!empty($object->errors)) {
				setEventMessages('', $object->errors, 'errors');
			} else {
				setEventMessages($object->error, [], 'errors');
			}
		}
	}

	// Add file in accident workstop
	if ($action == 'sendfile') {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$objectlineid = GETPOST('objectlineid');
		if ($objectlineid > 0) {
			$objectline->fetch($objectlineid);
			$folder = $objectline->ref;
		} else {
			$folder = 'temp';
		}
		$object->fetch($id);

		if (!empty($_FILES) && ! empty($_FILES['files']['name'][0])) {
			if (is_array($_FILES['files']['tmp_name'])) {
				$files = $_FILES['files']['tmp_name'];
			}
			else {
				$files = array($_FILES['files']['tmp_name']);
			}

			foreach ($files as $key => $file) {
				if (empty($_FILES['files']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['files']['error'][$key] == 1 || $_FILES['files']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
					}
				}
			}

			$filedir = $conf->digiriskdolibarr->multidir_output[$object->entity ?? 1] . '/accident/' . $object->ref . '/workstop/' . $folder;
			if (!file_exists($filedir)) {
				if (dol_mkdir($filedir) < 0) {
					$object->error = $langs->transnoentities("ErrorCanNotCreateDir", $filedir);
					$error++;
				}
			}

			if (!$error) {
				dol_mkdir($filedir);
				if (!empty($filedir)) {
					$result = digirisk_dol_add_file_process($filedir, 0, 1, 'files', '', null, '', 1, $object);
				}
			}
		}
	}

	if ($action == 'removefile') {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$filetodelete = GETPOST('filetodelete');
		$objectlineid = GETPOST('objectlineid');
		if ($objectlineid > 0) {
			$objectline->fetch($objectlineid);
			$folder = $objectline->ref;
		} else {
			$folder = 'temp';
		}

		$accident_upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident/' . $object->ref . '/workstop/' . $folder . '/';

		//Delete file
		if (file_exists($accident_upload_dir . '/' . $filetodelete)) {
			unlink($accident_upload_dir . '/' . $filetodelete);
		}

		//Delete file thumbs
		$thumbs_names = getAllThumbsNames($filetodelete);
		if (!empty($thumbs_names)) {
			foreach($thumbs_names as $thumb_name) {
				$thumb_fullname  = $accident_upload_dir . 'thumbs/' . $thumb_name;
				if (file_exists($thumb_fullname)) {
					unlink($thumb_fullname);
				}
			}
		}

		$action = '';
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	require_once DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

	// Action confirm_lock, confirm_archive.
	require_once __DIR__ . '/../../../saturne/core/tpl/signature/signature_action_workflow.tpl.php';
}

/*
 * View
 */

$form    = new Form($db);
$title   = $langs->trans("Accident");
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

if ($conf->browser->layout == 'phone') {
	$onPhone = 1;
} else {
	$onPhone = 0;
}

saturne_header(0,'', $title, $helpUrl);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewAccident"), '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate accident-table">' . "\n";

	//Label -- Libellé
	print '<tr><td class="minwidth400">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . GETPOST('label') . '">';
	print '</td></tr>';

	//User Employer -- Utilisateur responsable de la société
	if ( ! empty($mysoc->managers)) {
		$usertmp->fetch('', $mysoc->managers, $mysoc->id, 0, $conf->entity);
	}
	$userlist = $form->select_dolusers( GETPOST('fk_user_employer') ?: ($usertmp->id > 0 ? $usertmp->id : $user->id), '', 0, null, 0, '', '', '', 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserEmployer', 'UserEmployer_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_employer', $userlist, GETPOST('fk_user_employer') ?: ($usertmp->id > 0 ? $usertmp->id : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//User Victim -- Utilisateur victime de l'accident
	$userlist = $form->select_dolusers((GETPOST('fk_user_victim') ?: $user->id), '', 0, null, 0, '', '', '', 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserVictim', 'UserVictim_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_victim', $userlist, ( ! empty(GETPOST('fk_user_victim')) ? GETPOST('fk_user_victim') : (! empty(GETPOST('fromiduser')) ? $fromiduser : $user->id)), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//AccidentType
	print '<tr><td class="minwidth400">' . $langs->trans("AccidentType") . '</td><td>';
	print $form->selectarray('accident_type', array('0' => $langs->trans('WorkAccidentStatement'), '1' => $langs->trans('CommutingAccident')), '', 0, 0, 0, '', 0, 0, 0, '', 'minwidth300', 1);
	print '</td></tr>';

	//ExternalAccident
	print '<tr><td class="minwidth400">' . $langs->trans("ExternalAccident") . '</td><td>';
	print $form->selectarray('external_accident', array('1' => $langs->trans('No'), '2' => $langs->trans('Yes'), '3' => $langs->trans('Other')), '', 0, 0, 0, '', 0, 0, 0, '', 'minwidth300', 1);
	print '</td></tr>';

	//FkElement -- Lieu de l'accident - DigiriskElement
	print '<tr class="fk_element_field"><td class="minwidth400">' . $langs->trans("AccidentLocation") . '</td><td>';
	print $digiriskelement->select_digiriskelement_list(( ! empty(GETPOST('fromid')) ? GETPOST('fromid') : $object->fk_element), 'fk_element', '', 0, 0, [], 0, 0, 'minwidth300', 0, false, 0);
	print '</td></tr>';

	//FkSoc -- Lieu de l'accident - Société extérieure
	print '<tr class="fk_soc_field hidden"' . (GETPOST('fk_soc') > 0 ? '' : 'style="display:none"') . '><td class="minwidth400">' . $langs->trans("AccidentLocation") . '</td>';
	print '<td>';
	//For external user force the company to user company
	if ( ! empty($user->socid)) {
		print $form->select_company($user->socid, 'fk_soc', '', 1, 1, 0, '', 0, 'minwidth300');
	} else {
		print $form->select_company('', 'fk_soc', '', 'SelectThirdParty', 1, 0, '', 0, 'minwidth300');
	}
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	//AccidentLocation -- lieu de l'accident
	print '<tr class="accident_location_field hidden" ' . (GETPOST('accident_location') ? '' : 'style="display:none"') . '><td class="minwidth400">' . $langs->trans("AccidentLocation") . '</td><td>';
	$doleditor = new DolEditor('accident_location', GETPOST('accident_location'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//Accident Date -- Date de l'accident
	print '<tr><td class="minwidth400"><label for="accident_date">' . $langs->trans("AccidentDate") . '</label></td><td>';
	print $form->selectDate(GETPOST('dateo') ? dol_mktime(GETPOST('dateohour', 'int'),GETPOST('dateomin', 'int'),0,GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int')) : dol_now('tzuser'), 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Description -- Description
	print '<tr class="content_field"><td><label for="content">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', GETPOST('description'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr><td class="titlefield">' . $form->editfieldkey($langs->trans("Photo"), 'Photo', '', $object, 0) . '</td><td>';
	print '<input class="flat" type="file" name="userfile[]" id="Photo" />';
	print '</td></tr>';

	// Other attributes
	//  include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" id ="actionButtonCreate" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelCreate" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("ModifyAccident"), '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit accident-table">' . "\n";

	//Ref -- Ref
	print '<tr><td class="fieldrequired minwidth400">' . $langs->trans("Ref") . '</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="minwidth400">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . $object->label . '">';
	print '</td></tr>';

	//User Employer -- Utilisateur responsable de la société
	$userlist = $form->select_dolusers(( ! empty($object->fk_user_employer) ? $object->fk_user_employer : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserEmployer', 'UserEmployer_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_employer', $userlist, ( ! empty($object->fk_user_employer) ? $object->fk_user_employer : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//User Victim -- Utilisateur victime de l'accient
	$userlist = $form->select_dolusers(( ! empty($object->fk_user_victim) ? $object->fk_user_victim : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('UserVictim', 'UserVictim_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('fk_user_victim', $userlist, ( ! empty($object->fk_user_victim) ? $object->fk_user_victim : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//AccidentType
	print '<tr><td class="minwidth400">' . $langs->trans("AccidentType") . '</td><td>';
	print $form->selectarray('accident_type', array('0' => $langs->trans('WorkAccidentStatement'), '1' => $langs->trans('CommutingAccident')), $object->accident_type, 0, 0, 0, '', 0, 0, 0, '', 'minwidth300', 1);
	print '</td></tr>';

	//ExternalAccident
	print '<tr><td class="minwidth400">' . $langs->trans("ExternalAccident") . '</td><td>';
	print $form->selectarray('external_accident', array('1' => $langs->trans('No'), '2' => $langs->trans('Yes'), '3' => $langs->trans('Other')), $object->external_accident, 0, 0, 0, '', 0, 0, 0, '', 'minwidth300', 1);
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr class="' . (($object->external_accident == 1) ? ' fk_element_field' : ' fk_element_field hidden' ) . '" style="' . (($object->external_accident == 1) ? ' ' : ' display:none') . '"><td>' . $langs->trans("AccidentLocation") . '</td><td>';
	print $digiriskelement->select_digiriskelement_list($object->fk_element, 'fk_element', '', 0, 0, [], 0, 0, 'minwidth300', 0, false, 0);
	print '</td></tr>';

	//FkSoc -- Société extérieure
	print '<tr class="' . (($object->external_accident == 2) ? ' fk_soc_field' : ' fk_soc_field hidden' ) . '" style="' . (($object->external_accident == 2) ? ' ' : ' display:none') . '"><td class="minwidth400">' . $langs->trans("AccidentLocation") . '</td>';
	print '<td>';
	//For external user force the company to user company
	if ( ! empty($user->socid)) {
		print $form->select_company($user->socid, 'fk_soc', '', 1, 1, 0, '', 0, 'minwidth300');
	} else {
		print $form->select_company($object->fk_soc, 'fk_soc', '', 'SelectThirdParty', 1, 0, '', 0, 'minwidth300');
	}
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	//AccidentLocation -- lieu de l'accident
	print '<tr class="' . (($object->external_accident == 3) ? ' accident_location_field' : ' accident_location_field hidden' ) . '" style="' . (($object->external_accident == 3) ? ' ' : ' display:none') . '"><td class="minwidth400">' . $langs->trans("AccidentLocation") . '</td><td>';
	$doleditor = new DolEditor('accident_location', $object->accident_location, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//Accident Date -- Date de l'accident
	print '<tr><td class="minwidth400"><label for="accident_date">' . $langs->trans("AccidentDate") . '</label></td><td>';
	print $form->selectDate(GETPOST('dateo') ? dol_mktime(GETPOST('dateohour', 'int'),GETPOST('dateomin', 'int'),0,GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int')) : ($object->accident_date ?: dol_now('tzuser')), 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Description -- Description
	print '<tr class="content_field"><td><label for="content">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr><td class="titlefield">' . $form->editfieldkey($langs->trans("Photo"), 'Photo', '', $object, 0) . '</td><td>';
	print '<input class="flat" type="file" name="userfile[]" id="Photo" />';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';
	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {
	$counter = 0;

	$morecssGauge     = 'inline-block floatright';
	$move_title_gauge = 1;

	$arrayAccident   = [];
	$arrayAccident[] = $object->ref;
	$arrayAccident[] = $object->label;
	$arrayAccident[] = (!empty($object->accident_type) ? $object->accident_type : 0);
	$arrayAccident[] = $object->accident_date;
	$arrayAccident[] = $object->description;
	switch ($object->external_accident) {
		case 1:
			$arrayAccident[] = $object->fk_element > 0 ? $object->fk_element : $object->fk_standard;
			break;
		case 2:
			$arrayAccident[] = $object->fk_soc;
			break;
		case 3:
			$arrayAccident[] = $object->accident_location;
			break;
	}
	$arrayAccident[] = $object->fk_user_victim;

	$maxnumber = count($arrayAccident);

	foreach ($arrayAccident as $arrayAccidentData) {
		if (dol_strlen($arrayAccidentData) > 0 ) {
			$counter += 1;
		}
	}

	// Object card
	// ------------------------------------------------------------
	$object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);

	//Number workstop days
	$accidentlines     = $objectline->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $object->id . ' AND t.status >= 0']);
	$totalworkstopdays = 0;

	if (!empty($accidentlines) && $accidentlines > 0) {
		foreach ($accidentlines as $accidentline) {
			if ($accidentline->status > 0) {
				$totalworkstopdays += $accidentline->workstop_days;
			}
		}
		$morehtmlref      = $langs->trans('TotalWorkStopDays') . ' : ' . $totalworkstopdays;
		$lastaccidentline = end($accidentlines);
		$morehtmlref     .= '<br>' . $langs->trans('ReturnWorkDate') . ' : ' . dol_print_date($lastaccidentline->date_end_workstop, 'dayhour');
	} else {
		$morehtmlref = $langs->trans('RegisterAccident');
	}
	$morehtmlref .= '<br>';

	include_once './../../core/tpl/digiriskdolibarr_configuration_gauge_view.tpl.php';

	//$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">' . digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element, 'small', 5, 0, 0, 0, 80, 80, 0, 0, 0, $object->element, $object) . '</div>';

	$linkback = '<a href="' . dol_buildpath('/digiriskdolibarr/view/accident/accident_list.php', 1) . '">' . $langs->trans("BackToList") . '</a>';

	saturne_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

	$formConfirm = '';

	// Confirmation to delete
	if ($action == 'delete' && $permissiontodelete) {
		$formConfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id=' . $object->id, $langs->trans('DeleteAccident'), $langs->trans('ConfirmDeleteAccident'), 'confirm_delete', '', 0, 1);
	}

	// Confirmation to delete line
	if ($action == 'deleteline') {
		$objectline->fetch($lineid);
		$formConfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteAccidentWorkStop'), $langs->trans('ConfirmDeleteAccidentWorkStop', $objectline->ref), 'confirm_deleteLine', '', 0, 1);
	}

	// Clone confirmation
	if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_clone', '', 'yes', 'actionButtonClone', 350, 600);
	}

	// Confirmation to lock
	if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Call Hook formConfirm.
	$parameters = ['formConfirm' => $formConfirm];
	$reshook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook.

	if (empty($reshook)) {
		$formConfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formConfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formConfirm;

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
	unset($object->fields['accident_location']);
	unset($object->fields['fk_soc']);
	unset($object->fields['fk_user_employer']);
	unset($object->fields['fk_user_victim']);
	unset($object->fields['fk_element']);

	//Label -- Libellé
	print '<tr><td class="titlefield">';
	print $form->textwithpicto($langs->trans("Label"), $langs->trans("GaugeCounter"), 1, 'info');
	print '</td>';
	print '<td>';
	print $object->label;
	print '</td></tr>';

	//User Employer -- Responsable de la société
	print '<tr><td class="titlefield">';
	print $form->textwithpicto($langs->trans("UserEmployer"), $langs->trans("GaugeCounter"), 1, 'info');
	print '</td>';
	print '<td>';
	$usertmp->fetch($object->fk_user_employer);
	if ($usertmp > 0) {
		print $usertmp->getNomUrl(1);
	}
	print '</td></tr>';

	//User Victim -- Victime de l'accident
	print '<tr><td class="titlefield">';
	print $form->textwithpicto($langs->trans("UserVictim"), $langs->trans("GaugeCounter"), 1, 'info');
	print '</td>';
	print '<td>';
	$usertmp->fetch($object->fk_user_victim);
	if ($usertmp > 0) {
		print $usertmp->getNomUrl(1);
	}
	print '</td></tr>';

	//Accident type -- Type de l'accident
	print '<tr><td class="titlefield">';
	print $form->textwithpicto($langs->trans("AccidentType"), $langs->trans("GaugeCounter"), 1, 'info');
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
	print $form->textwithpicto($langs->trans("AccidentDate"), $langs->trans("GaugeCounter"), 1, 'info');
	print '</td>';
	print '<td>';
	print dol_print_date($object->accident_date, 'dayhoursec');
	print '</td></tr>';

	//AccidentLocation -- Lieu de l'accident
	print '<tr><td class="titlefield">';
	print $form->textwithpicto($langs->trans("AccidentLocation"), $langs->trans("GaugeCounter"), 1, 'info');
	print '</td>';
	print '<td>';
	switch ($object->external_accident) {
		case 1:
			if ($object->fk_standard > 0) {
				$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
				print $digiriskstandard->getNomUrl(1, 'blank', 1);
			} else if ($object->fk_element > 0) {
				$digiriskelement->fetch($object->fk_element);
				print $digiriskelement->getNomUrl(1, 'blank', 1);
			}
			break;
		case 2:
			$thirdparty->fetch($object->fk_soc);
			print $thirdparty->getNomUrl(1);
			break;
		case 3:
			print $object->accident_location;
			break;
	}
	print '</td></tr>';

	//Description -- Description
	print '<tr><td class="titlefield">';
	print $form->textwithpicto($langs->trans("Description"), $langs->trans("GaugeCounter"), 1, 'info');
	print '</td>';
	print '<td>';
	print $object->description;
	print '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

	print dol_get_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >';
		$parameters = [];
		$reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Edit
			$displayButton = $onPhone ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->trans('Modify');
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit' . '">' . $displayButton . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
			}

			// Lock.
			$displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
			if ($object->status == Accident::STATUS_VALIDATED) {
				print '<span class="butAction" id="actionButtonLock">' . $displayButton . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft')) . '">' . $displayButton . '</span>';
			}

			// Create Investigation.
			$displayButton = $onPhone ? '<i class="fas fa-search-plus fa-2x"></i>' : '<i class="fas fa-search-plus"></i> ' . $langs->trans('AccidentInvestigation');
			if ($object->status == $object::STATUS_LOCKED) {
				print '<a class="butAction" id="actionButtonCreateInvestigation" href="'. dol_buildpath('/custom/digiriskdolibarr/view/accident_investigation/accident_investigation_card.php?action=create&fk_accident=' . $id, 1) .'">' . $displayButton . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLocked', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
			}

			// Clone.
			$displayButton = $onPhone ? '<i class="fas fa-clone fa-2x"></i>' : '<i class="fas fa-clone"></i>' . ' ' . $langs->trans('ToClone');
			print '<span class="butAction" id="actionButtonClone" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone' . '">' . $displayButton . '</span>';

			// Delete (need delete permission, or if draft, just need create/modify permission).
			$displayButton = $onPhone ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->trans('Delete');
			print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete);
		}
		print '</div>';

		// Accident Lines
		$accidentlines = $objectline->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $object->id . ' AND t.status >= 0']);

		if (($object->status == Accident::STATUS_VALIDATED) || (!empty($accidentlines))) {
			// ACCIDENT LINES
			print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
			print load_fiche_titre($langs->trans("AccidentRiskList"), '', '');
			print '<table id="tablelines" class="noborder noshadow" width="100%">';

			// Define colspan for the button 'Add'
			$colspan = 3; // Columns: total ht + col edit + col delete
			$img_extensions = ['png', 'jpg', 'jpeg'];

			print '<tr class="liste_titre">';
			print '<td><span>' . $langs->trans('Ref.') . '</span></td>';
			print '<td>' . '<b>' . $langs->trans('WorkStopDays') . '</b><span style="color:red"> *</span>' . '</td>';
			print '<td>' . $langs->trans('DateStartWorkStop') . '</td>';
			print '<td>' . $langs->trans('DateEndWorkStop') . '</td>';
			print '<td>' . $langs->trans('WorkStopDocument') . '</td>';
			print '<td class="center" colspan="' . $colspan . '">' . $langs->trans('ActionsLine') . '</td>';
			print '</tr>';

			if ( ! empty($accidentlines) && $accidentlines > 0) {
				foreach ($accidentlines as $key => $item) {
					//action edit
					if (($action == 'editline' || $subaction == 'editline') && $lineid == $key) {
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
						print '<input type="number" name="workstop_days" class="minwidth150" value="' . $item->workstop_days . '">';
						print '</td>';

						$coldisplay++;
						print '<td>';
						print $form->selectDate($item->date_start_workstop, 'datestart', 1, 1, 0, '', 1);
						print '</td>';

						$coldisplay++;
						print '<td>';
						print $form->selectDate($item->date_end_workstop, 'dateend', 1, 1, 0, '', 1);
						print '</td>';

						$coldisplay++;
						print '<td>';
						print '<input name="declarationLink" value="'. (GETPOST('declarationLink') ?: $item->declaration_link) .'">';
						print '</td>';

						$coldisplay += $colspan;
						print '<td class="center" colspan="' . $colspan . '">';
						print '<input type="submit" class="button" value="' . $langs->trans('Save') . '" name="updateLine" id="updateLine">';
						print ' &nbsp; <input type="submit" id ="cancelLine" class="button" name="cancelLine" value="' . $langs->trans("Cancel") . '">';
						print '</td>';
						print '</tr>';
						print '</form>';
					//action view
					} elseif ($item->status == 1) {
						print '<td>';
						print $item->ref;
						print '</td>';

						$coldisplay++;
						print '<td>';
						print $item->workstop_days;
						print '</td>';

						$coldisplay++;
						print '<td>';
						print dol_print_date($item->date_start_workstop, 'dayhour');
						print '</td>';

						$coldisplay++;
						print '<td>';
						print dol_print_date($item->date_end_workstop, 'dayhour');
						print '</td>';

						$coldisplay++;
						print '<td>';
						$is_link = dol_is_url($item->declaration_link);
						print ($is_link ? '<a target="_blank" href="'. $item->declaration_link .'">' : '') . $item->declaration_link . ($is_link ? '</a>' : '') ;
						print '</td>';

						$coldisplay += $colspan;

						//Actions buttons
						if ($object->status == Accident::STATUS_VALIDATED) {
							print '<td class="center">';
							$coldisplay++;
							print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=editline&amp;lineid=' . $item->id . '" style="padding-right: 20px"><i class="fas fa-pencil-alt" style="color: #666"></i></a>';
							print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=deleteline&amp;lineid=' . $item->id . '&amp;token=' . newToken() . '">';
							print img_delete();
							print '</a>';
							print '</td>';
						} else {
							print '<td class="center">';
							print '-';
							print '</td>';
						}
						print '</tr>';
					}
				}
				print '</tr>';
			}
		}
		//action create
		if ($object->status == Accident::STATUS_VALIDATED && $permissiontoadd && $action != 'editline') {
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
			print '<td>';
			print $form->selectDate(dol_now('tzuser'), 'datestart', 1, 1, 0, '', 1);
			print '</td>';

			$coldisplay++;
			print '<td>';
			print $form->selectDate(dol_now('tzuser'), 'dateend', 1, 1, 0, '', 1);
			print '</td>';

			$coldisplay++;
			print '<td class="maxwidth100">';
			print '<input name="declarationLink" id="declarationLink" value="'. GETPOST('declarationLink') . '">';
			print '</td>';

			$coldisplay += $colspan;
			print '<td class="center" colspan="' . $colspan . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">';
			print '</td>';
			print '</tr>';

			print '</form>';
		}
		print '</table>';
		print '</div>';
	}

	if ($permissiontoadd) {
		print '</div><div class="fichehalfright">';
	} else {
		print '</div><div class="">';
	}

	$moreHtmlCenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/digiriskdolibarr/view/accident/accident_agenda.php', 1) . '?id=' . $object->id);

	// List of actions on element.
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	$formActions = new FormActions($db);
	$formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10, '', $moreHtmlCenter);

	print '</div></div></div>';
}

// End of page
llxFooter();
$db->close();
