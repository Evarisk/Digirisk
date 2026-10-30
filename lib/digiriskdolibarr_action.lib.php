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
 * \file    lib/digiriskdolibarr_action.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Actions of an actions tab (accident investigation, ticket): project tasks typed by the extrafield
 *          digirisk_action_type, grouped under one container task per type.
 */

/**
 * Return the container task of the actions of a type.
 *
 * The container is the first child task of that type: the accident investigation creates its two (T1 = curative,
 * T2 = corrective) when it is validated, the ticket creates its own with its first action of the type. It is found
 * by its type, whatever its label has been renamed to afterwards.
 *
 * @param  DoliDB $db           Database handler
 * @param  int    $parentTaskId Task the containers hang under: fk_task of the AccidentInvestigation, TKP-<ref> of the ticket
 * @param  int    $type         digirisk_action_type: 1 = curative, 2 = corrective, 3 = preventive
 * @return int                  Container task ID, 0 if none
 */
function digiriskdolibarr_get_action_container_task(DoliDB $db, int $parentTaskId, int $type): int
{
    // Without a parent the query would pick any top-level task of the type
    if ($parentTaskId <= 0) {
        return 0;
    }

    $sql  = 'SELECT t.rowid FROM ' . MAIN_DB_PREFIX . 'projet_task t';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields ef ON ef.fk_object = t.rowid';
    $sql .= ' WHERE t.fk_task_parent = ' . $parentTaskId;
    $sql .= '   AND ef.digirisk_action_type = ' . $type;
    $sql .= ' ORDER BY t.rowid ASC LIMIT 1';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return 0;
    }

    $obj = $db->fetch_object($resql);
    $db->free($resql);

    return !empty($obj) ? (int) $obj->rowid : 0;
}

/**
 * Return the actions of a type, filtered by digirisk_action_type.
 *
 * The container tasks themselves are excluded: they only group the actions in the project task view.
 * Actions created before they were parented to the container are still listed.
 *
 * @param  DoliDB $db           Database handler
 * @param  int    $parentTaskId Task the containers hang under: fk_task of the AccidentInvestigation, TKP-<ref> of the ticket
 * @param  int    $type         digirisk_action_type: 1 = curative, 2 = corrective, 3 = preventive
 * @return array                Array of stdClass rows
 */
function digiriskdolibarr_get_actions_by_type(DoliDB $db, int $parentTaskId, int $type): array
{
    $results = [];
    if ($parentTaskId <= 0) {
        return $results;
    }

    $containerTaskId = digiriskdolibarr_get_action_container_task($db, $parentTaskId, $type);

    // The assignee is the first executive of the task, the one digiriskdolibarr_update_action_field() replaces
    $sql  = 'SELECT t.rowid, t.ref, t.label, t.datec, t.dateo, t.datee, t.progress, t.fk_statut, t.budget_amount,';
    $sql .= ' u2.rowid AS user_id, CONCAT(u2.firstname, \' \', u2.lastname) AS user_fullname';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'projet_task t';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields ef ON ef.fk_object = t.rowid';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u2 ON u2.rowid = (';
    $sql .= '  SELECT ec2.fk_socpeople FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
    $sql .= '  INNER JOIN ' . MAIN_DB_PREFIX . 'c_type_contact ctc2';
    $sql .= '   ON ctc2.rowid = ec2.fk_c_type_contact';
    $sql .= '   AND ctc2.code = \'TASKEXECUTIVE\' AND ctc2.element = \'project_task\'';
    $sql .= '  WHERE ec2.element_id = t.rowid ORDER BY ec2.rowid ASC LIMIT 1)';
    $sql .= ' WHERE t.fk_task_parent IN (' . $containerTaskId . ', ' . $parentTaskId . ')';
    $sql .= '   AND t.rowid <> ' . $containerTaskId;
    $sql .= '   AND ef.digirisk_action_type = ' . $type;
    $sql .= ' ORDER BY t.datee ASC, t.rowid ASC';

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $results[] = $obj;
        }
        $db->free($resql);
    } else {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
    }

    return $results;
}

/**
 * Return the tags of the actions, in one query for all of them.
 *
 * The tags are linked to the task through categorie_project_task, whatever the type of their category: the kanban of
 * the action plan and digiriskdolibarr_get_action_tag_options() offer the tags of the projects as well.
 *
 * @param  DoliDB $db      Database handler
 * @param  int[]  $taskIds Action IDs
 * @return array<int, array<int, array{id: int, label: string, color: string}>> Tags of each action, by action ID
 */
