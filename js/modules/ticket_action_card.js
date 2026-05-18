/**
 * Reworked ticket action card — issue #4443.
 * Wires:
 *   1. Sticky-bar action buttons (legacy tile-style) to the AJAX endpoint.
 *   2. Tap-to-edit fields ([data-edit-field]) — click → input/select swap → save on blur/Enter.
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
 * Bind all delegated events.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.event = function() {
    // Sticky-bar / legacy tiles
    $(document).on('click', '.ticket-action-tile:not([disabled])', window.digiriskdolibarr.ticketActionCard.onTileClick);
    // Tap-to-edit — bind on the field WRAPPER so clicks anywhere in it (label/value) work.
    $(document).on('click', '.tac-field:not(.tac-field--readonly), .tac-chip:not(.tac-chip--readonly), .tac-hero__subject', window.digiriskdolibarr.ticketActionCard.onFieldClick);
};

/**
 * Action tile / sticky-bar button click handler.
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

    if (confirmK && !$tile.hasClass('ticket-action-tile--armed')) {
        window.digiriskdolibarr.ticketActionCard.armTile($tile);
        return;
    }
    $tile.removeClass('ticket-action-tile--armed');

    window.digiriskdolibarr.ticketActionCard.dispatchTile($card, $tile, ajaxUrl, ticketId, action);
};

/**
 * Visually arm a destructive tile and revert after 4s if not confirmed.
 *
 * @param  {jQuery} $tile
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.armTile = function($tile) {
    $tile.addClass('ticket-action-tile--armed');
    var originalHtml = $tile.html();
    $tile.data('original-html', originalHtml);
    $tile.html('<i class="fas fa-exclamation-triangle"></i> Confirmer ?');

    setTimeout(function() {
        if ($tile.hasClass('ticket-action-tile--armed')) {
            $tile.removeClass('ticket-action-tile--armed');
            $tile.html($tile.data('original-html'));
        }
    }, 4000);
};

/**
 * Send the tile action to the AJAX endpoint and reload on success.
 *
 * @param  {jQuery} $card
 * @param  {jQuery} $tile
 * @param  {string} ajaxUrl
 * @param  {number} ticketId
 * @param  {string} action
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.dispatchTile = function($card, $tile, ajaxUrl, ticketId, action) {
    $tile.addClass('ticket-action-tile--loading');

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
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || '', 'success');
            setTimeout(function() { window.location.reload(); }, 600);
        } else {
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        $tile.removeClass('ticket-action-tile--loading');
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
    });
};

/**
 * Tap-to-edit click — swap the field value for an editable input.
 *
 * Reads:
 *   data-edit-field   logical field name sent to the backend
 *   data-edit-type    text | longtext | number | date | select
 *   data-edit-value   current raw value
 *   data-edit-options JSON list of {id,label} for select-type fields
 *   data-edit-format  date format hint
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onFieldClick = function(event) {
    var $wrap = $(this);
    if ($wrap.hasClass('tac-editing') || $wrap.hasClass('tac-saving')) {
        return; // already in edit mode
    }
    var type    = $wrap.data('edit-type');
    var field   = $wrap.data('edit-field');
    var current = String($wrap.attr('data-edit-value') == null ? '' : $wrap.attr('data-edit-value'));
    if (!field || !type) {
        return;
    }

    $wrap.addClass('tac-editing');
    var $valueCell = $wrap.is('.tac-field') ? $wrap.find('.tac-field__value') : $wrap;
    var originalHtml = $valueCell.html();
    $wrap.data('original-html', originalHtml);

    var $input;
    if (type === 'text') {
        $input = $('<input type="text" class="tac-edit-input">').val(current);
    } else if (type === 'longtext') {
        $input = $('<textarea class="tac-edit-textarea" rows="4">').val(current);
    } else if (type === 'number') {
        $input = $('<input type="number" min="0" max="100" step="1" class="tac-edit-input tac-edit-input--narrow">').val(current);
    } else if (type === 'date') {
        // Render YYYY-MM-DD for the native picker. Source value may be a timestamp.
        var dateValue = current;
        if (/^\d{9,}$/.test(current)) {
            var d = new Date(parseInt(current, 10) * 1000);
            dateValue = d.toISOString().substring(0, 10);
        }
        $input = $('<input type="date" class="tac-edit-input">').val(dateValue);
    } else if (type === 'select') {
        $input = $('<select class="tac-edit-input">');
        var options = $wrap.data('edit-options') || [];
        options.forEach(function(opt) {
            var $opt = $('<option>').val(String(opt.id)).text(opt.label);
            if (String(opt.id) === current) {
                $opt.prop('selected', true);
            }
            $input.append($opt);
        });
    } else {
        $wrap.removeClass('tac-editing');
        return;
    }

    // Stop click propagation while editing so a subsequent click inside the input doesn't re-trigger.
    $input.on('click', function(e) { e.stopPropagation(); });

    // Render the input — replace the value cell content (or the whole chip for a chip).
    if ($wrap.is('.tac-chip') || $wrap.is('.tac-hero__subject')) {
        $wrap.html('').append($input);
    } else {
        $valueCell.html('').append($input);
    }
    $input.focus();
    if ($input.is('input[type="text"], textarea, input[type="number"]')) {
        $input.select && $input.select();
    }

    // ---- Save handlers
    var commit = function() {
        var newValue = $input.val();
        var oldValue = current;
        if (String(newValue) === String(oldValue)) {
            // Nothing changed — restore display and bail.
            window.digiriskdolibarr.ticketActionCard.restoreField($wrap, originalHtml);
            return;
        }
        window.digiriskdolibarr.ticketActionCard.saveField($wrap, field, newValue, originalHtml);
    };
    var cancel = function() {
        window.digiriskdolibarr.ticketActionCard.restoreField($wrap, originalHtml);
    };

    if (type === 'longtext') {
        // For textareas: Ctrl+Enter saves, Escape cancels, blur saves.
        $input.on('keydown', function(e) {
            if (e.key === 'Escape') { cancel(); }
            else if (e.key === 'Enter' && e.ctrlKey) { commit(); }
        });
    } else if (type === 'select') {
        // Selects: change fires immediately, no need to wait for blur.
        $input.on('change', commit);
        $input.on('keydown', function(e) {
            if (e.key === 'Escape') { cancel(); }
        });
    } else {
        $input.on('keydown', function(e) {
            if (e.key === 'Escape') { cancel(); }
            else if (e.key === 'Enter') { commit(); }
        });
    }
    $input.on('blur', commit);
};

/**
 * Restore the field display (no save).
 *
 * @param  {jQuery} $wrap
 * @param  {string} originalHtml
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.restoreField = function($wrap, originalHtml) {
    $wrap.removeClass('tac-editing');
    if ($wrap.is('.tac-chip') || $wrap.is('.tac-hero__subject')) {
        $wrap.html(originalHtml);
    } else {
        $wrap.find('.tac-field__value').html(originalHtml);
    }
};

/**
 * Send the new field value to the AJAX endpoint, then swap the display.
 *
 * @param  {jQuery} $wrap
 * @param  {string} field
 * @param  {*}      newValue
 * @param  {string} originalHtml  Fallback if the request fails.
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.saveField = function($wrap, field, newValue, originalHtml) {
    var $card    = $wrap.closest('.ticket-action-card');
    var ajaxUrl  = $card.data('ajax-url');
    var ticketId = $card.data('ticket-id');

    $wrap.removeClass('tac-editing').addClass('tac-saving');

    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: ticketId,
            action: 'update_field',
            field: field,
            value: newValue,
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        $wrap.removeClass('tac-saving');
        if (response && response.success) {
            // Apply the server-rendered display (already escaped).
            var displayHtml = response.display || originalHtml;
            if ($wrap.is('.tac-chip') || $wrap.is('.tac-hero__subject')) {
                $wrap.html(displayHtml);
            } else {
                $wrap.find('.tac-field__value').html(displayHtml || '<span class="tac-field__empty">—</span>');
            }
            // Persist the new raw value so subsequent edits start from it.
            $wrap.attr('data-edit-value', String(newValue));
            $wrap.addClass('tac-saved');
            setTimeout(function() { $wrap.removeClass('tac-saved'); }, 1200);
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || 'Saved', 'success');
        } else {
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
            window.digiriskdolibarr.ticketActionCard.restoreField($wrap, originalHtml);
        }
    }).fail(function() {
        $wrap.removeClass('tac-saving');
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
        window.digiriskdolibarr.ticketActionCard.restoreField($wrap, originalHtml);
    });
};

/**
 * Show a transient toast.
 *
 * @param  {jQuery} $card
 * @param  {string} message
 * @param  {string} kind     'success' or 'error'
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.flash = function($card, message, kind) {
    var $toast = $card.find('.tac-toast, .ticket-action-card__toast').first();
    $toast.removeClass('is-error is-success').addClass('is-' + kind).text(message);
    setTimeout(function() { $toast.text(''); $toast.removeClass('is-error is-success'); }, 2500);
};
