<?php
/* Copyright (C) 2013-2016    Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016         Christophe Battarel <christophe@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \file       htdocs/public/ticket/create_ticket.php
 *       \ingroup    ticket
 *       \brief      Display public form to add new ticket
 */

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOLOGIN'))        define("NOLOGIN", 1); // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

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
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";
if (!$res) die("Include of main fails");require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/ticket/mod_ticket_simple.php';
require_once '../../lib/digiriskdolibarr_function.lib.php';

global $langs;
// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'mails', 'ticket', 'digiriskdolibarr@digiriskdolibarr'));

// Get parameters
$id = GETPOST('id', 'int');
$msg_id = GETPOST('msg_id', 'int');

$action = GETPOST('action', 'aZ09');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewticketcard', 'globalcard'));

$object = new Ticket($db);
$extrafields = new ExtraFields($db);
$category = new Categorie($db);
$modTicket = new mod_ticket_simple($db);

$extrafields->fetch_name_optionals_label($object->table_element);

$upload_dir         = $conf->categorie->multidir_output[isset($object->entity) ? $object->entity : 1];

/*
 * Actions
 */
$parameters = array(
	'id' => $id,
);
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($action == 'add') {

	$register = GETPOST('register');
	$pertinence = GETPOST('pertinence');
	$service = GETPOST('service');
	$firstname = GETPOST('firstname');
	$lastname = GETPOST('lastname');
	$message = GETPOST('message');

	$object->ref = $modTicket->getNextValue($thirdparty,$object);

	$object->message = $firstname . ' ' . $lastname . ' ' . $message;

	$result = $object->create($user);

	if ($result > 0) {

		$registerCat = $category;
		$registerCat->fetch($register);
		$registerCat->add_type($object, Categorie::TYPE_TICKET);

		$pertinenceCat = $category;
		$pertinenceCat->fetch($pertinence);
		$pertinenceCat->add_type($object, Categorie::TYPE_TICKET);

		$serviceCat = $category;
		$serviceCat->fetch($service);
		$serviceCat->add_type($object, Categorie::TYPE_TICKET);

		// Creation OK
		$urltogo = $_SERVER['PHP_SELF'];
		setEventMessages($langs->trans("TicketSend", ''), null);
		header("Location: ".$urltogo);
		exit;
	}

}
// Add file in email form
if (empty($reshook) && GETPOST('addfile', 'alpha') && !GETPOST('add', 'alpha')) {
	////$res = $object->fetch('','',GETPOST('track_id'));
	////if($res > 0)
	////{
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp directory TODO Use a dedicated directory for temp mails files
	$vardir = $conf->ticket->dir_output;
	$upload_dir_tmp = $vardir.'/temp/'.session_id();
	if (!dol_is_dir($upload_dir_tmp)) {
		dol_mkdir($upload_dir_tmp);
	}

	dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile', '', null, '', 0);
	$action = 'create_ticket';
	////}
}

// Remove file
if (empty($reshook) && GETPOST('removedfile', 'alpha') && !GETPOST('add', 'alpha')) {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp directory
	$vardir = $conf->ticket->dir_output.'/';
	$upload_dir_tmp = $vardir.'/temp/'.session_id();

	// TODO Delete only files that was uploaded from email form
	dol_remove_file_process($_POST['removedfile'], 0, 0);
	$action = 'create_ticket';
}

