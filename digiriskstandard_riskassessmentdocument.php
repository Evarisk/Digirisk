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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

require_once './class/digiriskstandard.class.php';
require_once './class/digiriskelement.class.php';
require_once './class/digiriskdocuments/groupmentdocument.class.php';
require_once './class/digiriskdocuments/workunitdocument.class.php';
require_once './class/digiriskdocuments/riskassessmentdocument.class.php';
require_once './lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once './lib/digiriskdolibarr_function.lib.php';
require_once './core/modules/digiriskdolibarr/digiriskdocuments/riskassessmentdocument/modules_riskassessmentdocument.php';
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

		$moreparams['object'] = "";
		$moreparams['user']   = $user;

		$result = $riskassessmentdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

		//Création du dossier à zipper
		$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';

		$pathToZip = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessmentdocument/' . $riskassessmentdocument->ref;
		dol_mkdir($pathToZip);

		// Ajout du fichier au dossier à zipper

		copy(DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessmentdocument/' . $riskassessmentdocument->last_main_doc, $pathToZip . '/' . $riskassessmentdocument->last_main_doc);

		$digiriskelementlist = $digiriskelement->fetchDigiriskElementFlat(0);

		if ( ! empty( $digiriskelementlist ) ) {

			foreach ($digiriskelementlist as $digiriskelementsingle) {
				if ($digiriskelementsingle['object']->element_type == 'groupment') {
					$digiriskelementdocument = new GroupmentDocument($db);
				} elseif ($digiriskelementsingle['object']->element_type == 'workunit') {
					$digiriskelementdocument = new WorkUnitDocument($db);
				}
				$subFolder = $digiriskelementdocument->element;

				$moreparams['object'] = $digiriskelementsingle['object'];
				$moreparams['user']   = $user;

				$digiriskelementdocumentmodel = 'DIGIRISKDOLIBARR_'.strtoupper($digiriskelementdocument->element).'_DEFAULT_MODEL';

				$digiriskelementdocument->generateDocument($conf->global->$digiriskelementdocumentmodel, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

				// Ajout du fichier au dossier à zipper
				$sourceFilePath = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/' . $subFolder . '/' . $digiriskelementsingle['object']->ref . '/';

				copy($sourceFilePath . $digiriskelementdocument->last_main_doc, $pathToZip . '/'  . $digiriskelementdocument->last_main_doc);
			}

			// Get real path for our folder
			$rootPath = realpath($pathToZip);

			// Initialize archive object
			$zip = new ZipArchive();

			$zip->open($riskassessmentdocument->ref . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

			// Create recursive directory iterator
			/** @var SplFileInfo[] $files */
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($rootPath),
				RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ($files as $name => $file)
			{
				// Skip directories (they would be added automatically)
				if (!$file->isDir())
				{
					// Get real and relative path for current file
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($rootPath) + 1);

					// Add current file to archive
					$zip->addFile($filePath, $relativePath);
				}
			}

			// Zip archive will be created only after closing object
			$zip->close();

			//move archive to riskassessmentdocument folder
			rename(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/' . $riskassessmentdocument->ref . '.zip', $pathToZip . '.zip');

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
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader('', $title, $help_url, '', '', '', $morejs, $morecss); ?>

<div id="cardContent" value="">

<?php // Part to show record
$res  = $object->fetch_optionals();
$head = digiriskstandardPrepareHead($object);

dol_fiche_head($head, 'standardRiskAssessmentDocument', $title, -1, "digiriskdolibarr@digiriskdolibarr");

// Object card
// ------------------------------------------------------------
$width = 80; $cssclass = 'photoref';

$morehtmlref = '<div class="refidno">';
$morehtmlref .= '</div>';
$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 1, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';

digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">' . "\n";

//JSON Decode and show fields
require_once './core/tpl/digiriskdolibarr_riskassessmentdocumentfields_view.tpl.php';

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
