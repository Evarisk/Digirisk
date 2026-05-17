<?php
/**
 * \file    core/tpl/actionplan/actionplan_list_gantt.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Gantt chart template for action plan tasks
 *
 * Variables expected from calling PHP:
 * - $tasksJson   array  Task data (enriched)
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
            <!-- Sidebar: task list -->
            <div class="gantt-sidebar">
                <div class="gantt-sidebar-header">
                    <span class="gantt-col-task"><?= $langs->trans('Task') ?></span>
                    <span class="gantt-col-resp"><?= $langs->trans('TaskExecutive') ?></span>
                </div>
                <?php foreach ($tasksJson as $t) : ?>
                    <div class="gantt-sidebar-row" data-task-id="<?= $t['id'] ?>">
                        <div class="gantt-col-task">
                            <a href="<?= $t['url'] ?>" target="_blank" class="gantt-task-ref"><?= dol_escape_htmltag($t['ref']) ?></a>
                            <span class="gantt-task-label" title="<?= dol_escape_htmltag($t['label']) ?>"><?= dol_escape_htmltag(dol_trunc($t['label'], 25)) ?></span>
                            <?php if (!empty($t['risk_nomurl'])) : ?>
                                <span class="gantt-risk-badge"><?= $t['risk_nomurl'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="gantt-col-resp">
                            <?php if (!empty($t['responsible'])) : ?>
                                <?php foreach ($t['responsible'] as $resp) : ?>
                                    <span class="gantt-responsible" title="<?= dol_escape_htmltag($resp['fullname']) ?>">
                                        <i class="fas fa-user-tie"></i> <?= dol_escape_htmltag(dol_trunc($resp['fullname'], 15)) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span class="gantt-responsible gantt-responsible-none">N/A</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Timeline: rendered by JS -->
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
