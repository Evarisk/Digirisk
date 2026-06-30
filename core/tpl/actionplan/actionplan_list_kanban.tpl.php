<?php
/**
 * \file    core/tpl/actionplan/actionplan_list_kanban.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Kanban board template for action plan tasks
 *
 * Variables expected from calling PHP:
 * - $tasksJson          array  Task data (enriched)
 * - $kanbanThresholds   array  Column threshold config
 * - $globalProgress     int    Global PAPRIPACT progress (average of all task percentages)
 * - $globalTaskCount    int    Total number of corrective actions
 * - $langs              Translate
 * - $projectId          int    DU project ID
 */

// Number of cards rendered live per column; the rest are lazy-loaded by the JS module
$kanbanPageSize = getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_PAGE_SIZE', 30);

// Kanban columns definition
$columns = [
    'draft'    => ['label' => $langs->trans('ColumnDraft'),      'icon' => 'fa-pencil-alt', 'color' => '#999999', 'min' => 0,  'max' => $kanbanThresholds['draft_max']],
    'progress' => ['label' => $langs->trans('ColumnInProgress'), 'icon' => 'fa-play',       'color' => '#e9ad4f', 'min' => $kanbanThresholds['draft_max'] + 1, 'max' => $kanbanThresholds['progress_max']],
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

// Global progress bar color, matching the per-card threshold logic
if ($globalProgress <= $kanbanThresholds['draft_max']) {
    $globalBarClass = 'progress-grey';
} elseif ($globalProgress <= $kanbanThresholds['progress_max']) {
    $globalBarClass = 'progress-yellow';
} elseif ($globalProgress <= $kanbanThresholds['control_max']) {
    $globalBarClass = 'progress-blue';
} else {
    $globalBarClass = 'progress-green';
}
?>

<div class="actionplan-global-progress">
    <div class="apgp-header">
        <span class="apgp-title"><i class="fas fa-chart-line"></i> <?= $langs->trans('ActionPlanGlobalProgress') ?></span>
        <span class="apgp-percent"><?= $globalProgress ?>%</span>
    </div>
    <div class="apgp-bar">
        <div class="apgp-fill <?= $globalBarClass ?>" style="width: <?= $globalProgress ?>%"></div>
    </div>
    <div class="apgp-subtitle"><?= $langs->trans('ActionPlanGlobalProgressInfo', $globalTaskCount) ?></div>
</div>

<div class="kanban-settings-wrapper">
    <button type="button" class="kanban-settings-btn" id="kanbanSettingsBtn" title="<?= dol_escape_htmltag($langs->trans('Settings')) ?>">
        <i class="fas fa-cog"></i>
    </button>
    <div class="kanban-settings-popover" id="kanbanSettingsPopover">
        <div class="ksp-row">
            <label><i class="fas fa-arrows-alt-h"></i> <?= $langs->trans('ColumnWidth') ?></label>
            <input type="range" id="kanbanColWidth" min="260" max="500" value="350" step="10">
            <span class="ksp-val" id="kanbanColWidthVal">350px</span>
        </div>
        <div class="ksp-row">
            <label><i class="fas fa-columns"></i> <?= $langs->trans('ColumnGap') ?></label>
            <input type="range" id="kanbanColGap" min="8" max="50" value="26" step="2">
            <span class="ksp-val" id="kanbanColGapVal">26px</span>
        </div>
    </div>
</div>

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
                <?php
                // Lazy-render: only the first $kanbanPageSize cards are emitted live.
                // Remaining cards are pre-rendered (PHP) and serialized as JSON for the
                // JS module to inject on demand — keeps the initial DOM small on large
                // boards (thousands of tasks would otherwise freeze the browser).
                $deferredCards = [];
                foreach ($columnTasks[$colKey] as $cardIndex => $t) {
                    if ($cardIndex < $kanbanPageSize) {
                        require __DIR__ . '/actionplan_kanban_card.tpl.php';
                    } else {
                        ob_start();
                        require __DIR__ . '/actionplan_kanban_card.tpl.php';
                        $deferredCards[] = ob_get_clean();
                    }
                }
                ?>
                <?php if (!empty($deferredCards)) : ?>
                    <button type="button" class="kanban-load-more" data-column="<?= $colKey ?>" data-remaining="<?= count($deferredCards) ?>" data-label="<?= dol_escape_htmltag($langs->trans('KanbanLoadMore', '%s')) ?>">
                        <i class="fas fa-chevron-down"></i> <span class="kanban-load-more-text"><?= $langs->trans('KanbanLoadMore', count($deferredCards)) ?></span>
                    </button>
                    <script type="application/json" class="kanban-deferred-data" data-column="<?= $colKey ?>"><?= json_encode($deferredCards, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
