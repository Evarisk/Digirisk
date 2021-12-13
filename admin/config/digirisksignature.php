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
 *     \file        admin/digirisksignature.php
 *     \ingroup     digiriskdolibarr
 *     \brief       Page to public interface of module DigiriskDolibarr for signature
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

global $langs, $user;

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

if ($action == 'setPublicInterface') {
	if (GETPOST('value')) dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE', 1, 'integer', 0, '', $conf->entity);
	else dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE', 0, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title    = $langs->trans("DigiriskSignature");

$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'digirisksignature', '', -1, "digiriskdolibarr@digiriskdolibarr");

print load_fiche_titre('<i class="fas fa-file-signature"></i> ' . $langs->trans('SignatureManagement'), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("SignaturePublicInterface"), '', '');

$enabledisablehtml = $langs->trans("SignatureActivatePublicInterface").' ';
if (empty($conf->global->DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setPublicInterface&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setPublicInterface&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE" name="DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE" value="'.(empty($conf->global->DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE) ? 0 : 1).'">';

print '<br><br>';

if (!empty($conf->global->DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE)) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>';
	print '<td class="center">'.$langs->trans("Status").'</td>';
	print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
	print '</tr>';

	// Show logo for company
	print '<tr class="oddeven"><td>'.$langs->trans("SignatureShowCompanyLogo").'</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO');
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("SignatureShowCompanyLogoHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	print '</table>';
	print '</div>';
}

// End of page
print dol_get_fiche_end();
llxFooter();
$db->close();
