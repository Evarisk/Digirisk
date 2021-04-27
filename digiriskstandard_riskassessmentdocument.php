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
 *   	\file       digiriskstandard_riskassessmentdocument.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view riskassessmentdocument
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

dol_include_once('/digiriskdolibarr/class/digiriskstandard.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdocuments/groupmentdocument.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdocuments/workunitdocument.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdocuments/riskassessmentdocument.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskstandard.lib.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_function.lib.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/riskassessmentdocument/modules_riskassessmentdocument.php');

global $db, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object                 = new DigiriskStandard($db);
$digiriskelement        = new DigiriskElement($db);
$riskassessmentdocument = new RiskAssessmentDocument($db);
$hookmanager->initHooks(array('digiriskelementriskassessmentdocument', 'globalcard')); // Note that conf->hooks_modules contains array

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
$permissiontoread   = $user->rights->digiriskdolibarr->riskassessmentdocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->riskassessmentdocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->riskassessmentdocument->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	if (($action == 'update' && !GETPOST("cancel", 'alpha'))
		|| ($action == 'updateedit') && $permissiontoadd)
	{

		$auditStartDate = GETPOST('AuditStartDate', 'none');
		$auditEndDate   = GETPOST('AuditEndDate', 'none');
		$recipent       = GETPOST('Recipient', 'alpha');
		$method         = GETPOST('Method', 'alpha');
		$sources        = GETPOST('Sources', 'alpha');
		$importantNote  = GETPOST('ImportantNote', 'alpha');
		$sitePlans      = GETPOST('SitePlans', 'alpha');

		$auditStartDate = explode('/',$auditStartDate);
		$auditStartDate = $auditStartDate[2] . '-' . $auditStartDate[1] . '-' . $auditStartDate[0];

		$auditEndDate = explode('/',$auditEndDate);
		$auditEndDate = $auditEndDate[2] . '-' . $auditEndDate[1] . '-' . $auditEndDate[0];

		dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE", $auditStartDate, 'date', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE", $auditEndDate, 'date', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT", $recipent, 'integer', 0, '', $conf->entity);

		dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD", $method, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES", $sources, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTE", $importantNote, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SITE_PLANS", $sitePlans, 'chaine', 0, '', $conf->entity);

		if ($action != 'updateedit' && !$error)
		{
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
	}

	// Action to build doc
	if ($action == 'builddoc' && $permissiontoadd) {
		$outputlangs = $langs;
		$newlang = '';

		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// To be sure vars is defined
		if (empty($hidedetails)) $hidedetails = 0;
		if (empty($hidedesc)) $hidedesc = 0;
		if (empty($hideref)) $hideref = 0;
		if (empty($moreparams)) $moreparams = null;

		$model      = GETPOST('model', 'alpha');

		$result = $riskassessmentdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		$digiriskelementlist = $digiriskelement->fetchDigiriskElementFlat(0);

		if ( ! empty( $digiriskelementlist ) ) {
			foreach ($digiriskelementlist as $digiriskelementsingle) {
				if ($digiriskelementsingle->element_type == 'groupment') {
					$digiriskelementdocument = new GroupmentDocument($db);
				} elseif ($digiriskelementsingle->element_type == 'workunit') {
					$digiriskelementdocument = new WorkUnitDocument($db);
				}
				$moreparams = $digiriskelementsingle;
				$digiriskelementdocumentmodel = 'DIGIRISKDOLIBARR_'.strtoupper($digiriskelementdocument->element).'_DEFAULT_MODEL';

				$digiriskelementdocument->generateDocument($conf->global->$digiriskelementdocumentmodel, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
			}
		}
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			if (empty($donotredirect))
			{
				setEventMessages($langs->trans("FileGenerated") . ' - ' . $riskassessmentdocument->last_main_doc, null);

				$urltoredirect = $_SERVER['REQUEST_URI'];
				$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
				$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

				header('Location: ' . $urltoredirect . '#builddoc');
				exit;
			}
		}
	}
}

// Delete file in doc form
if ($action == 'remove_file' && $permissiontodelete)
{
	if (!empty($upload_dir)) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$langs->load("other");
		$filetodelete = GETPOST('file', 'alpha');
		$file = $upload_dir.'/'.$filetodelete;
		$ret = dol_delete_file($file, 0, 0, 0, $object);
		if ($ret) setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
		else setEventMessages($langs->trans("ErrorFailToDeleteFile", $filetodelete), null, 'errors');

		// Make a redirect to avoid to keep the remove_file into the url that create side effects
		$urltoredirect = $_SERVER['REQUEST_URI'];
		$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
		$urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

		header('Location: '.$urltoredirect);
		exit;
	}
	else {
		setEventMessages('BugFoundVarUploaddirnotDefined', null, 'errors');
	}
}

/*
 * View
 */

$formfile 	 = new FormFile($db);
$emptyobject = new stdClass($db);

$title    = $langs->trans('RiskAssessmentDocument');
$help_url = 'FR:Module_DigiriskDolibarr#Document_unique_2';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");

digiriskHeader('', $title, $help_url, '', '', '', $morejs); ?>

<div id="cardContent" value="">

<?php // Part to show record
$res  = $object->fetch_optionals();
$head = digiriskstandardPrepareHead($object);

dol_fiche_head($head, 'standardRiskAssessmentDocument', '', -1, $object->picto);

// Object card
// ------------------------------------------------------------
$width = 80; $cssclass = 'photoref';

$morehtmlref = '<div class="refidno">';
$morehtmlref .= '</div>';
$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';

digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">' . "\n";

//JSON Decode and show fields
include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_riskassessmentdocumentfields_view.tpl.php';

print '</table>';
print '</div>';

// Buttons for actions
print '<div class="tabsAction" >' . "\n";
$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Modify
	if ($permissiontoadd) {
		if ( $action == 'edit' ) {
			print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
		} else {
			print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
		}
	} else {
		if ( $action == 'edit' ) {
			print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Save') . '</a>' . "\n";
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>' . "\n";
		}
	}
}

print '</div>';
print '</form>';

dol_fiche_end();

// Document Generation -- Génération des documents
$includedocgeneration = 1;
if ($includedocgeneration && $action != 'edit') {
	$dir_files  = 'riskassessmentdocument';
	$filedir    = $upload_dir . '/' . $dir_files;
	$urlsource  = $_SERVER["PHP_SELF"];
	$modulepart = 'digiriskdolibarr:RiskAssessmentDocument';

	print digiriskshowdocuments($modulepart,$dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_DEFAULT_MODEL, 1, 0, 28, 0, '', $langs->trans('RiskAssessmentDocument'), '', $langs->defaultlang, '', $riskassessmentdocument);
}

// End of page
llxFooter();
$db->close();
