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
 * \file    lib/digiriskdolibarr_riskoverview.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Functions of the public overview of the risks: one row per groupment and work unit, one
 *          column per danger category, the number of risks on the colour of the worst one.
 */

/**
 * Danger categories of each tab, by position in js/json/dangerCategories.json.
 * The risks of the INRS brochure ED 840, then the strenuousness factors (C2P and the other ones the
 * single document has to list), vibrations being in both.
 */
const DIGIRISK_RISK_OVERVIEW_CATEGORIES = [
    'risk'       => [0, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 31],
    'penibility' => [28, 22, 18, 30, 29, 23, 27, 24, 25, 26],
];

/**
 * Tabs of the overview, in their display order.
 */
const DIGIRISK_RISK_OVERVIEW_TABS = ['risk', 'penibility', 'rps', 'register'];

/**
 * Switches of the setup that hide a tab: the risk tab is always shown.
 */
const DIGIRISK_RISK_OVERVIEW_TAB_CONSTS = [
    'penibility' => 'DIGIRISKDOLIBARR_RISK_OVERVIEW_PENIBILITY',
    'rps'        => 'DIGIRISKDOLIBARR_RISK_OVERVIEW_PSYCHOSOCIAL',
    'register'   => 'DIGIRISKDOLIBARR_RISK_OVERVIEW_REGISTER',
];

/**
 * Risks detailed in the tooltip of a number, the worst ones: beyond, the tooltip only says how many
 * more the number counts.
 */
const DIGIRISK_RISK_OVERVIEW_TOOLTIP_RISKS = 5;

/**
 * Tickets detailed in the tooltip of a number of the register tab, the latest ones.
 */
const DIGIRISK_RISK_OVERVIEW_TOOLTIP_TICKETS = 5;

/**
 * Statuses of a ticket still open (Ticket::STATUS_*: not read, read, assigned, in progress, need more
 * information, waiting): the register tab only counts them unless the visitor asks for the closed and
 * cancelled ones (8 and 9).
 */
const DIGIRISK_RISK_OVERVIEW_OPEN_TICKET_STATUSES = [0, 1, 2, 3, 5, 7];

/**
 * Wrong PIN codes checked within DIGIRISK_RISK_OVERVIEW_PIN_LOCK_DELAY seconds, every visitor together:
 * beyond, no code is checked until the oldest one is out of the delay, so that a code of 4 digits
 * cannot be tried out one by one.
 */
const DIGIRISK_RISK_OVERVIEW_PIN_ATTEMPTS = 10;

/**
 * Seconds the wrong PIN codes are counted over.
 */
const DIGIRISK_RISK_OVERVIEW_PIN_LOCK_DELAY = 900;

/**
 * Type of the security event (llx_events) recorded for a wrong PIN code, listed in the audit of
 * Dolibarr with the IP address of the visitor.
 */
const DIGIRISK_RISK_OVERVIEW_PIN_EVENT = 'DIGIRISK_OVERVIEW_PIN_FAILED';

/**
 * Whether the public overview is open: enabled in the setup, with a key to put in the link.
 *
 * @return bool
 */
