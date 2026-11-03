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
 * \file    class/actionplandashboard.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class file for the PAPRIPACT dashboard
 */

/**
 * Class for the PAPRIPACT dashboard
 *
 * The action plan page already resolves the displayed project, year and criteria, so the dashboard receives the
 * corrective actions the Kanban shows and its figures always match it. This class only computes them, the
 * actionplan_dashboard.tpl.php template draws them.
 */
class ActionPlanDashboard
{
    /**
     * @var int Number of late corrective actions listed, the most overdue first
     */
    public const LATE_LIST_SIZE = 8;

    /**
     * @var int Number of upcoming due dates listed, the nearest first
     */
    public const UPCOMING_LIST_SIZE = 5;

    /**
     * @var int Number of GP/UT listed, the most loaded first
     */
    public const ELEMENT_LIST_SIZE = 10;

    /**
     * @var int Days ahead a due date counts as close
     */
    public const SOON_DAYS = 30;

    /**
     * @var string Bar color of the corrective actions linked to no risk
     */
    private const NO_RISK_COLOR = '#a0aec0';

    /**
     * @var string Bar color of the lowest risk level, the grey of the cotation scale is too light for a bar
     */
    private const LOW_RISK_BAR_COLOR = '#9ca3af';

    /**
     * @var array Corrective actions of the action plan page, enriched by it (progress, due date, risk, GP/UT...)
     */
    private array $tasks;

    /**
     * @var array Kanban columns from digiriskActionPlanGetKanbanColumns()
     */
    private array $kanbanColumns;

    /**
     * @var array Risk levels (label and color) from Risk::getCotations()
     */
    private array $cotations;

    /**
     * @var array Criteria from digiriskActionPlanGetFilters(), year resolved
     */
    private array $filters;

    /**
     * @var string Kanban URL carrying the displayed project and year, a link adds the criteria
     */
    private string $kanbanUrl;

    /**
     * @var array<int, array{task: array<string, mixed>, progress: int, due_date: string, is_done: bool, is_late: bool, is_soon: bool, is_coming: bool, late_days: int, state: 'done'|'todo'|'late', column: string, level: int, element: int, budget: float, workload: int, timespent: int, carried: bool}> Indicators of each corrective action, computed once for every block
     */
    private array $indicators = [];

    /**
     * Constructor
     *
     * @param array  $tasks         Corrective actions displayed by the action plan page
     * @param array  $kanbanColumns Kanban columns from digiriskActionPlanGetKanbanColumns()
     * @param array  $cotations     Risk levels from Risk::getCotations()
     * @param array  $filters       Criteria from digiriskActionPlanGetFilters(), year resolved
     * @param string $kanbanUrl     Kanban URL carrying the displayed project and year
     */
    public function __construct(array $tasks, array $kanbanColumns, array $cotations, array $filters, string $kanbanUrl)
    {
        $this->tasks         = $tasks;
        $this->kanbanColumns = $kanbanColumns;
        $this->cotations     = $cotations;
        $this->filters       = $filters;
        $this->kanbanUrl     = $kanbanUrl;

        $this->computeIndicators();
    }

    /**
     * Load every block of the dashboard
     *
     * @return array Blocks: kpis, months, statuses, levels, followUp, upcoming, late, elements
     */
    public function load_dashboard(): array
    {
        return [
            'year'     => (int) $this->filters['year'],
            'nbTasks'  => count($this->indicators),
            'kanban'   => $this->getKanbanUrl([]),
            'kpis'     => $this->getKpis(),
            'months'   => $this->getMonths(),
            'statuses' => $this->getStatuses(),
            'levels'   => $this->getLevels(),
            'followUp' => $this->getFollowUp(),
            'upcoming' => $this->getUpcoming(),
            'late'     => $this->getLate(),
            'elements' => $this->getElements()
        ];
    }

