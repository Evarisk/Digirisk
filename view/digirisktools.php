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
 *	\file       view/digirisktools.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Tools page of digiriskdolibarr top menu
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $user;

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once __DIR__ . '/../class/digiriskstandard.class.php';
require_once __DIR__ . '/../class/digiriskelement.class.php';
require_once __DIR__ . '/../class/digiriskelement/groupment.class.php';
require_once __DIR__ . '/../class/digiriskelement/workunit.class.php';
require_once __DIR__ . '/../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../class/riskanalysis/risksign.class.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_standard.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_sirius.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_standard.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_canopus.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/riskanalysis/risk/mod_risk_standard.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';
require_once __DIR__ . '/../core/modules/digiriskdolibarr/riskanalysis/risksign/mod_risksign_standard.php';

// Load translation files required by the page
saturne_load_langs();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$error = 0;

// Initialize technical objects
$digiriskStandard     = new DigiriskStandard($db);
$digiriskElement      = new DigiriskElement($db);
$groupment            = new Groupment($db);
$workUnit             = new WorkUnit($db);
$risk                 = new Risk($db);
$riskAssessment       = new RiskAssessment($db);
$risksign             = new RiskSign($db);
$task                 = new DigiriskTask($db);
$extrafields          = new ExtraFields($db);
$refGroupmentMod      = new $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON();
$refWorkUnitMod       = new $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON();
$refRiskMod           = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
$refRiskAssessmentMod = new $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON();
$refRiskSignMod       = new $conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON();

$numberingModuleName = [
	'project/task' => $conf->global->PROJECT_TASK_ADDON,
];
list($refTaskMod)     = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
$permtoupload     = $user->rights->ecm->upload;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

if (GETPOST('dataMigrationImport', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/arborescence_export.zip/', $_FILES['dataMigrationImportfile']['name'][0]) || $_FILES['dataMigrationImportfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedZIP'), null, 'errors');
		} else {
			if (is_array($_FILES['dataMigrationImportfile']['tmp_name'])) $userfiles = $_FILES['dataMigrationImportfile']['tmp_name'];
			else $userfiles                                                          = array($_FILES['dataMigrationImportfile']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['dataMigrationImportfile']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['dataMigrationImportfile']['error'][$key] == 1 || $_FILES['dataMigrationImportfile']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					} else {
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
					}
				}
			}

			if ( ! $error) {
				$filedir = $upload_dir . '/temp/';
				if ( ! empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportfile', '', null, '', 0, null);
				}
			}

			if ($result > 0) {
				$zip = new ZipArchive;
				if ($zip->open($filedir . $_FILES['dataMigrationImportfile']['name'][0]) === TRUE) {
					$zip->extractTo($filedir);
					$zip->close();
				}
			}

			$filename = preg_replace( '/\.zip/', '.json', $_FILES['dataMigrationImportfile']['name'][0]);

			$json                = file_get_contents($filedir . $filename);
			$digiriskExportArray = json_decode($json, true);
			$digiriskExportArray = end($digiriskExportArray);

			$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($digiriskExportArray['digiriskelements']['digiriskelements']));
			foreach ($it as $key => $v) {
				$element[$key][] = $v;
			}

			for ($i = 0; $i <= count($element['id']) - 1; $i++) {
				if ($element['type'][$i] != 'digi-society') {
					if ($element['type'][$i] == 'digi-group') {
						$digiriskElement->ref = $refGroupmentMod->getNextValue($digiriskElement);
						$type                 = 'groupment';
					} elseif ($element['type'][$i] == 'digi-workunit') {
						$digiriskElement->ref = $refWorkUnitMod->getNextValue($digiriskElement);
						$type                 = 'workunit';
					}

					$digiriskElement->element      = $type;
					$digiriskElement->element_type = $type;
					$digiriskElement->label        = $element['title'][$i];
					$digiriskElement->description  = $element['content'][$i];

					$digiriskElement->array_options['wp_digi_id'] = $element['id'][$i];
					$digiriskElement->array_options['entity'] = $conf->entity;

					$digiriskElement->fk_parent = $digiriskElement->fetch_id_from_wp_digi_id($element['parent_id'][$i]) ?: 0;

					$digiriskElement->create($user);
				}
			}
		}

		$fileImports = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
		if ( ! empty($fileImports)) {
			foreach ($fileImports as $fileImport) {
				unlink($fileImport['fullname']);
			}
		}

		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TOOLS_TREE_ALREADY_IMPORTED', 1, 'integer', 0, '', $conf->entity);
	}
}

