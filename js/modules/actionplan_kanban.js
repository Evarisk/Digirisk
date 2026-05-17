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

    // Responsible: click initial → show select
    $(document).on('click', '.kanban-initial-responsible', function(e) {
        e.stopPropagation();
        var $wrapper = $(this).closest('.kanban-responsible-wrapper');
        var $select  = $wrapper.find('.kanban-responsible-select');
        $(this).hide();
        $select.addClass('visible').trigger('focus');
    });

    // Responsible: change → save + hide select → update initial
    $(document).on('change', '.kanban-responsible-select', function() {
        var $select  = $(this);
        var $wrapper = $select.closest('.kanban-responsible-wrapper');
        var $initial = $wrapper.find('.kanban-initial-responsible');
        var taskId   = $select.data('task-id');
        var userId   = $select.val();
        var selText  = $select.find('option:selected').text().trim();
        var newInit  = userId > 0 ? selText.charAt(0).toUpperCase() : '?';

        // Hide select, update initial, show initial
        $select.removeClass('visible');
        $initial.text(newInit)
                .attr('title', userId > 0 ? selText : '')
                .toggleClass('kanban-initial-empty', userId == 0)
                .show();

        window.digiriskdolibarr.actionplanKanban.saveResponsible(taskId, userId, $select);
    });

    // Responsible: blur select → hide if no change
    $(document).on('blur', '.kanban-responsible-select', function() {
        var $select = $(this);
        var $initial = $select.siblings('.kanban-initial-responsible');
        setTimeout(function() {
            $select.removeClass('visible');
            $initial.show();
        }, 200);
    });

    // Prevent card drag when interacting with selects
    $(document).on('mousedown', '.kanban-responsible-select, .kanban-contributor-select, .kanban-add-contributor-btn, .kanban-initial-responsible', function(e) {
        e.stopPropagation();
    });

    // Add contributor: toggle select visibility
    $(document).on('click', '.kanban-add-contributor-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $select = $(this).siblings('.kanban-contributor-select');
        $select.toggleClass('visible');
        if ($select.hasClass('visible')) {
            $select.trigger('focus');
        }
    });

    // Contributor select change: AJAX save
    $(document).on('change', '.kanban-contributor-select', function() {
        var $select = $(this);
        var taskId  = $select.data('task-id');
        var userId  = $select.val();
        if (userId) {
            window.digiriskdolibarr.actionplanKanban.addContributor(taskId, userId, $select);
        }
        $select.removeClass('visible');
        $select.val(''); // Reset
    });

    // Hide contributor select on blur
    $(document).on('blur', '.kanban-contributor-select', function() {
        var $select = $(this);
        setTimeout(function() {
            $select.removeClass('visible');
        }, 200);
    });

    // Progress bar: draggable slider
    $(document).on('mousedown', '.kanban-progress-bar', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $bar     = $(this);
        var $card    = $bar.closest('.kanban-card');
        var $fill    = $bar.find('.kanban-progress-fill');
        var $text    = $bar.siblings('.kanban-progress-text');
        var taskId   = $card.data('task-id');
        var barWidth = $bar.width();

        // Calculate % from mouse position
        function calcPercent(pageX) {
            var offset = $bar.offset().left;
            var pct = Math.round(((pageX - offset) / barWidth) * 100);
            return Math.max(0, Math.min(100, pct));
        }

        // Visual update
        function updateVisual(pct) {
            $fill.css('width', pct + '%');
            $fill.removeClass('progress-red progress-yellow progress-green');
            if (pct === 0) {
                $fill.addClass('progress-red');
            } else if (pct < 100) {
                $fill.addClass('progress-yellow');
            } else {
                $fill.addClass('progress-green');
            }
            $text.text(pct + '%');
        }

        // Initial click position
        var pct = calcPercent(e.pageX);
        updateVisual(pct);
        $bar.addClass('kanban-bar-dragging');

        $(document).on('mousemove.progressDrag', function(ev) {
            pct = calcPercent(ev.pageX);
            updateVisual(pct);
        });

        $(document).on('mouseup.progressDrag', function() {
            $(document).off('mousemove.progressDrag mouseup.progressDrag');
            $bar.removeClass('kanban-bar-dragging');
            $card.data('progress', pct);

            // Move card to correct column
            var targetColumn = null;
            $('.kanban-column').each(function() {
                var min = parseInt($(this).data('progress-min'));
                var max = parseInt($(this).data('progress-max'));
                if (pct >= min && pct <= max) {
                    targetColumn = $(this);
                    return false;
                }
            });

            if (targetColumn) {
                var $currentCol = $card.closest('.kanban-column');
                if (targetColumn[0] !== $currentCol[0]) {
                    $card.detach();
                    targetColumn.find('.kanban-column-body .kanban-empty').remove();
                    targetColumn.find('.kanban-column-body').append($card);
                    var $sourceBody = $currentCol.find('.kanban-column-body');
                    if ($sourceBody.children('.kanban-card').length === 0) {
                        $sourceBody.append('<div class="kanban-empty">Aucune action corrective</div>');
                    }
                    window.digiriskdolibarr.actionplanKanban.updateCounts();
                }
            }

            // AJAX save
            window.digiriskdolibarr.actionplanKanban.saveProgress(taskId, pct);
        });
    });

    // Prevent card drag when interacting with progress bar
    $(document).on('mousedown', '.kanban-card-progress', function(e) {
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
 * Update progress bar UI, move card to correct column, and save via AJAX
 *
 * @param {jQuery} $card    The card element
 * @param {number} taskId   Task row ID
 * @param {number} val      New progress value (0-100)
 * @param {string} origHtml Original progress bar HTML (fallback)
 */
window.digiriskdolibarr.actionplanKanban.updateProgress = function($card, taskId, val, origHtml) {
    // Restore progress bar
    var colorClass = val === 0 ? 'progress-red' : (val < 100 ? 'progress-yellow' : 'progress-green');
    var barHtml = '<div class="kanban-progress-bar">' +
                  '<div class="kanban-progress-fill ' + colorClass + '" style="width:' + val + '%"></div>' +
                  '</div>' +
                  '<span class="kanban-progress-text">' + val + '%</span>';
    $card.find('.kanban-card-progress').html(barHtml);
    $card.data('progress', val);

    // Find target column based on thresholds
    var targetColumn = null;
    $('.kanban-column').each(function() {
        var $col = $(this);
        var min  = parseInt($col.data('progress-min'));
        var max  = parseInt($col.data('progress-max'));
        if (val >= min && val <= max) {
            targetColumn = $col;
            return false;
        }
    });

    // Move card to target column if needed
    if (targetColumn) {
        var $currentCol = $card.closest('.kanban-column');
        if (targetColumn[0] !== $currentCol[0]) {
            $card.detach();
            targetColumn.find('.kanban-column-body .kanban-empty').remove();
            targetColumn.find('.kanban-column-body').append($card);
            var $sourceBody = $currentCol.find('.kanban-column-body');
            if ($sourceBody.children('.kanban-card').length === 0) {
                $sourceBody.append('<div class="kanban-empty">Aucune action corrective</div>');
            }
            window.digiriskdolibarr.actionplanKanban.updateCounts();
        }
    }

    // AJAX save
    window.digiriskdolibarr.actionplanKanban.saveProgress(taskId, val);
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

/**
 * AJAX add contributor (TASKCONTRIBUTOR contact)
 *
 * @param {number} taskId   Task row ID
 * @param {number} userId   User ID
 * @param {jQuery} $select  The select element
 */
window.digiriskdolibarr.actionplanKanban.addContributor = function(taskId, userId, $select) {
    var token          = window.saturne.toolbox.getToken();
    var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
    var $card          = $select.closest('.kanban-card');

    $card.addClass('kanban-card-saving');

    $.ajax({
        url: document.URL + querySeparator + 'action=addTaskContributor&task_id=' + taskId + '&user_id=' + userId + '&token=' + token,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                // Update count badge and tooltip
                $card.find('.kanban-contributor-count').text(response.count).attr('title', response.names);
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
