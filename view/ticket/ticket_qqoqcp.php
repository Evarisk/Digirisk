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
 * \file    view/ticket/ticket_qqoqcp.php
 * \ingroup digiriskdolibarr
 * \brief   QQOQCCP tab of a ticket (#5549): What, Who, Where, When, How, How much and Why of the problem, kept in
 *          ticket extrafields, and its document (TicketQqoqcpDocument).
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load Saturne libraries
dol_include_once('/saturne/lib/documents.lib.php');

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/ticketqqoqcpdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';

// Load translation files required by the page
saturne_load_langs(['ticket', 'projects']);

// Get parameters
$id      = GETPOSTINT('id');
$ref     = GETPOST('ref', 'alpha');
$trackId = GETPOST('track_id', 'alpha');
$action  = GETPOST('action', 'aZ09');
$cancel  = GETPOST('cancel', 'alpha');

// Initialize technical objects
$object   = new Ticket($db);
$document = new TicketQqoqcpDocument($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['ticketqqoqcp', 'digiriskdolibarrglobal', 'globalcard']);

if ($id > 0 || !empty($ref) || !empty($trackId)) {
    $object->fetch($id, $ref, $trackId);
    if ($object->id > 0) {
        $object->fetch_optionals();
    }
}
// The ticket belongs to the Dolibarr core: the documents of Saturne are filed under the module
$object->module = 'digiriskdolibarr';

// Security check - Protection if external user
$permissiontoread   = $user->hasRight('ticket', 'read');
$permissiontoadd    = (int) ($user->hasRight('ticket', 'write') && !$user->socid);
$permissiontodelete = $permissiontoadd;

saturne_check_access($permissiontoread, $object);

// Traceability (#4885): the changes are logged on the ticket like those made from its card
$ticketLogConst = 'DIGIRISKDOLIBARR_TICKET_LOG_MODIFICATIONS';
$qqoqcpFields   = digiriskdolibarr_ticket_qqoqcp_fields();
$pageUrl        = $_SERVER['PHP_SELF'] . '?id=' . (int) $object->id;
$upload_dir     = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

/*
 * Actions
 */

if ($cancel) {
    header('Location: ' . $pageUrl);
    exit;
}

if ($action === 'update_qqoqcp' && $permissiontoadd && $object->id > 0) {
    foreach (array_keys($qqoqcpFields) as $key) {
        if ($key === 'digiriskdolibarr_qqoqcp_who') {
            $newValue = implode(',', array_filter(array_map('intval', GETPOST($key, 'array'))));
        } else {
            $newValue = GETPOST($key, 'alphanohtml');
        }

        $oldValue = (string) ($object->array_options['options_' . $key] ?? '');
        if ($newValue !== $oldValue) {
            $logLabel = $langs->transnoentities($qqoqcpFields[$key]['label']);
            if ($key === 'digiriskdolibarr_qqoqcp_who') {
                // Names rather than the ids the extrafield stores
                $logOld = digiriskdolibarr_ticket_qqoqcp_who_names($oldValue);
                $logNew = digiriskdolibarr_ticket_qqoqcp_who_names($newValue);
            } else {
                $logOld = $oldValue;
                $logNew = $newValue;
            }
            digiriskdolibarr_ticket_log_field_change($object, $langs->transnoentities('QQOQCP') . ' - ' . $logLabel, $logOld, $logNew, $ticketLogConst);
        }
        $object->array_options['options_' . $key] = $newValue;
    }

    $result = $object->insertExtraFields();
    if ($result > 0) {
        setEventMessages($langs->trans('RecordSaved'), []);
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }

    header('Location: ' . $pageUrl);
    exit;
}

// Generation and removal of the QQOQCP document
require_once dol_buildpath('/saturne/core/tpl/documents/documents_action.tpl.php');

/*
 * View
 */

$title   = $langs->trans('Ticket') . ' ' . $object->ref . ' - ' . $langs->trans('QQOQCP');
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $helpUrl);

$head = digiriskdolibarr_ticket_prepare_head($object);
print dol_get_fiche_head($head, 'digiriskqqoqcp', $langs->trans('Ticket'), -1, 'ticket');

