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
 * \brief   JavaScript of the actions tabs (accident investigation, ticket): the add button of the header of each
 *          section opens the add-action form of that section, the progress of an action is dragged on its
 *          slider then saved on release, and the Gantt of the actions is laid out.
 */

'use strict';

/**
 * Init the actions object and its mandatory "init" method.
 */
window.digiriskdolibarr.actions = {};

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.actions.init = function() {
    if (!$('.digirisk-actions-add, .digirisk-actions-progress__slider').length) {
        return;
    }

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
    $(document).on('click', '.digirisk-actions-add', window.digiriskdolibarr.actions.toggleActionForm);
    $(document).on('click', '.digirisk-actions-form__cancel', window.digiriskdolibarr.actions.closeActionForm);
    $(document).on('input', '.digirisk-actions-progress__slider', window.digiriskdolibarr.actions.refreshProgress);
    $(document).on('change', '.digirisk-actions-progress__slider', window.digiriskdolibarr.actions.saveProgress);
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
