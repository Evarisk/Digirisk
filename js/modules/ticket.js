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

  // Ticket card (issue #4443) — inline (on-the-fly) editing
  $(document).on( 'click',   '.digirisk-ticket-card .dtc-subject-value', window.digiriskdolibarr.ticket.editSubjectInline);
  $(document).on( 'keydown', '.digirisk-ticket-card .dtc-subject-input', window.digiriskdolibarr.ticket.subjectInputKeydown);
  $(document).on( 'blur',    '.digirisk-ticket-card .dtc-subject-input', window.digiriskdolibarr.ticket.saveSubjectInline);
  $(document).on( 'click',   '.digirisk-ticket-card .kanban-add-tag-btn', window.digiriskdolibarr.ticket.toggleTagDropdown);
  $(document).on( 'click',   '.digirisk-ticket-card .kanban-tag-option', window.digiriskdolibarr.ticket.addCategoryInline);
  $(document).on( 'click',   '.digirisk-ticket-card .kanban-tag-remove', window.digiriskdolibarr.ticket.removeCategoryInline);
  $(document).on( 'click',   '.digirisk-ticket-card .kanban-tag-dropdown', function(event) { event.stopPropagation(); });
  $(document).on( 'click',   window.digiriskdolibarr.ticket.closeTagDropdowns);
  $(document).on( 'click',   '.digirisk-ticket-card .dtc-assignee-value', window.digiriskdolibarr.ticket.editAssigneeInline);
  $(document).on( 'change',  '.digirisk-ticket-card .dtc-assignee-select', window.digiriskdolibarr.ticket.saveAssigneeInline);
  $(document).on( 'blur',    '.digirisk-ticket-card .dtc-assignee-select', window.digiriskdolibarr.ticket.closeAssigneeInline);
  $(document).on( 'focus',   '.digirisk-ticket-card .dtc-progress-value[contenteditable]', window.digiriskdolibarr.ticket.progressFocus);
  $(document).on( 'keydown', '.digirisk-ticket-card .dtc-progress-value[contenteditable]', window.digiriskdolibarr.ticket.progressKeydown);
  $(document).on( 'blur',    '.digirisk-ticket-card .dtc-progress-value[contenteditable]', window.digiriskdolibarr.ticket.saveProgressInline);
  $(document).on( 'click',   '.edit-message-on-click', function() { window.location.href = $(this).data('edit-url'); });
  $(document).on( 'click',   '.digirisk-ticket-card .dtc-extrafield-value', window.digiriskdolibarr.ticket.editExtrafieldInline);

  $(document).on( 'change', '.param-table input, .param-table select, .param-table textarea', window.digiriskdolibarr.ticket.handleParamChange);
  // CKEDITOR is only loaded on pages with a rich-text editor. Guard the global
  // so this handler never throws "CKEDITOR is not defined" — an uncaught error
  // here aborts the whole digiriskdolibarr init chain (no try/catch in the
  // auto-init loop), leaving later modules (e.g. the ticket kanban picker)
  // unwired.
  if (typeof CKEDITOR !== 'undefined') {
    CKEDITOR.on('instanceReady', function(e) {
      CKEDITOR.instances[e.editor.name].on('change', function() {
        window.digiriskdolibarr.ticket.handleParamChange.call(this.container.$);
      });
    });
  }
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
	var $table = $(this).closest('.param-table');
	if (!$table.length) {
		return;
	}
	var $btn = $('.' + $table.data('btn'));
	$btn.prop('disabled', false);
};

