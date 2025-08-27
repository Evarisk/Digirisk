
/**
 * Initialise l'objet "navigation" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

window.digiriskdolibarr.navigation = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.navigation.init = function() {
	window.digiriskdolibarr.navigation.event();
};

/**
 * La méthode contenant tous les événements pour la navigation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.navigation.event = function() {
	// Main Menu Digirisk Society
	$( document ).on( 'click', '.digirisk-wrap .navigation-container .toolbar div', window.digiriskdolibarr.navigation.toggleAll );
	$( document ).on( 'click', '#slider', window.digiriskdolibarr.navigation.setUnitActive );
	$( document ).on( 'click', '.side-nav-responsive', window.digiriskdolibarr.navigation.toggleMobileNav );
	$( document ).on( 'click', '.save-organization', window.digiriskdolibarr.navigation.saveOrganization );
};

/**
 * Déplies ou replies tous les éléments enfants
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic
 * @return {void}
 */
window.digiriskdolibarr.navigation.toggleAll = function( event ) {
	event.preventDefault();

	if ( $( this ).hasClass( 'toggle-plus' ) ) {

		$( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
		$( '.digirisk-wrap .navigation-container .workunit-list .unit' ).addClass( 'toggled' );

		// local storage add all
		let MENU = []
		$( '.digirisk-wrap .navigation-container .workunit-list .unit .title' ).get().map(function (v){
			MENU.push($(v).attr('value'))
		})
		localStorage.setItem('menu', JSON.stringify(Array.from(MENU.values())) );
	}

	if ( $( this ).hasClass( 'toggle-minus' ) ) {
		$( '.digirisk-wrap .navigation-container .workunit-list .unit.toggled' ).removeClass( 'toggled' );
		$( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').addClass( 'fa-chevron-right').removeClass( 'fa-chevron-down' );

		// local storage delete all
		let emptyMenu = new Set('0');
		localStorage.setItem('menu', JSON.stringify(Object.values(emptyMenu)) );
	}
};

/**
 * Ajout la classe 'active' à l'élément.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiriskdolibarr.navigation.setUnitActive = function( event ) {

	$( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
	let id = $(this).attr('value');

	$( this ).closest( '.unit' ).addClass( 'active' );
	$( this ).closest( '.unit' ).attr( 'value', id );

};

/**
 * Toggle la classe "active" sur le menu des GP/UT en mobile.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiriskdolibarr.navigation.toggleMobileNav = function( event ) {
	$( this ).closest( '.side-nav' ).find( '#id-left' ).toggleClass( 'active' );
}

/**
 * Permet de sauvegarder l'organisation des groupements et unités de travail
 *
 * @since   8.2.2
 * @version 8.2.2
 */
window.digiriskdolibarr.navigation.saveOrganization = function( event ) {
	let token = window.saturne.toolbox.getToken()

	let idArray     = []
	let parentArray = []
	let id          = 0
	let parent_id   = 0

	//Notices
	let actionContainerSuccess = $('.messageSuccessOrganizationSaved');
	let actionContainerError   = $('.messageErrorOrganizationSaved');

	$('.route').each(function() {
		id = $(this).attr('id')
		parent_id = $(this).parent('ul').attr('id').split(/space/)[1]

		idArray.push(id)
		parentArray.push(parent_id)
	})

	window.saturne.loader.display($(this));

	//ajouter sécurité si le nombre de gp à la fin n'est pas le même qu'en bdd alors on stop tout

	$.ajax({
		url: document.URL + '&action=saveOrganization&ids='+idArray.toString()+'&parent_ids='+parentArray+'&token='+token,
		success: function() {
			actionContainerSuccess.removeClass('hidden');

			$('.wpeo-loader').addClass('button-disable')
			$('.wpeo-loader').attr('style','background: #47e58e !important;border-color: #47e58e !important;')
			$('.wpeo-loader').find('.fas.fa-check').attr('style', '')
			$("a").attr("onclick", "").unbind("click");

			$('.wpeo-loader').removeClass('wpeo-loader')
		},
		error: function ( ) {
			actionContainerError.removeClass('hidden');

			$('.wpeo-loader').addClass('button-disable')
			$('.wpeo-loader').attr('style','background: #e05353 !important;border-color: #e05353 !important;')
			$('.wpeo-loader').find('.fas.fa-times').attr('style', '')

			$('.wpeo-loader').removeClass('wpeo-loader')
		}
	});
}
