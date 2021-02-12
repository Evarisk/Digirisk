<?php
/* Copyright (C) 2021 SuperAdmin
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

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');


/**
 * \file    digiriskdolibarr/js/digiriskdolibarr.js.php
 * \ingroup digiriskdolibarr
 * \brief   JavaScript file for module DigiriskDolibarr.
 */

// Load Dolibarr environment
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

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');
?>

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

	//action buttons
	jQuery( document ).on( 'click', '#actionButtonEdit', window.eoxiaJS.redirect );
	jQuery( document ).on( 'click', '#actionButtonCancelCreate', window.eoxiaJS.redirectAfterCancelCreate );

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

	} else {

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

	var params = new window.URLSearchParams(window.location.search);
	var id = $(params.get('id'))

	//get ID from div selected in left menu
	history.pushState({ path:  document.URL}, '', this.href)
	//change URL without refresh

	//empty and fill object card
	$('#cardContent').empty()
	//$('#cardContent').attr('value', id)
	$('#cardContent').load( document.URL + ' #cardContent' , id);

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
