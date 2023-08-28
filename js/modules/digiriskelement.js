
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
	$( document ).on( 'click', '#select_all_shared_elements_by_digiriskelement', window.digiriskdolibarr.digiriskelement.selectAllSharedElementByDigiriskElement );
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
	let digiriskelementid = $(this).attr('name');
	if(this.checked) {
		// Iterate each checkbox
		$(this).closest('.ui-widget').find('.importsharedelement-digiriskelement-' + digiriskelementid).not(':disabled').each(function() {
			this.checked = true;
		});
	} else {
		$(this).closest('.ui-widget').find('.importsharedelement-digiriskelement-' + digiriskelementid).not(':disabled').each(function() {
			this.checked = false;
		});
	}
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
	if(this.checked) {
		// Iterate each checkbox
		$(this).closest('.ui-widget').find(':checkbox').not(':disabled').not('#select_all_shared_elements_by_digiriskelement').each(function() {
			this.checked = true;
		});
	} else {
		$(this).closest('.ui-widget').find(':checkbox').not(':disabled').not('#select_all_shared_elements_by_digiriskelement').each(function() {
			this.checked = false;
		});
	}
};
