<?php
/**
 * \file    core/tpl/actionplan/actionplan_list_kanban.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Kanban board template for action plan tasks
 *
 * Variables expected from calling PHP:
 * - $tasksJson          array  Task data (enriched)
 * - $kanbanThresholds   array  Column threshold config
 * - $langs              Translate
 * - $projectId          int    DU project ID
 */

// Kanban columns definition
$columns = [
    'draft'    => ['label' => $langs->trans('ColumnDraft'),      'icon' => 'fa-pencil-alt', 'color' => '#e05353', 'min' => 0,  'max' => $kanbanThresholds['draft_max']],
    'progress' => ['label' => $langs->trans('ColumnInProgress'), 'icon' => 'fa-spinner',    'color' => '#e9ad4f', 'min' => $kanbanThresholds['draft_max'] + 1, 'max' => $kanbanThresholds['progress_max']],
    'control'  => ['label' => $langs->trans('ColumnInControl'),  'icon' => 'fa-search',     'color' => '#3085d6', 'min' => $kanbanThresholds['progress_max'] + 1, 'max' => $kanbanThresholds['control_max']],
    'done'     => ['label' => $langs->trans('ColumnDone'),       'icon' => 'fa-check',      'color' => '#47e58e', 'min' => 100, 'max' => 100],
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

<div class="kanban-board" data-token="<?= newToken() ?>">
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

                        <!-- Header: Ref + Date + Workload + Budget + Risk -->
                        <div class="kanban-card-header">
                            <a href="<?= $t['url'] ?>" class="kanban-card-ref" target="_blank"><?= dol_escape_htmltag($t['ref']) ?></a>
                            <?php if (!empty($t['date_end_fmt'])) : ?>
                                <span class="kanban-meta-item" title="<?= dol_escape_htmltag($langs->trans('Deadline')) ?>">
                                    <i class="fas fa-calendar-alt"></i> <?= $t['date_end_fmt'] ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($t['planned_workload_fmt'])) : ?>
                                <span class="kanban-meta-item" title="<?= dol_escape_htmltag($langs->trans('PlannedWorkload')) ?>">
                                    <i class="fas fa-clock"></i> <?= $t['planned_workload_fmt'] ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($t['budget_fmt'])) : ?>
                                <span class="kanban-meta-item" title="<?= dol_escape_htmltag($langs->trans('Budget')) ?>">
                                    <i class="fas fa-coins"></i> <?= $t['budget_fmt'] ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($t['risk_nomurl'])) : ?>
                                <span class="kanban-card-risk"><?= $t['risk_nomurl'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Label -->
                        <div class="kanban-card-label"><?= dol_escape_htmltag($t['label']) ?></div>

                        <!-- Contacts row: [Responsible avatar + select] | [contributor count] [add contributor] -->
                        <div class="kanban-card-contacts">
                            <!-- Responsible: avatar(s) + select -->
                            <div class="kanban-responsible-wrapper">
                                <?php if (!empty($t['responsible'])) : ?>
                                    <div class="kanban-responsible-avatars"
                                         title="<?= dol_escape_htmltag(implode(', ', array_map(function($r) { return $r['fullname']; }, $t['responsible']))) ?>">
                                        <?php $firstResp = $t['responsible'][0]; ?>
                                        <?php if (!empty($firstResp['photo'])) : ?>
                                            <img src="<?= $firstResp['photo'] ?>" class="kanban-avatar" alt="<?= dol_escape_htmltag($firstResp['fullname']) ?>">
                                        <?php else : ?>
                                            <span class="kanban-avatar kanban-avatar-initials"><?= strtoupper(mb_substr($firstResp['fullname'], 0, 1)) ?></span>
                                        <?php endif; ?>
                                        <?php if (count($t['responsible']) > 1) : ?>
                                            <span class="kanban-avatar-more">...</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <select class="kanban-responsible-select" data-task-id="<?= $t['id'] ?>">
                                    <option value="0"><?= dol_escape_htmltag($langs->trans('Unassigned')) ?></option>
                                    <?php foreach ($allUsers as $u) : ?>
                                        <option value="<?= $u['id'] ?>"
                                            <?= (!empty($t['responsible']) && $t['responsible'][0]['id'] == $u['id']) ? 'selected' : '' ?>>
                                            <?= dol_escape_htmltag($u['fullname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <span class="kanban-separator">|</span>

                            <!-- Contributor count badge -->
                            <span class="kanban-contributor-count"
                                  title="<?= !empty($t['contributors']) ? dol_escape_htmltag(implode(', ', array_map(function($c) { return $c['fullname']; }, $t['contributors']))) : dol_escape_htmltag($langs->trans('NoContributors')) ?>">
                                <?= count($t['contributors'] ?? []) ?>
                            </span>

                            <!-- Add contributor -->
                            <div class="kanban-add-contributor-wrapper">
                                <button class="kanban-add-contributor-btn" title="<?= dol_escape_htmltag($langs->trans('AddContributor')) ?>">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <select class="kanban-contributor-select" data-task-id="<?= $t['id'] ?>">
                                    <option value=""><?= dol_escape_htmltag($langs->trans('SelectUser')) ?></option>
                                    <?php foreach ($allUsers as $u) : ?>
                                        <option value="<?= $u['id'] ?>"><?= dol_escape_htmltag($u['fullname']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- File count -->
                            <?php if ($t['file_count'] > 0) : ?>
                                <a href="<?= $t['url'] ?>" class="kanban-file-badge" title="<?= dol_escape_htmltag($langs->trans('NumberOfLinkedFiles')) ?>">
                                    <i class="fas fa-paperclip"></i> <?= $t['file_count'] ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Progress bar -->
                        <div class="kanban-card-progress">
                            <div class="kanban-progress-bar">
                                <div class="kanban-progress-fill <?= $t['progress'] == 0 ? 'progress-red' : ($t['progress'] < 100 ? 'progress-yellow' : 'progress-green') ?>"
                                     style="width: <?= $t['progress'] ?>%"></div>
                            </div>
                            <span class="kanban-progress-text"><?= $t['progress'] ?>%</span>
                        </div>

                        <!-- Categories/Tags -->
                        <?php if (!empty($t['categories'])) : ?>
                            <div class="kanban-card-tags">
                                <?php foreach ($t['categories'] as $cat) : ?>
                                    <span class="kanban-tag" style="background: <?= !empty($cat['color']) ? '#' . dol_escape_htmltag($cat['color']) : '#8c8c8c' ?>">
                                        <?= dol_escape_htmltag($cat['label']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
