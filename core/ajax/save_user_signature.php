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
 * \file    core/ajax/save_user_signature.php
 * \ingroup digiriskdolibarr
 * \brief   AJAX endpoint to save/reset the reusable signature of the current user (stored as an entity const).
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';

global $db, $user;

header('Content-Type: application/json');

if (empty($user->id)) {
    echo json_encode(['success' => false, 'error' => 'NotLoggedIn']);
    exit;
}

if (!$user->hasRight('digiriskdolibarr', 'mobilepreventionplan', 'write')) {
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$action = GETPOST('action', 'aZ09');

if ($action === 'save') {
    // Read the base64 data URL from the raw body to avoid GETPOST sanitizing the payload.
    $data      = json_decode(file_get_contents('php://input'), true);
    $signature = (is_array($data) && isset($data['signature'])) ? $data['signature'] : '';

    if (!digiriskIsValidSignature($signature)) {
        echo json_encode(['success' => false, 'error' => 'InvalidSignature']);
        exit;
    }

    // Save into the standard "ElectronicSignature" (UserSignature) record for the current user.
    $result = digiriskSaveUserElectronicSignature($db, $user, $user->id, $signature);
    echo json_encode($result > 0 ? ['success' => true] : ['success' => false, 'error' => 'SaveFailed']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'UnknownAction']);
