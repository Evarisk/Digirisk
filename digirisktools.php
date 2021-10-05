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
require_once './class/digiriskelement.class.php';
require_once './class/digiriskelement/groupment.class.php';
require_once './class/digiriskelement/workunit.class.php';
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
$digiriskElement      = new DigiriskElement($db);
$groupment            = new Groupment($db);
$workUnit             = new WorkUnit($db);
$risk                 = new Risk($db);
$riskAssessment       = new RiskAssessment($db);
$extrafields          = new ExtraFields($db);
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

	$json = file_get_contents("C:/wamp64/www/dolibarr-13.0.2/documents/ecm/digiriskdolibarr/20211005123613_global_export.json");
	$digiriskExportArray = json_decode($json, true);
	$digiriskExportArray = array_shift($digiriskExportArray);

	$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($digiriskExportArray));
	foreach($it as $key => $v) {
		$element[$key][] = $v;
	}

	for ($i = 0; $i <= count($element['id']) -1; $i++) {
		if ($element['type'][$i] != 'digi-society') {
			if ($element['type'][$i] == 'digi-group') {
				$digiriskElement->ref = $refGroupmentMod->getNextValue($digiriskElement);
				$type = 'groupment';
			} elseif ($element['type'][$i] == 'digi-workunit') {
				$digiriskElement->ref = $refWorkUnitMod->getNextValue($digiriskElement);
				$type = 'workunit';
			}

			$digiriskElement->element      = $type;
			$digiriskElement->element_type = $type;
			$digiriskElement->label        = $element['title'][$i];
			$digiriskElement->description  = $element['content'][$i];

			$digiriskElement->array_options['wp_digi_id'] = $element['id'][$i];

			$digiriskElement->fk_parent = $digiriskElement->fetch_id_from_wp_digi_id($element['parent_id'][$i]) ?: 0;

			$digiriskElement->create($user);
		}
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
