<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       risk_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for risk
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       		  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');			// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', '1');		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("XFRAMEOPTIONS_ALLOWALL"))   define('XFRAMEOPTIONS_ALLOWALL', '1');			// Do not add the HTTP header 'X-Frame-Options: SAMEORIGIN' but 'X-Frame-Options: ALLOWALL'

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
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/digiriskevaluation.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/mod_task_simple.php';
dol_include_once('/digiriskdolibarr/class/risk.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskevaluation.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_risk_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_evaluation_standard.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');

// load digiriskdolibarr libraries
require_once __DIR__.'/class/risk.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'risklist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new Risk($db);
$extrafields = new ExtraFields($db);
$evaluation = new DigiriskEvaluation($db);
$diroutputmassaction = $conf->digiriskdolibarr->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('risklist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = "t.".key($object->fields); // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = "ASC";
if (!$evalsortfield) $evalsortfield = "evaluation.".key($evaluation->fields);
// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}
foreach ($evaluation->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['evaluation.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($object->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

foreach ($evaluation->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['evaluation.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key])) {
			$arrayfields["ef.".$key] = array(
				'label'=>$extrafields->attributes[$object->table_element]['label'][$key],
				'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1),
				'position'=>$extrafields->attributes[$object->table_element]['pos'][$key],
				'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]),
				'langfile'=>$extrafields->attributes[$object->table_element]['langfile'][$key]
			);
		}
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$permissiontoread = $user->rights->digiriskdolibarr->risk->read;
$permissiontoadd = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;

// Security check
if (empty($conf->digiriskdolibarr->enabled)) accessforbidden('Module not enabled');
$socid = 0;
if ($user->socid > 0)	// Protection if external user
{
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'digiriskdolibarr', $id, '');
//if (!$permissiontoread) accessforbidden();



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($object->fields as $key => $val)
		{
			$search[$key] = '';
		}
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	if ($action == 'add') {

		$riskComment 	= GETPOST('riskComment');
		$fk_element		= GETPOST('id');
		$ref 			= GETPOST('ref');
		$cotation 		= GETPOST('cotation');
		$method 		= GETPOST('cotationMethod');
		$category 		= GETPOST('category');
		$photo 			= GETPOST('photo');

		$risk->description 	= $db->escape($riskComment);
		$risk->fk_element 	= $fk_element ? $fk_element : 0;
		$risk->fk_projet 	= $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
		$risk->category 	= $category;
		$refRiskMod 		= new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
		$refRisk 			= $refRiskMod->getNextValue($risk);

		if ($refRisk) {
			$risk->ref = $refRisk;
		}

		if (!$error)
		{
			$result = $risk->create($user);

			if ($result > 0)
			{
				$task = new Task($db);
				$third_party = new Societe($db);
				$extrafields->fetch_name_optionals_label($task->table_element);

				$taskRef = new $conf->global->PROJECT_TASK_ADDON();

				$task->ref                              = $taskRef->getNextValue($third_party, $task);
				$task->label                            = 'salut';
				$task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
				$task->date_c                           = dol_now();
				$task->fk_task_parent                   = 0;
				$task->array_options['options_fk_risk'] = $risk->id;

				$task->create($user);

				$formation 			= GETPOST('formation');
				$protection 		= GETPOST('protection');
				$occurrence 		= GETPOST('occurrence');
				$gravite 			= GETPOST('gravite');
				$exposition 		= GETPOST('exposition');
				$evaluationComment 	= GETPOST('evaluationComment');
				$evaluation 		= new DigiriskEvaluation($db);
				$refCot 			= new $conf->global->DIGIRISKDOLIBARR_EVALUATION_ADDON();

				//photo upload and thumbs generation

				$pathToECMPhoto = DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo;
				$pathToRiskPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/risk/' . $refRisk ;

				mkdir($pathToRiskPhoto);
				copy($pathToECMPhoto,$pathToRiskPhoto . '/' . $photo);

				global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
				$destfull = $pathToRiskPhoto . '/' . $photo;

				// Create thumbs
				// We can't use $object->addThumbs here because there is no $object known
				// Used on logon for example
				$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
				// Create mini thumbs for image (Ratio is near 16/9)
				// Used on menu or for setup page for example
				$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");

				$evaluation->photo			= $photo;
				$evaluation->cotation 		= $cotation;
				$evaluation->fk_risk 		= $risk->id;
				$evaluation->status 		= 1;
				$evaluation->method 		= $method;
				$evaluation->ref   			= $refCot->getNextValue($evaluation);
				$evaluation->formation  	= $formation;
				$evaluation->protection  	= $protection;
				$evaluation->occurrence  	= $occurrence;
				$evaluation->gravite  		= $gravite;
				$evaluation->exposition  	= $exposition;
				$evaluation->comment 		= $db->escape($evaluationComment);

				$result2 = $evaluation->create($user);

				if ($result2 > 0)
				{
					// Creation OK
					$urltogo = $backtopage ? str_replace('__ID__', $result2, $backtopage) : $backurlforlist;
					$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $evaluation->id, $urltogo); // New method to autoselect project after a New on another form object creation
					header("Location: ".$urltogo);
					exit;
				}
				else
				{
					// Creation KO
					if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
					else  setEventMessages($evaluation->error, null, 'errors');
					$action = 'create';
				}
			}
			else
			{
				// Creation KO
				if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
				else  setEventMessages($risk->error, null, 'errors');
				$action = 'create';
			}
		}
	}

	if ($action == 'saveRisk') {

		$riskID 			= GETPOST('riskID');
		$comment 			= GETPOST('riskComment');
		$cotation 			= GETPOST('cotation');
		$method 			= GETPOST('cotationMethod');
		$evaluationComment 	= GETPOST('evaluationComment');
		$photo 				= GETPOST('photo');

		$formation 	= GETPOST('formation');
		$protection = GETPOST('protection');
		$occurrence = GETPOST('occurrence');
		$gravite 	= GETPOST('gravite');
		$exposition = GETPOST('exposition');

		$evaluation 			= new DigiriskEvaluation($db);
		$evaluation->cotation 	= $cotation;
		$evaluation->fk_risk 	= $riskID;
		$evaluation->status 	= 1;
		$evaluation->method 	= $method;
		$evaluation->comment 	= $db->escape($evaluationComment);
		$evaluation->photo 		= $photo;

		$risk = new Risk($db);
		$risk->fetch($riskID);

		$pathToECMPhoto = DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo;
		$pathToRiskPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/risk/' . $risk->ref ;

		$files = dol_dir_list($pathToRiskPhoto . '/');
		foreach ($files as $file) {
			unlink($pathToRiskPhoto . '/' . $file['name']);
		}

		dol_delete_dir($pathToRiskPhoto );
		mkdir($pathToRiskPhoto);
		copy($pathToECMPhoto,$pathToRiskPhoto . '/' . $photo);


		$refCot 			= new $conf->global->DIGIRISKDOLIBARR_EVALUATION_ADDON();
		$evaluation->ref 	= $refCot->getNextValue($evaluation);

		if ($method == 'digirisk') {
			$evaluation->formation  	= $formation ;
			$evaluation->protection  	= $protection ;
			$evaluation->occurrence  	= $occurrence ;
			$evaluation->gravite  		= $gravite ;
			$evaluation->exposition  	= $exposition ;
		}

		$evaluation->create($user);
	}

	if ($action == "deleteRisk") {

		$id = GETPOST('deletedRiskId');

		$risk = new Risk($db);
		$risk->fetch($id);

		$pathToRiskPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/risk/' . $risk->ref ;

		$files = dol_dir_list($pathToRiskPhoto . '/');
		foreach ($files as $file) {
			unlink($pathToRiskPhoto . '/' . $file['name']);
		}
		dol_delete_dir($pathToRiskPhoto);
		$evaluation = new DigiriskEvaluation($db);
		$lastEvaluations =  $evaluation->fetchFromParent($id);
		foreach ($lastEvaluations as $lastEvaluation ) {
			$lastEvaluation->delete($user);
		}

		$risk->delete($user);
	}

	if ($action == 'addEvaluation') {

				$formation 			= GETPOST('formation');
				$protection 		= GETPOST('protection');
				$occurrence 		= GETPOST('occurrence');
				$gravite 			= GETPOST('gravite');
				$exposition 		= GETPOST('exposition');
				$evaluationComment 	= GETPOST('evaluationComment');
				$riskID 			= GETPOST('riskToAssign');

				$riskComment 	= GETPOST('riskComment');
				$cotation 		= GETPOST('cotation');
				$method 		= GETPOST('cotationMethod');
				$photo 			= GETPOST('photo');

				$evaluation 		= new DigiriskEvaluation($db);
				$refCot 			= new $conf->global->DIGIRISKDOLIBARR_EVALUATION_ADDON();

				$risk = new Risk($db);
				$risk->fetch($riskID);
				//photo upload and thumbs generation

				$pathToECMPhoto = DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo;
				$pathToRiskPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/risk/' . $risk->ref ;

				copy($pathToECMPhoto,$pathToRiskPhoto . '/' . $photo);

				global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
				$destfull = $pathToRiskPhoto . '/' . $photo;

				// Create thumbs
				// We can't use $object->addThumbs here because there is no $object known
				// Used on logon for example
				$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
				// Create mini thumbs for image (Ratio is near 16/9)
				// Used on menu or for setup page for example
				$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");

				$evaluation->photo			= $photo;
				$evaluation->cotation 		= $cotation;
				$evaluation->fk_risk 		= $risk->id;
				$evaluation->status 		= 1;
				$evaluation->method 		= $method;
				$evaluation->ref   			= $refCot->getNextValue($evaluation);
				$evaluation->formation  	= $formation;
				$evaluation->protection  	= $protection;
				$evaluation->occurrence  	= $occurrence;
				$evaluation->gravite  		= $gravite;
				$evaluation->exposition  	= $exposition;
				$evaluation->comment 		= $db->escape($evaluationComment);

				$result2 = $evaluation->create($user);

				if ($result2 > 0)
				{

					exit;
				}
				else
				{
					// Creation KO
					if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
					else  setEventMessages($evaluation->error, null, 'errors');
					$action = 'create';
				}
	}

	if ($action == 'saveEvaluation') {

		$riskID 			= GETPOST('riskID');
		$comment 			= GETPOST('riskComment');
		$cotation 			= GETPOST('cotation');
		$method 			= GETPOST('cotationMethod');
		$evaluationComment 	= GETPOST('evaluationComment');
		$photo 				= GETPOST('photo');

		$formation 	= GETPOST('formation');
		$protection = GETPOST('protection');
		$occurrence = GETPOST('occurrence');
		$gravite 	= GETPOST('gravite');
		$exposition = GETPOST('exposition');

		$evaluation 			= new DigiriskEvaluation($db);
		$evaluation->cotation 	= $cotation;
		$evaluation->fk_risk 	= $riskID;
		$evaluation->status 	= 1;
		$evaluation->method 	= $method;
		$evaluation->comment 	= $db->escape($evaluationComment);
		$evaluation->photo 		= $photo;

		$risk = new Risk($db);
		$risk->fetch($riskID);

		$pathToECMPhoto = DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo;
		$pathToRiskPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/risk/' . $risk->ref ;

		$files = dol_dir_list($pathToRiskPhoto . '/');
		foreach ($files as $file) {
			unlink($pathToRiskPhoto . '/' . $file['name']);
		}

		dol_delete_dir($pathToRiskPhoto );
		mkdir($pathToRiskPhoto);
		copy($pathToECMPhoto,$pathToRiskPhoto . '/' . $photo);


		$refCot 			= new $conf->global->DIGIRISKDOLIBARR_EVALUATION_ADDON();
		$evaluation->ref 	= $refCot->getNextValue($evaluation);

		if ($method == 'digirisk') {
			$evaluation->formation  	= $formation ;
			$evaluation->protection  	= $protection ;
			$evaluation->occurrence  	= $occurrence ;
			$evaluation->gravite  		= $gravite ;
			$evaluation->exposition  	= $exposition ;
		}

		$evaluation->create($user);
	}

	if ($action == "deleteEvaluation") {

		$id = GETPOST('deletedRiskId');

		$risk = new Risk($db);
		$risk->fetch($id);

		$pathToRiskPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/risk/' . $risk->ref ;

		$files = dol_dir_list($pathToRiskPhoto . '/');
		foreach ($files as $file) {
			unlink($pathToRiskPhoto . '/' . $file['name']);
		}
		dol_delete_dir($pathToRiskPhoto);
		$evaluation = new DigiriskEvaluation($db);
		$lastEvaluations =  $evaluation->fetchFromParent($id);
		foreach ($lastEvaluations as $lastEvaluation ) {
			$lastEvaluation->delete($user);
		}

		$risk->delete($user);
	}

	// Mass actions
	$objectclass = 'Risk';
	$objectlabel = 'Risk';
	$uploaddir = $conf->digiriskdolibarr->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$form = new Form($db);

$now = dol_now();
//$evaluation = new DigiriskEvaluation($db);
//
//$evaluation->fields['ref']['visible'] = 0;
//$evaluation->fields['cotation']['visible'] = 4;

//$help_url="EN:Module_Risk|FR:Module_Risk_FR|ES:Módulo_Risk";
$help_url = '';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("Risks"));


// Build and execute select
// --------------------------------------------------------------------
if (!preg_match('/(evaluation)/', $sortfield)) {

	$sql = 'SELECT ';
	foreach ($object->fields as $key => $val)
	{
			$sql .= 't.'.$key.', ';
	}
// Add fields from extrafields
	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
	}
// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
	$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
//	if ($object->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($object->element).")";
	$sql .= " WHERE 1 = 1";
	$sql .= " AND entity = ".$conf->entity;
	foreach ($search as $key => $val)
	{
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
		if (strpos($object->fields[$key]['type'], 'integer:') === 0) {
			if ($search[$key] == '-1') $search[$key] = '';
			$mode_search = 2;
		}
		if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
	}
	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	/* If a group by is required
	$sql.= " GROUP BY ";
	foreach($object->fields as $key => $val)
	{
		$sql.='t.'.$key.', ';
	}
	// Add fields from extrafields
	if (! empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
	}
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListGroupBy',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
	$sql=preg_replace('/,\s*$/','', $sql);
	*/

	$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$resql = $db->query($sql);

		$nbtotalofrecords = $db->num_rows($resql);
		if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
		{
			$page = 0;
			$offset = 0;
		}
	}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
	{
		$num = $nbtotalofrecords;
	}
	else
	{
		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if (!$resql)
		{
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}

// Direct jump if only one record found
	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?id='.$id);
		exit;
	}

} else {
	$sql = 'SELECT ';
	foreach ($evaluation->fields as $key => $val)
	{
		$sql .= 'evaluation.'.$key.', ';
	}
// Add fields from extrafields
	if (!empty($extrafields->attributes[$evaluation->table_element]['label'])) {
		foreach ($extrafields->attributes[$evaluation->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$evaluation->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
	}
// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
	$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= " FROM ".MAIN_DB_PREFIX.$evaluation->table_element." as evaluation";
	if (is_array($extrafields->attributes[$evaluation->table_element]['label']) && count($extrafields->attributes[$evaluation->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$evaluation->table_element."_extrafields as ef on (evaluation.rowid = ef.fk_object)";
	if ($evaluation->ismultientitymanaged == 1) $sql .= " WHERE evaluation.entity IN (".getEntity($evaluation->element).")";
	else $sql .= " WHERE 1 = 1";
	$sql .= " AND status = 1";
	foreach ($search as $key => $val)
	{
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($evaluation->isInt($evaluation->fields[$key]) || $evaluation->isFloat($evaluation->fields[$key])) ? 1 : 0);
		if (strpos($evaluation->fields[$key]['type'], 'integer:') === 0) {
			if ($search[$key] == '-1') $search[$key] = '';
			$mode_search = 2;
		}
		if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
	}
	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$resql = $db->query($sql);

		$nbtotalofrecords = $db->num_rows($resql);
		if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
		{
			$page = 0;
			$offset = 0;
		}
	}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
	{
		$num = $nbtotalofrecords;
	}
	else
	{
		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if (!$resql)
		{
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}

// Direct jump if only one record found
	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?id='.$id);
		exit;
	}
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
foreach ($search as $key => $val)
{
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
	else $param .= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param .= '&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>$langs->trans("Validate"),
	//'generate_doc'=>$langs->trans("ReGeneratePDF"),
	//'builddoc'=>$langs->trans("PDFMerge"),
	//'presend'=>$langs->trans("SendByMail"),
);
if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendRiskRef";
$modelmail = "risk";
$objecttmp = new Risk($db);
$trackid = 'xxxx'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';*/

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (!empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $object->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		}
		elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		print '</td>';
	}
}

foreach ($evaluation->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';
	if (!empty($arrayfields['evaluation.'.$key]['checked']))
	{
		print '<td class="liste_titre'.'">';
		if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $evaluation->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		}
		elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		print '</td>';
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';

	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}

}

foreach ($evaluation->fields as $key => $val)
{
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';

	if (!empty($arrayfields['evaluation.'.$key]['checked']))
	{
		$cssforfield = '';

		if (GETPOST('sortorder') == 'asc') {
			$sortorder = 'desc';
		} else {
			$sortorder = 'asc';
		}
		print getTitleFieldOfList($arrayfields['evaluation.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 'evaluation.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $evalsortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}

}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Ajouter contenu de la liste titre

//print getTitleFieldOfList($langs->trans('Evaluation') , $_SERVER['PHP_SELF'], '', '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''));



// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val)
	{
		if (preg_match('/\$object/', $val)) $needToFetchEachLine++; // There is at least one compute field that use $object
	}
}


// Loop on record
// --------------------------------------------------------------------

// contenu
$i = 0;
$totalarray = array();

while ($i < ($limit ? min($num, $limit) : $num))
{
	$obj = $db->fetch_object($resql);



	if (empty($obj)) break; // Should not happen

	// Si on trie avec un champ d'une évaluation, on fetch le risque et non l'évaluation
	if ($obj->fk_risk > 0) {
		$object->fetch($obj->fk_risk);
	} else {
		// Store properties in $object
		$object->setVarsFromFetchObj($obj);
	}

	// Show here line of result
	print '<tr class="oddeven" id="risk_row_'. $object->id .'">';

	foreach ($object->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
		elseif ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';

		if (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield .= ($cssforfield ? ' ' : '').'right';
		//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').' style="width:2%">';
			if ($key == 'status') print $object->getLibStatut(5);

			elseif ($key == 'category') {
				?>
				<div class="table-cell table-50 cell-risk" data-title="Risque">
					<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
						 data-tooltip-persist="true"
						 data-color="red"
						 aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">
						<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $object->get_danger_category($object) . '.png' ; ?>" aria-label="" />
					</div>
				</div>
				<?php
			}
			else print $object->showOutputField($val, $key, $object->$key, '');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!empty($val['isameasure']))
			{
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
				$totalarray['val']['t.'.$key] += $object->$key;
			}
		}

	}
	$lastEvaluation = $evaluation->fetchFromParent($object->id, 1);
	if (!empty ($lastEvaluation)) {
		$lastEvaluation = array_shift($lastEvaluation);

		if (empty($evaluation)) break; // Should not happen

		// Store properties in $object

		foreach ($lastEvaluation->fields as $key => $val)
		{
			$cssforfield = (empty($val['css']) ? '' : $val['css']);
			if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
			elseif ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';

			if (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';

			if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield .= ($cssforfield ? ' ' : '').'right';
			//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

			if (!empty($arrayfields['evaluation.'.$key]['checked']))
			{
				$cssforfield = '';
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
				if ($key == 'status') print $lastEvaluation->getLibStatut(5);
				elseif ($key == 'cotation') {
					$cotationList = $evaluation->fetchFromParent($object->id);
					?>

					<!-- Afficahge évaluation dans tableau -->
					<div class="risk-evaluation wpeo-modal-event ">
						<div id="cotation_square<?php echo $object->id ?>" class="evaluation-cotation" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>">
							<span><?php echo $lastEvaluation->cotation; ?></span>
						</div>
						<div class="evaluation-photo">
							<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element.'/'.$object->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
							if (count($filearray)) {
								print '<span class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $object->element).'</span>';
							} else {
								$nophoto = '/public/theme/common/nophoto.png'; ?>
								<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
							<?php } ?>
						</div>
						<div class="evaluation-content">
							<div class="evaluation-data">
								<span class="evaluation-reference evaluation-list action cotation default-cotation modal-open" value="<?php echo $object->id ?>"><?php echo $lastEvaluation->ref; ?></span>
								<span class="evaluation-author">
									<?php $user->fetch($lastEvaluation->fk_user_creat); ?>
									<?php echo $user->getNomUrl( 0, '', 0, 0, 2 ); ?>
								</span>
								<span class="evaluation-date">
									<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', $lastEvaluation->date_creation); ?>
								</span>
								<span class="evaluation-count"><i class="fas fa-comments"></i><?php echo count($cotationList) ?></span>
							</div>
							<div class="evaluation-comment">
								<?php echo $lastEvaluation->comment; ?>
							</div>
						</div>
						<div class="evaluation-add wpeo-button button-square-40 button-primary modal-open" value="<?php echo $object->id ?>">
							<i class="fas fa-plus button-icon"></i>
						</div>
					</div>

					<div class="cotation-container grid wpeo-modal-event tooltip hover cotation-square" id="cotation_square<?php echo $object->id ?>">

						<!-- Modal-EvaluationsList -->

						<div id="list_evaluations<?php echo $object->id ?>" class="wpeo-modal" value="<?php echo $object->id ?>">
							<div class="modal-container wpeo-modal-event">
								<!-- Modal-Header -->
								<div class="modal-header">
									<h2 class="modal-title">
										<?php echo $langs->trans('EvaluationsList') ?>
										<div class="evaluation-add wpeo-button button-square-40 button-primary">
											<i class="fas fa-plus button-icon"></i>
										</div>
									</h2>

									<div class="modal-close"><i class="fas fa-times"></i></div>
								</div>
								<!-- Modal-Content -->
								<div class="modal-content" id="#modalContent">
									<ul class="evaluations-list" style="display: grid">
									<?php
										$cotationList = $evaluation->fetchFromParent($object->id);
										if (!empty($cotationList)) :
											foreach ($cotationList as $cotation) : ?>
												<div class="risk-evaluation">
													<div id="cotation_square<?php echo $object->id ?>" class="evaluation-cotation" data-scale="<?php echo $cotation->get_evaluation_scale() ?>">
														<span><?php echo $cotation->cotation; ?></span>
													</div>
													<div class="evaluation-photo">

													<i class="fas fa-image"></i>

													</div>
													<div class="evaluation-content">
														<div class="evaluation-data">
															<span class="evaluation-reference action cotation default-cotation" value="<?php echo $object->id ?>"><?php echo $cotation->ref; ?></span>
															<span class="evaluation-author">
																<?php $user->fetch($cotation->fk_user_creat); ?>
																<?php echo $user->getNomUrl( 0, '', 0, 0, 2 ); ?>
															</span>
															<span class="evaluation-date">
																<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', $cotation->date_creation); ?>
															</span>
															<span class="evaluation-count"><i class="fas fa-comments"></i><?php echo count($cotationList) ?></span>
														</div>
														<div class="evaluation-comment">
															<?php echo $cotation->comment; ?>
														</div>
													</div>
													<div class="evaluation-edit wpeo-button button-square-40 button-primary">
														<i class="fas fa-edit button-icon"></i>
													</div>
													<div class="evaluation-delete wpeo-button button-square-40 button-grey">
														<i class="fas fa-times button-icon"></i>
													</div>
												</div>
											<?php endforeach; ?>
										<?php endif; ?>
									</ul>
								</div>
								<!-- Modal-Footer -->
								<div class="modal-footer">
									<div class="wpeo-button button-grey modal-close">
										<span><?php echo $langs->trans('CloseModal'); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>

<!--					MODAL EDIT / ADD EVALUATION
-->
					<?php if ($object->id < 1) $idObj = 0;
					else $idObj = $object->id;
					?>
					<div id="edit_add_evaluation<?php echo $object->id ?>" class="wpeo-modal risk-evaluation modal-risk-<?php echo $object->id ?>" value="<?php echo $object->id ?>">
						<div class="modal-container wpeo-modal-event">
							<!-- Modal-Header -->
							<div class="modal-header">
								<h2 class="modal-title">
									<?php echo $langs->trans('EvaluationCreate') . ' - Risque ' . $object->id ?>
								</h2>

								<div class="modal-close"><i class="fas fa-times"></i></div>
							</div>
							<!-- Modal-Content -->
							<div class="modal-content" id="#modalContent<?php echo $object->id ?>">
								<div class="w-100 minwidth100 wpeo-button button-square-40 evaluation-standard select-evaluation-method button-blue">
									<input id="cotationMethod<?php echo $object->id ?>" type="hidden" class="evaluation-method" value="standard">

									Simplifié
								</div>

								<div class="w-100 minwidth100 wpeo-button button-square-40 evaluation-advanced select-evaluation-method button-grey">
									<input type="hidden" class="evaluation-method" value="advanced">
									Avancé
								</div>
								<div>
									Cotation
									<div class="wpeo-gridlayout cotation-standard">
										<?php
										$defaultCotation = array(0, 48, 51, 100);
										$evaluation = new DigiriskEvaluation($db);
										if ( ! empty( $defaultCotation )) :
											foreach ( $defaultCotation as $request ) :
												$evaluation->cotation = $request; ?>
												<div data-id="<?php echo 0; ?>"
													 data-evaluation-method="standard"
													 data-evaluation-id="<?php echo $request; ?>"
													 data-variable-id="<?php echo 152+$request; ?>"
													 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
													 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
													 class="evaluation-cotation cotation wpeo-button"><?php echo $request; ?>
												</div>
											<?php endforeach;
										endif; ?>
									</div>
									<?php
									$string = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
									$json_a = json_decode($string, true);

									$evaluation_method = $json_a[0];
									$evaluation_method_survey = $evaluation_method['option']['variable'];
									?>
									<div class="wpeo-gridlayout cotation-advanced" style="display:none">
										<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $object->id ; ?>" />
										<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
										<p><i class="fas fa-info-circle"></i> <?php echo 'Cliquez sur les cases du tableau pour remplir votre évaluation'; ?></p>
										<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
											<div class="table-row table-header">
												<div class="table-cell">
													<span></span>
												</div>
												<?php
												for ( $l = 0; $l < count($evaluation_method_survey); $l++ ) :
													?>
													<div class="table-cell">
														<span><?php echo $l; ?></span>
													</div>
												<?php
												endfor; ?>
											</div>
											<?php $l = 0; ?>
											<?php foreach($evaluation_method_survey as $critere) {
												$name = strtolower($critere['name']);
												?>
												<div class="table-row">
													<div class="table-cell"><?php echo $critere['name'] ; ?></div>
													<?php foreach($critere['option']['survey']['request'] as $request) {
														?>
														<div class="table-cell can-select cell-<?php echo $object->id ?>"
															 data-type="<?php echo $name ?>"
															 data-id="<?php echo  $object->id ? $object->id : 0 ; ?>"
															 data-evaluation-id="<?php echo $evaluation_id ? $evaluation_id : 0 ; ?>"
															 data-variable-id="<?php echo $l ; ?>"
															 data-seuil="<?php echo  $request['seuil']; ?>">
															<?php echo  $request['question'] ; ?>
														</div>
													<?php } $l++;  ?>
												</div>
											<?php } ?>
										</div>
									</div>
								</div>

								<div>
									<div class="photo-container grid wpeo-modal-event tooltip hover">
									PHOTO :
										<?php
										$relativepath = 'digiriskdolibarr/medias';
										$modulepart = 'ecm';
										$path = '/dolibarr/htdocs/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
										$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type.'/'.$element['object']->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
										if (count($filearray)) : ?>
											<?php print '<span class="floatleft inline-block valignmiddle divphotoref">'.$element['object']->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $element['object']->element_type).'</span>'; ?>
										<?php else : ?>
											<?php $nophoto = '/public/theme/common/nophoto.png'; ?>
											<div class="action photo evaluation-photo-open default-photo modal-open" value="<?php echo $object->id ?>">

												<span class="floatleft inline-block valignmiddle divphotoref photo-edit<?php echo $object->id ?>">
													<input type="hidden" value="<?php echo $path ?>" id="pathToPhoto<?php echo $object->id ?>">
													<img class="photo maxwidth50"  src="<?php echo DOL_URL_ROOT.'/public/theme/common/nophoto.png' ?>">
												</span>
											</div>

											<!-- Modal-AddPhoto -->

											<div id="photo_modal<?php echo $object->id ?>" class="wpeo-modal modal-photo">
												<div class="modal-container wpeo-modal-event">
													<!-- Modal-Header -->
													<div class="modal-header">
														<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
														<div class="modal-close"><i class="fas fa-times"></i></div>
													</div>
													<!-- Modal-Content -->
													<div class="modal-content" id="#modalContent<?php echo $object->id ?>">
														Ajoutez de nouveaux fichiers dans 'digiriskdolibarr/medias'
														<div class="action">
															<a href="<?php echo '../../ecm/index.php' ?>" target="_blank">
																<div class="wpeo-button button-square-50 button-event add action-input button-progress">
																	<i class="button-icon fas fa-plus"></i>
																</div>
															</a>
														</div>

														<input type="hidden" id="photoLinked<?php echo $object->id ?>" value="">
														<div class="wpeo-table table-row">
															<?php
															$files =  dol_dir_list(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias');
															$relativepath = 'digiriskdolibarr/medias';
															$modulepart = 'ecm';
															$path = '/dolibarr/htdocs/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
															$j = 0;

															if ( !empty($files) ) {
																foreach ($files as $file) {
																	print '<div class="table-cell center clickable-photo clickable-photo'. $j .'" value="'. $j .'">';
																	if (image_format_supported($file['name']) >= 0)
																	{
																		$fullpath = $path . '/' . $file['relativename'] . '&entity=' . $conf->entity;
																		?>
																			<input type="hidden" id="filename<?php echo $object->id ?>" value="<?php echo $file['name'] ?>">
																			<img class="photo photo<?php echo $j ?> maxwidth200" src="<?php echo $fullpath; ?>">
																		<?php
																	}
																	else {
																		print '&nbsp;';
																	}
																	$j++;

																}
															}
														?>
														</div>
													</div>

													<!-- Modal-Footer -->
													<div class="modal-footer">
														<div class="wpeo-button button-grey button-blue save-photo">
															<span><?php echo $langs->trans('SavePhoto'); ?></span>
														</div>
														<div class="wpeo-button button-grey modal-close">
															<span><?php echo $langs->trans('CloseModal'); ?></span>
														</div>
													</div>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>
								<div>
									<div data-title="evaluationComment<?php echo $object->id ?>" class="table-cell table-100 cell-comment">
										Commentaire :
										<?php print '<textarea name="evaluationComment'. $object->id .'" id="evaluationComment'. $object->id .'" class="minwidth150" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
									</div>
								</div>

							</div>


							<!-- Modal-Footer -->
							<div class="modal-footer">
								<?php
									$evaluation = new DigiriskEvaluation($db);
									$evaluation->cotation = 0;
								?>
								<div data-scale="<?php echo $evaluation->get_evaluation_scale() ?>" class="cotation cotation-span<?php echo $object->id ?>">
									<span id="current_equivalence<?php echo $object->id ?>"></span>
								</div>

								<div class="wpeo-button evaluation-create button-blue modal-close">
								<input type="hidden" value="<?php echo $object->id ?>" class="risk-to-assign">
									<i class="fas fa-plus"></i>
										<?php echo $langs->trans('Add'); ?>
								</div>
							</div>
						</div>
					</div>

		<?php	} elseif ($key == 'has_tasks') {
					?>
					<div class="table-cell cell-tasks" data-title="Tâches" class="padding">
						<span class="cell-tasks-container">
		<!--				VUE SI Y A DES TACHES   -->
							<?php $related_tasks = $object->get_related_tasks($object);
							if (($related_tasks !== -1) ) :
								foreach ($related_tasks as $related_task) :
									$related_task->fetchTimeSpent($related_task->id); ?>
									<span class="ref"><?php echo $related_task->ref; ?></span>
		<!--							// @todo truc sympa pour l'author.-->
									<span class="author">
										<div class="avatar" style="background-color: #50a1ed;">
											<?php $user = new User($db); ?>
											<?php $user->fetch($related_task->fk_user_creat); ?>
											<span><?php echo $user->firstname[0] . $user->lastname[0]; ?></span>
										</div>
									</span>
									<span class="date"><i class="fas fa-calendar-alt"></i><?php echo date("d/m/Y", $related_task->date_c) ?></span>
									<span class="label"><?php echo $related_task->label; ?></span>
									<!--													print '<a target="_blank" href="/dolibarr/htdocs/projet/tasks/time.php?id=' . $related_task->id . '&withproject=1">' . '&nbsp' .  gmdate('H:i', $related_task->duration_effective ) . '</a>';-->
								<?php endforeach; ?>
							<?php else : ?>
								<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>
							<?php endif; ?>
						</span>
						<!--									VUE SI Y EN A PAS   -->
					</div>
				<?php
				}
				else print $lastEvaluation->showOutputField($val, $key, $lastEvaluation->$key, '');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!empty($val['isameasure']))
				{
					if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
					$totalarray['val']['t.'.$key] += $lastEvaluation->$key;
				}
			}
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
//	print '<td class="nowrap"><td>'. $langs->trans('Evaluation') .'</td>';
//	print '</td>';
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected = 0;
		if (in_array($object->id, $arrayofselected)) $selected = 1;
		print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}

	print '</td>';
	if (!$i) $totalarray['nbfield']++;

	print '</tr>'."\n";
	$i++;

}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0)
{
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}


$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', $arrayofmassactions) && ($nbtotalofrecords === '' || $nbtotalofrecords))
{
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_digiriskdolibarr', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
