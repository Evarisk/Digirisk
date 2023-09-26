<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

require_once __DIR__ . '/../lib/digiriskdolibarr.lib.php';

// Translations
saturne_load_langs(["admin"]);

// Initialize technical objects
$project     = new Project($db);
$third_party = new Societe($db);
$form        = new Form($db);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

require_once '../core/tpl/digiriskdolibarr_projectcreation_action.tpl.php';

if ($action == 'setredirectafterconnection') {
	$constForVal = 'DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION';
	dolibarr_set_const($db, $constForVal, $value, 'integer', 0, '', $conf->entity);
}

if ($action == 'setMediaInfos') {
	$error = 0;
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MINI']         = GETPOST('MediaMaxWidthMini', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MINI']        = GETPOST('MediaMaxHeightMini', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_SMALL']        = GETPOST('MediaMaxWidthSmall', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_SMALL']       = GETPOST('MediaMaxHeightSmall', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM']       = GETPOST('MediaMaxWidthMedium', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM']      = GETPOST('MediaMaxHeightMedium', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE']        = GETPOST('MediaMaxWidthLarge', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE']       = GETPOST('MediaMaxHeightLarge', 'alpha');
	$mediasMax['DIGIRISKDOLIBARR_DISPLAY_NUMBER_MEDIA_GALLERY'] = GETPOST('DisplayNumberMediaGallery', 'alpha');

	foreach($mediasMax as $constName => $valueMax) {
		if (empty($valueMax)) {
			setEventMessages('MediaDimensionEmptyError', [], 'errors');
			$error++;
			break;
		} else if ($valueMax < 0) {
			setEventMessages('MediaDimensionNegativeError', [], 'errors');
			$error++;
			break;
		} else {
			dolibarr_set_const($db, $constName, $valueMax, 'integer', 0, '', $conf->entity);
		}
	}

	if (empty($error)) {
		setEventMessages('MediaDimensionSetWithSuccess', []);
	}
}

/*
 * View
 */

$title    = $langs->trans("DigiriskdolibarrSetup");
$helpUrl  = 'FR:Module_Digirisk#Configuration';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
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
print ajax_constantonoff('DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION');
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

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="media_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMediaInfos">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMini">' . $langs->trans('MediaMaxWidthMini') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthMiniDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthMini" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MINI . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMini">' . $langs->trans('MediaMaxHeightMini') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightMiniDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightMini" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MINI . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthSmall">' . $langs->trans('MediaMaxWidthSmall') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthSmallDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthSmall" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_SMALL . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightSmall">' . $langs->trans('MediaMaxHeightSmall') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightSmallDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightSmall" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_SMALL . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMedium">' . $langs->trans('MediaMaxWidthMedium') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthMediumDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthMedium" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMedium">' . $langs->trans('MediaMaxHeightMedium') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightMediumDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightMedium" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthLarge">' . $langs->trans('MediaMaxWidthLarge') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthLargeDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthLarge" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightLarge">' . $langs->trans('MediaMaxHeightLarge') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightLargeDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightLarge" value="' . $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="DisplayNumberMediaGallery">' . $langs->trans('DisplayNumberMediaGallery') . '</label></td>';
print '<td>' . $langs->trans('DisplayNumberMediaGalleryDescription') . '</td>';
print '<td><input type="number" name="DisplayNumberMediaGallery" value="' . $conf->global->DIGIRISKDOLIBARR_DISPLAY_NUMBER_MEDIA_GALLERY . '"></td>';
print '</td></tr>';

print '</table>';
print $form->buttonsSaveCancel('Save', '');
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
