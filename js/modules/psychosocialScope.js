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
  window.digiriskdolibarr.psychosocialScope.restoreDetail();
  window.digiriskdolibarr.psychosocialScope.openFromHash();
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
  $(document).on('click', '.rps-scope-table .rps-tab', scope.selectTab);
  $(document).on('input', '.rps-scope-table .rps-search-input', scope.filter);
  $(document).on('keydown', '.rps-scope-table .rps-search-input', scope.keepEnter);
  $(document).on('change', '.rps-scope-table .rps-group-input', scope.filter);
  $(document).on('change', '.rps-scope-table .rps-select', scope.selectRow);
  $(document).on('change', '.rps-scope-table .rps-select-group', scope.selectGroup);
  $(document).on('change', '.rps-scope-table .rps-select-all', scope.selectAll);
  $(document).on('click', '.rps-scope-table .rps-bulk-clear', scope.clearSelection);
  $(document).on('click', '.rps-scope-table .rps-bulk-apply', scope.bulkApply);
  $(document).on('change', '.rps-scope-table .rps-state-input', scope.qualifyRow);
  $(document).on('click', '.rps-scope-table .rps-reason', scope.editReason);
  $(document).on('mousedown', scope.closeReasonOutside);
  $(document).on('keydown', scope.closeReasonOnEscape);

  // Ligne de l'unité : détail des facteurs et des actions
  $(document).on('click', '.rps-detail-toggle', scope.toggleDetail);
  $(document).on('click', '.rps-task-add-submit', scope.addTask);
  $(document).on('keydown', '.rps-task-add-label', scope.addTaskOnEnter);
  $(document).on('input', '.rps-task-add-label', scope.clearInvalid);

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
 * Libellé d'un état de qualification.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {string} state État : 1, 0 ou -1
 * @return {string}       Libellé de l'état
 */
window.digiriskdolibarr.psychosocialScope.stateLabel = function(state) {
  return {'1': 'Concernée', '0': 'Non concernée', '-1': 'À qualifier'}[String(state)] || '';
};

/**
 * Texte comparé par la recherche, sans accents ni majuscules : « peinture » trouve « Peinture ».
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {string} text Texte d'origine
 * @return {string}      Texte normalisé
 */
