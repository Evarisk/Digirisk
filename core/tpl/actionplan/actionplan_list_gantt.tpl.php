<?php
/**
 * \file    core/tpl/actionplan/actionplan_list_gantt.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Gantt chart template for action plan tasks
 *
 * Variables expected from calling PHP:
 * - $tasksJson   array  Task data
 * - $langs       Translate
 * - $projectId   int    DU project ID
 */
?>

<div class="gantt-container" data-tasks='<?= dol_escape_htmltag(json_encode($tasksJson)) ?>'>
    <?php if (empty($tasksJson)) : ?>
        <div class="gantt-empty">
            <i class="fas fa-tasks" style="font-size: 48px; opacity: 0.3;"></i>
            <p><?= $langs->trans('NoTasks') ?></p>
        </div>
    <?php else : ?>
        <div class="gantt-chart">
            <div class="gantt-sidebar">
                <div class="gantt-sidebar-header">
                    <span><?= $langs->trans('Task') ?></span>
                </div>
                <?php foreach ($tasksJson as $t) : ?>
                    <div class="gantt-sidebar-row" data-task-id="<?= $t['id'] ?>">
                        <a href="<?= $t['url'] ?>" target="_blank" class="gantt-task-ref"><?= dol_escape_htmltag($t['ref']) ?></a>
                        <span class="gantt-task-label" title="<?= dol_escape_htmltag($t['label']) ?>"><?= dol_escape_htmltag(dol_trunc($t['label'], 30)) ?></span>
                        <?php if (!empty($t['risk_ref'])) : ?>
                            <span class="gantt-risk-badge"><i class="fas fa-exclamation-triangle"></i> <?= dol_escape_htmltag($t['risk_ref']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($t['categories'])) : ?>
                            <span class="gantt-tags">
                                <?php foreach ($t['categories'] as $cat) : ?>
                                    <span class="gantt-tag" style="background: <?= !empty($cat['color']) ? '#' . dol_escape_htmltag($cat['color']) : '#8c8c8c' ?>"><?= dol_escape_htmltag(dol_trunc($cat['label'], 12)) ?></span>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="gantt-timeline-wrapper">
                <div class="gantt-timeline-header" id="gantt-timeline-header"></div>
                <div class="gantt-timeline-body" id="gantt-timeline-body">
                    <?php foreach ($tasksJson as $t) : ?>
                        <div class="gantt-row" data-task-id="<?= $t['id'] ?>"
                             data-start="<?= $t['date_start'] ?>"
                             data-end="<?= $t['date_end'] ?>"
                             data-progress="<?= $t['progress'] ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
