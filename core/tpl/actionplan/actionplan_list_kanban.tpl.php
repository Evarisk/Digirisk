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
    'draft'    => ['label' => $langs->trans('ColumnDraft'),      'icon' => 'fa-pencil-alt', 'color' => '#999999', 'min' => 0,  'max' => $kanbanThresholds['draft_max']],
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
                            <span class="kanban-meta-item kanban-editable-meta" data-field="planned_workload" data-task-id="<?= $t['id'] ?>"
                                  data-raw="<?= $t['planned_workload'] > 0 ? round($t['planned_workload'] / 3600, 2) : 0 ?>"
                                  title="<?= dol_escape_htmltag($langs->trans('PlannedWorkload')) ?>">
                                <i class="fas fa-clock"></i> <span class="kanban-meta-value"><?= !empty($t['planned_workload_fmt']) ? $t['planned_workload_fmt'] : '-' ?></span>
                            </span>
                            <span class="kanban-meta-item kanban-editable-meta" data-field="budget" data-task-id="<?= $t['id'] ?>"
                                  data-raw="<?= $t['budget'] ?>"
                                  title="<?= dol_escape_htmltag($langs->trans('Budget')) ?>">
                                <i class="fas fa-coins"></i> <span class="kanban-meta-value"><?= !empty($t['budget_fmt']) ? $t['budget_fmt'] : '-' ?></span>
                            </span>
                            <?php if (!empty($t['risk_nomurl'])) : ?>
                                <span class="kanban-card-risk"><?= $t['risk_nomurl'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Label -->
                        <div class="kanban-card-label"><?= dol_escape_htmltag($t['label']) ?></div>

                        <!-- Contacts row: [Resp initial] | [Contrib initials] [count] [👤+] -->
                        <div class="kanban-card-contacts">
                            <!-- Responsible: clickable initial circle -->
                            <?php
                            $respInitial  = '';
                            $respFullname = '';
                            $respId       = 0;
                            if (!empty($t['responsible'])) {
                                $respInitial  = strtoupper(mb_substr($t['responsible'][0]['fullname'], 0, 1));
                                $respFullname = $t['responsible'][0]['fullname'];
                                $respId       = $t['responsible'][0]['id'];
                            }
                            // Color matching progress bar
                            $p = $t['progress'];
                            if ($p <= $kanbanThresholds['draft_max']) {
                                $respColor = '#999999';
                            } elseif ($p <= $kanbanThresholds['progress_max']) {
                                $respColor = '#e9ad4f';
                            } elseif ($p <= $kanbanThresholds['control_max']) {
                                $respColor = '#3085d6';
                            } else {
                                $respColor = '#47e58e';
                            }
                            ?>
                            <div class="kanban-responsible-wrapper" data-task-id="<?= $t['id'] ?>" data-current-user="<?= $respId ?>">
                                <span class="kanban-initial kanban-initial-responsible <?= empty($respInitial) ? 'kanban-initial-empty' : '' ?>"
                                      title="<?= dol_escape_htmltag($respFullname ?: $langs->trans('Unassigned')) ?>"
                                      style="background: <?= $respColor ?>">
                                    <?= $respInitial ?: '?' ?>
                                </span>
                                <!-- Hidden select, shown on click -->
                                <select class="kanban-responsible-select" data-task-id="<?= $t['id'] ?>">
                                    <option value="0"><?= dol_escape_htmltag($langs->trans('Unassigned')) ?></option>
                                    <?php foreach ($allUsers as $u) : ?>
                                        <option value="<?= $u['id'] ?>" data-initial="<?= strtoupper(mb_substr($u['fullname'], 0, 1)) ?>"
                                            <?= ($respId == $u['id']) ? 'selected' : '' ?>>
                                            <?= dol_escape_htmltag($u['fullname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <span class="kanban-separator">|</span>

                            <!-- Contributor initials -->
                            <?php if (!empty($t['contributors'])) : ?>
                                <?php foreach ($t['contributors'] as $contrib) : ?>
                                    <span class="kanban-initial kanban-initial-contributor"
                                          title="<?= dol_escape_htmltag($contrib['fullname']) ?>">
                                        <?= strtoupper(mb_substr($contrib['fullname'], 0, 2)) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>

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
                        </div>

                        <!-- Progress bar -->
                        <div class="kanban-card-progress">
                            <div class="kanban-progress-bar">
                                <?php
                                if ($t['progress'] <= $kanbanThresholds['draft_max']) {
                                    $barClass = 'progress-grey';
                                } elseif ($t['progress'] <= $kanbanThresholds['progress_max']) {
                                    $barClass = 'progress-yellow';
                                } elseif ($t['progress'] <= $kanbanThresholds['control_max']) {
                                    $barClass = 'progress-blue';
                                } else {
                                    $barClass = 'progress-green';
                                }
                                ?>
                                <div class="kanban-progress-fill <?= $barClass ?>"
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
