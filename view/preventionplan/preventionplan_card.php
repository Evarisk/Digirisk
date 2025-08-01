<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

// Load DigiriskDolibarr libraries.
require_once __DIR__ . '/../../class/digiriskdocuments.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/preventionplandocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_preventionplan.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs(['other', 'mails']);

// Get parameters
$id                  = GETPOST('id', 'int');
$lineid              = GETPOST('lineid', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'preventionplancard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object             = new PreventionPlan($db);
$preventionplandet  = new PreventionPlanLine($db);
$signatory          = new SaturneSignature($db, $moduleNameLowerCase, $object->element);
$document           = new PreventionPlanDocument($db);
$digiriskelement    = new DigiriskElement($db);
$digiriskelementtmp = new DigiriskElement($db);
$digiriskresources  = new DigiriskResources($db);
$risk               = new Risk($db);
$contact            = new Contact($db);
$usertmp            = new User($db);
$extrafields        = new ExtraFields($db);
$resources          = new DigiriskResources($db);
$thirdparty         = new Societe($db);
$project            = new Project($db);

// Load object
$object->fetch($id);

$deletedElements = $digiriskelement->getMultiEntityTrashList();
if (empty($deletedElements)) {
	$deletedElements = [0];
}

// Load resources
$allLinks = $resources->fetchDigiriskResources();

// Load numbering modules
$numberingModules = [
	'digiriskelement/' . $object->element            => $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON,
	'digiriskelement/' . $preventionplandet->element => $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON,
];

list($refPreventionPlanMod, $refPreventionPlanDetMod) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$extrafields->fetch_name_optionals_label($preventionplandet->table_element);

// Initialize hooks
$hookmanager->initHooks(array('preventionplancard', 'globalcard')); // Note that conf->hooks_modules contains array

// Get files upload dir
$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->preventionplan->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->preventionplan->write;
$permissiontodelete = $user->rights->digiriskdolibarr->preventionplan->delete;

saturne_check_access($permissiontoadd, $object);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/preventionplan/preventionplan_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage                                                                              = dol_buildpath('/digiriskdolibarr/view/preventionplan/preventionplan_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
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
		$project                  = GETPOST('fk_project');
		$masterWorkerId           = GETPOST('maitre_oeuvre');
		$extSocietyId             = GETPOST('ext_society');
		$extResponsibleId         = GETPOST('ext_society_responsible');
		$extIntervenantsIds       = GETPOST('ext_intervenants');
		$labourInspectorId        = GETPOST('labour_inspector');
		$labourInspectorContactId = GETPOST('labour_inspector_contact');
		$label                    = GETPOST('label');
		$priorVisitBool           = GETPOST('prior_visit_bool');
		$priorVisitText           = GETPOST('prior_visit_text');
		$cssctInterventation      = GETPOST('cssct_intervention');

		// Initialize object preventionplan
		$now                   = dol_now();
		$object->ref           = $refPreventionPlanMod->getNextValue($object);
		$object->ref_ext       = 'digirisk_' . $object->ref;
		$object->date_creation = $object->db->idate($now);
		$object->tms           = $now;
		$object->import_key    = "";
		$object->label         = $label;
		$object->status        = PreventionPlan::STATUS_DRAFT;
		$object->fk_project    = $project;

		$date_start       = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));
		$date_end         = dol_mktime(GETPOST('dateehour', 'int'), GETPOST('dateemin', 'int'), 0, GETPOST('dateemonth', 'int'), GETPOST('dateeday', 'int'), GETPOST('dateeyear', 'int'));
		$prior_visit_date = dol_mktime(GETPOST('dateihour', 'int'), GETPOST('dateimin', 'int'), 0, GETPOST('dateimonth', 'int'), GETPOST('dateiday', 'int'), GETPOST('dateiyear', 'int'));

		$object->date_start = $date_start;
		$object->date_end   = $date_end;

		$object->prior_visit_bool = $priorVisitBool;
		if ($priorVisitBool) {
			$object->prior_visit_text = $priorVisitText;
			$object->prior_visit_date = $prior_visit_date;
		}

		$object->cssct_intervention = $cssctInterventation;

		$object->fk_user_creat = $user->id ? $user->id : 1;

		// Check parameters
		if ($masterWorkerId < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MasterWorker')), null, 'errors');
			$error++;
		} else {
			$usertmp->fetch($masterWorkerId);
		}

		if ($extSocietyId < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSociety')), null, 'errors');
			$error++;
		}

		if (is_array($extResponsibleId)) {
			if (empty(array_filter($extResponsibleId))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
				$error++;
			}
		} elseif (empty($extResponsibleId)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
			$error++;
		}

		if ($labourInspectorId < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspectorSociety')), null, 'errors');
			$error++;
		}

		if (is_array($labourInspectorContactId)) {
			if (empty(array_filter($labourInspectorContactId))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
				$error++;
			}
		} elseif (empty($labourInspectorContactId)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
			$error++;
		}

		if ( ! $error) {
			$result = $object->create($user, true);
			if ($result > 0) {
                if (isModEnabled('categorie')) {
                    $categories = GETPOST('categories', 'array');
                    if (method_exists($object, 'setCategories')) {
                        $object->setCategories($categories);
                    }
                }
				$object->setInProgress($user, true);
				$digiriskresources->setDigiriskResources($db, $user->id, 'ExtSociety', 'societe', array($extSocietyId), $conf->entity, 'preventionplan', $object->id, 1);
				$digiriskresources->setDigiriskResources($db, $user->id, 'LabourInspector', 'societe', array($labourInspectorId), $conf->entity, 'preventionplan', $object->id, 1);
				$digiriskresources->setDigiriskResources($db, $user->id, 'LabourInspectorAssigned', 'socpeople', array($labourInspectorContactId), $conf->entity, 'preventionplan', $object->id, 1);

				if ($masterWorkerId > 0) {
					$signatory->setSignatory($object->id, 'preventionplan', 'user', array($masterWorkerId), 'MasterWorker');
				}

				if ($extResponsibleId > 0) {
					$signatory->setSignatory($object->id, 'preventionplan', 'socpeople', array($extResponsibleId), 'ExtSocietyResponsible');
				}
				if (!empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_CREATE)) {
					$object->call_trigger('PREVENTIONPLAN_CREATE', $user);
				}
				// Creation prevention plan OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Creation prevention plan KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		} else {
			$action = 'create';
		}
	}

	// Action to update record
	if ($action == 'update' && $permissiontoadd) {
		// Get parameters
		$project                  = GETPOST('fk_project');
		$masterWorkerId           = GETPOST('maitre_oeuvre');
		$extSocietyId             = GETPOST('ext_society');
		$extResponsibleId         = GETPOST('ext_society_responsible');
		$extIntervenantsIds       = GETPOST('ext_intervenants');
		$labourInspectorId        = GETPOST('labour_inspector');
		$labourInspectorContactId = GETPOST('labour_inspector_contact') ? GETPOST('labour_inspector_contact') : 0;
		$label                    = GETPOST('label');
		$priorVisitBool           = GETPOST('prior_visit_bool');
		$priorVisitText           = GETPOST('prior_visit_text');
		$cssctInterventation      = GETPOST('cssct_intervention');

		// Initialize object preventionplan
		$now           = dol_now();
		$object->tms   = $now;
		$object->label = $label;

		$date_start       = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));
		$date_end         = dol_mktime(GETPOST('dateehour', 'int'), GETPOST('dateemin', 'int'), 0, GETPOST('dateemonth', 'int'), GETPOST('dateeday', 'int'), GETPOST('dateeyear', 'int'));
		$prior_visit_date = dol_mktime(GETPOST('dateihour', 'int'), GETPOST('dateimin', 'int'), 0, GETPOST('dateimonth', 'int'), GETPOST('dateiday', 'int'), GETPOST('dateiyear', 'int'));

		$object->date_start = $date_start;
		$object->date_end   = $date_end;

		$object->prior_visit_bool = $priorVisitBool;
		if ($priorVisitBool) {
			$object->prior_visit_text = $priorVisitText;
			$object->prior_visit_date = $prior_visit_date;
		}
		$object->cssct_intervention = $cssctInterventation;

		$object->fk_user_creat = $user->id ? $user->id : 1;

		$object->fk_project = $project;

		// Check parameters
		if ($masterWorkerId < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MasterWorker')), null, 'errors');
			$error++;
		} else {
			$usertmp->fetch($masterWorkerId);
		}

		if ($extSocietyId < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSociety')), null, 'errors');
			$error++;
		}

		if (is_array($extResponsibleId)) {
			if (empty(array_filter($extResponsibleId))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
				$error++;
			}
		} elseif (empty($extResponsibleId)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')), null, 'errors');
			$error++;
		}

		if ($labourInspectorId < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspectorSociety')), null, 'errors');
			$error++;
		}

		if (is_array($labourInspectorContactId)) {
			if (empty(array_filter($labourInspectorContactId))) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
				$error++;
			}
		} elseif (empty($labourInspectorContactId)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('LabourInspector')), null, 'errors');
			$error++;
		}

		if ( ! $error) {
			$result = $object->update($user, false);
			if ($result > 0) {
                if (isModEnabled('categorie')) {
                    $categories = GETPOST('categories', 'array');
                    if (method_exists($object, 'setCategories')) {
                        $object->setCategories($categories);
                    }
                }
				$digiriskresources->setDigiriskResources($db, $user->id, 'ExtSociety', 'societe', array($extSocietyId), $conf->entity, 'preventionplan', $object->id, 0);
				$digiriskresources->setDigiriskResources($db, $user->id, 'LabourInspector', 'societe', array($labourInspectorId), $conf->entity, 'preventionplan', $object->id, 0);
				$digiriskresources->setDigiriskResources($db, $user->id, 'LabourInspectorAssigned', 'socpeople', array($labourInspectorContactId), $conf->entity, 'preventionplan', $object->id, 0);

				$signatory->setSignatory($object->id, 'preventionplan', 'user', array($masterWorkerId), 'MasterWorker');
				$signatory->setSignatory($object->id, 'preventionplan', 'socpeople', array($extResponsibleId), 'ExtSocietyResponsible');

				// Update prevention plan OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Update prevention plan KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		} else {
			$action = 'edit';
		}
	}

	// Action to delete record
	if ($action == 'confirm_delete' &&  $permissiontodelete  && GETPOST("confirm") == "yes") {
		$object->status = 0;
		$result         = $object->delete($user);

		if ($result < 0) {
			// Delete accident KO
			if (!empty($object->errors)) {
                setEventMessages(null, $object->errors, 'errors');
            } else {
                setEventMessages($object->error, [], 'errors');
            }
		}
		// Delete accident OK
		$urltogo = str_replace('preventionplan_card.php', 'preventionplan_list.php', $_SERVER["PHP_SELF"]);
		header("Location: " . $urltogo);
		exit;
	}

	// Action to add line
	if ($action == 'addLine' && $permissiontoadd) {
		// Get parameters
		$actionsDescription = GETPOST('actionsdescription');
		$preventionMethod   = GETPOST('preventionmethod');
		$location           = GETPOST('fk_element');
		$riskCategoryId     = GETPOST('risk_category_id');
		$parentId           = GETPOST('parent_id');

		// Initialize object preventionplan line
		$preventionplandet->date_creation     = $object->db->idate($now);
		$preventionplandet->ref               = $refPreventionPlanDetMod->getNextValue($preventionplandet);
		$preventionplandet->entity            = $conf->entity;
		$preventionplandet->status            = PreventionPlanLine::STATUS_VALIDATED;
		$preventionplandet->description       = $actionsDescription;
		$preventionplandet->category          = $riskCategoryId;
		$preventionplandet->prevention_method = $preventionMethod;
		$preventionplandet->fk_preventionplan = $parentId;
		$preventionplandet->fk_element        = $location;

		// Check parameters
		if ($location < 1) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Location')), null, 'errors');
			$error++;
		}

		if ($riskCategoryId < 0 || $riskCategoryId == 'undefined') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('INRSRisk')), null, 'errors');
			$error++;
		}

		if ( ! $error) {
			$result = $preventionplandet->create($user, false);
			if ($result > 0) {
				// Creation prevention plan line OK
				setEventMessages($langs->trans('AddPreventionPlanLine') . ' ' . $preventionplandet->ref . ' ' . $langs->trans('PreventionPlanMessage'), array());
				$preventionplandet->call_trigger('PREVENTIONPLANDET_CREATE', $user);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Creation prevention plan line KO
				if ( ! empty($preventionplandet->errors)) setEventMessages(null, $preventionplandet->errors, 'errors');
				else setEventMessages($preventionplandet->error, null, 'errors');
			}
		}
	}

	// Action to update line
	if ($action == 'updateLine' && $permissiontoadd) {
		// Get parameters
		$actionsDescription = GETPOST('actionsdescription');
		$preventionMethod   = GETPOST('preventionmethod');
		$location            = GETPOST('fk_element');
		$riskCategoryId    = GETPOST('risk_category_id');
		$parentId           = GETPOST('parent_id');

		$preventionplandet->fetch($lineid);

		// Initialize object prevention plan line
		$preventionplandet->description       = $actionsDescription;
		$preventionplandet->category          = $riskCategoryId;
		$preventionplandet->prevention_method = $preventionMethod;
		$preventionplandet->fk_preventionplan = $parentId;
		$preventionplandet->fk_element        = $location;

		// Check parameters
		if ($parentId < 1) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Location')), null, 'errors');
			$error++;
		}
		if ($riskCategoryId < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('INRSRisk')), null, 'errors');
			$error++;
		}

		if ( ! $error) {
			$result = $preventionplandet->update($user, false);
			if ($result > 0) {
				// Update prevention plan line OK
				setEventMessages($langs->trans('UpdatePreventionPlanLine') . ' ' . $preventionplandet->ref . ' ' . $langs->trans('PreventionPlanMessage'), array());
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parentId, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Update prevention plan line KO
				if ( ! empty($preventionplandet->errors)) setEventMessages(null, $preventionplandet->errors, 'errors');
				else setEventMessages($preventionplandet->error, null, 'errors');
			}
		}
	}

	// Action to delete line
	if ($action == 'deleteline' && $permissiontodelete) {
		$preventionplandet->fetch($lineid);
		$result = $preventionplandet->delete($user, false, false);
		if ($result > 0) {
			// Delete prevention plan line OK
			setEventMessages($langs->trans('DeletePreventionPlanLine') . ' ' . $preventionplandet->ref . ' ' . $langs->trans('PreventionPlanMessage'), array());
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parentId, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Delete prevention plan line KO
			if ( ! empty($preventionplandet->errors)) setEventMessages(null, $preventionplandet->errors, 'errors');
			else setEventMessages($preventionplandet->error, null, 'errors');
		}
	}

    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/banner_actions.tpl.php';

	// Actions builddoc, forcebuilddoc, remove_file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

	// Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

	// Action to set status STATUS_INPROGRESS
	if ($action == 'confirm_setInProgress') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setInProgress($user, false);
			if ($result > 0) {
				// Set In progress OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set In progress KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_VALIDATED
	if ($action == 'confirm_setPendingSignature') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setPendingSignature($user, false);
			if ($result > 0) {
				// Set pending signature OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set pending signature KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_LOCKED
	if ($action == 'confirm_setLocked') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setLocked($user, false);
			if ($result > 0) {
				// Set locked OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set locked KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action to set status STATUS_ARCHIVED
	if ($action == 'setArchived') {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setArchived($user, false);
			if ($result > 0) {
				// Set Archived OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set Archived KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes') {
		$options['clone_label']         = GETPOST('clone_label');
		$options['preventionplan_risk'] = GETPOST('clone_preventionplan_risk');
		$options['attendants']          = GETPOST('clone_attendants');
		$options['schedule']            = GETPOST('clone_schedule');
        $options['categories']          = GETPOST('clone_categories');

		if (1 == 0 && ! GETPOST('clone_preventionplan_risk') && ! GETPOST('clone_attendants') && ! GETPOST('clone_schedule')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				$result = $object->createFromClone($user, $object->id, $options);
				if ($result > 0) {
					header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
					exit();
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
		}
	}

	// Actions to send emails
	$triggersendname    = 'PREVENTIONPLAN_SENTBYMAIL';
	$trackid            = 'preventionplan' . $object->id;
	$labourInspector    = $digiriskresources->fetchResourcesFromObject('LabourInspector', $object);
	$labourInspectorId  = $labourInspector->id;
	$thirdparty->fetch($labourInspectorId);
	$object->thirdparty = $thirdparty;

	include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$form        = new Form($db);
$formproject = new FormProjets($db);

$title       = $langs->trans("PreventionPlan");
$titleCreate = $langs->trans("NewPreventionPlan");
$titleEdit   = $langs->trans("ModifyPreventionPlan");

$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Plan_de_pr.C3.A9vention';

saturne_header(1, '', $title, $helpUrl);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($titleCreate, '', $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate preventionplan-table">';

	//Project -- projet
	print '<tr><td class="fieldrequired">' . img_picto('', 'project') . ' ' . $langs->trans("Project") . '</td><td>';
	print $formproject->select_projects(-1, $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT, 'fk_project', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="minwidth400">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat minwidth100imp widthcentpercentminusxx maxwidth400" type="text" size="36" name="label" id="label" value="' . GETPOST('label') . '">';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr><td class="minwidth400"><label for="date_debut">' . $langs->trans("StartDate") . '</label></td><td>';
	print $form->selectDate(dol_now('tzuser'), 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr><td class="minwidth400"><label for="date_fin">' . $langs->trans("EndDate") . '</label></td><td>';
	print $form->selectDate(dol_time_plus_duree(dol_now('tzuser'), 1, 'y'), 'datee', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Maitre d'oeuvre
	if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE < 0 || empty($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE)) {
		$userlist = $form->select_dolusers(( ! empty(GETPOST('maitre_oeuvre')) ? GETPOST('maitre_oeuvre') : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth100imp widthcentpercentminusxx maxwidth400', 0, 1);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('MasterWorker', 'MasterWorker_id', '', $object, 0) . '</td>';
		print '<td>';
		print $form->selectarray('maitre_oeuvre', $userlist, ( ! empty(GETPOST('maitre_oeuvre')) ? GETPOST('maitre_oeuvre') : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth100imp widthcentpercentminusxx maxwidth400', 1);
		print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';
	} else {
		$usertmp->fetch($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE);
		print '<tr>';
		print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('MasterWorker', 'MasterWorker_id', '', $object, 0) . '</td>';
		print '<td>' . $usertmp->getNomUrl(1) . '</td>';
		print '<input type="hidden" name="maitre_oeuvre" value="' . $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE . '">';
		print '</td></tr>';
	}

	//External society -- Société extérieure
	print '<tr><td class="fieldrequired minwidth400">' . img_picto('', 'building') . ' ' . $langs->trans("ExtSociety") . '</td><td>';
	$events    = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_society_responsible', 'params' => array('add-customer-contact' => 'disabled'));
	print $form->select_company(GETPOST('ext_society'), 'ext_society', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';
	$extSocietyResponsibleId = GETPOST('ext_society_responsible');

	//External responsible -- Responsable de la société extérieure
	print '<tr><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('', 'address') . ' ' . $langs->trans("ExtSocietyResponsible");
	print $htmltext;
	print '</td><td>';
	print $form->selectcontacts((empty(GETPOST('ext_society', 'int')) ? -1 : GETPOST('ext_society', 'int')), $extSocietyResponsibleId, 'ext_society_responsible', 1, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print '<a href="' . DOL_URL_ROOT . '/contact/card.php?action=create' . (empty(GETPOST('ext_society', 'int')) ? '' : '&socid=' . GETPOST('ext_society', 'int')) .'&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create&ext_society='. (empty(GETPOST('ext_society', 'int')) ? '' : GETPOST('ext_society', 'int'))) . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddContact") . '"></span></a>';
	print '</td></tr>';

	// CSSCT Intervention
	print '<tr><td class="minwidth300">' . $langs->trans("CSSCTIntervention") . '</td><td>';
	print '<input type="checkbox" id="cssct_intervention" name="cssct_intervention" ' . (GETPOST('cssct_intervention') ? ' checked=""' : '') . '>';
	print '</td></tr>';

	//Prior Visit -- Inspection commune préalable
	print '<tr><td class="minwidth400">' . $langs->trans("PriorVisit") . '</td><td>';
	print '<input type="checkbox" id="prior_visit_bool" name="prior_visit_bool"' . (GETPOST('prior_visit_bool') ? ' checked=""' : '') . '>';
	print $form->textwithpicto('', $langs->trans('CSEMustBeAlerted3DaysBeforeVisit'));
	print '</td></tr>';

	//Prior Visit Date -- Date de l'inspection commune préalable
	print '<tr class="prior_visit_date_field hidden" ' . (GETPOST('prior_visit_bool') ? '' : 'style="display:none"') . '><td class="minwidth400"><label for="prior_visit_date">' . $langs->trans("PriorVisitDate") . '</label></td><td>';
	print $form->selectDate(dol_now('tzuser'), 'datei', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Prior Visit Texte -- Note de l'inspection
	print '<tr  class="prior_visit_text_field hidden"' . (GETPOST('prior_visit_bool') ? '' : 'style="display:none"') . '><td class="minwidth400"><label for="prior_visit_text">' . $langs->trans("PriorVisitText") . '</label></td><td>';
	$doleditor = new DolEditor('prior_visit_text', GETPOST('prior_visit_text'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//Labour inspector Society -- Entreprise Inspecteur du travail
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('', 'building') . ' ' . $langs->trans("LabourInspectorSociety");
	print '</td>';
	print '<td>';
	$events    = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labour_inspector_contact', 'params' => array('add-customer-contact' => 'disabled'));
	print $form->select_company((GETPOST('labour_inspector') ? GETPOST('labour_inspector') : ($allLinks['LabourInspectorSociety']->id[0] ?: 0)), 'labour_inspector', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '<a href="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/admin/securityconf.php' . '" target="_blank">' . $langs->trans("ConfigureLabourInspector") . '</a>';
	print '</td></tr>';
	$labourInspectorContactId        = (GETPOST('labour_inspector_contact') ? GETPOST('labour_inspector_contact') : ($allLinks['LabourInspectorContact']->id[0] ?: -1));

	if ( ! empty($allLinks['LabourInspectorContact'])) {
		$contact->fetch($allLinks['LabourInspectorContact']->id[0]);
	}

	//Labour inspector -- Inspecteur du travail
	print '<tr><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('', 'address') . ' ' . $langs->trans("LabourInspector");
	print $htmltext;
	print '</td><td>';
	print $form->selectcontacts((GETPOST('labour_inspector') ? GETPOST('labour_inspector') : ($allLinks['LabourInspectorSociety']->id[0] ?: -1)), $labourInspectorContactId, 'labour_inspector_contact', 1, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print '</td></tr>';

    // Categories
    if (!empty($conf->categorie->enabled)) {
        print '<tr><td>'.$langs->trans("Categories").'</td><td>';
        $categoryArborescence = $form->select_all_categories('preventionplan', '', 'parent', 64, 0, 1);
        print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $categoryArborescence, GETPOST('categories', 'array'), '', 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
        print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/categories/index.php?type=preventionplan&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddCategories') . '"></span></a>';
        print "</td></tr>";
    }

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

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
	print load_fiche_titre($titleEdit, '', $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	$objectResources   = $digiriskresources->fetchResourcesFromObject('', $object);
	$objectSignatories = $signatory->fetchSignatory('', $object->id, 'preventionplan');

	print '<table class="border centpercent tableforfieldedit  preventionplan-table">' . "\n";

	//Ref -- Ref
	print '<tr><td class="fieldrequired minwidth400">' . $langs->trans("Ref") . '</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Project -- projet
	print '<tr><td class="fieldrequired minwidth400">' . img_picto('', 'project') . ' ' . $langs->trans("Project") . '</td><td>';
	print $formproject->select_projects(-1, $object->fk_project, 'fk_project', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="minwidth400">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat minwidth100imp widthcentpercentminusxx maxwidth400" type="text" size="36" name="label" id="label" value="' . $object->label . '">';
	print '</td></tr>';

	//Start Date -- Date début
	print '<tr class="oddeven"><td class="minwidth400"><label for="date_debut">' . $langs->trans("StartDate") . '</label></td><td>';
	print $form->selectDate($object->date_start, 'dateo', 1, 1, 0, '', 1);
	print '</td></tr>';

	//End Date -- Date fin
	print '<tr class="oddeven"><td class="minwidth400"><label for="date_fin">' . $langs->trans("EndDate") . '</label></td><td>';
	print $form->selectDate($object->date_end, 'datee', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Maitre d'oeuvre
	$masterWorker  = is_array($objectSignatories['MasterWorker']) ? array_shift($objectSignatories['MasterWorker'])->element_id : '';
	$userlist      = $form->select_dolusers($masterWorker, '', 1, null, 0, '', '', 0, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth100imp widthcentpercentminusxx maxwidth400', 0, 1);
	print '<tr>';
	print '<td class="fieldrequired minwidth400" style="width:10%">' . img_picto('', 'user') . ' ' . $form->editfieldkey('MasterWorker', 'MasterWorker_id', '', $object, 0) . '</td>';
	print '<td>';
	print $form->selectarray('maitre_oeuvre', $userlist, $masterWorker, 1, null, null, null, "40%", 0, 0, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	//External society -- Société extérieure
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('', 'building') . ' ' . $langs->trans("ExtSociety");
	print '</td>';
	print '<td>';
	$events    = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'ext_society_responsible', 'params' => array('add-customer-contact' => 'disabled'));
	//For external user force the company to user company
	if ( ! empty($user->socid)) {
		print $form->select_company($user->socid, 'ext_society', '', 1, 1, 0, $events, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	} else {
		$extSocietyId = is_array($objectResources['ExtSociety']) ? array_shift($objectResources['ExtSociety'])->id : '';
		print $form->select_company($extSocietyId, 'ext_society', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	}
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';
	$extSocietyResponsibleId = is_array($objectSignatories['ExtSocietyResponsible']) ? array_shift($objectSignatories['ExtSocietyResponsible'])->element_id : GETPOST('ext_society_responsible');

	if ($extSocietyResponsibleId > 0) {
		$contact->fetch($extSocietyResponsibleId);
	}

	//External responsible -- Responsable de la société extérieure
	$extSociety = $digiriskresources->fetchResourcesFromObject('ExtSociety', $object);
	print '<tr class="oddeven"><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('', 'address') . ' ' . $langs->trans("ExtSocietyResponsible");
	print $htmltext;
	print '</td><td>';
	print $form->selectcontacts($extSociety->id, dol_strlen($contact->email) ? $extSocietyResponsibleId : -1, 'ext_society_responsible', '', 0, '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
    print '<a href="' . DOL_URL_ROOT . '/contact/card.php?action=create' . (empty($extSociety->id) ? '' : '&socid=' . $extSociety->id) .'&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create&ext_society='. (empty($extSociety->id) ? '' : $extSociety->id)) . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddContact") . '"></span></a>';
    print '</td></tr>';

	// CSSCT Intervention
	print '<tr><td class="minwidth400">';
	print $langs->trans("CSSCTIntervention");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="cssct_intervention" name="cssct_intervention"' . ($object->cssct_intervention ? ' checked=""' : '') . '"> ';
	$htmltext = $langs->trans("CSSCTInterventionText");
	print $form->textwithpicto('', $htmltext);
	print '<br>';
	print '</td></tr>';

	//Prior Visit -- Inspection commune préalable
	print '<tr class="oddeven"><td class="minwidth400">';
	print $langs->trans("PriorVisit");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="prior_visit_bool" name="prior_visit_bool"' . ($object->prior_visit_bool ? ' checked=""' : '') . '"> ';
	print '</td></tr>';

	//Prior Visit Date -- Date de l'inspection commune préalable
	print '<tr class="' . ($object->prior_visit_bool ? ' prior_visit_date_field' : ' prior_visit_date_field hidden' ) . '" style="' . ($object->prior_visit_bool ? ' ' : ' display:none') . '"><td class="minwidth400"><label for="prior_visit_date">' . $langs->trans("PriorVisitDate") . '</label></td><td>';
	print $form->selectDate($object->date_start, 'datei', 1, 1, 0, '', 1);
	print '</td></tr>';

	//Prior Visit Text -- Note de l'inspection
	print '<tr class="' . ($object->prior_visit_bool ? ' prior_visit_date_field' : ' prior_visit_date_field hidden' ) . '" style="' . ($object->prior_visit_bool ? ' ' : ' display:none') . '"><td class="minwidth400"><label for="prior_visit_text">' . $langs->trans("PriorVisitText") . '</label></td><td>';
	$doleditor = new DolEditor('prior_visit_text', $object->prior_visit_text, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	if (is_array($objectResources['LabourInspector']) && $objectResources['LabourInspector'] > 0) {
		$labourInspectorSociety = array_shift($objectResources['LabourInspector']);
	}
	if (is_array($objectResources['LabourInspectorAssigned']) && $objectResources['LabourInspectorAssigned'] > 0) {
		$labourInspector_assigned = array_shift($objectResources['LabourInspectorAssigned']);
	}
	//Labour inspector Society -- Entreprise Inspecteur du travail
	print '<tr><td class="fieldrequired minwidth400">';
	print img_picto('', 'building') . ' ' . $langs->trans("LabourInspectorSociety");
	print '</td>';
	print '<td>';
	$events    = array();
	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/custom/digiriskdolibarr/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labour_inspector_contact', 'params' => array('add-customer-contact' => 'disabled'));
	print $form->select_company($labourInspectorSociety->id, 'labour_inspector', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '<a href="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/admin/securityconf.php' . '" target="_blank">' . $langs->trans("ConfigureLabourInspector") . '</a>';
	print '</td></tr>';

	$labourInspectorContact = ! empty($digiriskresources->fetchResourcesFromObject('LabourInspectorAssigned', $object)) ? $digiriskresources->fetchResourcesFromObject('LabourInspectorAssigned', $object) : GETPOST('labour_inspector_contact');

	//Labour inspector -- Inspecteur du travail
	$labourInspectorSociety = $digiriskresources->fetchResourcesFromObject('LabourInspector', $object);
	print '<tr><td class="fieldrequired minwidth400">';
	$htmltext = img_picto('', 'address') . ' ' . $langs->trans("LabourInspector");
	print $htmltext;
	print '</td><td>';
	print $form->selectcontacts($labourInspectorSociety->id, dol_strlen($contact->email) ? $labourInspectorContact->id : -1, 'labour_inspector_contact', '', 0, '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
	print '</td></tr>';

    // Tags-Categories
    if ($conf->categorie->enabled) {
        print '<tr><td>'.$langs->trans("Categories").'</td><td>';
        $categoryArborescence = $form->select_all_categories('preventionplan', '', 'parent', 64, 0, 1);
        $c = new Categorie($db);
        $cats = $c->containing($object->id, 'preventionplan');
        $arrayselected = array();
        if (is_array($cats)) {
            foreach ($cats as $cat) {
                $arrayselected[] = $cat->id;
            }
        }
        print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $categoryArborescence, $arrayselected, '', 0, 'minwidth100imp widthcentpercentminusxx maxwidth400');
        print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/categories/index.php?type=preventionplan&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddCategories') . '"></span></a>';
        print "</td></tr>";
    }

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';
	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
}

$formconfirm = '';

// SetLocked confirmation
if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
    $formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
}

// setPendingSignature confirmation
if (($action == 'setPendingSignature' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidatePreventionPlan'), $langs->trans('ConfirmValidatePreventionPlan', $object->ref), 'confirm_setPendingSignature', '', 'yes', 'actionButtonPendingSignature', 350, 600);
}

// setInProgress confirmation
if (($action == 'setInProgress' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ReOpenPreventionPlan'), $langs->trans('ConfirmReOpenPreventionPlan', $object->ref), 'confirm_setInProgress', '', 'yes', 'actionButtonInProgress', 350, 600);
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
	// Define confirmation messages
	$formquestionclone = ['text' => $langs->trans("ConfirmClone"),
		['type' => 'text',     'name' => 'clone_label',               'label' => $langs->trans("NewLabelForClonePreventionPlan"), 'value' => empty($tmpcode) ? $langs->trans("CopyOf") . ' ' . $object->ref : $tmpcode, 'size' => 24],
		['type' => 'checkbox', 'name' => 'clone_preventionplan_risk', 'label' => $langs->trans("ClonePreventionPlanRisk"),        'value' => 1],
		['type' => 'checkbox', 'name' => 'clone_attendants',          'label' => $langs->trans("CloneAttendantsPreventionPlan"),  'value' => 1],
		['type' => 'checkbox', 'name' => 'clone_schedule',            'label' => $langs->trans("CloneSchedulePreventionPlan"),    'value' => 1],
        ['type' => 'checkbox', 'name' => 'clone_categories',          'label' => $langs->trans('CloneCategories'),                'value' => 1]
	];

	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmClonePreventionPlan', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'actionButtonClone', 350, 600);
}

// Delete confirmation
if ($action == 'delete' && $permissiontodelete) {
	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeletePreventionPlan"), $langs->trans('ConfirmDeletePreventionPlan'), "confirm_delete", '', '', 1);
}

// Call Hook formConfirm
$parameters                        = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook                           = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

// Print form confirm
print $formconfirm;

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {
	// Object card
	// ------------------------------------------------------------

	$object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);

    // External Society -- Société extérieure
    $extSociety  = $digiriskresources->fetchResourcesFromObject('ExtSociety', $object);
    $moreHtmlRef = $langs->trans('ExtSociety') . ' : ' . $extSociety->getNomUrl(1) . '<br>';

	if ($conf->browser->layout == 'phone') {
		$onPhone = 1;
	} else {
		$onPhone = 0;
	}

	saturne_banner_tab($object, 'id', '', 1, 'rowid', 'ref', $moreHtmlRef);

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
	print '<input type="checkbox" id="prior_visit_bool" name="prior_visit_bool"' . ($object->prior_visit_bool ? ' checked=""' : '') . '" disabled> ';
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

	print '</table>';
	print '</div>';
	print '<div class="fichehalfright">';
	print '<table class="border centpercent tableforfield">';

	//Labour inspector Society -- Entreprise Inspecteur du travail
	print '<tr><td class="titlefield">';
	print $langs->trans("LabourInspectorSociety");
	print '</td>';
	print '<td>';
	$labourInspector = $digiriskresources->fetchResourcesFromObject('LabourInspector', $object);
	if ($labourInspector > 0) {
		print $labourInspector->getNomUrl(1);
	}
	print '</td></tr>';

	//Labour inspector -- Inspecteur du travail
	print '<tr><td class="titlefield">';
	print $langs->trans("LabourInspector");
	print '</td>';
	print '<td>';
	$labourInspectorContact = $digiriskresources->fetchResourcesFromObject('LabourInspectorAssigned', $object);
	if ($labourInspectorContact > 0) {
		print $labourInspectorContact->getNomUrl(1);
	}
	print '</td></tr>';

	//Attendants -- Participants
	print '<tr><td class="titlefield">';
	print $langs->trans("Attendants");
	print '</td>';
	print '<td>';
	$attendants  = count($signatory->fetchSignatory('MasterWorker', $object->id, 'preventionplan'));
	$attendants += count($signatory->fetchSignatory('ExtSocietyResponsible', $object->id, 'preventionplan'));
	$attendants += count($signatory->fetchSignatory('ExtSocietyAttendant', $object->id, 'preventionplan'));
	$url         = dol_buildpath('/custom/saturne/view/saturne_attendants.php?id=' . $object->id . '&module_name=DigiriskDolibarr&object_type=' . $object->element . '&document_type=PreventionPlanDocument', 3);
	$displayButton = $onPhone ? '<i class="fas fa-plus fa-2x"></i>' : '<i class="fas fa-plus"></i> ' . $langs->trans('AddAttendants');
	print '<a ' . ($object->status == 1 ? 'href="' .  $url . '"' : '') . '">' . $attendants;
	print '<span class="' . ($object->status == $object::STATUS_DRAFT ? 'butAction' : 'butActionRefused classfortooltip') . '" id="actionButtonAddAttendants" title="' . dol_escape_htmltag($langs->trans("PreventionPlanMustBeInProgress")) . '">' .  $displayButton . '</span></a>';
	print '</td></tr>';

    // Categories
    if ($conf->categorie->enabled) {
        print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
        print $form->showCategories($object->id, 'preventionplan', 1);
        print "</td></tr>";
    }

	print '</table>';
	print '</div>';
	print '</div>';

	print dol_get_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >';
		$parameters = array();
		$reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook) && $permissiontoadd) {
			// Modify
			$displayButton = $onPhone ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->trans('Modify');
			if ($object->status == $object::STATUS_DRAFT) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken() . '">' . $displayButton . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('PreventionPlanMustBeInProgress')) . '">' . $displayButton . '</span>';
			}

			// Validate
			$displayButton = $onPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Validate');
			if ($object->status == $object::STATUS_DRAFT) {
				print '<a class="butAction" id="actionButtonPendingSignature">' . $displayButton . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('PreventionPlanMustBeInProgressToValidate')) . '">' . $displayButton . '</span>';
			}

			// ReOpen
			$displayButton = $onPhone ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->trans('ReOpenDoli');
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<span class="butAction" id="actionButtonInProgress">' . $displayButton . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('PreventionPlanMustBeValidated')) . '">' . $displayButton . '</span>';
			}

			// Sign
			$displayButton = $onPhone ? '<i class="fas fa-signature fa-2x"></i>' : '<i class="fas fa-signature"></i>' . ' ' . $langs->trans('Sign');
			if ($object->status == $object::STATUS_VALIDATED && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
				print '<a class="butAction" id="actionButtonSign" href="' . dol_buildpath('/custom/saturne/view/saturne_attendants.php?id=' . $object->id . '&module_name=DigiriskDolibarr&object_type=' . $object->element . '&document_type=PreventionPlanDocument', 3) . '">' . $displayButton . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
			}

			// Lock
			$displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
			if ($object->status == $object::STATUS_VALIDATED && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
				print '<span class="butAction" id="actionButtonLock">' . $displayButton . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('AllSignatoriesMustHaveSigned')) . '">' . $displayButton . '</span>';
			}

            // Send email
            $displayButton = $onPhone ? '<i class="fas fa-envelope fa-2x"></i>' : '<i class="fas fa-envelope"></i>' . ' ' . $langs->trans('SendMail') . ' ';
            if ($object->status == PreventionPlan::STATUS_LOCKED) {
                $fileParams = dol_most_recent_file($upload_dir . '/' . $object->element . 'document' . '/' . $object->ref);
                $file       = $fileParams['fullname'];
                if (file_exists($file) && !strstr($fileParams['name'], 'specimen')) {
                    $forcebuilddoc = 0;
                } else {
                    $forcebuilddoc = 1;
                }
                print dolGetButtonAction($displayButton, '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&forcebuilddoc=' . $forcebuilddoc . '&mode=init#formmailbeforetitle');
            } else {
                print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans('ObjectMustBeLockedToSendEmail', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

			// Archive
			$displayButton = $onPhone ?  '<i class="fas fa-archive fa-2x"></i>' : '<i class="fas fa-archive"></i>' . ' ' . $langs->trans('Archive');
			if ($object->status == $object::STATUS_LOCKED) {
				print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setArchived&token=' . newToken() . '">' . $displayButton . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
			}

			// Clone
			$displayButton = $onPhone ? '<i class="fas fa-clone fa-2x"></i>' : '<i class="fas fa-clone"></i>' . ' ' . $langs->trans('ToClone');
			print '<span class="butAction" id="actionButtonClone">' . $displayButton . '</span>';
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
		$preventionplandets = $preventionplandet->fetchAll('', '', 0, 0, ['fk_preventionplan' => GETPOST('id')]);

		print '<tr class="liste_titre">';
		print '<td><span>' . $langs->trans('Ref.') . '</span></td>';
		print '<td>' . $langs->trans('GP/UT') . '</td>';
		print '<td>' . $form->textwithpicto($langs->trans('ActionsDescription'), $langs->trans("ActionsDescriptionTooltip")) . '</td>';
		print '<td class="center">' . $form->textwithpicto($langs->trans('INRSRisk'), $langs->trans('INRSRiskTooltip')) . '</td>';
		print '<td>' . $form->textwithpicto($langs->trans('PreventionMethod'), $langs->trans('PreventionMethodTooltip')) . '</td>';
		print '<td class="center" colspan="' . $colspan . '">' . $langs->trans('ActionsPreventionPlanRisk') . '</td>';
		print '</tr>';

		if ( ! empty($preventionplandets) && $preventionplandets > 0) {
			print '<tr>';
			foreach ($preventionplandets as $key => $item) {
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
					print $digiriskelementtmp->selectDigiriskElementList($item->fk_element, 'fk_element', ['customsql' => ' t.rowid NOT IN (' . implode(',', $deletedElements) . ')'], 0, 0, array(), 0, 0, 'minwidth100 maxwidth300', 0, false, 1);
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
								 aria-label="<?php echo $risk->getDangerCategoryName($item) ?>">
								<img class="danger-category-pic hover"
									 src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($item) . '.png'; ?>"/>
							</div>
						</div>

						<ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
							<?php
							$dangerCategories = Risk::getDangerCategories();
							if ( ! empty($dangerCategories)) :
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
					print ' &nbsp; <input type="submit" id ="cancelLine" class="button" name="cancelLine" value="' . $langs->trans("Cancel") . '">';
					print '</td>';
					print '</tr>';

					if (is_object($preventionplandet)) {
						print $preventionplandet->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
					}
					print '</form>';
				} else {
					print '<tr>';
					print '<td>';
					print $item->ref;
					print '</td>';

					print '<td>';
					$digiriskelement->fetch($item->fk_element);
					print $digiriskelement->getNomUrl(1, 'blank', 0, '', -1, 1);
					print '</td>';

					$coldisplay++;
					print '<td>';
					print $item->description;
					print '</td>';

					$coldisplay++;
					print '<td class="center">'; ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
							 aria-label="<?php echo $risk->getDangerCategoryName($item) ?>">
							<img class="danger-category-pic hover"
								 src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($item) . '.png'; ?>"
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
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=editline&lineid=' . $item->id . '" style="padding-right: 20px"><i class="fas fa-pencil-alt" style="color: #666"></i></a>';
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=deleteline&lineid=' . $item->id . '&token=' . newToken() . '">';
						print img_delete();
						print '</a>';
						print '</td>';
					} else {
						print '<td class="center">';
						print '-';
						print '</td>';
					}

					if (is_object($preventionplandet)) {
						print $preventionplandet->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
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
			print $refPreventionPlanDetMod->getNextValue($preventionplandet);
			print '</td>';
			print '<td>';
			print $digiriskelementtmp->selectDigiriskElementList('', 'fk_element', ['customsql' => ' t.rowid NOT IN (' . implode(',', $deletedElements) . ')'], 0, 0, array(), 0, 0, 'minwidth100 maxwidth300', '', false, 1);
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
				<ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
					<?php
					$dangerCategories = Risk::getDangerCategories();
					if ( ! empty($dangerCategories)) :
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

			if (is_object($preventionplandet)) {
				print $preventionplandet->showOptionals($extrafields, 'edit', array('style' => $bcnd[$var], 'colspan' => $coldisplay), '', '', 1);
			}
			print '</form>';
		}
		print '</table>';
		print '</div>';
	}
	// Document Generation -- Génération des documents
	if ($permissiontoadd) {
		print '<div class=""><div class="preventionplanDocument fichehalfleft">';

		$objref    = dol_sanitizeFileName($object->ref);
		$dirFiles  = $document->element . '/' . $objref;
		$filedir   = $upload_dir . '/' . $dirFiles;
		$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $id;

		$modulepart   = 'digiriskdolibarr:PreventionPlanDocument';
		$defaultmodel = $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_DEFAULT_MODEL;
		$title        = $langs->trans('PreventionPlanDocument');

		if ($permissiontoadd || $permissiontoread) {
			$genallowed = 1;
		}

		$filelist = dol_dir_list($filedir, 'files');
		if (!empty($filelist) && is_array($filelist)) {
			foreach ($filelist as $file) {
				if (preg_match('/sign/', $file['name'])) {
					$filesigned = 1;
				}
			}
		}

		print saturne_show_documents($modulepart, $dirFiles, $filedir, $urlsource, $genallowed, 0, $defaultmodel, 1, 0, 0, 0, 0, $title, 0, 0, empty($soc->default_lang) ? '' : $soc->default_lang, $object, 0, 'remove_file', (($object->status > $object::STATUS_VALIDATED) ? 1 : 0), $langs->trans('ObjectMustBeLockedToGenerate', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))));
	}

	if ($permissiontoadd) {
		print '</div><div class="fichehalfright">';
	} else {
		print '</div><div class="">';
	}

    $moreHtmlCenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiriskDolibarr&object_type=' . $object->element);

    // List of actions on element
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
    $formActions = new FormActions($db);
    $formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10, '', $moreHtmlCenter);

	print '</div></div></div>';

	// Presend form
	$labourInspector    = $digiriskresources->fetchResourcesFromObject('LabourInspector', $object);
	$labourInspectorId = $labourInspector->id;
	$thirdparty->fetch($labourInspectorId);
	$object->thirdparty = $thirdparty;

	$modelmail    = 'preventionplan';
	$defaulttopic = 'Information';
	$diroutput    = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element . 'document';
	$ref          = $object->ref . '/';
	$trackid      = 'preventionplan' . $object->id;

    // Select mail models is same action as presend
    if (GETPOST('modelselected', 'alpha')) {
        $action = 'presend';
    }
    // Presend form
    if ($action == 'presend') {
        $langs->load('mails');

        $object->fetch_projet();
        if (!in_array($object->element, ['societe', 'user', 'member'])) {
            include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            $ref        = dol_sanitizeFileName($object->ref);
            $fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/') . '[^\-]+');
            $file       = $fileparams['fullname'];
        }

        // Define output language
        $outputlangs = $langs;
        $newlang     = '';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) {
            $newlang = $_REQUEST['lang_id'];
        }
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
            $newlang = $object->thirdparty->default_lang;
        }

        if (!empty($newlang)) {
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang($newlang);
            // Load traductions files required by page
            $outputlangs->loadLangs(['digiriskdolibarr']);
        }

        $topicmail = '';
        if (empty($object->ref_client)) {
            $topicmail = $outputlangs->trans($defaulttopic, '__REF__');
        } else {
            $topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
        }

        print load_fiche_titre($langs->trans('SendMail'), '', $object->picto, '', 'formmailbeforetitle');

        print dol_get_fiche_head('');

        // Create form for email
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
        $formmail     = new FormMail($db);
        $masterWorker = $signatory->fetchSignatory('MasterWorker', $object->id, 'preventionplan');
        $masterWorker = array_shift($masterWorker);

        $formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
        $formmail->fromtype             = (GETPOST('fromtype') ? GETPOST('fromtype') : (!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));
        $formmail->fromid               = $masterWorker->id;
        $formmail->trackid              = $trackid;
        $formmail->fromname             = $masterWorker->firstname . ' ' . $masterWorker->lastname;
        $formmail->frommail             = $masterWorker->email;
        $formmail->fromalsorobot        = 1;
        $formmail->withfrom             = 1;

        // Fill list of recipient with email inside <>.
        $liste = [];

        $labourInspectorContact = $digiriskresources->fetchResourcesFromObject('LabourInspectorAssigned', $object);

        if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
            $listeuser = [];
            $fuserdest = new User($db);

            $result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, ['customsql' => 't.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''], 'AND', true);
            if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
                foreach ($fuserdest->users as $uuserdest) {
                    $listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
                }
            } elseif ($result < 0) {
                setEventMessages(null, $fuserdest->errors, 'errors');
            }
            if (count($listeuser) > 0) {
                $formmail->withtouser   = $listeuser;
                $formmail->withtoccuser = $listeuser;
            }
        }

        $signatoriesArray = $signatory->fetchSignatories($object->id, 'preventionplan');
        if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
            foreach ($signatoriesArray as $objectSignatory) {
                $liste[$objectSignatory->id] = dol_strtoupper($objectSignatory->lastname) . ' ' . ucfirst($objectSignatory->firstname) . (!empty($objectSignatory->email) ? ' <' . $objectSignatory->email . '>' : '');
            }
        }

        if (!array_key_exists($labourInspectorContact->id, $liste)) {
            $liste[$labourInspectorContact->id] = $labourInspectorContact->firstname . ' ' . $labourInspectorContact->lastname . (!empty($labourInspectorContact->email) ? ' <' . $labourInspectorContact->email . '>' : '');
        }

        $formmail->withto              = $liste;
        $formmail->withtofree          = (GETPOSTISSET('sendto') ? (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1') : '1');
        $formmail->withtocc            = $liste;
        $formmail->withtoccc           = $conf->global->MAIN_EMAIL_USECCC;
        $formmail->withtopic           = $topicmail;
        $formmail->withfile            = 2;
        $formmail->withbody            = 1;
        $formmail->withdeliveryreceipt = 1;
        $formmail->withcancel          = 1;

        //$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
        if (!isset($arrayoffamiliestoexclude)) {
            $arrayoffamiliestoexclude = null;
        }

        // Make substitution in email content
        $substitutionarray                       = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
        $substitutionarray['__CHECK_READ__']     = (is_object($object) && is_object($object->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $object->thirdparty->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
        $substitutionarray['__PERSONALIZED__']   = ''; // deprecated
        $substitutionarray['__CONTACTCIVNAME__'] = '';
        $parameters                              = [
            'mode' => 'formemail'
        ];
        complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

        // Find the good contact address
        $tmpobject = $object;

        $contactarr = [];
        $contactarr = $tmpobject->liste_contact(-1, 'external');

        if (is_array($contactarr) && count($contactarr) > 0) {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            $contactstatic = new Contact($db);

            foreach ($contactarr as $contact) {
                $contactstatic->fetch($contact['id']);
                $substitutionarray['__CONTACT_NAME_' . $contact['code'] . '__'] = $contactstatic->getFullName($outputlangs, 1);
            }
        }

        // Array of substitutions
        $formmail->substit = $substitutionarray;

        // Array of other parameters
        $formmail->param['action']    = 'send';
        $formmail->param['models']    = $modelmail;
        $formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
        $formmail->param['id']        = $object->id;
        $formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
        $formmail->param['fileinit']  = [$file];

        // Show form
        print $formmail->get_form();

        print dol_get_fiche_end();
    }
}

// End of page
llxFooter();
$db->close();
