/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/ticket_qqoqcp.js
 * \ingroup digiriskdolibarr
 * \brief   QQOQCCP tab of a ticket (view/ticket/ticket_qqoqcp.php, #5591).
 */

'use strict';

window.digiriskdolibarr.ticketQqoqcp = {};

/**
 * Init.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQqoqcp.init = function() {
  window.digiriskdolibarr.ticketQqoqcp.event();
};

/**
 * Event bindings (delegated).
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQqoqcp.event = function() {
  $(document).on('submit', '#builddoc_form', window.digiriskdolibarr.ticketQqoqcp.carryFields);
};

/**
 * The fields of the tab are always open: their values go along with the generation form, saved before the
 * document is built, so that a change not saved yet is never missing from the document.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQqoqcp.carryFields = function() {
  const qqoqcpForm = document.getElementById('digirisk-qqoqcp-form');
  if (!qqoqcpForm) {
    return;
  }

  const buildDocForm = $(this);
  buildDocForm.find('.digirisk-qqoqcp-carried').remove();
  new FormData(qqoqcpForm).forEach(function(value, name) {
    if (name !== 'token' && name !== 'action') {
      $('<input type="hidden" class="digirisk-qqoqcp-carried">').attr('name', name).val(value).appendTo(buildDocForm);
    }
  });
};
