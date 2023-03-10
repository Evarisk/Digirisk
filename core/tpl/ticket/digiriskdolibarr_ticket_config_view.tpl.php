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

$multiCompanyMention = (empty($multiEntityConfig) ? '' : 'MULTICOMPANY_');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities("Parameters") . '</td>';
print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
print '</tr>';

// Show logo for company
print '<tr class="oddeven"><td>' . $langs->transnoentities('TicketShowCompanyLogo') . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_SHOW_COMPANY_LOGO');
print '</td>';
print '<td class="center">';
print '';
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketShowCompanyLogoHelp"));
print '</td>';
print '</tr>';

// GP/UT Hide ref
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketDigiriskElementHideRef") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_DIGIRISKELEMENT_HIDE_REF');
print '</td>';
print '<td class="center">';
print '';
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketDigiriskElementHideRefHelp"));
print '</td>';
print '</tr>';

if ($conf->multicompany->enabled) {
	//Page de sélection de l'entité
	print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowSelectorOnTicketPublicInterface") . '</td>';
	print '<td class="center">';
	if (empty($conf->global->DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE)) {
		// Button off, click to enable
		print '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setMultiEntitySelector&token=' . newToken() . '&value=1">';
		print img_picto($langs->transnoentities("Disabled"), 'switch_off');
	} else {
		// Button on, click to disable
		print '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setMultiEntitySelector&token=' . newToken() . '&value=0">';
		print img_picto($langs->transnoentities("Activated"), 'switch_on');
	}
	print '</a>';
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("ShowSelectorOnTicketPublicInterfaceHelp"));
	print '</td>';
	print '</tr>';
}

//Envoi d'emails automatiques
print '<tr class="oddeven"><td>' . $langs->transnoentities("SendEmailOnTicketSubmit") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'SEND_EMAIL_ON_TICKET_SUBMIT');
print '</td>';
print '<td class="center">';
print '';
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("SendEmailOnTicketSubmitHelp"));
print '</td>';
print '</tr>';

//Email to send ticket submitted
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setEmails">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

$submittedSendMailToConf = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_SUBMITTED_SEND_MAIL_TO';
print '<tr class="oddeven"><td>' . $langs->transnoentities("SendEmailTo") . '</td>';
print '<td class="center">';
print '<input name="emails" id="emails" value="' . $conf->global->$submittedSendMailToConf . '">';
print '</td>';
print '<td class="center">';
print '<input type="submit" class="button" value="' . $langs->transnoentities('Save') . '">';
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("MultipleEmailsSeparator"));
print '</td>';
print '</tr>';
print '</form>';

print load_fiche_titre($langs->transnoentities("PublicInterfaceConfiguration"), '', '');
print '<span class="opacitymedium">' . $langs->transnoentities("PublicInterfaceConfigurationInfo") . '</span>';
print '</br></br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities("Parameters") . '</td>';
print '<td class="center">' . $langs->transnoentities("Visible") . '</td>';
print '<td class="center">' . $langs->transnoentities("Required") . '</td>';
print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
print '</tr>';

// Photo visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketPhotoVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_PHOTO_VISIBLE');
print '</td>';
print '<td class="center">';
print '';
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketPhotoVisibleHelp"));
print '</td>';
print '</tr>';

// GP/UT Visible and Required
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketDigiriskElementVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_DIGIRISKELEMENT_VISIBLE');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_DIGIRISKELEMENT_REQUIRED');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketDigiriskElementVisibleHelp"));
print '</td>';
print '</tr>';

// Email Visible and Required
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketEmailVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_EMAIL_VISIBLE');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_EMAIL_REQUIRED');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketEmailVisibleHelp"));
print '</td>';
print '</tr>';

// Firstname Visible and Required
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketFirstNameVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_FIRSTNAME_VISIBLE');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_FIRSTNAME_REQUIRED');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketFirstNameVisibleHelp"));
print '</td>';
print '</tr>';

// Lastname Visible and Required
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketLastNameVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_LASTNAME_VISIBLE');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_LASTNAME_REQUIRED');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketLastNameVisibleHelp"));
print '</td>';
print '</tr>';

