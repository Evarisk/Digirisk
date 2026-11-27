/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/rpsQuestionnaire.js
 * \ingroup digiriskdolibarr
 * \brief   Questionnaires RPS (#5324).
 *          Page publique : compte les réponses données et, à l'envoi, pointe la première question sans réponse
 *          (le serveur refuse aussi une réponse incomplète, la page marche sans JavaScript).
 *          Onglet de gestion : copie du lien public, unités et nombre de réponses proposés d'après la catégorie
 *          de poste choisie, détail d'une unité.
 *          Balisage : core/tpl/riskanalysis/rpsquestionnaire/*.tpl.php
 */

/**
 * Initialise l'objet "rpsQuestionnaire" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   23.10.0
 * @version 23.10.0
 */
window.digiriskdolibarr.rpsQuestionnaire = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.init = function() {
  window.digiriskdolibarr.rpsQuestionnaire.event();
  // Une réponse refusée revient avec ses choix : la progression part de là
  $('.rps-questionnaire__form').each(window.digiriskdolibarr.rpsQuestionnaire.refreshProgress);
};

/**
 * Les événements de la page publique et de l'onglet de gestion.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.event = function() {
  $(document).on('change', '.rps-questionnaire__form input[type="radio"]', window.digiriskdolibarr.rpsQuestionnaire.pickAnswer);
  $(document).on('submit', '.rps-questionnaire__form', window.digiriskdolibarr.rpsQuestionnaire.checkBeforeSend);
  $(document).on('click', '.rps-session-copy', window.digiriskdolibarr.rpsQuestionnaire.copyLink);
  $(document).on('change', '.rps-session-form select[name="fk_exposuregroup"]', window.digiriskdolibarr.rpsQuestionnaire.proposeUnits);
  $(document).on('click', '.rps-session-unit-toggle', window.digiriskdolibarr.rpsQuestionnaire.toggleUnitDetail);
};

/**
 * Une réponse donnée : la question n'est plus signalée et la progression avance.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.pickAnswer = function() {
  $(this).closest('.rps-questionnaire__question').removeClass('is-missing');
  window.digiriskdolibarr.rpsQuestionnaire.refreshProgress.call($(this).closest('.rps-questionnaire__form')[0]);
};

/**
 * Questions sans réponse du formulaire.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @param  {jQuery} form Formulaire du questionnaire
 * @return {jQuery}      Blocs des questions sans réponse
 */
window.digiriskdolibarr.rpsQuestionnaire.missingQuestions = function(form) {
  return form.find('.rps-questionnaire__question').filter(function() {
    return $(this).find('input[type="radio"]:checked').length === 0;
  });
};

/**
 * Compteur et barre de progression des réponses.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.refreshProgress = function() {
  const form     = $(this);
  const total    = parseInt(form.data('total'), 10) || 1;
  const answered = total - window.digiriskdolibarr.rpsQuestionnaire.missingQuestions(form).length;

  form.find('.rps-questionnaire__answered').text(answered);
  form.find('.rps-questionnaire__progress-fill').css('width', Math.round(100 * answered / total) + '%');
  form.toggleClass('is-complete', answered === total);
};

/**
 * À l'envoi : une unité choisie et toutes les questions répondues, sinon la première question manquante
 * est montrée, avec le nombre de réponses qui restent.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @param  {Event} event Envoi du formulaire
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.checkBeforeSend = function(event) {
  const form    = $(this);
  const errors  = form.find('.rps-questionnaire__errors--live');
  const units   = form.find('input[name="unit"][type="radio"]');
  const missing = window.digiriskdolibarr.rpsQuestionnaire.missingQuestions(form);

  let message = '';
  let target  = null;
  if (units.length && !units.filter(':checked').length) {
    message = form.data('label-unit');
    target  = form.find('.rps-questionnaire__step--unit');
  } else if (missing.length) {
    message = String(form.data('label-missing')).replace('%s', missing.length);
    target  = missing.first();
    missing.addClass('is-missing');
  }

  if (!target) {
    form.find('.rps-questionnaire__send').prop('disabled', true);
    return;
  }

  event.preventDefault();
  errors.text(message).prop('hidden', false);
  target[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
};

/**
 * Copie le lien public d'une session dans le presse-papiers.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.copyLink = function() {
  const button = $(this);
  const input  = button.closest('.rps-session-link').find('.rps-session-url');
  const done   = function() {
    button.addClass('is-copied');
    setTimeout(function() {
      button.removeClass('is-copied');
    }, 1600);
  };

  input.trigger('select');
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(input.val()).then(done);
  } else {
    document.execCommand('copy');
    done();
  }
};

/**
 * Catégorie de poste choisie dans le formulaire d'une session : ses unités de travail (celles où le groupe
 * d'exposition a une part) sont proposées, et son effectif comme nombre de réponses par unité tant que le champ
 * est vide.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.proposeUnits = function() {
  const select = $(this);
  const form   = select.closest('.rps-session-form');
  const option = select.find('option:selected');
  const units  = option.data('units') || [];
  const people = parseInt(option.data('people'), 10) || 0;
  const field  = form.find('select.rps-session-units');
  const max    = form.find('input[name="max_answers"]');

  if (units.length) {
    field.val(units.map(String)).trigger('change');
  }
  if (people > 0 && String(max.val()).trim() === '') {
    max.val(people);
  }
};

/**
 * Déplie ou replie le détail d'une unité de travail d'une session : la moyenne de chaque critère.
 *
 * @since   23.10.0
 * @version 23.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.rpsQuestionnaire.toggleUnitDetail = function() {
  const button = $(this);
  const detail = $('#' + button.attr('aria-controls'));
  const open   = button.attr('aria-expanded') !== 'true';

  button.attr('aria-expanded', open ? 'true' : 'false');
  detail.prop('hidden', !open);
};
