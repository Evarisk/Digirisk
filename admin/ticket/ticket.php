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
 *     \file        admin/ticket/ticket.php
 *     \ingroup     digiriskdolibarr
 *     \brief       Page to public interface of module DigiriskDolibarr for ticket
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

global $conf, $langs, $user, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php";
include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";

require_once '../../lib/digiriskdolibarr.lib.php';
require_once '../../lib/digiriskdolibarr_ticket.lib.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));
$extra_fields = new ExtraFields($db);
$category     = new Categorie($db);

// Access control
if ( ! $user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

/*
 * Actions
 */

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$TSProject = GETPOST('TSProject', 'none');
	$TSProject = preg_split('/_/', $TSProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_TICKET_PROJECT", $TSProject[0], 'integer', 0, '', $conf->entity);
	setEventMessages($langs->trans('TicketProjectUpdated'), array());

	if ($action != 'updateedit' && ! $error) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'setPublicInterface') {
	if (GETPOST('value')) dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 1, 'integer', 0, '', $conf->entity);
	else dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 0, 'integer', 0, '', $conf->entity);
	setEventMessages($langs->trans('TicketPublicInterfaceEnabled'), array());
}

if ($action == 'setEmails') {
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', GETPOST('emails'), 'integer', 0, '', $conf->entity);
	setEventMessages($langs->trans('EmailsToNotifySet'), array());
}

