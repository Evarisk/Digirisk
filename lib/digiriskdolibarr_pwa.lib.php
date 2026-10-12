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
 * Turn a search term written as a date into the ranges it covers.
 *
 * Accepts a day (12/09/2026, 12/09/26, 2026-09-12), a day without its year (12/09), a month
 * (09/2026, 2026-09) or a year (2026), with /, - or . as separator. Day and month come in the order
 * of the user's date format, the one the cards are displayed with.
 * A day without its year is the same day in every year from ten years back to two years ahead:
 * one range per year rather than date functions of the database, which differ from one to another.
 * Bounds are built like the stored dates (dol_mktime() in the server timezone), so that a date
 * stored at midnight of the searched day falls in it.
 *
 * @param  string       $term Search term
 * @return int[][]|null       List of [start, end] timestamps, null when the term is not a date
 */
function digiriskPwaSearchDateRange(string $term): ?array
{
    global $langs;

    $monthFirst = strpos($langs->trans('FormatDateShort'), '%m') === 0;
    $day        = 0;
    $month      = 0;
    $year       = 0;

    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $term, $parts)) {
        list(, $year, $month, $day) = $parts;
    } elseif (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2}|\d{4})$/', $term, $parts)) {
        list(, $first, $second, $year) = $parts;
        $day   = $monthFirst ? $second : $first;
        $month = $monthFirst ? $first : $second;
    } elseif (preg_match('/^(\d{1,2})[\/.-](\d{4})$/', $term, $parts) || preg_match('/^(\d{4})-(\d{1,2})$/', $term, $parts)) {
        $month = strlen($parts[1]) === 4 ? $parts[2] : $parts[1];
        $year  = strlen($parts[1]) === 4 ? $parts[1] : $parts[2];
    } elseif (preg_match('/^(\d{1,2})[\/.-](\d{1,2})$/', $term, $parts)) {
        $day   = (int) ($monthFirst ? $parts[2] : $parts[1]);
        $month = (int) ($monthFirst ? $parts[1] : $parts[2]);

        $ranges      = [];
        $currentYear = (int) dol_print_date(dol_now(), '%Y');
        for ($eachYear = $currentYear - 10; $eachYear <= $currentYear + 2; $eachYear++) {
            if (checkdate($month, $day, $eachYear)) {
                $ranges[] = [(int) dol_mktime(0, 0, 0, $month, $day, $eachYear), (int) dol_mktime(23, 59, 59, $month, $day, $eachYear)];
            }
        }

        return !empty($ranges) ? $ranges : null;
    } elseif (preg_match('/^(\d{4})$/', $term, $parts)) {
        $year = $parts[1];
    } else {
        return null;
    }

    $day   = (int) $day;
    $month = (int) $month;
    $year  = (int) $year;
    if ($year < 100) {
        $year += 2000;
    }
    if ($year < 1970 || $year > 2100 || $month > 12 || ($day > 0 && !checkdate($month, $day, $year))) {
        return null;
    }

    if ($day > 0) {
        return [[(int) dol_mktime(0, 0, 0, $month, $day, $year), (int) dol_mktime(23, 59, 59, $month, $day, $year)]];
    }
    if ($month > 0) {
        return [[(int) dol_mktime(0, 0, 0, $month, 1, $year), (int) dol_mktime(23, 59, 59, $month, (int) date('t', mktime(0, 0, 0, $month, 1, $year)), $year)]];
    }

    return [[(int) dol_mktime(0, 0, 0, 1, 1, $year), (int) dol_mktime(23, 59, 59, 12, 31, $year)]];
}

