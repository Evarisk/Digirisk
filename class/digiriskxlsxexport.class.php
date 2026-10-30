<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    class/digiriskxlsxexport.class.php
 * \ingroup digiriskdolibarr
 * \brief   Base of the Excel exports of Digirisk: what the ticket register and the accidents share
 */

// Load Saturne libraries
dol_include_once('/saturne/class/saturnexlsxexport.class.php');

/**
 * Class for DigiriskXlsxExport
 *
 * A ticket and an accident investigation both carry actions: project tasks typed by the extrafield
 * digirisk_action_type, hanging under a parent task, directly or under the container task of their type.
 */
class DigiriskXlsxExport extends SaturneXlsxExport
{
    /**
     * Translation key of each type of action, by value of the extrafield digirisk_action_type
     */
    public const ACTION_TYPES = [1 => 'DigiriskXlsxCurative', 2 => 'DigiriskXlsxCorrective', 3 => 'DigiriskXlsxPreventive'];

    /**
     * Colours of an overdue action: background, text
     */
    public const OVERDUE_COLOURS = ['E05353', 'FFFFFF'];

    /**
     * Colours of a completed action: background, text
     */
    public const DONE_COLOURS = ['DCFCE7', '166534'];

    /**
     * @var array<int, string> Full name of each user, by id
     */
    protected array $userNames = [];

    /**
     * Ids listed in a value of a multiple select extrafield
     *
     * @param  string|null $value Comma separated ids
     * @return array              Ids
     */
    protected function getIds(?string $value): array
    {
        // An empty multiple select may hold -1
        return array_values(array_filter(array_map('intval', explode(',', (string) $value)), function (int $id): bool {
            return $id > 0;
        }));
    }

