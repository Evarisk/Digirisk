/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/preventionplan-mobile.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript for the mobile prevention plan quick-creation interface.
 */

'use strict';

/**
 * Init the preventionplanmobile object and its mandatory "init" method.
 */
window.digiriskdolibarr.preventionplanmobile = {};

/**
 * The current SignaturePad instance (null until the drawing canvas is initialised).
 */
window.digiriskdolibarr.preventionplanmobile.signaturePad = null;

/**
 * Monotonic index used to key risk blocks (kept unique across deletions).
 */
window.digiriskdolibarr.preventionplanmobile.riskIndex = 0;

/**
 * Monotonic index used to key certification rows (kept unique across deletions).
 */
window.digiriskdolibarr.preventionplanmobile.certIndex = 0;

/**
 * Guard so the delegated events are attached exactly once (framework init OR the DOM-ready fallback).
 */
window.digiriskdolibarr.preventionplanmobile.bound = false;

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.init = function() {
    // The fire permit interface shares every selector of this one, and both modules delegate on
    // document: bind only when the prevention plan form or success screen is the one on screen.
    var form = $('.digirisk-mobile-form--preventionplan');
    var successBlock = $('.digirisk-mobile-extsign');
    
    if (!form.length && !successBlock.length) {
        return;
    }

    window.digiriskdolibarr.preventionplanmobile.event();
    // Initialise the drawing canvas straight away if it is visible.
    if (form.find('.digirisk-mobile-signature-draw').length && !form.find('.digirisk-mobile-signature-draw').hasClass('hidden')) {
        window.digiriskdolibarr.preventionplanmobile.initCanvas();
    }
    // The certification picker is turned into select2 by Dolibarr's ajax_combobox() (printed in the tpl).

    // Edit mode: rows already rendered server-side occupy the first indexes, so keep counting after them
    if (form.length) {
        window.digiriskdolibarr.preventionplanmobile.riskIndex = parseInt(form.data('risk-start-index'), 10) || 0;
        window.digiriskdolibarr.preventionplanmobile.certIndex = parseInt(form.data('cert-start-index'), 10) || 0;
    }
};

