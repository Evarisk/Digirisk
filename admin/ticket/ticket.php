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
 *     \file        admin/ticket.php
 *     \ingroup     digiriskdolibarr
 *     \brief       Page to public interface of module DigiriskDolibarr for ticket
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

global $conf, $langs, $user, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../../lib/digiriskdolibarr.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));
$extra_fields = new ExtraFields( $db );
$category     = new Categorie( $db );

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
	if (GETPOST('value')) dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 1, 'integer', 0, '', $conf->entity);
	else dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 0, 'integer', 0, '', $conf->entity);
}

if ($action == 'setEmails') {
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', GETPOST('emails'), 'integer', 0, '', $conf->entity);
}

if ($action == 'generateExtrafields') {
	$ret1 = $extra_fields->addExtraField( 'digiriskdolibarr_ticket_lastname', $langs->trans("LastName"), 'varchar', 2000, 255, 'ticket', 0, 0, '', '', 1, '', 1);
	$ret2 = $extra_fields->addExtraField( 'digiriskdolibarr_ticket_firstname', $langs->trans("FirstName"), 'varchar', 2100, 255, 'ticket', 0, 0, '', '', 1, '', 1);
	$ret3 = $extra_fields->addExtraField( 'digiriskdolibarr_ticket_phone', $langs->trans("Phone"), 'phone', 2200, '', 'ticket', 0, 0, '', '', 1, '', 1);
	$ret4 = $extra_fields->addExtraField( 'digiriskdolibarr_ticket_service', $langs->trans("Service"), 'varchar', 2300, 255, 'ticket', 0, 0, '', '', 1, '', 1);
	$ret5 = $extra_fields->addExtraField( 'digiriskdolibarr_ticket_location', $langs->trans("Location"), 'varchar', 2400, 255, 'ticket', 0, 0, '', '', 1, '', 1);
	$ret6 = $extra_fields->addExtraField( 'digiriskdolibarr_ticket_date', $langs->trans("Date"), 'datetime', 2500, '', 'ticket', 0, 0, '', '', 1, '', 1);
	if ($ret1 > 0 && $ret2 >  0 && $ret3 >  0 && $ret4 > 0 && $ret5 >  0 && $ret6 > 0) {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 1, 'integer', 0, '', $conf->entity);
		setEventMessages($langs->trans('ExtrafieldsCreated'), array());
	} else {
		setEventMessages($extra_fields->error, null, 'errors');
	}
}

if ($action == 'generateCategories') {

	$category->label = $langs->trans('Register');
	$category->description = '';
	$category->color = '';
	$category->visible = 1;
	$category->type = 'ticket';

	$result = $category->create($user);
	if ($result > 0) {

		$category->label = $langs->trans('Accident');
		$category->description = '';
		$category->color = '';
		$category->visible = 1;
		$category->type = 'ticket';
		$category->fk_parent = $result;
		$result2 = $category->create($user);

		if ($result2 > 0) {
			$category->label = $langs->trans('PresquAccident');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result2;
			$category->create($user);

			$category->label = $langs->trans('AccidentWithDIAT');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result2;
			$category->create($user);

			$category->label = $langs->trans('AccidentWithoutDIAT');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result2;
			$category->create($user);
		} else {
			setEventMessages($category->error, null, 'errors');
		}

		$category->label = $langs->trans('SST');
		$category->description = '';
		$category->color = '';
		$category->visible = 1;
		$category->type = 'ticket';
		$category->fk_parent = $result;
		$result3 = $category->create($user);

		if ($result3 > 0) {
			$category->label = $langs->trans('AnticipatedLeave');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result3;
			$category->create($user);

			$category->label = $langs->trans('Others');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result3;
			$category->create($user);

			$category->label = $langs->trans('HumanProblem');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result3;
			$category->create($user);

			$category->label = $langs->trans('MaterialProblem');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result3;
			$category->create($user);

			$category->label = $langs->trans('EnhancementSuggestion');
			$category->description = '';
			$category->color = '';
			$category->visible = 1;
			$category->type = 'ticket';
			$category->fk_parent = $result3;
			$category->create($user);

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		$category->label = $langs->trans('DGI');
		$category->description = '';
		$category->color = '';
		$category->visible = 1;
		$category->type = 'ticket';
		$category->fk_parent = $result;
		$result4 = $category->create($user);

		if ($result2 > 0 && $result3 > 0 && $result4 > 0) {
			dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 1, 'integer', 0, '', $conf->entity);
			setEventMessages($langs->trans('CategoriesCreated'), array());
		}

	} else {
	  setEventMessages($category->error, null, 'errors');
	}

}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title    = $langs->trans("Ticket");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'ticket', '', -1, "digiriskdolibarr@digiriskdolibarr");
print '<span class="opacitymedium">'.$langs->trans("DigiriskTicketPublicAccess").'</span> : <a class="wordbreak" href="'.dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php', 1).'" target="_blank" >'.dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php', 2).'</a>';
print dol_get_fiche_end();

$enabledisablehtml = $langs->trans("TicketActivatePublicInterface").' ';
if (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE)) {
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
print '<input type="hidden" id="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" name="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" value="'.(empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE) ? 0 : 1).'">';

print '<br><br>';

if (!empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Parameters") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("Action") . '</td>';
	print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
	print '</tr>';

	// Show logo for company
	print '<tr class="oddeven"><td>'.$langs->trans("TicketShowCompanyLogo").'</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketShowCompanyLogoHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

// Extrafields generation

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateExtrafields">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("GenerateExtrafields") . '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
	print '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS ? '<button type="submit" class="wpeo-button button-disable">' . $langs->trans('Create') . '</button> ' : '<button type="submit" class="wpeo-button button-blue">' . $langs->trans('Create') . '</button>';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("ExtrafieldsGeneration"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

//Categories generation

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateCategories">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("GenerateCategories") . '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
	print '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? '<button type="submit" class="wpeo-button button-disable">' . $langs->trans('Create') . '</button> ' : '<button type="submit" class="wpeo-button button-blue">' . $langs->trans('Create') . '</button>';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("CategoriesGeneration"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	//Envoi d'emails automatiques
	print '<tr class="oddeven"><td>'.$langs->trans("SendEmailOnTicketSubmit").'</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_SEND_EMAIL_ON_TICKET_SUBMIT');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("SendEmailOnTicketSubmitHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	//Use captcha on create ticket paghe
	print '<tr class="oddeven"><td>'.$langs->trans("UseCaptchaOnCreateTicketPage").'</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_USE_CAPTCHA');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print '</td>';
	print '</tr>';


	//Email to send ticket submitted
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setEmails">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("SendEmailTo") . '</td>';
	print '<td class="center">';
	print '<input name="emails" id="emails" value="'.$conf->global->DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO.'">';
	print '</td>';
	print '<td class="center">';
	print '<button class="wpeo-button" type="submit">' . $langs->trans('Save') . '</button>';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("MultipleEmailsSeparator"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';

}


// End of page
llxFooter();
$db->close();
