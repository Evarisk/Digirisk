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
 * \file    core/ajax/save_pwa_nav_favorites.php
 * \ingroup digiriskdolibarr
 * \brief   AJAX endpoint to save/reset the per-user PWA bottom nav favorites (stored in llx_user_param).
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_pwa_nav.lib.php';

header('Content-Type: application/json');

global $conf, $db, $user;

if (empty($user->id)) {
    echo json_encode(['success' => false, 'error' => 'NotLoggedIn']);
    exit;
}

$action = GETPOST('action', 'aZ09');
$key    = 'DIGIRISKDOLIBARR_PWA_NAV_FAVORITES';

if ($action === 'save') {
    // CSV of slugs, validated against the items the user may actually reach
    $raw       = GETPOST('favorites', 'aZ09comma');
    $wanted    = array_filter(array_map('trim', explode(',', $raw)));
    $favorites = array_values(array_intersect(array_keys(digiriskPwaNavGetItems($user)), $wanted));

    if (count($favorites) > DIGIRISKDOLIBARR_PWA_NAV_MAX_FAVORITES) {
        echo json_encode(['success' => false, 'error' => 'TooManyFavorites']);
        exit;
    }

    // 'none' sentinel: an empty value would delete the param and restore the defaults
    $value = empty($favorites) ? 'none' : implode(',', $favorites);

    $res = dol_set_user_param($db, $conf, $user, [$key => $value]);
    echo json_encode($res > 0 ? ['success' => true, 'favorites' => $favorites] : ['success' => false, 'error' => 'SaveFailed']);
    exit;
}

if ($action === 'reset') {
    // An empty value makes dol_set_user_param delete the parameter
    $res = dol_set_user_param($db, $conf, $user, [$key => '']);
    echo json_encode($res > 0 ? ['success' => true] : ['success' => false, 'error' => 'ResetFailed']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'UnknownAction']);
