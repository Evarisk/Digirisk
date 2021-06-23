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
 *   	\file       preventionplan_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view preventionplan
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

require_once __DIR__ . '/class/digiriskdocuments.class.php';
require_once __DIR__ . '/class/digiriskelement.class.php';
require_once __DIR__ . '/class/digiriskresources.class.php';
require_once __DIR__ . '/class/preventionplan.class.php';
require_once __DIR__ . '/class/riskanalysis/risk.class.php';
require_once __DIR__ . '/class/digiriskdocuments/preventionplandocument.class.php';
require_once __DIR__ . '/lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/lib/digiriskdolibarr_preventionplan.lib.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/preventionplan/mod_preventionplan_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/preventionplandet/mod_preventionplandet_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/preventionplandocument/mod_preventionplandocument_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/preventionplandocument/modules_preventionplandocument.php';

global $db, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$lineid              = GETPOST('lineid', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'preventionplancard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object = new PreventionPlan($db);
$signatory = new PreventionPlanSignature($db);

$usertmp = new User($db);
$contact = new Contact($db);

$object->fetch($id);

$digiriskelement   = new DigiriskElement($db);
$digiriskresources = new DigiriskResources($db);

$hookmanager->initHooks(array('preventionplansignature', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
$permissiontoread   = $user->rights->digiriskdolibarr->preventionplandocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->preventionplandocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->preventionplandocument->delete;

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

	if (!$error) {
		$result = $signatory->update($user, false);
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

/*
 *  View
 */

$title    = $langs->trans("PreventionPlan");
$help_url = 'EN:Module_Third_Parties|FR:Module_DigiriskDolibarr#L.27onglet_Horaire_de_travail|ES:Empresas';
$morejs   = array("/digiriskdolibarr/js/signature-pad.min.js", "/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

if (!empty($object->id)) $res = $object->fetch_optionals();

// Object card
// ------------------------------------------------------------

$head = preventionplanPrepareHead($object);
print dol_get_fiche_head($head, 'preventionplanAttendants', $langs->trans("PreventionPlan"), -1, '');
dol_banner_tab($object, 'ref', '', 0, 'rowid', 'ref');

print '<div class="fichecenter"></div>';
print '<div class="underbanner clearboth"></div>';

print dol_get_fiche_end();

print load_fiche_titre($langs->trans("SignatureManagement"), '', '');

print '<div class="div-table-responsive">';

print '<table class="border centpercent tableforfield">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("People").'</td>';
print '<td class="center">'.$langs->trans("Action").'</td>';
print '<td>'.$langs->trans("Status").'</td>';
print '</tr>'."\n";

//Master builder -- Maitre Oeuvre
print '<tr class="oddeven"><td>';
print $langs->trans("MaitreOeuvre");
print '</td>';
print '<td>';
$element = $signatory->fetchSignatory('PP_MAITRE_OEUVRE', $id);

if ($element > 0) {
	$element = array_shift($element);
	$usertmp->fetch($element->element_id);
	print $usertmp->getNomUrl(1);
}
print '<br>';
print '</td>';
print '<td class="center">'; ?>

<?php if (empty($element->signature)) : ?>
	<div class="wpeo-button button-blue wpeo-modal-event modal-signature-open modal-open" value="<?php echo $element->id ?>">
		<span><?php echo $langs->trans('Sign'); ?></span>
	</div>

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
						<span><?php echo $langs->trans('Validate'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php else : ?>
	<img src='<?php echo $element->signature ?>' width="100px" height="100px" style="border: #0b419b solid 2px">
<?php endif; ?>

<?php
print '</td>';
print '<td>';
print $element->status;
print '</td></tr>';

//External Society Responsible -- Responsable Société extérieure
print '<tr class="oddeven"><td>';
print $langs->trans("ExtSocietyResponsible");
print '</td>';
print '<td>';
$ext_society_responsible = $signatory->fetchSignatory('PP_EXT_SOCIETY_RESPONSIBLE', $id);

if ($ext_society_responsible > 0) {
	$ext_society_responsible = array_shift($ext_society_responsible);
	$contact->fetch($ext_society_responsible->element_id);
	print $contact->getNomUrl(1);
}
print '<br>';
print '</td>';
print '<td class="center">'; ?>
<?php if (empty($ext_society_responsible->signature)) : ?>
	<div class="wpeo-button button-blue wpeo-modal-event modal-signature-open modal-open" value="<?php echo $ext_society_responsible->id ?>">
		<span><?php echo $langs->trans('Sign'); ?></span>
	</div>

	<div class="modal-signature" value="<?php echo $ext_society_responsible->id ?>">
		<div class="wpeo-modal modal-signature" id="modal-signature<?php echo $ext_society_responsible->id ?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header-->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('Signature'); ?></h2>
					<div class="modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- Modal-ADD Signature Content-->
				<div class="modal-content" id="#modalContent">
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
					<div class="signature-validate wpeo-button button-primary" value="<?php echo $ext_society_responsible->id ?>">
						<span><?php echo $langs->trans('Validate'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php else : ?>
<img src='<?php echo $ext_society_responsible->signature ?>' width="100px" height="100px" style="border: #0b419b solid 2px">
<?php endif; ?>
<?php
print '</td>';
print '<td>';
print $ext_society_responsible->status;
print '</td></tr>';

//External Society Intervenants -- Intervenants Société extérieure
$j = 1;
$ext_society_intervenants = $signatory->fetchSignatory('PP_EXT_SOCIETY_INTERVENANTS', $id);
if (is_array($ext_society_intervenants) && !empty ($ext_society_intervenants) && $ext_society_intervenants > 0) {
	foreach ($ext_society_intervenants as $ext_society_intervenant) {
		print '<tr class="oddeven"><td>';
		print $langs->trans("ExtSocietyIntervenants") . ' ' . $j;
		print '</td>';

		print '<td>';
		$contact->fetch($ext_society_intervenant->element_id);
		print $contact->getNomUrl(1);
		print '</td>';

		print '<td class="center">';
		if (empty($ext_society_intervenant->signature)) : ?>
			<div class="wpeo-button button-blue wpeo-modal-event modal-signature-open modal-open" value="<?php echo $ext_society_intervenant->id ?>">
				<span><?php echo $langs->trans('Sign'); ?></span>
			</div>

			<div class="modal-signature" value="<?php echo $ext_society_intervenant->id ?>">
				<div class="wpeo-modal modal-signature" id="modal-signature<?php echo $ext_society_intervenant->id ?>">
					<div class="modal-container wpeo-modal-event">
						<!-- Modal-Header-->
						<div class="modal-header">
							<h2 class="modal-title"><?php echo $langs->trans('Signature'); ?></h2>
							<div class="modal-close"><i class="fas fa-times"></i></div>
						</div>
						<!-- Modal-ADD Signature Content-->
						<div class="modal-content" id="#modalContent">
							<canvas style="height: 95%; width: 95%; border: #0b419b solid 2px">
								<?php  echo $ext_society_intervenant->signature ?>
							</canvas>
						</div>
						<!-- Modal-Footer-->
						<div class="modal-footer">
							<div class="signature-erase wpeo-button button-grey">
								<span><i class="fas fa-eraser"></i> <?php echo $langs->trans('Erase'); ?></span>
							</div>
							<div class="wpeo-button button-grey modal-close">
								<span><?php echo $langs->trans('Cancel'); ?></span>
							</div>
							<div class="signature-validate wpeo-button button-primary" value="<?php echo $ext_society_intervenant->id ?>">
								<span><?php echo $langs->trans('Validate'); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="wpeo-modal-event modal-signature-open modal-open" value="<?php echo $ext_society_intervenant->id ?>">
				<img src='<?php echo $ext_society_intervenant->signature ?>' width="100px" height="100px" style="border: #0b419b solid 2px">
			</div>

			<div class="modal-signature" value="<?php echo $ext_society_intervenant->id ?>">
				<div class="wpeo-modal modal-signature" id="modal-signature<?php echo $ext_society_intervenant->id ?>">
					<div class="modal-container wpeo-modal-event">
						<!-- Modal-Header-->
						<div class="modal-header">
							<h2 class="modal-title"><?php echo $langs->trans('Signature'); ?></h2>
							<div class="modal-close"><i class="fas fa-times"></i></div>
						</div>
						<!-- Modal-ADD Signature Content-->
						<div class="modal-content" id="#modalContent">
							<input type="hidden" id="signature_data<?php echo $ext_society_intervenant->id ?>" value="<?php echo $ext_society_intervenant->signature ?>">
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
							<div class="signature-validate wpeo-button button-primary" value="<?php echo $ext_society_intervenant->id ?>">
								<span><?php echo $langs->trans('Validate'); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php print '</td>';

		print '<td>';
		print $ext_society_intervenant->status;
		print '</td>';
		print '</tr>';

		$j++;
	}
}else {
	print '<td></td>';
}

print '</tr>';
print '</table>';
print '</div>';
print '</div>';


dol_fiche_end();
// End of page
llxFooter();
$db->close();