if (GETPOST('dataMigrationImportRisks', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/risques_export.zip/', $_FILES['dataMigrationImportRisksfile']['name'][0]) || $_FILES['dataMigrationImportRisksfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedZIP'), null, 'errors');
		} else {
			if (is_array($_FILES['dataMigrationImportRisksfile']['tmp_name'])) $userfiles = $_FILES['dataMigrationImportRisksfile']['tmp_name'];
			else $userfiles                                                               = array($_FILES['dataMigrationImportRisksfile']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['dataMigrationImportRisksfile']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['dataMigrationImportRisksfile']['error'][$key] == 1 || $_FILES['dataMigrationImportRisksfile']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					} else {
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
					}
				}
			}

			if ( ! $error) {
				$filedir = $upload_dir . '/temp/';
				if ( ! empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportRisksfile', '', null, '', 0, null);
				}
			}

			if ($result > 0) {
				$zip = new ZipArchive;
				if ($zip->open($filedir . $_FILES['dataMigrationImportRisksfile']['name'][0]) === TRUE) {
					$zip->extractTo($filedir);
					$zip->close();
				}
			}

			$filename = preg_replace( '/\.zip/', '.json', $_FILES['dataMigrationImportRisksfile']['name'][0]);

			$json                = file_get_contents($filedir . $filename);
			$digiriskExportArray = json_decode($json, true);

			//Risk
			foreach ($digiriskExportArray['risks'] as $digiriskExportRisk) {
				$risk->ref        = $refRiskMod->getNextValue($risk);
				$risk->category   = $risk->getDangerCategoryPositionByName($digiriskExportRisk['danger_category']['name']);
				$risk->fk_element = $digiriskElement->fetch_id_from_wp_digi_id($digiriskExportRisk['parent_id']);
				$risk->fk_projet  = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

				if ( ! $error) {
					$result = $risk->create($user, true);
					if ($result > 0) {
						$riskAssessment->ref                 = $refRiskAssessmentMod->getNextValue($riskAssessment);
						$riskAssessment->date_riskassessment = $digiriskExportRisk['evaluation']['date']['raw'];
						$riskAssessment->cotation            = $digiriskExportRisk['current_equivalence'];
						$riskAssessment->status              = 1;
						$riskAssessment->fk_risk             = $risk->id;

						if ($digiriskExportRisk['evaluation_method']['name'] == 'Evarisk') {
							$riskassessment_variables = array_values($digiriskExportRisk['evaluation']['variables']);
							$riskAssessment->gravite    = $riskassessment_variables[0];
							$riskAssessment->exposition = $riskassessment_variables[1];
							$riskAssessment->occurrence = $riskassessment_variables[2];
							$riskAssessment->formation  = $riskassessment_variables[3];
							$riskAssessment->protection = $riskassessment_variables[4];

							$riskAssessment->method = 'advanced';
						} else {
							$riskAssessment->method = 'standard';
						}

						foreach ($digiriskExportRisk['comment'] as $digiriskExportComment) {
							$riskAssessment->comment = $digiriskExportComment['content'];
						}

						$result2 = $riskAssessment->create($user, true);

						if ($result2 < 0) {
							// Creation evaluation KO
							if ( ! empty($riskAssessment->errors)) setEventMessages(null, $riskAssessment->errors, 'errors');
							else setEventMessages($riskAssessment->error, null, 'errors');
						}
					} else {
						// Creation risk KO
						if ( ! empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
						else setEventMessages($risk->error, null, 'errors');
					}
				}
			}
		}

		$fileImportRisks = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
		if ( ! empty($fileImportRisks)) {
			foreach ($fileImportRisks as $fileImportRisk) {
				unlink($fileImportRisk['fullname']);
			}
		}

		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TOOLS_RISKS_ALREADY_IMPORTED', 1, 'integer', 0, '', $conf->entity);
	}
}

