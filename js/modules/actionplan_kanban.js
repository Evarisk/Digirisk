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
    // The ticket action-card picker reuses the generic .kanban-board / .kanban-*
    // markup but has its own module (ticketPickerKanban). Bail out here so this
    // module's global, unscoped handlers don't double-bind onto the picker
    // (e.g. the "+ tag" toggle would fire twice and cancel itself out).
    if (!$('.kanban-board').length || $('.tac-picker-kanban-board').length) {
        return;
    }
    window.digiriskdolibarr.actionplanKanban.event();
    window.digiriskdolibarr.actionplanKanban.initLazyLoad();
    window.digiriskdolibarr.actionplanKanban.initSortable();
    window.digiriskdolibarr.actionplanKanban.initSettings();
};

/**
 * Pre-rendered cards awaiting injection, keyed by column. Populated by initLazyLoad.
 */
window.digiriskdolibarr.actionplanKanban.deferredCards = {};

/**
 * Number of cards rendered live per "load more" click (matches server page size default)
 */
window.digiriskdolibarr.actionplanKanban.chunkSize = 30;

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
        var newInit  = userId > 0 ? ($select.find('option:selected').data('initial') || selText.substring(0, 2).toUpperCase()) : '?';

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
    $(document).on('mousedown', '.kanban-responsible-select, .kanban-contributor-dropdown, .kanban-add-contributor-btn, .kanban-initial-responsible, .kanban-contributor-search, .kanban-contributor-option', function(e) {
        e.stopPropagation();
    });

    // Add contributor: toggle dropdown visibility
    $(document).on('click', '.kanban-add-contributor-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Close all other dropdowns first
        $('.kanban-contributor-dropdown.visible').not($(this).siblings('.kanban-contributor-dropdown')).each(function() {
            $(this).removeClass('visible');
            $(this).closest('.kanban-card').removeClass('kanban-card-dropdown-open');
        });
        var $dropdown = $(this).siblings('.kanban-contributor-dropdown');
        var $card = $(this).closest('.kanban-card');
        $dropdown.toggleClass('visible');
        if ($dropdown.hasClass('visible')) {
            $card.addClass('kanban-card-dropdown-open');
            var $search = $dropdown.find('.kanban-contributor-search');
            $search.val('').trigger('input');
            $search.trigger('focus');
        } else {
            $card.removeClass('kanban-card-dropdown-open');
        }
    });

    // Contributor search: filter options
    $(document).on('input', '.kanban-contributor-search', function() {
        var query = $(this).val().toLowerCase();
        $(this).closest('.kanban-contributor-dropdown').find('.kanban-contributor-option').each(function() {
            var text = $(this).data('search') || '';
            if (query === '' || text.indexOf(query) !== -1) {
                $(this).removeClass('hidden');
            } else {
                $(this).addClass('hidden');
            }
        });
    });

    // Contributor option click: AJAX add
    $(document).on('click', '.kanban-contributor-option', function(e) {
        e.stopPropagation();
        var $opt = $(this);
        if ($opt.hasClass('assigned')) return;

        var $dropdown = $opt.closest('.kanban-contributor-dropdown');
        var taskId = $dropdown.data('task-id');
        var userId = $opt.data('value');

        $dropdown.removeClass('visible');
        $dropdown.closest('.kanban-card').removeClass('kanban-card-dropdown-open');
        window.digiriskdolibarr.actionplanKanban.addContributor(taskId, userId, $dropdown);

        // Mark as assigned immediately
        $opt.addClass('assigned');
        $opt.append('<i class="fas fa-check" style="margin-left:4px;font-size:9px;color:#28a745"></i>');
    });

    // Close dropdown on outside click
    $(document).on('click', function() {
        $('.kanban-contributor-dropdown.visible').each(function() {
            $(this).removeClass('visible');
            $(this).closest('.kanban-card').removeClass('kanban-card-dropdown-open');
        });
    });
    $(document).on('click', '.kanban-contributor-dropdown', function(e) {
        e.stopPropagation();
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

        // Visual update – use column thresholds for color
        function updateVisual(pct) {
            $fill.css('width', pct + '%');
            $fill.removeClass('progress-grey progress-yellow progress-blue progress-green');

            // Find which column this pct belongs to
            var colorClass = 'progress-grey';
            $('.kanban-column').each(function() {
                var min = parseInt($(this).data('progress-min'));
                var max = parseInt($(this).data('progress-max'));
                if (pct >= min && pct <= max) {
                    var col = $(this).data('column');
                    if (col === 'draft')    colorClass = 'progress-grey';
                    if (col === 'progress') colorClass = 'progress-yellow';
                    if (col === 'control')  colorClass = 'progress-blue';
                    if (col === 'done')     colorClass = 'progress-green';
                    return false;
                }
            });
            $fill.addClass(colorClass);
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

    // Editable meta fields (workload / budget): click to edit
    $(document).on('click', '.kanban-editable-meta', function(e) {
        e.stopPropagation();
        var $meta = $(this);
        if ($meta.find('input').length > 0) return;

        var $value   = $meta.find('.kanban-meta-value');
        var field    = $meta.data('field');
        var taskId   = $meta.data('task-id');
        var rawVal   = $meta.data('raw') || 0;
        var origText = $value.text();

        var $input;
        if (field === 'planned_workload') {
            // rawVal is in hours (decimal), convert to HH:MM for display
            var h = Math.floor(rawVal);
            var m = Math.round((rawVal - h) * 60);
            var displayVal = rawVal > 0 ? (h + ':' + (m < 10 ? '0' : '') + m) : '';
            $input = $('<input type="text" class="kanban-meta-input" placeholder="0:00">');
            $input.val(displayVal);
        } else {
            $input = $('<input type="number" class="kanban-meta-input" step="any" min="0" placeholder="0.00">');
            $input.val(rawVal > 0 ? rawVal : '');
        }
        $value.replaceWith($input);
        $input.trigger('focus').select();

        function save() {
            var val = $input.val();
            var $card = $meta.closest('.kanban-card');
            $card.addClass('kanban-card-saving');

            var token = window.saturne.toolbox.getToken();
            var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

            $.ajax({
                url: document.URL + sep + 'action=updateTaskMeta&task_id=' + taskId + '&field=' + field + '&value=' + encodeURIComponent(val) + '&token=' + token,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    $card.removeClass('kanban-card-saving');
                    var $newValue = $('<span class="kanban-meta-value"></span>');
                    if (response.success) {
                        $newValue.text(response.formatted);
                        $meta.data('raw', response.raw);
                        $card.addClass('kanban-card-saved');
                        setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
                    } else {
                        $newValue.text(origText);
                        $card.addClass('kanban-card-error');
                        setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
                    }
                    $input.replaceWith($newValue);
                },
                error: function() {
                    $card.removeClass('kanban-card-saving');
                    var $newValue = $('<span class="kanban-meta-value"></span>').text(origText);
                    $input.replaceWith($newValue);
                    $card.addClass('kanban-card-error');
                    setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
                }
            });
        }

        $input.on('blur', save);
        $input.on('keydown', function(ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                $input.off('blur');
                save();
            } else if (ev.key === 'Escape') {
                $input.off('blur');
                var $newValue = $('<span class="kanban-meta-value"></span>').text(origText);
                $input.replaceWith($newValue);
            }
        });
    });

    // Prevent drag on editable meta
    $(document).on('mousedown', '.kanban-editable-meta', function(e) {
        e.stopPropagation();
    });

    // Editable dates: click to edit with native date picker
    $(document).on('click', '.kanban-editable-date', function(e) {
        e.stopPropagation();
        var $date = $(this);
        if ($date.find('input').length > 0) return;

        var $value   = $date.find('.kanban-date-value');
        var field    = $date.data('field');
        var taskId   = $date.data('task-id');
        var rawVal   = $date.data('raw') || '';
        var origText = $value.text();

        var $input = $('<input type="date" class="kanban-date-input">');
        $input.val(rawVal);
        $value.replaceWith($input);
        $input.trigger('focus');

        function saveDate() {
            var val = $input.val();
            var $card = $date.closest('.kanban-card');

            // Cross-validate start vs end date
            if (val) {
                var $row = $date.closest('.kanban-dates-row');
                var $otherDate = $row.find('.kanban-editable-date').not($date);
                var otherRaw = $otherDate.data('raw') || '';
                if (otherRaw) {
                    var isInvalid = false;
                    if (field === 'date_end' && val < otherRaw) {
                        isInvalid = true;
                    } else if (field === 'date_start' && val > otherRaw) {
                        isInvalid = true;
                    }
                    if (isInvalid) {
                        // Show discreet warning toast on card
                        var $toast = $('<div class="kanban-date-warning"><i class="fas fa-exclamation-triangle"></i> Date fin antérieure au début</div>');
                        $card.append($toast);
                        setTimeout(function() { $toast.addClass('visible'); }, 10);
                        setTimeout(function() {
                            $toast.removeClass('visible');
                            setTimeout(function() { $toast.remove(); }, 300);
                        }, 3000);

                        // Restore original text
                        var $newValue = $('<span class="kanban-date-value"></span>').text(origText);
                        $input.replaceWith($newValue);
                        return;
                    }
                }
            }

            $card.addClass('kanban-card-saving');

            var token = window.saturne.toolbox.getToken();
            var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

            $.ajax({
                url: document.URL + sep + 'action=updateTaskDate&task_id=' + taskId + '&field=' + field + '&value=' + encodeURIComponent(val) + '&token=' + token,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    $card.removeClass('kanban-card-saving');
                    var $newValue = $('<span class="kanban-date-value"></span>');
                    if (response.success) {
                        $newValue.text(response.formatted);
                        $date.data('raw', response.raw);
                        $card.addClass('kanban-card-saved');
                        setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
                    } else {
                        $newValue.text(origText);
                        $card.addClass('kanban-card-error');
                        setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
                    }
                    $input.replaceWith($newValue);
                },
                error: function() {
                    $card.removeClass('kanban-card-saving');
                    var $newValue = $('<span class="kanban-date-value"></span>').text(origText);
                    $input.replaceWith($newValue);
                    $card.addClass('kanban-card-error');
                    setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
                }
            });
        }

        $input.on('change', function() {
            $input.off('blur');
            saveDate();
        });
        $input.on('blur', saveDate);
        $input.on('keydown', function(ev) {
            if (ev.key === 'Escape') {
                $input.off('blur').off('change');
                var $newValue = $('<span class="kanban-date-value"></span>').text(origText);
                $input.replaceWith($newValue);
            }
        });
    });

    // Prevent drag on editable dates
    $(document).on('mousedown', '.kanban-editable-date', function(e) {
        e.stopPropagation();
    });

    // Tag: toggle dropdown
    $(document).on('click', '.kanban-add-tag-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Close all other tag dropdowns
        $('.kanban-tag-dropdown.visible').not($(this).siblings('.kanban-tag-dropdown')).each(function() {
            $(this).removeClass('visible');
            $(this).closest('.kanban-card').removeClass('kanban-card-dropdown-open');
        });
        var $dropdown = $(this).siblings('.kanban-tag-dropdown');
        var $card = $(this).closest('.kanban-card');
        $dropdown.toggleClass('visible');
        if ($dropdown.hasClass('visible')) {
            $card.addClass('kanban-card-dropdown-open');
        } else {
            $card.removeClass('kanban-card-dropdown-open');
        }
    });

    // Tag: option click - add category
    $(document).on('click', '.kanban-tag-option', function(e) {
        e.stopPropagation();
        var $opt = $(this);
        if ($opt.hasClass('assigned')) return;

        var $dropdown = $opt.closest('.kanban-tag-dropdown');
        var taskId = $dropdown.data('task-id');
        var catId  = $opt.data('value');
        var $card  = $opt.closest('.kanban-card');
        var $tagsRow = $card.find('.kanban-card-tags');

        $dropdown.removeClass('visible');
        $card.removeClass('kanban-card-dropdown-open');
        $card.addClass('kanban-card-saving');

        var token = window.saturne.toolbox.getToken();
        var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

        $.ajax({
            url: document.URL + sep + 'action=addTaskCategory&task_id=' + taskId + '&cat_id=' + catId + '&token=' + token,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $card.removeClass('kanban-card-saving');
                if (response.success) {
                    var bgColor = response.color ? '#' + response.color : '#8c8c8c';
                    var $tag = $('<span class="kanban-tag" data-cat-id="' + response.id + '" style="background:' + bgColor + '">' + response.label + '<span class="kanban-tag-remove" title="Supprimer">&times;</span></span>');
                    $tagsRow.find('.kanban-tag-dropdown-wrapper').before($tag);
                    // Mark as assigned
                    $opt.addClass('assigned');
                    $opt.append('<i class="fas fa-check" style="margin-left:auto;font-size:9px;color:#28a745"></i>');
                    $card.addClass('kanban-card-saved');
                    setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
                } else {
                    $card.addClass('kanban-card-error');
                    setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
                }
            },
            error: function() {
                $card.removeClass('kanban-card-saving');
                $card.addClass('kanban-card-error');
                setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
            }
        });
    });

    // Tag: close dropdown on outside click
    $(document).on('click', function() {
        $('.kanban-tag-dropdown.visible').each(function() {
            $(this).removeClass('visible');
            $(this).closest('.kanban-card').removeClass('kanban-card-dropdown-open');
        });
    });
    $(document).on('click', '.kanban-tag-dropdown', function(e) {
        e.stopPropagation();
    });

    // Tag: remove category from task
    $(document).on('click', '.kanban-tag-remove', function(e) {
        e.stopPropagation();
        var $btn  = $(this);
        var $tag  = $btn.closest('.kanban-tag');
        var catId = $tag.data('cat-id');
        var $card = $tag.closest('.kanban-card');
        var taskId = $card.data('task-id');

        $tag.css('opacity', '0.4');
        var token = window.saturne.toolbox.getToken();
        var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

        $.ajax({
            url: document.URL + sep + 'action=removeTaskCategory&task_id=' + taskId + '&cat_id=' + catId + '&token=' + token,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $tag.slideUp(150, function() { $(this).remove(); });
                    // Unmark from dropdown
                    $card.find('.kanban-tag-option[data-value="' + catId + '"]').removeClass('assigned').find('.fa-check').remove();
                    $card.addClass('kanban-card-saved');
                    setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
                } else {
                    $tag.css('opacity', '1');
                }
            },
            error: function() {
                $tag.css('opacity', '1');
            }
        });
    });

    // Prevent drag on tag elements
    $(document).on('mousedown', '.kanban-tag, .kanban-tag-remove, .kanban-add-tag-btn, .kanban-tag-dropdown, .kanban-tag-option', function(e) {
        e.stopPropagation();
    });

    // Remove contributor: click on × button
    $(document).on('click', '.kanban-remove-contact', function(e) {
        e.stopPropagation();
        e.preventDefault();

        var $btn     = $(this);
        var $wrapper = $btn.closest('.kanban-initial-wrapper');
        var taskId   = $wrapper.data('task-id');
        var userId   = $wrapper.data('user-id');
        var $card    = $wrapper.closest('.kanban-card');

        $wrapper.css('opacity', '0.4');

        var token = window.saturne.toolbox.getToken();
        var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

        $.ajax({
            url: document.URL + sep + 'action=removeTaskContributor&task_id=' + taskId + '&user_id=' + userId + '&token=' + token,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $wrapper.slideUp(150, function() { $(this).remove(); });
                    $card.addClass('kanban-card-saved');
                    setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
                } else {
                    $wrapper.css('opacity', '1');
                    $card.addClass('kanban-card-error');
                    setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
                }
            },
            error: function() {
                $wrapper.css('opacity', '1');
                $card.addClass('kanban-card-error');
                setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
            }
        });
    });

    // Prevent drag on remove buttons
    $(document).on('mousedown', '.kanban-remove-contact, .kanban-initial-wrapper', function(e) {
        e.stopPropagation();
    });

    // Lazy-load: "load more" button injects the next chunk of deferred cards
    $(document).on('click', '.kanban-load-more', function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.digiriskdolibarr.actionplanKanban.loadMore($(this).data('column'));
    });

    // Prevent card drag when clicking the load-more button
    $(document).on('mousedown', '.kanban-load-more', function(e) {
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
        items: '> .kanban-card',
        placeholder: 'kanban-card-placeholder',
        tolerance: 'pointer',
        cursor: 'grabbing',
        cancel: '.kanban-progress-bar, .kanban-card-progress, .kanban-responsible-select, .kanban-contributor-dropdown, .kanban-add-contributor-btn, .kanban-initial-responsible, .kanban-contributor-search, .kanban-contributor-option, .kanban-card-label, .kanban-inline-edit, .kanban-editable-meta, .kanban-remove-contact, .kanban-initial-wrapper, .kanban-editable-date, .kanban-tag, .kanban-tag-remove, .kanban-add-tag-btn, .kanban-tag-dropdown, .kanban-tag-option',
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
            $fill.removeClass('progress-grey progress-yellow progress-blue progress-green');
            if (colKey === 'draft')         $fill.addClass('progress-grey');
            else if (colKey === 'progress') $fill.addClass('progress-yellow');
            else if (colKey === 'control')  $fill.addClass('progress-blue');
            else                            $fill.addClass('progress-green');

            // Remove empty state from target
            $column.find('.kanban-empty').remove();

            // Keep the "load more" button anchored at the bottom of the column
            $column.append($column.children('.kanban-load-more'));

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
 * Read the deferred (lazy-loaded) card payloads emitted per column by the TPL
 * and stash them in memory so loadMore() can inject them on demand.
 */
window.digiriskdolibarr.actionplanKanban.initLazyLoad = function() {
    window.digiriskdolibarr.actionplanKanban.deferredCards = {};
    $('.kanban-deferred-data').each(function() {
        var colKey = $(this).data('column');
        try {
            window.digiriskdolibarr.actionplanKanban.deferredCards[colKey] = JSON.parse($(this).text()) || [];
        } catch (err) {
            window.digiriskdolibarr.actionplanKanban.deferredCards[colKey] = [];
        }
        // Free the inline payload from the DOM once parsed
        $(this).remove();
    });
};

/**
 * Inject the next chunk of deferred cards into a column.
 *
 * @param {string} colKey Column key (draft / progress / control / done)
 */
window.digiriskdolibarr.actionplanKanban.loadMore = function(colKey) {
    var remaining = window.digiriskdolibarr.actionplanKanban.deferredCards[colKey] || [];
    if (remaining.length === 0) {
        return;
    }

    var $column = $('.kanban-column[data-column="' + colKey + '"]');
    var $body   = $column.find('.kanban-column-body');
    var $btn    = $body.find('.kanban-load-more');

    var chunk = remaining.splice(0, window.digiriskdolibarr.actionplanKanban.chunkSize);
    $btn.before(chunk.join(''));

    // Update or remove the load-more button
    if (remaining.length === 0) {
        $btn.remove();
    } else {
        $btn.attr('data-remaining', remaining.length);
        var labelTpl = $btn.data('label') || '+ %s';
        $btn.find('.kanban-load-more-text').text(labelTpl.replace('%s', remaining.length));
    }

    // Make the freshly injected cards draggable
    $('.kanban-sortable').sortable('refresh');
};

/**
 * Update column counters after drag & drop.
 * Live DOM cards + still-deferred (not yet injected) cards = true total.
 */
window.digiriskdolibarr.actionplanKanban.updateCounts = function() {
    $('.kanban-column').each(function() {
        var colKey   = $(this).data('column');
        var deferred = window.digiriskdolibarr.actionplanKanban.deferredCards[colKey];
        var count    = $(this).find('.kanban-card').length + (deferred ? deferred.length : 0);
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
window.digiriskdolibarr.actionplanKanban.addContributor = function(taskId, userId, $dropdown) {
    var token          = window.saturne.toolbox.getToken();
    var querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
    var $card          = $dropdown.closest('.kanban-card');

    // Parse source type from prefixed value (internal_123 or external_456)
    var parts  = userId.split('_');
    var source = parts[0]; // 'internal' or 'external'
    var realId = parts[1];

    $card.addClass('kanban-card-saving');

    $.ajax({
        url: document.URL + querySeparator + 'action=addTaskContributor&task_id=' + taskId + '&user_id=' + realId + '&source=' + source + '&token=' + token,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                var initials = response.initials || '??';
                var fullname = response.fullname || '';
                // Add contributor initial chip to the DOM
                var $wrapper = $('<span class="kanban-initial-wrapper" data-task-id="' + taskId + '" data-user-id="' + realId + '"></span>');
                var $chip = $('<span class="kanban-initial kanban-initial-contributor" title="' + fullname + '">' + initials + '</span>');
                var $remove = $('<span class="kanban-remove-contact" title="Supprimer">&times;</span>');
                $wrapper.append($chip).append($remove);
                $card.find('.kanban-add-contributor-wrapper').before($wrapper);

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
 * Kanban settings — column width & gap with localStorage persistence
 */
window.digiriskdolibarr.actionplanKanban.initSettings = function() {
    var $btn     = $('#kanbanSettingsBtn');
    var $popover = $('#kanbanSettingsPopover');
    var $board   = $('.kanban-board');
    var $widthSlider = $('#kanbanColWidth');
    var $gapSlider   = $('#kanbanColGap');
    var $widthVal    = $('#kanbanColWidthVal');
    var $gapVal      = $('#kanbanColGapVal');

    if (!$btn.length) return;

    // Restore saved values
    var savedWidth = localStorage.getItem('kanbanColWidth');
    var savedGap   = localStorage.getItem('kanbanColGap');

    if (savedWidth) {
        $widthSlider.val(savedWidth);
        $widthVal.text(savedWidth + 'px');
        $('.kanban-column').css({'min-width': savedWidth + 'px', 'max-width': savedWidth + 'px'});
    }
    if (savedGap) {
        $gapSlider.val(savedGap);
        $gapVal.text(savedGap + 'px');
        $board.css('gap', savedGap + 'px');
    }

    // Toggle popover
    $btn.on('click', function(e) {
        e.stopPropagation();
        $popover.toggleClass('open');
    });

    // Close on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.kanban-settings-wrapper').length) {
            $popover.removeClass('open');
        }
    });

    // Width slider
    $widthSlider.on('input', function() {
        var val = $(this).val();
        $widthVal.text(val + 'px');
        $('.kanban-column').css({'min-width': val + 'px', 'max-width': val + 'px'});
        localStorage.setItem('kanbanColWidth', val);
    });

    // Gap slider
    $gapSlider.on('input', function() {
        var val = $(this).val();
        $gapVal.text(val + 'px');
        $board.css('gap', val + 'px');
        localStorage.setItem('kanbanColGap', val);
    });
};
