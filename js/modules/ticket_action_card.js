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

    // Flat: collect every section, sort by 'order', append in order to the single .tac-body grid.
    var $body = $card.find('.tac-body').first();
    var bag = [];
    $card.find('.tac-section[data-section-id]').each(function() {
        var $sec = $(this);
        var id = $sec.attr('data-section-id');
        var cfg = layout.sections[id];
        if (!cfg) {
            return; // unknown section — leave where the TPL put it
        }
        var width = cfg.width || 'full';
        $sec.attr('data-order', cfg.order || 0);
        $sec.attr('data-width', width);
        $sec.toggleClass('tac-section--hidden', cfg.visible === false);
        $sec.removeClass('tac-section--width-half tac-section--width-full tac-section--width-span');
        $sec.addClass('tac-section--width-' + width);
        bag.push({ $el: $sec, order: cfg.order || 0 });
    });
    bag.sort(function(a, b) { return a.order - b.order; });
    bag.forEach(function(item) { $body.append(item.$el); });
};

/**
 * Serialize the current DOM state into a layout object ready for the AJAX save.
 *
 * @return {Object}
 */
window.digiriskdolibarr.ticketActionCard.serializeLayout = function() {
    var $card = $('.tac-card').first();
    var layout = {
        v: 2,
        density:     $card.attr('data-density')      || 'cozy',
        tagsMode:    $card.attr('data-tags-mode')    || 'chips',
        actionsMode: $card.attr('data-actions-mode') || 'bar',
        sections: {},
    };
    $card.find('.tac-body .tac-section[data-section-id]').each(function(idx) {
        var $s = $(this);
        layout.sections[$s.attr('data-section-id')] = {
            visible: !$s.hasClass('tac-section--hidden'),
            width:   $s.attr('data-width') || 'full',
            order:   idx,
        };
    });
    return layout;
};

