<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    admin/deletedelements.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr deletedelements page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user, $conf, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../../lib/digiriskdolibarr.lib.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

/*
 * Actions
 */

if ($action == 'setDeletedElements') {
	$constforval = 'DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_.C3.89l.C3.A9ment_Digirisk';
$title    = $langs->trans("DigiriskElement") . ' - ' . $langs->trans("DeletedElements");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'digiriskelement', '', -1, "digiriskdolibarr@digiriskdolibarr");
$head = digiriskdolibarrAdminDigiriskElementPrepareHead();
print dol_get_fiche_head($head, 'deletedelements', '', -1, "digiriskdolibarr@digiriskdolibarr");

/*
 *  Numbering module
 */
print load_fiche_titre($langs->trans("DeletedDigiriskElement"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DeletedDigiriskElement');
print "</td><td>";
print $langs->trans('ShowDeletedDigiriskElement');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setDeletedElements&value=0" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
}
else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setDeletedElements&value=1" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

print '</table>';
// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
