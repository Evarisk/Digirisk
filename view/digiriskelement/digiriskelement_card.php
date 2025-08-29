<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once __DIR__ . '/../../class/digiriskdocuments.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskelement/groupment.class.php';
require_once __DIR__ . '/../../class/digiriskelement/workunit.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/groupmentdocument.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/workunitdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$element_type        = GETPOST('element_type', 'alpha');
$fkParent            = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object           = new DigiriskElement($db);
$extrafields      = new ExtraFields($db);
$digiriskstandard = new DigiriskStandard($db);
$project          = new Project($db);

$object->fetch($id);

if ( $object->element_type == 'groupment') {
	$document = new GroupmentDocument($db);
} elseif (  $object->element_type == 'workunit' ) {
	$document = new WorkUnitDocument($db);
}

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('digiriskelementcard', 'digiriskelementview','globalcard')); // Note that conf->hooks_modules contains array

$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiriskelement->delete;

saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=1', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage                                                                              = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
		}
	}

	// Action to add record
	include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

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

    $object->element = $object->element_type;

	// Actions builddoc, forcebuilddoc, remove_file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

	// Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    $object->element = 'digiriskelement';

	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes") {
		$object->fetch($id);
		$result = $object->delete($user);

		if ($result > 0) {
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header('Location: ' . $backurlforlist);
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
$formconfirm = '';

$parameters                        = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook                           = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

if ( $object->element_type == 'groupment' ) {
	$title         = $langs->trans("Groupment");
	$titleCreate   = $langs->trans("NewGroupment");
	$titleEdit     = $langs->trans("ModifyGroupment");
} elseif ( $object->element_type == 'workunit' ) {
	$title         = $langs->trans("WorkUnit");
	$titleCreate   = $langs->trans("NewWorkUnit");
	$titleEdit     = $langs->trans("ModifyWorkUnit");
} else {
	$element_type = GETPOST('element_type', 'alpha');
	if ( $element_type == 'groupment' ) {
		$title = $langs->trans("NewGroupment");
	} else {
		$title = $langs->trans("NewWorkUnit");
	}
}

$deletedElements = $object->getMultiEntityTrashList();
if (empty($deletedElements)) {
	$deletedElements = [0];
}

$helpUrl = 'FR:Module_Digirisk#Cr.C3.A9ation_UT_et_GP';

digirisk_header($title, $helpUrl); ?>

<div id="cardContent" value="">

<?php // Part to create
if ($action == 'create') {
	$object->fetch($fkParent);
	print load_fiche_titre($title, '', $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	unset($object->fields['ref']);
	unset($object->fields['status']);
	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);
	unset($object->fields['description']);
	unset($object->fields['show_in_selector']);

	print '<table class="border centpercent tableforfieldcreate">' . "\n";

	print '<tr><td class="fieldrequired">' . $langs->trans("ParentElement") . '</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="parent" id="parent">';
	if (empty($fkParent)) {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		print $digiriskstandard->getNomUrl(1, 'blank', 0, '', -1, 1);
	} else {
		print $object->getNomUrl(1, 'blank', 0, '', -1, 1);
	}
	print '</td></tr>';

	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

	print '<tr><td>' . $langs->trans("Description") . '</td><td>';
	print '<input hidden class="flat" type="text" size="36" name="description" id="description">';
	print '<textarea name="description" id="description" class="minwidth400" rows="' . ROWS_3 . '">' . '</textarea>' . "\n";
	print '</td></tr>';

	print '<input hidden class="flat" type="text" size="36" name="element_type" value="' . $element_type . '">';
	print '<input hidden class="flat" type="text" size="36" name="fk_parent" value="' . $fkParent . '">';

	print '<tr><td>' . $langs->trans("ShowInSelectOnPublicTicketInterface") . '</td><td>';
	print '<input type="checkbox" id="show_in_selector" name="show_in_selector" checked="checked">';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>' . "\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" id ="actionButtonCreate" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print '&nbsp; ';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelCreate" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($titleEdit, '', $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	unset($object->fields['status']);
	unset($object->fields['element_type']);
	unset($object->fields['fk_parent']);
	unset($object->fields['last_main_doc']);
	unset($object->fields['entity']);
	unset($object->fields['show_in_selector']);

	print '<table class="border centpercent tableforfieldedit">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	print '<tr><td>';
	print $langs->trans("ShowInSelectOnPublicTicketInterface");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="show_in_selector" name="show_in_selector"' . (($object->show_in_selector == 0) ?  '' : ' checked=""') . '"> ';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	if ($id != $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) {
        $children         = $object->fetchDigiriskElementFlat($id);
        $childrenElements = [];
        if (is_array($children) && !empty($children)) {
            foreach ($children as $key => $value) {
                $childrenElements[$key] .= $key;
            }
        }
        print '<tr><td>' . $langs->trans("ParentElement") . '</td><td>';
		print $object->selectDigiriskElementList($object->fk_parent, 'fk_parent', ['customsql' => 'element_type="groupment" AND t.rowid NOT IN (' . rtrim(implode(',', $deletedElements) . ',' . implode(',', $childrenElements), ',') . ')'], 0, 0, [], 0, 0, 'minwidth100 maxwidth300', GETPOST('id'));
	}

	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" id ="actionButtonSave" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" id ="actionButtonCancelEdit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '"  onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}

