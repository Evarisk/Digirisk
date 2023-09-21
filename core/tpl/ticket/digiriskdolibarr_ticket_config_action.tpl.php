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

$entity              = (empty($multiEntityConfig) ? $conf->entity : 0);
$multiCompanyMention = (empty($multiEntityConfig) ? '' : 'MULTICOMPANY_');

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$TSProject = GETPOST('TSProject', 'none');
	$TSProject = explode('_', $TSProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_TICKET_PROJECT", $TSProject[0], 'integer', 0, '', $entity);
	setEventMessages($langs->transnoentities('TicketProjectUpdated'), array());

	if ($action != 'updateedit') {
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'setPublicInterface') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_ENABLE_PUBLIC_INTERFACE', 1, 'integer', 0, '', $entity);
		$transKey = 'TicketPublicInterfaceEnabled';
	} else {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_ENABLE_PUBLIC_INTERFACE', 0, 'integer', 0, '', $entity);
		$transKey = 'TicketPublicInterfaceDisabled';
	}
	setEventMessages($langs->transnoentities($transKey), array());
}

if ($action == 'setMulticompanyConfig') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_USE_MULTICOMPANY_CONFIG', 1, 'integer', 0, '', $conf->entity);
		$transKey = 'TicketPublicInterfaceDisabled';
	} else {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_USE_MULTICOMPANY_CONFIG', 0, 'integer', 0, '', $conf->entity);
		$transKey = 'TicketPublicInterfaceEnabled';
	}
	setEventMessages($langs->transnoentities($transKey), array());
}

if ($action == 'setMultiEntitySelector') {
	if (GETPOST('value')) dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', 1, 'integer', 0, '', 0);
	else dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', 0, 'integer', 0, '', 0);
//	setEventMessages($langs->transnoentities('TicketPublicInterfaceEnabled'), array());
}


if ($action == 'setEmails') {
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_SUBMITTED_SEND_MAIL_TO', GETPOST('emails'), 'integer', 0, '', $entity);
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
		setEventMessages($extra_fields->error, array(), 'errors');
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
			setEventMessages($category->error, array(), 'errors');
		}

		$result3 = createTicketCategory($langs->transnoentities('SST'), '', '7594F6', 1, 'ticket', $result,'pictogramme_Sante-et-securite_32px.png');

		if ($result3 > 0) {

			createTicketCategory($langs->transnoentities('AnticipatedLeave'), '', '', 1, 'ticket', $result3,'pictogramme_depart-anticipe_64px.png');
			createTicketCategory($langs->transnoentities('HumanProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-humain_64px.png');
			createTicketCategory($langs->transnoentities('MaterialProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-materiel_64px.png');
			createTicketCategory($langs->transnoentities('Others'), '', '', 1, 'ticket', $result3,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, array(), 'errors');
		}

		createTicketCategory($langs->transnoentities('DGI'), '', 'E96A6A', 1, 'ticket', $result,'pictogramme_Danger-grave-et-imminent_32px.png');

		$result4 = createTicketCategory($langs->transnoentities('Quality'), '', 'FFDF77', 1, 'ticket', $result,'pictogramme_Qualité_32px.png');

		if ($result4 > 0) {

			createTicketCategory($langs->transnoentities('NonCompliance'), '', '', 1, 'ticket', $result4,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->transnoentities('EnhancementSuggestion'), '', '', 1, 'ticket', $result4,"pictogramme_suggestions-amelioration_64px.png");

		} else {
			setEventMessages($category->error, array(), 'errors');
		}

		$result5 = createTicketCategory($langs->transnoentities('Environment'), '', '5CD264', 1, 'ticket', $result,'pictogramme_environnement_32px.png');

		if ($result5 > 0) {

			createTicketCategory($langs->transnoentities('NonCompliance'), '', '', 1, 'ticket', $result5,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->transnoentities('Others'), '', '', 1, 'ticket', $result5,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, array(), 'errors');
		}

		if ($result2 > 0 && $result3 > 0 && $result4 > 0) {
			dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 1, 'integer', 0, '', $conf->entity);
			setEventMessages($langs->transnoentities('CategoriesCreated'), array());
		}
	} else {
		setEventMessages($category->error, array(), 'errors');
	}
}

if ($action == 'setMainCategory') {
	$category_id = GETPOST('mainCategory');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', $category_id, 'integer', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('MainCategorySet'), array());
}

if ($action == 'setParentCategoryLabel') {
	$label = GETPOST('parentCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_PARENT_CATEGORY_LABEL', $label, 'chaine', 0, '', $entity);
	setEventMessages($langs->transnoentities('ParentCategoryLabelSet'), array());
}

if ($action == 'setChildCategoryLabel') {
	$label = GETPOST('childCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_CHILD_CATEGORY_LABEL', $label, 'chaine', 0, '', $entity);
	setEventMessages($langs->transnoentities('ChildCategoryLabelSet'), array());
}

if ($action == 'setTicketSuccessMessage') {

	$successmessage = GETPOST('DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_SUCCESS_MESSAGE', 'none');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_SUCCESS_MESSAGE', $successmessage, 'chaine', 0, '', $entity);
	setEventMessages($langs->transnoentities('TicketSuccessMessageSet'), array());
}

if ($action == 'generateQRCode') {
	$urlToEncode = GETPOST('urlToEncode');
	$targetPath = GETPOST('targetPath');
	$size = '400x400';

	ob_clean();

	$QR = imagecreatefrompng('https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs='.$size.'&chl='.urlencode($urlToEncode));

	if (! is_dir($targetPath)) {
		mkdir($targetPath, 0777, true);
	}

	$targetPath = $targetPath . "ticketQRCode.png";

	imagepng($QR,$targetPath);
	setEventMessages($langs->transnoentities('QRCodeGenerated'), array());
}
