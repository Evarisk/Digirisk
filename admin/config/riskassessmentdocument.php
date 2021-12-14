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
 * \file    admin/riskanalysis.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr riskanalysis page.
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
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once '../../lib/digiriskdolibarr.lib.php';

$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

if (($action == 'update' && !GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$EvaluatorDuration = GETPOST('EvaluatorDuration','alpha');

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_EVALUATOR_DURATION", $EvaluatorDuration, 'integer', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_Analyse_des_risques';
$title    = $langs->trans("RiskAssessmentDocument");

$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'riskassessmentdocument', '', -1, "digiriskdolibarr@digiriskdolibarr");


// RISKS

print load_fiche_titre('<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('RiskConfig'), '', '');
print '<hr>';


print load_fiche_titre($langs->trans("DigiriskRiskNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/risk/");
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
						print "</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>'."\n";

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_RISK_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_RISK_ADDON.'.php' == $file) {
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
						if ($conf->global->DIGIRISKDOLIBARR_RISK_ADDON.'.php' == $file) { // If module is the one used, we show existing errors
							if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>\n";
					}
				}
			}
		}
		closedir($handle);
	}
}

print '</table>';

print load_fiche_titre($langs->trans("DigiriskRiskData"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('RiskDescription');
print "</td><td>";
print $langs->trans('RiskDescriptionDescription');
print '</td>';


print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_RISK_DESCRIPTION');
print '</td>';
print '</tr>';


print '<tr class="oddeven"><td>';
print $langs->trans('RiskCategoryEdit');
print "</td><td>";
print $langs->trans('RiskCategoryEditDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowParentRisks');
print "</td><td>";
print $langs->trans('ShowParentRisksDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_PARENT_RISKS');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('MoveRisks');
print "</td><td>";
print $langs->trans('MoveRisksDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_MOVE_RISKS');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('SortRisksListingsByCotation');
print "</td><td>";
print $langs->trans('SortRisksListingsByCotationDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('RiskDescriptionPrefill');
print "</td><td>";
print $langs->trans('RiskDescriptionPrefillDescription');
print '</td>';


print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL');
print '</td>';
print '</tr>';

print '</table>';
print '<hr>';

// Risk Assessment

print load_fiche_titre('<i class="fas fa-exclamation-circle"></i> ' . $langs->trans('RiskAssessmentConfig'), '', '');
print '<hr>';

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

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/riskassessment/");
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
print ajax_constantonoff('DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('MultipleRiskAssessmentMethodName');
print "</td><td>\n";
print $langs->trans('MultipleRiskAssessmentMethodDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowRiskAssessmentDate');
print "</td><td>";
print $langs->trans('ShowRiskAssessmentDateDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE');
print '</td>';
print '</tr>';
print '</table>';

// Tasks

print '<hr>';
print load_fiche_titre('<i class="fas fa-tasks"></i> ' . $langs->trans("TaskConfig"), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("TasksManagement"), '', '');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="social_form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("SelectProject").'</td>';
print '<td>'.$langs->trans("Action").'</td>';
print '</tr>';

// Project
if (!empty($conf->projet->enabled)) {
	$langs->load("projects");
	print '<tr class="oddeven"><td><label for="DUProject">'.$langs->trans("DUProject").'</label></td><td>';
	$numprojet = $formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_DU_PROJECT, 'DUProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '</td></tr>';
}

print '</table>';
print '</form>';

print load_fiche_titre($langs->trans("DigiriskTaskData"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('TasksManagement');
print "</td><td>";
print $langs->trans('TaskManagementDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_TASK_MANAGEMENT');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowTaskStartDate');
print "</td><td>";
print $langs->trans('ShowTaskStartDateDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_TASK_START_DATE');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowTaskEndDate');
print "</td><td>";
print $langs->trans('ShowTaskEndDateDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_TASK_END_DATE');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowTaskProgress');
print "</td><td>";
print $langs->trans('ShowTaskProgressDescription') .' %';
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowAllTasks');
print "</td><td>";
print $langs->trans('ShowAllTasksDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_ALL_TASKS');
print '</td>';
print '</tr>';
print '</table>';
print '<hr>';

// Evaluators

print load_fiche_titre('<i class="fas fa-user-check"></i> ' . $langs->trans("EvaluatorConfig"), '', '');
print '<hr>';


print load_fiche_titre($langs->trans("DigiriskEvaluatorNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/evaluator/");
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
						if ($conf->global->DIGIRISKDOLIBARR_EVALUATOR_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_EVALUATOR_ADDON.'.php' == $file) {
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
						if ($conf->global->DIGIRISKDOLIBARR_EVALUATOR_ADDON.'.php' == $file) { // If module is the one used, we show existing errors
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

print load_fiche_titre($langs->trans("DigiriskEvaluatorData"), '', '');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="social_form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Action").'</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="EvaluatorDuration">'.$langs->trans("EvaluatorDuration").'</label></td>';
print '<td>'.$langs->trans("EvaluatorDurationDescription").'</td>';
print '<td><input type="number" name="EvaluatorDuration" value="'.$conf->global->DIGIRISKDOLIBARR_EVALUATOR_DURATION.'"></td>';
print '<td><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</td></tr>';

print '</table>';
print '</form>';
print '<hr>';

// Risk sign
print load_fiche_titre('<i class="fas fa-map-signs"></i> ' . $langs->trans("RiskSignConfig"), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("DigiriskRiskSignNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/risksign/");
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
						if ($conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON.'.php' == $file) {
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
						if ($conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON.'.php' == $file) { // If module is the one used, we show existing errors
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

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();