if (GETPOST('dataMigrationImportRiskSigns', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/signalisations_export.zip/', $_FILES['dataMigrationImportRiskSignsfile']['name'][0]) || $_FILES['dataMigrationImportRiskSignsfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedZIP'), null, 'errors');
		} else {
			if (is_array($_FILES['dataMigrationImportRiskSignsfile']['tmp_name'])) $userfiles = $_FILES['dataMigrationImportRiskSignsfile']['tmp_name'];
			else $userfiles                                                               = array($_FILES['dataMigrationImportRiskSignsfile']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['dataMigrationImportRiskSignsfile']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['dataMigrationImportRiskSignsfile']['error'][$key] == 1 || $_FILES['dataMigrationImportRiskSignsfile']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					} else {
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
					}
				}
			}

			if ( ! $error) {
				$filedir = $upload_dir . '/temp/';
				if ( ! empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportRiskSignsfile', '', null, '', 0, null);
				}
			}

			if ($result > 0) {
				$zip = new ZipArchive;
				if ($zip->open($filedir . $_FILES['dataMigrationImportRiskSignsfile']['name'][0]) === TRUE) {
					$zip->extractTo($filedir);
					$zip->close();
				}
			}

			$filename = preg_replace( '/\.zip/', '.json', $_FILES['dataMigrationImportRiskSignsfile']['name'][0]);

			$json                = file_get_contents($filedir . $filename);
			$digiriskExportArray = json_decode($json, true);

			//RiskSign
			foreach ($digiriskExportArray['risksigns'] as $digiriskExportRiskSign) {
				$risksign->ref         = $refRiskSignMod->getNextValue($risksign);
				$risksign->category    = $risksign->getRiskSignCategoryPositionByName($digiriskExportRiskSign['recommendation_category']['name']);
				$risksign->description = $digiriskExportRiskSign['comment']['content'];
				$risksign->fk_element  = $digiriskElement->fetch_id_from_wp_digi_id($digiriskExportRiskSign['parent_id']);

				if ( ! $error) {
					$result = $risksign->create($user, true);
					if ($result < 0) {
						// Creation risksign KO
						if ( ! empty($risksign->errors)) setEventMessages(null, $risksign->errors, 'errors');
						else setEventMessages($risksign->error, null, 'errors');
					}
				}
			}
		}

		$fileImportRiskSigns = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
		if ( ! empty($fileImportRiskSigns)) {
			foreach ($fileImportRiskSigns as $fileImportRiskSign) {
				unlink($fileImportRiskSign['fullname']);
			}
		}

		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TOOLS_RISKSIGNS_ALREADY_IMPORTED', 1, 'integer', 0, '', $conf->entity);
	}
}

if (GETPOST('dataMigrationImportGlobal', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/global_export.zip/', $_FILES['dataMigrationImportGlobalfile']['name'][0]) || $_FILES['dataMigrationImportGlobalfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedZIP'), null, 'errors');
		} else {
			if (is_array($_FILES['dataMigrationImportGlobalfile']['tmp_name'])) $userfiles = $_FILES['dataMigrationImportGlobalfile']['tmp_name'];
			else $userfiles                                                               = array($_FILES['dataMigrationImportGlobalfile']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['dataMigrationImportGlobalfile']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['dataMigrationImportGlobalfile']['error'][$key] == 1 || $_FILES['dataMigrationImportGlobalfile']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					} else {
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
					}
				}
			}

			if ( ! $error) {
				$filedir = $upload_dir . '/temp/';
				if ( ! empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportGlobalfile', '', null, '', 0, null);
				}
			}

			if ($result > 0) {
				$zip = new ZipArchive;
				if ($zip->open($filedir . $_FILES['dataMigrationImportGlobalfile']['name'][0]) === TRUE) {
					$zip->extractTo($filedir);
					$zip->close();
				}
			}

			$filename = preg_replace( '/\.zip/', '.json', $_FILES['dataMigrationImportGlobalfile']['name'][0]);

			$json                = file_get_contents($filedir . $filename);
			$digiriskExportArray = json_decode($json, true);

			$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($digiriskExportArray['digiriskelements']['digiriskelements']));
			foreach ($it as $key => $v) {
				$element[$key][] = $v;
			}

			for ($i = 0; $i <= count($element['id']) - 1; $i++) {
				if ($element['type'][$i] != 'digi-society') {
					if ($element['type'][$i] == 'digi-group') {
						$digiriskElement->ref = $refGroupmentMod->getNextValue($digiriskElement);
						$type                 = 'groupment';
					} elseif ($element['type'][$i] == 'digi-workunit') {
						$digiriskElement->ref = $refWorkUnitMod->getNextValue($digiriskElement);
						$type                 = 'workunit';
					}

					$digiriskElement->element      = $type;
					$digiriskElement->element_type = $type;
					$digiriskElement->label        = $element['title'][$i];
					$digiriskElement->description  = $element['content'][$i];

					$digiriskElement->array_options['wp_digi_id'] = $element['id'][$i];
					$digiriskElement->array_options['entity'] = $conf->entity;

					$digiriskElement->fk_parent = $digiriskElement->fetch_id_from_wp_digi_id($element['parent_id'][$i]) ?: 0;

					$digiriskElement->create($user);
				}
			}

			//Risk
			foreach ($digiriskExportArray['risks'] as $digiriskExportRisk) {
				$risk->ref        = $refRiskMod->getNextValue($risk);
				$risk->category   = $risk->getDangerCategoryPositionByName($digiriskExportRisk['danger_category']['name']);
				$risk->fk_element = $digiriskElement->fetch_id_from_wp_digi_id($digiriskExportRisk['parent_id']);
				$risk->fk_projet  = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

				if ( ! $error) {
					$result = $risk->create($user, true);
					if ($result > 0) {
						$riskAssessment->ref                 = $refRiskAssessmentMod->getNextValue($riskAssessment);
						$riskAssessment->date_riskassessment = $digiriskExportRisk['evaluation']['date']['raw'];
						$riskAssessment->cotation            = $digiriskExportRisk['current_equivalence'];
						$riskAssessment->status              = 1;
						$riskAssessment->fk_risk             = $risk->id;

						if ($digiriskExportRisk['evaluation_method']['name'] == 'Evarisk') {
							$riskassessment_variables = array_values($digiriskExportRisk['evaluation']['variables']);
							$riskAssessment->gravite    = $riskassessment_variables[0];
							$riskAssessment->exposition = $riskassessment_variables[1];
							$riskAssessment->occurrence = $riskassessment_variables[2];
							$riskAssessment->formation  = $riskassessment_variables[3];
							$riskAssessment->protection = $riskassessment_variables[4];

							$riskAssessment->method = 'advanced';
						} else {
							$riskAssessment->method = 'standard';
						}

						foreach ($digiriskExportRisk['comment'] as $digiriskExportComment) {
							$riskAssessment->comment = $digiriskExportComment['content'];
						}

						$result2 = $riskAssessment->create($user, true);

						if ($result2 < 0) {
							// Creation evaluation KO
							if ( ! empty($riskAssessment->errors)) setEventMessages(null, $riskAssessment->errors, 'errors');
							else setEventMessages($riskAssessment->error, null, 'errors');
						}
					} else {
						// Creation risk KO
						if ( ! empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
						else setEventMessages($risk->error, null, 'errors');
					}
				}
			}

			//RiskSign
			foreach ($digiriskExportArray['risksigns'] as $digiriskExportRiskSign) {
				$risksign->ref         = $refRiskSignMod->getNextValue($risksign);
				$risksign->category    = $risksign->getRiskSignCategoryPositionByName($digiriskExportRiskSign['recommendation_category']['name']);
				$risksign->description = $digiriskExportRiskSign['comment']['content'];
				$risksign->fk_element  = $digiriskElement->fetch_id_from_wp_digi_id($digiriskExportRiskSign['parent_id']);

				if ( ! $error) {
					$result = $risksign->create($user, true);
					if ($result < 0) {
						// Creation risksign KO
						if ( ! empty($risksign->errors)) setEventMessages(null, $risksign->errors, 'errors');
						else setEventMessages($risksign->error, null, 'errors');
					}
				}
			}
		}

		$fileImportGlobals = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
		if ( ! empty($fileImportGlobals)) {
			foreach ($fileImportGlobals as $fileImportGlobal) {
				unlink($fileImportGlobal['fullname']);
			}
		}

		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TOOLS_GLOBAL_ALREADY_IMPORTED', 1, 'integer', 0, '', $conf->entity);
	}
}

