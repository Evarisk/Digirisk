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
 * \file    core/tpl/actionplan/actionplan_dashboard.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Dashboard of the action plan (PAPRIPACT): key figures, due dates by month, statuses, risk levels, points
 *          to look after, next due dates, late actions and GP/UT of the displayed corrective actions
 *
 * Variables expected from calling PHP:
 * - $actionPlanDashboard array     Blocks from ActionPlanDashboard::load_dashboard()
 * - $conf                Conf
 * - $langs               Translate
 */

// The chart draws in a fixed box stretched over the plot: a month is 100 units wide, the plot 300 units high
$chartHeight = 300;
$months      = $actionPlanDashboard['months'];
$states      = ['done' => 'ActionPlanStateDone', 'todo' => 'ActionPlanStateTodo', 'late' => 'ActionPlanStateLate'];
?>

<div class="actionplan-dashboard">
    <div class="apd-kpis">
        <?php foreach ($actionPlanDashboard['kpis'] as $kpi) : ?>
            <a class="apd-kpi apd-kpi--<?php echo $kpi['tone']; ?>" href="<?php echo dol_escape_htmltag($kpi['url']); ?>">
                <span class="apd-kpi__head">
                    <span class="apd-kpi__icon"><i class="<?php echo $kpi['icon']; ?>"></i></span>
                    <span class="apd-kpi__title"><?php echo $kpi['title']; ?></span>
                </span>
                <span class="apd-kpi__value"><?php echo $kpi['value']; ?></span>
                <span class="apd-kpi__detail"><?php echo $kpi['detail']; ?></span>
                <?php if (!empty($kpi['alert'])) : ?>
                    <span class="apd-kpi__alert"><i class="fas fa-exclamation-triangle"></i><?php echo $kpi['alert']; ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="apd-grid">
        <section class="apd-card apd-card--wide">
            <header class="apd-card__header">
                <h3 class="apd-card__title"><i class="fas fa-chart-bar"></i><?php echo $langs->trans('ActionPlanDueMonths', $actionPlanDashboard['year']); ?></h3>
                <span class="apd-card__figure">
                    <?php echo $langs->trans('ActionPlanTasksCount', $actionPlanDashboard['nbTasks']); ?>
                    <?php if ($months['nbLate'] > 0) : ?>
                        <span class="apd-badge apd-badge--alert"><?php echo $langs->trans('ActionPlanLateBadge', $months['nbLate']); ?></span>
                    <?php endif; ?>
                </span>
            </header>
            <div class="apd-legend">
                <?php foreach ($states as $stateKey => $stateLabel) : ?>
                    <span class="apd-legend__item apd-legend__item--<?php echo $stateKey; ?>"><?php echo $langs->trans($stateLabel); ?></span>
                <?php endforeach; ?>
            </div>
            <div class="apd-chart">
                <div class="apd-chart__ticks">
                    <?php foreach ($months['ticks'] as $tick) : ?>
                        <span class="apd-chart__tick"><?php echo $tick; ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="apd-chart__plot">
                    <svg class="apd-chart__bars" viewBox="0 0 <?php echo count($months['months']) * 100; ?> <?php echo $chartHeight; ?>" preserveAspectRatio="none" role="img" aria-label="<?php echo dol_escape_htmltag($langs->trans('ActionPlanDueMonths', $actionPlanDashboard['year'])); ?>">
                        <?php foreach ($months['months'] as $index => $month) :
                            // Stacked from the bottom: finished, on time, then late on top where it shows
                            $barBottom = $chartHeight;
                            foreach (array_keys($states) as $stateKey) :
                                $barHeight  = round($month[$stateKey] / $months['scale'] * $chartHeight, 1);
                                $barBottom -= $barHeight;
                                if ($barHeight > 0) : ?>
                                    <rect class="apd-chart__bar apd-chart__bar--<?php echo $stateKey; ?>" x="<?php echo $index * 100 + 26; ?>" y="<?php echo $barBottom; ?>" width="48" height="<?php echo $barHeight; ?>"></rect>
                                <?php endif;
                            endforeach; ?>
                            <rect class="apd-chart__hit" x="<?php echo $index * 100; ?>" y="0" width="100" height="<?php echo $chartHeight; ?>"
                                  data-index="<?php echo $index; ?>"
                                  data-title="<?php echo dol_escape_htmltag(ucfirst($month['name'])); ?>"
                                  <?php foreach ($states as $stateKey => $stateLabel) : ?>
                                      data-<?php echo $stateKey; ?>="<?php echo dol_escape_htmltag($langs->transnoentities($stateLabel) . '|' . $month[$stateKey]); ?>"
                                  <?php endforeach; ?>></rect>
                        <?php endforeach; ?>
                    </svg>
                    <div class="apd-chart__tooltip" hidden></div>
                </div>
                <div class="apd-chart__months">
                    <?php foreach ($months['months'] as $month) : ?>
                        <span><?php echo dol_escape_htmltag($month['label']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <details class="apd-chart__table">
                <summary><?php echo $langs->trans('ActionPlanDueMonthsDetail'); ?></summary>
                <div class="div-table-responsive-no-min">
                    <table class="noborder centpercent">
                        <tr class="liste_titre">
                            <td><?php echo $langs->trans('Month'); ?></td>
                            <?php foreach ($states as $stateLabel) : ?>
                                <td class="right"><?php echo $langs->trans($stateLabel); ?></td>
                            <?php endforeach; ?>
                            <td class="right"><?php echo $langs->trans('Total'); ?></td>
                        </tr>
                        <?php foreach ($months['months'] as $month) : ?>
                            <tr class="oddeven">
                                <td><?php echo dol_escape_htmltag(ucfirst($month['name'])); ?></td>
                                <?php foreach (array_keys($states) as $stateKey) : ?>
                                    <td class="right"><?php echo $month[$stateKey]; ?></td>
                                <?php endforeach; ?>
                                <td class="right"><?php echo $month['done'] + $month['todo'] + $month['late']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </details>
            <?php if ($months['nbNoDueDate'] > 0 || $months['nbPrevious'] > 0) : ?>
                <footer class="apd-card__footer">
                    <i class="fas fa-info-circle"></i>
                    <?php
                    $outsideMonths = [];
                    if ($months['nbNoDueDate'] > 0) {
                        $outsideMonths[] = $langs->trans('ActionPlanNoDueDateCount', $months['nbNoDueDate']);
                    }
                    if ($months['nbPrevious'] > 0) {
                        $outsideMonths[] = $langs->trans('ActionPlanPreviousYearCount', $months['nbPrevious']);
                    }
                    echo implode(' · ', $outsideMonths);
                    ?>
                </footer>
            <?php endif; ?>
        </section>

        <section class="apd-card apd-card--narrow">
            <header class="apd-card__header">
                <h3 class="apd-card__title"><i class="fas fa-columns"></i><?php echo $langs->trans('ActionPlanByStatus'); ?></h3>
                <a class="apd-card__more" href="<?php echo dol_escape_htmltag($actionPlanDashboard['kanban']); ?>"><?php echo $langs->trans('ActionPlanSeeKanban'); ?><i class="fas fa-arrow-right"></i></a>
            </header>
            <ul class="apd-bars">
                <?php foreach ($actionPlanDashboard['statuses']['rows'] as $status) : ?>
                    <li class="apd-bars__row">
                        <span class="apd-bars__label"><i class="fas <?php echo dol_escape_htmltag($status['icon']); ?>"></i><?php echo dol_escape_htmltag($status['label']); ?><span class="apd-bars__count"><?php echo $status['nb']; ?></span></span>
                        <span class="apd-bars__value"><?php echo $status['percent']; ?> %</span>
                        <svg class="apd-bars__bar" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true">
                            <rect width="<?php echo $actionPlanDashboard['statuses']['max'] > 0 ? round($status['nb'] / $actionPlanDashboard['statuses']['max'] * 100, 1) : 0; ?>" height="10" fill="<?php echo dol_escape_htmltag($status['color']); ?>"></rect>
                        </svg>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="apd-card apd-card--third">
            <header class="apd-card__header">
                <h3 class="apd-card__title"><i class="fas fa-exclamation-triangle"></i><?php echo $langs->trans('ActionPlanByRiskLevel'); ?></h3>
            </header>
            <?php if ($actionPlanDashboard['levels']['max'] > 0) : ?>
                <div class="apd-legend">
                    <span class="apd-legend__item apd-legend__item--total"><?php echo $langs->trans('ActionPlanNbTasks'); ?></span>
                    <span class="apd-legend__item apd-legend__item--done-level"><?php echo $langs->trans('ActionPlanStateDone'); ?></span>
                </div>
                <ul class="apd-bars">
                    <?php foreach ($actionPlanDashboard['levels']['rows'] as $level) :
                        $barWidth  = $actionPlanDashboard['levels']['max'] > 0 ? round($level['nb'] / $actionPlanDashboard['levels']['max'] * 100, 1) : 0;
                        $doneWidth = $actionPlanDashboard['levels']['max'] > 0 ? round($level['done'] / $actionPlanDashboard['levels']['max'] * 100, 1) : 0; ?>
                        <li>
                            <a class="apd-bars__row<?php echo empty($level['url']) ? ' apd-bars__row--static' : ''; ?>" <?php echo !empty($level['url']) ? 'href="' . dol_escape_htmltag($level['url']) . '"' : ''; ?>>
                                <span class="apd-bars__label"><?php echo dol_escape_htmltag($level['label']); ?><span class="apd-bars__count"><?php echo $level['nb']; ?></span></span>
                                <span class="apd-bars__value"><?php echo $langs->trans('ActionPlanDoneOf', $level['done'], $level['nb']); ?></span>
                                <svg class="apd-bars__bar" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true">
                                    <rect class="apd-bars__total" width="<?php echo $barWidth; ?>" height="10" fill="<?php echo dol_escape_htmltag($level['color']); ?>"></rect>
                                    <rect width="<?php echo $doneWidth; ?>" height="10" fill="<?php echo dol_escape_htmltag($level['color']); ?>"></rect>
                                </svg>
                                <span class="apd-bars__sub">
                                    <?php echo $langs->trans('ActionPlanBudgetValue', price(round($level['budget']), 0, $langs, 1, -1, 0, $conf->currency)); ?>
                                    <?php if ($level['late'] > 0) : ?>
                                        <span class="apd-bars__late"><?php echo $langs->trans('ActionPlanLateBadge', $level['late']); ?></span>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <div class="apd-empty"><i class="fas fa-exclamation-triangle"></i><?php echo $langs->trans('ActionPlanEmpty'); ?></div>
            <?php endif; ?>
        </section>

        <section class="apd-card apd-card--third">
            <header class="apd-card__header">
                <h3 class="apd-card__title"><i class="fas fa-clipboard-check"></i><?php echo $langs->trans('ActionPlanFollowUp'); ?></h3>
            </header>
            <ul class="apd-list">
                <?php foreach ($actionPlanDashboard['followUp'] as $followUp) : ?>
                    <li class="apd-list__item">
                        <span class="apd-list__icon<?php echo $followUp['nb'] > 0 ? ' apd-list__icon--alert' : ''; ?>"><i class="<?php echo $followUp['icon']; ?>"></i></span>
                        <span class="apd-list__main">
                            <span class="apd-list__title"><?php echo $followUp['label']; ?></span>
                            <span class="apd-list__meta"><?php echo $followUp['help']; ?></span>
                        </span>
                        <span class="apd-list__value<?php echo $followUp['nb'] > 0 ? ' apd-list__value--alert' : ''; ?>"><?php echo $followUp['nb']; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="apd-card apd-card--third apd-upcoming">
            <header class="apd-card__header">
                <h3 class="apd-card__title"><i class="far fa-calendar-alt"></i><?php echo $langs->trans('ActionPlanUpcoming'); ?></h3>
                <?php if ($actionPlanDashboard['upcoming']['nb'] > 0) : ?>
                    <span class="apd-upcoming__count"><?php echo $actionPlanDashboard['upcoming']['nb']; ?></span>
                <?php endif; ?>
            </header>
            <?php if (!empty($actionPlanDashboard['upcoming']['rows'])) : ?>
                <ul class="apd-upcoming__list">
                    <?php foreach ($actionPlanDashboard['upcoming']['rows'] as $upcoming) : ?>
                        <li class="apd-upcoming__item">
                            <span class="apd-upcoming__tile">
                                <span class="apd-upcoming__day"><?php echo $upcoming['day']; ?></span>
                                <span class="apd-upcoming__month"><?php echo $upcoming['month']; ?></span>
                            </span>
                            <span class="apd-upcoming__content">
                                <a class="apd-upcoming__label" href="<?php echo dol_escape_htmltag($upcoming['task']['url']); ?>"><?php echo dol_escape_htmltag($upcoming['task']['label']); ?></a>
                                <span class="apd-upcoming__meta">
                                    <span><?php echo dol_escape_htmltag($upcoming['task']['ref']); ?></span>
                                    <?php if (!empty($upcoming['task']['element'])) : ?>
                                        <span><i class="fas fa-sitemap"></i><?php echo dol_escape_htmltag($upcoming['task']['element']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-tasks"></i><?php echo $upcoming['task']['progress']; ?> %</span>
                                </span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <div class="apd-empty"><i class="far fa-calendar-check"></i><?php echo $langs->trans('ActionPlanUpcomingEmpty'); ?></div>
            <?php endif; ?>
        </section>

        <section class="apd-card apd-card--half" id="actionplan-dashboard-late">
            <header class="apd-card__header">
                <h3 class="apd-card__title"><i class="fas fa-hourglass-end"></i><?php echo $langs->trans('ActionPlanLateTasks'); ?></h3>
                <?php if ($actionPlanDashboard['late']['nb'] > 0) : ?>
                    <span class="apd-badge apd-badge--alert"><?php echo $actionPlanDashboard['late']['nb']; ?></span>
                <?php endif; ?>
            </header>
            <?php if (!empty($actionPlanDashboard['late']['rows'])) : ?>
                <ul class="apd-feed">
                    <?php foreach ($actionPlanDashboard['late']['rows'] as $late) : ?>
                        <li class="apd-feed__item">
                            <span class="apd-feed__icon"><i class="fas fa-hourglass-end"></i></span>
                            <span class="apd-feed__main">
                                <a class="apd-feed__label" href="<?php echo dol_escape_htmltag($late['task']['url']); ?>"><?php echo dol_escape_htmltag($late['task']['label']); ?></a>
                                <span class="apd-feed__meta">
                                    <span><?php echo dol_escape_htmltag($late['task']['ref']); ?></span>
                                    <?php if (!empty($late['task']['element'])) : ?>
                                        <span><i class="fas fa-sitemap"></i><?php echo dol_escape_htmltag($late['task']['element']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-user"></i><?php echo !empty($late['task']['responsible']) ? dol_escape_htmltag($late['task']['responsible']) : $langs->trans('ActionPlanNoManager'); ?></span>
                                </span>
                            </span>
                            <span class="apd-feed__aside">
                                <span class="apd-badge apd-badge--alert"><?php echo $langs->trans('ActionPlanLateDaysValue', $late['days']); ?></span>
                                <span class="apd-feed__date"><?php echo dol_escape_htmltag($late['task']['dateEnd']); ?> · <?php echo $late['task']['progress']; ?> %</span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($actionPlanDashboard['late']['nb'] > count($actionPlanDashboard['late']['rows'])) : ?>
                    <footer class="apd-card__footer">
                        <?php echo $langs->trans('ActionPlanLateTasksMore', $actionPlanDashboard['late']['nb'] - count($actionPlanDashboard['late']['rows'])); ?>
                    </footer>
                <?php endif; ?>
            <?php else : ?>
                <div class="apd-empty"><i class="fas fa-check"></i><?php echo $langs->trans('ActionPlanLateTasksEmpty'); ?></div>
            <?php endif; ?>
        </section>

        <section class="apd-card apd-card--half">
            <header class="apd-card__header">
                <h3 class="apd-card__title"><i class="fas fa-sitemap"></i><?php echo $langs->trans('ActionPlanByElement'); ?></h3>
            </header>
            <?php if (!empty($actionPlanDashboard['elements']['rows'])) : ?>
                <div class="div-table-responsive-no-min">
                    <table class="apd-table">
                        <thead>
                            <tr>
                                <th><?php echo $langs->trans('ActionPlanElement'); ?></th>
                                <th class="apd-table__nb right"><?php echo $langs->trans('ActionPlanTasksShort'); ?></th>
                                <th class="apd-table__nb right"><?php echo $langs->trans('ActionPlanStateDone'); ?></th>
                                <th class="apd-table__nb right"><?php echo $langs->trans('ActionPlanStateLate'); ?></th>
                                <th class="apd-table__budget right"><?php echo $langs->trans('Budget'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actionPlanDashboard['elements']['rows'] as $element) : ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($element['url'])) : ?>
                                            <a href="<?php echo dol_escape_htmltag($element['url']); ?>"><?php echo dol_escape_htmltag($element['label']); ?></a>
                                        <?php else : ?>
                                            <span class="opacitymedium"><?php echo dol_escape_htmltag($element['label']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="right"><?php echo $element['nb']; ?></td>
                                    <td class="right"><?php echo $element['done']; ?></td>
                                    <td class="right<?php echo $element['late'] > 0 ? ' apd-table__alert' : ''; ?>"><?php echo $element['late']; ?></td>
                                    <td class="right nowraponall"><?php echo price(round($element['budget']), 0, $langs, 1, -1, 0, $conf->currency); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($actionPlanDashboard['elements']['nbOut'] > 0) : ?>
                    <footer class="apd-card__footer">
                        <?php echo $langs->trans('ActionPlanByElementMore', $actionPlanDashboard['elements']['nbOut']); ?>
                    </footer>
                <?php endif; ?>
            <?php else : ?>
                <div class="apd-empty"><i class="fas fa-sitemap"></i><?php echo $langs->trans('ActionPlanEmpty'); ?></div>
            <?php endif; ?>
        </section>
    </div>
</div>
