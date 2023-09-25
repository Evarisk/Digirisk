
/**
 * Initialise l'objet "signature" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiriskdolibarr.document = {};

/**
 * Initialise le canvas document
 *
 * @since   8.1.2
 * @version 8.1.2
 */
window.digiriskdolibarr.document.canvas;

/**
 * Initialise le bouton document
 *
 * @since   8.1.2
 * @version 8.1.2
 */
window.digiriskdolibarr.document.buttonSignature;

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   8.1.2
 * @version 8.1.2
 *
 * @return {void}
 */
window.digiriskdolibarr.document.init = function() {
	window.digiriskdolibarr.document.event();
};

/**
 * La méthode contenant tous les événements pour les documents.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.document.event = function() {
	$( document ).on( 'click', '#builddoc_generatebutton', window.digiriskdolibarr.document.displayLoader );
	$( document ).on( 'click', '.pdf-generation', window.digiriskdolibarr.document.displayLoader );
	$( document ).on( 'click', '.send-risk-assessment-document-by-mail', window.digiriskdolibarr.document.displayLoader );
};

/**
 * Display loader on generation document.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.document.displayLoader = function(  ) {
	window.saturne.loader.display($(this).closest('.div-table-responsive-no-min'));
};