/**
 * Fetch one page of a PWA list, filtered by a free text search and a status.
 *
 * The search matches what the cards display: reference, label, exterior company and period. Each
 * word of the search has to match one of them, so that "BMW 09/2026" finds the plans of BMW starting
 * or ending in September 2026. A word written as a date (with or without its year), a month or a
 * year finds the records whose start or end date falls in it.
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

    $element    = (new $className($db))->element;
    $conditions = [];
    foreach (preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
        $like       = "'%" . $db->escapeforlike($db->escape($term)) . "%'";
        $termChecks = [
            't.ref LIKE ' . $like,
            't.label LIKE ' . $like,
            // Exterior company, linked through the resources of the record
            'EXISTS (SELECT 1 FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskresources as r'
            . ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as s ON s.rowid = r.element_id'
            . " WHERE r.ref = 'ExtSociety' AND r.element_type = 'societe' AND r.status = 1"
            . " AND r.object_type = '" . $db->escape($element) . "' AND r.object_id = t.rowid"
            . ' AND (s.nom LIKE ' . $like . ' OR s.name_alias LIKE ' . $like . '))',
        ];

        // Start or end date falling in the day, the month or the year the term is written as: the
        // dates read on the card, not every period that merely spans them
        foreach (digiriskPwaSearchDateRange($term) ?? [] as $dateRange) {
            $rangeSql     = " BETWEEN '" . $db->idate($dateRange[0]) . "' AND '" . $db->idate($dateRange[1]) . "'";
            $termChecks[] = '(t.date_start' . $rangeSql . ' OR t.date_end' . $rangeSql . ')';
        }

        $conditions[] = '(' . implode(' OR ', $termChecks) . ')';
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
 * Render the status icon for a record in the PWA lists
 *
 * @param object $record The object record (PreventionPlan or FirePermit)
 * @return string HTML of the status badge
 */
