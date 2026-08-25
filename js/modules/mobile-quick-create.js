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
 * \file    js/modules/mobile-quick-create.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript shared by the mobile quick-creation interfaces (prevention plan, fire permit).
 *          Everything is driven by data attributes on .digirisk-mobile-form, so the same code serves
 *          both object types: only the form template changes.
 */

'use strict';

/**
 * Init the mobilequickcreate object and its mandatory "init" method.
 */
window.digiriskdolibarr.mobilequickcreate = {};

/**
 * The current SignaturePad instance (null until the drawing canvas is initialised).
 */
window.digiriskdolibarr.mobilequickcreate.signaturePad = null;

/**
 * Monotonic index used to key protection rows (kept unique across deletions).
 */
window.digiriskdolibarr.mobilequickcreate.protectionIndex = 0;

/**
 * Monotonic index used to key certification rows (kept unique across deletions).
 */
window.digiriskdolibarr.mobilequickcreate.certIndex = 0;

/**
 * Guard so the delegated events are attached exactly once (framework init OR the DOM-ready fallback).
 */
window.digiriskdolibarr.mobilequickcreate.bound = false;

/**
 * Automatically called by the DigiriskDolibarr library.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.init = function() {
    // The prevention plan interface shares every selector of this one, and both modules delegate on
    // document: bind only when the fire permit form is the one on screen.
    var form = $('.digirisk-mobile-form--firepermit');
    if (!form.length) {
        return;
    }

    window.digiriskdolibarr.mobilequickcreate.event();
    // Initialise the drawing canvas straight away if it is visible.
    if (form.find('.digirisk-mobile-signature-draw').length && !form.find('.digirisk-mobile-signature-draw').hasClass('hidden')) {
        window.digiriskdolibarr.mobilequickcreate.initCanvas();
    }
    // The certification picker is turned into select2 by Dolibarr's ajax_combobox() (printed in the tpl).

    // Edit mode: rows already rendered server-side occupy the first indexes, so keep counting after them
    if (form.length) {
        window.digiriskdolibarr.mobilequickcreate.protectionIndex = parseInt(form.data('protection-start-index'), 10) || 0;
        window.digiriskdolibarr.mobilequickcreate.certIndex       = parseInt(form.data('cert-start-index'), 10) || 0;
    }
};

/**
 * All the events of the mobilequickcreate interface.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.event = function() {
    if (window.digiriskdolibarr.mobilequickcreate.bound) {
        return;
    }
    window.digiriskdolibarr.mobilequickcreate.bound = true;
    $(document).on('click', '.digirisk-mobile-signature-clear', window.digiriskdolibarr.mobilequickcreate.clearSignature);
    $(document).on('click', '.digirisk-mobile-signature-save', window.digiriskdolibarr.mobilequickcreate.saveSignature);
    $(document).on('click', '.digirisk-mobile-siren-search', window.digiriskdolibarr.mobilequickcreate.searchSiren);
    $(document).on('change', '.digirisk-mobile-contact-select', window.digiriskdolibarr.mobilequickcreate.selectContact);
    $(document).on('change', '.digirisk-mobile-preventionplan-select', window.digiriskdolibarr.mobilequickcreate.selectPreventionPlan);
    $(document).on('change', '.digirisk-mobile-date-start, .digirisk-mobile-date-end', window.digiriskdolibarr.mobilequickcreate.checkDates);
    $(document).on('click', '.digirisk-mobile-risk-add', window.digiriskdolibarr.mobilequickcreate.openRiskModal);
    $(document).on('click', '.digirisk-mobile-risk-modal-close, .digirisk-mobile-risk-modal__overlay', window.digiriskdolibarr.mobilequickcreate.closeRiskModal);
    $(document).on('click', '.digirisk-mobile-risk-option', window.digiriskdolibarr.mobilequickcreate.addRisk);
    $(document).on('click', '.digirisk-mobile-risk-item-delete', window.digiriskdolibarr.mobilequickcreate.removeRisk);
    $(document).on('click', '.digirisk-mobile-protection-add', window.digiriskdolibarr.mobilequickcreate.openProtectionModal);
    $(document).on('click', '.digirisk-mobile-protection-modal-close, .digirisk-mobile-protection-modal__overlay', window.digiriskdolibarr.mobilequickcreate.closeProtectionModal);
    $(document).on('click', '.digirisk-mobile-protection-option', window.digiriskdolibarr.mobilequickcreate.addProtection);
    $(document).on('click', '.digirisk-mobile-protection-item-delete', window.digiriskdolibarr.mobilequickcreate.removeProtection);
    $(document).on('click', '.digirisk-mobile-cert-add', window.digiriskdolibarr.mobilequickcreate.addCertification);
    $(document).on('click', '.digirisk-mobile-cert-item-delete', window.digiriskdolibarr.mobilequickcreate.removeCertification);
    $(document).on('submit', '.digirisk-mobile-form', window.digiriskdolibarr.mobilequickcreate.submitForm);
};

/**
 * (Re)initialise the signature drawing pad and size the canvas to its container.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.initCanvas = function() {
    var canvas = document.querySelector('.digirisk-mobile-form--firepermit .digirisk-mobile-signature-canvas');
    if (!canvas || typeof SignaturePad === 'undefined' || window.digiriskdolibarr.mobilequickcreate.signaturePad) {
        return;
    }
    var ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width  = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    window.digiriskdolibarr.mobilequickcreate.signaturePad = new SignaturePad(canvas, { penColor: 'rgb(0, 0, 0)' });
};

/**
 * Clear the drawing pad.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.clearSignature = function() {
    if (window.digiriskdolibarr.mobilequickcreate.signaturePad) {
        window.digiriskdolibarr.mobilequickcreate.signaturePad.clear();
    }
};

/**
 * Save the drawn signature for the current user (reusable across plans).
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.saveSignature = function() {
    var pad    = window.digiriskdolibarr.mobilequickcreate.signaturePad;
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
                status.removeClass('success').addClass('error').text((resp && resp.error) ? resp.error : 'KO');
            }
        },
        error: function(jqXHR) {
            status.removeClass('success').addClass('error').text('KO (HTTP ' + jqXHR.status + ')');
        }
    });
};

/**
 * Look up an existing third party by SIREN or SIRET and pre-fill the exterior company block.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.searchSiren = function() {
    var form   = $('.digirisk-mobile-form');
    var siren  = $('.digirisk-mobile-siren-input').val().replace(/[^0-9]/g, '');
    var result = $('.digirisk-mobile-siren-result');

    // Either a 9 digit SIREN or a 14 digit SIRET.
    if (siren.length !== 9 && siren.length !== 14) {
        result.removeClass('success').addClass('error').text(form.data('invalid-siren-label') || 'SIREN/SIRET invalide');
        return;
    }

    window.saturne.loader.display($('.digirisk-mobile-siren-search'));

    // Le nom part avec l'identifiant : une entreprise deja en base dont le SIREN n'est pas
    // renseigne se retrouve par sa raison sociale au lieu d'etre annoncee comme a creer
    var companyName = $('.digirisk-mobile-ext-society-name').val() || '';

    $.ajax({
        url: form.data('siren-lookup-url') + '?siren=' + encodeURIComponent(siren) + '&name=' + encodeURIComponent(companyName),
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            $('.digirisk-mobile-siren-search').removeClass('wpeo-loader');
            if (!resp || !resp.success) {
                result.removeClass('success').addClass('error').text((resp && resp.error) ? resp.error : 'KO');
                return;
            }
            if (resp.found) {
                window.digiriskdolibarr.mobilequickcreate.fillFoundCompany(resp);
            } else {
                window.digiriskdolibarr.mobilequickcreate.resetFoundCompany();
                result.removeClass('error').addClass('success').text(form.data('company-not-found-label') || '');
            }
        },
        error: function() {
            $('.digirisk-mobile-siren-search').removeClass('wpeo-loader');
            result.removeClass('success').addClass('error').text('KO');
        }
    });
};

/**
 * Fill the exterior company block from a found third party and populate its contacts.
 *
 * @param {Object} resp Ajax response
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.fillFoundCompany = function(resp) {
    var form = $('.digirisk-mobile-form');

    $('.digirisk-mobile-ext-society-id').val(resp.societe.id);
    $('.digirisk-mobile-ext-society-name').val(resp.societe.name);
    if (resp.societe.email) {
        $('.digirisk-mobile-ext-society-email').val(resp.societe.email);
    }
    // Show back the identifier the company actually has on file, SIRET first.
    if (resp.societe.siret || resp.societe.siren) {
        $('.digirisk-mobile-siren-input').val(resp.societe.siret || resp.societe.siren);
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
window.digiriskdolibarr.mobilequickcreate.resetFoundCompany = function() {
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
window.digiriskdolibarr.mobilequickcreate.selectContact = function() {
    var option = $(this).find('option:selected');
    var id     = $(this).val();

    if (id) {
        $('.digirisk-mobile-resp-contact-id').val(id);
        $('.digirisk-mobile-resp-lastname').val(option.data('lastname')).prop('readonly', true);
        $('.digirisk-mobile-resp-firstname').val(option.data('firstname')).prop('readonly', true);
        $('.digirisk-mobile-resp-email').val(option.data('email')).prop('readonly', true);
    } else {
        $('.digirisk-mobile-resp-contact-id').val('');
        $('.digirisk-mobile-resp-lastname').val('').prop('readonly', false);
        $('.digirisk-mobile-resp-firstname').val('').prop('readonly', false);
        $('.digirisk-mobile-resp-email').val('').prop('readonly', false);
    }
};

/**
 * Fire permit only: picking the parent prevention plan pre-fills the exterior company,
 * its responsible and the intervention period, so the same data is not typed twice.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.selectPreventionPlan = function() {
    var form = $('.digirisk-mobile-form');
    var url  = form.data('preventionplan-lookup-url');
    var id   = $(this).val();

    if (!url || !id) {
        return;
    }

    $.ajax({
        url: url + '?id=' + encodeURIComponent(id),
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (!resp || !resp.success) {
                return;
            }
            if (resp.societe && resp.societe.id) {
                $('.digirisk-mobile-ext-society-id').val(resp.societe.id);
                $('.digirisk-mobile-ext-society-name').val(resp.societe.name || '');
                $('.digirisk-mobile-ext-society-email').val(resp.societe.email || '');
                $('.digirisk-mobile-siren-input').val(resp.societe.siret || resp.societe.siren || '');
            }
            if (resp.responsible && resp.responsible.id) {
                $('.digirisk-mobile-resp-contact-id').val(resp.responsible.id);
                $('.digirisk-mobile-resp-lastname').val(resp.responsible.lastname || '');
                $('.digirisk-mobile-resp-firstname').val(resp.responsible.firstname || '');
                $('.digirisk-mobile-resp-email').val(resp.responsible.email || '');
                $('.digirisk-mobile-resp-phone').val(resp.responsible.phone || '');
            }
            // Only suggest the plan period when the user has not entered one yet.
            if (resp.date_start && !$('.digirisk-mobile-date-start').val()) {
                $('.digirisk-mobile-date-start').val(resp.date_start);
            }
            if (resp.date_end && !$('.digirisk-mobile-date-end').val()) {
                $('.digirisk-mobile-date-end').val(resp.date_end);
            }
            window.digiriskdolibarr.mobilequickcreate.checkDates();
        }
    });
};

/**
 * Maximum accepted span between the start and the end date, in days.
 * Comes from the form (one year for a prevention plan, one month for a fire permit).
 *
 * @return {number} Span in days
 */
