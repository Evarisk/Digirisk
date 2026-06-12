/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * Initialise l'objet "organization" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * Gère l'arborescence GP/UT : réorganisation par glisser-déposer (auto-sauvegarde),
 * renommage inline des libellés, ajout rapide et suppression via dialogs natifs.
 * Utilisé à la fois par le panneau de navigation de gauche et par la page d'organisation autonome.
 *
 * @since   9.0.0
 * @version 9.0.0
 */

window.digiriskdolibarr.organization = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.init = function() {
	window.digiriskdolibarr.organization.event();
	window.digiriskdolibarr.organization.initSortable();
};

/**
 * La méthode contenant tous les événements pour l'organisation.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.event = function() {
	$(document).on('click', '.organization-tree .toggle-children', window.digiriskdolibarr.organization.toggleChildren);
	$(document).on('click', '.organization-tree .element-label', window.digiriskdolibarr.organization.editLabel);
	$(document).on('click', '.organization-tree .quick-add-btn', window.digiriskdolibarr.organization.openQuickAdd);
	$(document).on('click', '.quick-add-cancel', window.digiriskdolibarr.organization.closeQuickAdd);
	$(document).on('click', '.organization-tree .delete-element-btn', window.digiriskdolibarr.organization.openDelete);
	$(document).on('click', '#confirm-delete-yes', window.digiriskdolibarr.organization.confirmDelete);
	$(document).on('click', '.confirm-delete-cancel', window.digiriskdolibarr.organization.closeDelete);
	$(document).on('click', '.organization-tree .row-container', window.digiriskdolibarr.organization.navigateToElement);
};

/**
 * Récupère les libellés traduits déposés dans l'élément de configuration par le TPL.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {Object} Les traductions disponibles côté JS.
 */
window.digiriskdolibarr.organization.getConfig = function() {
	let $config = $('#digirisk-organization-config');
	return {
		saving:       $config.data('saving'),
		saved:        $config.data('saved'),
		errorSaving:  $config.data('error-saving'),
		newGroupment: $config.data('new-groupment'),
		newWorkunit:  $config.data('new-workunit')
	};
};

