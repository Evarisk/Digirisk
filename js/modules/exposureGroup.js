/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/exposureGroup.js
 * \ingroup digiriskdolibarr
 * \brief   Groupes d'exposition (#5644) : la grille de répartition des groupes sur les GP/UT recalcule, à la
 *          saisie, la répartition totale de chaque groupe et l'effectif exposé de chaque GP/UT.
 *          Balisage : view/digiriskstandard/digiriskstandard_exposuregroup.php
 */

/**
 * Initialise l'objet "exposureGroup" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   23.10.0
 * @version 23.10.0
 */
window.digiriskdolibarr.exposureGroup = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.exposureGroup.init = function() {
  window.digiriskdolibarr.exposureGroup.event();
  // Une saisie refusée revient telle quelle : les totaux et les parts invalides se recalculent dès l'affichage
  $('.exposuregroup-grid .exposuregroup-share').first().each(window.digiriskdolibarr.exposureGroup.refresh);
};

/**
 * Les événements de la grille.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.exposureGroup.event = function() {
  $(document).on('input', '.exposuregroup-grid .exposuregroup-share', window.digiriskdolibarr.exposureGroup.refresh);
};

/**
 * Lit une part saisie : vide vaut 0, la virgule comme le point séparent les décimales.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @param  {string} value Part saisie
 * @return {number}       Part en %, NaN si la saisie n'est pas un nombre
 */
window.digiriskdolibarr.exposureGroup.parse = function(value) {
  const text = String(value).trim().replace(/\s/g, '').replace(',', '.');
  if (text === '') {
    return 0;
  }

  return /^\d+(\.\d+)?$/.test(text) ? parseFloat(text) : NaN;
};

/**
 * Écrit un nombre avec le séparateur décimal de la langue, sans décimale s'il est entier.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @param  {number} number   Nombre
 * @param  {number} digits   Nombre maximal de décimales
 * @param  {string} decimal  Séparateur décimal
 * @return {string}          Nombre écrit
 */
window.digiriskdolibarr.exposureGroup.format = function(number, digits, decimal) {
  const factor  = Math.pow(10, digits);
  const rounded = Math.round(number * factor) / factor;

  return String(rounded).replace('.', decimal);
};

/**
 * Recalcule la grille après une saisie : le total de chaque groupe, dans la grille et dans la liste des groupes,
 * puis l'effectif exposé de chaque GP/UT. Une part qui n'est pas un pourcentage entre 0 et 100 est signalée.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.exposureGroup.refresh = function() {
  const exposureGroup = window.digiriskdolibarr.exposureGroup;
  const grid          = $(this).closest('.exposuregroup-grid');
  const decimal       = grid.data('decimal') || ',';
  const people        = {};
  const totals        = {};

  grid.find('th.exposuregroup-grid__group').each(function() {
    people[$(this).data('group')] = Number($(this).data('people')) || 0;
    totals[$(this).data('group')] = 0;
  });

  grid.find('tbody tr').each(function() {
    let exposed = 0;
    $(this).find('.exposuregroup-share').each(function() {
      const share   = exposureGroup.parse($(this).val());
      const invalid = isNaN(share) || share > 100;
      $(this).toggleClass('exposuregroup-share--invalid', invalid);
      if (invalid) {
        return;
      }
      totals[$(this).data('group')] += share;
      exposed += people[$(this).data('group')] * share / 100;
    });
    $(this).find('.exposuregroup-exposed').html(exposed > 0 ? exposureGroup.format(exposed, 1, decimal) : '<span class="opacitymedium">&mdash;</span>');
  });

  Object.keys(totals).forEach(function(groupId) {
    const total = totals[groupId];
    const state = Math.abs(total - 100) < 0.01 ? 'complete' : (total > 100 ? 'over' : 'partial');
    $('.exposuregroup-total[data-group="' + groupId + '"]')
      .removeClass('exposuregroup-total--complete exposuregroup-total--partial exposuregroup-total--over')
      .addClass('exposuregroup-total--' + state)
      .text(exposureGroup.format(total, 2, decimal) + ' %');
  });
};
