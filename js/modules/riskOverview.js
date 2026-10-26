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
 *          the three tabs are in the page, a click shows one without reloading it.
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
