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
 * \file    lib/digiriskdolibarr_pwa_nav.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library functions for the PWA bottom navigation (items definition + per-user favorites).
 */

// Maximum number of favorite items displayed in the bottom bar
if (!defined('DIGIRISKDOLIBARR_PWA_NAV_MAX_FAVORITES')) {
    define('DIGIRISKDOLIBARR_PWA_NAV_MAX_FAVORITES', 5);
}

/**
 * Return the canonical list of PWA bottom nav items the connected user may reach.
 *
 * Keys are stable slugs persisted in the user conf (DIGIRISKDOLIBARR_PWA_NAV_FAVORITES),
 * array order is the display order in both the favorites bar and the burger drawer.
 * Items the user has no permission for are dropped, so a slug kept in an old favorites
 * list simply disappears instead of showing a link leading to an access denied page.
 *
 * Navigation only: creating a prevention plan or a fire permit is reached from the "+"
 * button of the matching list, which is the single entry point to the creation screens.
 *
 * @param  User $user User whose permissions filter the list
 * @return array<string,array{url:string,page:string,icon:string,label:string}>
 */
function digiriskPwaNavGetItems(User $user): array
{
    global $langs;

    $frontendBase = dol_buildpath('/custom/digiriskdolibarr/view/frontend/', 1);
    $items        = [];

    $items['home'] = [
        'url'   => $frontendBase . 'pwa_home.php',
        'page'  => 'pwa_home.php',
        'icon'  => 'fa-home',
        'label' => $langs->transnoentities('PwaNavHome'),
    ];

    if ($user->hasRight('digiriskdolibarr', 'preventionplan', 'read')) {
        $items['preventionplans'] = [
            'url'   => $frontendBase . 'pwa_preventionplan_list.php',
            'page'  => 'pwa_preventionplan_list.php',
            'icon'  => 'fa-file-signature',
            'label' => $langs->transnoentities('PwaNavPreventionPlans'),
        ];
    }

    if ($user->hasRight('digiriskdolibarr', 'firepermit', 'read')) {
        $items['firepermits'] = [
            'url'   => $frontendBase . 'pwa_firepermit_list.php',
            'page'  => 'pwa_firepermit_list.php',
            'icon'  => 'fa-fire-alt',
            'label' => $langs->transnoentities('PwaNavFirePermits'),
        ];
    }

    return $items;
}

/**
 * Return the user's favorite nav slugs, in canonical item order.
 *
 * Stored in the user personal conf (llx_user_param) under key
 * DIGIRISKDOLIBARR_PWA_NAV_FAVORITES as a CSV of slugs. The literal value 'none'
 * means "no favorite at all": an empty value would make dol_set_user_param
 * delete the param and silently bring the defaults back.
 *
 * @param  User     $user  User to read the personal conf from
 * @param  array    $items Available items, already filtered by permission
 * @return string[]        Favorite slugs (possibly empty)
 */
function digiriskPwaNavGetFavorites(User $user, array $items): array
{
    $raw = isset($user->conf->DIGIRISKDOLIBARR_PWA_NAV_FAVORITES) ? trim((string) $user->conf->DIGIRISKDOLIBARR_PWA_NAV_FAVORITES) : '';

    if ($raw === '') {
        // No choice made yet: everything the user may reach, capped at the bar capacity
        return array_slice(array_keys($items), 0, DIGIRISKDOLIBARR_PWA_NAV_MAX_FAVORITES);
    }
    if ($raw === 'none') {
        return [];
    }

    // Keep only slugs that still exist and are allowed, in canonical order
    $wanted    = array_map('trim', explode(',', $raw));
    $favorites = array_values(array_intersect(array_keys($items), $wanted));

    return array_slice($favorites, 0, DIGIRISKDOLIBARR_PWA_NAV_MAX_FAVORITES);
}
