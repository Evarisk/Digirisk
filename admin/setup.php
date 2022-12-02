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
 * \file    admin/setup.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/project/mod_project_simple.php';

require_once '../lib/digiriskdolibarr.lib.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$project     = new Project($db);
$third_party = new Societe($db);
$projectRef  = new $conf->global->PROJECT_ADDON();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Access control
if ( ! $user->admin) accessforbidden();

// Parameters
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

require_once '../core/tpl/digiriskdolibarr_projectcreation_action.tpl.php';

if ($action == 'setredirectafterconnection') {
	$constforval = 'DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION';
	dolibarr_set_const($db, $constforval, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setMediaDimension') {
	$MediaMaxWidthMedium = GETPOST('MediaMaxWidthMedium', 'alpha');
	$MediaMaxHeightMedium = GETPOST('MediaMaxHeightMedium', 'alpha');
	$MediaMaxWidthLarge = GETPOST('MediaMaxWidthLarge', 'alpha');
	$MediaMaxHeightLarge = GETPOST('MediaMaxHeightLarge', 'alpha');

	if (!empty($MediaMaxWidthMedium) || $MediaMaxWidthMedium === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM", $MediaMaxWidthMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightMedium) || $MediaMaxHeightMedium === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM", $MediaMaxHeightMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxWidthLarge) || $MediaMaxWidthLarge === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE", $MediaMaxWidthLarge, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightLarge) || $MediaMaxHeightLarge === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE", $MediaMaxHeightLarge, 'integer', 0, '', $conf->entity);
	}
}

/*
 * View
 */

$page_name = "DigiriskdolibarrSetup";
$help_url  = 'FR:Module_DigiriskDolibarr#Configuration';

$morejs  = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $langs->trans($page_name), $help_url, '', '', '', $morejs, $morecss);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, "digiriskdolibarr@digiriskdolibarr");

print '<div style="text-indent: 3em"><br>' . '<i class="fas fa-2x fa-calendar-alt" style="padding: 10px"></i>   ' . $langs->trans("AgendaModuleRequired") . '<br></div>';
print '<div style="text-indent: 3em"><br>' . '<i class="fas fa-2x fa-tools" style="padding: 10px"></i>  ' . $langs->trans("HowToSetupOtherModules") . '  ' . '<a href=' . '"../../../admin/modules.php">' . $langs->trans('ConfigMyModules') . '</a>' . '<br></div>';
print '<div style="text-indent: 3em"><br>' . '<i class="fas fa-2x fa-file-alt" style="padding: 10px"></i>  ' . $langs->trans("AvoidLogoProblems") . '  ' . '<a href="' . $langs->trans('LogoHelpLink') . '">' . $langs->trans('LogoHelpLink') . '</a>' . '<br></div>';
print '<div style="text-indent: 3em"><br>' . '<i class="fab fa-2x fa-css3-alt" style="padding: 10px"></i>  ' . $langs->trans("HowToSetupIHM") . '  ' . '<a href=' . '"../../../admin/ihm.php">' . $langs->trans('ConfigIHM') . '</a>' . '<br></div>';
print '<div style="text-indent: 3em"><br>' . '<i class="fas fa-2x fa-globe" style="padding: 10px"></i>  ' . $langs->trans("HowToSharedElement") . '  ' . '<a href="' . $langs->trans('HowToSharedElementLink') . '">' . $langs->trans('ConfigSharedElement') . '</a>' . '<br></div>';

print load_fiche_titre($langs->trans("DigiriskData"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DigiriskManagement');
print '</td><td>';
print $langs->trans('DigiriskDescription');
print '</td>';

print '<td class="center">';
if ($conf->global->DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION) {
	print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setredirectafterconnection&value=0" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Activated"), 'switch_on') . '</a>';
} else {
	print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=setredirectafterconnection&value=1" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
}
print '</td>';
print '</tr>';

//Use captcha
print '<tr class="oddeven"><td>';
print  $langs->trans("UseCaptcha");
print '</td><td>';
print $langs->trans('UseCaptchaDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_USE_CAPTCHA');
print '</td>';
print '</tr>';

// Advanced Import
print '<tr class="oddeven"><td>';
print  $langs->trans("AdvancedImport");
print '</td><td>';
print $langs->trans('AdvancedImportDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_TOOLS_ADVANCED_IMPORT');
print '</td>';
print '</tr>';

// Trashbin Import
print '<tr class="oddeven"><td>';
print  $langs->trans("TrashBinExport");
print '</td><td>';
print $langs->trans('TrashBinExportDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_TOOLS_TRASH_BIN_IMPORT');
print '</td>';
print '</tr>';

// Manuel input number employees
print '<tr class="oddeven"><td>';
print  $langs->trans("ManuelInputNBEmployees");
print '</td><td>';
print $langs->trans('ManuelInputNBEmployeesDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES');
print '</td>';
print '</tr>';

// Manuel input number worked hours
print '<tr class="oddeven"><td>';
print  $langs->trans("ManuelInputNBWorkedHours");
print '</td><td>';
print $langs->trans('ManuelInputNBWorkedHoursDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS');
print '</td>';
print '</tr>';
print '</table>';

print load_fiche_titre($langs->trans("MediaData"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="media_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMediaDimension">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMedium">' . $langs->trans("MediaMaxWidthMedium") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxWidthMediumDescription") . '</td>';
print '<td><input type="number" name="MediaMaxWidthMedium" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMedium">' . $langs->trans("MediaMaxHeightMedium") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxHeightMediumDescription") . '</td>';
print '<td><input type="number" name="MediaMaxHeightMedium" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthLarge">' . $langs->trans("MediaMaxWidthLarge") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxWidthLargeDescription") . '</td>';
print '<td><input type="number" name="MediaMaxWidthLarge" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightLarge">' . $langs->trans("MediaMaxHeightLarge") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxHeightLargeDescription") . '</td>';
print '<td><input type="number" name="MediaMaxHeightLarge" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
