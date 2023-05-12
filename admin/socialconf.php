<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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

global $conf, $db, $langs, $user, $hookmanager;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

require_once '../class/digiriskresources.class.php';
require_once __DIR__ . '/../core/tpl/digirisk_security_checks.php';

// Translations
$langs->loadLangs(array('admin', 'companies', "digiriskdolibarr@digiriskdolibarr"));

// Access control
if ( ! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'aZ09');
$error  = 0;

// Initialize technical objects
$resources = new DigiriskResources($db);

$allLinks = $resources->digirisk_dolibarr_fetch_resources();

$hookmanager->initHooks(array('adminsocial', 'globaladmin'));

$submissionElectionDateCSE = dol_mktime(0, 0, 0, GETPOST('SubmissionElectionDateCSEmonth', 'int'), GETPOST('SubmissionElectionDateCSEday', 'int'), GETPOST('SubmissionElectionDateCSEyear', 'int'));
$electionDateCSE           = dol_mktime(0, 0, 0, GETPOST('date_debutmonth', 'int'), GETPOST('date_debutday', 'int'), GETPOST('date_debutyear', 'int'));
$secondElectionDateCSE     = dol_mktime(0, 0, 0, GETPOST('SecondElectionDateCSEmonth', 'int'), GETPOST('SecondElectionDateCSEday', 'int'), GETPOST('SecondElectionDateCSEyear', 'int'));
$electionDateDP            = dol_mktime(0, 0, 0, GETPOST('date_finmonth', 'int'), GETPOST('date_finday', 'int'), GETPOST('date_finyear', 'int'));
$date                      = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
		$submissionElectionDateCSE = GETPOST('SubmissionElectionDateCSE');
		$submissionElectionDateCSE = dol_print_date(dol_stringtotime($submissionElectionDateCSE), 'dayrfc');

		$electionDateCSE = GETPOST('ElectionDateCSE');
		$electionDateCSE = dol_print_date(dol_stringtotime($electionDateCSE), 'dayrfc');

		$secondElectionDateCSE = GETPOST('SecondElectionDateCSE');
		$secondElectionDateCSE = dol_print_date(dol_stringtotime($secondElectionDateCSE), 'dayrfc');

		$electionDateDP = GETPOST('ElectionDateDP');
		$electionDateDP = dol_print_date(dol_stringtotime($electionDateDP), 'dayrfc');

		dolibarr_set_const($db, "DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE", GETPOST("modalites", 'none'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT", GETPOST("permanent", 'none'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL", GETPOST("occasional", 'none'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_CSE_SUBMISSION_ELECTION_DATE", $submissionElectionDateCSE, 'date', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_CSE_ELECTION_DATE", $electionDateCSE, 'date', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_CSE_SECOND_ELECTION_DATE", $secondElectionDateCSE, 'date', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_DP_ELECTION_DATE", $electionDateDP, 'date', 0, '', $conf->entity);

		$CSEtitulaires = ! empty(GETPOST('TitularsCSE', 'array')) ? GETPOST('TitularsCSE', 'array') : (GETPOST('TitularsCSE', 'int') > 0 ? array(GETPOST('TitularsCSE', 'int')) : array());
		$CSEsuppleants = ! empty(GETPOST('AlternatesCSE', 'array')) ? GETPOST('AlternatesCSE', 'array') : (GETPOST('AlternatesCSE', 'int') > 0 ? array(GETPOST('AlternatesCSE', 'int')) : array());
		$TitularsDP    = ! empty(GETPOST('TitularsDP', 'array')) ? GETPOST('TitularsDP', 'array') : (GETPOST('TitularsDP', 'int') > 0 ? array(GETPOST('TitularsDP', 'int')) : array());
		$AlternatesDP  = ! empty(GETPOST('AlternatesDP', 'array')) ? GETPOST('AlternatesDP', 'array') : (GETPOST('AlternatesDP', 'int') > 0 ? array(GETPOST('AlternatesDP', 'int')) : array());

		$resources->digirisk_dolibarr_set_resources($db, $user->id, 'TitularsCSE', 'societe', $CSEtitulaires, $conf->entity);
		$resources->digirisk_dolibarr_set_resources($db, $user->id, 'AlternatesCSE', 'societe', $CSEsuppleants, $conf->entity);
		$resources->digirisk_dolibarr_set_resources($db, $user->id, 'TitularsDP', 'societe', $TitularsDP, $conf->entity);
		$resources->digirisk_dolibarr_set_resources($db, $user->id, 'AlternatesDP', 'societe', $AlternatesDP, $conf->entity);

		$HarassmentOfficer = GETPOST('HarassmentOfficer', 'int');
		$HarassmentOfficerCSE = GETPOST('HarassmentOfficerCSE', 'int');

		$resources->digirisk_dolibarr_set_resources($db, $user->id, 'HarassmentOfficer', 'user', array($HarassmentOfficer), $conf->entity);
		$resources->digirisk_dolibarr_set_resources($db, $user->id, 'HarassmentOfficerCSE', 'user', array($HarassmentOfficerCSE), $conf->entity);

		// Submit file
		if (!empty($conf->global->MAIN_UPLOAD_DOC)) {
			if (!empty($_FILES) && ! empty($_FILES['userfile']['name'][0])) {
				if (is_array($_FILES['userfile']['tmp_name'])) {
					$userFiles = $_FILES['userfile']['tmp_name'];
				} else {
					$userFiles = [$_FILES['userfile']['tmp_name']];
				}

				foreach ($userFiles as $key => $userFile) {
					if (empty($_FILES['userfile']['tmp_name'][$key])) {
						$error++;
						if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
							setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
						}
					}
				}

				$fileDir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/informationssharing/deficiencyreport/';
				if (!file_exists($fileDir)) {
					if (dol_mkdir($fileDir) < 0) {
						$db->error = $langs->transnoentities('ErrorCanNotCreateDir', $fileDir);
						$error++;
					}
				}

				if (!$error) {
					digirisk_dol_add_file_process($fileDir, 0, 1, 'userfile', '', null, '', 0);
				}
			}
		}

		if ($action != 'updateedit' && ! $error) {
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}
	}

	if ($action == 'delete_file' && !empty($user->admin)) {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileToDelete = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/informationssharing/deficiencyreport/' . GETPOST('file');
		$result       = dol_delete_file($fileToDelete);
		if ($result > 0) {
			setEventMessage($langs->trans('FileWasRemoved', GETPOST('file')));
			header('Location: ' . $_SERVER['PHP_SELF']);
		}
	}
}

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_Social';
$title    = $langs->trans("CompanyFoundation") . ' - ' . $langs->trans("Social");

