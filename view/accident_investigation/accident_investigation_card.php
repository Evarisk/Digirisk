<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 *  \file    view/accident_investigation.php
 *  \ingroup digiriskdolibarr
 *  \brief   Tab of accident investigation on generic element
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

// Load DigiriskDolibarr librairies
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/accident_investigation.class.php';
require_once __DIR__ . '/../../class/digiriskdocuments/accidentinvestigationdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident_investigation.lib.php';

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accidentinvestigationcard'; // To manage different context of search
$cancel              = GETPOST('cancel', 'aZ09');
$confirm             = GETPOST('confirm', 'aZ09');
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$accident   = new Accident($db);
$object     = new AccidentInvestigation($db);
$document   = new AccidentInvestigationDocument($db);
$project    = new Project($db);
$task       = new Task($db);
$signatory  = new SaturneSignature($db);

$numRefConf = strtoupper($task->element) . '_ADDON';

$numberingModuleName = [
	'project/task' => $conf->global->$numRefConf,
];
list($modTask) = saturne_require_objects_mod($numberingModuleName);

// Initialize view objects
$form        = new Form($db);
$formcompany = new FormCompany($db);

$hookmanager->initHooks(['accidentinvestigationcard', 'digiriskdolibarrglobal', 'globalcard']); // Note that conf->hooks_modules contains array

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

$upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity ?? 1];

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once.

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->accident_investigation->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident_investigation->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident_investigation->delete;
saturne_check_access($permissiontoread);

/*
*  Actions
*/

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $objectLinked may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/accident_investigation/accident_investigation_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/digiriskdolibarr/view/accident_investigation/accident_investigation_card.php', 1) . '?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($action == 'confirm_set_validate') {
		$result = $object->validate($user);

		if ($result > 0) {
			setEventMessages('AccidentInvestigationValidated', []);

			if ($object->fk_task <= 0 || empty($object->fk_task)) {
				$accident->fetch($object->fk_accident);

				$task->fk_project = $accident->fk_project;

				$task->ref        = $modTask->getNextValue(0, $task);
				$task->label      = $accident->ref . ' - ' . $accident->label;
				$result           = $task->create($user);

				if ($result > 0) {
					$object->fk_task = $result;
					$object->update($user, true);

					$task->fk_project     = $accident->fk_project;
					$task->ref            = $modTask->getNextValue(0, $task);
					$task->label          = $accident->ref . ' - T1 - ' . $langs->trans('CurativeAction');
					$task->fk_task_parent = $result;
					$resOne               = $task->create($user);

					$task->fk_project     = $accident->fk_project;
					$task->ref            = $modTask->getNextValue(0, $task);
					$task->label          = $accident->ref . ' - T2 - ' . $langs->trans('PreventiveAction');
					$task->fk_task_parent = $result;
					$resTwo               = $task->create($user);

					if ($resOne > 0 && $resTwo > 0) {
						setEventMessages('AccidentInvestigationTaskCreated', []);
					} else {
						setEventMessages($task->error, [], 'errors');
					}
				} else {
					setEventMessages($task->error, [], 'errors');
				}
			}
		} else {
			setEventMessages($object->error, [], 'errors');
		}
		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
		exit();
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	require_once DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

	// Actions builddoc, forcebuilddoc, remove_file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

	// Action to generate pdf from odt file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

	// Action confirm_lock, confirm_archive.
	require_once __DIR__ . '/../../../saturne/core/tpl/signature/signature_action_workflow.tpl.php';
}

/*
*	View
*/

$title   = $langs->trans('AccidentInvestigation');
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

if ($conf->browser->layout == 'phone') {
	$onPhone = 1;
} else {
	$onPhone = 0;
}

saturne_header(1,'', $title, $helpUrl);