$morehtmlref  = '<div class="refidno">';
$morehtmlref .= '<span class="opacitymedium">' . $langs->trans('TicketTrackId') . ' : </span>' . dolPrintLabel($object->track_id);
if (!empty($object->fk_project)) {
    $project = new Project($db);
    if ($project->fetch((int) $object->fk_project) > 0) {
        $morehtmlref .= '<br>' . $project->getNomUrl(1) . ($project->title ? ' - ' . dol_escape_htmltag($project->title) : '');
    }
}
$morehtmlref .= '</div>';

$linkback = '<a href="' . dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_action_card.php', 1) . '"><strong>' . $langs->trans('BackToList') . '</strong></a>';

dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

$editMode = ($action === 'edit' && $permissiontoadd);

if ($editMode) {
    print '<form method="POST" action="' . dol_escape_htmltag($pageUrl) . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update_qqoqcp">';
}

$titleButton = '';
if ($permissiontoadd && !$editMode) {
    $titleButton = dolGetButtonTitle($langs->trans('Modify'), '', 'fa fa-pen', $pageUrl . '&action=edit&token=' . newToken());
}
print load_fiche_titre($langs->trans('QQOQCPTitle'), $titleButton, '');

print '<div class="digirisk-qqoqcp' . ($editMode ? ' digirisk-qqoqcp--edit' : '') . '">';
foreach ($qqoqcpFields as $key => $field) {
    $value = (string) ($object->array_options['options_' . $key] ?? '');

    print '<div class="digirisk-qqoqcp__item">';
    print '<div class="digirisk-qqoqcp__head">';
    print '<span class="digirisk-qqoqcp__picto"><i class="fas ' . $field['picto'] . '"></i></span>';
    print '<div><div class="digirisk-qqoqcp__label">' . $langs->trans($field['label']) . '</div>';
    print '<div class="digirisk-qqoqcp__help">' . $langs->trans($field['help']) . '</div></div>';
    print '</div>';

    print '<div class="digirisk-qqoqcp__value">';
    if ($key === 'digiriskdolibarr_qqoqcp_who') {
        if ($editMode) {
            // Active users as an array (outputmode 1), offered in a multiple select keeping the saved ones
            $usersList     = $form->select_dolusers('', $key, 0, null, 0, '', '', '0', 0, 0, '', 0, '', '', 0, 1);
            $selectedUsers = array_values(array_filter(explode(',', $value), 'is_numeric'));
            print $form->multiselectarray($key, is_array($usersList) ? $usersList : [], $selectedUsers, 0, 0, 'centpercent');
        } else {
            $names = digiriskdolibarr_ticket_qqoqcp_who_names($value);
            print !empty($names) ? dol_escape_htmltag(implode(', ', $names)) : '<span class="opacitymedium">' . $langs->trans('QQOQCPEmpty') . '</span>';
        }
    } elseif ($editMode) {
        print '<textarea name="' . $key . '" rows="4" class="centpercent">' . dol_escape_htmltag($value, 0, 1) . '</textarea>';
    } else {
        // keepn: without it the line breaks are escaped into a literal \r\n
        print trim($value) !== '' ? dol_nl2br(dol_escape_htmltag($value, 0, 1)) : '<span class="opacitymedium">' . $langs->trans('QQOQCPEmpty') . '</span>';
    }
    print '</div>';
    print '</div>';
}
print '</div>';

if ($editMode) {
    print '<div class="center">';
    print '<input type="submit" class="button button-save" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
    print ' <input type="submit" class="button button-cancel" name="cancel" value="' . dol_escape_htmltag($langs->trans('Cancel')) . '">';
    print '</div>';
    print '</form>';
} else {
    // Document of the analysis, generated with the values saved above
    $objRef   = dol_sanitizeFileName($object->ref);
    $dirFiles = $document->element . '/' . $objRef;
    $fileDir  = $upload_dir . '/' . $dirFiles;

    print '<div class="fichehalfleft digirisk-qqoqcp__documents">';
    print saturne_show_documents('digiriskdolibarr:TicketQqoqcpDocument', $dirFiles, $fileDir, $pageUrl, $permissiontoadd, $permissiontodelete, getDolGlobalString('DIGIRISKDOLIBARR_TICKETQQOQCPDOCUMENT_DEFAULT_MODEL'), 1, 0, 0, 0, '', $langs->trans('QQOQCPDocuments'), '', '', '', $object);
    print '</div>';
}

print '</div>'; // fichecenter

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
