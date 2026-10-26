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
 * \file    lib/digiriskdolibarr_accidentinvestigation.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for accident investigation
 */

/**
 * Prepare accident investigation pages header
 *
 * @param  AccidentInvestigation $object Accident investigation
 * @return array                         Array of tabs
 * @throws Exception
 */
function accidentinvestigation_prepare_head(AccidentInvestigation $object): array
{
    global $langs;

    $moreParams['attendantTableMode'] = 'simple';

    // Key 15 places this tab between attendants (10) and note (20) after ksort.
    $head     = [];
    $head[15] = [
        dol_buildpath('/digiriskdolibarr/view/accidentinvestigation/accidentinvestigation_actions.php', 1) . '?id=' . $object->id,
        '<i class="fas fa-tasks pictofixedwidth"></i>' . $langs->trans('ActionsTab'),
        'actions',
    ];

    return saturne_object_prepare_head($object, $head, $moreParams, true);
}

/**
 * Return the container task (T1 = curative, T2 = corrective) of an accident investigation.
 *
 * Both containers are created when the investigation is validated, so the oldest child task of a
 * given type is always the container, whatever its label has been renamed to afterwards.
 *
 * @param  DoliDB $db           Database handler
 * @param  int    $parentTaskId fk_task of the AccidentInvestigation
 * @param  int    $type         1 = curative, 2 = corrective
 * @return int                  Container task ID, 0 if none
 */
function getAccidentInvestigationActionContainerTask(DoliDB $db, int $parentTaskId, int $type): int
{
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
 * Return the actions of an accident investigation filtered by digirisk_action_type.
 *
 * The T1/T2 container tasks themselves are excluded: they only group the actions in the project
 * task view. Actions created before they were parented to the container are still listed.
 *
 * @param  DoliDB $db           Database handler
 * @param  int    $parentTaskId fk_task of the AccidentInvestigation
 * @param  int    $type         1 = curative, 2 = corrective
 * @return array                Array of stdClass rows
 */
function getAccidentInvestigationActionsByType(DoliDB $db, int $parentTaskId, int $type): array
{
    $results = [];

    $containerTaskId = getAccidentInvestigationActionContainerTask($db, $parentTaskId, $type);

    $sql  = 'SELECT t.rowid, t.ref, t.label, t.datec, t.dateo, t.datee, t.progress, t.fk_statut,';
    $sql .= ' (SELECT CONCAT(u2.firstname, \' \', u2.lastname)';
    $sql .= '  FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
    $sql .= '  INNER JOIN ' . MAIN_DB_PREFIX . 'c_type_contact ctc2';
    $sql .= '   ON ctc2.rowid = ec2.fk_c_type_contact';
    $sql .= '   AND ctc2.code = \'TASKEXECUTIVE\' AND ctc2.element = \'project_task\'';
    $sql .= '  INNER JOIN ' . MAIN_DB_PREFIX . 'user u2 ON u2.rowid = ec2.fk_socpeople';
    $sql .= '  WHERE ec2.element_id = t.rowid LIMIT 1) AS user_fullname';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'projet_task t';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields ef ON ef.fk_object = t.rowid';
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
 * Return the Gantt of the actions of an accident investigation.
 *
 * An action is only given a deadline: its bar starts at the start date of its task when one was entered in the
 * project, else at its creation, and runs to its deadline, or stays open up to the end of the timeline while it has
 * none. The timeline covers whole weeks, months, quarters or years around the bars and today, and every position is
 * a percentage of it.
 *
 * @param  DoliDB                           $db            Database handler
 * @param  array<int, array<int, stdClass>> $actionsByType Actions of each type, from getAccidentInvestigationActionsByType()
 * @return array{today: float, ticks: array<int, array{position: float, label: string}>, rows: array<int, array<int, array{action: stdClass, from: int, end: int, left: float, length: float, state: string, lateDays: int}>>}
 */
function getAccidentInvestigationActionsGantt(DoliDB $db, array $actionsByType): array
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

/**
 * Return total budget of a task and his children
 *
 * @param  int $taskId ID of the task.
 * @return int         Total budget.
 * @throws Exception
 */
function get_recursive_task_budget(int $taskId): int {
	$totalBudget  = 0;
	$childrenTask = saturne_fetch_all_object_type('SaturneTask', '', '', 0, 0, ['customsql' => 'fk_task_parent = ' . $taskId]);

	if (is_array($childrenTask) && !empty($childrenTask)) {
		foreach ($childrenTask as $childTask) {
			$totalBudget   += $childTask->budget_amount;
			$childrenBudget = get_recursive_task_budget($childTask->id);
			$totalBudget   += $childrenBudget;
		}
	} else {
		return 0;
	}

	return $totalBudget;
}
