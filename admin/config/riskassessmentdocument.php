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

global $conf, $db, $langs, $mc, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formcompany.class.php";
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/evaluator.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risksign.class.php';

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

// Actions update_mask
require_once __DIR__ . '/../../../saturne/core/tpl/actions/admin_conf_actions.tpl.php';

if (GETPOST('action') == 'setmod') {
    $value = GETPOST('value');
    $valueArray = explode('_', $value);
    $objectType = $valueArray[1];

    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. strtoupper($objectType) .'_ADDON', $value, 'chaine', 0, '', $conf->entity);
}

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$DUProject             = GETPOST('DUProject', 'none');
	$DUProject             = preg_split('/_/', $DUProject);
    $EnvironmentProject    = GETPOST('EnvironmentProject', 'none');
    $EnvironmentProject    = preg_split('/_/', $EnvironmentProject);
	$evaluatorDuration     = GETPOST('EvaluatorDuration', 'alpha');
	$taskTimeSpentDuration = GETPOST('TaskTimeSpentDuration', 'alpha');

    if ($DUProject[0] > 0 && $DUProject[0] != $conf->global->DIGIRISKDOLIBARR_DU_PROJECT) {
        dolibarr_set_const($db, "DIGIRISKDOLIBARR_DU_PROJECT", $DUProject[0], 'integer', 0, '', $conf->entity);

        $url = '/projet/tasks.php?id=' . $DUProject[0];

        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
        $sql .= " url='".$db->escape($url)."'";
        $sql .= " WHERE leftmenu='digiriskactionplan'";
        $sql .= " AND entity=" . $conf->entity;

        $resql = $db->query($sql);
        if (!$resql) {
            $error = "Error ".$db->lasterror();
            return -1;
        }
    }

    if ($EnvironmentProject[0] > 0 && $EnvironmentProject[0] != $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT) {
        dolibarr_set_const($db, "DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT", $EnvironmentProject[0], 'integer', 0, '', $conf->entity);

        $url = '/projet/tasks.php?id=' . $EnvironmentProject[0];

        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
        $sql .= " url='".$db->escape($url)."'";
        $sql .= " WHERE leftmenu='digiriskenvironmentalactionplan'";
        $sql .= " AND entity=" . $conf->entity;

        $resql = $db->query($sql);
        if (!$resql) {
            $error = "Error ".$db->lasterror();
            return -1;
        }
    }

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
$formcompany = new FormCompany($db);
$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl  = 'FR:Module_Digirisk#L.27onglet_Analyse_des_risques';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'riskassessmentdocument', $title, -1, 'digiriskdolibarr_color@digiriskdolibarr');


