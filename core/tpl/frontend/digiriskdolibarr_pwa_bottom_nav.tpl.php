<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Bottom navigation bar of the PWA: per-user favorites + burger drawer.
 *          Expects: $langs, $user.
 */

require_once __DIR__ . '/../../../lib/digiriskdolibarr_pwa_nav.lib.php';

global $langs, $user;

$navItems     = digiriskPwaNavGetItems($user);
$navFavorites = digiriskPwaNavGetFavorites($user, $navItems);

// The active tab is the page currently being displayed
$navCurrentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="digirisk-pwa-nav">
    <div class="digirisk-pwa-nav__favorites">
        <?php foreach ($navFavorites as $navSlug) {
            $navItem = $navItems[$navSlug]; ?>
        <a href="<?php print dol_escape_htmltag($navItem['url']); ?>" class="digirisk-pwa-nav__item<?php print ($navCurrentPage == $navItem['page']) ? ' active' : ''; ?>" data-nav-slug="<?php print dol_escape_htmltag($navSlug); ?>">
            <i class="fas <?php print dol_escape_htmltag($navItem['icon']); ?>"></i>
            <span><?php print dol_escape_htmltag($navItem['label']); ?></span>
        </a>
        <?php } ?>
    </div>

    <button type="button" class="digirisk-pwa-nav__item digirisk-pwa-nav__burger" data-action="toggle-pwa-nav-drawer" aria-expanded="false" aria-label="<?php print dol_escape_htmltag($langs->trans('PwaNavMenu')); ?>">
        <i class="fas fa-bars"></i>
        <span><?php print $langs->trans('PwaNavMenu'); ?></span>
    </button>
</nav>

<div class="digirisk-pwa-nav-overlay" data-action="close-pwa-nav-drawer"></div>
<div class="digirisk-pwa-nav-drawer"
     data-ajax-url="<?php print dol_escape_htmltag(dol_buildpath('/custom/digiriskdolibarr/core/ajax/save_pwa_nav_favorites.php', 1)); ?>"
     data-max-favorites="<?php print DIGIRISKDOLIBARR_PWA_NAV_MAX_FAVORITES; ?>"
     data-add-favorite-label="<?php print dol_escape_htmltag($langs->trans('PwaNavAddFavorite')); ?>"
     data-remove-favorite-label="<?php print dol_escape_htmltag($langs->trans('PwaNavRemoveFavorite')); ?>">
    <div class="digirisk-pwa-nav-drawer__header">
        <span class="digirisk-pwa-nav-drawer__title"><?php print $langs->trans('PwaNavMenu'); ?></span>
        <span class="digirisk-pwa-nav-drawer__hint"><i class="fas fa-star"></i> <?php print $langs->trans('PwaNavFavoritesHint', DIGIRISKDOLIBARR_PWA_NAV_MAX_FAVORITES); ?></span>
    </div>
    <div class="digirisk-pwa-nav-drawer__grid">
        <?php foreach ($navItems as $navSlug => $navItem) {
            $isNavFavorite = in_array($navSlug, $navFavorites, true); ?>
        <div class="digirisk-pwa-nav-drawer__item<?php print ($navCurrentPage == $navItem['page']) ? ' active' : ''; ?>" data-nav-slug="<?php print dol_escape_htmltag($navSlug); ?>">
            <a href="<?php print dol_escape_htmltag($navItem['url']); ?>" class="digirisk-pwa-nav-drawer__link">
                <i class="fas <?php print dol_escape_htmltag($navItem['icon']); ?>"></i>
                <span><?php print dol_escape_htmltag($navItem['label']); ?></span>
            </a>
            <button type="button" class="digirisk-pwa-nav-drawer__star<?php print $isNavFavorite ? ' is-favorite' : ''; ?>" data-action="toggle-pwa-nav-favorite" aria-pressed="<?php print $isNavFavorite ? 'true' : 'false'; ?>" aria-label="<?php print dol_escape_htmltag($isNavFavorite ? $langs->trans('PwaNavRemoveFavorite') : $langs->trans('PwaNavAddFavorite')); ?>">
                <i class="fas fa-star"></i>
            </button>
        </div>
        <?php } ?>
    </div>
</div>