    /**
     * Load the full names of the users
     *
     * @param  array $userIds User ids
     * @return void
     */
    protected function fetchUserNames(array $userIds): void
    {
        $userIds = array_diff(array_filter(array_unique(array_map('intval', $userIds))), array_keys($this->userNames));
        foreach (array_chunk($userIds, 500) as $chunkIds) {
            $resql = $this->db->query('SELECT rowid, firstname, lastname FROM ' . MAIN_DB_PREFIX . 'user WHERE rowid IN (' . implode(',', $chunkIds) . ')');
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $this->userNames[(int) $obj->rowid] = trim($obj->firstname . ' ' . $obj->lastname);
                }
                $this->db->free($resql);
            }
        }
    }

    /**
     * Load the actions hanging under parent tasks: their child tasks, and the tasks under the container task of
     * each type of action, the containers themselves left out
     *
     * @param  array $ownerByParent Id of the object owning the actions (ticket, investigation), by parent task id
     * @return array                Records of the actions, by owner id, the earliest due first
     */
    protected function fetchActions(array $ownerByParent): array
    {
        global $langs;

        $actionsByOwner = [];
        if (empty($ownerByParent)) {
            return $actionsByOwner;
        }

        $children      = $this->fetchChildTasks(array_keys($ownerByParent));
        $grandChildren = $this->fetchChildTasks(array_keys($children));

        // The container of a type is the first child task of that type, it only groups the actions of the type
        $containers = [];
        foreach ($children as $task) {
            $type = (int) $task->digirisk_action_type;
            $key  = $task->fk_task_parent . '-' . $type;
            if ($type > 0 && (!isset($containers[$key]) || (int) $task->rowid < $containers[$key])) {
                $containers[$key] = (int) $task->rowid;
            }
        }
        $containerIds = array_flip($containers);

        $tasks = [];
        foreach ($children as $taskId => $task) {
            if (!isset($containerIds[$taskId])) {
                $tasks[$taskId] = [$task, $ownerByParent[(int) $task->fk_task_parent]];
            }
        }
        foreach ($grandChildren as $taskId => $task) {
            $parent         = $children[(int) $task->fk_task_parent];
            $tasks[$taskId] = [$task, $ownerByParent[(int) $parent->fk_task_parent]];
        }
        $executives = $this->fetchTaskExecutives(array_keys($tasks));

        foreach ($tasks as $taskId => [$task, $ownerId]) {
            $type     = (int) $task->digirisk_action_type;
            $progress = (int) $task->progress;
            $due      = $this->getTimestamp($task->datee);
            $overdue  = $progress < 100 && !empty($due) && $due < $this->now;

            $actionsByOwner[$ownerId][] = [
                'task'      => $task,
                'url'       => DOL_MAIN_URL_ROOT . '/projet/tasks/task.php?id=' . $taskId,
                'type'      => $langs->transnoentities(self::ACTION_TYPES[$type] ?? 'DigiriskXlsxTask'),
                'progress'  => $progress,
                'start'     => $this->getTimestamp($task->dateo),
                'due'       => $due,
                'state'     => $progress >= 100 ? 'done' : ($progress > 0 ? 'inProgress' : 'toDo'),
                'overdue'   => $overdue,
                'lateDays'  => $overdue ? (int) floor(($this->now - $due) / 86400) : null,
                'executive' => $executives[$taskId] ?? '',
            ];
        }
        foreach ($actionsByOwner as $ownerId => $actions) {
            usort($actions, function (array $a, array $b): int {
                return [$a['due'] ?: PHP_INT_MAX, (int) $a['task']->rowid] <=> [$b['due'] ?: PHP_INT_MAX, (int) $b['task']->rowid];
            });
            $actionsByOwner[$ownerId] = $actions;
        }

        return $actionsByOwner;
    }

    /**
     * Load the child tasks of the given tasks, with their type of action
     *
     * @param  array $parentIds Parent task ids
     * @return array            Tasks, by id
     */
    protected function fetchChildTasks(array $parentIds): array
    {
        $tasks = [];
        foreach (array_chunk($parentIds, 500) as $chunkIds) {
            $sql  = 'SELECT t.rowid, t.ref, t.label, t.fk_task_parent, t.dateo, t.datee, t.progress, t.budget_amount, ef.digirisk_action_type';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'projet_task as t';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields as ef ON ef.fk_object = t.rowid';
            $sql .= ' WHERE t.fk_task_parent IN (' . implode(',', array_map('intval', $chunkIds)) . ')';
            $sql .= ' ORDER BY t.rowid ASC';

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $tasks[(int) $obj->rowid] = $obj;
                }
                $this->db->free($resql);
            }
        }

        return $tasks;
    }

    /**
     * Load the person in charge of each task, its executive contact
     *
     * @param  array $taskIds Task ids
     * @return array          Full name, by task id
     */
    protected function fetchTaskExecutives(array $taskIds): array
    {
        $executives = [];
        foreach (array_chunk($taskIds, 500) as $chunkIds) {
            $sql  = 'SELECT ec.element_id, u.firstname, u.lastname FROM ' . MAIN_DB_PREFIX . 'element_contact as ec';
            $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . "c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact AND ctc.code = 'TASKEXECUTIVE' AND ctc.element = 'project_task'";
            $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'user as u ON u.rowid = ec.fk_socpeople';
            $sql .= ' WHERE ec.element_id IN (' . implode(',', array_map('intval', $chunkIds)) . ')';
            $sql .= ' ORDER BY ec.rowid ASC';

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    if (!isset($executives[(int) $obj->element_id])) {
                        $executives[(int) $obj->element_id] = trim($obj->firstname . ' ' . $obj->lastname);
                    }
                }
                $this->db->free($resql);
            }
        }

        return $executives;
    }

    /**
     * Number of actions, of completed ones and of overdue ones
     *
     * @param  array $actions Records of the actions
     * @return array          Actions, completed, overdue
     */
    protected function countActions(array $actions): array
    {
        $nbDone    = 0;
        $nbOverdue = 0;
        foreach ($actions as $action) {
            $nbDone    += $action['state'] === 'done' ? 1 : 0;
            $nbOverdue += $action['overdue'] ? 1 : 0;
        }

        return [count($actions), $nbDone, $nbOverdue];
    }

    /**
     * Columns describing an action
     *
     * @return array Columns
     */
    protected function getActionColumns(): array
    {
        global $langs;

        return [
            ['label' => $langs->transnoentities('DigiriskActionType'), 'width' => 14],
            ['label' => $langs->transnoentities('Ref'), 'width' => 12],
            ['label' => $langs->transnoentities('Label'), 'width' => 36, 'format' => 'longtext'],
            ['label' => $langs->transnoentities('DigiriskXlsxResponsible'), 'width' => 18],
            ['label' => $langs->transnoentities('DateStart'), 'width' => 12, 'format' => 'date'],
            ['label' => $langs->transnoentities('DigiriskXlsxDueDate'), 'width' => 12, 'format' => 'date'],
            ['label' => $langs->transnoentities('Progression'), 'width' => 11, 'format' => 'percent'],
            ['label' => $langs->transnoentities('Status'), 'width' => 12],
            ['label' => $langs->transnoentities('DigiriskXlsxLateDays'), 'width' => 11, 'format' => 'int'],
            ['label' => $langs->transnoentities('Budget'), 'width' => 12, 'format' => 'money'],
        ];
    }

    /**
     * Cells describing an action, in the order of getActionColumns()
     *
     * @param  array $action Record of the action
     * @return array         Cells
     */
    protected function getActionCells(array $action): array
    {
        global $langs;

        // Overdue in red, completed in green, as the action plans of Digirisk show them
        if ($action['overdue']) {
            $state = ['value' => $langs->transnoentities('DigiriskXlsxOverdue'), 'colours' => self::OVERDUE_COLOURS];
        } elseif ($action['state'] === 'done') {
            $state = ['value' => $langs->transnoentities('DigiriskXlsxDone'), 'colours' => self::DONE_COLOURS];
        } else {
            $state = $langs->transnoentities($action['state'] === 'inProgress' ? 'DigiriskXlsxInProgress' : 'DigiriskXlsxToDo');
        }

        return [
            $action['type'],
            ['value' => $action['task']->ref, 'url' => $action['url']],
            $action['task']->label,
            $action['executive'],
            $this->getExcelDate($action['start']),
            $this->getExcelDate($action['due']),
            $action['progress'] / 100,
            $state,
            $action['lateDays'],
            $action['task']->budget_amount !== null ? (float) $action['task']->budget_amount : null,
        ];
    }

    /**
     * Sort labels in their natural order, what was not filled in last
     *
     * @param  array  $labels    Labels, by key
     * @param  string $notFilled Label of what was not filled in
     * @return void
     */
    protected function sortLabels(array &$labels, string $notFilled): void
    {
        uasort($labels, function ($a, $b) use ($notFilled): int {
            return [$a === $notFilled] <=> [$b === $notFilled] ?: strnatcasecmp((string) $a, (string) $b);
        });
    }
}
