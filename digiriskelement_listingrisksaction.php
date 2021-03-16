<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       digiriskelement_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view digiriskelement
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
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/class/listingrisksaction.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');
dol_include_once('/digiriskdolibarr/class/groupment.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_groupment_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_groupment_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_workunit_standard.php');

global $db, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id 				 = GETPOST('id', 'int');
$ref        		 = GETPOST('ref', 'alpha');
$action 			 = GETPOST('action', 'aZ09');
$confirm    		 = GETPOST('confirm', 'alpha');
$cancel     		 = GETPOST('cancel', 'aZ09');
$contextpage 		 = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage 		 = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$element_type        = GETPOST('element_type', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object 			 = new DigiriskElement($db);
$extrafields  	 	 = new ExtraFields($db);
$diroutputmassaction = $conf->digiriskdolibarr->dir_output.'/temp/massgeneration/'.$user->id;
$object->fetch($id);
$hookmanager->initHooks(array('digiriskelementcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search 	= array();

foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
$permissiontoread = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiriskelement->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissiondellink = $user->rights->digiriskdolibarr->digiriskelement->write;
$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

if ($user->socid > 0) $socid = $user->socid;
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

	$backurlforlist = dol_buildpath('/digiriskdolibarr/digiriskelement_card.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/digiriskelement_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}
	if ($action == 'add') { ?>
		<script>
			jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
			//console.log( this );
			let id = $(this).attr('value')
			jQuery( this ).closest( '.unit' ).addClass( 'active' );

			var unitActive = jQuery( this ).closest( '.unit.active' ).attr('id')
			localStorage.setItem('unitactive', unitActive );

			jQuery( this ).closest( '.unit' ).attr( 'value', id );
		</script>
	<?php }
	// Action to add record
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to build doc

	if ($action == 'builddoc' && $permissiontoadd) {

		if (is_numeric(GETPOST('model', 'alpha'))) {
			$error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Model"));
		} else {
			if ($id > 0) {
				// Reload to get all modified line records and be ready for hooks
				$ret = $object->fetch($id);
				$ret = $object->fetch_thirdparty();
				/*if (empty($object->id) || ! $object->id > 0)
				{
					dol_print_error('Object must have been loaded by a fetch');
					exit;
				}*/

				// Save last template used to generate document
				if (GETPOST('model', 'alpha')) {
					$object->setDocModel($user, GETPOST('model', 'alpha'));
				}

				// Special case to force bank account
				//if (property_exists($object, 'fk_bank'))
				//{
				if (GETPOST('fk_bank', 'int')) {
					// this field may come from an external module
					$object->fk_bank = GETPOST('fk_bank', 'int');
				} elseif (!empty($object->fk_account)) {
					$object->fk_bank = $object->fk_account;
				}
				//}

				$outputlangs = $langs;
				$newlang = '';

				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($object->thirdparty->default_lang)) $newlang = $object->thirdparty->default_lang; // for proposal, order, invoice, ...
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($object->default_lang)) $newlang = $object->default_lang; // for thirdparty
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				// To be sure vars is defined
				if (empty($hidedetails)) $hidedetails = 0;
				if (empty($hidedesc)) $hidedesc = 0;
				if (empty($hideref)) $hideref = 0;
				if (empty($moreparams)) $moreparams = null;

				$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				} else {
					if (empty($donotredirect))    // This is set when include is done by bulk action "Bill Orders"
					{
						setEventMessages($langs->trans("FileGenerated"), null);

						$urltoredirect = $_SERVER['REQUEST_URI'];
						$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
						$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

						header('Location: ' . $urltoredirect . '#builddoc');
						exit;
					}
				}
			} else {

				$outputlangs = $langs;
				$newlang = '';

				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($object->thirdparty->default_lang)) $newlang = $object->thirdparty->default_lang; // for proposal, order, invoice, ...
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($object->default_lang)) $newlang = $object->default_lang; // for thirdparty
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				// To be sure vars is defined
				if (empty($hidedetails)) $hidedetails = 0;
				if (empty($hidedesc)) $hidedesc = 0;
				if (empty($hideref)) $hideref = 0;
				if (empty($moreparams)) $moreparams = null;

				$result = $object->generateDocument('listing_risks_actions_odt', $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				} else {
					if (empty($donotredirect))    // This is set when include is done by bulk action "Bill Orders"
					{
						setEventMessages($langs->trans("FileGenerated"), null);

						$urltoredirect = $_SERVER['REQUEST_URI'];
						$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
						$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

						header('Location: ' . $urltoredirect . '#builddoc');
						exit;
					}
				}
			}
		}
	}
//
//	// Actions to send emails
//	$triggersendname = 'DIGIRISKELEMENT_SENTBYMAIL';
//	$autocopy = 'MAIN_MAIL_AUTOCOPY_DIGIRISKELEMENT_TO';
//	$trackid = 'digiriskelement'.$object->id;
//	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 *
 * Put here all code to build page
 */

