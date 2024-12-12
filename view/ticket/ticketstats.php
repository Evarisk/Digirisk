<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    view/ticket/ticketstats.php
 * \ingroup digiriskdolibarr
 * \brief   Page with tickets statistics
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
if (isModEnabled('category')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Load DigiriskDolibarr librairies
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action           = GETPOST('action', 'aZ09');
$socid            = GETPOST('socid', 'int');
$digiriskElements = GETPOST('digiriskElements', 'array');
$categories       = GETPOST('categories', 'array');
$userID           = GETPOST('userID', 'int');
$userAssignID     = GETPOST('userAssignID', 'int');
$status           = GETPOST('status', 'array');

// Initialize technical objects
$object          = new Ticket($db);
$dashboard       = new SaturneDashboard($db, 'digiriskdolibarr');
$digiriskElement = new DigiriskElement($db);

// Initialize view objects
$form = new Form($db);

// Get date parameters
$dateStart = dol_mktime(0, 0, 0, GETPOST('dateStartmonth', 'int'), GETPOST('dateStartday', 'int'), GETPOST('dateStartyear', 'int'));
$dateEnd   = dol_mktime(23, 59, 59, GETPOST('dateEndmonth', 'int'), GETPOST('dateEndday', 'int'), GETPOST('dateEndyear', 'int'));

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

$hookmanager->initHooks(['ticketstats', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check
$permissionToRead = $user->rights->ticket->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Actions adddashboardinfo, closedashboardinfo, generate_csv
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/dashboard_actions.tpl.php';
}

/*
 * View
 */

$title    = $langs->trans('TicketStatistics');
$help_url = 'FR:Module_Digirisk#Statistiques_des_tickets';

saturne_header(0, '', $title, $help_url);

print load_fiche_titre($title, '', 'ticket');

$head = ticketstats_prepare_head();
print dol_get_fiche_head($head, 'byyear', $title, -1);

$moreJoin  = '';
$moreWhere = '';

if ($socid > 0) {
    $moreWhere .= ' AND t.fk_soc = ' . $socid;
}

if (is_array($digiriskElements)) {
    if (in_array($langs->trans('All'), $digiriskElements)) {
        unset($digiriskElements[array_search($langs->trans('All'), $digiriskElements)]);
    } else if (in_array('IS NULL', $digiriskElements)) {
        unset($digiriskElements[array_search($langs->trans('None'), $digiriskElements)]);
        $moreJoin  .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'ticket_extrafields as tkextra ON t.rowid = tkextra.fk_object';
        $moreJoin  .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskelement as e ON tkextra.digiriskdolibarr_ticket_service = e.rowid';
        $moreWhere .= ' AND e.rowid IS NULL';
    } else if (!empty($digiriskElements)) {
        $moreJoin  .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'ticket_extrafields as tkextra ON t.rowid = tkextra.fk_object';
        $moreJoin  .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskelement as e ON tkextra.digiriskdolibarr_ticket_service = e.rowid';
        $moreWhere .= ' AND e.rowid IN (' . $db->sanitize(implode(',', $digiriskElements)) . ')';
    }
}

if (is_array($categories)) {
    if (in_array($langs->trans('All'), $categories)) {
        unset($categories[array_search($langs->trans('All'), $categories)]);
    } else if (in_array('IS NULL', $categories)) {
        unset($categories[array_search($langs->trans('None'), $categories)]);
        $moreJoin  .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_ticket as cattk ON (t.rowid = cattk.fk_ticket)';
        $moreWhere .= ' AND cattk.fk_categorie IS NULL';
    } else if (!empty($categories)) {
        $moreJoin  .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_ticket as cattk ON (t.rowid = cattk.fk_ticket)';
        $moreWhere .= ' AND cattk.fk_categorie IN (' . $db->sanitize(implode(',', $categories)) . ')';
    }
}

if ($userID > 0) {
    $moreWhere .= ' AND t.fk_user_create = ' . $userID;
}

if ($userAssignID > 0) {
    $moreWhere .= ' AND t.fk_user_assign = ' . $userAssignID;
}

if (is_array($status)) {
    if (in_array($langs->trans('All'), $status)) {
        unset($status[array_search($langs->trans('All'), $status)]);
    } else if (in_array('IS NULL', $status)) {
        unset($status[array_search($langs->trans('None'), $status)]);
        $moreWhere .= ' AND t.fk_statut IS NULL';
    } else if (!empty($status)) {
        $moreWhere .= ' AND t.fk_statut IN (' . $db->sanitize(implode(',', $status)) . ')';
    }
}

if (!empty($dateStart) && !empty($dateEnd)) {
    $moreWhere .= " AND t.datec BETWEEN '" . $db->idate($dateStart) . "' AND '" . $db->idate($dateEnd) . "'";
}

print '<div class="fichecenter">';

// Show filter box
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?refresh=1">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

$all  = [$langs->trans('All') => $langs->trans('All')];
$none = ['IS NULL' => $langs->trans('None')];

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">' . $langs->trans('Filter') . '</td></tr>';

// Company
print '<tr><td>' . $form->textwithpicto($langs->trans('ThirdParty'), $langs->trans('ThirdPartyHelp')) . '</td><td>';
print img_picto('', 'company', 'class="pictofixedwidth"');
print $form->select_company($socid, 'socid', '', 1, 0, 0, [], 0, 'widthcentpercentminusx maxwidth300');
print '</td></tr>';

// DigiriskElement
print '<tr><td>' . $form->textwithpicto($langs->trans('GP/UT'), $langs->trans('GP/UTHelp')) . '</td><td>';
$digiriskElementsArray  = $none;
$activeDigiriskElements = $digiriskElement->getActiveDigiriskElements();
if (is_array($activeDigiriskElements) && !empty($activeDigiriskElements)) {
    foreach ($activeDigiriskElements as $digiriskElement) {
        $digiriskElementsArray[$digiriskElement->id] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
    }
}
print $form->multiselectarray('digiriskElements', $all + $digiriskElementsArray, (!empty(GETPOST('refresh', 'int')) ? GETPOST('digiriskElements', 'array') : $all + $digiriskElementsArray), 0, 0, 'minwidth100imp widthcentpercentminusx maxwidth300');
print '</td></tr>';

// Category
if (isModEnabled('categorie')) {
    print '<tr><td>' . $form->textwithpicto($langs->trans('Category') . ' ' . lcfirst($langs->trans('Ticket')), $langs->trans('CategoryTicketHelp')) . '</td><td>';
    $cateArbo = $form->select_all_categories(Categorie::TYPE_TICKET, null, 'parent', null, null, 1);
    print img_picto('', 'category', 'class="pictofixedwidth"');
    print $form->multiselectarray('categories', $all + $none + $cateArbo, (!empty(GETPOST('refresh', 'int')) ? GETPOST('categories', 'array') : $all + $none + $cateArbo), 0, 0, 'minwidth100imp widthcentpercentminusx maxwidth300');
    print '</td></tr>';
}

// User
print '<tr><td>' . $form->textwithpicto($langs->trans('CreatedBy'), $langs->trans('CreatedByHelp')) . '</td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userID, 'userID', 1, '', 0, '', '', $conf->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');

// Assign at user
print '<tr><td>' . $form->textwithpicto($langs->trans('AssignedTo'), $langs->trans('AssignedToHelp')) . '</td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userAssignID, 'userAssignID', 1, '', 0, '', '', $conf->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');