function digiriskdolibarr_get_actions_tags(DoliDB $db, array $taskIds): array
{
    $tags = [];
    if (empty($taskIds) || !isModEnabled('category')) {
        return $tags;
    }

    $sql  = 'SELECT ct.fk_project_task, c.rowid, c.label, c.color';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'categorie_project_task ct';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'categorie c ON c.rowid = ct.fk_categorie';
    $sql .= ' WHERE ct.fk_project_task IN (' . implode(',', array_map('intval', $taskIds)) . ')';
    $sql .= '   AND c.entity IN (' . getEntity('category') . ')';
    $sql .= ' ORDER BY c.label ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return $tags;
    }

    while ($obj = $db->fetch_object($resql)) {
        $tags[(int) $obj->fk_project_task][] = ['id' => (int) $obj->rowid, 'label' => (string) $obj->label, 'color' => (string) $obj->color];
    }
    $db->free($resql);

    return $tags;
}

/**
 * Return the tags an action can be given: the same as on the kanban of the action plan, the categories of the tasks
 * then those of the projects.
 *
 * @param  Form $form Form handler
 * @return array<int, string> Full label of each tag, by category ID; empty without the module Categories
 */
function digiriskdolibarr_get_action_tag_options(Form $form): array
{
    $options = [];
    if (!isModEnabled('category')) {
        return $options;
    }

    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

    foreach ([Categorie::TYPE_PROJECT_TASK, Categorie::TYPE_PROJECT] as $categoryType) {
        $categories = $form->select_all_categories($categoryType, '', 'parent', 64, 0, 1);
        if (is_array($categories)) {
            $options += $categories;
        }
    }

    return $options;
}

/**
 * Create an action from the add-action form of its section (core/tpl/actions/): label, start date, deadline, budget,
 * assignee and tags. The action is created validated rather than left in draft in the project.
 *
 * The caller sets the project, the parent task, the ref and the extrafields of the task beforehand.
 *
 * @param  Task $task New task of the action
 * @param  int  $type digirisk_action_type of the section the form belongs to
 * @param  User $user User creating the action
 * @return int        ID of the action, <= 0 if KO with the errors in the task
 */
function digiriskdolibarr_create_action(Task $task, int $type, User $user): int
{
    // Each section renders its own form, so field names are suffixed with the action type
    $startPrefix    = 'actionstart' . $type;
    $deadlinePrefix = 'actiondeadline' . $type;

    $task->label      = GETPOST('action_label', 'alphanohtml');
    $task->date_start = dol_mktime(0, 0, 0, GETPOSTINT($startPrefix . 'month'), GETPOSTINT($startPrefix . 'day'), GETPOSTINT($startPrefix . 'year'));
    // Task::create() only writes date_end, the datee property is deprecated and ignored
    $task->date_end   = dol_mktime(23, 59, 59, GETPOSTINT($deadlinePrefix . 'month'), GETPOSTINT($deadlinePrefix . 'day'), GETPOSTINT($deadlinePrefix . 'year'));
    $task->progress   = 0;
    $task->status     = Task::STATUS_VALIDATED;
    // Left empty, the action gets no budget rather than a zero one
    if (GETPOST('action_budget', 'alpha') !== '') {
        $task->budget_amount = (float) price2num(GETPOST('action_budget', 'alpha'));
    }

    $result = $task->create($user);
    if ($result <= 0) {
        return $result;
    }

    $assignedUser = GETPOSTINT('assigned_user' . $type);
    if ($assignedUser > 0) {
        $task->add_contact($assignedUser, 'TASKEXECUTIVE', 'internal');
    }

    $tagIds = array_filter(array_map('intval', GETPOST('action_tags' . $type, 'array')));
    if (!empty($tagIds) && $task->setCategories($tagIds) < 0) {
        setEventMessages($task->error, $task->errors, 'warnings');
    }

    return $result;
}

/**
 * Change one field of an action, edited in place in the table of its section (js/modules/actions.js).
 *
 * The new value is posted in "value": text for the label, Y-m-d for the dates (empty to clear), amount for the budget
 * (empty for none), user ID for the assignee (none below 1), category IDs for the tags (none when absent).
 *
 * @param  Task   $task  Action, fetched
 * @param  string $field label, date_start, date_end, budget, assignee or tags
 * @param  User   $user  User making the change
 * @return array{label: string, old: string, new: string}|null Field and its values before and after, for the logs;
 *                                                              null if KO, with the errors in the task
 */
