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
 *   	\file       view/digiriskelement/digiriskelement_card.php
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
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

require_once './../../class/digiriskdocuments.class.php';
require_once './../../class/digiriskelement.class.php';
require_once './../../class/digiriskstandard.class.php';
require_once './../../class/digiriskdocuments/groupmentdocument.class.php';
require_once './../../class/digiriskdocuments/workunitdocument.class.php';
require_once './../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once './../../lib/digiriskdolibarr_function.lib.php';
require_once './../../core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_standard.php';
require_once './../../core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_standard.php';
require_once './../../core/modules/digiriskdolibarr/digiriskdocuments/groupmentdocument/modules_groupmentdocument.php';
require_once './../../core/modules/digiriskdolibarr/digiriskdocuments/workunitdocument/modules_workunitdocument.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$element_type        = GETPOST('element_type', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object           = new DigiriskElement($db);
$extrafields      = new ExtraFields($db);
$digiriskstandard = new DigiriskStandard($db);

$object->fetch($id);

if ( $object->element_type == 'groupment') {
	$digiriskelementdocument = new GroupmentDocument($db);
} elseif (  $object->element_type == 'workunit' ) {
	$digiriskelementdocument = new WorkUnitDocument($db);
}

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('digiriskelementcard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

//Security check
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiriskelement->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=1', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	// Action to add record
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	if ($action == 'add' && $permissiontoadd) { ?>
		<script>
			jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
			//console.log( this );
			let id = $(this).attr('value');
			jQuery( this ).closest( '.unit' ).addClass( 'active' );

			var unitActive = jQuery( this ).closest( '.unit.active' ).attr('id');
			localStorage.setItem('unitactive', unitActive );

			jQuery( this ).closest( '.unit' ).attr( 'value', id );
		</script>
		<?php
	}

	if ($action == 'view' && $permissiontoadd) {
		header('Location: ' . $backtopage);
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

		$moreparams['object'] = $object;
		$moreparams['user']   = $user;

		$result = $digiriskelementdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			if (empty($donotredirect))
			{
				setEventMessages($langs->trans("FileGenerated") . ' - ' . $digiriskelementdocument->last_main_doc, null);

				$urltoredirect = $_SERVER['REQUEST_URI'];
				$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
				$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

				header('Location: ' . $urltoredirect . '#builddoc');
				exit;
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

	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes")
	{
		$object->fetch($id);
		$result = $object->delete($user);

		if ($result > 0)
		{
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header('Location: '.$backurlforlist);
			exit;
		} else {
			dol_syslog($object->error, LOG_DEBUG);
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

/*
 * View
 */

$form        = new Form($db);
$emptyobject = new stdClass($db);
$formconfirm = '';

$parameters = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;


if ( $object->element_type == 'groupment' ) {
	$title        = $langs->trans("Groupment");
	$title_create = $langs->trans("NewGroupment");
	$title_edit   = $langs->trans("ModifyGroupment");
	$object->picto = 'groupment@digiriskdolibarr';
} else if ( $object->element_type == 'workunit' ) {
	$title         = $langs->trans("WorkUnit");
	$title_create  = $langs->trans("NewWorkUnit");
	$title_edit    = $langs->trans("ModifyWorkUnit");
	$object->picto = 'workunit@digiriskdolibarr';
} else {
	$element_type = GETPOST('element_type', 'alpha');
	if ( $element_type == 'groupment' ){
		$title_create = $langs->trans("NewGroupment");
	}else {
		$title_create  = $langs->trans("NewWorkUnit");
	}
}

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader('', $title, $help_url, '', '', '', $morejs, $morecss); ?>

	<div id="cardContent" value="">

<?php // Part to create
if ($action == 'create') {
	$object->fetch($fk_parent);
	print load_fiche_titre($title_create, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head(array(), '');

	unset($object->fields['ref']);
	unset($object->fields['status']);
	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);
	unset($object->fields['description']);

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	$type = 'DIGIRISKDOLIBARR_'.strtoupper($element_type).'_ADDON';
	$digirisk_addon = $conf->global->$type;
	$modele = new $digirisk_addon($db);

	print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="'.$modele->getNextValue($object).'">';
	print $modele->getNextValue($object);
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("ParentElement").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="parent" id="parent">';
	if (empty($fk_parent)) {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		print $digiriskstandard->getNomUrl(1, 'blank', 1);
	} else {
		print $object->getNomUrl(1, 'blank', 1);
	}
	print '</td></tr>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	print '<tr><td>'.$langs->trans("Description").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="description" id="description">';
	print '<textarea name="description" id="description" class="minwidth400" rows="'.ROWS_3.'">'.'</textarea>'."\n";
	print '</td></tr>';

	print '<input hidden class="flat" type="text" size="36" name="element_type" value="'.$element_type.'">';
	print '<input hidden class="flat" type="text" size="36" name="fk_parent" value="'.$fk_parent.'">';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" id ="actionButtonCreate" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelCreate" class="button" name="cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($title_edit, '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	unset($object->fields['status']);
	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	if ($id != $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) {
		print '<tr><td>'.$langs->trans("ParentElement").'</td><td>';
		print $object->select_digiriskelement_list($object->fk_parent, 'fk_parent', 'element_type="groupment"', '',  0, 0, array(), '',  0,  0,  'minwidth100',  GETPOST('id'),  false);
	}

	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"  onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}

if (!$object->id) {
	$object->ref    = $conf->global->MAIN_INFO_SOCIETE_NOM;
	$object->label  = $langs->trans('Society');
	$object->entity = $conf->entity;
	unset($object->fields['element_type']);
}

// Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create'))) {

	$formconfirm = '';
	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteDigiriskElement'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}


	print $formconfirm;
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);

	print dol_get_fiche_head($head, 'elementCard', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	$trash_list = $object->fetchDigiriskElementFlat($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);

	if ($trash_list < 0 || empty($trash_list)) {
		$trash_list = array();
	}

	// Object card
	// ------------------------------------------------------------
	$width = 80; $cssclass = 'photoref';

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
	if (isset($object->element_type)) {
		$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';
	} else {
		$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';
	}

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">'."\n";

	if ($object->id) {
		print '<tr><td class="titlefield">' . $langs->trans("ElementType") . '</td><td>';
		print $langs->trans($object->element_type);
		print '</td></tr>';

		print '<div class="titlefield hidden elementID" id="elementID" value="'.$object->id.'">'.$langs->trans("ID").'</div>';
		print '<tr><td class="titlefield">'.$langs->trans("ParentElement").'</td><td>';
		$parent_element = new DigiriskElement($db);
		$result = $parent_element->fetch($object->fk_parent);
		if ($result > 0) {
			print $parent_element->ref . ( !empty($parent_element->label) ?  ' - ' . $parent_element->label : '');
		}
		else {
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
		}

		print '</td></tr>';
	}

	//Show common fields
//	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';

	print '<table class="border tableforfield" width="100%">';

	// label
	print '<td class="titlefield tdtop">'.$langs->trans("label").'</td><td>';
	print dol_htmlentitiesbr($object->label);
	print '</td></tr>';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Modify
			if ($permissiontoadd) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>' . "\n";
			}

			if ($permissiontodelete && !array_key_exists($object->id, $trash_list) && $object->id != $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH){
				print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete">'.$langs->trans("Delete").'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("CanNotDoThis").'">'.$langs->trans('Delete').'</a>';
			}

		}
		print '</div>' . "\n";

		// Document Generation -- Génération des documents
		$includedocgeneration = 1;
		if ($includedocgeneration) {
			print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';

			$objref = dol_sanitizeFileName($object->ref);
			$dir_files = $digiriskelementdocument->element . '/' . $objref;
			$filedir = $upload_dir . '/' . $dir_files;
			$urlsource = $_SERVER["PHP_SELF"] . '?id='. $id;

			if ($digiriskelementdocument->element == 'groupmentdocument') {
				$modulepart = 'digiriskdolibarr:GroupmentDocument';
				$defaultmodel = $conf->global->DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL;
				$title = $langs->trans('GroupmentDocument');
			} elseif ($digiriskelementdocument->element == 'workunitdocument') {
				$modulepart = 'digiriskdolibarr:WorkUnitDocument';
				$defaultmodel = $conf->global->DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL;
				$title = $langs->trans('WorkUnitDocument');
			}

			print digiriskshowdocuments($modulepart, $dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, $defaultmodel, 1, 0, 28, 0, '', $title, '', $langs->defaultlang, '', $digiriskelementdocument);
		}


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="' . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_agenda.php', 1) . '?id=' . $object->id . '">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'digiriskelement@digiriskdolibarr', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}
}

// End of page
llxFooter();
$db->close();