if (empty($reshook) && $action == 'create_ticket' && GETPOST('add', 'alpha')) {
	$error = 0;
	$origin_email = GETPOST('email', 'alpha');
	if (empty($origin_email)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
		$action = '';
	} else {
		// Search company saved with email
		$searched_companies = $object->searchSocidByEmail($origin_email, '0');

		// Chercher un contact existant avec cette adresse email
		// Le premier contact trouvé est utilisé pour déterminer le contact suivi
		$contacts = $object->searchContactByEmail($origin_email);

		// Option to require email exists to create ticket
		if (!empty($conf->global->TICKET_EMAIL_MUST_EXISTS) && !$contacts[0]->socid) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorEmailMustExistToCreateTicket"));
			$action = '';
		}
	}

	if (!GETPOST("subject", "restricthtml")) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject")));
		$action = '';
	} elseif (!GETPOST("message", "restricthtml")) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("message")));
		$action = '';
	}

	// Check email address
	if (!isValidEmail($origin_email)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorBadEmailAddress", $langs->transnoentities("email")));
		$action = '';
	}

	if (!$error) {
		$object->db->begin();

		$object->track_id = generate_random_id(16);

		$object->subject = GETPOST("subject", "restricthtml");
		$object->message = GETPOST("message", "restricthtml");
		$object->origin_email = $origin_email;

		$object->type_code = GETPOST("type_code", 'aZ09');
		$object->category_code = GETPOST("category_code", 'aZ09');
		$object->severity_code = GETPOST("severity_code", 'aZ09');
		if (is_array($searched_companies)) {
			$object->fk_soc = $searched_companies[0]->id;
		}

		if (is_array($contacts) and count($contacts) > 0) {
			$object->fk_soc = $contacts[0]->socid;
			$usertoassign = $contacts[0]->id;
		}

		$ret = $extrafields->setOptionalsFromPost(null, $object);

		// Generate new ref
		$object->ref = $object->getDefaultRef();
		if (!is_object($user)) {
			$user = new User($db);
		}

		$object->context['disableticketemail'] = 1; // Disable emails sent by ticket trigger when creation is done from this page, emails are already sent later

		$id = $object->create($user);
		if ($id <= 0) {
			$error++;
			$errors = ($object->error ? array($object->error) : $object->errors);
			array_push($object->errors, $object->error ? array($object->error) : $object->errors);
			$action = 'create_ticket';
		}

		if (!$error && $id > 0) {
			if ($usertoassign > 0) {
				$object->add_contact($usertoassign, "SUPPORTCLI", 'external', 0);
			}
		}

		if (!$error) {
			$object->db->commit();
			$action = "infos_success";
		} else {
			$object->db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create_ticket';
		}

		if (!$error) {
			$res = $object->fetch($id);
			if ($res) {
				// Create form object
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				$formmail = new FormMail($db);

				// Init to avoid errors
				$filepath = array();
				$filename = array();
				$mimetype = array();

				$attachedfiles = $formmail->get_attached_files();
				$filepath = $attachedfiles['paths'];
				$filename = $attachedfiles['names'];
				$mimetype = $attachedfiles['mimes'];

				// Send email to customer

				$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketNewEmailSubject', $object->ref, $object->track_id);
				$message  = ($conf->global->TICKET_MESSAGE_MAIL_NEW ? $conf->global->TICKET_MESSAGE_MAIL_NEW : $langs->transnoentities('TicketNewEmailBody')).'<br><br>';
				$message .= $langs->transnoentities('TicketNewEmailBodyInfosTicket').'<br>';

				$url_public_ticket = ($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE.'/' : dol_buildpath('/public/ticket/view.php', 2)).'?track_id='.$object->track_id;
				$infos_new_ticket = $langs->transnoentities('TicketNewEmailBodyInfosTrackId', '<a href="'.$url_public_ticket.'" rel="nofollow noopener">'.$object->track_id.'</a>').'<br>';
				$infos_new_ticket .= $langs->transnoentities('TicketNewEmailBodyInfosTrackUrl').'<br><br>';

				$message .= $infos_new_ticket;
				$message .= $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE ? $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE : $langs->transnoentities('TicketMessageMailSignatureText');

				$sendto = GETPOST('email', 'alpha');

				$from = $conf->global->MAIN_INFO_SOCIETE_NOM.'<'.$conf->global->TICKET_NOTIFICATION_EMAIL_FROM.'>';
				$replyto = $from;
				$sendtocc = '';
				$deliveryreceipt = 0;

				if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
					$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
				}
				include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1, '', '', 'tic'.$object->id, '', 'ticket');
				if ($mailfile->error || $mailfile->errors) {
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
				} else {
					$result = $mailfile->sendfile();
				}
				if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
				}

				// Send email to TICKET_NOTIFICATION_EMAIL_TO
				$sendto = $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
				if ($sendto) {
					$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketNewEmailSubjectAdmin', $object->ref, $object->track_id);
					$message_admin = $langs->transnoentities('TicketNewEmailBodyAdmin', $object->track_id).'<br><br>';
					$message_admin .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
					$message_admin .= '<li>'.$langs->trans('Type').' : '.$object->type_label.'</li>';
					$message_admin .= '<li>'.$langs->trans('Category').' : '.$object->category_label.'</li>';
					$message_admin .= '<li>'.$langs->trans('Severity').' : '.$object->severity_label.'</li>';
					$message_admin .= '<li>'.$langs->trans('From').' : '.$object->origin_email.'</li>';
					// Extrafields
					$extrafields->fetch_name_optionals_label($object->table_element);
					if (is_array($object->array_options) && count($object->array_options) > 0) {
						foreach ($object->array_options as $key => $value) {
							$key = substr($key, 8); // remove "options_"
							$message_admin .= '<li>'.$langs->trans($extrafields->attributes[$object->element]['label'][$key]).' : '.$extrafields->showOutputField($key, $value).'</li>';
						}
					}
					$message_admin .= '</ul>';

					$message_admin .= '</ul>';
					$message_admin .= '<p>'.$langs->trans('Message').' : <br>'.$object->message.'</p>';
					$message_admin .= '<p><a href="'.dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id.'" rel="nofollow noopener">'.$langs->trans('SeeThisTicketIntomanagementInterface').'</a></p>';

					$from = $conf->global->MAIN_INFO_SOCIETE_NOM.' <'.$conf->global->TICKET_NOTIFICATION_EMAIL_FROM.'>';
					$replyto = $from;

					if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
						$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
						$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
					}
					include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
					$mailfile = new CMailFile($subject, $sendto, $from, $message_admin, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1, '', '', 'tic'.$object->id, '', 'ticket');
					if ($mailfile->error || $mailfile->errors) {
						setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					} else {
						$result = $mailfile->sendfile();
					}
					if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
						$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
					}
				}
			}

			// Copy files into ticket directory
			$destdir = $conf->ticket->dir_output.'/'.$object->ref;
			if (!dol_is_dir($destdir)) {
				dol_mkdir($destdir);
			}
			foreach ($filename as $i => $val) {
				dol_move($filepath[$i], $destdir.'/'.$filename[$i], 0, 1);
				$formmail->remove_attached_files($i);
			}

			//setEventMessages($langs->trans('YourTicketSuccessfullySaved'), null, 'mesgs');

			// Make a redirect to avoid to have ticket submitted twice if we make back
			$messagetoshow = $langs->trans('MesgInfosPublicTicketCreatedWithTrackId', '{s1}', '{s2}');
			$messagetoshow = str_replace(array('{s1}', '{s2}'), array('<strong>'.$object->track_id.'</strong>', '<strong>'.$object->ref.'</strong>'), $messagetoshow);
			setEventMessages($messagetoshow, null, 'warnings');
			setEventMessages($langs->trans('PleaseRememberThisId'), null, 'warnings');
			header("Location: index.php");
			exit;
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}



