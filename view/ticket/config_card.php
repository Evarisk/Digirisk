<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    view/ticket/config.php
 * \ingroup digiriskdolibarr
 * \brief   Page to create/edit/view config
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/categories.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object      = new Categorie($db);
$extraFields = new ExtraFields($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['digiriskelementcard', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extraFields->fetch_name_optionals_label($object->table_element);

// Initialize array of search criterias
if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiriskelement->delete;
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=1', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
            else $backtopage                                                                              = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
        }
    }

    if ($action == 'set_ticket_category_config') {
        $data['use_signatory'] = GETPOST('use_signatory');

        $object->table_element = 'categories';
        $object->array_options['options_ticket_category_config'] = json_encode($data);
        $object->updateExtraField('ticket_category_config');

        setEventMessage('SavedConfig');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket');
        exit;
    }
}

/*
 * View
 */

$title    = $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $help_url);

// Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create'))) {
    $object->fetch_optionals();
    $ticketCategoryConfig = json_decode($object->array_options['options_ticket_category_config']);

    $head = categories_prepare_head($object, 'ticket');
    print dol_get_fiche_head($head, 'test', $langs->trans($title), -1, 'category');

    // Object card
    // ------------------------------------------------------------
    saturne_banner_tab($object);

    print '<div class="fichecenter">';

    print load_fiche_titre($langs->trans('test'), '', '');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="set_ticket_category_config">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Parameters') . '</td>';
    print '<td class="center">' . $langs->trans('ShortInfo') . '</td>';
    print '<td class="center">' . $langs->trans('Value') . '</td>';
    print '</tr>';

    // Use signatory
    print '<tr class="oddeven"><td>';
    print $langs->transnoentities('TicketPublicInterfaceUseSignatory');
    print '</td><td class="center">';
    print $form->textwithpicto('', $langs->transnoentities('TicketPublicInterfaceUseSignatoryDescription'), 1, 'help', '', 0, 3, 'abconsmartphone');
    print '</td>';
    print '<td class="center">';
    print '<input type="checkbox" id="use_signatory" name="use_signatory"' . ($ticketCategoryConfig->use_signatory ? ' checked=""' : '') . '"> ';
    print '</td></tr>';

    print '</table>';
    print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
    print '</form>';
    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
