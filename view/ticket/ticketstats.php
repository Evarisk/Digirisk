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
if (!empty($conf->category->enabled)) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Load DigiriskDolibarr librairies
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../class/ticketdigiriskstats.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action              = GETPOST('action', 'aZ09');
$socid               = GETPOST('socid', 'int');
$digiriskelementlist = GETPOST('digiriskelementlist', 'array');
$ticketcats          = GETPOST('ticketcats', 'array');
$userid              = GETPOST('userid', 'int');
$userassignid        = GETPOST('userassignid', 'int');
$object_status       = GETPOST('object_status', 'array');

// Initialize technical objects
$object          = new Ticket($db);
$dashboard       = new SaturneDashboard($db, 'digiriskdolibarr');
$digiriskelement = new DigiriskElement($db);

// Initialize view objects
$form = new Form($db);

$nowyear   = strftime("%Y", dol_now());
$year      = GETPOST('year') > 0 ? GETPOST('year', 'int') : $nowyear;
//$startyear = $year - (empty($conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS) ? 2 : max(1, min(10, $conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS)));
$date_start = dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
$date_end   = dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));
$startyear = strftime("%Y", !empty($date_start) ? $date_start : dol_now());
$endyear   = strftime("%Y", !empty($date_end) ? $date_end : dol_now() + 1);
$datestart = dol_print_date((!empty($date_start) ? $date_start : dol_now()), 'dayxcard');
$dateend   = dol_print_date((!empty($date_end) ? $date_end : dol_now()), 'dayxcard');

//Security check
$permissiontoread = $user->rights->ticket->read;

saturne_check_access($permissiontoread);

/*
 * View
 */

$title    = $langs->trans('TicketStatistics');
$help_url = 'FR:Module_Digirisk#Statistiques_des_tickets';

saturne_header(0, '', $title, $help_url);

print load_fiche_titre($title, '', 'ticket');

$head = ticketstats_prepare_head();
print dol_get_fiche_head($head, 'byyear', $title, -1);

if (is_array($object_status)) {
	if (in_array($langs->trans('All'), $object_status)) {
		unset($object_status[array_search($langs->trans('All'), $object_status)]);
	} else {
		if (!empty($object_status)) {
			$moreWhere .= ' AND tk.fk_statut IN ('.$db->sanitize(implode(',', $object_status)).')';
		} else if (!empty(GETPOST('refresh', 'int'))) {
			$moreWhere .= ' AND tk.fk_statut IS NULL';
		}
	}
}

if (is_array($ticketcats)) {
	if (in_array($langs->trans('All'), $ticketcats)) {
		unset($ticketcats[array_search($langs->trans('All'), $ticketcats)]);
	} else {
		if (!empty($ticketcats)) {
			$moreFrom .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_ticket as cattk ON (tk.rowid = cattk.fk_ticket)';
			$moreWhere .= ' AND cattk.fk_categorie IN ('.$db->sanitize(implode(',', $ticketcats)).')';
		} else if (!empty(GETPOST('refresh', 'int'))) {
			$moreFrom .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_ticket as cattk ON (tk.rowid = cattk.fk_ticket)';
			$moreWhere .= ' AND cattk.fk_categorie IS NULL';
		}
	}
}

if (is_array($digiriskelementlist)) {
	if (in_array($langs->trans('All'), $digiriskelementlist)) {
		unset($digiriskelementlist[array_search($langs->trans('All'), $digiriskelementlist)]);
	} else {
		if (!empty($digiriskelementlist)) {
			$moreJoin .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ticket_extrafields as tkextra ON tk.rowid = tkextra.fk_object';
			$moreJoin .= ' LEFT JOIN '.MAIN_DB_PREFIX.'digiriskdolibarr_digiriskelement as e ON tkextra.digiriskdolibarr_ticket_service = e.rowid';
			$moreWhere .= ' AND e.rowid IN ('.$db->sanitize(implode(',', $digiriskelementlist)).')';
		} else if (!empty(GETPOST('refresh', 'int'))) {
			$moreJoin .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ticket_extrafields as tkextra ON tk.rowid = tkextra.fk_object';
			$moreJoin .= ' LEFT JOIN '.MAIN_DB_PREFIX.'digiriskdolibarr_digiriskelement as e ON tkextra.digiriskdolibarr_ticket_service = e.rowid';
			$moreWhere .= ' AND e.rowid IS NULL';
		}
	}
}

print '<div class="fichecenter"><div class="fichehalfleft">';

// Show filter box
print '<form class="ticketstats" name="stats" method="POST" action="' . $_SERVER['PHP_SELF'] . '?refresh=1">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

$all = [$langs->trans('All') => $langs->trans('All')];

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">' . $langs->trans('Filter') . '</td></tr>';

