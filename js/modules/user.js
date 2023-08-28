
/**
 * Initialise l'objet "digiriskusers" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiriskdolibarr.digiriskusers = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiriskusers.init = function() {
	window.digiriskdolibarr.digiriskusers.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiriskusers.event = function() {
	$( document ).on( 'input', '.digirisk-users #firstname', window.digiriskdolibarr.digiriskusers.fillEmail );
	$( document ).on( 'input', '.digirisk-users #lastname', window.digiriskdolibarr.digiriskusers.fillEmail );
};

/**
 * Clique sur une des user de la liste.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.digiriskdolibarr.digiriskusers.fillEmail = function( event ) {

	var firstname  = $('.digirisk-users #firstname').val()
	var lastname   = $('.digirisk-users #lastname').val()
	var domainMail = $( '.input-domain-mail' ).val();

	var together = window.digiriskdolibarr.digiriskusers.removeDiacritics( firstname + '.' + lastname + '@' + domainMail ).toLowerCase();

	$('.digirisk-users #email').val(together)
};

/**
 * [description]
 *
 * @memberof EO_Framework_Global
 *
 * @param  {void} input [description]
 * @returns {void}       [description]
 */
window.digiriskdolibarr.digiriskusers.removeDiacritics = function( input ) {
	var output = '';
	var normalized = input.normalize( 'NFD' );
	var i = 0;
	var j = 0;

	while ( i < input.length ) {
		output += normalized[j];

		j += ( input[i] == normalized[j] ) ? 1 : 2;
		i++;
	}

	return output;
};
