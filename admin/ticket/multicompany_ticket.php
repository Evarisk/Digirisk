<?php
/* Copyright (C) 2023 EOXIA <dev@eoxia.com>
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
 *     \file        admin/ticket/multicompany_ticket.php
 *     \ingroup     digiriskdolibarr
 *     \brief       Page to multicompany public interface of module DigiriskDolibarr for ticket
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
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
require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php";
include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
include_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

require_once '../../lib/digiriskdolibarr.lib.php';
require_once '../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../core/tpl/digirisk_security_checks.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$extra_fields = new ExtraFields($db);
$category     = new Categorie($db);
$ticket       = new Ticket($db);

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action            = GETPOST('action', 'alpha');
$backtopage        = GETPOST('backtopage', 'alpha');
$value             = GETPOST('value', 'alpha');
$multiEntityConfig = 1;

/*
 * Actions
 */

require_once '../../core/tpl/ticket/digiriskdolibarr_ticket_config_action.tpl.php';

/*
 * View
 */

if ( ! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }
$form      = new Form($db);
$formother = new FormOther($db);

$help_url = '';
$title    = $langs->transnoentities("Ticket");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->transnoentities("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = multicompany_prepare_head();

print dol_get_fiche_head($head, 'ticket', '', -1, 'digiriskdolibarr@digiriskdolibarr');
print load_fiche_titre('<i class="fa fa-ticket"></i> ' . $langs->transnoentities("TicketManagement"), '', '');
print '<hr>';
if ($conf->multicompany->enabled) {
	print load_fiche_titre($langs->transnoentities("MultiEntityPublicInterface"), '', '');

	print '<span class="opacitymedium">' . $langs->transnoentities("DigiriskTicketPublicAccess") . '</span> : <a class="wordbreak" href="' . dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php', 1) . '" target="_blank" >' . dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php', 2) . '</a>';
}

print dol_get_fiche_end();

$enabledisablehtml = $langs->transnoentities("TicketActivatePublicInterface") . ' ';
if (empty($conf->global->DIGIRISKDOLIBARR_MULTICOMPANY_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=1">';
	$enabledisablehtml .= img_picto($langs->transnoentities("Disabled"), 'switch_off');
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=0">';
	$enabledisablehtml .= img_picto($langs->transnoentities("Activated"), 'switch_on');
}
$enabledisablehtml .= '</a>';
print $enabledisablehtml;
print '<input type="hidden" id="DIGIRISKDOLIBARR_MULTICOMPANY_TICKET_ENABLE_PUBLIC_INTERFACE" name="DIGIRISKDOLIBARR_MULTICOMPANY_TICKET_ENABLE_PUBLIC_INTERFACE" value="' . (empty($conf->global->DIGIRISKDOLIBARR_MULTICOMPANY_TICKET_ENABLE_PUBLIC_INTERFACE) ? 0 : 1) . '">';

print '<br><br>';

if (!empty($conf->global->DIGIRISKDOLIBARR_MULTICOMPANY_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	require_once '../../core/tpl/ticket/digiriskdolibarr_ticket_config_view.tpl.php';
}

print '</table>';
print '</div>';
print '<span class="opacitymedium">' . $langs->transnoentities("TicketPublicInterfaceConfigDocumentation") . '</span> : <a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" >' . $langs->transnoentities('DigiriskDocumentation') . '</a>';

// End of page
llxFooter();
$db->close();
