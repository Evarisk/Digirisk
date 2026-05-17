<?php
/**
 * \file    core/tpl/actionplan/actionplan_list_kanban.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Kanban board template for action plan tasks
 *
 * Variables expected from calling PHP:
 * - $tasksJson          array  Task data
 * - $kanbanThresholds   array  Column threshold config
 * - $langs              Translate
 * - $projectId          int    DU project ID
 */

// Kanban columns definition
$columns = [
    'draft'    => ['label' => $langs->trans('ColumnDraft'),     'icon' => 'fa-pencil-alt', 'color' => '#e05353', 'min' => 0,  'max' => $kanbanThresholds['draft_max']],
    'progress' => ['label' => $langs->trans('ColumnInProgress'),'icon' => 'fa-spinner',    'color' => '#e9ad4f', 'min' => $kanbanThresholds['draft_max'] + 1, 'max' => $kanbanThresholds['progress_max']],
    'control'  => ['label' => $langs->trans('ColumnInControl'), 'icon' => 'fa-search',     'color' => '#3085d6', 'min' => $kanbanThresholds['progress_max'] + 1, 'max' => $kanbanThresholds['control_max']],
    'done'     => ['label' => $langs->trans('ColumnDone'),      'icon' => 'fa-check',      'color' => '#47e58e', 'min' => 100, 'max' => 100],
];

// Sort tasks into columns
$columnTasks = ['draft' => [], 'progress' => [], 'control' => [], 'done' => []];
foreach ($tasksJson as $t) {
    $p = $t['progress'];
    if ($p <= $kanbanThresholds['draft_max']) {
        $columnTasks['draft'][] = $t;
    } elseif ($p <= $kanbanThresholds['progress_max']) {
        $columnTasks['progress'][] = $t;
    } elseif ($p <= $kanbanThresholds['control_max']) {
        $columnTasks['control'][] = $t;
    } else {
        $columnTasks['done'][] = $t;
    }
}
?>

<div class="actionplan-unsaved-indicator"></div>

<div class="kanban-board" data-token="<?= newToken() ?>" data-url="<?= $_SERVER['PHP_SELF'] ?>">
    <?php foreach ($columns as $colKey => $colDef) : ?>
        <div class="kanban-column" data-column="<?= $colKey ?>"
             data-progress-min="<?= $colDef['min'] ?>"
             data-progress-max="<?= $colDef['max'] ?>">
            <div class="kanban-column-header" style="border-top: 3px solid <?= $colDef['color'] ?>">
                <span class="kanban-column-icon"><i class="fas <?= $colDef['icon'] ?>"></i></span>
                <span class="kanban-column-title"><?= $colDef['label'] ?></span>
                <span class="kanban-column-count"><?= count($columnTasks[$colKey]) ?></span>
            </div>
            <div class="kanban-column-body kanban-sortable" data-column="<?= $colKey ?>">
                <?php if (empty($columnTasks[$colKey])) : ?>
                    <div class="kanban-empty"><?= $langs->trans('NoTasks') ?></div>
                <?php endif; ?>
                <?php foreach ($columnTasks[$colKey] as $t) : ?>
                    <div class="kanban-card" data-task-id="<?= $t['id'] ?>" data-progress="<?= $t['progress'] ?>">
                        <div class="kanban-card-header">
                            <a href="<?= $t['url'] ?>" class="kanban-card-ref" target="_blank"><?= dol_escape_htmltag($t['ref']) ?></a>
                            <?php if (!empty($t['risk_ref'])) : ?>
                                <span class="kanban-card-risk badge badge-warning" title="<?= dol_escape_htmltag($langs->trans('LinkedRisk')) ?>">
                                    <i class="fas fa-exclamation-triangle"></i> <?= dol_escape_htmltag($t['risk_ref']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="kanban-card-label"><?= dol_escape_htmltag(dol_trunc($t['label'], 80)) ?></div>

                        <?php if (!empty($t['categories'])) : ?>
                            <div class="kanban-card-tags">
                                <?php foreach ($t['categories'] as $cat) : ?>
                                    <span class="kanban-tag" style="background: <?= !empty($cat['color']) ? '#' . dol_escape_htmltag($cat['color']) : '#8c8c8c' ?>">
                                        <?= dol_escape_htmltag(dol_trunc($cat['label'], 20)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="kanban-card-meta">
                            <?php if ($t['planned_workload'] > 0) : ?>
                                <span class="kanban-meta-item" title="<?= dol_escape_htmltag($langs->trans('PlannedWorkload')) ?>">
                                    <i class="fas fa-clock"></i> <?= convertSecondToTime($t['planned_workload'], 'allhourmin') ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($t['date_end'])) : ?>
                                <span class="kanban-meta-item" title="<?= dol_escape_htmltag($langs->trans('Deadline')) ?>">
                                    <i class="fas fa-calendar-alt"></i> <?= dol_print_date(dol_stringtotime($t['date_end']), 'day') ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="kanban-card-progress">
                            <div class="kanban-progress-bar">
                                <div class="kanban-progress-fill <?= $t['progress'] == 0 ? 'progress-red' : ($t['progress'] < 100 ? 'progress-yellow' : 'progress-green') ?>"
                                     style="width: <?= $t['progress'] ?>%"></div>
                            </div>
                            <span class="kanban-progress-text"><?= $t['progress'] ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