/**
 * Ticket card (#4443) — start inline edit of the subject.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Click event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.editSubjectInline = function(event) {
  event.preventDefault();
  var $wrap = $(this).closest('.dtc-head').find('.dtc-subject');
  if (!$wrap.length || $wrap.find('.dtc-subject-input').length) {
    return;
  }
  var current = String($wrap.attr('data-value') || '');
  var $input  = $('<input type="text" class="dtc-subject-input" />').val(current);
  $wrap.html($input);
  $input.trigger('focus').trigger('select');
};

/**
 * Ticket card (#4443) — Enter saves, Escape cancels the subject edit.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Keydown event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.subjectInputKeydown = function(event) {
  if (event.key === 'Enter' || event.keyCode === 13) {
    event.preventDefault();
    $(this).trigger('blur');
  } else if (event.key === 'Escape' || event.keyCode === 27) {
    var $wrap = $(this).closest('.dtc-subject');
    $(this).data('done', true);
    window.digiriskdolibarr.ticket.renderSubject($wrap, String($wrap.attr('data-value') || ''));
  }
};

/**
 * Ticket card (#4443) — save the subject on the fly (AJAX), no page reload.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.saveSubjectInline = function() {
  var $input = $(this);
  var $wrap  = $input.closest('.dtc-subject');
  if ($input.data('done')) {
    return;
  }
  $input.data('done', true);
  var newValue = $input.val().trim();
  var oldValue = String($wrap.attr('data-value') || '');
  if (newValue === '' || newValue === oldValue) {
    window.digiriskdolibarr.ticket.renderSubject($wrap, oldValue);
    return;
  }
  var token = window.saturne.toolbox.getToken();
  var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);
  $.ajax({
    url: document.URL + sep + 'action=setsubject_ajax&token=' + token,
    type: 'POST',
    data: { subject: newValue },
    dataType: 'json',
    success: function(resp) {
      var saved = (resp && resp.subject != null) ? resp.subject : newValue;
      $wrap.attr('data-value', saved);
      window.digiriskdolibarr.ticket.renderSubject($wrap, saved);
    },
    error: function() {
      window.digiriskdolibarr.ticket.renderSubject($wrap, oldValue);
    }
  });
};

/**
 * Ticket card (#4443) — render the subject value span.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {jQuery} $wrap Subject wrapper.
 * @param  {string} value Subject value.
 * @return {void}
 */
window.digiriskdolibarr.ticket.renderSubject = function($wrap, value) {
  var text = (value && value.length) ? $('<div>').text(value).html() : '';
  $wrap.html('<span class="dtc-subject-value">' + text + '</span>');
};

