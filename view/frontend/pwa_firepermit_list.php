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
 * \file    view/frontend/pwa_firepermit_list.php
 * \ingroup digiriskdolibarr
 * \brief   PWA screen listing the fire permits (search, status filter, pagination).
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
require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_pwa.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['companies', 'projects']);

// Security check
$permissiontoread = $user->hasRight('digiriskdolibarr', 'firepermit', 'read');
saturne_check_access($permissiontoread);

// Get parameters
$listSearch = trim(GETPOST('search', 'alphanohtml'));
$listStatus = GETPOST('status', 'alphanohtml');
$listPage   = max(0, GETPOSTINT('page'));

$object            = new FirePermit($db);
$preventionplan    = new PreventionPlan($db);
$digiriskresources = new DigiriskResources($db);

$listStatusOptions = [
    ''                            => $langs->transnoentities('AllStatus'),
    FirePermit::STATUS_DRAFT      => $langs->transnoentities('InProgress'),
    FirePermit::STATUS_VALIDATED  => $langs->transnoentities('ValidatePendingSignature'),
    FirePermit::STATUS_LOCKED     => $langs->transnoentities('Locked'),
    FirePermit::STATUS_ARCHIVED   => $langs->transnoentities('Archived'),
];
if (!array_key_exists($listStatus, $listStatusOptions)) {
    $listStatus = '';
}

list($listRows, $listTotal, $listTotalPages) = digiriskPwaFetchList('FirePermit', $listSearch, $listStatus, $listPage, function ($record) use ($digiriskresources, $preventionplan, $user) {
    // fetchResourcesFromObject() returns the resolved Societe for a single match, and 0 when there is none
    $extSociety  = $digiriskresources->fetchResourcesFromObject('ExtSociety', $record);
    $societyName = (is_object($extSociety) && $extSociety->id > 0) ? $extSociety->name : '';

    $lines = [];
    if ($societyName !== '') {
        $lines[] = ['icon' => 'fa-industry', 'text' => $societyName];
    }
    $lines[] = ['icon' => 'fa-calendar-alt', 'text' => digiriskPwaFormatPeriod($record->date_start, $record->date_end)];
    if ($record->fk_preventionplan > 0 && $preventionplan->fetch($record->fk_preventionplan) > 0) {
        $lines[] = ['icon' => 'fa-project-diagram', 'text' => $preventionplan->ref];
    }

    return [
        'url'        => dol_buildpath('/custom/digiriskdolibarr/view/firepermit/firepermit_card.php', 1) . '?id=' . $record->id,
        'editUrl'    => ($record->status == FirePermit::STATUS_DRAFT && $user->hasRight('digiriskdolibarr', 'firepermit', 'write'))
            ? dol_buildpath('/custom/digiriskdolibarr/view/firepermit/firepermit_mobile_create.php', 1) . '?id=' . $record->id
            : '',
        'ref'        => $record->ref,
        'title'      => $record->label,
        'lines'      => $lines,
        'statusHtml' => $record->getLibStatut(3),
    ];
});

$listCreateUrl = $user->hasRight('digiriskdolibarr', 'firepermit', 'write')
    ? dol_buildpath('/custom/digiriskdolibarr/view/firepermit/firepermit_mobile_create.php', 1)
    : '';

/*
 * View
 */

$title = $langs->trans('PwaNavFirePermits');
digiriskPwaHeader($title);

$pwaHeaderTitle = $title;
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php';

print '<div class="pwa-container digirisk-pwa">';
require __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_list.tpl.php';
print '</div>';

require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
