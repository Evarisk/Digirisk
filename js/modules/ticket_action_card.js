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
    window.digiriskdolibarr.ticketActionCard.initThreadReplyEditor();
    // Drop the newest message into view on load — feels like opening a chat app.
    setTimeout(function() { window.digiriskdolibarr.ticketActionCard.scrollThreadToBottom(); }, 200);
};

/**
 * Boot CKEditor on the thread reply textarea once on page load. Keeps the editor
 * persistent (no tap-to-edit swap) since the reply box is a permanent form.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.initThreadReplyEditor = function() {
    var $ta = $('.tac-thread__reply-body');
    if (!$ta.length || !window.CKEDITOR) {
        return;
    }
    var id = $ta.attr('id');
    if (!id) {
        return;
    }
    try {
        var ed = window.CKEDITOR.replace(id, {
            customConfig: window.ckeditorConfig || '',
            removePlugins: 'elementspath,save,flash,div,anchor,specialchar,exportpdf,wsc,scayt',
            versionCheck: false,
            htmlEncodeOutput: false,
            allowedContent: true,
            toolbar: 'Basic',
            height: 100,
            width: '100%'
        });
        // Ctrl/Cmd+Enter inside the CKEditor iframe also fires Send.
        // CKEditor encodes modifiers into the keystroke value: CTRL=0x110000, ENTER=13.
        // Numpad Enter is keyCode 10 on some keyboards, so we accept both.
        ed.on('key', function(ev) {
            var CTRL = window.CKEDITOR.CTRL || 0x110000;
            if (ev.data.keyCode === (CTRL + 13) || ev.data.keyCode === (CTRL + 10)) {
                ev.cancel();
                $('[data-thread-send]').first().trigger('click');
            }
        });
    } catch (e) {
        // Fallback: keep the plain textarea.
    }
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

    // ---- Watch / bookmark toggle.
    $(document).on('click', '[data-watch-toggle]',         window.digiriskdolibarr.ticketActionCard.onWatchToggle);

    // ---- Thread reply send + edit/delete/quote per-message actions.
    $(document).on('click',    '[data-thread-send]',       window.digiriskdolibarr.ticketActionCard.onThreadSend);
    $(document).on('click',    '[data-msg-edit]',          window.digiriskdolibarr.ticketActionCard.onMsgEdit);
    $(document).on('click',    '[data-msg-delete]',        window.digiriskdolibarr.ticketActionCard.onMsgDelete);
    $(document).on('click',    '[data-msg-quote]',         window.digiriskdolibarr.ticketActionCard.onMsgQuote);
    $(document).on('keydown',  '[data-thread-reply] input, [data-thread-reply] textarea', window.digiriskdolibarr.ticketActionCard.onReplyKeydown);

    // ---- Drag & drop file upload anywhere on the card.
    // Uses a "drag enter/leave counter" pattern: dragenter increments, dragleave
    // decrements. The overlay shows when counter > 0. Fixes the flicker that
    // happens when the cursor moves between child elements during a drag.
    var $card = $('.tac-card');
    if ($card.length) {
        var dragCounter = 0;
        $card.on('dragenter', function(e) {
            if (!e.originalEvent.dataTransfer || !e.originalEvent.dataTransfer.types) { return; }
            if (Array.from(e.originalEvent.dataTransfer.types).indexOf('Files') === -1) { return; }
            e.preventDefault();
            e.stopPropagation();
            dragCounter++;
            if (dragCounter === 1) {
                $card.addClass('tac-dragover');
            }
        });
        $card.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        $card.on('dragleave', function(e) {
            if (dragCounter > 0) { dragCounter--; }
            if (dragCounter === 0) {
                $card.removeClass('tac-dragover');
            }
        });
        $card.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dragCounter = 0;
            $card.removeClass('tac-dragover');
            var files = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
            if (files && files.length) {
                window.digiriskdolibarr.ticketActionCard.uploadFiles(files);
            }
        });

        // "Choisir fichier" button — hidden <input type="file"> next to the Upload link.
        $(document).on('click', '[data-pick-file]', function(e) {
            e.preventDefault();
            $('#tac-file-input').trigger('click');
        });
        $(document).on('change', '#tac-file-input', function() {
            if (this.files && this.files.length) {
                window.digiriskdolibarr.ticketActionCard.uploadFiles(this.files);
            }
        });
    }

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
        // 'w' shortcut toggles watch — but only when not typing in a field.
        if (e.key === 'w' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            var tag = (e.target && e.target.tagName) || '';
            if (['INPUT', 'TEXTAREA', 'SELECT'].indexOf(tag) !== -1) { return; }
            if (e.target && e.target.isContentEditable) { return; }
            var $btn = $('[data-watch-toggle]');
            if ($btn.length) {
                e.preventDefault();
                $btn.trigger('click');
            }
        }
    });
};

/**
 * Upload one or more files to the ticket via FormData / AJAX. Each file is
 * sent in its own request so partial failures don't abort the rest. The page
 * reloads once all uploads succeed so the new files appear in the list.
 *
 * @param  {FileList} files
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.uploadFiles = function(files) {
    var $card    = $('.tac-card').first();
    var ajaxUrl  = $card.data('ajax-url');
    var ticketId = $card.data('ticket-id');

    // 1. Client-side validation: max size (mirrors common PHP upload_max_filesize default).
    var MAX_BYTES = 32 * 1024 * 1024; // 32 MB
    var FORBIDDEN_EXT = /\.(php|phtml|exe|bat|cmd|sh|js|html?|jar)$/i;
    var validFiles = [];
    var rejected = [];
    Array.prototype.forEach.call(files, function(f) {
        if (f.size > MAX_BYTES) {
            rejected.push(f.name + ' (trop volumineux > 32 MB)');
        } else if (FORBIDDEN_EXT.test(f.name)) {
            rejected.push(f.name + ' (extension interdite)');
        } else {
            validFiles.push(f);
        }
    });
    if (rejected.length) {
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Refusé : ' + rejected.join(', '), 'error');
    }
    if (!validFiles.length) {
        return;
    }

    // 2. Duplicate check: if filenames match existing entries in the list, ask once for confirmation.
    var existing = $('.tac-files-list__item').map(function() { return $(this).data('file-name'); }).get();
    var duplicates = validFiles.filter(function(f) { return existing.indexOf(f.name) !== -1; });
    if (duplicates.length) {
        var names = duplicates.map(function(f) { return f.name; }).join(', ');
        if (!window.confirm('Écraser les fichiers déjà présents ?\n\n' + names)) {
            // Filter out duplicates.
            validFiles = validFiles.filter(function(f) { return existing.indexOf(f.name) === -1; });
            if (!validFiles.length) { return; }
        }
    }

    // 3. Render a progress UI in the toast area.
    var total   = validFiles.length;
    var done    = 0;
    var failed  = 0;
    var $toast  = $card.find('.tac-toast, .ticket-action-card__toast').first();
    $toast.removeClass('is-error is-success').addClass('is-success')
        .html('<span class="tac-upload-label">Upload 0 / ' + total + '…</span>'
            + '<div class="tac-upload-bar"><span class="tac-upload-bar__fill" style="width:0%"></span></div>');

    var updateProgress = function() {
        var pct = Math.round(100 * done / total);
        $toast.find('.tac-upload-label').text('Upload ' + done + ' / ' + total + ' (' + pct + '%)');
        $toast.find('.tac-upload-bar__fill').css('width', pct + '%');
    };

    validFiles.forEach(function(file) {
        var fd = new FormData();
        fd.append('ticket_id', ticketId);
        fd.append('action', 'upload_file');
        fd.append('upload', file);
        fd.append('token', $('input[name="token"]').val() || '');
        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(response) {
            if (!response || !response.success) { failed++; }
        }).fail(function() {
            failed++;
        }).always(function() {
            done++;
            updateProgress();
            if (done === total) {
                if (failed === 0) {
                    $toast.html('Upload terminé ✓');
                    setTimeout(function() { window.location.reload(); }, 500);
                } else {
                    $toast.removeClass('is-success').addClass('is-error')
                        .html(failed + ' échec(s) sur ' + total + ' fichier(s)');
                }
            }
        });
    });
};

/**
 * Send a reply to the ticket thread.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onThreadSend = function(event) {
    event.preventDefault();
    var $btn      = $(this);
    var $form     = $btn.closest('[data-thread-reply]');
    var $card     = $('.tac-card').first();
    var ajaxUrl   = $card.data('ajax-url');
    var ticketId  = $card.data('ticket-id');
    var subject   = $form.find('input[name="subject"]').val();
    var isPrivate = $form.find('input[name="private"]').is(':checked') ? 1 : 0;
    var byEmail   = $form.find('input[name="by_email"]').is(':checked') ? 1 : 0;

    // Pull body from CKEditor if it's bound, else from the raw textarea.
    var $ta = $form.find('.tac-thread__reply-body');
    var taId = $ta.attr('id');
    var body = '';
    if (taId && window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[taId]) {
        body = window.CKEDITOR.instances[taId].getData();
    } else {
        body = $ta.val();
    }
    if (!body || !body.trim()) {
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Le message est vide', 'error');
        return;
    }

    var editingId = $form.data('editing-message-id');

    $btn.prop('disabled', true);
    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: ticketId,
            action: editingId ? 'edit_message' : 'post_message',
            message_id: editingId || 0,
            subject: subject,
            body: body,
            private: isPrivate,
            by_email: byEmail,
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        $btn.prop('disabled', false);
        if (response && response.success) {
            var $thread = $form.siblings('[data-thread]');
            if (editingId) {
                // In-place body refresh; clear edit state on the form.
                var $msg = $thread.find('.tac-thread__msg[data-msg-id="' + editingId + '"]');
                $msg.find('[data-msg-body]').html(response.body_html || '');
                if (response.subject) {
                    $msg.find('.tac-thread__subject').text(response.subject);
                }
                $form.removeData('editing-message-id').removeClass('tac-thread__reply--editing');
                $form.find('[data-thread-send]').html('<i class="fas fa-paper-plane"></i> ' + (($('[data-thread-reply]').data('lang-send') || 'Envoyer')));
            } else {
                // Append new bubble + update (N) counter.
                $thread.find('.tac-thread__empty').remove();
                $thread.append(response.bubble);
                var $count = $('.tac-section[data-section-id="messages_thread"] h3 span');
                if ($count.length) {
                    var n = parseInt(($count.text().match(/\d+/) || [0])[0], 10);
                    $count.text('(' + (n + 1) + ')');
                }
                window.digiriskdolibarr.ticketActionCard.scrollThreadToBottom();
            }
            $form.find('input[name="subject"]').val('');
            $form.find('input[name="private"]').prop('checked', false);
            $form.find('input[name="by_email"]').prop('checked', false);
            if (taId && window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[taId]) {
                window.CKEDITOR.instances[taId].setData('');
            } else {
                $ta.val('');
            }
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || 'OK', 'success');
        } else {
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        $btn.prop('disabled', false);
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
    });
};

/**
 * Smoothly scroll the thread to the bottom so the latest bubble is visible.
 * Used after sending a reply.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.scrollThreadToBottom = function() {
    var $thread = $('[data-thread]').first();
    if (!$thread.length) {
        return;
    }
    var $last = $thread.find('.tac-thread__msg').last();
    if ($last.length && $last[0].scrollIntoView) {
        $last[0].scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }
};

/**
 * Start editing a message: load its body back into the reply form and flip the
 * Send button into "Save" mode. Form keeps the edit state in a data attribute.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onMsgEdit = function(event) {
    event.preventDefault();
    var $msg = $(this).closest('.tac-thread__msg');
    var msgId = $msg.data('msg-id');
    if (!msgId) {
        return;
    }
    var $form = $('[data-thread-reply]').first();
    var $ta   = $form.find('.tac-thread__reply-body');
    var taId  = $ta.attr('id');
    var body  = $msg.find('[data-msg-body]').html() || '';
    var subj  = $msg.find('.tac-thread__subject').text() || '';

    $form.data('editing-message-id', msgId).addClass('tac-thread__reply--editing');
    $form.find('input[name="subject"]').val(subj);
    if (taId && window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[taId]) {
        window.CKEDITOR.instances[taId].setData(body);
        window.CKEDITOR.instances[taId].focus();
    } else {
        $ta.val(body).focus();
    }
    $form.find('[data-thread-send]').html('<i class="fas fa-save"></i> ' + (($('[data-thread-reply]').data('lang-save') || 'Enregistrer')));
    if ($form[0].scrollIntoView) {
        $form[0].scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }
};

/**
 * Delete a message after confirmation. Removes the bubble on success and
 * decrements the (N) counter.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onMsgDelete = function(event) {
    event.preventDefault();
    var $msg  = $(this).closest('.tac-thread__msg');
    var msgId = $msg.data('msg-id');
    var $card = $('.tac-card').first();
    if (!msgId) {
        return;
    }
    if (!window.confirm($('[data-thread-reply]').data('lang-confirm-delete') || 'Supprimer ce message ?')) {
        return;
    }
    $.ajax({
        url: $card.data('ajax-url'),
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: $card.data('ticket-id'),
            action: 'delete_message',
            message_id: msgId,
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        if (response && response.success) {
            $msg.fadeOut(150, function() {
                $(this).remove();
                var $count = $('.tac-section[data-section-id="messages_thread"] h3 span');
                if ($count.length) {
                    var n = parseInt(($count.text().match(/\d+/) || [0])[0], 10);
                    $count.text('(' + Math.max(0, n - 1) + ')');
                }
            });
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || 'OK', 'success');
        } else {
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
    });
};

/**
 * Quote a message: copies its body into the reply textarea wrapped in a
 * <blockquote> with author/date attribution.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onMsgQuote = function(event) {
    event.preventDefault();
    var $msg    = $(this).closest('.tac-thread__msg');
    var author  = $msg.find('.tac-thread__author').first().text() || '';
    var body    = $msg.find('[data-msg-body]').html() || '';
    var $form   = $('[data-thread-reply]').first();
    var $ta     = $form.find('.tac-thread__reply-body');
    var taId    = $ta.attr('id');
    // Quote sits BEFORE the user's reply so they type their answer underneath it.
    var quoted  = '<blockquote><strong>' + $('<div>').text(author).html() + ' :</strong>' + body + '</blockquote><p>&nbsp;</p>';
    if (taId && window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[taId]) {
        var ed = window.CKEDITOR.instances[taId];
        ed.setData(quoted + (ed.getData() || ''));
        ed.focus();
    } else {
        $ta.val(quoted + ($ta.val() || '')).focus();
    }
    if ($form[0].scrollIntoView) {
        $form[0].scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }
};

/**
 * Ctrl/Cmd+Enter inside the reply textarea triggers Send. Bound on the document
 * so it survives CKEditor remounts.
 *
 * @param  {KeyboardEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onReplyKeydown = function(event) {
    if (event.key !== 'Enter' || !(event.ctrlKey || event.metaKey)) {
        return;
    }
    event.preventDefault();
    $('[data-thread-send]').first().trigger('click');
};

/**
 * Toggle watch / bookmark state on the ticket. Updates the button icon
 * optimistically, rolls back on AJAX failure.
 *
 * @param  {MouseEvent} event
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.onWatchToggle = function(event) {
    event.preventDefault();
    var $btn = $(this);
    var $card = $('.tac-card').first();
    var wasWatched = $btn.hasClass('is-watched');

    // Optimistic flip.
    $btn.toggleClass('is-watched');
    $btn.find('i').toggleClass('fas far');

    $.ajax({
        url: $card.data('ajax-url'),
        method: 'POST',
        dataType: 'json',
        data: {
            ticket_id: $card.data('ticket-id'),
            action: 'toggle_watch',
            token: $('input[name="token"]').val() || ''
        }
    }).done(function(response) {
        if (response && response.success) {
            window.digiriskdolibarr.ticketActionCard.flash($card, response.message || '', 'success');
        } else {
            // Rollback.
            $btn.toggleClass('is-watched');
            $btn.find('i').toggleClass('fas far');
            window.digiriskdolibarr.ticketActionCard.flash($card, (response && response.message) || 'Erreur', 'error');
        }
    }).fail(function() {
        $btn.toggleClass('is-watched');
        $btn.find('i').toggleClass('fas far');
        window.digiriskdolibarr.ticketActionCard.flash($card, 'Erreur réseau', 'error');
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
    // Let inner <a> links (history badges, getNomUrl links, etc.) navigate normally
    // instead of opening the tap-to-edit input.
    if ($(event.target).closest('a').length) {
        return;
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
        var options = $wrap.data('edit-options') || [];
        // Long lists (or any server-searched field) get a searchable combo; short
        // static lists keep a plain native <select> which is fine to scroll.
        var useCombo = (!!$wrap.data('edit-remote') || options.length > 8) && !$wrap.is('.tac-hero__subject');
        if (useCombo) {
            var curLabel = '';
            options.forEach(function(opt) {
                if (String(opt.id) === current) { curLabel = opt.label; }
            });
            $input = $('<input type="text" class="tac-edit-input tac-combo__input" autocomplete="off">')
                .attr('placeholder', curLabel || '—')
                .data('combo-value', current)
                .data('is-combo', true);
        } else {
            $input = $('<select class="tac-edit-input">');
            options.forEach(function(opt) {
                var $opt = $('<option>').val(String(opt.id)).text(opt.label);
                if (String(opt.id) === current) {
                    $opt.prop('selected', true);
                }
                $input.append($opt);
            });
        }
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
        } else if ($input.data('is-combo')) {
            // Searchable combo stores the chosen option id separately from the
            // visible search text.
            newValue = $input.data('combo-value');
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
    } else if (type === 'select' && $input.data('is-combo')) {
        // Custom searchable combo — built in-house because Select2 stalls on
        // "Searching…" in this Dolibarr build. A floating, accent-insensitive
        // filtered list anchored to the input via fixed positioning (so it escapes
        // the card's overflow:hidden). When the field declares data-edit-remote, the
        // list is fetched server-side instead of filtered from a static array.
        window.digiriskdolibarr.ticketActionCard.initCombo($wrap, $input, $wrap.data('edit-options') || [], current, commit, cancel, $wrap.data('edit-remote') || '');
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
    // Tear down a searchable combo's floating list + its document/window handlers.
    var comboNs = $wrap.data('tac-combo-ns');
    if (comboNs) {
        var $list = $wrap.data('tac-combo-list');
        if ($list) { $list.remove(); }
        $(document).off('.' + comboNs);
        $(window).off('.' + comboNs);
        $wrap.removeData('tac-combo-ns').removeData('tac-combo-list');
    }
    $wrap.removeData('tac-editor-id').removeData('tac-edit-bar');
};

/**
 * Build a searchable combo for a long select field: a text input (already inserted
 * by the caller) plus a floating, accent-insensitive filtered option list.
 *
 * The list is appended to <body> and positioned with position:fixed so it is not
 * clipped by the card's overflow:hidden. Selecting an option stores its id on the
 * input via .data('combo-value') and commits.
 *
 * @param  {jQuery}   $wrap     The field wrapper (.tac-field / .tac-chip).
 * @param  {jQuery}   $input    The text input rendered in place of the value.
 * @param  {Array}    options   [{id, label}, ...] (static mode seed).
 * @param  {string}   current   Currently selected id.
 * @param  {Function} commit    Save callback (reads $input.data('combo-value')).
 * @param  {Function} cancel    Restore callback.
 * @param  {string}   remote    Optional server source key ('societe'|'project'); when
 *                              set, the list is fetched via AJAX instead of filtered.
 * @return {void}
 */
