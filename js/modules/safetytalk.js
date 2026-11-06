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
 * \file    js/modules/safetytalk.js
 * \ingroup digiriskdolibarr
 * \brief   Safety talks of the mobile application: theme tiles, people of a cycle, and around the media
 *          block of Saturne, the size of a file checked before sending and the content of the spread refreshed.
 */

'use strict';

/**
 * Init the safetytalk object and its mandatory "init" method.
 */
window.digiriskdolibarr.safetytalk = {};

/**
 * Guard so the delegated events are attached exactly once.
 */
window.digiriskdolibarr.safetytalk.bound = false;

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.init = function() {
    window.digiriskdolibarr.safetytalk.event();
};

/**
 * All the events of the safety talk screens.
 *
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.event = function() {
    if (window.digiriskdolibarr.safetytalk.bound) {
        return;
    }
    window.digiriskdolibarr.safetytalk.bound = true;

    $(document).on('click', '.digirisk-safetytalk-theme', window.digiriskdolibarr.safetytalk.selectTheme);
    $(document).on('click', '.digirisk-safetytalk-guest-add', window.digiriskdolibarr.safetytalk.addGuest);
    $(document).on('click', '.digirisk-safetytalk-guest-remove', window.digiriskdolibarr.safetytalk.removeGuest);
    $(document).on('input focusout', '.digirisk-safetytalk-email', window.digiriskdolibarr.safetytalk.checkEmail);
    $(document).on('submit', '.digirisk-safetytalk-form, .digirisk-safetytalk-person-form', window.digiriskdolibarr.safetytalk.checkEmails);
    $(document).on('submit', '.digirisk-safetytalk-confirm', window.digiriskdolibarr.safetytalk.confirm);
    $(document).ajaxComplete(window.digiriskdolibarr.safetytalk.refreshMedia);
    // Before the media block, which listens on the document: a refused file must not reach it
    window.addEventListener('change', window.digiriskdolibarr.safetytalk.checkFileSize, true);
};

/**
 * Pick the theme of the talk. The title follows the theme as long as it was not typed by hand.
 *
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.selectTheme = function() {
    const tile  = $(this);
    const form  = tile.closest('form');
    const label = form.find('.digirisk-safetytalk-label');
    const previousName = form.find('.digirisk-safetytalk-theme.active').data('name') || '';

    form.find('.digirisk-safetytalk-theme').removeClass('active');
    tile.addClass('active');
    form.find('.digirisk-safetytalk-theme-input').val(tile.data('theme'));

    if (label.val().trim() === '' || label.val() === previousName) {
        label.val(tile.data('name') || '');
    }
};

/**
 * Add a row for a person without Dolibarr account in the form of a cycle, and put the cursor in it.
 *
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.addGuest = function() {
    const block = $(this).closest('.digirisk-safetytalk-guests');
    const row   = $(block.find('.digirisk-safetytalk-guest-template').html());

    block.find('.digirisk-safetytalk-guests__list').append(row);
    row.find('input').first().trigger('focus');
};

/**
 * Take a person without Dolibarr account out of the form of a cycle.
 *
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.removeGuest = function() {
    $(this).closest('.digirisk-safetytalk-guest').remove();
};

/**
 * Whether an email is one the server takes (isValidEmail(), the FILTER_VALIDATE_EMAIL of PHP): words split
 * by single dots before the @, then a domain of two labels at least, none starting or ending with a dash,
 * the last one starting with a letter.
 *
 * @param  {string}  email Email, trimmed
 * @return {boolean}
 */