/*
 * View
 */

$form = new Form($db);
$formticket = new FormTicket($db);

//if (!$conf->global->TICKET_ENABLE_PUBLIC_INTERFACE) {
//	print '<div class="error">'.$langs->trans('TicketPublicInterfaceForbidden').'</div>';
//	$db->close();
//	exit();
//}

$arrayofjs =  array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$arrayofcss = array('/opensurvey/css/style.css', '/ticket/css/styles.css.php');

llxHeaderTicket($langs->trans("CreateTicket"), "", 0, 0, $arrayofjs, $arrayofcss);


print '<div class="ticketpublicarea">';

print load_fiche_titre($title_edit, '', "digiriskdolibarr32px@digiriskdolibarr");

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add">';
print '<input type="hidden" id="register" name="register" value="'.GETPOST('register').'">';
print '<input type="hidden" id="pertinence" name="pertinence" value="'.GETPOST('pertinence').'">';
print '<input type="hidden" id="service" name="service" value="'.GETPOST('service').'">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

dol_fiche_head(array(), '0', '', 1);

print '<div class="img-fields-container">';
print '<table class="border centpercent tableforimgfields">'."\n";
//// Common attributes
//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';
//
//// Other attributes
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

print '<tr><td>'.$langs->trans("Register").'</td><td>';
// @ todo afficher la categorie qui a été enregistrée comme la catégorie racine du champ registre (dans une conf $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORY_REGISTRE_ID)
$registerCategory = $category->rechercher(0,'Registre','ticket', true);
$registerChildren = $registerCategory[0]->get_filles();
foreach ($registerChildren as $register) {
	if ($register->id == GETPOST('register')) {
		print '<td class="ticket-register" id="'.$register->id.'" style="border: solid">';
	} else {
		print '<td class="ticket-register" id="'.$register->id.'">';
	}
//	print '<a href="'.$_SERVER['REQUEST_URI'].'?register='.$element->id.'">';
	show_category_image($register, $upload_dir);
	print '</td>';
}

