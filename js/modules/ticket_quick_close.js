/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/ticket_quick_close.js
 * \ingroup digiriskdolibarr
 * \brief   Quick close modal of the tickets (core/tpl/ticket/ticket_quick_close_modal.tpl.php), the way the to-do
 *          board of ReedCRM closes an event: at 100 % the ticket is closed, below only its progress changes. Opened
 *          from the percentage of a card of the ticket kanban (js/modules/ticket_action_card.js), and from the state
 *          of a row of the list of the registers.
 */

'use strict';

window.digiriskdolibarr.ticketQuickClose = {};

/**
 * Whether the last press inside the modal started on its backdrop.
 *
 * @type {boolean}
 */
window.digiriskdolibarr.ticketQuickClose.pressedOnBackdrop = false;

/**
 * Init the quick close of the tickets, where the page renders its modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.init = function() {
    if (!$('#tacQuickCloseModal').length) {
        return;
    }

    window.digiriskdolibarr.ticketQuickClose.event();
    window.digiriskdolibarr.ticketQuickClose.initList();
};

/**
 * Bind the controls of the modal and the triggers of the list.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.event = function() {
    $(document).on('click', '#tacQuickCloseModal .modal-close, #tacQuickCloseModal .tac-quick-close-cancel', window.digiriskdolibarr.ticketQuickClose.close);

    // The backdrop closes the modal only when the press started on it too: a text selected in the comment and
    // released outside the frame also ends with a click on the backdrop
    $(document).on('mousedown', '#tacQuickCloseModal', function(e) {
        window.digiriskdolibarr.ticketQuickClose.pressedOnBackdrop = $(e.target).is('#tacQuickCloseModal');
    });

    $(document).on('click', '#tacQuickCloseModal', function(e) {
        if ($(e.target).is('#tacQuickCloseModal') && window.digiriskdolibarr.ticketQuickClose.pressedOnBackdrop) {
            window.digiriskdolibarr.ticketQuickClose.close();
        }
        window.digiriskdolibarr.ticketQuickClose.pressedOnBackdrop = false;
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#tacQuickCloseModal').hasClass('modal-active')) {
            window.digiriskdolibarr.ticketQuickClose.close();
        }
    });

    // The slider and the exact figure move together, and the modal tells whether it closes the ticket
    $(document).on('input', '#tacQuickCloseProgressRange', function() {
        window.digiriskdolibarr.ticketQuickClose.setProgress($(this).val());
    });

    // A figure being typed is left alone, only the slider and the wording follow it
    $(document).on('input', '#tacQuickCloseProgress', function() {
        window.digiriskdolibarr.ticketQuickClose.setProgress($(this).val(), true);
    });

    $(document).on('change', '#tacQuickCloseProgress', function() {
        window.digiriskdolibarr.ticketQuickClose.setProgress($(this).val());
    });

    // Enter in the figure saves, as the button does
    $(document).on('keydown', '#tacQuickCloseProgress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            window.digiriskdolibarr.ticketQuickClose.save();
        }
    });

    $(document).on('click', '#tacQuickCloseModal .tac-quick-close-confirm', window.digiriskdolibarr.ticketQuickClose.save);

    // List of the registers: the trigger added after the state of an open ticket
    $(document).on('click', '.ticket-list-quick-close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.digiriskdolibarr.ticketQuickClose.openFromRow($(this).closest('tr[data-rowid]'));
    });

    $(document).on('keydown', '.ticket-list-quick-close', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            window.digiriskdolibarr.ticketQuickClose.openFromRow($(this).closest('tr[data-rowid]'));
        }
    });

    $(document).on('ticketQuickClosed', window.digiriskdolibarr.ticketQuickClose.applyToList);
};

/**
 * Open the modal for a ticket.
 *
 * @param  {Object} ticket          Ticket to close
 * @param  {number} ticket.id       Row ID
 * @param  {string} ticket.ref      Reference
 * @param  {string} ticket.subject  Subject
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.open = function(ticket) {
    var $modal = $('#tacQuickCloseModal');

    $modal.attr('data-ticket-id', ticket.id);
    $modal.find('.tac-quick-close__ref').text(ticket.ref || '');
    $modal.find('.tac-quick-close__subject').text(ticket.subject || '');
    $modal.find('.tac-quick-close__error').removeClass('visible').text('');
    $('#tacQuickCloseComment').val('');

    // The modal is opened to close the ticket, a lower progress is a deliberate change
    window.digiriskdolibarr.ticketQuickClose.setProgress(100);

    $modal.addClass('modal-active');
    $('#tacQuickCloseComment').trigger('focus');
};

/**
 * Close the modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.close = function() {
    $('#tacQuickCloseModal').removeClass('modal-active');
};

/**
 * Set the progress the ticket is left at. 100 % closes it, below only its progress changes: the title and the
 * confirm button say which of the two is about to happen.
 *
 * @param  {number|string} value      Percentage picked
 * @param  {boolean}       keepTyping Leave the figure being typed untouched
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.setProgress = function(value, keepTyping) {
    var $modal   = $('#tacQuickCloseModal');
    var progress = window.digiriskdolibarr.ticketQuickClose.getProgress(value);
    var closing  = progress === 100;

    $('#tacQuickCloseProgressRange').val(progress);
    if (!keepTyping) {
        $('#tacQuickCloseProgress').val(progress);
    }

    $modal.find('.modal-title').text($modal.attr(closing ? 'data-trans-title-close' : 'data-trans-title-progress'));
    $modal.find('.tac-quick-close__confirm-label').text($modal.attr(closing ? 'data-trans-confirm-close' : 'data-trans-confirm-progress'));
};

/**
 * Read a percentage as a whole number between 0 and 100, an empty or unreadable one meaning a closing.
 *
 * @param  {number|string} value Percentage to read, the field of the modal when omitted
 * @return {number}              Percentage
 */
