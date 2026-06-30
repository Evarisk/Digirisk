<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/config/ticket_kanban.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr ticket kanban configuration page.
 *          Controls ActionComm audit logging for each inline edit action.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

// Translations
saturne_load_langs(['admin']);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Initialize ticket kanban logging constants with defaults (all OFF)
$ticketKanbanLogDefaults = [
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_STATUS'          => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_ASSIGNEE'        => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_CATEGORY_ADD'    => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_CATEGORY_REMOVE' => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_TYPE'            => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_GROUP'           => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_SEVERITY'        => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_SUBJECT'         => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_DEADLINE'        => 0,
    'DIGIRISKDOLIBARR_TICKET_KANBAN_CLOSED_LIMIT'        => 50,
];
foreach ($ticketKanbanLogDefaults as $constName => $defaultVal) {
    if (!isset($conf->global->$constName)) {
        dolibarr_set_const($db, $constName, $defaultVal, 'chaine', 0, '', $conf->entity);
    }
}

if ($action == 'set_closed_limit') {
    $newLimit = max(0, GETPOSTINT('closed_limit'));
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_KANBAN_CLOSED_LIMIT', $newLimit, 'chaine', 0, '', $conf->entity);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'ticket_kanban', $title, -1, 'digiriskdolibarr_color@digiriskdolibarr');

print load_fiche_titre('<i class="fas fa-columns"></i> ' . $langs->trans('TicketKanbanLogging'), '', '');
print '<hr>';

// ActionComm logging toggles
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

$ticketKanbanLogs = [
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_STATUS'          => ['TicketKanbanLogStatus',         'TicketKanbanLogStatusDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_ASSIGNEE'        => ['TicketKanbanLogAssignee',        'TicketKanbanLogAssigneeDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_CATEGORY_ADD'    => ['TicketKanbanLogCategoryAdd',     'TicketKanbanLogCategoryAddDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_CATEGORY_REMOVE' => ['TicketKanbanLogCategoryRemove',  'TicketKanbanLogCategoryRemoveDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_TYPE'            => ['TicketKanbanLogType',            'TicketKanbanLogTypeDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_GROUP'           => ['TicketKanbanLogGroup',           'TicketKanbanLogGroupDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_SEVERITY'        => ['TicketKanbanLogSeverity',        'TicketKanbanLogSeverityDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_SUBJECT'         => ['TicketKanbanLogSubject',         'TicketKanbanLogSubjectDesc'],
    'DIGIRISKDOLIBARR_TICKET_KANBAN_LOG_DEADLINE'        => ['TicketKanbanLogDeadline',        'TicketKanbanLogDeadlineDesc'],
];

foreach ($ticketKanbanLogs as $constName => $transKeys) {
    print '<tr class="oddeven"><td>';
    print $langs->trans($transKeys[0]);
    print '</td><td>';
    print $langs->trans($transKeys[1]);
    print '</td>';
    print '<td class="center">';
    print ajax_constantonoff($constName);
    print '</td>';
    print '</tr>';
}

print '</table>';

// Closed tickets limit
print load_fiche_titre('<i class="fas fa-eye-slash"></i> ' . $langs->trans('TicketKanbanClosedLimit'), '', '');
print '<hr>';

$currentLimit = getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_KANBAN_CLOSED_LIMIT', 50);
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="action" value="set_closed_limit">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td>';
print '</tr>';
print '<tr class="oddeven"><td>' . $langs->trans('TicketKanbanClosedLimit') . '</td>';
print '<td>' . $langs->trans('TicketKanbanClosedLimitDesc') . '</td>';
print '<td class="center">';
print '<input type="number" name="closed_limit" value="' . (int) $currentLimit . '" min="0" max="9999" style="width:80px;text-align:center;">';
print ' <input type="submit" class="button smallpaddingimp" value="' . $langs->trans('Modify') . '">';
print '</td></tr>';
print '</table>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
