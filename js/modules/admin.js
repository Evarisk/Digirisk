/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/admin.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript file toolbox for module DigiriskDolibarr
 */

'use strict';

/**
 * Init admin JS
 *
 * @memberof DigiriskDolibarr_Framework_Admin
 *
 * @since   21.0.0
 * @version 21.0.0
 */
window.digiriskdolibarr.admin = {};

// Common suffix for all IDs
window.digiriskdolibarr.admin.urlSuffix = '-ticket-public-interface-url';

// The option keys that vary across each mapping
window.digiriskdolibarr.admin.optionKeys = [
  'origin-current',
  'short-current',
  'external-current',
  'origin-multicompany',
  'short-multicompany',
  'external-multicompany',
];

// Dynamically generate the mappings array
window.digiriskdolibarr.admin.mappings = window.digiriskdolibarr.admin.optionKeys.map(key => ({
  radio: `#${key}${window.digiriskdolibarr.admin.urlSuffix}`,
  label: `#${key}${window.digiriskdolibarr.admin.urlSuffix}-label`,
  input: `#${key}${window.digiriskdolibarr.admin.urlSuffix}-input`
}));

/**
 * Admin Init
 *
 * @memberof DigiriskDolibarr_Framework_Admin
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.admin.init = function() {
  window.digiriskdolibarr.admin.initRadioLogicTicketPublicInterface();
};

/**
 * Attach behavior to each mapping
 *
 * @memberof DigiriskDolibarr_Framework_Admin
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.admin.initRadioLogicTicketPublicInterface = function () {
  window.digiriskdolibarr.admin.mappings.forEach(({ radio, label, input }) => {
    // when the label is clicked, check the radio and focus the input if present
    $(document).on('click', label, () => {
      $(radio).prop('checked', true);
      if (input) {
        $(input).focus();
      }
    });

    // when the input gets focus, check the corresponding radio
    if (input) {
      $(document).on('focus', input, () => {
        $(radio).prop('checked', true);
      });
    }
  });
};
