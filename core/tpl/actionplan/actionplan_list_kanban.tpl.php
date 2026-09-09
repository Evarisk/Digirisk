<?php
/**
 * \file    core/tpl/actionplan/actionplan_list_kanban.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Kanban board template for action plan tasks
 *
 * Variables expected from calling PHP:
 * - $tasksJson          array  Task data (enriched)
 * - $kanbanColumns      array  Columns from digiriskActionPlanGetKanbanColumns()
 * - $globalProgress     int    Global PAPRIPACT progress (average of all task percentages)
 * - $globalTaskCount    int    Total number of corrective actions
 * - $langs              Translate
 * - $projectId          int    Displayed project ID
 */

// Number of cards rendered live per column; the rest are lazy-loaded by the JS module
$kanbanPageSize = getDolGlobalInt('DIGIRISKDOLIBARR_KANBAN_PAGE_SIZE', 30);

// Sort tasks into columns
$columnTasks = [];
foreach ($kanbanColumns as $kanbanColumn) {
    $columnTasks[$kanbanColumn['key']] = [];
}
foreach ($tasksJson as $t) {
    $taskColumn = digiriskActionPlanGetColumnForProgress($kanbanColumns, (int) $t['progress']);
    if (!empty($taskColumn)) {
        $columnTasks[$taskColumn['key']][] = $t;
    }
}

// Global progress bar colour, taken from the column the average falls in
$globalColumn = digiriskActionPlanGetColumnForProgress($kanbanColumns, $globalProgress);
$globalColor  = !empty($globalColumn) ? $globalColumn['color'] : '#999999';
?>

<div class="actionplan-global-progress">
    <div class="apgp-header">
        <span class="apgp-title"><i class="fas fa-chart-line"></i> <?= $langs->trans('ActionPlanGlobalProgress') ?></span>
        <span class="apgp-percent"><?= $globalProgress ?>%</span>
    </div>
    <div class="apgp-bar">
        <div class="apgp-fill" style="width: <?= $globalProgress ?>%; background: <?= dol_escape_htmltag($globalColor) ?>"></div>
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
    <?php foreach ($kanbanColumns as $colDef) :
        $colKey = $colDef['key'];
    ?>
        <div class="kanban-column" data-column="<?= dol_escape_htmltag($colKey) ?>"
             data-progress-min="<?= (int) $colDef['min'] ?>"
             data-progress-max="<?= (int) $colDef['max'] ?>"
             data-color="<?= dol_escape_htmltag($colDef['color']) ?>">
            <div class="kanban-column-header" style="border-top: 3px solid <?= dol_escape_htmltag($colDef['color']) ?>">
                <span class="kanban-column-icon"><i class="fas <?= dol_escape_htmltag($colDef['icon']) ?>"></i></span>
                <span class="kanban-column-title"><?= dol_escape_htmltag($colDef['label']) ?></span>
                <span class="kanban-column-count"><?= count($columnTasks[$colKey]) ?></span>
            </div>
            <div class="kanban-column-body kanban-sortable" data-column="<?= dol_escape_htmltag($colKey) ?>">
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
                    <button type="button" class="kanban-load-more" data-column="<?= dol_escape_htmltag($colKey) ?>" data-remaining="<?= count($deferredCards) ?>" data-label="<?= dol_escape_htmltag($langs->trans('KanbanLoadMore', '%s')) ?>">
                        <i class="fas fa-chevron-down"></i> <span class="kanban-load-more-text"><?= $langs->trans('KanbanLoadMore', count($deferredCards)) ?></span>
                    </button>
                    <script type="application/json" class="kanban-deferred-data" data-column="<?= dol_escape_htmltag($colKey) ?>"><?= json_encode($deferredCards, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
