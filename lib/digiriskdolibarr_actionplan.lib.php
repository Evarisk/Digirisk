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
 * \file    lib/digiriskdolibarr_actionplan.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for the action plan (PAPRIPACT)
 *
 * The Kanban, the Gantt and the CSV export display the same corrective actions, so the
 * GP/UT, risk level and tag criteria are resolved here once instead of in each consumer.
 */

/**
 * Read the action plan filter criteria from the request
 *
 * @return array Filters: element (GP/UT id), element_children (include the sub elements),
 *               scale (risk level 1 to 4), tags (category ids)
 */
function digiriskActionPlanGetFilters(): array
{
    // "Remove filter" wins over any criterion still posted by the form
    if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) {
        return ['element' => 0, 'element_children' => 1, 'scale' => 0, 'tags' => []];
    }

    $tags = GETPOST('search_tags', 'array');
    $tags = is_array($tags) ? array_values(array_filter(array_map('intval', $tags))) : [];

    return [
        // The empty option of the GP/UT selector posts -1, it means "every GP/UT" like 0
        'element'          => max(0, GETPOSTINT('search_element')),
        // Sub elements are included by default: a GP is rarely interesting without its work units
        'element_children' => GETPOSTISSET('search_element_children') ? GETPOSTINT('search_element_children') : 1,
        'scale'            => GETPOSTINT('search_scale'),
        'tags'             => $tags,
    ];
}

/**
 * Return whether at least one action plan filter is active
 *
 * @param  array $filters Filters from digiriskActionPlanGetFilters()
 * @return bool           True if the task list is restricted
 */
function digiriskActionPlanHasFilters(array $filters): bool
{
    return !empty($filters['element']) || !empty($filters['scale']) || !empty($filters['tags']);
}

/**
 * Return the hidden inputs carrying the current filters through a form (exports, view switch)
 *
 * @param  array  $filters Filters from digiriskActionPlanGetFilters()
 * @return string          HTML hidden inputs
 */
function digiriskActionPlanFilterHiddenInputs(array $filters): string
{
    $out  = '<input type="hidden" name="search_element" value="' . (int) $filters['element'] . '">';
    $out .= '<input type="hidden" name="search_element_children" value="' . (int) $filters['element_children'] . '">';
    $out .= '<input type="hidden" name="search_scale" value="' . (int) $filters['scale'] . '">';
    foreach ($filters['tags'] as $tagID) {
        $out .= '<input type="hidden" name="search_tags[]" value="' . (int) $tagID . '">';
    }

    return $out;
}

/**
 * Return the GP/UT tree of the current entity, ordered as the organization navigation
 *
 * @param  DoliDB $db Database handler
 * @return array      ['flat' => [id => ['ref', 'label', 'type', 'depth']], 'children' => [parent id => [child ids]]]
 * @throws Exception
 */
function digiriskActionPlanGetElementTree(DoliDB $db): array
{
    require_once __DIR__ . '/../class/digiriskelement.class.php';

    $digiriskElement = new DigiriskElement($db);
    $activeElements  = $digiriskElement->getActiveDigiriskElements('current');
    if (!is_array($activeElements) || empty($activeElements)) {
        return ['flat' => [], 'children' => []];
    }

    // Elements moved to the trash are still validated on old instances, drop the whole trash branch
    $trashID = getDolGlobalInt('DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH');
    if ($trashID > 0) {
        $trashedElements = $digiriskElement->fetchDigiriskElementFlat($trashID, $activeElements, 'current', true);
        $activeElements  = array_diff_key($activeElements, $trashedElements);
        if (empty($activeElements)) {
            return ['flat' => [], 'children' => []];
        }
    }

    $flat     = [];
    $children = [];
    foreach ($digiriskElement->fetchDigiriskElementFlat(0, $activeElements, 'current') as $elementID => $flatElement) {
        $object                 = $flatElement['object'];
        $flat[(int) $elementID] = [
            'ref'   => $object->ref,
            'label' => $object->label,
            'type'  => $object->element_type,
            'depth' => (int) $flatElement['depth'],
        ];
        $children[(int) $object->fk_parent][] = (int) $elementID;
    }

    return ['flat' => $flat, 'children' => $children];
}