if (GETPOST('dataMigrationExportGlobal', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// DigiriskElements data
	$alldigiriskelements = $digiriskElement->fetchAll();
	if (is_array($alldigiriskelements) && !empty($alldigiriskelements)) {
		foreach ($alldigiriskelements as $digiriskelementsingle) {
			$digiriskelementsExportArray['rowid']            = $digiriskelementsingle->id;
			$digiriskelementsExportArray['label']            = $digiriskelementsingle->label;
			$digiriskelementsExportArray['status']           = $digiriskelementsingle->status;
			$digiriskelementsExportArray['description']      = $digiriskelementsingle->description;
			$digiriskelementsExportArray['element_type']     = $digiriskelementsingle->element_type;
			$digiriskelementsExportArray['photo']            = $digiriskelementsingle->photo;
			$digiriskelementsExportArray['show_in_selector'] = $digiriskelementsingle->show_in_selector;
			$digiriskelementsExportArray['fk_parent']        = $digiriskelementsingle->fk_parent;
			$digiriskelementsExportArray['ranks']            = $digiriskelementsingle->ranks;

			$digiriskExportArray['digiriskelements'][$digiriskelementsingle->id] = $digiriskelementsExportArray;

			// Risks data
			$allrisks = $risk->fetchFromParent($digiriskelementsingle->id);
			if (is_array($allrisks) && !empty($allrisks)) {
				foreach ($allrisks as $risksingle) {
					$risksExportArray['rowid']       = $risksingle->id;
					$risksExportArray['status']      = $risksingle->status;
					$risksExportArray['category']    = $risksingle->category;
					$risksExportArray['description'] = $risksingle->description;
					$risksExportArray['fk_element']  = $risksingle->fk_element;
					$risksExportArray['fk_projet']   = $risksingle->fk_projet;

					$digiriskExportArray['digiriskelements'][$digiriskelementsingle->id]['risks'][$risksingle->id] = $risksExportArray;

					// Riskassessments data
					$allrisksassessments = $riskAssessment->fetchFromParent($risksingle->id);
					if (is_array($allrisksassessments) && !empty($allrisksassessments)) {
						foreach ($allrisksassessments as $risksassessmentsingle) {
							$riskAssessmentsExportArray['rowid']               = $risksassessmentsingle->id;
							$riskAssessmentsExportArray['status']              = $risksassessmentsingle->status;
							$riskAssessmentsExportArray['status']              = $risksassessmentsingle->status;
							$riskAssessmentsExportArray['method']              = $risksassessmentsingle->method;
							$riskAssessmentsExportArray['cotation']            = $risksassessmentsingle->cotation;
							$riskAssessmentsExportArray['gravite']             = $risksassessmentsingle->gravite;
							$riskAssessmentsExportArray['protection']          = $risksassessmentsingle->protection;
							$riskAssessmentsExportArray['occurrence']          = $risksassessmentsingle->occurrence;
							$riskAssessmentsExportArray['formation']           = $risksassessmentsingle->formation;
							$riskAssessmentsExportArray['exposition']          = $risksassessmentsingle->exposition;
							$riskAssessmentsExportArray['date_riskassessment'] = $risksassessmentsingle->date_riskassessment;
							$riskAssessmentsExportArray['comment']             = $risksassessmentsingle->comment;
							$riskAssessmentsExportArray['photo']               = $risksassessmentsingle->photo;
							$riskAssessmentsExportArray['fk_risk']             = $risksassessmentsingle->fk_risk;

							$digiriskExportArray['digiriskelements'][$digiriskelementsingle->id]['risks'][$risksingle->id]['riskassessments'][$risksassessmentsingle->id] = $riskAssessmentsExportArray;
						}
					}

					// Tasks data
					$risk->fetch($risksingle->id);
					$alltasks = $risk->getRelatedTasks($risk);
					if (is_array($alltasks) && !empty($alltasks)) {
						foreach ($alltasks as $tasksingle) {
							$tasksExportArray['rowid']              = $tasksingle->id;
							$tasksExportArray['dateo']              = $tasksingle->date_start;
							$tasksExportArray['datee']              = $tasksingle->date_end;
							$tasksExportArray['label']              = $tasksingle->label;
							$tasksExportArray['description']        = $tasksingle->description;
							$tasksExportArray['duration_effective'] = $tasksingle->duration_effective;
							$tasksExportArray['planned_workload']   = $tasksingle->planned_workload;
							$tasksExportArray['progress']           = $tasksingle->progress;
							$tasksExportArray['priority']           = $tasksingle->priority;
							$tasksExportArray['budget_amount']      = $tasksingle->budget_amount;
							$tasksExportArray['fk_statut']          = $tasksingle->fk_statut;
							$tasksExportArray['note_public']        = $tasksingle->note_public;
							$tasksExportArray['note_private']       = $tasksingle->note_private;
							$tasksExportArray['rang']               = $tasksingle->rang;
							$tasksExportArray['fk_project']         = $tasksingle->fk_projet;
							$tasksExportArray['fk_task_parent']     = $tasksingle->fk_task_parent;
							$tasksExportArray['fk_risk']            = $risksingle->id;

							$digiriskExportArray['digiriskelements'][$digiriskelementsingle->id]['risks'][$risksingle->id]['tasks'][$tasksingle->id] = $tasksExportArray;
						}
					}
				}
			}

			// Risksings data
			$allrisksigns = $risksign->fetchFromParent($digiriskelementsingle->id);
			if (is_array($allrisksigns) && !empty($allrisksigns)) {
				foreach ($allrisksigns as $risksignsingle) {
					$risksignsExportArray['rowid']       = $risksignsingle->id;
					$risksignsExportArray['status']      = $risksignsingle->status;
					$risksignsExportArray['category']    = $risksignsingle->category;
					$risksignsExportArray['description'] = $risksignsingle->description;
					$risksignsExportArray['fk_element']  = $risksignsingle->fk_element;

					$digiriskExportArray['digiriskelements'][$digiriskelementsingle->id]['risksigns'][$risksignsingle->id] = $risksignsExportArray;
				}
			}
		}
	}

	$digiriskExportArray = json_encode($digiriskExportArray, JSON_PRETTY_PRINT);

	$filedir = $upload_dir . '/temp/';
	$export_base = $filedir . dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_global_export';
	$filename = $export_base . '.json';

	file_put_contents($filename, $digiriskExportArray);

	$zip = new ZipArchive();
	if ($zip->open($export_base . '.zip', ZipArchive::CREATE ) === TRUE) {
		$zip->addFile($filename, basename($filename));
		$zip->close();
		$filenamezip = dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_global_export.zip';
		$filepath = DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&file=' . urlencode('temp/'.$filenamezip);

		?>
		<script>
			var alink = document.createElement( 'a' );
			alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
			alink.setAttribute('download', <?php echo json_encode($filenamezip); ?>);
			alink.click();
		</script>
		<?php
		$fileExportGlobals = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
	}
}

if (GETPOST('dataMigrationImportGlobalDolibarr', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/dolibarr_global_export.zip/', $_FILES['dataMigrationImportGlobalDolibarrfile']['name'][0]) || $_FILES['dataMigrationImportGlobalDolibarrfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormattedZIP'), null, 'errors');
		} else {
			if (is_array($_FILES['dataMigrationImportGlobalDolibarrfile']['tmp_name'])) $userfiles = $_FILES['dataMigrationImportGlobalDolibarrfile']['tmp_name'];
			else $userfiles                                                               = array($_FILES['dataMigrationImportGlobalDolibarrfile']['tmp_name']);

			foreach ($userfiles as $key => $userfile) {
				if (empty($_FILES['dataMigrationImportGlobalDolibarrfile']['tmp_name'][$key])) {
					$error++;
					if ($_FILES['dataMigrationImportGlobalDolibarrfile']['error'][$key] == 1 || $_FILES['dataMigrationImportGlobalDolibarrfile']['error'][$key] == 2) {
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
					} else {
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
					}
				}
			}

			if ( ! $error) {
				$filedir = $upload_dir . '/temp/';
				if ( ! empty($filedir)) {
					$result = dol_add_file_process($filedir, 0, 1, 'dataMigrationImportGlobalDolibarrfile', '', null, '', 0, null);
				}
			}

			if ($result > 0) {
				$zip = new ZipArchive;
				if ($zip->open($filedir . $_FILES['dataMigrationImportGlobalDolibarrfile']['name'][0]) === TRUE) {
					$zip->extractTo($filedir);
					$zip->close();
				}
			}

			$filename = preg_replace( '/\.zip/', '.json', $_FILES['dataMigrationImportGlobalDolibarrfile']['name'][0]);

			$json                = file_get_contents($filedir . $filename);
			$digiriskExportArray = json_decode($json, true);

			if (is_array($digiriskExportArray['digiriskelements']) && !empty($digiriskExportArray['digiriskelements'])) {
				foreach ($digiriskExportArray['digiriskelements'] as $digiriskelementsingle) {
					if ($digiriskelementsingle['element_type'] == 'groupment') {
						$digiriskElement->ref = $refGroupmentMod->getNextValue($digiriskElement);
					} elseif ($digiriskelementsingle['element_type'] == 'workunit') {
						$digiriskElement->ref = $refWorkUnitMod->getNextValue($digiriskElement);
					}
					$digiriskElement->label            = $digiriskelementsingle['label'];
					$digiriskElement->status           = $digiriskelementsingle['status'];
					$digiriskElement->description      = $digiriskelementsingle['description'];
					$digiriskElement->element          = $digiriskelementsingle['element_type'];
					$digiriskElement->element_type     = $digiriskelementsingle['element_type'];
					$digiriskElement->photo            = $digiriskelementsingle['photo'];
					$digiriskElement->show_in_selector = $digiriskelementsingle['show_in_selector'];
					$digiriskElement->ranks            = $digiriskelementsingle['ranks'];

					$digiriskElement->array_options['wp_digi_id'] = $digiriskelementsingle['rowid'];
					$digiriskElement->array_options['entity']     = $conf->entity;

					$digiriskElement->fk_parent = $digiriskElement->fetch_id_from_wp_digi_id($digiriskelementsingle['fk_parent']) ?: 0;

					$digiriskelementid = $digiriskElement->create($user);

					//Risk
					if (array_key_exists('risks', $digiriskelementsingle)) {
						foreach ($digiriskelementsingle['risks'] as $digiriskExportRisk) {
							$risk->ref         = $refRiskMod->getNextValue($risk);
							$risk->status      = $digiriskExportRisk['status'];
							$risk->category    = $digiriskExportRisk['category'];
							$risk->description = $digiriskExportRisk['description'];
							$risk->fk_element  = $digiriskelementid;
							$risk->fk_projet   = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

							if ( ! $error) {
								$result = $risk->create($user, true);
								if ($result > 0) {
									if (array_key_exists('riskassessments', $digiriskExportRisk)) {
										foreach ($digiriskExportRisk['riskassessments'] as $digiriskExportRiskAssessement) {
											$riskAssessment->ref                 = $refRiskAssessmentMod->getNextValue($riskAssessment);
											$riskAssessment->status              = $digiriskExportRiskAssessement['status'];
											$riskAssessment->method              = $digiriskExportRiskAssessement['method'];
											$riskAssessment->cotation            = $digiriskExportRiskAssessement['cotation'];
											$riskAssessment->gravite             = $digiriskExportRiskAssessement['gravite'];
											$riskAssessment->protection          = $digiriskExportRiskAssessement['protection'];
											$riskAssessment->occurrence          = $digiriskExportRiskAssessement['occurrence'];
											$riskAssessment->formation           = $digiriskExportRiskAssessement['formation'];
											$riskAssessment->exposition          = $digiriskExportRiskAssessement['exposition'];
											$riskAssessment->date_riskassessment = $digiriskExportRiskAssessement['date_riskassessment'];
											$riskAssessment->comment             = $digiriskExportRiskAssessement['comment'];
											$riskAssessment->photo               = $digiriskExportRiskAssessement['photo'];
											$riskAssessment->fk_risk             = $risk->id;

											$result2 = $riskAssessment->create($user, true, false);

											if ($result2 < 0) {
												// Creation evaluation KO
												if ( ! empty($riskAssessment->errors)) setEventMessages('', $riskAssessment->errors, 'errors');
												else setEventMessages($riskAssessment->error, array(), 'errors');
											}
										}
									}
									if (array_key_exists('tasks', $digiriskExportRisk)) {
										foreach ($digiriskExportRisk['tasks'] as $digiriskExportTask) {
											$task->ref                      = $refTaskMod->getNextValue('', $task);
											$task->date_start               = $digiriskExportTask['date_start'];
											$task->date_end                 = $digiriskExportTask['date_end'];
											$task->label                    = $digiriskExportTask['label'];
											$task->description              = $digiriskExportTask['description'];
											$task->duration_effective       = $digiriskExportTask['duration_effective'];
											$task->planned_workload         = $digiriskExportTask['planned_workload'];
											$task->progress                 = $digiriskExportTask['progress'];
											$task->priority                 = $digiriskExportTask['priority'];
											$task->budget_amount            = $digiriskExportTask['budget_amount'];
										//	$task->fk_statut                = $digiriskExportTask['fk_statut'];
											$task->note_public              = $digiriskExportTask['note_public'];
											$task->note_private             = $digiriskExportTask['note_private'];
											$task->fk_project               = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
											$task->array_options['fk_risk'] = $risk->id;

											$result3 = $task->create($user, true);

											if ($result3 < 0) {
												// Creation task KO
												if ( ! empty($task->errors)) setEventMessages('', $task->errors, 'errors');
												else setEventMessages($task->error, array(), 'errors');
											}
										}
									}
								} else {
									// Creation risk KO
									if ( ! empty($risk->errors)) setEventMessages('', $risk->errors, 'errors');
									else setEventMessages($risk->error, array(), 'errors');
								}
							}
						}
					}

					//RiskSign
					if (array_key_exists('risksigns', $digiriskelementsingle)) {
						foreach ($digiriskelementsingle['risksigns'] as $digiriskExportRiskSign) {
							$risksign->ref         = $refRiskSignMod->getNextValue($risksign);
							$risksign->status      = $digiriskExportRiskSign['status'];
							$risksign->category    = $digiriskExportRiskSign['category'];
							$risksign->description = $digiriskExportRiskSign['description'];
							$risksign->fk_element  = $digiriskelementid;

							if (!$error) {
								$result = $risksign->create($user, true);
								if ($result < 0) {
									// Creation risksign KO
									if (!empty($risksign->errors)) setEventMessages(null, $risksign->errors, 'errors');
									else setEventMessages($risksign->error, null, 'errors');
								}
							}
						}
					}
				}
			}
		}

		$fileImportGlobals = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
		if ( ! empty($fileImportGlobals)) {
			foreach ($fileImportGlobals as $fileImportGlobal) {
				unlink($fileImportGlobal['fullname']);
			}
		}
	}
}

