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
 * \file    view/ticket/category_config.php
 * \ingroup digiriskdolibarr
 * \brief   Page to manage the category configuration of the ticket
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['users']);

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
$userTmp     = new User($db);

// Initialize view objects
$form     = new Form($db);
$formMail = new FormMail($db);

$hookmanager->initHooks(['digiriskelementcard', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extraFields->fetch_name_optionals_label('ticket');

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
    if ($action == 'set_ticket_category_config') {
        $data['use_signatory']    = (int) (GETPOST('use_signatory') == 'on');
        $data['show_description'] = (int) (GETPOST('show_description') == 'on');
        $data['mail_template']    = GETPOST('mail_template');
        $data['recipients']       = implode(',', GETPOST('recipients', 'array'));

        $extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_email'] = $langs->trans('Email');
        $extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_photo'] = $langs->trans('Photo');
        $extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_digiriskelement'] = $langs->transnoentities('Service');

        $config = json_decode($object->array_options['options_ticket_category_config'], true) ?? [];

        unset($extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_service']);
        foreach ($extraFields->attributes['ticket']['label'] as $key => $field) {
            $data[$key] = [
                'visible'  => (int) (GETPOST($key . '_visible') == 'on'),
                'required' => (int) (GETPOST($key . '_required') == 'on'),
                'position' => $config[$key]['position'] ?? 0,
            ];
        }

        $object->table_element = 'categorie';
        $object->array_options['options_ticket_category_config'] = json_encode($data);

        $result = $object->updateExtraField('ticket_category_config');

        if ($result < 0) {
            throw new Exception($object->error);
        }

        setEventMessage('SavedConfig');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket');
        exit;
    }

    if ($action == 'draggableSubmit') {

        $order = json_decode(file_get_contents('php://input'), true);
        if (empty($order)) {
            exit;
        }

        $data = [];
        if (!empty($object->array_options['options_ticket_category_config'])) {
            $data = json_decode($object->array_options['options_ticket_category_config'], true);
        }
        foreach ($order as $key => $value) {
            if (!isset($data[$value])) {
                $data[$value] = [];
            }
            $data[$value]['position'] = $key;
        }

        $object->array_options['options_ticket_category_config'] = json_encode($data);
        $object->updateExtraField('ticket_category_config');
        $action = '';
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
print dol_get_fiche_head($head, 'config', $langs->trans($title), -1, 'category');

$backurlforlist = dol_buildpath('categories/index.php?type=ticket&mainmenu=ticket', 1);
$linkback = '<a href="' . dol_sanitizeUrl($backurlforlist) . '">' . $langs->trans('BackToList') . '</a>';
$object->next_prev_filter = 'type:=:'.((int) $object->type);
$object->ref = $object->label;
$morehtmlref = '<br><div class="refidno"><a href="' . dol_sanitizeUrl($backurlforlist) . '">' . $langs->trans('Root') . '</a> >> ';
$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
foreach ($ways as $way) {
    $morehtmlref .= $way."<br>\n";
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'id', $linkback, ($user->socid ? 0 : 1), 'rowid', 'ref', $morehtmlref, '&type=ticket');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

$link = img_picto('', 'fa-cog', 'class="paddingrightonly"') . '<a href="' . dol_buildpath('digiriskdolibarr/admin/ticket/ticket.php', 1). '" target="_blank">' . $langs->transnoentities('GeneralConfig') . '</a>';
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

// Show category description
print '<tr class="oddeven"><td>';
print img_picto('', 'fa-font', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('Description'), $langs->transnoentities('TicketPublicInterfaceShowCategoryDescriptionHelp'), 1, 'info') . '</td>';
print '</td><td class="center">';
print '<input type="checkbox" id="show_description" name="show_description"' . ($ticketCategoryConfig->show_description ? ' checked=""' : '') . '"> ';
if (getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_SHOW_CATEGORY_DESCRIPTION')) {
    print $form->textwithtooltip($langs->transnoentities('Inherited'), $langs->transnoentities('PermissionInheritedFromConfig'));
}
print '</td></tr>';

// Email template
print '<tr class="oddeven"><td>';
print img_picto('', 'fa-envelope', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('EmailTemplate'), $langs->transnoentities('EmailTemplateDescription'), 1, 'info') . '</td>';
print '</td><td class="center">';
$formMail->fetchAllEMailTemplate('ticket_send', $user, $langs);
$emailTemplateLabels = [];
foreach ($formMail->lines_model as $emailTemplateLine) {
    $emailTemplateLabels[$emailTemplateLine->id] = $emailTemplateLine->label;
}
if (!empty($emailTemplateLabels)) {
    print Form::selectarray('mail_template', $emailTemplateLabels, $ticketCategoryConfig->mail_template, 1);
} else {
    print $langs->transnoentities('NoEmailTemplate');
}
print '</td></tr>';

// Recipient
print '<tr class="oddeven"><td>';
print img_picto('', 'fa-user', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('Recipient'), $langs->transnoentities('RecipientDescription'), 1, 'info') . '</td>';
print '</td><td class="center">';
$userTmp->fetchAll('ASC', 't.lastname', 0, 0, "(t.statut:=:1) AND (t.employee:=:1) AND (t.email:isnot:NULL) AND (t.email:!=:'')", 'AND', true);
if (is_array($userTmp->users) && !empty($userTmp->users)) {
    $recipients = [];
    foreach ($userTmp->users as $recipient) {
        $recipients[$recipient->id] = dolGetFirstLastname($recipient->firstname, $recipient->lastname) . ' (' . $recipient->email . ')';
    }
    print Form::multiselectarray('recipients', $recipients, explode(',', $ticketCategoryConfig->recipients));
} else {
    print $langs->transnoentities('NoRecipient');
}
print '</td></tr>';

if (getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE')) {
    if (getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS')) {
        print load_fiche_titre($langs->transnoentities('PublicInterfaceConfiguration'), $link, '');

        print '<table class="noborder centpercent" id="draggableTable">';
        print '<tr class="liste_titre">';
        print '<td>' . $langs->trans('Parameters') . '</td>';
        print '<td class="center">' . $langs->transnoentities('Visible') . '</td>';
        print '<td class="center">' . $langs->transnoentities('Required') . '</td>';
        print '</tr>';

        // Photo visible
        print '<tr class="oddeven" draggable="true" data-name="digiriskdolibarr_ticket_photo"><td>';
        print img_picto('', 'fa-image', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('TicketPhotoVisible'), $langs->transnoentities('TicketPhotoVisibleHelp'), 1, 'info') . '</td>';
        print '</td><td class="center">';
        print '<input type="checkbox" id="digiriskdolibarr_ticket_photo_visible" name="digiriskdolibarr_ticket_photo_visible"' . ($ticketCategoryConfig->digiriskdolibarr_ticket_photo->visible ? ' checked=""' : '') . '"> ';
        print '</td><td class="center"></td></tr>';

        // Get all categories and their configuration
        $categories = $object->get_all_ways();
        $categoriesConfig = [];
        foreach ($categories[0] as $category) {
            if ($category->id == $id) {
                continue;
            }
            $categoriesConfig[$category->label] = json_decode($category->array_options['options_ticket_category_config'], true);
        }

        $keysWithValueOn = [];
        $fields          = [
            'digiriskdolibarr_ticket_digiriskelement' => ['picto' => 'fa-network-wired'],
            'digiriskdolibarr_ticket_email'           => ['picto' => 'fa-envelope'],
            'digiriskdolibarr_ticket_firstname'       => ['picto' => 'fa-user'],
            'digiriskdolibarr_ticket_lastname'        => ['picto' => 'fa-user'],
            'digiriskdolibarr_ticket_phone'           => ['picto' => 'fa-phone'],
            'digiriskdolibarr_ticket_location'        => ['picto' => 'fa-map-marker'],
            'digiriskdolibarr_ticket_date'            => ['picto' => 'fa-calendar-alt']
        ];

        $extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_email'] = $langs->trans('Email');
        $extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_digiriskelement'] = $langs->transnoentities('Service');
        unset($extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_service']);

        // remove from extrafield list all fields that are not ticket related
        $extraFields->attributes['ticket']['label'] = array_filter($extraFields->attributes['ticket']['label'], function ($key) {
            return strpos($key, 'digiriskdolibarr_ticket') !== false;
        }, ARRAY_FILTER_USE_KEY);

        uksort($extraFields->attributes['ticket']['label'], function ($a, $b) use ($ticketCategoryConfig) {
            return $ticketCategoryConfig->{$a}->position <=> $ticketCategoryConfig->{$b}->position;
        });

        foreach ($extraFields->attributes['ticket']['label'] as $key => $field) {
            $label = str_replace('digiriskdolibarr_ticket_', '', $key);

            $extraFieldVisible  = $key . '_visible';
            $extraFieldRequired = $key . '_required';

            // Check if the field is visible or required in the other categories
            foreach ($categoriesConfig as $category) {
                $keysWithValueOn[$key] = $keysWithValueOn[$key] ?? [];
                if (!empty($category[$key]['visible'])) {
                    $keysWithValueOn[$key]['visible'] = true;
                }
                if (!empty($category[$key]['required'])) {
                    $keysWithValueOn[$key]['required'] = true;
                }
            }

            // Extra field visible and required
            print '<tr class="oddeven" draggable="true" data-name="' . $key . '"><td>';
            print ($fields[$key]['picto'] ? img_picto('', $fields[$key]['picto'], 'class="paddingrightonly"') : getPictoForType($extraFields->attributes['ticket']['type'][$key])) . $form->textwithpicto($langs->transnoentities('Ticket' . ucfirst($label) . 'Visible'), $langs->transnoentities('Ticket' . ucfirst($label) . 'VisibleHelp'), 1, 'info') . '</td>';
            print '</td><td class="center">';
            print '<input type="checkbox" id="' . $extraFieldVisible . '" name="' . $extraFieldVisible . '"' . (getDolGlobalInt(dol_strtoupper($key) . '_VISIBLE') || !empty($keysWithValueOn[$key]['visible']) || !empty($ticketCategoryConfig->$key->visible) ? ' checked' : '') . (getDolGlobalInt(dol_strtoupper($key) . '_VISIBLE') || !empty($keysWithValueOn[$key]['visible']) ? ' disabled' : '') . '>';
            if (getDolGlobalInt(dol_strtoupper($key) . '_VISIBLE') || !empty($keysWithValueOn[$key]['visible'])) {
                print $form->textwithtooltip($langs->transnoentities('Inherited'), $langs->transnoentities('PermissionInheritedFromConfig'));
            }
            print '</td><td class="center">';
            print '<input type="checkbox" id="' . $extraFieldRequired . '" name="' . $extraFieldRequired . '"' . (getDolGlobalInt(dol_strtoupper($key) . '_REQUIRED') || !empty($keysWithValueOn[$key]['required']) || !empty($ticketCategoryConfig->$key->required) ? ' checked=""' : '') . (getDolGlobalInt(dol_strtoupper($key) . '_REQUIRED') || !empty($keysWithValueOn[$key]['required']) ? ' disabled' : '') . '>';
            if (getDolGlobalInt(dol_strtoupper($key) . '_REQUIRED') || !empty($keysWithValueOn[$key]['required'])) {
                print $form->textwithtooltip($langs->transnoentities('Inherited'), $langs->transnoentities('PermissionInheritedFromConfig'));
            }
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
