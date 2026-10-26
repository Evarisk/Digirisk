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
 * \file    js/modules/accidentinvestigation.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript of the actions tab of the accident investigation: the add button of the header of each
 *          section (curative, corrective) opens the add-action form of that section, and the progress of an
 *          action is dragged on its slider then saved on release.
 */

'use strict';

/**
 * Init the accidentinvestigation object and its mandatory "init" method.
 */
window.digiriskdolibarr.accidentinvestigation = {};

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.accidentinvestigation.init = function() {
    if (!$('.accidentinvestigation-action-add, .accidentinvestigation-progress__slider').length) {
        return;
    }

    window.digiriskdolibarr.accidentinvestigation.event();
    $('.accidentinvestigation-progress__slider').each(window.digiriskdolibarr.accidentinvestigation.refreshProgress);
};

/**
 * Bind the events of the page.
 *
 * @return {void}
 */
window.digiriskdolibarr.accidentinvestigation.event = function() {
    $(document).on('click', '.accidentinvestigation-action-add', window.digiriskdolibarr.accidentinvestigation.toggleActionForm);
    $(document).on('click', '.accidentinvestigation-action-form__cancel', window.digiriskdolibarr.accidentinvestigation.closeActionForm);
    $(document).on('input', '.accidentinvestigation-progress__slider', window.digiriskdolibarr.accidentinvestigation.refreshProgress);
    $(document).on('change', '.accidentinvestigation-progress__slider', window.digiriskdolibarr.accidentinvestigation.saveProgress);
};

/**
 * Open the add-action form of the section, or close it when it is already open.
 *
 * @param  {Event} event Click on the add button.
 * @return {void}
 */
window.digiriskdolibarr.accidentinvestigation.toggleActionForm = function(event) {
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
window.digiriskdolibarr.accidentinvestigation.closeActionForm = function() {
    $(this).closest('.accidentinvestigation-action-form').addClass('hideobject');
};

/**
 * Fill the slider up to its value and write the value next to it, while it is dragged.
 *
 * @return {void}
 */
window.digiriskdolibarr.accidentinvestigation.refreshProgress = function() {
    const $slider = $(this);
    const value   = parseInt($slider.val(), 10) || 0;

    this.style.setProperty('--progress', value + '%');
    $slider.siblings('.accidentinvestigation-progress__value').text(value + ' %');
    $slider.closest('.accidentinvestigation-progress').toggleClass('accidentinvestigation-progress--done', value >= 100);
};

/**
 * Save the progress of the action once the slider is released, then refresh its row and the counters of the sections
 * from the page sent back: the close checkbox follows the progress, and the counters the actions done.
 *
 * @return {void}
 */
window.digiriskdolibarr.accidentinvestigation.saveProgress = function() {
    const $slider   = $(this);
    const $progress = $slider.closest('.accidentinvestigation-progress');
    const rowId     = $slider.closest('tr').attr('id');
    const value     = $slider.val();

    // The arrow keys fire a change at each step: only the last one is saved
    clearTimeout($slider.data('saveTimer'));
    $slider.data('saveTimer', setTimeout(function() {
        $progress.addClass('accidentinvestigation-progress--saving');

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
                    $newRow.find('.accidentinvestigation-progress__slider').each(window.digiriskdolibarr.accidentinvestigation.refreshProgress);
                }
                $page.find('[id^="accidentinvestigation-actions-counter-"]').each(function() {
                    $('#' + this.id).replaceWith(this);
                });
            },
            error: function() {
                // Back to the saved progress, which the value attribute still holds
                $slider.val($slider.attr('value'));
                window.digiriskdolibarr.accidentinvestigation.refreshProgress.call($slider[0]);
            },
            complete: function() {
                $progress.removeClass('accidentinvestigation-progress--saving');
            }
        });
    }, 300));
};
