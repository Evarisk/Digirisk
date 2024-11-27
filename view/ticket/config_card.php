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
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');

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
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissionToRead = $user->hasRight('categorie', 'creer') && $user->hasRight('digiriskdolibarr', 'adminpage', 'read');;
saturne_check_access($permissionToRead, $object);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    $backurlforlist = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=1', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
            else $backtopage                                                                              = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
        }
    }

    if ($action == 'set_ticket_category_config') {
        $data['use_signatory'] = GETPOST('use_signatory');
        $data['photo_visible'] = GETPOST('photo_visible');

        $ticketExtraFields = ['digiriskelement', 'email', 'firstname', 'lastname', 'phone', 'location', 'date'];
        foreach ($ticketExtraFields as $ticketExtraField) {
            $extraFieldVisible  = $ticketExtraField . '_visible';
            $extraFieldRequired = $ticketExtraField . '_required';

            $data[$extraFieldVisible]  = GETPOST($extraFieldVisible);
            $data[$extraFieldRequired] = GETPOST($extraFieldRequired);
        }

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

$title   = $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $helpUrl);

$object->fetch_optionals();
$ticketCategoryConfig = json_decode($object->array_options['options_ticket_category_config']);

$head = categories_prepare_head($object, 'ticket');
print dol_get_fiche_head($head, 'test', $langs->trans($title), -1, 'category');

$backtolist = (GETPOST('backtolist') ? GETPOST('backtolist') : DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.urlencode($type));
$linkback = '<a href="'.dol_sanitizeUrl($backtolist).'">'.$langs->trans("BackToList").'</a>';
$object->next_prev_filter = 'type:=:'.((int) $object->type);
$object->ref = $object->label;
$morehtmlref = '<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.urlencode($type).'">'.$langs->trans("Root").'</a> >> ';
$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
foreach ($ways as $way) {
    $morehtmlref .= $way."<br>\n";
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'id', $linkback, ($user->socid ? 0 : 1), 'rowid', 'ref', $morehtmlref, '&type=ticket');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

$link = img_picto('', 'fa-cog', 'class="paddingrightonly"') . '<a href="' . dol_buildpath('digiriskdolibarr/admin/ticket/ticket.php', 1). '" target="_blank">' . $langs->transnoentities('Configuration') . '</a>';
print load_fiche_titre($langs->transnoentities('CategorieManagement'), $link, '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_ticket_category_config">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td>';
print '</tr>';

// Use signatory
print '<tr class="oddeven"><td>';
print img_picto('', 'fa-signature', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('Signatory'), $langs->transnoentities('PublicInterfaceUseSignatoryDescription'), 1, 'info') . '</td>';
print '</td><td class="center">';
print '<input type="checkbox" id="use_signatory" name="use_signatory"' . ($ticketCategoryConfig->use_signatory ? ' checked=""' : '') . '"> ';
print '</td></tr>';

if (getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE')) {
    if (dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0)) {
        print load_fiche_titre($langs->transnoentities('PublicInterfaceConfiguration'), $link, '');

        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td class="maxwidth150onsmartphone">' . $langs->trans('Parameters') . '</td>';
        print '<td class="center">' . $langs->transnoentities('Visible') . '</td>';
        print '<td class="center">' . $langs->transnoentities('Required') . '</td>';
        print '</tr>';

        // Photo visible
        print '<tr class="oddeven"><td class="maxwidth150onsmartphone">';
        print img_picto('', 'fa-image', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('TicketPhotoVisible'), $langs->transnoentities('TicketPhotoVisibleHelp'), 1, 'info') . '</td>';
        print '</td><td class="center">';
        print '<input type="checkbox" id="photo_visible" name="photo_visible"' . ($ticketCategoryConfig->photo_visible ? ' checked=""' : '') . '"> ';
        print '</td><td class="center"></td></tr>';

        $ticketExtraFields = [
            'digiriskelement' => ['picto' => 'fa-network-wired'],
            'email'           => ['picto' => 'fa-envelope'],
            'firstname'       => ['picto' => 'fa-user'],
            'lastname'        => ['picto' => 'fa-user'],
            'phone'           => ['picto' => 'fa-phone'],
            'location'        => ['picto' => 'fa-map-marker'],
            'date'            => ['picto' => 'fa-calendar-alt']
        ];
        foreach ($ticketExtraFields as $ticketExtraField => $ticketExtraFieldData) {
            $extraFieldVisible  = $ticketExtraField . '_visible';
            $extraFieldRequired = $ticketExtraField . '_required';

            // Extra field visible and required
            print '<tr class="oddeven"><td class="maxwidth150onsmartphone">';
            print img_picto('', $ticketExtraFieldData['picto'], 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('Ticket' . ucfirst($ticketExtraField) . 'Visible'), $langs->transnoentities('Ticket' . ucfirst($ticketExtraField) . 'VisibleHelp'), 1, 'info') . '</td>';
            print '</td><td class="center">';
            print '<input type="checkbox" id="' . $extraFieldVisible . '" name="' . $extraFieldVisible . '"' . ($ticketCategoryConfig->$extraFieldVisible ? ' checked=""' : '') . '"> ';
            print '</td><td class="center">';
            print '<input type="checkbox" id="' . $extraFieldRequired . '" name="' . $extraFieldRequired . '"' . ($ticketCategoryConfig->$extraFieldRequired ? ' checked=""' : '') . '"> ';
            print '</td></tr>';
        }

        print '</table>';
    }
}

print '</table>';
print $form->buttonsSaveCancel('Save', '');
print '</form>';
print '</div>';

// End of page
print dol_get_fiche_end();
llxFooter();
$db->close();
