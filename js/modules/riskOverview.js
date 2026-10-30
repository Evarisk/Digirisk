/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/riskOverview.js
 * \ingroup digiriskdolibarr
 * \brief   Public overview of the risks (public/digiriskelement/digiriskelement_risk_overview.php):
 *          the three tabs are in the page, a click shows one without reloading it; a number of risks
 *          lists them on hover, or on a first touch on a phone.
 */

/**
 * Initialise l'objet "riskOverview" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   23.5.1
 * @version 23.5.1
 */
window.digiriskdolibarr.riskOverview = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.init = function() {
  window.digiriskdolibarr.riskOverview.event();
};

/**
 * La méthode contenant tous les événements de la synthèse des risques.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.event = function() {
  $(document).on('click', '.risk-overview__tab[data-tab]', window.digiriskdolibarr.riskOverview.showTab);
  $(document).on('click', '.risk-overview__toggle', window.digiriskdolibarr.riskOverview.toggleGroupment);
  $(document).on('click', '.risk-overview__fold-all', window.digiriskdolibarr.riskOverview.foldAll);
  $(document).on('click', '.risk-overview__unfold-all', window.digiriskdolibarr.riskOverview.unfoldAll);
  $(document).on('mouseenter focusin', '.risk-overview td.has-risk-tooltip', window.digiriskdolibarr.riskOverview.showRisks);
  $(document).on('mouseleave focusout', '.risk-overview td.has-risk-tooltip', window.digiriskdolibarr.riskOverview.leaveRisks);
  $(document).on('mousemove', '.risk-overview td.has-risk-tooltip', window.digiriskdolibarr.riskOverview.showAfterScroll);
  $(document).on('pointerdown', 'body.digirisk-risk-overview-public', window.digiriskdolibarr.riskOverview.rememberTap);
  $(document).on('click', '.risk-overview td.has-risk-tooltip .risk-overview__link', window.digiriskdolibarr.riskOverview.openOnSecondTap);
  $(document).on('click', '.risk-overview__tooltip', window.digiriskdolibarr.riskOverview.tapTooltip);
  // The table scrolls inside its card: a tooltip left open would no longer face its cell
  document.addEventListener('scroll', window.digiriskdolibarr.riskOverview.hideOnScroll, true);
};

/**
 * Affiche, au survol d'un nombre de risques, les risques qu'il compte, les plus graves en premier.
 * L'infobulle est posée sur la page et non dans la case : le tableau défile dans sa carte, qui la
 * couperait.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.showRisks = function() {
  const template = $(this).children('template.risk-overview__risks')[0];
  if (!template) {
    return;
  }

  let tooltip = $('.risk-overview__tooltip');
  if (!tooltip.length) {
    tooltip = $('<div class="risk-overview__tooltip" role="tooltip" hidden></div>').appendTo('body');
  }
  tooltip.empty().append(document.importNode(template.content, true)).prop('hidden', false);
  // Au doigt, l'infobulle se touche : son lien ouvre la liste, la case qu'elle couvre ne se touche plus
  tooltip.toggleClass('is-touch', window.digiriskdolibarr.riskOverview.touch === true);
  window.digiriskdolibarr.riskOverview.tooltipCell = this;

  // Sous la case, sinon au-dessus ; une infobulle trop haute pour l'un et l'autre se met à côté de la
  // case, qu'elle ne doit pas cacher. Jamais hors de l'écran
  const margin = 8;
  const gap    = 6;
  const cell   = this.getBoundingClientRect();
  const width  = tooltip.outerWidth();
  const height = tooltip.outerHeight();
  const bottom = window.innerHeight - margin;
  const right  = window.innerWidth - margin;
  let top      = cell.bottom + gap;
  let left     = cell.left;

  if (top + height > bottom) {
    top = cell.top - gap - height;
    if (top < margin) {
      top  = cell.top + cell.height / 2 - height / 2;
      left = cell.right + gap + width <= right ? cell.right + gap : cell.left - gap - width;
    }
  }

  tooltip.css({
    top: Math.max(margin, Math.min(top, bottom - height)),
    left: Math.max(margin, Math.min(left, right - width))
  });
};

/**
 * Masque l'infobulle des risques.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.hideRisks = function() {
  $('.risk-overview__tooltip').prop('hidden', true);
};

/**
 * Masque l'infobulle quand le pointeur quitte la case. Ouverte au doigt, elle reste : la souris
 * émulée quitte la case dès que le doigt touche l'infobulle, avant que son lien ne reçoive le clic.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.leaveRisks = function() {
  if (!window.digiriskdolibarr.riskOverview.touch) {
    window.digiriskdolibarr.riskOverview.hideRisks();
  }
};

/**
 * Masque l'infobulle quand la page ou le tableau défile, mais pas quand elle défile elle-même.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {Event} event Défilement
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.hideOnScroll = function(event) {
  if (!$(event.target).closest('.risk-overview__tooltip').length) {
    window.digiriskdolibarr.riskOverview.hideRisks();
  }
};

/**
 * Remet l'infobulle qu'un défilement a fermée quand la souris bouge à nouveau sur la case : le
 * pointeur n'en est pas sorti, aucun survol ne la rouvrirait.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.showAfterScroll = function() {
  const riskOverview = window.digiriskdolibarr.riskOverview;
  const tooltip      = $('.risk-overview__tooltip');

  if (!riskOverview.touch && (!tooltip.length || tooltip.prop('hidden') || riskOverview.tooltipCell !== this)) {
    riskOverview.showRisks.call(this);
  }
};

/**
 * Au doigt, il n'y a pas de survol : le premier toucher d'un nombre affiche ses risques, le second
 * ouvre la liste. Retient, avant que le toucher n'affiche l'infobulle, si elle était déjà ouverte
 * sur cette case ; un toucher hors de la case et de l'infobulle la ferme.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {PointerEvent} event Appui sur la page
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.rememberTap = function(event) {
  const riskOverview = window.digiriskdolibarr.riskOverview;
  const cell         = $(event.target).closest('.risk-overview td.has-risk-tooltip')[0];
  const isOpen       = $('.risk-overview__tooltip').length > 0 && !$('.risk-overview__tooltip').prop('hidden');

  riskOverview.touch        = event.pointerType !== 'mouse';
  riskOverview.tapWhileOpen = isOpen;
  riskOverview.tapOpenCell  = isOpen && cell !== undefined && cell === riskOverview.tooltipCell;

  if (riskOverview.touch && isOpen && !cell && !$(event.target).closest('.risk-overview__tooltip').length) {
    riskOverview.hideRisks();
  }
};

/**
 * Au premier toucher d'un nombre, affiche ses risques au lieu d'ouvrir la liste.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {MouseEvent} event Clic sur le nombre
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.openOnSecondTap = function(event) {
  const riskOverview = window.digiriskdolibarr.riskOverview;

  if (riskOverview.touch && !riskOverview.tapOpenCell) {
    event.preventDefault();
    riskOverview.showRisks.call($(this).closest('td')[0]);
  }
};

/**
 * Ouverte au doigt, l'infobulle se ferme quand on la touche ; son lien ouvre d'abord la liste. Le
 * clic du toucher qui vient de l'ouvrir tombe sur elle quand elle couvre sa case : il est ignoré.
 *
 * @since   23.6.0
 * @version 23.6.0
 *
 * @param  {MouseEvent} event Clic sur l'infobulle
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.tapTooltip = function(event) {
  if (!window.digiriskdolibarr.riskOverview.tapWhileOpen) {
    event.preventDefault();
    return;
  }
  window.digiriskdolibarr.riskOverview.hideRisks();
};

/**
 * Groupements repliés, par identifiant : le même repli vaut pour les trois onglets.
 *
 * @since   23.5.1
 * @version 23.5.1
 */