/*
 * View
 */

$title   = $langs->trans("Tools");
$helpUrl = 'FR:Module_Digirisk#Import.2Fexport_de_donn.C3.A9es';

saturne_header(0,"", $title, $helpUrl);

print load_fiche_titre($title, '', 'wrench');

if ($user->rights->digiriskdolibarr->adminpage->read) {
	if ($conf->global->DIGIRISKDOLIBARR_TOOLS_TREE_ALREADY_IMPORTED == 1) : ?>
		<div class="wpeo-notice notice-info">
			<div class="notice-content">
				<div class="notice-subtitle"><strong><?php echo $langs->trans("DataMigrationTreeAlreadyImported"); ?></strong></div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($conf->global->DIGIRISKDOLIBARR_TOOLS_RISKS_ALREADY_IMPORTED == 1) : ?>
	<div class="wpeo-notice notice-info">
		<div class="notice-content">
			<div class="notice-subtitle"><strong><?php echo $langs->trans("DataMigrationRisksAlreadyImported"); ?></strong></div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ($conf->global->DIGIRISKDOLIBARR_TOOLS_RISKSIGNS_ALREADY_IMPORTED == 1) : ?>
		<div class="wpeo-notice notice-info">
			<div class="notice-content">
				<div class="notice-subtitle"><strong><?php echo $langs->trans("DataMigrationRiskSignsAlreadyImported"); ?></strong></div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($conf->global->DIGIRISKDOLIBARR_TOOLS_GLOBAL_ALREADY_IMPORTED == 1) : ?>
		<div class="wpeo-notice notice-info">
			<div class="notice-content">
				<div class="notice-subtitle"><strong><?php echo $langs->trans("DataMigrationGlobalAlreadyImported"); ?></strong></div>
			</div>
		</div>
	<?php endif; ?>

	<?php print load_fiche_titre($langs->trans("DigiriskDataMigration"), '', '');

	print load_fiche_titre($langs->trans("DataMigrationWordPressToDolibarr"), '', '');

	print '<form class="data-migration-from" name="DataMigration" id="DataMigration" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" method="POST">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td class="center">' . $langs->trans("Action") . '</td>';
	print '</tr>';

	if ($conf->global->DIGIRISKDOLIBARR_TOOLS_ADVANCED_IMPORT == 1) {
		print '<tr class="oddeven"><td>';
		print $langs->trans('DataMigrationImport');
		print "</td><td>";
		print $langs->trans('DataMigrationImportDescription');
		print '</td>';

		print '<td class="center data-migration-import">';
		print '<input class="flat" type="file" name="dataMigrationImportfile[]" id="data-migration-import" />';
		print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImport" value="' . $langs->trans("Upload") . '">';
		print '</td>';
		print '</tr>';

		print '<tr class="oddeven"><td>';
		print $langs->trans('DataMigrationImportRisks');
		print "</td><td>";
		print $langs->trans('DataMigrationImportRisksDescription');
		print '</td>';

		print '<td class="center data-migration-import-risks">';
		print '<input class="flat" type="file" name="dataMigrationImportRisksfile[]" id="data-migration-import-risks" />';
		print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportRisks" value="' . $langs->trans("Upload") . '">';
		print '</td>';
		print '</tr>';

		print '<tr class="oddeven"><td>';
		print $langs->trans('DataMigrationImportRiskSigns');
		print "</td><td>";
		print $langs->trans('DataMigrationImportRiskSignsDescription');
		print '</td>';

		print '<td class="center data-migration-import-risksigns">';
		print '<input class="flat" type="file" name="dataMigrationImportRiskSignsfile[]" id="data-migration-import-risksigns" />';
		print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportRiskSigns" value="' . $langs->trans("Upload") . '">';
		print '</td>';
		print '</tr>';
	}

	// Import data form WordPress
	print '<tr class="oddeven"><td>';
	print $langs->trans('DataMigrationImportGlobal');
	print "</td><td>";
	print $langs->trans('DataMigrationImportGlobalDescription');
	print '</td>';

	print '<td class="center data-migration-import-global">';
	print '<input class="flat" type="file" name="dataMigrationImportGlobalfile[]" id="data-migration-import-global" />';
	print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportGlobal" value="' . $langs->trans("Upload") . '">';
	print '</td>';
	print '</tr>';
	print '</table>';
	print '</form>';

	print load_fiche_titre($langs->trans("DataMigrationDolibarrToDolibarr"), '', '');

	print '<form class="data-migration-export-global-from" name="dataMigrationExportGlobal" id="dataMigrationExportGlobal" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="dataMigrationExportGlobal">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td class="center">' . $langs->trans("Action") . '</td>';
	print '</tr>';

	// Export data from Dolibarr
	print '<tr class="oddeven"><td>';
	print $langs->trans('DataMigrationExportGlobal');
	print "</td><td>";
	print $langs->trans('DataMigrationExportGlobalDescription');
	print '</td>';

	print '<td class="center data-migration-export-global">';
	print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationExportGlobal" value="' . $langs->trans("ExportData") . '">';
	print '</td>';
	print '</tr>';
	print '</form>';

	print '<form class="data-migration-from" name="DataMigration" id="DataMigration" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" method="POST">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="">';

	// Import data from Dolibarr
	print '<tr class="oddeven"><td>';
	print $langs->trans('DataMigrationImportGlobal');
	print "</td><td>";
	print $langs->trans('DataMigrationImportGlobalDolibarrDescription');
	print '</td>';

	print '<td class="center data-migration-import-global-dolibarr">';
	print '<input class="flat" type="file" name="dataMigrationImportGlobalDolibarrfile[]" id="data-migration-import-global-dolibarr" />';
	print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationImportGlobalDolibarr" value="' . $langs->trans("Upload") . '">';
	print '</td>';
	print '</tr>';
	print '</table>';
	print '</form>';
}

// End of page
llxFooter();
$db->close();