    /**
     * Compute the indicators of each corrective action
     *
     * The due date is compared as a YYYY-MM-DD string, the format the page gives it: a corrective action is late
     * from the day after its due date, and only while it is not finished.
     *
     * @return void
     */
    private function computeIndicators(): void
    {
        $today     = dol_print_date(dol_now(), 'dayrfc');
        $soonLimit = dol_print_date(dol_time_plus_duree(dol_now(), self::SOON_DAYS, 'd'), 'dayrfc');

        foreach ($this->tasks as $task) {
            $progress = (int) $task['progress'];
            $dueDate  = (string) $task['date_end'];
            $isDone   = $progress >= 100;
            $isLate   = !$isDone && $dueDate !== '' && $dueDate < $today;
            $column   = digiriskActionPlanGetColumnForProgress($this->kanbanColumns, $progress);

            $this->indicators[] = [
                'task'      => $task,
                'progress'  => $progress,
                'due_date'  => $dueDate,
                'is_done'   => $isDone,
                'is_late'   => $isLate,
                'is_soon'   => !$isDone && $dueDate !== '' && $dueDate >= $today && $dueDate <= $soonLimit,
                'is_coming' => !$isDone && $dueDate !== '' && $dueDate >= $today,
                'late_days' => $isLate ? (int) round((strtotime($today) - strtotime($dueDate)) / 86400) : 0,
                'state'     => $isDone ? 'done' : ($isLate ? 'late' : 'todo'),
                'column'    => $column['key'] ?? '',
                // 0 stands for the corrective actions linked to no risk, they carry no level
                'level'     => !empty($task['risk_data']) ? digiriskActionPlanGetScaleFromCotation((int) $task['risk_data']['cotation']) : 0,
                // A GP/UT moved to the trash is out of the organization tree: it has an id but no reference
                'element'   => $task['element_ref'] !== '' ? (int) $task['element_id'] : 0,
                'budget'    => (float) $task['budget'],
                'workload'  => (int) $task['planned_workload'],
                'timespent' => (int) $task['duration_effective'],
                'carried'   => !empty($task['carried_over_from'])
            ];
        }
    }

    /**
     * Return the Kanban URL a block links to
     *
     * @param  array  $criteria Criteria replacing the displayed ones (element, element_children, scale)
     * @return string           Kanban URL
     */
    private function getKanbanUrl(array $criteria): string
    {
        return $this->kanbanUrl . digiriskActionPlanFilterUrlParams(array_merge($this->filters, $criteria));
    }

    /**
     * Return the sum of an indicator over the corrective actions matching a condition
     *
     * @param  string        $key       Indicator to add up, an empty key counts the actions
     * @param  callable|null $condition Condition on the indicators of an action, every action when null
     * @return float                    Sum
     */
    private function sum(string $key, ?callable $condition = null): float
    {
        $sum = 0;
        foreach ($this->indicators as $indicator) {
            if ($condition === null || $condition($indicator)) {
                $sum += ($key === '') ? 1 : $indicator[$key];
            }
        }

        return $sum;
    }

