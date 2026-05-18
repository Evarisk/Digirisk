/**
 * 1-click ticket action card — issue #4443.
 * Wires the action tiles to the AJAX endpoint and applies optimistic UI feedback.
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.digiriskdolibarr.ticketActionCard = {};

/**
 * Auto-invoked by digiriskdolibarr.js on document.ready.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.init = function() {
    window.digiriskdolibarr.ticketActionCard.event();
};

/**
 * Bind all delegated events for the tile grid.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.event = function() {
    $(document).on('click', '.ticket-action-tile:not(.ticket-action-tile--disabled)', window.digiriskdolibarr.ticketActionCard.onTileClick);
};

/**
 * Tile click handler. Reads the action from data-action and dispatches.
 *
 * Special-case actions that need a UI step before the AJAX call:
 *   - assign_other → open the user picker overlay
 *   - link_accident → redirect to the accident creation form pre-filled with fk_ticket
 *   - open_full_card → redirect to the standard Dolibarr ticket card
 *   - add_message → redirect to the message tab
 *
 * Confirm-required actions (data-confirm attribute) ask once before dispatching.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onTileClick = function(event) {
    event.preventDefault();

    var $tile     = $(this);
    var action    = $tile.data('action');
    var $card     = $tile.closest('.ticket-action-card');
    var ticketId  = $card.data('ticket-id');
    var ajaxUrl   = $card.data('ajax-url');
    var confirmK  = $tile.data('confirm');

    if (!ticketId || !ajaxUrl || !action) {
        return;
    }

    // Special-cases — redirect-style actions, no AJAX.
    if (action === 'open_full_card') {
        window.location.href = '../../../../ticket/card.php?id=' + encodeURIComponent(ticketId);
        return;
    }
    if (action === 'add_message') {
        window.location.href = '../../../../ticket/messaging.php?action=presend&mode=init&id=' + encodeURIComponent(ticketId);
        return;
    }
    if (action === 'link_accident') {
        window.location.href = '../accident/accident_card.php?action=create&fk_ticket=' + encodeURIComponent(ticketId);
        return;
    }
    if (action === 'assign_other') {
        window.location.href = '../../../../ticket/card.php?action=assign_ticket&track_id=&id=' + encodeURIComponent(ticketId) + '&set=assign_ticket';
        return;
    }

    // Two-step confirm for destructive actions (close / cancel).
    if (confirmK && !$tile.hasClass('ticket-action-tile--armed')) {
        window.digiriskdolibarr.ticketActionCard.armTile($tile);
        return;
    }
    if ($tile.hasClass('ticket-action-tile--armed')) {
        $tile.removeClass('ticket-action-tile--armed');
    }

    // Standard AJAX action.
    window.digiriskdolibarr.ticketActionCard.dispatch($card, $tile, ajaxUrl, ticketId, action);
};

/**
 * Visually "arm" a destructive tile and revert after 4s if not confirmed.
 *
 * @param  {jQuery} $tile
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.armTile = function($tile) {
    $tile.addClass('ticket-action-tile--armed');
    var originalLabel = $tile.find('.ticket-action-tile__label').text();
    $tile.data('original-label', originalLabel);
    $tile.find('.ticket-action-tile__label').text($tile.data('confirm-label') || 'Confirmer ?');

    setTimeout(function() {
        if ($tile.hasClass('ticket-action-tile--armed')) {
            $tile.removeClass('ticket-action-tile--armed');
            $tile.find('.ticket-action-tile__label').text($tile.data('original-label'));
        }
    }, 4000);
};

/**
 * Dispatch the AJAX call to the action card endpoint and apply UI feedback.
 *
 * @param  {jQuery} $card
 * @param  {jQuery} $tile
 * @param  {string} ajaxUrl
 * @param  {number} ticketId
 * @param  {string} action
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.dispatch = function($card, $tile, ajaxUrl, ticketId, action) {
    $tile.addClass('ticket-action-tile--loading');
    $card.find('.ticket-action-card__toast').removeClass('is-error is-success').text('');

    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: ticketId,
            action: action,
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        $tile.removeClass('ticket-action-tile--loading');
        if (response && response.success) {
            window.digiriskdolibarr.ticketActionCard.applyState($card, response);
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || '', 'success');
        } else {
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        $tile.removeClass('ticket-action-tile--loading');
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
    });
};

/**
 * Re-paint the header chip + tile disabled states from the AJAX payload.
 *
 * @param  {jQuery} $card
 * @param  {Object} response
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.applyState = function($card, response) {
    if (!response.ticket) {
        return;
    }
    var $statusChip = $card.find('.ticket-action-card__status');
    if (response.ticket.status_html) {
        $statusChip.html(response.ticket.status_html);
    }
    $statusChip.attr('data-status', response.ticket.status);

    // Reload after a brief delay to refresh tile-disabled states based on the new status.
    setTimeout(function() {
        window.location.reload();
    }, 700);
};

/**
 * Show a transient toast in the card.
 *
 * @param  {jQuery} $card
 * @param  {string} message
 * @param  {string} kind     'success' or 'error'
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.flash = function($card, message, kind) {
    var $toast = $card.find('.ticket-action-card__toast');
    $toast.removeClass('is-error is-success').addClass('is-' + kind).text(message);
};
