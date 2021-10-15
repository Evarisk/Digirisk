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
 * \file    admin/riskassessment.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr riskassessment page.
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../../lib/digiriskdolibarr.lib.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$type          = 'riskassessment';
$error         = 0;

/*
 * Actions
 */
if ($action == 'updateMask') {
	$maskconstriskassessment = GETPOST('maskconstriskassessment', 'alpha');
	$maskriskassessment      = GETPOST('maskriskassessment', 'alpha');

	if ($maskconstriskassessment) $res = dolibarr_set_const($db, $maskconstriskassessment, $maskriskassessment, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setmod') {
	$constforval = 'DIGIRISKDOLIBARR_'.strtoupper($type)."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setmethod') {
	$constforval = 'DIGIRISKDOLIBARR_MULTIPLE_'.strtoupper($type).'_METHOD';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setadvancedmethod') {
	$constforval = 'DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setshowriskassessmentdate') {
	$constforval = 'DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */

$form = new Form($db);

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_Analyse_des_risques';
$title    = $langs->trans("RiskAnalysis") . ' - ' . $langs->trans("RiskAssessment");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'riskanalysis', '', -1, "digiriskdolibarr@digiriskdolibarr");
$head = digiriskdolibarrAdminRiskAnalysisPrepareHead();
print dol_get_fiche_head($head, 'riskassessment', '', -1, "digiriskdolibarr@digiriskdolibarr");

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DigiriskRiskAssessmentNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/".$type."/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir.$filebis;

					$module = new $classname($db);

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>';
						print $langs->trans($module->name);
						print "</td><td>";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON.'.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue").': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval = $langs->trans($nextval);
								$htmltooltip .= $nextval.'<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error).'<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON.'.php' == $file) {  // If module is the one used, we show existing errors
							if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>";
					}
				}
			}
		}
		closedir($handle);
	}
}

print '</table>';

print load_fiche_titre($langs->trans("DigiriskRiskAssessmentData"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('AdvancedRiskAssessmentMethod');
print "</td><td>";
print $langs->trans('AdvancedRiskAssessmentMethodDescription');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setadvancedmethod&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setadvancedmethod&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('MultipleRiskAssessmentMethodName');
print "</td><td>\n";
print $langs->trans('MultipleRiskAssessmentMethodDescription');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmethod&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmethod&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowRiskAssessmentDate');
print "</td><td>";
print $langs->trans('ShowRiskAssessmentDateDescription');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowriskassessmentdate&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowriskassessmentdate&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';
print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
