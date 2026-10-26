/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/psychosocialScope.js
 * \ingroup digiriskdolibarr
 * \brief   Risques psychosociaux de la liste des risques : qualification des unités de travail
 *          sur le groupement de tête, évaluation et plan d'action d'une unité concernée.
 *          Balisage : core/tpl/riskanalysis/risk/digiriskdolibarr_rps_*.tpl.php
 */

/**
 * Initialise l'objet "psychosocialScope" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   23.5.1
 * @version 23.5.1
 */
window.digiriskdolibarr.psychosocialScope = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.init = function() {
  window.digiriskdolibarr.psychosocialScope.event();
};

/**
 * La méthode contenant tous les événements des risques psychosociaux.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.event = function() {
  const scope = window.digiriskdolibarr.psychosocialScope;

  // Qualification des unités, sur le groupement de tête
  $(document).on('click', '.rps-scope-toggle', scope.toggleBody);
  $(document).on('click', '.rps-switch button', scope.qualify);
  $(document).on('focusout', '.rps-reason-input', scope.saveReason);

  // Fenêtre d'évaluation d'une unité, ouverte par la modale Saturne
  $(document).on('click', '.rps-open-eval, .rps-open-actions', scope.openAssessment);
  $(document).on('click', '#rps_eval_modal .rps-method', scope.switchMethod);
  $(document).on('click', '#rps_eval_modal .rps-grid-answer', scope.pickAnswer);
  $(document).on('click', '#rps-actions-more', scope.toggleActions);
  $(document).on('click', '#rps-eval-save', scope.saveAssessment);
  $(document).on('click', '.rps-consolidate', scope.consolidate);

  // Plan d'action de l'unité, tenu depuis le bloc
  $(document).on('change', '.rps-action-item .rps-task-done', scope.saveTaskDone);
  $(document).on('change', '.rps-action-item .rps-task-owner', scope.saveTaskOwner);
  $(document).on('change', '.rps-action-item .rps-task-due', scope.saveTaskDue);
  $(document).on('click', '.rps-action-item .rps-task-delete', scope.deleteTask);
};

/**
 * Libellé d'un palier de cotation ("51-79 · Elevé"), rendu par le PHP sur le bloc.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {number} level Palier de 1 à 4
 * @return {string}       Libellé du palier
 */
window.digiriskdolibarr.psychosocialScope.scaleLabel = function(level) {
  const labels = $('.rps-scope').first().data('scale-labels') || {};
  return labels[level] || '';
};

/**
 * Palier de cotation d'une moyenne de facteurs, avec les bornes de rps_scope_band().
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {number} average Moyenne des facteurs cotés
 * @return {number}         Palier de 1 à 4
 */
window.digiriskdolibarr.psychosocialScope.bandOf = function(average) {
  if (average >= 3.5) {
    return 4;
  }
  if (average >= 2.5) {
    return 3;
  }
  if (average >= 1.5) {
    return 2;
  }
  return 1;
};

/**
 * Affiche une cotation sur une pastille .rps-band, ou "non cotée" au palier 0.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} band  Pastille de cotation
 * @param  {number} level Palier de 0 à 4
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.setBand = function(band, level) {
  band.attr('data-scale', level);
  band.text(level > 0 ? window.digiriskdolibarr.psychosocialScope.scaleLabel(level) : 'non cotée');
};

/**
 * Affiche un niveau dans une case de facteur, ou la vide au niveau 0.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} cell  Case de facteur
 * @param  {number} level Niveau de 0 à 4
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.setCell = function(cell, level) {
  cell.attr('data-scale', level || 0);
  cell.text(level || '');
};

/**
 * Envoie une requête à core/ajax/save_rps_assessment.php.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {Object} payload Données envoyées en JSON
 * @return {Promise}        Réponse décodée : {ok, body}
 */
window.digiriskdolibarr.psychosocialScope.post = function(payload) {
  const endpoint = $('.rps-scope').first().data('endpoint');
  const token    = window.saturne.toolbox.getToken();

  return fetch(endpoint + '?token=' + token, {
    method: 'POST',
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    body: JSON.stringify(payload),
    credentials: 'same-origin'
  }).then(function(response) {
    return response.json().then(function(body) {
      return {ok: response.ok && body && body.success, body: body};
    });
  });
};

