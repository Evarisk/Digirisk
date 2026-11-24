/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/riskLibrary.js
 * \ingroup digiriskdolibarr
 * \brief   Bibliothèque des risques (#5644) : le crayon d'une case de la grille ouvre la qualification du GP/UT pour
 *          le risque de la bibliothèque, remplie avec son état et son motif.
 *          Balisage : view/digiriskstandard/digiriskstandard_risklibrary.php
 */

/**
 * Initialise l'objet "riskLibrary" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   23.10.0
 * @version 23.10.0
 */
window.digiriskdolibarr.riskLibrary = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskLibrary.init = function() {
  window.digiriskdolibarr.riskLibrary.event();
};

/**
 * Les événements de la grille et de la qualification.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskLibrary.event = function() {
  $(document).on('click', '.risklibrary-grid .risklibrary-edit', window.digiriskdolibarr.riskLibrary.fillScope);
  $(document).on('change', '#risklibrary_scope_modal input[name="concerned"]', window.digiriskdolibarr.riskLibrary.toggleReason);
};

/**
 * Remplit la qualification avec la case cliquée : le risque, le GP/UT, son état et son motif. La modale s'ouvre par
 * la classe modal-open de la case, comme les autres modales.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskLibrary.fillScope = function() {
  const cell  = $(this);
  const modal = $('#risklibrary_scope_modal');

  modal.find('input[name="modelid"]').val(cell.data('model'));
  modal.find('input[name="elementid"]').val(cell.data('element'));
  modal.find('.risklibrary-modal__subject').text(cell.data('title'));
  modal.find('textarea[name="reason"]').val(cell.data('reason'));
  modal.find('input[name="concerned"][value="' + cell.data('state') + '"]').prop('checked', true);

  window.digiriskdolibarr.riskLibrary.toggleReason();
};

/**
 * Le motif ne se demande qu'à un GP/UT non concerné, qui ne peut s'en passer.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskLibrary.toggleReason = function() {
  const modal        = $('#risklibrary_scope_modal');
  const notConcerned = modal.find('input[name="concerned"]:checked').val() === '0';

  modal.find('textarea[name="reason"]').toggle(notConcerned).prop('required', notConcerned);
};
