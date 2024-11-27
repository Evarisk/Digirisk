/* Copyright (C) 2023-2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/ticket.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript ticket file for module DigiriskDolibarr
 */

'use strict';

/**
 * Init ticket JS
 *
 * @memberof DigiriskDolibarr_Ticket
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @type {Object}
 */
window.digiriskdolibarr.ticket = {};

/**
 * Ticket init
 *
 * @memberof DigiriskDolibarr_Ticket
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.init = function() {
  window.digiriskdolibarr.ticket.event();
};

/**
 * Registers all event listeners for the ticket system.
 *
 * This method binds various events to elements associated with ticket functionalities:
 * - Click events for managing parent and subcategories.
 * - Keyup events for validating email and phone inputs.
 *
 * The commented-out lines are placeholders for additional functionality, such as file handling
 * and dashboard ticket info management, which can be activated as needed.
 *
 * @memberof DigiriskDolibarr_Ticket
 * @since    1.1.0
 * @version  10.3.0
 *
 * @returns {void} This method does not return a value.
 */
window.digiriskdolibarr.ticket.event = function() {
  $(document).on('click', '.ticket-parentCategory', window.digiriskdolibarr.ticket.getMainCategoryID);
  $(document).on('click', '.ticket-subCategory', window.digiriskdolibarr.ticket.getSheetSubCategoryID);
	//$( document ).on( 'submit', '#sendFile', window.digiriskdolibarr.ticket.tmpStockFile );
	//$( document ).on( 'click', '.linked-file-delete', window.digiriskdolibarr.ticket.removeFile );
	// $( document ).on( 'change', '.add-dashboard-info', window.digiriskdolibarr.ticket.addDashBoardTicketInfo );
	// $( document ).on( 'click', '.close-dashboard-info', window.digiriskdolibarr.ticket.closeDashBoardTicketInfo );
	$( document ).on( 'keyup', '#email', window.digiriskdolibarr.ticket.checkValidEmail );
	$( document ).on( 'keyup', '#options_digiriskdolibarr_ticket_phone', window.digiriskdolibarr.ticket.checkValidPhone );
};

/**
 * Retrieves the main category ID when a parent category element is clicked.
 *
 * This method performs the following actions:
 * - Retrieves the `data-rowid` attribute of the clicked element to identify the category.
 * - Sends an AJAX request to update the category display based on the selected parent category.
 * - Highlights the selected category as active.
 *
 * Error handling can be added within the `error` callback to manage failures gracefully.
 *
 * @memberof DigiriskDolibarr_Ticket
 * @since    10.3.0
 * @version  10.3.0
 *
 * @returns {void} This method does not return a value.
 */
window.digiriskdolibarr.ticket.getMainCategoryID = function() {
  let mainCategoryID  = $(this).data('rowid');
  let token           = window.saturne.toolbox.getToken();
  let querySeparator  = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.categories-container'));

  $.ajax({
    url: document.URL + querySeparator + 'parentCategory=' + mainCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.img-fields-container').replaceWith($(resp).find('.img-fields-container'));
      $('.ticket-parentCategory[data-rowid=' + mainCategoryID + ']').addClass('active');
    },
    error: function() {
      console.error('Failed to retrieve category data. Please try again.');
    }
  });
};

/**
 * Get sheet sub category ID after click event
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.getSheetSubCategoryID = function() {
  let mainCategoryID     = $('.ticket-parentCategory.active').data('rowid');
  let subCategoryID      = $(this).data('rowid');
  let token              = window.saturne.toolbox.getToken();
  let querySeparator     = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.categories-container'));

  $.ajax({
    url: document.URL + querySeparator + 'parentCategory=' + mainCategoryID + '&subCategory=' + subCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.img-fields-container').replaceWith($(resp).find('.img-fields-container'));
      $('.ticket-parentCategory[data-rowid=' + mainCategoryID + ']').addClass('active');
      $('.ticket-subCategory[data-rowid=' + subCategoryID + ']').addClass('active');
    },
    error: function() {}
  });
};

/**
 * Upload automatiquement le(s) fichier(s) séelectionnés dans ecm/digiriskdolibarr/ticket/tmp/__REF__
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.tmpStockFile = function( ) {
	event.preventDefault()

	var files = $('#sendfile').prop('files');

	const formData = new FormData();
	for (let i = 0; i < files.length; i++) {
		let file = files[i]
		formData.append('files[]', file)
	}
	var ticket_id      = $('#ticket_id').val()
	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

	window.saturne.loader.display($('.files-uploaded'));

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
window.digiriskdolibarr.ticket.removeFile = function( event ) {
	let filetodelete   = $(this).attr('value');
	filetodelete       = filetodelete.replace('_mini', '')
	let ticket_id      = $('#ticket_id').val()
	let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL)

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
window.digiriskdolibarr.ticket.addDashBoardTicketInfo = function() {
	let token = window.saturne.toolbox.getToken()

	let selectTitle       = $('#select2-boxcombo-container').attr('title')
	let digiriskelementID = selectTitle.split(' : ')[0];
	let catID             = selectTitle.split(' : ')[2];
	let querySeparator    = window.saturne.toolbox.getQuerySeparator(document.URL)

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
window.digiriskdolibarr.ticket.closeDashBoardTicketInfo = function() {
	let token = window.saturne.toolbox.getToken()

	let box               = $(this);
	let digiriskelementID = box.attr('data-digiriskelementid');
	let catID             = box.attr('data-catid');
	let querySeparator    = window.saturne.toolbox.getQuerySeparator(document.URL)

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
window.digiriskdolibarr.ticket.checkValidEmail = function() {
	var reEmail = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	if (reEmail.test(this.value) == false) {
		$(this).css("border", "3px solid red");
	} else {
		$(this).css("border", "3px solid green");
	}
};

/**
 * Check if phone is valid
 *
 * @since   9.8.2
 * @version 9.8.2
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.checkValidPhone = function() {
	var rePhone = /^(?:(?:(?:\+|00)\d{2}[\s]?(?:\(0\)[\s]?)?)|0){1}[1-9]{1}([\s.-]?)(?:\d{2}\1?){3}\d{2}$/;
	if (rePhone.test(this.value) == false) {
		$(this).css("border", "3px solid red");
	} else {
		$(this).css("border", "3px solid green");
	}
};
