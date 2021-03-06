<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       risk_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for risk
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/mod_project_simple.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/mod_task_simple.php';

require_once './class/digiriskelement.class.php';
require_once './class/digiriskstandard.class.php';
require_once './class/riskanalysis/risk.class.php';
require_once './class/riskanalysis/riskassessment.class.php';
require_once './core/modules/digiriskdolibarr/riskanalysis/risk/mod_risk_standard.php';
require_once './core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';
require_once './lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once './lib/digiriskdolibarr_function.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$action              = GETPOST('action', 'aZ09');
$massaction          = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'riskcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$toselect            = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$sortfield           = GETPOST('sortfield', 'alpha');
$sortorder           = GETPOST('sortorder', 'alpha');

// Initialize technical objects
$object            = new DigiriskStandard($db);
$risk              = new Risk($db);
$evaluation        = new RiskAssessment($db);
$ecmdir            = new EcmDirectory($db);
$project           = new Project($db);
$task              = new Task($db);
$extrafields       = new ExtraFields($db);
$refRiskMod        = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
$refEvaluationMod  = new $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON();
$refProjectMod     = new $conf->global->PROJECT_ADDON();
$refTaskMod        = new $conf->global->PROJECT_TASK_ADDON();

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
$hookmanager->initHooks(array('risklist', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($risk->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($risk->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = "t.".key($risk->fields); // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = "ASC";
if (!$evalsortfield) $evalsortfield = "evaluation.".key($evaluation->fields);

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search = array();
foreach ($risk->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($risk->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($risk->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}
foreach ($evaluation->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['evaluation.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_risk
$permissiontoread = $user->rights->digiriskdolibarr->risk->read;
$permissiontoadd = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;

// Security check - Protection if external user
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $risk, $action); // Note that $action and $risk may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($risk->fields as $key => $val) {
			$search[$key] = '';
		}
		foreach ($evaluation->fields as $key => $val) {
			$search[$key] = '';
		}
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;

	$backtopage = dol_buildpath('/digiriskdolibarr/risk_list.php', 1);

	if (!$error && $action == 'saveRisk' && $permissiontoadd) {
		$riskID      = GETPOST('riskID');
		$description = GETPOST('riskComment', 'restricthtml');
		$category    = GETPOST('riskCategory');

		$risk->fetch($riskID);

		$risk->description =  $description;
		$risk->category    = $category;

		$result = $risk->update($user, true);

		if ($result > 0) {
			// Update risk OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		} else {
			// Update risk KO
			if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
			else  setEventMessages($risk->error, null, 'errors');
		}
	}

	if (!$error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
		if (!empty($toselect)) {
			foreach ($toselect as $toselectedid) {
				$ListEvaluations =  $evaluation->fetchFromParent($toselectedid,0);
				$risk->fetch($toselectedid);

				if (!empty ($ListEvaluations) && $ListEvaluations > 0) {
					foreach ($ListEvaluations as $lastEvaluation ) {
						$pathToEvaluationPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/riskassessment/' . $lastEvaluation->ref;

						if ( file_exists( $pathToEvaluationPhoto ) && !(empty($lastEvaluation->ref))) {
							$files = dol_dir_list($pathToEvaluationPhoto);
							if (!empty($files)) {
								foreach ($files as $file) {
									if (is_file($file['fullname'])) {
										unlink($file['fullname']);
									}
								}
							}

							$files = dol_dir_list($pathToEvaluationPhoto . '/thumbs');
							if (!empty($files)) {
								foreach ($files as $file) {
									unlink($file['fullname']);
								}
							}
							dol_delete_dir($pathToEvaluationPhoto . '/thumbs');
							dol_delete_dir($pathToEvaluationPhoto);

							$lastEvaluation->delete($user, true);
						}
					}
				}

				$result = $risk->delete($user, true);

				if ($result < 0) {
					// Delete risk KO
					if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
					else  setEventMessages($risk->error, null, 'errors');
				}
			}

			// Delete risk OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		}
	}

	if (!$error && $action == 'addEvaluation' && $permissiontoadd) {
		$evaluationComment = GETPOST('evaluationComment', 'restricthtml');
		$riskID            = GETPOST('riskToAssign');
		$cotation          = GETPOST('cotation');
		$method            = GETPOST('cotationMethod');
		$photo             = GETPOST('photo');

		$risk->fetch($riskID);


		$evaluation->photo      = $photo;
		$evaluation->cotation   = $cotation;
		$evaluation->fk_risk    = $risk->id;
		$evaluation->status     = 1;
		$evaluation->method     = $method;
		$evaluation->ref        = $refEvaluationMod->getNextValue($evaluation);
		$evaluation->comment    = $evaluationComment;

		if ($method == 'advanced') {
			$formation  = GETPOST('formation');
			$protection = GETPOST('protection');
			$occurrence = GETPOST('occurrence');
			$gravite    = GETPOST('gravite');
			$exposition = GETPOST('exposition');

			$evaluation->formation  = $formation;
			$evaluation->protection = $protection;
			$evaluation->occurrence = $occurrence;
			$evaluation->gravite    = $gravite;
			$evaluation->exposition = $exposition;
		}

		//photo upload and thumbs generation
		if ( !empty ($photo) ) {
			$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';
			$pathToECMPhoto =  DOL_DATA_ROOT . $entity . '/ecm/digiriskdolibarr/medias/' . $photo;

			$pathToEvaluationPhoto = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessment/' . $evaluation->ref;

			mkdir($pathToEvaluationPhoto);
			copy($pathToECMPhoto,$pathToEvaluationPhoto . '/' . $photo);

			global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
			$destfull = $pathToEvaluationPhoto . '/' . $photo;

			// Create thumbs
			// We can't use $object->addThumbs here because there is no $object known
			// Used on logon for example
			$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
			// Create mini thumbs for image (Ratio is near 16/9)
			// Used on menu or for setup page for example
			$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
		}

		$result = $evaluation->create($user, true);

		if ($result > 0)
		{
			// Creation evaluation OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		}
		else
		{
			// Creation evaluation KO
			if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
			else  setEventMessages($evaluation->error, null, 'errors');
		}
	}

	if (!$error && $action == 'saveEvaluation' && $permissiontoadd) {
		$evaluationID      = GETPOST('evaluationID');
		$cotation          = GETPOST('cotation');
		$method            = GETPOST('cotationMethod');
		$evaluationComment = GETPOST('evaluationComment', 'restricthtml');
		$photo             = GETPOST('photo');

		$evaluation->fetch($evaluationID);

		$evaluation->cotation = $cotation;
		$evaluation->method   = $method;
		$evaluation->comment  = $evaluationComment;
		$evaluation->photo    = $photo;

		if ($method == 'advanced') {
			$formation  = GETPOST('formation');
			$protection = GETPOST('protection');
			$occurrence = GETPOST('occurrence');
			$gravite    = GETPOST('gravite');
			$exposition = GETPOST('exposition');

			$evaluation->formation  = $formation;
			$evaluation->protection = $protection;
			$evaluation->occurrence = $occurrence;
			$evaluation->gravite    = $gravite;
			$evaluation->exposition = $exposition;
		}
		$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';
		if ( !(empty($photo))) {
			$pathToECMPhoto =  DOL_DATA_ROOT .$entity. '/ecm/digiriskdolibarr/medias/' . $photo;

			$pathToEvaluationPhoto = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessment/' . $evaluation->ref;

			if (is_dir($pathToEvaluationPhoto)) {
				$files = dol_dir_list($pathToEvaluationPhoto);
				foreach ($files as $file) {
					if (is_file($file['fullname'])) {
						unlink($file['fullname']);
					}
				}

				$files = dol_dir_list($pathToEvaluationPhoto . '/thumbs');
				foreach ($files as $file) {
					unlink($file['fullname']);
				}

				copy($pathToECMPhoto,$pathToEvaluationPhoto . '/' . $photo);
			} else {
				mkdir($pathToEvaluationPhoto);
				copy($pathToECMPhoto,$pathToEvaluationPhoto . '/' . $photo);
			}

			global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
			$destfull = $pathToEvaluationPhoto . '/' . $photo;

			// Create thumbs
			$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
			// Create mini thumbs for image (Ratio is near 16/9)
			$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");

		}
		$result = $evaluation->update($user, true);

		if ($result > 0)
		{
			// Update evaluation OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		}
		else
		{
			// Update evaluation KO
			if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
			else  setEventMessages($evaluation->error, null, 'errors');
		}
	}

	if (!$error && $action == "deleteEvaluation" && $permissiontodelete) {
		$evaluation_id = GETPOST('deletedEvaluationId');

		$evaluation->fetch($evaluation_id);

		$pathToEvaluationPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/riskassessment/' . $evaluation->ref;
		$files = dol_dir_list($pathToEvaluationPhoto);
		foreach ($files as $file) {
			if (is_file($file['fullname'])) {
				unlink($file['fullname']);
			}
		}

		$files = dol_dir_list($pathToEvaluationPhoto . '/thumbs');
		foreach ($files as $file) {
			unlink($file['fullname']);
		}

		dol_delete_dir($pathToEvaluationPhoto . '/thumbs');
		dol_delete_dir($pathToEvaluationPhoto);

		$previousEvaluation = $evaluation;
		$result = $evaluation->delete($user, true);
		$previousEvaluation->updateEvaluationStatus($user,$evaluation->fk_risk);

		if ($result > 0)
		{
			// Delete evaluation OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		}
		else
		{
			// Delete evaluation KO
			if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
			else  setEventMessages($evaluation->error, null, 'errors');
		}
	}

	if ($action == "uploadPhoto" && !empty($conf->global->MAIN_UPLOAD_DOC)) {
		// Define relativepath and upload_dir
		$relativepath = 'digiriskdolibarr/medias';
		$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

		if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
		else $userfiles = array($_FILES['userfile']['tmp_name']);

		foreach ($userfiles as $key => $userfile)
		{
			if (empty($_FILES['userfile']['tmp_name'][$key]))
			{
				$error++;
				if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}
		}

		if (!$error)
		{
			$generatethumbs = 0;
			$res = dol_add_file_process($upload_dir, 0, 1, 'userfile', '', null, '', $generatethumbs);
			if ($res > 0)
			{
				$result = $ecmdir->changeNbOfFiles('+');
			}
		}
	}

	if (!$error && $action == 'addRiskAssessmentTask' && $permissiontoadd) {
		$riskID    = GETPOST('riskToAssign');
		$tasktitle = GETPOST('tasktitle');

		$extrafields->fetch_name_optionals_label($task->table_element);

		$task->ref                              = $refTaskMod->getNextValue('', $task);
		$task->label                            = $tasktitle;
		$task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
		$task->date_c                           = dol_now();
		$task->fk_task_parent                   = 0;
		$task->array_options['options_fk_risk'] = $riskID;

		$result = $task->create($user, true);

		if ($result > 0) {
			// Creation task OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		} else {
			// Creation task KO
			if (!empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
			else  setEventMessages($task->error, null, 'errors');
		}
	}

	if (!$error && $action == 'saveRiskAssessmentTask' && $permissiontoadd) {
		$riskAssessmentTaskID  = GETPOST('riskAssessmentTaskID');
		$tasktitle             = GETPOST('tasktitle');

		$task->fetch($riskAssessmentTaskID);

		$task->label = $tasktitle;

		$result = $task->update($user, true);

		if ($result > 0) {
			// Update task OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		} else {
			// Update task KO
			if (!empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
			else  setEventMessages($task->error, null, 'errors');
		}
	}

	if (!$error && $action == "deleteRiskAssessmentTask" && $permissiontodelete) {
		$deleteRiskAssessmentTaskId = GETPOST('deletedRiskAssessmentTaskId');

		$task->fetch($deleteRiskAssessmentTaskId);

		$result = $task->delete($user, true);

		if ($result > 0)
		{
			// Delete task OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		}
		else
		{
			// Delete $task KO
			if (!empty($task->errors)) setEventMessages(null, $task->errors, 'errors');
			else  setEventMessages($task->error, null, 'errors');
		}
	}
}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("DigiriskElementRisk");
$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Object card
// ------------------------------------------------------------
$allRisks = 1;
require_once './core/tpl/digiriskdolibarr_risklist_view.tpl.php';

// End of page
llxFooter();
$db->close();