/**
 * Density button click — switch the card's density class and persist.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onDensityChange = function(event) {
    event.stopPropagation();
    var $btn   = $(this);
    var newDen = $btn.data('density');
    if (!newDen) {
        return;
    }
    var $card = $('.tac-card').first();
    $card.removeClass('tac-density-compact tac-density-cozy tac-density-spacious');
    $card.addClass('tac-density-' + newDen);
    $card.attr('data-density', newDen);
    $btn.closest('.tac-hero__density').find('.tac-hero__density-btn').removeClass('is-active');
    $btn.addClass('is-active');
    window.digiriskdolibarr.ticketActionCard.saveLayout();
};

/**
 * Tags mode switch (chips ↔ selector). Persists the choice, then reloads so the
 * Classification section is re-rendered server-side with the right widget.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onTagsModeChange = function(event) {
    event.stopPropagation();
    var $btn  = $(this);
    var mode  = $btn.data('tagsmode');
    if (!mode) {
        return;
    }
    var $card = $('.tac-card').first();
    if (($card.attr('data-tags-mode') || 'chips') === mode) {
        return; // already in this mode
    }
    $card.attr('data-tags-mode', mode);
    $btn.closest('.tac-hero__tagsmode').find('.tac-hero__tagsmode-btn').removeClass('is-active');
    $btn.addClass('is-active');

    // Save then reload — TPL renders chips vs selector at PHP level, so a soft reload is needed.
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
    }).done(function() { window.location.reload(); });
};

/**
 * Selector-mode tag add — fires on <select> change.
 *
 * @param  {Event} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onTagAddSelect = function(event) {
    var $sel = $(this);
    var catId = parseInt($sel.val(), 10);
    if (!catId) {
        return;
    }
    window.digiriskdolibarr.ticketActionCard.tagAjax('add_category', catId, $sel);
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

    // ---- Layout customization (edit mode toggle, drag/resize/hide/reset/density).
    $(document).on('click', '[data-customize-toggle]',     window.digiriskdolibarr.ticketActionCard.onCustomizeToggle);
    $(document).on('click', '[data-layout-reset]',         window.digiriskdolibarr.ticketActionCard.onLayoutReset);
    $(document).on('click', '.tac-section__hide-btn',      window.digiriskdolibarr.ticketActionCard.onHideSection);
    $(document).on('click', '.tac-section__show-btn',      window.digiriskdolibarr.ticketActionCard.onShowSection);
    $(document).on('click', '.tac-section__width-btn',     window.digiriskdolibarr.ticketActionCard.onWidthChange);
    $(document).on('click', '.tac-hero__density-btn',      window.digiriskdolibarr.ticketActionCard.onDensityChange);
    $(document).on('click', '.tac-hero__tagsmode-btn',     window.digiriskdolibarr.ticketActionCard.onTagsModeChange);
    $(document).on('click', '.tac-hero__actionsmode-btn',  window.digiriskdolibarr.ticketActionCard.onActionsModeChange);

    // ---- Classification tags 1-click add/remove.
    $(document).on('click',  '[data-tag-add]',             window.digiriskdolibarr.ticketActionCard.onTagAdd);
    $(document).on('click',  '[data-tag-remove]',          window.digiriskdolibarr.ticketActionCard.onTagRemove);
    $(document).on('change', '[data-tag-add-select]',      window.digiriskdolibarr.ticketActionCard.onTagAddSelect);

    // ---- Linked files preview + delete.
    $(document).on('click', '[data-file-preview]',         window.digiriskdolibarr.ticketActionCard.onFilePreview);
    $(document).on('click', '[data-file-delete]',          window.digiriskdolibarr.ticketActionCard.onFileDelete);
    $(document).on('click', '[data-lightbox-close]',       window.digiriskdolibarr.ticketActionCard.closeLightbox);
    $(document).on('click', '[data-lightbox]', function(e) {
        // Click on the lightbox backdrop (outside the inner content) closes it.
        if (e.target === this) {
            window.digiriskdolibarr.ticketActionCard.closeLightbox();
        }
    });

    // ---- Kebab menu open/close + outside-click dismissal.
    $(document).on('click', '[data-kebab-toggle]',         window.digiriskdolibarr.ticketActionCard.onKebabToggle);
    $(document).on('click', function(e) {
        // Close all open kebabs when the click is outside any open kebab.
        if (!$(e.target).closest('[data-kebab]').length) {
            $('[data-kebab].is-open').removeClass('is-open');
        }
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('[data-kebab].is-open').removeClass('is-open');
            // Escape also closes the lightbox if open.
            if ($('[data-lightbox]').is(':visible')) {
                window.digiriskdolibarr.ticketActionCard.closeLightbox();
            }
        }
    });
};

/**
 * Open the lightbox modal with the previewed file (image or PDF).
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onFilePreview = function(event) {
    event.preventDefault();
    event.stopPropagation();
    var $btn  = $(this);
    var href  = $btn.data('file-href');
    var kind  = $btn.data('file-kind');
    var name  = $btn.data('file-name') || '';
    var $lb   = $('[data-lightbox]');
    var $title = $('[data-lightbox-title]');
    var $body = $('[data-lightbox-content]');

    $title.text(name);
    $('[data-lightbox-open]').attr('href', href);
    // The href comes pre-encoded from PHP (urlencode on the filename portion). Do NOT
    // re-encode it here — re-running encodeURI re-encodes already-safe chars and breaks
    // document.php's file= parameter.
    if (kind === 'image') {
        $body.html('<img alt="" />');
        $body.find('img').attr('src', href);
    } else if (kind === 'pdf') {
        // <embed> tends to render better than <iframe> for inline PDF viewing
        // (Chrome + Firefox + Edge use their built-in viewer plugin). Falls back to
        // download if no viewer is available, and shows the "Open in new tab" link
        // we put at the top of the lightbox.
        $body.html('<embed type="application/pdf">');
        $body.find('embed').attr('src', href);
    } else {
        window.open(href, '_blank');
        return;
    }
    $lb.addClass('is-open').attr('aria-hidden', 'false');
};

/**
 * Close the lightbox modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.closeLightbox = function() {
    var $lb = $('[data-lightbox]');
    $lb.removeClass('is-open').attr('aria-hidden', 'true');
    $('[data-lightbox-content]').empty();
};

/**
 * Delete an attached file. Uses the arm-confirm pattern (click once = warn,
 * click again within 4s = actually delete).
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onFileDelete = function(event) {
    event.preventDefault();
    event.stopPropagation();
    var $btn  = $(this);
    var name  = $btn.data('file-name');
    if (!name) { return; }

    // Two-click safety: first click swaps the icon for "?" and pulses red.
    if (!$btn.hasClass('is-armed')) {
        $btn.addClass('is-armed');
        var originalHtml = $btn.html();
        $btn.data('original-html', originalHtml);
        $btn.html('<i class="fas fa-question"></i>');
        setTimeout(function() {
            if ($btn.hasClass('is-armed')) {
                $btn.removeClass('is-armed').html($btn.data('original-html'));
            }
        }, 4000);
        return;
    }
    $btn.removeClass('is-armed');

    var $card    = $('.tac-card').first();
    var ajaxUrl  = $card.data('ajax-url');
    var ticketId = $card.data('ticket-id');

    $btn.prop('disabled', true);
    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: ticketId,
            action: 'delete_file',
            file_name: name,
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        if (response && response.success) {
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || 'OK', 'success');
            // Remove the row from the DOM rather than reloading the page.
            $btn.closest('.tac-files-list__item').fadeOut(150, function() { $(this).remove(); });
        } else {
            $btn.prop('disabled', false);
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        $btn.prop('disabled', false);
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
    });
};

/**
 * Actions-mode switch (bar ↔ kebab menu). Saves + reloads since the bar / menu
 * rendering is server-side conditional.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onActionsModeChange = function(event) {
    event.stopPropagation();
    var $btn  = $(this);
    var mode  = $btn.data('actionsmode');
    if (!mode) { return; }
    var $card = $('.tac-card').first();
    if (($card.attr('data-actions-mode') || 'bar') === mode) { return; }
    $card.attr('data-actions-mode', mode);
    $btn.closest('.tac-hero__actionsmode').find('.tac-hero__actionsmode-btn').removeClass('is-active');
    $btn.addClass('is-active');

    var layout = window.digiriskdolibarr.ticketActionCard.serializeLayout();
    $.ajax({
        url: $card.data('ajax-url'),
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: $card.data('ticket-id'),
            action: 'save_layout',
            layout: JSON.stringify(layout),
            token: $('input[name="token"]').val() || ''
        }
    }).done(function() { window.location.reload(); });
};

/**
 * Open/close the kebab menu (⋮). Toggling is_open swaps visibility via CSS.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onKebabToggle = function(event) {
    event.preventDefault();
    event.stopPropagation();
    var $kebab = $(this).closest('[data-kebab]');
    // Close other open kebabs before opening this one.
    $('[data-kebab].is-open').not($kebab).removeClass('is-open');
    $kebab.toggleClass('is-open');
};

/**
 * Add a classification tag to the ticket in 1 click.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onTagAdd = function(event) {
    event.preventDefault();
    var $btn = $(this);
    var catId = $btn.data('category-id');
    if (!catId) {
        return;
    }
    window.digiriskdolibarr.ticketActionCard.tagAjax('add_category', catId, $btn);
};

/**
 * Remove a classification tag from the ticket in 1 click.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onTagRemove = function(event) {
    event.preventDefault();
    event.stopPropagation();
    var $tag = $(this).closest('.tac-tag');
    var catId = $tag.data('category-id');
    if (!catId) {
        return;
    }
    window.digiriskdolibarr.ticketActionCard.tagAjax('remove_category', catId, $tag);
};

/**
 * Shared AJAX call for tag add/remove. Reloads the page on success so the
 * available / assigned chip lists are rebuilt server-side (simpler than
 * patching the DOM and keeping both pools in sync).
 *
 * @param  {string}  ajaxAction  'add_category' or 'remove_category'
 * @param  {number}  catId
 * @param  {jQuery}  $sourceEl
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.tagAjax = function(ajaxAction, catId, $sourceEl) {
    var $card = $('.tac-card').first();
    var ajaxUrl = $card.data('ajax-url');
    var ticketId = $card.data('ticket-id');

    $sourceEl.css('opacity', 0.5).prop('disabled', true);

    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: ticketId,
            action: ajaxAction,
            category_id: catId,
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        if (response && response.success) {
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || 'OK', 'success');
            setTimeout(function() { window.location.reload(); }, 500);
        } else {
            $sourceEl.css('opacity', 1).prop('disabled', false);
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        $sourceEl.css('opacity', 1).prop('disabled', false);
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
    });
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
        // Entering edit mode — activate sortable on the single body grid.
        $body.addClass('tac-edit-mode');
        $label.text($label.data('edit-on-label') || 'Terminé');
        $btn.addClass('tac-hero__customize--active');

        if ($.fn.sortable) {
            var $grid = $card.find('.tac-body').first();
            if ($grid.length && !$grid.data('uiSortable')) {
                $grid.sortable({
                    items:       '> .tac-section',
                    handle:      '.tac-section__drag',
                    placeholder: 'tac-section-placeholder',
                    tolerance:   'pointer',
                    opacity:     0.8,
                    forcePlaceholderSize: true,
                    stop: function() {
                        window.digiriskdolibarr.ticketActionCard.saveLayout();
                    }
                });
            }
        }
    } else {
        // Exiting edit mode.
        $body.removeClass('tac-edit-mode');
        $label.text($label.data('edit-off-label') || 'Personnaliser');
        $btn.removeClass('tac-hero__customize--active');
        if ($.fn.sortable) {
            var $grid2 = $card.find('.tac-body').first();
            if ($grid2.data('uiSortable')) {
                $grid2.sortable('destroy');
            }
        }
        window.digiriskdolibarr.ticketActionCard.saveLayout();
    }
};

/**
 * Reset the user's saved layout (server-side delete) then reload to show defaults.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onLayoutReset = function() {
    if (!window.confirm('Réinitialiser la mise en page ?')) {
        return;
    }
    var $card   = $('.tac-card').first();
    var ajaxUrl = $card.data('ajax-url');
    var ticketId = $card.data('ticket-id');

    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: ticketId,
            action: 'reset_layout',
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        if (response && response.success) {
            window.location.reload();
        } else {
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
    });
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
        // Unique id so CKEditor.replace() can target the textarea after it's inserted in the DOM.
        var taId = 'tac-longtext-' + Math.random().toString(36).slice(2, 8);
        $input = $('<textarea class="tac-edit-textarea" rows="6">').attr('id', taId).val(current);
        $wrap.data('tac-editor-id', taId);
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
    if ($input.is('input[type="text"], input[type="number"]')) {
        $input.select && $input.select();
    }

    // ---- Save handlers
    var commit = function() {
        // For longtext fields backed by a CKEditor instance, pull the rich HTML
        // from the editor rather than the (stale) underlying textarea.
        var newValue;
        var editorId = $wrap.data('tac-editor-id');
        if (type === 'longtext' && editorId && window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[editorId]) {
            newValue = window.CKEDITOR.instances[editorId].getData();
        } else {
            newValue = $input.val();
        }
        var oldValue = current;
        if (String(newValue) === String(oldValue)) {
            window.digiriskdolibarr.ticketActionCard.cleanupEditor($wrap);
            window.digiriskdolibarr.ticketActionCard.restoreField($wrap, originalHtml);
            return;
        }
        window.digiriskdolibarr.ticketActionCard.cleanupEditor($wrap);
        window.digiriskdolibarr.ticketActionCard.saveField($wrap, field, newValue, originalHtml);
    };
    var cancel = function() {
        window.digiriskdolibarr.ticketActionCard.cleanupEditor($wrap);
        window.digiriskdolibarr.ticketActionCard.restoreField($wrap, originalHtml);
    };

    if (type === 'longtext') {
        // Init CKEditor on the textarea after it's in the DOM. Save is via the floating
        // "Enregistrer" button injected next to the editor (blur on a CKEditor area is
        // unreliable because the user may click any toolbar button).
        if (window.CKEDITOR && $input.attr('id')) {
            try {
                window.CKEDITOR.replace($input.attr('id'), {
                    customConfig: window.ckeditorConfig || '',
                    removePlugins: 'elementspath,save,flash,div,anchor,specialchar,exportpdf,wsc,scayt',
                    versionCheck: false,
                    htmlEncodeOutput: false,
                    allowedContent: true,
                    toolbar: 'Basic',
                    height: 200,
                    width: '100%'
                });
            } catch (e) {
                console.warn('CKEditor init failed, falling back to plain textarea:', e);
            }
        }
        // Floating action bar for longtext (Save / Cancel) — blur would fire on every
        // toolbar click otherwise.
        var $bar = $('<div class="tac-edit-actions">'
            + '<button type="button" class="tac-edit-actions__save">Enregistrer</button> '
            + '<button type="button" class="tac-edit-actions__cancel">Annuler</button>'
            + '</div>');
        $input.after($bar);
        $bar.find('.tac-edit-actions__save').on('click', function(e) { e.stopPropagation(); commit(); });
        $bar.find('.tac-edit-actions__cancel').on('click', function(e) { e.stopPropagation(); cancel(); });
        $wrap.data('tac-edit-bar', $bar);
        // Keep Escape support too.
        $(document).on('keydown.tac-longtext-' + ($input.attr('id') || ''), function(e) {
            if (e.key === 'Escape') { cancel(); }
        });
    } else if (type === 'select') {
        $input.on('change', commit);
        $input.on('keydown', function(e) { if (e.key === 'Escape') { cancel(); } });
        $input.on('blur', commit);
    } else {
        $input.on('keydown', function(e) {
            if (e.key === 'Escape') { cancel(); }
            else if (e.key === 'Enter') { commit(); }
        });
        $input.on('blur', commit);
    }
};

/**
 * Destroy any CKEditor instance attached to this field + remove the floating
 * Save/Cancel bar. Called on commit and cancel of longtext fields.
 *
 * @param  {jQuery} $wrap
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.cleanupEditor = function($wrap) {
    var editorId = $wrap.data('tac-editor-id');
    if (editorId && window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[editorId]) {
        try { window.CKEDITOR.instances[editorId].destroy(true); } catch (e) {}
        $(document).off('keydown.tac-longtext-' + editorId);
    }
    var $bar = $wrap.data('tac-edit-bar');
    if ($bar) {
        $bar.remove();
    }
    $wrap.removeData('tac-editor-id').removeData('tac-edit-bar');
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