/**
 * All the events of the preventionplanmobile interface.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.event = function() {
    if (window.digiriskdolibarr.preventionplanmobile.bound) {
        return;
    }
    window.digiriskdolibarr.preventionplanmobile.bound = true;
    $(document).on('click', '.digirisk-mobile-signature-clear', window.digiriskdolibarr.preventionplanmobile.clearSignature);
    $(document).on('click', '.digirisk-mobile-signature-save', window.digiriskdolibarr.preventionplanmobile.saveSignature);
    $(document).on('click', '.digirisk-mobile-siren-search', window.digiriskdolibarr.preventionplanmobile.searchSiren);
    // Bound on the id: select_company renders either a select or, on big databases, an ajax
    // autocompleter whose hidden input carries that same id
    $(document).on('change', '#ext_society_picker', window.digiriskdolibarr.preventionplanmobile.selectSociety);
    $(document).on('change', '.digirisk-mobile-contact-select', window.digiriskdolibarr.preventionplanmobile.selectContact);
    $(document).on('change', '.digirisk-mobile-date-start, .digirisk-mobile-date-end', window.digiriskdolibarr.preventionplanmobile.checkDates);
    $(document).on('change', '.digirisk-mobile-prior-visit-toggle', window.digiriskdolibarr.preventionplanmobile.togglePriorVisit);
    $(document).on('click', '.digirisk-mobile-risk-add', window.digiriskdolibarr.preventionplanmobile.openRiskModal);
    $(document).on('click', '.digirisk-mobile-risk-modal-close, .digirisk-mobile-risk-modal__overlay', window.digiriskdolibarr.preventionplanmobile.closeRiskModal);
    $(document).on('click', '.digirisk-mobile-risk-option', window.digiriskdolibarr.preventionplanmobile.addRisk);
    $(document).on('click', '.digirisk-mobile-risk-block__delete', window.digiriskdolibarr.preventionplanmobile.confirmRemoveRisk);
    $(document).on('click', '.digirisk-mobile-confirm-cancel', window.digiriskdolibarr.preventionplanmobile.closeConfirmModal);
    $(document).on('click', '.digirisk-mobile-confirm-delete', window.digiriskdolibarr.preventionplanmobile.removeRisk);
    $(document).on('click', '.digirisk-mobile-protection-add', window.digiriskdolibarr.preventionplanmobile.openProtectionModal);
    $(document).on('click', '.digirisk-mobile-protection-modal-close, .digirisk-mobile-protection-modal__overlay', window.digiriskdolibarr.preventionplanmobile.closeProtectionModal);
    $(document).on('click', '.digirisk-mobile-protection-option', window.digiriskdolibarr.preventionplanmobile.addProtection);
    $(document).on('click', '.digirisk-mobile-protection-item-delete', window.digiriskdolibarr.preventionplanmobile.removeProtection);
    $(document).on('click', '.digirisk-mobile-cert-add', window.digiriskdolibarr.preventionplanmobile.addCertification);
    $(document).on('click', '.digirisk-mobile-cert-item-delete', window.digiriskdolibarr.preventionplanmobile.removeCertification);
    $(document).on('submit', '.digirisk-mobile-form', window.digiriskdolibarr.preventionplanmobile.submitForm);
    $(document).on('click', '.digirisk-mobile-extsign__resend', window.digiriskdolibarr.preventionplanmobile.resendExtSignatureEmail);
};

/**
 * Send the signature link to the exterior company again from the success screen.
 *
 * The automatic email of the creation may have failed, or simply never reached its recipient: the
 * button reports what happened instead of leaving the user guessing.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.resendExtSignatureEmail = function() {
    var button = $(this);
    var card   = button.closest('.digirisk-mobile-extsign');
    var status = card.find('.digirisk-mobile-extsign__status');
    var planId = card.data('plan-id');

    if (button.hasClass('button-disable')) {
        return;
    }
    button.addClass('button-disable');

    $.ajax({
        url: document.URL.split('#')[0] + (document.URL.indexOf('?') >= 0 ? '&' : '?') + 'action=resend_ext_signature_email&token=' + window.saturne.toolbox.getToken(),
        type: 'POST',
        data: { plan_id: planId },
        success: function(resp) {
            button.removeClass('button-disable');
            if (!resp) {
                return;
            }
            status
                .removeClass('digirisk-mobile-extsign__status--pending digirisk-mobile-extsign__status--sent digirisk-mobile-extsign__status--error')
                .addClass(resp.success ? 'digirisk-mobile-extsign__status--sent' : 'digirisk-mobile-extsign__status--error')
                .find('span').text(resp.message || '');
            status.find('i').attr('class', resp.success ? 'fas fa-paper-plane' : 'fas fa-exclamation-circle');
        },
        error: function(jqXHR) {
            button.removeClass('button-disable');
            status
                .removeClass('digirisk-mobile-extsign__status--pending digirisk-mobile-extsign__status--sent')
                .addClass('digirisk-mobile-extsign__status--error')
                .find('span').text('Erreur serveur (HTTP ' + jqXHR.status + ')');
        }
    });
};

/**
 * (Re)initialise the signature drawing pad and size the canvas to its container.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.initCanvas = function() {
    var canvas = document.querySelector('.digirisk-mobile-form--preventionplan .digirisk-mobile-signature-canvas');
    if (!canvas || typeof SignaturePad === 'undefined' || window.digiriskdolibarr.preventionplanmobile.signaturePad) {
        return;
    }
    var ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width  = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    window.digiriskdolibarr.preventionplanmobile.signaturePad = new SignaturePad(canvas, { penColor: 'rgb(0, 0, 0)' });
};

/**
 * Clear the drawing pad.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.clearSignature = function() {
    if (window.digiriskdolibarr.preventionplanmobile.signaturePad) {
        window.digiriskdolibarr.preventionplanmobile.signaturePad.clear();
    }
};

/**
 * Save the drawn signature for the current user (reusable across plans).
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.saveSignature = function() {
    var pad    = window.digiriskdolibarr.preventionplanmobile.signaturePad;
    var form   = $('.digirisk-mobile-form');
    var status = $('.digirisk-mobile-signature-status');

    if (!pad || pad.isEmpty()) {
        status.removeClass('success').addClass('error').text(form.data('empty-signature-label') || 'Signature vide');
        return;
    }

    var signature = pad.toDataURL('image/png');

    $.ajax({
        // The payload travels as raw JSON, so the anti CSRF token has to go in the URL:
        // without it Dolibarr answers 403 to every POST (MAIN_SECURITY_CSRF_WITH_TOKEN)
        url: form.data('save-signature-url') + '?action=save&token=' + window.saturne.toolbox.getToken(),
        type: 'POST',
        processData: false,
        contentType: 'application/json',
        data: JSON.stringify({ signature: signature }),
        success: function(resp) {
            if (resp && resp.success) {
                form.attr('data-has-signature', '1');
                $('.digirisk-mobile-signature-preview').attr('src', signature);
                $('.digirisk-mobile-signature-draw').addClass('hidden');
                $('.digirisk-mobile-signature-saved').removeClass('hidden');
                status.removeClass('error').addClass('success').text('');
            } else {
                status.removeClass('success').addClass('error').text((resp && resp.error) ? resp.error : 'Erreur lors de l\'enregistrement');
            }
        },
        error: function(jqXHR) {
            status.removeClass('success').addClass('error').text('Erreur serveur (HTTP ' + jqXHR.status + ')');
        }
    });
};

/**
 * Look up an existing third party by SIREN and pre-fill the exterior company block.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.searchSiren = function() {
    var form   = $('.digirisk-mobile-form');
    var siren  = $('.digirisk-mobile-siren-input').val().replace(/[^0-9]/g, '');
    var result = $('.digirisk-mobile-siren-result');

    if (siren.length < 9) {
        result.removeClass('success').addClass('error').text(form.data('invalid-siren-label') || 'SIREN invalide');
        return;
    }

    window.saturne.loader.display($('.digirisk-mobile-siren-search'));

    $.ajax({
        url: form.data('siren-lookup-url') + '?siren=' + encodeURIComponent(siren),
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            $('.digirisk-mobile-siren-search').removeClass('wpeo-loader');
            if (!resp || !resp.success) {
                result.removeClass('success').addClass('error').text((resp && resp.error) ? resp.error : 'Erreur lors de la recherche');
                return;
            }
            if (resp.found) {
                window.digiriskdolibarr.preventionplanmobile.fillFoundCompany(resp);
            } else {
                window.digiriskdolibarr.preventionplanmobile.resetFoundCompany();
                result.removeClass('error').addClass('success').text(form.data('company-not-found-label') || '');
            }
        },
        error: function() {
            $('.digirisk-mobile-siren-search').removeClass('wpeo-loader');
            result.removeClass('success').addClass('error').text('Erreur de connexion au serveur');
        }
    });
};

/**
 * Fill the exterior company block from the third party picked in the list. Clearing the picker
 * only drops the link to the existing company: what was typed stays, so the same fields can be
 * used to describe a company to create.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.selectSociety = function() {
    var form  = $('.digirisk-mobile-form');
    var socid = parseInt($(this).val(), 10) || 0;

    if (socid <= 0) {
        window.digiriskdolibarr.preventionplanmobile.resetFoundCompany();
        $('.digirisk-mobile-siren-result').removeClass('error success').text('');
        return;
    }

    $.ajax({
        url: form.data('siren-lookup-url') + '?socid=' + encodeURIComponent(socid),
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (!resp || !resp.success || !resp.found) {
                return;
            }
            window.digiriskdolibarr.preventionplanmobile.fillFoundCompany(resp);
            $('.digirisk-mobile-siren-input').val(resp.societe.siret || resp.societe.siren || '');
        }
    });
};

/**
 * Fill the exterior company block from a found third party and populate its contacts.
 *
 * @param {Object} resp Ajax response
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.fillFoundCompany = function(resp) {
    var form = $('.digirisk-mobile-form');

    $('.digirisk-mobile-ext-society-id').val(resp.societe.id);
    $('.digirisk-mobile-ext-society-name').val(resp.societe.name);
    if (resp.societe.email) {
        $('.digirisk-mobile-ext-society-email').val(resp.societe.email);
    }
    // L'adresse est celle du tiers resolu : elle n'ecrase pas une saisie en cours quand la fiche
    // du tiers ne la renseigne pas
    if (resp.societe.address) {
        $('.digirisk-mobile-ext-society-address').val(resp.societe.address);
    }
    if (resp.societe.zip) {
        $('.digirisk-mobile-ext-society-zip').val(resp.societe.zip);
    }
    if (resp.societe.town) {
        $('.digirisk-mobile-ext-society-town').val(resp.societe.town);
    }
    $('.digirisk-mobile-siren-result').removeClass('error').addClass('success').text((form.data('company-found-label') || '') + ' ' + resp.societe.name);

    var select = $('.digirisk-mobile-contact-select');
    select.find('option:not(:first)').remove();
    if (resp.contacts && resp.contacts.length) {
        $.each(resp.contacts, function(index, contact) {
            var label = ((contact.lastname || '') + ' ' + (contact.firstname || '')).trim();
            select.append($('<option>', {
                value: contact.id,
                text: label + (contact.email ? ' (' + contact.email + ')' : ''),
                'data-lastname': contact.lastname || '',
                'data-firstname': contact.firstname || '',
                'data-email': contact.email || ''
            }));
        });
        $('.digirisk-mobile-contact-picker').removeClass('hidden');
    } else {
        $('.digirisk-mobile-contact-picker').addClass('hidden');
    }
};

/**
 * Reset the exterior company id/contact when no company matches the SIREN (a new one will be created).
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.resetFoundCompany = function() {
    $('.digirisk-mobile-ext-society-id').val('');
    $('.digirisk-mobile-resp-contact-id').val('');
    $('.digirisk-mobile-contact-picker').addClass('hidden');
    $('.digirisk-mobile-contact-select').val('');
};

/**
 * When picking an existing contact, fill the responsible fields and lock them.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.selectContact = function() {
    var option = $(this).find('option:selected');
    var id     = $(this).val();

    if (id) {
        var contactEmail = option.data('email') || '';

        $('.digirisk-mobile-resp-contact-id').val(id);
        $('.digirisk-mobile-resp-lastname').val(option.data('lastname')).prop('readonly', true);
        $('.digirisk-mobile-resp-firstname').val(option.data('firstname')).prop('readonly', true);
        // L'email est desormais obligatoire : le verrouiller alors que la fiche du contact n'en a
        // pas enfermerait l'utilisateur devant une erreur qu'il ne peut pas corriger
        $('.digirisk-mobile-resp-email').val(contactEmail).prop('readonly', contactEmail !== '');
    } else {
        $('.digirisk-mobile-resp-contact-id').val('');
        $('.digirisk-mobile-resp-lastname').val('').prop('readonly', false);
        $('.digirisk-mobile-resp-firstname').val('').prop('readonly', false);
        $('.digirisk-mobile-resp-email').val('').prop('readonly', false);
    }
};

/**
 * Validate that end date is after start date and at most one year apart.
 *
 * @return {boolean} True if dates are valid or incomplete, false if invalid
 */
