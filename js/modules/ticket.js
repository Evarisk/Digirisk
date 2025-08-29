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
  $(document).on('click', '.ticket-parentCategory, .ticket-subCategory', function() {
	if (!$(this).parent().hasClass('category-redirect')) {
		window.digiriskdolibarr.ticket.handleCategorySelection({
		  clickedElement: this,
		  isSubCategory: $(this).hasClass('ticket-subCategory')
		});
	}
  });


  $(document).on( 'click', '.public-ticket-validate', window.digiriskdolibarr.ticket.addSignature);
  $(document).on( 'submit', '#sendFile', window.digiriskdolibarr.ticket.tmpStockFile);
  $(document).on( 'click', '.linked-file-delete', window.digiriskdolibarr.ticket.removeFile);
  $(document).on( 'change', '.add-dashboard-info', window.digiriskdolibarr.ticket.addDashBoardTicketInfo);
  $(document).on( 'click', '.close-dashboard-info', window.digiriskdolibarr.ticket.closeDashBoardTicketInfo);
  $(document).on( 'keyup', '#email', window.digiriskdolibarr.ticket.checkValidEmail);
  $(document).on( 'keyup', '#options_digiriskdolibarr_ticket_phone', window.digiriskdolibarr.ticket.checkValidPhone);

  $(document).on( 'change', '.param-table input, .param-table select, .param-table textarea', window.digiriskdolibarr.ticket.handleParamChange);
  CKEDITOR.on('instanceReady', function(e) {
	CKEDITOR.instances[e.editor.name].on('change', function() {
		window.digiriskdolibarr.ticket.handleParamChange.call(this.container.$)
	});
 });
};

/**
 * Handles the retrieval of category data (main or subcategory) and updates the display.
 *
 * This function performs the following actions:
 * - Retrieves the `data-rowid` attribute of the clicked element to identify the category (main or sub).
 * - Optionally identifies the currently active parent category if handling a subcategory.
 * - Sends an AJAX request to fetch and update the category display based on the selected category.
 * - Replaces the `.digirisk-page-container` content with the updated response.
 * - Highlights the selected category as active.
 * - Redraws the signature canvas using `window.saturne.signature.drawSignatureOnCanvas()`.
 *
 * Error handling can be added within the `error` callback to manage failures gracefully.
 *
 * @memberof DigiriskDolibarr_Ticket
 * @since    10.3.0
 * @version  10.3.0
 *
 * @param {Object}      options An object containing configuration for the request:
 * @param {HTMLElement} options.clickedElement The DOM element that triggered the click event.
 * @param {boolean}     options.isSubCategory Indicates whether the clicked element is a subcategory.
 *
 * @returns {void} This method does not return a value.
 */
window.digiriskdolibarr.ticket.handleCategorySelection = function(options) {
  const { clickedElement, isSubCategory } = options;

  let mainCategoryID = isSubCategory ? $('.ticket-parentCategory.active').data('rowid') : $(clickedElement).data('rowid');
  let subCategoryID  = isSubCategory ? $(clickedElement).data('rowid') : null;
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.categories-container'));

  let url = document.URL + querySeparator + 'parentCategory=' + mainCategoryID + (isSubCategory ? '&subCategory=' + subCategoryID : '') + '&token=' + token;
  $.ajax({
    url: url,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.digirisk-page-container').replaceWith($(resp).find('.digirisk-page-container'));
      $('.ticket-parentCategory[data-rowid=' + mainCategoryID + ']').addClass('active');
      if (isSubCategory) {
        $('.ticket-subCategory[data-rowid=' + subCategoryID + ']').addClass('active');
      }
      window.saturne.signature.drawSignatureOnCanvas();
    },
    error: function() {
      console.error('Failed to retrieve category data. Please try again.');
    }
  });
};

/**
 * Captures and stores the current signature from the canvas.
 *
 * This function performs the following actions:
 * - Checks if the `signaturePad` canvas exists and contains a drawn signature.
 * - Converts the signature into a Base64 data URL format.
 * - Stores the serialized signature data into a hidden input field named `signature`.
 *
 * @memberof DigiriskDolibarr_Ticket
 * @since    10.3.0
 * @version  10.3.0
 *
 * @returns {void} This method does not return a value.
 */
window.digiriskdolibarr.ticket.addSignature = function() {
  if (window.saturne.signature.canvas) {
    if (!window.saturne.signature.canvas.signaturePad.isEmpty()) {
      let signature = window.saturne.signature.canvas.toDataURL();
      $('input[name="signature"]').val(JSON.stringify(signature));
    }
  }
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

	let files = $('#sendfile').prop('files');
	let parentCategory = $('#parentCategory').val();
	let subCategory = $('#subCategory').val();


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

		let errorMessage = $(resp).find('.file-error').val();
		if (errorMessage) {
			$.jnotify(errorMessage, 'error');
		}

		$('#sendFileForm').load(document.URL+ querySeparator + 'ticket_id='+ticket_id + '&parentCategory='+parentCategory + '&subCategory='+subCategory + ' #fileLinkedTable')
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
    contentType: 'application/json charset=utf-8',
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
		contentType: 'application/json charset=utf-8',
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

/**
 * Handle parameter change to enable save button
 *
 * @since   21.1.0
 * @version 21.1.0
 */
window.digiriskdolibarr.ticket.handleParamChange = function() {
	$table = $(this).closest('table');
	$btn   = $('.'+$table.data('btn'));
	$btn.prop('disabled', false);
};
