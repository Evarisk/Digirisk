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
 * \file    lib/digiriskdolibarr_pwa.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Helper functions shared by the PWA screens (page header, list fetching, formatting).
 */

// Number of records displayed per PWA list page
if (!defined('DIGIRISKDOLIBARR_PWA_LIST_LIMIT')) {
    define('DIGIRISKDOLIBARR_PWA_LIST_LIMIT', 15);
}

/**
 * Open a PWA page: hide the Dolibarr menus and load the app assets.
 *
 * @param  string $title    Page title
 * @param  string $bodyMore Extra body classes
 * @return void
 */
function digiriskPwaHeader(string $title, string $bodyMore = '')
{
    global $conf;

    $moreJS = [
        '/custom/saturne/js/saturne.min.js',
        '/custom/digiriskdolibarr/js/signature-pad.min.js',
        '/custom/digiriskdolibarr/js/digiriskdolibarr.min.js',
    ];
    $moreCSS = [
        '/custom/saturne/css/saturne.min.css',
        '/custom/digiriskdolibarr/css/digiriskdolibarr.min.css',
    ];

    $conf->dol_hide_topmenu         = 1;
    $conf->dol_hide_leftmenu        = 1;
    $conf->global->MAIN_FAVICON_URL = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/digiriskdolibarr_color.png';

    llxHeader('', $title, 'FR:Module_Digirisk', '', 0, 0, $moreJS, $moreCSS, '', trim('template-pwa digirisk-mobile-create ' . $bodyMore));
}

/**
 * Fetch one page of a PWA list, filtered by a free text search and a status.
 *
 * The search matches the reference and the label, which is what the cards display.
 *
 * @param  string   $className   Object class to fetch ('PreventionPlan', 'FirePermit')
 * @param  string   $search      Free text search (may be empty)
 * @param  string   $status      Status to filter on (empty string = every status)
 * @param  int      $page        Zero based page number
 * @param  callable $rowBuilder  Turns a fetched record into a card row
 * @return array                 [rows, total, totalPages]
 */
function digiriskPwaFetchList(string $className, string $search, string $status, int $page, callable $rowBuilder): array
{
    global $db;

    $conditions = [];
    if ($search !== '') {
        $escaped      = $db->escape($search);
        $conditions[] = "(t.ref LIKE '%" . $escaped . "%' OR t.label LIKE '%" . $escaped . "%')";
    }
    if ($status !== '') {
        $conditions[] = 't.status = ' . ((int) $status);
    } else {
        // Deleted records never show up in the application
        $conditions[] = 't.status >= 0';
    }

    $filter = ['customsql' => implode(' AND ', $conditions)];

    $total = saturne_fetch_all_object_type($className, '', '', 0, 0, $filter, 'AND', false, true, false, '', ['count' => true]);
    if (!is_numeric($total) || $total < 0) {
        $total = 0;
    }

    $limit   = DIGIRISKDOLIBARR_PWA_LIST_LIMIT;
    $records = saturne_fetch_all_object_type($className, 'DESC', 't.rowid', $limit, $limit * $page, $filter);
    if (!is_array($records)) {
        $records = [];
    }

    $rows = [];
    foreach ($records as $record) {
        $rows[] = $rowBuilder($record);
    }

    return [$rows, (int) $total, (int) ceil($total / $limit)];
}

/**
 * Count the records of an object type sitting in a given status.
 *
 * @param  string $className Object class to count ('PreventionPlan', 'FirePermit')
 * @param  int    $status    Status to count
 * @return int               Number of records
 */
function digiriskPwaCountByStatus(string $className, int $status): int
{
    $total = saturne_fetch_all_object_type($className, '', '', 0, 0, ['customsql' => 't.status = ' . $status], 'AND', false, true, false, '', ['count' => true]);

    return (is_numeric($total) && $total > 0) ? (int) $total : 0;
}

/**
 * Format a start/end period for a PWA card.
 *
 * @param  int|string $dateStart Start timestamp
 * @param  int|string $dateEnd   End timestamp
 * @return string                Human readable period, or a dash when both dates are missing
 */
function digiriskPwaFormatPeriod($dateStart, $dateEnd): string
{
    global $langs;

    $start = !empty($dateStart) ? dol_print_date($dateStart, 'day') : '';
    $end   = !empty($dateEnd)   ? dol_print_date($dateEnd, 'day')   : '';

    if ($start === '' && $end === '') {
        return $langs->transnoentities('NotDefined');
    }
    if ($start === '') {
        return '→ ' . $end;
    }
    if ($end === '') {
        return $start . ' →';
    }

    return $start . ' → ' . $end;
}
