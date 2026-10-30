/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    js/modules/actions.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript of the actions tabs (accident investigation, ticket): the view switches between condensed and
 *          detailed, the add button of the header of each section opens the add-action form of that section, a
 *          click on a field of an action edits it in place, the progress of an action is dragged on its slider, both
 *          saved at once, and the Gantt of the actions is laid out.
 */

'use strict';

/**
 * Init the actions object and its mandatory "init" method.
 */
window.digiriskdolibarr.actions = {};

/**
 * Key of the view chosen, kept in the browser.
 *
 * @type {string}
 */
window.digiriskdolibarr.actions.viewStorageKey = 'digiriskdolibarrActionsView';

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.actions.init = function() {
    if (!$('#digirisk-actions').length) {
        return;
    }

    let view = 'condensed';
    try {
        view = window.localStorage.getItem(window.digiriskdolibarr.actions.viewStorageKey) || view;
    } catch (error) {
        // Storage refused (private browsing, blocked site data): the condensed view stays
    }
    window.digiriskdolibarr.actions.applyView(view);

    window.digiriskdolibarr.actions.event();
    $('.digirisk-actions-progress__slider').each(window.digiriskdolibarr.actions.refreshProgress);
    window.digiriskdolibarr.actions.drawGantt();
};

/**
 * Bind the events of the page.
 *
 * @return {void}
 */
window.digiriskdolibarr.actions.event = function() {
    $(document).on('click', '.digirisk-actions__view', window.digiriskdolibarr.actions.switchView);
    $(document).on('click', '.digirisk-actions-add', window.digiriskdolibarr.actions.toggleActionForm);
    $(document).on('click', '.digirisk-actions-form__cancel', window.digiriskdolibarr.actions.closeActionForm);
    $(document).on('click', '.digirisk-actions-cell', window.digiriskdolibarr.actions.editCell);
    $(document).on('input', '.digirisk-actions-progress__slider', window.digiriskdolibarr.actions.refreshProgress);
    $(document).on('change', '.digirisk-actions-progress__slider', window.digiriskdolibarr.actions.saveProgress);
};

/**
 * Show the condensed view, without the Gantt nor the detail columns, or the detailed one.
 *
 * @param  {string} view condensed or detailed.
 * @return {void}
 */
window.digiriskdolibarr.actions.applyView = function(view) {
    const detailed = view === 'detailed';

    $('#digirisk-actions').toggleClass('digirisk-actions--condensed', !detailed);
    $('.digirisk-actions__view').each(function() {
        $(this).toggleClass('btnTitleSelected', ($(this).data('view') === 'detailed') === detailed);
    });
};

/**
 * Switch to the view of the button, and remember it for the next actions tabs.
 *
 * @param  {Event} event Click on a view button.
 * @return {void}
 */
window.digiriskdolibarr.actions.switchView = function(event) {
    event.preventDefault();

    const view = $(this).data('view');
    window.digiriskdolibarr.actions.applyView(view);
    try {
        window.localStorage.setItem(window.digiriskdolibarr.actions.viewStorageKey, view);
    } catch (error) {
        // Storage refused: the view only holds until the page is left
    }
};

/**
 * Turn a cell of an action into the editor of its field, filled with its raw value: a text, number or date field, or
 * a copy of the list of the users or of the tags. Leaving the editor saves the new value, Escape gives the cell back.
 *
 * @param  {Event} event Click on the cell.
 * @return {void}
 */
