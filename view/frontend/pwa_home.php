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
 * \file    view/frontend/pwa_home.php
 * \ingroup digiriskdolibarr
 * \brief   Home screen of the DigiriskDolibarr PWA: counters and creation shortcuts.
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
require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/firepermit.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_pwa.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// The home screen is the application entry point: reaching it only requires reading one of the two objects
$canReadPreventionPlan = $user->hasRight('digiriskdolibarr', 'preventionplan', 'read');
$canReadFirePermit     = $user->hasRight('digiriskdolibarr', 'firepermit', 'read');
saturne_check_access($canReadPreventionPlan || $canReadFirePermit);

$listPreventionPlanUrl = dol_buildpath('/custom/digiriskdolibarr/view/frontend/pwa_preventionplan_list.php', 1);
$listFirePermitUrl     = dol_buildpath('/custom/digiriskdolibarr/view/frontend/pwa_firepermit_list.php', 1);

// Counters: one tile per status worth acting on, each linking to the matching filtered list
$homeTiles = [];
if ($canReadPreventionPlan) {
    $homeTiles[] = [
        'icon'  => 'fa-file-signature',
        'label' => $langs->transnoentities('PwaTilePreventionPlanInProgress'),
        'count' => digiriskPwaCountByStatus('PreventionPlan', PreventionPlan::STATUS_DRAFT),
        'url'   => $listPreventionPlanUrl . '?status=' . PreventionPlan::STATUS_DRAFT,
    ];
    $homeTiles[] = [
        'icon'  => 'fa-pen-nib',
        'label' => $langs->transnoentities('PwaTilePreventionPlanToSign'),
        'count' => digiriskPwaCountByStatus('PreventionPlan', PreventionPlan::STATUS_VALIDATED),
        'url'   => $listPreventionPlanUrl . '?status=' . PreventionPlan::STATUS_VALIDATED,
    ];
}
if ($canReadFirePermit) {
    $homeTiles[] = [
        'icon'  => 'fa-fire-alt',
        'label' => $langs->transnoentities('PwaTileFirePermitInProgress'),
        'count' => digiriskPwaCountByStatus('FirePermit', FirePermit::STATUS_DRAFT),
        'url'   => $listFirePermitUrl . '?status=' . FirePermit::STATUS_DRAFT,
    ];
    $homeTiles[] = [
        'icon'  => 'fa-pen-nib',
        'label' => $langs->transnoentities('PwaTileFirePermitToSign'),
        'count' => digiriskPwaCountByStatus('FirePermit', FirePermit::STATUS_VALIDATED),
        'url'   => $listFirePermitUrl . '?status=' . FirePermit::STATUS_VALIDATED,
    ];
}

/*
 * View
 */

$title = $langs->trans('PwaNavHome');
digiriskPwaHeader($title);

$pwaHeaderTitle = $title;
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php';

require __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_home.tpl.php';

require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
