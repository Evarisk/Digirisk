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
 *   	\file       view/accident/accident_metadata.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view accident metadata
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOST('id', 'int');
$action              = GETPOST('action', 'aZ09');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accidentmetadata'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object           = new Accident($db);
$accidentmetadata = new AccidentMetaData($db);
$objectline       = new AccidentWorkStop($db);
$project          = new Project($db);
$usertmp          = new User($db);
$thirdparty       = new Societe($db);
$form             = new Form($db);


// Load object
$object->fetch($id);
if ($id > 0 && $object->external_accident != 2) {
    unset($object->fields['fk_soc']);
    unset($object->fk_soc);
}

$hookmanager->initHooks(['accidentmetadata', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check

$permissiontoread   = $user->rights->digiriskdolibarr->accident->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident->delete;

saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/accident/accident_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/digiriskdolibarr/view/accident/accident_metadata.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
            }
		}
	}

	if (GETPOST('cancel') || GETPOST('cancelLine')) {
		// Cancel accident
		$urltogo = str_replace('__ID__', $result, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}

	// Action to update record
	if ($action == 'update' && $permissiontoadd) {
		// Get parameters
		$relative_location                    = GETPOST('relative_location');
		$victim_activity                      = GETPOST('victim_activity');
		$accident_nature                      = GETPOST('accident_nature');
		$accident_object                      = GETPOST('accident_object');
		$accident_nature_doubt                = GETPOST('accident_nature_doubt');
		$accident_nature_doubt_link           = GETPOST('accident_nature_doubt_link');
		$victim_transported_to                = GETPOST('victim_transported_to');
		$collateral_victim                    = GETPOST('collateral_victim');
		$accident_noticed                     = GETPOST('accident_noticed');
		$accident_notice_by                   = GETPOST('accident_notice_by');
		$accident_described_by_victim         = GETPOST('accident_described_by_victim');
		$registered_in_accident_register      = GETPOST('registered_in_accident_register');
		$register_number                      = GETPOST('register_number');
		$consequence                          = GETPOST('consequence');
		$police_report                        = GETPOST('police_report');
		$police_report_by                     = GETPOST('police_report_by');
		$first_person_noticed_is_witness      = GETPOST('first_person_noticed_is_witness');
		$thirdparty_responsibility            = GETPOST('thirdparty_responsibility');
		$accident_investigation               = GETPOST('accident_investigation');
		$accident_investigation_link          = GETPOST('accident_investigation_link');
		$cerfa_link                           = GETPOST('cerfa_link');
		$fk_user_witness                      = GETPOST('fk_user_witness');
		$fk_soc_responsible                   = GETPOST('fk_soc_responsible');
		$fk_soc_responsible_insurance_society = GETPOST('fk_soc_responsible_insurance_society');
		$fk_accident                          = GETPOST('id');

		$workhours_morning_date_start   = dol_mktime(GETPOST('datewmshour', 'int'), GETPOST('datewmsmin', 'int'), 0, dol_print_date(dol_now(), '%m'), dol_print_date(dol_now(), '%d'), dol_print_date(dol_now(), '%Y'), 'tzuser');
		$workhours_morning_date_end     = dol_mktime(GETPOST('datewmehour', 'int'), GETPOST('datewmemin', 'int'), 0, dol_print_date(dol_now(), '%m'), dol_print_date(dol_now(), '%d'), dol_print_date(dol_now(), '%Y'), 'tzuser');
		$workhours_afternoon_date_start = dol_mktime(GETPOST('datewashour', 'int'), GETPOST('datewasmin', 'int'), 0, dol_print_date(dol_now(), '%m'), dol_print_date(dol_now(), '%d'), dol_print_date(dol_now(), '%Y'), 'tzuser');
		$workhours_afternoon_date_end   = dol_mktime(GETPOST('datewaehour', 'int'), GETPOST('datewaemin', 'int'), 0, dol_print_date(dol_now(), '%m'), dol_print_date(dol_now(), '%d'), dol_print_date(dol_now(), '%Y'), 'tzuser');

		$accident_notice_date = dol_mktime(GETPOST('datenhour', 'int'), GETPOST('datenmin', 'int'), 0, GETPOST('datenmonth', 'int'), GETPOST('datenday', 'int'), GETPOST('datenyear', 'int'));
		$register_date        = dol_mktime(GETPOST('daterhour', 'int'), GETPOST('datermin', 'int'), 0, GETPOST('datermonth', 'int'), GETPOST('daterday', 'int'), GETPOST('dateryear', 'int'));

		// Initialize object AccidentMetaData
		$now                             = dol_now();
		$accidentmetadata->date_creation = $accidentmetadata->db->idate($now);
		$accidentmetadata->tms           = $now;
		$accidentmetadata->status        = 1;

		$accidentmetadata->relative_location = $relative_location;
		$accidentmetadata->victim_activity   = $victim_activity;
		$accidentmetadata->accident_nature   = $accident_nature;
		$accidentmetadata->accident_object   = $accident_object;

		$accidentmetadata->accident_nature_doubt      = $accident_nature_doubt;
		$accidentmetadata->accident_nature_doubt_link = $accident_nature_doubt_link;
		$accidentmetadata->victim_transported_to      = $victim_transported_to;
		$accidentmetadata->collateral_victim          = $collateral_victim;

		$accidentmetadata->workhours_morning_date_start   = $workhours_morning_date_start;
		$accidentmetadata->workhours_morning_date_end     = $workhours_morning_date_end;
		$accidentmetadata->workhours_afternoon_date_start = $workhours_afternoon_date_start;
		$accidentmetadata->workhours_afternoon_date_end   = $workhours_afternoon_date_end;

		$accidentmetadata->accident_noticed                = $accident_noticed;
		$accidentmetadata->accident_notice_date            = $accident_notice_date;
		$accidentmetadata->accident_notice_by              = $accident_notice_by;
		$accidentmetadata->accident_described_by_victim    = $accident_described_by_victim;
		$accidentmetadata->registered_in_accident_register = $registered_in_accident_register;
		//$accidentmetadata->register_date                   = $register_date;
		//$accidentmetadata->register_number                 = $register_number;

		$accidentmetadata->register_date   = $object->date_creation;
		$accidentmetadata->register_number = $object->ref;

		$accidentmetadata->consequence      = $consequence;
		$accidentmetadata->police_report    = $police_report;
		$accidentmetadata->police_report_by = $police_report_by;

		$accidentmetadata->first_person_noticed_is_witness = $first_person_noticed_is_witness;
		$accidentmetadata->thirdparty_responsibility       = $thirdparty_responsibility;

		$accidentmetadata->accident_investigation      = $accident_investigation;
		$accidentmetadata->accident_investigation_link = $accident_investigation_link;
		$accidentmetadata->cerfa_link                  = $cerfa_link;

		$accidentmetadata->fk_user_witness                      = $fk_user_witness;
		$accidentmetadata->fk_soc_responsible                   = $fk_soc_responsible;
		$accidentmetadata->fk_soc_responsible_insurance_society = $fk_soc_responsible_insurance_society;
		$accidentmetadata->fk_accident                          = $fk_accident;

		if (!$error) {
			$result = $accidentmetadata->create($user);
			if ($result > 0) {
				// Update Accident metadata OK
				setEventMessages($langs->trans('AccidentMetaDataSave'), []);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Update Accident metadata KO
				if ( ! empty($accidentmetadata->errors)) {
                    setEventMessages(null, $accidentmetadata->errors, 'errors');
                } else {
                    setEventMessages($accidentmetadata->error, [], 'errors');
                }
			}
		} else {
			$action = 'edit';
		}
	}

    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/banner_actions.tpl.php';
}

