<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    view/frontend/pwa_firepermit_rounds.php
 * \ingroup digiriskdolibarr
 * \brief   PWA screen of the fire watch rounds after hot work: the fire permits to watch, then for
 *          one of them the end of the work and the rounds.
 *
 * Same screens as the public interface of the watcher (public/firepermit/firepermit_rounds.php),
 * inside the application: its header and navigation, and the logged in user as watcher.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/firepermit.class.php';
require_once __DIR__ . '/../../class/firepermitround.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_firepermitround.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_pwa.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['companies']);

// Get parameters
$action = GETPOST('action', 'aZ09');
$id     = GETPOSTINT('id');
$saved  = GETPOST('saved', 'aZ09');

// Security check: following the surveillance is reading the permit, walking a round is writing on it
$permissiontoread = $user->hasRight('digiriskdolibarr', 'firepermit', 'read');
$permissiontoadd  = $user->hasRight('digiriskdolibarr', 'firepermit', 'write');
saturne_check_access($permissiontoread);

$hookmanager->initHooks(['firepermitroundspwa']);

$object = new FirePermit($db);

$now    = dol_now();
$errors = [];

$listUrl   = $_SERVER['PHP_SELF'];
$permitUrl = function (int $permitId, string $savedState = '') use ($listUrl) {
    return $listUrl . '?id=' . $permitId . (dol_strlen($savedState) ? '&saved=' . $savedState : '');
};

$roundsPublic      = false;
$roundsCanRecord   = $permissiontoadd;
$roundsDefaultName = $user->getFullName($langs);

// Rounds are walked on signed permits only, as on the public interface
if ($id > 0) {
    if ($object->fetch($id) <= 0 || $object->status != FirePermit::STATUS_LOCKED) {
        $object = new FirePermit($db);
        $id     = 0;
    }
}

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($resHook < 0) {
    $errors = array_merge($errors, (array) $hookmanager->errors);
}

if (empty($resHook) && $permissiontoadd && $object->id > 0) {
    // Photos of the round, posted by the Saturne media block before the form is sent
    digiriskFirePermitRoundsMediaActions($object, (string) $action, $now);

    $actionResult = digiriskFirePermitRoundsDoActions($object, $user, (string) $action, $now);
    if (dol_strlen($actionResult['saved'])) {
        header('Location: ' . $permitUrl($object->id, $actionResult['saved']));
        exit;
    }
    $errors = array_merge($errors, $actionResult['errors']);
}

/*
 * View
 */

$title = $langs->trans('PwaNavFireWatch');
digiriskPwaHeader($title);

$pwaHeaderTitle = $title;
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php';

print '<div class="pwa-container digirisk-pwa">';
if ($object->id > 0) {
    list(
        'societyName'     => $societyName,
        'workLocation'    => $workLocation,
        'workTypes'       => $workTypes,
        'rounds'          => $rounds,
        'roundToRecord'   => $roundToRecord,
        'nextReloadAt'    => $nextReloadAt,
        'roundItemLevels' => $roundItemLevels,
    ) = digiriskFirePermitRoundsCardData($object, $now);

    require __DIR__ . '/../../core/tpl/firepermit/firepermit_rounds_public_card.tpl.php';
} else {
    $listRows = digiriskFirePermitRoundsListRows($now, $permitUrl);

    require __DIR__ . '/../../core/tpl/firepermit/firepermit_rounds_public_list.tpl.php';
}
print '</div>';

require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