function digiriskdolibarr_update_action_field(Task $task, string $field, User $user): ?array
{
    global $conf, $db, $langs;

    $formatDate   = function ($date): string {
        return isDolTms($date) ? dol_print_date($date, 'day') : '';
    };
    $formatBudget = function ($amount) use ($conf, $langs): string {
        return ($amount !== null && $amount !== '') ? price($amount, 0, $langs, 1, -1, -1, $conf->currency) : '';
    };
    $formatTags   = function (int $taskId) use ($db): string {
        $tags = digiriskdolibarr_get_actions_tags($db, [$taskId]);
        return implode(', ', array_column($tags[$taskId] ?? [], 'label'));
    };

    switch ($field) {
        case 'label':
            $label = trim(GETPOST('value', 'alphanohtml'));
            if ($label === '') {
                $task->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('ActionLabel'));
                return null;
            }
            $change      = ['label' => $langs->transnoentities('ActionLabel'), 'old' => (string) $task->label, 'new' => $label];
            $task->label = $label;
            break;

        case 'date_start':
        case 'date_end':
            $isStart = $field === 'date_start';
            $date    = '';
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', GETPOST('value', 'alpha'), $matches)) {
                $date = $isStart ? dol_mktime(0, 0, 0, (int) $matches[2], (int) $matches[3], (int) $matches[1]) : dol_mktime(23, 59, 59, (int) $matches[2], (int) $matches[3], (int) $matches[1]);
            }
            $change = ['label' => $langs->transnoentities($isStart ? 'DateStart' : 'ActionDeadline'), 'old' => $formatDate($isStart ? $task->date_start : $task->date_end), 'new' => $formatDate($date)];
            if ($isStart) {
                $task->date_start = $date;
            } else {
                $task->date_end = $date;
            }
            break;

        case 'budget':
            $amount              = GETPOST('value', 'alpha') !== '' ? (float) price2num(GETPOST('value', 'alpha')) : null;
            $change              = ['label' => $langs->transnoentities('Budget'), 'old' => $formatBudget($task->budget_amount), 'new' => $formatBudget($amount)];
            $task->budget_amount = $amount;
            break;

        case 'assignee':
            // The assignee shown is the first executive of the task: only that one is replaced, the others stay
            $executives = function () use ($task): array {
                $contacts = $task->liste_contact(-1, 'internal', 0, 'TASKEXECUTIVE');
                return is_array($contacts) ? $contacts : [];
            };
            $current = null;
            foreach ($executives() as $contact) {
                if ($current === null || (int) $contact['rowid'] < (int) $current['rowid']) {
                    $current = $contact;
                }
            }

            $userId = GETPOSTINT('value');
            $change = ['label' => $langs->transnoentities('DigiriskActionAssignedTo'), 'old' => $current !== null ? trim($current['firstname'] . ' ' . $current['lastname']) : '', 'new' => ''];
            if ($current !== null && (int) $current['id'] === $userId) {
                $change['new'] = $change['old'];
                return $change;
            }

            if ($current !== null && $task->delete_contact((int) $current['rowid']) < 0) {
                return null;
            }
            if ($userId > 0) {
                if ($task->add_contact($userId, 'TASKEXECUTIVE', 'internal') < 0) {
                    return null;
                }
                foreach ($executives() as $contact) {
                    if ((int) $contact['id'] === $userId) {
                        $change['new'] = trim($contact['firstname'] . ' ' . $contact['lastname']);
                    }
                }
            }
            return $change;

        case 'tags':
            $change = ['label' => $langs->transnoentities('Categories'), 'old' => $formatTags($task->id), 'new' => ''];
            if ($task->setCategories(array_values(array_filter(array_map('intval', GETPOST('value', 'array'))))) < 0) {
                return null;
            }
            $change['new'] = $formatTags($task->id);
            return $change;

        default:
            $task->errors[] = $langs->trans('ErrorBadParameters');
            return null;
    }

    if ($task->update($user) <= 0) {
        return null;
    }

    return $change;
}

/**
 * Return the Gantt of the actions of an actions tab.
 *
 * An action is only given a deadline: its bar starts at the start date of its task when one was entered in the
 * project, else at its creation, and runs to its deadline, or stays open up to the end of the timeline while it has
 * none. The timeline covers whole weeks, months, quarters or years around the bars and today, and every position is
 * a percentage of it.
 *
 * @param  DoliDB                           $db            Database handler
 * @param  array<int, array<int, stdClass>> $actionsByType Actions of each type, from digiriskdolibarr_get_actions_by_type()
 * @return array{today: float, ticks: array<int, array{position: float, label: string}>, rows: array<int, array<int, array{action: stdClass, from: int, end: int, left: float, length: float, state: string, lateDays: int}>>}
 */
