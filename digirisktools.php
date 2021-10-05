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
 *	\file       digirisktools.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Tools page of digiriskdolibarr top menu
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

require_once './class/digiriskstandard.class.php';
require_once './class/digiriskelement/groupment.class.php';
require_once './class//digiriskelement/workunit.class.php';
require_once './class/riskanalysis/risk.class.php';
require_once './class/riskanalysis/riskassessment.class.php';
require_once './core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_standard.php';
require_once './core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_standard.php';
require_once './core/modules/digiriskdolibarr/riskanalysis/risk/mod_risk_standard.php';
require_once './core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';

global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$digiriskStandard     = new DigiriskStandard($db);
$groupment            = new Groupment($db);
$workUnit             = new WorkUnit($db);
$risk                 = new Risk($db);
$riskAssessment       = new RiskAssessment($db);
$refGroupmentMod      = new $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON();
$refWorkUnitMod       = new $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON();
$refRiskMod           = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
$refRiskAssessmentMod = new $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON();


// Security check
if (!$user->rights->digiriskdolibarr->lire) accessforbidden();

$permtoupload = $user->rights->ecm->upload;

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$error = 0;

/*
 * Actions
 */

if ($action == "dataMigrationImport" && !empty($conf->global->MAIN_UPLOAD_DOC)) {

	$json = file_get_contents("C:/wamp64/www/dolibarr-13.0.4/documents/ecm/digiriskdolibarr/20211004152016_global_export.json");
	$digiriskExportArray = json_decode($json, true);
	$digiriskExportArray = array_shift($digiriskExportArray);

	//DigiriskStandard
	$digiriskExportArray['title'] = $digiriskStandard->label;

	echo '<pre>';
	print_r($digiriskExportArray);
	echo '</pre>';
	exit;

	$recursive = true;

	if ( $recursive ) {
		$elements = recurse_tree_import(0,0,$digiriskExportArray);
		echo '<pre>';
		print_r($elements);
		echo '</pre>';
		exit;
		if ( $elements > 0  && !empty($elements) ) {
			// Super fonction itérations flat.
			$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($elements));
			echo '<pre>';
			print_r($it);
			echo '</pre>';
			exit;
			foreach($it as $key => $v) {
				$element[$key][$v] = $v;
			}

			if (is_array($element)) {
				$children_id = array_shift ($element);
			}

			echo '<pre>';
			print_r($elements);
			echo '</pre>';
			exit;

			// RISQUES des enfants du parent.
			if (!empty ($children_id)) {
				foreach ($children_id as $element) {

					$risk = new Risk($this->db);

					$result = $risk->fetchFromParent($element);
					if (!empty ($result)) {
						foreach ($result as $risk) {
							$evaluation = new RiskAssessment($this->db);
							$lastEvaluation = $evaluation->fetchFromParent($risk->id,1);
							if ( $lastEvaluation > 0  && !empty($lastEvaluation) ) {
								$lastEvaluation = array_shift($lastEvaluation);
								$risk->lastEvaluation = $lastEvaluation->cotation;
							}

							$risks[$risk->id] = $risk;
						}
					}
				}
			}
		}
	}

	//START NIVEAU 1
	//Groupment
	foreach ($digiriskExportArray['list_group'] as $digiriskExportGroupment) {
		$groupment->ref          = $refGroupmentMod->getNextValue($groupment);
		$groupment->element_type = 'groupment';
		$groupment->label        = $digiriskExportGroupment['title'];
		$groupment->description  = $digiriskExportGroupment['content'];

		$groupmentID = $groupment->create($user);

		//WorkUnit
		foreach ($digiriskExportGroupment['list_workunit'] as $digiriskExportWorkUnit) {
			$workUnit->ref          = $refWorkUnitMod->getNextValue($workUnit);
			$workUnit->element_type = 'workunit';
			$workUnit->label        = $digiriskExportWorkUnit['title'];
			$workUnit->description  = $digiriskExportWorkUnit['content'];
			$workUnit->fk_parent    = $groupmentID;

			$workUnit->create($user);
		}

		//Risk
		foreach ($digiriskExportGroupment['list_risk'] as $digiriskExportRisk) {
			$risk->ref        = $refRiskMod->getNextValue($risk);
			$risk->category   = $risk->get_danger_category_position_by_name($digiriskExportRisk['danger_category']['name']);
			$risk->fk_element = $groupmentID;
			$risk->fk_projet  = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

			if (!$error) {
				$result = $risk->create($user, true);
				if ($result > 0) {
					$riskAssessment->ref           = $refRiskAssessmentMod->getNextValue($riskAssessment);
					$riskAssessment->date_creation = $digiriskExportRisk['evaluation']['date']['raw'];
					$riskAssessment->tms           = $digiriskExportRisk['evaluation']['date']['raw'];
					$riskAssessment->cotation      = $digiriskExportRisk['current_equivalence'];
					$riskAssessment->status        = 1;
					$riskAssessment->fk_risk       = $risk->id;

					if ($digiriskExportRisk['evaluation_method']['name'] == 'Evarisk') {
						$riskAssessment->gravite    = $digiriskExportRisk['evaluation']['variables']['105'];
						$riskAssessment->exposition = $digiriskExportRisk['evaluation']['variables']['106'];
						$riskAssessment->occurrence = $digiriskExportRisk['evaluation']['variables']['107'];
						$riskAssessment->formation  = $digiriskExportRisk['evaluation']['variables']['108'];
						$riskAssessment->protection = $digiriskExportRisk['evaluation']['variables']['109'];

						$riskAssessment->method = 'advanced';
					}

					foreach ($digiriskExportRisk['comment'] as $digiriskExportComment) {
						$riskAssessment->comment = $digiriskExportComment['content'];
					}

					$result2 = $riskAssessment->create($user, true);

					if ($result2 < 0) {
						// Creation evaluation KO
						if (!empty($riskAssessment->errors)) setEventMessages(null, $riskAssessment->errors, 'errors');
						else  setEventMessages($riskAssessment->error, null, 'errors');
					}
				} else {
					// Creation risk KO
					if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
					else  setEventMessages($risk->error, null, 'errors');
				}
			}
		}
		echo '<pre>';
		print_r($digiriskExportGroupment);
		echo '</pre>';
		exit;
	}
	//WorkUnit
	foreach ($digiriskExportArray['list_workunit'] as $digiriskExportWorkUnit) {
		$workUnit->ref          = $refWorkUnitMod->getNextValue($workUnit);
		$workUnit->element_type = 'workunit';
		$workUnit->label        = $digiriskExportWorkUnit['title'];
		$workUnit->description  = $digiriskExportWorkUnit['content'];

		$workUnitID = $workUnit->create($user);

		//Risk
		foreach ($digiriskExportWorkUnit['list_risk'] as $digiriskExportRisk) {
			$risk->ref        = $refRiskMod->getNextValue($risk);
			$risk->category   = $risk->get_danger_category_position_by_name($digiriskExportRisk['danger_category']['name']);
			$risk->fk_element = $workUnitID;
			$risk->fk_projet  = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

			if (!$error) {
				$result = $risk->create($user, true);
				if ($result > 0) {
					$riskAssessment->ref           = $refRiskAssessmentMod->getNextValue($riskAssessment);
					$riskAssessment->date_creation = $digiriskExportRisk['evaluation']['date']['raw'];
					$riskAssessment->tms           = $digiriskExportRisk['evaluation']['date']['raw'];
					$riskAssessment->cotation      = $digiriskExportRisk['current_equivalence'];
					$riskAssessment->status        = 1;
					$riskAssessment->fk_risk       = $risk->id;

					if ($digiriskExportRisk['evaluation_method']['name'] == 'Evarisk') {
						$riskAssessment->gravite    = $digiriskExportRisk['evaluation']['variables']['105'];
						$riskAssessment->exposition = $digiriskExportRisk['evaluation']['variables']['106'];
						$riskAssessment->occurrence = $digiriskExportRisk['evaluation']['variables']['107'];
						$riskAssessment->formation  = $digiriskExportRisk['evaluation']['variables']['108'];
						$riskAssessment->protection = $digiriskExportRisk['evaluation']['variables']['109'];

						$riskAssessment->method = 'advanced';
					}

					foreach ($digiriskExportRisk['comment'] as $digiriskExportComment) {
						$riskAssessment->comment = $digiriskExportComment['content'];
					}

					$result2 = $riskAssessment->create($user, true);

					if ($result2 < 0) {
						// Creation evaluation KO
						if (!empty($riskAssessment->errors)) setEventMessages(null, $riskAssessment->errors, 'errors');
						else  setEventMessages($riskAssessment->error, null, 'errors');
					}
				} else {
					// Creation risk KO
					if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
					else  setEventMessages($risk->error, null, 'errors');
				}
			}
		}
	}
	//END NIVEAU 1
}