/**
 * Ticket card (#4443) — toggle the "add tag" dropdown (same UX as the ticket kanban).
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Click event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.toggleTagDropdown = function(event) {
  event.preventDefault();
  event.stopPropagation();
  var $dropdown = $(this).siblings('.kanban-tag-dropdown');
  $('.digirisk-ticket-card .kanban-tag-dropdown').not($dropdown).removeClass('visible');
  $dropdown.toggleClass('visible');
};

/**
 * Ticket card (#4443) — close any open tag dropdown (outside click).
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.closeTagDropdowns = function() {
  $('.digirisk-ticket-card .kanban-tag-dropdown.visible').removeClass('visible');
};

/**
 * Ticket card (#4443) — add a category on the fly (same endpoint/behaviour as the kanban).
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Click event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.addCategoryInline = function(event) {
  event.stopPropagation();
  var $opt = $(this);
  if ($opt.hasClass('assigned')) {
    return;
  }
  var $dropdown = $opt.closest('.kanban-tag-dropdown');
  var $tagsRow  = $opt.closest('.kanban-card-tags');
  var catId     = $opt.data('value');
  $dropdown.removeClass('visible');
  $.ajax({
    url: $tagsRow.data('tag-url') + '?action=addTicketCategory&ticket_id=' + $tagsRow.data('ticket-id') + '&cat_id=' + catId + '&token=' + window.saturne.toolbox.getToken(),
    type: 'POST',
    dataType: 'json',
    success: function(response) {
      if (!response || !response.success) {
        return;
      }
      var bgColor = response.color ? '#' + response.color : '#8c8c8c';
      var $tag = $('<span class="kanban-tag" data-cat-id="' + response.id + '" style="background:' + bgColor + '">'
        + $('<div>').text(response.label).html()
        + '<span class="kanban-tag-remove" title="&times;">&times;</span></span>');
      $tagsRow.find('.kanban-tag-dropdown-wrapper').before($tag);
      $opt.addClass('assigned').append('<i class="fas fa-check" style="margin-left:auto;font-size:9px;color:#28a745"></i>');
    }
  });
};

/**
 * Ticket card (#4443) — remove a category on the fly (same endpoint/behaviour as the kanban).
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Click event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.removeCategoryInline = function(event) {
  event.stopPropagation();
  var $tag     = $(this).closest('.kanban-tag');
  var $tagsRow = $tag.closest('.kanban-card-tags');
  var catId    = $tag.data('cat-id');
  $tag.css('opacity', '0.4');
  $.ajax({
    url: $tagsRow.data('tag-url') + '?action=removeTicketCategory&ticket_id=' + $tagsRow.data('ticket-id') + '&cat_id=' + catId + '&token=' + window.saturne.toolbox.getToken(),
    type: 'POST',
    dataType: 'json',
    success: function(response) {
      if (!response || !response.success) {
        $tag.css('opacity', '1');
        return;
      }
      $tag.slideUp(150, function() {
        $(this).remove();
        $tagsRow.find('.kanban-tag-option[data-value="' + catId + '"]').removeClass('assigned').find('.fa-check').remove();
      });
    },
    error: function() {
      $tag.css('opacity', '1');
    }
  });
};

/**
 * Ticket card (#4443) — reveal the assignee select for on-the-fly editing.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Click event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.editAssigneeInline = function(event) {
  event.preventDefault();
  var $cell = $(this).closest('.dtc-assignee');
  if ($cell.find('.dtc-assignee-editor').is(':visible')) {
    return;
  }
  $cell.find('.dtc-assignee-value').hide();
  $cell.find('.dtc-assignee-editor').show();
  $cell.find('.dtc-assignee-select').trigger('focus');
};

/**
 * Ticket card (#4443) — save the assignee on the fly (same endpoint as the kanban).
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.saveAssigneeInline = function() {
  var $sel   = $(this);
  var $cell  = $sel.closest('.dtc-assignee');
  var userId = parseInt($sel.val(), 10) || 0;
  $.ajax({
    url: $cell.data('assign-url') + '?action=setassignee_ajax&id=' + $cell.data('ticket-id') + '&user_id=' + userId + '&token=' + window.saturne.toolbox.getToken(),
    type: 'POST',
    dataType: 'json',
    success: function(resp) {
      if (resp && resp.nomurl) {
        $cell.find('.dtc-assignee-value').html(resp.nomurl).show();
      } else {
        $cell.find('.dtc-assignee-value').show();
      }
      $cell.find('.dtc-assignee-editor').hide();
    },
    error: function() {
      $cell.find('.dtc-assignee-value').show();
      $cell.find('.dtc-assignee-editor').hide();
    }
  });
};

/**
 * Ticket card (#4443) — close the assignee editor on blur (no selection made).
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.closeAssigneeInline = function() {
  var $cell = $(this).closest('.dtc-assignee');
  window.setTimeout(function() {
    if ($cell.find('.dtc-assignee-editor').is(':visible')) {
      $cell.find('.dtc-assignee-editor').hide();
      $cell.find('.dtc-assignee-value').show();
    }
  }, 200);
};

/**
 * Ticket card (#4443) — select the whole progress value when it gains focus.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.progressFocus = function() {
  var el = this;
  window.setTimeout(function() {
    var range = document.createRange();
    range.selectNodeContents(el);
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }, 0);
};

/**
 * Ticket card (#4443) — Enter validates the progress (triggers blur), no line break.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Keydown event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.progressKeydown = function(event) {
  if (event.key === 'Enter' || event.keyCode === 13) {
    event.preventDefault();
    $(this).trigger('blur');
  }
};

/**
 * Ticket card (#4443) — save the progress on the fly from the contenteditable value.
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.saveProgressInline = function() {
  var $val     = $(this);
  var $cell    = $val.closest('.dtc-progress');
  var oldValue = parseInt($cell.attr('data-value'), 10) || 0;
  var newValue = parseInt(($val.text() || '').replace(/[^0-9]/g, ''), 10);
  if (isNaN(newValue)) {
    newValue = oldValue;
  }
  newValue = Math.max(0, Math.min(100, newValue));
  $val.text(newValue);
  if (newValue === oldValue) {
    return;
  }
  var action = $cell.data('action') || 'setprogress_ajax';
  $.ajax({
    url: $cell.data('progress-url') + '?action=' + action + '&id=' + $cell.data('ticket-id') + '&token=' + window.saturne.toolbox.getToken(),
    type: 'POST',
    data: { progress: newValue },
    dataType: 'json',
    success: function(resp) {
      var val = (resp && resp.progress != null) ? resp.progress : newValue;
      $cell.attr('data-value', val);
      $val.text(val);
    },
    error: function() {
      $val.text(oldValue);
    }
  });
};

/**
 * Ticket card (#4883) — inline (on-the-fly) editing for extrafields
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} event Click event.
 * @return {void}
 */