// Status
print '<tr><td>' . $form->textwithpicto($langs->trans('Status'), $langs->trans('StatusHelp')) . '</td><td>';
print $form->multiselectarray('status', $all + $none + $object->labelStatusShort, (!empty(GETPOST('refresh', 'int')) ? GETPOST('status', 'array') : $all + $none + $object->labelStatusShort), 0, 0, 'minwidth100imp widthcentpercentminusx maxwidth300', 1);
print '</td></tr>';

// DateRange
$startYear = strftime('%Y', dol_now()) - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$startDay  = dol_mktime(0, 0, 0, getDolGlobalInt('SOCIETE_FISCAL_MONTH_START'), 1, $startYear);
print '<tr><td>' . $langs->trans('DateRange') . '</td><td>';
print $langs->trans('From') . $form->selectDate((!empty($dateStart) ? $dateStart : $startDay), 'dateStart');
print $langs->trans('At') . $form->selectDate((!empty($dateEnd) ? $dateEnd : dol_now()), 'dateEnd');
print '</td></tr>';

print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button" value="' . $langs->trans('Refresh') . '"></td></tr>';

print '</table>';
print '</form>';

$moreParams = [
    'LoadTicketDashboard' => 1,
    'join'                => $moreJoin,
    'where'               => $moreWhere
];

$dashboard->show_dashboard($moreParams);

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