window.digiriskdolibarr.actions.editCell = function(event) {
    const $cell = $(this);
    if ($cell.hasClass('digirisk-actions-cell--editing') || $(event.target).closest('a').length) {
        return;
    }

    const field   = $cell.data('field');
    // attr() rather than data(): data() would turn "12" into a number and "" into undefined
    const value   = String($cell.attr('data-value') || '');
    const content = $cell.html();
    const taskId  = $cell.closest('tr').data('taskId');

    const restore = function() {
        $cell.removeClass('digirisk-actions-cell--editing').html(content);
    };
    const save = function(newValue) {
        // Nothing changed: no need for a round trip
        const normalized = Array.isArray(newValue) ? newValue.slice().sort().join(',') : String(newValue);
        const initial    = field === 'tags' ? value.split(',').filter(Boolean).sort().join(',') : value;
        if (normalized === initial) {
            restore();
            return;
        }
        window.digiriskdolibarr.actions.saveField($cell, field, newValue, restore);
    };

    $cell.addClass('digirisk-actions-cell--editing');

    // Assignee and tags: a copy of the list rendered once under the sections, searchable with select2
    if (field === 'assignee' || field === 'tags') {
        const $select = $('#digirisk_actions_editor_' + field).clone().attr('id', 'digirisk-actions-editor-' + field + '-' + taskId);
        $cell.empty().append($select);
        $select.val(field === 'tags' ? value.split(',').filter(Boolean) : (parseInt(value, 10) > 0 ? value : '-1'));

        const selected = function() {
            if (field === 'tags') {
                return $select.val() || [];
            }
            return parseInt($select.val(), 10) > 0 ? $select.val() : '0';
        };

        if ($.fn.select2) {
            // Several tags are picked in a row: the list stays open until the user clicks away
            $select.select2({width: '100%', dropdownAutoWidth: true, closeOnSelect: field !== 'tags'});
            $select.on('select2:close', function() {
                save(selected());
            });
            $select.select2('open');
        } else {
            // A change is followed by a blur: only the first one saves
            $select.on('change blur', function() {
                $select.off('change blur');
                save(selected());
            });
            $select.trigger('focus');
        }
        return;
    }

    const type    = field === 'label' ? 'text' : (field === 'budget' ? 'number' : 'date');
    const $input  = $('<input class="digirisk-actions-cell__input">').attr('type', type).val(value);
    let isDone      = false; // blur still fires after Enter or Escape
    let isTyping    = false; // a date typed with the keyboard is only saved once left
    let typingTimer = null;

    if (type === 'number') {
        $input.attr({min: 0, step: 'any'});
    }
    $cell.empty().append($input);
    $input.trigger('focus');

    const leave = function(keep) {
        if (isDone) {
            return;
        }
        isDone = true;
        clearTimeout(typingTimer);

        // A half-typed date reads as an empty value: give the cell back rather than erase the date
        if (!keep || ($input[0].validity && $input[0].validity.badInput)) {
            restore();
            return;
        }
        save($input.val());
    };

    $input.on('blur', function() {
        leave(true);
    });
    $input.on('keydown', function(keyEvent) {
        if (keyEvent.key === 'Escape' || keyEvent.key === 'Enter') {
            keyEvent.preventDefault();
            leave(keyEvent.key === 'Enter');
            return;
        }
        // Typing a date rewrites it segment by segment and fires a change on every intermediate value
        if (type === 'date') {
            isTyping = true;
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function() {
                isTyping = false;
            }, 1500);
        }
    });
    // A day picked in the calendar fires a change without any keystroke: that choice is saved at once
    if (type === 'date') {
        $input.on('change', function() {
            if (!isTyping) {
                leave(true);
            }
        });
    }
};

/**
 * Save a field edited in place, then refresh the action from the page sent back. A refused value is told and the
 * cell given back as it was.
 *
 * @param  {jQuery}          $cell   Cell of the field.
 * @param  {string}          field   Field of the action: label, assignee, date_start, date_end, budget or tags.
 * @param  {string|string[]} value   New value.
 * @param  {Function}        restore Gives the cell back as it was.
 * @return {void}
 */
