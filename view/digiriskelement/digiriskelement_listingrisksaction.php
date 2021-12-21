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
 *   	\file       view/digiriskelement/digiriskelement_listingrisksaction.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view listingrisksaction
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
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

require_once './../../class/digiriskelement.class.php';
require_once './../../class/digiriskstandard.class.php';
require_once './../../class/digiriskdocuments/listingrisksaction.class.php';
require_once './../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once './../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once './../../lib/digiriskdolibarr_function.lib.php';
require_once './../../core/modules/digiriskdolibarr/digiriskdocuments/listingrisksaction/modules_listingrisksaction.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id     = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$type   = GETPOST('type', 'aZ09');

// Initialize technical objects
$listingrisksaction = new ListingRisksAction($db);
$hookmanager->initHooks(array('digiriskelementlistingrisksaction', 'globalcard')); // Note that conf->hooks_modules contains array

if ($type != 'standard') {
	$object = new DigiriskElement($db);
	$object->fetch($id);
} else {
	$object = new DigiriskStandard($db);
	$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
}
$digiriskstandard = new DigiriskStandard($db);

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1];
$permissiontoread   = $user->rights->digiriskdolibarr->listingrisksaction->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->listingrisksaction->write;
$permissiontodelete = $user->rights->digiriskdolibarr->listingrisksaction->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

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

		if ( $type != 'standard' ) {
			$moreparams['object'] = $object;
			$moreparams['user']   = $user;
		} else {
			$moreparams['object'] = "";
			$moreparams['user']   = $user;
		}

		$result = $listingrisksaction->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			if (empty($donotredirect))
			{
				setEventMessages($langs->trans("FileGenerated") . ' - ' . $listingrisksaction->last_main_doc, null);

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
if ($action == 'remove_file' && $permissiontodelete) {
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

$emptyobject = new stdClass($db);

$title    = $langs->trans('ListingRisksAction');
$help_url = 'FR:Module_DigiriskDolibarr#Listing_des_actions_correctives';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader('', $title, $help_url, '', '', '', $morejs, $morecss); ?>

<div id="cardContent" value="">

<?php $res  = $object->fetch_optionals();

if ($type == 'standard') {
	$head = digiriskstandardPrepareHead($object);
} else {
	$head = digiriskelementPrepareHead($object);
}

// Part to show record
print dol_get_fiche_head($head, 'elementListingRisksAction', $title, -1, "digiriskdolibarr@digiriskdolibarr");

// Object card
// ------------------------------------------------------------
$width = 80; $cssclass = 'photoref';


if ($type != 'standard') {
	dol_strlen($object->label) ? $morehtmlref = ' - ' . $object->label : '';
	$morehtmlref .= '<div class="refidno">';
	// ParentElement
	$parent_element = new DigiriskElement($db);
	$result = $parent_element->fetch($object->fk_parent);
	if ($result > 0) {
		$morehtmlref .= $langs->trans("Description").' : '.$object->description;
		$morehtmlref .= '<br>'.$langs->trans("ParentElement").' : '.$parent_element->getNomUrl(1, 'blank', 1);
	}
	else {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		$morehtmlref .= $langs->trans("Description").' : '.$object->description;
		$morehtmlref .= '<br>'.$langs->trans("ParentElement").' : '.$digiriskstandard->getNomUrl(1, 'blank', 1);
	}
	$morehtmlref .= '</div>';
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';
	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);
} else {
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 1, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';
	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', '', '', 0, $morehtmlleft);
}

unset($object->fields['element_type']);
unset($object->fields['fk_parent']);
unset($object->fields['last_main_doc']);
unset($object->fields['entity']);

print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">' . "\n";

// Common attributes
unset($object->fields['import_key']);
unset($object->fields['json']);
unset($object->fields['import_key']);
unset($object->fields['model_odt']);
unset($object->fields['type']);
unset($object->fields['last_main_doc']);
unset($object->fields['label']);
unset($object->fields['description']);

print '</table>';
print '</div>';

print dol_get_fiche_end();

// Document Generation -- Génération des documents
$includedocgeneration = 1;
if ($includedocgeneration) {
	if ($type != 'standard') {
		$objref = dol_sanitizeFileName($object->ref);
		$dir_files = 'listingrisksaction/' . $objref;
		$filedir = $upload_dir . '/' . $dir_files;
		$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
	} else {
		$dir_files = 'listingrisksaction';
		$filedir = $upload_dir . '/' . $dir_files;
		$urlsource = $_SERVER["PHP_SELF"]. '?id=' . $object->id . '&type=standard';
	}

	$modulepart = 'digiriskdolibarr:ListingRisksAction';

	print digiriskshowdocuments($modulepart,$dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, $conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL, 1, 0, 28, 0, '', $langs->trans('ListingRisksAction'), '', $langs->defaultlang, '', $listingrisksaction);
}

// End of page
llxFooter();
$db->close();
