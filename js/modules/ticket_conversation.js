/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/ticket_conversation.js
 * \ingroup digiriskdolibarr
 * \brief   Ticket conversation thread + composer (card ticket_card.php).
 */

'use strict';

window.digiriskdolibarr.ticketConversation = {};

/**
 * Init.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.init = function() {
  window.digiriskdolibarr.ticketConversation.event();
};

/**
 * Event bindings (delegated).
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.event = function() {
  $(document).on('click',   '.dtc-conversation .dtc-composer__tab', window.digiriskdolibarr.ticketConversation.switchMode);
  $(document).on('click',   '.dtc-conversation [data-thread-send]', window.digiriskdolibarr.ticketConversation.send);
  $(document).on('keydown', '.dtc-conversation .dtc-chip-input',    window.digiriskdolibarr.ticketConversation.chipKeydown);
  $(document).on('click',   '.dtc-conversation [data-chip-remove]', window.digiriskdolibarr.ticketConversation.chipRemove);
  $(document).on('click',   '.dtc-conversation .dtc-attach-btn',    window.digiriskdolibarr.ticketConversation.openFilePicker);
  $(document).on('change',  '.dtc-conversation .dtc-file-input',    window.digiriskdolibarr.ticketConversation.onFileInput);
  $(document).on('click',   '.dtc-conversation [data-file-remove]', window.digiriskdolibarr.ticketConversation.removeFile);
  $(document).on('dragover', '.dtc-composer',                       window.digiriskdolibarr.ticketConversation.onDragOver);
  $(document).on('dragleave', '.dtc-composer',                      window.digiriskdolibarr.ticketConversation.onDragLeave);
  $(document).on('drop',    '.dtc-composer',                        window.digiriskdolibarr.ticketConversation.onDrop);
  $(document).on('click',   '.dtc-conversation [data-dtc-edit]',    window.digiriskdolibarr.ticketConversation.editMessage);
  $(document).on('click',   '.dtc-conversation [data-dtc-delete]',  window.digiriskdolibarr.ticketConversation.deleteMessage);
  $(document).on('click',   '.dtc-conversation [data-dtc-quote]',   window.digiriskdolibarr.ticketConversation.quoteMessage);
  $(document).on('click',   '.dtc-conversation .dtc-edit-save',     window.digiriskdolibarr.ticketConversation.saveEdit);
  $(document).on('click',   '.dtc-conversation .dtc-edit-cancel',   window.digiriskdolibarr.ticketConversation.cancelEdit);
  $(document).on('keydown', '.dtc-conversation',                    window.digiriskdolibarr.ticketConversation.ctrlEnter);
};

/**
 * Inline-edit a message (author-only): swap the body for a textarea.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.editMessage = function() {
  var $body = $(this).closest('.dtc-msg').find('.dtc-msg__body');
  if ($body.find('textarea').length) {
    return;
  }
  var current = $body.text().trim();
  $body.data('orig', $body.html());
  $body.html('<textarea class="dtc-edit-area"></textarea><div class="dtc-edit-actions"><button type="button" class="dtc-edit-save"><i class="fas fa-check"></i></button><button type="button" class="dtc-edit-cancel"><i class="fas fa-times"></i></button></div>');
  $body.find('textarea').val(current).trigger('focus');
};

/**
 * Save an inline message edit.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.saveEdit = function() {
  var $card = $('.dtc-conversation');
  var $msg  = $(this).closest('.dtc-msg');
  var $body = $msg.find('.dtc-msg__body');
  var val   = $body.find('textarea').val();
  $.ajax({
    url: $card.data('url') + '?action=edit_message_ajax&id=' + $card.data('ticket-id') + '&token=' + window.saturne.toolbox.getToken(),
    type: 'POST',
    dataType: 'json',
    data: { message_id: $msg.data('msg-id'), body: val },
    success: function(resp) {
      if (resp && resp.success) {
        $body.html(resp.body_html);
      } else {
        $body.html($body.data('orig'));
        $.jnotify((resp && resp.message) || 'Error', 'error');
      }
    },
    error: function() {
      $body.html($body.data('orig'));
    }
  });
};

/**
 * Cancel an inline message edit.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.cancelEdit = function() {
  var $body = $(this).closest('.dtc-msg__body');
  $body.html($body.data('orig'));
};

/**
 * Delete a message (author-only, confirmed).
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.deleteMessage = function() {
  var $card = $('.dtc-conversation');
  var $msg  = $(this).closest('.dtc-msg');
  if (!window.confirm($card.data('lang-confirm-delete') || 'Supprimer ce message ?')) {
    return;
  }
  $.ajax({
    url: $card.data('url') + '?action=delete_message_ajax&id=' + $card.data('ticket-id') + '&token=' + window.saturne.toolbox.getToken(),
    type: 'POST',
    dataType: 'json',
    data: { message_id: $msg.data('msg-id') },
    success: function(resp) {
      if (resp && resp.success) {
        $msg.slideUp(150, function() { $(this).remove(); });
      } else {
        $.jnotify((resp && resp.message) || 'Error', 'error');
      }
    }
  });
};

/**
 * Quote a message into the composer.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.quoteMessage = function() {
  var $msg   = $(this).closest('.dtc-msg');
  var text   = $msg.find('.dtc-msg__body').text().trim();
  var author = $msg.find('.dtc-msg__author').text().trim();
  var safe   = $('<div>').text('« ' + author + ' : ' + text + ' »').html();
  var quoteHtml = '<p><em>' + safe + '</em></p><p><br></p>';
  if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.dtc_body) {
    var ed = CKEDITOR.instances.dtc_body;
    ed.setData(ed.getData() + quoteHtml);
    ed.focus();
  } else {
    var $ta = $('#dtc_body');
    $ta.val('> ' + author + ' : ' + text + '\n' + ($ta.val() || ''));
  }
};

/**
 * Open the hidden file picker.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.openFilePicker = function() {
  $(this).closest('.dtc-composer').find('.dtc-file-input').trigger('click');
};

/**
 * Handle file input selection.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.onFileInput = function() {
  var $form = $(this).closest('.dtc-composer');
  window.digiriskdolibarr.ticketConversation.addFiles($form, this.files);
  $(this).val('');
};

/**
 * Drag helpers.
 *
 * @param  {Object} event Drag event.
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.onDragOver = function(event) {
  event.preventDefault();
  $(this).addClass('dtc-composer--dragover');
};
window.digiriskdolibarr.ticketConversation.onDragLeave = function() {
  $(this).removeClass('dtc-composer--dragover');
};
window.digiriskdolibarr.ticketConversation.onDrop = function(event) {
  event.preventDefault();
  $(this).removeClass('dtc-composer--dragover');
  var dt = event.originalEvent && event.originalEvent.dataTransfer;
  if (dt && dt.files && dt.files.length) {
    window.digiriskdolibarr.ticketConversation.addFiles($(this), dt.files);
  }
};

/**
 * Append files to the composer's pending list.
 *
 * @param  {jQuery}   $form    Composer form.
 * @param  {FileList} fileList Files to add.
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.addFiles = function($form, fileList) {
  var files = $form.data('dtcFiles') || [];
  for (var i = 0; i < fileList.length; i++) {
    files.push(fileList[i]);
  }
  $form.data('dtcFiles', files);
  window.digiriskdolibarr.ticketConversation.renderFiles($form);
};

/**
 * Render the pending file list.
 *
 * @param  {jQuery} $form Composer form.
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.renderFiles = function($form) {
  var files = $form.data('dtcFiles') || [];
  var $list = $form.find('.dtc-file-list');
  $list.empty();
  files.forEach(function(f, idx) {
    $list.append('<span class="dtc-file"><i class="fas fa-paperclip"></i> ' + $('<div>').text(f.name).html() + ' <button type="button" data-file-remove="' + idx + '">&times;</button></span>');
  });
};

/**
 * Remove a pending file.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.removeFile = function() {
  var $form = $(this).closest('.dtc-composer');
  var idx = parseInt($(this).data('file-remove'), 10);
  var files = $form.data('dtcFiles') || [];
  files.splice(idx, 1);
  $form.data('dtcFiles', files);
  window.digiriskdolibarr.ticketConversation.renderFiles($form);
};

/**
 * Add a recipient chip on Enter / comma.
 *
 * @param  {Object} event Keydown event.
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.chipKeydown = function(event) {
  if (event.key === 'Enter' || event.keyCode === 13 || event.key === ',' || event.keyCode === 188) {
    event.preventDefault();
    event.stopPropagation();
    var $input = $(this);
    var val = $.trim(($input.val() || '').replace(/,+$/, ''));
    if (val === '') {
      return;
    }
    var $chips = $input.closest('.dtc-recipients').find('.dtc-chips');
    var safe = $('<div>').text(val).html();
    if ($chips.find('.dtc-chip').filter(function() { return $(this).data('value') === val; }).length === 0) {
      $chips.append('<span class="dtc-chip" data-value="' + safe + '">' + safe + ' <button type="button" data-chip-remove>&times;</button></span>');
    }
    $input.val('');
  }
};

/**
 * Remove a recipient chip.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.chipRemove = function() {
  $(this).closest('.dtc-chip').remove();
};

/**
 * Collect the recipient chip values for a given target (to/cc).
 *
 * @param  {jQuery} $form  Composer form.
 * @param  {string} target 'to' or 'cc'.
 * @return {Array}         Recipient strings.
 */