/**
 * Return a GP/UT and, optionally, all its descendants
 *
 * @param  array $elementTree     Tree from digiriskActionPlanGetElementTree()
 * @param  int   $elementID       Selected GP/UT
 * @param  bool  $includeChildren Add the whole sub tree
 * @return array                  Element ids
 */
function digiriskActionPlanGetElementBranch(array $elementTree, int $elementID, bool $includeChildren): array
{
    if ($elementID <= 0) {
        return [];
    }
    if (!$includeChildren) {
        return [$elementID];
    }

    $branch  = [];
    $toVisit = [$elementID];
    while (!empty($toVisit)) {
        $currentID = array_pop($toVisit);
        if (isset($branch[$currentID])) {
            continue;
        }
        $branch[$currentID] = $currentID;
        foreach ($elementTree['children'][$currentID] ?? [] as $childID) {
            $toVisit[] = $childID;
        }
    }

    return array_values($branch);
}

/**
 * Return the risk linked to each action plan task
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $taskIDs Task ids
 * @return array           [task id => risk id]
 */
function digiriskActionPlanGetTaskRisks(DoliDB $db, array $taskIDs): array
{
    if (empty($taskIDs)) {
        return [];
    }

    $sql  = 'SELECT fk_object, fk_risk FROM ' . MAIN_DB_PREFIX . 'projet_task_extrafields';
    $sql .= ' WHERE fk_risk > 0 AND fk_object IN (' . implode(',', array_map('intval', $taskIDs)) . ')';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $taskRisks = [];
    while ($obj = $db->fetch_object($resql)) {
        $taskRisks[(int) $obj->fk_object] = (int) $obj->fk_risk;
    }
    $db->free($resql);

    return $taskRisks;
}

/**
 * Return the GP/UT carrying each risk
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $riskIDs Risk ids
 * @return array           [risk id => digirisk element id]
 */
function digiriskActionPlanGetRiskElements(DoliDB $db, array $riskIDs): array
{
    if (empty($riskIDs)) {
        return [];
    }

    $sql  = 'SELECT rowid, fk_element FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk';
    $sql .= ' WHERE rowid IN (' . implode(',', array_map('intval', $riskIDs)) . ')';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $riskElements = [];
    while ($obj = $db->fetch_object($resql)) {
        $riskElements[(int) $obj->rowid] = (int) $obj->fk_element;
    }
    $db->free($resql);

    return $riskElements;
}

/**
 * Return the risk level (1 to 4) of the last validated evaluation of each risk
 *
 * Same thresholds as RiskAssessment::getEvaluationScale(), resolved in one query
 * instead of one fetchFromParent() per risk.
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $riskIDs Risk ids
 * @return array           [risk id => scale]
 */
function digiriskActionPlanGetRiskScales(DoliDB $db, array $riskIDs): array
{
    if (empty($riskIDs)) {
        return [];
    }

    require_once __DIR__ . '/../class/riskanalysis/riskassessment.class.php';

    $sql  = 'SELECT fk_risk, cotation FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment';
    $sql .= ' WHERE status = ' . RiskAssessment::STATUS_VALIDATED;
    $sql .= '   AND fk_risk IN (' . implode(',', array_map('intval', $riskIDs)) . ')';
    $sql .= ' ORDER BY date_creation DESC, rowid DESC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $riskScales = [];
    while ($obj = $db->fetch_object($resql)) {
        $riskID = (int) $obj->fk_risk;
        if (isset($riskScales[$riskID])) {
            continue; // Rows are sorted, the first one seen is the last evaluation
        }
        $riskScales[$riskID] = digiriskActionPlanGetScaleFromCotation((int) $obj->cotation);
    }
    $db->free($resql);

    return $riskScales;
}

