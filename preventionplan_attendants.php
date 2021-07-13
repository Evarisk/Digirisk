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
 *   	\file       preventionplan_attendants.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view preventionplan_signature
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
if (!$res) die("Include of main fails");

require_once __DIR__ . '/class/digiriskresources.class.php';
require_once __DIR__ . '/class/preventionplan.class.php';
require_once __DIR__ . '/lib/digiriskdolibarr_preventionplan.lib.php';
require_once __DIR__ . '/lib/digiriskdolibarr_function.lib.php';

global $db, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$action              = GETPOST('action', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'preventionplansignature'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object            = new PreventionPlan($db);
$signatory         = new PreventionPlanSignature($db);
$digiriskresources = new DigiriskResources($db);
$usertmp           = new User($db);
$contact           = new Contact($db);

$object->fetch($id);

$hookmanager->initHooks(array('preventionplansignature', 'globalcard')); // Note that conf->hooks_modules contains array

$permissiontoread   = $user->rights->digiriskdolibarr->preventionplandocument->read;

if (!$permissiontoread) accessforbidden();

/*
/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Action to add record
if ($action == 'addSignature') {
	$signatoryID = GETPOST('signatoryID');
	$signature = GETPOST('signature');
	$request_body = file_get_contents('php://input');

	$signatory->fetch($signatoryID);
	$signatory->signature = $request_body;
	$signatory->signature_date = dol_now();

	if (!$error) {
		$result = $signatory->update($user, false);
		if ($result > 0) {
			$signatory->setSigned($user, false);
			// Creation signature OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		}
		else
		{
			// Creation signature KO
			if (!empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
			else  setEventMessages($signatory->error, null, 'errors');
		}
	}
}

// Action to set status STATUS_ABSENT
if ($action == 'setAbsent') {
	$signatoryID = GETPOST('signatoryID');

	$signatory->fetch($signatoryID);

	if (!$error) {
		$result = $signatory->setAbsent($user, false);
		if ($result > 0) {
			// Creation signature OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		}
		else
		{
			// Creation signature KO
			if (!empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
			else  setEventMessages($signatory->error, null, 'errors');
		}
	}
}

// Action to send Email
if ($action == 'send') {
	$signatoryID = GETPOST('signatoryID');

	$signatory->fetch($signatoryID);

	if (!$error) {
		$result = $signatory->setPendingSignature($user, false);
		if ($result > 0) {
			// Actions to send emails
			$langs->load('mails');

			$triggersendname = 'DIGIRISkDOLIBARR_SIGNATURE_SENTBYMAIL';
			$trackid = 'PreventionPlanSignature'.$element->id;
			$url = dol_buildpath('/custom/digiriskdolibarr/public/signature/add_signature.php', 3);
			$subject = ''; $actionmsg = ''; $actionmsg2 = '';
			$sendto = $signatory->email;
			$sendtoid = array();

			if (dol_strlen($sendto))
			{
				require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

				//$from = dol_string_nospecial($conf->global->MAIN_INFO_SOCIETE_NOM, ' ', array(",")).' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
				$from = $conf->global->DIGIRISKDOLIBARR_SIGNATURE_EMAIL;
				$message = $langs->trans('SignatureEmailMessage');
				$message .= $url;
				$subject = $langs->trans('SignatureEmailSubject');

				$actionmsg2 = $langs->transnoentities('MailSentBy').' '.CMailFile::getValidAddress($from, 4, 0, 1).' '.$langs->transnoentities('at').' '.CMailFile::getValidAddress($sendto, 4, 0, 1);
				if ($message)
				{
					$actionmsg = $langs->transnoentities('MailFrom').': '.dol_escape_htmltag($from);
					$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($sendto));
					$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic').": ".$subject);
					$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody').":");
					$actionmsg = dol_concatdesc($actionmsg, $message);
				}

				// Create form object
				// Send mail (substitutionarray must be done just before this)
				if (empty($sendcontext)) $sendcontext = 'mail';
				$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', $sendcontext);

				if ($mailfile->error)
				{
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					$action = 'presend';
				} else {
					$result = $mailfile->sendfile();
					if ($result)
					{
						// Initialisation of datas of object to call trigger
						if (is_object($object))
						{
							if (empty($actiontypecode)) $actiontypecode = 'AC_OTH_AUTO'; // Event insert into agenda automatically

							$object->socid          = $sendtosocid; // To link to a company
							$object->sendtoid       = $sendtoid; // To link to contact-addresses. This is an array.
							$object->actiontypecode = $actiontypecode; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
							$object->actionmsg      = $actionmsg; // Long text (@todo Replace this with $message, we already have details of email in dedicated properties)
							$object->actionmsg2     = $actionmsg2; // Short text ($langs->transnoentities('MailSentBy')...);
							$object->trackid        = $trackid;
							$object->fk_element     = $object->id;
							$object->elementtype    = $object->element;
							$object->email_from     = $from;
							$object->email_subject  = $subject;
							$object->email_to       = $sendto;
							$object->email_subject  = $subject;
							$object->email_msgid    = $mailfile->msgid;

							// Call of triggers (you should have set $triggersendname to execute trigger)
							if (!empty($triggersendname))
							{
								// Call trigger
								$result = $object->call_trigger($triggersendname, $user);
								if ($result < 0) $error++;
								// End call triggers
								if ($error) {
									setEventMessages($object->error, $object->errors, 'errors');
								}
							}
							// End call of triggers
						}

						// Redirect here
						// This avoid sending mail twice if going out and then back to page
						$mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2));
						setEventMessages($mesg, null, 'mesgs');

						$moreparam = '';
						if (isset($paramname2) || isset($paramval2)) $moreparam .= '&'.($paramname2 ? $paramname2 : 'mid').'='.$paramval2;
						header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname ? $paramname : 'id').'='.(is_object($object) ? $object->id : '').$moreparam);
						exit;
					} else {
						$langs->load("other");
						$mesg = '<div class="error">';
						if ($mailfile->error) {
							$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
							$mesg .= '<br>'.$mailfile->error;
						} else {
							$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
							if (!empty($conf->global->MAIN_DISABLE_ALL_MAILS)) {
								$mesg .= '<br>Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
							} else {
								$mesg .= '<br>Unkown Error, please refers to your administrator';
							}
						}
						$mesg .= '</div>';

						setEventMessages($mesg, null, 'warnings');
						$action = 'presend';
					}
				}
			} else {
				$langs->load("errors");
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
				dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
				$action = 'presend';
			}
		} else {
			// Creation signature KO
			if (!empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
			else  setEventMessages($signatory->error, null, 'errors');
		}
	}
}

/*
 *  View
 */

$title    = $langs->trans("PreventionPlanAttendants");
$help_url = '';
$morejs   = array("/digiriskdolibarr/js/signature-pad.min.js", "/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

if (!empty($object->id)) $res = $object->fetch_optionals();

// Object card
// ------------------------------------------------------------

$head = preventionplanPrepareHead($object);
print dol_get_fiche_head($head, 'preventionplanAttendants', $langs->trans("PreventionPlan"), -1, "digiriskdolibarr@digiriskdolibarr");
$morehtmlref = ' - ' . $object->label;
$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';

digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

print '<div class="fichecenter"></div>';
print '<div class="underbanner clearboth"></div>';

print dol_get_fiche_end();

print '<div class="div-table-responsive">'; ?>

<div class="wpeo-notice notice-warning">
	<div class="notice-content">
		<div class="notice-title"><?php echo $langs->trans('DisclaimerSignatureTitle') ?></div>
		<div class="notice-subtitle"><?php echo $langs->trans("DisclaimerSignature") ?></div>
	</div>
</div>

<?php
// Show direct link to public interface
print '<br><!-- Link to public interface -->'."\n";
print showDirectPublicLinkSignature($signatory).'<br>';
print '</div>';

print load_fiche_titre($langs->trans("SignatureResponsibles"), '', '');

print '<table class="border centpercent tableforfield">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Role").'</td>';
print '<td>'.$langs->trans("SignatureDate").'</td>';
print '<td>'.$langs->trans("PublicID").'</td>';
print '<td>'.$langs->trans("Name").'</td>';
print '<td class="center">'.$langs->trans("ActionsSignature").'</td>';
print '<td class="center">'.$langs->trans("Signature").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>'."\n";

//Master builder -- Maitre Oeuvre
$element = $signatory->fetchSignatory('PP_MAITRE_OEUVRE', $id);
if ($element > 0) {
	$element = array_shift($element);
	$usertmp->fetch($element->element_id);
}

$url = $_SERVER['REQUEST_URI'];
$zone = "private";

print '<tr class="oddeven"><td>';
print $langs->trans("MaitreOeuvre");
print '</td><td>';
print dol_print_date($element->signature_date,'dayhour');
print '</td><td>';
print $element->signature_url;
print '</td><td>';
print $usertmp->getNomUrl(1);
print '</td><td class="center">';
require __DIR__ . "/core/tpl/digiriskdolibarr_signature_action_view.tpl.php";
print '</td><td class="center">';
require __DIR__ . "/core/tpl/digiriskdolibarr_signature_view.tpl.php";
print '</td><td class="center">';
print $element->getLibStatut(5);
print '</td></tr>';

//External Society Responsible -- Responsable Société extérieure
$element = $signatory->fetchSignatory('PP_EXT_SOCIETY_RESPONSIBLE', $id);
if ($element > 0) {
	$element = array_shift($element);
	$contact->fetch($element->element_id);
}
print '<tr class="oddeven"><td>';
print $langs->trans("ExtSocietyResponsible");
print '</td><td>';
print dol_print_date($element->signature_date,'dayhour');
print '</td><td>';
print $element->signature_url;
print '</td><td>';
print $contact->getNomUrl(1);
print '</td><td class="center">';
require __DIR__ . "/core/tpl/digiriskdolibarr_signature_action_view.tpl.php";
print '</td><td class="center">';
require __DIR__ . "/core/tpl/digiriskdolibarr_signature_view.tpl.php";
print '</td><td class="center">';
print $element->getLibStatut(5);
print '</td></tr>';

print '</tr>';
print '</table>';
print '<br>';

print load_fiche_titre($langs->trans("SignatureIntervenants"), '', '');

print '<table class="border centpercent tableforfield">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Role").'</td>';
print '<td>'.$langs->trans("SignatureDate").'</td>';
print '<td>'.$langs->trans("PublicID").'</td>';
print '<td>'.$langs->trans("Name").'</td>';
print '<td class="center">'.$langs->trans("ActionsSignature").'</td>';
print '<td class="center">'.$langs->trans("Signature").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>'."\n";

//External Society Intervenants -- Intervenants Société extérieure
$j = 1;
$ext_society_intervenants = $signatory->fetchSignatory('PP_EXT_SOCIETY_INTERVENANTS', $id);
if (is_array($ext_society_intervenants) && !empty ($ext_society_intervenants) && $ext_society_intervenants > 0) {
	foreach ($ext_society_intervenants as $element) {
		print '<tr class="oddeven"><td>';
		print $langs->trans("ExtSocietyIntervenants") . ' ' . $j;
		print '</td><td>';
		print dol_print_date($element->signature_date,'dayhour');
		print '</td><td>';
		print $element->signature_url;
		print '</td><td>';
		$contact->fetch($element->element_id);
		print $contact->getNomUrl(1);
		print '</td><td class="center">';
		require __DIR__ . "/core/tpl/digiriskdolibarr_signature_action_view.tpl.php";
		print '</td><td class="center">';
		require __DIR__ . "/core/tpl/digiriskdolibarr_signature_view.tpl.php";
		print '</td><td class="center">';
		print $element->getLibStatut(5);
		print '</td></tr>';
		$j++;
	}
}else {
	print '<td></td>';
}

print '</tr>';
print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