window.digiriskdolibarr.ticketConversation.collectRecipients = function($form, target) {
  return $form.find('.dtc-recipients[data-target="' + target + '"] .dtc-chip').map(function() {
    return $(this).data('value');
  }).get();
};

/**
 * Toggle the composer between internal note and public message.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.switchMode = function() {
  var $tab  = $(this);
  var $form = $tab.closest('.dtc-composer');
  var mode  = $tab.data('mode');
  $form.attr('data-mode', mode).removeClass('dtc-composer--internal dtc-composer--public').addClass('dtc-composer--' + mode);
  $form.find('.dtc-composer__tab').removeClass('is-active');
  $tab.addClass('is-active');
  $form.find('.dtc-composer__public').toggle(mode === 'public');
  $form.find('.dtc-composer__mentions').toggle(mode === 'internal');
  var label = (mode === 'public') ? ($form.data('lang-send') || 'Send') : ($form.data('lang-savenote') || 'Save');
  $form.find('.dtc-composer__send-label').text(label);
};

/**
 * Read the CKEditor (or fallback textarea) body value.
 *
 * @return {string} HTML body.
 */
window.digiriskdolibarr.ticketConversation.getBody = function() {
  if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.dtc_body) {
    return CKEDITOR.instances.dtc_body.getData();
  }
  return $('#dtc_body').val() || '';
};

