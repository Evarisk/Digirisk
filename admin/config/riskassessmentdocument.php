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
 * \file    admin/config/riskassessmentdocument.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr riskassessmentdocument page.
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

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formcompany.class.php";
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$project = new Project($db);
$task    = new Task($db);

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$DUProject             = GETPOST('DUProject', 'none');
	$DUProject             = preg_split('/_/', $DUProject);
	$evaluatorDuration     = GETPOST('EvaluatorDuration', 'alpha');
	$taskTimeSpentDuration = GETPOST('TaskTimeSpentDuration', 'alpha');

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_DU_PROJECT", $DUProject[0], 'integer', 0, '', $conf->entity);

	if (!empty($evaluatorDuration) || $evaluatorDuration === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_EVALUATOR_DURATION", $evaluatorDuration, 'integer', 0, '', $conf->entity);
	}

	if (!empty($taskTimeSpentDuration) || $taskTimeSpentDuration === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION", $taskTimeSpentDuration, 'integer', 0, '', $conf->entity);
	}

	if (!empty(GETPOST('project_contact_type'))) {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEFAULT_PROJECT_CONTACT_TYPE", GETPOST('project_contact_type'), 'integer', 0, '', $conf->entity);
	}

	if (!empty(GETPOST('task_contact_type'))) {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEFAULT_TASK_CONTACT_TYPE", GETPOST('task_contact_type'), 'integer', 0, '', $conf->entity);
	}

	if ($action != 'updateedit' && ! $error) {
		setEventMessage($langs->trans('SavedConfig'));
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$helpUrl  = 'FR:Module_Digirisk#L.27onglet_Analyse_des_risques';
$title    = $langs->trans("RiskAssessmentDocument");

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'riskassessmentdocument', '', -1, "digiriskdolibarr@digiriskdolibarr");