$morejs  = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss = array("/digiriskdolibarr/css/digiriskdolibarr.css");

$counter = 0;

$socialResources = array("TitularsCSE", "AlternatesCSE", "TitularsDP", "AlternatesDP");
$socialConsts    = array("DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE", "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT", "DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL", 'DIGIRISKDOLIBARR_CSE_SUBMISSION_ELECTION_DATE', "DIGIRISKDOLIBARR_CSE_ELECTION_DATE", "DIGIRISKDOLIBARR_DP_ELECTION_DATE");

$maxnumber = count($socialResources) + count($socialConsts);

foreach ($socialConsts as $socialConst) {
	if (dol_strlen($conf->global->$socialConst) > 0 && $conf->global->$socialConst != '--') {
		$counter += 1;
	}
}
foreach ($socialResources as $socialResource) {
	if ( ! empty($allLinks[$socialResource] && $allLinks[$socialResource]->id[0] > 0)) {
		$counter += 1;
	}
}

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($title, '', 'title_setup');

$head = company_admin_prepare_head();

print dol_get_fiche_head($head, 'social', '', -1, '');

$form      = new Form($db);
$resources = new DigiriskResources($db);

$allLinks = $resources->digirisk_dolibarr_fetch_resources();

$submissionElectionDateCSE = $conf->global->DIGIRISKDOLIBARR_CSE_SUBMISSION_ELECTION_DATE;
$electionDateCSE           = $conf->global->DIGIRISKDOLIBARR_CSE_ELECTION_DATE;
$secondElectionDateCSE     = $conf->global->DIGIRISKDOLIBARR_CSE_SECOND_ELECTION_DATE;
$electionDateDP            = $conf->global->DIGIRISKDOLIBARR_DP_ELECTION_DATE;