/*
 * View
 */

$accidentmetadata->fetch(0, '', ' AND fk_accident = ' . $object->id . ' AND status = 1');

$title    = $langs->trans("AccidentMetaData");
$help_url = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

saturne_header(0, '', $title, $help_url);

// Part to edit record
if ($id && $action == 'edit') {
	print load_fiche_titre($title, '', "digiriskdolibarr_color@digiriskdolibarr");

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	print '<table class="border tableforfieldedit accident-metadata-table">';

	//RelativeLocation
	print '<tr><td class="minwidth400">' . $langs->trans("RelativeLocation") . '</td><td>';
	print saturne_select_dictionary('relative_location', 'c_relative_location', 'label', 'label', $accidentmetadata->relative_location ?? '', 1);
	print '<a href="' . DOL_URL_ROOT . '/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="' . $langs->trans('ConfigDico') . '">' . ' ' . img_picto('', 'globe') . '</a>';
	print '</td></tr>';

	print '<tr></tr>';

	//VictimActivity
	print '<tr class="content_field"><td><label for="victim_activity">' . $form->textwithpicto($langs->trans("VictimActivity"), $langs->trans("VictimActivityTooltip")) . '</label></td><td>';
	$doleditor = new DolEditor('victim_activity', $accidentmetadata->victim_activity, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//AccidentNature
	print '<tr class="content_field"><td><label for="accident_nature">' . $form->textwithpicto($langs->trans("AccidentNature"), $langs->trans("AccidentNatureTooltip")) . '</label></td><td>';
	$doleditor = new DolEditor('accident_nature', $accidentmetadata->accident_nature, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//AccidentObject
	print '<tr class="content_field"><td><label for="accident_object">' . $form->textwithpicto($langs->trans("AccidentObject"), $langs->trans("AccidentObjectTooltip")) . '</label></td><td>';
	$doleditor = new DolEditor('accident_object', $accidentmetadata->accident_object, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr></tr>';

	//AccidentNatureDoubt
	print '<tr><td class="minwidth400">' . $form->textwithpicto($langs->trans("AccidentNatureDoubt"), $langs->trans("AccidentNatureDoubtTooltip")) . '</td><td>';
	$doleditor = new DolEditor('accident_nature_doubt', $accidentmetadata->accident_nature_doubt, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	//AccidentNatureDoubtLink
	print '<tr><td class="minwidth400">' . img_picto('', 'globe') . ' ' . $langs->trans("AccidentNatureDoubtLink") . '</td><td>';
	print '<input type="text" class="minwidth400 url-container" name="accident_nature_doubt_link" id="accident_nature_doubt_link" value="' . $accidentmetadata->accident_nature_doubt_link . '">';
	print '</td></tr>';

	print '<tr></tr>';

	//VictimTransportedTo
	print '<tr><td class="minwidth400">' . $langs->trans("VictimTransportedTo") . '</td><td>';
	print '<input type="text" class="minwidth400" name="victim_transported_to" id="victim_transported_to" value="' . $accidentmetadata->victim_transported_to . '">';
	print '</td></tr>';

	//CollateralVictim
	print '<tr><td class="minwidth400">' . $langs->trans("CollateralVictim") . '</td><td>';
	print '<input type="checkbox" id="collateral_victim" name="collateral_victim"' . ($accidentmetadata->collateral_victim ? ' checked=""' : '') . '>';
	print '</td></tr>';

	print '<tr></tr>';

	//VictimWorkHours
	print '<tr><td class="minwidth400">' . $form->textwithpicto($langs->trans("VictimWorkHours"), $langs->trans("VictimWorkHoursTooltip")) . '</td><td>';
	print $langs->trans("FromDigirisk") . ' ' . $form->selectDate($accidentmetadata->workhours_morning_date_start, 'datewms', 1, 1, 0, '', 0) . ' ' . $langs->trans("At") . ' ' . $form->selectDate($accidentmetadata->workhours_morning_date_end, 'datewme', 1, 1, 0, '', 0) . ' ' . $langs->trans("AndFrom") . ' ' . $form->selectDate($accidentmetadata->workhours_afternoon_date_start, 'datewas', 1, 1, 0, '', 0) . ' ' . $langs->trans("At") . ' ' . $form->selectDate($accidentmetadata->workhours_afternoon_date_end, 'datewae', 1, 1, 0, '', 0);
	print '</td></tr>';

	print '<tr></tr>';

	//AccidentNoticed
	print '<tr><td class="minwidth400">' . $langs->trans("AccidentNoticed") . '</td><td>';
	print $form->selectarray('accident_noticed', array('1' => $langs->trans('Found'), '2' => $langs->trans('Known')), $accidentmetadata->accident_noticed, -1, 0, 0, '', 0, 0, 0, '', 'minwidth400', 1);
	print '</td></tr>';

	//AccidentNoticeDate
	print '<tr><td class="minwidth400"><label for="accident_notice_date">' . $langs->trans("AccidentNoticeDate") . '</label></td><td>';
	print $form->selectDate($accidentmetadata->accident_notice_date, 'daten', 1, 1, 0, '', 1, 1);
	print '</td></tr>';

	//AccidentNoticeBy
	print '<tr><td class="minwidth400">' . $langs->trans("AccidentNoticeBy") . '</td><td>';
	print $form->selectarray('accident_notice_by', array('1' => $langs->trans('ByEmployer'), '2' => $langs->trans('ByEmployees')), $accidentmetadata->accident_notice_by, -1, 0, 0, '', 0, 0, 0, '', 'minwidth400', 1);
	print '</td></tr>';

	//AccidentDescribedByVictim
	print '<tr><td class="minwidth400">' . $langs->trans("AccidentDescribedByVictim") . '</td><td>';
	print '<input type="checkbox" id="accident_described_by_victim" name="accident_described_by_victim"' . ($accidentmetadata->accident_described_by_victim ? ' checked=""' : '') . '>';
	print '</td></tr>';

	print '<tr></tr>';

	//RegisteredInAccidentRegister
	print '<tr><td class="minwidth400">' . $langs->trans("RegisteredInAccidentRegister") . '</td><td>';
	print '<input type="checkbox" id="registered_in_accident_register" name="registered_in_accident_register"' . ($accidentmetadata->registered_in_accident_register ? ' checked=""' : '') . '>';
	print '</td></tr>';

	//RegisterDate
	print '<tr><td class="minwidth400"><label for="register_date">' . $langs->trans("RegisterDate") . '</label></td><td>';
	print dol_print_date($object->date_creation);
	//print $form->selectDate($accidentmetadata->register_date, 'dater', 1, 1, 0, '', 1, 1);
	print '</td></tr>';

	//RegisterNumber
	print '<tr><td class="minwidth400">' . $langs->trans("RegisterNumber") . '</td><td>';
	print $object->ref;
	//print '<input type="text" class="minwidth400" name="register_number" id="register_number" value="' . $accidentmetadata->register_number . '">';
	print '</td></tr>';

	print '<tr></tr>';

	//Consequence
	print '<tr><td class="minwidth400">' . $form->textwithpicto($langs->trans("Consequence"), $langs->trans("ConsequenceTooltip")) . '</td><td>';
	print $form->selectarray('consequence', array('1' => $langs->trans('WithoutWorkStop'), '2' => $langs->trans('WithWorkStop'), '3' => $langs->trans('Fatal')), $accidentmetadata->consequence, -1, 0, 0, '', 0, 0, 0, '', 'minwidth400', 1);
	print '</td></tr>';

	print '<tr></tr>';

	//PoliceReport  -- Rapport de police
	print '<tr><td class="minwidth400">' . $langs->trans("PoliceReport") . '</td><td>';
	print '<input type="checkbox" id="police_report" name="police_report"' . ($accidentmetadata->police_report ? ' checked=""' : '') . '>';
	print '</td></tr>';

	//PoliceReportBy
	print '<tr><td class="minwidth400">' . $langs->trans("PoliceReportBy") . '</td><td>';
	print '<input type="text" class="minwidth400" name="police_report_by" id="police_report_by" value="' . $accidentmetadata->police_report_by . '">';
	print '</td></tr>';

	print '<tr></tr>';

	//FirstPersonNoticedIsWitness
	print '<tr><td class="minwidth400">' . img_picto('', 'user') . ' ' . $form->textwithpicto($langs->trans("FirstPersonNoticedIsWitness"), $langs->trans("FirstPersonNoticedIsWitnessTooltip")) . '</td><td>';
	print $form->selectarray('first_person_noticed_is_witness', array('1' => $langs->trans('Witness'), '2' => $langs->trans('FirstPersonNoticed')), $accidentmetadata->first_person_noticed_is_witness, -1, 0, 0, '', 0, 0, 0, '', 'minwidth400', 1);

	//FKUserWitness
	$userlist = $form->select_dolusers('', 'fk_user_witness', 0, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300', 0, 1);
	print $form->selectarray('fk_user_witness', $userlist, $accidentmetadata->fk_user_witness, $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	print '<tr></tr>';

	//ThirdPartyResponsability
	print '<tr><td class="minwidth400">' . $form->textwithpicto($langs->trans("ThirdPartyResponsability"), $langs->trans("ThirdPartyResponsabilityTooltip")) . '</td><td>';
	print '<input type="checkbox" id="thirdparty_responsibility" name="thirdparty_responsibility"' . ($accidentmetadata->thirdparty_responsibility ? ' checked=""' : '') . '>';
	print '</td></tr>';

	//FkSocResponsible
	print '<tr><td class="minwidth400">';
	print img_picto('', 'building') . ' ' . $langs->trans("FkSocResponsible");
	print '</td>';
	print '<td>';
	//For external user force the company to user company
	if ( ! empty($user->socid)) {
		print $form->select_company($user->socid, 'fk_soc_responsible', '', 1, 1, 0, '', 0, 'minwidth300');
	} else {
		print $form->select_company($accidentmetadata->fk_soc_responsible, 'fk_soc_responsible', '', 'SelectThirdParty', 1, 0, '', 0, 'minwidth300');
	}
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	//FkSocResponsibleInsuranceSociety
	print '<tr><td class="minwidth400">';
	print img_picto('', 'building') . ' ' . $langs->trans("FkSocResponsibleInsuranceSociety");
	print '</td>';
	print '<td>';
	//For external user force the company to user company
	if ( ! empty($user->socid)) {
		print $form->select_company($user->socid, 'fk_soc_responsible_insurance_society', '', 1, 1, 0, '', 0, 'minwidth300');
	} else {
		print $form->select_company($accidentmetadata->fk_soc_responsible_insurance_society, 'fk_soc_responsible_insurance_society', '', 'SelectThirdParty', 1, 0, '', 0, 'minwidth300');
	}
	print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	print '<tr></tr>';

	//AccidentInvestigation
	print '<tr><td class="minwidth400">' . $langs->trans("AccidentInvestigation") . '</td><td>';
	print '<input type="checkbox" id="accident_investigation" name="accident_investigation"' . ($accidentmetadata->accident_investigation ? ' checked=""' : '') . '>';
	print '</td></tr>';

	//AccidentInvestigationLink
	print '<tr><td class="minwidth400">' . img_picto('', 'globe') . ' ' . $langs->trans("AccidentInvestigationLink") . '</td><td>';
	print '<input type="text" class="minwidth400 url-container" name="accident_investigation_link" id="accident_investigation_link" value="' . $accidentmetadata->accident_investigation_link . '">';
	print '</td></tr>';

	//CerfaLink
	print '<tr><td class="minwidth400">' . img_picto('', 'globe') . ' ' . $langs->trans("CerfaLink") . '</td><td>';
	print '<input type="text" class="minwidth400 url-container" name="cerfa_link" id="cerfa_link" value="' . $accidentmetadata->cerfa_link . '">';
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
	print '</div>';
	print '</div>';
}

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {
	$counter = 0;

	$morecssGauge     = 'inline-block floatright';
	$move_title_gauge = 1;

	$arrayAccidentMetaData   = array();
	$arrayAccidentMetaData[] = ($accidentmetadata->relative_location == '-1' ? '' : $accidentmetadata->relative_location);
	$arrayAccidentMetaData[] = $accidentmetadata->victim_activity;
	$arrayAccidentMetaData[] = $accidentmetadata->accident_nature;
	$arrayAccidentMetaData[] = $accidentmetadata->accident_object;

	$arrayAccidentMetaData[] = $accidentmetadata->accident_nature_doubt;
	$arrayAccidentMetaData[] = $accidentmetadata->accident_nature_doubt_link;
	$arrayAccidentMetaData[] = $accidentmetadata->victim_transported_to;
	$arrayAccidentMetaData[] = $accidentmetadata->collateral_victim;

	$arrayAccidentMetaData[] = $accidentmetadata->workhours_morning_date_start;
	$arrayAccidentMetaData[] = $accidentmetadata->workhours_morning_date_end;
	$arrayAccidentMetaData[] = $accidentmetadata->workhours_afternoon_date_start;
	$arrayAccidentMetaData[] = $accidentmetadata->workhours_afternoon_date_end;

	$arrayAccidentMetaData[] = (empty($accidentmetadata->accident_noticed) ? 0 : $accidentmetadata->accident_noticed);
	$arrayAccidentMetaData[] = $accidentmetadata->accident_notice_date;
	$arrayAccidentMetaData[] = (empty($accidentmetadata->accident_notice_by) ? 0 : $accidentmetadata->accident_notice_by);
	$arrayAccidentMetaData[] = $accidentmetadata->accident_described_by_victim;

	$arrayAccidentMetaData[] = $accidentmetadata->registered_in_accident_register;

	$arrayAccidentMetaData[] = (empty($accidentmetadata->consequence) ? 0 : $accidentmetadata->consequence);
	$arrayAccidentMetaData[] = $accidentmetadata->police_report;
	$arrayAccidentMetaData[] = $accidentmetadata->police_report_by;

	$arrayAccidentMetaData[] = (empty($accidentmetadata->first_person_noticed_is_witness) ? 0 : $accidentmetadata->first_person_noticed_is_witness);
	$arrayAccidentMetaData[] = $accidentmetadata->thirdparty_responsibility;

	$arrayAccidentMetaData[] = $accidentmetadata->accident_investigation;
	$arrayAccidentMetaData[] = $accidentmetadata->accident_investigation_link;
	$arrayAccidentMetaData[] = $accidentmetadata->cerfa_link;

	$arrayAccidentMetaData[] = ($accidentmetadata->fk_user_witness > 0 ? $accidentmetadata->fk_user_witness : '');
	$arrayAccidentMetaData[] = ($accidentmetadata->fk_soc_responsible > 0 ? $accidentmetadata->fk_soc_responsible : '');
	$arrayAccidentMetaData[] = ($accidentmetadata->fk_soc_responsible_insurance_society > 0 ? $accidentmetadata->fk_soc_responsible_insurance_society : '');
	$arrayAccidentMetaData[] = ($accidentmetadata->fk_accident > 0 ? $accidentmetadata->fk_accident : '');

	$maxnumber = count($arrayAccidentMetaData);

	foreach ($arrayAccidentMetaData as $arrayAccidentMetaDataSingle) {
		if (dol_strlen($arrayAccidentMetaDataSingle) > 0) {
			$counter += 1;
		}
	}

	// Object metadata
	// ------------------------------------------------------------
	saturne_get_fiche_head($object, 'accidentMetadata', $title);

	include_once './../../core/tpl/digiriskdolibarr_configuration_gauge_view.tpl.php';

    // Object card
    // ------------------------------------------------------------
    list($moreHtmlRef, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object, 'id', '', 1, 'rowid', 'ref', $moreHtmlRef, dol_strlen($object->photo) > 0, $moreParams);

	print '<div class="div-table-responsive">';
	print '<div class="fichecenter">';
	print '<table class="border centpercent tableforfield">';

	//Unset for order
	unset($accidentmetadata->fields['json']);

    $accident = $object;
	$object   = $accidentmetadata;

	//Relative location
	if (dol_strlen($object->relative_location) > 0 && $object->relative_location != '-1') {
        $object->relative_location = $langs->trans($object->relative_location);
	} else {
        $object->relative_location = '';
    }

	//Accident Noticed
	if (empty($object->accident_noticed) || $object->accident_noticed < 0) {
		$object->accident_noticed = '';
	} else if ($object->accident_noticed == 1) {
		$object->accident_noticed = $langs->trans('Found');
	} else if ($object->accident_type == 2) {
		$object->accident_noticed = $langs->trans('Known');
	}

	//AccidentNoticeBy
	if (empty($object->accident_notice_by) || $object->accident_notice_by < 0) {
		$object->accident_notice_by = '';
	} else if ($object->accident_notice_by == 1) {
		$object->accident_notice_by = $langs->trans('ByEmployer');
	} else if ($object->accident_notice_by == 2) {
		$object->accident_notice_by = $langs->trans('ByEmployees');
	}

	//Consequence
	if (empty($object->consequence) || $object->consequence < 0) {
		$object->consequence = '';
	} else if ($object->consequence == 1) {
		$object->consequence = $langs->trans('WithoutWorkStop');
	} else if ($object->consequence == 2) {
		$object->consequence = $langs->trans('WithWorkStop');
	} else if ($object->consequence == 3) {
		$object->consequence = $langs->trans('Fatal');
	}

	//FirstPersonNoticedIsWitness
	if (empty($object->first_person_noticed_is_witness) || $object->first_person_noticed_is_witness < 0) {
		$object->first_person_noticed_is_witness = '';
	} else if ($object->first_person_noticed_is_witness == 1) {
		$object->first_person_noticed_is_witness = $langs->trans('Witness');
	} else if ($object->first_person_noticed_is_witness == 2) {
		$object->first_person_noticed_is_witness = $langs->trans('FirstPersonNoticed');
	}

	$usertmp->fetch($object->fk_user_witness);
	if ($usertmp > 0) {
		$object->fk_user_witness = $usertmp->getNomUrl(1);
	}

	$thirdparty->fetch($object->fk_soc_responsible);
	if ($thirdparty > 0) {
		$object->fk_soc_responsible = getNomUrlSociety($thirdparty, 1, 'blank');
	}

	$thirdparty->fetch($object->fk_soc_responsible_insurance_society);
	if ($thirdparty > 0) {
		$object->fk_soc_responsible_insurance_society = getNomUrlSociety($thirdparty, 1, 'blank');
	}

	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print dol_get_fiche_end();

	// Buttons for actions
	print '<div class="tabsAction" >';
	$parameters = array();
	$reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	if (empty($reshook) && $accident->status < Accident::STATUS_VALIDATED) {
		print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=edit' . '">' . $langs->trans("Modify") . '</a>';
	}
	print '</div>';
}

// End of page
llxFooter();
$db->close();
