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
 *	\file       view/digirisktools.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Tools page of digiriskdolibarr top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once './../class/digiriskstandard.class.php';
require_once './../class/digiriskelement.class.php';
require_once './../class/digiriskelement/groupment.class.php';
require_once './../class/digiriskelement/workunit.class.php';
require_once './../class/riskanalysis/risk.class.php';
require_once './../class/riskanalysis/riskassessment.class.php';
require_once './../class/riskanalysis/risksign.class.php';
require_once './../core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_standard.php';
require_once './../core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_standard.php';
require_once './../core/modules/digiriskdolibarr/riskanalysis/risk/mod_risk_standard.php';
require_once './../core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';
require_once './../core/modules/digiriskdolibarr/riskanalysis/risksign/mod_risksign_standard.php';

global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

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
$extrafields          = new ExtraFields($db);
$refGroupmentMod      = new $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON();
$refWorkUnitMod       = new $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON();
$refRiskMod           = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
$refRiskAssessmentMod = new $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON();
$refRiskSignMod       = new $conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON();

$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1];

// Security check
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
$permtoupload     = $user->rights->ecm->upload;

if ( ! $user->rights->digiriskdolibarr->adminpage->read) accessforbidden();

/*
 * Actions
 */

if (GETPOST('dataMigrationImport', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/\.json/', $_FILES['dataMigrationImportfile']['name'][0]) || $_FILES['dataMigrationImportfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormatted'), null, 'errors');
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

			$json                = file_get_contents($filedir . $_FILES['dataMigrationImportfile']['name'][0]);
			$digiriskExportArray = json_decode($json, true);
			$digiriskExportArray = end($digiriskExportArray);

			$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($digiriskExportArray));
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

		$fileImport = dol_dir_list($filedir, "files", 0, '', '', '', '', 1);
		$fileImport = array_shift($fileImport);
		if ( ! empty($fileImport)) {
			unlink($fileImport['fullname']);
		}

		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TOOLS_TREE_ALREADY_IMPORTED', 1, 'integer', 0, '', $conf->entity);
	}
}

if (GETPOST('dataMigrationImportRisks', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/\.json/', $_FILES['dataMigrationImportRisksfile']['name'][0]) || $_FILES['dataMigrationImportRisksfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormatted'), null, 'errors');
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

			$json                = file_get_contents($filedir . $_FILES['dataMigrationImportRisksfile']['name'][0]);
			$digiriskExportArray = json_decode($json, true);

			//Risk
			foreach ($digiriskExportArray as $digiriskExportRisk) {
				$risk->ref        = $refRiskMod->getNextValue($risk);
				$risk->category   = $risk->get_danger_category_position_by_name($digiriskExportRisk['danger_category']['name']);
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
							$riskAssessment->gravite    = $digiriskExportRisk['evaluation']['variables']['105'];
							$riskAssessment->exposition = $digiriskExportRisk['evaluation']['variables']['106'];
							$riskAssessment->occurrence = $digiriskExportRisk['evaluation']['variables']['107'];
							$riskAssessment->formation  = $digiriskExportRisk['evaluation']['variables']['108'];
							$riskAssessment->protection = $digiriskExportRisk['evaluation']['variables']['109'];

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
		$fileImportRisks = array_shift($fileImportRisks);
		if ( ! empty($fileImportRisks)) {
			unlink($fileImportRisks['fullname']);
		}

		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TOOLS_RISKS_ALREADY_IMPORTED', 1, 'integer', 0, '', $conf->entity);
	}
}

if (GETPOST('dataMigrationImportRiskSigns', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/\.json/', $_FILES['dataMigrationImportRiskSignsfile']['name'][0]) || $_FILES['dataMigrationImportRiskSignsfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormatted'), null, 'errors');
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

			$json                = file_get_contents($filedir . $_FILES['dataMigrationImportRiskSignsfile']['name'][0]);
			$digiriskExportArray = json_decode($json, true);

			//RiskSign
			foreach ($digiriskExportArray as $digiriskExportRiskSign) {
				$risksign->ref         = $refRiskSignMod->getNextValue($risksign);
				$risksign->category    = $risksign->get_risksign_category_position_by_name($digiriskExportRiskSign['recommendation_category']['name']);
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
		$fileImportRiskSigns = array_shift($fileImportRiskSigns);
		if ( ! empty($fileImportRiskSigns)) {
			unlink($fileImportRiskSigns['fullname']);
		}

		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TOOLS_RISKSIGNS_ALREADY_IMPORTED', 1, 'integer', 0, '', $conf->entity);
	}
}

if (GETPOST('dataMigrationImportGlobal', 'alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Submit file
	if ( ! empty($_FILES)) {
		if ( ! preg_match('/\.zip/', $_FILES['dataMigrationImportGlobalfile']['name'][0]) || $_FILES['dataMigrationImportGlobalfile']['size'][0] < 1) {
			setEventMessages($langs->trans('ErrorFileNotWellFormatted'), null, 'errors');
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

			$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($digiriskExportArray[0]));
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
			foreach ($digiriskExportArray[1] as $digiriskExportRisk) {
				$risk->ref        = $refRiskMod->getNextValue($risk);
				$risk->category   = $risk->get_danger_category_position_by_name($digiriskExportRisk['danger_category']['name']);
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
							$riskAssessment->gravite    = $digiriskExportRisk['evaluation']['variables']['105'];
							$riskAssessment->exposition = $digiriskExportRisk['evaluation']['variables']['106'];
							$riskAssessment->occurrence = $digiriskExportRisk['evaluation']['variables']['107'];
							$riskAssessment->formation  = $digiriskExportRisk['evaluation']['variables']['108'];
							$riskAssessment->protection = $digiriskExportRisk['evaluation']['variables']['109'];

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
			foreach ($digiriskExportArray[2] as $digiriskExportRiskSign) {
				$risksign->ref         = $refRiskSignMod->getNextValue($risksign);
				$risksign->category    = $risksign->get_risksign_category_position_by_name($digiriskExportRiskSign['recommendation_category']['name']);
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

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader("", $langs->trans("Tools"), $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($langs->trans("Tools"), '', 'wrench');

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
}

// End of page
llxFooter();
$db->close();