window.digiriskdolibarr.ticketActionCard.initCombo = function($wrap, $input, options, current, commit, cancel, remote) {
    var ns = 'taccombo' + Math.random().toString(36).slice(2, 8);
    var $list = $('<ul class="tac-combo__list" role="listbox">').appendTo('body');
    var activeIndex = -1;
    var visible = [];
    var searchTimer = null;
    var reqSeq = 0;

    // commit/cancel must fire at most once — a mousedown on an option bubbles to the
    // outside-click handler too, which would otherwise cancel right after committing.
    var settled = false;
    var doCommit = function() { if (settled) { return; } settled = true; commit(); };
    var doCancel = function() { if (settled) { return; } settled = true; cancel(); };

    var norm = function(s) {
        return String(s == null ? '' : s)
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .toLowerCase();
    };

    var position = function() {
        var r = $input[0].getBoundingClientRect();
        $list.css({
            position: 'fixed',
            top: Math.round(r.bottom + 2) + 'px',
            left: Math.round(r.left) + 'px',
            width: Math.round(r.width) + 'px'
        });
    };

    // Paint the list from a [{id,label}] array (used by both static and remote).
    var paint = function(rows) {
        $list.empty();
        visible = [];
        activeIndex = -1;
        rows.forEach(function(opt) {
            var $li = $('<li class="tac-combo__opt" role="option">')
                .attr('data-value', String(opt.id))
                .text(opt.label);
            if (String(opt.id) === String(current)) { $li.addClass('is-current'); }
            $list.append($li);
            visible.push(opt);
        });
        if (visible.length === 0) {
            $list.append('<li class="tac-combo__empty">Aucun résultat</li>');
        }
        position();
    };

    var render = function(term) {
        if (remote) {
            // Debounced server-side search.
            $list.empty().append('<li class="tac-combo__empty">…</li>');
            position();
            if (searchTimer) { clearTimeout(searchTimer); }
            var seq = ++reqSeq;
            searchTimer = setTimeout(function() {
                var $card = $wrap.closest('.ticket-action-card');
                $.ajax({
                    url: $card.data('ajax-url'),
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        ticket_id: $card.data('ticket-id'),
                        action: 'search_options',
                        source: remote,
                        term: term || '',
                        token: $('input[name="token"]').val() || ''
                    }
                }).done(function(response) {
                    if (seq !== reqSeq) { return; } // a newer query superseded this one
                    paint((response && response.success && response.results) ? response.results : []);
                }).fail(function() {
                    if (seq !== reqSeq) { return; }
                    paint([]);
                });
            }, 250);
            return;
        }
        var f = norm(term);
        paint(options.filter(function(opt) {
            return !f || norm(opt.label).indexOf(f) !== -1;
        }));
    };

    var choose = function(value) {
        $input.data('combo-value', String(value));
        doCommit();
    };

    var setActive = function(idx) {
        var $opts = $list.find('.tac-combo__opt');
        if (!$opts.length) { return; }
        activeIndex = Math.max(0, Math.min(idx, $opts.length - 1));
        $opts.removeClass('is-active');
        var $a = $opts.eq(activeIndex).addClass('is-active');
        var liTop = $a[0].offsetTop, liBot = liTop + $a[0].offsetHeight;
        if (liTop < $list[0].scrollTop) { $list[0].scrollTop = liTop; }
        else if (liBot > $list[0].scrollTop + $list[0].clientHeight) { $list[0].scrollTop = liBot - $list[0].clientHeight; }
    };

    render('');
    $input.focus();

    $input.on('input', function() { render($input.val()); });
    $input.on('keydown', function(e) {
        if (e.key === 'Escape') { e.preventDefault(); doCancel(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIndex + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(activeIndex - 1); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && visible[activeIndex]) { choose(visible[activeIndex].id); }
        }
    });

    // mousedown (not click) so we beat the input's blur-cancel race.
    $list.on('mousedown', '.tac-combo__opt', function(e) {
        e.preventDefault();
        choose($(this).attr('data-value'));
    });

    // Click anywhere outside the input + list cancels.
    $(document).on('mousedown.' + ns, function(e) {
        if ($(e.target).closest($input).length || $(e.target).closest($list).length) { return; }
        doCancel();
    });
    $(window).on('scroll.' + ns + ' resize.' + ns, position);

    $wrap.data('tac-combo-ns', ns).data('tac-combo-list', $list);
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
            // Status changed → append a new pill to the timeline so users see the
            // transition live without a reload. Tile / sticky actions reload the page
            // anyway (server-rendered milestones), so this only matters for the
            // tap-to-edit path on .tac-chip--status.
            if (field === 'fk_statut') {
                var $timeline = $('.tac-timeline');
                if ($timeline.length) {
                    // Strip --current from the previous tail.
                    $timeline.find('.tac-timeline__step--current').removeClass('tac-timeline__step--current');
                    // Extract the new status label from the badge HTML the server returned.
                    var newStatusLabel = $('<div>').html(response.display || '').text().trim();
                    if (newStatusLabel) {
                        $timeline
                            .append('<span class="tac-timeline__delta" aria-hidden="true"><i class="fas fa-long-arrow-alt-right"></i> à l\'instant</span>')
                            .append('<span class="tac-timeline__step tac-timeline__step--current" title="à l\'instant">' + newStatusLabel + '</span>');
                    }
                }
            }
            // Severity changed → refresh the card's tac-severity-* class so the rail
            // and ref tint update without a full reload. Also show/hide the hero badge.
            if (field === 'severity_code') {
                var $sevCard = $('.tac-card').first();
                $sevCard.attr('class', function(_, c) { return c.replace(/\btac-severity-\S+/g, ''); });
                var newKey = (response.ticket && response.ticket.severity_key) || (String(newValue || '').toLowerCase().replace(/[^a-z0-9_-]/g, '') || 'default');
                if (['low','normal','high','blocking'].indexOf(newKey) === -1) { newKey = 'default'; }
                $sevCard.addClass('tac-severity-' + newKey);
                $sevCard.attr('data-severity', newKey);
                // Hero severity badge — remove for low/normal, show for high/blocking.
                // Read the label from the AJAX response display HTML (server-rendered),
                // not from the DOM — at this point the field cell still contains the
                // <select> with ALL the options, so .text() would return everything concatenated.
                $('[data-severity-badge]').remove();
                if (newKey === 'high' || newKey === 'blocking') {
                    var variant = (newKey === 'blocking') ? 'red' : 'orange';
                    var label = $('<div>').html(response.display || '').text().trim();
                    $('.tac-chip--status').after(
                        '<span class="tac-chip tac-chip--readonly tac-chip--severity tac-chip--severity-' + variant + '" data-severity-badge>'
                        + '<i class="fas fa-exclamation-triangle"></i> ' + label.toUpperCase()
                        + '</span>'
                    );
                }
            }
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

