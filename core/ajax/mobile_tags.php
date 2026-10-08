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
 * \file    core/ajax/mobile_tags.php
 * \ingroup digiriskdolibarr
 * \brief   AJAX endpoint listing the prevention plan or fire permit tags (?object_type=), so the mobile
 *          form can offer a tag created in another tab as soon as the user comes back, without
 *          reloading the page and losing what was already typed.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

global $db, $user;

header('Content-Type: application/json');

if (empty($user->id)) {
    echo json_encode(['success' => false, 'error' => 'NotLoggedIn']);
    exit;
}

// Category type registered by the constructCategory hook for each object
$tagTypes = [
    'preventionplan' => 'digiriskpreventionplan',
    'firepermit'     => 'digiriskfirepermit',
];

$objectType = GETPOST('object_type', 'aZ09');
if (!isset($tagTypes[$objectType])) {
    echo json_encode(['success' => false, 'error' => 'BadObjectType']);
    exit;
}

if (!$user->hasRight('digiriskdolibarr', $objectType, 'write')) {
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$tags = [];
if (isModEnabled('categorie')) {
    // Same call as the form, so both list the tags with the same labels
    $form       = new Form($db);
    $tagOptions = $form->select_all_categories($tagTypes[$objectType], '', 'parent', 64, 0, 1);

    foreach ((is_array($tagOptions) ? $tagOptions : []) as $tagId => $tagLabel) {
        $tags[] = ['id' => (int) $tagId, 'label' => (string) $tagLabel];
    }
}

echo json_encode(['success' => true, 'tags' => $tags]);
exit;
