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
<?php
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>
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
	jQuery( document ).on( 'click', '.toggle-unit', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '#newGroupment', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '#newWorkunit', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '.digirisk-wrap .navigation-container .toolbar div', window.eoxiaJS.navigation.toggleAll );
	jQuery( document ).on( 'click', '#slider', window.eoxiaJS.navigation.setUnitActive );
	jQuery( document ).on( 'click', '#newGroupment', window.eoxiaJS.navigation.redirect );
	jQuery( document ).on( 'click', '#newWorkunit', window.eoxiaJS.navigation.redirect );
	jQuery( document ).on( 'click', '.side-nav-responsive', window.eoxiaJS.navigation.toggleMobileNav );
	jQuery( document ).on( 'click', '.save-organization', window.eoxiaJS.navigation.saveOrganization );
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
	console.log($(this))
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
	$( this ).closest( '.side-nav' ).find( '#id-left' ).removeClass( 'active' );

	//empty and fill object card
	$('#cardContent').load( URLToGo + ' #cardContent' , id);
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

	$('.route').each(function() {
		id = $(this).attr('id')
		parent_id = $(this).parent('ul').attr('id').split(/space/)[1]

		idArray.push(id)
		parentArray.push(parent_id)
	})

	window.eoxiaJS.loader.display($(this));

	//ajouter sécurité si le nombre de gp à la fin n'est pas le même qu'en bdd alors on stop tout

	$.ajax({
		url: document.URL + '&action=saveOrganization&ids='+idArray.toString()+'&parent_ids='+parentArray,
		success: function() {
			actionContainerSuccess.removeClass('hidden');

			$('.wpeo-loader').addClass('button-disable')
			$('.wpeo-loader').attr('style','background: #47e58e !important;border-color: #47e58e !important;')
			$('.wpeo-loader').find('.fas.fa-check').attr('style', '')

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
	} else if ($(this).hasClass('open-media-gallery')) {
		$('#media_gallery').addClass('modal-active');
        $('#media_gallery').attr('value', idSelected);
        $('#media_gallery').find('.type-from').attr('value', $(this).find('.type-from').val());
		$('#media_gallery').find('.wpeo-button').attr('value', idSelected);
	} else if ($(this).hasClass('risk-evaluation-edit')) {
		$('#risk_evaluation_edit' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('evaluator-add')) {
		$('#evaluator_add' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('open-medias-linked') && $(this).hasClass('digirisk-element')) {
	    console.log( $('#digirisk_element_medias_modal_' + idSelected))
        $('#digirisk_element_medias_modal_' + idSelected).addClass('modal-active');
        //$('#risk_assessment_medias_modal_' + idSelected).addClass('modal-active');
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
	$(this).closest('.modal-active').removeClass('modal-active')
	$('.clicked-photo').attr('style', '');
	$('.clicked-photo').removeClass('clicked-photo');
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
		jQuery( document ).on( 'mouseenter touchstart', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onEnter );
		jQuery( document ).on( 'mouseleave touchend', '.wpeo-tooltip-event:not([data-tooltip-persist="true"])', window.eoxiaJS.tooltip.onOut );
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

window.eoxiaJS.signature.event = function() {
	jQuery( document ).on( 'click', '.signature-erase', window.eoxiaJS.signature.clearCanvas );
    jQuery( document ).on( 'click', '.signature-validate', window.eoxiaJS.signature.createSignature );
    jQuery( document ).on( 'click', '.auto-download', window.eoxiaJS.signature.autoDownloadSpecimen );
};

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

	var signature_data = jQuery( '#signature_data' + triggeredElement.attr('value') ).val();
	window.eoxiaJS.signature.canvas.signaturePad.fromDataURL(signature_data);
};

window.eoxiaJS.signature.clearCanvas = function( event ) {
	var canvas = jQuery( this ).closest( '.modal-signature' ).find( 'canvas' );
	canvas[0].signaturePad.clear();
};

window.eoxiaJS.signature.createSignature = function() {
	let elementSignatory = $(this).attr('value');
	let elementRedirect  = $(this).find('#redirect' + elementSignatory).attr('value');
	let elementZone  = $(this).find('#zone' + elementSignatory).attr('value');
    let actionContainerSuccess = $('.noticeSignatureSuccess');
	var signatoryIDPost = '';
	if (elementSignatory !== 0) {
		signatoryIDPost = '&signatoryID=' + elementSignatory;
	}

	if ( ! $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].signaturePad.isEmpty() ) {
		var signature = $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].toDataURL();
	}

	var url = '';
	var type = '';
	if (elementZone == "private") {
		url = document.URL + '&action=addSignature' + signatoryIDPost;
		type = "POST"
	} else {
		url = document.URL + '&action=addSignature' + signatoryIDPost;
		type = "POST";
	}
	$.ajax({
		url: url,
		type: type,
		processData: false,
		contentType: 'application/octet-stream',
		data: signature,
		success: function() {
            if (elementZone == "private") {
				actionContainerSuccess.load(document.URL + ' .noticeSignatureSuccess .all-notice-content')
				actionContainerSuccess.removeClass('hidden');
				$('.signatures-container').load( document.URL + ' .signatures-container');
            } else {
                window.location.replace(elementRedirect);
            }
		},
		error: function ( ) {
		    alert('Error')
		}
	});
};

window.eoxiaJS.signature.download = function(fileUrl, filename) {
    var a = document.createElement("a");
    a.href = fileUrl;
    a.setAttribute("download", filename);
    a.click();
}

window.eoxiaJS.signature.autoDownloadSpecimen = function( event ) {
    let element = $(this).closest('.file-generation')
	let url = document.URL + '&action=builddoc'
    $.ajax({
        url: url,
        type: "POST",
        success: function ( ) {
            let filename = element.find('.specimen-name').attr('value')
            let path = element.find('.specimen-path').attr('value')

            window.eoxiaJS.signature.download(path + filename, filename);
            $.ajax({
                url: document.URL + '&action=remove_file',
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
 * La méthode contenant tous les évènements pour le mediaGallery.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.event = function() {
	// Photos
	jQuery( document ).on( 'click', '.clickable-photo', window.eoxiaJS.mediaGallery.selectPhoto );
	jQuery( document ).on( 'click', '.save-photo', window.eoxiaJS.mediaGallery.savePhoto );
	jQuery( document ).on( 'click', '.modal-content .formattachnewfile .button', window.eoxiaJS.mediaGallery.sendPhoto );
	jQuery( document ).on( 'click', '.clicked-photo-preview', window.eoxiaJS.mediaGallery.previewPhoto );
	jQuery( document ).on( 'input', '.form-element #search_in_gallery', window.eoxiaJS.mediaGallery.handleSearch );
	jQuery( document ).on( 'click', '.media-gallery-unlink', window.eoxiaJS.mediaGallery.unlinkFile );
	jQuery( document ).on( 'click', '.media-gallery-favorite', window.eoxiaJS.mediaGallery.addToFavorite );
}

/**
 * Select photo.
 *
 * @since   1.0.0
 * @version 1.0.0
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
 * Action save photo.
 *
 * @since   1.0.0
 * @version 8.2.0
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

	if (type === 'riskassessment') {
		mediaLinked = modalFrom.find('.element-linked-medias')
        window.eoxiaJS.loader.display(mediaLinked);

        let riskAssessmentPhoto = ''
        riskAssessmentPhoto = $('.risk-evaluation-photo-'+idToSave+'.risk-'+riskId)

        let filepath = modalFrom.find('.risk-evaluation-photo-single .filepath-to-riskassessment').val()
        let newPhoto = filepath + favorite.replace(/\./, '_small.')

        $.ajax({
            url: document.URL + "&action=addFiles&risk_id="+riskId+"&riskassessment_id="+idToSave+"&filenames="+filenames,
            type: "POST",
            processData: false,
            contentType: false,
            success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
                parent.removeClass('modal-active')
                riskAssessmentPhoto.each( function() {
                    $(this).find('.clicked-photo-preview').attr('src',newPhoto )
                    $(this).find('.filename').attr('value', favorite.match(/_small/) ? favorite.replace(/\./, '_small.') : favorite)
                });
                mediaLinked.load(document.URL+'&favorite='+favorite + ' .element-linked-medias-'+idToSave+'.risk-'+riskId)
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
        let newPhoto = filepath + favorite.replace(/\./, '_small.')

        $.ajax({
            url: document.URL + "&action=addFiles&digiriskelement_id="+idToSave+"&filenames="+filenames,
            type: "POST",
            processData: false,
            contentType: false,
            success: function ( ) {
                $('.wpeo-loader').removeClass('wpeo-loader')
                parent.removeClass('modal-active')
                digiriskElementPhoto.attr('src',newPhoto )

				let photoContainer = digiriskElementPhoto.closest('.open-media-gallery')
				photoContainer.removeClass('open-media-gallery')
				photoContainer.addClass('open-medias-linked')
				photoContainer.addClass('digirisk-element')
				photoContainer.closest('.unit-container').find('.digirisk-element-medias-modal').load(document.URL+ ' #digirisk_element_medias_modal_'+idToSave)

				if (idToSave === currentElementID) {
					let digiriskBanner = $('.arearef.heightref')
					digiriskBanner.load(document.URL+'&favorite='+favorite + ' .arearef.heightref')
				}
				mediaLinked.load(document.URL+'&favorite='+favorite + ' .element-linked-medias-'+idToSave+'.digirisk-element')
				console.log(modalFrom)
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
	let element  = $(this).closest('.nowrap');
	let files    = element.find("input[name='userfile[]']").prop("files");
	let formdata = new FormData();
    let elementParent = $(this).closest('.modal-container').find('.ecm-photo-list-content');
	let actionContainerSuccess = $('.messageSuccessSendPhoto');
	let actionContainerError = $('.messageErrorSendPhoto');
	window.eoxiaJS.loader.display($('#media_gallery').find('.modal-content'));

	$.each(files, function(index, file) {
		formdata.append("userfile[]", file);
	})
	$.ajax({
		url: document.URL + "&action=uploadPhoto",
		type: "POST",
		data: formdata,
		processData: false,
		contentType: false,
		success: function ( ) {
			$('.wpeo-loader').removeClass('wpeo-loader')
			window.eoxiaJS.loader.display(elementParent);
			elementParent.empty()
			elementParent.load( document.URL + ' .ecm-photo-list');
            elementParent.removeClass('wpeo-loader');

			actionContainerSuccess.empty()
			actionContainerSuccess.load(' .send-photo-success-notice')
			actionContainerSuccess.removeClass('hidden');
        },
		error: function ( ) {
			actionContainerError.empty()
			actionContainerError.load(' .send-photo-error-notice')
			actionContainerError.removeClass('hidden');
		}
	});
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
	setTimeout(function(){
		jQuery( document ).find('.ui-dialog').addClass('preview-photo');
	}, 200);
};

/**
 * Action unlink photo.
 *
 * @since   8.2.0
 * @version 8.2.0
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
	var params = new window.URLSearchParams(window.location.search);
	var currentElementID = params.get('id')

	let mediaContainer = $(this).closest('.media-container')
	let previousPhoto = null
	let previousName = ''
	let newPhoto = ''


	window.eoxiaJS.loader.display($(this).closest('.media-container'));

	document.URL.match('/?/') ? querySeparator = '&' : 1

	if (type === 'riskassessment') {
		let riskAssessmentPhoto = $('.risk-evaluation-photo-'+element_linked_id)
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]

		if (previousName == filename.replace(/\./, '_small.')) {
			newPhoto = previousPhoto[0].src.replace(previousName, '')
		} else {
			newPhoto = previousPhoto[0].src
		}

		$.ajax({
			url: document.URL + querySeparator + "action=unlinkFile&risk_id="+riskId+"&riskassessment_id="+element_linked_id+"&filename="+filename,
			type: "POST",
			processData: false,
			success: function ( ) {
				$('.wpeo-loader').removeClass('wpeo-loader')
				riskAssessmentPhoto.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',newPhoto )
				});
				mediaContainer.hide()
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')
		previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]

		if (previousName == filename.replace(/\./, '_small.')) {
			newPhoto = previousPhoto[0].src.replace(previousName, '')
		} else {
			newPhoto = previousPhoto[0].src
		}

		$.ajax({
			url: document.URL + querySeparator + "action=unlinkFile&digiriskelement_id="+element_linked_id+"&filename="+filename,
			type: "POST",
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
 * @version 8.2.0
 *
 * @return {void}
 */
window.eoxiaJS.mediaGallery.addToFavorite = function( event ) {

	event.preventDefault()
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

	document.URL.match('/?/') ? querySeparator = '&' : 1

	window.eoxiaJS.loader.display(mediaContainer);

	if (type === 'riskassessment') {
		previousPhoto = $(this).closest('.modal-container').find('.risk-evaluation-photo .clicked-photo-preview')
		elementPhotos = $('.risk-evaluation-photo-'+element_linked_id+'.risk-'+riskId)

		$(this).closest('.modal-content').find('.risk-evaluation-photo-single .filename').attr('value', filename)
		let previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]
		let saveButton = $(this).closest('.modal-container').find('.risk-evaluation-save')
		saveButton.addClass('button-disable')
		$.ajax({
			url: document.URL + querySeparator + "action=addToFavorite&riskassessment_id="+element_linked_id+"&filename="+filename,
			type: "POST",
			processData: false,
			success: function ( ) {
				let newPhoto = ''
				if (previousName.length > 0 ) {
					newPhoto = previousPhoto[0].src.trim().replace(previousName , filename.replace(/\./, '_small.'))
				} else {
					newPhoto = previousPhoto[0].src.trim() + filename.replace(/\./, '_small.')
				}
				elementPhotos.each( function() {
					$(this).find('.clicked-photo-preview').attr('src',newPhoto )
				});
				saveButton.removeClass('button-disable')
				$('.wpeo-loader').removeClass('wpeo-loader')
			}
		});
	} else if (type === 'digiriskelement') {
		previousPhoto = $('.digirisk-element-'+element_linked_id).find('.photo.clicked-photo-preview')

		let previousName = previousPhoto[0].src.trim().split(/thumbs%2F/)[1].split(/"/)[0]

		$.ajax({
			url: document.URL + querySeparator + "action=addToFavorite&digiriskelement_id="+element_linked_id+"&filename="+filename,
			type: "POST",
			processData: false,
			success: function ( ) {
				let newPhoto = ''
				if (previousName.length > 0 ) {
					newPhoto = previousPhoto[0].src.trim().replace(previousName , filename.replace(/\./, '_small.'))
				} else {
					newPhoto = previousPhoto[0].src.trim() + filename.replace(/\./, '_small.')
				}
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

	element.closest('.wpeo-dropdown').find('.input-hidden-danger').val(element.data('id'));
	var riskDescriptionPrefill = element.closest('.wpeo-dropdown').find('.input-risk-description-prefill').val();
	if (riskDescriptionPrefill == 1) {
		element.closest('.risk-content').find('.risk-description textarea').text(element.closest('.wpeo-tooltip-event').attr('aria-label'));
	}
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

	let riskCommentText = elementRisk.find('.risk-description textarea').val()
	let riskDescriptionPrefill = elementRisk.find('.risk-category .input-risk-description-prefill').val()
	let riskDescriptionText = elementRisk.find('.risk-category .danger-category-pic').attr('aria-label')

	let evaluationText = elementEvaluation.find('.risk-evaluation-comment textarea').val()

	let taskText = elementTask.find('input').val()

	riskCommentText = window.eoxiaJS.risk.sanitizeBeforeRequest(riskCommentText)
	riskDescriptionText = window.eoxiaJS.risk.sanitizeBeforeRequest(riskDescriptionText)
	evaluationText = window.eoxiaJS.risk.sanitizeBeforeRequest(evaluationText)
	taskText = window.eoxiaJS.risk.sanitizeBeforeRequest(taskText)

	//Risk
	var category = elementRisk.find('.risk-category input').val();
	if (riskDescriptionPrefill == 1) {
		var description = riskDescriptionText;
	} else {
		var description = riskCommentText;
	}

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

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($('.fichecenter'));

	$.ajax({
		url: document.URL + '&action=add',
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

			let modalRisk = $('.risk-add-modal');
			modalRisk.html($(resp).find('.risk-add-modal'))

			elementParent.empty();
			elementParent.html($(resp).find('.div-table-responsive'))

			let numberOfRisks = $('.valignmiddle.col-title');
			numberOfRisks.html($(resp).find('.valignmiddle.col-title'));

			actionContainerSuccess.empty()
			actionContainerSuccess.html($(resp).find('.risk-create-success-notice'));

			actionContainerSuccess.removeClass('hidden');

			$('.fichecenter').removeClass('wpeo-loader');
		},
		error: function ( resp ) {
			actionContainerError.empty()
			actionContainerError.html($(resp).find('.risk-create-error-notice'));
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

	let riskCommentText = elementRisk.find('.risk-description textarea').val()

	riskCommentText = window.eoxiaJS.risk.sanitizeBeforeRequest(riskCommentText)

	var category = elementRisk.find('.risk-category input').val();

	if (riskCommentText) {
		var description = riskCommentText;
	} else {
		var description = '';
	}
	//var newParent = $(this).closest('.risk-container').find('#select2-socid-container').attr('title');
	var newParent = $(this).closest('.risk-container').find('#socid option:selected').text();
	if (newParent) {
			newParent = newParent.split(/ /)[0];
	}

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($(this));
	if (riskCommentText) {
		window.eoxiaJS.loader.display($(this).closest('.risk-row-content-' + editedRiskId).find('.risk-description-'+editedRiskId));
	}

	$.ajax({
		url:  document.URL + '&action=saveRisk',
		type: "POST",
		processData: false,
		data: JSON.stringify({
			riskID: editedRiskId,
			category: category,
			comment: description,
			newParent: newParent
		}),
		contentType: false,
		success: function ( ) {
			window.location.reload()
			//elementParent.load(document.URL + ' .div-table-responsive')

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

	let elementParent = $('.fichecenter').find('.div-table-responsive');
	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.risk-evaluation-container-' + riskToAssign));

	$.ajax({
		url: document.URL + '&action=addEvaluation',
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
		success: function( ) {
			elementParent.load( document.URL + ' .div-table-responsive');
			element.find('#risk_evaluation_add'+riskToAssign).removeClass('modal-active');

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
	let actionContainerSuccess = $('.messageSuccessEvaluationDelete');
	let actionContainerError = $('.messageErrorEvaluationDelete');
	let evaluationID = element.attr('value');

	var r = confirm(textToShow);
	if (r == true) {

		let elementParent = $(this).closest('.risk-evaluations-list-content');
		let riskId = elementParent.attr('value');
		let evaluationSingle = $('.risk-evaluation-single-content-'+riskId);
		let evaluationRef =  $('.risk-evaluation-ref-'+evaluationID).attr('value');

		window.eoxiaJS.loader.display($(this));
		evaluationSingle.empty()

		$.ajax({
			url:document.URL + '&action=deleteEvaluation&deletedEvaluationId=' + deletedEvaluationId,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( ) {
				elementParent.empty()

				elementParent.load( document.URL + ' .risk-evaluations-list-'+riskId);
				evaluationSingle.load( document.URL + ' .risk-evaluation-single-'+riskId);

				elementParent.removeClass('wpeo-loader');

				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteEvaluation1').val()
				textToShow += evaluationRef
				textToShow += actionContainerSuccess.find('.valueForDeleteEvaluation2').val()

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
			},
			error: function ( ) {

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
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.evaluation.saveEvaluation = function ( event ) {
	let element = $(this).closest('.risk-evaluation-edit-modal');
	let evaluationID = element.attr('value');
	let actionContainerSuccess = $('.messageSuccessEvaluationEdit');
	let actionContainerError = $('.messageErrorEvaluationEdit');
	let evaluationText = element.find('.risk-evaluation-comment textarea').val()

	let elementParent = $(this).closest('.risk-evaluation-container').find('.risk-evaluations-list-content');
	let riskId = elementParent.attr('value');
	let evaluationSingle = $(this).closest('.risk-evaluation-container').find('.risk-evaluation-single-content');
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
	window.eoxiaJS.loader.display(listModalContainer.find('.modal-content .risk-evaluations-list-content'))
	$.ajax({
		url: document.URL + '&action=saveEvaluation',
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
		success: function ( ) {
			if (fromList) {
				listModalContainer.find('.modal-content .risk-evaluations-list-content').load(document.URL + ' .risk-evaluations-list-'+riskId)
				$('.risk-evaluation-single-content-'+riskId).load(document.URL + ' .risk-evaluation-single-'+riskId)
			} else {
				$('.div-table-responsive').load(document.URL + ' .div-table-responsive')
			}
			$('.wpeo-loader').removeClass('wpeo-loader')
            //elementParent.removeClass('wpeo-loader');
			//listModalContainer.find('.modal-content .risk-evaluations-list-content').removeClass('wpeo-loader');
            //$(this).closest('.risk-evaluation-container').removeClass('wpeo-loader');

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
    let elementToRefresh = $(this).closest('.riskassessment-tasks');

	let taskText = single.find('.riskassessment-task-label').val()

	taskText = window.eoxiaJS.risk.sanitizeBeforeRequest(taskText)

	var riskToAssignPost = '';
	if (riskToAssign !== '') {
		riskToAssignPost = '&riskToAssign=' + riskToAssign;
	}

	var task = taskText;
	var taskPost = '';
	if (task !== '') {
		taskPost = '&tasktitle=' + encodeURI(task);
	}

	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.riskassessment-task-listing-wrapper-'+ riskToAssign));

	$.ajax({
		url: document.URL + '&action=addRiskAssessmentTask' + riskToAssignPost + taskPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			$('.div-table-responsive').load(document.URL + ' .div-table-responsive')
			element.find('#risk_assessment_task_add'+riskToAssign).removeClass('modal-active');

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
	let element = $(this).closest('.riskassessment-tasks');
	let deletedRiskAssessmentTaskId = $(this).attr('value');
	let textToShow = element.find('.labelForDelete').val();
	let actionContainerSuccess = $('.messageSuccessTaskDelete');
	let actionContainerError = $('.messageErrorTaskDelete');

	var r = confirm(textToShow);
	if (r == true) {

		let riskId = element.attr('value');
		let riskAssessmentTaskRef =  $('.riskassessment-task-container-'+deletedRiskAssessmentTaskId).attr('value');

		window.eoxiaJS.loader.display($(this));

		$.ajax({
			url: document.URL + '&action=deleteRiskAssessmentTask&deletedRiskAssessmentTaskId=' + deletedRiskAssessmentTaskId,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( ) {
				$('.div-table-responsive').load(document.URL + ' .div-table-responsive')
                //element.load( document.URL + ' .riskassessment-tasks'+riskId);

				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteTask1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerSuccess.find('.valueForDeleteTask2').val()

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
			},
			error: function ( ) {

				let textToShow = '';
				textToShow += actionContainerError.find('.valueForDeleteTask1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerError.find('.valueForDeleteTask2').val()

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
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.riskassessmenttask.saveRiskAssessmentTask = function ( event ) {
    let element = $(this).closest('.riskassessment-tasks');
    let editedRiskAssessmentTaskId = $(this).attr('value');
	let elementRiskAssessmentTask = $(this).closest('.riskassessment-task-container');
	let actionContainerSuccess = $('.messageSuccessTaskEdit');
	let actionContainerError = $('.messageErrorTaskEdit');
    let riskId = element.attr('value');
    let textToShow = '';

	let taskText = elementRiskAssessmentTask.find('.riskassessment-task-label' + editedRiskAssessmentTaskId).val()

	taskText = window.eoxiaJS.risk.sanitizeBeforeRequest(taskText)

	var task = taskText
	var taskPost = '';
	if (task !== '') {
		taskPost = '&tasktitle=' + encodeURI(task);
	}

	let taskRef =  $('.riskassessment-task-ref-'+editedRiskAssessmentTaskId).attr('value');

	window.eoxiaJS.loader.display($(this));
	window.eoxiaJS.loader.display($('.riskassessment-task-single-'+ editedRiskAssessmentTaskId));

	$.ajax({
		url: document.URL + '&action=saveRiskAssessmentTask&riskAssessmentTaskID=' + editedRiskAssessmentTaskId + taskPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			$('.div-table-responsive').load(document.URL + ' .div-table-responsive')

			textToShow += actionContainerSuccess.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTask2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
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
	let actionContainerSuccess = $('.messageSuccessRiskSignEdit');
	let actionContainerError = $('.messageErrorRiskSignEdit');

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
	let elementParent = $('.div-table-responsive:not(.list-titre)');

	window.eoxiaJS.loader.display(elementRiskSign);
	$.ajax({
		url: document.URL + '&action=saveRiskSign&riskSignID=' + editedRiskSignId + categoryPost + descriptionPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( ) {
			elementParent.empty()
			elementParent.load( document.URL + ' .div-table-responsive');
			elementRiskSign.removeClass('wpeo-loader');

			$(this).closest('.div-table-responsive').load( document.URL + '&action=saveRiskSign&riskSignID=' + editedRiskSignId + categoryPost + descriptionPost + ' .div-table-responsive');

			actionContainerSuccess.empty()
			actionContainerSuccess.load(' .risksign-edit-success-notice')
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			actionContainerError.empty()
			actionContainerError.load(' .risksign-edit-error-notice')
			actionContainerError.removeClass('hidden');
		}
	});

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
	jQuery( document ).on( 'change', '#userid'          , window.eoxiaJS.evaluator.selectUser );
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
	//alert(user);
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
 * La méthode contenant tous les évènements pour les tickets.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.event = function() {
	jQuery( document ).on( 'click', '.ticket-register', window.eoxiaJS.ticket.selectRegister );
	jQuery( document ).on( 'click', '.ticket-pertinence', window.eoxiaJS.ticket.selectPertinence );
	jQuery( document ).on( 'submit', '#sendFile', window.eoxiaJS.ticket.tmpStockFile );
	jQuery( document ).on( 'click', '.linked-file-delete', window.eoxiaJS.ticket.removeFile );
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

	let requestParams = ''
	if (document.URL.match(/\?/)) {
		requestParams = '&'
	} else {
		requestParams = '?'
	}

	let register = window.eoxiaJS.ticket.getRegister()
	if (register > 0) {
		requestParams += 'register=' + register + '&'
	}

	let pertinence = window.eoxiaJS.ticket.getPertinence()
	if (pertinence > 0) {
		requestParams += 'pertinence=' + pertinence  + '&'
	}

	$('.img-fields-container').load(document.URL + requestParams + ' .tableforimgfields');
};

/**
 * Clique sur un des registres de la liste.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.selectRegister = function( ) {
	let pertinenceInput = $('.ticketpublicarea').find("#pertinence");
	let registerInput = $('.ticketpublicarea').find("#register");

	pertinenceInput.val(0)
	registerInput.val($(this).attr('id'))

	window.eoxiaJS.ticket.updateFormData()
};

/**
 * Récupère la valeur du registre sélectionné
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.getRegister = function( ) {
	return $('.ticketpublicarea').find("#register").val()
};

/**
 * Clique sur une des pertinences de la liste.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.ticket.selectPertinence = function( ) {
	let pertinenceInput = $('.ticketpublicarea').find("#pertinence");
	pertinenceInput.val($(this).attr('id'))

	window.eoxiaJS.ticket.updateFormData()
};

/**
 * Récupère la valeur de la pertinence sélectionnée
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */

window.eoxiaJS.ticket.getPertinence = function(  ) {
	return $('.ticketpublicarea').find("#pertinence").val()
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

	fetch(document.URL + '?action=sendfile&ticket_id='+ticket_id, {
		method: 'POST',
		body: formData,
	}).then((response) => {
		setTimeout(function(){
			$('#sendFileForm').load(document.URL+ '?ticket_id='+ticket_id + ' #fileLinkedTable')
		}, 800);
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

	fetch(document.URL + '?action=removefile&filetodelete='+filetodelete+'&ticket_id='+ticket_id, {
		method: 'POST',
	}).then((response) => {
		setTimeout(function(){
			$('#sendFileForm').load(document.URL+ '?ticket_id='+ticket_id + ' #fileLinkedTable')
		}, 800);
	})
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
 * La méthode contenant tous les évènements pour les preventionplans.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.preventionplan.event = function() {
    jQuery( document ).on( 'click', '#prior_visit_bool', window.eoxiaJS.preventionplan.showDateAndText );
};

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

window.eoxiaJS.document.event = function() {
	jQuery( document ).on( 'click', '#builddoc_generatebutton', window.eoxiaJS.document.displayLoader );
	jQuery( document ).on( 'click', ' .send-risk-assessment-document-by-mail', window.eoxiaJS.document.displayLoader );
};

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
 * La méthode contenant tous les évènements pour le migration.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.keyEvent.event = function() {
	jQuery( document ).on( 'keydown', window.eoxiaJS.keyEvent.keyup );
}

/**
 * Action modal close & validation with key events
 *
 * @since   1.0.0
 * @version 1.0.0
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
 * La méthode contenant tous les évènements pour le migration.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.event = function() {
	jQuery(document).on( 'click', ' .blockvmenu', window.eoxiaJS.menu.toggleMenu);
	jQuery(document).ready(function() { window.eoxiaJS.menu.setMenu()});
}

/**
 * Action Toggle main menu.
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.toggleMenu = function() {

	var menu = $(this).closest('#id-left').find('a.vmenu');
	var elementParent = $(this).closest('#id-left').find('div.vmenu')


	if (jQuery(this).find('.minimizeMenu').length > 0) {

		var text = '';
		menu.each(function (index, value) {
			text = $(this).html().split(' ');
			$(this).html(text[0]+' '+text[1]+' '+text[2]);
		});

		var elementText = $(this).find('.minimizeMenu').html().split(' ');
		$(this).find('.minimizeMenu').html(elementText[0]+' '+elementText[1]+' '+elementText[2]);

		elementParent.css('width', '30px');
		elementParent.find('.blockvmenusearch').hide();

		jQuery(this).find('.minimizeMenu').removeClass('minimizeMenu').addClass('maximizeMenu');
		localStorage.setItem('maximized', 'false')

	} else if (jQuery(this).find('.maximizeMenu').length > 0) {
		var text2 = '';
		menu.each(function (index, value) {
			text2 = $(this).html().split(' ');
			$(this).html(text2[0]+' '+text2[1]+' '+text2[2]+' '+$(this).attr('title'));
		});

		var elementText2 = $(this).find('.maximizeMenu').html().split(' ');
		jQuery(this).find('.maximizeMenu').html(elementText2[0]+' '+elementText2[1]+' '+elementText2[2]+' Réduire le menu');

		elementParent.css('width', '');
		elementParent.find('.blockvmenusearch').show();

		jQuery(this).find('.maximizeMenu').removeClass('maximizeMenu').addClass('minimizeMenu');
		$('div.menu_titre').attr('style', 'width: 188px !important')

		localStorage.setItem('maximized', 'true')

	}
};

/**
 * Action set  menu.
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.menu.setMenu = function() {
	$('.minimizeMenu').parent().parent().parent().attr('style', 'cursor:pointer ! important')
	if (localStorage.maximized == 'false') {
		$('#id-left').attr('style', 'display:none !important')
	}
	if (localStorage.maximized == 'false') {
		var text = '';
		var menu = $(document).find('a.vmenu');
		var elementParent = $(document).find('div.vmenu')
		console.log(menu)
		menu.each(function (index, value) {
			text = $(this).html().split(' ');
			$(this).html(text[0]+' '+text[1]+' '+text[2]);
			console.log($(this))
		});

		$('#id-left').attr('style', 'display:block !important')
		$('div.menu_titre').attr('style', 'width: 50px !important')

		var elementText = $('.minimizeMenu').html().split(' ');
		$('.minimizeMenu').html(elementText[0]+' '+elementText[1]+' '+elementText[2]);
		$('.minimizeMenu').removeClass('minimizeMenu').addClass('maximizeMenu');

		elementParent.css('width', '30px');
		elementParent.find('.blockvmenusearch').hide();
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
 * La méthode contenant tous les évènements pour les accidents.
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.accident.event = function() {
    jQuery( document ).on( 'submit', ' .sendfile', window.eoxiaJS.accident.tmpStockFile );
    jQuery( document ).on( 'click', ' .linked-file-delete-workstop', window.eoxiaJS.accident.removeFile );
	jQuery( document ).on( 'click', '#external_accident', window.eoxiaJS.accident.showExternalAccidentLocation );
};

/**
 * Upload automatiquement le(s) fichier(s) séelectionnés dans digiriskdolibarr/accident/accident_ref/workstop/__REF__ (temp ou ref du workstop)
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.eoxiaJS.accident.tmpStockFile = function(id) {
    var files = $('#sendfile').prop('files');


    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        let file = files[i]
        formData.append('files[]', file)
    }


    $.ajax({
        url: document.URL + '&action=sendfile&objectlineid=' + id,
        type: "POST",
        processData: false,
        contentType: false,
		data: formData,
        success: function ( ) {
            $('#sendFileForm' + id).load(document.URL + ' #fileLinkedTable' + id)
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
    filetodelete = filetodelete.replace('_mini', '')
    let objectlineid = $(this).closest('.objectline').attr('value')

    $.ajax({
        url: document.URL + '&action=removefile&filetodelete='+filetodelete+'&objectlineid='+objectlineid,
        type: "POST",
        processData: false,
        contentType: false,
        success: function ( ) {
            $('#sendFileForm' + objectlineid).load(document.URL + ' #fileLinkedTable' + objectlineid)
        },
        error: function ( ) {
        }
    });
};

window.eoxiaJS.accident.showExternalAccidentLocation = function() {
	let fkElementField = $(this).closest('.accident-table').find('.fk_element_field')
	let fkSocField = $(this).closest('.accident-table').find('.fk_soc_field')

	if (fkSocField.hasClass('hidden')) {
		fkElementField.attr('style', 'display:none')
		fkSocField.attr('style', '')
		fkElementField.addClass('hidden')
		fkSocField.removeClass('hidden')
	} else {
		fkElementField.attr('style', '')
		fkSocField.attr('style', 'display:none')
		fkElementField.removeClass('hidden')
		fkSocField.addClass('hidden')
	}
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
