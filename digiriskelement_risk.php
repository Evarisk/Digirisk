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
 *   	\file       digiriskelement_risk.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view risk
 */


// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/class/riskanalysis/risk.class.php');
dol_include_once('/digiriskdolibarr/class/riskanalysis/riskassessment.class.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/risk/mod_risk_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_function.lib.php');

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
$object            = new DigiriskElement($db);
$risk              = new Risk($db);
$evaluation        = new RiskAssessment($db);
$extrafields       = new ExtraFields($db);
$refRiskMod        = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
$refEvaluationMod  = new $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON();

$hookmanager->initHooks(array('riskcard', 'globalcard')); // Note that conf->hooks_modules contains array

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

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($risk->fields as $key => $val)
		{
			$search[$key] = '';
		}
		foreach ($evaluation->fields as $key => $val)
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

	$error = 0;

	$backtopage = dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?id='.($id > 0 ? $id : '__ID__');

	if (!$error && $action == 'add' && $permissiontoadd) {
		$riskComment = GETPOST('riskComment');
		$fk_element  = GETPOST('id');
		$ref         = GETPOST('ref');
		$cotation    = GETPOST('cotation');
		$method      = GETPOST('cotationMethod');
		$category    = GETPOST('category');
		$photo       = GETPOST('photo');

		$risk->description = $db->escape($riskComment);
		$risk->fk_element  = $fk_element ? $fk_element : 0;
		$risk->fk_projet   = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
		$risk->category    = $category;
		$risk->ref         = $refRiskMod->getNextValue($risk);

		if (!$error) {
			$result = $risk->create($user, true);
			if ($result > 0) {
				$evaluationComment 	= GETPOST('evaluationComment');

				$evaluation->photo       = $photo;
				$evaluation->cotation    = $cotation;
				$evaluation->fk_risk     = $risk->id;
				$evaluation->status      = 1;
				$evaluation->method      = $method;
				$evaluation->ref         = $refEvaluationMod->getNextValue($evaluation);
				$evaluation->comment     = $db->escape($evaluationComment);

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
				$pathToECMPhoto = DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo;
				$pathToEvaluationPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/riskassessment/' . $evaluation->ref;

				mkdir($pathToEvaluationPhoto);
				copy($pathToECMPhoto,$pathToEvaluationPhoto . '/' . $photo);

				global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
				$destfull = $pathToEvaluationPhoto . '/' . $photo;

				// Create thumbs
				$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
				// Create mini thumbs for image (Ratio is near 16/9)
				$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");

				$result2 = $evaluation->create($user);

				if ($result2 > 0) {
					// Creation risk + evaluation OK
					$urltogo = str_replace('__ID__', $result2, $backtopage);
					$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
					header("Location: ".$urltogo);
					exit;
				} else {
					// Creation evaluation KO
					if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
					else  setEventMessages($evaluation->error, null, 'errors');
				}
			}
			else
			{
				// Creation risk KO
				if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
				else  setEventMessages($risk->error, null, 'errors');
			}
		}
	}

	if (!$error && $action == 'saveRisk' && $permissiontoadd) {
		$riskID      = GETPOST('riskID');
		$description = GETPOST('riskComment');
		$category    = GETPOST('riskCategory');

		$risk->fetch($riskID);

		$risk->description = $description;
		$risk->category    = $category;

		$result = $risk->update($user);

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
						dol_delete_dir($pathToEvaluationPhoto. '/thumbs');
						dol_delete_dir($pathToEvaluationPhoto);

						$lastEvaluation->delete($user);
					}
				}

				$result = $risk->delete($user);

				if ($result > 0) {
					// Delete risk OK
					$urltogo = str_replace('__ID__', $result, $backtopage);
					$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
					header("Location: ".$urltogo);
					exit;
				} else {
					// Delete risk KO
					if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
					else  setEventMessages($risk->error, null, 'errors');
				}
			}
		}
	}

	if (!$error && $action == 'addEvaluation' && $permissiontoadd) {
		$evaluationComment = GETPOST('evaluationComment');
		$riskID            = GETPOST('riskToAssign');
		$cotation          = GETPOST('cotation');
		$method            = GETPOST('cotationMethod');
		$photo             = GETPOST('photo');

		$risk->fetch($riskID);
		//photo upload and thumbs generation
		if ( !empty ($photo) ) {
			$pathToECMPhoto = DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo;
			$pathToEvaluationPhoto = DOL_DATA_ROOT . '/digiriskdolibarr/riskassessment/' . $refEvaluationMod->getNextValue($evaluation);

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

		$evaluation->photo      = $photo;
		$evaluation->cotation   = $cotation;
		$evaluation->fk_risk    = $risk->id;
		$evaluation->status     = 1;
		$evaluation->method     = $method;
		$evaluation->ref        = $refEvaluationMod->getNextValue($evaluation);
		$evaluation->comment    = $db->escape($evaluationComment);

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

		$result = $evaluation->create($user);

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
		$evaluationComment = GETPOST('evaluationComment');
		$photo             = GETPOST('photo');

		$evaluation->fetch($evaluationID);

		$evaluation->cotation = $cotation;
		$evaluation->method   = $method;
		$evaluation->comment  = $db->escape($evaluationComment);
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

		$pathToECMPhoto        = DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo;
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

		copy($pathToECMPhoto,$pathToEvaluationPhoto . '/' . $photo);

		global $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall ;
		$destfull = $pathToEvaluationPhoto . '/' . $photo;

		// Create thumbs
		$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
		// Create mini thumbs for image (Ratio is near 16/9)
		$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");

		$result = $evaluation->update($user);

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
		$result = $evaluation->delete($user);
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
}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("DigiriskElementRisk");
$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");

digiriskHeader('', $title, $help_url, '', '', '', $morejs);

print '<div id="cardContent" value="">';

if ($object->id > 0) {
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);
	dol_fiche_head($head, 'elementRisk', $langs->trans("DigiriskElementRisk"), -1, 'digiriskdolibarr@digiriskdolibarr');

	// Object card
	// ------------------------------------------------------------
	$width = 80;
	$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';
	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print '<div class="fichecenter wpeo-wrap">';
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<div class="underbanner clearboth"></div>';

	$advanced_method_cotation_json  = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
	$advanced_method_cotation_array = json_decode($advanced_method_cotation_json, true);

	// Build and execute select
	// --------------------------------------------------------------------
	if (!preg_match('/(evaluation)/', $sortfield)) {
		$sql = 'SELECT ';
		foreach ($risk->fields as $key => $val)
		{
			$sql .= 't.'.$key.', ';
		}
		// Add fields from extrafields
			if (!empty($extrafields->attributes[$risk->table_element]['label'])) {
				foreach ($extrafields->attributes[$risk->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$risk->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
			}
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $risk); // Note that $action and $risk may have been modified by hook
		$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
		$sql = preg_replace('/,\s*$/', '', $sql);
		$sql .= " FROM ".MAIN_DB_PREFIX.$risk->table_element." as t";
		if (is_array($extrafields->attributes[$risk->table_element]['label']) && count($extrafields->attributes[$risk->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$risk->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
		if ($risk->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($risk->element).")";
		else $sql .= " WHERE 1 = 1";
		$sql .= " AND fk_element = ".$id;

		foreach ($search as $key => $val)
		{
			if ($key == 'status' && $search[$key] == -1) continue;
			$mode_search = (($risk->isInt($risk->fields[$key]) || $risk->isFloat($risk->fields[$key])) ? 1 : 0);
			if (strpos($risk->fields[$key]['type'], 'integer:') === 0) {
				if ($search[$key] == '-1') $search[$key] = '';
				$mode_search = 2;
			}
			if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
		if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
		// Add where from extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $risk); // Note that $action and $risk may have been modified by hook
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
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$risk->table_element." as r on (evaluation.fk_risk = r.rowid)";
	if (is_array($extrafields->attributes[$evaluation->table_element]['label']) && count($extrafields->attributes[$evaluation->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$evaluation->table_element."_extrafields as ef on (evaluation.rowid = ef.fk_object)";
	if ($evaluation->ismultientitymanaged == 1) $sql .= " WHERE evaluation.entity IN (".getEntity($evaluation->element).")";
	else $sql .= " WHERE 1 = 1";
	$sql .= " AND evaluation.status = 1";
	$sql .= " AND r.fk_element =".$id;
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
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sql .= $db->order($sortfield, $sortorder);

	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		exit;
	}
		$num = $db->num_rows($resql);
	}

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	$param .= '&id='.$id;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	foreach ($search as $key => $val)
	{
		if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
		else $param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array();
	if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions); ?>

	<!-- BUTTON MODAL RISK ADD -->
	<?php $newcardbutton = '<div class="risk-add wpeo-button button-square-40 button-blue modal-open" value="'.$object->id.'"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add animated"></i></div>' ?>

	<!-- RISK ADD MODAL-->
	<div class="risk-add-modal" value="<?php echo $object->id ?>">
		<div class="wpeo-modal modal-risk-0" id="risk_add<?php echo $object->id ?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('AddRiskTitle') . ' ' . $refRiskMod->getNextValue($risk);  ?></h2>
					<div class="modal-refresh modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- Modal-ADD Risk Content-->
				<div class="modal-content" id="#modalContent">
					<div class="risk-content">
						<div class="risk-category">
							<span class="title"><?php echo $langs->trans('Risk'); ?><required>*</required></span>
							<input class="input-hidden-danger" type="hidden" name="risk_category_id" value="undefined" />
							<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
								<div class="dropdown-toggle dropdown-add-button button-cotation">
									<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-exclamation-triangle button-icon"></i><i class="fas fa-plus-circle button-add"></i></span>
									<img class="danger-category-pic hidden tooltip hover" src="" />
								</div>
								<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
									<?php
									$dangerCategories = $risk->get_danger_categories();
									if ( ! empty( $dangerCategories ) ) :
										foreach ( $dangerCategories as $dangerCategory ) : ?>
											<li class="item dropdown-item wpeo-tooltip-event classfortooltip" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>">
												<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
											</li>
										<?php endforeach;
									endif; ?>
								</ul>
							</div>
						</div>
						<div class="risk-description">
							<span class="title"><?php echo $langs->trans('Description'); ?></span>
							<?php print '<textarea name="riskComment" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
						</div>
						<hr>
					</div>
					<div class="risk-evaluation-container standard">
						<span class="section-title"><?php echo ' ' . $langs->trans('Evaluation'); ?></span>
						<div class="risk-evaluation-header">
							<div class="wpeo-button evaluation-standard select-evaluation-method selected button-blue">
								<span><?php echo $langs->trans('SimpleCotation') ?></span>
							</div>
							<div class="wpeo-button evaluation-advanced select-evaluation-method button-grey">
								<span><?php echo $langs->trans('AdvancedCotation') ?></span>
							</div>
							<input class="risk-evaluation-method" type="hidden" value="standard">
						</div>
						<div class="risk-evaluation-content-wrapper">
							<div class="risk-evaluation-content">
								<div class="cotation-container">
									<div class="cotation-standard">
										<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?><required>*</required></span>
										<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
											<?php
											$defaultCotation = array(0, 48, 51, 100);
											if ( ! empty( $defaultCotation )) :
												foreach ( $defaultCotation as $request ) :
													$evaluation->cotation = $request; ?>
													<div data-id="<?php echo 0; ?>"
														 data-evaluation-method="standard"
														 data-evaluation-id="<?php echo $request; ?>"
														 data-variable-id="<?php echo 152+$request; ?>"
														 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
														 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
														 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
												<?php endforeach;
											endif; ?>
										</div>
									</div>
									<input class="risk-evaluation-seuil" type="hidden" value="undefined">
									<?php
									$evaluation_method = $advanced_method_cotation_array[0];
									$evaluation_method_survey = $evaluation_method['option']['variable'];
									?>
									<div class="wpeo-gridlayout cotation-advanced" style="display:none">
										<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
										<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
										<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectCotation') ?><required>*</required></span>
										<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
											<div class="table-row table-header">
												<div class="table-cell">
													<span></span>
												</div>
												<?php for ( $l = 0; $l < count($evaluation_method_survey); $l++ ) : ?>
													<div class="table-cell">
														<span><?php echo $l; ?></span>
													</div>
												<?php endfor; ?>
											</div>
											<?php $l = 0; ?>
											<?php foreach($evaluation_method_survey as $critere) :
											$name = strtolower($critere['name']); ?>
											<div class="table-row">
												<div class="table-cell"><?php echo $critere['name'] ; ?></div>
												<?php foreach($critere['option']['survey']['request'] as $request) : ?>
													<div class="table-cell can-select cell-<?php echo  $risk->id ? $risk->id : 0 ; ?>"
														 data-type="<?php echo $name ?>"
														 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
														 data-evaluation-id="<?php echo $evaluation_id ? $evaluation_id : 0 ; ?>"
														 data-variable-id="<?php echo $l ; ?>"
														 data-seuil="<?php echo  $request['seuil']; ?>">
														<?php echo  $request['question'] ; ?>
													</div>
												<?php endforeach; $l++; ?>
											</div>
										<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
							<div class="risk-evaluation-photo">
								<span class="title"><?php echo $langs->trans('Photo'); ?></span>
								<div class="risk-evaluation-photo-container wpeo-modal-event tooltip hover">
									<?php
									$relativepath = 'digiriskdolibarr/medias';
									$modulepart = 'ecm';
									$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
									$nophoto = '/public/theme/common/nophoto.png'; ?>
									<!-- BUTTON RISK EVALUATION PHOTO MODAL -->
									<div class="action risk-evaluation-photo default-photo modal-open" value="<?php echo $object->id ?>">
										<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
											<input type="hidden" value="<?php echo $path ?>">
											<input class="filename" type="hidden" value="">
											<img class="photo maxwidth50"  src="<?php echo DOL_URL_ROOT.'/public/theme/common/nophoto.png' ?>">
										</span>
									</div>
									<!-- RISK EVALUATION PHOTO MODAL -->
									<div class="wpeo-modal modal-photo" id="risk_evaluation_photo<?php echo $object->id ?>">
										<div class="modal-container wpeo-modal-event">
											<!-- Modal-Header -->
											<div class="modal-header">
												<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
												<div class="modal-close"><i class="fas fa-times"></i></div>
											</div>
											<!-- Modal-Content -->
											<div class="modal-content" id="#modalContent<?php echo $object->id ?>">
												<div class="action">
													<a href="<?php echo '../../ecm/index.php' ?>" target="_blank">
														<div class="wpeo-button button-square-50 button-blue">
															<i class="button-icon fas fa-plus"></i>
														</div>
													</a>
												</div>
												<div class="wpeo-table table-row">
													<?php
													$files =  dol_dir_list(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias');
													$relativepath = 'digiriskdolibarr/medias';
													$modulepart = 'ecm';
													$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
													$j = 0;

													if ( !empty($files) ) :
														foreach ($files as $file) :
															print '<div class="table-cell center clickable-photo clickable-photo'. $j .'" value="'. $j .'">';
															if (image_format_supported($file['name']) >= 0) :
																$fullpath = $path . '/' . $file['relativename'] . '&entity=' . $conf->entity; ?>
																<input class="filename" type="hidden" value="<?php echo $file['name'] ?>">
																<img class="photo photo<?php echo $j ?> maxwidth200" src="<?php echo $fullpath; ?>">
															<?php else : print '&nbsp;';
															endif;
															$j++;
															print '</div>';
														endforeach;
													endif; ?>
												</div>
											</div>
											<!-- Modal-Footer -->
											<div class="modal-footer">
												<div class="save-photo wpeo-button button-blue">
													<span><?php echo $langs->trans('SavePhoto'); ?></span>
												</div>
												<div class="wpeo-button button-grey modal-close">
													<span><?php echo $langs->trans('CloseModal'); ?></span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="risk-evaluation-calculated-cotation" style="display: none">
								<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?><required>*</required></span>
								<div data-scale="1" class="risk-evaluation-cotation cotation">
									<span><?php echo 0 ?></span>
								</div>
							</div>
							<div class="risk-evaluation-comment">
								<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
								<?php print '<textarea name="evaluationComment'. $risk->id .'" class="minwidth150" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
							</div>
						</div>
					</div>
				</div>
				<!-- Modal-Footer -->
				<div class="modal-footer">
					<div class="risk-create wpeo-button button-primary button-disable modal-close">
						<span><i class="fas fa-plus"></i>  <?php echo $langs->trans('AddRiskButton'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php $title = $langs->trans('DigiriskElementRisksList');
 	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, $newcardbutton, '', $limit, 0, 0, 1);

	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($search_all)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $risk); // Note that $action and $risk may have been modified by hook
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
	foreach ($risk->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
			if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			elseif (strpos($val['type'], 'integer:') === 0) {
				print $risk->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
			}
			elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
			print '</td>';
		}
	}

	foreach ($evaluation->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
		if (!empty($arrayfields['evaluation.'.$key]['checked']))
		{
			print '<td class="liste_titre'.'">';
			print '</td>';
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $risk); // Note that $action and $risk may have been modified by hook
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
	foreach ($risk->fields as $key => $val)
	{
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
		}

	}

	foreach ($evaluation->fields as $key => $val)
	{
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
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
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	print '</tr>'."\n";

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
			$risk->fetch($obj->fk_risk);
		} else {
			// Store properties in $risk
			$risk->setVarsFromFetchObj($obj);
		}

		// Show here line of result
		print '<tr class="oddeven risk-row risk_row_'. $risk->id .'" id="risk_row_'. $risk->id .'">';

		foreach ($risk->fields as $key => $val)
		{
			$cssforfield = (empty($val['css']) ? '' : $val['css']);
			if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
			elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			if (!empty($arrayfields['t.'.$key]['checked']))
			{
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').' style="width:2%">';
				if ($key == 'status') print $risk->getLibStatut(5);
				elseif ($key == 'category') { ?>
					<div class="table-cell table-50 cell-risk" data-title="Risque">
						<div class="wpeo-dropdown dropdown-large category-danger padding">
							<img class="danger-category-pic hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($risk) . '.png' ; ?>"/>
						</div>
					</div>
				</div>
				<?php
			}

			elseif ($key == 'ref') {
				?>
				<div class="risk-container" value="<?php echo $risk->id ?>">
					<!-- BUTTON MODAL RISK EDIT -->
					<div class="risk-edit modal-open" value="<?php echo $risk->id ?>"><i class="fas fa-exclamation-triangle"></i><?php echo ' ' . $risk->ref; ?></div>
					<!-- RISK EDIT MODAL -->
					<div id="risk_edit<?php echo $risk->id ?>" class="wpeo-modal modal-risk-<?php echo $risk->id ?>">
						<div class="modal-container wpeo-modal-event">
							<!-- Modal-Header -->
							<div class="modal-header">
								<h2 class="modal-title"><?php echo $langs->trans('EditRisk') . ' ' . $risk->ref ?></h2>
								<div class="modal-close"><i class="fas fa-times"></i></div>
							</div>
							<!-- MODAL RISK EDIT CONTENT -->
							<div class="modal-content" id="#modalContent">
								<div class="risk-content">
									<div class="risk-category">
										<span class="title"><?php echo $langs->trans('Risk'); ?></span>
										<input class="input-hidden-danger" type="hidden" name="risk_category_id" value=<?php echo $risk->category ?> />
										<div class="wpeo-dropdown dropdown-large dropdown-grid category-danger padding">
											<div class="dropdown-toggle dropdown-add-button button-cotation">
												<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($risk) . '.png'?>"" />
											</div>
											<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0">
												<?php
												$dangerCategories = $risk->get_danger_categories();
												if ( ! empty( $dangerCategories ) ) :
													foreach ( $dangerCategories as $dangerCategory ) : ?>
														<li class="item dropdown-item wpeo-tooltip-event classfortooltip" data-is-preset="<?php echo ''; ?>" data-id="<?php echo $dangerCategory['position'] ?>">
															<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
														</li>
													<?php endforeach;
												endif; ?>
											</ul>
										</div>
									</div>
									<div class="risk-description">
										<span class="title"><?php echo $langs->trans('Description'); ?></span>
										<?php print '<textarea name="riskComment" rows="'.ROWS_2.'">'.$risk->description.'</textarea>'."\n"; ?>
									</div>
								</div>
							</div>
							<!-- Modal-Footer -->
							<div class="modal-footer">
								<div class="risk-save wpeo-button button-green save modal-close" value="<?php echo $risk->id ?>">
									<span><i class="fas fa-save"></i>  <?php echo $langs->trans('UpdateRisk'); ?></span>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
				else print $risk->showOutputField($val, $key, $risk->$key, '');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!empty($val['isameasure']))
				{
					if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
					$totalarray['val']['t.'.$key] += $risk->$key;
				}
			}
		}

		// Store properties in $lastEvaluation
		foreach ($evaluation->fields as $key => $val)
		{
			$cssforfield = (empty($val['css']) ? '' : $val['css']);
			if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
			elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			if (!empty($arrayfields['evaluation.'.$key]['checked']))
			{
				$cssforfield = '';
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
				if ($key == 'cotation') {
					$lastEvaluation = $evaluation->fetchFromParent($risk->id, 1);
					if (!empty ($lastEvaluation) && $lastEvaluation > 0) {
						$lastEvaluation = array_shift($lastEvaluation);
						$cotationList = $evaluation->fetchFromParent($risk->id); ?>
						<div class="risk-evaluation-container" value="<?php echo $risk->id ?>">
							<!-- RISK EVALUATION SINGLE -->
							<div class="risk-evaluation-single">
								<div class="risk-evaluation-cotation" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>">
									<span><?php echo $lastEvaluation->cotation; ?></span>
								</div>
								<div class="risk-evaluation-photo">
									<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$lastEvaluation->element.'/'.$lastEvaluation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
									if (count($filearray)) {
										print '<span class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$lastEvaluation->element, 'small', 1, 0, 0, 0, 40, 0, 0, 0, 0, $lastEvaluation->element, $lastEvaluation).'</span>';
									} else {
										$nophoto = '/public/theme/common/nophoto.png'; ?>
										<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
									<?php } ?>
								</div>
								<div class="risk-evaluation-content">
									<div class="risk-evaluation-data">
										<!-- BUTTON MODAL RISK EVALUATION LIST  -->
										<span class="risk-evaluation-reference risk-evaluation-list modal-open" value="<?php echo $risk->id ?>"><?php echo $lastEvaluation->ref; ?></span>
										<span class="risk-evaluation-author">
											<?php $user->fetch($lastEvaluation->fk_user_creat); ?>
											<?php echo $user->getNomUrl( 0, '', 0, 0, 2 ); ?>
										</span>
										<span class="risk-evaluation-date">
											<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', $lastEvaluation->date_creation); ?>
										</span>
										<span class="risk-evaluation-count"><i class="fas fa-comments"></i><?php echo count($cotationList) ?></span>
									</div>
									<div class="risk-evaluation-comment"><?php echo $lastEvaluation->comment; ?></div>
								</div>
								<!-- BUTTON MODAL RISK EVALUATION ADD  -->
								<div class="risk-evaluation-add wpeo-button button-square-40 button-primary modal-open" value="<?php echo $risk->id ?>">
									<i class="fas fa-plus button-icon"></i>
								</div>
							</div>
							<!-- RISK EVALUATION LIST MODAL -->
							<div class="risk-evaluation-list-modal">
								<div class="wpeo-modal" id="risk_evaluation_list<?php echo $risk->id ?>">
									<div class="modal-container wpeo-modal-event">
										<!-- Modal-Header -->
										<div class="modal-header">
											<h2 class="modal-title"><?php echo $langs->trans('EvaluationList')  . ' R' . $risk->id ?></h2>
											<div class="modal-refresh modal-close"><i class="fas fa-times"></i></div>
										</div>
										<!-- MODAL RISK EVALUATION LIST CONTENT -->
										<div class="modal-content" id="#modalContent">
											<ul class="risk-evaluations-list">
												<?php if (!empty($cotationList)) :
													foreach ($cotationList as $cotation) : ?>
														<li class="risk-evaluation risk-evaluation<?php echo $cotation->id ?>" value="<?php echo $cotation->id ?>">
															<div class="risk-evaluation-container">
																<div class="risk-evaluation-single">
																	<div class="risk-evaluation-cotation" data-scale="<?php echo $cotation->get_evaluation_scale() ?>">
																		<span><?php echo $cotation->cotation; ?></span>
																	</div>
																	<div class="risk-evaluation-photo">
																		<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element.'/'.$cotation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
																		if (count($filearray)) {
																			print '<span class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element, 'small', 1, 0, 0, 0, 40, 0, 1, 0, 0, $cotation->element, $cotation).'</span>';
																		} else {
																			$nophoto = '/public/theme/common/nophoto.png'; ?>
																			<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
																		<?php } ?>
																	</div>
																	<div class="risk-evaluation-content">
																		<div class="risk-evaluation-data">
																			<span class="risk-evaluation-reference"><?php echo $cotation->ref; ?></span>
																			<span class="risk-evaluation-author">
																				<?php $user->fetch($cotation->fk_user_creat); ?>
																				<?php echo $user->getNomUrl( 0, '', 0, 0, 2 ); ?>
																			</span>
																			<span class="risk-evaluation-date">
																				<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', $cotation->date_creation); ?>
																			</span>
																			<span class="risk-evaluation-count"><i class="fas fa-comments"></i><?php echo count($cotationList) ?></span>
																		</div>
																		<div class="risk-evaluation-comment"><?php echo $cotation->comment; ?></div>
																	</div>
																</div>
																<!-- BUTTON MODAL RISK EVALUATION EDIT  -->
																<div class="risk-evaluation-actions wpeo-gridlayout grid-2 grid-gap-0">
																	<div class="risk-evaluation-edit wpeo-button button-square-50 button-grey modal-open" value="<?php echo $cotation->id ?>">
																		<i class="fas fa-pencil-alt button-icon"></i>
																	</div>
																	<div class="risk-evaluation-delete wpeo-button button-square-50 button-transparent">
																		<i class="fas fa-trash button-icon"></i>
																	</div>
																</div>
															</div>
															<!-- RISK EVALUATION EDIT MODAL-->
															<div class="risk-evaluation-edit-modal">
																<div class="wpeo-modal modal-risk" id="risk_evaluation_edit<?php echo $cotation->id ?>">
																	<div class="modal-container wpeo-modal-event">
																		<!-- Modal-Header -->
																		<div class="modal-header">
																			<h2 class="modal-title"><?php echo $langs->trans('EvaluationEdit') . ' ' . $cotation->id ?></h2>
																			<div class="modal-refresh modal-close"><i class="fas fa-times"></i></div>
																		</div>
																		<!-- Modal EDIT Evaluation Content-->
																		<div class="modal-content" id="#modalContent<?php echo $cotation->id ?>">
																			<div class="risk-evaluation-container <?php echo $cotation->method; ?>">
																				<div class="risk-evaluation-header">
																					<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($cotation->method == "standard") ? " selected button-blue" : " button-grey" ?> button-radius-2">
																						<span><?php echo $langs->trans('SimpleCotation') ?></span>
																					</div>
																					<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($cotation->method == "advanced") ? " selected button-blue" : " button-grey" ?> button-radius-2">
																						<span><?php echo $langs->trans('AdvancedCotation') ?></span>
																					</div>
																					<input class="risk-evaluation-method" type="hidden" value="<?php echo $cotation->method ?>" />
																				</div>
																				<div class="risk-evaluation-content-wrapper">
																					<div class="risk-evaluation-content">
																						<div class="cotation-container">
																								<div class="cotation-standard" style="<?php echo ($cotation->method == "standard") ? " display:block" : " display:none" ?>">
																									<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?></span>
																									<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
																										<?php
																										$defaultCotation = array(0, 48, 51, 100);
																										if ( ! empty( $defaultCotation )) :
																											foreach ( $defaultCotation as $request ) :
																												$evaluation->cotation = $request; ?>
																												<div data-id="<?php echo 0; ?>"
																													 data-evaluation-method="standard"
																													 data-evaluation-id="<?php echo $request; ?>"
																													 data-variable-id="<?php echo 152+$request; ?>"
																													 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																													 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																													 class="risk-evaluation-cotation cotation<?php echo ($cotation->cotation == $request) ? " selected-cotation" : "" ?>"><?php echo $request; ?></div>
																											<?php endforeach;
																										endif; ?>
																									</div>
																								</div>
																								<input class="risk-evaluation-seuil" type="hidden" value="<?php echo $cotation->cotation ?>">
																								<?php
																								$evaluation_method = $advanced_method_cotation_array[0];
																								$evaluation_method_survey = $evaluation_method['option']['variable'];
																								?>
																								<div class="wpeo-gridlayout cotation-advanced" style="<?php echo ($cotation->method == "advanced") ? " display:block" : " display:none" ?>">
																									<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
																									<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
																									<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectCotation') ?></span>
																									<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
																										<div class="table-row table-header">
																											<div class="table-cell">
																												<span></span>
																											</div>
																											<?php for ( $l = 0; $l < count($evaluation_method_survey); $l++ ) : ?>
																												<div class="table-cell">
																													<span><?php echo $l; ?></span>
																												</div>
																											<?php endfor; ?>
																										</div>
																										<?php $l = 0;
																										foreach($evaluation_method_survey as $critere) :
																											$name = strtolower($critere['name']); ?>
																											<div class="table-row">
																												<div class="table-cell"><?php echo $critere['name'] ; ?></div>
																												<?php foreach($critere['option']['survey']['request'] as $request) : ?>
																													<div class="table-cell can-select cell-<?php echo $cotation->id ? $cotation->id : 0;
																														if (!empty($request['seuil'])) {
																															echo $request['seuil'] == $cotation->$name ? " active" : "" ;
																														} ?>"
																														 data-type="<?php echo $name ?>"
																														 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
																														 data-evaluation-id="<?php echo $cotation->id ? $cotation->id : 0 ; ?>"
																														 data-variable-id="<?php echo $l ; ?>"
																														 data-seuil="<?php echo  $request['seuil']; ?>">
																														<?php echo  $request['question'] ; ?>
																													</div>
																												<?php endforeach; $l++; ?>
																											</div>
																										<?php endforeach; ?>
																									</div>
																								</div>
																							</div>
																					</div>
																					<div class="risk-evaluation-photo">
																						<span class="title"><?php echo $langs->trans('Photo'); ?></span>
																						<div class="risk-evaluation-photo-container wpeo-modal-event tooltip hover">
																							<?php
																							$relativepath = 'digiriskdolibarr/medias';
																							$modulepart = 'ecm';
																							$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
																							$nophoto = '/public/theme/common/nophoto.png'; ?>
																							<!-- BUTTON RISK EVALUATION PHOTO MODAL -->
																							<div class="action risk-evaluation-photo default-photo modal-open" value="<?php echo $cotation->id ?>">
																								<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element.'/'.$cotation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
																								if (count($filearray)) {
																									print '<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element, 'small', 1, 0, 0, 0, 40, 0, 1, 0, 0, $cotation->element, $cotation).'<input class="filename" type="hidden" value="'.$cotation->photo.'"/>'.'</span>';
																								} else {
																									$nophoto = '/public/theme/common/nophoto.png'; ?>
																									<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>">
																										<input class="filename" type="hidden" value="<?php echo $cotation->photo ?>">
																									</span>
																								<?php } ?>
																							</div>
																							<!-- RISK EVALUATION PHOTO MODAL -->
																							<div class="wpeo-modal modal-photo" id="risk_evaluation_photo<?php echo $cotation->id ?>">
																								<div class="modal-container wpeo-modal-event">
																									<!-- Modal-Header -->
																									<div class="modal-header">
																										<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
																										<div class="modal-close"><i class="fas fa-times"></i></div>
																									</div>
																									<!-- Modal-Content -->
																									<div class="modal-content" id="#modalContent<?php echo $cotation->id ?>">
																										<div class="action">
																											<a href="<?php echo '../../ecm/index.php' ?>" target="_blank">
																												<div class="wpeo-button button-square-50 button-blue">
																													<i class="button-icon fas fa-plus"></i>
																												</div>
																											</a>
																										</div>
																										<div class="wpeo-table table-row">
																											<?php
																											$files =  dol_dir_list(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias');
																											$relativepath = 'digiriskdolibarr/medias';
																											$modulepart = 'ecm';
																											$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
																											$j = 0;

																											if ( !empty($files) ) :
																												foreach ($files as $file) :
																													print '<div class="table-cell center clickable-photo clickable-photo'. $j .'" value="'. $j .'">';
																													if (image_format_supported($file['name']) >= 0) :
																														$fullpath = $path . '/' . $file['relativename'] . '&entity=' . $conf->entity; ?>
																														<input class="filename" type="hidden" value="<?php echo $file['name'] ?>">
																														<img class="photo photo<?php echo $j ?> maxwidth200" src="<?php echo $fullpath; ?>">
																													<?php else : print '&nbsp;';
																													endif;
																													$j++;
																													print '</div>';
																												endforeach;
																											endif; ?>
																										</div>
																									</div>
																									<!-- Modal-Footer -->
																									<div class="modal-footer">
																										<div class="save-photo wpeo-button button-blue">
																											<span><?php echo $langs->trans('SavePhoto'); ?></span>
																										</div>
																										<div class="wpeo-button button-grey modal-close">
																											<span><?php echo $langs->trans('CloseModal'); ?></span>
																										</div>
																									</div>
																								</div>
																							</div>
																						</div>
																					</div>
																					<div class="risk-evaluation-calculated-cotation"  style="<?php echo ($cotation->method == "advanced") ? " display:block" : " display:none" ?>">
																						<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?></span>
																						<div data-scale="<?php echo $cotation->get_evaluation_scale() ?>" class="risk-evaluation-cotation cotation">
																							<span><?php echo  $cotation->cotation ?></span>
																						</div>
																					</div>
																					<div class="risk-evaluation-comment">
																						<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
																						<?php print '<textarea name="evaluationComment'. $cotation->id .'" rows="'.ROWS_2.'">'.$cotation->comment.'</textarea>'."\n"; ?>
																					</div>
																				</div>
																			</div>
																		</div>
																		<!-- Modal-Footer -->
																		<div class="modal-footer">
																			<div class="wpeo-button risk-evaluation-save button-green">
																				<i class="fas fa-save"></i> <?php echo $langs->trans('UpdateEvaluation'); ?>
																			</div>
																		</div>
																	</div>
																</div>
																<hr>
															</div>
														</li>
													<?php endforeach; ?>
												<?php endif; ?>
											</ul>
										</div>
										<!-- Modal-Footer -->
										<div class="modal-footer">
											<div class="wpeo-button button-grey modal-refresh modal-close">
												<span><?php echo $langs->trans('CloseModal'); ?></span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php } else { ?>
						<div class="risk-evaluation-container">
							<div class="risk-evaluation-add wpeo-button button-square-40 button-primary modal-open" value="<?php echo $risk->id ?>">
								<i class="fas fa-plus button-icon"></i>
							</div>
						</div>
					<?php } ?>
				<?php } elseif ($key == 'has_tasks') { ?>
					<div class="table-cell cell-tasks" data-title="Tâches" class="padding">
						<span class="cell-tasks-container">
		<!--				VUE SI Y A DES TACHES   -->
							<?php $related_tasks = $risk->get_related_tasks($risk);
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
			<?php } else print $lastEvaluation->showOutputField($val, $key, $lastEvaluation->$key, '');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!empty($val['isameasure']))
				{
					if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
					$totalarray['val']['t.'.$key] += $lastEvaluation->$key;
				}
			}
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'object'=>$risk, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $risk); // Note that $action and $risk may have been modified by hook
		print $hookmanager->resPrint;

		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($risk->id, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$risk->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$risk->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}

		print '</td>';
		if (!$i) $totalarray['nbfield']++;
		print '</tr>'."\n";
		$i++;
	}

	// If no record found
	if ($num == 0)
	{
		$colspan = 1;
		foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
	}

	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $risk); // Note that $action and $risk may have been modified by hook
	print $hookmanager->resPrint; ?>

	<!-- RISK EVALUATION ADD MODAL-->
	<div class="risk-evaluation-add-modal">
		<div class="wpeo-modal modal-risk" id="risk_evaluation_add">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('EvaluationCreate') . ' ' . $refEvaluationMod->getNextValue($evaluation); ?></h2>
					<div class="modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- Modal-ADD Evaluation Content-->
				<div class="modal-content" id="#modalContent">
					<div class="risk-evaluation-container">
						<div class="risk-evaluation-header">
							<div class="wpeo-button evaluation-standard select-evaluation-method selected button-blue button-radius-2">
								<span><?php echo $langs->trans('SimpleCotation') ?></span>
							</div>
							<div class="wpeo-button evaluation-advanced select-evaluation-method button-grey button-radius-2">
								<span><?php echo $langs->trans('AdvancedCotation') ?></span>
							</div>
							<input class="risk-evaluation-method" type="hidden" value="standard">
						</div>
						<div class="risk-evaluation-content-wrapper">
							<div class="risk-evaluation-content">
								<div class="cotation-container">
										<div class="cotation-standard">
											<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?><required>*</required></span>
											<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
												<?php
												$defaultCotation = array(0, 48, 51, 100);
												if ( ! empty( $defaultCotation )) :
													foreach ( $defaultCotation as $request ) :
														$evaluation->cotation = $request; ?>
														<div data-id="<?php echo 0; ?>"
															 data-evaluation-method="standard"
															 data-evaluation-id="<?php echo $request; ?>"
															 data-variable-id="<?php echo 152+$request; ?>"
															 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
															 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
															 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
													<?php endforeach;
												endif; ?>
											</div>
										</div>
										<input class="risk-evaluation-seuil" type="hidden">
										<?php
										$evaluation_method = $advanced_method_cotation_array[0];
										$evaluation_method_survey = $evaluation_method['option']['variable'];
										?>
										<div class="wpeo-gridlayout cotation-advanced" style="display:none">
											<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
											<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
											<p><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectCotation') ?></p>
											<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
												<div class="table-row table-header">
													<div class="table-cell">
														<span></span>
													</div>
													<?php for ( $l = 0; $l < count($evaluation_method_survey); $l++ ) : ?>
														<div class="table-cell">
															<span><?php echo $l; ?></span>
														</div>
													<?php endfor; ?>
												</div>
												<?php $l = 0; ?>
												<?php foreach($evaluation_method_survey as $critere) :
													$name = strtolower($critere['name']); ?>
													<div class="table-row">
														<div class="table-cell"><?php echo $critere['name'] ; ?></div>
														<?php foreach($critere['option']['survey']['request'] as $request) : ?>
															<div class="table-cell can-select cell-<?php echo  $evaluation_id ? $evaluation_id : 0 ; ?>"
																 data-type="<?php echo $name ?>"
																 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
																 data-evaluation-id="<?php echo $evaluation_id ? $evaluation_id : 0 ; ?>"
																 data-variable-id="<?php echo $l ; ?>"
																 data-seuil="<?php echo  $request['seuil']; ?>">
																<?php echo  $request['question'] ; ?>
															</div>
														<?php endforeach; $l++; ?>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
							</div>
							<div class="risk-evaluation-photo">
								<span class="title"><?php echo $langs->trans('Photo'); ?></span>
								<div class="risk-evaluation-photo-container wpeo-modal-event tooltip hover">
									<?php
									$relativepath = 'digiriskdolibarr/medias';
									$modulepart = 'ecm';
									$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
									$nophoto = '/public/theme/common/nophoto.png'; ?>
									<!-- BUTTON RISK EVALUATION PHOTO MODAL -->
									<div class="action risk-evaluation-photo default-photo modal-open" value="<?php echo $risk->id ?>">
										<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
											<input type="hidden" value="<?php echo $path ?>">
											<input class="filename" type="hidden" value="">
											<img class="photo maxwidth50"  src="<?php echo DOL_URL_ROOT.'/public/theme/common/nophoto.png' ?>">
										</span>
									</div>
									<!-- RISK EVALUATION PHOTO MODAL -->
									<div class="wpeo-modal modal-photo" id="risk_evaluation_photo<?php echo $risk->id ?>">
										<div class="modal-container wpeo-modal-event">
											<!-- Modal-Header -->
											<div class="modal-header">
												<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
												<div class="modal-close"><i class="fas fa-times"></i></div>
											</div>
											<!-- Modal-Content -->
											<div class="modal-content" id="#modalContent<?php echo $object->id ?>">
												<div class="action">
													<a href="<?php echo '../../ecm/index.php' ?>" target="_blank">
														<div class="wpeo-button button-square-50 button-blue">
															<i class="button-icon fas fa-plus"></i>
														</div>
													</a>
												</div>
												<div class="wpeo-table table-row">
													<?php
													$files =  dol_dir_list(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias');
													$relativepath = 'digiriskdolibarr/medias';
													$modulepart = 'ecm';
													$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
													$j = 0;

													if ( !empty($files) ) :
														foreach ($files as $file) :
															print '<div class="table-cell center clickable-photo clickable-photo'. $j .'" value="'. $j .'">';
															if (image_format_supported($file['name']) >= 0) :
																$fullpath = $path . '/' . $file['relativename'] . '&entity=' . $conf->entity; ?>
																<input class="filename" type="hidden" value="<?php echo $file['name'] ?>">
																<img class="photo photo<?php echo $j ?> maxwidth200" src="<?php echo $fullpath; ?>">
															<?php else : print '&nbsp;';
															endif;
															$j++;
															print '</div>';
														endforeach;
													endif; ?>
												</div>
											</div>
											<!-- Modal-Footer -->
											<div class="modal-footer">
												<div class="save-photo wpeo-button button-blue">
													<span><?php echo $langs->trans('SavePhoto'); ?></span>
												</div>
												<div class="wpeo-button button-grey modal-close">
													<span><?php echo $langs->trans('CloseModal'); ?></span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="risk-evaluation-calculated-cotation" style="display: none">
								<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?><required>*</required></span>
								<div data-scale="1" class="risk-evaluation-cotation cotation">
									<span><?php echo 0 ?></span>
								</div>
							</div>
							<div class="risk-evaluation-comment">
								<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
								<?php print '<textarea name="evaluationComment'. $risk->id .'" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
							</div>
						</div>
					</div>
				</div>
				<!-- Modal-Footer -->
				<div class="modal-footer">
					<div class="risk-evaluation-create wpeo-button button-blue button-disable modal-close" value="">
						<i class="fas fa-plus"></i> <?php echo $langs->trans('Add'); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php print '</table>'."\n";
	print '<!-- End table -->';
	print '</div>'."\n";
	print '<!-- End div class="div-table-responsive" -->';
	print '</form>'."\n";
	print '<!-- End form -->';
	print '</div>'."\n";
	print '<!-- End div class="fichecenter" -->';

	dol_fiche_end();
}

print '</div>'."\n";
print '<!-- End div class="cardcontent" -->';

// End of page
llxFooter();
$db->close();
