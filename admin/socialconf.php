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
 */

/**
 * \file    admin/socialconf.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr setup page for social data configuration.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $user, $hookmanager;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

require_once __DIR__ . '/../class/digiriskresources.class.php';

// Translations
saturne_load_langs(["admin", "companies"]);

// Parameters
$action = GETPOST('action', 'aZ09');
$error  = 0;

// Initialize technical objects
$resources = new DigiriskResources($db);

$allLinks = $resources->fetchDigiriskResources();

// Staff representative bodies shown on the page: code => translation key of its name
$activeStaffBodies = $resources->getActiveStaffRepresentativeBodies();

$hookmanager->initHooks(array('admincompany', 'globaladmin'));

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE", GETPOST("modalites", 'none'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT", GETPOST("permanent", 'none'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL", GETPOST("occasional", 'none'), 'chaine', 0, '', $conf->entity);

		// Only the bodies shown on the page posted their fields: a body chosen with this save has none yet, and
		// one left out of the choice keeps its data, found again when it is chosen back
		foreach (array_keys($activeStaffBodies) as $staffBody) {
			// An empty date field explodes to a single element: reading [1] and [2] warned, and the
			// constant was stored as the string '--', which every consumer then has to special-case
			$electionDate = explode('/', GETPOST('ElectionDate' . $staffBody, 'none'));
			$electionDate = count($electionDate) == 3 ? $electionDate[2] . '-' . $electionDate[1] . '-' . $electionDate[0] : '';
			dolibarr_set_const($db, 'DIGIRISKDOLIBARR_' . $staffBody . '_ELECTION_DATE', $electionDate, 'date', 0, '', $conf->entity);

			foreach (['Titulars', 'Alternates'] as $memberType) {
				// setDigiriskResources concatenates the ids in its INSERT without quoting them: cast them
				$memberIds = array_values(array_filter(array_map('intval', GETPOST($memberType . $staffBody, 'array')), function ($memberId) {
					return $memberId > 0;
				}));
				$resources->setDigiriskResources($db, $user->id, $memberType . $staffBody, 'societe', $memberIds, $conf->entity);
			}
		}

		// Stored as 'none' when no body is chosen: an empty constant is deleted, and the page would fall back on the ESC
		$chosenStaffBodies = array_intersect(GETPOST('StaffRepresentativeBodies', 'array'), array_keys(DigiriskResources::getStaffRepresentativeBodies()));
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_STAFF_REPRESENTATIVE_BODIES', !empty($chosenStaffBodies) ? implode(',', $chosenStaffBodies) : 'none', 'chaine', 0, '', $conf->entity);

		$harassmentOfficer = GETPOST('HarassmentOfficer', 'int');
		$resources->setDigiriskResources($db, $user->id, 'HarassmentOfficer', 'user', array($harassmentOfficer), $conf->entity);

		// The ESC harassment officer is only shown with the ESC
		if (isset($activeStaffBodies['CSE'])) {
			$harassmentOfficerCSE = GETPOST('HarassmentOfficerCSE', 'int');
			$resources->setDigiriskResources($db, $user->id, 'HarassmentOfficerCSE', 'user', array($harassmentOfficerCSE), $conf->entity);
		}

		if (! $error && GETPOSTINT('addUser') > 0) {
			// Configuration has just been saved, the user can be created without losing it
			$backToPage = $_SERVER["PHP_SELF"];
			setEventMessages($langs->trans("SetupSaved"), null);
			header("Location: " . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($backToPage) . '&backtopageforcancel=' . urlencode($backToPage));
			exit;
		}

		if ($action != 'updateedit' && ! $error) {
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}
	}
}

/*
 * View
 */

$help_url = 'FR:Module_Digirisk#L.27onglet_Social';
$title    = $langs->trans("CompanyFoundation") . ' - ' . $langs->trans("Social");

$morejs  = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss = array("/saturne/css/saturne.min.css", "/digiriskdolibarr/css/digiriskdolibarr.css", "/digiriskdolibarr/css/digiriskdolibarr.min.css");

$counter = 0;

// The choice may just have been saved without leaving the page
$activeStaffBodies = $resources->getActiveStaffRepresentativeBodies();

$socialResources = array();
$socialConsts    = array("DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE", "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT", "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL");
foreach (array_keys($activeStaffBodies) as $staffBody) {
	$socialResources[] = 'Titulars' . $staffBody;
	$socialResources[] = 'Alternates' . $staffBody;
	$socialConsts[]    = 'DIGIRISKDOLIBARR_' . $staffBody . '_ELECTION_DATE';
}

$maxnumber = count($socialResources) + count($socialConsts);

foreach ($socialConsts as $socialConst) {
	if (dol_strlen(getDolGlobalString($socialConst)) > 0 && getDolGlobalString($socialConst) != '--') {
		$counter += 1;
	}
}
foreach ($socialResources as $socialResource) {
	if (!empty($allLinks[$socialResource]->id[0])) {
		$counter += 1;
	}
}

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss, '', 'saturne-theme');

print load_fiche_titre($title, '', 'title_setup');