function digiriskRiskOverviewPublicEnabled(): bool
{
    return getDolGlobalInt('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_INTERFACE') && dol_strlen(getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY')) > 0;
}

/**
 * Tabs of the overview the setup shows, in their display order. A switch never saved shows its tab,
 * as on an installation updated without reactivating the module. The register tab needs the tickets.
 *
 * @return array risk, then penibility, rps and register unless switched off
 */
function digiriskRiskOverviewTabs(): array
{
    return array_values(array_filter(DIGIRISK_RISK_OVERVIEW_TABS, function (string $tab): bool {
        if ($tab == 'register' && !isModEnabled('ticket')) {
            return false;
        }
        return !isset(DIGIRISK_RISK_OVERVIEW_TAB_CONSTS[$tab]) || getDolGlobalInt(DIGIRISK_RISK_OVERVIEW_TAB_CONSTS[$tab], 1);
    }));
}

/**
 * Check the key given to the public overview.
 *
 * @param  string $key Key read from the URL
 * @return bool        True when the overview is open and the key matches
 */
function digiriskRiskOverviewCheckPublicKey(string $key): bool
{
    return digiriskRiskOverviewPublicEnabled() && hash_equals(getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY'), $key);
}

/**
 * PIN code of the setup, asked after the key of the link.
 *
 * @return string Digits, empty when the link alone opens the overview
 */
function digiriskRiskOverviewPublicPin(): string
{
    return getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_PIN_SECRET');
}

/**
 * Proof kept in the session of a visitor who gave the PIN code: a new PIN code or a new key asks it
 * again.
 *
 * @return string
 */
function digiriskRiskOverviewPinProof(): string
{
    return hash('sha256', getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY') . '|' . digiriskRiskOverviewPublicPin());
}

/**
 * Whether the visitor may see the overview without giving the PIN code: there is none, or it was
 * given in this session.
 *
 * @return bool
 */
function digiriskRiskOverviewPinGiven(): bool
{
    global $conf;

    if (digiriskRiskOverviewPublicPin() === '') {
        return true;
    }

    $proof = $_SESSION['digiriskRiskOverviewPin'][$conf->entity] ?? '';
    return is_string($proof) && hash_equals(digiriskRiskOverviewPinProof(), $proof);
}

/**
 * Check the PIN code given to the public overview, and remember it in the session when it is right.
 *
 * The wrong codes are counted for every visitor together: past DIGIRISK_RISK_OVERVIEW_PIN_ATTEMPTS
 * within DIGIRISK_RISK_OVERVIEW_PIN_LOCK_DELAY seconds, no code is checked any more. An attempt is
 * recorded before it is counted, then checked, then forgotten unless the code was wrong: attempts sent
 * at the same time each count the ones recorded before them, so they cannot all slip under the limit
 * (a counter read then written back lets parallel requests check as many codes as they like).
 *
 * @param  string $pin PIN code typed by the visitor
 * @return string      ok, wrong, or locked when too many wrong codes were given lately
 */
function digiriskRiskOverviewCheckPin(string $pin): string
{
    global $conf, $db, $langs, $user;

    require_once DOL_DOCUMENT_ROOT . '/core/class/events.class.php';

    $attempt              = new Events($db);
    $attempt->type        = DIGIRISK_RISK_OVERVIEW_PIN_EVENT;
    $attempt->dateevent   = dol_now();
    $attempt->description = $langs->transnoentities('RiskOverviewPinWrongEvent');
    // Without its record, an attempt could not be counted: no code is checked
    if ($attempt->create($user) <= 0) {
        return 'locked';
    }
    $forgetAttempt = function () use ($attempt, $db) {
        $db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'events WHERE rowid = ' . ((int) $attempt->id));
    };

    $sql  = 'SELECT COUNT(rowid) as nb FROM ' . MAIN_DB_PREFIX . 'events';
    $sql .= " WHERE type = '" . $db->escape(DIGIRISK_RISK_OVERVIEW_PIN_EVENT) . "'";
    $sql .= ' AND entity = ' . ((int) $conf->entity);
    $sql .= " AND dateevent > '" . $db->idate(dol_now() - DIGIRISK_RISK_OVERVIEW_PIN_LOCK_DELAY) . "'";
    $resql    = $db->query($sql);
    $obj      = $resql ? $db->fetch_object($resql) : null;
    $attempts = $obj ? (int) $obj->nb : PHP_INT_MAX;
    if ($attempts > DIGIRISK_RISK_OVERVIEW_PIN_ATTEMPTS) {
        $forgetAttempt();
        return 'locked';
    }

    if ($pin !== '' && hash_equals(digiriskRiskOverviewPublicPin(), $pin)) {
        $forgetAttempt();
        $_SESSION['digiriskRiskOverviewPin'][$conf->entity] = digiriskRiskOverviewPinProof();
        return 'ok';
    }

    // The record stays: a wrong code, in the security audit of Dolibarr
    return 'wrong';
}

/**
 * Link to the public overview, to share or to print as a QR code.
 *
 * @param  string $tab Tab to open, empty for the first one
 * @return string      Absolute URL, empty when the overview is closed
 */
function digiriskRiskOverviewPublicUrl(string $tab = ''): string
{
    global $conf;

    if (!digiriskRiskOverviewPublicEnabled()) {
        return '';
    }

    $params = ['entity' => $conf->entity, 'key' => getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY')];
    if (in_array($tab, digiriskRiskOverviewTabs(), true)) {
        $params['tab'] = $tab;
    }

    return dol_buildpath('/custom/digiriskdolibarr/public/digiriskelement/digiriskelement_risk_overview.php', 3) . '?' . http_build_query($params);
}

/**
 * Risk list of a groupment or work unit in the application, the worst risks first: where a number of
 * the overview leads. Only a user logged in to Dolibarr reaches it, a visitor gets the login page.
 *
 * @param  int    $elementId Groupment or work unit
 * @param  int    $category  Danger category the list is filtered on, -1 for the whole list
 * @return string            URL of digiriskelement_risk.php sorted by descending cotation
 */
function digiriskRiskOverviewElementUrl(int $elementId, int $category = -1): string
{
    $params = ['id' => $elementId, 'sortfield' => 'evaluation.cotation', 'sortorder' => 'DESC'];
    if ($category >= 0) {
        $params['search_category'] = $category;
    }

    return dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?' . http_build_query($params);
}

/**
 * Filters of the register tab, read from the URL: by default only the open tickets, whatever their date.
 *
 * @return array closed (whether the closed and cancelled tickets count), start and end (timestamps of the
 *               first and last day of the creation dates, null for no bound), from and to (the same days
 *               as YYYY-MM-DD, for the form)
 */
function digiriskRiskOverviewRegisterFilters(): array
{
    $filters = ['closed' => GETPOSTINT('register_closed') > 0, 'start' => null, 'end' => null, 'from' => '', 'to' => ''];

    $days = [];
    foreach (['from', 'to'] as $bound) {
        $day = GETPOST('register_' . $bound, 'alphanohtml');
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $day, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            $days[$bound] = [(int) $parts[1], (int) $parts[2], (int) $parts[3], $day];
        }
    }
    // Two days given the wrong way round still make a period
    if (isset($days['from'], $days['to']) && $days['from'][3] > $days['to'][3]) {
        $days = ['from' => $days['to'], 'to' => $days['from']];
    }
    if (isset($days['from'])) {
        $filters['start'] = dol_mktime(0, 0, 0, $days['from'][1], $days['from'][2], $days['from'][0]);
        $filters['from']  = $days['from'][3];
    }
    if (isset($days['to'])) {
        $filters['end'] = dol_mktime(23, 59, 59, $days['to'][1], $days['to'][2], $days['to'][0]);
        $filters['to']  = $days['to'][3];
    }

    return $filters;
}

/**
 * URL parameters of the filters of the register tab, kept by the export and the reset.
 *
 * @param  array $filters Filters of digiriskRiskOverviewRegisterFilters()
 * @return array          register_closed, register_from, register_to when set
 */
function digiriskRiskOverviewRegisterFilterParams(array $filters): array
{
    return array_filter([
        'register_closed' => $filters['closed'] ? 1 : '',
        'register_from'   => $filters['from'],
        'register_to'     => $filters['to'],
    ], function ($value): bool {
        return $value !== '';
    });
}

/**
 * What the filters of the register tab keep, for the legend of the page and of the Excel sheet.
 *
 * @param  array  $filters Filters of digiriskRiskOverviewRegisterFilters()
 * @return string          "Tickets ouverts uniquement, créés du 01/01/2026 au 30/10/2026"
 */
function digiriskRiskOverviewRegisterFilterLabel(array $filters): string
{
    global $langs;

    $label = $langs->transnoentities($filters['closed'] ? 'RiskOverviewRegisterFilterAll' : 'RiskOverviewRegisterFilterOpen');
    if ($filters['start'] !== null && $filters['end'] !== null) {
        $label .= ', ' . $langs->transnoentities('RiskOverviewRegisterFilterPeriod', dol_print_date($filters['start'], 'day'), dol_print_date($filters['end'], 'day'));
    } elseif ($filters['start'] !== null) {
        $label .= ', ' . $langs->transnoentities('RiskOverviewRegisterFilterSince', dol_print_date($filters['start'], 'day'));
    } elseif ($filters['end'] !== null) {
        $label .= ', ' . $langs->transnoentities('RiskOverviewRegisterFilterUntil', dol_print_date($filters['end'], 'day'));
    }

    return $label;
}

/**
 * Ticket list of a groupment or work unit in the application (its register tab), the latest first:
 * where a number of the register tab leads, filtered as the tab so that it holds what the number
 * counts. Only a user logged in to Dolibarr reaches it.
 *
 * @param  int    $elementId   Groupment or work unit
 * @param  array  $categoryIds Ticket categories the list is filtered on, any of them; empty for every ticket
 * @param  array  $filters     Filters of digiriskRiskOverviewRegisterFilters(), empty for none
 * @return string              URL of digiriskelement_register.php
 */
function digiriskRiskOverviewRegisterUrl(int $elementId, array $categoryIds = [], array $filters = []): string
{
    $params = ['id' => $elementId];
    if (!empty($categoryIds)) {
        $params['search_categories_filter'] = array_values(array_map('intval', $categoryIds));
    }
    if (!empty($filters) && empty($filters['closed'])) {
        $params['search_fk_statut'] = implode(',', DIGIRISK_RISK_OVERVIEW_OPEN_TICKET_STATUSES);
    }
    foreach (['start' => 'dtstart', 'end' => 'dtend'] as $bound => $suffix) {
        if (!empty($filters[$bound])) {
            $params['search_datec_' . $suffix . 'day']   = dol_print_date($filters[$bound], '%d');
            $params['search_datec_' . $suffix . 'month'] = dol_print_date($filters[$bound], '%m');
            $params['search_datec_' . $suffix . 'year']  = dol_print_date($filters[$bound], '%Y');
        }
    }

    return dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_register.php', 1) . '?' . http_build_query($params);
}

/**
 * Columns of the register tab: the registers, children of the main ticket category of the setup, each
 * with its subcategories. A ticket counts in a register when it is classified in the register or in
 * one of its subcategories, at any depth.
 *
 * @param  DoliDB $db Database handler
 * @return array      Columns, by category id: position (the category id), name, shortName, ids (the
 *                    register and every category under it) and subs (id => name, ids), in their order
 */
function digiriskRiskOverviewRegisterColumns(DoliDB $db): array
{
    $mainCategoryId = getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY');
    if (!isModEnabled('categorie') || $mainCategoryId <= 0) {
        return [];
    }

    // The tree under the main category, level by level
    $categories = [];
    $parentIds  = [$mainCategoryId];
    $depth      = 0;
    while (!empty($parentIds) && $depth++ < 10) {
        $sql  = 'SELECT rowid, fk_parent, label FROM ' . MAIN_DB_PREFIX . 'categorie';
        $sql .= ' WHERE fk_parent IN (' . implode(',', array_map('intval', $parentIds)) . ')';
        $sql .= ' AND entity IN (' . getEntity('category') . ')';
        $sql .= ' ORDER BY position ASC, label ASC';
        $resql = $db->query($sql);
        if (!$resql) {
            break;
        }

        $parentIds = [];
        while ($obj = $db->fetch_object($resql)) {
            $categories[(int) $obj->rowid] = [
                'parent' => (int) $obj->fk_parent,
                'name'   => trim(dol_html_entity_decode($obj->label, ENT_QUOTES | ENT_HTML5)),
            ];
            $parentIds[] = (int) $obj->rowid;
        }
    }

    // Every category under a category, itself included
    $descendants = function (int $categoryId) use (&$descendants, $categories): array {
        $ids = [$categoryId];
        foreach ($categories as $childId => $child) {
            if ($child['parent'] == $categoryId) {
                $ids = array_merge($ids, $descendants($childId));
            }
        }
        return $ids;
    };

    $columns = [];
    foreach ($categories as $categoryId => $category) {
        if ($category['parent'] != $mainCategoryId) {
            continue;
        }

        $subs = [];
        foreach ($categories as $subId => $sub) {
            if ($sub['parent'] == $categoryId) {
                $subs[$subId] = ['name' => $sub['name'], 'ids' => $descendants($subId)];
            }
        }

        $columns[$categoryId] = [
            'position'  => $categoryId,
            'name'      => $category['name'],
            'shortName' => $category['name'],
            'ids'       => $descendants($categoryId),
            'subs'      => $subs,
        ];
    }

    return $columns;
}

/**
 * Add to the rows of the overview the tickets of each element: every ticket of the element, as its
 * register tab lists them whatever their status, and the tickets of each register and subcategory.
 *
 * @param  DoliDB $db              Database handler
 * @param  array  $rows            Rows of digiriskRiskOverviewRows()
 * @param  array  $registerColumns Columns of digiriskRiskOverviewRegisterColumns()
 * @param  array  $filters         Filters of digiriskRiskOverviewRegisterFilters(): the open tickets only
 *                                 unless closed, created within start and end; empty for every ticket
 * @return array                   The rows, each with tickets: total, unregistered (the tickets of no
 *                                 register), registers (id => count, subs (id => count), direct (the
 *                                 tickets of no subcategory)) and list (each ticket for the tooltips:
 *                                 ref, date, status, severity, subject, categories; neither its message
 *                                 nor who declared it)
 */
function digiriskRiskOverviewAddTickets(DoliDB $db, array $rows, array $registerColumns, array $filters = []): array
{
    $rowIndexes = [];
    foreach ($rows as $index => $row) {
        $rowIndexes[$row['id']] = $index;
    }

    // Each ticket of a groupment or work unit, once per category it is classified in
    $sql  = 'SELECT t.rowid, t.ref, t.datec, t.fk_statut, t.severity_code, t.subject, t.message, ef.digiriskdolibarr_ticket_service as fk_element, ct.fk_categorie';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'ticket as t';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'ticket_extrafields as ef ON ef.fk_object = t.rowid';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_ticket as ct ON ct.fk_ticket = t.rowid';
    $sql .= ' WHERE t.entity IN (' . getEntity('ticket') . ')';
    $sql .= " AND ef.digiriskdolibarr_ticket_service IS NOT NULL AND ef.digiriskdolibarr_ticket_service <> ''";
    if (!empty($filters) && empty($filters['closed'])) {
        $sql .= ' AND t.fk_statut IN (' . implode(',', DIGIRISK_RISK_OVERVIEW_OPEN_TICKET_STATUSES) . ')';
    }
    if (!empty($filters['start'])) {
        $sql .= " AND t.datec >= '" . $db->idate($filters['start']) . "'";
    }
    if (!empty($filters['end'])) {
        $sql .= " AND t.datec <= '" . $db->idate($filters['end']) . "'";
    }
    $resql = $db->query($sql);

    $tickets = [];
    while ($resql && $obj = $db->fetch_object($resql)) {
        $elementId = (int) $obj->fk_element;
        if (!isset($rowIndexes[$elementId])) {
            continue;
        }
        if (!isset($tickets[(int) $obj->rowid])) {
            // The subject says what happened; a ticket without one reads the start of its message
            $subject = trim(html_entity_decode(dol_string_nohtmltag((string) $obj->subject), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($subject === '') {
                $subject = trim(preg_replace('/\s+/u', ' ', html_entity_decode(dol_string_nohtmltag((string) $obj->message), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            }
            $tickets[(int) $obj->rowid] = [
                'id'         => (int) $obj->rowid,
                'element'    => $elementId,
                'ref'        => (string) $obj->ref,
                'date'       => $db->jdate($obj->datec),
                'status'     => (int) $obj->fk_statut,
                'severity'   => (string) $obj->severity_code,
                'subject'    => $subject,
                'categories' => [],
            ];
        }
        $tickets[(int) $obj->rowid]['categories'][] = (int) $obj->fk_categorie;
    }

    foreach ($rows as &$row) {
        $row['tickets'] = ['total' => 0, 'unregistered' => 0, 'registers' => [], 'list' => []];
        foreach ($registerColumns as $registerId => $registerColumn) {
            $row['tickets']['registers'][$registerId] = ['count' => 0, 'subs' => array_fill_keys(array_keys($registerColumn['subs']), 0), 'direct' => 0];
        }
    }
    unset($row);

    foreach ($tickets as $ticket) {
        $row = &$rows[$rowIndexes[$ticket['element']]];
        $row['tickets']['total']++;
        $row['tickets']['list'][] = $ticket;
        $inRegister = false;
        foreach ($registerColumns as $registerId => $registerColumn) {
            if (!array_intersect($ticket['categories'], $registerColumn['ids'])) {
                continue;
            }
            $inRegister = true;
            $register   = &$row['tickets']['registers'][$registerId];
            $register['count']++;
            $inSub = false;
            foreach ($registerColumn['subs'] as $subId => $sub) {
                if (array_intersect($ticket['categories'], $sub['ids'])) {
                    $register['subs'][$subId]++;
                    $inSub = true;
                }
            }
            if (!$inSub) {
                $register['direct']++;
            }
            unset($register);
        }
        if (!$inRegister) {
            $row['tickets']['unregistered']++;
        }
        unset($row);
    }

    return $rows;
}

/**
 * Columns of a tab of danger categories, in the order of the tab. The psychosocial column of the risk
 * tab goes with the psychosocial tab, when the setup hides it.
 *
 * @param  string $tab risk or penibility
 * @return array       Columns: position, name, short name (without "Risques liés aux"...), pictogram file
 */
function digiriskRiskOverviewCategoryColumns(string $tab): array
{
    $categories = [];
    foreach (Risk::getDangerCategories() as $category) {
        $categories[(int) $category['position']] = $category;
    }

    $showPsychosocial = in_array('rps', digiriskRiskOverviewTabs(), true);

    $columns = [];
    foreach (DIGIRISK_RISK_OVERVIEW_CATEGORIES[$tab] ?? [] as $position) {
        if (!isset($categories[$position]) || ($position == Risk::PSYCHOSOCIAL_CATEGORY_POSITION && !$showPsychosocial)) {
            continue;
        }
        $name = $categories[$position]['name'];

        $columns[$position] = [
            'position'  => $position,
            'name'      => $name,
            // The column heads are narrow: "Risques liés aux ambiances thermiques" reads "Ambiances thermiques"
            'shortName' => dol_ucfirst(preg_replace('/^Risques\s+(et nuisances\s+)?(liés\s+(aux|au|à la|à l\')\s*|de\s+|d\'|d’)/u', '', $name)),
            'picto'     => $categories[$position]['thumbnail_name'] . '.png',
        ];
    }

    return $columns;
}

/**
 * Columns of the psychosocial tab: the factors F1 to F6.
 *
 * @param  array          $rpsSubCategories Psychosocial factors, by position
 * @param  Translate|null $outputLangs      Language of the generated document, the user language when null
 * @return array                            Columns: position, name, factor key (F1...)
 */
function digiriskRiskOverviewFactorColumns(array $rpsSubCategories, $outputLangs = null): array
{
    global $langs;

    $labelLangs = $outputLangs ?? $langs;

    $columns = [];
    foreach ($rpsSubCategories as $position => $subCategory) {
        $columns[(int) $position] = [
            'position'  => (int) $position,
            'name'      => $labelLangs->transnoentities($subCategory['label']),
            'shortName' => $labelLangs->transnoentities($subCategory['label']),
            'factor'    => 'F' . ((int) $position + 1),
        ];
    }

    return $columns;
}

/**
 * Content of a cell of the overview, shared by the page and the Excel export.
 *
 * A risk cell holds the number of risks of the category with the level of the worst one, a
 * psychosocial cell the level of a factor or the average of the factors. A cell without anything
 * reads NC, its state telling why.
 *
 * A number leads to the risk list of the element, so it must count what the list shows: a category
 * cell opens the list filtered on its category, the total of the risk tab the whole list.
 *
 * A cell of the register tab holds the number of tickets of a register, the total every ticket of the
 * element: it opens the ticket list of the element, filtered on the register.
 *
 * @param  array  $row      Row of digiriskRiskOverviewRows()
 * @param  string $tab      risk, penibility, rps or register
 * @param  int    $position Danger category, factor on the rps tab, register on the register tab; -1 for the total column
 * @param  array  $columns  Columns of the tab, to add up the total
 * @return array            value (null for NC), scale (0 when the cell has no level), kind (count, level,
 *                          total, ticket), decimals shown, state of an empty cell (empty, notConcerned,
 *                          notAssessed, toQualify, noWorkUnit, notRated), link (whether the number opens
 *                          the risk list) and category (the list filter, -1 for the whole list)
 */
function digiriskRiskOverviewCell(array $row, string $tab, int $position, array $columns): array
{
    $rps  = $row['rps'];
    $cell = ['value' => null, 'scale' => 0, 'kind' => 'count', 'decimals' => 0, 'state' => '', 'link' => false, 'category' => -1];

    // The register ids are ticket categories, unrelated to the danger categories of the other tabs
    if ($tab == 'register') {
        if ($position < 0) {
            return array_merge($cell, ['value' => (int) $row['tickets']['total'], 'kind' => 'total', 'link' => $row['tickets']['total'] > 0]);
        }
        $count = (int) ($row['tickets']['registers'][$position]['count'] ?? 0);
        return array_merge($cell, ['value' => $count, 'kind' => 'ticket', 'link' => $count > 0, 'category' => $position]);
    }

    // Psychosocial average of the element, or why there is none: it opens the whole list, at the top
    // of which the psychosocial block shows the factors. The psychosocial tab gives it with a decimal;
    // among the counts of the risk tab it reads as a whole number, the average rounded, which is the
    // level of its colour (the levels change at .5)
    $rpsAverage = function (int $decimals) use ($rps, $cell): array {
        if ($rps['average'] !== null) {
            return array_merge($cell, ['value' => round($rps['average'], $decimals), 'scale' => (int) $rps['band'], 'kind' => 'level', 'decimals' => $decimals, 'link' => true]);
        }
        $states = [0 => 'notConcerned', 1 => 'notAssessed', -1 => 'toQualify'];
        return array_merge($cell, ['kind' => 'level', 'state' => $states[$rps['state']] ?? 'noWorkUnit']);
    };

    if ($tab == 'rps') {
        if ($position < 0) {
            return $rpsAverage(1);
        }
        if (isset($rps['levels'][$position])) {
            $level = (float) $rps['levels'][$position];
            return array_merge($cell, ['value' => round($level, 1), 'scale' => rps_scope_band($level), 'kind' => 'level', 'decimals' => 1, 'link' => true]);
        }
        $empty = $rpsAverage(1);
        return array_merge($cell, ['kind' => 'level', 'state' => $empty['value'] !== null ? 'notRated' : $empty['state']]);
    }

    if ($position < 0) {
        // Risk tab: the lines of the risk list of the element, every category, the psychosocial risks
        // being a single line there as well
        if ($tab == 'risk') {
            $count = 0;
            foreach ($row['cells'] as $category => $categoryCell) {
                if ($category != Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
                    $count += $categoryCell['count'];
                }
            }
            // A work unit out of the psychosocial scope does not show its psychosocial line, nor the
            // overview when the setup hides the psychosocial risks
            if ($row['psychosocialLine'] && isset($columns[Risk::PSYCHOSOCIAL_CATEGORY_POSITION]) && ($row['type'] != 'workunit' || $rps['state'] === null || $rps['state'] === 1)) {
                $count++;
            }
            return array_merge($cell, ['value' => $count, 'kind' => 'total', 'link' => true]);
        }

        // Other tabs: the risks of their categories, which no single list shows
        $count = 0;
        foreach ($columns as $column) {
            $count += $row['cells'][$column['position']]['count'] ?? 0;
        }
        return array_merge($cell, ['value' => $count, 'kind' => 'total']);
    }

    if ($position == Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
        return $rpsAverage(0);
    }

    $categoryCell = $row['cells'][$position] ?? [];
    if (!empty($categoryCell['count'])) {
        return array_merge($cell, ['value' => (int) $categoryCell['count'], 'scale' => (int) $categoryCell['scale'], 'link' => true, 'category' => $position]);
    }

    return array_merge($cell, ['state' => 'empty']);
}

/**
 * Risks a number of the overview counts, for its tooltip: the worst ones first, with their current
 * assessment as the tooltip of a risk of the action plan shows it (cotation, photo, date, comment).
 *
 * A category cell holds the risks of its category, the total of a tab those of its categories: every
 * category but the psychosocial one on the risk tab, as its count does. The psychosocial risks read as
 * a level, not as risks: they have no tooltip.
 *
 * @param  array  $row      Row of digiriskRiskOverviewRows()
 * @param  string $tab      risk or penibility
 * @param  int    $position Danger category, -1 for the total column
 * @param  array  $columns  Columns of the tab
 * @return array            risks (the DIGIRISK_RISK_OVERVIEW_TOOLTIP_RISKS worst ones, with the name and
 *                          pictogram of their category and the URL of their photo, empty without one)
 *                          and more (how many other risks the number counts)
 * @throws Exception
 */
function digiriskRiskOverviewCellRisks(array $row, string $tab, int $position, array $columns): array
{
    global $conf, $db;

    static $categories = null;
    static $risk       = null;
    static $photoUrls  = [];

    if (!in_array($tab, ['risk', 'penibility'], true) || $position == Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
        return ['risks' => [], 'more' => 0];
    }

    if ($position >= 0) {
        $risks = $row['cells'][$position]['risks'] ?? [];
    } else {
        $risks = [];
        foreach ($tab == 'risk' ? array_keys($row['cells']) : array_keys($columns) as $category) {
            if ($category != Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
                $risks = array_merge($risks, $row['cells'][$category]['risks'] ?? []);
            }
        }
    }

    // The worst first, the oldest risk first among equal cotations
    usort($risks, fn($first, $second) => [$second['cotation'], $first['id']] <=> [$first['cotation'], $second['id']]);
    $shownRisks = array_slice($risks, 0, DIGIRISK_RISK_OVERVIEW_TOOLTIP_RISKS);

    if ($categories === null) {
        $categories = [];
        foreach (Risk::getDangerCategories() as $category) {
            $categories[(int) $category['position']] = $category;
        }
        $risk = new Risk($db);
    }

    $baseOutputDir = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? (DOL_DATA_ROOT . '/digiriskdolibarr');
    foreach ($shownRisks as &$shownRisk) {
        $category = $categories[$shownRisk['category']] ?? [];

        $shownRisk['categoryName']  = (string) ($category['name'] ?? '');
        $shownRisk['categoryPicto'] = isset($category['thumbnail_name']) ? $category['thumbnail_name'] . '.png' : '';
        $shownRisk['description']   = trim(html_entity_decode(dol_string_nohtmltag($shownRisk['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $shownRisk['comment']       = trim(html_entity_decode(dol_string_nohtmltag($shownRisk['comment']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        // A risk is in the tooltip of its category and in the one of the total: its photo is looked for once
        if (!isset($photoUrls[$shownRisk['id']])) {
            $photoUrls[$shownRisk['id']] = $risk->resolveDashboardRiskPhotoUrl($baseOutputDir, $shownRisk['assessmentRef'], $shownRisk['photo'], false);
        }
        $shownRisk['photoUrl'] = $photoUrls[$shownRisk['id']];
    }
    unset($shownRisk);

    return ['risks' => $shownRisks, 'more' => count($risks) - count($shownRisks)];
}

/**
 * Rows of the overview: every active groupment and work unit of the entity, in the order of the tree,
 * with the risks of each category and the psychosocial assessment.
 *
 * The risk cells only count the risks of the element itself, as the risk list of the element does. A
 * psychosocial cell reads the consolidated assessment of the work unit; a groupment shows the average
 * of its assessed work units, which carry the psychosocial risks.
 *
 * @param  DoliDB $db                        Database handler
 * @param  array  $activeDigiriskElementList Active elements of the entity, by id
 * @param  array  $rpsLines                  Psychosocial lines of the work units, from digiriskdolibarr_rps_scope_data.tpl.php
 *                                           (which also declares rps_scope_band(), used here)
 * @param  array  $rpsSubCategories          Psychosocial factors F1 to F6, by position
 * @return array                             Rows, in the order of the tree
 * @throws Exception
 */
function digiriskRiskOverviewRows(DoliDB $db, array $activeDigiriskElementList, array $rpsLines, array $rpsSubCategories): array
{
    $rows = [];
    foreach (flatten_tree(recurse_tree(0, 0, $activeDigiriskElementList)) as $elementId => $node) {
        $rows[$elementId] = [
            'id'       => (int) $elementId,
            'ref'      => $node['object']->ref,
            'label'    => $node['object']->label,
            'type'     => $node['object']->element_type,
            'depth'    => (int) $node['depth'],
            'parent'   => (int) $node['object']->fk_parent,
            // Elements right under it: a groupment holding some can be folded on the page
            'children' => 0,
            'cells'    => [],
            // Whether its risk list holds a psychosocial line
            'psychosocialLine' => false,
            'rps'      => ['state' => null, 'levels' => [], 'average' => null, 'band' => 0, 'reason' => ''],
            // Filled by digiriskRiskOverviewAddTickets() when the register tab is shown
            'tickets'  => ['total' => 0, 'unregistered' => 0, 'registers' => [], 'list' => []],
        ];
    }
    foreach ($rows as $row) {
        if (isset($rows[$row['parent']])) {
            $rows[$row['parent']]['children']++;
        }
    }

    // Risks of the element itself, with their current assessment: count and worst level per category,
    // and the risks themselves for the tooltip of the number
    $risk               = new Risk($db);
    $riskAssessment     = new RiskAssessment($db);
    $riskInfos          = $risk->loadRiskInfos(['filterRisk' => " AND t.type = 'risk'"]);
    $showAssessmentDate = getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE');
    foreach ($riskInfos['current']['risks'] as $currentRisk) {
        $elementId = (int) $currentRisk->fk_element;
        if (!isset($rows[$elementId])) {
            continue;
        }

        $riskAssessment->cotation = $currentRisk->riskAssessmentCotation;
        $scale                    = $riskAssessment->getEvaluationScale();
        $category                 = (int) $currentRisk->category;

        $cell = $rows[$elementId]['cells'][$category] ?? ['count' => 0, 'scale' => 0, 'risks' => []];
        $cell['count']++;
        $cell['scale']   = max($cell['scale'], $scale);
        $cell['risks'][] = [
            'id'             => (int) $currentRisk->id,
            'ref'            => (string) $currentRisk->ref,
            'description'    => (string) $currentRisk->description,
            'category'       => $category,
            'cotation'       => (int) $currentRisk->riskAssessmentCotation,
            'scale'          => (int) $scale,
            'assessmentRef'  => (string) $currentRisk->riskAssessmentRef,
            // The date the risk list shows: the one set on the assessment when the setup says so
            'assessmentDate' => $db->jdate($showAssessmentDate && !empty($currentRisk->riskAssessmentDate) ? $currentRisk->riskAssessmentDate : $currentRisk->riskAssessmentDateCreation),
            'comment'        => (string) $currentRisk->riskAssessmentComment,
            'photo'          => (string) $currentRisk->riskAssessmentPhoto,
        ];

        $rows[$elementId]['cells'][$category] = $cell;

        // The risk list of the element shows one psychosocial line, for a risk without a factor: the
        // risks of a factor are the detail of the assessment (digiriskdolibarr_risklist_view.tpl.php)
        if ($category == Risk::PSYCHOSOCIAL_CATEGORY_POSITION && ($currentRisk->sub_category === null || $currentRisk->sub_category === '' || (int) $currentRisk->sub_category < 0)) {
            $rows[$elementId]['psychosocialLine'] = true;
        }
    }

    // Psychosocial assessment of the work units, gathered on every groupment above them
    $groupments = [];
    foreach ($rpsLines as $rpsLine) {
        $elementId = (int) $rpsLine['id'];
        if (!isset($rows[$elementId])) {
            continue;
        }

        $rows[$elementId]['rps'] = [
            'state'   => (int) $rpsLine['state'],
            'levels'  => $rpsLine['levels'],
            'average' => $rpsLine['done'] ? (float) $rpsLine['average'] : null,
            'band'    => $rpsLine['done'] ? (int) $rpsLine['band'] : 0,
            // Justification of a work unit out of scope, expected by the single document
            'reason'  => $rpsLine['state'] === 0 ? (string) $rpsLine['reason'] : '',
        ];

        $parentId = $rows[$elementId]['parent'];
        $guard    = 0;
        while (isset($rows[$parentId]) && $guard++ < 50) {
            $groupments[$parentId]['states'][] = (int) $rpsLine['state'];
            if ($rpsLine['done']) {
                $groupments[$parentId]['averages'][] = (float) $rpsLine['average'];
                foreach ($rpsLine['levels'] as $factor => $level) {
                    $groupments[$parentId]['levels'][$factor][] = (int) $level;
                }
            }
            $parentId = $rows[$parentId]['parent'];
        }
    }

    foreach ($groupments as $groupmentId => $groupment) {
        $averages = $groupment['averages'] ?? [];
        $levels   = [];
        foreach ($groupment['levels'] ?? [] as $factor => $factorLevels) {
            $levels[$factor] = array_sum($factorLevels) / count($factorLevels);
        }

        // Concerned when one of its work units is, out of scope when all of them are
        $states = array_unique($groupment['states']);
        $state  = in_array(1, $states, true) ? 1 : ($states === [0] ? 0 : -1);

        $rows[$groupmentId]['rps'] = [
            'state'   => $state,
            'levels'  => $levels,
            'average' => empty($averages) ? null : array_sum($averages) / count($averages),
            'band'    => empty($averages) ? 0 : rps_scope_band(array_sum($averages) / count($averages)),
            'reason'  => '',
        ];
    }

    return array_values($rows);
}

/**
 * Format a psychosocial level or average for a cell: always one decimal, so that a level and an
 * average read alike in the column.
 *
 * @param  float          $value       Level or average from 1 to 4
 * @param  Translate|null $outputLangs Language of the generated document, the user language when null
 * @return string                      "2,5", "3,0"
 */
function digiriskRiskOverviewFormatLevel(float $value, $outputLangs = null): string
{
    return price(round($value, 1), 0, $outputLangs ?? '', 0, 1, 1);
}

/**
 * Send the overview as an Excel workbook, laid out as the single document presentation workbook: one
 * sheet per tab the setup shows, one row per groupment and work unit, the cells coloured as on the page.
 *
 * @param  array  $overviewRows        Rows of digiriskRiskOverviewRows()
 * @param  array  $overviewColumns     Columns of each tab, by tab
 * @param  array  $rpsScale            Psychosocial levels, for the legend
 * @param  string $registerFilterLabel What the filters of the register tab keep, under its legend
 * @return void
 */
function digiriskRiskOverviewExportXlsx(array $overviewRows, array $overviewColumns, array $rpsScale, string $registerFilterLabel = ''): void
{
    global $langs, $mysoc;

    require_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpspreadsheet/src/autoloader.php';
    require_once DOL_DOCUMENT_ROOT . '/includes/Psr/autoloader.php';
    require_once PHPEXCELNEW_PATH . 'Spreadsheet.php';

    // Colours of the Digirisk cotation chips (css/scss/element/_cotation.scss), and of the psychosocial
    // scale for a psychosocial level (css/scss/variable/_colors.scss): background, text
    $levelColours    = [1 => ['ECECEC', '000000'], 2 => ['E9AD4F', 'FFFFFF'], 3 => ['E05353', 'FFFFFF'], 4 => ['2B2B2B', 'FFFFFF']];
    $rpsLevelColours = [1 => ['3A9D5D', 'FFFFFF'], 2 => ['E9AD4F', '3A2A00'], 3 => ['E05353', 'FFFFFF'], 4 => ['8F2222', 'FFFFFF']];
    $emptyColours    = ['E8F6ED', '15803D'];
    // An empty cell of the psychosocial sheet is a unit not assessed, no good news: neutral
    $rpsEmptyColours = ['F1F5F9', '64748B'];
    $headColours     = ['263C5C', 'FFFFFF'];
    // A number of tickets has no level: a neutral tint, as on the page (.is-register)
    $ticketColours   = ['E7F3FF', '1E3A5F'];

    $tabLabels   = ['risk' => 'RiskOverviewTabRisks', 'penibility' => 'RiskOverviewTabPenibility', 'rps' => 'RiskOverviewTabPsychosocial', 'register' => 'RiskOverviewTabRegisters'];
    $countLabels = ['risk' => 'RiskOverviewRiskCount', 'penibility' => 'RiskOverviewRiskCount', 'rps' => 'RiskOverviewPsychosocialAverage', 'register' => 'RiskOverviewTicketCount'];
    $levelLabels = [1 => 'GreyRisk', 2 => 'OrangeRisk', 3 => 'RedRisk', 4 => 'BlackRisk'];

    $fill = function (array $colours, bool $bold = false): array {
        return [
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $colours[0]]],
            'font' => ['color' => ['rgb' => $colours[1]], 'bold' => $bold],
        ];
    };
    $cellName = function (int $column, int $row): string {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row;
    };

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $spreadsheet->getProperties()->setCreator('Digirisk')->setTitle($langs->transnoentities('RiskOverviewPublicTitle') . ' - ' . $mysoc->name);
    $spreadsheet->removeSheetByIndex(0);

    foreach (digiriskRiskOverviewTabs() as $tab) {
        $columns = $overviewColumns[$tab] ?? [];
        $sheet   = $spreadsheet->createSheet();
        // A sheet name holds 31 characters at most, without []:*?/\
        $sheet->setTitle(dol_trunc(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $langs->transnoentities($tabLabels[$tab])), 31, 'right', 'UTF-8', 1));

        // Heads: element, reference, total, then one narrow column per category or factor, its name upwards
        $heads = [$langs->transnoentities('RiskOverviewElement'), $langs->transnoentities('Ref'), $langs->transnoentities($countLabels[$tab])];
        // Few columns, named across: the factors and the registers
        $wideColumns = in_array($tab, ['rps', 'register'], true);
        foreach ($columns as $column) {
            $heads[] = ($tab == 'rps' ? $column['factor'] . ' - ' : '') . $column['shortName'];
        }
        foreach ($heads as $index => $head) {
            $sheet->setCellValue($cellName($index + 1, 1), $head);
        }
        $lastColumn = count($heads);
        $sheet->getStyle($cellName(1, 1) . ':' . $cellName($lastColumn, 1))->applyFromArray($fill($headColours, true) + [
            'alignment' => ['wrapText' => true, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM],
        ]);
        $sheet->getStyle($cellName(4, 1) . ':' . $cellName($lastColumn, 1))->getAlignment()->setTextRotation(90)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight($wideColumns ? 120 : 170);
        $sheet->getColumnDimension('A')->setWidth(44);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(12);
        for ($index = 4; $index <= $lastColumn; $index++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index))->setWidth($wideColumns ? 12 : 7);
        }

        // One row per element, indented as in the tree
        $rowIndex = 1;
        foreach ($overviewRows as $overviewRow) {
            $rowIndex++;
            $sheet->setCellValue($cellName(1, $rowIndex), $overviewRow['label']);
            $sheet->setCellValue($cellName(2, $rowIndex), $overviewRow['ref']);
            $sheet->getStyle($cellName(1, $rowIndex))->getAlignment()->setIndent((int) $overviewRow['depth']);
            if ($overviewRow['type'] == 'groupment') {
                $sheet->getStyle($cellName(1, $rowIndex) . ':' . $cellName(2, $rowIndex))->getFont()->setBold(true);
            }

            $positions = array_merge([-1], array_keys($columns));
            foreach ($positions as $index => $position) {
                $cell       = digiriskRiskOverviewCell($overviewRow, $tab, (int) $position, $columns);
                $coordinate = $cellName($index + 3, $rowIndex);
                if ($cell['value'] === null) {
                    $sheet->setCellValue($coordinate, $langs->transnoentities('RiskOverviewNotConcerned'));
                    $sheet->getStyle($coordinate)->applyFromArray($fill($tab == 'rps' ? $rpsEmptyColours : $emptyColours));
                } else {
                    $sheet->setCellValue($coordinate, $cell['value']);
                    // A psychosocial value of the psychosocial sheet keeps its decimal, as on the page: 3,0 rather than 3
                    if ($cell['decimals']) {
                        $sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('0.0');
                    }
                    if ($cell['kind'] == 'ticket') {
                        // A register without any ticket stays greyed out
                        if ($cell['value'] > 0) {
                            $sheet->getStyle($coordinate)->applyFromArray($fill($ticketColours, true));
                        } else {
                            $sheet->getStyle($coordinate)->getFont()->getColor()->setRGB('94A3B8');
                        }
                    } elseif ($cell['scale'] > 0) {
                        $sheet->getStyle($coordinate)->applyFromArray($fill(($cell['kind'] == 'level' ? $rpsLevelColours : $levelColours)[$cell['scale']], true));
                    } else {
                        $sheet->getStyle($coordinate)->getFont()->setBold(true);
                    }
                }
            }
        }

        $sheet->getStyle($cellName(3, 2) . ':' . $cellName($lastColumn, $rowIndex))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($cellName(1, 1) . ':' . $cellName($lastColumn, $rowIndex))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D9D9D9');
        $sheet->freezePane('D2');

        // Legend under the table, as on the page; a number of tickets has no colour scale
        $rowIndex += 2;
        $legendTexts = ['risk' => 'RiskOverviewLegendRisks', 'penibility' => 'RiskOverviewLegendRisks', 'rps' => 'RiskOverviewLegendPsychosocial', 'register' => 'RiskOverviewLegendRegisters'];
        $sheet->setCellValue($cellName(1, $rowIndex), $langs->transnoentities($legendTexts[$tab]));
        if ($tab == 'register' && $registerFilterLabel !== '') {
            $rowIndex++;
            $sheet->setCellValue($cellName(1, $rowIndex), $langs->transnoentities('RiskOverviewRegisterFilterShown', $registerFilterLabel));
        }
        $legend = [];
        if ($tab != 'register') {
            foreach (($tab == 'rps' ? $rpsLevelColours : $levelColours) as $level => $colours) {
                $legend[] = [$colours, $tab == 'rps' ? $level . ' ' . $rpsScale[$level]['label'] : $langs->transnoentities($levelLabels[$level])];
            }
            $legend[] = [$tab == 'rps' ? $rpsEmptyColours : $emptyColours, $langs->transnoentities('RiskOverviewNotConcerned') . ' : ' . $langs->transnoentities($tab == 'rps' ? 'RiskOverviewNoRatingHelp' : 'RiskOverviewNotConcernedHelp')];
        }
        foreach ($legend as $key) {
            $rowIndex++;
            $sheet->getStyle($cellName(3, $rowIndex))->applyFromArray($fill($key[0]));
            $sheet->setCellValue($cellName(1, $rowIndex), $key[1]);
        }

        // Printed on one page width, landscape, with the company and the date
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getHeaderFooter()->setOddHeader('&L' . $mysoc->name . '&R' . dol_print_date(dol_now(), 'day'));
    }
    $spreadsheet->setActiveSheetIndex(0);

    $fileName = dol_sanitizeFileName(dol_string_unaccent($langs->transnoentities('RiskOverviewPublicTitle') . ' ' . $mysoc->name . ' ' . dol_print_date(dol_now(), '%Y-%m-%d'))) . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $fileName) . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
}
