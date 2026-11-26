<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/digiriskdocuments/digiriskdolibarr_riskassessmentdocument_generation_modal.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Generation modal of the risk assessment document, opened by the button of the documents box
 *          (js/modules/riskAssessmentDocumentGeneration.js). With the ZIP archive option, the sheet of each groupment
 *          and workunit is generated one request each (core/ajax/riskassessmentdocument_generation.php), then the form
 *          of the box generates the document. The lines of the list are written by the JS.
 *
 * Variables expected:
 *   $langs
 *   $generationUrl  string URL of core/ajax/riskassessmentdocument_generation.php
 *   $archiveEnabled int    1 when the ZIP archive option is on
 */

if (empty($langs) || empty($generationUrl)) {
    print 'Error: missing variables for digiriskdolibarr_riskassessmentdocument_generation_modal.tpl.php';
    return;
}
?>

<div class="wpeo-modal modal-close-only-with-button risk-assessment-document-generation" id="riskAssessmentDocumentGenerationModal"
     role="dialog" aria-modal="true" aria-labelledby="riskAssessmentDocumentGenerationTitle"
     data-url="<?= dol_escape_htmltag($generationUrl) ?>"
     data-archive="<?= (int) $archiveEnabled ?>"
     data-models-without-archive="<?= dol_escape_htmltag(json_encode(RiskAssessmentDocument::MODELS_WITHOUT_ARCHIVE)) ?>"
     data-label-document="<?= dol_escape_htmltag($langs->trans($archiveEnabled ? 'RiskAssessmentDocumentGenerationArchive' : 'RiskAssessmentDocument')) ?>"
     data-label-document-alone="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocument')) ?>"
     data-trans-listing="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationListing')) ?>"
     data-trans-sheets="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationSheets', '%s', '%s')) ?>"
     data-trans-document="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationDocument')) ?>"
     data-trans-stopping="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationStopping')) ?>"
     data-trans-stopped="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationStopped')) ?>"
     data-trans-failed="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationFailed')) ?>"
     data-trans-unexpected="<?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationUnexpected', '%s')) ?>">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title" id="riskAssessmentDocumentGenerationTitle"><?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerated')) ?></h2>
        </div>
        <div class="modal-content">
            <p class="risk-assessment-document-generation__status" aria-live="polite"></p>
            <div class="risk-assessment-document-generation__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <span class="risk-assessment-document-generation__bar-value"></span>
            </div>
            <ul class="risk-assessment-document-generation__steps"></ul>
        </div>
        <div class="modal-footer">
            <button type="button" class="wpeo-button button-grey risk-assessment-document-generation__stop"><?= dol_escape_htmltag($langs->trans('RiskAssessmentDocumentGenerationStop')) ?></button>
            <button type="button" class="wpeo-button button-blue risk-assessment-document-generation__close" hidden><?= dol_escape_htmltag($langs->trans('CloseWindowShort')) ?></button>
        </div>
    </div>
</div>