// Phone Visible and Required
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketPhoneVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_PHONE_VISIBLE');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_PHONE_REQUIRED');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketPhoneVisibleHelp"));
print '</td>';
print '</tr>';

// Location Visible and Required
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketLocationVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_LOCATION_VISIBLE');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_LOCATION_REQUIRED');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketLocationVisibleHelp"));
print '</td>';
print '</tr>';

// Date Visible and Required
print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketDateVisible") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_DATE_VISIBLE');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_DATE_REQUIRED');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("TicketDateVisibleHelp"));
print '</td>';
print '</tr>';

print '</table>';

print load_fiche_titre($langs->transnoentities("TicketSuccessMessageData"), '', '');

print '<table class="noborder centpercent">';

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setTicketSuccessMessage">';

print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities("Name") . '</td>';
print '<td>' . $langs->transnoentities("Description") . '</td>';
print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
print "</tr>";

$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $ticket);
complete_substitutions_array($substitutionarray, $langs, $ticket);

// Substitution array/string
$helpforsubstitution = '';
if (is_array($substitutionarray) && count($substitutionarray)) {
	$helpforsubstitution .= $langs->trans('AvailableVariables') . ' :<br>' . "\n";
}
foreach ($substitutionarray as $key => $val) {
	$helpforsubstitution .= $key . ' -> ' . $langs->trans(dol_string_nohtmltag(dolGetFirstLineOfText($val))) . '<br>';
}

// Ticket success message
$successMessageConf = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_SUCCESS_MESSAGE';
$successmessage = $langs->transnoentities($conf->global->$successMessageConf) ?: $langs->transnoentities('YouMustNotifyYourHierarchy');
print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->transnoentities("TicketSuccessMessage"), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody');
print '</td><td>';
$doleditor = new DolEditor('DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_SUCCESS_MESSAGE', $successmessage, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->transnoentities("Save") . '">';
print '</td></tr>';
print '</form>';
print '</table>';

print '</div>';

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

print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateTicketCategories") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 1</a></sup></td>';
print '<td class="center">';
print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? $langs->transnoentities('AlreadyGenerated') : $langs->transnoentities('NotCreated');
print '</td>';
print '<td class="center">';
print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? '<a type="" class=" butActionRefused" value="">' . $langs->transnoentities('Create') . '</a>' : '<input type="submit" class="button" value="' . $langs->transnoentities('Create') . '">';
print '</td>';

print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("CategoriesGeneration"));
print '</td>';
print '</tr>';
print '</form>';

//Set default main category
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMainCategory">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

$mainCategoryConf = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_MAIN_CATEGORY';
print '<tr class="oddeven"><td>' . $langs->transnoentities("MainCategory") . '</td>';
print '<td class="center">';
print $formother->select_categories('ticket', $conf->global->$mainCategoryConf, 'mainCategory');
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button" value="' . $langs->transnoentities('Save') . '">';
print '</td>';

print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("MainCategorySetting"));
print '</td>';
print '</tr>';
print '</form>';

//Set parent category label
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setParentCategoryLabel">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

