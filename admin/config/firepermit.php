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
 * \file    admin/config/firepermit.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr firepermit page.
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
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_firepermitround.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';
require_once __DIR__ . '/../../class/firepermit.class.php';

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$error = 0;

// Initialize technical objects
$usertmp = new User($db);
$object  = new FirePermit($db);

// Initialize view objects
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$form = new Form($db);

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$FPRProject = GETPOST('FPRProject', 'none');
	$FPRProject = preg_split('/_/', $FPRProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_FIREPERMIT_PROJECT", $FPRProject[0], 'integer', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

// Actions set_mod, update_mask
require_once __DIR__ . '/../../../saturne/core/tpl/actions/admin_conf_actions.tpl.php';

if ($action == 'setMaitreOeuvre') {
	$masterWorkerId = GETPOST('maitre_oeuvre');

	if ( ! $error) {
		$constforval = 'DIGIRISKDOLIBARR_' . strtoupper($object->element) . "_MAITRE_OEUVRE";
		dolibarr_set_const($db, $constforval, $masterWorkerId, 'integer', 0, '', $conf->entity);
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}

if ($action == 'setMobileDefaults' && !GETPOST('cancel', 'alpha')) {
	// Un permis de feu couvre des travaux courts : la duree proposee reste dans le mois accepte
	$defaultDuration = GETPOSTINT('DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DURATION');
	if ($defaultDuration < 1) {
		$defaultDuration = 1;
	}
	if ($defaultDuration > 31) {
		$defaultDuration = 31;
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DURATION', $defaultDuration, 'integer', 0, '', $conf->entity);

	$defaultStartToday = GETPOSTINT('DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DATE_START_TODAY');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DATE_START_TODAY', $defaultStartToday ? 1 : 0, 'integer', 0, '', $conf->entity);

	$defaultEmailAutoSend = GETPOSTINT('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_AUTO_SEND');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_AUTO_SEND', $defaultEmailAutoSend ? 1 : 0, 'integer', 0, '', $conf->entity);

	$emailTemplateExt = GETPOSTINT('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_EXT');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_EXT', $emailTemplateExt, 'integer', 0, '', $conf->entity);

	$emailTemplateInt = GETPOSTINT('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_INT');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_INT', $emailTemplateInt, 'integer', 0, '', $conf->entity);

	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	if (!$error) {
		header('Location: ' . $_SERVER['PHP_SELF']);
		exit;
	}
}

if ($action == 'setFireWatch' && !GETPOST('cancel', 'alpha')) {
	$roundDelays = digiriskFirePermitParseRoundDelays(GETPOST('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_DELAYS', 'alphanohtml'));
	if (empty($roundDelays)) {
		setEventMessages($langs->trans('FireWatchErrorNoDelay'), null, 'errors');
		$error++;
	}

	if (!$error) {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_ROUND_DELAYS', implode(',', $roundDelays), 'chaine', 0, '', $conf->entity);

		foreach (DIGIRISK_FIREPERMIT_ROUND_ITEMS as $roundItem) {
			$constName = 'DIGIRISKDOLIBARR_FIREPERMIT_ROUND_' . strtoupper($roundItem);
			$itemLevel = GETPOSTINT($constName);
			if (!in_array($itemLevel, [DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED, DIGIRISK_FIREPERMIT_ROUND_ITEM_OPTIONAL, DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED], true)) {
				$itemLevel = DIGIRISK_FIREPERMIT_ROUND_ITEM_OPTIONAL;
			}
			dolibarr_set_const($db, $constName, $itemLevel, 'integer', 0, '', $conf->entity);
		}

		$publicInterface = GETPOSTINT('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_INTERFACE') ? 1 : 0;
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_INTERFACE', $publicInterface, 'integer', 0, '', $conf->entity);

		// The link handed to the watcher carries this key: create it the first time the interface is opened
		if ($publicInterface && !dol_strlen(getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_KEY'))) {
			dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_KEY', bin2hex(random_bytes(24)), 'chaine', 0, '', $conf->entity);
		}

		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
		header('Location: ' . $_SERVER['PHP_SELF'] . '#firewatch');
		exit;
	}
}

// A new key cuts off every link handed out so far, for instance once a guard company leaves
if ($action == 'setNewFireWatchKey') {
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_KEY', bin2hex(random_bytes(24)), 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans('FireWatchPublicKeyRenewed'), null, 'mesgs');
	header('Location: ' . $_SERVER['PHP_SELF'] . '#firewatch');
	exit;
}

/*
 * View
 */

$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl  = 'FR:Module_Digirisk';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'firepermit', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

print load_fiche_titre('<i class="fas fa-fire-alt"></i> ' . $langs->trans("FirePermitManagement"), '', '');
print '<hr>';
print load_fiche_titre($langs->trans("LinkedProject"), '', '');

// Project
if (isModEnabled('project')) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<table class="noborder centpercent editmode">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("SelectProject") . '</td>';
	print '<td>' . $langs->trans("Action") . '</td>';
	print '</tr>';

	$langs->load("projects");
	print '<tr class="oddeven"><td><label for="FPRProject">' . $langs->trans("FPRProject") . '</label></td><td>';
	$formproject->select_projects(-1,  $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_PROJECT, 'FPRProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}

$objectModSubdir = 'digiriskelement';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$object = new FirePermitLine($db);

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

print load_fiche_titre($langs->trans("FirePermitData"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="fire_permit_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMaitreOeuvre">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MasterWorker">' . $langs->trans("MasterWorker") . '</label></td>';
print '<td>' . $langs->trans("MasterWorkerDescription") . '</td>';
$userlist = $form->select_dolusers(( ! empty($conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE) ? $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE : $user->id), '', 0, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300', 0, 1);
print '<td>';
print $form->selectarray('maitre_oeuvre', $userlist, ( ! empty($conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE) ? $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE : $user->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
print '</td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// --- Mobile creation defaults ---
print load_fiche_titre($langs->trans('MobilePPDefaultsTitle'), '', '');

$defaultDuration      = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DURATION', 1);
$defaultStartToday    = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DATE_START_TODAY', 1);
$defaultEmailAutoSend = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_AUTO_SEND', 0);
$emailTemplateExt     = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_EXT', 0);
$emailTemplateInt     = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_INT', 0);

// Fetch email templates
$emailTemplatesList = ['0' => ''];
$sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE type_template = 'firepermit' AND active = 1 ORDER BY position ASC, label ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$emailTemplatesList[$obj->rowid] = $obj->label;
	}
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="mobile_defaults_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMobileDefaults">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Option') . '</td>';
print '<td>' . $langs->trans('DefaultValue') . '</td>';
print '<td>' . $langs->trans('Action') . '</td>';
print '</tr>';

// Default start date = today
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DATE_START_TODAY">' . $langs->trans('MobileFPDefaultDateStartToday') . '</label></td>';
print '<td><input type="checkbox" name="DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DATE_START_TODAY" id="DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DATE_START_TODAY" value="1"' . ($defaultStartToday ? ' checked' : '') . '> ' . $langs->trans('Yes') . '</td>';
print '<td rowspan="5"><input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '"></td>';
print '</tr>';

// Default duration in days
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DURATION">' . $langs->trans('MobileFPDefaultDuration') . '</label></td>';
print '<td><input type="number" name="DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DURATION" id="DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_DURATION" value="' . $defaultDuration . '" min="1" max="31" class="flat minwidth100"> ' . $langs->trans('Days') . '</td>';
print '</tr>';

// Email Template Ext
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_EXT">' . $langs->trans('MobilePPEmailTemplateExt') . '</label></td>';
print '<td>' . $form->selectarray('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_EXT', $emailTemplatesList, $emailTemplateExt, 0, 0, 0, '', 1) . '</td>';
print '</tr>';

// Auto-send signature email on mobile creation
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_AUTO_SEND">' . $langs->trans('MobilePPEmailAutoSend') . '</label></td>';
print '<td><input type="checkbox" name="DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_AUTO_SEND" id="DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_AUTO_SEND" value="1"' . ($defaultEmailAutoSend ? ' checked' : '') . '> ' . $langs->trans('Yes') . '</td>';
print '</tr>';

// Email Template Int
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_INT">' . $langs->trans('MobilePPEmailTemplateInt') . '</label></td>';
print '<td>' . $form->selectarray('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_INT', $emailTemplatesList, $emailTemplateInt, 0, 0, 0, '', 1) . '</td>';
print '</tr>';

print '</table>';
print '</form>';

// --- Fire watch rounds after the hot work ---
print '<div id="firewatch"></div>';
print load_fiche_titre('<i class="fas fa-walking pictofixedwidth"></i>' . $langs->trans('FireWatchSetupTitle'), '', '');
print '<div class="opacitymedium marginbottomonly">' . $langs->trans('FireWatchSetupDescription') . '</div>';

$roundItemLevels = [
	DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED => $langs->trans('Disabled'),
	DIGIRISK_FIREPERMIT_ROUND_ITEM_OPTIONAL => $langs->trans('Optional'),
	DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED => $langs->trans('Required'),
];
$roundItemLabels = [
	'comment'   => 'Comment',
	'photo'     => 'Photo',
	'geoloc'    => 'FireWatchGeoloc',
	'signature' => 'Signature',
];

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="firewatch_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setFireWatch">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Option') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '<td>' . $langs->trans('Action') . '</td>';
print '</tr>';

// Delays of the rounds after the end of the work
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_FIREPERMIT_ROUND_DELAYS">' . $langs->trans('FireWatchRoundDelays') . '</label>';
print '<div class="opacitymedium small">' . $langs->trans('FireWatchRoundDelaysHelp') . '</div></td>';
print '<td><input type="text" name="DIGIRISKDOLIBARR_FIREPERMIT_ROUND_DELAYS" id="DIGIRISKDOLIBARR_FIREPERMIT_ROUND_DELAYS" class="flat minwidth200" value="' . dol_escape_htmltag(implode(', ', digiriskFirePermitGetRoundDelays())) . '"> ' . $langs->trans('Minutes');
print '<div class="opacitymedium small">' . dol_escape_htmltag(implode(' / ', array_map('digiriskFirePermitFormatDelay', digiriskFirePermitGetRoundDelays()))) . '</div></td>';
print '<td rowspan="6"><input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '"></td>';
print '</tr>';

// What the watcher is asked for when recording a round
foreach (DIGIRISK_FIREPERMIT_ROUND_ITEMS as $roundItem) {
	$constName = 'DIGIRISKDOLIBARR_FIREPERMIT_ROUND_' . strtoupper($roundItem);
	print '<tr class="oddeven"><td><label for="' . $constName . '">' . $langs->trans('FireWatchAskFor', $langs->transnoentities($roundItemLabels[$roundItem])) . '</label></td>';
	print '<td>' . $form->selectarray($constName, $roundItemLevels, digiriskFirePermitRoundItemLevel($roundItem), 0, 0, 0, '', 0, 0, 0, '', 'minwidth150') . '</td>';
	print '</tr>';
}

// Public interface used by the watcher
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_INTERFACE">' . $langs->trans('FireWatchPublicInterface') . '</label>';
print '<div class="opacitymedium small">' . $langs->trans('FireWatchPublicInterfaceHelp') . '</div></td>';
print '<td><input type="checkbox" name="DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_INTERFACE" id="DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_INTERFACE" value="1"' . (getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_INTERFACE') ? ' checked' : '') . '> ' . $langs->trans('Yes') . '</td>';
print '</tr>';

print '</table>';
print '</form>';

$fireWatchPublicUrl = digiriskFirePermitRoundsPublicUrl();
if (dol_strlen($fireWatchPublicUrl)) {
	print '<div class="firepermit-firewatch__public">';
	print '<div class="firepermit-firewatch__qr">' . digiriskGetQrCodeSvg($fireWatchPublicUrl) . '</div>';
	print '<div>';
	print '<div><strong>' . $langs->trans('FireWatchPublicLink') . '</strong></div>';
	print '<div class="opacitymedium small">' . $langs->trans('FireWatchPublicLinkAdminHelp') . '</div>';
	print '<div class="firepermit-firewatch__url"><a href="' . dol_escape_htmltag($fireWatchPublicUrl) . '" target="_blank" rel="noopener">' . dol_escape_htmltag($fireWatchPublicUrl) . '</a> ' . showValueWithClipboardCPButton($fireWatchPublicUrl, 0, 'none') . '</div>';
	print '<div class="margintoponly"><a class="button smallpaddingimp" href="' . $_SERVER['PHP_SELF'] . '?action=setNewFireWatchKey&token=' . newToken() . '">' . $langs->trans('FireWatchRenewPublicKey') . '</a></div>';
	print '</div>';
	print '</div>';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