if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewAccidentInvestigation"), '', $object->picto);

	print dol_get_fiche_head();

	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'. $backtopageforcancel . '">';
	}

	print '<table class="border centpercent tableforfieldcreate">';

	$conf->tzuserinputkey = 'tzuser';

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	print '</table></br>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Create');
} else if ($id > 0 && $action == 'edit') {
	print load_fiche_titre($langs->trans("UpdateAccidentInvestigation"), '', $object->picto);

	print dol_get_fiche_head();

	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'. $backtopageforcancel . '">';
	}

	print '<table class="border centpercent tableforfieldupdate">';

	$conf->tzuserinputkey = 'tzuser';

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	print '</table></br>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Update');
} else if ($id > 0 || (!empty($ref) && empty($action))) {
	$object->fetch($id);

	saturne_get_fiche_head($object, 'accidentinvestigation', $title);
	saturne_banner_tab($object);

	$formConfirm = '';

	// Draft confirmation
	if (($action == 'set_draft' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ReOpenObject', $langs->transnoentities('The' . ucfirst($object->element))),  $langs->trans('ConfirmReOpenObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_setdraft', '', 'yes', 'actionButtonInProgress', 350, 600);
	}
	// Validate confirmation
	if (($action == 'set_validate' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ValidateObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmValidateObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_set_validate', '', 'yes', 'actionButtonValidate', 350, 600);
	}
	// Lock confirmation
	if (($action == 'set_lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
	}
	// Archive confirmation
	if (($action == 'set_archive' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ArchiveObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmArchiveObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_archive', '', 'yes', 'actionButtonArchive', 350, 600);
	}
	// Delete confirmation
	if ($action == 'delete') {
		$formConfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
	}
	// Remove file confirmation
	if ($action == 'removefile') {
		$formConfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&file=' . GETPOST('file') . '&entity=' . $conf->entity, $langs->trans('RemoveFileObject'), $langs->trans('ConfirmRemoveFileObject', GETPOST('file')), 'remove_file', '', 'yes', 1, 350, 600);
	}

	// Call Hook formConfirm.
	$parameters = ['formConfirm' => $formConfirm];
	$reshook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook.
	if (empty($reshook)) {
		$formConfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formConfirm = $hookmanager->resPrint;
	}

	// Print form confirm.
	print $formConfirm;

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	print '<tr class="linked-medias causality_tree question-table"><td class=""><label for="causality_tree">' . $langs->trans("CausalityTree") . '</label></td><td class="linked-medias-list">';
	$pathPhotos = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accident_investigation/'. $object->ref . '/causality_tree/';
	$fileArray  = dol_dir_list($pathPhotos, 'files');
	?>
	<span class="add-medias" <?php echo ($object->status != AccidentInvestigation::STATUS_LOCKED && empty($object->causality_tree)) ? '' : 'style="display:none"' ?>>
		<input hidden multiple class="fast-upload" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">
		<label for="fast-upload-photo-default">
			<div class="wpeo-button button-square-50">
				<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
			</div>
		</label>
		<input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->causality_tree ?>"/>
		<div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">
			<input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $object->id?>" data-from-type="accident_investigation" data-from-subtype="causality_tree" data-from-subdir="causality_tree"/>
			<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
		</div>
	</span>
	<?php
	$relativepath = 'digiriskdolibarr/medias/thumbs';
	print saturne_show_medias_linked('digiriskdolibarr', $pathPhotos, 'small', 1, 0, 0, 0, 50, 50, 0, 0, 0, 'accident_investigation/'. $object->ref . '/causality_tree/', $object, 'causality_tree', $object->status < AccidentInvestigation::STATUS_LOCKED, $permissiontodelete && $object->status < AccidentInvestigation::STATUS_LOCKED);
	print '</td></tr>';

	print '</table></div>';

	print '<div class="clearboth"></div>';

	if ($action != 'presend') {
		print '<div class="tabsAction">';

		// Edit
		$displayButton = $onPhone ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->trans('Modify');
		if ($object->status == $object::STATUS_DRAFT) {
			print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit' . '">' . $displayButton . '</a>';
		} else {
			print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
		}

		// Validate.
		$displayButton = $onPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Validate');
		if ($object->status == AccidentInvestigation::STATUS_DRAFT) {
			print '<span class="butAction" id="actionButtonValidate" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_validate&token=' . newToken() . '">' . $displayButton . '</span>';
		} else {
			print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
		}

		// ReOpen.
		$displayButton = $onPhone ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->trans('ReOpenDoli');
		if ($object->status == AccidentInvestigation::STATUS_VALIDATED) {
			print '<span class="butAction" id="actionButtonInProgress" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_draft&token=' . newToken() . '">' . $displayButton . '</span>';
		} else {
			print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
		}

		// Lock.
		$displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
		if ($object->status == AccidentInvestigation::STATUS_VALIDATED) {
			print '<span class="butAction" id="actionButtonLock" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_lock&token=' . newToken() . '">' . $displayButton . '</span>';
		} else {
			print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
		}

		// Archive.
		$displayButton = $onPhone ?  '<i class="fas fa-archive fa-2x"></i>' : '<i class="fas fa-archive"></i>' . ' ' . $langs->trans('Archive');
		if ($object->status == AccidentInvestigation::STATUS_LOCKED) {
			print '<span class="butAction" id="actionButtonArchive" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_archive&token=' . newToken() . '">' . $displayButton . '</span>';
		} else {
			print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
		}

		// Clone.
		$displayButton = $onPhone ? '<i class="fas fa-clone fa-2x"></i>' : '<i class="fas fa-clone"></i>' . ' ' . $langs->trans('ToClone');
		print '<span class="butAction" id="actionButtonClone">' . $displayButton . '</span>';

		// Delete (need delete permission, or if draft, just need create/modify permission).
		$displayButton = $onPhone ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->trans('Delete');
		print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete || ($object->status == AccidentInvestigation::STATUS_DRAFT));

		print '</div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';

		// Documents.
		$objRef    = dol_sanitizeFileName($object->ref);
		$dirFiles  = $object->element . 'document/' . $objRef;
		$fileDir   = $upload_dir . '/' . $dirFiles;
		$urlSource = $_SERVER['PHP_SELF'] . '?id=' . $object->id;

		print saturne_show_documents('digiriskdolibarr:AccidentInvestigationDocument', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, $conf->global->DIGIRISKDOLIBARR_ACCIDENTINVESTIGATION_DOCUMENT_DEFAULT_MODEL, 1, 0, 0, 0, 0, '', '', $langs->defaultlang, '', $object, 0, 'removefile', (($object->status > $object::STATUS_DRAFT) ? 1 : 0), $langs->trans('ObjectMustBeValidatedToGenerate', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))));

		print '</div><div class="fichehalfright">';

		// List of actions on element.
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formActions = new FormActions($db);
		$formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10);

		print '</div></div>';
	}
}

// End of page
llxFooter();
$db->close();
