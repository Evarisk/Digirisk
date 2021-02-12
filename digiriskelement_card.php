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
dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');
dol_include_once('/digiriskdolibarr/class/groupment.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');
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
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd)
	{
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'DIGIRISKELEMENT_MODIFY');
	}
	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
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

// Part to create
if ($action == 'create')
{
	print load_fiche_titre($title_create, '', 'object_'.$object->picto, '', '', 'digitest');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	dol_fiche_head(array(), '');

	unset($object->fields['ref']);
	unset($object->fields['status']);
	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	//TODO require les models avec une variable
	$type = 'DIGIRISKDOLIBARR_'.strtoupper($element_type).'_ADDON';
	$digirisk_addon = $conf->global->$type;
	$modele = new $digirisk_addon($db);

	print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="ref" id="ref" value="'.$modele->getNextValue($object).'">';
	print $modele->getNextValue($object);
	print '</td></tr>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	print '<input hidden class="flat" type="text" size="36" name="element_type" value="'.$element_type.'">';
	print '<input hidden class="flat" type="text" size="36" name="fk_parent" value="'.$fk_parent.'">';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" id ="actionButtonCreate" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';

	print '<input type="'.($backtopage ? "submit" : "button").'" id ="actionButtonCancelCreate" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';

}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($title_edit, '', 'object_'.$object->picto, '', '', 'digitest');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	dol_fiche_head();

	unset($object->fields['status']);
	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);

	dol_fiche_head($head, 'elementCard', $langs->trans("DigiriskElement"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteDigiriskElement'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';
	$width = 80; $cssclass = 'photoref';
	$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type).'</div>';

	$object->digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";


	print '<tr><td class="titlefield">'.$langs->trans("ElementType").'</td><td>';
	print $langs->trans($object->element_type);
	print '</td></tr>';

	print '<div class="titlefield hidden elementID" id="elementID" value="'.$object->id.'">'.$langs->trans("ID").'</div>';
	print '<tr><td class="titlefield">'.$langs->trans("ParentElement").'</td><td>';
	$parent_element = new DigiriskElement($db);
	$result = $parent_element->fetch($object->fk_parent);
	if ($result > 0) {
		print $parent_element->ref . ( !empty($parent_element->description) ?  ' - ' . $parent_element->description : '');
	}
	else {
		print $conf->global->MAIN_INFO_SOCIETE_NOM;
	}
	print '</td></tr>';

	//Show common fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();

	/*
	 * Lines
	 */

	if (!empty($object->table_element_line))
	{
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
		{
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines))
		{
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines')
		{
			if ($action != 'editline')
			{
				// Add products/services form
				$object->formAddObjectLine(1, $mysoc, $soc);

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
		{
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction" >'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook))
		{
			// Modify
			if ($permissiontoadd)
			{
				print '<a class="butAction" id="actionButtonEdit" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit">'.$langs->trans("Modify").'</a>'."\n";
			}
			else
			{
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
			}

//			// Send
//			if (empty($user->socid)) {
//				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>'."\n";
//			}

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd))
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>'."\n";
			}
			else
			{
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
			}
		}
		print '</div>'."\n";
	}

	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Document Generation -- Génération des documents

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;
		// Documents
		if ($includedocgeneration) {

			$objref = dol_sanitizeFileName($object->ref);

			$relativepath = $objref . '/' . $objref . '.pdf';
			$dir_files = $object->element_type . '/' . $objref;
			$filedir = $conf->digiriskdolibarr->dir_output.'/'.$dir_files;

			$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
			$genallowed = $user->rights->digiriskdolibarr->digiriskelement->read;	// If you can read, you can build the PDF to read content
			$delallowed = $user->rights->digiriskdolibarr->digiriskelement->create;	// If you can create/edit, you can remove a file on card

			if ( $object->element_type == 'groupment' ) {
				$modulepart = 'digiriskdolibarr:Groupment';
			} else {
				$modulepart = 'digiriskdolibarr:WorkUnit';
			}

			print $formfile->showdocuments($modulepart,$dir_files, $filedir, $urlsource, $genallowed, $delallowed, $conf->global->DIGIRISKDOLIBARR_GROUPMENT_DEFAULT_MODEL, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang, '');

		}

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

}

// End of page
llxFooter();
$db->close();