window.digiriskdolibarr.preventionplanmobile.checkDates = function() {
    var form     = $('.digirisk-mobile-form');
    var startVal = $('.digirisk-mobile-date-start').val();
    var endVal   = $('.digirisk-mobile-date-end').val();
    var errorBox = $('.digirisk-mobile-date-error');

    if (!startVal) {
        return true;
    }

    var start = new Date(startVal);
    // Native max on the end input helps the picker; one year minus one day is the accepted span.
    var maxEnd = new Date(start);
    maxEnd.setFullYear(maxEnd.getFullYear() + 1);
    $('.digirisk-mobile-date-end').attr('min', startVal).attr('max', maxEnd.toISOString().slice(0, 10));

    if (!endVal) {
        errorBox.addClass('hidden').text('');
        return true;
    }

    var end = new Date(endVal);
    if (end < start) {
        errorBox.removeClass('hidden').text(form.data('end-before-start-label') || '');
        return false;
    }
    if (end > maxEnd) {
        errorBox.removeClass('hidden').text(form.data('max-one-year-label') || '');
        return false;
    }

    errorBox.addClass('hidden').text('');
    return true;
};

/**
 * Full date validation on submit: both dates required (says which one is missing),
 * end after start, at most one year. Highlights the offending field.
 *
 * @return {boolean} True if valid
 */
