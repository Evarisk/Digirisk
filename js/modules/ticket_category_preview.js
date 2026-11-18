/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 */

/**
 * \file    js/modules/ticket_category_preview.js
 * \ingroup digiriskdolibarr
 * \brief   Preview of the public interface on the ticket category configuration (#5711)
 */

'use strict';

/**
 * Init ticket category preview JS
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @type {Object}
 */
window.digiriskdolibarr.ticketCategoryPreview = {};

/**
 * Ticket category preview init
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketCategoryPreview.init = function() {
  if (!$('.ticket-category-preview .ticket-category-preview__screen').length) {
    return;
  }

  window.digiriskdolibarr.ticketCategoryPreview.event();
  window.digiriskdolibarr.ticketCategoryPreview.refresh();
};

/**
 * Redraws the preview whenever a setting of the page changes, saved or not: a checkbox, the external link,
 * the order of the fields dragged in their table, or the text of an editor
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketCategoryPreview.event = function() {
  var preview = window.digiriskdolibarr.ticketCategoryPreview;

  $(document).on('change', '.param-table input', preview.scheduleRefresh);
  $(document).on('input', '#external_link, textarea#validate_text, textarea#success_message', preview.scheduleRefresh);
  $(document).on('sortupdate', '.dragable-container', preview.scheduleRefresh);

  // CKEditor is only there when the rich text editor is enabled. An editor may be ready before this
  // module starts, or after
  if (typeof CKEDITOR !== 'undefined') {
    $.each(CKEDITOR.instances, function(name, editor) {
      preview.watchEditor(editor);
    });
    CKEDITOR.on('instanceReady', function(event) {
      preview.watchEditor(event.editor);
    });
  }
};

/**
 * Redraws the preview on each change of a CKEditor, once per editor
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Object} editor CKEditor instance
 * @return {void}
 */
window.digiriskdolibarr.ticketCategoryPreview.watchEditor = function(editor) {
  if (editor.ticketCategoryPreviewWatched) {
    return;
  }
  editor.ticketCategoryPreviewWatched = true;
  editor.on('change', window.digiriskdolibarr.ticketCategoryPreview.scheduleRefresh);
  window.digiriskdolibarr.ticketCategoryPreview.scheduleRefresh();
};

/**
 * Redraws the preview once the other handlers of the change are done: unchecking "Visible" also
 * unchecks "Required" (ticket.js), whatever order the handlers run in
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketCategoryPreview.scheduleRefresh = function() {
  setTimeout(window.digiriskdolibarr.ticketCategoryPreview.refresh, 0);
};

/**
 * Draws the preview from the settings of the page and from what the categories above this one set
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketCategoryPreview.refresh = function() {
  var preview  = window.digiriskdolibarr.ticketCategoryPreview;
  var $preview = $('.ticket-category-preview');
  if (!$preview.find('.ticket-category-preview__screen').length) {
    return;
  }

  // A category opening an external link never shows the form
  var externalLink = ($('#external_link').val() || '').trim();
  $preview.find('.ticket-category-preview__external').text(($preview.data('external-link-text') || '').replace('%s', externalLink)).prop('hidden', !externalLink);
  $preview.find('.ticket-category-preview__form').toggleClass('ticket-category-preview__form--unreachable', !!externalLink);

  $preview.find('.ticket-category-preview__description').prop('hidden', !($('#show_description').prop('checked') || $preview.data('inherited-description') == 1));

  // The message comes first, then the visible fields in the order of their table
  var $fields = $preview.find('.ticket-category-preview__fields').empty();
  $fields.append(preview.field($fields.data('message-label'), 'textarea', true));
  $('.dragable-container .dragable-item').each(function() {
    var name = $(this).data('name');
    if ($('#' + name + '_visible').prop('checked')) {
      $fields.append(preview.field($(this).data('preview-label'), $(this).data('preview-type'), $('#' + name + '_required').prop('checked')));
    }
  });

  var useSignatory = $('#use_signatory').prop('checked') || $preview.data('inherited-signatory') == 1;
  $preview.find('.ticket-category-preview__signature').prop('hidden', !useSignatory);
  $preview.find('.ticket-category-preview__send-button').toggleClass('ticket-category-preview__send-button--disabled', useSignatory);

  // The deepest text wins: the one of this category when it has one, else the one of a category above
  var validateText = preview.editorValue('validate_text') || $preview.find('.ticket-category-preview__inherited-validate-text').html();
  preview.setHtml($preview.find('.ticket-category-preview__validate-text'), validateText);
  $preview.find('.ticket-category-preview__validate').prop('hidden', !validateText.trim());

  var successMessage = preview.editorValue('success_message') || $preview.find('.ticket-category-preview__inherited-success-message').html();
  preview.setHtml($preview.find('.ticket-category-preview__success'), successMessage);
};

/**
 * Draws one field of the form: its label, a star when it is required, and an empty input of its kind
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {string}  label    Label of the field
 * @param  {string}  type     media, select, date, textarea, checkbox, stars or text
 * @param  {boolean} required Whether the field is required
 * @return {jQuery}           Field
 */
