<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/frontend/pwa_safetytalk_list.php
 * \ingroup digiriskdolibarr
 * \brief   PWA list of the safety talk cycles, each one opening on its talks.
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
require_once __DIR__ . '/../../lib/digiriskdolibarr_pwa.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_safetytalk.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects']);

// Security check
$permissiontoread = isModEnabled('project') && $user->hasRight('projet', 'lire');
$permissiontoadd  = isModEnabled('project') && $user->hasRight('projet', 'creer');
saturne_check_access($permissiontoread);

// Get parameters
$listSearch = trim(GETPOST('search', 'alphanohtml'));
$listStatus = GETPOST('status', 'alphanohtml');
$listPage   = max(0, GETPOSTINT('page'));

$listStatusOptions = [
    ''                              => $langs->transnoentities('AllStatus'),
    (string) Project::STATUS_VALIDATED => $langs->transnoentities('SafetyTalkStatusOpen'),
    (string) Project::STATUS_CLOSED    => $langs->transnoentities('SafetyTalkStatusClosed'),
];
if (!array_key_exists($listStatus, $listStatusOptions)) {
    $listStatus = '';
}

list($cycles, $listTotal, $listTotalPages) = digiriskSafetyTalkFetchCycles($db, $user, $listSearch, $listStatus, $listPage, DIGIRISKDOLIBARR_PWA_LIST_LIMIT);

$listRows = [];
foreach ($cycles as $cycle) {
    $talks     = digiriskSafetyTalkFetchTalks($db, $cycle);
    $heldCount = 0;
    $nextTalk  = null;
    $pictos    = [];
    foreach ($talks as $talk) {
        $period = digiriskSafetyTalkGetPeriod($talk);
        if ($period == 'done') {
            $heldCount++;
        } elseif ($nextTalk === null && ($period == 'today' || $period == 'upcoming')) {
            $nextTalk = $talk;
        }

        $theme = digiriskSafetyTalkGetTheme((string) ($talk->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD] ?? ''));
        if ($theme !== null) {
            $pictos[$theme['position']] = ['src' => $theme['src'], 'title' => $theme['name']];
        }
    }

    $lines = [
        ['icon' => 'fa-users', 'text' => $langs->transnoentities('SafetyTalkCardParticipants', count(digiriskSafetyTalkGetPeopleNames($db, $cycle, $langs)))],
        ['icon' => 'fa-comments', 'text' => $langs->transnoentities('SafetyTalkCardTalks', count($talks), $heldCount)],
    ];
    $lines[] = $nextTalk !== null
        ? ['icon' => 'fa-calendar-alt', 'text' => $langs->transnoentities('SafetyTalkCardNext', dol_print_date($nextTalk->date_start, 'dayhour', 'tzuserrel') . ' - ' . $nextTalk->label), 'class' => 'next']
        : ['icon' => 'fa-calendar-times', 'text' => $langs->transnoentities('SafetyTalkCardNoTalk')];

    $listRows[] = [
        'url'        => dol_buildpath('/digiriskdolibarr/view/safetytalk/safetytalk_project.php', 1) . '?id=' . $cycle->id,
        'editUrl'    => '',
        'ref'        => $cycle->ref,
        'title'      => $cycle->title,
        'lines'      => $lines,
        'pictos'     => array_values($pictos),
        'foot'       => $langs->transnoentities('PwaCardCreatedOn', dol_print_date($cycle->date_c, 'day', 'tzuserrel')),
        'statusHtml' => digiriskSafetyTalkCycleStatusHtml($cycle),
    ];
}

$listCreateUrl = $permissiontoadd ? dol_buildpath('/digiriskdolibarr/view/safetytalk/safetytalk_project.php', 1) . '?action=create' : '';

/*
 * View
 */

$title = $langs->trans('PwaNavSafetyTalks');
digiriskPwaHeader($title);

$pwaHeaderTitle = $title;
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php';

print '<div class="pwa-container digirisk-pwa">';
if (!digiriskSafetyTalkSpreadAvailable()) {
    print '<div class="digirisk-safetytalk-warning"><i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('SafetyTalkSpreadMissing') . '</div>';
}
require __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_list.tpl.php';
print '</div>';

require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