window.digiriskdolibarr.mobilequickcreate.getMaxSpanDays = function() {
    return parseInt($('.digirisk-mobile-form').data('max-span-days'), 10) || 365;
};

/**
 * Format a date for a native date / datetime-local input.
 * Built from the local parts on purpose: toISOString() would shift to UTC and could move the day.
 *
 * @param  {Date}    date       Date to format
 * @param  {boolean} withTime   True for a datetime-local input
 * @return {string}             "YYYY-MM-DD" or "YYYY-MM-DDTHH:MM"
 */
window.digiriskdolibarr.mobilequickcreate.formatForInput = function(date, withTime) {
    var pad   = function(number) { return (number < 10 ? '0' : '') + number; };
    var value = date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    if (withTime) {
        value += 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }
    return value;
};

/**
 * Validate that end date is after start date and within the accepted span.
 *
 * @return {boolean} True if dates are valid or incomplete, false if invalid
 */
window.digiriskdolibarr.mobilequickcreate.checkDates = function() {
    var form     = $('.digirisk-mobile-form');
    var endEl    = $('.digirisk-mobile-date-end');
    var startVal = $('.digirisk-mobile-date-start').val();
    var endVal   = endEl.val();
    var errorBox = $('.digirisk-mobile-date-error');

    if (!startVal) {
        return true;
    }

    var start  = new Date(startVal);
    var maxEnd = new Date(start);
    maxEnd.setDate(maxEnd.getDate() + window.digiriskdolibarr.mobilequickcreate.getMaxSpanDays());

    // Native min/max on the end input so the picker itself refuses out-of-range values.
    var withTime = endEl.attr('type') === 'datetime-local';
    endEl.attr('min', startVal).attr('max', window.digiriskdolibarr.mobilequickcreate.formatForInput(maxEnd, withTime));

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
        errorBox.removeClass('hidden').text(form.data('max-span-label') || '');
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
window.digiriskdolibarr.mobilequickcreate.validateDatesForSubmit = function() {
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
        maxEnd.setDate(maxEnd.getDate() + window.digiriskdolibarr.mobilequickcreate.getMaxSpanDays());
        if (end < start) {
            message = form.data('end-before-start-label') || '';
            endEl.addClass('digirisk-mobile-field-error');
        } else if (end > maxEnd) {
            message = form.data('max-span-label') || '';
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
 * Open the risk picker modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.openRiskModal = function() {
    $('.digirisk-mobile-risk-modal').removeClass('hidden');
};

/**
 * Close the risk picker modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.closeRiskModal = function() {
    $('.digirisk-mobile-risk-modal').addClass('hidden');
};

/**
 * Add the picked risk (danger category) to the selected list.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.addRisk = function() {
    var option   = $(this);
    var position = String(option.data('position'));

    // Avoid adding the same danger category twice.
    var exists = false;
    $('.digirisk-mobile-risk-item').each(function() {
        if (String($(this).attr('data-position')) === position) {
            exists = true;
        }
    });
    if (exists) {
        window.digiriskdolibarr.mobilequickcreate.closeRiskModal();
        return;
    }

    var form           = $('.digirisk-mobile-form');
    var placeholder    = form.data('risk-comment-label') || '';
    // Only the fire permit tracks the equipment used for each type of work.
    var equipmentLabel = form.data('risk-equipment-label') || '';

    var $row = $('<div class="digirisk-mobile-risk-item"></div>').attr('data-position', position);
    $('<img class="digirisk-mobile-risk-item-photo" alt="">').attr('src', option.data('thumbnail')).attr('title', option.data('name')).appendTo($row);
    $('<input type="hidden" name="risk_category[]">').val(position).appendTo($row);
    $('<input type="text" name="risk_comment[]" class="digirisk-mobile-risk-item-comment">').attr('placeholder', placeholder).appendTo($row);
    if (equipmentLabel) {
        $('<input type="text" name="risk_equipment[]" class="digirisk-mobile-risk-item-equipment">').attr('placeholder', equipmentLabel).appendTo($row);
    }
    $('<button type="button" class="digirisk-mobile-risk-item-delete"><i class="fas fa-trash"></i></button>').appendTo($row);

    $('.digirisk-mobile-risk-list').append($row);
    window.digiriskdolibarr.mobilequickcreate.closeRiskModal();
};

/**
 * Remove a selected risk from the list.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.removeRisk = function() {
    $(this).closest('.digirisk-mobile-risk-item').remove();
};

/**
 * Open the protection picker modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.openProtectionModal = function() {
    $('.digirisk-mobile-protection-modal').removeClass('hidden');
};

/**
 * Close the protection picker modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.closeProtectionModal = function() {
    $('.digirisk-mobile-protection-modal').addClass('hidden');
};

/**
 * Add the picked protection (EPI) to the selected list, with a mandatory checkbox.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.addProtection = function() {
    var option   = $(this);
    var position = String(option.data('position'));

    // Avoid adding the same protection twice.
    var exists = false;
    $('.digirisk-mobile-protection-item').each(function() {
        if (String($(this).attr('data-position')) === position) {
            exists = true;
        }
    });
    if (exists) {
        window.digiriskdolibarr.mobilequickcreate.closeProtectionModal();
        return;
    }

    var form            = $('.digirisk-mobile-form');
    var placeholder     = form.data('risk-comment-label') || '';
    var mandatoryLabel  = form.data('mandatory-label') || '';
    var index           = window.digiriskdolibarr.mobilequickcreate.protectionIndex++;

    var $row = $('<div class="digirisk-mobile-protection-item"></div>').attr('data-position', position);
    $('<img class="digirisk-mobile-protection-item-photo" alt="">').attr('src', option.data('thumbnail')).attr('title', option.data('name')).appendTo($row);
    $('<input type="hidden" name="protection_position[' + index + ']">').val(position).appendTo($row);
    $('<input type="text" name="protection_comment[' + index + ']" class="digirisk-mobile-protection-item-comment">').attr('placeholder', placeholder).appendTo($row);

    var $mandatory = $('<label class="digirisk-mobile-protection-item-mandatory"></label>');
    $('<input type="checkbox" value="1">').attr('name', 'protection_mandatory[' + index + ']').appendTo($mandatory);
    $('<span></span>').text(mandatoryLabel).appendTo($mandatory);
    $mandatory.appendTo($row);

    $('<button type="button" class="digirisk-mobile-protection-item-delete"><i class="fas fa-trash"></i></button>').appendTo($row);

    $('.digirisk-mobile-protection-list').append($row);
    window.digiriskdolibarr.mobilequickcreate.closeProtectionModal();
};

/**
 * Remove a selected protection from the list.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.removeProtection = function() {
    $(this).closest('.digirisk-mobile-protection-item').remove();
};

/**
 * Add the certification currently selected in the picker to the list.
 *
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.addCertification = function() {
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

    var index          = window.digiriskdolibarr.mobilequickcreate.certIndex++;
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
window.digiriskdolibarr.mobilequickcreate.removeCertification = function() {
    $(this).closest('.digirisk-mobile-cert-item').remove();
};

/**
 * Guard the submit: require a saved signature and valid dates.
 *
 * @param  {Event} event Submit event
 * @return {void}
 */
window.digiriskdolibarr.mobilequickcreate.submitForm = function(event) {
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

    if (!window.digiriskdolibarr.mobilequickcreate.validateDatesForSubmit()) {
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
            window.digiriskdolibarr.mobilequickcreate.showFormErrors((resp && resp.errors && resp.errors.length) ? resp.errors : ['KO']);
            resetSubmit();
        },
        error: function() {
            window.digiriskdolibarr.mobilequickcreate.showFormErrors(['KO']);
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
window.digiriskdolibarr.mobilequickcreate.showFormErrors = function(errors) {
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
    window.digiriskdolibarr.mobilequickcreate.init();
});
