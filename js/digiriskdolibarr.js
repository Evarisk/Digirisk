/* Javascript library of module DigiriskDolibarr */

/**
 * @namespace DigiriskDolibarr_Framework_Init
 *
 * @author Evarisk <technique@evarisk.com>
 * @copyright 2015-2023 Evarisk
 */
'use strict';
/**
 * @namespace EO_Framework_Init
 *
 * @author Evarisk <technique@evarisk.com>
 * @copyright 2015-2021 Evarisk
 */

if ( ! window.digiriskdolibarr ) {
	/**
	 * [digiriskdolibarr description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Object}
	 */
	window.digiriskdolibarr = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.digiriskdolibarr.scriptsLoaded = false;
}

if ( ! window.digiriskdolibarr.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.digiriskdolibarr.init = function() {
		window.digiriskdolibarr.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.digiriskdolibarr.load_list_script = function() {
		if ( ! window.digiriskdolibarr.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.digiriskdolibarr ) {

				if ( window.digiriskdolibarr[key].init ) {
					window.digiriskdolibarr[key].init();
				}

				for ( slug in window.digiriskdolibarr[key] ) {

					if ( window.digiriskdolibarr[key] && window.digiriskdolibarr[key][slug] && window.digiriskdolibarr[key][slug].init ) {
						window.digiriskdolibarr[key][slug].init();
					}

				}
			}

			window.digiriskdolibarr.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.digiriskdolibarr.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.digiriskdolibarr ) {
			if ( window.digiriskdolibarr[key].refresh ) {
				window.digiriskdolibarr[key].refresh();
			}

			for ( slug in window.digiriskdolibarr[key] ) {

				if ( window.digiriskdolibarr[key] && window.digiriskdolibarr[key][slug] && window.digiriskdolibarr[key][slug].refresh ) {
					window.digiriskdolibarr[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.digiriskdolibarr.init );
}
