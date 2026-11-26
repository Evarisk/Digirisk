/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/modules/riskAssessmentDocumentGeneration.js
 * \ingroup digiriskdolibarr
 * \brief   Generation modal of the risk assessment document
 *          (core/tpl/digiriskdocuments/digiriskdolibarr_riskassessmentdocument_generation_modal.tpl.php), opened by
 *          the form of the documents box instead of submitting it at once.
 *
 *          With the ZIP archive option, the sheet of each groupment and workunit is generated one request each
 *          (core/ajax/riskassessmentdocument_generation.php), its line telling where it stands. The form is then
 *          submitted with the names of these sheets, that the archive takes as they are instead of generating them
 *          again. The first error stops everything: the risk assessment document is never generated with a sheet
 *          missing from its archive.
 */

'use strict';

window.digiriskdolibarr.riskAssessmentDocumentGeneration = {};

/**
 * Whether the user asked to stop: the sheet being generated is finished, the next ones are not started.
 *
 * @type {boolean}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.stopRequested = false;

/**
 * Icon of a line of the list, by state.
 *
 * @type {Object<string, string>}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.icons = {
    pending: 'far fa-circle',
    running: 'fas fa-spinner fa-spin',
    done: 'fas fa-check-circle',
    error: 'fas fa-times-circle',
    skipped: 'fas fa-minus-circle'
};

/**
 * Init the generation modal, where the page of the risk assessment document renders it.
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.init = function() {
    if (!$('#riskAssessmentDocumentGenerationModal').length) {
        return;
    }

    window.digiriskdolibarr.riskAssessmentDocumentGeneration.event();
};

/**
 * Bind the form of the documents box and the buttons of the modal.
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.event = function() {
    // The submission rather than the click on the button: Enter in the model selector submits the form too
    $(document).on('submit', '#builddoc_form', window.digiriskdolibarr.riskAssessmentDocumentGeneration.start);
    $(document).on('click', '#riskAssessmentDocumentGenerationModal .risk-assessment-document-generation__stop', window.digiriskdolibarr.riskAssessmentDocumentGeneration.stop);
    $(document).on('click', '#riskAssessmentDocumentGenerationModal .risk-assessment-document-generation__close', window.digiriskdolibarr.riskAssessmentDocumentGeneration.close);

    // Back to the page from the next one: the browser may restore it as it was left, modal open on the last step
    $(window).on('pageshow', function(event) {
        if (event.originalEvent && event.originalEvent.persisted) {
            window.digiriskdolibarr.riskAssessmentDocumentGeneration.close();
        }
    });
};

/**
 * Open the modal and run the generation: the sheets one by one, then the form of the box.
 *
 * @param  {Event} event Submission of the form of the documents box
 * @return {Promise<void>}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.start = async function(event) {
    const generation = window.digiriskdolibarr.riskAssessmentDocumentGeneration;
    const $form      = $(this);
    const $modal     = $('#riskAssessmentDocumentGenerationModal');

    event.preventDefault();
    if ($modal.hasClass('modal-active')) {
        return;
    }

    // Saturne and Digirisk each put a loader on the documents box at the click: the modal shows the progress now
    const $box = $form.find('.div-table-responsive-no-min');
    window.saturne.loader.remove($box);
    $box.children('.loader-spin').remove();

    generation.stopRequested = false;
    generation.reset($modal);
    $modal.addClass('modal-active');

    let elements = [];
    if (parseInt($modal.attr('data-archive'), 10) === 1) {
        generation.setStatus($modal, $modal.attr('data-trans-listing'));
        try {
            const data = await generation.request($modal, {action: 'list'});
            elements   = Array.isArray(data.elements) ? data.elements : [];
        } catch (message) {
            generation.finish($form, $modal, 'failed', message);
            return;
        }
    }

    const $sheetSteps   = elements.map(element => generation.addStep($modal, element));
    const $documentStep = generation.addStep($modal, {label: $modal.attr('data-label-document'), depth: 0});
    const total         = elements.length + 1;
    const documents     = {};

    for (let index = 0; index < elements.length; index++) {
        if (generation.stopRequested) {
            generation.skipFrom($sheetSteps.slice(index).concat([$documentStep]));
            generation.finish($form, $modal, 'stopped');
            return;
        }

        const $step = $sheetSteps[index];
        generation.setStepState($step, 'running');
        generation.scrollTo($modal, $step);
        generation.setStatus($modal, generation.format($modal.attr('data-trans-sheets'), [index + 1, elements.length]));

        try {
            const data = await generation.request($modal, {action: 'generate', id: elements[index].id, lang_id: $form.find('[name="lang_id"]').val() || ''});

            documents[elements[index].id] = data.file;
            generation.setStepState($step, 'done');
            $('<a>', {class: 'risk-assessment-document-generation__step-file', href: data.url, target: '_blank', rel: 'noopener'}).text(data.file).appendTo($step);
            generation.setProgress($modal, (index + 1) / total);
        } catch (message) {
            generation.setStepState($step, 'error');
            $('<span>', {class: 'risk-assessment-document-generation__step-error'}).text(message).appendTo($step);
            generation.skipFrom($sheetSteps.slice(index + 1).concat([$documentStep]));
            generation.finish($form, $modal, 'failed');
            return;
        }
    }

    if (generation.stopRequested) {
        generation.skipFrom([$documentStep]);
        generation.finish($form, $modal, 'stopped');
        return;
    }

    generation.submit($form, $modal, $documentStep, documents);
};

/**
 * Submit the form of the documents box with the sheets just generated. The page that comes back lists the new files
 * and tells whether the generation went well.
 *
 * @param  {jQuery}                 $form         Form of the documents box
 * @param  {jQuery}                 $modal        Generation modal
 * @param  {jQuery}                 $documentStep Line of the risk assessment document
 * @param  {Object<string, string>} documents     Name of the generated sheet, by element id
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.submit = function($form, $modal, $documentStep, documents) {
    const generation = window.digiriskdolibarr.riskAssessmentDocumentGeneration;

    // Past this point the request runs on the server to its end: nothing left to stop
    $modal.find('.risk-assessment-document-generation__stop').prop('hidden', true);
    generation.setStepState($documentStep, 'running');
    generation.scrollTo($modal, $documentStep);
    generation.setStatus($modal, $modal.attr('data-trans-document'));

    $form.find('input[name^="digiriskelementdocuments["]').remove();
    Object.keys(documents).forEach(function(id) {
        $('<input>', {type: 'hidden', name: 'digiriskelementdocuments[' + id + ']', value: documents[id]}).appendTo($form);
    });

    // The native submission does not trigger the submit event again, and works even if a field is named "submit"
    HTMLFormElement.prototype.submit.call($form[0]);
};

/**
 * Post a call to the generation endpoint.
 *
 * @param  {jQuery} $modal Generation modal, holding the URL of the endpoint
 * @param  {Object} data   Parameters of the call
 * @return {Promise<Object>} Answer of the endpoint, rejected with the message to show
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.request = function($modal, data) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: $modal.attr('data-url'),
            type: 'POST',
            dataType: 'json',
            data: $.extend({token: window.saturne.toolbox.getToken()}, data)
        }).done(function(response) {
            if (response && !response.error) {
                resolve(response);
            } else {
                reject(response && response.error ? response.error : window.digiriskdolibarr.riskAssessmentDocumentGeneration.format($modal.attr('data-trans-unexpected'), [200]));
            }
        }).fail(function(xhr) {
            const response = xhr.responseJSON;
            reject(response && response.error ? response.error : window.digiriskdolibarr.riskAssessmentDocumentGeneration.format($modal.attr('data-trans-unexpected'), [xhr.status]));
        });
    });
};

/**
 * Ask to stop after the sheet being generated.
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.stop = function() {
    const $modal = $('#riskAssessmentDocumentGenerationModal');

    window.digiriskdolibarr.riskAssessmentDocumentGeneration.stopRequested = true;
    $(this).prop('disabled', true);
    window.digiriskdolibarr.riskAssessmentDocumentGeneration.setStatus($modal, $modal.attr('data-trans-stopping'));
};

/**
 * Close the modal, once the generation stopped or failed.
 *
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.close = function() {
    $('#riskAssessmentDocumentGenerationModal').removeClass('modal-active');
};

/**
 * Empty the modal of the previous generation.
 *
 * @param  {jQuery} $modal Generation modal
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.reset = function($modal) {
    $modal.removeClass('risk-assessment-document-generation--failed risk-assessment-document-generation--stopped');
    $modal.find('.risk-assessment-document-generation__steps').empty();
    $modal.find('.risk-assessment-document-generation__stop').prop('disabled', false).prop('hidden', false);
    $modal.find('.risk-assessment-document-generation__close').prop('hidden', true);
    window.digiriskdolibarr.riskAssessmentDocumentGeneration.setStatus($modal, '');
    window.digiriskdolibarr.riskAssessmentDocumentGeneration.setProgress($modal, 0);
};

/**
 * Add a line to the list.
 *
 * @param  {jQuery} $modal  Generation modal
 * @param  {Object} element Element of the line: ref, label and depth in the tree
 * @return {jQuery}         The line
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.addStep = function($modal, element) {
    const $step = $('<li>', {class: 'risk-assessment-document-generation__step'});

    $step[0].style.setProperty('--depth', String(parseInt(element.depth, 10) || 0));
    $('<i>', {class: 'risk-assessment-document-generation__step-icon', 'aria-hidden': 'true'}).appendTo($step);
    if (element.ref) {
        $('<span>', {class: 'risk-assessment-document-generation__step-ref'}).text(element.ref).appendTo($step);
    }
    $('<span>', {class: 'risk-assessment-document-generation__step-label'}).text(element.label || '').appendTo($step);

    window.digiriskdolibarr.riskAssessmentDocumentGeneration.setStepState($step, 'pending');

    return $step.appendTo($modal.find('.risk-assessment-document-generation__steps'));
};

/**
 * Set the state of a line: pending, running, done, error or skipped.
 *
 * @param  {jQuery} $step Line of the list
 * @param  {string} state New state
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.setStepState = function($step, state) {
    const icons = window.digiriskdolibarr.riskAssessmentDocumentGeneration.icons;

    Object.keys(icons).forEach(function(knownState) {
        $step.removeClass('risk-assessment-document-generation__step--' + knownState);
    });
    $step.addClass('risk-assessment-document-generation__step--' + state);
    $step.find('.risk-assessment-document-generation__step-icon').attr('class', 'risk-assessment-document-generation__step-icon ' + icons[state]);
};

/**
 * Mark as not generated the lines the generation will not reach.
 *
 * @param  {jQuery[]} $steps Lines of the list
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.skipFrom = function($steps) {
    $steps.forEach($step => window.digiriskdolibarr.riskAssessmentDocumentGeneration.setStepState($step, 'skipped'));
};

/**
 * End a generation that did not reach the risk assessment document, and let the modal be closed.
 *
 * @param  {jQuery} $form   Form of the documents box
 * @param  {jQuery} $modal  Generation modal
 * @param  {string} outcome 'stopped' or 'failed'
 * @param  {string} detail  Optional message of the error, when no line carries it
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.finish = function($form, $modal, outcome, detail) {
    const $button = $form.find('#builddoc_generatebutton');
    let message   = $modal.attr('data-trans-' + outcome);
    if (detail) {
        message += ' - ' + detail;
    }

    // The page stays: the button Saturne greyed out at the click must start a new generation
    window.saturne.loader.remove($button);
    $button.removeClass('button-disable').addClass('button-blue');

    $modal.addClass('risk-assessment-document-generation--' + outcome);
    window.digiriskdolibarr.riskAssessmentDocumentGeneration.setStatus($modal, message);
    $modal.find('.risk-assessment-document-generation__stop').prop('hidden', true);
    $modal.find('.risk-assessment-document-generation__close').prop('hidden', false).trigger('focus');
};

/**
 * Set the line of text above the progress bar.
 *
 * @param  {jQuery} $modal  Generation modal
 * @param  {string} message Text to show
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.setStatus = function($modal, message) {
    $modal.find('.risk-assessment-document-generation__status').text(message);
};

/**
 * Fill the progress bar.
 *
 * @param  {jQuery} $modal Generation modal
 * @param  {number} ratio  Part done, from 0 to 1
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.setProgress = function($modal, ratio) {
    const percent = Math.round(Math.min(Math.max(ratio, 0), 1) * 100);

    $modal.find('.risk-assessment-document-generation__bar').attr('aria-valuenow', percent);
    $modal.find('.risk-assessment-document-generation__bar-value').css('width', percent + '%');
};

/**
 * Scroll the list so the line being generated stays in sight, however many groupments and workunits come before it.
 *
 * @param  {jQuery} $modal Generation modal
 * @param  {jQuery} $step  Line to show
 * @return {void}
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.scrollTo = function($modal, $step) {
    const list = $modal.find('.risk-assessment-document-generation__steps')[0];
    const step = $step[0];

    // The list is positioned (SCSS): the offset of the line is taken from its top
    list.scrollTop = step.offsetTop - (list.clientHeight - step.offsetHeight) / 2;
};

/**
 * Replace the %s of a translation, in order.
 *
 * @param  {string}               text   Translation
 * @param  {Array<string|number>} values Values of the %s
 * @return {string}                      Text to show
 */
window.digiriskdolibarr.riskAssessmentDocumentGeneration.format = function(text, values) {
    let index = 0;

    return String(text || '').replace(/%s/g, () => (index < values.length ? String(values[index++]) : ''));
};
