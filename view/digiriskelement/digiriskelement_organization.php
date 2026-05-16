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
 *   	\file       view/digiriskelement/digiriskelement_organization.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to organize arborescence
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);
// Get parameters
$id                  = GETPOSTINT('id');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object      = new DigiriskElement($db);
$extrafields = new ExtraFields($db);

$object->fetch($id);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('digiriskelementcard', 'globalcard')); // Note that conf->hooks_modules contains array

//Security check
$permissiontoread = $user->rights->digiriskdolibarr->digiriskelement->read;

saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = array();
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

	if ($action == 'saveOrganization') {
		$ids              = GETPOST('ids');
		$parentIds        = GETPOST('parent_ids');
		$arrayIds         = preg_split('/,/', $ids);
		$arrayParentIds   = preg_split('/,/', $parentIds);
		$i                = 0;

		if ( ! empty($arrayIds) && $arrayIds > 0) {
			foreach ($arrayIds as $id) {
				$digiriskelement = new DigiriskElement($db);
				$digiriskelement->fetch((int) $id);
				$digiriskelement->ranks     = $i + 1;
				$digiriskelement->fk_parent = $arrayParentIds[$i];

				$digiriskelement->update($user);
				$i++;
			}
		}
	}

	// Action AJAX pour renommer un élément inline
	if ($action == 'renameElement') {
		$data = json_decode(file_get_contents('php://input'), true);
		$elementId = (int) $data['elementId'];
		$newLabel  = trim($data['label']);

		header('Content-Type: application/json');

		if ($elementId > 0 && !empty($newLabel)) {
			$digiriskelement = new DigiriskElement($db);
			$digiriskelement->fetch($elementId);
			$digiriskelement->label = $newLabel;
			$result = $digiriskelement->update($user);

			if ($result > 0) {
				echo json_encode(['status' => 'success', 'label' => $newLabel]);
			} else {
				echo json_encode(['status' => 'error', 'message' => $digiriskelement->error]);
			}
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
		}
		exit;
	}

	// Action pour créer un élément rapidement via le dialog
	if ($action == 'quickCreateElement') {
		$elementType  = GETPOST('element_type', 'alpha');
		$fkParent     = GETPOSTINT('fk_parent');
		$label        = trim(GETPOST('label', 'alphanohtml'));
		$description  = trim(GETPOST('description', 'restricthtml'));
		$showInSelect = GETPOSTINT('show_in_selector');

		if (!empty($label) && $fkParent > 0 && in_array($elementType, ['groupment', 'workunit'])) {
			$digiriskelement = new DigiriskElement($db);
			$digiriskelement->element_type = $elementType;
			$digiriskelement->label        = $label;
			$digiriskelement->description  = $description;
			$digiriskelement->fk_parent    = $fkParent;
			$digiriskelement->show_in_selector = $showInSelect;
			$digiriskelement->entity       = $conf->entity;
			$digiriskelement->status       = 1;

			$result = $digiriskelement->create($user);

			if ($result > 0) {
				setEventMessages($langs->trans('ObjectCreated', $digiriskelement->ref), null, 'mesgs');
			} else {
				setEventMessages($digiriskelement->error, $digiriskelement->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Label')), null, 'errors');
		}

		header('Location: ' . $_SERVER['PHP_SELF'] . '?mainmenu=' . GETPOST('mainmenu', 'alpha') . '&leftmenu=' . GETPOST('leftmenu', 'alpha'));
		exit;
	}

	// Action pour supprimer un élément depuis l'arborescence
	if ($action == 'deleteElement') {
		$deleteId = GETPOSTINT('deleteid');

		if ($deleteId > 0 && $user->rights->digiriskdolibarr->digiriskelement->delete) {
			$digiriskelement = new DigiriskElement($db);
			$result = $digiriskelement->fetch($deleteId);

			if ($result > 0) {
				$deleteResult = $digiriskelement->delete($user);
				if ($deleteResult > 0) {
					setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
				} else {
					setEventMessages($digiriskelement->error, $digiriskelement->errors, 'errors');
				}
			} else {
				setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
			}
		} else {
			setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
		}

		header('Location: ' . $_SERVER['PHP_SELF'] . '?mainmenu=' . GETPOST('mainmenu', 'alpha') . '&leftmenu=' . GETPOST('leftmenu', 'alpha'));
		exit;
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

$helpUrl  = 'FR:Module_Digirisk';
$title    = $langs->trans('DigiriskElementOrganization');

saturne_header(0, '', $title, $helpUrl);

?>
<div id="cardContent" value="">
<?php
$objects = $object->fetchAll('',  'ranks',  0,  0, array('customsql' => 'status > 0 AND t.entity = ' . $conf->entity));
if (is_array($objects)) {
	$results = recurse_tree(0, 0, $objects);
} else {
	$results = array();
}

$risk = new Risk($db);
$riskInfos = $risk->loadRiskInfos([]);


if (!empty($results)) :
?>
    <script>
        $(document).ready(function() {

            $('.space').sortable({
                connectWith:'.space:not("'+'.workunit'+'")',
                tolerance:'intersect',
                handle: '.drag-handle',
                receive:function(event, ui){
                    // Mettre à jour les chevrons après un drop
                    updateChevrons();

                    // Auto-sauvegarde immédiate
                    var token = window.saturne.toolbox.getToken();
                    var idArray = [];
                    var parentArray = [];
                    $('.route').each(function() {
                        idArray.push($(this).attr('id'));
                        parentArray.push($(this).parent('ul').attr('id').split(/space/)[1]);
                    });

                    // Feedback visuel
                    var $indicator = $('.unsaved-indicator');
                    $indicator.addClass('visible').removeClass('saved');
                    $indicator.html('<i class="fas fa-spinner fa-spin"></i> <?= dol_string_nohtmltag($langs->trans("SavingInProgress")) ?>');

                    $.ajax({
                        url: document.URL + '&action=saveOrganization&ids=' + idArray.toString() + '&parent_ids=' + parentArray + '&token=' + token,
                        success: function() {
                            $indicator.addClass('saved');
                            $indicator.html('<i class="fas fa-check-circle"></i> <?= dol_string_nohtmltag($langs->trans("Saved")) ?>');
                            setTimeout(function() {
                                $indicator.removeClass('visible saved');
                            }, 2000);
                        },
                        error: function() {
                            $indicator.html('<i class="fas fa-exclamation-triangle"></i> <?= dol_string_nohtmltag($langs->trans("ErrorSaving")) ?>');
                            setTimeout(function() {
                                $indicator.removeClass('visible');
                            }, 4000);
                        }
                    });
                },
            });

            // Mise à jour dynamique des chevrons après drag & drop
            function updateChevrons() {
                $('.groupment').each(function() {
                    var $row = $(this);
                    var $chevronDiv = $row.find('.chevron');
                    var $space = $row.siblings('.space').first();
                    if (!$space.length) {
                        $space = $row.closest('li.route').find('> ul.space');
                    }
                    var hasChildren = $space.children('li.route').length > 0;
                    var $icon = $chevronDiv.find('i');

                    if (hasChildren) {
                        $icon.removeClass('chevron-empty fa-chevron-right').addClass('fa-chevron-down toggle-children');
                        $icon.css('pointer-events', '');
                    } else {
                        $icon.removeClass('toggle-children fa-chevron-down').addClass('fa-chevron-right chevron-empty');
                        $icon.css('pointer-events', 'none');
                    }
                });
            }

            $('.space').disableSelection();

            // Toggle children
            $(document).on('click', '.toggle-children', function() {
                var $icon = $(this);
                var $children = $icon.closest('.row-container').siblings('.space');
                
                $children.slideToggle(200, function() {
                    if ($children.is(':visible')) {
                        $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    } else {
                        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    }
                });
            });

            // Inline editing des noms GP/UT
            $(document).on('click', '.row-container .element-label', function(e) {
                e.stopPropagation();
                var $label = $(this);

                // Empêcher la double activation
                if ($label.find('input').length) return;

                var currentText = $label.text().trim();
                var elementId = $label.closest('.route').attr('id');
                var $input = $('<input type="text" class="inline-edit-input" />').val(currentText);

                $label.html($input);
                $input.focus().select();

                // Sauvegarder au blur ou Enter
                function saveLabel() {
                    var newLabel = $input.val().trim();
                    if (!newLabel || newLabel === currentText) {
                        $label.text(currentText);
                        return;
                    }

                    var token = window.saturne.toolbox.getToken();
                    $label.text(newLabel).css('opacity', '0.5');

                    $.ajax({
                        url: document.URL + '&action=renameElement&token=' + token,
                        type: 'POST',
                        data: JSON.stringify({ elementId: elementId, label: newLabel }),
                        contentType: 'application/json',
                        success: function(response) {
                            if (response.status === 'success') {
                                $label.css({'opacity': '1', 'color': '#47e58e'});
                                setTimeout(function() { $label.css('color', ''); }, 800);
                            } else {
                                $label.text(currentText).css({'opacity': '1', 'color': '#e05353'});
                                setTimeout(function() { $label.css('color', ''); }, 1500);
                            }
                        },
                        error: function() {
                            $label.text(currentText).css({'opacity': '1', 'color': '#e05353'});
                            setTimeout(function() { $label.css('color', ''); }, 1500);
                        }
                    });
                }

                $input.on('blur', saveLabel);
                $input.on('keydown', function(e) {
                    if (e.key === 'Enter') { e.preventDefault(); $input.blur(); }
                    if (e.key === 'Escape') { $label.text(currentText); }
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        $input.off('blur'); // Éviter le double-save
                        saveLabel();
                        // Trouver tous les labels visibles et naviguer
                        var $allLabels = $('.element-label:visible');
                        var currentIndex = $allLabels.index($label);
                        var nextIndex = e.shiftKey ? currentIndex - 1 : currentIndex + 1;
                        if (nextIndex >= 0 && nextIndex < $allLabels.length) {
                            setTimeout(function() { $allLabels.eq(nextIndex).trigger('click'); }, 50);
                        }
                    }
                });
            });

            // Pop-up d'ajout rapide GP/UT via <dialog> natif
            $(document).on('click', '.quick-add-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn = $(this);
                var dialog = document.getElementById('quick-add-dialog');

                $('#quick-add-type-input').val($btn.data('type'));
                $('#quick-add-fk-parent-input').val($btn.data('parent-id'));
                $('#quick-add-parent-info').text($btn.data('parent-ref') + ' - ' + $btn.data('parent-label'));
                $('#quick-add-dialog-title').html($btn.data('type') === 'groupment' ? '<?= $langs->trans("NewGroupment") ?>' : '<?= $langs->trans("NewWorkUnit") ?>');
                $('#quick-add-label-input').val('');
                $('#quick-add-description-input').val('');
                $('#quick-add-selector-input').prop('checked', true);

                dialog.showModal();
                setTimeout(function() { $('#quick-add-label-input').focus(); }, 50);
            });

            // Suppression d'un élément avec confirmation via dialog
            $(document).on('click', '.delete-element-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var ref = $(this).data('ref');
                var id = $(this).data('id');
                var dialog = document.getElementById('confirm-delete-dialog');
                $('#confirm-delete-ref').html(ref);
                $('#confirm-delete-yes').data('id', id);
                dialog.showModal();
            });

            // Confirmation de suppression
            $(document).on('click', '#confirm-delete-yes', function() {
                var id = $(this).data('id');
                window.location.href = '<?= $_SERVER['PHP_SELF'] ?>' +
                    '?action=deleteElement&deleteid=' + id + '&token=<?= newToken() ?>' +
                    '&mainmenu=<?= GETPOST("mainmenu", "alpha") ?>&leftmenu=<?= GETPOST("leftmenu", "alpha") ?>';
            });
        })
    </script>

    <!-- Dialog natif d'ajout rapide via form POST standard -->
    <dialog id="quick-add-dialog">
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] . '?action=quickCreateElement&token=' . newToken() ?>">
            <h3 id="quick-add-dialog-title" class="modal-title"></h3>
            <input type="hidden" name="element_type" id="quick-add-type-input" />
            <input type="hidden" name="fk_parent" id="quick-add-fk-parent-input" />
            <table class="border centpercent">
                <tr>
                    <td class="fieldrequired"><?= $langs->trans('ParentElement') ?></td>
                    <td><span id="quick-add-parent-info" class="parent-info"></span></td>
                </tr>
                <tr>
                    <td class="fieldrequired"><?= $langs->trans('Label') ?></td>
                    <td><input type="text" name="label" id="quick-add-label-input" class="flat minwidth300" required /></td>
                </tr>
                <tr>
                    <td><?= $langs->trans('Description') ?></td>
                    <td><textarea name="description" id="quick-add-description-input" class="flat minwidth300" rows="3"></textarea></td>
                </tr>
                <tr>
                    <td><?= $langs->trans('ShowInSelectOnPublicTicketInterface') ?></td>
                    <td><input type="checkbox" name="show_in_selector" id="quick-add-selector-input" value="1" checked /></td>
                </tr>
            </table>
            <div class="modal-actions">
                <button type="submit" class="button"><?= $langs->trans('Create') ?></button>
                <button type="button" class="button" onclick="document.getElementById('quick-add-dialog').close();"><?= $langs->trans('Cancel') ?></button>
            </div>
        </form>
    </dialog>

    <!-- Dialog natif de confirmation de suppression -->
    <dialog id="confirm-delete-dialog">
        <h3 class="modal-title"><?= dol_string_nohtmltag($langs->trans('Delete')) ?></h3>
        <p><?= dol_string_nohtmltag($langs->trans('ConfirmDeleteObject')) ?></p>
        <p><strong id="confirm-delete-ref"></strong></p>
        <div class="modal-actions">
            <button type="button" class="button btn-danger" id="confirm-delete-yes"><?= dol_string_nohtmltag($langs->trans('Confirm')) ?></button>
            <button type="button" class="button" onclick="document.getElementById('confirm-delete-dialog').close();"><?= dol_string_nohtmltag($langs->trans('Cancel')) ?></button>
        </div>
    </dialog>
    <div class="messageErrorOrganizationSaved notice hidden">
        <div class="wpeo-notice notice-error organization-saved-error-notice">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('OrganizationNotSaved') ?></div>
                <div class="notice-subtitle">
                    <span class="text"></span>
                </div>
            </div>
            <div class="notice-close"><i class="fas fa-times"></i></div>
        </div>
    </div>
    <div class="organization-header">
        <h3 class='title' id='title0'><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM ?></h3>
        <span class="unsaved-indicator"><i class="fas fa-exclamation-circle"></i> <?= $langs->trans('UnsavedChanges') ?></span>
    </div>

    <div class='container'>
        <input type="hidden" name="token" value="<?php echo newToken() ?>">
        <ul class='space space-0 first-space ui-sortable' id='space0' value="0">
            <?php display_recurse_tree_organization($results, 1, $riskInfos) ?>
        </ul>
    </div>
<?php
print '<hr>';

else :
		print '<div class="wpeo-notice notice-warning notice-red">';
		print '<div class="notice-content">';
		print '<a href="' . dol_buildpath('/custom/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=' . $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD, 2) . '">' . '<b><div class="notice-subtitle">'.$langs->trans("YouDontHaveOrganization") . '</b></a>';
		print '</div>';
		print '</div>';
		print '</div>';
?>
<?php
endif;

// End of page
llxFooter();
$db->close();