/* ---------------------------------------------------------------------------
 * Qualification des unités de travail
 * ------------------------------------------------------------------------ */

/**
 * Replie ou déplie la liste des unités du bloc.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.toggleBody = function() {
  const body = $(this).closest('.rps-scope').find('.rps-body');

  body.toggleClass('rps-hidden');
  $(this).find('i').toggleClass('fa-chevron-up', !body.hasClass('rps-hidden')).toggleClass('fa-chevron-down', body.hasClass('rps-hidden'));
};

/**
 * Recalcule l'en-tête et les groupes du bloc après un changement de qualification.
 * La cotation du bloc est celle de l'unité la plus sévère, comme côté PHP.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} block Bloc de qualification
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.refreshScope = function(block) {
  const scope = window.digiriskdolibarr.psychosocialScope;
  let concerned = 0, assessed = 0, sum = 0, band = 0;

  block.find('.rps-row[data-state="1"]').each(function() {
    concerned++;
    const average = parseFloat($(this).attr('data-average'));
    if (!isNaN(average)) {
      assessed++;
      sum += average;
      band = Math.max(band, scope.bandOf(average));
    }
  });

  block.find('.rps-group').each(function() {
    const rows = $(this).find('.rps-row').length;
    $(this).find('.rps-count').text(rows);
    $(this).prop('hidden', rows === 0);
  });

  block.find('#rps-average').text(assessed > 0 ? (sum / assessed).toFixed(2).replace('.', ',') : '—');
  block.find('#rps-done').text(assessed);
  block.find('#rps-concerned').text(concerned);
  block.find('#rps-bar').val(concerned > 0 ? Math.round(100 * assessed / concerned) : 0);
  scope.setBand(block.find('#rps-band'), band);
};

/**
 * Déplace une ligne dans le groupe de son nouvel état, les unités restant classées par référence.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} row   Ligne de l'unité
 * @param  {string} state Nouvel état : 1, 0 ou -1
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.moveRow = function(row, state) {
  const key  = state === '1' ? 'yes' : (state === '0' ? 'no' : 'tbd');
  const list = row.closest('.rps-scope').find('.rps-group[data-group="' + key + '"] .rps-rows');
  if (!list.length) {
    return;
  }

  const reference = row.find('.rps-unit-ref').text().trim();
  const before    = list.children('.rps-row').not(row).filter(function() {
    return $(this).find('.rps-unit-ref').text().trim().localeCompare(reference, 'fr', {numeric: true}) > 0;
  }).first();

  if (before.length) {
    row.insertBefore(before);
  } else {
    list.append(row);
  }
};

/**
 * Une unité déclarée non concernée doit porter un motif : il est demandé sur place.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} row Ligne de l'unité
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.askReason = function(row) {
  const meta = row.find('.rps-unit-meta');
  if (meta.find('.rps-reason-input').length) {
    return;
  }
  if (row.attr('data-meta') === undefined) {
    row.attr('data-meta', meta.html());
  }

  const input = $('<input type="text" class="rps-reason-input" placeholder="Motif de non-exposition (obligatoire pour le document unique)">');
  input.val(meta.find('.rps-reason').text().trim());
  meta.empty().append(input);
  input.trigger('focus');
};

/**
 * Remet le texte d'origine sous une unité qui n'est plus déclarée non concernée.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} row Ligne de l'unité
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.restoreMeta = function(row) {
  const meta = row.find('.rps-unit-meta');
  if (!meta.find('.rps-reason-input').length) {
    return;
  }
  meta.html(row.attr('data-meta') !== undefined ? row.attr('data-meta') : '<span class="rps-attention">aucun facteur coté</span>');
};

/**
 * Qualifie une unité : concernée, non concernée ou à qualifier. La qualification est
 * enregistrée tout de suite, c'est elle qui commande la ligne psychosociale de l'unité.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.qualify = function() {
  const scope  = window.digiriskdolibarr.psychosocialScope;
  const button = $(this);
  const row    = button.closest('.rps-row');
  const state  = button.attr('data-value');

  button.siblings().addBack().removeClass('is-yes is-no is-tbd');
  button.addClass(state === '1' ? 'is-yes' : (state === '0' ? 'is-no' : 'is-tbd'));

  // Le motif n'est demandé qu'une fois la ligne dans son groupe, et ce groupe affiché : déplacer
  // le champ, ou le créer dans un groupe encore masqué, lui ferait perdre le focus
  row.attr('data-state', state);
  scope.moveRow(row, state);
  scope.refreshScope(row.closest('.rps-scope'));
  if (state === '0') {
    scope.askReason(row);
  } else {
    scope.restoreMeta(row);
  }

  const reasonInput = row.find('.rps-reason-input');
  scope.saveScope(row, reasonInput.length ? reasonInput.val() : row.find('.rps-reason').text());
};

/**
 * Enregistre la qualification d'une unité et son motif.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} row    Ligne de l'unité
 * @param  {string} reason Motif de non-exposition
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.saveScope = function(row, reason) {
  window.digiriskdolibarr.psychosocialScope.post({
    action: 'scope',
    fk_element: parseInt(row.attr('data-element'), 10),
    concerned: parseInt(row.attr('data-state'), 10),
    reason: (reason || '').trim()
  }).then(function(result) {
    if (!result.ok) {
      window.alert((result.body && result.body.error) ? result.body.error : 'Qualification non enregistrée.');
    }
  }).catch(function() {
    window.alert('Qualification non enregistrée.');
  });
};

/**
 * Enregistre le motif d'une unité non concernée à la sortie du champ.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.saveReason = function() {
  const row = $(this).closest('.rps-row');
  if (row.length) {
    window.digiriskdolibarr.psychosocialScope.saveScope(row, $(this).val());
  }
};

/* ---------------------------------------------------------------------------
 * Évaluation d'une unité de travail
 * ------------------------------------------------------------------------ */