$head = company_admin_prepare_head();

print dol_get_fiche_head($head, 'social', '', -1, '');

$form      = new Form($db);
$resources = new DigiriskResources($db);

// Saves the configuration before leaving to the user creation page, so nothing is lost on the way back
$addUserLink = ' <a class="add-user-link" href="#"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';

$allLinks = $resources->fetchDigiriskResources();

print '<span class="opacitymedium">' . $langs->trans("DigiriskMenu") . "</span><br>\n";
print "<br>";

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="social_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="addUser" id="addUser" value="0">'; ?>

<h2 class="">
	<?php echo $langs->trans('SocialConfiguration') ?>
</h2>
<?php include_once '../core/tpl/digiriskdolibarr_configuration_gauge_view.tpl.php'; ?>
<hr>
<?php
/*
*** Participation Agreement -- Accords de participation ***
*/

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("ParticipationAgreement") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

// * Terms And Conditions - Modalités *

print '<tr class="oddeven"><td><label for="modalites">' . $langs->trans("TermsAndConditions") . '</label></td><td>';
$doleditor = new DolEditor('modalites', getDolGlobalString('DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE'), '', 200, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

/*
*** Exceptions to working hours -- Dérogations aux horaires de travail ***
*/

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("ExceptionsToWorkingHours") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

// * Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="permanent">' . $langs->trans("PermanentDerogation") . '</label></td><td>';
$doleditor = new DolEditor('permanent', getDolGlobalString('DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT'), '', 200, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="occasional">' . $langs->trans("OccasionalDerogation") . '</label></td><td>';
$doleditor = new DolEditor('occasional', getDolGlobalString('DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL'), '', 200, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

/*
* Harassment officer more 250 employees
*/

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("HarassmentOfficer") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";
$harassmentOfficerIds = $allLinks['HarassmentOfficer']->id ?? [];
print '<tr>';
print '<td>' . $langs->trans("ActionOnUser") . '</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';
print $form->select_dolusers($harassmentOfficerIds, 'HarassmentOfficer', 1, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300');
print $addUserLink;
print '</td></tr>';

/*
* Harassment officer CSE, only with the ESC
*/

if (isset($activeStaffBodies['CSE'])) {
	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("HarassmentOfficerCSE") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";
	$harassmentOfficerCSEIds = $allLinks['HarassmentOfficerCSE']->id ?? [];
	print '<tr>';
	print '<td>' . $langs->trans("ActionOnUser") . '</td>';
	print '<td colspan="3" class="maxwidthonsmartphone">';
	print $form->select_dolusers($harassmentOfficerCSEIds, 'HarassmentOfficerCSE', 1, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300');
	print $addUserLink;
	print '</td></tr>';
}

/*
*** Staff representative bodies -- Instances représentatives du personnel ***
*/

$staffBodyLabels = array();
foreach (DigiriskResources::getStaffRepresentativeBodies() as $staffBody => $staffBodyLabel) {
	$staffBodyLabels[$staffBody] = $langs->trans($staffBodyLabel);
}

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("StaffRepresentativeBodies") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";
print '<tr class="oddeven"><td><label for="StaffRepresentativeBodies">' . $form->textwithpicto($langs->trans("StaffRepresentativeBodies"), $langs->trans("StaffRepresentativeBodiesHelp")) . '</label></td>';
print '<td colspan="3" class="maxwidthonsmartphone">';
// Full names are long: the field takes the width of the cell, up to 750 px
print $form->multiselectarray('StaffRepresentativeBodies', $staffBodyLabels, array_keys($activeStaffBodies), 0, 0, 'minwidth300 maxwidth750', 0, '100%');
print '</td></tr>';

// One section per body: its election date, its titulars, its alternates
$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', '', 0, 1);
foreach ($activeStaffBodies as $staffBody => $staffBodyLabel) {
	$electionDate = getDolGlobalString('DIGIRISKDOLIBARR_' . $staffBody . '_ELECTION_DATE');

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans($staffBodyLabel) . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

	print '<tr class="oddeven"><td><label for="ElectionDate' . $staffBody . '">' . $langs->trans('ElectionDate' . $staffBody) . '</label></td><td>';
	print $form->selectDate(strtotime($electionDate) ? $electionDate : -1, 'ElectionDate' . $staffBody, 0, 0, 0, 'social_form', 1, 1);
	print '</td></tr>';

	foreach (['Titulars', 'Alternates'] as $memberType) {
		print '<tr>';
		print '<td><label for="' . $memberType . $staffBody . '">' . $langs->trans($memberType) . '</label></td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		print $form->multiselectarray($memberType . $staffBody, $userlist, $allLinks[$memberType . $staffBody]->id ?? [], null, null, null, null, "300");
		print $addUserLink;
		print '</td></tr>';
	}
}

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</div>';
print '</form>';

print '<script>
	$(document).ready(function() {
		$(".add-user-link").on("click", function(event) {
			event.preventDefault();
			$("#addUser").val(1);
			$("form[name=\'social_form\']").submit();
		});
	});
</script>';

llxFooter();
$db->close();