window.digiriskdolibarr.actions.saveField = function($cell, field, value, restore) {
    const $row = $cell.closest('tr');

    $cell.addClass('digirisk-actions-cell--saving');

    $.ajax({
        url: window.location.href.replace(/#.*/, ''),
        type: 'POST',
        data: {
            token: window.saturne.toolbox.getToken(),
            action: 'updateActionField',
            taskid: $row.data('taskId'),
            field: field,
            value: value
        },
        success: function(resp) {
            window.digiriskdolibarr.actions.refreshFromPage(resp, $row.attr('id'));
        },
        error: function(xhr) {
            $cell.removeClass('digirisk-actions-cell--saving');
            restore();
            if ($.jnotify) {
                $.jnotify(xhr.responseText || 'Error', 'error', true);
            }
        }
    });
};

/**
 * Refresh an action from the page sent back after saving it: its row, the counters of the sections and the Gantt.
 *
 * @param  {string} resp  HTML of the page.
 * @param  {string} rowId ID of the row of the action.
 * @return {void}
 */
window.digiriskdolibarr.actions.refreshFromPage = function(resp, rowId) {
    const $page   = $(new DOMParser().parseFromString(resp, 'text/html'));
    const $newRow = $page.find('#' + rowId);
    if ($newRow.length) {
        $('#' + rowId).replaceWith($newRow);
        $newRow.find('.digirisk-actions-progress__slider').each(window.digiriskdolibarr.actions.refreshProgress);
    }
    $page.find('[id^="digirisk-actions-counter-"]').each(function() {
        $('#' + this.id).replaceWith(this);
    });
    $('#digirisk-actions-gantt').replaceWith($page.find('#digirisk-actions-gantt'));
    window.digiriskdolibarr.actions.drawGantt();
};

/**
 * Open the add-action form of the section, or close it when it is already open.
 *
 * @param  {Event} event Click on the add button.
 * @return {void}
 */
window.digiriskdolibarr.actions.toggleActionForm = function(event) {
    event.preventDefault();

    const $form = $('#' + $(this).data('form'));
    $form.toggleClass('hideobject');
    if (!$form.hasClass('hideobject')) {
        $form.find('input[name="action_label"]').trigger('focus');
    }
};

/**
 * Close the add-action form.
 *
 * @return {void}
 */
window.digiriskdolibarr.actions.closeActionForm = function() {
    $(this).closest('.digirisk-actions-form').addClass('hideobject');
};

/**
 * Fill the slider up to its value and write the value next to it, while it is dragged.
 *
 * @return {void}
 */
window.digiriskdolibarr.actions.refreshProgress = function() {
    const $slider = $(this);
    const value   = parseInt($slider.val(), 10) || 0;

    this.style.setProperty('--progress', value + '%');
    $slider.siblings('.digirisk-actions-progress__value').text(value + ' %');
    $slider.closest('.digirisk-actions-progress').toggleClass('digirisk-actions-progress--done', value >= 100);
};

/**
 * Save the progress of the action once the slider is released, then refresh its row and the counters of the sections
 * from the page sent back: the close checkbox follows the progress, and the counters the actions done.
 *
 * @return {void}
 */
window.digiriskdolibarr.actions.saveProgress = function() {
    const $slider   = $(this);
    const $progress = $slider.closest('.digirisk-actions-progress');
    const rowId     = $slider.closest('tr').attr('id');
    const value     = $slider.val();

    // The arrow keys fire a change at each step: only the last one is saved
    clearTimeout($slider.data('saveTimer'));
    $slider.data('saveTimer', setTimeout(function() {
        $progress.addClass('digirisk-actions-progress--saving');

        $.ajax({
            url: window.location.href.replace(/#.*/, ''),
            type: 'POST',
            data: {
                token: window.saturne.toolbox.getToken(),
                action: 'updateProgress',
                taskid: $slider.data('taskId'),
                progress_value: value
            },
            success: function(resp) {
                // Dragged again meanwhile: the next save refreshes the row
                if ($slider.val() !== value) {
                    return;
                }

                window.digiriskdolibarr.actions.refreshFromPage(resp, rowId);
            },
            error: function() {
                // Back to the saved progress, which the value attribute still holds
                $slider.val($slider.attr('value'));
                window.digiriskdolibarr.actions.refreshProgress.call($slider[0]);
            },
            complete: function() {
                $progress.removeClass('digirisk-actions-progress--saving');
            }
        });
    }, 300));
};

/**
 * Lay out the Gantt of the actions: the positions computed by the back office, percentages of the timeline carried
 * by data attributes, become the CSS variables of the ticks, of today and of the bars. The chart stays hidden until then.
 *
 * @return {void}
 */
window.digiriskdolibarr.actions.drawGantt = function() {
    const $gantt = $('#digirisk-actions-gantt');

    $gantt.find('[data-position]').each(function() {
        this.style.setProperty('--position', this.dataset.position + '%');
    });
    $gantt.find('.digirisk-actions-gantt__bar').each(function() {
        this.style.setProperty('--start', this.dataset.start + '%');
        this.style.setProperty('--length', this.dataset.length + '%');
        this.style.setProperty('--progress', this.dataset.progress + '%');
    });
    $gantt.addClass('digirisk-actions-gantt--drawn');
};