/**
 * Volet de la méthode affichée dans la fenêtre d'évaluation.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {jQuery} Volet actif
 */
window.digiriskdolibarr.psychosocialScope.activePane = function() {
  const modal = $('#rps_eval_modal');
  return modal.find('.rps-pane[data-method="' + modal.attr('data-method') + '"]');
};

/**
 * La fenêtre s'ouvre par la modale Saturne : elle est remise à jour, et le bouton Actions
 * l'amène directement sur sa section.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.openAssessment = function() {
  window.digiriskdolibarr.psychosocialScope.refreshAssessment();

  if ($(this).hasClass('rps-open-actions')) {
    setTimeout(function() {
      const section = document.getElementById('rps-eval-actions');
      if (section) {
        section.scrollIntoView({block: 'start'});
      }
    }, 0);
  }
};

/**
 * Change de méthode : seul le volet affiché est lu et enregistré, celui qu'on quitte est remis à zéro.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.switchMethod = function() {
  const modal  = $('#rps_eval_modal');
  const method = $(this).attr('data-method');
  if (method === modal.attr('data-method')) {
    return;
  }

  window.digiriskdolibarr.psychosocialScope.activePane().find('.rps-grid-answer.is-picked').removeClass('is-picked');
  modal.attr('data-method', method);
  modal.find('.rps-method').removeClass('is-on');
  $(this).addClass('is-on');
  window.digiriskdolibarr.psychosocialScope.refreshAssessment();
};

/**
 * Retient une réponse, ou la retire si elle l'était déjà : un clic par erreur se rattrape.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.pickAnswer = function() {
  const wasPicked = $(this).hasClass('is-picked');

  $(this).siblings().addBack().removeClass('is-picked');
  $(this).toggleClass('is-picked', !wasPicked);
  window.digiriskdolibarr.psychosocialScope.refreshAssessment();
};

/**
 * Recalcule la fenêtre d'évaluation : avancement, facteurs, cotation retenue et actions.
 * Un facteur prend le plus sévère de ses critères, la cotation suit la moyenne des facteurs.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.refreshAssessment = function() {
  const scope = window.digiriskdolibarr.psychosocialScope;
  const modal = $('#rps_eval_modal');
  const rows  = scope.activePane().find('.rps-grid-row');
  const factors = {};
  let answered  = 0;

  rows.each(function() {
    const picked = $(this).find('.rps-grid-answer.is-picked');
    if (!picked.length) {
      return;
    }
    answered++;
    const factor = parseInt($(this).attr('data-sub-category'), 10);
    // Les positions 6 et 7 décrivent le contexte et l'impact : hors cotation
    if (factor <= 5) {
      factors[factor] = Math.max(factors[factor] || 0, parseInt(picked.attr('data-scale'), 10));
    }
  });

  modal.find('#rps-eval-answered').text(answered);
  modal.find('#rps-eval-total').text(rows.length);
  modal.find('.rps-grid-factors .rps-cell').each(function() {
    scope.setCell($(this), factors[$(this).attr('data-factor')]);
  });

  const levels = Object.values(factors);
  const level  = levels.length ? scope.bandOf(levels.reduce((sum, value) => sum + value, 0) / levels.length) : 0;
  scope.setBand(modal.find('#rps-eval-band'), level);
  modal.find('#rps-eval-save').prop('disabled', answered === 0);

  scope.refreshActions(factors);
};

/**
 * Seuls les facteurs à traiter (Élevé ou Extrême) ouvrent d'emblée leur champ d'action : les
 * autres restent accessibles d'un clic, et un facteur déjà renseigné ne se replie jamais.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {Object} factors Niveau de chaque facteur coté
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.refreshActions = function(factors) {
  const modal    = $('#rps_eval_modal');
  const expanded = modal.hasClass('rps-actions-expanded');
  let foldable   = 0;

  modal.find('.rps-action-row').each(function() {
    const level  = factors[$(this).attr('data-factor')] || 0;
    const filled = $(this).find('.rps-action-input').val().trim() !== '';
    const always = level >= 3 || filled;

    $(this).toggleClass('is-to-treat', level >= 3).prop('hidden', !always && !expanded);
    if (!always) {
      foldable++;
    }
    window.digiriskdolibarr.psychosocialScope.setCell($(this).find('[data-action-cell]'), level);
  });

  // Le bouton reste là une fois les facteurs dépliés, pour les replier
  const more = modal.find('#rps-actions-more');
  more.prop('hidden', foldable === 0);
  more.find('span').text(expanded ? 'Masquer les facteurs en dessous du seuil' : 'Agir aussi sur un facteur en dessous du seuil (' + foldable + ')');
  more.find('i').toggleClass('fa-chevron-up', expanded).toggleClass('fa-chevron-down', !expanded);
};

/**
 * Déplie ou replie les facteurs en dessous du seuil.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.toggleActions = function() {
  $('#rps_eval_modal').toggleClass('rps-actions-expanded');
  window.digiriskdolibarr.psychosocialScope.refreshAssessment();
};

/**
 * Affiche une erreur dans le pied de la fenêtre d'évaluation.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {string} message Message d'erreur
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.showError = function(message) {
  $('#rps-eval-error').text(message).prop('hidden', false);
};

/**
 * Enregistre l'évaluation : les réponses du volet actif, et les actions saisies, qui
 * deviennent des tâches du projet préfixées du facteur.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.saveAssessment = function() {
  const scope  = window.digiriskdolibarr.psychosocialScope;
  const modal  = $('#rps_eval_modal');
  const button = $(this);
  const answers = [];
  const tasks   = [];

  scope.activePane().find('.rps-grid-row').each(function() {
    const picked = $(this).find('.rps-grid-answer.is-picked');
    if (picked.length) {
      answers.push({
        criterion: parseInt($(this).attr('data-criterion'), 10),
        label: $(this).attr('data-label'),
        sub_category: parseInt($(this).attr('data-sub-category'), 10),
        scale: parseInt(picked.attr('data-scale'), 10)
      });
    }
  });

  if (answers.length === 0) {
    scope.showError('Cotez au moins un critère avant d\'enregistrer.');
    return;
  }

  modal.find('.rps-action-row').each(function() {
    const input = $(this).find('.rps-action-input');
    const label = input.val().trim();
    if (label === '') {
      return;
    }
    tasks.push({
      label: input.attr('data-factor-label') + ' : ' + label,
      owner: parseInt($(this).find('.rps-action-owner').val(), 10) || 0,
      due: $(this).find('.rps-action-due').val()
    });
  });

  $('#rps-eval-error').prop('hidden', true);
  button.prop('disabled', true);

  scope.post({
    action: 'assessment',
    fk_element: parseInt(modal.attr('data-element'), 10),
    date: modal.find('#rps-eval-date').val(),
    comment: modal.find('#rps-eval-comment').val(),
    answers: answers,
    tasks: tasks
  }).then(function(result) {
    if (result.ok) {
      window.location.reload();
      return;
    }
    scope.showError((result.body && result.body.error) ? result.body.error : 'Enregistrement impossible.');
    button.prop('disabled', false);
  }).catch(function() {
    scope.showError('Enregistrement impossible.');
    button.prop('disabled', false);
  });
};

/**
 * Consolide les risques psychosociaux éclatés d'une unité en une seule évaluation.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.consolidate = function() {
  const button = $(this);
  const label  = button.html();

  button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Consolidation...');
  window.digiriskdolibarr.psychosocialScope.post({action: 'consolidate', fk_element: parseInt(button.attr('data-element'), 10)}).then(function(result) {
    if (result.ok) {
      window.location.reload();
      return;
    }
    button.prop('disabled', false).html(label);
    window.alert((result.body && result.body.error) ? result.body.error : 'Consolidation impossible.');
  }).catch(function() {
    button.prop('disabled', false).html(label);
    window.alert('Consolidation impossible.');
  });
};

/* ---------------------------------------------------------------------------
 * Plan d'action de l'unité
 * ------------------------------------------------------------------------ */