// Company
print '<tr><td class="left">'.$form->textwithpicto($langs->trans("ThirdParty"), $langs->trans("ThirdPartyHelp")).'</td><td class="left">';
print img_picto('', 'company', 'class="pictofixedwidth"');
print $form->select_company($socid, 'socid', '', 1, 0, 0, array(), 0, 'widthcentpercentminusx maxwidth300');
print '</td></tr>';

// DigiriskElement
print '<tr><td class="left">'.$form->textwithpicto($langs->trans("GP/UT"), $langs->trans("GP/UTHelp")).'</td><td class="left">';
$deletedElements = $digiriskelement->getMultiEntityTrashList();
if (empty($deletedElements)) {
    $deletedElements = [0];
}
$objectList = saturne_fetch_all_object_type('digiriskelement', '', '', 0, 0,  ['customsql' => 'rowid NOT IN (' . implode(',', $deletedElements) . ')']);
$digiriskElementsData  = [];
if (is_array($objectList) && !empty($objectList)) {
	foreach ($objectList as $digiriskElement) {
		$digiriskElementsData[$digiriskElement->id] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
	}
}
$digiriskElementsData = $all + $digiriskElementsData;
print $form->multiselectarray('digiriskelementlist', $digiriskElementsData, ((!empty(GETPOST('refresh', 'int'))) ? GETPOST('digiriskelementlist', 'array') : $digiriskelementlist), 0, 0, 'widthcentpercentminusx maxwidth300');
print '</td></tr>';

// Category
if (!empty($conf->category->enabled)) {
	$cat_type = Categorie::TYPE_TICKET;
	$cat_label = $langs->trans("Category") . ' ' .lcfirst($langs->trans("Ticket"));
	print '<tr><td>'.$form->textwithpicto($cat_label, $langs->trans("CategoryTicketHelp")).'</td><td>';
	$cate_arbo = $form->select_all_categories($cat_type, null, 'parent', null, null, 1);
	print img_picto('', 'category', 'class="pictofixedwidth"');
	$cate_arbo = $all + $cate_arbo;
	print $form->multiselectarray('ticketcats', $cate_arbo, ((!empty(GETPOST('refresh', 'int'))) ? GETPOST('ticketcats', 'array') : $cate_arbo), 0, 0, 'widthcentpercentminusx maxwidth300');
	print '</td></tr>';
}

// User
print '<tr><td class="left">'.$form->textwithpicto($langs->trans("CreatedBy"), $langs->trans("CreatedByHelp")) .'</td><td class="left">';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', $conf->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');

// Assign at user
print '<tr><td class="left">'.$form->textwithpicto($langs->trans("AssignedTo"), $langs->trans("AssignedToHelp")).'</td><td class="left">';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userassignid, 'userassignid', 1, '', 0, '', '', $conf->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');

// Status
print '<tr><td class="left">'.$form->textwithpicto($langs->trans("Status"), $langs->trans("StatusHelp")).'</td><td class="left">';
$liststatus = $object->statuts_short;
$liststatus = $all + $liststatus;
print $form->multiselectarray('object_status', $liststatus, ((!empty(GETPOST('refresh', 'int'))) ? GETPOST('object_status', 'array') : $liststatus), 0, 0, 'widthcentpercentminusx maxwidth300', 1);
print '</td></tr>';

//DateRange -- Plage de date
if (!empty($conf->global->SOCIETE_FISCAL_MONTH_START)) {
	$startday = dol_mktime(0, 0, 0, $conf->global->SOCIETE_FISCAL_MONTH_START, 1, strftime("%Y", dol_now()));
} else {
	$startday = dol_now();
}
print '<tr><td class="left">' . $langs->trans("DateRange") . '</td><td class="left">';
print $langs->trans('From') . $form->selectDate((!empty($date_start) ? $date_start : $startday), 'datestart', 0, 0, 0, '', 1);
print $langs->trans('At') . $form->selectDate((!empty($date_end) ? $date_end : dol_time_plus_duree($startday, 1, 'y')), 'dateend', 0, 0, 0, '', 1);
print '</td></tr>';

print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button" value="' . $langs->trans("Refresh") . '"></td></tr>';

print '</table>';
print '</form>';

print '</div><div class="fichehalfright">';

$moreParams = [
    'loadRiskAssessmentDocument' => 0,
    'loadAccident'               => 0,
    'loadEvaluator'              => 0,
    'loadDigiriskResources'      => 0,
    'loadRisk'                   => 0,
    'loadTask'                   => 0,
    'socid'                      => $socid,
    'userid'                     => $userid > 0 ? $userid: 0,
    'userassignid'               => $userassignid > 0 ? $userassignid: 0,
    'categticketid'              => $ticketcats[0] > 0 ? $ticketcats[0] : 0,
    'from'                       => $moreFrom,
    'join'                       => $moreJoin,
    'where'                      => $moreWhere,
];
$dashboard->show_dashboard($moreParams);

print '</div></div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
