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
 * \file    public/firepermit/firepermit_rounds.php
 * \ingroup digiriskdolibarr
 * \brief   Public mobile interface of the safety watcher: fire permits to watch, end of the hot work
 *          and fire watch rounds (comment, photo, position, signature).
 *
 * The watcher is often a guard of an outside company with no Dolibarr account: the page is reached
 * through a link carrying a secret key, set up in the fire permit configuration. A user of the
 * application reaches the same screens from view/frontend/pwa_firepermit_rounds.php.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOLOGIN'))        define('NOLOGIN', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (!defined('NOIPCHECK'))      define('NOIPCHECK', '1');
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

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

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'aZ09');
$id     = GETPOSTINT('id');
$key    = GETPOST('key', 'alphanohtml');
$saved  = GETPOST('saved', 'aZ09');
$entity = !isModEnabled('multicompany') ? $conf->entity : GETPOSTINT('entity');

if ($entity > 0) {
    $conf->setEntityValues($db, $entity);
}

// Nobody is logged in: every change is made by an empty user, the watcher is named in the form
$user = new User($db);

$hookmanager->initHooks(['firepermitroundspublic', 'saturnepublicinterface']);

$object = new FirePermit($db);

$now    = dol_now();
$errors = [];

// The key opens the page: without it, nothing about the fire permits is shown
$accessAllowed = digiriskFirePermitRoundsCheckPublicKey($key);

$baseUrl   = $_SERVER['PHP_SELF'] . '?' . http_build_query(['entity' => $conf->entity, 'key' => $key]);
$listUrl   = $baseUrl;
$permitUrl = function (int $permitId, string $savedState = '') use ($baseUrl) {
    return $baseUrl . '&id=' . $permitId . (dol_strlen($savedState) ? '&saved=' . $savedState : '');
};

// What the screens of the rounds show differently from the application: the watcher types their name
$roundsPublic      = true;
$roundsCanRecord   = true;
$roundsDefaultName = '';

// A permit outside the watch (not locked, archived, another entity) is not shown either
if ($accessAllowed && $id > 0) {
    if ($object->fetch($id) <= 0 || $object->status != FirePermit::STATUS_LOCKED || (int) $object->entity !== (int) $conf->entity) {
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

if (empty($resHook) && $accessAllowed && $object->id > 0) {
    // Photos of the round, posted by the Saturne media block before the form is sent
    digiriskFirePermitRoundsMediaActions($object, (string) $action, $now);

    $actionResult = digiriskFirePermitRoundsDoActions($object, $user, (string) $action, $now, ' (' . $langs->transnoentities('FireWatchPublicInterface') . ')');
    if (dol_strlen($actionResult['saved'])) {
        header('Location: ' . $permitUrl($object->id, $actionResult['saved']));
        exit;
    }
    $errors = array_merge($errors, $actionResult['errors']);
}

/*
 * View
 */

$title = $langs->trans('FireWatchPublicTitle');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

$moreJS = ['/saturne/js/includes/signature-pad.min.js'];

if (!$accessAllowed) {
    http_response_code(403);
}

saturne_header(0, '', $title, '', '', 0, 0, $moreJS, [], '', 'digirisk-firewatch-public');

if (!$accessAllowed) {
    print '<div class="digirisk-firewatch"><div class="digirisk-firewatch__closed">';
    print '<i class="fas fa-lock"></i>';
    print '<div>' . $langs->trans('FireWatchPublicClosed') . '</div>';
    print '</div></div>';
} elseif ($object->id > 0) {
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

llxFooter('', 'public');
$db->close();
