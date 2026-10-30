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

    $sql  = 'SELECT t.rowid, t.ref, t.label, t.datec, t.dateo, t.datee, t.progress, t.fk_statut, t.budget_amount,';
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
