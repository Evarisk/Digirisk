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
 * \file    view/ticket/ticket_action_card.php
 * \ingroup digiriskdolibarr
 * \brief   One-click ticket action card — large tile UI for fast status / assignment / link actions.
 *
 * Issue #4443 — IHM ticket registre.
 */

if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs(['ticket', 'companies']);

// Get parameters
$id      = GETPOSTINT('id');
$trackId = GETPOST('track_id', 'alpha');
$action  = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Ticket($db);
if ($id > 0 || !empty($trackId)) {
    $object->fetch($id, '', $trackId);
    if ($object->id > 0) {
        $object->fetch_optionals();
    }
}

$hookmanager->initHooks([$moduleNameLowerCase . 'ticketactioncard', 'globalcard']);

// Security check
$permissionToRead  = $user->hasRight('ticket', 'read') && $user->hasRight($moduleNameLowerCase, 'read');
$permissionToWrite = $user->hasRight('ticket', 'write');
saturne_check_access($permissionToRead);

/*
 * View
 */

$title   = $langs->trans('TicketActionCard');
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $helpUrl);

// Page entry point — picker if no ticket loaded, otherwise the tile card.
if ($object->id <= 0) {
    require_once __DIR__ . '/../../core/tpl/ticket/ticket_action_card_picker.tpl.php';
} else {
    require_once __DIR__ . '/../../core/tpl/ticket/ticket_action_card_view.tpl.php';
}

// End of page
llxFooter();
$db->close();