window.digiriskdolibarr.safetytalk.isEmail = function(email) {
    return email.length <= 254 && /^[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*@([A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z]([A-Za-z0-9-]*[A-Za-z0-9])?$/.test(email);
};

/**
 * Show on an email field whether it is right or wrong: right as soon as it is, wrong once the domain is
 * being typed or the field was left. Left empty, it stays neutral, the email is optional.
 *
 * @param  {Event} event Input or focusout event
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.checkEmail = function(event) {
    const input = $(this);
    const email = input.val().trim();
    // A flag in the DOM: jQuery data may be gone when the field is left
    if (event.type === 'focusout' && email !== '') {
        input.attr('data-touched', '1');
    }

    let state = '';
    if (email !== '') {
        if (window.digiriskdolibarr.safetytalk.isEmail(email)) {
            state = 'valid';
        } else if (input.attr('data-touched') || email.indexOf('@') !== -1) {
            state = 'invalid';
        }
    }
    window.digiriskdolibarr.safetytalk.markEmail(input, state);
};

/**
 * State of an email field: its colour and its hint.
 *
 * @param  {jQuery} input Email field
 * @param  {string} state 'valid', 'invalid', or '' for neutral
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.markEmail = function(input, state) {
    input.toggleClass('digirisk-safetytalk-email--valid', state === 'valid');
    input.toggleClass('digirisk-safetytalk-email--invalid', state === 'invalid');
    input.attr('aria-invalid', state === 'invalid' ? 'true' : null);
    input.closest('.digirisk-safetytalk-guest').find('.digirisk-safetytalk-guest__hint').toggleClass('hidden', state !== 'invalid');
};

/**
 * Keep a form with a wrong email on the screen: every wrong one shown, the cursor in the first.
 *
 * @param  {Event} event Submit event
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.checkEmails = function(event) {
    const wrong = $(this).find('.digirisk-safetytalk-email').filter(function() {
        const email = this.value.trim();
        return email !== '' && !window.digiriskdolibarr.safetytalk.isEmail(email);
    });
    if (!wrong.length) {
        return;
    }

    event.preventDefault();
    wrong.each(function() {
        $(this).attr('data-touched', '1');
        window.digiriskdolibarr.safetytalk.markEmail($(this), 'invalid');
    });
    wrong.first().trigger('focus');
};

/**
 * Refuse a video or a document bigger than the server takes, before the media block sends it: the request
 * would reach the server empty and the file vanish without a word. Photos go through the editor, which
 * makes them lighter.
 *
 * @param  {Event} event Change event, caught before the media block
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.checkFileSize = function(event) {
    const input = $(event.target);
    const media = input.closest('.digirisk-safetytalk-media');
    if (!input.hasClass('saturne-file-upload') || !media.length) {
        return;
    }

    const maxSize = parseInt(media.data('max-upload'), 10) || 0;
    const tooBig  = Array.from(event.target.files || []).filter(function(file) {
        return maxSize > 0 && file.size > maxSize;
    });
    if (tooBig.length > 0) {
        event.stopPropagation();
        // Picking the same file again must fire the event again
        event.target.value = '';
        $.jnotify(tooBig.map(function(file) {
            return file.name;
        }).join(', ') + ' : ' + media.data('too-big-label'), 'error', true);
    }
};

/**
 * Once the media block has changed a talk, put in place what it does not refresh itself: the content of the
 * spread, and the files of the block after a photo, as the files list every one of them.
 *
 * @param  {Event}  event    AJAX complete event
 * @param  {Object} xhr      Request
 * @param  {Object} settings Settings of the request
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.refreshMedia = function(event, xhr, settings) {
    const match = /[?&]action=(uploadPhoto|deletePhoto|uploadFile|deleteFile)(&|$)/.exec(settings.url || '');
    if (!match || !$('.digirisk-safetytalk-media').length || !xhr.responseText) {
        return;
    }

    const response = $('<div>').html(xhr.responseText);
    const ids      = ['safetytalk-documents-list'];
    if (match[1] === 'uploadPhoto' || match[1] === 'deletePhoto') {
        $('.digirisk-safetytalk-media').find('.linked-medias[id$="master-media-row-container-file"], .saturne-files-modal').each(function() {
            ids.push(this.id);
        });
    }

    ids.forEach(function(id) {
        const current = $('#' + id);
        const updated = response.find('#' + id);
        if (current.length && updated.length) {
            const wasActive = current.hasClass('modal-active');
            current.replaceWith(updated);
            updated.toggleClass('modal-active', wasActive);
        }
    });

    // What the server refused comes back with the block, rather than on the next page
    (response.find('#safetytalk-media-errors').data('errors') || []).forEach(function(error) {
        $.jnotify($('<div>').text(error).html(), 'error', true);
    });
};

/**
 * Ask before a removal, there is no way back.
 *
 * @param  {Event} event Submit event
 * @return {void}
 */
window.digiriskdolibarr.safetytalk.confirm = function(event) {
    if (!window.confirm($(this).data('confirm'))) {
        event.preventDefault();
    }
};