function digiriskPwaStatusHtml($record): string
{
    $icon = 'fa-info';
    $statusType = 'status0';

    if ($record->status == $record::STATUS_DRAFT) {
        $icon = 'fa-edit';
        $statusType = 'status0';
    } elseif ($record->status == $record::STATUS_VALIDATED) {
        $icon = 'fa-signature';
        $statusType = 'status3';
    } elseif ($record->status == $record::STATUS_LOCKED) {
        $icon = 'fa-lock';
        $statusType = 'status8';
    } elseif ($record->status == $record::STATUS_ARCHIVED) {
        $icon = 'fa-archive';
        $statusType = 'status8';
    }

    $title = strip_tags($record->getLibStatut(1));

    return '<span class="badge badge-status badge-' . $statusType . '" title="' . dol_escape_htmltag($title) . '"><i class="fas ' . $icon . '"></i></span>';
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

/**
 * Tell where a start/end period stands today, for the badge of a PWA card.
 *
 * @param  int|string $dateStart Start timestamp
 * @param  int|string $dateEnd   End timestamp
 * @return array                 Badge ['text', 'class'], empty when both dates are missing
 */
function digiriskPwaPeriodState($dateStart, $dateEnd): array
{
    global $langs;

    if (empty($dateStart) && empty($dateEnd)) {
        return [];
    }

    $now = dol_now();
    $end = (int) $dateEnd;
    // A date typed without a time is stored at midnight: work still goes on during its last day
    if ($end > 0 && dol_print_date($end, '%H%M%S', 'tzserver') === '000000') {
        $end += 86399;
    }

    if (!empty($dateStart) && $now < $dateStart) {
        return ['text' => $langs->transnoentities('PwaPeriodUpcoming'), 'class' => 'upcoming'];
    }
    if ($end > 0 && $now > $end) {
        return ['text' => $langs->transnoentities('PwaPeriodFinished'), 'class' => 'finished'];
    }

    return ['text' => $langs->transnoentities('PwaPeriodOngoing'), 'class' => 'ongoing'];
}

/**
 * Details shared by the prevention plan and fire permit cards of the PWA lists: who to call, where the
 * work stands in time, whether the exterior company signed and which risks are involved, so a card
 * answers these without being opened.
 *
 * @param  PreventionPlan|FirePermit $record            Prevention plan or fire permit of the card
 * @param  DigiriskResources         $digiriskresources Resources helper
 * @param  SaturneSignature          $signatory         Signatories helper
 * @return array                                        ['lines' => card lines, 'pictos' => category pictos, 'foot' => creation text]
 * @throws Exception
 */
function digiriskPwaCardDetails($record, DigiriskResources $digiriskresources, SaturneSignature $signatory): array
{
    global $db, $langs;

    static $pictoCategories = [];
    static $authorNames     = [];

    $lines = [];

    // fetchResourcesFromObject() returns the resolved Societe for a single match, and 0 when there is none
    $extSociety = $digiriskresources->fetchResourcesFromObject('ExtSociety', $record);
    if (is_object($extSociety) && $extSociety->id > 0) {
        $lines[] = ['icon' => 'fa-building', 'text' => $extSociety->name . (dol_strlen($extSociety->town) ? ' · ' . $extSociety->town : '')];
    }

    $signatories    = $signatory->fetchSignatory('', $record->id, $record->element);
    $extResponsible = (is_array($signatories) && !empty($signatories['ExtSocietyResponsible'])) ? reset($signatories['ExtSocietyResponsible']) : null;
    if (is_object($extResponsible)) {
        $contact = dol_strlen($extResponsible->phone) ? $extResponsible->phone : $extResponsible->email;
        $lines[] = ['icon' => 'fa-user', 'text' => trim($extResponsible->firstname . ' ' . $extResponsible->lastname) . (dol_strlen($contact) ? ' · ' . $contact : '')];
    }

    $lines[] = ['icon' => 'fa-calendar-alt', 'text' => digiriskPwaFormatPeriod($record->date_start, $record->date_end), 'badge' => digiriskPwaPeriodState($record->date_start, $record->date_end)];

    // A draft has not asked the exterior company for its signature yet
    if (is_object($extResponsible) && $record->status > $record::STATUS_DRAFT) {
        if (dol_strlen($extResponsible->signature)) {
            $lines[] = ['icon' => 'fa-check-circle', 'class' => 'success', 'text' => $langs->transnoentities('PwaCardExtSignedOn', dol_print_date($extResponsible->signature_date, 'day', 'tzuser'))];
        } elseif (!empty($extResponsible->last_email_sent_date)) {
            $lines[] = ['icon' => 'fa-hourglass-half', 'class' => 'warning', 'text' => $langs->transnoentities('PwaCardExtSignatureEmailSent', dol_print_date($extResponsible->last_email_sent_date, 'day', 'tzuser'))];
        } else {
            $lines[] = ['icon' => 'fa-hourglass-half', 'class' => 'warning', 'text' => $langs->transnoentities('PwaCardExtSignaturePending')];
        }
    }

    $attendants = (is_array($signatories) && !empty($signatories['ExtSocietyAttendant'])) ? $signatories['ExtSocietyAttendant'] : [];
    if (!empty($attendants)) {
        $signedAttendants = count(array_filter($attendants, function ($attendant) {
            return dol_strlen($attendant->signature) > 0;
        }));
        $lines[] = ['icon' => 'fa-users', 'text' => $langs->transnoentities('PwaCardAttendants', (string) count($attendants), (string) $signedAttendants)];
    }

    // One picto per category of the lines, in the order they were added: danger categories for a
    // prevention plan, types of work for a fire permit
    if (!isset($pictoCategories[$record->element])) {
        $isFirePermit = ($record->element == 'firepermit');
        $categories   = $isFirePermit ? (new Risk($db))->getFirePermitDangerCategories() : Risk::getDangerCategories();

        $pictoCategories[$record->element] = [];
        foreach ((is_array($categories) ? $categories : []) as $category) {
            $category['src'] = dol_buildpath('/custom/digiriskdolibarr/img/' . ($isFirePermit ? 'typeDeTravaux' : 'categorieDangers') . '/' . $category['thumbnail_name'] . '.png', 1);

            $pictoCategories[$record->element][$category['position']] = $category;
        }
    }
    $pictos = [];
    $sql    = 'SELECT category, MIN(rowid) AS first_line FROM ' . MAIN_DB_PREFIX . $record->table_element . 'det';
    $sql   .= ' WHERE fk_' . $record->element . ' = ' . ((int) $record->id) . ' AND status >= 0';
    $sql   .= ' GROUP BY category ORDER BY first_line';
    $resql  = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            if (isset($pictoCategories[$record->element][$obj->category])) {
                $pictos[] = [
                    'src'   => $pictoCategories[$record->element][$obj->category]['src'],
                    'title' => $pictoCategories[$record->element][$obj->category]['name'],
                ];
            }
        }
        $db->free($resql);
    }

    $foot = '';
    if (!empty($record->date_creation)) {
        $authorId = (int) $record->fk_user_creat;
        if ($authorId > 0 && !array_key_exists($authorId, $authorNames)) {
            $author                 = new User($db);
            $authorNames[$authorId] = ($author->fetch($authorId) > 0) ? $author->getFullName($langs) : '';
        }
        $foot = !empty($authorNames[$authorId])
            ? $langs->transnoentities('PwaCardCreatedOnBy', dol_print_date($record->date_creation, 'day', 'tzuser'), $authorNames[$authorId])
            : $langs->transnoentities('PwaCardCreatedOn', dol_print_date($record->date_creation, 'day', 'tzuser'));
    }

    return ['lines' => $lines, 'pictos' => $pictos, 'foot' => $foot];
}
