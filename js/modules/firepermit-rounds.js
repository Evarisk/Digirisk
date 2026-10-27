/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/firepermit-rounds.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript of the screens of the safety watcher (fire watch rounds after hot work), public or
 *          in the application: local times and countdowns, position of the phone, sending of the round.
 *          The photos (media block) and the signature pad are those of Saturne.
 */

'use strict';

/**
 * Init the firepermitrounds object and its mandatory "init" method.
 */
window.digiriskdolibarr.firepermitrounds = {};

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.firepermitrounds.init = function() {
    if (!$('.digirisk-firewatch').length) {
        return;
    }

    window.digiriskdolibarr.firepermitrounds.event();
    window.digiriskdolibarr.firepermitrounds.localizeTimes();
    window.digiriskdolibarr.firepermitrounds.refreshCountdowns();
    window.setInterval(window.digiriskdolibarr.firepermitrounds.refreshCountdowns, 30000);
    window.digiriskdolibarr.firepermitrounds.scheduleReload();

    if ($('.digirisk-firewatch__round-form').length) {
        window.digiriskdolibarr.firepermitrounds.locate();
    }
};

/**
 * Bind the events of the page.
 *
 * @return {void}
 */
window.digiriskdolibarr.firepermitrounds.event = function() {
    $(document).on('click', '.digirisk-firewatch__geoloc-retry', window.digiriskdolibarr.firepermitrounds.locate);
    $(document).on('submit', '.digirisk-firewatch__round-form', window.digiriskdolibarr.firepermitrounds.submitRound);
};

/**
 * Show the times in the timezone of the phone: nobody is logged in, so the server cannot know it.
 *
 * @return {void}
 */
window.digiriskdolibarr.firepermitrounds.localizeTimes = function() {
    $('[data-firewatch-time]').each(function() {
        var date    = new Date(parseInt($(this).data('firewatch-time'), 10) * 1000);
        var dayOnly = $(this).data('firewatch-format') === 'day';
        var isToday = date.toDateString() === new Date().toDateString();
        var time    = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        var day     = date.toLocaleDateString([], { day: '2-digit', month: '2-digit' });

        $(this).text(dayOnly ? day : (isToday ? time : day + ' ' + time));
    });
};

/**
 * Write how long until (or since) each planned round.
 *
 * @return {void}
 */
window.digiriskdolibarr.firepermitrounds.refreshCountdowns = function() {
    var root     = $('.digirisk-firewatch');
    var labelIn  = root.data('label-in') || '%s';
    var labelAgo = root.data('label-ago') || '%s';
    var now      = Date.now() / 1000;

    $('[data-firewatch-countdown]').each(function() {
        var diff = parseInt($(this).data('firewatch-countdown'), 10) - now;
        var text = window.digiriskdolibarr.firepermitrounds.formatDuration(Math.abs(diff));

        $(this).text('(' + (diff >= 0 ? labelIn : labelAgo).replace('%s', text) + ')');
    });
};

/**
 * Write a duration in seconds as minutes, or hours and minutes.
 *
 * @param  {number} seconds Duration
 * @return {string}         "12 min", "1 h 05"
 */
window.digiriskdolibarr.firepermitrounds.formatDuration = function(seconds) {
    var minutes = Math.round(seconds / 60);
    if (minutes < 60) {
        return minutes + ' min';
    }

    var rest = minutes % 60;
    return Math.floor(minutes / 60) + ' h' + (rest > 0 ? ' ' + (rest < 10 ? '0' : '') + rest : '');
};

/**
 * Reload the permit when its next round opens, unless the watcher is already filling a form.
 *
 * @return {void}
 */
window.digiriskdolibarr.firepermitrounds.scheduleReload = function() {
    var root     = $('.digirisk-firewatch');
    var reloadAt = parseInt(root.data('reload-at'), 10) || 0;
    var now      = parseInt(root.data('now'), 10) || 0;

    if (reloadAt <= 0 || now <= 0 || $('.digirisk-firewatch__round-form').length) {
        return;
    }

    // Server clock against server clock: the phone may be a few minutes off
    window.setTimeout(function() {
        window.location.reload();
    }, Math.max(reloadAt - now, 5) * 1000);
};

/**
 * Read the position of the phone, to prove the round was walked on site.
 *
 * @return {void}
 */
window.digiriskdolibarr.firepermitrounds.locate = function() {
    var block  = $('.digirisk-firewatch__geoloc');
    var status = block.find('.digirisk-firewatch__geoloc-status');

    if (!block.length) {
        return;
    }

    if (!navigator.geolocation) {
        status.addClass('is-error').html('<i class="fas fa-exclamation-triangle"></i> ').append(document.createTextNode(block.data('label-error')));
        return;
    }

    status.removeClass('is-error is-done').html('<i class="fas fa-satellite-dish"></i> ').append(document.createTextNode(block.data('label-pending')));

    navigator.geolocation.getCurrentPosition(function(position) {
        block.find('input[name="latitude"]').val(position.coords.latitude);
        block.find('input[name="longitude"]').val(position.coords.longitude);
        var accuracy = Math.round(position.coords.accuracy) + ' m';
        status.addClass('is-done').html('<i class="fas fa-map-marker-alt"></i> ').append(document.createTextNode(String(block.data('label-done')).replace('%s', accuracy)));
    }, function() {
        block.find('input[name="latitude"], input[name="longitude"]').val('');
        status.addClass('is-error').html('<i class="fas fa-exclamation-triangle"></i> ').append(document.createTextNode(block.data('label-error')));
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
};

/**
 * Put the signature in the form and check what the browser cannot check alone.
 *
 * @param  {Event} event Submit event
 * @return {void}
 */
window.digiriskdolibarr.firepermitrounds.submitRound = function(event) {
    var form   = $(this);
    // Pad started by window.saturne.signature on the .canvas-signature of the form
    var canvas = window.saturne && window.saturne.signature ? window.saturne.signature.canvas : null;
    var pad    = canvas ? canvas.signaturePad : null;
    var errors = [];

    // Level 2 = required (see DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED). The data URL travels as JSON,
    // the way the Saturne signature is sent everywhere else
    if (pad && !pad.isEmpty()) {
        form.find('input[name="signature"]').val(JSON.stringify(canvas.toDataURL()));
    } else {
        form.find('input[name="signature"]').val('');
        if (parseInt(form.data('signature-level'), 10) === 2) {
            errors.push(form.data('error-signature'));
        }
    }

    if (parseInt(form.data('geoloc-level'), 10) === 2 && !form.find('input[name="latitude"]').val()) {
        errors.push(form.data('error-geoloc'));
    }

    // The media block shows its gallery once a photo has reached the server
    if (parseInt(form.data('photo-level'), 10) === 2 && !form.find('.saturne-media-gallery .open-media-editor-as-gallery').length) {
        errors.push(form.data('error-photo'));
    }

    if (errors.length) {
        event.preventDefault();
        var errorBlock = form.find('.digirisk-firewatch__form-error').empty().removeClass('hidden');
        errors.forEach(function(message) {
            errorBlock.append($('<div>').text(message));
        });
        return;
    }

    form.find('.digirisk-firewatch__submit').prop('disabled', true);
};