//	// Define relativepath and upload_dir
//	$relativepath = 'digiriskdolibarr/medias';
//	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
//
//	if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
//	else $userfiles = array($_FILES['userfile']['tmp_name']);
//
//	foreach ($userfiles as $key => $userfile) {
//		if (empty($_FILES['userfile']['tmp_name'][$key])) {
//			$error++;
//			if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
//				setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
//			} else {
//				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
//			}
//		}
//	}
//
//	if (!$error) {
//		$generatethumbs = 1;
//		$res = dol_add_file_process($upload_dir, 0, 1, 'userfile', '', null, '', $generatethumbs);
//		if ($res > 0) {
//			$result = $ecmdir->changeNbOfFiles('+');
//		}
//	}


/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader("", $langs->trans("Tools"), $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($langs->trans("Tools"), '', 'wrench');

if ($user->rights->digiriskdolibarr->adminpage->read) {
	print '<form method="POST" id="DataMigrationImport" enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"]."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print load_fiche_titre($langs->trans("DigiriskDataMigration"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td class="center">'.$langs->trans("Action").'</td>';
	print '</tr>';

	print '<tr class="oddeven"><td>';
	print $langs->trans('DataMigrationImport');
	print "</td><td>";
	print $langs->trans('DataMigrationImportDescription');
	print '</td>';

	print '<td class="center data-migration-import">';

	// To attach new file
	if ((!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) || !empty($section)) {
		$sectiondir = GETPOST('file', 'alpha') ? GETPOST('file', 'alpha') : GETPOST('section_dir', 'alpha');
		print '<!-- Start form to attach new file in digiriskdolibarr_photo_view.tpl.tpl.php sectionid=' . $section . ' sectiondir=' . $sectiondir . ' -->' . "\n";
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
		$formfile = new FormFile($db);
		$formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, 0, $permtoupload, 48, null, '', 0, '', 0, 'DataMigrationImport', '', $sectiondir, 1);
	} else print '&nbsp;';
	// End "Add new file" area

	print '</td>';
	print '</tr>';
	print '</table>';
	print '</form>';
}

// End of page
llxFooter();
$db->close();
