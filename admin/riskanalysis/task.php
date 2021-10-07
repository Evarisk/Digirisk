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
 * \file    admin/task.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr task page.
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
require_once '../../class/digiriskdocuments.class.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$error = 0;

/*
 * Actions
 */

if (($action == 'update' && !GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$DUProject = GETPOST('DUProject', 'none');
	$DUProject  = preg_split('/_/', $DUProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_DU_PROJECT", $DUProject[0], 'integer', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'settaskmanagement') {
	$constforval = 'DIGIRISKDOLIBARR_TASK_MANAGEMENT';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setshowtaskstartdate') {
	$constforval = 'DIGIRISKDOLIBARR_SHOW_TASK_START_DATE';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setshowtaskenddate') {
	$constforval = 'DIGIRISKDOLIBARR_SHOW_TASK_END_DATE';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setshowtaskprogress') {
	$constforval = 'DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setshowalltasks') {
	$constforval = 'DIGIRISKDOLIBARR_SHOW_ALL_TASKS';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */

if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_T.C3.A2che';
$title    = $langs->trans("RiskAnalysis") . ' - ' . $langs->trans("Task");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'riskanalysis', '', -1, "digiriskdolibarr@digiriskdolibarr");
$head = digiriskdolibarrAdminRiskAnalysisPrepareHead();
print dol_get_fiche_head($head, 'task', '', -1, "digiriskdolibarr@digiriskdolibarr");

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
if ($conf->global->DIGIRISKDOLIBARR_TASK_MANAGEMENT) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=settaskmanagement&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=settaskmanagement&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowTaskStartDate');
print "</td><td>";
print $langs->trans('ShowTaskStartDateDescription');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowtaskstartdate&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowtaskstartdate&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowTaskEndDate');
print "</td><td>";
print $langs->trans('ShowTaskEndDateDescription');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowtaskenddate&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowtaskenddate&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowTaskProgress');
print "</td><td>";
print $langs->trans('ShowTaskProgressDescription') .' %';
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowtaskprogress&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowtaskprogress&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ShowAllTasks');
print "</td><td>";
print $langs->trans('ShowAllTasksDescription');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_SHOW_ALL_TASKS) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowalltasks&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setshowalltasks&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';
print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
