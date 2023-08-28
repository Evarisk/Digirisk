
/**
 * Initialise l'objet "preventionplan" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiriskdolibarr.preventionplan = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplan.init = function() {
	window.digiriskdolibarr.preventionplan.event();
};

/**
 * La méthode contenant tous les événements pour les preventionplans.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplan.event = function() {
	$( document ).on( 'click', '#prior_visit_bool', window.digiriskdolibarr.preventionplan.showDateAndText );
};

/**
 * Show date and text for prevention plan prior visit.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplan.showDateAndText = function() {
	let dateField = $(this).closest('.preventionplan-table').find('.prior_visit_date_field')
	let textField = $(this).closest('.preventionplan-table').find('.prior_visit_text_field')

	if (dateField.hasClass('hidden')) {
		dateField.attr('style', '')
		textField.attr('style', '')
		dateField.removeClass('hidden')
		textField.removeClass('hidden')
	} else {
		dateField.attr('style', 'display:none')
		textField.attr('style', 'display:none')
		dateField.addClass('hidden')
		textField.addClass('hidden')
	}

};
