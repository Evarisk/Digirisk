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
 *       \file      public/signature/add_signature.php
 *       \ingroup    digiriskdolibarr
 *       \brief      Public page to add signature
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
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once '../../class/preventionplan.class.php';
require_once '../../lib/digiriskdolibarr_function.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other", "errors"));

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$signatory = new PreventionPlanSignature($db);
$user      = new User($db);
$signatory->fetch('',''," AND signature_url ="."'".$track_id."'");

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
			//$urltogo = str_replace('__ID__', $result, $backtopage);
			//$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			$host  = $_SERVER['HTTP_HOST'];
			$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$extra = 'index.php';
			$url = "http://".$host.$uri."/".$extra;
			echo '<pre>';
			print_r("Location: http://$host$uri/$extra");
			echo '</pre>';
			exit;
			header("Location: http://$host$uri/$extra");
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

/*
 * View
 */

$form = new Form($db);
$formticket = new FormTicket($db);

if (empty($conf->global->DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE))
{
	print $langs->trans('SignaturePublicInterfaceForbidden');
	exit;
}

$morejs   = array("/digiriskdolibarr/js/signature-pad.min.js", "/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeaderSignature($langs->trans("Signature"), "", 0, 0, $morejs, $morecss);

//print '<div class="ticketpublicarea">';
//print '<p style="text-align: center">'.($conf->global->TICKET_PUBLIC_TEXT_HOME ? $conf->global->TICKET_PUBLIC_TEXT_HOME : $langs->trans("TicketPublicDesc")).'</p>';
//print '<div class="ticketform">';
//print '<a href="create_ticket.php" rel="nofollow noopener" class="butAction marginbottomonly"><div class="index_create bigrounded"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span><br>'.dol_escape_htmltag($langs->trans("CreateTicket")).'</div></a>';
//print '<a href="list.php" rel="nofollow noopener" class="butAction marginbottomonly"><div class="index_display bigrounded"><span class="fa fa-list-alt valignmiddle btnTitle-icon"></span><br>'.dol_escape_htmltag($langs->trans("ViewMyTicketList")).'</div></a>';
//print '<a href="view.php" rel="nofollow noopener" class="butAction marginbottomonly"><div class="index_display bigrounded">'.img_picto('', 'ticket').'<br>'.dol_escape_htmltag($langs->trans("ShowTicketWithTrackId")).'</div></a>';
//print '<div style="clear:both;"></div>';
//print '</div>';
//print '</div>';

print '<div class="center">'."\n";
print '<table with="100%" id="tablepublicpayment">';
print '<tr><td colspan="2" class="opacitymedium">'.$langs->trans("ThisIsInformationOnDocumentToSign").' :</td></tr>'."\n";

$found = false;
$error = 0;
$var = false;

// Payment on customer order
$found = true;
$object = new PreventionPlan($db);
$result = $object->fetch($signatory->fk_object);
if ($result <= 0) {
	$mesg = $object->error;
	$error++;
}

// Creditor

print '<tr class="CTableRow'.($var ? '1' : '2').'"><td class="CTableRow'.($var ? '1' : '2').'">'.$langs->trans("Creditor");
print '</td><td class="CTableRow'.($var ? '1' : '2').'"><b>'.$creditor.'</b>';
print '<input type="hidden" name="creditor" value="'.$creditor.'">';
print '</td></tr>'."\n";

// Debitor

print '<tr class="CTableRow'.($var ? '1' : '2').'"><td class="CTableRow'.($var ? '1' : '2').'">'.$langs->trans("ThirdParty");
print '</td><td class="CTableRow'.($var ? '1' : '2').'"><b>'.$object->thirdparty->name.'</b>';

// Object

$text = '<b>'.$langs->trans("SignatureRef", $object->ref).'</b>';
print '<tr class="CTableRow'.($var ? '1' : '2').'"><td class="CTableRow'.($var ? '1' : '2').'">'.$langs->trans("Designation");
print '</td><td class="CTableRow'.($var ? '1' : '2').'">'.$text;
print '<input type="hidden" name="source" value="'.GETPOST("source", 'alpha').'">';
print '<input type="hidden" name="ref" value="'.$object->ref.'">';
print '</td></tr>'."\n";

if (!$found && !$mesg) $mesg = $langs->transnoentitiesnoconv("ErrorBadParameters");
if ($mesg) print '<tr><td align="center" colspan="2"><br><div class="warning">'.dol_escape_htmltag($mesg).'</div></td></tr>'."\n";

print '</table>'."\n";
print "\n";

if ( $signatory->role == 'PP_EXT_SOCIETY_INTERVENANTS') {
	$element = $signatory;
} else {
	$element = $signatory->fetchSignatory($signatory->role, $signatory->fk_object);
	$element = array_shift($element);
}
$url = dirname($_SERVER['PHP_SELF']) . '/index.php'; ?>
<?php if (empty($element->signature)) : ?>
	<div class="wpeo-button button-blue wpeo-modal-event modal-signature-open modal-open" value="<?php echo $element->id ?>">
		<span><?php echo $langs->trans('Sign'); ?></span>
	</div>
<?php else : ?>
	<img class="wpeo-modal-event modal-signature-open modal-open" value="<?php echo $element->id ?>" src='<?php echo $element->signature ?>' width="300px" height="200px" style="border: #0b419b solid 2px">
<?php endif; ?>

	<div class="modal-signature" value="<?php echo $element->id ?>">
		<div class="wpeo-modal modal-signature" id="modal-signature<?php echo $element->id ?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header-->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('Signature'); ?></h2>
					<div class="modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- Modal-ADD Signature Content-->
				<div class="modal-content" id="#modalContent">
					<input type="hidden" id="signature_data<?php echo $element->id ?>" value="<?php echo $element->signature ?>">
					<canvas style="height: 95%; width: 95%; border: #0b419b solid 2px"></canvas>
				</div>
				<!-- Modal-Footer-->
				<div class="modal-footer">
					<div class="signature-erase wpeo-button button-grey">
						<span><i class="fas fa-eraser"></i> <?php echo $langs->trans('Erase'); ?></span>
					</div>
					<div class="wpeo-button button-grey modal-close">
						<span><?php echo $langs->trans('Cancel'); ?></span>
					</div>
					<div class="signature-validate wpeo-button button-primary" value="<?php echo $element->id ?>">
						<input type="hidden" id="redirect<?php echo $element->id ?>" value="<?php echo $url ?>">
						<span><?php echo $langs->trans('Validate'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php

// End of page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix, $object);

llxFooter('', 'public');

$db->close();

