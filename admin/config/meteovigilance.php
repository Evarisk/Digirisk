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
 * \file    admin/config/meteovigilance.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr Météo-France vigilance configuration page.
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

// Translations
saturne_load_langs(['admin']);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$apiKey     = GETPOST('MeteoVigilanceApiKey', 'alpha');
$department = GETPOST('MeteoVigilanceDepartment', 'alpha');
$cacheTtl   = GETPOSTINT('MeteoVigilanceCacheTTL');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'update') {
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_API_KEY', $apiKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT', strtoupper(trim($department)), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_CACHE_TTL', ($cacheTtl > 0 ? $cacheTtl : 3600), 'integer', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), []);
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
print dol_get_fiche_head($head, 'meteovigilance', $title, -1, 'digiriskdolibarr_color@digiriskdolibarr');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label>' . $langs->trans('MeteoVigilanceEnabled') . '</label></td>';
print '<td>' . $langs->trans('MeteoVigilanceEnabledDescription') . '</td>';
print '<td>' . ajax_constantonoff('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_ENABLED') . '</td></tr>';

print '<tr class="oddeven"><td><label for="MeteoVigilanceApiKey">' . $langs->trans('MeteoVigilanceApiKey') . '</label></td>';
print '<td>' . $langs->trans('MeteoVigilanceApiKeyDescription') . '</td>';
print '<td><input type="text" name="MeteoVigilanceApiKey" value="' . dol_escape_htmltag(getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_API_KEY')) . '" size="50"></td></tr>';

print '<tr class="oddeven"><td><label for="MeteoVigilanceDepartment">' . $langs->trans('MeteoVigilanceDepartment') . '</label></td>';
print '<td>' . $langs->trans('MeteoVigilanceDepartmentDescription') . '</td>';
print '<td><input type="text" name="MeteoVigilanceDepartment" value="' . dol_escape_htmltag(getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT')) . '" size="6"></td></tr>';

print '<tr class="oddeven"><td><label for="MeteoVigilanceCacheTTL">' . $langs->trans('MeteoVigilanceCacheTTL') . '</label></td>';
print '<td>' . $langs->trans('MeteoVigilanceCacheTTLDescription') . '</td>';
print '<td><input type="number" name="MeteoVigilanceCacheTTL" value="' . (getDolGlobalInt('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_CACHE_TTL') ?: 3600) . '" min="60"></td></tr>';

print '</table>';
print '<div class="center"><input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