    /**
     * Get the key figures: progress, late actions, budget and workload
     *
     * @return array Tiles: title, icon, tone, value, detail, alert, url
     */
    private function getKpis(): array
    {
        global $conf, $langs;

        $nbTasks     = count($this->indicators);
        $nbDone      = (int) $this->sum('', fn ($indicator) => $indicator['is_done']);
        $nbLate      = (int) $this->sum('', fn ($indicator) => $indicator['is_late']);
        $nbSoon      = (int) $this->sum('', fn ($indicator) => $indicator['is_soon']);
        $nbNoManager = (int) $this->sum('', fn ($indicator) => empty($indicator['task']['responsible']) && !$indicator['is_done']);
        $budget      = $this->sum('budget');
        $budgetDone  = $this->sum('budget', fn ($indicator) => $indicator['is_done']);
        $workload    = (int) $this->sum('workload');
        $timeSpent   = (int) $this->sum('timespent');

        return [
            [
                'title'  => $langs->trans('ActionPlanKpiProgress'),
                'icon'   => 'fas fa-tasks',
                'tone'   => 'progress',
                'value'  => ($nbTasks > 0 ? (int) round($this->sum('progress') / $nbTasks) : 0) . ' %',
                'detail' => $langs->trans('ActionPlanKpiProgressDetail', $nbDone, $nbTasks),
                'alert'  => $nbNoManager > 0 ? $langs->trans('ActionPlanKpiNoManagerAlert', $nbNoManager) : '',
                'url'    => $this->getKanbanUrl([])
            ],
            [
                'title'  => $langs->trans('ActionPlanKpiLate'),
                'icon'   => 'fas fa-hourglass-end',
                'tone'   => 'late',
                'value'  => $nbLate,
                'detail' => $langs->trans('ActionPlanKpiLateDetail', $nbSoon, self::SOON_DAYS),
                'alert'  => '',
                'url'    => '#actionplan-dashboard-late'
            ],
            [
                'title'  => $langs->trans('ActionPlanKpiBudget'),
                'icon'   => 'fas fa-coins',
                'tone'   => 'budget',
                'value'  => price(round($budget), 0, $langs, 1, -1, 0, $conf->currency),
                'detail' => $langs->trans('ActionPlanKpiBudgetDetail', price(round($budgetDone), 0, $langs, 1, -1, 0, $conf->currency)),
                'alert'  => '',
                'url'    => $this->getKanbanUrl([])
            ],
            [
                'title'  => $langs->trans('ActionPlanKpiWorkload'),
                'icon'   => 'far fa-clock',
                'tone'   => 'workload',
                'value'  => $workload > 0 ? convertSecondToTime($workload, 'allhourmin') : '0',
                'detail' => $langs->trans('ActionPlanKpiWorkloadDetail', $timeSpent > 0 ? convertSecondToTime($timeSpent, 'allhourmin') : '0'),
                'alert'  => '',
                'url'    => $this->getKanbanUrl([])
            ]
        ];
    }