/**
 * Return the risk level matching a cotation
 *
 * @param  int $cotation Cotation of the evaluation
 * @return int           Scale between 1 and 4
 */
function digiriskActionPlanGetScaleFromCotation(int $cotation): int
{
    if ($cotation >= 80) {
        return 4;
    }
    if ($cotation >= 51) {
        return 3;
    }
    if ($cotation >= 48) {
        return 2;
    }

    return 1;
}

/**
 * Return the tags set on each action plan task
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $taskIDs Task ids
 * @return array           [task id => [category id, ...]]
 */
function digiriskActionPlanGetTaskCategories(DoliDB $db, array $taskIDs): array
{
    if (empty($taskIDs)) {
        return [];
    }

    $sql  = 'SELECT fk_project_task, fk_categorie FROM ' . MAIN_DB_PREFIX . 'categorie_project_task';
    $sql .= ' WHERE fk_project_task IN (' . implode(',', array_map('intval', $taskIDs)) . ')';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $taskCategories = [];
    while ($obj = $db->fetch_object($resql)) {
        $taskCategories[(int) $obj->fk_project_task][] = (int) $obj->fk_categorie;
    }
    $db->free($resql);

    return $taskCategories;
}

/**
 * Keep only the tasks matching the GP/UT, risk level and tag criteria
 *
 * A task without linked risk carries neither GP/UT nor risk level, so it is dropped as soon
 * as one of those two criteria is set.
 *
 * @param  DoliDB $db          Database handler
 * @param  array  $taskIDs     Task ids to filter
 * @param  array  $filters     Filters from digiriskActionPlanGetFilters()
 * @param  array  $elementTree Tree from digiriskActionPlanGetElementTree()
 * @return array               Matching task ids, in the given order
 * @throws Exception
 */
function digiriskActionPlanFilterTasks(DoliDB $db, array $taskIDs, array $filters, array $elementTree = []): array
{
    if (empty($taskIDs) || !digiriskActionPlanHasFilters($filters)) {
        return $taskIDs;
    }

    $keptTaskIDs = $taskIDs;

    if (!empty($filters['element']) || !empty($filters['scale'])) {
        $taskRisks = digiriskActionPlanGetTaskRisks($db, $keptTaskIDs);
        $riskIDs   = array_values(array_unique($taskRisks));

        if (!empty($filters['element'])) {
            if (empty($elementTree)) {
                $elementTree = digiriskActionPlanGetElementTree($db);
            }
            $branch       = digiriskActionPlanGetElementBranch($elementTree, (int) $filters['element'], !empty($filters['element_children']));
            $riskElements = digiriskActionPlanGetRiskElements($db, $riskIDs);
            $keptTaskIDs  = array_values(array_filter($keptTaskIDs, function ($taskID) use ($taskRisks, $riskElements, $branch) {
                $riskID = $taskRisks[$taskID] ?? 0;
                return $riskID > 0 && in_array($riskElements[$riskID] ?? 0, $branch, true);
            }));
        }

        if (!empty($filters['scale']) && !empty($keptTaskIDs)) {
            $riskScales  = digiriskActionPlanGetRiskScales($db, $riskIDs);
            $keptTaskIDs = array_values(array_filter($keptTaskIDs, function ($taskID) use ($taskRisks, $riskScales, $filters) {
                $riskID = $taskRisks[$taskID] ?? 0;
                return $riskID > 0 && ($riskScales[$riskID] ?? 0) == (int) $filters['scale'];
            }));
        }
    }

    if (!empty($filters['tags']) && !empty($keptTaskIDs)) {
        $taskCategories = digiriskActionPlanGetTaskCategories($db, $keptTaskIDs);
        $keptTaskIDs    = array_values(array_filter($keptTaskIDs, function ($taskID) use ($taskCategories, $filters) {
            return !empty(array_intersect($taskCategories[$taskID] ?? [], $filters['tags']));
        }));
    }

    return $keptTaskIDs;
}

