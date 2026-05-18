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
    window.digiriskdolibarr.ticketActionCard.applyLayout();
};

/**
 * Apply the saved user layout to the DOM on initial render.
 * Reads data-layout JSON from .tac-card, sorts sections per saved order,
 * hides invisible ones, applies width classes.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.applyLayout = function() {
    var $card = $('.tac-card').first();
    if (!$card.length) {
        return;
    }
    var rawLayout = $card.attr('data-layout');
    if (!rawLayout) {
        return;
    }
    var layout;
    try { layout = JSON.parse(rawLayout); } catch (e) { return; }
    if (!layout || !layout.sections) {
        return;
    }

    // Group sections by destination column and sort by order.
    var byCol = { left: [], right: [] };
    $card.find('.tac-section[data-section-id]').each(function() {
        var $sec = $(this);
        var id = $sec.attr('data-section-id');
        var cfg = layout.sections[id];
        if (!cfg) {
            return; // unknown section — leave where the TPL put it
        }
        var col = (cfg.column === 'right') ? 'right' : 'left';
        $sec.attr('data-col', col);
        $sec.attr('data-order', cfg.order || 0);
        $sec.attr('data-width', cfg.width || 'full');
        $sec.toggleClass('tac-section--hidden', cfg.visible === false);
        $sec.removeClass('tac-section--width-half tac-section--width-full tac-section--width-span');
        $sec.addClass('tac-section--width-' + (cfg.width || 'full'));
        byCol[col].push({ $el: $sec, order: cfg.order || 0 });
    });

    var $leftCol  = $card.find('.tac-col--left').first();
    var $rightCol = $card.find('.tac-col--right').first();
    ['left', 'right'].forEach(function(col) {
        byCol[col].sort(function(a, b) { return a.order - b.order; });
        var $target = (col === 'left') ? $leftCol : $rightCol;
        byCol[col].forEach(function(item) { $target.append(item.$el); });
    });
};

/**
 * Serialize the current DOM state into a layout object ready for the AJAX save.
 *
 * @return {Object}
 */
window.digiriskdolibarr.ticketActionCard.serializeLayout = function() {
    var layout = { v: 1, sections: {} };
    $('.tac-card .tac-col--left .tac-section[data-section-id]').each(function(idx) {
        var $s = $(this);
        layout.sections[$s.attr('data-section-id')] = {
            visible: !$s.hasClass('tac-section--hidden'),
            width:   $s.attr('data-width') || 'full',
            column:  'left',
            order:   idx,
        };
    });
    $('.tac-card .tac-col--right .tac-section[data-section-id]').each(function(idx) {
        var $s = $(this);
        layout.sections[$s.attr('data-section-id')] = {
            visible: !$s.hasClass('tac-section--hidden'),
            width:   $s.attr('data-width') || 'full',
            column:  'right',
            order:   idx,
        };
    });
    return layout;
};

/**
 * Persist the layout to the server.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.saveLayout = function() {
    var $card    = $('.tac-card').first();
    var ajaxUrl  = $card.data('ajax-url');
    var ticketId = $card.data('ticket-id');
    var layout   = window.digiriskdolibarr.ticketActionCard.serializeLayout();

    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: ticketId,
            action: 'save_layout',
            layout: JSON.stringify(layout),
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        if (response && response.success) {
            // Refresh the data-layout attribute so reloads / subsequent serialize() start fresh.
            $card.attr('data-layout', JSON.stringify({ v: 1, sections: layout.sections }));
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || 'Saved', 'success');
        } else {
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        window.digiriskdolibarr.ticketActionCard.flash($('.tac-card').first(), 'Erreur réseau', 'error');
    });
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

    // ---- Layout customization (edit mode toggle, drag/resize/hide).
    $(document).on('click', '[data-customize-toggle]',     window.digiriskdolibarr.ticketActionCard.onCustomizeToggle);
    $(document).on('click', '.tac-section__hide-btn',      window.digiriskdolibarr.ticketActionCard.onHideSection);
    $(document).on('click', '.tac-section__show-btn',      window.digiriskdolibarr.ticketActionCard.onShowSection);
    $(document).on('click', '.tac-section__width-btn',     window.digiriskdolibarr.ticketActionCard.onWidthChange);
};

/**
 * Toggle the layout edit mode.
 * Turn on: activate jQuery UI sortable on both columns, show controls, dashed borders.
 * Turn off: destroy sortable, save layout, hide controls.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onCustomizeToggle = function() {
    var $card  = $('.tac-card').first();
    var $body  = $('body');
    var $btn   = $('[data-customize-toggle]');
    var $label = $btn.find('.tac-hero__customize-label');
    var isOn   = $body.hasClass('tac-edit-mode');

    if (!isOn) {
        // Entering edit mode.
        $body.addClass('tac-edit-mode');
        $label.text($label.data('edit-on-label') || 'Terminé');
        $btn.addClass('tac-hero__customize--active');

        // Activate jQuery UI sortable on both columns. The :ui-sortable check avoids re-init.
        if ($.fn.sortable) {
            ['.tac-col--left', '.tac-col--right'].forEach(function(sel) {
                var $col = $(sel);
                if ($col.length && !$col.data('uiSortable')) {
                    $col.sortable({
                        connectWith: '.tac-col--left, .tac-col--right',
                        handle:      '.tac-section__drag',
                        placeholder: 'tac-section-placeholder',
                        tolerance:   'pointer',
                        opacity:     0.8,
                        stop: function() {
                            // After a drop, persist the new order/column.
                            window.digiriskdolibarr.ticketActionCard.saveLayout();
                        }
                    });
                }
            });
        }
    } else {
        // Exiting edit mode.
        $body.removeClass('tac-edit-mode');
        $label.text($label.data('edit-off-label') || 'Personnaliser');
        $btn.removeClass('tac-hero__customize--active');
        if ($.fn.sortable) {
            ['.tac-col--left', '.tac-col--right'].forEach(function(sel) {
                var $col = $(sel);
                if ($col.data('uiSortable')) {
                    $col.sortable('destroy');
                }
            });
        }
        // Final save on exit, just in case.
        window.digiriskdolibarr.ticketActionCard.saveLayout();
    }
};

/**
 * Hide a section (edit mode).
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onHideSection = function(event) {
    event.stopPropagation();
    var $sec = $(this).closest('.tac-section');
    $sec.addClass('tac-section--hidden');
    window.digiriskdolibarr.ticketActionCard.saveLayout();
};

/**
 * Show a previously hidden section (edit mode).
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onShowSection = function(event) {
    event.stopPropagation();
    var $sec = $(this).closest('.tac-section');
    $sec.removeClass('tac-section--hidden');
    window.digiriskdolibarr.ticketActionCard.saveLayout();
};

/**
 * Change a section width preset (half / full / span).
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onWidthChange = function(event) {
    event.stopPropagation();
    var $btn   = $(this);
    var width  = $btn.data('width');
    var $sec   = $btn.closest('.tac-section');
    $sec.removeClass('tac-section--width-half tac-section--width-full tac-section--width-span');
    $sec.addClass('tac-section--width-' + width);
    $sec.attr('data-width', width);
    window.digiriskdolibarr.ticketActionCard.saveLayout();
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
