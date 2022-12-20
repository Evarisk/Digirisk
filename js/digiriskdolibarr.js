/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/digiriskdolibarr.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript file for module DigiriskDolibarr.
 */

/* Javascript library of module DigiriskDolibarr */

'use strict';
/**
 * @namespace EO_Framework_Init
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2021 Eoxia
 */

if ( ! window.eoxiaJS ) {
	/**
	 * [eoxiaJS description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Object}
	 */
	window.eoxiaJS = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.eoxiaJS.scriptsLoaded = false;
}

if ( ! window.eoxiaJS.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.init = function() {
		window.eoxiaJS.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.load_list_script = function() {
		if ( ! window.eoxiaJS.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.eoxiaJS ) {

				if ( window.eoxiaJS[key].init ) {
					window.eoxiaJS[key].init();
				}

				for ( slug in window.eoxiaJS[key] ) {

					if ( window.eoxiaJS[key] && window.eoxiaJS[key][slug] && window.eoxiaJS[key][slug].init ) {
						window.eoxiaJS[key][slug].init();
					}

				}
			}

			window.eoxiaJS.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.eoxiaJS ) {
			if ( window.eoxiaJS[key].refresh ) {
				window.eoxiaJS[key].refresh();
			}

			for ( slug in window.eoxiaJS[key] ) {

				if ( window.eoxiaJS[key] && window.eoxiaJS[key][slug] && window.eoxiaJS[key][slug].refresh ) {
					window.eoxiaJS[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.eoxiaJS.init );
}

/**
 * Initialise l'objet "navigation" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

window.eoxiaJS.navigation = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.navigation.init = function() {
	window.eoxiaJS.navigation.event();
};

/**
 * La méthode contenant tous les événements pour la navigation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.navigation.event = function() {
	// Main Menu Digirisk Society
	$( document ).on( 'click', '.toggle-unit', window.eoxiaJS.navigation.switchToggle );
	$( document ).on( 'click', '#newGroupment', window.eoxiaJS.navigation.switchToggle );
	$( document ).on( 'click', '#newWorkunit', window.eoxiaJS.navigation.switchToggle );
	$( document ).on( 'click', '.digirisk-wrap .navigation-container .toolbar div', window.eoxiaJS.navigation.toggleAll );
	$( document ).on( 'click', '#slider', window.eoxiaJS.navigation.setUnitActive );
	$( document ).on( 'click', '#newGroupment', window.eoxiaJS.navigation.redirect );
	$( document ).on( 'click', '#newWorkunit', window.eoxiaJS.navigation.redirect );
	$( document ).on( 'click', '.side-nav-responsive', window.eoxiaJS.navigation.toggleMobileNav );
	$( document ).on( 'click', '.save-organization', window.eoxiaJS.navigation.saveOrganization );
};

/**
 * Gestion du toggle dans la navigation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.navigation.switchToggle = function( event ) {
	event.preventDefault();

	var MENU = localStorage.menu
	if (MENU == null || MENU == '') {
		MENU = new Set()
	} else {
		MENU = JSON.parse(MENU)
		MENU = new Set(MENU)
	}

	if ( $( this ).find( '.toggle-icon' ).hasClass( 'fa-chevron-down' ) ) {

		$(this).find( '.toggle-icon' ).removeClass('fa-chevron-down').addClass('fa-chevron-right');
		var idUnToggled = $(this).closest('.unit').attr('id').split('unit')[1]
		$(this).closest('.unit').removeClass('toggled');

		MENU.delete(idUnToggled)
		localStorage.setItem('menu',  JSON.stringify(Array.from(MENU.keys())))

	} else if ( $( this ).find( '.toggle-icon' ).hasClass( 'fa-chevron-right' ) ){

		$(this).find( '.toggle-icon' ).removeClass('fa-chevron-right').addClass('fa-chevron-down');
		$(this).closest('.unit').addClass('toggled');

		var idToggled = $(this).closest('.unit').attr('id').split('unit')[1]
		MENU.add(idToggled)
		localStorage.setItem('menu',  JSON.stringify(Array.from(MENU.keys())))
	}

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
window.eoxiaJS.navigation.toggleAll = function( event ) {
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
window.eoxiaJS.navigation.setUnitActive = function( event ) {

	$( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
	let id = $(this).attr('value');

	$( this ).closest( '.unit' ).addClass( 'active' );
	$( this ).closest( '.unit' ).attr( 'value', id );

};

/**
 * Redirection sur l'élément courant.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {booleen}
 */
window.eoxiaJS.navigation.redirect = function( event ) {
	var URLToGo = '';
	var params = new window.URLSearchParams(window.location.search);
	var id = $(params.get('id'))

	//get ID from div selected in left menu
	history.pushState({ path:  document.URL}, '', this.href);
	//change URL without refresh
	if (!id) {
		URLToGo = document.URL.split('?id=')[0];
	} else {
		URLToGo = document.URL;
	}
	$( this ).closest( '.side-nav' ).find( '#id-left' ).removeClass( 'active' );

	//empty and fill object card
	$.ajax({
		url: URLToGo,
		success: function( resp ) {
			$('#cardContent').html($(resp).find('#cardContent'))
		},
	});
	return false;
};

/**
 * Toggle la classe "active" sur le menu des GP/UT en mobile.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.navigation.toggleMobileNav = function( event ) {
	$( this ).closest( '.side-nav' ).find( '#id-left' ).toggleClass( 'active' );
}

/**
 * Permet de sauvegarder l'organisation des groupements et unités de travail
 *
 * @since   8.2.2
 * @version 8.2.2
 */
window.eoxiaJS.navigation.saveOrganization = function( event ) {

	let idArray = []
	let parentArray = []
	let id = 0
	let parent_id = 0

	//Notices
	let actionContainerSuccess = $('.messageSuccessOrganizationSaved');
	let actionContainerError = $('.messageErrorOrganizationSaved');
	let token = $('input[name="token"]').val();

	$('.route').each(function() {
		id = $(this).attr('id')
		parent_id = $(this).parent('ul').attr('id').split(/space/)[1]

		idArray.push(id)
		parentArray.push(parent_id)
	})

	window.eoxiaJS.loader.display($(this));

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


/**
 * Initialise l'objet "modal" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.modal = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.modal.init = function() {
	window.eoxiaJS.modal.event();
};

/**
 * La méthode contenant tous les événements pour la modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.modal.event = function() {
	$( document ).on( 'click', '.modal-close', window.eoxiaJS.modal.closeModal );
	$( document ).on( 'click', '.modal-open', window.eoxiaJS.modal.openModal );
	$( document ).on( 'click', '.modal-refresh', window.eoxiaJS.modal.refreshModal );
};

/**
 * Open Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.openModal = function ( event ) {
	let idSelected = $(this).attr('value');
	if (document.URL.match(/#/)) {
		var urlWithoutTag = document.URL.split(/#/)[0]
	} else {
		var urlWithoutTag = document.URL
	}
	history.pushState({ path:  document.URL}, '', urlWithoutTag);

	// Open modal evaluation.
	if ($(this).hasClass('risk-evaluation-add')) {
		$('#risk_evaluation_add'+idSelected).addClass('modal-active');
		$('.risk-evaluation-create'+idSelected).attr('value', idSelected);
	} else if ($(this).hasClass('risk-evaluation-list')) {
		$('#risk_evaluation_list' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('open-media-gallery')) {
		$('#media_gallery').addClass('modal-active');
		$('#media_gallery').attr('value', idSelected);
		$('#media_gallery').find('.type-from').attr('value', $(this).find('.type-from').val());
		$('#media_gallery').find('.wpeo-button').attr('value', idSelected);
		$('#media_gallery').find('.clicked-photo').attr('style', '')
		$('#media_gallery').find('.clicked-photo').removeClass('clicked-photo')
	} else if ($(this).hasClass('risk-evaluation-edit')) {
		$('#risk_evaluation_edit' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('evaluator-add')) {
		$('#evaluator_add' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('open-medias-linked') && $(this).hasClass('digirisk-element')) {
		$('#digirisk_element_medias_modal_' + idSelected).addClass('modal-active');
	}

	// Open modal risk.
	if ($(this).hasClass('risk-add')) {
		$('#risk_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risk-edit')) {
		$('#risk_edit' + idSelected).addClass('modal-active');
	}

	// Open modal riskassessment task.
	if ($(this).hasClass('riskassessment-task-add')) {
		$('#risk_assessment_task_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-edit')) {
		$('#risk_assessment_task_edit' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-list')) {
		$('#risk_assessment_task_list' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-timespent-edit')) {
		$('#risk_assessment_task_timespent_edit' + idSelected).addClass('modal-active');
	}

	// Open modal risksign.
	if ($(this).hasClass('risksign-add')) {
		$('#risksign_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risksign-edit')) {
		$('#risksign_edit' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risksign-photo')) {
		$(this).closest('.risksign-photo-container').find('#risksign_photo' + idSelected).addClass('modal-active');
	}

	// Open modal signature.
	if ($(this).hasClass('modal-signature-open')) {
		$('#modal-signature' + idSelected).addClass('modal-active');
		window.eoxiaJS.signature.modalSignatureOpened( $(this) );
	}

	// Open modal patch note.
	if ($(this).hasClass('show-patchnote')) {
		$('.fiche .wpeo-modal-patchnote').addClass('modal-active');
	}

	$('.notice').addClass('hidden');
};

/**
 * Close Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.closeModal = function ( event ) {
	if ($(event.target).hasClass('modal-active') || $(event.target).hasClass('modal-close') || $(event.target).parent().hasClass('modal-close')) {
		$(this).closest('.modal-active').removeClass('modal-active')
		$('.clicked-photo').attr('style', '');
		$('.clicked-photo').removeClass('clicked-photo');
		$('.notice').addClass('hidden');
	}
};

/**
 * Refresh Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.refreshModal = function ( event ) {
	window.location.reload();
};

// Dropdown
/**
 * [dropdown description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @type {Object}
 */
window.eoxiaJS.dropdown = {};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @returns {void} [description]
 */
window.eoxiaJS.dropdown.init = function() {
	window.eoxiaJS.dropdown.event();
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @returns {void} [description]
 */
window.eoxiaJS.dropdown.event = function() {
	$( document ).on( 'keyup', window.eoxiaJS.dropdown.keyup );
	$( document ).on( 'keypress', window.eoxiaJS.dropdown.keypress );
	$( document ).on( 'click', '.wpeo-dropdown:not(.dropdown-active) .dropdown-toggle:not(.disabled)', window.eoxiaJS.dropdown.open );
	$( document ).on( 'click', '.wpeo-dropdown.dropdown-active .dropdown-content', function(e) { e.stopPropagation() } );
	$( document ).on( 'click', '.wpeo-dropdown.dropdown-active:not(.dropdown-force-display) .dropdown-content .dropdown-item', window.eoxiaJS.dropdown.close  );
	$( document ).on( 'click', '.wpeo-dropdown.dropdown-active', function ( e ) { window.eoxiaJS.dropdown.close( e ); e.stopPropagation(); } );
	$( document ).on( 'click', 'body', window.eoxiaJS.dropdown.close );
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.keyup = function( event ) {
	if ( 27 === event.keyCode ) {
		window.eoxiaJS.dropdown.close();
	}
}

/**
 * Do a barrel roll!
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.keypress = function( event ) {

	let currentString  = localStorage.currentString ? localStorage.currentString : ''
	let keypressNumber = localStorage.keypressNumber ? +localStorage.keypressNumber : 0

	currentString += event.keyCode
	keypressNumber += +1

	localStorage.setItem('currentString', currentString)
	localStorage.setItem('keypressNumber', keypressNumber)

	if (keypressNumber > 9) {
		localStorage.setItem('currentString', '')
		localStorage.setItem('keypressNumber', 0)
	}

	if (currentString === '9897114114101108114111108108') {
		var a="-webkit-",
			b='transform:rotate(1turn);',
			c='transition:4s;';

		document.head.innerHTML += '<style>body{' + a + b + a + c + b + c
	}
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.open = function( event ) {
	var triggeredElement = $( this );
	var angleElement = triggeredElement.find('[data-fa-i2svg]');
	var callbackData = {};
	var key = undefined;

	window.eoxiaJS.dropdown.close( event, $( this ) );

	if ( triggeredElement.attr( 'data-action' ) ) {
		window.eoxiaJS.loader.display( triggeredElement );

		triggeredElement.get_data( function( data ) {
			for ( key in callbackData ) {
				if ( ! data[key] ) {
					data[key] = callbackData[key];
				}
			}

			window.eoxiaJS.request.send( triggeredElement, data, function( element, response ) {
				triggeredElement.closest( '.wpeo-dropdown' ).find( '.dropdown-content' ).html( response.data.view );

				triggeredElement.closest( '.wpeo-dropdown' ).addClass( 'dropdown-active' );

				/* Toggle Button Icon */
				if ( angleElement ) {
					window.eoxiaJS.dropdown.toggleAngleClass( angleElement );
				}
			} );
		} );
	} else {
		triggeredElement.closest( '.wpeo-dropdown' ).addClass( 'dropdown-active' );

		/* Toggle Button Icon */
		if ( angleElement ) {
			window.eoxiaJS.dropdown.toggleAngleClass( angleElement );
		}
	}

	event.stopPropagation();
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.close = function( event ) {
	var _element = $( this );
	$( '.wpeo-dropdown.dropdown-active:not(.no-close)' ).each( function() {
		var toggle = $( this );
		var triggerObj = {
			close: true
		};

		_element.trigger( 'dropdown-before-close', [ toggle, _element, triggerObj ] );

		if ( triggerObj.close ) {
			toggle.removeClass( 'dropdown-active' );

			/* Toggle Button Icon */
			var angleElement = $( this ).find('.dropdown-toggle').find('[data-fa-i2svg]');
			if ( angleElement ) {
				window.eoxiaJS.dropdown.toggleAngleClass( angleElement );
			}
		} else {
			return;
		}
	});
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {jQuery} button [description]
 * @returns {void}        [description]
 */
window.eoxiaJS.dropdown.toggleAngleClass = function( button ) {
	if ( button.hasClass('fa-caret-down') || button.hasClass('fa-caret-up') ) {
		button.toggleClass('fa-caret-down').toggleClass('fa-caret-up');
	}
	else if ( button.hasClass('fa-caret-circle-down') || button.hasClass('fa-caret-circle-up') ) {
		button.toggleClass('fa-caret-circle-down').toggleClass('fa-caret-circle-up');
	}
	else if ( button.hasClass('fa-angle-down') || button.hasClass('fa-angle-up') ) {
		button.toggleClass('fa-angle-down').toggleClass('fa-angle-up');
	}
	else if ( button.hasClass('fa-chevron-circle-down') || button.hasClass('fa-chevron-circle-up') ) {
		button.toggleClass('fa-chevron-circle-down').toggleClass('fa-chevron-circle-up');
	}
};

/**
 * @namespace EO_Framework_Tooltip
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2018 Eoxia
 */

if ( ! window.eoxiaJS.tooltip ) {

	/**
	 * [tooltip description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @type {Object}
	 */
	window.eoxiaJS.tooltip = {};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.tooltip.init = function() {
		window.eoxiaJS.tooltip.event();
	};

	window.eoxiaJS.tooltip.tabChanged = function() {
		$( '.wpeo-tooltip' ).remove();
	}

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.tooltip.event = function() {
		$( document ).on( 'mouseenter touchstart', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onEnter );
		$( document ).on( 'mouseleave touchend', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onOut );
	};

	window.eoxiaJS.tooltip.onEnter = function( event ) {
		window.eoxiaJS.tooltip.display( $( this ) );
	};

	window.eoxiaJS.tooltip.onOut = function( event ) {
		window.eoxiaJS.tooltip.remove( $( this ) );
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @param  {void} event [description]
	 * @returns {void}       [description]
	 */
	window.eoxiaJS.tooltip.display = function( element ) {
		var direction = ( $( element ).data( 'direction' ) ) ? $( element ).data( 'direction' ) : 'top';
		var el = $( '<span class="wpeo-tooltip tooltip-' + direction + '">' + $( element ).attr( 'aria-label' ) + '</span>' );
		var pos = $( element ).position();
		var offset = $( element ).offset();
		$( element )[0].tooltipElement = el;
		$( 'body' ).append( $( element )[0].tooltipElement );

		if ( $( element ).data( 'color' ) ) {
			el.addClass( 'tooltip-' + $( element ).data( 'color' ) );
		}

		var top = 0;
		var left = 0;

		switch( $( element ).data( 'direction' ) ) {
			case 'left':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( $( element ).outerHeight() / 2 ) ) + 'px';
				left = ( offset.left - el.outerWidth() - 10 ) + 3 + 'px';
				break;
			case 'right':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( $( element ).outerHeight() / 2 ) ) + 'px';
				left = offset.left + $( element ).outerWidth() + 8 + 'px';
				break;
			case 'bottom':
				top = ( offset.top + $( element ).height() + 10 ) + 10 + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
			case 'top':
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
			default:
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( $( element ).outerWidth() / 2 ) ) + 'px';
				break;
		}

		el.css( {
			'top': top,
			'left': left,
			'opacity': 1
		} );

		$( element ).on("remove", function() {
			$( $( element )[0].tooltipElement ).remove();

		} );
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @param  {void} event [description]
	 * @returns {void}       [description]
	 */
	window.eoxiaJS.tooltip.remove = function( element ) {
		if ( $( element )[0] && $( element )[0].tooltipElement ) {
			$( $( element )[0].tooltipElement ).remove();
		}
	};
}

/**
 * @namespace EO_Framework_Loader
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2018 Eoxia
 */

/*
 * Gestion du loader.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! window.eoxiaJS.loader ) {

	/**
	 * [loader description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @type {Object}
	 */
	window.eoxiaJS.loader = {};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.loader.init = function() {
		window.eoxiaJS.loader.event();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.loader.event = function() {
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @param  {void} element [description]
	 * @returns {void}         [description]
	 */
	window.eoxiaJS.loader.display = function( element ) {
		// Loader spécial pour les "button-progress".
		if ( element.hasClass( 'button-progress' ) ) {
			element.addClass( 'button-load' )
		} else {
			element.addClass( 'wpeo-loader' );
			var el = $( '<span class="loader-spin"></span>' );
			element[0].loaderElement = el;
			element.append( element[0].loaderElement );
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @param  {jQuery} element [description]
	 * @returns {void}         [description]
	 */
	window.eoxiaJS.loader.remove = function( element ) {
		if ( 0 < element.length && ! element.hasClass( 'button-progress' ) ) {
			element.removeClass( 'wpeo-loader' );

			$( element[0].loaderElement ).remove();
		}
	};
}

/**
 * Initialise l'objet "signature" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature = {};

/**
 * Initialise le canvas signature
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature.canvas;

/**
 * Initialise le boutton signature
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature.buttonSignature;

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.init = function() {
	window.eoxiaJS.signature.event();
};

/**
 * La méthode contenant tous les événements pour la signature.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.event = function() {
	$( document ).on( 'click', '.signature-erase', window.eoxiaJS.signature.clearCanvas );
	$( document ).on( 'click', '.signature-validate', window.eoxiaJS.signature.createSignature );
	$( document ).on( 'click', '.auto-download', window.eoxiaJS.signature.autoDownloadSpecimen );
};

/**
 * Open modal signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.modalSignatureOpened = function( triggeredElement ) {
	window.eoxiaJS.signature.buttonSignature = triggeredElement;

	var ratio =  Math.max( window.devicePixelRatio || 1, 1 );

	window.eoxiaJS.signature.canvas = document.querySelector('#modal-signature' + triggeredElement.attr('value') + ' canvas' );

	window.eoxiaJS.signature.canvas.signaturePad = new SignaturePad( window.eoxiaJS.signature.canvas, {
		penColor: "rgb(0, 0, 0)"
	} );

	window.eoxiaJS.signature.canvas.width = window.eoxiaJS.signature.canvas.offsetWidth * ratio;
	window.eoxiaJS.signature.canvas.height = window.eoxiaJS.signature.canvas.offsetHeight * ratio;
	window.eoxiaJS.signature.canvas.getContext( "2d" ).scale( ratio, ratio );
	window.eoxiaJS.signature.canvas.signaturePad.clear();

	var signature_data = $( '#signature_data' + triggeredElement.attr('value') ).val();
	window.eoxiaJS.signature.canvas.signaturePad.fromDataURL(signature_data);
};

/**
 * Action Clear sign
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.clearCanvas = function( event ) {
	var canvas = $( this ).closest( '.modal-signature' ).find( 'canvas' );
	canvas[0].signaturePad.clear();
};

/**
 * Action create signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.createSignature = function() {
	let elementSignatory = $(this).attr('value');
	let elementRedirect  = '';
	let elementCode = '';
	let elementZone  = $(this).find('#zone' + elementSignatory).attr('value');
	let elementConfCAPTCHA  = $('#confCAPTCHA').val();
	let actionContainerSuccess = $('.noticeSignatureSuccess');
	var signatoryIDPost = '';
	if (elementSignatory !== 0) {
		signatoryIDPost = '&signatoryID=' + elementSignatory;
	}

	if ( ! $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].signaturePad.isEmpty() ) {
		var signature = $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].toDataURL();
	}

	let token = $('.modal-signature').find('input[name="token"]').val();

	var url = '';
	var type = '';
	if (elementZone == "private") {
		url = document.URL + '&action=addSignature' + signatoryIDPost + '&token=' + token;
		type = "POST"
	} else {
		url = document.URL + '&action=addSignature' + signatoryIDPost + '&token=' + token;
		type = "POST";
	}

	if (elementConfCAPTCHA == 1) {
		elementCode = $('#securitycode').val();
		let elementSessionCode = $('#sessionCode').val();
		if (elementSessionCode != elementCode) {
			elementRedirect = $('#redirectSignatureError').val();
		}
	} else {
		elementRedirect = $(this).find('#redirect' + elementSignatory).attr('value');
	}

	$.ajax({
		url: url,
		type: type,
		processData: false,
		contentType: 'application/octet-stream',
		data: JSON.stringify({
			signature: signature,
			code: elementCode
		}),
		success: function( resp ) {
			if (elementZone == "private") {
				actionContainerSuccess.html($(resp).find('.noticeSignatureSuccess .all-notice-content'));
				actionContainerSuccess.removeClass('hidden');
				$('.signatures-container').html($(resp).find('.signatures-container'));
			} else {
				window.location.replace(elementRedirect);
			}
		},
		error: function ( ) {
			alert('Error')
		}
	});
};

/**
 * Download signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.download = function(fileUrl, filename) {
	var a = document.createElement("a");
	a.href = fileUrl;
	a.setAttribute("download", filename);
	a.click();
}

/**
 * Auto Download signature specimen
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.autoDownloadSpecimen = function( event ) {
	let element = $(this).closest('.file-generation')
	let token = $('.digirisk-signature-container').find('input[name="token"]').val();
	let url = document.URL + '&action=builddoc&token=' + token
	$.ajax({
		url: url,
		type: "POST",
		success: function ( ) {
			let filename = element.find('.specimen-name').attr('value')
			let path = element.find('.specimen-path').attr('value')

			window.eoxiaJS.signature.download(path + filename, filename);
			$.ajax({
				url: document.URL + '&action=remove_file&token=' + token,
				type: "POST",
				success: function ( ) {
				},
				error: function ( ) {
				}
			});
		},
		error: function ( ) {
		}
	});
};

/**
 * Initialise l'objet "mediaGallery" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 8.2.0
 */
window.eoxiaJS.mediaGallery = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 8.2.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.init = function() {
	window.eoxiaJS.mediaGallery.event();
};

/**
 * La méthode contenant tous les événements pour le mediaGallery.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.event = function() {
	// Photos
	$( document ).on( 'click', '.clickable-photo', window.eoxiaJS.mediaGallery.selectPhoto );
	$( document ).on( 'click', '.save-photo', window.eoxiaJS.mediaGallery.savePhoto );
	$( document ).on( 'change', '.flat.minwidth400.maxwidth200onsmartphone', window.eoxiaJS.mediaGallery.sendPhoto );
	$( document ).on( 'click', '.clicked-photo-preview', window.eoxiaJS.mediaGallery.previewPhoto );
	$( document ).on( 'input', '.form-element #search_in_gallery', window.eoxiaJS.mediaGallery.handleSearch );
	$( document ).on( 'click', '.media-gallery-unlink', window.eoxiaJS.mediaGallery.unlinkFile );
	$( document ).on( 'click', '.media-gallery-favorite', window.eoxiaJS.mediaGallery.addToFavorite );
}

/**
 * Select photo.
 *
 * @since   8.2.0
 * @version 8.2.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.selectPhoto = function( event ) {
	let photoID = $(this).attr('value');
	let parent = $(this).closest('.modal-content')

	if ($(this).hasClass('clicked-photo')) {
		$(this).attr('style', 'none !important')
		$(this).removeClass('clicked-photo')

		if ($('.clicked-photo').length === 0) {
			$(this).closest('.modal-container').find('.save-photo').addClass('button-disable');
		}

	} else {
		parent.closest('.modal-container').find('.save-photo').removeClass('button-disable');

		parent.find('.clickable-photo'+photoID).attr('style', 'border: 5px solid #0d8aff !important');
		parent.find('.clickable-photo'+photoID).addClass('clicked-photo');
	}
};

/**
 * Action save photo to an object.
 *
 * @since   8.2.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.savePhoto = function( event ) {
	let parent = $('#media_gallery')
	let idToSave = $(this).attr('value')
	let mediaGalleryModal = $(this).closest('.modal-container')
	let filesLinked = mediaGalleryModal.find('.clicked-photo')
	let modalFrom = $('.modal-active:not(.modal-photo)')

	let riskId = modalFrom.attr('value')
	let mediaLinked = ''
	let type = $(this).find('.type-from').val()

	var params = new window.URLSearchParams(window.location.search);
	var currentElementID = params.get('id')

	let filenames = ''
	if (filesLinked.length > 0) {
		filesLinked.each(function(  ) {
			filenames += $( this ).find('.filename').val() + 'vVv'
		});
	}
	let favorite = filenames
	favorite = favorite.split('vVv')[0]

	window.eoxiaJS.loader.display($(this));

	let token = $('.id-container').find('input[name="token"]').val();

	if (type === 'riskassessment') {
		mediaLinked = modalFrom.find('.element-linked-medias')
		window.eoxiaJS.loader.display(mediaLinked);

		let riskAssessmentPhoto = ''
		riskAssessmentPhoto = $('.risk-evaluation-photo-'+idToSave+'.risk-'+riskId)

		let filepath = modalFrom.find('.risk-evaluation-photo-single .filepath-to-riskassessment').val()
		let thumbName = window.eoxiaJS.file.getThumbName(favorite)
		let newPhoto = filepath + thumbName

		$.ajax({
			url: document.URL + "&action=addFiles&token=" + token +'&favorite='+favorite,
			type: "POST",
			data: JSON.stringify({
				risk_id: riskId,
				riskassessment_id: idToSave,
				filenames: filenames,
			}),
			processData: false,
			contentType: false,
			success: function ( resp ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				parent.removeClass('modal-active')

				newPhoto = newPhoto.replace(/\ /g, '%20')
				newPhoto = newPhoto.replace(/\(/g, '%28')
				newPhoto = newPhoto.replace(/\)/g, '%29')
				newPhoto = newPhoto.replace(/\+/g, '%2B')

				//Update risk assessment main img in "photo" of risk assessment modal
				riskAssessmentPhoto.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',newPhoto)
					$(this).find('.filename').attr('value', favorite)
					$(this).find('.clicked-photo-preview').hasClass('photodigiriskdolibarr') ? $(this).find('.clicked-photo-preview').removeClass('photodigiriskdolibarr').addClass('photo') : 0
				});

				//Remove special chars from img
				favorite = favorite.replace(/\ /g, '%20')
				favorite = favorite.replace(/\(/g, '%28')
				favorite = favorite.replace(/\)/g, '%29')
				favorite = favorite.replace(/\+/g, '%2B')

				mediaLinked.html($(resp).find('.element-linked-medias-'+idToSave+'.risk-'+riskId).first())

				modalFrom.find('.messageSuccessSavePhoto').removeClass('hidden')
			},
			error: function ( ) {
				modalFrom.find('.messageErrorSavePhoto').removeClass('hidden')
			}
		});

	} else if (type === 'digiriskelement') {
		mediaLinked = $('#digirisk_element_medias_modal_'+idToSave).find('.element-linked-medias')
		window.eoxiaJS.loader.display(mediaLinked);

		let digiriskElementPhoto = ''
		digiriskElementPhoto = $('.digirisk-element-'+idToSave).find('.clicked-photo-preview')

		let filepath = $('.digirisk-element-'+idToSave).find('.filepath-to-digiriskelement').val()
		let thumbName = window.eoxiaJS.file.getThumbName(favorite)
		let newPhoto = filepath + thumbName

		$.ajax({
			url: document.URL + "&action=addDigiriskElementFiles&token=" + token,
			type: "POST",
			data: JSON.stringify({
				digiriskelement_id: idToSave,
				filenames: filenames,
			}),
			processData: false,
			contentType: false,
			success: function ( resp ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				parent.removeClass('modal-active')

				newPhoto = newPhoto.replace(/\ /g, '%20')
				newPhoto = newPhoto.replace(/\(/g, '%28')
				newPhoto = newPhoto.replace(/\)/g, '%29')
				newPhoto = newPhoto.replace(/\+/g, '%2B')

				digiriskElementPhoto.attr('src',newPhoto )

				let photoContainer = digiriskElementPhoto.closest('.open-media-gallery')
				photoContainer.removeClass('open-media-gallery')
				photoContainer.addClass('open-medias-linked')
				photoContainer.addClass('digirisk-element')
				photoContainer.closest('.unit-container').find('.digirisk-element-medias-modal').load(document.URL+ ' #digirisk_element_medias_modal_'+idToSave)

				favorite = favorite.replace(/\ /g, '%20')
				favorite = favorite.replace(/\(/g, '%28')
				favorite = favorite.replace(/\)/g, '%29')
				favorite = favorite.replace(/\+/g, '%2B')

				if (idToSave === currentElementID) {
					let digiriskBanner = $('.arearef.heightref')
					digiriskBanner.load(document.URL+'&favorite='+favorite + ' .arearef.heightref')
				}
				mediaLinked.load(document.URL+'&favorite='+favorite + ' .element-linked-medias-'+idToSave+'.digirisk-element')
				modalFrom.find('.messageSuccessSavePhoto').removeClass('hidden')
			},
			error: function ( ) {
				modalFrom.find('.messageErrorSavePhoto').removeClass('hidden')
			}
		});
	}
};

/**
 * Action handle search in medias
 *
 * @since   8.2.0
 * @version 8.2.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.handleSearch = function( event ) {
	let searchQuery = $('#search_in_gallery').val()
	let photos = $('.center.clickable-photo')

	photos.each(function(  ) {
		$( this ).text().trim().match(searchQuery) ? $(this).show() : $(this).hide()
	});
};

/**
 * Action send photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.sendPhoto = function( event ) {

	event.preventDefault()
	let files    = $(this).prop("files");
	let elementParent = $(this).closest('.modal-container').find('.ecm-photo-list-content');
	let actionContainerSuccess = $('.messageSuccessSendPhoto');
	let actionContainerError = $('.messageErrorSendPhoto');
	let totalCount = files.length
    let progress = 0
	let token = $('.id-container.page-ut-gp-list').find('input[name="token"]').val();
	$('#myBar').width(0)
    $('#myProgress').attr('style', 'display:block')
	window.eoxiaJS.loader.display($('#myProgress'));
	let querySeparator = '?'
	document.URL.match(/\?/) ? querySeparator = '&' : 1

	$.each(files, function(index, file) {
		let formdata = new FormData();
		formdata.append("userfile[]", file);
		//@to
		$.ajax({
			url: document.URL + querySeparator + "action=uploadPhoto&uploadMediasSuccess=1&token=" + token,
			type: "POST",
			data: formdata,
			processData: false,
			contentType: false,
			success: function (resp) {
				if ($(resp).find('.error-medias').length) {
					let response = $(resp).find('.error-medias').val()
					let decoded_response = JSON.parse(response)
					$('#myBar').width('100%')
					$('#myBar').css('background-color','#e05353')
					$('.wpeo-loader').removeClass('wpeo-loader');

					let textToShow = '';
					textToShow += decoded_response.message

					actionContainerError.find('.notice-subtitle').text(textToShow)

					actionContainerError.removeClass('hidden');

				} else {
					progress += (1 / totalCount) * 100
					$('#myBar').animate({
						width: progress + '%'
					}, 300);
					if (index + 1 === totalCount) {
						elementParent.load( document.URL + querySeparator + 'uploadMediasSuccess=1' + ' .ecm-photo-list', () => {
							setTimeout(() => {
								$('#myProgress').fadeOut(800)
								$('.wpeo-loader').removeClass('wpeo-loader');
								$('#myProgress').find('.loader-spin').remove();
							}, 800)
							$('#add_media_to_gallery').parent().html($(resp).find('#add_media_to_gallery'))
							if (totalCount == 1) {
								elementParent.closest('.modal-container').find('.save-photo').removeClass('button-disable');
								elementParent.find('.clickable-photo0').attr('style', 'border: 5px solid #0d8aff !important');
								elementParent.find('.clickable-photo0').addClass('clicked-photo');
							}
						});
						actionContainerSuccess.removeClass('hidden');
					}
				}
			},
			error : function (resp) {

			}
		});
	})
};

/**
 * Action preview photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.previewPhoto = function( event ) {
	var checkExist = setInterval(function() {
		if ($('.ui-dialog').length) {
			clearInterval(checkExist);
			$( document ).find('.ui-dialog').addClass('preview-photo');
			$( document ).find('.ui-dialog').css('z-index', '1500');
		}
	}, 100);
};

/**
 * Action unlink photo.
 *
 * @since   8.2.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.unlinkFile = function( event ) {

	event.preventDefault()
	let element_linked_id = $(this).find('.element-linked-id').val()
	let filename = $(this).find('.filename').val()
	let querySeparator = '?'
	let riskId = $(this).closest('.modal-risk').attr('value')
	let type = $(this).closest('.modal-container').find('.type-from').val()
	let noPhotoPath = $(this).closest('.modal-container').find('.no-photo-path').val()
	var params = new window.URLSearchParams(window.location.search);
	var currentElementID = params.get('id')

	let mediaContainer = $(this).closest('.media-container')
	let previousPhoto = null
	let previousName = ''
	let newPhoto = ''

	let token = $('.id-container.page-ut-gp-list').find('input[name="token"]').val();

	window.eoxiaJS.loader.display($(this).closest('.media-container'));

	document.URL.match(/\?/) ? querySeparator = '&' : 1

	if (type === 'riskassessment') {
		let riskAssessmentPhoto = $('.risk-evaluation-photo-'+element_linked_id)
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		newPhoto = previousPhoto[0].src
		$.ajax({
			url: document.URL + querySeparator + "action=unlinkFile&token=" + token,
			type: "POST",
			data: JSON.stringify({
				risk_id: riskId,
				riskassessment_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				riskAssessmentPhoto.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',noPhotoPath )
				});
				mediaContainer.hide()
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')
		newPhoto = previousPhoto[0].src

		$.ajax({
			url: document.URL + querySeparator + "action=unlinkDigiriskElementFile&token=" + token,
			type: "POST",
			data: JSON.stringify({
				digiriskelement_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				previousPhoto.attr('src',newPhoto)
				mediaContainer.hide()
				if (element_linked_id === currentElementID) {
					let digiriskBanner = $('.arearef.heightref')
					digiriskBanner.find('input[value="'+filename+'"]').siblings('').hide()
				}
			}
		});
	}

};

/**
 * Action add photo to favorite.
 *
 * @since   8.2.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.addToFavorite = function( event ) {

	event.preventDefault()
	var params = new window.URLSearchParams(window.location.search);
	var id = window.location.search.split(/id=/)[1]
	let element_linked_id = $(this).find('.element-linked-id').val()
	let filename = $(this).find('.filename').val()
	let querySeparator = '?'
	let mediaContainer = $(this).closest('.media-container')
	let modalFrom = $('.modal-risk.modal-active')
	let riskId = modalFrom.attr('value')
	let type = $(this).closest('.modal-container').find('.type-from').val()
	let previousPhoto = null
	let elementPhotos = ''

	//change star button style
	let previousFavorite = $(this).closest('.element-linked-medias').find('.fas.fa-star')
	let newFavorite = $(this).find('.far.fa-star')

	previousFavorite.removeClass('fas')
	previousFavorite.addClass('far')
	newFavorite.addClass('fas')
	newFavorite.removeClass('far')

	document.URL.match(/\?/) ? querySeparator = '&' : 1

	window.eoxiaJS.loader.display(mediaContainer);

	let token = $('.id-container.page-ut-gp-list').find('input[name="token"]').val();

	if (type === 'riskassessment') {
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		elementPhotos = $('.risk-evaluation-photo-'+element_linked_id+'.risk-'+riskId)

		$(this).closest('.modal-content').find('.risk-evaluation-photo-single .filename').attr('value', filename)

		let filepath = modalFrom.find('.risk-evaluation-photo-single .filepath-to-riskassessment').val()
		let thumbName = window.eoxiaJS.file.getThumbName(filename)
		let newPhoto = filepath + thumbName

		let saveButton = $(this).closest('.modal-container').find('.risk-evaluation-save')
		saveButton.addClass('button-disable')
		$.ajax({
			url: document.URL + querySeparator + "action=addToFavorite&token=" + token,
			data: JSON.stringify({
				riskassessment_id: element_linked_id,
				filename: filename,
			}),
			type: "POST",
			processData: false,
			success: function ( ) {
				newPhoto = newPhoto.replace(/\ /g, '%20')
				newPhoto = newPhoto.replace(/\(/g, '%28')
				newPhoto = newPhoto.replace(/\)/g, '%29')
				newPhoto = newPhoto.replace(/\+/g, '%2B')

				elementPhotos.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',newPhoto )
					$(this).find('.clicked-photo-preview').hasClass('photodigiriskdolibarr') ? $(this).find('.clicked-photo-preview').removeClass('photodigiriskdolibarr').addClass('photo') : 0
				});
				saveButton.removeClass('button-disable')
				$('.wpeo-loader').removeClass('wpeo-loader')
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')

		let filepath =$('.digirisk-element-'+element_linked_id).find('.filepath-to-digiriskelement').val()
		let thumbName = window.eoxiaJS.file.getThumbName(filename)
		let newPhoto = filepath + thumbName

		jQuery.ajax({
			url: document.URL + querySeparator + "action=addDigiriskElementPhotoToFavorite&token=" + token,
			type: "POST",
			data: JSON.stringify({
				digiriskelement_id: element_linked_id,
				filename: filename,
			}),
			processData: false,
			success: function ( resp ) {

				if (id === element_linked_id) {
					$('.arearef.heightref.valignmiddle.centpercent').load(' .arearef.heightref.valignmiddle.centpercent')
				}
				newPhoto = newPhoto.replace(/\ /g, '%20')
				newPhoto = newPhoto.replace(/\(/g, '%28')
				newPhoto = newPhoto.replace(/\)/g, '%29')
				newPhoto = newPhoto.replace(/\+/g, '%2B')

				previousPhoto.attr('src',newPhoto )
				$('.wpeo-loader').removeClass('wpeo-loader')
			}
		});
	}

};

/**
 * Initialise l'objet "risk" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.risk = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.init = function() {
	window.eoxiaJS.risk.event();
};

/**
 * La méthode contenant tous les événements pour le risk.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.event = function() {
	$( document ).on( 'click', '.category-danger .item, .wpeo-table .category-danger .item', window.eoxiaJS.risk.selectDanger );
	$( document ).on( 'click', '.risk-create:not(.button-disable)', window.eoxiaJS.risk.createRisk );
	$( document ).on( 'click', '.risk-save', window.eoxiaJS.risk.saveRisk );
	$( document ).on( 'click', '.risk-unlink-shared', window.eoxiaJS.risk.unlinkSharedRisk );
	$( document ).on( 'click', '#select_all_shared_risks', window.eoxiaJS.risk.selectAllSharedRisk );
};

/**
 * Lors du clic sur un riskCategory, remplaces le contenu du toggle et met l'image du risque sélectionné.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.eoxiaJS.risk.selectDanger = function( event ) {
	var element = $(this);
	element.closest('.content').removeClass('active');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle span').hide();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').show();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('src', element.find('img').attr('src'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('aria-label', element.closest('.wpeo-tooltip-event').attr('aria-label'));

	element.closest('.wpeo-dropdown').find('.input-hidden-danger').val(element.data('id'));
	var riskDescriptionPrefill = element.closest('.wpeo-dropdown').find('.input-risk-description-prefill').val();
	if (riskDescriptionPrefill == 1) {
		element.closest('.risk-content').find('.risk-description textarea').text(element.closest('.wpeo-tooltip-event').attr('aria-label'));
	}
	var elementParent = $(this).closest('.modal-container');

	// Rend le bouton "active".
	window.eoxiaJS.risk.haveDataInInput(elementParent);
};

/**
 * Check value on riskCategory and riskCotation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.eoxiaJS.risk.haveDataInInput = function( elementParent ) {
	var element = elementParent.parent().parent();
	var cotation = element.find('.risk-evaluation-seuil');

	if (element.hasClass('risk-add-modal')) {
		var category = element.find('input[name="risk_category_id"]')
		if ( category.val() >= 0  && cotation.val() >= 0  ) {
			element.find('.risk-create.button-disable').removeClass('button-disable');
		}
	} else if (element.hasClass('risk-evaluation-add-modal')) {
		if ( cotation.val() >= 0 ) {
			element.find('.risk-evaluation-create.button-disable').removeClass('button-disable');
		}
	}
};

/**
 * Sanitize request before send.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.sanitizeBeforeRequest = function ( text ) {
	if (typeof text == 'string') {
		if (text.match(/"/)) {
			return text.split(/"/).join('')
		}
	}
	return text
}

/**
 * Action create risk.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.createRisk = function ( event ) {
	let elementRisk = $(this).closest('.fichecenter').find('.risk-content');
	let elementEvaluation = $(this).closest('.fichecenter').find('.risk-evaluation-container');
	let elementTask = $(this).closest('.fichecenter').find('.riskassessment-task');

	let riskCommentText = elementRisk.find('.risk-description textarea').val()

	let evaluationText = elementEvaluation.find('.risk-evaluation-comment textarea').val()

	let taskText = elementTask.find('input').val()

	riskCommentText = window.eoxiaJS.risk.sanitizeBeforeRequest(riskCommentText)
	evaluationText = window.eoxiaJS.risk.sanitizeBeforeRequest(evaluationText)
	taskText = window.eoxiaJS.risk.sanitizeBeforeRequest(taskText)

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	//Risk
	var category = elementRisk.find('.risk-category input').val();
	var description = riskCommentText;

	//Risk assessment
	var method = elementEvaluation.find('.risk-evaluation-header .risk-evaluation-method').val();
	var cotation = elementEvaluation.find('.risk-evaluation-seuil').val();
	var photo = elementEvaluation.find('.risk-evaluation-photo-single .filename').val();
	var comment = evaluationText;
	var date = elementEvaluation.find('#RiskAssessmentDate').val();
	var criteres = []

	Object.values($('.table-cell.active.cell-0')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres[ $(v).data( 'type' )] = $(v).data( 'seuil' )
		}
	})

	//Task
	var task = taskText;
	let dateStart = elementTask.find('#RiskassessmentTaskDateStartModalRisk').val();
	let hourStart = elementTask.find('#RiskassessmentTaskDateStartModalRiskhour').val();
	let minStart  = elementTask.find('#RiskassessmentTaskDateStartModalRiskmin').val();
	let dateEnd   = elementTask.find('#RiskassessmentTaskDateEndModalRisk').val();
	let hourEnd   = elementTask.find('#RiskassessmentTaskDateEndModalRiskhour').val();
	let minEnd    = elementTask.find('#RiskassessmentTaskDateEndModalRiskmin').val();
	let budget    = elementTask.find('.riskassessment-task-budget').val()

	//Loader
	window.eoxiaJS.loader.display($('.fichecenter.risklist'));

	$.ajax({
		url: document.URL + '&action=add&token='+token,
		type: "POST",
		data: JSON.stringify({
			cotation: cotation,
			comment: comment,
			category: category,
			description: description,
			method: method,
			photo: photo,
			date: date,
			task: task,
			dateStart: dateStart,
			hourStart: hourStart,
			minStart: minStart,
			dateEnd: dateEnd,
			hourEnd: hourEnd,
			minEnd: minEnd,
			budget: budget,
			criteres: {
				gravite: criteres['gravite'] ? criteres['gravite'] : 0,
				occurrence: criteres['occurrence'] ? criteres['occurrence'] : 0,
				protection: criteres['protection'] ? criteres['protection'] : 0,
				formation: criteres['formation'] ? criteres['formation'] : 0,
				exposition: criteres['exposition'] ? criteres['exposition'] : 0
			}
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))

			let actionContainerSuccess = $('.messageSuccessRiskCreate')
			actionContainerSuccess.html($(resp).find('.risk-create-success-notice'))
			actionContainerSuccess.removeClass('hidden')
			$('.fichecenter.risklist').removeClass('wpeo-loader');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskCreate');

			actionContainerError.html($(resp).find('.risk-create-error-notice'));
			actionContainerError.removeClass('hidden');

			$('.fichecenter.risklist').removeClass('wpeo-loader');
		}
	});

};

/**
 * Action save risk.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.saveRisk = function ( event ) {
	let editedRiskId = $(this).attr('value');
	let elementRisk = $(this).closest('.risk-container').find('.risk-content');
	var params = new window.URLSearchParams(window.location.search);
	var id = params.get('id')
	let moveRiskDisabled = $(this).closest('.risk-container').find('.move-risk').hasClass('move-disabled')
	let riskCommentText = elementRisk.find('.risk-description textarea').val()
	riskCommentText = window.eoxiaJS.risk.sanitizeBeforeRequest(riskCommentText)

	var category = elementRisk.find('.risk-category input').val();

	if (riskCommentText) {
		var description = riskCommentText;
	} else {
		var description = '';
	}

	var newParent = $(this).closest('.risk-container').find('#socid option:selected').val();
	let elementParent = $('.fichecenter.risklist').find('.div-title-and-table-responsive');

	if (newParent == id || moveRiskDisabled) {
		window.eoxiaJS.loader.display($(this).closest('.risk-row-content-' + editedRiskId).find('.risk-description-'+editedRiskId));
		window.eoxiaJS.loader.display($(this).closest('.risk-row-content-' + editedRiskId).find('.risk-category'));
	} else {
		window.eoxiaJS.loader.display($(this).closest('.risk-row-content-' + editedRiskId))
	}
	let riskRef =  $('.risk_row_'+editedRiskId).find('.risk-container > div:nth-child(1)').text();

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url:  document.URL + '&action=saveRisk&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			riskID: editedRiskId,
			category: category,
			comment: description,
			newParent: newParent
		}),
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader');
			let actionContainerSuccess = $('.messageSuccessRiskEdit');
			if (newParent == id || moveRiskDisabled) {
				$('.modal-active').removeClass('modal-active')
				$('.risk-description-'+editedRiskId).html($(resp).find('.risk-description-'+editedRiskId))
				$('.risk-row-content-' + editedRiskId).find('.risk-category .cell-risk').html($(resp).find('.risk-row-content-' + editedRiskId).find('.risk-category .cell-risk').children())
				$('.risk-row-content-' + editedRiskId).find('.risk-category').fadeOut(800);
				$('.risk-row-content-' + editedRiskId).find('.risk-category').fadeIn(800);
				$('.risk-row-content-' + editedRiskId).find('.risk-description-'+editedRiskId).fadeOut(800);
				$('.risk-row-content-' + editedRiskId).find('.risk-description-'+editedRiskId).fadeIn(800);
			} else {
				$('.risk-row-content-'+editedRiskId).fadeOut(800, function () {
					$('.fichecenter .opacitymedium.colorblack.paddingleft').html($(resp).find('#searchFormListRisks .opacitymedium.colorblack.paddingleft'))
				});
			}

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForEditRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerSuccess.find('.valueForEditRisk2').val()
			actionContainerSuccess.find('a').attr('href', '#risk_row_'+editedRiskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskEdit');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForEditRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerError.find('.valueForEditRisk2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action unlink shared risk.
 *
 * @since   9.2.0
 * @version 9.2.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.unlinkSharedRisk = function ( event ) {
	let riskId = $(this).attr('value');
	//let elementRisk = $(this).closest('.risk-container').find('.risk-content');
	let elementParent = $('.fichecenter.sharedrisklist').find('.div-table-responsive');

	window.eoxiaJS.loader.display($(this));

	let riskRef =  $('.risk_row_'+riskId).find('.risk-container > div:nth-child(1)').text();
	let url = document.URL.split(/#/);

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url: url[0] + '&action=unlinkSharedRisk&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			riskID: riskId,
		}),
		contentType: false,
		success: function ( resp ) {
			//refresh shared risk list form
			$('.confirmquestions').html($(resp).find('.confirmquestions').children())
			//refresh shared risk counter
			$('.fichecenter.sharedrisklist .opacitymedium.colorblack.paddingleft').html($(resp).find('#searchFormSharedListRisks .opacitymedium.colorblack.paddingleft'))
			let actionContainerSuccess = $('.messageSuccessRiskUnlinkShared');

			$('#risk_row_' + riskId).fadeOut(800);

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRisk2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskUnlinkShared');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForUnlinkSharedRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerError.find('.valueForUnlinkSharedRisk2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action select All shared risk.
 *
 * @since   9.2.0
 * @version 9.2.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.selectAllSharedRisk = function ( event ) {
	if(this.checked) {
		// Iterate each checkbox
		$(this).closest('.ui-widget').find(':checkbox').not(':disabled').each(function() {
			this.checked = true;
		});
	} else {
		$(this).closest('.ui-widget').find(':checkbox').not(':disabled').each(function() {
			this.checked = false;
		});
	}
};

/**
 * Initialise l'objet "evaluation" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.evaluation = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.init = function() {
	window.eoxiaJS.evaluation.event();
};

/**
 * La méthode contenant tous les événements pour le evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.event = function() {
	$( document ).on( 'click', '.select-evaluation-method', window.eoxiaJS.evaluation.selectEvaluationMethod);
	$( document ).on( 'click', '.cotation-container .risk-evaluation-cotation.cotation', window.eoxiaJS.evaluation.selectSeuil );
	$( document ).on( 'click', '.risk-evaluation-create', window.eoxiaJS.evaluation.createEvaluation);
	$( document ).on( 'click', '.risk-evaluation-save', window.eoxiaJS.evaluation.saveEvaluation);
	$( document ).on( 'click', '.risk-evaluation-delete', window.eoxiaJS.evaluation.deleteEvaluation);
}

/**
 * Select Evaluation Method.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.selectEvaluationMethod = function ( event ) {
	var elementParent = $(this).closest('.modal-container');
	var multiple_method = elementParent.find('.risk-evaluation-multiple-method').val();
	if (multiple_method > 0) {
		elementParent.find('.select-evaluation-method.selected').removeClass('selected');

		$(this).addClass('selected');
		$(this).removeClass('button-grey');
		$(this).addClass('button-blue');

		elementParent.find('.select-evaluation-method:not(.selected)').removeClass('button-blue');
		elementParent.find('.select-evaluation-method:not(.selected)').addClass('button-grey');

		if ($(this).hasClass('evaluation-standard')) {
			elementParent.find('.cotation-advanced').attr('style', 'display:none');
			elementParent.find('.cotation-standard').attr('style', 'display:block');
			elementParent.find('.risk-evaluation-calculated-cotation').attr('style', 'display:none')
			elementParent.find('.risk-evaluation-method').val('standard');
			elementParent.find(this).closest('.risk-evaluation-container').removeClass('advanced');
			elementParent.find(this).closest('.risk-evaluation-container').addClass('standard');
		} else {
			elementParent.find('.cotation-standard').attr('style', 'display:none');
			elementParent.find('.cotation-advanced').attr('style', 'display:block');
			elementParent.find('.risk-evaluation-calculated-cotation').attr('style', 'display:block');
			elementParent.find('.risk-evaluation-method').val('advanced');
			elementParent.find(this).closest('.risk-evaluation-container').addClass('advanced');
			elementParent.find(this).closest('.risk-evaluation-container').removeClass('standard');
		}
	}
};

/**
 * Clique sur une des cotations simples.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.eoxiaJS.evaluation.selectSeuil = function( event ) {
	var element       = $(this);
	var elementParent = $(this).closest('.modal-container')
	var seuil         = element.data( 'seuil' );
	var variableID    = element.data( 'variable-id' );

	element.closest('.cotation-container').find('.risk-evaluation-seuil').val($( this ).text());
	element.closest('.cotation-container').find('.selected-cotation').removeClass('selected-cotation')
	element.addClass('selected-cotation')

	if ( variableID && seuil ) {
		// Rend le bouton "active".
		window.eoxiaJS.risk.haveDataInInput( elementParent )
	}
};

/**
 * Get default cotation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {int} risk assessment cotation value.
 * @return {int}
 */
window.eoxiaJS.evaluation.getDynamicScale = function (cotation) {
	switch (true) {
		case (cotation === 0):
		case (cotation < 48):
			return 1;
		case (cotation < 51):
			return 2;
		case (cotation < 79):
			return 3;
		case (cotation < 101):
			return 4;
	}
};

/**
 * Action create evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.createEvaluation = function ( event ) {

	var riskToAssign = $(this).attr('value');
	let element = $(this).closest('.risk-evaluation-add-modal');
	let single = element.find('.risk-evaluation-container');

	let evaluationText = single.find('.risk-evaluation-comment textarea').val()
	evaluationText = window.eoxiaJS.risk.sanitizeBeforeRequest(evaluationText)

	var method = single.find('.risk-evaluation-method').val();
	var cotation = single.find('.risk-evaluation-seuil').val();
	var comment = evaluationText
	var date = single.find('#RiskAssessmentDateCreate0').val();
	var photo = single.find('.risk-evaluation-photo-single .filename').val();

	let criteres = [];
	Object.values($('.table-cell.active.cell-0')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres[ $(v).data( 'type' )] = $(v).data( 'seuil' )
		}
	})

	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.risk-evaluation-list-container-' + riskToAssign));

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=addEvaluation&token='+token,
		type: "POST",
		data: JSON.stringify({
			cotation: cotation,
			comment: comment,
			method: method,
			photo: photo,
			date: date,
			riskId: riskToAssign,
			criteres: {
				gravite: criteres['gravite'] ? criteres['gravite'] : 0,
				occurrence: criteres['occurrence'] ? criteres['occurrence'] : 0,
				protection: criteres['protection'] ? criteres['protection'] : 0,
				formation: criteres['formation'] ? criteres['formation'] : 0,
				exposition: criteres['exposition'] ? criteres['exposition'] : 0
			}
		}),
		processData: false,
		contentType: false,
		success: function( resp ) {
			//refresh risk assessment list
			$('.risk-evaluation-list-container-' + riskToAssign).html($(resp).find('.risk-evaluation-list-container-' + riskToAssign).children())

			let actionContainerSuccess = $('.messageSuccessEvaluationCreate');

			$('.risk-evaluation-list-container-' + riskToAssign).fadeOut(800);
			$('.risk-evaluation-list-container-' + riskToAssign).fadeIn(800);
			actionContainerSuccess.empty()
			actionContainerSuccess.html($(resp).find('.riskassessment-create-success-notice'))
			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskToAssign)

			$('.wpeo-loader').removeClass('wpeo-loader')
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorEvaluationCreate');

			$(this).closest('.risk-row-content-' + riskToAssign).removeClass('wpeo-loader');

			actionContainerError.empty()
			actionContainerError.html($(resp).find('.riskassessment-create-error-notice'))

			actionContainerError.removeClass('hidden');
		}
	});
};


/**
 * Action delete evaluation.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {boolean}
 */
window.eoxiaJS.evaluation.deleteEvaluation = function ( event ) {
	let element = $(this).closest('.risk-evaluation-in-modal');
	let deletedEvaluationId = element.attr('value');
	let textToShowBeforeDelete = element.find('.labelForDelete').val();
	let actionContainerSuccess = $('.messageSuccessEvaluationDelete');
	let actionContainerError = $('.messageErrorEvaluationDelete');
	let evaluationID = element.attr('value');

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	var r = confirm(textToShowBeforeDelete);
	if (r == true) {

		let elementParent = $(this).closest('.risk-evaluations-list-content');
		let riskId = elementParent.attr('value');
		let evaluationRef =  $('.risk-evaluation-ref-'+evaluationID).attr('value');

		window.eoxiaJS.loader.display($('.risk-evaluation-ref-'+evaluationID));

		$.ajax({
			url:document.URL + '&action=deleteEvaluation&deletedEvaluationId=' + deletedEvaluationId + '&token=' + token,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( resp ) {
				//remove risk assessment from list in modal
				$('.risk-evaluation-container-' + evaluationID).fadeOut(400)

				//refresh risk assessment list
				$('.risk-evaluation-list-content-' + riskId).html($(resp).find('.risk-evaluation-list-content-' + riskId).children())

				//refresh risk assessment add modal to actualize last risk assessment
				$('#risk_evaluation_add' + riskId).html($(resp).find('#risk_evaluation_add' + riskId).children())

				//update risk assessment counter
				let evaluationCounterText = $('#risk_row_'+riskId).find('.table-cell-header-label').text()
				let evaluationCounter = evaluationCounterText.split(/\(/)[1].split(/\)/)[0]
				$('#risk_row_'+riskId).find('.table-cell-header-label').html('<strong>' + evaluationCounterText.split(/\(/)[0] + '(' + (+evaluationCounter - 1) + ')' + '</strong>')
				if (evaluationCounter - 1 < 1) {
					$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))
				}

				elementParent.removeClass('wpeo-loader');

				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteEvaluation1').val()
				textToShow += evaluationRef
				textToShow += actionContainerSuccess.find('.valueForDeleteEvaluation2').val()

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
			},
			error: function ( resp ) {

				let textToShow = '';
				textToShow += actionContainerError.find('.valueForDeleteEvaluation1').val()
				textToShow += evaluationRef
				textToShow += actionContainerError.find('.valueForDeleteEvaluation2').val()

				actionContainerError.find('.notice-subtitle .text').text(textToShow);
				actionContainerError.removeClass('hidden');
				}
			});

		} else {
		return false;
	}
}

/**
 * Action save evaluation.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.saveEvaluation = function ( event ) {
	let element = $(this).closest('.risk-evaluation-edit-modal');
	let evaluationID = element.attr('value');

	let evaluationText = element.find('.risk-evaluation-comment textarea').val()

	let elementParent = $(this).closest('.risk-row').find('.risk-evaluations-list-content');
	let riskId = elementParent.attr('value');
	let evaluationRef =  $('.risk-evaluation-ref-'+evaluationID).attr('value');
	let listModalContainer = $('.risk-evaluation-list-modal-'+riskId)
	let listModal = $('#risk_evaluation_list'+riskId)
	let fromList = listModal.hasClass('modal-active');

	evaluationText = window.eoxiaJS.risk.sanitizeBeforeRequest(evaluationText)

	var method = element.find('.risk-evaluation-method').val();
	var cotation = element.find('.risk-evaluation-seuil').val();
	var comment = evaluationText;
	var date = element.find('#RiskAssessmentDateEdit' + evaluationID).val();
	var photo = element.find('.risk-evaluation-photo .filename').val();

	let criteres = [];
	Object.values($('.table-cell.active.cell-'+evaluationID)).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres[ $(v).data( 'type' )] = $(v).data( 'seuil' )
		}
	})

	window.eoxiaJS.loader.display($(this));

	if (fromList) {
		window.eoxiaJS.loader.display($('.risk-evaluation-ref-'+evaluationID));
	} else {
		window.eoxiaJS.loader.display($('.risk-evaluation-container-'+evaluationID));
	}

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=saveEvaluation&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			cotation: cotation,
			comment: comment,
			method: method,
			photo: photo,
			date: date,
			evaluationID: evaluationID,
			criteres: {
				gravite: criteres['gravite'] ? criteres['gravite'] : 0,
				occurrence: criteres['occurrence'] ? criteres['occurrence'] : 0,
				protection: criteres['protection'] ? criteres['protection'] : 0,
				formation: criteres['formation'] ? criteres['formation'] : 0,
				exposition: criteres['exposition'] ? criteres['exposition'] : 0
			}
		}),
		contentType: false,
		success: function ( resp ) {
			if (fromList) {
				$('.risk-evaluation-ref-'+evaluationID+':not(.last-risk-assessment)').fadeOut(800);
				$('.risk-evaluation-ref-'+evaluationID+':not(.last-risk-assessment)').fadeIn(800);
			} else {
				$('.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').fadeOut(800);
				$('.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').fadeIn(800);
			}
			//refresh risk assessment single in modal list
			listModalContainer.find('.risk-evaluation-ref-'+evaluationID).html($(resp).find('.risk-evaluation-ref-'+evaluationID).children())

			//refresh risk assessment single in list
			$('.risk-evaluation-container.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').html($(resp).find('.risk-evaluation-container.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').children())

			//refresh risk assessment add modal to actualize last risk assessment
			$('#risk_evaluation_add' + riskId).html($(resp).find('#risk_evaluation_add' + riskId).children())

			$('.wpeo-loader').removeClass('wpeo-loader')
			let actionContainerSuccess = $('.messageSuccessEvaluationEdit');

			element.find('#risk_evaluation_edit'+evaluationID).removeClass('modal-active');

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForEditEvaluation1').val()
			textToShow += evaluationRef
			textToShow += actionContainerSuccess.find('.valueForEditEvaluation2').val()
			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			let actionContainerError = $('.messageErrorEvaluationEdit');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForEditEvaluation1').val()
			textToShow += evaluationRef
			textToShow += actionContainerError.find('.valueForEditEvaluation2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});

};

/**
 * Initialise l'objet "evaluationMethodEvarisk" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.evaluationMethodEvarisk = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluationMethodEvarisk.init = function() {
	window.eoxiaJS.evaluationMethodEvarisk.event();
};

/**
 * La méthode contenant tous les événements pour le evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluationMethodEvarisk.event = function() {
	$( document ).on( 'click', '.wpeo-table.evaluation-method .table-cell.can-select', window.eoxiaJS.evaluationMethodEvarisk.selectSeuil );
};

/**
 * Select Seuil on advanced cotation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.eoxiaJS.evaluationMethodEvarisk.selectSeuil = function( event ) {
	$( this ).closest( '.table-row' ).find( '.active' ).removeClass( 'active' );
	$( this ).addClass( 'active' );

	var elementParent = $(this).closest('.modal-container');
	var element       = $( this );
	var evaluationID  = element.data( 'evaluation-id' );

	let criteres = [];
	Object.values(elementParent.find('.table-cell.active.cell-'+evaluationID)).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres.push($(v).data( 'seuil' ));
		}
	});

	// Rend le bouton "active" et met à jour la cotation et la scale
	if (criteres.length === 5) {
		let cotationBeforeAdapt = criteres[0] * criteres[1] * criteres[2] * criteres[3] * criteres[4];

		let root = window.location.pathname.split(/view/)[0]

		fetch(root + '/js/json/default.json').then(response => response.json()).then(data => {
			let cotationAfterAdapt = data[0].option.matrix[cotationBeforeAdapt];
			elementParent.find('.risk-evaluation-calculated-cotation').find('.risk-evaluation-cotation').attr('data-scale', window.eoxiaJS.evaluation.getDynamicScale(cotationAfterAdapt));
			elementParent.find('.risk-evaluation-calculated-cotation').find('.risk-evaluation-cotation span').text(cotationAfterAdapt);
			elementParent.find('.risk-evaluation-content').find('.risk-evaluation-seuil').val(cotationAfterAdapt);
			window.eoxiaJS.risk.haveDataInInput(elementParent);
		})
	}
};

/**
 * Initialise l'objet "riskassessmenttask" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.riskassessmenttask = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.init = function() {
	window.eoxiaJS.riskassessmenttask.event();
};

/**
 * La méthode contenant tous les événements pour le riskassessment-task.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.event = function() {
	$( document ).on( 'input', '.riskassessment-task-label', window.eoxiaJS.riskassessmenttask.fillRiskAssessmentTaskLabel);
	$( document ).on( 'click', '.riskassessment-task-create', window.eoxiaJS.riskassessmenttask.createRiskAssessmentTask);
	$( document ).on( 'click', '.riskassessment-task-save', window.eoxiaJS.riskassessmenttask.saveRiskAssessmentTask);
	$( document ).on( 'click', '.riskassessment-task-delete', window.eoxiaJS.riskassessmenttask.deleteRiskAssessmentTask );
	$( document ).on( 'click', '.riskassessment-task-timespent-create', window.eoxiaJS.riskassessmenttask.createRiskAssessmentTaskTimeSpent);
	$( document ).on( 'click', '.riskassessment-task-timespent-save', window.eoxiaJS.riskassessmenttask.saveRiskAssessmentTaskTimeSpent);
	$( document ).on( 'click', '.riskassessment-task-timespent-delete', window.eoxiaJS.riskassessmenttask.deleteRiskAssessmentTaskTimeSpent );
	$( document ).on( 'click', '.riskassessment-task-progress-checkbox:not(.riskassessment-task-progress-checkbox-readonly)', window.eoxiaJS.riskassessmenttask.checkTaskProgress );
	$( document ).on( 'change', '#RiskassessmentTaskTimespentDatehour', window.eoxiaJS.riskassessmenttask.selectRiskassessmentTaskTimespentDateHour );
	$( document ).on( 'change', '#RiskassessmentTaskTimespentDatemin', window.eoxiaJS.riskassessmenttask.selectRiskassessmentTaskTimespentDateMin );
	$( document ).on( 'keyup', '.riskassessment-task-label', window.eoxiaJS.riskassessmenttask.checkRiskassessmentTaskLabelLength );
};

/**
 * Fill riskassessmenttask label
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.fillRiskAssessmentTaskLabel = function( event ) {
	var elementParent = $(this).closest('.modal-container');

	// Rend le bouton "active".
	window.eoxiaJS.riskassessmenttask.haveDataInInput(elementParent);
};

/**
 * Check value on riskAssessmentTask.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.haveDataInInput = function( elementParent ) {
	var element = elementParent.parent().parent();

	if (element.hasClass('riskassessment-task-add-modal')) {
		var riskassessmenttasklabel = element.find('input[name="label"]').val();
		if ( riskassessmenttasklabel.length ) {
			element.find('.button-disable').removeClass('button-disable');
		}
	}
};

/**
 * Action create task.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.createRiskAssessmentTask = function ( event ) {
	var riskToAssign = $(this).attr('value');
	let element = $(this).closest('.riskassessment-task-add-modal');
	let single = element.find('.riskassessment-task-container');

	let taskText = single.find('.riskassessment-task-label').val()
	taskText = window.eoxiaJS.risk.sanitizeBeforeRequest(taskText)

	let dateStart = single.find('#RiskassessmentTaskDateStart' + riskToAssign).val();
	let hourStart = single.find('#RiskassessmentTaskDateStart' + riskToAssign + 'hour').val();
	let minStart  = single.find('#RiskassessmentTaskDateStart' + riskToAssign + 'min').val();
	let dateEnd   = single.find('#RiskassessmentTaskDateEnd' + riskToAssign).val();
	let hourEnd   = single.find('#RiskassessmentTaskDateEnd' + riskToAssign + 'hour').val();
	let minEnd    = single.find('#RiskassessmentTaskDateEnd' + riskToAssign + 'min').val();
	let budget    = single.find('.riskassessment-task-budget').val()

	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.riskassessment-tasks' + riskToAssign));

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=addRiskAssessmentTask&token='+token,
		type: "POST",
		data: JSON.stringify({
			tasktitle: taskText,
			dateStart: dateStart,
			hourStart: hourStart,
			minStart: minStart,
			dateEnd: dateEnd,
			hourEnd: hourEnd,
			minEnd: minEnd,
			budget: budget,
			riskToAssign: riskToAssign,
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.tasks-list-container-'+riskToAssign).html($(resp).find('.tasks-list-container-'+riskToAssign).children())
			let actionContainerSuccess = $('.messageSuccessTaskCreate');

			$('.riskassessment-tasks' + riskToAssign).fadeOut(800);
			$('.riskassessment-tasks' + riskToAssign).fadeIn(800);

			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskToAssign)

			actionContainerSuccess.html($(resp).find('.task-create-success-notice'))
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			$(this).closest('.risk-row-content-' + riskToAssign).removeClass('wpeo-loader');
			let actionContainerError = $('.messageErrorTaskCreate');
			actionContainerError.html($(resp).find('.task-create-error-notice'))
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action delete riskassessmenttask.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.deleteRiskAssessmentTask = function ( event ) {
	let element = $(this).closest('.riskassessment-tasks');
	let riskId = $(this).closest('.riskassessment-tasks').attr('value');
	let deletedRiskAssessmentTaskId = $(this).attr('value');
	let textToShow = element.find('.labelForDelete').val();
	let actionContainerSuccess = $('.messageSuccessTaskDelete');
	let actionContainerError = $('.messageErrorTaskDelete');

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	var r = confirm(textToShow);
	if (r == true) {

		let riskAssessmentTaskRef =  $('.riskassessment-task-container-'+deletedRiskAssessmentTaskId).attr('value');

		window.eoxiaJS.loader.display($('.riskassessment-task-container-'+deletedRiskAssessmentTaskId));

		$.ajax({
			url: document.URL + '&action=deleteRiskAssessmentTask&deletedRiskAssessmentTaskId=' + deletedRiskAssessmentTaskId + '&token=' + token,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( resp ) {
				$('.riskassessment-task-container-'+deletedRiskAssessmentTaskId).hide()
				$('.riskassessment-tasks' + riskId).fadeOut(800);
				$('.riskassessment-tasks' + riskId).fadeIn(800);
				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteTask1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerSuccess.find('.valueForDeleteTask2').val()

				actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
			},
			error: function ( resp ) {
				$('.wpeo-loader').removeClass('wpeo-loader');
				window.scrollTo(0, 0);
				let response = JSON.parse(resp.responseText)

				let textToShow = '';
				textToShow += actionContainerError.find('.valueForDeleteTask1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerError.find('.valueForDeleteTask2').val()
				textToShow += ' : '
				textToShow += response.message

				actionContainerError.find('.notice-subtitle .text').text(textToShow);
				actionContainerError.removeClass('hidden');
			}
		});

	} else {
		return false;
	}
};

/**
 * Action save riskassessmenttask.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.saveRiskAssessmentTask = function ( event ) {
	let editedRiskAssessmentTaskId = $(this).attr('value');
	let elementRiskAssessmentTask = $(this).closest('.riskassessment-task-container');
	let riskId = $(this).closest('.riskassessment-tasks').attr('value')
	let textToShow = '';

	let taskText = elementRiskAssessmentTask.find('.riskassessment-task-label' + editedRiskAssessmentTaskId).val()
	taskText = window.eoxiaJS.risk.sanitizeBeforeRequest(taskText)

	let taskRef =  $('.riskassessment-task-ref-'+editedRiskAssessmentTaskId).attr('value');

	let taskProgress = 0;
	if (elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox' + editedRiskAssessmentTaskId).is(':checked')) {
		taskProgress = 1;
	}

	let dateStart = elementRiskAssessmentTask.find('#RiskassessmentTaskDateStart' + editedRiskAssessmentTaskId).val();
	let hourStart = elementRiskAssessmentTask.find('#RiskassessmentTaskDateStart' + editedRiskAssessmentTaskId + 'hour').val();
	let minStart  = elementRiskAssessmentTask.find('#RiskassessmentTaskDateStart' + editedRiskAssessmentTaskId + 'min').val();
	let dateEnd   = elementRiskAssessmentTask.find('#RiskassessmentTaskDateEnd' + editedRiskAssessmentTaskId).val();
	let hourEnd   = elementRiskAssessmentTask.find('#RiskassessmentTaskDateEnd' + editedRiskAssessmentTaskId + 'hour').val();
	let minEnd    = elementRiskAssessmentTask.find('#RiskassessmentTaskDateEnd' + editedRiskAssessmentTaskId + 'min').val();
	let budget    = elementRiskAssessmentTask.find('.riskassessment-task-budget'  + editedRiskAssessmentTaskId).val()

	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.riskassessment-task-single-'+ editedRiskAssessmentTaskId));

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=saveRiskAssessmentTask&token='+token,
		data: JSON.stringify({
			riskAssessmentTaskID: editedRiskAssessmentTaskId,
			tasktitle: taskText,
			dateStart: dateStart,
			hourStart: hourStart,
			minStart: minStart,
			dateEnd: dateEnd,
			hourEnd: hourEnd,
			minEnd: minEnd,
			budget: budget,
			taskProgress: taskProgress,
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.riskassessment-task-container-'+editedRiskAssessmentTaskId).html($(resp).find('.riskassessment-task-container-'+editedRiskAssessmentTaskId).children())
			let actionContainerSuccess = $('.messageSuccessTaskEdit');
			$('.riskassessment-tasks' + riskId).fadeOut(800);
			$('.riskassessment-tasks' + riskId).fadeIn(800);
			textToShow += actionContainerSuccess.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTask2').val()

			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorTaskEdit');

			textToShow += actionContainerError.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerError.find('.valueForEditTask2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action create task timespent.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.createRiskAssessmentTaskTimeSpent = function ( event ) {
	let taskID     = $(this).attr('value');
	let element    = $(this).closest('.riskassessment-task-edit-modal');
	let single     = element.find('.riskassessment-task-timespent-container');
	let riskId     = element.find('riskassessment-task-single').attr('value');
	let textToShow = '';
	let taskRef    = element.find('.riskassessment-task-ref-'+taskID).attr('value');
	let timespent  = $('.modal-header .riskassessment-task-data').find('.riskassessment-task-timespent')

	let date     = single.find('#RiskassessmentTaskTimespentDate' + taskID).val();
	let hour     = single.find('#RiskassessmentTaskTimespentDate' + taskID + 'hour').val();
	let min      = single.find('#RiskassessmentTaskTimespentDate' + taskID + 'min').val();
	let comment  = single.find('.riskassessment-task-timespent-comment').val()
	comment      = window.eoxiaJS.risk.sanitizeBeforeRequest(comment)
	let duration = single.find('.riskassessment-task-timespent-duration').val()

	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.riskassessment-task-single-'+ taskID));

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=addRiskAssessmentTaskTimeSpent&token='+token,
		type: "POST",
		data: JSON.stringify({
			taskID: taskID,
			date: date,
			hour: hour,
			min: min,
			comment: comment,
			duration: duration,
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			//element.html($(resp).find(single))
			let actionContainerSuccess = $('.messageSuccessTaskTimeSpentCreate'+ taskID);

			$('.riskassessment-tasks' + riskId).fadeOut(800);
			$('.riskassessment-tasks' + riskId).fadeIn(800);

			textToShow += actionContainerSuccess.find('.valueForCreateTaskTimeSpent1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForCreateTaskTimeSpent2').val()

			$('#risk_assessment_task_edit'+taskID).first().find('.riskassessment-task-timespent-container').first().append($(resp).find('#risk_assessment_task_edit'+taskID).first().find('.riskassessment-task-timespent-list-content').last())
			$('.loader-spin').remove();
			$('.wpeo-loader').removeClass('wpeo-loader')

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
			timespent.html($(resp).find('.modal-header .riskassessment-task-data').find('.riskassessment-task-timespent'))
		},
		error: function ( resp ) {
			$(this).closest('.risk-row-content-' + riskId).removeClass('wpeo-loader');
			let actionContainerError = $('.messageErrorTaskTimeSpentCreate'+ taskID);
			actionContainerError.html($(resp).find('.task-timespent-create-error-notice'))
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action delete riskassessmenttasktimespent.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.deleteRiskAssessmentTaskTimeSpent = function ( event ) {
	let element = $(this).closest('.riskassessment-task-timespent-list-content');
	let riskId = $(this).closest('.riskassessment-task-timespent-list-content').attr('value');
	let RiskAssessmentTaskId = $(this).closest('.riskassessment-task').attr('value');
	let deletedRiskAssessmentTaskTimeSpentId = $(this).attr('value');
	let textToShow = element.find('.labelForDelete').val();

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	var r = confirm(textToShow);
	if (r == true) {

		let riskAssessmentTaskRef =  $('.riskassessment-task-container-'+RiskAssessmentTaskId).attr('value');

		window.eoxiaJS.loader.display($(this));

		$.ajax({
			url: document.URL + '&action=deleteRiskAssessmentTaskTimeSpent&deletedRiskAssessmentTaskTimeSpentId=' + deletedRiskAssessmentTaskTimeSpentId + '&token=' + token,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( resp ) {
				$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))
				let actionContainerSuccess = $('.messageSuccessTaskTimeSpentDelete');
				$('.riskassessment-tasks' + riskId).fadeOut(800);
				$('.riskassessment-tasks' + riskId).fadeIn(800);
				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteTaskTimeSpent1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerSuccess.find('.valueForDeleteTaskTimeSpent2').val()

				//actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
			},
			error: function ( resp ) {
				let actionContainerError = $('.messageErrorTaskDeleteTimeSpent');

				let textToShow = '';
				textToShow += actionContainerError.find('.valueForDeleteTaskTimeSpent1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerError.find('.valueForDeleteTaskTimeSpent2').val()

				actionContainerError.find('.notice-subtitle .text').text(textToShow);
				actionContainerError.removeClass('hidden');
			}
		});

	} else {
		return false;
	}
};

/**
 * Action save riskassessmenttasktimespent.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.saveRiskAssessmentTaskTimeSpent = function ( event ) {
	let riskAssessmentTaskTimeSpentID = $(this).attr('value');
	let element    = $(this).closest('.riskassessment-task-timespent-edit-modal');
	let single     = element.find('.riskassessment-task-timespent-container');
	let riskId     = $(this).closest('.riskassessment-tasks').attr('value')
	let taskID     = single.attr('value');
	let textToShow = '';
	let taskRef    =  element.closest('.riskassessment-task').find('.riskassessment-task-ref-'+taskID).attr('value');

	let date     = single.find('#RiskassessmentTaskTimespentDateEdit' + riskAssessmentTaskTimeSpentID).val();
	let hour     = single.find('#RiskassessmentTaskTimespentDateEdit' + riskAssessmentTaskTimeSpentID + 'hour').val();
	let min      = single.find('#RiskassessmentTaskTimespentDateEdit' + riskAssessmentTaskTimeSpentID + 'min').val();
	let comment  = single.find('.riskassessment-task-timespent-comment').val()
	comment      = window.eoxiaJS.risk.sanitizeBeforeRequest(comment)
	let duration = single.find('.riskassessment-task-timespent-duration').val()

	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.riskassessment-task-single-'+ taskID));

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=saveRiskAssessmentTaskTimeSpent&token='+token,
		data: JSON.stringify({
			riskAssessmentTaskTimeSpentID: riskAssessmentTaskTimeSpentID,
			date: date,
			hour: hour,
			min: min,
			comment: comment,
			duration: duration,
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))
			let actionContainerSuccess = $('.messageSuccessTaskTimeSpentEdit');
			$('.riskassessment-tasks' + riskId).fadeOut(800);
			$('.riskassessment-tasks' + riskId).fadeIn(800);
			textToShow += actionContainerSuccess.find('.valueForEditTaskTimeSpent1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTaskTimeSpent2').val()

			//actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageSuccessTaskTimeSpentEdit');

			textToShow += actionContainerError.find('.valueForEditTaskTimeSpent1').val()
			textToShow += taskRef
			textToShow += actionContainerError.find('.valueForEditTaskTimeSpent2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action check task progress.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.checkTaskProgress = function ( event ) {
	let elementRiskAssessmentTask = $(this).closest('.riskassessment-task-container');
	let RiskAssessmentTaskId = elementRiskAssessmentTask.find('.riskassessment-task-reference').attr('value');
	let riskId = $(this).closest('.riskassessment-tasks').attr('value');
	let textToShow = '';

	let taskRef = elementRiskAssessmentTask.attr('value');

	let taskProgress = '';
	if (elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).hasClass('progress-checkbox-check')) {
		taskProgress = 0;
		elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).toggleClass('progress-checkbox-check').toggleClass('progress-checkbox-uncheck');
	} else if (elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).hasClass('progress-checkbox-uncheck')) {
		taskProgress = 1;
		elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).toggleClass('progress-checkbox-uncheck').toggleClass('progress-checkbox-check');
	}

	window.eoxiaJS.loader.display($('.riskassessment-task-single-'+ RiskAssessmentTaskId));

	let token = $('.fichecenter.risklist').find('input[name="token"]').val();

	let url = window.location.href.replace(/#.*/, "");

	$.ajax({
		url: url + '&action=checkTaskProgress&token='+token,
		data: JSON.stringify({
			riskAssessmentTaskID: RiskAssessmentTaskId,
			taskProgress: taskProgress,
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))
			let actionContainerSuccess = $('.messageSuccessTaskEdit');
			$('.riskassessment-tasks' + riskId).fadeOut(800);
			$('.riskassessment-tasks' + riskId).fadeIn(800);
			textToShow += actionContainerSuccess.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTask2').val()

			//actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorTaskEdit');

			textToShow += actionContainerError.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerError.find('.valueForEditTask2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Select riskAssessmentTask TimeSpent date hour.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.selectRiskassessmentTaskTimespentDateHour = function( event ) {
	$(this).closest('.nowraponall').find('.select-riskassessmenttask-timespent-datehour').remove();
	$(this).before('<input class="select-riskassessmenttask-timespent-datehour" type="hidden" id="RiskassessmentTaskTimespentDatehour" name="RiskassessmentTaskTimespentDatehour" value='+$(this).val()+'>')
};

/**
 * Select riskAssessmentTask TimeSpent date min.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.selectRiskassessmentTaskTimespentDateMin = function( event ) {
	$(this).closest('.nowraponall').find('.select-riskassessmenttask-timespent-datemin').remove();
	$(this).before('<input class="select-riskassessmenttask-timespent-datemin" type="hidden" id="RiskassessmentTaskTimespentDatemin" name="RiskassessmentTaskTimespentDatemin" value='+$(this).val()+'>')
};

/**
 * Check riskassessmenttask label length
 *
 * @since   9.4.0
 * @version 9.4.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.checkRiskassessmentTaskLabelLength = function( event ) {
	var labelLenght = $(this).val().length;
	if (labelLenght > 255) {
		let actionContainerWarning = $('.messageWarningTaskLabel');
		actionContainerWarning.removeClass('hidden');
		$('.riskassessment-task-create').removeClass('button-blue');
		$('.riskassessment-task-create').addClass('button-grey');
		$('.riskassessment-task-create').addClass('button-disable');
	} else {
		let actionContainerWarning = $('.messageWarningTaskLabel');
		actionContainerWarning.addClass('hidden');
		$('.riskassessment-task-create').addClass('button-blue');
		$('.riskassessment-task-create').removeClass('button-grey');
		$('.riskassessment-task-create').removeClass('button-disable');
	}
};

/**
 * Initialise l'objet "risksign" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.risksign = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.init = function() {
	window.eoxiaJS.risksign.event();
};

/**
 * La méthode contenant tous les événements pour le risksign.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.event = function() {
	$( document ).on( 'click', '.risksign-category-danger .item, .wpeo-table .risksign-category-danger .item', window.eoxiaJS.risksign.selectRiskSign );
	$( document ).on( 'click', '.risksign-create:not(.button-disable)', window.eoxiaJS.risksign.createRiskSign );
	$( document ).on( 'click', '.risksign-save', window.eoxiaJS.risksign.saveRiskSign );
	$( document ).on( 'click', '.risksign-unlink-shared', window.eoxiaJS.risksign.unlinkSharedRiskSign );
	$( document ).on( 'click', '#select_all_shared_risksigns', window.eoxiaJS.risksign.selectAllSharedRiskSign );
};

/**
 * Lors du clic sur un riskSignCategory, remplaces le contenu du toggle et met l'image du risque sélectionné.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.eoxiaJS.risksign.selectRiskSign = function( event ) {
	var element = $(this);
	element.closest('.content').removeClass('active');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle span').hide();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').show();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('src', element.find('img').attr('src'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('aria-label', element.closest('.wpeo-tooltip-event').attr('aria-label'));

	element.closest('.fichecenter').find('.input-hidden-danger').val(element.data('id'));

	var elementParent = $(this).closest('.modal-container');

	// Rend le bouton "active".
	window.eoxiaJS.risksign.haveDataInInput(elementParent);
};

/**
 * Check value on riskSignCategory.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.eoxiaJS.risksign.haveDataInInput = function( elementParent ) {
	var element = elementParent.parent().parent();

	if (element.hasClass('risksign-add-modal')) {
		var risksign_category = element.find('input[name="risksign_category_id"]')
		if ( risksign_category.val() >= 0 ) {
			element.find('.button-disable').removeClass('button-disable');
		}
	}
};

/**
 * Action create risksign.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.createRiskSign = function ( event ) {
	let elementRiskSign = $(this).closest('.fichecenter').find('.risksign-content');

	var category = elementRiskSign.find('.risksign-category input').val();
	var description = elementRiskSign.find('.risksign-description textarea').val();

	window.eoxiaJS.loader.display($('.fichecenter.risksignlist'));

	let token = $('.fichecenter.risksignlist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=add&token='+token,
		type: "POST",
		data: JSON.stringify({
			riskSignCategory: category,
			riskSignDescription: description
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risksignlist').html($(resp).find('#searchFormListRiskSigns'))

			let actionContainerSuccess = $('.messageSuccessRiskSignCreate');

			$('.fichecenter.risksignlist').removeClass('wpeo-loader');

			actionContainerSuccess.html($(resp).find('.risksign-create-success-notice'))
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskCreate');
			actionContainerError.html($(resp).find('.risksign-create-error-notice'))
			actionContainerError.removeClass('hidden');
		}
	});

};

/**
 * Action save risksign.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.saveRiskSign = function ( event ) {
	let editedRiskSignId = $(this).attr('value');
	let elementRiskSign = $(this).closest('.risksign-container').find('.risksign-content');
	let textToShow = ''

	var category = elementRiskSign.find('.risksign-category input').val();
	var description = elementRiskSign.find('.risksign-description textarea').val();

	let riskSignRef =  $('.risksign_row_'+editedRiskSignId).find('.risksign-container > div:nth-child(1)').text();

	window.eoxiaJS.loader.display(elementRiskSign);

	let token = $('.fichecenter.risksignlist').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=saveRiskSign&token='+token,
		data: JSON.stringify({
			riskSignID: editedRiskSignId,
			riskSignCategory: category,
			riskSignDescription: description
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risksignlist').html($(resp).find('#searchFormListRiskSigns'))

			let actionContainerSuccess = $('.messageSuccessRiskSignEdit');

			elementRiskSign.removeClass('wpeo-loader');

			textToShow += actionContainerSuccess.find('.valueForEditRiskSign1').val()
			textToShow += riskSignRef
			textToShow += actionContainerSuccess.find('.valueForEditRiskSign2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			let actionContainerError = $('.messageErrorRiskSignEdit');
			elementRiskSign.removeClass('wpeo-loader');

			textToShow += actionContainerError.find('.valueForEditRiskSign1').val()
			textToShow += riskSignRef
			textToShow += actionContainerError.find('.valueForEditRiskSign2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});

};

/**
 * Action unlink shared risk sign.
 *
 * @since   9.4.0
 * @version 9.4.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.unlinkSharedRiskSign = function ( event ) {
	let risksignId = $(this).attr('value');
	//let elementRisk = $(this).closest('.risk-container').find('.risk-content');
	let elementParent = $('.fichecenter.sharedrisksignlist').find('.div-table-responsive');

	window.eoxiaJS.loader.display($(this));

	let risksignRef =  $('.risksign_row_'+risksignId).find('.risksign-container > div:nth-child(1)').text();
	let url = document.URL.split(/#/);

	let token = $('.fichecenter.risksignlist').find('input[name="token"]').val();

	$.ajax({
		url: url[0] + '&action=unlinkSharedRiskSign&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			risksignID: risksignId,
		}),
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.sharedrisksignlist .opacitymedium.colorblack.paddingleft').html($(resp).find('#searchFormSharedListRiskSigns .opacitymedium.colorblack.paddingleft'))
			let actionContainerSuccess = $('.messageSuccessRiskSignUnlinkShared');

			$('#risksign_row_' + risksignId).fadeOut(800);

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRiskSign1').val()
			textToShow += risksignRef
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRiskSign2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskSignUnlinkShared');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForUnlinkSharedRiskSign1').val()
			textToShow += risksignRef
			textToShow += actionContainerError.find('.valueForUnlinkSharedRiskSign2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action select All shared risk sign.
 *
 * @since   9.4.0
 * @version 9.4.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.selectAllSharedRiskSign = function ( event ) {
	if(this.checked) {
		// Iterate each checkbox
		$(this).closest('.ui-widget').find(':checkbox').not(':disabled').each(function() {
			this.checked = true;
		});
	} else {
		$(this).closest('.ui-widget').find(':checkbox').not(':disabled').each(function() {
			this.checked = false;
		});
	}
};

/**
 * Initialise l'objet "évaluateur" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.evaluator = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluator.init = function() {
	window.eoxiaJS.evaluator.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 9.6.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluator.event = function() {
	$( document ).on( 'click', '.evaluator-create', window.eoxiaJS.evaluator.createEvaluator );
	$( document ).on( 'change', '#fk_user_employer', window.eoxiaJS.evaluator.selectUser );
};

/**
 * Clique sur une des user de la liste.
 *
 * @since   1.0.0
 * @version 9.6.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.eoxiaJS.evaluator.selectUser = function( event ) {
	var elementParent = $(this).closest('.modal-container');
	let userID = elementParent.find('#fk_user_employer').val();
	let token = $('.fichecenter.evaluatorlist').find('input[name="token"]').val();

	window.eoxiaJS.loader.display(elementParent.find('input[name="evaluatorJob"]'));

	$.ajax({
		url:  document.URL + '&action=getEvaluatorJob&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			userID: userID
		}),
		contentType: false,
		success: function ( resp ) {
			elementParent.find('input[name="evaluatorJob"]').val($(resp).find('input[name="evaluatorJob"]').val())
			elementParent.find('input[name="evaluatorJob"]').removeClass('wpeo-loader')
		},
		error: function ( resp ) {
		}
	});

	// Rend le bouton "active".
	window.eoxiaJS.evaluator.haveDataInInput(elementParent);
}

/**
 * Check value on evaluatorUser.
 *
 * @since   9.6.0
 * @version 9.6.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.eoxiaJS.evaluator.haveDataInInput = function( elementParent ) {
	var element = elementParent.parent().parent();

	if (element.hasClass('evaluator-add-modal')) {
		var evaluator_user = element.find('#fk_user_employer');
		if ( evaluator_user.val() > 0 ) {
			element.find('.button-disable').removeClass('button-disable');
		} else {
			element.find('.evaluator-create').addClass('button-disable');
		}
	}
};

/**
 * Action create evaluator.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluator.createEvaluator = function ( event ) {
	let elementEvaluator = $(this).closest('.fichecenter').find('.evaluator-content');

	var userName = $('#select2-fk_user_employer-container').attr('title')
	var userID = $('#fk_user_employer').find("option:contains('"+userName+"')").attr('value')

	var date     = elementEvaluator.find('#EvaluatorDate').val();
	var duration = elementEvaluator.find('.evaluator-duration .duration').val();
	var job      = elementEvaluator.find('.evaluatorJob').val();

	let elementParent = $(this).closest('.fichecenter').find('.div-table-responsive');

	window.eoxiaJS.loader.display(elementParent);

	let token = $('.fichecenter').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=add&token='+token,
		type: "POST",
		data: JSON.stringify({
			evaluatorID: userID,
			date: date,
			duration: duration,
			job: job
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter').html($(resp).find('#searchFormList'))

			let actionContainerSuccess = $('.messageSuccessEvaluatorCreate');

			actionContainerSuccess.find($(resp).find('.evaluator-create-success-notice'))
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorEvaluatorCreate');

			actionContainerError.find($(resp).find('.evaluator-create-error-notice'))
			actionContainerError.removeClass('hidden');
		}
	});

};

/**
 * Initialise l'objet "digiriskusers" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.digiriskusers = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.digiriskusers.init = function() {
	window.eoxiaJS.digiriskusers.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.digiriskusers.event = function() {
	$( document ).on( 'input', '.digirisk-users #firstname', window.eoxiaJS.digiriskusers.fillEmail );
	$( document ).on( 'input', '.digirisk-users #lastname', window.eoxiaJS.digiriskusers.fillEmail );
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
window.eoxiaJS.digiriskusers.fillEmail = function( event ) {

	var firstname = $('.digirisk-users #firstname').val()
	var lastname = $('.digirisk-users #lastname').val()
	var domainMail = $( '.input-domain-mail' ).val();

	var together = window.eoxiaJS.digiriskusers.removeDiacritics( firstname + '.' + lastname + '@' + domainMail ).toLowerCase();

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
window.eoxiaJS.digiriskusers.removeDiacritics = function( input ) {
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

/**
 * Initialise l'objet "notice" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.notice = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.notice.init = function() {
	window.eoxiaJS.notice.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.notice.event = function() {
	$( document ).on( 'click', '.notice-close', window.eoxiaJS.notice.closeNotice );
	$( document ).on( 'click', '.notice-subtitle', window.eoxiaJS.notice.lineBlink );
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
window.eoxiaJS.notice.closeNotice = function( event ) {
	$(this).closest('.wpeo-notice').fadeOut(function () {
		$(this).closest('.wpeo-notice').addClass("hidden");
	});

	if ($(this).hasClass('notice-close-forever')) {
		let token = $(this).closest('.wpeo-notice').find('input[name="token"]').val();
		let querySeparator = '?';

		document.URL.match(/\?/) ? querySeparator = '&' : 1

		$.ajax({
			url: document.URL + querySeparator + 'action=closenotice&token='+token,
			type: "POST",
		});
	}
};

/**
 * Fais disparaître & réapparaître la ligne du risque concerné par l'action
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.eoxiaJS.notice.lineBlink = function( event ) {
	var jquerySelector = $(this).closest('.notice-content').find('a').attr('href');
	if (jquerySelector.match(/RK/)) {
		jquerySelector = '#risk_row_' + jquerySelector.split(/RK/)[1]
	}
	$(jquerySelector).fadeOut(200)
	$(jquerySelector).fadeIn(200)
};


/**
 * Initialise l'objet "ticket" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.ticket = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.init = function() {
	window.eoxiaJS.ticket.event();
};

/**
 * La méthode contenant tous les événements pour les tickets.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.event = function() {
	$( document ).on( 'click', '.ticket-parentCategory', window.eoxiaJS.ticket.selectParentCategory );
	$( document ).on( 'click', '.ticket-subCategory', window.eoxiaJS.ticket.selectSubCategory );
	$( document ).on( 'submit', '#sendFile', window.eoxiaJS.ticket.tmpStockFile );
	$( document ).on( 'click', '.linked-file-delete', window.eoxiaJS.ticket.removeFile );
	$( document ).on( 'change', '.add-dashboard-info', window.eoxiaJS.ticket.addDashBoardTicketInfo );
	$( document ).on( 'click', '.close-dashboard-info', window.eoxiaJS.ticket.closeDashBoardTicketInfo );
	$( document ).on( 'keyup', '.email', window.eoxiaJS.ticket.checkValidEmail );
};

/**
 * Mets à jour les input du formulaire
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.updateFormData = function( ) {

	let parentCategoryID = window.eoxiaJS.ticket.getParentCategory()
	let subCategoryID = window.eoxiaJS.ticket.getSubCategory()

	$('.ticket-parentCategory.active').removeClass('active')
	$('.ticket-parentCategory'+parentCategoryID).addClass('active')

	$('.subCategories').attr('style','display:none')
	$('.children'+parentCategoryID).attr('style','')

	$('.ticket-subCategory.active').removeClass('active')
	$('.ticket-subCategory'+subCategoryID).addClass('active')
};

/**
 * Clique sur une des catégories parentes de la liste.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.selectParentCategory = function( ) {
	let subCategoryInput = $('.ticketpublicarea').find("#subCategory");
	let parentCategoryInput = $('.ticketpublicarea').find("#parentCategory");

	subCategoryInput.val(0)
	parentCategoryInput.val($(this).attr('id'))

	window.eoxiaJS.ticket.updateFormData()
};

/**
 * Récupère la valeur de la catégorie parente sélectionnée
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.getParentCategory = function( ) {
	return $('.ticketpublicarea').find("#parentCategory").val()
};

/**
 * Clique sur une des catégories enfants de la liste.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.selectSubCategory = function( ) {
	let subCategoryInput = $('.ticketpublicarea').find("#subCategory");
	subCategoryInput.val($(this).attr('id'))

	window.eoxiaJS.ticket.updateFormData()
};

/**
 * Récupère la valeur de la catégorie enfant sélectionnée
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.getSubCategory = function(  ) {
	return $('.ticketpublicarea').find("#subCategory").val()
};

/**
 * Upload automatiquement le(s) fichier(s) séelectionnés dans ecm/digiriskdolibarr/ticket/tmp/__REF__
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.tmpStockFile = function( ) {
	event.preventDefault()

	var files = $('#sendfile').prop('files');

	const formData = new FormData();
	for (let i = 0; i < files.length; i++) {
		let file = files[i]
		formData.append('files[]', file)
	}
	var ticket_id = $('#ticket_id').val()
    let querySeparator = '?'
    document.URL.match(/\?/) ? querySeparator = '&' : 1

	window.eoxiaJS.loader.display($('.files-uploaded'));

    fetch(document.URL + querySeparator + 'action=sendfile&ticket_id='+ticket_id, {
		method: 'POST',
		body: formData,
	}).then((resp) => {
        $('#sendFileForm').load(document.URL+ querySeparator + 'ticket_id='+ticket_id + ' #fileLinkedTable')
})
};

/**
 * Upload automatiquement le(s) fichier(s) séelectionnés dans ecm/digiriskdolibarr/ticket/tmp/__REF__
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.removeFile = function( event ) {
	let filetodelete = $(this).attr('value');
	filetodelete = filetodelete.replace('_mini', '')
	let ticket_id = $('#ticket_id').val()
    let querySeparator = '?'

    document.URL.match(/\?/) ? querySeparator = '&' : 1

	fetch(document.URL + querySeparator + 'action=removefile&filetodelete='+filetodelete+'&ticket_id='+ticket_id, {
		method: 'POST',
	}).then((resp) => {
		$(this).parent().parent().hide()
	})
};

/**
 * Add ticket dashboard info for a category by service
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.addDashBoardTicketInfo = function() {
	let selectTitle = $('#select2-boxcombo-container').attr('title')
	let digiriskelementID = selectTitle.split(' : ')[0];
	let catID = selectTitle.split(' : ')[2];
	let querySeparator = '?';

	let token = $('.dashboardticket').find('input[name="token"]').val();

	document.URL.match(/\?/) ? querySeparator = '&' : 1

	$.ajax({
		url: document.URL + querySeparator + 'action=adddashboardinfo&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			digiriskelementID: digiriskelementID,
			catID: catID
		}),
		contentType: false,
		success: function ( resp ) {
			window.location.reload();
		},
		error: function ( ) {
		}
	});
};

/**
 * Close ticket dashboard info for a category by service
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.closeDashBoardTicketInfo = function() {
	let box = $(this);
	let digiriskelementID = $(this).attr('data-digiriskelementid');
	let catID = $(this).attr('data-catid');
	let querySeparator = '?';

	let token = $('.dashboardticket').find('input[name="token"]').val();

	document.URL.match(/\?/) ? querySeparator = '&' : 1

	$.ajax({
		url: document.URL + querySeparator + 'action=closedashboardinfo&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			digiriskelementID: digiriskelementID,
			catID: catID
		}),
		contentType: false,
		success: function ( resp ) {
			box.closest('.box-flex-item').fadeOut(400)
            $('.add-widget-box').attr('style', '')
			$('.add-widget-box').html($(resp).find('.add-widget-box').children())
		},
		error: function ( ) {
		}
	});
};

/**
 * Check if email is valid
 *
 * @since   9.6.0
 * @version 9.6.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.checkValidEmail = function() {
	var reEmail = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	if (reEmail.test(this.value) == false) {
		$(this).css("border", "3px solid red");
	} else {
		$(this).css("border", "3px solid green");
	}
};

/**
 * Initialise l'objet "preventionplan" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.preventionplan = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.preventionplan.init = function() {
	window.eoxiaJS.preventionplan.event();
};

/**
 * La méthode contenant tous les événements pour les preventionplans.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.preventionplan.event = function() {
	$( document ).on( 'click', '#prior_visit_bool', window.eoxiaJS.preventionplan.showDateAndText );
};

/**
 * Show date and text for prevention plan prior visit.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.preventionplan.showDateAndText = function() {
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

/**
 * Initialise l'objet "signature" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.document = {};

/**
 * Initialise le canvas document
 *
 * @since   8.1.2
 * @version 8.1.2
 */
window.eoxiaJS.document.canvas;

/**
 * Initialise le boutton document
 *
 * @since   8.1.2
 * @version 8.1.2
 */
window.eoxiaJS.document.buttonSignature;

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   8.1.2
 * @version 8.1.2
 *
 * @return {void}
 */
window.eoxiaJS.document.init = function() {
	window.eoxiaJS.document.event();
};

/**
 * La méthode contenant tous les événements pour les documents.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.document.event = function() {
	$( document ).on( 'click', '#builddoc_generatebutton', window.eoxiaJS.document.displayLoader );
	$( document ).on( 'click', ' .send-risk-assessment-document-by-mail', window.eoxiaJS.document.displayLoader );
};

/**
 * Display loader on generation document.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.document.displayLoader = function(  ) {
	window.eoxiaJS.loader.display($('#builddoc_generatebutton'));
	window.eoxiaJS.loader.display($('.tabBar'));
};

/**
 * Initialise l'objet "keyEvent" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.keyEvent = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.init = function() {
	window.eoxiaJS.keyEvent.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.event = function() {
	$( document ).on( 'keydown', window.eoxiaJS.keyEvent.keyup );
	$( document ).on( 'keyup', '.url-container' , window.eoxiaJS.keyEvent.checkUrlFormat );
	$( document ).on( 'click', '.modal-active:not(.modal-container)' , window.eoxiaJS.modal.closeModal );
}

/**
 * Action modal close & validation with key events
 *
 * @since   1.0.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.keyup = function( event ) {
	if ( 'Escape' === event.key  ) {
		$(this).find('.modal-active .modal-close .fas.fa-times').first().click();
	}

	if ( 'Enter' === event.key )  {
		event.preventDefault()
		if (!$('input, textarea').is(':focus')) {
			$(this).find('.modal-active .modal-footer .wpeo-button').not('.button-disable').first().click();
		} else {
			$('textarea:focus').val($('textarea:focus').val() + '\n')
		}
	}
};

/**
 * Check url format of url containers
 *
 * @since   1.0.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.checkUrlFormat = function( event ) {
	var urlRegex = /[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi;
	if ($(this).val().match(urlRegex)) {
		$(this).attr('style', 'border: solid; border-color: green')
	} else if ($('input:focus').val().length > 0) {
		$(this).attr('style', 'border: solid; border-color: red')
	}
};

/**
 * Initialise l'objet "menu" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.menu = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.init = function() {
	window.eoxiaJS.menu.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.event = function() {
	$(document).on( 'click', ' .blockvmenu', window.eoxiaJS.menu.toggleMenu);
	$(document).ready(function() { window.eoxiaJS.menu.setMenu()});
}

/**
 * Action Toggle main menu.
 *
 * @since   8.5.0
 * @version 9.4.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.toggleMenu = function() {

	var menu = $(this).closest('#id-left').find('a.vmenu, font.vmenudisabled, span.vmenu');
	var elementParent = $(this).closest('#id-left').find('div.vmenu')
	var text = '';

	if ($(this).find('span.vmenu').find('.fa-chevron-circle-left').length > 0) {

		menu.each(function () {
			text = $(this).html().split('</i>');
			if (text[1].match(/&gt;/)) {
				text[1] = text[1].replace(/&gt;/, '')
			}
			$(this).attr('title', text[1])
			$(this).html(text[0]);
		});

		elementParent.css('width', '30px');
		elementParent.find('.blockvmenusearch').hide();
		$('span.vmenu').attr('title', ' Agrandir le menu')

		$('span.vmenu').html($('span.vmenu').html());

		$(this).find('span.vmenu').find('.fa-chevron-circle-left').removeClass('fa-chevron-circle-left').addClass('fa-chevron-circle-right');
		localStorage.setItem('maximized', 'false')

	} else if ($(this).find('span.vmenu').find('.fa-chevron-circle-right').length > 0) {

		menu.each(function () {
			$(this).html($(this).html().replace('&gt;','') + ' ' + $(this).attr('title'));
		});

		elementParent.css('width', '188px');
		elementParent.find('.blockvmenusearch').show();
		$('div.menu_titre').attr('style', 'width: 188px !important; cursor : pointer' )
		$('span.vmenu').attr('title', ' Réduire le menu')
		$('span.vmenu').html('<i class="fas fa-chevron-circle-left"></i> Réduire le menu');

		localStorage.setItem('maximized', 'true')

		$(this).find('span.vmenu').find('.fa-chevron-circle-right').removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-left');
	}
};

/**
 * Action set  menu.
 *
 * @since   8.5.0
 * @version 9.0.1
 *
 * @return {void}
 */
window.eoxiaJS.menu.setMenu = function() {
	if ($('.blockvmenu.blockvmenufirst').length > 0) {
		if ($('.blockvmenu.blockvmenufirst').html().match(/digiriskdolibarr/)) {
			$('span.vmenu').find('.fa-chevron-circle-left').parent().parent().parent().attr('style', 'cursor:pointer ! important')

			if (localStorage.maximized == 'false') {
				$('#id-left').attr('style', 'display:none !important')
			}

			if (localStorage.maximized == 'false') {
				var text = '';
				var menu = $('#id-left').find('a.vmenu, font.vmenudisabled, span.vmenu');
				var elementParent = $(document).find('div.vmenu')

				menu.each(function () {
					text = $(this).html().split('</i>');
					$(this).attr('title', text[1])
					$(this).html(text[0]);
				});

				$('#id-left').attr('style', 'display:block !important')
				$('div.menu_titre').attr('style', 'width: 50px !important')
				$('span.vmenu').attr('title', ' Agrandir le menu')

				$('span.vmenu').html($('span.vmenu').html())
				$('span.vmenu').find('.fa-chevron-circle-left').removeClass('fa-chevron-circle-left').addClass('fa-chevron-circle-right');

				elementParent.css('width', '30px');
				elementParent.find('.blockvmenusearch').hide();
			}
			localStorage.setItem('currentString', '')
			localStorage.setItem('keypressNumber', 0)
		}
	}
};

/**
 * Initialise l'objet "accident" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   8.5.0
 * @version 8.5.0
 */
window.eoxiaJS.accident = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.accident.init = function() {
	window.eoxiaJS.accident.event();
};

/**
 * La méthode contenant tous les événements pour les accidents.
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.accident.event = function() {
	$( document ).on( 'submit', ' .sendfile', window.eoxiaJS.accident.tmpStockFile );
	$( document ).on( 'click', ' .linked-file-delete-workstop', window.eoxiaJS.accident.removeFile );
	$( document ).on( 'change', '#external_accident', window.eoxiaJS.accident.showExternalAccidentLocation );
};

/**
 * Upload automatiquement le(s) fichier(s) séelectionnés dans digiriskdolibarr/accident/accident_ref/workstop/__REF__ (temp ou ref du workstop)
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.accident.tmpStockFile = function(id, subaction = '') {
	var files = $('#sendfile').prop('files');

	const formData = new FormData();
	for (let i = 0; i < files.length; i++) {
		let file = files[i]
		formData.append('files[]', file)
	}

	let token = $('.div-table-responsive-no-min').find('input[name="token"]').val();
	let subactionPost = subaction.length > 0 ? '&subaction=' + subaction : ''
	$.ajax({
		url: document.URL + '&action=sendfile&objectlineid=' + id + '&token=' + token + subactionPost,
		type: "POST",
		processData: false,
		contentType: false,
		data: formData,
		success: function ( resp ) {
			// ca ne marche pas car l'action ici est sendfile et plus editline donc le % resp ne peut pas contenir la réponse
			$('#sendFileForm' + id).html($(resp).find('#fileLinkedTable' + id))
		},
		error: function ( ) {
		}
	});
};

/**
 * Supprime le fichier séelectionné dans  digiriskdolibarr/accident/accident_ref/workstop/__REF__ (temp ou ref du workstop)
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */

window.eoxiaJS.accident.removeFile = function( event ) {
	let filetodelete = $(this).attr('value');
	let subActionPost = $(this).hasClass('edit-line') ? '&subaction=editline' : ''
	filetodelete = filetodelete.replace('_mini', '')
	let objectlineid = $(this).closest('.objectline').attr('value')
	let token = $('.div-table-responsive-no-min').find('input[name="token"]').val();

	$.ajax({
		url: document.URL + '&action=removefile&filetodelete='+filetodelete+'&objectlineid='+objectlineid+'&token='+token+subActionPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('#sendFileForm' + objectlineid).html($(resp).find('#fileLinkedTable' + objectlineid))
		},
		error: function ( ) {
		}
	});
};

/**
 * Show external Accident location.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.accident.showExternalAccidentLocation = function() {
	let fkElementField = $(this).closest('.accident-table').find('.fk_element_field')
	let fkSocField = $(this).closest('.accident-table').find('.fk_soc_field')
	let accidentLocationField = $(this).closest('.accident-table').find('.accident_location_field')
	let externalAccident = $(this).closest('.accident-table').find('#select2-external_accident-container').attr('title')

	switch (externalAccident) {
		case 'Non':
			fkElementField.attr('style', '')
			fkSocField.attr('style', 'display:none')
			accidentLocationField.attr('style', 'display:none')
			fkElementField.removeClass('hidden')
			fkSocField.addClass('hidden')
			accidentLocationField.addClass('hidden')
			break;
		case 'Oui':
			fkElementField.attr('style', 'display:none')
			fkSocField.attr('style', '')
			accidentLocationField.attr('style', 'display:none')
			fkElementField.addClass('hidden')
			fkSocField.removeClass('hidden')
			accidentLocationField.addClass('hidden')
			break;
		case 'Autre':
			fkElementField.attr('style', 'display:none')
			fkSocField.attr('style', 'display:none')
			accidentLocationField.attr('style', '')
			fkElementField.addClass('hidden')
			fkSocField.addClass('hidden')
			accidentLocationField.removeClass('hidden')
			break;
	}
};

/**
 * Initialise l'objet "dashboard" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.dashboard = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.dashboard.init = function() {
	window.eoxiaJS.dashboard.event();
};

/**
 * La méthode contenant tous les événements pour les dashboards.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.dashboard.event = function() {
	$( document ).on( 'change', '.add-dashboard-widget', window.eoxiaJS.dashboard.addDashBoardInfo );
	$( document ).on( 'click', '.close-dashboard-widget', window.eoxiaJS.dashboard.closeDashBoardInfo );
};

/**
 * Add widget dashboard info
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.eoxiaJS.dashboard.addDashBoardInfo = function() {
	var dashboardWidgetForm = document.getElementById('dashBoardForm');
	var formData = new FormData(dashboardWidgetForm);
	let dashboardWidgetName = formData.get('boxcombo')
	let querySeparator = '?';
	let token = $('.dashboard').find('input[name="token"]').val();
	document.URL.match(/\?/) ? querySeparator = '&' : 1

	$.ajax({
		url: document.URL + querySeparator + 'action=adddashboardinfo&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			dashboardWidgetName: dashboardWidgetName
		}),
		contentType: false,
		success: function ( resp ) {
			window.location.reload();
		},
		error: function ( ) {
		}
	});
};

/**
 * Close widget dashboard info
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.eoxiaJS.dashboard.closeDashBoardInfo = function() {
	let box = $(this);
	let dashboardWidgetName = $(this).attr('data-widgetname');
	let querySeparator = '?';
	let token = $('.dashboard').find('input[name="token"]').val();
	document.URL.match(/\?/) ? querySeparator = '&' : 1

	$.ajax({
		url: document.URL + querySeparator + 'action=closedashboardinfo&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			dashboardWidgetName: dashboardWidgetName
		}),
		contentType: false,
		success: function ( resp ) {
			box.closest('.box-flex-item').fadeOut(400)
			$('.add-widget-box').attr('style', '')
			$('.add-widget-box').html($(resp).find('.add-widget-box').children())
		},
		error: function ( ) {
		}
	});
};

/**
 * Initialise l'objet "file" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.file = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.file.init = function() {
};

/**
 * Close widget dashboard info
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.eoxiaJS.file.getThumbName = function(file, type = 'small') {
	let fileExtension = file.split('.').pop();
	let fileName = file.split('.'+fileExtension)[0]
	let thumbName = fileName + '_' + type + '.' + fileExtension
	return thumbName;
};
