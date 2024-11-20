<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    admin/event.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr config event auto page.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $module, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once __DIR__ . '/../lib/digiriskdolibarr.lib.php';

saturne_check_access($user->admin);

// Load translation files required by the page
saturne_load_langs(['admin', 'other', 'agenda']);

$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'alpha');
$searchEvent = GETPOST('search_event', 'alpha');
$backtopage  = GETPOST('backtopage', 'alpha');

// Get list of triggers available
$triggers = saturne_fetch_dictionary('c_digiriskdolibarr_action_trigger');

/*
 *	Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
    $searchEvent = '';
    $action = '';
}

if (GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {	// To avoid the save when we click on search
    $action = '';
}

if ($action == "save" && empty($cancel)) {
    $db->begin();

    foreach ($triggers as $trigger) {
        $keyparam = 'DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_'.$trigger->ref;
        if ($searchEvent === '' || preg_match('/'.preg_quote($searchEvent, '/').'/i', $keyparam)) {
            $res = dolibarr_set_const($db, $keyparam, GETPOST($keyparam, 'alpha') == 'on' ? 1 : 0, 'integer', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }
        }
    }

    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        $db->commit();
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
        $db->rollback();
    }
}

/*
 * View
 */

$form = new Form($db);

$title   = $langs->trans("ModuleSetup", $moduleName);
$helpUrl = 'FR:Module_DigiriskDolibarr';

saturne_header(0, '', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'event', '', -1, "digiriskdolibarr_color@digiriskdolibarr");

print load_fiche_titre('<i class="fas fa-calendar-alt"></i> ' . $langs->trans('ConfTrigger'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('AdvancedTriggers');
print '</td><td>';
print $langs->trans('AdvancedTriggersDescription', ucfirst($module));
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_ADVANCED_TRIGGER');
print '</td>';
print '</tr>';
print '</table>';

print load_fiche_titre($langs->trans('ConfEventAuto'), '', '');

print '<span class="opacitymedium">'.$langs->trans("DigiriskEventAutoDesc").'</span><br>';
print '<br>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="liste_titre"><input type="text" name="search_event" value="'.dol_escape_htmltag($searchEvent).'"></td>';
print '<td class="liste_titre"></td>';
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print '<th class="liste_titre" colspan="2">'.$langs->trans("DigiriskActionsEvents").'</th>';
print '<th class="liste_titre"><a href="'.$_SERVER["PHP_SELF"].'?action=selectall&search_event='. urlencode($searchEvent) . '">'.$langs->trans("All").'</a>/<a href="'.$_SERVER["PHP_SELF"].'?action=selectnone'.($param ? $param : '').'">'.$langs->trans("None").'</a></th>';
print '</tr>';

// Show each trigger (list is in c_digiriskdolibarr_action_trigger)
if (!empty($triggers)) {
    foreach ($triggers as $trigger) {
        if ($searchEvent === '' || preg_match('/' . preg_quote($searchEvent, '/') . '/i', $trigger->ref)) {
            print '<tr class="oddeven">';
            print '<td>' . $trigger->ref . '</td>';
            print '<td>' . $langs->trans($trigger->label) . '</td>';
            print '<td class="right">';
            $key = 'DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_' . $trigger->ref;
            $value = $conf->global->$key;
            print '<input class="oddeven" type="checkbox" name="' . $key . '"' . ((($action == 'selectall' || $value) && $action != "selectnone") ? ' checked' : '') . '>';
            print '</td></tr>';
        }
    }
}
print '</table>';
print '</div>';

print $form->buttonsSaveCancel();
print '</form>';
print '<br>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