    /**
     * Get the corrective actions by month of their due date, split into finished, on time and late
     *
     * The actions without due date and the ones carried over from a previous year fall outside the months: they are
     * counted apart. The scale is rounded up to a multiple of four so the four grid lines land on whole numbers.
     *
     * @return array Months (name, label, done, todo, late), scale, ticks, nbLate, nbNoDueDate, nbPrevious
     */
    private function getMonths(): array
    {
        global $langs;

        $year   = (int) $this->filters['year'];
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'label' => rtrim($langs->transnoentitiesnoconv('MonthShort' . sprintf('%02d', $month)), '.'),
                'name'  => $langs->transnoentitiesnoconv('Month' . sprintf('%02d', $month)) . ' ' . $year,
                'done'  => 0,
                'todo'  => 0,
                'late'  => 0
            ];
        }

        $nbNoDueDate = 0;
        $nbPrevious  = 0;
        foreach ($this->indicators as $indicator) {
            if ($indicator['due_date'] === '') {
                $nbNoDueDate++;
            } elseif ((int) substr($indicator['due_date'], 0, 4) != $year) {
                $nbPrevious++;
            } else {
                $months[(int) substr($indicator['due_date'], 5, 2)][$indicator['state']]++;
            }
        }

        $max = 0;
        foreach ($months as $month) {
            $max = max($max, $month['done'] + $month['todo'] + $month['late']);
        }
        $scale = max(4, (int) ceil($max / 4) * 4);

        return [
            'months'      => array_values($months),
            'scale'       => $scale,
            'ticks'       => [$scale, $scale * 3 / 4, $scale / 2, $scale / 4, 0],
            'nbLate'      => (int) $this->sum('', fn ($indicator) => $indicator['is_late']),
            'nbNoDueDate' => $nbNoDueDate,
            'nbPrevious'  => $nbPrevious
        ];
    }

    /**
     * Get the corrective actions by Kanban column
     *
     * @return array Columns (label, color, nb, percent) and the largest count, the bars are drawn against it
     */
    private function getStatuses(): array
    {
        $statuses = ['rows' => [], 'max' => 0];
        foreach ($this->kanbanColumns as $column) {
            $nb = (int) $this->sum('', fn ($indicator) => $indicator['column'] == $column['key']);

            $statuses['rows'][] = [
                // The column labels come from trans(), already html encoded
                'label'   => dol_html_entity_decode($column['label'], ENT_QUOTES | ENT_HTML5),
                'icon'    => $column['icon'],
                'color'   => $column['color'],
                'nb'      => $nb,
                'percent' => count($this->indicators) > 0 ? (int) round(100 * $nb / count($this->indicators)) : 0
            ];
            $statuses['max'] = max($statuses['max'], $nb);
        }

        return $statuses;
    }

    /**
     * Get the corrective actions by level of their linked risk, with the finished ones and their budget
     *
     * @return array Levels (label, color, nb, done, budget, url) and the largest count, the bars are drawn against it
     */
    private function getLevels(): array
    {
        global $langs;

        $levelLabels = [];
        foreach ($this->cotations as $level => $cotation) {
            $levelLabels[$level] = ['label' => $cotation['label'], 'color' => $level == 1 ? self::LOW_RISK_BAR_COLOR : $cotation['color']];
        }
        $levelLabels[0] = ['label' => $langs->trans('ActionPlanNoLinkedRisk'), 'color' => self::NO_RISK_COLOR];

        $levels = ['rows' => [], 'max' => 0];
        foreach ($levelLabels as $level => $levelLabel) {
            $isLevel = fn ($indicator) => $indicator['level'] == $level;
            $nb      = (int) $this->sum('', $isLevel);

            // The actions linked to no risk only show up when there are some, the Kanban cannot filter them
            if ($level == 0 && $nb == 0) {
                continue;
            }

            $levels['rows'][] = [
                'label'  => $levelLabel['label'],
                'color'  => $levelLabel['color'],
                'nb'     => $nb,
                'done'   => (int) $this->sum('', fn ($indicator) => $isLevel($indicator) && $indicator['is_done']),
                'late'   => (int) $this->sum('', fn ($indicator) => $isLevel($indicator) && $indicator['is_late']),
                'budget' => $this->sum('budget', $isLevel),
                'url'    => $level > 0 ? $this->getKanbanUrl(['scale' => $level]) : ''
            ];
            $levels['max'] = max($levels['max'], $nb);
        }

        return $levels;
    }

    /**
     * Get the points to look after: unfinished actions nobody leads, without due date or linked to no risk
     *
     * @return array Rows: label, help, icon, nb
     */
    private function getFollowUp(): array
    {
        global $langs;

        $rows = [
            [
                'label' => $langs->trans('ActionPlanNoManager'),
                'help'  => $langs->trans('ActionPlanNoManagerHelp'),
                'icon'  => 'fas fa-user-slash',
                'nb'    => (int) $this->sum('', fn ($indicator) => empty($indicator['task']['responsible']) && !$indicator['is_done'])
            ],
            [
                'label' => $langs->trans('ActionPlanNoDueDate'),
                'help'  => $langs->trans('ActionPlanNoDueDateHelp'),
                'icon'  => 'far fa-calendar-times',
                'nb'    => (int) $this->sum('', fn ($indicator) => $indicator['due_date'] === '' && !$indicator['is_done'])
            ],
            [
                'label' => $langs->trans('ActionPlanNoLinkedRisk'),
                'help'  => $langs->trans('ActionPlanNoLinkedRiskHelp'),
                'icon'  => 'fas fa-unlink',
                'nb'    => (int) $this->sum('', fn ($indicator) => $indicator['level'] == 0)
            ]
        ];
        if (digiriskActionPlanCarriesOverLate()) {
            $rows[] = [
                'label' => $langs->trans('ActionPlanCarriedOver'),
                'help'  => $langs->trans('ActionPlanCarriedOverHelp'),
                'icon'  => 'fas fa-history',
                'nb'    => (int) $this->sum('', fn ($indicator) => $indicator['carried'])
            ];
        }

        return $rows;
    }

    /**
     * Get the next due dates of the unfinished corrective actions, the nearest first
     *
     * @return array Rows: day, month, task
     */
    private function getUpcoming(): array
    {
        global $langs;

        $coming = array_values(array_filter($this->indicators, fn ($indicator) => $indicator['is_coming']));
        usort($coming, fn ($indicatorA, $indicatorB) => strcmp($indicatorA['due_date'], $indicatorB['due_date']));

        $rows = [];
        foreach (array_slice($coming, 0, self::UPCOMING_LIST_SIZE) as $indicator) {
            $rows[] = [
                'day'   => substr($indicator['due_date'], 8, 2),
                'month' => rtrim($langs->trans('MonthShort' . substr($indicator['due_date'], 5, 2)), '.'),
                'task'  => $this->getTaskRow($indicator)
            ];
        }

        return ['rows' => $rows, 'nb' => count($coming)];
    }

    /**
     * Get the late corrective actions, the most overdue first
     *
     * @return array Rows (task, late days) and the number of late actions
     */
    private function getLate(): array
    {
        $late = array_values(array_filter($this->indicators, fn ($indicator) => $indicator['is_late']));
        usort($late, fn ($indicatorA, $indicatorB) => $indicatorB['late_days'] <=> $indicatorA['late_days']);

        $rows = [];
        foreach (array_slice($late, 0, self::LATE_LIST_SIZE) as $indicator) {
            $rows[] = ['task' => $this->getTaskRow($indicator), 'days' => $indicator['late_days']];
        }

        return ['rows' => $rows, 'nb' => count($late)];
    }

    /**
     * Return what a list shows of a corrective action
     *
     * @param  array $indicator Indicators of the corrective action
     * @return array            ref, label, url, element, responsible, due date, progress
     */
    private function getTaskRow(array $indicator): array
    {
        $task = $indicator['task'];

        return [
            'ref'         => $task['ref'],
            'label'       => $task['label'],
            'url'         => $task['url'],
            'element'     => $indicator['element'] > 0 ? $task['element_ref'] . ' - ' . $task['element_label'] : '',
            'responsible' => implode(', ', array_map(fn ($responsible) => $responsible['fullname'], $task['responsible'])),
            'dateEnd'     => $task['date_end_fmt'],
            'progress'    => $indicator['progress']
        ];
    }

    /**
     * Get the corrective actions by GP/UT carrying their risk, the most loaded first
     *
     * @return array Rows (label, nb, done, late, budget, url) and the number of GP/UT left out
     */
    private function getElements(): array
    {
        global $langs;

        $elements = [];
        foreach ($this->indicators as $indicator) {
            if ((int) $indicator['task']['element_id'] <= 0) {
                continue;
            }

            // The actions of the GP/UT in the trash share one row, the Kanban filter cannot select them
            $elementID = $indicator['element'];
            if (!isset($elements[$elementID])) {
                $elements[$elementID] = [
                    'label'  => $elementID > 0 ? $indicator['task']['element_ref'] . ' - ' . $indicator['task']['element_label'] : $langs->trans('ActionPlanElementTrashed'),
                    'nb'     => 0,
                    'done'   => 0,
                    'late'   => 0,
                    'budget' => 0,
                    'url'    => $elementID > 0 ? $this->getKanbanUrl(['element' => $elementID, 'element_children' => 0]) : ''
                ];
            }
            $elements[$elementID]['nb']++;
            $elements[$elementID]['done']   += $indicator['is_done'] ? 1 : 0;
            $elements[$elementID]['late']   += $indicator['is_late'] ? 1 : 0;
            $elements[$elementID]['budget'] += $indicator['budget'];
        }

        // Most corrective actions first, the GP/UT reference breaks the ties so the order stays the same
        uasort($elements, fn ($elementA, $elementB) => $elementB['nb'] <=> $elementA['nb'] ?: strnatcmp($elementA['label'], $elementB['label']));

        return [
            'rows'  => array_values(array_slice($elements, 0, self::ELEMENT_LIST_SIZE, true)),
            'nbOut' => max(0, count($elements) - self::ELEMENT_LIST_SIZE)
        ];
    }
}
