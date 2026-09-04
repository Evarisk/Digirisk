
/**
 * Initialise l'objet "digiriskelement" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   9.8.2
 * @version 9.8.2
 */
window.digiriskdolibarr.digiriskelement = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   9.8.2
 * @version 9.8.2
 *
 * @return {void}
 */
window.digiriskdolibarr.digiriskelement.init = function() {
	window.digiriskdolibarr.digiriskelement.event();
};

/**
 * La méthode contenant tous les événements pour le digiriskelement.
 *
 * @since   9.8.2
 * @version 9.8.2
 *
 * @return {void}
 */
window.digiriskdolibarr.digiriskelement.event = function() {
	$( document ).on( 'click', '.select-all-shared-elements-by-digiriskelement', window.digiriskdolibarr.digiriskelement.selectAllSharedElementByDigiriskElement );
	$( document ).on( 'click', '#select_all_shared_elements', window.digiriskdolibarr.digiriskelement.selectAllSharedElements );
};

/**
 * Action select all shared elements by digiriskelement.
 *
 * @since   9.2.0
 * @version 9.8.2
 *
 * @return {void}
 */
window.digiriskdolibarr.digiriskelement.selectAllSharedElementByDigiriskElement = function ( event ) {
	let digiriskelementid = $(this).data('digiriskelement-id');
	let checked           = this.checked;

	$(this).closest('.ui-widget').find('.importsharedelement-digiriskelement-' + digiriskelementid).not(':disabled').each(function() {
		this.checked = checked;
	});
};

/**
 * Action select all shared elements.
 *
 * @since   9.2.0
 * @version 9.8.2
 *
 * @return {void}
 */
window.digiriskdolibarr.digiriskelement.selectAllSharedElements = function ( event ) {
	let checked = this.checked;

	// Les cases de groupe suivent l'état global : les laisser de côté afficherait des groupes
	// décochés au-dessus de lignes cochées
	$(this).closest('.ui-widget').find(':checkbox').not(':disabled').each(function() {
		this.checked = checked;
	});
};