function digiriskdolibarr_get_actions_gantt(DoliDB $db, array $actionsByType): array
{
    $now   = dol_now();
    $first = $now;
    $last  = $now;

    $bars = [];
    foreach ($actionsByType as $type => $actions) {
        foreach ($actions as $action) {
            $end   = (!empty($action->datee) && $action->datee !== '0000-00-00 00:00:00') ? (int) $db->jdate($action->datee) : 0;
            $from  = (int) $db->jdate(!empty($action->dateo) ? $action->dateo : $action->datec);
            // A deadline set before the creation: the bar keeps the day of the deadline
            $start = ($end > 0 && $from > $end) ? (int) dol_get_first_hour($end, 'tzserver') : $from;

            $bars[$type][] = ['action' => $action, 'from' => $from, 'start' => $start, 'end' => $end];
            $first         = min($first, $start);
            $last          = max($last, $end > 0 ? $end : $now);
        }
    }

    // Unit of the timeline from its span: weeks up to two months, then months, quarters and years
    $span = ($last - $first) / 86400;
    if ($span <= 62) {
        $unit = 'week';
    } elseif ($span <= 366) {
        $unit = 'month';
    } elseif ($span <= 1100) {
        $unit = 'quarter';
    } else {
        $unit = 'year';
    }

    // Ticks from the start of the unit holding the first bar, the last one closes the unit holding the last bar.
    // mktime() works in the timezone of the server, like the dates of the database.
    $year  = (int) date('Y', $first);
    $month = (int) date('n', $first);
    $day   = (int) date('j', $first) - (int) date('N', $first) + 1;
    $tickAt = function (int $index) use ($unit, $year, $month, $day): int {
        if ($unit == 'week') {
            return (int) mktime(0, 0, 0, $month, $day + 7 * $index, $year);
        } elseif ($unit == 'month') {
            return (int) mktime(0, 0, 0, $month + $index, 1, $year);
        } elseif ($unit == 'quarter') {
            return (int) mktime(0, 0, 0, $month - ($month - 1) % 3 + 3 * $index, 1, $year);
        }
        return (int) mktime(0, 0, 0, 1, 1, $year + $index);
    };

    $tickTimes = [$tickAt(0)];
    while (end($tickTimes) <= $last) {
        $tickTimes[] = $tickAt(count($tickTimes));
    }
    $timelineStart = $tickTimes[0];
    $timelineEnd   = array_pop($tickTimes);
    $positionOf    = function (int $time) use ($timelineStart, $timelineEnd): float {
        return round(($time - $timelineStart) * 100 / ($timelineEnd - $timelineStart), 3);
    };

    $tickFormat = ['week' => '%d %b', 'month' => '%b %Y', 'quarter' => '%b %Y', 'year' => '%Y'];
    $ticks      = [];
    foreach ($tickTimes as $tickTime) {
        $ticks[] = ['position' => $positionOf($tickTime), 'label' => dol_print_date($tickTime, $tickFormat[$unit], 'tzserver')];
    }

    $rows = [];
    foreach ($bars as $type => $typeBars) {
        foreach ($typeBars as $bar) {
            $isDone = (int) $bar['action']->progress >= 100;
            $isLate = !$isDone && $bar['end'] > 0 && $bar['end'] < $now;
            $left   = $positionOf($bar['start']);

            $rows[$type][] = [
                'action'   => $bar['action'],
                'from'     => $bar['from'],
                'end'      => $bar['end'],
                'left'     => $left,
                // Without deadline, the bar stays open up to the end of the timeline. A bar keeps a visible width even
                // when it starts and ends on the same day
                'length'   => max(round(($bar['end'] > 0 ? $positionOf($bar['end']) : 100) - $left, 3), 0.8),
                'state'    => $isDone ? 'done' : ($isLate ? 'late' : ($bar['end'] > 0 ? 'progress' : 'open')),
                'lateDays' => $isLate ? (int) floor(($now - $bar['end']) / 86400) + 1 : 0,
            ];
        }
    }

    return ['today' => $positionOf($now), 'ticks' => $ticks, 'rows' => $rows];
}