window.digiriskdolibarr.psychosocialScope.normalize = function(text) {
  return String(text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
};

/**
 * Recalcule l'en-tête et les compteurs des onglets après un changement de qualification.
 * La cotation du bloc est celle de l'unité la plus sévère, comme côté PHP.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} block Bloc de qualification
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.refreshScope = function(block) {
  const scope  = window.digiriskdolibarr.psychosocialScope;
  const counts = {all: 0, '1': 0, '0': 0, '-1': 0};
  let concerned = 0, assessed = 0, sum = 0, band = 0;

  block.find('.rps-row').each(function() {
    const state = $(this).attr('data-state');
    counts.all++;
    counts[state]++;
    if (state !== '1') {
      return;
    }
    concerned++;
    const average = parseFloat($(this).attr('data-average'));
    if (!isNaN(average)) {
      assessed++;
      sum += average;
      band = Math.max(band, scope.bandOf(average));
    }
  });

  block.find('.rps-tab').each(function() {
    $(this).find('.rps-tab-count').text(counts[$(this).attr('data-filter')] || 0);
  });

  block.find('#rps-average').text(assessed > 0 ? (sum / assessed).toFixed(2).replace('.', ',') : '—');
  block.find('#rps-done').text(assessed);
  block.find('#rps-concerned').text(concerned);
  block.find('#rps-bar').val(concerned > 0 ? Math.round(100 * assessed / concerned) : 0);
  scope.setBand(block.find('#rps-band'), band);
};

/**
 * Applique l'onglet, la recherche et le regroupement : masque les unités écartées, et les
 * groupements dont aucune unité ne reste affichée.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} block Bloc de qualification
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.applyFilters = function(block) {
  const scope   = window.digiriskdolibarr.psychosocialScope;
  const filter  = block.find('.rps-tab.is-on').attr('data-filter') || 'all';
  const term    = scope.normalize(block.find('.rps-search-input').val()).trim();
  const grouped = block.find('.rps-group-input').is(':checked');
  const shown   = {};
  let visible   = 0;

  block.find('.rps-row').each(function() {
    const row  = $(this);
    const show = (filter === 'all' || row.attr('data-state') === filter)
      && (term === '' || scope.normalize(row.attr('data-search')).indexOf(term) !== -1);

    row.toggleClass('rps-hidden', !show);
    if (show) {
      visible++;
      shown[row.attr('data-group')] = true;
    }
  });

  block.find('.rps-group-row').each(function() {
    $(this).toggleClass('rps-hidden', !grouped || !shown[$(this).attr('data-group')]);
  });
  block.find('.rps-empty').toggleClass('rps-hidden', visible > 0);
  scope.refreshSelection(block);
};

/**
 * Change d'onglet : toutes les unités, ou celles d'un état.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.selectTab = function() {
  const block = $(this).closest('.rps-scope');

  block.find('.rps-tab').removeClass('is-on');
  $(this).addClass('is-on');
  window.digiriskdolibarr.psychosocialScope.applyFilters(block);
};

/**
 * Relance le filtre après une saisie dans la recherche ou un changement de regroupement.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.filter = function() {
  window.digiriskdolibarr.psychosocialScope.applyFilters($(this).closest('.rps-scope'));
};

/**
 * Unités cochées, affichées ou non par les filtres.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} block Bloc de qualification
 * @return {jQuery}       Lignes sélectionnées
 */
window.digiriskdolibarr.psychosocialScope.selectedRows = function(block) {
  return block.find('.rps-row').filter(function() {
    return $(this).find('.rps-select').is(':checked');
  });
};

/**
 * Met à jour les cases d'en-tête et la barre d'actions groupées : elle remplace le pied du bloc
 * tant qu'une unité est sélectionnée.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} block Bloc de qualification
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.refreshSelection = function(block) {
  const selected = window.digiriskdolibarr.psychosocialScope.selectedRows(block);
  const allTicked = function(rows) {
    return rows.length > 0 && rows.find('.rps-select:not(:checked)').length === 0;
  };

  block.find('.rps-row').each(function() {
    $(this).toggleClass('is-selected', $(this).find('.rps-select').is(':checked'));
  });
  block.find('.rps-group-row').each(function() {
    $(this).find('.rps-select-group').prop('checked', allTicked(block.find('.rps-row[data-group="' + $(this).attr('data-group') + '"]').not('.rps-hidden')));
  });
  block.find('.rps-select-all').prop('checked', allTicked(block.find('.rps-row').not('.rps-hidden')));

  block.find('.rps-bulk').toggleClass('rps-hidden', selected.length === 0);
  block.find('.rps-foot').toggleClass('rps-hidden', selected.length > 0);
  block.find('.rps-bulk-count').text(selected.length + (selected.length > 1 ? ' unités sélectionnées' : ' unité sélectionnée'));
};

/**
 * Coche ou décoche une unité.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.selectRow = function() {
  window.digiriskdolibarr.psychosocialScope.refreshSelection($(this).closest('.rps-scope'));
};

/**
 * Coche ou décoche les unités affichées d'un groupement.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.selectGroup = function() {
  const block = $(this).closest('.rps-scope');
  const group = $(this).closest('.rps-group-row').attr('data-group');

  block.find('.rps-row[data-group="' + group + '"]').not('.rps-hidden').find('.rps-select').prop('checked', $(this).is(':checked'));
  window.digiriskdolibarr.psychosocialScope.refreshSelection(block);
};

/**
 * Coche ou décoche toutes les unités affichées.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.selectAll = function() {
  const block = $(this).closest('.rps-scope');

  block.find('.rps-row').not('.rps-hidden').find('.rps-select').prop('checked', $(this).is(':checked'));
  window.digiriskdolibarr.psychosocialScope.refreshSelection(block);
};

/**
 * Vide la sélection.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.clearSelection = function() {
  const block = $(this).closest('.rps-scope');

  block.find('.rps-select').prop('checked', false);
  window.digiriskdolibarr.psychosocialScope.refreshSelection(block);
};

/**
 * Affiche un état sur la ligne d'une unité : la feuille de style montre alors ses facteurs,
 * son motif ou le lien d'évaluation.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} row    Ligne de l'unité
 * @param  {string} state  État : 1, 0 ou -1
 * @param  {string} reason Motif de non-exposition
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.setRowState = function(row, state, reason) {
  row.attr('data-state', state);
  row.find('.rps-state-input').val(state);
  row.find('.rps-state-select').attr('data-state', state);
  row.find('.rps-reason-text').text(state === '0' ? reason : '');
};

/**
 * Enregistre la qualification d'une ou plusieurs unités, en un seul appel.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} rows   Lignes des unités
 * @param  {string} state  État : 1, 0 ou -1
 * @param  {string} reason Motif de non-exposition
 * @return {Promise}       Vrai une fois enregistré
 */
window.digiriskdolibarr.psychosocialScope.saveScope = function(rows, state, reason) {
  return window.digiriskdolibarr.psychosocialScope.post({
    action: 'scope',
    fk_elements: rows.map(function() {
      return parseInt($(this).attr('data-element'), 10);
    }).get(),
    concerned: parseInt(state, 10),
    reason: (reason || '').trim()
  }).then(function(result) {
    if (!result.ok) {
      window.alert((result.body && result.body.error) ? result.body.error : 'Qualification non enregistrée.');
    }
    return result.ok;
  }).catch(function() {
    window.alert('Qualification non enregistrée.');
    return false;
  });
};

/**
 * Qualifie des unités : une unité non concernée doit porter un motif, demandé une seule fois
 * pour toute la sélection. Le changement se défait depuis la notification qui le confirme.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} block   Bloc de qualification
 * @param  {jQuery} rows    Lignes des unités
 * @param  {string} state   Nouvel état : 1, 0 ou -1
 * @param  {Object} options anchor : élément sous lequel demander le motif, onCancel : appelée si rien
 *                          n'est enregistré, onSaved : appelée une fois enregistré
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.qualifyRows = function(block, rows, state, options) {
  const scope = window.digiriskdolibarr.psychosocialScope;
  rows = rows.filter(function() {
    return $(this).attr('data-state') !== state;
  });
  if (!rows.length) {
    if (options.onCancel) {
      options.onCancel();
    }
    return;
  }

  const apply = function(reason) {
    const before = rows.map(function() {
      return {row: this, state: $(this).attr('data-state'), reason: $(this).find('.rps-reason-text').text().trim()};
    }).get();

    scope.saveScope(rows, state, reason).then(function(saved) {
      if (!saved) {
        if (options.onCancel) {
          options.onCancel();
        }
        return;
      }
      rows.each(function() {
        scope.setRowState($(this), state, reason);
      });
      if (options.onSaved) {
        options.onSaved();
      }
      scope.refreshScope(block);
      scope.applyFilters(block);

      const subject = rows.length === 1 ? rows.find('.rps-unit-ref').text().trim() + ' · ' + rows.find('.rps-unit-label').text().trim() : rows.length + ' unités';
      scope.toast(subject + ' → ' + scope.stateLabel(state), function() {
        scope.restoreStates(block, before);
      });
    });
  };

  if (state === '0') {
    scope.askReason(options.anchor, block, rows.length, rows.length === 1 ? rows.find('.rps-reason-text').text().trim() : '', apply, options.onCancel);
  } else {
    apply('');
  }
};

/**
 * Remet les unités dans l'état qu'elles avaient avant un changement, motif compris.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} block  Bloc de qualification
 * @param  {Array}  before État et motif de chaque unité avant le changement
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.restoreStates = function(block, before) {
  const scope   = window.digiriskdolibarr.psychosocialScope;
  const batches = {};

  // Un appel par couple état et motif : les unités qui le partagent repartent ensemble
  before.forEach(function(entry) {
    const key = entry.state + '|' + entry.reason;
    batches[key] = batches[key] || {state: entry.state, reason: entry.reason, rows: []};
    batches[key].rows.push(entry.row);
  });

  Object.values(batches).reduce(function(chain, batch) {
    return chain.then(function() {
      return scope.saveScope($(batch.rows), batch.state, batch.reason).then(function(saved) {
        if (saved) {
          $(batch.rows).each(function() {
            scope.setRowState($(this), batch.state, batch.reason);
          });
        }
      });
    });
  }, Promise.resolve()).then(function() {
    scope.refreshScope(block);
    scope.applyFilters(block);
  });
};

/**
 * Qualifie une unité depuis la liste déroulante de sa ligne.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.qualifyRow = function() {
  const select   = $(this);
  const row      = select.closest('.rps-row');
  const previous = row.attr('data-state');

  window.digiriskdolibarr.psychosocialScope.qualifyRows(row.closest('.rps-scope'), row, select.val(), {
    anchor: select,
    onCancel: function() {
      select.val(previous);
    }
  });
};

/**
 * Qualifie toutes les unités sélectionnées.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.bulkApply = function() {
  const scope = window.digiriskdolibarr.psychosocialScope;
  const block = $(this).closest('.rps-scope');

  scope.qualifyRows(block, scope.selectedRows(block), $(this).attr('data-state'), {
    anchor: $(this),
    onSaved: function() {
      block.find('.rps-select').prop('checked', false);
    }
  });
};

/**
 * Modifie le motif d'une unité non concernée.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.editReason = function() {
  const scope = window.digiriskdolibarr.psychosocialScope;
  const row   = $(this).closest('.rps-row');
  const block = row.closest('.rps-scope');

  scope.askReason($(this), block, 1, row.find('.rps-reason-text').text().trim(), function(reason) {
    scope.saveScope(row, '0', reason).then(function(saved) {
      if (saved) {
        scope.setRowState(row, '0', reason);
        scope.toast(row.find('.rps-unit-ref').text().trim() + ' · motif enregistré');
      }
    });
  });
};

/**
 * Demande le motif de non-exposition dans une bulle, sous l'élément qui l'a déclenchée. Les motifs
 * déjà donnés sur ce document unique sont proposés d'un clic.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery}   anchor   Élément sous lequel placer la bulle
 * @param  {jQuery}   block    Bloc de qualification
 * @param  {number}   count    Nombre d'unités concernées par le motif
 * @param  {string}   current  Motif actuel
 * @param  {Function} onOk     Appelée avec le motif validé
 * @param  {Function} onCancel Appelée si la bulle est fermée sans motif
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.askReason = function(anchor, block, count, current, onOk, onCancel) {
  const scope   = window.digiriskdolibarr.psychosocialScope;
  const reasons = {};

  scope.closeReason();
  block.find('.rps-row[data-state="0"] .rps-reason-text').each(function() {
    const reason = $(this).text().trim();
    if (reason !== '') {
      reasons[reason] = true;
    }
  });

  const popup = $('<div class="rps-pop" role="dialog"></div>');
  popup.append($('<div class="rps-pop-title"></div>').text('Motif de non-exposition' + (count > 1 ? ' · ' + count + ' unités' : '')));
  const chips = $('<div class="rps-pop-chips"></div>');
  Object.keys(reasons).slice(0, 6).forEach(function(reason) {
    chips.append($('<button type="button" class="rps-pop-chip"></button>').text(reason));
  });
  if (chips.children().length) {
    popup.append(chips);
  }
  const input = $('<input type="text" class="rps-pop-input" placeholder="Motif, attendu par le document unique">').val(current);
  popup.append(input);
  popup.append('<div class="rps-pop-actions"><button type="button" class="wpeo-button button-grey button-size-small rps-pop-cancel">Annuler</button>'
    + '<button type="button" class="wpeo-button button-blue button-size-small rps-pop-ok">Valider</button></div>');
  $('body').append(popup);

  // Sous l'élément, ou au-dessus quand la place manque en bas de l'écran
  const rect = anchor[0].getBoundingClientRect();
  let top    = rect.bottom + 6;
  if (top + popup.outerHeight() > window.innerHeight - 8) {
    top = Math.max(8, rect.top - popup.outerHeight() - 6);
  }
  popup.css({
    top: top + window.scrollY,
    left: Math.min(Math.max(8, rect.left), window.innerWidth - popup.outerWidth() - 8) + window.scrollX
  });

  scope.reasonPopup = {element: popup, onCancel: onCancel};

  const validate = function() {
    const reason = input.val().trim();
    if (reason === '') {
      input.addClass('is-invalid').trigger('focus');
      return;
    }
    scope.reasonPopup.onCancel = null;
    scope.closeReason();
    onOk(reason);
  };

  popup.on('click', '.rps-pop-chip', function() {
    input.val($(this).text()).removeClass('is-invalid').trigger('focus');
  });
  popup.on('click', '.rps-pop-ok', validate);
  popup.on('click', '.rps-pop-cancel', scope.closeReason);
  input.on('keydown', function(event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      validate();
    }
  });
  input.trigger('focus');
};

/**
 * Ferme la bulle du motif ; fermée sans motif, le changement est abandonné.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {boolean} Vrai si une bulle était ouverte
 */
window.digiriskdolibarr.psychosocialScope.closeReason = function() {
  const popup = window.digiriskdolibarr.psychosocialScope.reasonPopup;
  if (!popup) {
    return false;
  }

  window.digiriskdolibarr.psychosocialScope.reasonPopup = null;
  popup.element.remove();
  if (popup.onCancel) {
    popup.onCancel();
  }
  return true;
};

/**
 * Ferme la bulle du motif sur un clic en dehors.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {Event} event Appui de la souris
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.closeReasonOutside = function(event) {
  if (window.digiriskdolibarr.psychosocialScope.reasonPopup && !$(event.target).closest('.rps-pop').length) {
    window.digiriskdolibarr.psychosocialScope.closeReason();
  }
};

/**
 * Ferme la bulle du motif sur Échap.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {Event} event Touche enfoncée
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.closeReasonOnEscape = function(event) {
  if (event.key === 'Escape') {
    window.digiriskdolibarr.psychosocialScope.closeReason();
  }
};

/**
 * Confirme un enregistrement en bas de l'écran, avec de quoi le défaire.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {string}   message Message affiché
 * @param  {Function} onUndo  Appelée par le bouton Annuler, absent sans elle
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.toast = function(message, onUndo) {
  const scope = window.digiriskdolibarr.psychosocialScope;
  const toast = $('<div class="rps-toast" role="status"></div>').append($('<span></span>').text(message));

  $('.rps-toast').remove();
  clearTimeout(scope.toastTimer);
  if (onUndo) {
    toast.append($('<button type="button" class="rps-toast-undo">Annuler</button>').on('click', function() {
      toast.remove();
      onUndo();
    }));
  }
  $('body').append(toast);
  scope.toastTimer = setTimeout(function() {
    toast.remove();
  }, 6000);
};

/**
 * Suit les liens vers le bloc : #rps-unit-ID sur le groupement de tête montre la ligne de l'unité,
 * #rps-eval sur une unité ouvre son évaluation.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.openFromHash = function() {
  const hash = window.location.hash;

  if (hash === '#rps-eval') {
    // Retiré de l'adresse : recharger la page après l'enregistrement ne doit pas rouvrir l'évaluation
    window.history.replaceState(null, '', window.location.pathname + window.location.search);
    setTimeout(function() {
      $('.rps-open-eval').first().trigger('click');
    }, 0);
    return;
  }

  const match = /^#rps-unit-(\d+)$/.exec(hash);
  const row   = match ? $('.rps-scope-table .rps-row[data-element="' + match[1] + '"]') : $();
  if (!row.length) {
    return;
  }

  const block = row.closest('.rps-scope');
  block.find('.rps-tab').removeClass('is-on').filter('[data-filter="all"]').addClass('is-on');
  block.find('.rps-search-input').val('');
  window.digiriskdolibarr.psychosocialScope.applyFilters(block);
  row[0].scrollIntoView({block: 'center'});
  row.addClass('rps-flash');
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
  const items = card.find('.rps-action-item');
  const done  = items.filter('.is-done').length;

  card.find('.rps-detail-count').text(items.length ? done + '/' + items.length + ' actions' : 'Actions');
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

/* ---------------------------------------------------------------------------
 * Détail de la ligne de l'unité
 * ------------------------------------------------------------------------ */

/**
 * Clé du stockage local qui retient le détail déplié d'une page à l'autre.
 *
 * @since   23.5.1
 * @version 23.5.1
 */
window.digiriskdolibarr.psychosocialScope.detailKey = 'digiriskdolibarr.rpsUnitDetail';

/**
 * Déplie ou replie le détail de la ligne : facteurs nommés et actions de prévention.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery}  card     Bloc de l'unité
 * @param  {boolean} open     Vrai pour déplier
 * @param  {boolean} remember Vrai pour garder ce choix aux prochains affichages
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.showDetail = function(card, open, remember) {
  card.find('.rps-unit-detail').prop('hidden', !open);
  card.find('.rps-detail-toggle').attr('aria-expanded', open ? 'true' : 'false');
  card.find('.rps-detail-chevron').toggleClass('fa-chevron-up', open).toggleClass('fa-chevron-down', !open);

  if (remember) {
    try {
      window.localStorage.setItem(window.digiriskdolibarr.psychosocialScope.detailKey, open ? '1' : '0');
    } catch (error) {
      // Stockage indisponible : le détail se replie simplement au prochain affichage
    }
  }
};

/**
 * Bascule le détail depuis son bouton.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.toggleDetail = function() {
  window.digiriskdolibarr.psychosocialScope.showDetail($(this).closest('.rps-unit-card'), $(this).attr('aria-expanded') !== 'true', true);
};

/**
 * Redéplie le détail s'il l'était à la dernière visite.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.restoreDetail = function() {
  const card = $('.rps-unit-card');
  if (!card.length) {
    return;
  }

  try {
    if (window.localStorage.getItem(window.digiriskdolibarr.psychosocialScope.detailKey) === '1') {
      window.digiriskdolibarr.psychosocialScope.showDetail(card, true, false);
    }
  } catch (error) {
    // Stockage indisponible : le détail reste replié
  }
};

/**
 * Ajoute une action de prévention sur le facteur choisi, sans repasser par l'évaluation.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.addTask = function() {
  const scope  = window.digiriskdolibarr.psychosocialScope;
  const form   = $(this).closest('.rps-task-add');
  const card   = form.closest('.rps-unit-card');
  const input  = form.find('.rps-task-add-label');
  const button = form.find('.rps-task-add-submit');
  const label  = input.val().trim();

  if (label === '') {
    input.addClass('is-invalid').trigger('focus');
    return;
  }

  button.prop('disabled', true);
  scope.post({
    action: 'task_create',
    fk_element: parseInt(card.attr('data-element'), 10),
    factor: parseInt(form.find('.rps-task-add-factor').val(), 10),
    label: label
  }).then(function(result) {
    if (result.ok) {
      // La page rechargée reprend l'action avec son responsable et son échéance modifiables
      scope.showDetail(card, true, true);
      window.location.reload();
      return;
    }
    button.prop('disabled', false);
    window.alert((result.body && result.body.error) ? result.body.error : 'Action non enregistrée.');
  }).catch(function() {
    button.prop('disabled', false);
    window.alert('Action non enregistrée.');
  });
};

/**
 * Entrée dans le libellé ajoute l'action, au lieu d'envoyer le formulaire de la liste des risques
 * qui contient le bloc.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {Event} event Touche enfoncée
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.addTaskOnEnter = function(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
    window.digiriskdolibarr.psychosocialScope.addTask.call(this);
  }
};

/**
 * Entrée dans la recherche ne doit pas envoyer le formulaire de la liste des risques qui
 * contient le bloc : le filtre s'applique déjà à la saisie.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {Event} event Touche enfoncée
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.keepEnter = function(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
  }
};

/**
 * Retire la marque d'erreur d'un champ dès qu'il est saisi.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialScope.clearInvalid = function() {
  $(this).removeClass('is-invalid');
};
