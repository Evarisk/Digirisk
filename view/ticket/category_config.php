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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

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
$permissionToRead = $user->hasRight('categorie', 'creer') && $user->hasRight('digiriskdolibarr', 'ticket', 'categoryconfig');
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

        $data['use_signatory']         = GETPOST('use_signatory');
        $data['show_description']      = GETPOST('show_description');
        $data['mail_template']         = GETPOST('mail_template');
        $data['recipients']            = implode(',', GETPOST('recipients', 'array'));
        $data['photo_visible']         = GETPOST('photo_visible');
        $data['external_link']         = GETPOST('external_link');
        $data['external_link_new_tab'] = GETPOST('external_link_new_tab') ? 1 : 0;
        $data['validate_text']         = GETPOST('validate_text', 'restricthtml');
        $data['success_message']       = GETPOST('success_message', 'restricthtml');

        $extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_email'] = $langs->trans('Email');
        foreach ($extraFields->attributes['ticket']['label'] as $key => $field) {
            $extraFieldVisible  = $key . '_visible';
            $extraFieldRequired = $key . '_required';

            $data[$extraFieldVisible]  = GETPOST($extraFieldVisible);
            $data[$extraFieldRequired] = GETPOST($extraFieldRequired);
        }
        $config = json_decode($object->array_options['options_ticket_category_config'], true);
        if (empty($config)) {
            $config = [];
        }

        $object->table_element = 'categorie';
        $object->array_options['options_ticket_category_config'] = json_encode(array_merge($config, $data));
        $object->updateExtraField('ticket_category_config');

        setEventMessage('SavedConfig');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket');
        exit;
    }

    if ($action == 'addExtraField') {
        $label = GETPOST('extrafieldlabel', 'alpha');
        $type  = GETPOST('extrafieldtype', 'alpha');
        $categories = GETPOST('extrafieldcategories', 'array');

        if (empty($label) || empty($type)) {
            setEventMessages($langs->transnoentities('ErrorFieldRequired'), [], 'errors');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket&action=createExtra');
            exit;
        }

        // Auto-generate attrname from label: lowercase, no accents, only alphanumeric + underscore
        $attrname = strtolower(trim($label));
        $attrname = preg_replace('/[àáâãäå]/u', 'a', $attrname);
        $attrname = preg_replace('/[éèêë]/u', 'e', $attrname);
        $attrname = preg_replace('/[îï]/u', 'i', $attrname);
        $attrname = preg_replace('/[ôö]/u', 'o', $attrname);
        $attrname = preg_replace('/[ùûü]/u', 'u', $attrname);
        $attrname = preg_replace('/[ç]/u', 'c', $attrname);
        $attrname = preg_replace('/[^a-z0-9]+/', '_', $attrname);
        $attrname = trim($attrname, '_');
        $attrname = 'digiriskdolibarr_ticket_' . $attrname;

        $commonExtraFieldsValue = [
            'alwayseditable' => 1, 'list' => 1, 'help' => '', 'entity' => 0, 'langfile' => 'digiriskdolibarr@digiriskdolibarr', 'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('project')", 'moreparams' => ['css' => 'minwidth100 maxwidth300']
        ];

        $extraFieldsArrays = [
			$attrname => ['Label' => $label, 'type' => $type, 'elementtype' => ['ticket'], 'position' => 1000, 'list' => 5, 'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('categorie') && isModEnabled('ticket')", 'params' => ['categories' => $categories], 'moreparams' => []]
		];

        saturne_manage_extrafields($extraFieldsArrays, $commonExtraFieldsValue);

        setEventMessage($langs->transnoentities('SavedConfig'));
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket');
        exit;
    }

    if ($action == 'dragableSubmit') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            exit;
        }

        $config = json_decode($object->array_options['options_ticket_category_config'], true);
        if (empty($config)) {
            $config = [];
        }

        $object->table_element = 'categorie';
        $object->array_options['options_ticket_category_config'] = json_encode(array_merge($config, [
            'order' => $data
        ]));
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