print '</td></tr>';

print '<tr><td>'.$langs->trans("Pertinence").'</td><td>';
if (GETPOST('register')) {
	$selectedRegister = $category;
	$selectedRegister->fetch(GETPOST('register'));
	$selectedRegisterChildren = $selectedRegister->get_filles();

	foreach ($selectedRegisterChildren as $pertinence) {
		if ($pertinence->id == GETPOST('pertinence')) {
			print '<td class="ticket-pertinence" id="'.$pertinence->id.'" style="border: solid">';
		} else {
			print '<td class="ticket-pertinence" id="'.$pertinence->id.'">';
		}
//		print '<a href="'.$_SERVER['REQUEST_URI'].'&pertinence='.$pertinence->id.'">';
		show_category_image($pertinence, $upload_dir);
		print '</td>';
	}

} else {
	print '<td>';
	print '</td>';
}

print '</td></tr>';

print '<tr><td>'.$langs->trans("Service").'</td><td>';
$serviceCategory = $category->rechercher(0,'Service','ticket', true);
$serviceChildren = $serviceCategory[0]->get_filles();

foreach ($serviceChildren as $service) {
	if ($service->id == GETPOST('service')) {
		print '<td class="ticket-service" id="'.$service->id.'" style="border: solid">';
	} else {
		print '<td class="ticket-service" id="'.$service->id.'">';
	}
//	print '<a href="'.$_SERVER['REQUEST_URI'].'&service='.$service->id.'">';
	show_category_image($service, $upload_dir);
	print '</td>';
}
print '</td></tr>';
print '</table>';
print '<br>';
print '</div>';
print '<table class="border centpercent tableforinputfields">'."\n";

print '<tr><td>'.$langs->trans("Firstname").'</td><td>';
print '<input name="firstname" "type=text value="">';
print '</td></tr>';

print '<tr><td>'.$langs->trans("Lastname").'</td><td>';
print '<input name="lastname" type=text value="">';
print '</td></tr>';

print '<tr><td>'.$langs->trans("Message").'</td><td>';

$doleditor = new DolEditor('message', $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL ? $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL : '', '', 90, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();

print '</td></tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Send").'">';
//print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"  onClick="javascript:history.go(-1)">';
print '</div>';

print '</form>';
print '</div>';

// End of page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

$db->close();
