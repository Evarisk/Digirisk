///* Copyright (C) 2021 SuperAdmin
// *
// * This program is free software: you can redistribute it and/or modify
// * it under the terms of the GNU General Public License as published by
// * the Free Software Foundation, either version 3 of the License, or
// * (at your option) any later version.
// *
// * This program is distributed in the hope that it will be useful,
// * but WITHOUT ANY WARRANTY; without even the implied warranty of
// * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// * GNU General Public License for more details.
// *
// * You should have received a copy of the GNU General Public License
// * along with this program.  If not, see <https://www.gnu.org/licenses/>.
// *
// * Library javascript to enable Browser notifications
// */
//
//if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
//if (!defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
//if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
//if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
//if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
//if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
//if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
//if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
//if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
//
//
///**
// * \file    digiriskdolibarr/js/digiriskdolibarr.js.php
// * \ingroup digiriskdolibarr
// * \brief   JavaScript file for module DigiriskDolibarr.
// */
//
//// Load Dolibarr environment
//$res = 0;
//// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
//if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
//// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
//$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
//while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
//if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
//if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
//// Try main.inc.php using relative path
//if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
//if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
//if (!$res) die("Include of main fails");
//
//// Define js type
//header('Content-Type: application/javascript');
//// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
//// You can use CTRL+F5 to refresh your browser cache.
//if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
//else header('Cache-Control: no-cache');
//

/* Javascript library of module DigiriskDolibarr */

'use strict';

/**
 * @namespace EO_Framework_Init
 *
 * @author Eoxia <dev@eoxia.com>
 * @copyright 2015-2018 Eoxia
 */

/*

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

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @param  {void} cbName [description]
	 * @param  {void} cbArgs [description]
	 * @returns {void}        [description]
	 */
	window.eoxiaJS.cb = function( cbName, cbArgs ) {
		var key = undefined;
		var slug = undefined;
		for ( key in window.eoxiaJS ) {

			for ( slug in window.eoxiaJS[key] ) {

				if ( window.eoxiaJS[key] && window.eoxiaJS[key][slug] && window.eoxiaJS[key][slug][cbName] ) {
					window.eoxiaJS[key][slug][cbName](cbArgs);
				}
			}
		}
	};

	jQuery( document ).ready( window.eoxiaJS.init );
}

/**
 * Initialise l'objet "navigation" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since 6.0.0
 * @version 7.0.0
 */

window.eoxiaJS.navigation = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @return {void}
 *
 * @since 6.0.0
 * @version 6.2.4
 */
window.eoxiaJS.navigation.init = function() {
	window.eoxiaJS.navigation.event();
};

/**
 * La méthode contenant tous les évènements pour la navigation.
 *
 * @since 6.0.0
 * @version 6.3.0
 *
 * @return {void}
 */
