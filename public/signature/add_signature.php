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
 *       \file       public/signature/add_signature.php
 *       \ingroup    digiriskdolibarr
 *       \brief      Public page to add signature
 */

if ( ! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if ( ! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if ( ! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if ( ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if ( ! defined('NOLOGIN'))        define("NOLOGIN", 1);           // This means this output page does not require to be logged.
if ( ! defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1);       // We accept to go on this page from external web site.
if ( ! defined('NOIPCHECK'))		define('NOIPCHECK', '1');      // Do not check IP defined into conf $dolibarr_main_restrict_ip
if ( ! defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once '../../class/preventionplan.class.php';
require_once '../../class/digiriskdocuments/preventionplandocument.class.php';
require_once '../../class/firepermit.class.php';
require_once '../../class/digiriskdocuments/firepermitdocument.class.php';
require_once '../../class/accident.class.php';
//require_once '../../class/digiriskdocuments/accidentdocument.class.php';
require_once '../../lib/digiriskdolibarr_function.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other", "errors"));

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action   = GETPOST('action', 'aZ09');
$source   = GETPOST('source', 'aZ09');
$type     = GETPOST('type', 'aZ09');
$url      = dirname($_SERVER['PHP_SELF']) . '/signature_success.php';

// Initialize technical objects
$user = new User($db);

switch ($type) {
	case 'preventionplan':
		$object         = new PreventionPlan($db);
		$signatory      = new PreventionPlanSignature($db);
		$objectdocument = new PreventionPlanDocument($db);
		break;
	case 'firepermit':
		$object         = new FirePermit($db);
		$signatory      = new FirePermitSignature($db);
		$objectdocument = new FirePermitDocument($db);
		break;
	case 'accident':
		$object         = new Accident($db);
		$signatory      = new AccidentSignature($db);
		//$objectdocument = new AccidentDocument($db);
		break;
}

$signatory->fetch('', '', " AND signature_url =" . "'" . $track_id . "'");
if (dol_strlen($signatory->signature)) {
	$urltoredirect = dirname($_SERVER['PHP_SELF']) . '/signature_success.php';
	header('Location: ' . $urltoredirect);
}
$object->fetch($signatory->fk_object);

$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Action to add record
if ($action == 'addSignature') {
	$signatoryID  = GETPOST('signatoryID');
	$data = json_decode(file_get_contents('php://input'), true);

	$signatory->fetch($signatoryID);
	$signatory->signature      = $data['signature'];
	$signatory->signature_date = dol_now();

	// Check Captcha code if is enabled
	if ( ! empty($conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA)) {
		$sessionkey = 'dol_antispam_value';
		$ok         = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) === strtolower($data['code'])));

		if ( ! $ok) {
			$error++;
			setEventMessage($langs->trans('ErrorBadValueForCode'), 'errors');
			$action = '';
		}
	}

	if ( ! $error) {
		$result = $signatory->update($user, false);
		if ($result > 0) {
			$signatory->setSigned($user, false);
			// Creation signature OK
			exit;
		} else {
			// Creation signature KO
			if ( ! empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
			else setEventMessages($signatory->error, null, 'errors');
		}
	} else {
		exit;
	}
}

if ($action == 'builddoc') {
	$outputlangs = $langs;
	$newlang     = '';

	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
	if ( ! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}

	// To be sure vars is defined
	if (empty($hidedetails)) $hidedetails = 0;
	if (empty($hidedesc)) $hidedesc       = 0;
	if (empty($hideref)) $hideref         = 0;
	if (empty($moreparams)) $moreparams   = null;

	$constforval = "DIGIRISKDOLIBARR_".strtoupper($type.'document')."_SPECIMEN_ADDON_ODT_PATH";
	$template = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $conf->global->$constforval);
	$model = $type.'document_specimen_odt:'.$template.'template_'.$type.'document_specimen.odt';

	$moreparams['object'] = $object;
	$moreparams['user']   = $user;

	$result = $objectdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	if ($result <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');

		$action = '';
	} elseif (empty($donotredirect)) {
		$document_name = $objectdocument->last_main_doc;

		copy($conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/' . $type.'document' . '/' . $object->ref . '/specimen/' . $document_name, DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/temp/' . $type . '_specimen_' . $track_id . '.odt');

		setEventMessages($langs->trans("FileGenerated") . ' - ' . $document_name, null);

		$urltoredirect = $_SERVER['REQUEST_URI'];
		$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
		$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

		header('Location: ' . $urltoredirect . '#builddoc');
		exit;
	}
}

if ($action == 'remove_file') {
	$files = dol_dir_list(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/temp/'); // get all file names

	foreach ($files as $file) {
		if (is_file($file['fullname'])) {
			dol_delete_file($file['fullname']);
		}
	}
}
/*
 * View
 */

$form = new Form($db);

if (empty($conf->global->DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE)) {
	print $langs->trans('SignaturePublicInterfaceForbidden');
	exit;
}

$morejs  = array("/digiriskdolibarr/js/signature-pad.min.js", "/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeaderSignature($langs->trans("Signature"), "", 0, 0, $morejs, $morecss);

if ( $signatory->role == 'PP_EXT_SOCIETY_INTERVENANTS') {
	$element = $signatory;
} else {
	$element = $signatory->fetchSignatory($signatory->role, $signatory->fk_object, $type);
	$element = array_shift($element);
}

?>
<div class="digirisk-signature-container">
	<input type="hidden" name="token" value="<?php echo newToken(); ?>">
	<div class="wpeo-gridlayout grid-2">
		<div class="informations">
			<input type="hidden" id="confCAPTCHA" value="<?php echo $conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA ?>"/>
			<div class="wpeo-gridlayout grid-2 file-generation">
				<strong class="grid-align-middle"><?php echo $langs->trans("ThisIsInformationOnDocumentToSign"); ?></strong>
				<?php if ($type == 'preventionplan') : ?>
					<?php $path = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/documents/temp/';	?>
					<input type="hidden" class="specimen-name" value="<?php echo $type . '_specimen_' . $track_id . '.odt' ?>">
					<input type="hidden" class="specimen-path" value="<?php echo $path ?>">
					<input type="hidden" class="track-id" value="<?php echo $track_id ?>">
					<span class="wpeo-button button-primary  button-radius-2 grid-align-right auto-download"><i class="button-icon fas fa-file-pdf"></i><?php echo '  ' . $langs->trans('ShowDocument'); ?></span>
				<?php endif; ?>
			</div>
			<br>
			<div class="wpeo-table table-flex table-2">
				<div class="table-row">
					<div class="table-cell"><?php echo $langs->trans("Name"); ?></div>
					<div class="table-cell table-end"><?php echo $signatory->firstname . ' ' . $signatory->lastname; ?></div>
				</div>
				<div class="table-row">
					<div class="table-cell"><?php echo $langs->trans("DocumentName"); ?></div>
					<div class="table-cell table-end"><?php echo $object->ref . ' ' . $object->label; ?></div>
				</div>
			</div>
		</div>
		<div class="signature">
			<div class="wpeo-gridlayout grid-2">
				<strong class="grid-align-middle"><?php echo $langs->trans("Signature"); ?></strong>
				<div class="wpeo-button button-primary button-square-40 button-radius-2 grid-align-right wpeo-modal-event modal-signature-open modal-open" value="<?php echo $element->id ?>">
					<span><i class="fas fa-pen-nib"></i> <?php echo $langs->trans('Sign'); ?></span>
				</div>
			</div>
			<br>
			<div class="signature-element">
				<?php require "../../core/tpl/signature/digiriskdolibarr_signature_view.tpl.php"; ?>
			</div>
		</div>
	</div>
	<?php
	if ( ! empty($conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA)) {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
		print '<div class="center"><label for="email"><span class="fieldrequired">' . $langs->trans("SecurityCode") . '</span></label>';
		print '<span class="span-icon-security inline-block">';
		print '<input id="securitycode" placeholder="' . $langs->trans("SecurityCode") . '" class="flat input-icon-security width125" type="text" maxlength="5" name="code" tabindex="3" />';
		print '<input type="hidden" id="sessionCode" value="' . $_SESSION['dol_antispam_value'] . '"/>';
		print '<input type="hidden" id="redirectSignatureError" value="' . $_SERVER['REQUEST_URI'] . '"/>';
		print '</span>';
		print '<span class="nowrap inline-block">';
		print '<img class="inline-block valignmiddle" src="' . DOL_URL_ROOT . '/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
		print '<a class="inline-block valignmiddle" href="" tabindex="4" data-role="button">' . img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"') . '</a>';
		print '</span>';
		print '</div>';
	}?>
</div>
<?php

llxFooter('', 'public');
$db->close();