if ($action != 'createExtra') {
$link = img_picto('', 'fa-cog', 'class="paddingrightonly"') . '<a href="' . dol_buildpath('digiriskdolibarr/admin/ticket/ticket.php', 1). '" target="_blank">' . $langs->transnoentities('GeneralConfig') . '</a>';
print load_fiche_titre($langs->transnoentities('CategorieManagement'), $link, '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_ticket_category_config">';

print '<table class="noborder centpercent param-table" data-btn="savebtn-1">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td>';
print '</tr>';

// Use signatory + Validate text
print '<tr class="oddeven"><td>';
print img_picto('', 'fa-signature', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('PublicInterfaceUseSignatory'), $langs->transnoentities('PublicInterfaceUseSignatoryDescription'), 1, 'info');
print '</td><td class="center">';
print '<div style="display:flex;align-items:center;gap:8px;">';
print '<input type="checkbox" id="use_signatory" name="use_signatory"' . ($ticketCategoryConfig->use_signatory ? ' checked=""' : '') . '"> ';
$doleditor = new DolEditor('validate_text', $ticketCategoryConfig->validate_text ?? '', '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</div>';
print '</td></tr>';

// Show category description
print '<tr class="oddeven"><td>';
print img_picto('', 'fa-font', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('TicketPublicInterfaceShowCategory'), $langs->transnoentities('TicketPublicInterfaceShowCategoryDescriptionHelp'), 1, 'info') . '</td>';
print '</td><td class="center">';
print '<input type="checkbox" id="show_description" name="show_description"' . ($ticketCategoryConfig->show_description ? ' checked=""' : '') . '"> ';
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

// External link
print '<tr class="oddeven"><td>';
print img_picto('', 'fa-external-link-alt', 'class="paddingrightonly"') . $form->textwithpicto($langs->transnoentities('ExternalURL'), $langs->transnoentities('ExternalLinkDescription'), 1, 'info') . '</td>';
print '</td><td class="center">';
print '<input type="url" name="external_link" id="external_link" class="marginleftonly" placeholder="https://demo.digirisk.com/ticket" pattern="https?://.*" value="' . $ticketCategoryConfig->external_link . '" />';
print '<input type="checkbox" id="external_link_new_tab" name="external_link_new_tab" class="marginleftonly" ' . ($ticketCategoryConfig->external_link_new_tab ? ' checked=""' : '') . '> ';
print '<label for="external_link_new_tab">' . $langs->transnoentities('OpenInNewTab') . '</label>';
print '</td></tr>';

// Get all categories and their configuration
$categories = $object->get_all_ways();
$categoriesConfig = [];
$order = $ticketCategoryConfig->order ?? [];
foreach ($categories[0] as $category) {
    if ($category->id == $id) {
        continue;
    }
    $categoriesConfig[$category->label] = json_decode($category->array_options['options_ticket_category_config'], true);
    if (isset($categoriesConfig[$category->label]['order'])) {
        $order = array_merge($categoriesConfig[$category->label]['order']);
    }
}

$parentCategoryIds = [];
foreach ($categories[0] as $category) {
    if ($category->id != $id) {
        $parentCategoryIds[] = $category->id;
    }
}

$successMessage = $langs->transnoentities('YouMustNotifyYourHierarchy');
foreach ($categoriesConfig as $categoryLabel => $categoryConfig) {
    if (!empty($categoryConfig['success_message'])) {
        $successMessage = $categoryConfig['success_message'];
    }
}

// Success Message
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->transnoentities("TicketSuccessMessage"), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody') . '</td>';
print '</td><td class="center">';
$doleditor = new DolEditor('success_message', $ticketCategoryConfig->success_message ?? $successMessage, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</td></tr>';

print '</table>';

print '<div class="center" bis_skin_checked="1">';
print '<input type="submit" class="button button-save savebtn-1" name="save" value="' . $langs->trans('Save') . '" disabled>';
print '</div>';
} // end if ($action != 'createExtra')

if (getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE')) {
    if (dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0)) {
        if ($action != 'createExtra') {
            $createExtraUrl = $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket&action=createExtra';
            print '<div class="tabsAction">';
            print '<a class="butAction" href="' . dol_sanitizeUrl($createExtraUrl) . '">' . img_picto('', 'add', 'class="paddingright"') . $langs->transnoentities('AddExtraField') . '</a>';
            print '</div>';
        }

        if ($action == 'createExtra') {
            print load_fiche_titre($langs->transnoentities('AddExtraField'), '', '');

            // Fetch all ticket categories for the Categories field
            $allTicketCategories = new Categorie($db);
            $ticketCategoriesList = $allTicketCategories->get_full_arbo('ticket');

            print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="addExtraField">';
            print '<table class="noborder centpercent">';
            print '<tbody>';

            // Label
            print '<tr>';
            print '<td class="titlefieldcreate fieldrequired">' . $langs->transnoentities('LabelOrTranslationKey') . '</td>';
            print '<td class="valeur"><input type="text" name="extrafieldlabel" id="label" class="width200" value="" autofocus="" spellcheck="false"></td>';
            print '</tr>';

            // Type
            print '<tr>';
            print '<td class="fieldrequired">' . $langs->transnoentities('Type') . '</td>';
            print '<td class="valeur">';
            if (empty($formadmin)) {
                include_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
                $formadmin = new FormAdmin($db);
            }
            print $formadmin->selectTypeOfFields('extrafieldtype', GETPOST('type', 'alpha'));
            print '</td>';
            print '</tr>';

            // Categories
            print '<tr>';
            print '<td>' . $form->textwithpicto($langs->transnoentities('Categories'), $langs->transnoentities('IfEmptyVisibleAllCategories'), 1, 'info') . '</td>';
            print '<td class="valeur">';
            if (!empty($ticketCategoriesList)) {
                $categoriesOptions = [];
                foreach ($ticketCategoriesList as $cat) {
                    $categoriesOptions[$cat['id']] = $cat['fulllabel'];
                }
                print Form::multiselectarray('extrafieldcategories', $categoriesOptions, [$id], 0, 0, 'minwidth200');
            } else {
                print $langs->transnoentities('NoCategories');
            }
            print ' <span class="opacitymedium">(' . $langs->transnoentities('IfEmptyVisibleAllCategories') . ')</span>';
            print '</td>';
            print '</tr>';

            print '</tbody>';
            print '</table>';

            print '<div class="center">';
            print '<input type="submit" class="button button-save" name="save" value="' . $langs->trans('Save') . '">';
            print '&nbsp;';
            print '<a class="button button-cancel" href="' . dol_sanitizeUrl($_SERVER['PHP_SELF'] . '?id=' . $id . '&type=ticket') . '">' . $langs->trans('Cancel') . '</a>';
            print '</div>';
            print '</form>';
        } else {
            print '<table class="noborder centpercent dragable-container param-table" data-loader="default" data-success-message="' . $langs->transnoentities('SavedConfig') . '" data-error-message="' . $langs->transnoentities('Error') . '" data-btn="savebtn-2">';
            ?>
            <script>
            $(document).ready(function(){
                $(".config-move-line").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
                $(".config-move-line").css("background-repeat","no-repeat");
                $(".config-move-line").css("background-position","center center");
            });
            </script>
            <?php
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans('Parameters') . '</td>';
            print '<td class="center">' . $langs->transnoentities('Visible') . '</td>';
            print '<td class="center">' . $langs->transnoentities('Required') . '</td>';
            print '<td></td>';
            print '</tr>';

            $keysWithValueOn = [];
            $fields          = [
                'digiriskdolibarr_ticket_service'   => ['picto' => 'fa-network-wired'],
                'digiriskdolibarr_ticket_email'     => ['picto' => 'fa-envelope'],
                'digiriskdolibarr_ticket_firstname' => ['picto' => 'fa-user'],
                'digiriskdolibarr_ticket_lastname'  => ['picto' => 'fa-user'],
                'digiriskdolibarr_ticket_phone'     => ['picto' => 'fa-phone'],
                'digiriskdolibarr_ticket_location'  => ['picto' => 'fa-map-marker'],
                'digiriskdolibarr_ticket_date'      => ['picto' => 'fa-calendar-alt'],
                'photo'                             => ['picto' => 'fa-image']
            ];


            $extraFields->attributes['ticket']['label']['digiriskdolibarr_ticket_email'] = $langs->trans('Email');
            $extraFields->attributes['ticket']['label']['photo'] = $langs->trans('Photo');

            uksort($extraFields->attributes['ticket']['label'], function($a, $b) use ($order) {
                $indexA = array_search($a, $order);
                $indexB = array_search($b, $order);

                $indexA = ($indexA === false) ? PHP_INT_MAX : $indexA;
                $indexB = ($indexB === false) ? PHP_INT_MAX : $indexB;

                return $indexA <=> $indexB;
            });
            foreach ($extraFields->attributes['ticket']['label'] as $key => $field) {
                $label = str_replace('digiriskdolibarr_ticket_', '', $key);
                $fieldCategories = $extraFields->attributes['ticket']['param'][$key]['options']['categories'] ?? [];
                $categoryOrParentInList = !empty($fieldCategories) && (
                    in_array($id, $fieldCategories) ||
                    !empty(array_filter($parentCategoryIds, function($parentId) use ($fieldCategories) { return in_array($parentId, $fieldCategories); }))
                );
                if ((strpos($key, 'digiriskdolibarr_ticket') === false && $key !== 'photo') ||
                    (!empty($fieldCategories) && !$categoryOrParentInList)) {
                    continue; // Goes to the next element if 'digiriskdolibarr_ticket' is not found
                }

                $extraFieldVisible  = $key . '_visible';
                $extraFieldRequired = $key . '_required';

                // Check if the field is visible or required in the other categories
                foreach ($categoriesConfig as $category) {
                    if (isset($category[$extraFieldVisible]) && $category[$extraFieldVisible] === 'on') {
                        $keysWithValueOn[$extraFieldVisible] = true;
                    }
                    if (isset($category[$extraFieldRequired]) && $category[$extraFieldRequired] === 'on') {
                        $keysWithValueOn[$extraFieldRequired] = true;
                    }
                }

                // Extra field visible and required
                print '<tr class="oddeven dragable-item" data-name="' . $key . '"><td>';
                print ($fields[$key]['picto'] ? img_picto('', $fields[$key]['picto'], 'class="paddingrightonly"') : getPictoForType($extraFields->attributes['ticket']['type'][$key])) . $form->textwithpicto($langs->transnoentities('Ticket' . ucfirst($label) . 'Visible'), $langs->transnoentities('Ticket' . ucfirst($label) . 'VisibleHelp'), 1, 'info') . '</td>';
                print '</td><td class="center">';
                print '<input type="checkbox" id="' . $extraFieldVisible . '" name="' . $extraFieldVisible . '"' . ($keysWithValueOn[$extraFieldVisible] || $ticketCategoryConfig->$extraFieldVisible ? ' checked' : '') . ($keysWithValueOn[$extraFieldVisible] ? ' disabled' : '') . '>';
                if ($keysWithValueOn[$extraFieldVisible]) {
                    print $form->textwithtooltip($langs->transnoentities('Inherited'), $langs->transnoentities('PermissionInheritedFromConfig'));
                }
                print '</td><td class="center">';
                if (!in_array($key, ['digiriskdolibarr_ticket_photo'])) {
                    print '<input type="checkbox" id="' . $extraFieldRequired . '" name="' . $extraFieldRequired . '"' . ($keysWithValueOn[$extraFieldRequired] || $ticketCategoryConfig->$extraFieldRequired ? ' checked=""' : '') . ($keysWithValueOn[$extraFieldRequired] ? ' disabled' : '') . '>';
                    if ($keysWithValueOn[$extraFieldRequired]) {
                        print $form->textwithtooltip($langs->transnoentities('Inherited'), $langs->transnoentities('PermissionInheritedFromConfig'));
                    }
                }
                print '</td>';
                print '<td class="config-move-line">';
                print '</tr>';
            }

            print '</table>';
        }
    }
}

print '</table>';
if ($action != 'createExtra') {
    print '<div class="center" bis_skin_checked="1">';
    print '<input type="submit" class="button button-save savebtn-2" name="save" value="' . $langs->trans('Save') . '" disabled>';
    print '<div>';
    print '</form>';
    print '</div>';
}

// End of page
print dol_get_fiche_end();
llxFooter();
$db->close();