window.digiriskdolibarr.ticket.editExtrafieldInline = function(event) {
  event.preventDefault();
  var $span = $(this);
  if ($span.data('editing')) return;
  $span.data('editing', true);

  var type = $span.data('type') || 'text';
  var rawValue = $span.data('raw');
  if (typeof rawValue === 'undefined') rawValue = '';
  
  var $input;
  if (type === 'textarea') {
    $input = $('<textarea class="dtc-extrafield-input" style="width:100%; min-height:80px; outline:none; transition: border-color 0.2s;" autocomplete="off"></textarea>');
  } else if (type === 'select') {
    $input = $('<select class="dtc-extrafield-input" style="width:100%; outline:none; transition: border-color 0.2s;"></select>');
    var optionsObj = $span.data('options');
    if (typeof optionsObj === 'string') {
      try { optionsObj = JSON.parse(optionsObj); } catch(e) {}
    }
    if (typeof optionsObj === 'object' && optionsObj !== null) {
      $.each(optionsObj, function(val, text) {
        var $opt = $('<option></option>').attr('value', val).text(text);
        if (val == rawValue) $opt.prop('selected', true);
        $input.append($opt);
      });
    }
  } else {
    $input = $('<input type="' + type + '" class="dtc-extrafield-input" style="width:100%; outline:none; transition: border-color 0.2s;" autocomplete="off">');
  }
  
  if (type !== 'select') {
    $input.val(rawValue);
  }
  $span.hide().after($input);
  $input.trigger('focus');

  var saveFunc = function() {
    var newValue = $input.val();
    if (newValue === rawValue) {
      $input.remove();
      $span.show();
      $span.data('editing', false);
      return;
    }
    
    var field = $span.data('field');
    var token = window.saturne.toolbox.getToken();
    var sep = window.saturne.toolbox.getQuerySeparator(document.URL);
    
    $input.prop('disabled', true);
    // Visual feedback: green border during save
    $input.css({'border-color': '#10b981', 'box-shadow': '0 0 0 1px #10b981'});
    
    $.ajax({
      url: document.URL + sep + 'action=setextrafield_ajax&token=' + token,
      type: 'POST',
      data: { field: field, value: newValue },
      dataType: 'json',
      success: function(resp) {
        $input.remove();
        $span.data('raw', newValue);
        
        var displayValue = newValue;
        if (type === 'select') {
          displayValue = $input.find('option[value="' + newValue + '"]').text() || newValue;
        }

        if (type === 'textarea') {
          $span.html(displayValue.replace(/\n/g, '<br>'));
        } else {
          $span.text(displayValue);
        }
        
        if (newValue === '') {
          var placeholder = $span.data('placeholder') || 'Non défini';
          $span.html(placeholder).addClass('is-empty');
        } else {
          $span.removeClass('is-empty');
        }
        $span.show();
        $span.data('editing', false);

        // Flash green on the span after saving
        $span.css({'border-bottom-color': '#10b981', 'color': '#10b981'});
        setTimeout(function() {
            $span.css({'border-bottom-color': '', 'color': ''});
        }, 1200);
      },
      error: function() {
        $input.remove();
        $span.show();
        $span.data('editing', false);
        $.jnotify('Erreur lors de la sauvegarde', 'error');
      }
    });
  };

  if (type === 'select') {
    $input.on('change blur', saveFunc);
  } else {
    $input.on('blur', saveFunc);
    if (type !== 'textarea') {
      $input.on('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          $input.trigger('blur');
        }
      });
    }
  }
};

// Direct select handler
$(document).on('change', '.dtc-direct-select', function() {
  var $select = $(this);
  var field = $select.data('field');
  var newValue = $select.val();
  var token = window.saturne.toolbox.getToken();
  var sep = window.saturne.toolbox.getQuerySeparator(document.URL);
  
  $select.prop('disabled', true);
  $select.css({'border-color': '#10b981', 'box-shadow': '0 0 0 1px #10b981'});
  
  $.ajax({
    url: document.URL + sep + 'action=setextrafield_ajax&token=' + token,
    type: 'POST',
    data: { field: field, value: newValue },
    dataType: 'json',
    success: function() {
      $select.prop('disabled', false);
      $select.css({'border-color': '#10b981', 'box-shadow': ''});
      setTimeout(function() {
          $select.css({'border-color': '', 'box-shadow': ''});
      }, 1200);
    },
    error: function() {
      $select.prop('disabled', false);
      $select.css({'border-color': '', 'box-shadow': ''});
      $.jnotify('Erreur lors de la sauvegarde', 'error');
    }
  });
});

