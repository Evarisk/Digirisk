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
 * \file    admin/config/event.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr config event auto page.
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../../lib/digiriskdolibarr.lib.php';

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'agenda', 'digiriskdolibarr@digiriskdolibarr'));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

$search_event = GETPOST('search_event', 'alpha');

// Get list of triggers available
$triggers = array();
$sql = "SELECT a.rowid, a.code, a.label, a.elementtype, a.rang as position";
$sql .= " FROM ".MAIN_DB_PREFIX."c_digiriskdolibarr_action_trigger as a";
$sql .= " ORDER BY a.rang ASC";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$triggers[$i]['rowid']   = $obj->rowid;
		$triggers[$i]['code'] 	 = $obj->code;
		$triggers[$i]['element'] = $obj->elementtype;
		$triggers[$i]['label']	 = ($langs->trans("Notify_".$obj->code) != "Notify_".$obj->code ? $langs->trans("Notify_".$obj->code) : $obj->label);
		$triggers[$i]['position']= $obj->position;

		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

/*
 *	Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_event = '';
	$action = '';
}

if (GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {	// To avoid the save when we click on search
	$action = '';
}

if ($action == "save" && empty($cancel)) {
	$i = 0;

	$db->begin();

	foreach ($triggers as $trigger) {
		$keyparam = 'DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_'.$trigger['code'];
		if ($search_event === '' || preg_match('/'.preg_quote($search_event, '/').'/i', $keyparam)) {
			$res = dolibarr_set_const($db, $keyparam, GETPOST($keyparam, 'alpha'), 'chaine', 0, '', $conf->entity);
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

$page_name = "Event";
$help_url  = 'FR:Module_DigiriskDolibarr';

$morejs  = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $langs->trans($page_name), $help_url, '', '', '', $morejs, $morecss);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

$param = '';
$param .= '&search_event='.urlencode($search_event);

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'event', '', -1, 'digiriskdolibarr@digiriskdolibarr');

print '<span class="opacitymedium">'.$langs->trans("DigiriskEventAutoDesc").'</span><br>';
print "<br>\n";

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="liste_titre"><input type="text" name="search_event" value="'.dol_escape_htmltag($search_event).'"></td>';
print '<td class="liste_titre"></td>';
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';
print '</tr>'."\n";

print '<tr class="liste_titre">';
print '<th class="liste_titre" colspan="2">'.$langs->trans("DigiriskActionsEvents").'</th>';
print '<th class="liste_titre"><a href="'.$_SERVER["PHP_SELF"].'?action=selectall'.($param ? $param : '').'">'.$langs->trans("All").'</a>/<a href="'.$_SERVER["PHP_SELF"].'?action=selectnone'.($param ? $param : '').'">'.$langs->trans("None").'</a></th>';
print '</tr>'."\n";

// Show each trigger (list is in c_digiriskdolibarr_action_trigger)
if (!empty($triggers)) {
	foreach ($triggers as $trigger) {
		if ($search_event === '' || preg_match('/' . preg_quote($search_event, '/') . '/i', $trigger['code'])) {
			print '<tr class="oddeven">';
			print '<td>' . $trigger['code'] . '</td>';
			print '<td>' . $trigger['label'] . '</td>';
			print '<td class="right">';
			$key = 'DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_' . $trigger['code'];
			$value = $conf->global->$key;
			print '<input class="oddeven" type="checkbox" name="' . $key . '"' . ((($action == 'selectall' || $value) && $action != "selectnone") ? ' checked' : '') . '>';
			print '</td></tr>';
		}
	}
}
print '</table>';
print '</div>';

print $form->buttonsSaveCancel();
print "</form>";
print "<br>";
// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