// Risks
print load_fiche_titre('<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('RiskConfig'), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("DigiriskRiskNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/risk/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

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
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>' . "\n";

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_RISK_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_RISK_ADDON . '.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setmod&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '&token=' . newToken() . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue") . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_RISK_ADDON . '.php' == $file) { // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
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
print $langs->trans('MoveRisks');
print "</td><td>";
print $langs->trans('MoveRisksDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_MOVE_RISKS');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('SortRisksListingsByEvaluation');
print "</td><td>";
print $langs->trans('SortRisksListingsByEvaluationDescription');
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

print '<tr class="oddeven"><td>';
print $langs->trans('ShowRiskOrigin');
print "</td><td>";
print $langs->trans('ShowRiskOriginDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowInheritedRisksInDocuments');
print "</td><td>";
print $langs->trans('ShowInheritedRisksInDocumentsDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowInheritedRisksInListings');
print "</td><td>";
print $langs->trans('ShowInheritedRisksInListingsDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_LISTINGS');
print '</td>';
print '</tr>';

$areRisksShared = !empty($conf->mc->entities['risk']) ? strpos($conf->mc->entities['risk'], $conf->entity) : 0;

print '<tr class="oddeven"><td>';
print $langs->trans('ShowSharedRisks');
print "</td><td>";
print $langs->trans('ShowSharedRisksDescription');
print '</td>';

print '<td class="center">';
if (isModEnabled('multicompany') && !empty($conf->mc->sharings['risk']) && $areRisksShared > 0) {
	print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS');
} else {
	print $langs->trans('DisabledSharedElement');
}
print '</td>';
print '</tr>';

print '</table>';

// Risk Assessments
print load_fiche_titre('<i class="fas fa-exclamation-circle"></i> ' . $langs->trans('RiskAssessmentConfig'), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("DigiriskRiskAssessmentNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/riskassessment/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

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
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON . '.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setmod&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '&token=' . newToken() . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue") . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON . '.php' == $file) {  // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
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

print '<tr class="oddeven"><td>';
print $langs->trans('ShowAllRiskAssessments');
print "</td><td>";
print $langs->trans('ShowAllRiskAssessmentsDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_ALL_RISKASSESSMENTS');
print '</td>';
print '</tr>';
print '</table>';

// Tasks
print load_fiche_titre('<i class="fas fa-tasks"></i> ' . $langs->trans("TaskConfig"), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("TasksManagement"), '', '');

// Project
if (isModEnabled('project')) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<table class="noborder centpercent editmode">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("SelectProject") . '</td>';
	print '<td>' . $langs->trans("Action") . '</td>';
	print '</tr>';

	$langs->load("projects");
	print '<tr class="oddeven"><td><label for="DUProject">' . $langs->trans("DUProject") . '</label></td><td>';
	$formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_DU_PROJECT, 'DUProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?socid=' . $soc->id . '&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create&socid=' . $soc->id) . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '<tr class="oddeven"><td><label for="projectContactType">' . $langs->trans("DefaultProjectContactType") . '</label></td><td class="maxwidth500">';
	$formcompany->selectTypeContact($project, $conf->global->DIGIRISKDOLIBARR_DEFAULT_PROJECT_CONTACT_TYPE, 'project_contact_type', 'internal', 'position', 0, 'minwidth500');
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '<tr class="oddeven"><td><label for="taskContactType">' . $langs->trans("DefaultTaskContactType") . '</label></td><td>';
	$formcompany->selectTypeContact($task, $conf->global->DIGIRISKDOLIBARR_DEFAULT_TASK_CONTACT_TYPE, 'task_contact_type', 'internal', 'position', 0, 'minwidth500');
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}

print load_fiche_titre($langs->trans("DigiriskTaskData"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
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
print $langs->trans('ShowTasksDone');
print "</td><td>";
print $langs->trans('ShowTasksDoneDescription') . ' % ' . $langs->trans('ShowTasksDoneDescriptionExtend');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_TASKS_DONE');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowTaskCalculatedProgress');
print "</td><td>";
print $langs->trans('ShowTaskCalculatedProgressDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS');
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

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="TaskTimeSpentDuration">' . $langs->trans("TaskTimeSpentDuration") . '</label></td>';
print '<td>' . $langs->trans("TaskTimeSpentDurationDescription") . '</td>';
print '<td><input type="number" name="TaskTimeSpentDuration" value="' . $conf->global->DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// Evaluators
print load_fiche_titre('<i class="fas fa-user-check"></i> ' . $langs->trans("EvaluatorConfig"), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("DigiriskEvaluatorNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/evaluator/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

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
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_EVALUATOR_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_EVALUATOR_ADDON . '.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setmod&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '&token=' . newToken() . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue") . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_EVALUATOR_ADDON . '.php' == $file) { // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="EvaluatorDuration">' . $langs->trans("EvaluatorDuration") . '</label></td>';
print '<td>' . $langs->trans("EvaluatorDurationDescription") . '</td>';
print '<td><input type="number" name="EvaluatorDuration" value="' . $conf->global->DIGIRISKDOLIBARR_EVALUATOR_DURATION . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// Risk signs
print load_fiche_titre('<i class="fas fa-map-signs"></i> ' . $langs->trans("RiskSignConfig"), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("DigiriskRiskSignNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/risksign/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

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
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>';

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON . '.php' == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setmod&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '&token=' . newToken() . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= $langs->trans("NextValue") . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON . '.php' == $file) { // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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

print load_fiche_titre($langs->trans("DigiriskRiskSignData"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowInheritedRiskSigns');
print "</td><td>";
print $langs->trans('ShowInheritedRiskSignsDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS');
print '</td>';
print '</tr>';

$areRiskSignsShared = !empty($conf->mc->entities['risksign']) ? strpos($conf->mc->entities['risksign'], $conf->entity) : 0;

print '<tr class="oddeven"><td>';
print $langs->trans('ShowSharedRiskSigns');
print "</td><td>";
print $langs->trans('ShowSharedRiskSignsDescription');
print '</td>';

print '<td class="center">';
if (isModEnabled('multicompany') && !empty($conf->mc->sharings['risksign']) && $areRiskSignsShared > 0) {
	print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS');
} else {
	print $langs->trans('DisabledSharedElement');
}
print '</td>';
print '</tr>';
print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