if ($action == 'generateExtrafields') {
	$ret1 = $extra_fields->addExtraField('digiriskdolibarr_ticket_lastname', $langs->trans("LastName"), 'varchar', 2000, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret2 = $extra_fields->addExtraField('digiriskdolibarr_ticket_firstname', $langs->transnoentities("FirstName"), 'varchar', 2100, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret3 = $extra_fields->addExtraField('digiriskdolibarr_ticket_phone', $langs->transnoentities("Phone"), 'phone', 2200, '', 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret4 = $extra_fields->addExtraField('digiriskdolibarr_ticket_service', $langs->trans("Service"), 'varchar', 2300, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret5 = $extra_fields->addExtraField('digiriskdolibarr_ticket_location', $langs->trans("Location"), 'varchar', 2400, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret6 = $extra_fields->addExtraField('digiriskdolibarr_ticket_date', $langs->trans("Date"), 'datetime', 2500, '', 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	if ($ret1 > 0 && $ret2 > 0 && $ret3 > 0 && $ret4 > 0 && $ret5 > 0 && $ret6 > 0) {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 1, 'integer', 0, '', 0);
		setEventMessages($langs->trans('ExtrafieldsCreated'), array());
	} else {
		setEventMessages($extra_fields->error, null, 'errors');
	}
}
$upload_dir = $conf->categorie->multidir_output[$conf->entity?:1];
global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

if ($action == 'generateCategories') {

	$result = createTicketCategory($langs->trans('Register'), '', '', 1, 'ticket');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', $result, 'integer', 0, '', $conf->entity);

	if ($result > 0) {

		$result2 = createTicketCategory($langs->trans('Accident'), '', 'FFA660', 1, 'ticket', $result,'pictogramme_Accident_32px.png');

		if ($result2 > 0) {

			createTicketCategory($langs->trans('PresquAccident'), '', '', 1, 'ticket', $result2,"pictogramme_presqu-accident_64px.png");
			createTicketCategory($langs->trans('AccidentWithoutDIAT'), '', '', 1, 'ticket', $result2,'pictogramme_accident-benin_64px.png');
			createTicketCategory($langs->trans('AccidentWithDIAT'), '', '', 1, 'ticket', $result2,'pictogramme_accident-du-travail_64px.png');

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		$result3 = createTicketCategory($langs->trans('SST'), '', '7594F6', 1, 'ticket', $result,'pictogramme_Sante-et-securite_32px.png');

		if ($result3 > 0) {

			createTicketCategory($langs->trans('AnticipatedLeave'), '', '', 1, 'ticket', $result3,'pictogramme_depart-anticipe_64px.png');
			createTicketCategory($langs->trans('HumanProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-humain_64px.png');
			createTicketCategory($langs->trans('MaterialProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-materiel_64px.png');
			createTicketCategory($langs->trans('Others'), '', '', 1, 'ticket', $result3,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		createTicketCategory($langs->trans('DGI'), '', 'E96A6A', 1, 'ticket', $result,'pictogramme_Danger-grave-et-imminent_32px.png');

		$result4 = createTicketCategory($langs->trans('Quality'), '', 'FFDF77', 1, 'ticket', $result,'pictogramme_Qualité_32px.png');

		if ($result4 > 0) {

			createTicketCategory($langs->trans('NonCompliance'), '', '', 1, 'ticket', $result4,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->trans('EnhancementSuggestion'), '', '', 1, 'ticket', $result4,"pictogramme_suggestions-amelioration_64px.png");

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		$result5 = createTicketCategory($langs->trans('Environment'), '', '5CD264', 1, 'ticket', $result,'pictogramme_environnement_32px.png');

		if ($result5 > 0) {

			createTicketCategory($langs->trans('NonCompliance'), '', '', 1, 'ticket', $result5,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->trans('Others'), '', '', 1, 'ticket', $result5,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		if ($result2 > 0 && $result3 > 0 && $result4 > 0) {
			dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 1, 'integer', 0, '', $conf->entity);
			setEventMessages($langs->trans('CategoriesCreated'), array());
		}
	} else {
		setEventMessages($category->error, null, 'errors');
	}
}

if ($action == 'setMainCategory') {
	$category_id = GETPOST('mainCategory');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', $category_id, 'integer', 0, '', $conf->entity);
	setEventMessages($langs->trans('MainCategorySet'), array());
}

if ($action == 'setParentCategoryLabel') {
	$label = GETPOST('parentCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL', $label, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans('ParentCategoryLabelSet'), array());
}

if ($action == 'setChildCategoryLabel') {
	$label = GETPOST('childCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL', $label, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans('ChildCategoryLabelSet'), array());
}

if ($action == 'setTicketSuccessMessage') {
	$successmessage = GETPOST('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', $successmessage, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans('TicketSuccessMessageSet'), array());
}

/*
 * View
 */

if ( ! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }
$form = new Form($db);

$help_url = '';
$title    = $langs->trans("Ticket");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();


print dol_get_fiche_head($head, 'ticket', '', -1, "digiriskdolibarr@digiriskdolibarr");
print load_fiche_titre('<i class="fa fa-ticket"></i> ' . $langs->trans("TicketManagement"), '', '');
print '<hr>';
print load_fiche_titre($langs->trans("PublicInterface"), '', '');

print '<span class="opacitymedium">' . $langs->trans("DigiriskTicketPublicAccess") . '</span> : <a class="wordbreak" href="' . dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity, 1) . '" target="_blank" >' . dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity, 2) . '</a>';
print dol_get_fiche_end();

$enabledisablehtml = $langs->trans("TicketActivatePublicInterface") . ' ';
if (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=1' . $param . '">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=0' . $param . '">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" name="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" value="' . (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE) ? 0 : 1) . '">';

print '<br><br>';

if ( ! empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Parameters") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("Action") . '</td>';
	print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
	print '</tr>';

	// Show logo for company
	print '<tr class="oddeven"><td>' . $langs->trans("TicketShowCompanyLogo") . '</td>';
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

	//Envoi d'emails automatiques
	print '<tr class="oddeven"><td>' . $langs->trans("SendEmailOnTicketSubmit") . '</td>';
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

	//Email to send ticket submitted
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setEmails">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("SendEmailTo") . '</td>';
	print '<td class="center">';
	print '<input name="emails" id="emails" value="' . $conf->global->DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO . '">';
	print '</td>';
	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->trans('Save').'">';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("MultipleEmailsSeparator"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';

	print load_fiche_titre($langs->trans("TicketSuccessMessageData"), '', '');

	print '<table class="noborder centpercent">';

	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setTicketSuccessMessage">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td class="center">' . $langs->trans("Action") . '</td>';
	print "</tr>";

	// Ticket success message
	$successmessage = $langs->trans($conf->global->DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE) ?: $langs->trans('YouMustNotifyYourHierarchy');
	print '<tr class="oddeven"><td>'.$langs->trans("TicketSuccessMessage");
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', $successmessage, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
	$doleditor->Create();
	print '</td>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</td></tr>';
	print '</form>';
	print '</table>';

	print '</div>';

	// Project
	print load_fiche_titre($langs->trans("LinkedProject"), '', '');

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="project_form">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<table class="noborder centpercent editmode">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("SelectProject") . '</td>';
	print '<td>' . $langs->trans("Action") . '</td>';
	print '</tr>';

	if ( ! empty($conf->projet->enabled)) {
		$langs->load("projects");
		print '<tr class="oddeven"><td><label for="TSProject">' . $langs->trans("TSProject") . '</label></td><td>';
		$numprojet = $formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_TICKET_PROJECT, 'TSProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
		print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
		print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
		print '</td></tr>';
	}

	print '</table>';
	print '</form>';

	print load_fiche_titre($langs->trans("TicketCategories"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Parameters") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("Action") . '</td>';
	print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
	print '</tr>';

	//Categories generation

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateCategories">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("GenerateCategories") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 1</a></sup></td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
	print '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? '<a type="" class=" butActionRefused" value="">'.$langs->trans('Create') .'</a>' : '<input type="submit" class="button" value="'.$langs->trans('Create') .'">' ;
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("CategoriesGeneration"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set default main category

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setMainCategory">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("MainCategory") . '</td>';
	$formother = new FormOther($db);
	print '<td class="center">';
	print $formother->select_categories('ticket', $conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY,'mainCategory');
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->trans('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("MainCategorySetting"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set parent category label

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setParentCategoryLabel">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("ParentCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 2</a></sup></td>';
	print '<td class="center">';
	print '<input name="parentCategoryLabel" value="'. $conf->global->DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL .'">';
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->trans('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("ParentCategorySetting"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set child category label

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setChildCategoryLabel">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->trans("ChildCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 3</a></sup></td>';
	print '<td class="center">';
	print '<input name="childCategoryLabel" value="'. $conf->global->DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL .'">';
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->trans('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("ChildCategorySetting"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';

	print load_fiche_titre($langs->trans("TicketExtrafields"), '', '');

	// Extrafields generation
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Parameters") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("Action") . '</td>';
	print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
	print '</tr>';

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateExtrafields">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';


	print '<tr class="oddeven"><td>' . $langs->trans("GenerateExtrafields") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 4</a></sup></td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
	print '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS ? '<a type="" class=" butActionRefused" value="">'.$langs->trans('Create') .'</a>' : '<input type="submit" class="button" value="'.$langs->trans('Create') .'">' ;
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("ExtrafieldsGeneration"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';
	print '<span class="opacitymedium">' . $langs->trans("TicketPublicInterfaceConfigDocumentation") . '</span> : <a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" >' . $langs->transnoentities('DigiriskDocumentation') . '</a>';
}


// End of page
llxFooter();
$db->close();