/**
 * Clear the composer body.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.clearBody = function() {
  if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.dtc_body) {
    CKEDITOR.instances.dtc_body.setData('');
  }
  $('#dtc_body').val('');
};

/**
 * Post the composed message (AJAX), append the returned bubble.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.send = function() {
  var $card = $('.dtc-conversation');
  var $form = $card.find('.dtc-composer');
  if ($form.length === 0) {
    return;
  }
  var mode = $form.attr('data-mode');
  var body = window.digiriskdolibarr.ticketConversation.getBody();
  if ($.trim($('<div>').html(body).text()) === '') {
    return;
  }
  var data = {
    body: body,
    subject: $form.find('[name="subject"]').val() || '',
    private: (mode === 'public') ? 0 : 1
  };
  if (mode === 'public') {
    data.to = window.digiriskdolibarr.ticketConversation.collectRecipients($form, 'to');
    data.cc = window.digiriskdolibarr.ticketConversation.collectRecipients($form, 'cc');
    data.model_id = $form.find('[name="model_id"]').val() || 0;
  } else {
    data.mentions = $form.find('#dtc_mentions').val() || [];
  }
  var files = $form.data('dtcFiles') || [];
  var $btn  = $form.find('[data-thread-send]');
  var url   = $card.data('url') + '?action=post_message_ajax&id=' + $card.data('ticket-id') + '&token=' + window.saturne.toolbox.getToken();
  var ajaxOpts = {
    url: url,
    type: 'POST',
    dataType: 'json',
    success: function(resp) {
      $btn.prop('disabled', false);
      if (!resp || !resp.success) {
        $.jnotify((resp && resp.message) || 'Error', 'error');
        return;
      }
      $card.find('.dtc-thread__empty').remove();
      $card.find('.dtc-thread').append(resp.bubble);
      window.digiriskdolibarr.ticketConversation.clearBody();
      $form.removeData('dtcFiles');
      window.digiriskdolibarr.ticketConversation.renderFiles($form);
      window.digiriskdolibarr.ticketConversation.scrollBottom();
      $.jnotify(resp.message, 'success');
    },
    error: function() {
      $btn.prop('disabled', false);
      $.jnotify('Error', 'error');
    }
  };
  if (files.length > 0) {
    var fd = new FormData();
    fd.append('body', data.body);
    fd.append('subject', data.subject);
    fd.append('private', data.private);
    (data.to || []).forEach(function(t) { fd.append('to[]', t); });
    (data.cc || []).forEach(function(c) { fd.append('cc[]', c); });
    (data.mentions || []).forEach(function(m) { fd.append('mentions[]', m); });
    fd.append('model_id', data.model_id || 0);
    files.forEach(function(f) { fd.append('files[]', f); });
    ajaxOpts.data = fd;
    ajaxOpts.processData = false;
    ajaxOpts.contentType = false;
  } else {
    ajaxOpts.data = data;
  }
  $btn.prop('disabled', true);
  $.ajax(ajaxOpts);
};

/**
 * Scroll the thread to the bottom (latest message).
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.scrollBottom = function() {
  var el = $('.dtc-conversation .dtc-thread')[0];
  if (el) {
    el.scrollTop = el.scrollHeight;
  }
};

/**
 * Ctrl/Cmd+Enter sends the message.
 *
 * @param  {Object} event Keydown event.
 * @return {void}
 */
window.digiriskdolibarr.ticketConversation.ctrlEnter = function(event) {
  if ((event.ctrlKey || event.metaKey) && (event.key === 'Enter' || event.keyCode === 13)) {
    event.preventDefault();
    window.digiriskdolibarr.ticketConversation.send();
  }
};
