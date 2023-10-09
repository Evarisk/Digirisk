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
 * \brief   Library files with common functions for Accident investigation
 */

/**
 * Prepare array of tabs for Accident investigation
 *
 * @param  AccidentInvestigation $object Accident investigation
 * @return array                         Array of tabs
 * @throws Exception
 */
function accidentinvestigation_prepare_head(AccidentInvestigation $object): array
{
	$moreparam['attendantTableMode'] = 'simple';

	return saturne_object_prepare_head($object, [], $moreparam, true);
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
