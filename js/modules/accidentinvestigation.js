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
 *          section (curative, corrective) opens the add-action form of that section.
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
    if (!$('.accidentinvestigation-action-add').length) {
        return;
    }

    window.digiriskdolibarr.accidentinvestigation.event();
};

/**
 * Bind the events of the page.
 *
 * @return {void}
 */
window.digiriskdolibarr.accidentinvestigation.event = function() {
    $(document).on('click', '.accidentinvestigation-action-add', window.digiriskdolibarr.accidentinvestigation.toggleActionForm);
    $(document).on('click', '.accidentinvestigation-action-form__cancel', window.digiriskdolibarr.accidentinvestigation.closeActionForm);
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
