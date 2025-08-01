<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    view/ticket/ticket_management_dashboard.php
 * \ingroup digiriskdolibarr
 * \brief   Page with ticket dashboard and statistics
 */

if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$dashboard = new SaturneDashboard($db, $moduleNameLowerCase);

$upload_dir = $conf->$moduleNameLowerCase->multidir_output[$conf->entity ?? 1];

$hookmanager->initHooks([$moduleNameLowerCase . 'ticket_management_dashboard', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissionToRead = $user->hasRight($moduleNameLowerCase, 'read');
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
    // Actions adddashboardinfo, closedashboardinfo, dashboardfilter, generate_csv
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/dashboard_actions.tpl.php';
}

/*
 * View
 */

$title   = $langs->transnoentities('TicketManagementDashboard');
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $helpUrl);

print load_fiche_titre($title, '', 'ticket');

$head = ticketstats_prepare_head();
print dol_get_fiche_head($head, 'dashboard',$title, -1, 'ticket');

print '<div class="fichecenter">';

$moreParams = ['LoadTicketStatsDashboard' => 1];
$dashboard->show_dashboard($moreParams);

print '</div>';

// End of page
llxFooter();
$db->close();
