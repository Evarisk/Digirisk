<?php
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
 * \file    core/ajax/mobile_firepermit_signature_state.php
 * \ingroup digiriskdolibarr
 * \brief   AJAX endpoint returning the signature state of a fire permit, so the mobile success
 *          screen can notice a signature given elsewhere (phone reached through the QR code, mail
 *          link opened on another device) without the user having to reload the page by hand.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once __DIR__ . '/../../class/firepermit.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';

global $db, $user;

header('Content-Type: application/json');

if (empty($user->id)) {
    echo json_encode(['success' => false, 'error' => 'NotLoggedIn']);
    exit;
}

if (!$user->hasRight('digiriskdolibarr', 'firepermit', 'read')) {
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$permitId = GETPOSTINT('id');
if ($permitId <= 0) {
    echo json_encode(['success' => false, 'error' => 'NotFound']);
    exit;
}

$firePermit = new FirePermit($db);
if ($firePermit->fetch($permitId) <= 0) {
    echo json_encode(['success' => false, 'error' => 'NotFound']);
    exit;
}

echo json_encode(['success' => true] + digiriskMobileGetSignatureState($db, $firePermit));
exit;