window.digiriskdolibarr.ticketCategoryPreview.field = function(label, type, required) {
  var $field = $('<div class="ticket-category-preview__field"></div>');
  var $label = $('<span class="ticket-category-preview__label"></span>').text(label);
  if (required) {
    $label.append($('<span class="ticket-category-preview__required"></span>').text(' *'));
  }
  $field.append($label);

  var icon = function(picto) {
    return $('<i class="fas"></i>').addClass(picto);
  };

  switch (type) {
    case 'media':
      $field.append($('<span class="ticket-category-preview__media"></span>').append(
        $('<span class="ticket-category-preview__media-button"></span>').append(icon('fa-camera')),
        $('<span class="ticket-category-preview__media-button ticket-category-preview__media-button--dark"></span>').append(icon('fa-images')),
        $('<span class="ticket-category-preview__media-button"></span>').append(icon('fa-microphone'))
      ));
      break;
    case 'stars':
      var $stars = $('<span class="ticket-category-preview__stars"></span>');
      for (var i = 0; i < 5; i++) {
        $stars.append(icon('fa-star'));
      }
      $field.append($stars);
      break;
    case 'checkbox':
      $field.append($('<span class="ticket-category-preview__checkbox"></span>'));
      break;
    default:
      var $input = $('<span class="ticket-category-preview__input"></span>').addClass('ticket-category-preview__input--' + type);
      if (type === 'select') {
        $input.append(icon('fa-chevron-down'));
      } else if (type === 'date') {
        $input.append(icon('fa-calendar-alt'));
      }
      $field.append($input);
  }

  return $field;
};

/**
 * Current text of an editor of the page, typed and not saved yet
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {string} name Name of the editor
 * @return {string}      HTML of the text
 */
window.digiriskdolibarr.ticketCategoryPreview.editorValue = function(name) {
  if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[name]) {
    return CKEDITOR.instances[name].getData();
  }

  return $('textarea#' + name).val() || '';
};

/**
 * Shows a text of the configuration as the public interface does, as HTML, without anything that would run:
 * scripts, frames, event attributes and javascript: links are dropped
 *
 * @memberof DigiriskDolibarr_TicketCategoryPreview
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {jQuery} $target Element to fill
 * @param  {string} html    Text
 * @return {void}
 */
window.digiriskdolibarr.ticketCategoryPreview.setHtml = function($target, html) {
  var parsed = new DOMParser().parseFromString('<div>' + (html || '') + '</div>', 'text/html');
  $(parsed).find('script, style, iframe, frame, object, embed, link, meta, base, form').remove();
  $(parsed).find('*').each(function() {
    for (var i = this.attributes.length - 1; i >= 0; i--) {
      var attributeName  = this.attributes[i].name.toLowerCase();
      var attributeValue = this.attributes[i].value.replace(/[\s\u0000-\u001f]/g, '').toLowerCase();
      if (attributeName.indexOf('on') === 0 || (['href', 'src', 'xlink:href', 'action', 'formaction'].indexOf(attributeName) !== -1 && attributeValue.indexOf('javascript:') === 0)) {
        this.removeAttribute(this.attributes[i].name);
      }
    }
  });

  $target.empty().append($(parsed.body.firstChild).contents());
};
