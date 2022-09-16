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
include_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

require_once '../../lib/digiriskdolibarr.lib.php';
require_once '../../lib/digiriskdolibarr_ticket.lib.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));
$extra_fields = new ExtraFields($db);
$category     = new Categorie($db);
$ticket       = new Ticket($db);

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
	setEventMessages($langs->transnoentities('TicketProjectUpdated'), array());

	if ($action != 'updateedit' && ! $error) {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'setPublicInterface') {
	if (GETPOST('value')) dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 1, 'integer', 0, '', $conf->entity);
	else dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 0, 'integer', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('TicketPublicInterfaceEnabled'), array());
}

if ($action == 'setEmails') {
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', GETPOST('emails'), 'integer', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('EmailsToNotifySet'), array());
}

if ($action == 'generateExtrafields') {
	$ret1 = $extra_fields->addExtraField('digiriskdolibarr_ticket_lastname', $langs->transnoentities("LastName"), 'varchar', 2000, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret2 = $extra_fields->addExtraField('digiriskdolibarr_ticket_firstname', $langs->transnoentities("FirstName"), 'varchar', 2100, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret3 = $extra_fields->addExtraField('digiriskdolibarr_ticket_phone', $langs->transnoentities("Phone"), 'phone', 2200, '', 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret4 = $extra_fields->addExtraField('digiriskdolibarr_ticket_service', $langs->transnoentities("Service"), 'sellist', 2300, '255', 'ticket', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:61:"digiriskdolibarr_digiriskelement:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 4, '','',0);
	$ret5 = $extra_fields->addExtraField('digiriskdolibarr_ticket_location', $langs->transnoentities("Location"), 'varchar', 2400, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	$ret6 = $extra_fields->addExtraField('digiriskdolibarr_ticket_date', $langs->transnoentities("Date"), 'datetime', 2500, '', 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
	if ($ret1 > 0 && $ret2 > 0 && $ret3 > 0 && $ret4 > 0 && $ret5 > 0 && $ret6 > 0) {
		setEventMessages($langs->transnoentities('ExtrafieldsCreated'), array());
	} else {
		setEventMessages($extra_fields->error, null, 'errors');
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 1, 'integer', 0, '', 0);
}
$upload_dir = $conf->categorie->multidir_output[$conf->entity?:1];
global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

if ($action == 'generateCategories') {

	$result = createTicketCategory($langs->transnoentities('Register'), '', '', 1, 'ticket');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', $result, 'integer', 0, '', $conf->entity);

	if ($result > 0) {

		$result2 = createTicketCategory($langs->transnoentities('Accident'), '', 'FFA660', 1, 'ticket', $result,'pictogramme_Accident_32px.png');

		if ($result2 > 0) {

			createTicketCategory($langs->transnoentities('PresquAccident'), '', '', 1, 'ticket', $result2,"pictogramme_presqu-accident_64px.png");
			createTicketCategory($langs->transnoentities('AccidentWithoutDIAT'), '', '', 1, 'ticket', $result2,'pictogramme_accident-benin_64px.png');
			createTicketCategory($langs->transnoentities('AccidentWithDIAT'), '', '', 1, 'ticket', $result2,'pictogramme_accident-du-travail_64px.png');

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		$result3 = createTicketCategory($langs->transnoentities('SST'), '', '7594F6', 1, 'ticket', $result,'pictogramme_Sante-et-securite_32px.png');

		if ($result3 > 0) {

			createTicketCategory($langs->transnoentities('AnticipatedLeave'), '', '', 1, 'ticket', $result3,'pictogramme_depart-anticipe_64px.png');
			createTicketCategory($langs->transnoentities('HumanProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-humain_64px.png');
			createTicketCategory($langs->transnoentities('MaterialProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-materiel_64px.png');
			createTicketCategory($langs->transnoentities('Others'), '', '', 1, 'ticket', $result3,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		createTicketCategory($langs->transnoentities('DGI'), '', 'E96A6A', 1, 'ticket', $result,'pictogramme_Danger-grave-et-imminent_32px.png');

		$result4 = createTicketCategory($langs->transnoentities('Quality'), '', 'FFDF77', 1, 'ticket', $result,'pictogramme_Qualité_32px.png');

		if ($result4 > 0) {

			createTicketCategory($langs->transnoentities('NonCompliance'), '', '', 1, 'ticket', $result4,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->transnoentities('EnhancementSuggestion'), '', '', 1, 'ticket', $result4,"pictogramme_suggestions-amelioration_64px.png");

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		$result5 = createTicketCategory($langs->transnoentities('Environment'), '', '5CD264', 1, 'ticket', $result,'pictogramme_environnement_32px.png');

		if ($result5 > 0) {

			createTicketCategory($langs->transnoentities('NonCompliance'), '', '', 1, 'ticket', $result5,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->transnoentities('Others'), '', '', 1, 'ticket', $result5,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, null, 'errors');
		}

		if ($result2 > 0 && $result3 > 0 && $result4 > 0) {
			dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 1, 'integer', 0, '', $conf->entity);
			setEventMessages($langs->transnoentities('CategoriesCreated'), array());
		}
	} else {
		setEventMessages($category->error, null, 'errors');
	}
}

if ($action == 'setMainCategory') {
	$category_id = GETPOST('mainCategory');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', $category_id, 'integer', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('MainCategorySet'), array());
}

if ($action == 'setParentCategoryLabel') {
	$label = GETPOST('parentCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL', $label, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('ParentCategoryLabelSet'), array());
}

if ($action == 'setChildCategoryLabel') {
	$label = GETPOST('childCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL', $label, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('ChildCategoryLabelSet'), array());
}

if ($action == 'setTicketSuccessMessage') {
	$successmessage = GETPOST('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', 'none');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', $successmessage, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('TicketSuccessMessageSet'), array());
}

if ($action == 'generateQRCode') {
	$data = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity;
	$size = '400x400';

	ob_clean();

	$QR = imagecreatefrompng('https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs='.$size.'&chl='.urlencode($data));

	$targetPath = $conf->digiriskdolibarr->multidir_output[$conf->entity?:1] . "/ticketqrcode/";
	if (! is_dir($targetPath)) {
		mkdir($targetPath, 0777, true);
	}

	$targetPath = $targetPath . "ticketQRCode.png";

	imagepng($QR,$targetPath);
}

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
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->transnoentities("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();

print dol_get_fiche_head($head, 'ticket', '', -1, "digiriskdolibarr@digiriskdolibarr");
print load_fiche_titre('<i class="fa fa-ticket"></i> ' . $langs->transnoentities("TicketManagement"), '', '');
print '<hr>';
print load_fiche_titre($langs->transnoentities("PublicInterface"), '', '');

print '<span class="opacitymedium">' . $langs->transnoentities("DigiriskTicketPublicAccess") . '</span> : <a class="wordbreak" href="' . dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity, 1) . '" target="_blank" >' . dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity, 2) . '</a>';
print dol_get_fiche_end();

$enabledisablehtml = $langs->transnoentities("TicketActivatePublicInterface") . ' ';
if (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=1' . $param . '">';
	$enabledisablehtml .= img_picto($langs->transnoentities("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=0' . $param . '">';
	$enabledisablehtml .= img_picto($langs->transnoentities("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" name="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" value="' . (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE) ? 0 : 1) . '">';

print '<br><br>';

if ( ! empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

	// Show logo for company
	print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketShowCompanyLogo") . '</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("TicketShowCompanyLogoHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	//Envoi d'emails automatiques
	print '<tr class="oddeven"><td>' . $langs->transnoentities("SendEmailOnTicketSubmit") . '</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_SEND_EMAIL_ON_TICKET_SUBMIT');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("SendEmailOnTicketSubmitHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	//Email to send ticket submitted
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setEmails">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("SendEmailTo") . '</td>';
	print '<td class="center">';
	print '<input name="emails" id="emails" value="' . $conf->global->DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO . '">';
	print '</td>';
	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->transnoentities('Save').'">';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("MultipleEmailsSeparator"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';

	print load_fiche_titre($langs->transnoentities("TicketSuccessMessageData"), '', '');

	print '<table class="noborder centpercent">';

	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setTicketSuccessMessage">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->transnoentities("Name").'</td>';
	print '<td>' . $langs->transnoentities("Description") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print "</tr>";

	$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $ticket);
	complete_substitutions_array($substitutionarray, $langs, $ticket);

	// Substitution array/string
	$helpforsubstitution = '';
	if (is_array($substitutionarray) && count($substitutionarray)) {
		$helpforsubstitution .= $langs->trans('AvailableVariables').' :<br>'."\n";
	}
	foreach ($substitutionarray as $key => $val) {
		$helpforsubstitution .= $key.' -> '.$langs->trans(dol_string_nohtmltag(dolGetFirstLineOfText($val))).'<br>';
	}

	// Ticket success message
	$successmessage = $langs->transnoentities($conf->global->DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE) ?: $langs->transnoentities('YouMustNotifyYourHierarchy');
	print '<tr class="oddeven"><td>'.$form->textwithpicto($langs->transnoentities("TicketSuccessMessage"), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody');
	print '</td><td>';
	$doleditor = new DolEditor('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', $successmessage, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
	$doleditor->Create();
	print '</td>';
	print '<td><input type="submit" class="button" name="save" value="' . $langs->transnoentities("Save") . '">';
	print '</td></tr>';
	print '</form>';
	print '</table>';

	print '</div>';

	// Project
	print load_fiche_titre($langs->transnoentities("LinkedProject"), '', '');

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="project_form">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<table class="noborder centpercent editmode">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Name") . '</td>';
	print '<td>' . $langs->transnoentities("SelectProject") . '</td>';
	print '<td>' . $langs->transnoentities("Action") . '</td>';
	print '</tr>';

	if ( ! empty($conf->projet->enabled)) {
		$langs->load("projects");
		print '<tr class="oddeven"><td><label for="TSProject">' . $langs->transnoentities("TSProject") . '</label></td><td>';
		$numprojet = $formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_TICKET_PROJECT, 'TSProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
		print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->transnoentities("AddProject") . '"></span></a>';
		print '<td><input type="submit" class="button" name="save" value="' . $langs->transnoentities("Save") . '">';
		print '</td></tr>';
	}

	print '</table>';
	print '</form>';

	print load_fiche_titre($langs->transnoentities("TicketCategories"), '', '', 0, 'TicketCategories');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

	//Categories generation

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateCategories">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateCategories") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 1</a></sup></td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? $langs->transnoentities('AlreadyGenerated') : $langs->transnoentities('NotCreated');
	print '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? '<a type="" class=" butActionRefused" value="">'.$langs->transnoentities('Create') .'</a>' : '<input type="submit" class="button" value="'.$langs->transnoentities('Create') .'">' ;
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("CategoriesGeneration"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set default main category

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setMainCategory">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("MainCategory") . '</td>';
	print '<td class="center">';
	print $formother->select_categories('ticket', $conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY,'mainCategory');
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->transnoentities('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("MainCategorySetting"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set parent category label

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setParentCategoryLabel">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("ParentCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 2</a></sup></td>';
	print '<td class="center">';
	print '<input name="parentCategoryLabel" value="'. $conf->global->DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL .'">';
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->transnoentities('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("ParentCategorySetting"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set child category label

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setChildCategoryLabel">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("ChildCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 3</a></sup></td>';
	print '<td class="center">';
	print '<input name="childCategoryLabel" value="'. $conf->global->DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL .'">';
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button" value="'. $langs->transnoentities('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("ChildCategorySetting"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';

	// Extrafields generation
	print load_fiche_titre($langs->transnoentities("TicketExtrafields"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateExtrafields">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';


	print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateExtrafields") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 4</a></sup></td>';
	print '<td class="center">';
	print dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) ? $langs->transnoentities('AlreadyGenerated') : $langs->transnoentities('NotCreated');
	print '</td>';
	print '<td class="center">';
	print dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) ? '<a type="" class=" butActionRefused" value="">'.$langs->transnoentities('Create') .'</a>' : '<input type="submit" class="button" value="'.$langs->transnoentities('Create') .'">' ;
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("ExtrafieldsGeneration"), 1, 'help');
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';

	// QR Code generation
	print load_fiche_titre($langs->transnoentities("QRCodeGeneration"), '', '');

	$QRCodeList = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity?:1] . "/ticketqrcode/");
	if (is_array($QRCodeList) && !empty($QRCodeList)) {
		$QRCode = array_shift($QRCodeList);
	} else {
		$QRCode = array();
	}

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateQRCode">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';


	print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateQRCode") . '</td>';
	print '<td class="center">';
	print array_key_exists('fullname', $QRCode) ? $langs->transnoentities('QRCodeAlreadyGenerated') : $langs->transnoentities('NotGenerated');
	print '</td>';
	print '<td class="center">';
	if (array_key_exists('fullname', $QRCode)) {
		$urladvanced = getAdvancedPreviewUrl('digiriskdolibarr', 'ticketqrcode/' . $QRCode['name']);
		print '<a class="clicked-photo-preview" href="'. $urladvanced .'">' . '<img width="200" src="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] .'"></a>';
		print '<a id="download" href="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] .'" download="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] .'"><i class="fas fa-download"></i></a>';
	} else {
		print '<input type="submit" class="button" value="'.$langs->transnoentities('Generate') .'">' ;
	}
	print '</td>';

	print '<td class="center minwidth800">';
	print $form->textwithpicto('', $langs->transnoentities("QRCodeGeneration"));
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';
	print '<span class="opacitymedium">' . $langs->transnoentities("TicketPublicInterfaceConfigDocumentation") . '</span> : <a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" >' . $langs->transnoentities('DigiriskDocumentation') . '</a>';
}

// End of page
llxFooter();
$db->close();