print '<span class="opacitymedium">' . $langs->trans("DigiriskMenu") . "</span><br>\n";
print "<br>";

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="social_form" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">'; ?>

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
$doleditor = new DolEditor('modalites', $conf->global->DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE ? $conf->global->DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

/*
*** Exceptions to working hours -- Dérogations aux horaires de travail ***
*/

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("ExceptionsToWorkingHours") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

// * Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="permanent">' . $langs->trans("PermanentDerogation") . '</label></td><td>';
$doleditor = new DolEditor('permanent', $conf->global->DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT ? $conf->global->DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="occasional">' . $langs->trans("OccasionalDerogation") . '</label></td><td>';
$doleditor = new DolEditor('occasional', $conf->global->DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL ? $conf->global->DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

/*
* Harassment officer more 250 employees
*/

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("HarassmentOfficer") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";
$HarassmentOfficer = $allLinks['HarassmentOfficer'];
print '<tr>';
print '<td>' . $langs->trans("ActionOnUser") . '</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';
print $form->select_dolusers($HarassmentOfficer->id, 'HarassmentOfficer', 1, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300');
if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
print '</td></tr>';

if (!empty($allLinks['TitularsCSE']) || !empty($allLinks['AlternatesCSE'])) {
	/*
	* Harassment officer CSE
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("HarassmentOfficerCSE") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";
	$HarassmentOfficerCSE = $allLinks['HarassmentOfficerCSE'];
	print '<tr>';
	print '<td>' . $langs->trans("ActionOnUser") . '</td>';
	print '<td colspan="3" class="maxwidthonsmartphone">';
	print $form->select_dolusers($HarassmentOfficerCSE->id, 'HarassmentOfficerCSE', 1, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', 'minwidth300');
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';
}

/*
*** ESC -- CSE ***
*/

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("ESC") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

// ESC submission election date - Date de présentation de l'élection du CSE
print '<tr class="oddeven"><td><label for="SubmissionElectionDateCSE">' . $langs->trans('SubmissionElectionDate') . '</label></td><td>';
print $form->selectDate(strtotime($submissionElectionDateCSE) ? $submissionElectionDateCSE : -1, 'SubmissionElectionDateCSE', 0, 0, 0, 'social_form', 1, 1);
print '</td></tr>';

if (!empty($submissionElectionDateCSE)) {
	$deficiencyReportDate = dol_time_plus_duree(dol_stringtotime($submissionElectionDateCSE), '30', 'd');
	if ((dol_now() > $deficiencyReportDate) || (!empty($secondElectionDateCSE) && dol_now() > dol_stringtotime($secondElectionDateCSE) && empty($allLinks['TitularsCSE']) && empty($allLinks['AlternatesCSE']))) {
		// Deficiency report - Procès-verbal de carence
		print '<tr class="oddeven"><td><label for="DeficiencyReport">' . $langs->trans('DeficiencyReport') . '</label></td><td>';
		print ajax_constantonoff('DIGIRISKDOLIBARR_DEFICIENCY_REPORT');
		print '<input class="flat" type="file" name="userfile[]" id="DeficiencyReportFile" />';
		$fileArray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/informationssharing/deficiencyreport/', 'files');
		// Scan directories
		if (is_array($fileArray) && !empty($fileArray)) {
			$out = '<div>';
			// Show list of found files
			foreach ($fileArray as $file) {
				$out .= $file['name'] . ' <a class="reposition" href="' . DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&file=informationssharing/deficiencyreport/' . urlencode(basename($file['name'])) . '">' . img_picto('', 'listlight') . '</a>';
				$out .= ' <a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=delete_file&token=' . newToken() . '&file=' . urlencode(basename($file['name'])) . '">' . img_picto('', 'delete') . '</a>';
				$out .= '<br>';
			}
			$out .= '</div>';
			print $out;
		}
		print '</td></tr>';

		// Deficiency report date - Date du procès-verbal de carence
		print '<tr class="oddeven"><td><label for="DeficiencyReportDate">' . $langs->trans('DeficiencyReportDate') . '</label></td><td>';
		print dol_print_date($deficiencyReportDate, 'day');
		print '</td></tr>';
	} else {
		// ESC election date - Date d'élection du CSE
		print '<tr class="oddeven"><td><label for="ElectionDateCSE">' . $langs->trans("ElectionDate") . '</label></td><td>';
		print $form->selectDate(strtotime($electionDateCSE) ? $electionDateCSE : -1, 'ElectionDateCSE', 0, 0, 0, 'social_form', 1, 1);
		print '</td></tr>';

		// ESC Second election date - Date d'élection du second tour du CSE
		print '<tr class="oddeven"><td><label for="SecondElectionDateCSE">' . $langs->trans("SecondElectionDate") . '</label></td><td>';
		print $form->selectDate(strtotime($secondElectionDateCSE) ? $secondElectionDateCSE : -1, 'SecondElectionDateCSE', 0, 0, 0, 'social_form', 1, 1);
		print '</td></tr>';

		// ESC titulars - Titulaires CSE
		$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
		$titulars_cse = $allLinks['TitularsCSE'];
		print '<tr>';
		print '<td>' . $form->editfieldkey('Titulars', 'TitularsCSE_id', '', $object, 0) . '</td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		print $form->multiselectarray('TitularsCSE', $userlist, $titulars_cse->id, null, null, null, null, "300");
		if (!GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';

		// ESC alternates - Suppléants CSE
		$userlist       = $form->select_dolusers('', '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
		$alternates_cse = $allLinks['AlternatesCSE'];
		print '<tr>';
		print '<td>' . $form->editfieldkey('Alternates', 'AlternatesCSE_id', '', $object, 0) . '</td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		print $form->multiselectarray('AlternatesCSE', $userlist, $alternates_cse->id, null, null, null, null, "300");
		if (!GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';
	}
}

/*
*** Staff Representative -- Délégués du Personnel ***
*/

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("StaffRepresentatives") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

print '<tr class="oddeven"><td><label for="ElectionDateDP">' . $langs->trans("ElectionDate") . '</label></td><td>';
print $form->selectDate(strtotime($electionDateDP) ? $electionDateDP : -1, 'ElectionDateDP', 0, 0, 0, 'social_form', 1, 1);

// * Staff Representatives Titulars - Titulaires Délégués du Personnel *

$userlist    = $form->select_dolusers('', '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$titulars_dp = $allLinks['TitularsDP'];

print '<tr>';
print '<td>' . $form->editfieldkey('Titulars', 'TitularsDP_id', '', $object, 0) . '</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('TitularsDP', $userlist, $titulars_dp->id, null, null, null, null, "300");

if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';

print '</td></tr>';

// * Staff Representatives Suppléants - Suppléants Délégués du Personnel *

$userlist      = $form->select_dolusers('', '', 0, null, 0, '', '', $conf->entity, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$alternates_dp = $allLinks['AlternatesDP'];

print '<tr>';
print '<td>' . $form->editfieldkey('Alternates', 'AlternatesDP', '', $object, 0) . '</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('AlternatesDP', $userlist, $alternates_dp->id, null, null, null, null, "300");

if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';

print '</td></tr>';

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</div>';
print '</form>';

llxFooter();
$db->close();
