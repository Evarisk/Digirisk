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
 * \file    js/modules/pwa-install.js
 * \ingroup digiriskdolibarr
 * \brief   "Install the application" button of the PWA.
 *          Chromium browsers (Android Chrome, Edge, Samsung Internet, desktop Chrome/Edge) hand over
 *          their install prompt through the beforeinstallprompt event: the button opens it directly.
 *          Elsewhere (iPhone, Firefox) no API exists, the button opens a step by step help instead.
 */

'use strict';

/**
 * Init the pwainstall object and its mandatory "init" method.
 */
window.digiriskdolibarr.pwainstall = {};

/**
 * Install prompt kept from the beforeinstallprompt event, null while the browser offers none.
 */
window.digiriskdolibarr.pwainstall.deferredPrompt = null;

/**
 * Guard so the delegated events are attached exactly once (framework init OR the DOM-ready fallback).
 */
window.digiriskdolibarr.pwainstall.bound = false;

/**
 * Session storage key remembering the banner was closed.
 */
window.digiriskdolibarr.pwainstall.dismissKey = 'digirisk-pwa-install-dismissed';

// Listened at script load, not in init(): the browser may fire it before the DOM is ready
window.addEventListener('beforeinstallprompt', function(event) {
    window.digiriskdolibarr.pwainstall.deferredPrompt = event;
    // Our button replaces the browser's own install banner, only on the screens offering it
    if (document.querySelector('.digirisk-pwa-install')) {
        event.preventDefault();
    }
});

window.addEventListener('appinstalled', function() {
    window.digiriskdolibarr.pwainstall.deferredPrompt = null;
    $('.digirisk-pwa-install').removeClass('is-visible');
});

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.pwainstall.init = function() {
    if (!$('.digirisk-pwa-install').length) {
        return;
    }

    window.digiriskdolibarr.pwainstall.event();

    if (!window.digiriskdolibarr.pwainstall.isStandalone() && !window.digiriskdolibarr.pwainstall.isDismissed()) {
        $('.digirisk-pwa-install').addClass('is-visible');
    }
};

/**
 * All the events of the install banner.
 *
 * @return {void}
 */
window.digiriskdolibarr.pwainstall.event = function() {
    if (window.digiriskdolibarr.pwainstall.bound) {
        return;
    }
    window.digiriskdolibarr.pwainstall.bound = true;
    $(document).on('click', '[data-action="pwa-install"]', window.digiriskdolibarr.pwainstall.install);
    $(document).on('click', '[data-action="pwa-install-dismiss"]', window.digiriskdolibarr.pwainstall.dismiss);
    $(document).on('click', '[data-action="pwa-install-help-close"]', window.digiriskdolibarr.pwainstall.closeHelp);
};

/**
 * Whether the page already runs as the installed application.
 *
 * @return {boolean}
 */
window.digiriskdolibarr.pwainstall.isStandalone = function() {
    // display_override of the manifest asks for window-controls-overlay, granted by desktop browsers
    return ['standalone', 'window-controls-overlay', 'fullscreen', 'minimal-ui'].some(function(mode) {
        return window.matchMedia('(display-mode: ' + mode + ')').matches;
    }) || window.navigator.standalone === true;
};

/**
 * Whether the device runs iOS, where every browser relies on Safari's "Add to Home Screen".
 *
 * @return {boolean}
 */
window.digiriskdolibarr.pwainstall.isIos = function() {
    // iPadOS reports itself as a Mac: only its touch screen tells it apart
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent) || (/macintosh/i.test(window.navigator.userAgent) && window.navigator.maxTouchPoints > 1);
};

/**
 * Whether the banner was closed during this browsing session.
 *
 * @return {boolean}
 */
window.digiriskdolibarr.pwainstall.isDismissed = function() {
    try {
        return window.sessionStorage.getItem(window.digiriskdolibarr.pwainstall.dismissKey) === '1';
    } catch (error) {
        return false;
    }
};

/**
 * Open the browser install prompt, or the help when the browser offers none.
 *
 * @param  {Event} event Click event
 * @return {void}
 */
window.digiriskdolibarr.pwainstall.install = function(event) {
    event.preventDefault();

    var deferredPrompt = window.digiriskdolibarr.pwainstall.deferredPrompt;
    if (!deferredPrompt) {
        window.digiriskdolibarr.pwainstall.openHelp();
        return;
    }

    // A prompt opens only once: after a refusal, the browser fires a new event when it allows another one
    window.digiriskdolibarr.pwainstall.deferredPrompt = null;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(function(choice) {
        if (choice.outcome === 'accepted') {
            $('.digirisk-pwa-install').removeClass('is-visible');
        }
    });
};

/**
 * Hide the banner until the end of the browsing session.
 *
 * @param  {Event} event Click event
 * @return {void}
 */
window.digiriskdolibarr.pwainstall.dismiss = function(event) {
    event.preventDefault();
    $('.digirisk-pwa-install').removeClass('is-visible');
    try {
        window.sessionStorage.setItem(window.digiriskdolibarr.pwainstall.dismissKey, '1');
    } catch (error) {
        // Storage refused (private browsing): the banner simply comes back on the next page
    }
};

/**
 * Open the step by step help matching the device.
 *
 * @return {void}
 */
window.digiriskdolibarr.pwainstall.openHelp = function() {
    $('.digirisk-pwa-install-help').attr('data-platform', window.digiriskdolibarr.pwainstall.isIos() ? 'ios' : 'other').addClass('is-open');
    $('.digirisk-pwa-install-overlay').addClass('is-open');
};

/**
 * Close the help.
 *
 * @param  {Event} event Click event
 * @return {void}
 */
window.digiriskdolibarr.pwainstall.closeHelp = function(event) {
    event.preventDefault();
    $('.digirisk-pwa-install-help, .digirisk-pwa-install-overlay').removeClass('is-open');
};

// Robust fallback: bind on DOM ready, independently of the DigiriskDolibarr load_list_script chain
// (which has no try/catch, so another module's error could otherwise leave the button dead).
// The `bound` guard in event() ensures the handlers are attached exactly once.
$(function() {
    window.digiriskdolibarr.pwainstall.init();
});
