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

    // Inline edit: click on label to edit
    $(document).on('click', '.kanban-card-label', function(e) {
        e.stopPropagation();
        var $label = $(this);

        // Prevent multiple edits
        if ($label.find('textarea').length > 0) {
            return;
        }

        var originalText = $label.text().trim();
        var taskId       = $label.closest('.kanban-card').data('task-id');

        // Replace text with textarea
        var $textarea = $('<textarea class="kanban-inline-edit">' + originalText + '</textarea>');
        $label.empty().append($textarea);
        $textarea.trigger('focus').select();

        // Save on blur
        $textarea.on('blur', function() {
            window.digiriskdolibarr.actionplanKanban.saveLabel(taskId, $textarea.val().trim(), $label, originalText);
        });

        // Save on Enter (without Shift), cancel on Escape
        $textarea.on('keydown', function(ev) {
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                $textarea.trigger('blur');
            } else if (ev.key === 'Escape') {
                $label.text(originalText);
            }
        });
    });

    // Responsible change
    $(document).on('change', '.kanban-responsible-select', function() {
        var $select = $(this);
        var taskId  = $select.data('task-id');
        var userId  = $select.val();
        window.digiriskdolibarr.actionplanKanban.saveResponsible(taskId, userId, $select);
    });

    // Prevent card drag when interacting with select
    $(document).on('mousedown', '.kanban-responsible-select', function(e) {
        e.stopPropagation();
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
    var token          = window.saturne.toolbox.getToken();
    var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
    var $card          = $('.kanban-card[data-task-id="' + taskId + '"]');

    $card.addClass('kanban-card-saving');

    $.ajax({
        url: document.URL + querySeparator + 'action=updateTaskProgress&task_id=' + taskId + '&new_progress=' + newProgress + '&token=' + token,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                $card.addClass('kanban-card-saved');
                setTimeout(function() {
                    $card.removeClass('kanban-card-saved');
                }, 2000);
            } else {
                $card.addClass('kanban-card-error');
                setTimeout(function() {
                    $card.removeClass('kanban-card-error');
                }, 3000);
            }
        },
        error: function() {
            $card.removeClass('kanban-card-saving');
            $card.addClass('kanban-card-error');
            setTimeout(function() {
                $card.removeClass('kanban-card-error');
            }, 3000);
        }
    });
};

/**
 * AJAX save task label (inline edit)
 *
 * @param {number} taskId       Task row ID
 * @param {string} newLabel     New label text
 * @param {jQuery} $labelEl     The label DOM element
 * @param {string} originalText Original text (for rollback)
 */
window.digiriskdolibarr.actionplanKanban.saveLabel = function(taskId, newLabel, $labelEl, originalText) {
    // If unchanged, just restore text
    if (newLabel === originalText || newLabel === '') {
        $labelEl.text(originalText);
        return;
    }

    var token          = window.saturne.toolbox.getToken();
    var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

    $labelEl.text(newLabel).addClass('kanban-label-saving');

    $.ajax({
        url: document.URL + querySeparator + 'action=updateTaskLabel&task_id=' + taskId + '&token=' + token,
        type: 'POST',
        data: { new_label: newLabel },
        dataType: 'json',
        success: function(response) {
            $labelEl.removeClass('kanban-label-saving');
            if (response.success) {
                $labelEl.addClass('kanban-label-saved');
                setTimeout(function() {
                    $labelEl.removeClass('kanban-label-saved');
                }, 1500);
            } else {
                $labelEl.text(originalText);
            }
        },
        error: function() {
            $labelEl.removeClass('kanban-label-saving').text(originalText);
        }
    });
};

/**
 * AJAX save task responsible (TASKEXECUTIVE contact)
 *
 * @param {number} taskId   Task row ID
 * @param {number} userId   User ID (0 = unassign)
 * @param {jQuery} $select  The select element
 */
window.digiriskdolibarr.actionplanKanban.saveResponsible = function(taskId, userId, $select) {
    var token          = window.saturne.toolbox.getToken();
    var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
    var $card          = $select.closest('.kanban-card');

    $card.addClass('kanban-card-saving');

    $.ajax({
        url: document.URL + querySeparator + 'action=updateTaskResponsible&task_id=' + taskId + '&user_id=' + userId + '&token=' + token,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                $card.addClass('kanban-card-saved');
                setTimeout(function() {
                    $card.removeClass('kanban-card-saved');
                }, 2000);
            } else {
                $card.addClass('kanban-card-error');
                setTimeout(function() {
                    $card.removeClass('kanban-card-error');
                }, 3000);
            }
        },
        error: function() {
            $card.removeClass('kanban-card-saving');
            $card.addClass('kanban-card-error');
            setTimeout(function() {
                $card.removeClass('kanban-card-error');
            }, 3000);
        }
    });
};