window.digiriskdolibarr.ticketQuickClose.getProgress = function(value) {
    var progress = parseInt(value === undefined ? $('#tacQuickCloseProgress').val() : value, 10);

    return isNaN(progress) ? 100 : Math.max(0, Math.min(100, progress));
};

/**
 * AJAX — close the ticket of the modal, or save its progress. The page shows the result on the ticketQuickClosed
 * event of the document, with the ticket ID and the answer of the action.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.save = function() {
    var $modal   = $('#tacQuickCloseModal');
    var $confirm = $modal.find('.tac-quick-close-confirm');
    var $error   = $modal.find('.tac-quick-close__error');
    var ticketId = parseInt($modal.attr('data-ticket-id'), 10);
    var url      = $modal.attr('data-url');
    var token    = window.saturne.toolbox.getToken();

    if (!ticketId || $confirm.prop('disabled')) {
        return;
    }

    $confirm.prop('disabled', true);
    $error.removeClass('visible').text('');

    $.ajax({
        url:      url + window.saturne.toolbox.getQuerySeparator(url) + 'action=quickCloseTicket&ticket_id=' + ticketId + '&token=' + token,
        type:     'POST',
        data:     {
            progress: window.digiriskdolibarr.ticketQuickClose.getProgress(),
            comment:  $('#tacQuickCloseComment').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.digiriskdolibarr.ticketQuickClose.close();
                $(document).trigger('ticketQuickClosed', [ticketId, response]);
            } else {
                $error.text(response.error || $modal.attr('data-trans-error')).addClass('visible');
            }
        },
        error: function(xhr) {
            $error.text((xhr.responseJSON && xhr.responseJSON.error) || $modal.attr('data-trans-error')).addClass('visible');
        },
        complete: function() {
            $confirm.prop('disabled', false);
        }
    });
};

/**
 * Index of a column of the list of the registers, found by the sort link of its title.
 *
 * @param  {string} field Sort field of the column, as t.fk_statut
 * @return {number}       Index of its cells in the rows, -1 when the column is hidden
 */
window.digiriskdolibarr.ticketQuickClose.getListColumnIndex = function(field) {
    var $title = $('.digirisk-ticket-list tr.liste_titre').first().children('th, td').filter(function() {
        return $(this).find('a[href*="sortfield=' + field + '&"], a[href$="sortfield=' + field + '"]').length > 0;
    }).first();

    return $title.length ? $title.index() : -1;
};

/**
 * List of the registers: add the trigger after the state of each open ticket the modal lists.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.initList = function() {
    var $modal  = $('#tacQuickCloseModal');
    var tickets = $modal.data('list-tickets');
    var index   = window.digiriskdolibarr.ticketQuickClose.getListColumnIndex('t.fk_statut');

    if (!tickets || index < 0) {
        return;
    }

    $('.digirisk-ticket-list tr[data-rowid]').each(function() {
        var ticket = tickets[$(this).attr('data-rowid')];
        if (!ticket) {
            return;
        }

        var $trigger = $('<span class="ticket-list-quick-close" role="button" tabindex="0"><i class="fas fa-check-circle"></i></span>');
        $trigger.attr('title', $modal.attr('data-trans-tooltip'));
        $(this).children().eq(index).addClass('ticket-list-quick-close-cell').append($trigger);
    });
};

/**
 * List of the registers: open the modal for the ticket of a row.
 *
 * @param  {jQuery} $row Row of the ticket
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.openFromRow = function($row) {
    var tickets = $('#tacQuickCloseModal').data('list-tickets') || {};
    var ticket  = tickets[$row.attr('data-rowid')];

    if (ticket) {
        window.digiriskdolibarr.ticketQuickClose.open({id: $row.attr('data-rowid'), ref: ticket.ref, subject: ticket.subject});
    }
};

/**
 * List of the registers: show a quick close on the row of the ticket, new state and progress, and no more trigger
 * once closed.
 *
 * @param  {Event}  e        ticketQuickClosed event
 * @param  {number} ticketId Ticket row ID
 * @param  {Object} response Answer of the quickCloseTicket action: progress, status, status_html, closed
 * @return {void}
 */
window.digiriskdolibarr.ticketQuickClose.applyToList = function(e, ticketId, response) {
    var $row = $('.digirisk-ticket-list tr[data-rowid="' + ticketId + '"]');
    if (!$row.length) {
        return;
    }

    var statusIndex   = window.digiriskdolibarr.ticketQuickClose.getListColumnIndex('t.fk_statut');
    var progressIndex = window.digiriskdolibarr.ticketQuickClose.getListColumnIndex('t.progress');

    if (statusIndex >= 0 && response.status_html) {
        var $statusCell = $row.children().eq(statusIndex);
        $statusCell.find('.badge').first().replaceWith(response.status_html);
        if (response.closed) {
            $statusCell.find('.ticket-list-quick-close').remove();
        }
    }
    if (progressIndex >= 0) {
        $row.children().eq(progressIndex).text(response.progress);
    }

    $row.addClass('ticket-list-quick-close-saved');
    setTimeout(function() { $row.removeClass('ticket-list-quick-close-saved'); }, 2000);
};
