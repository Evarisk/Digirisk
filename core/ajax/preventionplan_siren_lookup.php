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
 * \file    core/ajax/preventionplan_siren_lookup.php
 * \ingroup digiriskdolibarr
 * \brief   AJAX endpoint to look up an existing third party by SIREN (idprof1) or SIRET (idprof2)
 *          in the current entity.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

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

// Keep only digits: matches numbers stored with or without spaces/dots.
$idProfClean = digiriskMobileCleanIdProf(GETPOST('siren', 'alphanohtml'));

if (!digiriskMobileIsValidIdProf($idProfClean)) {
    echo json_encode(['success' => false, 'error' => 'InvalidSiren']);
    exit;
}

// A SIRET starts with the SIREN of its company, so comparing the first 9 digits on both columns
// matches whichever of the two the user typed and whichever of the two the company has on file.
$sirenPart = substr($idProfClean, 0, 9);

$sql  = 'SELECT rowid, nom, email, siren, siret FROM ' . MAIN_DB_PREFIX . 'societe';
$sql .= ' WHERE entity IN (' . getEntity('societe') . ')';
$sql .= " AND (REPLACE(REPLACE(REPLACE(siren, ' ', ''), '.', ''), '-', '') = '" . $db->escape($sirenPart) . "'";
$sql .= "  OR LEFT(REPLACE(REPLACE(REPLACE(siret, ' ', ''), '.', ''), '-', ''), 9) = '" . $db->escape($sirenPart) . "')";
$sql .= ' ORDER BY rowid ASC';

$resql = $db->query($sql);
if (!$resql) {
    echo json_encode(['success' => false, 'error' => 'SqlError']);
    exit;
}

if (!$db->num_rows($resql)) {
    echo json_encode(['success' => true, 'found' => false]);
    exit;
}

$obj = $db->fetch_object($resql);

// List the external contacts of the matched company so the user can pick a responsible.
$contacts    = [];
$societe     = new Societe($db);
$societe->fetch((int) $obj->rowid);
$contactList = $societe->contact_array_objects();
if (is_array($contactList)) {
    foreach ($contactList as $contact) {
        $contacts[] = [
            'id'        => (int) $contact->id,
            'firstname' => $contact->firstname,
            'lastname'  => $contact->lastname,
            'email'     => $contact->email,
        ];
    }
}

echo json_encode([
    'success'  => true,
    'found'    => true,
    'societe'  => [
        'id'    => (int) $obj->rowid,
        'name'  => $obj->nom,
        'email' => $obj->email,
        'siren' => $obj->siren,
        'siret' => $obj->siret,
    ],
    'contacts' => $contacts,
]);
exit;