/**
 * Enregistre aussitôt une modification d'action, sans recharger la page ni ouvrir la tâche.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery}   item    Ligne de l'action
 * @param  {Object}   payload Champs modifiés
 * @param  {Function} onSaved Appelée une fois l'action enregistrée
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.saveTask = function(item, payload, onSaved) {
  payload.action     = payload.action || 'task_update';
  payload.task_id    = parseInt(item.attr('data-task'), 10);
  payload.fk_element = parseInt(item.closest('.rps-unit-card').attr('data-element'), 10);

  item.addClass('is-saving');
  window.digiriskdolibarr.psychosocialScope.post(payload).then(function(result) {
    item.removeClass('is-saving');
    if (result.ok) {
      if (onSaved) {
        onSaved();
      }
      return;
    }
    window.alert((result.body && result.body.error) ? result.body.error : 'Modification impossible.');
  }).catch(function() {
    item.removeClass('is-saving');
    window.alert('Modification impossible.');
  });
};

/**
 * Recompte les actions faites d'une unité.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} card Bloc de l'unité
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.refreshTaskCount = function(card) {
  const items  = card.find('.rps-action-item');
  const done   = items.filter('.is-done').length;
  const plural = items.length > 1 ? 's' : '';

  card.find('.rps-actions-count').html('<b>' + done + '</b>/' + items.length + ' action' + plural + ' faite' + plural);
};

/**
 * Coche ou décoche une action comme faite.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.saveTaskDone = function() {
  const item = $(this).closest('.rps-action-item');
  const done = $(this).is(':checked');

  window.digiriskdolibarr.psychosocialScope.saveTask(item, {done: done ? 1 : 0}, function() {
    item.toggleClass('is-done', done);
    window.digiriskdolibarr.psychosocialScope.refreshTaskCount(item.closest('.rps-unit-card'));
  });
};

/**
 * Change le responsable d'une action.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.saveTaskOwner = function() {
  window.digiriskdolibarr.psychosocialScope.saveTask($(this).closest('.rps-action-item'), {owner: parseInt($(this).val(), 10) || 0});
};

/**
 * Change l'échéance d'une action.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.saveTaskDue = function() {
  window.digiriskdolibarr.psychosocialScope.saveTask($(this).closest('.rps-action-item'), {due: $(this).val()});
};

/**
 * Supprime une action, après confirmation.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.deleteTask = function() {
  const item  = $(this).closest('.rps-action-item');
  const card  = item.closest('.rps-unit-card');
  const label = item.find('.rps-action-label').text().trim();

  if (!window.confirm('Supprimer l\'action "' + label + '" ?')) {
    return;
  }
  window.digiriskdolibarr.psychosocialScope.saveTask(item, {action: 'task_delete'}, function() {
    item.remove();
    window.digiriskdolibarr.psychosocialScope.refreshTaskCount(card);
  });
};
