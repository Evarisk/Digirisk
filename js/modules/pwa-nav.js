/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/pwa-nav.js
 * \ingroup digiriskdolibarr
 * \brief   PWA bottom nav: burger drawer and per-user favorite items (star toggle, AJAX persistence).
 */

'use strict';

/**
 * Init the pwanav object and its mandatory "init" method.
 */
window.digiriskdolibarr.pwanav = {};

/**
 * Guard so the delegated events are attached exactly once (framework init OR the DOM-ready fallback).
 */
window.digiriskdolibarr.pwanav.bound = false;

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.pwanav.init = function() {
    window.digiriskdolibarr.pwanav.event();
};

/**
 * All the events of the PWA navigation.
 *
 * @return {void}
 */
window.digiriskdolibarr.pwanav.event = function() {
    if (window.digiriskdolibarr.pwanav.bound) {
        return;
    }
    window.digiriskdolibarr.pwanav.bound = true;
    $(document).on('click', '[data-action="toggle-pwa-nav-drawer"]', window.digiriskdolibarr.pwanav.toggleDrawer);
    $(document).on('click', '[data-action="close-pwa-nav-drawer"]', window.digiriskdolibarr.pwanav.closeDrawer);
    $(document).on('click', '[data-action="toggle-pwa-nav-favorite"]', window.digiriskdolibarr.pwanav.toggleFavorite);
};

/**
 * Open or close the burger drawer.
 *
 * @param  {Event} event Click event
 * @return {void}
 */
window.digiriskdolibarr.pwanav.toggleDrawer = function(event) {
    event.preventDefault();
    window.digiriskdolibarr.pwanav.setDrawerOpen(!$('.digirisk-pwa-nav-drawer').hasClass('is-open'));
};

/**
 * Close the burger drawer.
 *
 * @param  {Event} event Click event
 * @return {void}
 */
window.digiriskdolibarr.pwanav.closeDrawer = function(event) {
    event.preventDefault();
    window.digiriskdolibarr.pwanav.setDrawerOpen(false);
};

/**
 * Apply the drawer open/closed state.
 *
 * @param  {boolean} open True to open, false to close
 * @return {void}
 */
window.digiriskdolibarr.pwanav.setDrawerOpen = function(open) {
    $('.digirisk-pwa-nav-drawer').toggleClass('is-open', open);
    $('.digirisk-pwa-nav-overlay').toggleClass('is-open', open);
    $('.digirisk-pwa-nav__burger').toggleClass('active', open).attr('aria-expanded', open ? 'true' : 'false');
};

/**
 * Toggle an item as favorite from its star, persist it, then re-render the bar without reloading.
 *
 * @param  {Event} event Click event
 * @return {void}
 */
window.digiriskdolibarr.pwanav.toggleFavorite = function(event) {
    event.preventDefault();
    event.stopPropagation();

    var button = $(this);
    var item   = button.closest('.digirisk-pwa-nav-drawer__item');
    var drawer = $('.digirisk-pwa-nav-drawer');
    var slug   = item.attr('data-nav-slug');
    var max    = parseInt(drawer.attr('data-max-favorites'), 10) || 5;
    var adding = !button.hasClass('is-favorite');

    // Rebuild the whole favorites list from the drawer (canonical DOM order) with the toggle applied
    var favorites = [];
    drawer.find('.digirisk-pwa-nav-drawer__item').each(function() {
        var itemSlug   = $(this).attr('data-nav-slug');
        var isFavorite = $(this).find('.digirisk-pwa-nav-drawer__star').hasClass('is-favorite');
        if (itemSlug === slug) {
            isFavorite = adding;
        }
        if (itemSlug && isFavorite) {
            favorites.push(itemSlug);
        }
    });

    // Refuse silently past the bar capacity, but say so visually
    if (adding && favorites.length > max) {
        drawer.find('.digirisk-pwa-nav-drawer__hint').addClass('limit-reached');
        setTimeout(function() {
            drawer.find('.digirisk-pwa-nav-drawer__hint').removeClass('limit-reached');
        }, 1200);
        return;
    }

    var token = (window.saturne && window.saturne.toolbox) ? window.saturne.toolbox.getToken() : '';

    button.prop('disabled', true);
    $.ajax({
        url: drawer.attr('data-ajax-url'),
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'save',
            token: token,
            favorites: favorites.join(',')
        }
    }).done(function(resp) {
        if (resp && resp.success) {
            button.toggleClass('is-favorite', adding);
            button.attr('aria-pressed', adding ? 'true' : 'false');
            button.attr('aria-label', adding ? drawer.data('remove-favorite-label') : drawer.data('add-favorite-label'));
            window.digiriskdolibarr.pwanav.renderBar();
        }
    }).always(function() {
        button.prop('disabled', false);
    });
};

/**
 * Rebuild the bottom bar favorites from the drawer state.
 *
 * @return {void}
 */
window.digiriskdolibarr.pwanav.renderBar = function() {
    var bar = $('.digirisk-pwa-nav__favorites');
    if (!bar.length) {
        return;
    }

    bar.empty();
    $('.digirisk-pwa-nav-drawer .digirisk-pwa-nav-drawer__item').each(function() {
        if (!$(this).find('.digirisk-pwa-nav-drawer__star').hasClass('is-favorite')) {
            return;
        }
        var link    = $(this).find('.digirisk-pwa-nav-drawer__link');
        var navItem = $('<a>', {
            href: link.attr('href'),
            'class': 'digirisk-pwa-nav__item' + ($(this).hasClass('active') ? ' active' : ''),
            'data-nav-slug': $(this).attr('data-nav-slug')
        });
        navItem.append(link.find('i').clone());
        navItem.append($('<span>').text(link.find('span').text()));
        bar.append(navItem);
    });
};

// Robust fallback: bind on DOM ready, independently of the DigiriskDolibarr load_list_script chain
// (which has no try/catch, so another module's error could otherwise leave the drawer dead).
// The `bound` guard in event() ensures the handlers are attached exactly once.
$(function() {
    if ($('.digirisk-pwa-nav').length) {
        window.digiriskdolibarr.pwanav.init();
    }
});
