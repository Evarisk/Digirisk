/**
 * Kanban board module for action plan tasks
 *
 * @memberof digiriskdolibarr
 */
window.digiriskdolibarr.actionplanKanban = {};

/**
 * Init — auto-called by saturne.js on document.ready
 */
window.digiriskdolibarr.actionplanKanban.init = function() {
    window.digiriskdolibarr.actionplanKanban.event();
    window.digiriskdolibarr.actionplanKanban.initSortable();
};

/**
 * Register delegated events
 */
window.digiriskdolibarr.actionplanKanban.event = function() {
    // Hover effect on cards
    $(document).on('mouseenter', '.kanban-card', function() {
        $(this).addClass('kanban-card-hover');
    });
    $(document).on('mouseleave', '.kanban-card', function() {
        $(this).removeClass('kanban-card-hover');
    });
};

/**
 * Initialize jQuery UI sortable on Kanban columns
 */
window.digiriskdolibarr.actionplanKanban.initSortable = function() {
    if ($('.kanban-sortable').length === 0) {
        return;
    }

    $('.kanban-sortable').sortable({
        connectWith: '.kanban-sortable',
        placeholder: 'kanban-card-placeholder',
        tolerance: 'pointer',
        cursor: 'grabbing',
        receive: function(event, ui) {
            var $card    = ui.item;
            var $column  = $(this);
            var taskId   = $card.data('task-id');
            var colKey   = $column.data('column');
            var progressMax = $column.closest('.kanban-column').data('progress-max');
            var progressMin = $column.closest('.kanban-column').data('progress-min');
            var currentProgress = $card.data('progress');

            // Determine new progress based on column
            var newProgress = currentProgress;
            if (colKey === 'draft') {
                newProgress = 0;
            } else if (colKey === 'done') {
                newProgress = 100;
            } else if (currentProgress < progressMin || currentProgress > progressMax) {
                // If task is outside the column range, set to column min
                newProgress = progressMin;
            }

            // Update card data
            $card.data('progress', newProgress);
            $card.find('.kanban-progress-fill').css('width', newProgress + '%');
            $card.find('.kanban-progress-text').text(newProgress + '%');

            // Update progress bar color
            var $fill = $card.find('.kanban-progress-fill');
            $fill.removeClass('progress-red progress-yellow progress-green');
            if (newProgress === 0) {
                $fill.addClass('progress-red');
            } else if (newProgress < 100) {
                $fill.addClass('progress-yellow');
            } else {
                $fill.addClass('progress-green');
            }

            // Remove empty state from target
            $column.find('.kanban-empty').remove();

            // Add empty state to source if needed
            var $source = ui.sender;
            if ($source.children('.kanban-card').length === 0) {
                $source.append('<div class="kanban-empty">Aucune action corrective</div>');
            }

            // Update column counts
            window.digiriskdolibarr.actionplanKanban.updateCounts();

            // AJAX save
            window.digiriskdolibarr.actionplanKanban.saveProgress(taskId, newProgress);
        }
    });
};

/**
 * Update column counters after drag & drop
 */
window.digiriskdolibarr.actionplanKanban.updateCounts = function() {
    $('.kanban-column').each(function() {
        var count = $(this).find('.kanban-card').length;
        $(this).find('.kanban-column-count').text(count);
    });
};

/**
 * AJAX save task progress
 *
 * @param {number} taskId      Task row ID
 * @param {number} newProgress New progress value (0-100)
 */
window.digiriskdolibarr.actionplanKanban.saveProgress = function(taskId, newProgress) {
    var $board = $('.kanban-board');
    var token  = window.saturne.toolbox.getToken();
    var url    = $board.data('url');

    var $indicator = $('.actionplan-unsaved-indicator');
    $indicator.addClass('visible').removeClass('saved');
    $indicator.html('<i class="fas fa-spinner fa-spin"></i> Sauvegarde en cours...');

    $.ajax({
        url: url + '?action=updateTaskProgress&task_id=' + taskId + '&new_progress=' + newProgress + '&token=' + token,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $indicator.addClass('saved');
                $indicator.html('<i class="fas fa-check-circle"></i> Sauvegardé');
                setTimeout(function() {
                    $indicator.removeClass('visible saved');
                }, 2000);
            } else {
                $indicator.html('<i class="fas fa-exclamation-triangle"></i> Erreur');
                setTimeout(function() {
                    $indicator.removeClass('visible');
                }, 4000);
            }
        },
        error: function() {
            $indicator.html('<i class="fas fa-exclamation-triangle"></i> Erreur réseau');
            setTimeout(function() {
                $indicator.removeClass('visible');
            }, 4000);
        }
    });
};