if ( ! $object->id) {
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
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}


	print $formconfirm;
	$res = $object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);

	$trashList = $object->fetchDigiriskElementFlat($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);

	if ($trashList < 0 || empty($trashList)) {
		$trashList = [];
	}

	// Object card
	// ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

	saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefield">';
	print $langs->trans("ShowInSelectOnPublicTicketInterface");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="show_in_selectorshow_in_selector" name="show_in_selectorshow_in_selector"' . (($object->show_in_selector == 0) ?  '' : ' checked=""') . '" disabled> ';
	print '</td></tr>';

	print '<tr class="linked-medias digirisk-element-photo-'. $object->id .'"><td class=""><label for="photos">' . $langs->trans("Photo") . '</label></td><td class="linked-medias-list" style="display: flex; gap: 10px; height: auto;">';
	print '<span class="add-medias" '. (($object->status == $object::STATUS_VALIDATED) ? "" : "style='display:none'") . '>';
	print '<input hidden multiple class="fast-upload" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">';
	print '<label for="fast-upload-photo-default">';
	print '<div title="'. $langs->trans('AddPhotoFromComputer') .'" class="wpeo-button button-square-50">';
	print '<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>';
	print '</div>';
	print '</label>';
	print '&nbsp';
	print '<input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?>"/>';
	print '<div title="'. $langs->trans('AddPhotoFromMediaGallery') .'" class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">';
	print '<input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="'. $object->id .'" data-from-type="'. $object->element_type .'" data-from-subtype="photo" data-from-subdir="" data-photo-class="digirisk-element-photo-'. $object->id .'"/>';
	print '<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>';
	print '</div>';
	print '</span>';
	print '&nbsp';
    print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref, 'small', 5, 0, 0, 0, 50, 50, 0, 0, 0, $object->element_type . '/'. $object->ref . '/', $object, 'photo', $object->status == $object::STATUS_VALIDATED, $permissiontodelete && $object->status == $object::STATUS_VALIDATED);
	print '</td></tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >' . "\n";
		$parameters = [];
		$reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Modify
			if ($permissiontoadd) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>' . "\n";
			}

			if ($permissiontodelete && ! array_key_exists($object->id, $trashList) && $object->id != $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) {
				print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token='.newToken().'">' . $langs->trans("Delete") . '</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="' . $langs->trans("CanNotDoThis") . '">' . $langs->trans('Delete') . '</a>';
			}
		}
		print '</div>' . "\n";

		// Document Generation -- Génération des documents
		print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';

		$objref    = dol_sanitizeFileName($object->ref);
		$dirFiles  = $document->element . '/' . $objref;
		$filedir   = $upload_dir . '/' . $dirFiles;
		$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $id;

		if ($document->element == 'groupmentdocument') {
			$modulepart   = 'digiriskdolibarr:GroupmentDocument';
			$defaultmodel = $conf->global->DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL;
			$title        = $langs->trans('GroupmentDocument');
		} elseif ($document->element == 'workunitdocument') {
			$modulepart   = 'digiriskdolibarr:WorkUnitDocument';
			$defaultmodel = $conf->global->DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL;
			$title        = $langs->trans('WorkUnitDocument');
		}

		if ($permissiontoadd || $permissiontoread) {
			$genallowed = 1;
		}

		print saturne_show_documents($modulepart, $dirFiles, $filedir, $urlsource, 1,1, '', 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object);


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlright  = '<a href="' . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_agenda.php', 1) . '?id=' . $object->id . '">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions    = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'digiriskelement@digiriskdolibarr', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}
}

// End of page
llxFooter();
$db->close();
