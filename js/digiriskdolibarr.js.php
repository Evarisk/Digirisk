/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    digiriskdolibarr/js/digiriskdolibarr.js.php
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
		if ( ! window.eoxiaJS.scriptsLoaded ) {
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

	jQuery( document ).ready( window.eoxiaJS.init );
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
 * La méthode contenant tous les évènements pour la navigation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.navigation.event = function() {
	// Main Menu Digirisk Society
	jQuery( document ).on( 'click', '.digirisk-wrap .navigation-container .unit-container .toggle-unit', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '#newGroupment', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '#newWorkunit', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '.digirisk-wrap .navigation-container .toolbar div', window.eoxiaJS.navigation.toggleAll );
	jQuery( document ).on( 'click', '#slider', window.eoxiaJS.navigation.setUnitActive );
	jQuery( document ).on( 'click', '#newGroupment', window.eoxiaJS.navigation.redirect );
	jQuery( document ).on( 'click', '#newWorkunit', window.eoxiaJS.navigation.redirect );
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

	if ( jQuery( this ).find( '.toggle-icon' ).hasClass( 'fa-chevron-down' ) ) {

		jQuery(this).find( '.toggle-icon' ).removeClass('fa-chevron-down').addClass('fa-chevron-right');
		var idUnToggled = jQuery(this).closest('.unit').attr('id').split('unit')[1]
		jQuery(this).closest('.unit').removeClass('toggled');

		MENU.delete(idUnToggled)
		localStorage.setItem('menu',  JSON.stringify(Array.from(MENU.keys())))

	} else if ( jQuery( this ).find( '.toggle-icon' ).hasClass( 'fa-chevron-right' ) ){

		jQuery(this).find( '.toggle-icon' ).removeClass('fa-chevron-right').addClass('fa-chevron-down');
		jQuery(this).closest('.unit').addClass('toggled');

		var idToggled = jQuery(this).closest('.unit').attr('id').split('unit')[1]
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

	if ( jQuery( this ).hasClass( 'toggle-plus' ) ) {

		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit' ).addClass( 'toggled' );

		// local storage add all
		let MENU = $( '.digirisk-wrap .navigation-container .workunit-list .unit .title' ).get().map(v.attributes.value.value)
		localStorage.setItem('menu', JSON.stringify(Object.values(MENU)) );

	}

	if ( jQuery( this ).hasClass( 'toggle-minus' ) ) {
		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit.toggled' ).removeClass( 'toggled' );
		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').addClass( 'fa-chevron-right').removeClass( 'fa-chevron-down' );

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

	jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
	let id = $(this).attr('value');

	jQuery( this ).closest( '.unit' ).addClass( 'active' );
	jQuery( this ).closest( '.unit' ).attr( 'value', id );

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

	//empty and fill object card
	$('#cardContent').load( URLToGo + ' #cardContent' , id);
	return false;
};

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
 * La méthode contenant tous les évènements pour la modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.modal.event = function() {
	jQuery( document ).on( 'click', '.modal-close', window.eoxiaJS.modal.closeModal );
	jQuery( document ).on( 'click', '.modal-open', window.eoxiaJS.modal.openModal );
	jQuery( document ).on( 'click', '.modal-refresh', window.eoxiaJS.modal.refreshModal );
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
	} else if ($(this).hasClass('risk-evaluation-photo')) {
		$(this).closest('.risk-evaluation-photo-container').find('#risk_evaluation_photo' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('risk-evaluation-edit')) {
		$('#risk_evaluation_edit' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('evaluator-add')) {
		$('#evaluator_add' + idSelected).addClass('modal-active');
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
	if ($(this).hasClass('riskassessment-task-list')) {
		$('#risk_assessment_task_list' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-edit')) {
		$('#risk_assessment_task_edit' + idSelected).addClass('modal-active');
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
	$('.modal-active').removeClass('modal-active')
	$('.notice').addClass('hidden');
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
	jQuery( document ).on( 'keyup', window.eoxiaJS.dropdown.keyup );
	jQuery( document ).on( 'click', '.wpeo-dropdown:not(.dropdown-active) .dropdown-toggle:not(.disabled)', window.eoxiaJS.dropdown.open );
	jQuery( document ).on( 'click', '.wpeo-dropdown.dropdown-active .dropdown-content', function(e) { e.stopPropagation() } );
	jQuery( document ).on( 'click', '.wpeo-dropdown.dropdown-active:not(.dropdown-force-display) .dropdown-content .dropdown-item', window.eoxiaJS.dropdown.close  );
	jQuery( document ).on( 'click', '.wpeo-dropdown.dropdown-active', function ( e ) { window.eoxiaJS.dropdown.close( e ); e.stopPropagation(); } );
	jQuery( document ).on( 'click', 'body', window.eoxiaJS.dropdown.close );
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
	var triggeredElement = jQuery( this );
	var angleElement = triggeredElement.find('[data-fa-i2svg]');
	var callbackData = {};
	var key = undefined;

	window.eoxiaJS.dropdown.close( event, jQuery( this ) );

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
	var _element = jQuery( this );
	jQuery( '.wpeo-dropdown.dropdown-active:not(.no-close)' ).each( function() {
		var toggle = jQuery( this );
		var triggerObj = {
			close: true
		};

		_element.trigger( 'dropdown-before-close', [ toggle, _element, triggerObj ] );

		if ( triggerObj.close ) {
			toggle.removeClass( 'dropdown-active' );

			/* Toggle Button Icon */
			var angleElement = jQuery( this ).find('.dropdown-toggle').find('[data-fa-i2svg]');
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
 * @param  {void} button [description]
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

/*

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
		jQuery( '.wpeo-tooltip' ).remove();
	}

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Tooltip
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.tooltip.event = function() {
		jQuery( document ).on( 'mouseenter', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onEnter );
		jQuery( document ).on( 'mouseleave', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onOut );
	};

	window.eoxiaJS.tooltip.onEnter = function( event ) {
		window.eoxiaJS.tooltip.display( jQuery( this ) );
	};

	window.eoxiaJS.tooltip.onOut = function( event ) {
		window.eoxiaJS.tooltip.remove( jQuery( this ) );
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
		var direction = ( jQuery( element ).data( 'direction' ) ) ? jQuery( element ).data( 'direction' ) : 'top';
		var el = jQuery( '<span class="wpeo-tooltip tooltip-' + direction + '">' + jQuery( element ).attr( 'aria-label' ) + '</span>' );
		var pos = jQuery( element ).position();
		var offset = jQuery( element ).offset();
		jQuery( element )[0].tooltipElement = el;
		jQuery( 'body' ).append( jQuery( element )[0].tooltipElement );

		if ( jQuery( element ).data( 'color' ) ) {
			el.addClass( 'tooltip-' + jQuery( element ).data( 'color' ) );
		}

		var top = 0;
		var left = 0;

		switch( jQuery( element ).data( 'direction' ) ) {
			case 'left':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( jQuery( element ).outerHeight() / 2 ) ) + 'px';
				left = ( offset.left - el.outerWidth() - 10 ) + 3 + 'px';
				break;
			case 'right':
				top = ( offset.top - ( el.outerHeight() / 2 ) + ( jQuery( element ).outerHeight() / 2 ) ) + 'px';
				left = offset.left + jQuery( element ).outerWidth() + 8 + 'px';
				break;
			case 'bottom':
				top = ( offset.top + jQuery( element ).height() + 10 ) + 10 + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( jQuery( element ).outerWidth() / 2 ) ) + 'px';
				break;
			case 'top':
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( jQuery( element ).outerWidth() / 2 ) ) + 'px';
				break;
			default:
				top = offset.top - el.outerHeight() - 4  + 'px';
				left = ( offset.left - ( el.outerWidth() / 2 ) + ( jQuery( element ).outerWidth() / 2 ) ) + 'px';
				break;
		}

		el.css( {
			'top': top,
			'left': left,
			'opacity': 1
		} );

		jQuery( element ).on("remove", function() {
			jQuery( jQuery( element )[0].tooltipElement ).remove();

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
		if ( jQuery( element )[0] && jQuery( element )[0].tooltipElement ) {
			jQuery( jQuery( element )[0].tooltipElement ).remove();
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
			var el = jQuery( '<span class="loader-spin"></span>' );
			element[0].loaderElement = el;
			element.append( element[0].loaderElement );
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Loader
	 *
	 * @param  {void} element [description]
	 * @returns {void}         [description]
	 */
	window.eoxiaJS.loader.remove = function( element ) {
		if ( 0 < element.length && ! element.hasClass( 'button-progress' ) ) {
			element.removeClass( 'wpeo-loader' );

			jQuery( element[0].loaderElement ).remove();
		}
	};
}

/**
 * Initialise l'objet "photo" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.photo = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.photo.init = function() {
	window.eoxiaJS.photo.event();
};

/**
 * La méthode contenant tous les évènements pour le photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.photo.event = function() {
	// Photos
	jQuery( document ).on( 'click', '.clickable-photo', window.eoxiaJS.photo.selectPhoto );
	jQuery( document ).on( 'click', '.save-photo', window.eoxiaJS.photo.savePhoto );
	jQuery( document ).on( 'click', '.modal-content .formattachnewfile .button', window.eoxiaJS.photo.sendPhoto );
	jQuery( document ).on( 'click', '.clicked-photo-preview', window.eoxiaJS.photo.previewPhoto );
}

/**
 * Select photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.photo.selectPhoto = function( event ) {
	let photoID = $(this).attr('value');
	let element = $(this).attr('element');
	let parent = $(this).closest('.modal-content')

	parent.find('.clicked-photo').attr('style', 'none !important');
	parent.find('.clicked-photo').removeClass('clicked-photo');

	parent.find('.clickable-photo'+photoID).attr('style', 'border: 5px solid #0d8aff !important');
	parent.find('.clickable-photo'+photoID).addClass('clicked-photo');

	$(this).closest('.'+element+'-photo-container').find('.'+element+'-photo-single .filename').val(parent.find('.clicked-photo .filename').val());
	$(this).closest('.'+element+'-photo-container').find('.'+element+'-photo-single img').attr('src' , parent.find('.clicked-photo img').attr('src'));

};

/**
 * Action save photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.photo.savePhoto = function( event ) {
	$('.wpeo-modal.modal-photo.modal-active').removeClass('modal-active');
};

/**
 * Action send photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.photo.sendPhoto = function( event ) {

	event.preventDefault()
	let element  = $(this).closest('.nowrap');
	let files    = element.find("input[name='userfile[]']").prop("files");
	let formdata = new FormData();

	$.each(files, function(index, file) {
		formdata.append("userfile[]", file);
	})
	$.ajax({
		url: document.URL + "&action=uploadPhoto",
		type: "POST",
		data: formdata,
		processData: false,
		contentType: false,
	});

	let elementParent = $(this).closest('.modal-container').find('.ecm-photo-list-content');

	elementParent.empty();
	window.eoxiaJS.loader.display(elementParent);

	setTimeout(function(){
		elementParent.load( document.URL + ' .ecm-photo-list-');
		elementParent.removeClass('wpeo-loader');
	}, 800);
};

/**
 * Action preview photo.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.photo.previewPhoto = function( event ) {
	setTimeout(function(){
		jQuery( document ).find('.ui-dialog').addClass('preview-photo');
	}, 200);
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
 * La méthode contenant tous les évènements pour le risk.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.event = function() {
	jQuery( document ).on( 'click', '.category-danger .item, .wpeo-table .category-danger .item', window.eoxiaJS.risk.selectDanger );
	jQuery( document ).on( 'click', '.risk-create:not(.button-disable)', window.eoxiaJS.risk.createRisk );
	jQuery( document ).on( 'click', '.risk-save', window.eoxiaJS.risk.saveRisk );
	jQuery( document ).on( 'click', '.risk-delete', window.eoxiaJS.risk.deleteRisk );
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
	var element = jQuery(this);
	element.closest('.content').removeClass('active');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle span').hide();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').show();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('src', element.find('img').attr('src'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('aria-label', element.closest('.wpeo-tooltip-event').attr('aria-label'));

	element.closest('.fichecenter').find('.input-hidden-danger').val(element.data('id'));

	var elementParent = jQuery(this).closest('.modal-container');

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
 * Action create risk.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.createRisk = function ( event ) {
	let elementRisk = $(this).closest('.fichecenter').find('.risk-content');
	let elementEvaluation = $(this).closest('.fichecenter').find('.risk-evaluation-container');
	let elementTask = $(this).closest('.fichecenter').find('.riskassessment-task');
	let actionContainerSuccess = $('.messageSuccessRiskCreate');
	let actionContainerError = $('.messageErrorRiskCreate');

	var category = elementRisk.find('.risk-category input').val();
	var categoryPost = '';
	if (category !== 0) {
		categoryPost = '&category=' + category;
	}

	var description = elementRisk.find('.risk-description textarea').val();
	var descriptionPost = '';
	if (description !== '') {
		descriptionPost = '&riskComment=' + encodeURI(description);
	}

	var method = elementEvaluation.find('.risk-evaluation-header .risk-evaluation-method').val();
	var methodPost = '';
	if (method !== '') {
		methodPost = '&cotationMethod=' + method;
	}

	var cotation = elementEvaluation.find('.risk-evaluation-seuil').val();
	var cotationPost = '';
	if (cotation !== 0) {
		cotationPost = '&cotation=' + cotation;
	}

	let criteres = '';
	Object.values($('.table-cell.active.cell-0')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres += '&' + $(v).data( 'type' ) + '=' + $(v).data( 'seuil' );
		}
	})

	var photo = elementEvaluation.find('.risk-evaluation-photo-single .filename').val();
	var photoPost = '';
	if (photo !== 0) {
		photoPost = '&photo=' + encodeURI(photo);
	}

	var comment = elementEvaluation.find('.risk-evaluation-comment textarea').val();
	var commentPost = '';
	if (comment !== '') {
		commentPost = '&evaluationComment=' + encodeURI(comment);
	}

	var task = elementTask.find('input').val();
	var taskPost = '';
	if (task !== '') {
		taskPost = '&tasktitle=' + encodeURI(task);
	}

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($('.fichecenter'));

	$.ajax({
		url: document.URL + '&action=add' + categoryPost + descriptionPost + methodPost + cotationPost + criteres + photoPost + commentPost + taskPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {

			let numberOfRisks = $('.valignmiddle.col-title');
			numberOfRisks.load( document.URL + ' .table-fiche-title .titre.inline-block');

			let modalRisk = $('.risk-add-modal');
			modalRisk.load( document.URL + ' .modal-risk-0');

			actionContainerSuccess.empty()
			actionContainerSuccess.load(' .risk-create-success-notice')
			actionContainerSuccess.removeClass('hidden');

			elementParent.empty();
			elementParent.load( document.URL + ' .tagtable.liste')

			$('.fichecenter').removeClass('wpeo-loader');

		},
		error: function ( ) {

			actionContainerError.empty()
			actionContainerError.load(' .risk-create-error-notice')
			actionContainerError.removeClass('hidden');

			$('.fichecenter').removeClass('wpeo-loader');

		}
	});

};

/**
 * Action delete risk.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {boolean}
 */
window.eoxiaJS.risk.deleteRisk = function ( event ) {
	let deletedRiskId = $(this).attr('value');
	var r = confirm('Are you sure you want to delete this risk ?');
	if (r == true) {
		$('#risk_row_'+deletedRiskId).empty();
		$('#risk_row_'+deletedRiskId).load( document.URL + '&action=deleteRisk&deletedRiskId=' + deletedRiskId + ' #risk_row_'+deletedRiskId+' > div');
	} else {
		return false;
	}
};

/**
 * Action save risk.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risk.saveRisk = function ( event ) {
	let editedRiskId = $(this).attr('value');
	let elementRisk = $(this).closest('.risk-container').find('.risk-content');
	let actionContainerSuccess = $('.messageSuccessRiskEdit');
	let actionContainerError = $('.messageErrorRiskEdit');

	var category = elementRisk.find('.risk-category input').val();
	var categoryPost = '';
	if (category !== 0) {
		categoryPost = '&riskCategory=' + category;
	}

	var description = elementRisk.find('.risk-description textarea').val();
	var descriptionPost = '';
	if (description !== '') {
		descriptionPost = '&riskComment=' + encodeURI(description);
	}

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($(this).closest('.risk-row-content-' + editedRiskId));

	$.ajax({
		url: document.URL + '&action=saveRisk&riskID=' + editedRiskId + categoryPost + descriptionPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {

			elementParent.empty()
			elementParent.load( document.URL + ' .tagtable.liste');
			$(this).closest('.risk-row-content-' + editedRiskId).removeClass('wpeo-loader');

			actionContainerSuccess.empty()
			actionContainerSuccess.load(' .risk-edit-success-notice')
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			actionContainerError.empty()
			actionContainerError.load(' .risk-edit-error-notice')
			actionContainerError.removeClass('hidden');
		}
	});
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
 * La méthode contenant tous les évènements pour le evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.event = function() {
	jQuery( document ).on( 'click', '.select-evaluation-method', window.eoxiaJS.evaluation.selectEvaluationMethod);
	jQuery( document ).on( 'click', '.cotation-container .risk-evaluation-cotation.cotation', window.eoxiaJS.evaluation.selectSeuil );
	jQuery( document ).on( 'click', '.risk-evaluation-create', window.eoxiaJS.evaluation.createEvaluation);
	jQuery( document ).on( 'click', '.risk-evaluation-save', window.eoxiaJS.evaluation.saveEvaluation);
	jQuery( document ).on( 'click', '.risk-evaluation-delete', window.eoxiaJS.evaluation.deleteEvaluation);
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
	var elementParent = jQuery(this).closest('.modal-container');
	var multiple_method = elementParent.find('.risk-evaluation-multiple-method').val();
	if (multiple_method > 0) {
		elementParent.find('.select-evaluation-method.selected').removeClass('selected');


		$(this).addClass('selected');
		$(this).removeClass('button-grey');
		$(this).addClass('button-blue');

		elementParent.find('.select-evaluation-method:not(.selected)').removeClass('button-blue');
		elementParent.find('.select-evaluation-method:not(.selected)').addClass('button-grey');

		if ($(this).hasClass('evaluation-standard')) {
			$('.cotation-advanced').attr('style', 'display:none');
			$('.cotation-standard').attr('style', 'display:block');
			$('.risk-evaluation-calculated-cotation').attr('style', 'display:none')
			$('.risk-evaluation-method').val('standard');
			$(this).closest('.risk-evaluation-container').removeClass('advanced');
			$(this).closest('.risk-evaluation-container').addClass('standard');
		} else {
			$('.cotation-standard').attr('style', 'display:none');
			$('.cotation-advanced').attr('style', 'display:block');
			$('.risk-evaluation-calculated-cotation').attr('style', 'display:block');
			$('.risk-evaluation-method').val('advanced');
			$(this).closest('.risk-evaluation-container').addClass('advanced');
			$(this).closest('.risk-evaluation-container').removeClass('standard');
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
	var element       = jQuery( this );
	var elementParent = jQuery(this).closest('.modal-container')
	var seuil         = element.data( 'seuil' );
	var variableID    = element.data( 'variable-id' );

	element.closest('.cotation-container').find('.risk-evaluation-seuil').val(jQuery( this ).text());
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
 * @param  cotation cotation value.
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
	let actionContainerSuccess = $('.messageSuccessEvaluationCreate');
	let actionContainerError = $('.messageErrorEvaluationCreate');

	var riskToAssignPost = '';
	if (riskToAssign !== '') {
		riskToAssignPost = '&riskToAssign=' + riskToAssign;
	}

	var method = single.find('.risk-evaluation-method').val();
	var methodPost = '';
	if (method !== '') {
		methodPost = '&cotationMethod=' + method;
	}

	var cotation = single.find('.risk-evaluation-seuil').val();
	var cotationPost = '';
	if (cotation !== 0) {
		cotationPost = '&cotation=' + cotation;
	}

	var comment = single.find('.risk-evaluation-comment textarea').val();
	var commentPost = '';
	if (comment !== '') {
		commentPost = '&evaluationComment=' + encodeURI(comment);
	}

	var photo = single.find('.risk-evaluation-photo-single .filename').val();
	var photoPost = '';
	if (photo !== 0) {
		photoPost = '&photo=' + encodeURI(photo);
	}

	let criteres = '';
	Object.values($('.table-cell.active.cell-0')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres += '&' + $(v).data( 'type' ) + '=' + $(v).data( 'seuil' )
		}
	})

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($('.risk-row-content-' + riskToAssign));

	$.ajax({
		url: document.URL + '&action=addEvaluation' + riskToAssignPost + methodPost + cotationPost + criteres + photoPost + commentPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function( ) {
				elementParent.empty()
				elementParent.load( document.URL + ' .tagtable.liste');

				$(this).closest('.risk-row-content-' + riskToAssign).removeClass('wpeo-loader');

				actionContainerSuccess.empty()
				actionContainerSuccess.load(' .riskassessment-create-success-notice')
				actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {

			$(this).closest('.risk-row-content-' + riskToAssign).removeClass('wpeo-loader');

			actionContainerError.empty()
			actionContainerError.load(' .riskassessment-create-error-notice')
			actionContainerError.removeClass('hidden');
		}
	});
};


/**
 * Action delete evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {boolean}
 */
window.eoxiaJS.evaluation.deleteEvaluation = function ( event ) {
	let element = $(this).closest('.risk-evaluation');
	let deletedEvaluationId = element.attr('value');
	let textToShow = element.find('.labelForDelete').val();

	var r = confirm(textToShow);
	if (r == true) {
		element.empty();
		element.load( document.URL + '&action=deleteEvaluation&deletedEvaluationId=' + deletedEvaluationId + ' ' + element);
	} else {
		return false;
	}
}

/**
 * Action save evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.saveEvaluation = function ( event ) {
	let element = $(this).closest('.risk-evaluation-edit-modal');
	let evaluationID = element.attr('value');
	let actionContainerSuccess = $('.messageSuccessEvaluationEdit');
	let actionContainerError = $('.messageErrorEvaluationEdit');

	var method = element.find('.risk-evaluation-method').val();
	var methodPost = '';
	if (method !== '') {
		methodPost = '&cotationMethod=' + method;
	}

	var cotation = element.find('.risk-evaluation-seuil').val();
	var cotationPost = '';
	if (cotation !== 0) {
		cotationPost = '&cotation=' + cotation;
	}

	var comment = element.find('.risk-evaluation-comment textarea').val();
	var commentPost = '';
	if (comment !== '') {
		commentPost = '&evaluationComment=' + encodeURI(comment);
	}

	var photo = element.find('.risk-evaluation-photo .filename').val();
	var photoPost = '';
	if (photo !== 0) {
		photoPost = '&photo=' + encodeURI(photo);
	}

	let criteres = '';
	Object.values($('.table-cell.active.cell-'+evaluationID)).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres += '&' + $(v).data( 'type' ) + '=' + $(v).data( 'seuil' );
		}
	})

	let elementParent = $(this).closest('.risk-evaluations-list-content');
	let riskId = elementParent.attr('value');
	let evaluationSingle = $(this).closest('.risk-evaluation-container').find('.risk-evaluation-single-content');
	let evaluationRef =  $('.risk-evaluation-ref-'+evaluationID).attr('value');

	window.eoxiaJS.loader.display($(this));
	evaluationSingle.empty()

	$.ajax({
		url: document.URL + '&action=saveEvaluation&evaluationID=' + evaluationID +  methodPost + cotationPost + criteres + photoPost + commentPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			elementParent.empty()

			elementParent.load( document.URL + ' .risk-evaluations-list-'+riskId);
			evaluationSingle.load( document.URL + ' .risk-evaluation-single-'+riskId);

			elementParent.removeClass('wpeo-loader');
			element.find('#risk_evaluation_edit'+evaluationID).removeClass('modal-active');

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForEditEvaluation1').val()
			textToShow += evaluationRef
			textToShow += actionContainerSuccess.find('.valueForEditEvaluation2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {

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
 * La méthode contenant tous les évènements pour le evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluationMethodEvarisk.event = function() {
	jQuery( document ).on( 'click', '.wpeo-table.evaluation-method .table-cell.can-select', window.eoxiaJS.evaluationMethodEvarisk.selectSeuil );
};

/**
 * Select Seuil on advenced cotation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.eoxiaJS.evaluationMethodEvarisk.selectSeuil = function( event ) {
	jQuery( this ).closest( '.table-row' ).find( '.active' ).removeClass( 'active' );
	jQuery( this ).addClass( 'active' );

	var elementParent = jQuery(this).closest('.modal-container');
	var element       = jQuery( this );
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

		fetch('js/json/default.json').then(response => response.json()).then(data => {
			let cotationAfterAdapt = data[0].option.matrix[cotationBeforeAdapt];
			$('.risk-evaluation-calculated-cotation').find('.risk-evaluation-cotation').attr('data-scale', window.eoxiaJS.evaluation.getDynamicScale(cotationAfterAdapt));
			$('.risk-evaluation-calculated-cotation').find('.risk-evaluation-cotation span').text(cotationAfterAdapt);
			$('.risk-evaluation-content').find('.risk-evaluation-seuil').val(cotationAfterAdapt);
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
 * La méthode contenant tous les évènements pour le riskassessment-task.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.event = function() {
	jQuery( document ).on( 'input', '.riskassessment-task-label', window.eoxiaJS.riskassessmenttask.fillRiskAssessmentTaskLabel);
	jQuery( document ).on( 'click', '.riskassessment-task-create', window.eoxiaJS.riskassessmenttask.createRiskAssessmentTask);
	jQuery( document ).on( 'click', '.riskassessment-task-save', window.eoxiaJS.riskassessmenttask.saveRiskAssessmentTask);
	jQuery( document ).on( 'click', '.riskassessment-task-delete', window.eoxiaJS.riskassessmenttask.deleteRiskAssessmentTask );
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
	var elementParent = jQuery(this).closest('.modal-container');

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
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.createRiskAssessmentTask = function ( event ) {
	var riskToAssign = $(this).attr('value');
	let element = $(this).closest('.riskassessment-task-add-modal');
	let single = element.find('.riskassessment-task-container');
	let actionContainerSuccess = $('.messageSuccessTaskCreate');
	let actionContainerError = $('.messageErrorTaskCreate');

	var riskToAssignPost = '';
	if (riskToAssign !== '') {
		riskToAssignPost = '&riskToAssign=' + riskToAssign;
	}

	var task = single.find('.riskassessment-task-label').val();
	var taskPost = '';
	if (task !== '') {
		taskPost = '&tasktitle=' + encodeURI(task);
	}

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($(this));

	$.ajax({
		url: document.URL + '&action=addRiskAssessmentTask' + riskToAssignPost + taskPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			elementParent.empty()
			elementParent.load( document.URL + ' .tagtable.liste');
			$(this).closest('.risk-row-content-' + riskToAssign).removeClass('wpeo-loader');

			actionContainerSuccess.empty()
			actionContainerSuccess.load(' .task-create-success-notice')
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			$(this).closest('.risk-row-content-' + riskToAssign).removeClass('wpeo-loader');

			actionContainerError.empty()
			actionContainerError.load(' .task-create-error-notice')
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action delete riskassessmenttask.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.deleteRiskAssessmentTask = function ( event ) {
	let element = $(this).closest('.riskassessment-task');
	let deletedRiskAssessmentTaskId = element.attr('value');
	let textToShow = element.find('.labelForDelete').val()
	var r = confirm(textToShow);
	if (r == true) {
		element.empty();
		element.load( document.URL + '&action=deleteRiskAssessmentTask&deletedRiskAssessmentTaskId=' + deletedRiskAssessmentTaskId + ' ' + element);
	} else {
		return false;
	}
};

/**
 * Action save riskassessmenttask.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.saveRiskAssessmentTask = function ( event ) {
	let editedRiskAssessmentTaskId = $(this).attr('value');
	let elementRiskAssessmentTask = $(this).closest('.riskassessment-task-container');
	let actionContainerSuccess = $('.messageSuccessTaskEdit');
	let actionContainerError = $('.messageErrorTaskEdit');

	var task = elementRiskAssessmentTask.find('.riskassessment-task-label').val();
	var taskPost = '';
	if (task !== '') {
		taskPost = '&tasktitle=' + encodeURI(task);
	}

	let elementParent = $(this).closest('.riskassessment-task-list-content');
	let riskId = elementParent.attr('value');
	let taskSingle = $(this).closest('.riskassessment-task-container').find('.riskassessment-task-single-content');
	let taskRef =  $('.riskassessment-task-ref-'+editedRiskAssessmentTaskId).attr('value');

	taskSingle.empty()
	window.eoxiaJS.loader.display($(this));

	$.ajax({
		url: document.URL + '&action=saveRiskAssessmentTask&riskAssessmentTaskID=' + editedRiskAssessmentTaskId + taskPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			elementParent.empty()
			elementParent.load( document.URL + ' .riskassessment-task-list-'+riskId);
			taskSingle.load( document.URL + ' .riskassessment-task-single-'+riskId);
			elementRiskAssessmentTask.removeClass('wpeo-loader');

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTask2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			let textToShow = '';
			textToShow += actionContainerError.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerError.find('.valueForEditTask2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
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
 * La méthode contenant tous les évènements pour le risksign.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.event = function() {
	jQuery( document ).on( 'click', '.risksign-category-danger .item, .wpeo-table .risksign-category-danger .item', window.eoxiaJS.risksign.selectRiskSign );
	jQuery( document ).on( 'click', '.risksign-create:not(.button-disable)', window.eoxiaJS.risksign.createRiskSign );
	jQuery( document ).on( 'click', '.risksign-save', window.eoxiaJS.risksign.saveRiskSign );
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
	var element = jQuery(this);
	element.closest('.content').removeClass('active');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle span').hide();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').show();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('src', element.find('img').attr('src'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('aria-label', element.closest('.wpeo-tooltip-event').attr('aria-label'));

	element.closest('.fichecenter').find('.input-hidden-danger').val(element.data('id'));

	var elementParent = jQuery(this).closest('.modal-container');

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
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.createRiskSign = function ( event ) {
	let elementRiskSign = $(this).closest('.fichecenter').find('.risksign-content');
	let actionContainerSuccess = $('.messageSuccessRiskSignCreate');
	let actionContainerError = $('.messageErrorRiskCreate');

	var category = elementRiskSign.find('.risksign-category input').val();
	var categoryPost = '';
	if (category !== 0) {
		categoryPost = '&riskSignCategory=' + category;
	}

	var description = elementRiskSign.find('.risksign-description textarea').val();
	var descriptionPost = '';
	if (description !== '') {
		descriptionPost = '&riskSignDescription=' + encodeURI(description);
	}

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($('.fichecenter'));

	$.ajax({
		url: document.URL + '&action=add' + categoryPost + descriptionPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			let numberOfRiskSigns = $('.valignmiddle.col-title');
			numberOfRiskSigns.load( document.URL + ' .table-fiche-title .titre.inline-block');

			let modalRiskSign = $('.risksign-add-modal');
			modalRiskSign.load( document.URL + ' .modal-risksign-0');

			elementParent.empty()
			elementParent.load( document.URL + ' .tagtable.liste');
			$('.fichecenter').removeClass('wpeo-loader');

			actionContainerSuccess.empty()
			actionContainerSuccess.load(' .risksign-create-success-notice')
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			actionContainerError.empty()
			actionContainerError.load(' .risksign-create-error-notice')
			actionContainerError.removeClass('hidden');
		}
	});

};

/**
 * Action save risksign.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.risksign.saveRiskSign = function ( event ) {
	let editedRiskSignId = $(this).attr('value');
	let elementRiskSign = $(this).closest('.risksign-container').find('.risksign-content');

	var category = elementRiskSign.find('.risksign-category input').val();
	var categoryPost = '';
	if (category !== 0) {
		categoryPost = '&riskSignCategory=' + category;
	}

	var description = elementRiskSign.find('.risksign-description textarea').val();
	var descriptionPost = '';
	if (description !== '') {
		descriptionPost = '&riskSignDescription=' + encodeURI(description);
	}

	$.ajax({
		url: document.URL + '&action=saveRiskSign&riskSignID=' + editedRiskSignId + categoryPost + descriptionPost,
		type: "POST",
		processData: false,
		contentType: false
	});

	let elementParent = $('.div-table-responsive:not(.list-titre)');

	window.eoxiaJS.loader.display(elementRiskSign);

	setTimeout(function(){
		elementParent.empty()
		elementParent.load( document.URL + ' .div-table-responsive');
		elementRiskSign.removeClass('wpeo-loader');
	}, 800);
	$(this).closest('.div-table-responsive').load( document.URL + '&action=saveRiskSign&riskSignID=' + editedRiskSignId + categoryPost + descriptionPost + ' .div-table-responsive');
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
 * La méthode contenant tous les évènements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluator.event = function() {
	jQuery( document ).on( 'click', '.evaluator-create', window.eoxiaJS.evaluator.createEvaluator );
	jQuery( document ).on( 'click', '#userid'          , window.eoxiaJS.evaluator.selectUser );
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
window.eoxiaJS.evaluator.selectUser = function( event ) {
	$(this).closest('.evaluator-user').find('.user-selected').val(this.value)
};
/**
 * Action create evaluator.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluator.createEvaluator = function ( event ) {
	let elementEvaluator = $(this).closest('.fichecenter').find('.evaluator-content');
	let actionContainerSuccess = $('.messageSuccessEvaluatorCreate');
	let actionContainerError = $('.messageErrorEvaluatorCreate');

	var user = $('.user-selected').val()
	var userPost = '';
	if (user !== 0) {
		userPost = '&evaluatorID=' + encodeURI(user);
	}

	var date = elementEvaluator.find('#EvaluatorDate').val();
	var datePost = '';
	if (date !== '') {
		datePost = '&date=' + encodeURI(date);
	}

	var duration = elementEvaluator.find('.evaluator-duration .duration').val();
	var durationPost = '';
	if (duration !== 0) {
		durationPost = '&duration=' + duration;
	}

	let elementParent = $(this).closest('.fichecenter').find('.div-table-responsive');

	window.eoxiaJS.loader.display(elementParent);

	$.ajax({
		url: document.URL + '&action=add' + durationPost + datePost + userPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			let numberOfEvaluators = $('.valignmiddle.col-title');
			numberOfEvaluators.load( document.URL + ' .table-fiche-title .titre.inline-block');

			let modalEvaluator = $('.risksign-add-modal');
			modalEvaluator.load( document.URL + ' .modal-evaluator-0');

			elementParent.empty();
			elementParent.load( document.URL + ' .tagtable.liste');
			elementParent.removeClass('wpeo-loader');

			actionContainerSuccess.empty()
			actionContainerSuccess.load(' .evaluator-create-success-notice')
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			actionContainerError.empty()
			actionContainerError.load(' .evaluator-create-error-notice')
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
 * La méthode contenant tous les évènements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.digiriskusers.event = function() {
	jQuery( document ).on( 'input', '.digirisk-users #firstname', window.eoxiaJS.digiriskusers.fillEmail );
	jQuery( document ).on( 'input', '.digirisk-users #lastname', window.eoxiaJS.digiriskusers.fillEmail );
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
	var domainMail = jQuery( '.input-domain-mail' ).val();

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
 * La méthode contenant tous les évènements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.notice.event = function() {
	jQuery( document ).on( 'click', '.notice-close', window.eoxiaJS.notice.closeNotice );
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
	$(this).closest('.notice').addClass("hidden");
};

/**
 * Initialise l'objet "slider" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.slider = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.slider.init = function() {
	var slider = tns({
		container: '.wpeo-carousel',
		items                : 1,
		gutter               : 0,
		controls             : true,
		controlsPosition     : 'bottom',
		controlsText         : ['<i class="fas fa-angle-left icon"></i>', '<i class="fas fa-angle-right icon"></i>'],
		nav                  : true,
		navPosition          : 'bottom',
		speed                : 600,
		autoplay             : false,
		autoplayTimeout      : 4000,
		autoplayButtonOutput : false,
		rewind               : true,
		loop                 : false,
		autoHeight           : true,
		mouseDrag            : false
	});
};