window.digiriskdolibarr.riskOverview.folded = new Set();

/**
 * Replie ou déplie le groupement de la ligne cliquée.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.toggleGroupment = function() {
  const folded = window.digiriskdolibarr.riskOverview.folded;
  const id     = $(this).closest('tr').attr('data-id');

  if (folded.has(id)) {
    folded.delete(id);
  } else {
    folded.add(id);
  }
  window.digiriskdolibarr.riskOverview.applyFolding($(this).closest('.risk-overview'));
};

/**
 * Replie tous les groupements sauf ceux de tête : il reste la liste des groupements, le groupement
 * de tête (l'entreprise sur la plupart des documents uniques) replié n'en laisserait qu'une ligne.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.foldAll = function() {
  const overview = $(this).closest('.risk-overview');

  overview.find('.risk-overview__card').first().find('.risk-overview__toggle').each(function() {
    if ($(this).closest('th').attr('data-depth') !== '0') {
      window.digiriskdolibarr.riskOverview.folded.add($(this).closest('tr').attr('data-id'));
    }
  });
  window.digiriskdolibarr.riskOverview.applyFolding(overview);
};

/**
 * Déplie tous les groupements.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.unfoldAll = function() {
  window.digiriskdolibarr.riskOverview.folded.clear();
  window.digiriskdolibarr.riskOverview.applyFolding($(this).closest('.risk-overview'));
};

/**
 * Masque, dans chaque onglet, les lignes dont un groupement parent est replié, et met à jour les
 * flèches des groupements.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {jQuery} overview Page de la synthèse
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.applyFolding = function(overview) {
  const folded  = window.digiriskdolibarr.riskOverview.folded;
  const parents = {};

  overview.find('.risk-overview__card').first().find('tbody tr[data-id]').each(function() {
    parents[$(this).attr('data-id')] = $(this).attr('data-parent');
  });

  overview.find('tbody tr[data-id]').each(function() {
    let parent = parents[$(this).attr('data-id')];
    let hidden = false;
    while (parent !== undefined && !hidden) {
      hidden = folded.has(parent);
      parent = parents[parent];
    }
    $(this).prop('hidden', hidden);
  });

  overview.find('.risk-overview__toggle').each(function() {
    const isFolded = folded.has($(this).closest('tr').attr('data-id'));
    $(this).attr('aria-expanded', isFolded ? 'false' : 'true');
    $(this).attr('title', overview.attr(isFolded ? 'data-label-unfold' : 'data-label-fold'));
    $(this).find('i').toggleClass('fa-chevron-right', isFolded).toggleClass('fa-chevron-down', !isFolded);
  });
};

/**
 * Affiche l'onglet cliqué sans recharger la page. Le lien de l'onglet reste dans la barre
 * d'adresse : un lien copié ou une page rechargée rouvre le même onglet.
 *
 * @since   23.5.1
 * @version 23.5.1
 *
 * @param  {MouseEvent} event Clic sur l'onglet
 * @return {void}
 */
window.digiriskdolibarr.riskOverview.showTab = function(event) {
  // Ctrl, Maj ou clic molette : l'onglet s'ouvre dans un nouvel onglet du navigateur, comme un lien
  if (event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1) {
    return;
  }
  event.preventDefault();

  const tab      = $(this).attr('data-tab');
  const overview = $(this).closest('.risk-overview');

  overview.find('.risk-overview__tab').removeClass('is-active');
  $(this).addClass('is-active');
  overview.find('.risk-overview__card[data-tab]').each(function() {
    $(this).prop('hidden', $(this).attr('data-tab') !== tab);
  });

  if (window.history && window.history.replaceState) {
    window.history.replaceState(null, '', $(this).attr('href'));
  }
};
