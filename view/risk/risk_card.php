<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 *   	\file       view/risk/risk_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to read a whole risk
 *
 * The risk list only has room for a truncated description and for the last assessment of each risk.
 * This card reads a single risk in full: its whole description, every assessment it went through and
 * the action plan it opened.
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_actionplan.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_risk.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['errors', 'projects']);

// Get parameters
$id          = GETPOSTINT('id');
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'riskcard';
$backtopage  = GETPOST('backtopage', 'alpha');
// The action plan of the risk reads either as a table of its tasks or as the Gantt of the module
$taskView    = GETPOST('taskview', 'aZ09') == 'gantt' ? 'gantt' : 'list';

// Initialize technical objects
$object          = new Risk($db);
$riskAssessment  = new RiskAssessment($db);
$digiriskElement = new DigiriskElement($db);
$project         = new Project($db);
$form            = new Form($db);

// Load object
$object->fetch($id);

// The constructor reads the type from the URL, the card reads it from the risk itself
if ($object->type == 'riskenvironmental') {
    $object->picto = 'fontawesome_fa-leaf_fas_#d35968';
}

$hookmanager->initHooks(['riskcard', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->risk->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;

saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

/*
 * View
 */

$title   = $langs->trans('RiskCard');
$helpUrl = 'FR:Module_Digirisk';

// The Gantt timeline is wider than the page: #id-right is a table-cell, so its own overflow-x
// never scrolls and the whole page stretches instead. classforhorizontalscrolloftabs bounds it.
$moreCssOnBody = $taskView == 'gantt' ? 'classforhorizontalscrolloftabs' : '';

saturne_header(0, '', $title, $helpUrl, '', 0, 0, [], [], '', $moreCssOnBody);

if ($object->id <= 0) {
    print $langs->trans('ErrorRecordNotFound');
    llxFooter();
    $db->close();
    exit;
}

// Data of the risk, gathered once for the whole card
$digiriskElement->fetch($object->fk_element);

// Every assessment the risk went through, the most recent first
$riskAssessments = $riskAssessment->fetchFromParent($object->id, 0, 'DESC');
if (!is_array($riskAssessments)) {
    $riskAssessments = [];
}

// The cotation of a risk is the one of its last validated assessment
$lastRiskAssessment = null;
foreach ($riskAssessments as $riskAssessmentSingle) {
    if ($riskAssessmentSingle->status == RiskAssessment::STATUS_VALIDATED) {
        $lastRiskAssessment = $riskAssessmentSingle;
        break;
    }
}

// Action plan opened on the risk
$relatedTasks = $object->getRelatedTasks($object);
if (!is_array($relatedTasks)) {
    $relatedTasks = [];
}

$usersList = saturne_fetch_all_object_type('User');
if (!is_array($usersList)) {
    $usersList = [];
}

// The Gantt of the action plan reads the tasks in the shape its template expects. Only the keys it
// uses are built here: the risk is already named by the card, so its badge is left out of the bars.
$tasksJson     = [];
$kanbanColumns = [];
if ($taskView == 'gantt') {
    $kanbanColumns = digiriskActionPlanGetKanbanColumns($db);

    foreach ($relatedTasks as $relatedTask) {
        $responsible = [];
        foreach ($relatedTask->liste_contact(-1, 'internal') as $taskContact) {
            if ($taskContact['code'] == 'TASKEXECUTIVE') {
                $responsible[] = [
                    'id'       => (int) $taskContact['id'],
                    'fullname' => trim($taskContact['firstname'] . ' ' . $taskContact['lastname']),
                    'initials' => dol_strtoupper(dol_substr($taskContact['firstname'], 0, 1) . dol_substr($taskContact['lastname'], 0, 1)),
                    'photo'    => ''
                ];
            }
        }

        $tasksJson[] = [
            'id'          => $relatedTask->id,
            'ref'         => $relatedTask->ref,
            'label'       => $relatedTask->label,
            'date_start'  => $relatedTask->date_start ? dol_print_date($relatedTask->date_start, 'dayrfc') : '',
            'date_end'    => $relatedTask->date_end ? dol_print_date($relatedTask->date_end, 'dayrfc') : '',
            'progress'    => (int) $relatedTask->progress,
            'risk_ref'    => $object->ref,
            'risk_nomurl' => '',
            'responsible' => $responsible,
            'url'         => DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $relatedTask->id . '&withproject=1'
        ];
    }
}

require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_riskcard_view.tpl.php';

// End of page
llxFooter();
$db->close();
