/**
 * Initialise l'objet "button" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   9.8.2
 * @version 9.8.2
 */
window.digiriskdolibarr.button = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.button.init = function() {
	window.digiriskdolibarr.button.event();
};

/**
 * La méthode contenant tous les événements pour les buttons.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.button.event = function() {
	$( document ).on( 'click', '.wpeo-button:submit', window.digiriskdolibarr.button.addLoader );
	$( document ).on( 'click', '.auto-download', window.digiriskdolibarr.button.addLoader );
};

/**
 * Add loader on button
 *
 * @since   9.8.2
 * @version 9.8.2
 *
 * @return {void}
 */
window.digiriskdolibarr.button.addLoader = function() {
	window.saturne.loader.display($(this));
	$(this).toggleClass('button-blue button-disable');
};