window.digiriskdolibarr.preventionplanmobile.validateDatesForSubmit = function() {
    var form     = $('.digirisk-mobile-form');
    var startEl  = $('.digirisk-mobile-date-start');
    var endEl    = $('.digirisk-mobile-date-end');
    var errorBox = $('.digirisk-mobile-date-error');
    var startVal = startEl.val();
    var endVal   = endEl.val();
    var message  = '';

    startEl.removeClass('digirisk-mobile-field-error');
    endEl.removeClass('digirisk-mobile-field-error');

    if (!startVal && !endVal) {
        message = form.data('dates-required-label') || '';
        startEl.addClass('digirisk-mobile-field-error');
        endEl.addClass('digirisk-mobile-field-error');
    } else if (!startVal) {
        message = form.data('date-start-required-label') || '';
        startEl.addClass('digirisk-mobile-field-error');
    } else if (!endVal) {
        message = form.data('date-end-required-label') || '';
        endEl.addClass('digirisk-mobile-field-error');
    } else {
        var start  = new Date(startVal);
        var end    = new Date(endVal);
        var maxEnd = new Date(start);
        maxEnd.setFullYear(maxEnd.getFullYear() + 1);
        if (end < start) {
            message = form.data('end-before-start-label') || '';
            endEl.addClass('digirisk-mobile-field-error');
        } else if (end > maxEnd) {
            message = form.data('max-one-year-label') || '';
            endEl.addClass('digirisk-mobile-field-error');
        }
    }

    if (message) {
        errorBox.removeClass('hidden').text(message);
        return false;
    }
    errorBox.addClass('hidden').text('');
    return true;
};