window.eoxiaJS.navigation.event = function() {
	//toggles
	jQuery( document ).on( 'click', '.digirisk-wrap .navigation-container .unit-container .toggle-unit', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '#newGroupment', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '#newWorkunit', window.eoxiaJS.navigation.switchToggle );
	jQuery( document ).on( 'click', '.digirisk-wrap .navigation-container .toolbar div', window.eoxiaJS.navigation.toggleAll );
	jQuery( document ).on( 'click', '#slider', window.eoxiaJS.navigation.setUnitActive );

	//menu button
	jQuery( document ).on( 'click', '#slider', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#newGroupment', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#newWorkunit', window.eoxiaJS.redirect );
	// tabs
	jQuery( document ).on( 'click', '#elementDocument', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementCard', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementAgenda', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementRisk', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementSignalisation', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementLegaldisplay', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementInformationssharing', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementListingrisksaction', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#elementListingrisksphoto', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#riskDocumentSubmit', window.eoxiaJS.redirectRiskDocument );

	//modal
	jQuery( document ).on( 'click', '.modal-close', window.eoxiaJS.closeModal );
	jQuery( document ).on( 'click', '.modal-open', window.eoxiaJS.openModal );

	//action buttons
	jQuery( document ).on( 'click', '#actionButtonEdit', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#actionButtonCancelCreate', window.eoxiaJS.redirectAfterCancelCreate );

	//photos

	jQuery( document ).on( 'click', '.clickable-photo', window.eoxiaJS.selectPhoto );

	//signalisation

	jQuery( document ).on( 'click', '.signalisation-edit', window.eoxiaJS.editSignalisation );
	jQuery( document ).on( 'click', '.signalisation-create:not(.button-disable)', window.eoxiaJS.createSignalisation );
	jQuery( document ).on( 'click', '.signalisation-save', window.eoxiaJS.saveSignalisation );
	jQuery( document ).on( 'click', '.signalisation-delete', window.eoxiaJS.deleteSignalisation );
	jQuery( document ).on( 'click', '.signalisation-pic', window.eoxiaJS.haveSignalisationDataInInput );

	//risks
	jQuery( document ).on( 'click', '.risk-edit', window.eoxiaJS.editRisk );
	jQuery( document ).on( 'click', '.risk-create:not(.button-disable)', window.eoxiaJS.createRisk );
	jQuery( document ).on( 'click', '.risk-save', window.eoxiaJS.saveRisk );
	jQuery( document ).on( 'click', '.risk-delete', window.eoxiaJS.deleteRisk );
	//dropdown cotation
	jQuery( document ).on( 'click', '.table.risk .dropdown-list li.dropdown-item:not(.open-popup), .wpeo-table.table-listing-risk .dropdown-list li.dropdown-item:not(.open-popup), .wpeo-table.table-risk .dropdown-list li.dropdown-item:not(.open-popup)', window.eoxiaJS.selectSeuil );

};


/**
 * Gestion du toggle dans la navigation.
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
 * @param  {MouseEvent} event Les attributs lors du clic
 * @return {void}
 */
window.eoxiaJS.navigation.toggleAll = function( event ) {
	event.preventDefault();

	if ( jQuery( this ).hasClass( 'toggle-plus' ) ) {

		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit' ).addClass( 'toggled' );

		// local storage add all
		let MENU = $( '.digirisk-wrap .navigation-container .workunit-list .unit .title' ).get().map( v => v.attributes.value.value)
		localStorage.setItem('menu', JSON.stringify(Object.values(MENU)) )

	}

	if ( jQuery( this ).hasClass( 'toggle-minus' ) ) {
		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit.toggled' ).removeClass( 'toggled' );
		jQuery( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').addClass( 'fa-chevron-right').removeClass( 'fa-chevron-down' );

		// local storage delete all
		let emptyMenu = new Set('0')
		localStorage.setItem('menu', JSON.stringify(Object.values(emptyMenu)) )

	}
};


/**
 * Ajout la classe 'active' à l'élément.
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.navigation.setUnitActive = function( event ) {

	jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
	let id = $(this).attr('value')

	jQuery( this ).closest( '.unit' ).addClass( 'active' );
	jQuery( this ).closest( '.unit' ).attr( 'value', id );

};

window.eoxiaJS.redirect = function( event ) {
	var URLToGo = '';
	var params = new window.URLSearchParams(window.location.search);
	var id = $(params.get('id'))

	//get ID from div selected in left menu
	history.pushState({ path:  document.URL}, '', this.href)
	//change URL without refresh
	if (!id) {
		URLToGo = document.URL.split('?id=')[0]
	} else {
		URLToGo = document.URL
	}
	console.log(id)
	console.log(URLToGo)

	//empty and fill object card
	$('#cardContent').empty()
	//$('#cardContent').attr('value', id)
	$('#cardContent').load( URLToGo + ' #cardContent' , id);
	return false;
};

window.eoxiaJS.redirectAfterCancelCreate = function( event ) {

	var params = new window.URLSearchParams(window.location.search);
	let id = $(params.get('id'))

	//id of parent object if cancel create
	var parentID = document.URL.split("fk_parent=")[1]
	var URL = document.URL.split("?action")[0]
	if (parentID > 0) {
		//get ID from div selected in left menu
		history.pushState({ path:  document.URL}, '', URL  + '?id=' + parentID)
		//change URL without refresh
	} else {
		history.pushState({ path:  document.URL}, '', URL)
	}

	jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
	jQuery( `#scores[value="${parentID}"]` ).closest( '.unit' ).addClass( 'active' );
	jQuery( '#scores' ).closest( '.unit' ).attr( 'value', parentID );

	//empty and fill object card
	$('#cardContent').empty()
	$('#cardContent').attr('value', id)
	$('#cardContent').load( document.URL + ' #cardContent' , id);

	return false;

};



window.eoxiaJS.redirectRiskDocument = function( event ) {
	var fileList = "";
	var riskID = jQuery( this ).attr('value');
	var fileSelector = document.getElementById('riskDocument');
	fileList = fileSelector.files[0];

	var formData = new FormData();

	formData.append("userfile", fileList);
	var request = new XMLHttpRequest();
	request.open("POST", document.URL.split('digiriskelement_risk')[0] + 'archives/risk_document.php?id=' + riskID + '&sendit=true');
	request.send(formData);

	window.location.reload();
	//$('#cardContent').empty()
	//$('#cardContent').load( document.URL + ' #cardContent');

	//var params = new window.URLSearchParams(window.location.search);
	//var id = $(params.get('id'));
	//
	////get ID from div selected in left menu
	//history.pushState({ path:  document.URL}, '', this.href);
	////change URL without refresh
	//
	////empty and fill object card

	//$('#cardContent').empty();
	////$('#cardContent').attr('value', id)
	//$('#cardContent').load( document.URL.split('risk_card')[0] + 'archives/risk_document.php?id=' + riskID + '&sendit=salut');
	//return false;
};

// Onglet risques

window.eoxiaJS.closeModal = function ( event ) {
	$('.modal-active').removeClass('modal-active')
}

window.eoxiaJS.openModal = function ( event ) {

	let idSelected = $(this).attr('value');


	$('.modal-active').removeClass('modal-active');
	console.log(this)
	if ($(this).hasClass('digirisk-evaluation')) {
		$('#digirisk_evaluation_modal'+idSelected).addClass('modal-active');
	}
	else {
		$('#cotation_modal'+idSelected).addClass('modal-active');
		$('#photo_modal'+idSelected).addClass('modal-active');
	}
}

window.eoxiaJS.createRisk = function ( event ) {


	$('.risk-create.wpeo-button.add').addClass('button-disable');

	var description = $('#riskComment').val()
	var descriptionPost = ''
	if (description !== '') {
		descriptionPost = '&riskComment=' + encodeURI(description)
	}

	var comment = $('#evaluationComment').val()
	var commentPost = ''
	if (comment !== '') {
		commentPost = '&evaluationComment=' + encodeURI(comment)
	}

	var method = $('#cotationMethod0').val()
	var methodPost = ''
	if (method !== '') {
		methodPost = '&cotationMethod=' + method
	}

	var cotation = $('#cotationSpan0').text()
	var cotationPost = ''
	if (cotation !== 0) {
		cotationPost = '&cotation=' + cotation
	}

	var ref = $('#new_item_ref').val()
	var refPost = ''
	if (ref !== 0) {
		refPost = '&ref=' + ref
	}

	var category = $('.input-hidden-danger').val()
	var categoryPost = ''
	if (category !== 0) {
		categoryPost = '&category=' + category
	}

	var photo = $('#photoLinked0').val()
	var photoPost = ''
	if (photo !== 0) {
		photoPost = '&photo=' + encodeURI(photo)
	}

	let criteres = ''
	Object.values($('.table-cell.active.cell-0')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres += '&' + $(v).data( 'type' ) + '=' + $(v).data( 'seuil' )
		}

	})
	
	$('.main-table').load( document.URL + '&action=add' + refPost + commentPost + categoryPost + cotationPost + descriptionPost + methodPost + criteres + photoPost + ' .main-table')

}

window.eoxiaJS.editRisk = function ( event ) {

	let editedRiskId = $(this).attr('value')
	$('#risk_row_'+editedRiskId).empty()
	$('#risk_row_'+editedRiskId).load( document.URL + '&action=editRisk' + editedRiskId + ' #risk_row_'+editedRiskId+' > div')

}

window.eoxiaJS.deleteRisk = function ( event ) {

	let deletedRiskId = $(this).attr('value')
	var r = confirm('Are you sure you want to delete this risk ?')
	if (r == true) {
		$('#risk_row_'+deletedRiskId).empty()
		$('#risk_row_'+deletedRiskId).load( document.URL + '&action=deleteRisk&deletedRiskId=' + deletedRiskId + ' #risk_row_'+deletedRiskId+' > div')
	} else {
		return false
	}
}

window.eoxiaJS.saveRisk = function ( event ) {

	let editedRiskId = $(this).attr('value')

	var description = $('#riskComment'+editedRiskId).val()

	var descriptionPost = ''
	if (description !== '') {
		descriptionPost = '&riskComment=' + encodeURI(description)
	}

	var comment = $('#evaluationComment'+editedRiskId).val()
	var commentPost = ''
	if (comment !== '') {
		commentPost = '&evaluationComment=' + encodeURI(comment)
	}

	var method = $('#cotationMethod'+editedRiskId).val()
	var methodPost = ''
	if (method !== '') {
		methodPost = '&cotationMethod=' + method
	}

	var cotation = $('.cotation'+editedRiskId).text()
	var cotationPost = ''
	if (cotation !== 0) {
		cotationPost = '&cotation=' + cotation
	}

	let criteres = ''
	Object.values($('.table-cell.active')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres += '&' + $(v).data( 'type' ) + '=' + $(v).data( 'seuil' )
		}

	})

	var photo = $('#photoLinked'+editedRiskId).val()
	var photoPost = ''
	if (photo !== 0) {
		photoPost = '&photo=' + encodeURI(photo)
	}

	console.log(this)
	$('#risk_row_'+editedRiskId).empty()
	$('#risk_row_'+editedRiskId).load( document.URL + '&action=saveRisk&riskID=' + editedRiskId + commentPost + cotationPost + methodPost + criteres  + descriptionPost + photoPost + ' #risk_row_'+editedRiskId+' > div')
}
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
}

/**
 *
 *
 * Méthode Evarisk Cotation
 *
 */

/**
 * Clique sur une des cotations simples.
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 *
 * @since 6.0.0
 * @version 7.0.0
 */
window.eoxiaJS.selectSeuil = function( event ) {
	var element      = jQuery( this );
	var riskID       = element.data( 'id' );
	var seuil        = element.data( 'seuil' );
	var variableID   = element.data( 'variable-id' );
	var evaluationID = element.data( 'evaluation-id' );
	var evaluationMethod = element.data( 'evaluation-method' );

	jQuery( '.risk-row.edit[data-id="' + riskID + '"] .cotation-container .dropdown-toggle.cotation span' ).text( jQuery( this ).text() );
	jQuery( '.risk-row.edit[data-id="' + riskID + '"] .cotation-container .dropdown-toggle.cotation' ).attr( 'data-scale', seuil );

	if ( variableID && seuil ) {
		window.eoxiaJS.updateInputVariables( riskID, evaluationID, variableID, seuil, evaluationMethod );
	}
};

window.eoxiaJS.updateInputVariables = function( riskID, evaluationID, variableID, value, evaluationMethod, field ) {

	$('#cotationInput').attr('value', evaluationID)
	$('#cotationMethod'+riskID).attr('value', evaluationMethod)
	$('#cotationSpan'+riskID).text(evaluationID)
	$('#cotationSpan'+riskID).attr('data-scale', window.eoxiaJS.getDynamicScale(evaluationID))
	var element = jQuery(this);

	// Rend le bouton "active".
	if ( window.eoxiaJS.riskCategory.haveDataInInput(element)) {
		$( '.action .wpeo-button.button-disable' ).removeClass( 'button-disable' );
	}
};
/**
 * Initialise l'objet "evaluationMethodEvarisk" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since 1.0
 * @version 6.2.6.0
 */
window.eoxiaJS.evaluationMethodEvarisk = {};

window.eoxiaJS.evaluationMethodEvarisk.init = function() {
	window.eoxiaJS.evaluationMethodEvarisk.event();
};

window.eoxiaJS.evaluationMethodEvarisk.event = function() {
	jQuery( document ).on( 'click', '.wpeo-modal.evaluation-method .wpeo-table.evaluation-method .table-cell.can-select', window.eoxiaJS.evaluationMethodEvarisk.selectSeuil );
	jQuery( document ).on( 'click', '.wpeo-modal.evaluation-method .wpeo-button.button-main', window.eoxiaJS.evaluationMethodEvarisk.save );
	jQuery( document ).on( 'click', '.wpeo-modal.evaluation-method .wpeo-button.button-secondary', window.eoxiaJS.evaluationMethodEvarisk.close_modal );
};

window.eoxiaJS.evaluationMethodEvarisk.selectSeuil = function( event ) {
	jQuery( this ).closest( '.table-row' ).find( '.active' ).removeClass( 'active' );
	jQuery( this ).addClass( 'active' );

	var element      = jQuery( this );
	var riskID       = element.data( 'id' );
	var seuil        = element.data( 'seuil' );
	var variableID   = element.data( 'variable-id' );
	var evaluationID = element.data( 'evaluation-id' );

	window.eoxiaJS.evaluationMethodEvarisk.updateInputVariables( riskID, evaluationID, variableID, seuil, jQuery( '.wpeo-modal.modal-active.modal-risk-' + riskID + ' textarea' ) );

	var currentVal    = JSON.parse(jQuery( '.wpeo-modal.modal-risk-' + riskID + ' textarea' ).val());
	var canGetDetails = true;
	for (var key in currentVal) {
		if (currentVal[key] == '') {
			canGetDetails = false;
		}
	}
	console.log(riskID)
	if ( jQuery( '.wpeo-modal.modal-active.modal-risk-' + riskID + ' .table-cell.active' ).length == 5 ) {
		if ( jQuery( '.wpeo-modal.modal-active.modal-risk-' + riskID + ' .button-main' ).length ) {
			jQuery( '.wpeo-modal.modal-active.modal-risk-' + riskID + ' .button-main' ).addClass( 'disabled' );
		}
	}
};

window.eoxiaJS.getDynamicScale = function (cotation) {
	let scale = 0

	switch (true) {
		case (cotation < 48):
			scale = 1
			return scale;
		case (cotation < 51):
			scale = 2
			return scale;
		case (cotation < 79):
			scale = 3
			return scale;
		case (cotation < 101):
			scale = 4
			return scale;
		case (cotation === 0):
			scale = 1
			return scale;
	}
}

window.eoxiaJS.evaluationMethodEvarisk.updateInputVariables = function( riskID, evaluationID, variableID, value, field ) {
	var updateEvaluationID = false;


	console.log($('.table-cell.active'))

	let criteres = []
	Object.values($('.table-cell.active.cell-'+riskID)).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres.push($(v).data( 'seuil' ))
		}

	})

 	if ( updateEvaluationID ) {
		jQuery( '.risk-row.edit[data-id="' + riskID + '"] input[name="evaluation_method_id"]' ).val( evaluationID );
	}

	// Rend le bouton "active" et met à jour la cotation et la scale
	if (criteres.length === 5) {
		let cotationBeforeAdapt = criteres[0] * criteres[1] * criteres[2] * criteres[3] * criteres[4]

		fetch('js/json/default.json')
			.then(response => response.json())
			.then(data => {

				let cotationAfterAdapt = data[0].option.matrix[cotationBeforeAdapt]
				$('.cotation.cotation-span'+riskID).attr('data-scale', window.eoxiaJS.getDynamicScale(cotationAfterAdapt) )

				$('#current_equivalence'+riskID).text(cotationAfterAdapt)
			});

		jQuery( '.wpeo-button.cotation-save.button-disable' ).removeClass( 'button-disable' );
	}
};

window.eoxiaJS.evaluationMethodEvarisk.save = function( event ) {
	var riskID       = jQuery( this ).data( 'id' );
	let value 		 = $('.cotation.cotation-span'+riskID).text()
	let evaluationMethod = "digirisk"

	$('#cotationInput').attr('value', value)
	$('#cotationMethod'+riskID).attr('value', evaluationMethod)
	$('#cotationSpan'+riskID).text(value)
	$('#cotationSpan'+riskID).attr('data-scale', window.eoxiaJS.getDynamicScale(value))

	window.eoxiaJS.evaluationMethodEvarisk.close_modal( undefined, riskID );
};

window.eoxiaJS.evaluationMethodEvarisk.close_modal = function( event, riskID ) {
	if ( ! riskID ) {
		riskID = jQuery( this ).data( 'id' );
	}

	jQuery( '.wpeo-modal.modal-active .modal-close' ).click();
};

window.eoxiaJS.evaluationMethodEvarisk.fillVariables = function( element ) {
	element.attr( 'data-variables', element.closest( 'td' ).find( 'textarea[name="evaluation_variables"]' ).val() );
}

/**
 * Initialise l'objet "riskCategory" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since 6.0.0
 * @version 7.0.0
 */
window.eoxiaJS.riskCategory = {};

window.eoxiaJS.riskCategory.init = function() {
	window.eoxiaJS.riskCategory.event();
};

window.eoxiaJS.riskCategory.event = function() {
	jQuery( document ).on( 'click', '.table .category-danger .item, .wpeo-table .category-danger .item', window.eoxiaJS.riskCategory.selectDanger );
};

/**
 * Lors du clic sur un riskCategory, remplaces le contenu du toggle et met l'image du risque sélectionné.
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 *
 * @since 6.0.0
 * @version 7.0.0
 */
window.eoxiaJS.riskCategory.selectDanger = function( event ) {
	var element = jQuery(this);
	var data = {};
	element.closest('.content').removeClass('active');
	element.closest('tr, .table-row').find('input.input-hidden-danger').val(element.data('id'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle span').hide();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').show();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('src', element.find('img').attr('src'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('srcset', '');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('sizes', '');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('aria-label', element.closest('.tooltip').attr('aria-label'));

	//window.eoxiaJS.tooltip.remove(element.closest('.risk-row').find('.category-danger.wpeo-tooltip-event'));

	// Rend le bouton "active".
	if ( window.eoxiaJS.riskCategory.haveDataInInput(element)) {
		$( '.action .wpeo-button.button-disable' ).removeClass( 'button-disable' );
	}


//	// Si aucune donnée est entrée, on lance la requête.
//	if ( element.data( 'is-preset' ) && ! window.eoxiaJS.riskCategory.haveDataInInput( element ) ) {
//		data.action = 'check_predefined_danger';
//		data._wpnonce = element.closest( '.wpeo-dropdown' ).data( 'nonce' );
//		data.danger_id = element.data( 'id' );
//		data.society_id = element.closest( '.risk-row' ).find( 'input[name="parent_id"] ' ).val();
//
//		window.eoxiaJS.display( jQuery( this ).closest( 'table, .wpeo-table' ) );
//
//		window.eoxiaJS.request.send( jQuery( this ).closest( '.wpeo-dropdown' ), data );
//	}
//};
};
window.eoxiaJS.riskCategory.haveDataInInput = function( element ) {
	if ( $( '.risk-row' ).find( 'input[name="risk_category_id"]' ).val() > 0  && $('#cotationMethod0').val()  ) {
		return true;
	}
	return false;
};



// Photos

window.eoxiaJS.selectPhoto = function( event ) {
	let photoID = $(this).attr('value')

	$('.clicked-photo').children('img.photo').attr('style', 'none !important')
	$('.clicked-photo').removeClass('clicked-photo')

	$('.clickable-photo'+photoID).children('img.photo'+photoID).attr('style', 'border: 5px solid #555 !important')
	$('.clickable-photo'+photoID).addClass('clicked-photo')
	let riskToAssign = $(this).children('input').attr('id').split('filename')[1]

	let photoName = $('.clicked-photo').children('#filename'+riskToAssign).attr('value')
	$('#photoLinked'+riskToAssign).attr('value', photoName)
	$('.photo-edit'+riskToAssign).children('img').attr('src' , $('#pathToPhoto'+riskToAssign).val() + photoName)
};

// Onglet signalisations



window.eoxiaJS.createSignalisation = function ( event ) {


	$('.risk-create.wpeo-button.add').addClass('button-disable');

	var description = $('#signalisationComment').val()
	var descriptionPost = ''
	if (description !== '') {
		descriptionPost = '&signalisationComment=' + encodeURI(description)
	}

	var ref = $('#new_item_ref').val()
	var refPost = ''
	if (ref !== 0) {
		refPost = '&ref=' + ref
	}

	var category = $('.input-hidden-danger').val()
	var categoryPost = ''
	if (category !== 0) {
		categoryPost = '&category=' + category
	}

	var photo = $('#photoLinked0').val()
	var photoPost = ''
	if (photo !== 0) {
		photoPost = '&photo=' + encodeURI(photo)
	}

	$('.main-table').load( document.URL + '&action=add' + refPost  + categoryPost  + descriptionPost  + photoPost + ' .main-table')

}

window.eoxiaJS.editSignalisation = function ( event ) {

	let editedSignalisationId = $(this).attr('value')
	$('#signalisation_row_'+editedSignalisationId).empty()
	$('#signalisation_row_'+editedSignalisationId).load( document.URL + '&action=editSignalisation' + editedSignalisationId + ' #signalisation_row_'+editedSignalisationId+' > div')

}

window.eoxiaJS.deleteSignalisation = function ( event ) {

	let deletedSignalisationId = $(this).attr('value')
	var r = confirm('Are you sure you want to delete this signalisation ?')
	if (r == true) {
		$('#signalisation_row_'+deletedSignalisationId).empty()
		$('#signalisation_row_'+deletedSignalisationId).load( document.URL + '&action=deleteSignalisation&deletedSignalisationId=' + deletedSignalisationId + ' #signalisation_row_'+deletedSignalisationId+' > div')
	} else {
		return false
	}
}

window.eoxiaJS.saveSignalisation = function ( event ) {

	let editedSignalisationId = $(this).attr('value')

	var description = $('#signalisationComment'+editedSignalisationId).val()
	var descriptionPost = ''
	if (description !== '') {
		descriptionPost = '&signalisationComment=' + encodeURI(description)
	}

	var photo = $('#photoLinked'+editedSignalisationId).val()
	var photoPost = ''
	if (photo !== 0) {
		photoPost = '&photo=' + encodeURI(photo)
	}

	$('#signalisation_row_'+editedSignalisationId).empty()
	$('#signalisation_row_'+editedSignalisationId).load( document.URL + '&action=saveSignalisation&signalisationID=' + editedSignalisationId + descriptionPost + photoPost + ' #signalisation_row_'+editedSignalisationId+' > div')
}
window.eoxiaJS.haveSignalisationDataInInput = function( element ) {
	$( '.action .wpeo-button.button-disable' ).removeClass( 'button-disable' );
};
