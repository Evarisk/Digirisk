<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/accidentinvestigation/accidentinvestigation_actions.php
 * \ingroup digiriskdolibarr
 * \brief   Tab showing curative and corrective actions for an accident investigation
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/task/saturnetask.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/accidentinvestigation.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accidentinvestigation.lib.php';

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$action_type = GETPOST('action_type', 'int');
$taskid      = GETPOST('taskid', 'int');
$cancel      = GETPOST('cancel', 'aZ09');

// Initialize technical objects
$accident = new Accident($db);
$object   = new AccidentInvestigation($db);
$task     = new Task($db);

$numRefConf          = strtoupper($task->element) . '_ADDON';
$numberingModuleName = ['project/task' => $conf->global->$numRefConf];
list($modTask)       = saturne_require_objects_mod($numberingModuleName);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['accidentinvestigationactions', 'digiriskdolibarrglobal', 'globalcard']);

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once.

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->accidentinvestigation->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accidentinvestigation->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accidentinvestigation->delete;

saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */
if ($cancel) {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

if ($action === 'addAction' && $permissiontoadd) {
    $object->fetch($id);
    $accident->fetch($object->fk_accident);

    // Actions are grouped under the T1/T2 container task created when the investigation is validated
    $containerTaskId = getAccidentInvestigationActionContainerTask($db, (int) $object->fk_task, $action_type);

    // Each section renders its own form, so field names are suffixed with the action type
    $deadlinePrefix = 'actiondeadline' . $action_type;

    $newTask                 = new Task($db);
    $newTask->fk_project     = $accident->fk_project;
    $newTask->fk_task_parent = $containerTaskId > 0 ? $containerTaskId : (int) $object->fk_task;
    $newTask->ref            = $modTask->getNextValue(0, $newTask);
    $newTask->label          = GETPOST('action_label', 'alphanohtml');
    // Task::create() only writes date_end, the datee property is deprecated and ignored
    $newTask->date_end       = dol_mktime(
        23, 59, 59,
        GETPOST($deadlinePrefix . 'month', 'int'),
        GETPOST($deadlinePrefix . 'day',   'int'),
        GETPOST($deadlinePrefix . 'year',  'int')
    );
    $newTask->progress       = 0;

    $newTask->array_options['options_digirisk_action_type']     = (int) $action_type;
    $newTask->array_options['options_fk_accident']              = $accident->id;
    $newTask->array_options['options_fk_accidentinvestigation'] = $object->id;

    $result = $newTask->create($user);
    if ($result > 0) {
        $assignedUser = GETPOST('assigned_user' . $action_type, 'int');
        if ($assignedUser > 0) {
            $newTask->add_contact($assignedUser, 'TASKEXECUTIVE', 'internal');
        }
        setEventMessages($langs->trans('ActionAdded'), []);
    } else {
        setEventMessages($newTask->error, [], 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

if ($action === 'closeAction' && $permissiontoadd && $taskid > 0) {
    $taskToClose           = new Task($db);
    $taskToClose->fetch($taskid);
    $taskToClose->progress = 100;
    $result                = $taskToClose->update($user);
    if ($result > 0) {
        setEventMessages($langs->trans('ActionClosed'), []);
    } else {
        setEventMessages($taskToClose->error, [], 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

if ($action === 'updateProgress' && $permissiontoadd && $taskid > 0) {
    $taskToUpdate           = new Task($db);
    $taskToUpdate->fetch($taskid);
    $taskToUpdate->progress = min(100, max(0, GETPOST('progress_value', 'int')));
    $result                 = $taskToUpdate->update($user);
    if ($result <= 0) {
        setEventMessages($taskToUpdate->error, [], 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
    exit();
}

/*
 * View
 */
$title   = $langs->trans('AccidentInvestigation');
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

saturne_header(1, '', $title, $helpUrl);

if ($id > 0 || !empty($ref)) {
    $object->fetch($id);
    $accident->fetch($object->fk_accident);

    saturne_get_fiche_head($object, 'actions', $title);
    saturne_banner_tab($object);

    print '<div class="fichecenter">';

    if ((int) $object->fk_task <= 0) {
        print '<div class="opacitymedium center" style="padding:30px">';
        print '<i class="fas fa-info-circle fa-2x" style="color:#888;display:block;margin-bottom:10px"></i>';
        print dol_escape_htmltag($langs->trans('InvestigationNotValidatedYet'));
        print '</div>';
    } else {
        $sectionConfig = [
            1 => ['titleKey' => 'CurativeActions',  'addKey' => 'AddCurativeAction'],
            2 => ['titleKey' => 'CorrectiveActions', 'addKey' => 'AddCorrectiveAction'],
        ];

        foreach ($sectionConfig as $type => $cfg) {
            $actions = getAccidentInvestigationActionsByType($db, (int) $object->fk_task, $type);
            $total   = count($actions);
            $done    = 0;
            foreach ($actions as $row) {
                if ((int) $row->progress >= 100) {
                    $done++;
                }
            }

            $formId = 'digirisk-add-action-form-' . $type;
            $linkId = 'digirisk-add-action-link-' . $type;

            print '<div class="div-table-responsive" style="margin-bottom:24px">';

            // Section header
            print '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">';
            print '<span class="titre">' . $langs->trans($cfg['titleKey']);
            print ' <span class="badge badge-secondary marginleftonlyshort">';
            print $langs->trans('XActionsOfY', $done, $total);
            print '</span></span>';
            print '</div>';

            // Table
            print '<table class="tagtable liste" width="100%">';
            print '<thead><tr class="liste_titre">';
            print '<th class="liste_titre" width="20">&nbsp;</th>';
            print '<th class="liste_titre">' . $langs->trans('Ref') . '</th>';
            print '<th class="liste_titre">' . $langs->trans('ActionLabel') . '</th>';
            print '<th class="liste_titre">' . $langs->trans('ActionAssignedTo') . '</th>';
            print '<th class="liste_titre">' . $langs->trans('ActionDeadline') . '</th>';
            print '<th class="liste_titre" width="140">' . $langs->trans('ActionProgress') . '</th>';
            print '<th class="liste_titre">' . $langs->trans('Status') . '</th>';
            print '<th class="liste_titre" width="40">&nbsp;</th>';
            print '</tr></thead>';
            print '<tbody>';

            if (empty($actions)) {
                print '<tr><td colspan="8" class="opacitymedium center" style="padding:14px">';
                print $langs->trans('NoActionsYet');
                print '</td></tr>';
            }

            foreach ($actions as $row) {
                $pct    = max(0, min(100, (int) $row->progress));
                $isDone = ($pct >= 100);
                print '<tr class="oddeven">';

                // Checkbox to close action
                print '<td class="center">';
                if ($permissiontoadd) {
                    if (!$isDone) {
                        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" style="margin:0">';
                        print '<input type="hidden" name="token" value="' . newToken() . '">';
                        print '<input type="hidden" name="action" value="closeAction">';
                        print '<input type="hidden" name="taskid" value="' . ((int) $row->rowid) . '">';
                        print '<input type="checkbox" title="' . dol_escape_htmltag($langs->trans('MarkAsDone')) . '" onchange="this.form.submit()">';
                        print '</form>';
                    } else {
                        print '<input type="checkbox" checked disabled>';
                    }
                }
                print '</td>';

                // Ref (link to task)
                print '<td>';
                print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $row->rowid) . '" target="_blank">';
                print dol_escape_htmltag($row->ref);
                print '</a></td>';

                // Label
                print '<td>' . dol_escape_htmltag($row->label) . '</td>';

                // Assignee
                $assignee = !empty($row->user_fullname) ? trim($row->user_fullname) : '';
                print '<td>' . ($assignee !== '' ? dol_escape_htmltag($assignee) : '&mdash;') . '</td>';

                // Deadline
                print '<td>';
                if (!empty($row->datee) && $row->datee !== '0000-00-00 00:00:00') {
                    print dol_print_date($db->jdate($row->datee), 'day');
                } else {
                    print '&mdash;';
                }
                print '</td>';

                // Progress bar + inline edit
                $barClass = $isDone ? 'progress-bar-success' : 'progress-bar-info';
                print '<td style="padding:4px 8px">';
                print '<div class="progress sm" style="margin-bottom:4px">';
                print '<div class="progress-bar ' . $barClass . '" style="width:' . $pct . '%" title="' . $pct . '%"></div>';
                print '</div>';
                if ($permissiontoadd) {
                    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" style="display:flex;align-items:center;gap:4px;margin:0">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="updateProgress">';
                    print '<input type="hidden" name="taskid" value="' . ((int) $row->rowid) . '">';
                    print '<input type="number" name="progress_value" value="' . $pct . '" min="0" max="100" style="width:52px;padding:1px 4px;font-size:0.85em">';
                    print '<span style="font-size:0.85em;color:#666">%</span>';
                    print '<button type="submit" class="butActionShort" style="padding:1px 6px;font-size:0.8em;min-width:0">';
                    print '<i class="fas fa-check"></i>';
                    print '</button>';
                    print '</form>';
                } else {
                    print '<span style="font-size:0.8em;color:#666">' . $pct . ' %</span>';
                }
                print '</td>';

                // Status
                print '<td>' . $task->LibStatut((int) $row->fk_statut, 4) . '</td>';

                // External link
                print '<td class="right">';
                print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $row->rowid) . '" target="_blank"';
                print ' title="' . dol_escape_htmltag($langs->trans('OpenTask')) . '">';
                print '<i class="fas fa-external-link-alt"></i>';
                print '</a>';
                print '</td>';

                print '</tr>';
            }

            print '</tbody></table>';

            // Inline add-action form (JS toggle, no AJAX)
            if ($permissiontoadd) {
                print '<div style="margin-top:6px">';

                // Toggle link
                print '<span id="' . $linkId . '">';
                print '<a href="#" style="font-size:0.9em"';
                print ' onclick="document.getElementById(\'' . $formId . '\').style.display=\'block\';';
                print 'document.getElementById(\'' . $linkId . '\').style.display=\'none\';return false;">';
                print '<i class="fas fa-plus-circle"></i>&nbsp;' . $langs->trans($cfg['addKey']);
                print '</a></span>';

                // Hidden form
                print '<div id="' . $formId . '" style="display:none;margin-top:8px;padding:12px;background:#f8f9fa;border-radius:4px">';
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="addAction">';
                print '<input type="hidden" name="action_type" value="' . $type . '">';
                print '<table class="noborder" width="100%"><tr class="oddeven">';

                print '<td style="width:36%">';
                print '<label>' . $langs->trans('ActionLabel') . ' *</label><br>';
                print '<input type="text" name="action_label" class="minwidth300" required>';
                print '</td>';

                print '<td style="width:20%">';
                print '<label>' . $langs->trans('ActionDeadline') . '</label><br>';
                // Prefix suffixed with the type: both sections are rendered on the same page and
                // the datepicker fills its hidden day/month/year fields through getElementById
                print $form->selectDate('', 'actiondeadline' . $type, 0, 0, 1, 'addActionForm' . $type);
                print '</td>';

                print '<td style="width:24%">';
                print '<label>' . $langs->trans('ActionAssignedTo') . '</label><br>';
                print $form->select_dolusers('', 'assigned_user' . $type, 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
                print '</td>';

                print '<td style="vertical-align:bottom;white-space:nowrap">';
                print '<button type="submit" class="butAction butActionShort">' . $langs->trans('Add') . '</button>';
                print '&nbsp;';
                // Cancel: hide form, restore toggle link
                print '<a href="#" class="butActionDelete butActionShort"';
                print ' onclick="document.getElementById(\'' . $formId . '\').style.display=\'none\';';
                print 'document.getElementById(\'' . $linkId . '\').style.display=\'inline\';return false;">';
                print $langs->trans('Cancel') . '</a>';
                print '</td>';

                print '</tr></table>';
                print '</form>';
                print '</div>'; // #formId
                print '</div>'; // margin-top wrapper
            }

            print '</div>'; // div-table-responsive
        }

        // Footer note
        print '<div class="opacitymedium" style="font-size:0.85em;text-align:right;margin-top:4px">';
        print $langs->trans('ActionsAreProjectTasks');
        if ((int) $object->fk_task > 0) {
            print '&nbsp;&mdash;&nbsp;';
            print '<a href="' . dol_buildpath('/projet/tasks/task.php', 1) . '?id=' . ((int) $object->fk_task) . '" target="_blank">';
            print $langs->trans('SeeProjectTasksView');
            print '</a>';
        }
        print '</div>';
    }

    print '</div>'; // fichecenter

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