/**
 * Show the prior visit comment and date only once the visit is declared done.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.togglePriorVisit = function() {
    $('.digirisk-mobile-prior-visit-detail').toggleClass('hidden', !$(this).is(':checked'));
};

/**
 * Open the risk picker modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.openRiskModal = function() {
    $('.digirisk-mobile-risk-modal').removeClass('hidden');
};

/**
 * Close the risk picker modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.closeRiskModal = function() {
    $('.digirisk-mobile-risk-modal').addClass('hidden');
};

/**
 * Build a DOM fragment from one of the <template> elements printed by the form, with its
 * index placeholders resolved. Keeps the JS rows identical to the server-side ones.
 *
 * @param  {string} templateClass Class of the template element
 * @param  {Object} indexes       Placeholder name (without the underscores) => value
 * @return {Object}               jQuery object of the built row
 */
window.digiriskdolibarr.preventionplanmobile.buildFromTemplate = function(templateClass, indexes) {
    var markup = $('.' + templateClass).prop('innerHTML') || '';
    $.each(indexes, function(name, value) {
        markup = markup.split('__' + name + '__').join(value);
    });
    return $($.parseHTML($.trim(markup))).filter('div').first();
};

/**
 * Show the "no risk yet" hint only while the list is empty.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.refreshRiskEmptyState = function() {
    $('.digirisk-mobile-risk-empty').toggleClass('hidden', $('.digirisk-mobile-risk-block').length > 0);
};

/**
 * Add the picked risk (danger category) as a new block: description, photos and its own protections.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.addRisk = function() {
    var option   = $(this);
    var position = String(option.data('position'));

    // Avoid adding the same danger category twice.
    var exists = false;
    $('.digirisk-mobile-risk-block').each(function() {
        if (String($(this).attr('data-position')) === position) {
            exists = true;
        }
    });
    if (exists) {
        window.digiriskdolibarr.preventionplanmobile.closeRiskModal();
        return;
    }

    var index  = window.digiriskdolibarr.preventionplanmobile.riskIndex++;
    var $block = window.digiriskdolibarr.preventionplanmobile.buildFromTemplate('digirisk-mobile-risk-block-template', { RISKINDEX: index });

    $block.attr('data-position', position);
    $block.find('.digirisk-mobile-risk-block__picto').attr('src', option.data('thumbnail')).attr('alt', option.data('name'));
    $block.find('.digirisk-mobile-risk-block__name').text(option.data('name'));
    $block.find('.digirisk-mobile-risk-block__category').val(position);

    $('.digirisk-mobile-risk-list').append($block);
    window.digiriskdolibarr.preventionplanmobile.refreshRiskEmptyState();
    window.digiriskdolibarr.preventionplanmobile.closeRiskModal();
};

/**
 * Ask before dropping a risk: the block carries its photos and its protections, and there is
 * no way back before the form is submitted.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.confirmRemoveRisk = function() {
    var $block  = $(this).closest('.digirisk-mobile-risk-block');
    var $modal  = $('.digirisk-mobile-confirm-modal');
    var message = $('.digirisk-mobile-form').data('delete-risk-label') || '';

    $modal.attr('data-risk-index', $block.attr('data-index'));
    $modal.find('.digirisk-mobile-confirm-modal__body').text(message.replace('%s', $block.find('.digirisk-mobile-risk-block__name').text()));
    $modal.removeClass('hidden');
};

/**
 * Close the delete confirmation without touching the risk.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.closeConfirmModal = function() {
    $('.digirisk-mobile-confirm-modal').addClass('hidden').removeAttr('data-risk-index');
};

/**
 * Remove the confirmed risk block, with everything it holds.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.removeRisk = function() {
    var riskIndex = $('.digirisk-mobile-confirm-modal').attr('data-risk-index');

    $('.digirisk-mobile-risk-block[data-index="' + riskIndex + '"]').remove();
    window.digiriskdolibarr.preventionplanmobile.closeConfirmModal();
    window.digiriskdolibarr.preventionplanmobile.refreshRiskEmptyState();
};

/**
 * Open the protection picker modal for the risk block the button belongs to.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.openProtectionModal = function() {
    var riskIndex = $(this).closest('.digirisk-mobile-risk-block').attr('data-index');
    $('.digirisk-mobile-protection-modal').attr('data-risk-index', riskIndex).removeClass('hidden');
};

/**
 * Close the protection picker modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.closeProtectionModal = function() {
    $('.digirisk-mobile-protection-modal').addClass('hidden').removeAttr('data-risk-index');
};

/**
 * Add the picked protection (EPI) to the risk that opened the modal, with a mandatory checkbox.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.addProtection = function() {
    var option    = $(this);
    var position  = String(option.data('position'));
    var riskIndex = $('.digirisk-mobile-protection-modal').attr('data-risk-index');
    var $block    = $('.digirisk-mobile-risk-block[data-index="' + riskIndex + '"]');

    if (!$block.length) {
        window.digiriskdolibarr.preventionplanmobile.closeProtectionModal();
        return;
    }

    // Avoid adding the same protection twice on the same risk.
    var exists = false;
    $block.find('.digirisk-mobile-protection-item').each(function() {
        if (String($(this).attr('data-position')) === position) {
            exists = true;
        }
    });
    if (exists) {
        window.digiriskdolibarr.preventionplanmobile.closeProtectionModal();
        return;
    }

    var index = parseInt($block.attr('data-protection-index'), 10) || 0;
    $block.attr('data-protection-index', index + 1);

    var $row = window.digiriskdolibarr.preventionplanmobile.buildFromTemplate('digirisk-mobile-protection-row-template', { RISKINDEX: riskIndex, INDEX: index });
    $row.attr('data-position', position);
    $row.find('.digirisk-mobile-protection-item-photo').attr('src', option.data('thumbnail')).attr('title', option.data('name'));
    $row.find('input[type="hidden"]').val(position);

    $block.find('.digirisk-mobile-protection-list').append($row);
    window.digiriskdolibarr.preventionplanmobile.closeProtectionModal();
};

/**
 * Remove a selected protection from its risk.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.removeProtection = function() {
    $(this).closest('.digirisk-mobile-protection-item').remove();
};

/**
 * Add the certification currently selected in the picker to the list.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.addCertification = function() {
    var form   = $('.digirisk-mobile-form');
    var picker = $('.digirisk-mobile-cert-picker');
    var code   = picker.val();

    if (!code) {
        return;
    }

    var label = picker.find('option:selected').text();

    // Avoid adding the same certification twice.
    var exists = false;
    $('.digirisk-mobile-cert-item').each(function() {
        if ($(this).attr('data-code') === code) {
            exists = true;
        }
    });
    if (exists) {
        picker.val('').trigger('change');
        return;
    }

    var index          = window.digiriskdolibarr.preventionplanmobile.certIndex++;
    var mandatoryLabel = form.data('mandatory-label') || '';

    var $row = $('<div class="digirisk-mobile-cert-item"></div>').attr('data-code', code);
    $('<span class="digirisk-mobile-cert-item-label"></span>').text(label).appendTo($row);
    $('<input type="hidden" name="cert_code[' + index + ']">').val(code).appendTo($row);

    var $mandatory = $('<label class="digirisk-mobile-cert-item-mandatory"></label>');
    $('<input type="checkbox" value="1">').attr('name', 'cert_mandatory[' + index + ']').appendTo($mandatory);
    $('<span></span>').text(mandatoryLabel).appendTo($mandatory);
    $mandatory.appendTo($row);

    $('<button type="button" class="digirisk-mobile-cert-item-delete"><i class="fas fa-trash"></i></button>').appendTo($row);

    $('.digirisk-mobile-cert-list').append($row);

    // Reset the picker for the next pick.
    picker.val('').trigger('change');
};

/**
 * Remove a certification row.
 *
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.removeCertification = function() {
    $(this).closest('.digirisk-mobile-cert-item').remove();
};

/**
 * Guard the submit: require a saved signature and valid dates.
 *
 * @param  {Event} event Submit event
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.submitForm = function(event) {
    // Always submit through AJAX so a server-side error never wipes the typed data.
    event.preventDefault();
    var form      = $(this);
    var submitBtn = form.find('.digirisk-mobile-submit');

    // Saturne's global handler adds a spinner AND toggles the button to "button-disable" on click.
    // Fully restore the button whenever we don't navigate away, so it stays clickable.
    var resetSubmit = function() {
        if (window.saturne && window.saturne.loader && window.saturne.loader.remove) {
            window.saturne.loader.remove(submitBtn);
        }
        submitBtn.prop('disabled', false).removeClass('wpeo-loader button-disable').addClass('button-blue');
    };

    if (form.attr('data-has-signature') !== '1') {
        resetSubmit();
        $('.digirisk-mobile-signature-status').removeClass('success').addClass('error').text(form.data('need-signature-label') || '');
        $('html, body').animate({ scrollTop: $('.digirisk-mobile-signature').offset().top - 80 }, 300);
        return;
    }

    if (!window.digiriskdolibarr.preventionplanmobile.validateDatesForSubmit()) {
        resetSubmit();
        $('html, body').animate({ scrollTop: $('.digirisk-mobile-date-error').offset().top - 80 }, 300);
        return;
    }

    var formData = new FormData(form[0]);
    formData.append('ajax', '1');

    var errorBox = $('.digirisk-mobile-form-errors');
    submitBtn.prop('disabled', true);
    errorBox.addClass('hidden').empty();

    $.ajax({
        url: form.attr('action') || document.URL,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            if (resp && resp.success && resp.redirect) {
                window.location.href = resp.redirect; // keep the spinner during navigation
                return;
            }
            window.digiriskdolibarr.preventionplanmobile.showFormErrors((resp && resp.errors && resp.errors.length) ? resp.errors : ['Une erreur inattendue est survenue.']);
            resetSubmit();
        },
        error: function() {
            window.digiriskdolibarr.preventionplanmobile.showFormErrors(['Erreur de connexion au serveur.']);
            resetSubmit();
        }
    });
};

/**
 * Display server-side validation errors above the form without touching the fields.
 *
 * @param  {Array} errors List of error messages
 * @return {void}
 */
window.digiriskdolibarr.preventionplanmobile.showFormErrors = function(errors) {
    var errorBox = $('.digirisk-mobile-form-errors');
    var $ul      = $('<ul></ul>');
    $.each(errors, function(index, message) {
        $('<li></li>').text(message).appendTo($ul);
    });
    errorBox.empty().append($ul).removeClass('hidden');
    if (errorBox.offset()) {
        $('html, body').animate({ scrollTop: errorBox.offset().top - 80 }, 300);
    }
};

// Robust fallback: bind the mobile handlers on DOM ready, independently of the
// DigiriskDolibarr load_list_script chain (which has no try/catch, so another module's
// error could otherwise prevent this module from initialising and let the form POST natively).
// The `bound` guard in event() ensures the handlers are attached exactly once.
$(function() {
    window.digiriskdolibarr.preventionplanmobile.init();
});
