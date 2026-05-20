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
 * Return subtasks of an accident investigation task filtered by digirisk_action_type.
 *
 * @param  DoliDB $db           Database handler
 * @param  int    $parentTaskId fk_task of the AccidentInvestigation
 * @param  int    $type         1 = curative, 2 = corrective
 * @return array                Array of stdClass rows
 */
function getAccidentInvestigationActionsByType(DoliDB $db, int $parentTaskId, int $type): array
{
    $results = [];

    $sql  = 'SELECT t.rowid, t.ref, t.label, t.datee, t.progress, t.fk_statut,';
    $sql .= ' (SELECT CONCAT(u2.firstname, \' \', u2.lastname)';
    $sql .= '  FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
    $sql .= '  INNER JOIN ' . MAIN_DB_PREFIX . 'c_type_contact ctc2';
    $sql .= '   ON ctc2.rowid = ec2.fk_c_type_contact';
    $sql .= '   AND ctc2.code = \'TASKEXECUTIVE\' AND ctc2.element = \'projet_task\'';
    $sql .= '  INNER JOIN ' . MAIN_DB_PREFIX . 'user u2 ON u2.rowid = ec2.fk_socpeople';
    $sql .= '  WHERE ec2.element_id = t.rowid LIMIT 1) AS user_fullname';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'projet_task t';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields ef ON ef.fk_object = t.rowid';
    $sql .= ' WHERE t.fk_task_parent = ' . $parentTaskId;
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