$parentCategoryConf = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_PARENT_CATEGORY_LABEL';
print '<tr class="oddeven"><td>' . $langs->transnoentities("ParentCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 2</a></sup></td>';
print '<td class="center">';
print '<input name="parentCategoryLabel" value="' . $conf->global->$parentCategoryConf . '">';
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button" value="' . $langs->transnoentities('Save') . '">';
print '</td>';

print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ParentCategorySetting"));
print '</td>';
print '</tr>';
print '</form>';

//Set child category label

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setChildCategoryLabel">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

$childCategoryConf = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_CHILD_CATEGORY_LABEL';
print '<tr class="oddeven"><td>' . $langs->transnoentities("ChildCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 3</a></sup></td>';
print '<td class="center">';
print '<input name="childCategoryLabel" value="' . $conf->global->$childCategoryConf . '">';
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button" value="' . $langs->transnoentities('Save') . '">';
print '</td>';

print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ChildCategorySetting"));
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
print dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) ? '<a type="" class=" butActionRefused" value="">' . $langs->transnoentities('Create') . '</a>' : '<input type="submit" class="button" value="' . $langs->transnoentities('Create') . '">';
print '</td>';

print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ExtrafieldsGeneration"));
print '</td>';
print '</tr>';
print '</form>';

print '</table>';
print '</div>';

if (empty($multiEntityConfig)) {
// Entity QR Code generation
	print load_fiche_titre($langs->transnoentities("CompanyQRCodeGeneration"), '', '');

	$qrCodePath = $conf->digiriskdolibarr->multidir_output[$conf->entity ?: 1] . "/ticketqrcode/";
	$QRCodeList = dol_dir_list($qrCodePath);
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

	$targetPath = $qrCodePath;
	$urlToEncode = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity;

	print '<input hidden name="targetPath" value="' . $targetPath . '">';
	print '<input hidden name="urlToEncode" value="' . $urlToEncode . '">';

	print '<td class="center">';
	print array_key_exists('fullname', $QRCode) ? $langs->transnoentities('QRCodeAlreadyGenerated') : $langs->transnoentities('NotGenerated');
	print '</td>';
	print '<td class="center">';
	if (array_key_exists('fullname', $QRCode)) {
		$urladvanced = getAdvancedPreviewUrl('digiriskdolibarr', 'ticketqrcode/' . $QRCode['name']);
		print '<a class="clicked-photo-preview" href="' . $urladvanced . '">' . '<img width="200" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] . '" alt="' . $langs->transnoentities("TicketPublicInterfaceQRCode") . '"></a>';
		print '<a id="download" href="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] . '" download="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] . '"><i class="fas fa-download"></i></a>';
	} else {
		print '<input type="submit" class="button" value="' . $langs->transnoentities('Generate') . '">';
	}
	print '</td>';

	print '<td class="center minwidth800">';
	print $form->textwithpicto('', $langs->transnoentities("QRCodeGeneration"));
	print '</td>';
	print '</tr>';
	print '</form>';
}

if ($conf->multicompany->enabled) {
	// Multi Entity QR Code generation
	print load_fiche_titre($langs->transnoentities("MultiCompanyQRCodeGeneration"), '', '');

	$qrCodePath = DOL_DATA_ROOT . "/digiriskdolibarr/multicompany/ticketqrcode/";
	$QRCodeList = dol_dir_list($qrCodePath);

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

	$targetPath  = $qrCodePath;
	$urlToEncode = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php';

	print '<input hidden name="targetPath" value="' . $targetPath . '">';
	print '<input hidden name="urlToEncode" value="' . $urlToEncode . '">';

	print '<td class="center">';
	print array_key_exists('fullname', $QRCode) ? $langs->transnoentities('QRCodeAlreadyGenerated') : $langs->transnoentities('NotGenerated');
	print '</td>';
	print '<td class="center">';
	if (array_key_exists('fullname', $QRCode)) {
		$urladvanced = getAdvancedPreviewUrl('digiriskdolibarr', 'ticketqrcode/' . $QRCode['name']);
		print '<a class="clicked-photo-preview" href="' . $urladvanced . '">' . '<img width="200" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=1&file=' . 'multicompany/ticketqrcode/' . $QRCode['name'] . '" alt="' . $langs->transnoentities("MultiEntityTicketPublicInterfaceQRCode") . '"></a>';
		print '<a id="download" href="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=1&file=' . 'multicompany/ticketqrcode/' . $QRCode['name'] . '" download="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr' . '&file=' . 'multicompany/ticketqrcode/' . $QRCode['name'] . '"><i class="fas fa-download"></i></a>';
	} else {
		print '<input type="submit" class="button" value="' . $langs->transnoentities('Generate') . '">';
	}
	print '</td>';

	print '<td class="center minwidth800">';
	print $form->textwithpicto('', $langs->transnoentities("QRCodeGeneration"));
	print '</td>';
	print '</tr>';
	print '</form>';
}
