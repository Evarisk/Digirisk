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
 *   	\file       preventionplan_attendants.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view preventionplan_signature
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
if (!$res) die("Include of main fails");

require_once __DIR__ . '/class/digiriskresources.class.php';
require_once __DIR__ . '/class/preventionplan.class.php';
require_once __DIR__ . '/lib/digiriskdolibarr_preventionplan.lib.php';

global $db, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$action              = GETPOST('action', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'preventionplansignature'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object            = new PreventionPlan($db);
$signatory         = new PreventionPlanSignature($db);
$digiriskresources = new DigiriskResources($db);
$usertmp           = new User($db);
$contact           = new Contact($db);

$object->fetch($id);

$hookmanager->initHooks(array('preventionplansignature', 'globalcard')); // Note that conf->hooks_modules contains array

$permissiontoread   = $user->rights->digiriskdolibarr->preventionplandocument->read;

if (!$permissiontoread) accessforbidden();

/*
/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Action to add record
if ($action == 'addSignature') {
	$signatoryID = GETPOST('signatoryID');
	$signature = GETPOST('signature');
	$request_body = file_get_contents('php://input');

	$signatory->fetch($signatoryID);
	$signatory->signature = $request_body;

	if (!$error) {
		$result = $signatory->update($user, false);
		if ($result > 0) {
			$signatory->setSigned($user, false);
			// Creation signature OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		}
		else
		{
			// Creation signature KO
			if (!empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
			else  setEventMessages($signatory->error, null, 'errors');
		}
	}
}

/*
 *  View
 */

$title    = $langs->trans("PreventionPlan");
$help_url = '';
$morejs   = array("/digiriskdolibarr/js/signature-pad.min.js", "/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

if (!empty($object->id)) $res = $object->fetch_optionals();

// Object card
// ------------------------------------------------------------

$head = preventionplanPrepareHead($object);
print dol_get_fiche_head($head, 'preventionplanAttendants', $langs->trans("PreventionPlan"), -1, '');
dol_banner_tab($object, 'ref', '', 0, 'rowid', 'ref');

print '<div class="fichecenter"></div>';
print '<div class="underbanner clearboth"></div>';

print dol_get_fiche_end();

print '<div class="div-table-responsive">'; ?>

<div class="wpeo-notice notice-warning">
	<div class="notice-content">
		<div class="notice-title"><?php echo $langs->trans('DisclaimerSignatureTitle') ?></div>
		<div class="notice-subtitle"><?php echo $langs->trans("DisclaimerSignature") ?></div>
	</div>
</div>

<?php print load_fiche_titre($langs->trans("SignatureResponsibles"), '', '');

print '<table class="border centpercent tableforfield">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Role").'</td>';
print '<td>'.$langs->trans("Name").'</td>';
print '<td class="center">'.$langs->trans("Action").'</td>';
print '<td>'.$langs->trans("Status").'</td>';
print '</tr>'."\n";

//Master builder -- Maitre Oeuvre
print '<tr class="oddeven"><td>';
print $langs->trans("MaitreOeuvre");
print '</td><td>';
$element = $signatory->fetchSignatory('PP_MAITRE_OEUVRE', $id);
if ($element > 0) {
	$element = array_shift($element);
	$usertmp->fetch($element->element_id);
	print $usertmp->getNomUrl(1);
}
print '</td><td class="center">';
require __DIR__ . "/core/tpl/digiriskdolibarr_signature_view.tpl.php";
print '</td><td>';
print $element->getLibStatut(5);
print '</td></tr>';

//External Society Responsible -- Responsable Société extérieure
print '<tr class="oddeven"><td>';
print $langs->trans("ExtSocietyResponsible");
print '</td><td>';
$element = $signatory->fetchSignatory('PP_EXT_SOCIETY_RESPONSIBLE', $id);
if ($element > 0) {
	$element = array_shift($element);
	$contact->fetch($element->element_id);
	print $contact->getNomUrl(1);
}
print '</td><td class="center">';
require __DIR__ . "/core/tpl/digiriskdolibarr_signature_view.tpl.php";
print '</td><td>';
print $element->getLibStatut(5);
print '</td></tr>';

print '</tr>';
print '</table>';
print '<br>';

print load_fiche_titre($langs->trans("SignatureIntervenants"), '', '');

print '<table class="border centpercent tableforfield">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Role").'</td>';
print '<td>'.$langs->trans("Name").'</td>';
print '<td class="center">'.$langs->trans("Action").'</td>';
print '<td>'.$langs->trans("Status").'</td>';
print '</tr>'."\n";

//External Society Intervenants -- Intervenants Société extérieure
$j = 1;
$ext_society_intervenants = $signatory->fetchSignatory('PP_EXT_SOCIETY_INTERVENANTS', $id);
if (is_array($ext_society_intervenants) && !empty ($ext_society_intervenants) && $ext_society_intervenants > 0) {
	foreach ($ext_society_intervenants as $element) {
		print '<tr class="oddeven"><td>';
		print $langs->trans("ExtSocietyIntervenants") . ' ' . $j;
		print '</td><td>';
		$contact->fetch($element->element_id);
		print $contact->getNomUrl(1);
		print '</td><td class="center">';
		require __DIR__ . "/core/tpl/digiriskdolibarr_signature_view.tpl.php";
		print '</td><td>';
		print $element->getLibStatut(5);
		print '</td></tr>';
		$j++;
	}
}else {
	print '<td></td>';
}

print '</tr>';
print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