/**
 * Active le glisser-déposer (jQuery UI sortable) sur les listes d'éléments avec auto-sauvegarde.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.initSortable = function() {
	let $spaces = $('.organization-tree .space');
	if (!$spaces.length || typeof $spaces.sortable !== 'function') {
		return;
	}

	// "stop" fires once on the origin list per drag, capturing both sibling reordering and re-parenting
	$spaces.sortable({
		connectWith: '.organization-tree .space:not(.workunit)',
		tolerance:   'intersect',
		handle:      '.drag-handle',
		stop:        window.digiriskdolibarr.organization.onDrop
	});

	$spaces.disableSelection();
};

/**
 * Callback lors d'un dépôt : met à jour les chevrons puis déclenche l'auto-sauvegarde.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.onDrop = function() {
	window.digiriskdolibarr.organization.updateChevrons();
	window.digiriskdolibarr.organization.saveOrganization();
};

/**
 * Met à jour dynamiquement les chevrons des groupements après un glisser-déposer.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.updateChevrons = function() {
	$('.organization-tree .groupment').each(function() {
		let $row        = $(this);
		let $chevronDiv = $row.find('.chevron');
		let $space      = $row.siblings('.space').first();
		if (!$space.length) {
			$space = $row.closest('li.route').find('> ul.space');
		}
		let hasChildren = $space.children('li.route').length > 0;
		let $icon       = $chevronDiv.find('i');

		if (hasChildren) {
			$icon.removeClass('chevron-empty fa-chevron-right').addClass('fa-chevron-down toggle-children');
			$icon.css('pointer-events', '');
		} else {
			$icon.removeClass('toggle-children fa-chevron-down').addClass('fa-chevron-right chevron-empty');
			$icon.css('pointer-events', 'none');
		}
	});
};

/**
 * Sauvegarde l'organisation (ordre et parents) des GP/UT via une requête AJAX.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.saveOrganization = function() {
	let config      = window.digiriskdolibarr.organization.getConfig();
	let token       = window.saturne.toolbox.getToken();
	let idArray     = [];
	let parentArray = [];

	$('.organization-tree .route').each(function() {
		idArray.push($(this).attr('data-route-id') || $(this).attr('id'));
		parentArray.push($(this).parent('ul').attr('id').split(/space/)[1]);
	});

	let $indicator = $('.unsaved-indicator');
	if ($indicator.length) {
		$indicator.addClass('visible').removeClass('saved');
		$indicator.html('<i class="fas fa-spinner fa-spin"></i> ' + config.saving);
	}

	$.ajax({
		url: document.URL + '&action=saveOrganization&ids=' + idArray.toString() + '&parent_ids=' + parentArray + '&token=' + token,
		success: function() {
			if ($indicator.length) {
				$indicator.addClass('saved');
				$indicator.html('<i class="fas fa-check-circle"></i> ' + config.saved);
				setTimeout(function() {
					$indicator.removeClass('visible saved');
				}, 2000);
			}
		},
		error: function() {
			if ($indicator.length) {
				$indicator.html('<i class="fas fa-exclamation-triangle"></i> ' + config.errorSaving);
				setTimeout(function() {
					$indicator.removeClass('visible');
				}, 4000);
			}
		}
	});
};

/**
 * Déplie ou replie les enfants d'un groupement (page d'organisation autonome).
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.toggleChildren = function() {
	let $icon     = $(this);
	let $children = $icon.closest('.row-container').siblings('.space');

	$children.slideToggle(200, function() {
		if ($children.is(':visible')) {
			$icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
		} else {
			$icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
		}
	});
};

/**
 * Édition inline du libellé d'un GP/UT (clic sur le nom).
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiriskdolibarr.organization.editLabel = function(event) {
	event.stopPropagation();
	let $label = $(this);

	// Empêcher la double activation
	if ($label.find('input').length) {
		return;
	}

	let currentText = $label.text().trim();
	let $route      = $label.closest('.route');
	let elementId   = $route.attr('data-route-id') || $route.attr('id');
	let $input      = $('<input type="text" class="inline-edit-input" />').val(currentText);

	$label.html($input);
	$input.focus().select();

	// Sauvegarde au blur ou Enter
	function saveLabel() {
		let newLabel = $input.val().trim();
		if (!newLabel || newLabel === currentText) {
			$label.text(currentText);
			return;
		}

		let token = window.saturne.toolbox.getToken();
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
	$input.on('keydown', function(keyEvent) {
		if (keyEvent.key === 'Enter') {
			keyEvent.preventDefault();
			$input.blur();
		}
		if (keyEvent.key === 'Escape') {
			$label.text(currentText);
		}
		if (keyEvent.key === 'Tab') {
			keyEvent.preventDefault();
			$input.off('blur'); // Éviter le double-save
			saveLabel();
			// Trouver tous les labels visibles et naviguer
			let $allLabels   = $('.organization-tree .element-label:visible');
			let currentIndex = $allLabels.index($label);
			let nextIndex    = keyEvent.shiftKey ? currentIndex - 1 : currentIndex + 1;
			if (nextIndex >= 0 && nextIndex < $allLabels.length) {
				setTimeout(function() { $allLabels.eq(nextIndex).trigger('click'); }, 50);
			}
		}
	});
};

/**
 * Ouvre le dialog d'ajout rapide d'un GP/UT.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiriskdolibarr.organization.openQuickAdd = function(event) {
	event.preventDefault();
	event.stopPropagation();
	let $btn   = $(this);
	let config = window.digiriskdolibarr.organization.getConfig();
	let dialog = document.getElementById('quick-add-dialog');

	$('#quick-add-type-input').val($btn.data('type'));
	$('#quick-add-fk-parent-input').val($btn.data('parent-id'));
	$('#quick-add-parent-info').text($btn.data('parent-ref') + ' - ' + $btn.data('parent-label'));
	$('#quick-add-dialog-title').text($btn.data('type') === 'groupment' ? config.newGroupment : config.newWorkunit);
	$('#quick-add-label-input').val('');
	$('#quick-add-description-input').val('');
	$('#quick-add-selector-input').prop('checked', true);

	if (dialog && typeof dialog.showModal === 'function') {
		dialog.showModal();
		setTimeout(function() { $('#quick-add-label-input').focus(); }, 50);
	}
};

/**
 * Ferme le dialog d'ajout rapide.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.closeQuickAdd = function() {
	let dialog = document.getElementById('quick-add-dialog');
	if (dialog && typeof dialog.close === 'function') {
		dialog.close();
	}
};

/**
 * Ouvre le dialog de confirmation de suppression d'un élément.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiriskdolibarr.organization.openDelete = function(event) {
	event.preventDefault();
	event.stopPropagation();
	let $btn   = $(this);
	let dialog = document.getElementById('confirm-delete-dialog');

	$('#confirm-delete-ref').text($btn.data('ref'));
	$('#confirm-delete-yes').data('id', $btn.data('id'));

	if (dialog && typeof dialog.showModal === 'function') {
		dialog.showModal();
	}
};

/**
 * Confirme la suppression et redirige vers l'action deleteElement.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.confirmDelete = function() {
	let $btn     = $(this);
	let id       = $btn.data('id');
	let token    = $btn.data('token');
	let mainmenu = $btn.data('mainmenu') || '';
	let leftmenu = $btn.data('leftmenu') || '';

	window.location.href = window.location.pathname +
		'?action=deleteElement&deleteid=' + id + '&token=' + token +
		'&mainmenu=' + mainmenu + '&leftmenu=' + leftmenu;
};

/**
 * Ferme le dialog de confirmation de suppression.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.organization.closeDelete = function() {
	let dialog = document.getElementById('confirm-delete-dialog');
	if (dialog && typeof dialog.close === 'function') {
		dialog.close();
	}
};

/**
 * Navigation : un clic sur la carte de l'élément (hors libellé éditable et hors contrôles)
 * ouvre la page de l'élément (lien du badge de référence).
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiriskdolibarr.organization.navigateToElement = function(event) {
	// Laisser leur comportement au libellé éditable et aux contrôles interactifs (chevron, photo, poignée, boutons, liens)
	if ($(event.target).closest('.element-label, .drag-handle, .toggle-unit, .chevron, .actions, .open-media-gallery, .photo-container, a, input, button').length) {
		return;
	}

	let href = $(this).find('.ref-badge a').attr('href');
	if (href) {
		window.location.href = href;
	}
};