/* ============================================================
 * KANBAN PICKER — ticket_action_card.php picker view
 * Three dimension tabs: Type / Group / Severity.
 * ============================================================ */

window.digiriskdolibarr.ticketPickerKanban = {};

/**
 * Auto-invoked by digiriskdolibarr.js on document.ready.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.init = function() {
    if (!$('.tac-picker-kanban-board').length) {
        return;
    }
    window.digiriskdolibarr.ticketPickerKanban.event();
    window.digiriskdolibarr.ticketPickerKanban.initSortable();
    window.digiriskdolibarr.ticketPickerKanban.initSettings();
    window.digiriskdolibarr.ticketPickerKanban.initQuickCreate();

    // Restore the last active tab from localStorage.
    var savedDim = localStorage.getItem('tacPickerActiveDim');
    if (savedDim && $('#tacDimTabs .tac-dim-tab[data-dim="' + savedDim + '"]').length) {
        $('#tacDimTabs .tac-dim-tab').removeClass('active');
        $('#tacDimTabs .tac-dim-tab[data-dim="' + savedDim + '"]').addClass('active');
        $('#tacDimPanels .tac-dim-panel').removeClass('active');
        $('#tacDimPanels .tac-dim-panel[data-dim="' + savedDim + '"]').addClass('active');
    }
};

/**
 * Register delegated events for the kanban picker.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.event = function() {
    // Assignee initial: click → reveal a searchable select2 user picker.
    $(document).on('click', '.tac-picker-kanban-board .kanban-initial-responsible, .tac-picker-kanban-board .tac-picker-card__assignee-img', function(e) {
        e.stopPropagation();
        var $wrapper = $(this).closest('.kanban-responsible-wrapper');
        var $select  = $wrapper.find('.tac-picker-assignee-select');
        $(this).hide();
        $select.addClass('visible');
        if ($.fn.select2) {
            window.digiriskdolibarr.ticketPickerKanban.initAssigneeSelect2($select, $wrapper);
            $select.select2('open');
        } else {
            $select.trigger('focus');
        }
    });

    // Assignee select: change → save + hide select → update avatar
    $(document).on('change', '.tac-picker-assignee-select', function() {
        var $select  = $(this);
        var $wrapper = $select.closest('.kanban-responsible-wrapper');
        var $avatar  = $wrapper.find('.kanban-initial-responsible, .tac-picker-card__assignee-img');
        var ticketId = $select.data('ticket-id');
        var userId   = parseInt($select.val(), 10);
        var selOpt   = $select.find('option:selected');
        var newInit  = userId > 0 ? (selOpt.data('initial') || selOpt.text().substring(0, 2).toUpperCase()) : '?';
        var photo    = userId > 0 ? (selOpt.data('photo') || '') : '';

        $select.removeClass('visible');
        if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
            $select.off('select2:close');
            $select.select2('destroy');
        }

        if (photo) {
            $avatar.attr('src', photo).attr('title', selOpt.text().trim()).show();
        } else {
            $avatar.text(newInit)
                   .attr('title', userId > 0 ? selOpt.text().trim() : '')
                   .toggleClass('kanban-initial-empty', userId === 0)
                   .show();
        }

        window.digiriskdolibarr.ticketPickerKanban.saveAssignee(ticketId, userId, $select);
    });

    // Assignee select blur → hide (native fallback only; select2 manages its own
    // close via the select2:close event, so skip when select2 is active).
    $(document).on('blur', '.tac-picker-assignee-select', function() {
        var $select = $(this);
        if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
            return;
        }
        var $avatar = $select.siblings('.kanban-initial-responsible, .tac-picker-card__assignee-img');
        setTimeout(function() {
            $select.removeClass('visible');
            $avatar.show();
        }, 200);
    });

    // Prevent card drag when touching the assignee controls
    $(document).on('mousedown', '.tac-picker-assignee-select, .tac-picker-card__assignee-img, .kanban-responsible-wrapper', function(e) {
        e.stopPropagation();
    });

    // Tag: toggle dropdown
    $(document).on('click', '.tac-picker-kanban-board .kanban-add-tag-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.tac-picker-kanban-board .kanban-tag-dropdown.visible')
            .not($(this).siblings('.kanban-tag-dropdown'))
            .each(function() {
                $(this).removeClass('visible');
                $(this).closest('.tac-picker-card').removeClass('kanban-card-dropdown-open');
            });
        var $dropdown = $(this).siblings('.kanban-tag-dropdown');
        var $card = $(this).closest('.tac-picker-card');
        $dropdown.toggleClass('visible');
        $card.toggleClass('kanban-card-dropdown-open', $dropdown.hasClass('visible'));
    });

    // Tag option click: add category
    $(document).on('click', '.tac-picker-kanban-board .kanban-tag-option', function(e) {
        e.stopPropagation();
        var $opt = $(this);
        if ($opt.hasClass('assigned')) {
            return;
        }
        var $dropdown = $opt.closest('.kanban-tag-dropdown');
        var ticketId  = $dropdown.data('ticket-id');
        var catId     = $opt.data('value');
        var $card     = $opt.closest('.tac-picker-card');
        var $tagsRow  = $card.find('.kanban-card-tags');

        $dropdown.removeClass('visible');
        $card.removeClass('kanban-card-dropdown-open');

        window.digiriskdolibarr.ticketPickerKanban.saveTagAdd(ticketId, catId, $opt, $tagsRow, $card);
    });

    // Close tag dropdown on outside click
    $(document).on('click', function() {
        $('.tac-picker-kanban-board .kanban-tag-dropdown.visible').each(function() {
            $(this).removeClass('visible');
            $(this).closest('.tac-picker-card').removeClass('kanban-card-dropdown-open');
        });
    });
    $(document).on('click', '.tac-picker-kanban-board .kanban-tag-dropdown', function(e) {
        e.stopPropagation();
    });

    // Tag remove
    $(document).on('click', '.tac-picker-kanban-board .kanban-tag-remove', function(e) {
        e.stopPropagation();
        var $tag     = $(this).closest('.kanban-tag');
        var catId    = $tag.data('cat-id');
        var $card    = $tag.closest('.tac-picker-card');
        var ticketId = $card.data('ticket-id');

        $tag.css('opacity', '0.4');
        window.digiriskdolibarr.ticketPickerKanban.saveTagRemove(ticketId, catId, $tag, $card);
    });

    // Prevent drag on tag elements
    $(document).on('mousedown', '.tac-picker-kanban-board .kanban-tag, .tac-picker-kanban-board .kanban-tag-remove, .tac-picker-kanban-board .kanban-add-tag-btn, .tac-picker-kanban-board .kanban-tag-dropdown, .tac-picker-kanban-board .kanban-tag-option', function(e) {
        e.stopPropagation();
    });

    // Tab switching — persist active dim in localStorage so F5 keeps the current tab.
    $(document).on('click', '#tacDimTabs .tac-dim-tab', function() {
        var dim = $(this).data('dim');
        $('#tacDimTabs .tac-dim-tab').removeClass('active');
        $(this).addClass('active');
        $('#tacDimPanels .tac-dim-panel').removeClass('active');
        $('#tacDimPanels .tac-dim-panel[data-dim="' + dim + '"]').addClass('active');
        localStorage.setItem('tacPickerActiveDim', dim);
    });

    // Subject: focus on contenteditable span → store original for rollback.
    $(document).on('focus', '.tac-picker-kanban-board .tac-picker-card__subject-text', function() {
        var $span = $(this);
        $span.data('editOriginal', this.innerText || $span.text());
        $span.addClass('tac-picker-subject--editing');
        // Select all text so user can replace immediately.
        var el = this;
        setTimeout(function() {
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }, 0);
    });

    // Subject: Enter saves, Escape restores.
    $(document).on('keydown', '.tac-picker-kanban-board .tac-picker-card__subject-text', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.blur();
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            var original = $(this).data('editOriginal') || '';
            this.textContent = original;
            this.blur();
        }
    });

    // Subject: plain-text paste only.
    $(document).on('paste', '.tac-picker-kanban-board .tac-picker-card__subject-text', function(e) {
        e.preventDefault();
        var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text/plain');
        var sel  = window.getSelection();
        if (sel.rangeCount) {
            var range = sel.getRangeAt(0);
            range.deleteContents();
            range.insertNode(document.createTextNode(text));
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
        }
    });

    // Subject: blur → save if changed.
    $(document).on('blur', '.tac-picker-kanban-board .tac-picker-card__subject-text', function() {
        var $span      = $(this);
        $span.removeClass('tac-picker-subject--editing');
        var newSubject = $.trim(this.innerText || this.textContent || '');
        var original   = $span.data('editOriginal') || '';
        var ticketId   = $span.data('ticket-id');

        if (!newSubject) {
            this.textContent = original;
            return;
        }
        if (newSubject === original) {
            return;
        }

        var $card = $span.closest('.tac-picker-card');
        window.digiriskdolibarr.ticketPickerKanban.saveSubject(ticketId, newSubject, $span, original, $card);
    });

    // Prevent drag when clicking the subject text.
    $(document).on('mousedown', '.tac-picker-kanban-board .tac-picker-card__subject-text', function(e) {
        e.stopPropagation();
    });

    // Severity badge click → show inline select.
    $(document).on('click', '.tac-picker-kanban-board .tac-picker-sev-badge', function(e) {
        e.stopPropagation();
        var $badge  = $(this);
        var $card   = $badge.closest('.tac-picker-card');
        var $select = $card.find('.tac-picker-sev-inline-select');

        $badge.hide();
        $select.addClass('visible').trigger('focus');
    });

    // Severity inline select change → save + update badge.
    $(document).on('change', '.tac-picker-kanban-board .tac-picker-sev-inline-select', function() {
        var $select  = $(this);
        var $card    = $select.closest('.tac-picker-card');
        var $badge   = $card.find('.tac-picker-sev-badge');
        var ticketId = $select.data('ticket-id');
        var newVal   = $select.val();
        var selText  = $select.find('option:selected').text().trim();

        $select.removeClass('visible');

        var sevColors = {
            'LOW':      {bg: '#e9ecef', text: '#495057'},
            'NORMAL':   {bg: '#cfe2ff', text: '#0a58ca'},
            'HIGH':     {bg: '#fff3cd', text: '#664d03'},
            'BLOCKING': {bg: '#f8d7da', text: '#842029'}
        };
        var col = sevColors[(newVal || '').toUpperCase()] || {bg: '#e9ecef', text: '#495057'};

        $badge.css({'background': col.bg, 'color': col.text}).text(selText).show();
        $badge.attr('data-severity-code', newVal);

        window.digiriskdolibarr.ticketPickerKanban.saveDim(ticketId, 'severity', newVal || '__none__', $card);
    });

    // Severity inline select blur → restore badge.
    $(document).on('blur', '.tac-picker-kanban-board .tac-picker-sev-inline-select', function() {
        var $select = $(this);
        var $badge  = $select.closest('.tac-picker-card').find('.tac-picker-sev-badge');
        setTimeout(function() {
            if ($select.hasClass('visible')) {
                $select.removeClass('visible');
                $badge.show();
            }
        }, 200);
    });

    // Prevent drag when clicking severity badge or select.
    $(document).on('mousedown', '.tac-picker-kanban-board .tac-picker-sev-badge, .tac-picker-kanban-board .tac-picker-sev-inline-select', function(e) {
        e.stopPropagation();
    });
};

/**
 * Initialize jQuery UI Sortable for all three dimension kanban boards.
 * Each board's column bodies use the class kanban-picker-sortable and
 * are connected only within the same board (same data-dim value).
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.initSortable = function() {
    if (!$('.kanban-picker-sortable').length) {
        return;
    }

    var dims = ['status', 'type', 'group', 'severity'];
    var actionMap = {
        status:   'updateTicketStatus',
        type:     'updateTicketType',
        group:    'updateTicketGroup',
        severity: 'updateTicketSeverity'
    };
    var cancelSel = '.kanban-responsible-wrapper, .tac-picker-assignee-select, .kanban-tag, '
        + '.kanban-tag-remove, .kanban-add-tag-btn, .kanban-tag-dropdown, .kanban-tag-option, '
        + '.tac-picker-card__assignee-img, .kanban-initial-responsible, .kanban-card-label, '
        + '.tac-picker-card__subject-text, .tac-picker-sev-badge, .tac-picker-sev-inline-select';

    $.each(dims, function(i, dim) {
        var $cols = $('.kanban-picker-sortable[data-dim="' + dim + '"]');
        if (!$cols.length) {
            return;
        }
        $cols.sortable({
            connectWith:  '.kanban-picker-sortable[data-dim="' + dim + '"]',
            placeholder:  'kanban-card-placeholder',
            tolerance:    'pointer',
            cursor:       'grabbing',
            cancel:       cancelSel,
            receive: function(event, ui) {
                var $card    = ui.item;
                var $column  = $(this);
                var ticketId = $card.data('ticket-id');
                var newVal   = $column.data('dim-value');

                $card.data('dim-value', newVal);

                $column.find('.kanban-empty').remove();

                var $source = ui.sender;
                if ($source.children('.tac-picker-card').length === 0) {
                    $source.append('<div class="kanban-empty">Aucun ticket</div>');
                }

                window.digiriskdolibarr.ticketPickerKanban.updateCounts();
                window.digiriskdolibarr.ticketPickerKanban.saveDim(ticketId, dim, newVal, $card);
            }
        });
    });
};

/**
 * Update column header counters after a card move.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.updateCounts = function() {
    $('.tac-picker-column').each(function() {
        var count = $(this).find('.tac-picker-card').length;
        $(this).find('.kanban-column-count').text(count);
    });
};

/**
 * AJAX — save a dimension change (type / group / severity) after drag & drop.
 *
 * @param {number} ticketId  Ticket row ID
 * @param {string} dim       Dimension: 'type' | 'group' | 'severity'
 * @param {string} newVal    New code value for that dimension
 * @param {jQuery} $card     The card element
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.saveDim = function(ticketId, dim, newVal, $card) {
    var token     = window.saturne.toolbox.getToken();
    var sep       = window.saturne.toolbox.getQuerySeparator(document.URL);
    var actionMap = {
        status:   'updateTicketStatus',
        type:     'updateTicketType',
        group:    'updateTicketGroup',
        severity: 'updateTicketSeverity'
    };
    var action    = actionMap[dim];
    if (!action) {
        return;
    }

    $card.addClass('kanban-card-saving');

    $.ajax({
        url:      document.URL + sep + 'action=' + action + '&ticket_id=' + ticketId + '&dim_value=' + encodeURIComponent(newVal) + '&token=' + token,
        type:     'POST',
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                $card.addClass('kanban-card-saved');
                setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
            } else {
                $card.addClass('kanban-card-error');
                setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
            }
        },
        error: function() {
            $card.removeClass('kanban-card-saving');
            $card.addClass('kanban-card-error');
            setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
        }
    });
};

/**
 * Initialise a select2 searchable picker on a card's assignee <select>.
 * Options are already embedded in the DOM (no AJAX source) so it never hits
 * the "Searching…" stall that remote select2 shows in this Dolibarr build.
 *
 * @param  {jQuery} $select   The .tac-picker-assignee-select element.
 * @param  {jQuery} $wrapper  The .kanban-responsible-wrapper around it.
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.initAssigneeSelect2 = function($select, $wrapper) {
    if ($select.hasClass('select2-hidden-accessible')) {
        return;
    }
    // select2 builds aria-controls from the element id; the same ticket is rendered
    // on 4 boards so the markup carries no id. Give each one a unique id to avoid
    // select2's invalid "input[aria-controls*=]" selector error.
    if (!$select.attr('id')) {
        $select.attr('id', 'tac-assignee-' + Math.random().toString(36).slice(2, 9));
    }
    $select.select2({
        width:             '190px',
        dropdownCssClass:  'tac-picker-assignee-dropdown',
        containerCssClass: 'tac-picker-assignee-container'
    });
    // Closing the picker without choosing anyone restores the avatar. A real
    // selection is handled by the 'change' handler, which tears select2 down.
    $select.on('select2:close', function() {
        setTimeout(function() {
            if ($select.hasClass('visible')) {
                window.digiriskdolibarr.ticketPickerKanban.teardownAssigneeSelect2($select, $wrapper);
            }
        }, 50);
    });
};

/**
 * Destroy the assignee select2 widget and restore the avatar bubble.
 *
 * @param  {jQuery} $select   The .tac-picker-assignee-select element.
 * @param  {jQuery} $wrapper  The .kanban-responsible-wrapper around it.
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.teardownAssigneeSelect2 = function($select, $wrapper) {
    $select.removeClass('visible');
    if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
        $select.off('select2:close');
        $select.select2('destroy');
    }
    $wrapper.find('.kanban-initial-responsible, .tac-picker-card__assignee-img').show();
};

/**
 * AJAX — save new assignee for a ticket card.
 *
 * @param {number} ticketId   Ticket row ID
 * @param {number} userId     User row ID (0 = unassign)
 * @param {jQuery} $select    The select element
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.saveAssignee = function(ticketId, userId, $select) {
    var token = window.saturne.toolbox.getToken();
    var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);
    var $card = $select.closest('.tac-picker-card');

    $card.addClass('kanban-card-saving');

    $.ajax({
        url: document.URL + sep + 'action=updateTicketAssignee&ticket_id=' + ticketId + '&user_id=' + userId + '&token=' + token,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                $card.addClass('kanban-card-saved');
                setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
            } else {
                $card.addClass('kanban-card-error');
                setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
            }
        },
        error: function() {
            $card.removeClass('kanban-card-saving');
            $card.addClass('kanban-card-error');
            setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
        }
    });
};

/**
 * AJAX — add a category to a ticket, then inject the new tag chip.
 *
 * @param {number} ticketId  Ticket row ID
 * @param {number} catId     Category row ID
 * @param {jQuery} $opt      The clicked option element
 * @param {jQuery} $tagsRow  The .kanban-card-tags container
 * @param {jQuery} $card     The card element
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.saveTagAdd = function(ticketId, catId, $opt, $tagsRow, $card) {
    var token = window.saturne.toolbox.getToken();
    var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

    $card.addClass('kanban-card-saving');

    $.ajax({
        url: document.URL + sep + 'action=addTicketCategory&ticket_id=' + ticketId + '&cat_id=' + catId + '&token=' + token,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                var bgColor = response.color ? '#' + response.color : '#8c8c8c';
                var $tag = $('<span class="kanban-tag" data-cat-id="' + response.id + '" style="background:' + bgColor + '">'
                    + response.label
                    + '<span class="kanban-tag-remove" title="Supprimer">&times;</span></span>');
                $tagsRow.find('.kanban-tag-dropdown-wrapper').before($tag);
                $opt.addClass('assigned').append('<i class="fas fa-check" style="margin-left:auto;font-size:9px;color:#28a745"></i>');
                $card.addClass('kanban-card-saved');
                setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
            } else {
                $card.addClass('kanban-card-error');
                setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
            }
        },
        error: function() {
            $card.removeClass('kanban-card-saving');
            $card.addClass('kanban-card-error');
            setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
        }
    });
};

/**
 * AJAX — remove a category from a ticket, then remove the tag chip.
 *
 * @param {number} ticketId  Ticket row ID
 * @param {number} catId     Category row ID
 * @param {jQuery} $tag      The tag chip element
 * @param {jQuery} $card     The card element
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.saveTagRemove = function(ticketId, catId, $tag, $card) {
    var token = window.saturne.toolbox.getToken();
    var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

    $.ajax({
        url: document.URL + sep + 'action=removeTicketCategory&ticket_id=' + ticketId + '&cat_id=' + catId + '&token=' + token,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $tag.slideUp(150, function() {
                    $(this).remove();
                    $card.find('.kanban-tag-option[data-value="' + catId + '"]').removeClass('assigned').find('.fa-check').remove();
                });
                $card.addClass('kanban-card-saved');
                setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
            } else {
                $tag.css('opacity', '1');
                $card.addClass('kanban-card-error');
                setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
            }
        },
        error: function() {
            $tag.css('opacity', '1');
        }
    });
};

/**
 * AJAX — save an updated subject after contenteditable edit.
 *
 * @param {number} ticketId     Ticket row ID
 * @param {string} newSubject   New subject text
 * @param {jQuery} $span        The contenteditable .tac-picker-card__subject-text element
 * @param {string} originalText Subject before editing (for rollback)
 * @param {jQuery} $card        The card element
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.saveSubject = function(ticketId, newSubject, $span, originalText, $card) {
    var token = window.saturne.toolbox.getToken();
    var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

    $card.addClass('kanban-card-saving');

    $.ajax({
        url:      document.URL + sep + 'action=updateTicketSubject&ticket_id=' + ticketId + '&token=' + token,
        type:     'POST',
        data:     {subject: newSubject},
        dataType: 'json',
        success: function(response) {
            $card.removeClass('kanban-card-saving');
            if (response.success) {
                var saved = response.subject || newSubject;
                $span[0].textContent = saved;
                $span.data('editOriginal', saved);
                $card.addClass('kanban-card-saved');
                setTimeout(function() { $card.removeClass('kanban-card-saved'); }, 2000);
            } else {
                $span[0].textContent = originalText;
                $span.data('editOriginal', originalText);
                $card.addClass('kanban-card-error');
                setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
            }
        },
        error: function() {
            $card.removeClass('kanban-card-saving');
            $span[0].textContent = originalText;
            $span.data('editOriginal', originalText);
            $card.addClass('kanban-card-error');
            setTimeout(function() { $card.removeClass('kanban-card-error'); }, 3000);
        }
    });
};

/**
 * Column width & gap settings with localStorage persistence.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.initSettings = function() {
    var $btn      = $('#kanbanPickerSettingsBtn');
    var $popover  = $('#kanbanPickerSettingsPopover');
    var $board    = $('.tac-picker-kanban-board');
    var $wSlider  = $('#kanbanPickerColWidth');
    var $gSlider  = $('#kanbanPickerColGap');
    var $wVal     = $('#kanbanPickerColWidthVal');
    var $gVal     = $('#kanbanPickerColGapVal');

    if (!$btn.length) {
        return;
    }

    var savedW = localStorage.getItem('tacPickerKanbanColWidth');
    var savedG = localStorage.getItem('tacPickerKanbanColGap');
    if (savedW) {
        $wSlider.val(savedW);
        $wVal.text(savedW + 'px');
        $board.find('.tac-picker-column').css({'min-width': savedW + 'px', 'max-width': savedW + 'px'});
    }
    if (savedG) {
        $gSlider.val(savedG);
        $gVal.text(savedG + 'px');
        $board.css('gap', savedG + 'px');
    }

    $btn.on('click', function(e) {
        e.stopPropagation();
        $popover.toggleClass('open');
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#kanbanPickerSettingsBtn, #kanbanPickerSettingsPopover').length) {
            $popover.removeClass('open');
        }
    });

    $wSlider.on('input', function() {
        var val = $(this).val();
        $wVal.text(val + 'px');
        $board.find('.tac-picker-column').css({'min-width': val + 'px', 'max-width': val + 'px'});
        localStorage.setItem('tacPickerKanbanColWidth', val);
    });
    $gSlider.on('input', function() {
        var val = $(this).val();
        $gVal.text(val + 'px');
        $board.css('gap', val + 'px');
        localStorage.setItem('tacPickerKanbanColGap', val);
    });
};

// ---------------------------------------------------------------------------
// Quick-create ticket drawer
// ---------------------------------------------------------------------------

/**
 * Open the quick-create drawer and focus the subject field.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.openQuickCreate = function() {
    var $drawer = $('#tacQuickCreate');
    if (!$drawer.length) {
        return;
    }
    $drawer.addClass('is-open').attr('aria-hidden', 'false');
    $('body').css('overflow', 'hidden');
    // Reset the form and file list each time it opens.
    $('#tacQuickCreateForm')[0].reset();
    $('#tacQcFileList').empty();
    $('#tacQcDropzone').removeClass('is-dragover');
    $('#tacQcToast').removeClass('is-error is-success').text('');
    $('#tacQcSubmit').removeClass('is-loading').prop('disabled', false);
    setTimeout(function() { $('#tacQcSubject').trigger('focus'); }, 220);
};

/**
 * Close the quick-create drawer.
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.closeQuickCreate = function() {
    $('#tacQuickCreate').removeClass('is-open').attr('aria-hidden', 'true');
    $('body').css('overflow', '');
};

/**
 * Submit the quick-create form via AJAX (multipart/form-data for file uploads).
 *
 * @param {Event} e  Form submit event
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.submitQuickCreate = function(e) {
    e.preventDefault();

    var $form   = $('#tacQuickCreateForm');
    var $submit = $('#tacQcSubmit');
    var $toast  = $('#tacQcToast');

    var subject = $.trim($('#tacQcSubject').val());
    if (!subject) {
        $toast.removeClass('is-success').addClass('is-error').text('Le sujet est obligatoire.');
        $('#tacQcSubject').trigger('focus');
        return;
    }

    var token = window.saturne.toolbox.getToken();
    var sep   = window.saturne.toolbox.getQuerySeparator(document.URL);

    // Use FormData so that file inputs are included in the request.
    var fd = new FormData($form[0]);

    $submit.addClass('is-loading').prop('disabled', true);
    $toast.removeClass('is-error is-success').text('');

    $.ajax({
        url: document.URL + sep + 'action=createTicket&token=' + token,
        type: 'POST',
        dataType: 'json',
        data: fd,
        processData: false,
        contentType: false,
        success: function(response) {
            $submit.removeClass('is-loading').prop('disabled', false);
            if (response.success) {
                window.location.href = response.url;
            } else {
                $toast.removeClass('is-success').addClass('is-error')
                      .text(response.error || 'Une erreur est survenue.');
            }
        },
        error: function() {
            $submit.removeClass('is-loading').prop('disabled', false);
            $toast.removeClass('is-success').addClass('is-error')
                  .text('Erreur réseau. Veuillez réessayer.');
        }
    });
};

/**
 * Render the selected files list below the dropzone.
 *
 * @param {FileList} files
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.renderFileList = function(files) {
    var $list = $('#tacQcFileList');
    $list.empty();
    if (!files || !files.length) {
        return;
    }
    var k, file, icon, size;
    for (k = 0; k < files.length; k++) {
        file = files[k];
        icon = 'fa-file';
        if (/image\//i.test(file.type))              { icon = 'fa-file-image'; }
        else if (/pdf/i.test(file.type))             { icon = 'fa-file-pdf'; }
        else if (/word|doc/i.test(file.type))        { icon = 'fa-file-word'; }
        else if (/excel|sheet|xls/i.test(file.type)) { icon = 'fa-file-excel'; }
        else if (/zip|archive/i.test(file.type))     { icon = 'fa-file-archive'; }

        size = file.size < 1024 * 1024
            ? Math.round(file.size / 1024) + ' Ko'
            : (file.size / (1024 * 1024)).toFixed(1) + ' Mo';

        $list.append(
            '<li>'
            + '<i class="fas ' + icon + '"></i>'
            + '<span title="' + $('<span>').text(file.name).html() + '">' + $('<span>').text(file.name).html() + '</span>'
            + '<small style="color:#9ca3af;flex:0 0 auto">' + size + '</small>'
            + '</li>'
        );
    }
};

/**
 * Wire quick-create drawer events (called from ticketPickerKanban.init).
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketPickerKanban.initQuickCreate = function() {
    $(document).on('click', '.tac-quick-create-open', window.digiriskdolibarr.ticketPickerKanban.openQuickCreate);
    $(document).on('click', '.tac-quick-create-close', window.digiriskdolibarr.ticketPickerKanban.closeQuickCreate);
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#tacQuickCreate').hasClass('is-open')) {
            window.digiriskdolibarr.ticketPickerKanban.closeQuickCreate();
        }
    });
    $(document).on('submit', '#tacQuickCreateForm', window.digiriskdolibarr.ticketPickerKanban.submitQuickCreate);

    // File input change → update list.
    $(document).on('change', '#tacQcFiles', function() {
        window.digiriskdolibarr.ticketPickerKanban.renderFileList(this.files);
    });

    // Drag-and-drop on the dropzone.
    $(document).on('dragover dragenter', '#tacQcDropzone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('is-dragover');
    });
    $(document).on('dragleave dragend drop', '#tacQcDropzone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('is-dragover');
    });
    $(document).on('drop', '#tacQcDropzone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var dt    = e.originalEvent.dataTransfer;
        var files = dt && dt.files;
        if (!files || !files.length) {
            return;
        }
        // Assign dropped files to the real input via DataTransfer.
        try {
            var input = document.getElementById('tacQcFiles');
            var transfer = new DataTransfer();
            var j;
            for (j = 0; j < files.length; j++) { transfer.items.add(files[j]); }
            input.files = transfer.files;
            window.digiriskdolibarr.ticketPickerKanban.renderFileList(input.files);
        } catch {
            // DataTransfer API not available (old browser) — silently skip preview.
        }
    });

    // Keyboard activation of the dropzone (Enter/Space → click the hidden input).
    $(document).on('keydown', '#tacQcDropzone', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).find('#tacQcFiles').trigger('click');
        }
    });
};