$form 		 = new Form($db);
$formfile 	 = new FormFile($db);
$formproject = new FormProjets($db);

if ( $object->element_type == 'groupment' ) {
	$title = $langs->trans("Groupment");
	$title_create = $langs->trans("NewGroupment");
	$title_edit = $langs->trans("ModifyGroupment");
} else if ( $object->element_type == 'workunit' ) {
	$title = $langs->trans("WorkUnit");
	$title_create = $langs->trans("NewWorkUnit");
	$title_edit = $langs->trans("ModifyWorkUnit");
	$object->picto = 'workunit@digiriskdolibarr';
} else {
	$title_create = $langs->trans("NewDigiriskElement");
}

$help_url = 'FR:Module_DigiriskDolibarr';


$morejs = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");

$object->digiriskHeader('', $title, $help_url, '', '', '', $morejs);

?>
<div id="cardContent" value="">

<?php
// Example : Adding jquery code


if (!$object->id) {

	$object->ref = $conf->global->MAIN_INFO_SOCIETE_NOM;
	$object->label = 'Societe principale';
	$object->entity = 2;
	unset($object->fields['element_type']);


}
// Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);

	dol_fiche_head($head, 'elementListingrisksaction', $langs->trans("DigiriskElement"), -1, $object->picto);

	$formconfirm = '';



	// Object card
	// ------------------------------------------------------------

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';
	$width = 80; $cssclass = 'photoref';
	if (isset($object->element_type)) {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type).'</div>';
	} else {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, 'logos').'</div>';
	}
	$object->digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);


	dol_fiche_end();

	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Document Generation -- Génération des documents
	print '<h2>' . $langs->trans('ListingRisksAction') . ($object->id ? ' ' . $object->ref : ' ' . 'global') . '</h2>';

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;
		// Documents
		if ($includedocgeneration) {
			if ($object->id > 0) {
				$objref = dol_sanitizeFileName($object->ref);

				$relativepath = $objref . '/' . $objref . '.pdf';
				$dir_files = 'listingrisksaction/' . $objref;
				$filedir = $conf->digiriskdolibarr->dir_output.'/'.$dir_files;

				$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
				$genallowed = $user->rights->digiriskdolibarr->digiriskelement->read;	// If you can read, you can build the PDF to read content
				$delallowed = $user->rights->digiriskdolibarr->digiriskelement->create;	// If you can create/edit, you can remove a file on card

				$modulepart = 'digiriskdolibarr:ListingRisksAction';

				print $formfile->showdocuments($modulepart,$dir_files, $filedir, $urlsource, $genallowed, $delallowed, $conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang, '');

			}
			else {
				$objref = dol_sanitizeFileName($object->ref);

				$relativepath = $objref . '/' . $objref . '.pdf';
				$dir_files = 'listingrisksaction/mycompany';
				$filedir = $conf->digiriskdolibarr->dir_output.'/'.$dir_files;
				$object->modelpdf = 'listing_risks_actions_odt';
				$urlsource = $_SERVER["PHP_SELF"];
				$genallowed = $user->rights->digiriskdolibarr->digiriskelement->read;	// If you can read, you can build the PDF to read content
				$delallowed = $user->rights->digiriskdolibarr->digiriskelement->create;	// If you can create/edit, you can remove a file on card

				$modulepart = 'digiriskdolibarr:ListingRisksAction';

				print $formfile->showdocuments($modulepart,$dir_files, $filedir, $urlsource, $genallowed, $delallowed, $conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang, '');

			}
		}

	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) $action = 'presend';

	// Presend form

	//force generate document when 'send by mail' button is clicked
	$object->modelpdf = 'digiriskelement_A4_odt';

	//get title and content
	$modelmail = 'Digirisk_DigiriskElement';
	$defaulttopic = $langs->trans('SendDigiriskElement') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$diroutput = $conf->digiriskdolibarr->dir_output . '/digiriskelement';
	$trackid = 'digiriskelement'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	print '</div>';

	// Show links to link elements
	//$linktoelem = $form->showLinkToObjectBlock($object, null, array('digiriskelement'));
	//$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	$MAXEVENT = 10;

	$morehtmlright = '<a href="'.dol_buildpath('/digiriskdolibarr/digiriskelement_agenda.php', 1).'?id='.$object->id.'">';
	$morehtmlright .= $langs->trans("SeeAll");
	$morehtmlright .= '</a>';

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element_type.'@digiriskdolibarr', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

	print '</div></div></div>';
}

//Select mail models is same action as presend
if (GETPOST('modelselected')) $action = 'presend';

// Presend form

//force generate document when 'send by mail' button is clicked
$object->modelpdf = 'digiriskelement_A4_odt';

//get title and content
$modelmail = 'Digirisk_DigiriskElement';
$defaulttopic = $langs->trans('SendDigiriskElement') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
$diroutput = $conf->digiriskdolibarr->dir_output . '/digiriskelement';
$trackid = 'digiriskelement'.$object->id;

include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
print '</div>';



// End of page
llxFooter();
$db->close();