/**
 * Return the Kanban columns of the action plan
 *
 * Two sources: the percentage thresholds of the module configuration, which is the historical
 * behaviour and stays the default, or the column dictionary once the configuration points to it.
 * The dictionary is ignored when it holds no active column, so a board is never left empty.
 *
 * @param  DoliDB $db Database handler
 * @return array      Ordered columns: [['key', 'label', 'icon', 'color', 'min', 'max'], ...]
 */
function digiriskActionPlanGetKanbanColumns(DoliDB $db): array
{
    global $langs;

    if (getDolGlobalString('DIGIRISKDOLIBARR_KANBAN_COLUMN_SOURCE') == 'dictionary') {
        $dictionaryColumns = digiriskActionPlanGetDictionaryColumns($db);
        if (!empty($dictionaryColumns)) {
            return $dictionaryColumns;
        }
    }

    $draftMax    = getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_DRAFT_MAX', 0);
    $progressMax = getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_PROGRESS_MAX', 80);
    $controlMax  = getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_CONTROL_MAX', 99);

    return [
        ['key' => 'draft',    'label' => $langs->trans('ColumnDraft'),      'icon' => 'fa-pencil-alt', 'color' => '#999999', 'min' => 0,                'max' => $draftMax],
        ['key' => 'progress', 'label' => $langs->trans('ColumnInProgress'), 'icon' => 'fa-play',       'color' => '#e9ad4f', 'min' => $draftMax + 1,    'max' => $progressMax],
        ['key' => 'control',  'label' => $langs->trans('ColumnInControl'),  'icon' => 'fa-search',     'color' => '#3085d6', 'min' => $progressMax + 1, 'max' => $controlMax],
        ['key' => 'done',     'label' => $langs->trans('ColumnDone'),       'icon' => 'fa-check',      'color' => '#47e58e', 'min' => 100,              'max' => 100],
    ];
}

/**
 * Return the active columns of the action plan column dictionary
 *
 * The seeded rows carry a translation key as label, a renamed one carries free text:
 * trans() returns the key unchanged when it is not a translation, so both work.
 *
 * @param  DoliDB $db Database handler
 * @return array      Ordered columns, empty when the dictionary holds no active row
 */
function digiriskActionPlanGetDictionaryColumns(DoliDB $db): array
{
    global $langs;

    $sql  = 'SELECT rowid, ref, label, progress_min, progress_max, color, picto FROM ' . MAIN_DB_PREFIX . 'c_digiriskdolibarr_actionplan_column';
    $sql .= ' WHERE active = 1';
    $sql .= '   AND entity IN (0, ' . getEntity('c_digiriskdolibarr_actionplan_column') . ')';
    $sql .= ' ORDER BY position ASC, rowid ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $columns = [];
    while ($obj = $db->fetch_object($resql)) {
        $columns[] = [
            'key'   => 'dict' . (int) $obj->rowid,
            'label' => $langs->trans($obj->label),
            'icon'  => !empty($obj->picto) ? $obj->picto : 'fa-columns',
            'color' => !empty($obj->color) ? $obj->color : '#999999',
            'min'   => (int) $obj->progress_min,
            'max'   => (int) $obj->progress_max,
        ];
    }
    $db->free($resql);

    return $columns;
}

/**
 * Return the column a progress percentage falls in
 *
 * Dictionary ranges may leave holes, so a percentage matching no range falls back on the last
 * column starting below it, and on the first column when it stands before every range.
 *
 * @param  array $columns  Columns from digiriskActionPlanGetKanbanColumns()
 * @param  int   $progress Progress percentage of the corrective action
 * @return array           Matching column, empty array when there is no column at all
 */
function digiriskActionPlanGetColumnForProgress(array $columns, int $progress): array
{
    if (empty($columns)) {
        return [];
    }

    $fallbackColumn = [];
    foreach ($columns as $column) {
        if ($progress >= $column['min'] && $progress <= $column['max']) {
            return $column;
        }
        if ($progress > $column['max']) {
            $fallbackColumn = $column;
        }
    }

    return !empty($fallbackColumn) ? $fallbackColumn : $columns[0];
}