// Risks
print load_fiche_titre('<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('RiskConfig'), '', '');
print '<hr>';

$object          = new Risk($db);
$objectModSubdir = 'riskanalysis';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$areRisksShared = !empty($mc->entities['risk']) ? strpos($mc->entities['risk'], $conf->entity) : 0;
$areRisksSharable = isModEnabled('multicompany') && !empty($mc->sharings['risk']) && $areRisksShared > 0;

$constArray[$moduleNameLowerCase] = [
	'RiskDescription' => [
		'name'        => 'RiskDescription',
		'description' => 'RiskDescriptionDescription',
		'code'        => 'DIGIRISKDOLIBARR_RISK_DESCRIPTION',
	],
	'RiskCategoryEdit' => [
		'name'        => 'RiskCategoryEdit',
		'description' => 'RiskCategoryEditDescription',
		'code'        => 'DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT',
	],
	'MoveRisks' => [
		'name'        => 'MoveRisks',
		'description' => 'MoveRisksDescription',
		'code'        => 'DIGIRISKDOLIBARR_MOVE_RISKS',
	],
	'SortRisksListingsByEvaluation' => [
		'name'        => 'SortRisksListingsByEvaluation',
		'description' => 'SortRisksListingsByEvaluationDescription',
		'code'        => 'DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION',
	],
	'RiskDescriptionPrefill' => [
		'name'        => 'RiskDescriptionPrefill',
		'description' => 'RiskDescriptionPrefillDescription',
		'code'        => 'DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL',
	],
	'ShowRiskOrigin' => [
		'name'        => 'ShowRiskOrigin',
		'description' => 'ShowRiskOriginDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN',
	],
    'RiskListParentView' => [
        'name'        => 'RiskListParentView',
        'description' => 'RiskListParentViewDescription',
        'code'        => 'DIGIRISKDOLIBARR_RISK_LIST_PARENT_VIEW',
    ],
    'CategoryOnRisk' => [
        'name'        => 'CategoryOnRisk',
        'description' => 'CategoryOnRiskDescription',
        'code'        => 'DIGIRISKDOLIBARR_CATEGORY_ON_RISK',
    ],
	'ShowInheritedRisksInDocuments' => [
		'name'        => 'ShowInheritedRisksInDocuments',
		'description' => 'ShowInheritedRisksInDocumentsDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS',
	],
	'ShowInheritedRisksInListings' => [
		'name'        => 'ShowInheritedRisksInListings',
		'description' => 'ShowInheritedRisksInListingsDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_LISTINGS',
	],
	'ShowSharedRisks' => [
		'name'        => 'ShowSharedRisks',
		'description' => $langs->trans('ShowSharedRisksDescription') . (!$areRisksSharable ? '<br>' . img_picto('danger', 'fa-exclamation-triangle') . $langs->trans('DisabledSharedElement') : ''),
		'code'        => 'DIGIRISKDOLIBARR_SHOW_SHARED_RISKS',
        'disabled'    => !$areRisksSharable
	],
];

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

// Risk Assessments
print load_fiche_titre('<i class="fas fa-exclamation-circle"></i> ' . $langs->trans('RiskAssessmentConfig'), '', '');
print '<hr>';

$object          = new RiskAssessment($db);
$objectModSubdir = 'riskanalysis';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$constArray[$moduleNameLowerCase] = [
	'AdvancedRiskAssessmentMethod' => [
		'name'        => 'AdvancedRiskAssessmentMethod',
		'description' => 'AdvancedRiskAssessmentMethodDescription',
		'code'        => 'DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD',
	],
	'MultipleRiskAssessmentMethodName' => [
		'name'        => 'MultipleRiskAssessmentMethodName',
		'description' => 'MultipleRiskAssessmentMethodDescription',
		'code'        => 'DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD',
	],
    'ShowAllRiskAssessments' => [
        'name'        => 'ShowAllRiskAssessments',
        'description' => 'ShowAllRiskAssessmentsDescription',
        'code'        => 'DIGIRISKDOLIBARR_SHOW_ALL_RISKASSESSMENTS',
    ],
    'ShowRiskAssessmentDate' => [
        'name'        => 'ShowRiskAssessmentDate',
        'description' => 'ShowRiskAssessmentDateDescription',
        'code'        => 'DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE',
    ],
    'RiskAssessmentHideDateInDocument' => [
        'name'        => 'RiskAssessmentHideDateInDocument',
        'description' => 'RiskAssessmentHideDateInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT',
    ]
];

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

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
	$formproject->select_projects(-1,  $conf->global->DIGIRISKDOLIBARR_DU_PROJECT, 'DUProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?socid=' . $soc->id . '&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create&socid=' . $soc->id) . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

    print '<tr class="oddeven"><td><label for="EnvironmentProject">' . $langs->trans("EnvironmentProject") . '</label></td><td>';
    $formproject->select_projects(-1,  $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT, 'EnvironmentProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
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

$constArray[$moduleNameLowerCase] = [
	'TasksManagement' => [
		'name'        => 'TasksManagement',
		'description' => 'TasksManagementDescription',
		'code'        => 'DIGIRISKDOLIBARR_TASK_MANAGEMENT',
	],
	'ShowTaskStartDate' => [
		'name'        => 'ShowTaskStartDate',
		'description' => 'ShowTaskStartDateDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASK_START_DATE',
	],
	'ShowTaskEndDate' => [
		'name'        => 'ShowTaskEndDate',
		'description' => 'ShowTaskEndDateDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASK_END_DATE',
	],
	'ShowTasksDone' => [
		'name'        => 'ShowTasksDone',
		'description' => $langs->trans('ShowTasksDoneDescription') . ' % ' . $langs->trans('ShowTasksDoneDescriptionExtend'),
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASKS_DONE',
	],
	'ShowTaskCalculatedProgress' => [
		'name'        => 'ShowTaskCalculatedProgress',
		'description' => 'ShowTaskCalculatedProgressDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS',
	],
	'ShowAllTasks' => [
		'name'        => 'ShowAllTasks',
		'description' => 'ShowAllTasksDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_ALL_TASKS',
	],
    'TaskHideRefInDocument' => [
        'name'        => 'TaskHideRefInDocument',
        'description' => 'TaskHideRefInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT',
    ],
    'TaskHideResponsibleInDocument' => [
        'name'        => 'TaskHideResponsibleInDocument',
        'description' => 'TaskHideResponsibleInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT',
    ],
    'TaskHideDateInDocument' => [
        'name'        => 'TaskHideDateInDocument',
        'description' => 'TaskHideDateInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT',
    ],
    'TaskHideBudgetInDocument' => [
        'name'        => 'TaskHideBudgetInDocument',
        'description' => 'TaskHideBudgetInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT',
    ]
];

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

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

$object          = new Evaluator($db);
$objectModSubdir = 'digiriskelement';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

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


$object          = new RiskSign($db);
$objectModSubdir = 'riskanalysis';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$areRiskSignsShared    = !empty($mc->entities['risksign']) ? strpos($mc->entities['risksign'], $conf->entity) : 0;
$areRisksSignsSharable = isModEnabled('multicompany') && !empty($mc->sharings['risksign']) && $areRiskSignsShared > 0;

$constArray[$moduleNameLowerCase] = [
	'ShowInheritedRiskSigns' => [
		'name'        => 'ShowInheritedRiskSigns',
		'description' => 'ShowInheritedRiskSignsDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS',
	],
	'ShowSharedRiskSigns' => [
		'name'        => 'ShowSharedRiskSigns',
		'description' => $langs->trans('ShowSharedRiskSignsDescription') . (!$areRisksSignsSharable ? '<br>' . img_picto('danger', 'fa-exclamation-triangle') . $langs->trans('DisabledSharedElement') : ''),
		'code'        => 'DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS',
        'disabled'    => !$areRisksSignsSharable

    ],
];
require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
